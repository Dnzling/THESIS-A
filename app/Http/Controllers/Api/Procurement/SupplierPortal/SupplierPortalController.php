<?php

namespace App\Http\Controllers\Api\Procurement\SupplierPortal;

use App\Http\Controllers\Controller;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use App\Models\Procurement\SupplierPortal\SupplierVerificationDocument;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Store\Store;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SupplierPortalController extends Controller
{
    /**
     * Get supplier portal details (current logged-in supplier)
     * GET /api/supplier-portal/my-portal
     */
    public function getMyPortal(Request $request): JsonResponse
    {
        $user = auth()->user();
        $portal = SupplierPortal::where('user_id', $user->id)
            ->with(['supplier', 'verificationDocuments', 'rfqFeedbacks', 'poFeedbacks'])
            ->first();

        if (!$portal) {
            return response()->json([
                'success' => false,
                'message' => 'No portal found. Please register as a supplier.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $portal,
        ]);
    }

    /**
     * Update portal coordinates (supplier profile)
     * PUT /api/supplier-portal/coordinates
     */
    public function updateCoordinates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $user = auth()->user();
        $portal = SupplierPortal::where('user_id', $user->id)->firstOrFail();

        $portal->update([
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location updated.',
            'data' => $portal,
        ]);
    }

    /**
     * Register as a supplier
     * POST /api/supplier-portal/register
     */
    public function register(Request $request): JsonResponse
    {
        // Accept legacy `company_name` from older frontends by mapping it to `supplier_name`
        if (!$request->has('supplier_name') && $request->has('company_name')) {
            $request->merge(['supplier_name' => $request->company_name]);
        }

        $validator = Validator::make($request->all(), [
            'supplier_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'tin' => 'nullable|string|max:50',
            'payment_terms' => 'required|in:cash_on_delivery,net_7,net_15,net_30,net_60,advance_payment',
            'supplier_type' => 'required|in:raw_materials,furniture,accessories,services',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = auth()->user();
            $storeId = $user->store_id ?? Store::query()->value('id');

            if (!$storeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to determine store for supplier registration.',
                ], 422);
            }

            // Check if supplier portal already exists
            $existingPortal = SupplierPortal::where('user_id', $user->id)->first();
            if ($existingPortal && $existingPortal->status === 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have an approved supplier portal.',
                ], 409);
            }

            $portal = $existingPortal ?: new SupplierPortal(['user_id' => $user->id]);

            // Create or update supplier record
            $supplierTypeMap = [
                'raw_materials' => 'manufacturer',
                'furniture' => 'wholesaler',
                'accessories' => 'distributor',
                'services' => 'local_artisan',
            ];

            $paymentTermMap = [
                'cash_on_delivery' => 'cash_on_delivery',
                'net_7' => 'net_7',
                'net_15' => 'net_15',
                'net_30' => 'net_30',
                'net_60' => 'net_60',
                'advance_payment' => 'advance_payment',
            ];

            $paymentTerm = $paymentTermMap[$request->payment_terms] ?? 'net_30';
            $supplierType = $supplierTypeMap[$request->supplier_type] ?? 'wholesaler';

            $supplier = null;
            if ($portal->supplier_id) {
                $supplier = Supplier::withTrashed()->find($portal->supplier_id);
                if ($supplier && $supplier->trashed()) {
                    $supplier->restore();
                }
            }

            if (!$supplier) {
                $supplierCode = $this->generateSupplierCode();
                $supplier = Supplier::create([
                    'store_id' => $storeId,
                    'supplier_code' => $supplierCode,
                    'supplier_name' => $request->supplier_name,
                    'company_name' => $request->supplier_name,
                    'contact_person' => $request->contact_person,
                    'email' => $user->email,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'city' => $request->city,
                    'province' => $request->province,
                    'postal_code' => $request->postal_code,
                    'country' => $request->country,
                    'tin' => $request->tin,
                    'supplier_type' => $supplierType,
                    'payment_terms' => $paymentTerm,
                    'status' => 'inactive',
                ]);
            } else {
                $supplier->update([
                    'supplier_name' => $request->supplier_name,
                    'company_name' => $request->supplier_name,
                    'contact_person' => $request->contact_person,
                    'email' => $user->email,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'city' => $request->city,
                    'province' => $request->province,
                    'postal_code' => $request->postal_code,
                    'country' => $request->country,
                    'tin' => $request->tin,
                    'supplier_type' => $supplierType,
                    'payment_terms' => $paymentTerm,
                ]);
            }

            $portal->supplier_id = $supplier->id;
            // Persist submission metadata on portal but keep contact details on `suppliers` table
            $portal->status = 'pending';
            $portal->rejection_reason = null;
            $portal->last_submission_at = now();
            $portal->resubmission_count = ($portal->resubmission_count ?? 0) + 1;
            $portal->save();

            $portal->load(['supplier', 'verificationDocuments']);

            return response()->json([
                'success' => true,
                'message' => 'Supplier portal created. Please upload verification documents.',
                'data' => $portal,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error registering supplier: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function generateSupplierCode(): string
    {
        $prefix = 'SUPP-' . date('Y') . '-';
        $latest = Supplier::where('supplier_code', 'like', $prefix . '%')
            ->orderBy('supplier_code', 'desc')
            ->first();

        $nextNumber = 1;
        if ($latest && preg_match('/^' . preg_quote($prefix, '/') . '(\\d+)$/', $latest->supplier_code, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        }

        return $prefix . str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Upload verification document
     * POST /api/supplier-portal/documents
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'document_type' => 'required|in:business_license,tax_id,company_registration,bank_details',
            'file' => 'required|file|max:5120', // 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = auth()->user();
            $portal = SupplierPortal::where('user_id', $user->id)->firstOrFail();

            $file = $request->file('file');
            $path = 'supplier-documents/' . $portal->id . '/' . $request->document_type;
            
            // Store file
            $filename = $file->store($path, 'private');
            
            // Create or update document record
            SupplierVerificationDocument::updateOrCreate(
                [
                    'supplier_portal_id' => $portal->id,
                    'document_type' => $request->document_type,
                ],
                [
                    'file_path' => $filename,
                    'original_filename' => $file->getClientOriginalName(),
                    'file_mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'status' => 'pending',
                ]
            );

            // Update portal submission time
            $portal->update([
                'last_submission_at' => now(),
                'resubmission_count' => $portal->resubmission_count + 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully.',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error uploading document: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get supplier's verification documents
     * GET /api/supplier-portal/my-documents
     */
    public function getMyDocuments(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $portal = SupplierPortal::where('user_id', $user->id)->firstOrFail();

            $documents = $portal->verificationDocuments()
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $documents,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No supplier portal found for current user.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching documents: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download verification document
     * GET /api/supplier-portal/documents/{id}/download
     */
    public function downloadDocument($id): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        try {
            $user = auth()->user();
            $document = SupplierVerificationDocument::findOrFail($id);

            // Verify authorization
            if ($document->supplierPortal->user_id !== $user->id) {
                abort(403);
            }

            return Storage::disk('private')->download($document->file_path, $document->original_filename);
        } catch (\Exception $e) {
            abort(404);
        }
    }

    /**
     * Get portal dashboard stats
     * GET /api/supplier-portal/stats
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $portal = SupplierPortal::where('user_id', $user->id)->firstOrFail();

            $stats = [
                'portal_status' => $portal->status,
                'is_verified' => $portal->isVerified(),
                'total_rfqs' => $portal->rfqFeedbacks()->count(),
                'total_pos' => $portal->poFeedbacks()->count(),
                'pending_pos' => $portal->poFeedbacks()->where('receipt_status', 'pending')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching stats: ' . $e->getMessage(),
            ], 500);
        }
    }
}
