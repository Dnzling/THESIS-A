<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Models\Store\Store;
use App\Models\Store\StoreVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StoreVerificationController extends Controller
{
    public function submitDocuments(Request $request, Store $store)
    {
        try {
            // Check if user owns this store
            // First, you need to add user_id to stores table or have another way to link
            // For now, let's assume the authenticated user can submit

            $validated = $request->validate([
                'business_registration_number' => 'required|string|max:100',
                'business_registration_date' => 'required|date',
                'business_registration_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'gov_id_type' => ['required', Rule::in(['sss', 'tin', 'passport', 'driver_license', 'umid', 'national_id'])],
                'gov_id_number' => 'required|string|max:50',
                'gov_id_front_file' => 'required|file|mimes:jpg,jpeg,png|max:2048',
                'gov_id_back_file' => 'required|file|mimes:jpg,jpeg,png|max:2048',
                'selfie_with_id_file' => 'required|file|mimes:jpg,jpeg,png|max:2048',
                'business_permit_file' => 'nullable|file|mimes:pdf|max:2048',
                'tax_certificate_file' => 'nullable|file|mimes:pdf|max:2048',
                'other_documents' => 'nullable|array',
                'other_documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);

            // Upload files
            $uploads = [];
            $fileFields = [
                'business_registration_file',
                'gov_id_front_file',
                'gov_id_back_file',
                'selfie_with_id_file',
                'business_permit_file',
                'tax_certificate_file'
            ];

            foreach ($fileFields as $fileField) {
                if ($request->hasFile($fileField)) {
                    $path = $request->file($fileField)->store("store-verifications/{$store->id}", 'public');
                    $uploads[$fileField] = $path;
                }
            }

            // Upload other documents if any
            if ($request->has('other_documents')) {
                $otherDocs = [];
                foreach ($request->file('other_documents') as $file) {
                    $path = $file->store("store-verifications/{$store->id}/other", 'public');
                    $otherDocs[] = $path;
                }
                $uploads['other_documents'] = json_encode($otherDocs);
            }

            // Create or update verification record
            $verification = StoreVerification::updateOrCreate(
                ['store_id' => $store->id], // Use $store->id, not $store->store_id
                array_merge(
                    $validated,
                    $uploads,
                    [
                        'submitted_at' => now(),
                        'business_registration_date' => $validated['business_registration_date']
                    ]
                )
            );

            // Update store status to under_review
            $store->update(['status' => 'under_review']);

            return response()->json([
                'success' => true,
                'message' => 'Verification documents submitted successfully. Your store is now under review.',
                'data' => $verification->load('store')
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Store verification submission error: ' . $e->getMessage(), [
                'store_id' => $store->id ?? 'unknown',
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit verification documents',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get verification status for a store
     */
    public function getStatus(Store $store)
    {
        try {
            // Load verification data
            $store->load(['verification' => function ($query) {
                $query->latest();
            }]);

            $verification = $store->verification;

            $status = [
                'store_status' => $store->status,
                'is_verified' => $store->status === 'verified',
                'is_under_review' => $store->status === 'under_review',
                'is_pending' => $store->status === 'pending',
                'is_rejected' => $store->status === 'rejected',
                'submitted_at' => $verification->submitted_at ?? null,
                'reviewed_at' => $verification->reviewed_at ?? null,
                'rejection_reason' => $verification->rejection_reason ?? null,
                'reviewed_by' => $verification->reviewed_by ?? null,
                'documents_submitted' => $verification ? true : false
            ];

            return response()->json([
                'success' => true,
                'data' => $status,
                'message' => 'Verification status retrieved'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get verification status'
            ], 500);
        }
    }

    /**
     * Admin: Get all pending verifications
     */
    public function getPendingVerifications(Request $request)
    {
        // Only super admin or admin
        if (!Auth::user()->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $verifications = StoreVerification::whereNull('reviewed_at')
            ->with(['store', 'reviewer:id,fname,lname,email'])
            ->latest('submitted_at')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $verifications
        ]);
    }

    /**
     * Admin: Review verification
     */
    public function reviewVerification(Request $request, StoreVerification $verification)
    {
        // Only super admin
        if (!Auth::user()->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'rejection_reason' => 'required_if:action,reject|string|max:500'
        ]);

        if ($validated['action'] === 'approve') {
            // Start transaction
            DB::transaction(function () use ($verification) {
                // 1. Update verification as approved
                $verification->update([
                    'reviewed_at' => now(),
                    'reviewed_by' => Auth::id(),
                    'rejection_reason' => null
                ]);

                // 2. Update store status to verified
                $store = $verification->store;
                $store->update(['status' => 'verified']);

                // 3. Find user by store email (store owner)
                $storeCreate = Store::find($verification->store_id);

                if ($storeCreate) {
                    // 4. Link user to store and set as store_admin
                    $storeCreate->update([
                        'store_id' => $store->id,
                        'role_id' => 2 // store_admin role ID
                    ]);

                    // 5. Check if employee record already exists
                    $existingEmployee = \App\Models\Hr\Employee::where('user_id', $storeOwner->id)->first();

                    if (!$existingEmployee) {
                        // 6. Create employee record for store owner
                        \App\Models\Hr\Employee::create([
                            'user_id' => $storeCreate->id,
                            'store_id' => $store->id,
                            'employee_number' => 'ST' . str_pad($store->id, 3, '0', STR_PAD_LEFT) . 'ADM001',
                            'department' => 'Management',
                            'employment_type' => 'full_time',
                            'status' => 'active',
                            'hire_date' => now(),
                        ]);
                    }
                }
            });

            $message = 'Store verification approved';
        } else {
            DB::transaction(function () use ($verification, $validated) {
                // 1. Update verification as rejected
                $verification->update([
                    'reviewed_at' => now(),
                    'reviewed_by' => Auth::id(),
                    'rejection_reason' => $validated['rejection_reason']
                ]);

                // 2. Update store status to rejected
                $verification->store->update(['status' => 'rejected']);
            });

            $message = 'Store verification rejected';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $verification->fresh(['store', 'reviewer'])
        ]);
    }
}
