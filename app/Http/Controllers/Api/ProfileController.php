<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Core\ActivityLog;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerVerificationDocument;
use App\Models\Hr\Employee;
use App\Mail\OtpVerificationMail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    private const ALLOWED_CUSTOMER_ID_TYPES = [
        'national_id',
        'sss',
        'philhealth',
        'passport',
        'drivers_license',
        'postal_id',
        'umid',
        'voters_id',
    ];

    public function show(Request $request)
    {
        $user = $request->user();
        $employee = Employee::with('user', 'user.branch')
            ->where('user_id', $user->id)
            ->where('store_id', $user->store_id)
            ->first();
        $customer = Customer::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();
        $verificationDocuments = CustomerVerificationDocument::query()
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'employee' => $employee,
                'customer' => $customer,
                'verification_documents' => $verificationDocuments->map(fn (CustomerVerificationDocument $doc) => $this->formatVerificationDocument($doc))->values(),
            ]
        ]);
    }

    public function verificationStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = Customer::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        if (!$customer) {
            $customer = Customer::query()->create([
                'user_id' => $user->id,
                'verification_status' => 'unverified',
            ]);
        }

        $documents = CustomerVerificationDocument::query()
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (CustomerVerificationDocument $doc) => $this->formatVerificationDocument($doc))
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => $customer,
                'documents' => $documents,
                'allowed_id_types' => self::ALLOWED_CUSTOMER_ID_TYPES,
            ],
        ]);
    }

    public function submitVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'id_type' => ['required', Rule::in(self::ALLOWED_CUSTOMER_ID_TYPES)],
            'id_number' => 'required|string|max:120',
            'primary_id_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'selfie_with_id_file' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $customer = Customer::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        if (!$customer) {
            $customer = Customer::query()->create([
                'user_id' => $user->id,
                'verification_status' => 'unverified',
            ]);
        }

        $this->upsertVerificationDocument($user->id, 'primary_id', $validated['id_type'], $validated['id_number'], $request->file('primary_id_file'));
        $this->upsertVerificationDocument($user->id, 'selfie_with_id', $validated['id_type'], $validated['id_number'], $request->file('selfie_with_id_file'));

        $customer->update([
            'verification_required' => true,
            'verification_status' => 'pending',
            'verification_rejection_reason' => null,
            'verification_reviewed_by' => null,
            'verification_reviewed_at' => null,
            'verification_triggered_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Verification documents submitted successfully. Your account is now pending review.',
            'data' => [
                'customer' => $customer->fresh(),
                'documents' => CustomerVerificationDocument::query()
                    ->where('user_id', $user->id)
                    ->orderByDesc('updated_at')
                    ->get()
                    ->map(fn (CustomerVerificationDocument $doc) => $this->formatVerificationDocument($doc))
                    ->values(),
            ],
        ]);
    }

    public function serveVerificationDocument(Request $request, CustomerVerificationDocument $document): StreamedResponse|JsonResponse
    {
        $user = $request->user();
        if ((int) $document->user_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized document access.',
            ], 403);
        }

        $resolved = $this->resolveReadableDocumentPath((string) $document->file_path);
        if (!$resolved) {
            return response()->json([
                'success' => false,
                'message' => 'Document file not found.',
            ], 404);
        }

        [$driver, $path] = $resolved;
        if ($driver === 'absolute') {
            return response()->file($path);
        }

        return Storage::disk($driver)->response($path, $document->original_filename ?: basename($path));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'fname' => 'sometimes|string|max:100',
            'lname' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'birthday' => 'nullable|date',
            'contact_number' => 'nullable|string|max:30',
            'phone_number' => 'nullable|string|max:30',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'barangay' => 'nullable|string|max:150',
            'gender' => 'nullable|in:male,female,other',
            'emergency_contact_name' => 'nullable|string|max:120',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'emergency_contact_relationship' => 'nullable|string|max:60',
        ]);

        $user->fill([
            'fname' => $validated['fname'] ?? $user->fname,
            'lname' => $validated['lname'] ?? $user->lname,
            'email' => $validated['email'] ?? $user->email,
            'birthday' => $validated['birthday'] ?? $user->birthday,
        ]);

        $requiresVerification = false;
        if (array_key_exists('email', $validated) && $user->isDirty('email')) {
            $user->email_verified_at = null;
            $requiresVerification = true;
        }

        $user->save();

        $contactNumber = $validated['contact_number']
            ?? $validated['phone_number']
            ?? $validated['phone']
            ?? null;

        if (!is_null($contactNumber)) {
            $latestCustomer = Customer::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->first();

            if (!$latestCustomer) {
                $latestCustomer = Customer::query()->create([
                    'user_id' => $user->id,
                    'verification_status' => 'unverified',
                ]);
            }

            $latestCustomer->update([
                'contact_number' => $contactNumber,
            ]);
        }

        if ($requiresVerification) {
            $otp = $user->generateOtp();
            Mail::to($user->email)->send(new OtpVerificationMail($otp, $user->fname));
        }

        $employee = Employee::where('user_id', $user->id)->first();
        if ($employee) {
            $phoneValue = $contactNumber ?? $employee->phone;
            $employee->fill([
                'fname' => $validated['fname'] ?? $employee->fname,
                'lname' => $validated['lname'] ?? $employee->lname,
                'phone' => $phoneValue,
                'address' => $validated['address'] ?? $employee->address,
                'province' => $validated['province'] ?? $employee->province,
                'city' => $validated['city'] ?? $employee->city,
                'barangay' => $validated['barangay'] ?? $employee->barangay,
                'date_of_birth' => $validated['birthday'] ?? $employee->date_of_birth,
                'gender' => $validated['gender'] ?? $employee->gender,
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? $employee->emergency_contact_name,
                'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? $employee->emergency_contact_phone,
                'emergency_contact_relationship' => $validated['emergency_contact_relationship'] ?? $employee->emergency_contact_relationship,
            ]);
            $employee->save();
        }

        ActivityLog::record(
            'profile.update',
            'Updated profile information',
            ['updated_fields' => array_keys($validated)],
            'User',
            $user->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'requires_verification' => $requiresVerification,
            'data' => [
                'user' => $user->fresh(),
                'employee' => $employee?->fresh(),
                'customer' => Customer::query()
                    ->where('user_id', $user->id)
                    ->latest('id')
                    ->first(),
            ]
        ]);
    }

    public function updateAvatar(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Avatar upload not implemented'
        ], 501);
    }

    public function removeAvatar(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Avatar removal not implemented'
        ], 501);
    }

    private function upsertVerificationDocument(int $userId, string $documentType, string $idType, string $idNumber, $uploadedFile): void
    {
        $existing = CustomerVerificationDocument::query()
            ->where('user_id', $userId)
            ->where('document_type', $documentType)
            ->first();

        if ($existing?->file_path && Storage::disk('public')->exists($existing->file_path)) {
            Storage::disk('public')->delete($existing->file_path);
        }

        $directory = "customer-verification/{$userId}/{$documentType}";
        $storedPath = $uploadedFile->store($directory, 'public');

        CustomerVerificationDocument::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'document_type' => $documentType,
            ],
            [
                'id_type' => $idType,
                'id_number' => $idNumber,
                'file_path' => $storedPath,
                'original_filename' => $uploadedFile->getClientOriginalName(),
                'file_mime_type' => $uploadedFile->getClientMimeType(),
                'file_size' => (int) $uploadedFile->getSize(),
                'status' => 'pending',
                'rejection_reason' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]
        );
    }

    private function formatVerificationDocument(CustomerVerificationDocument $doc): array
    {
        return [
            'id' => $doc->id,
            'document_type' => $doc->document_type,
            'document_type_label' => $doc->getDocumentTypeLabel(),
            'id_type' => $doc->id_type,
            'id_number' => $doc->id_number,
            'status' => $doc->status,
            'rejection_reason' => $doc->rejection_reason,
            'original_filename' => $doc->original_filename,
            'file_size' => $doc->file_size,
            'file_url' => $doc->file_path ? url("/api/profile/verification/documents/{$doc->id}/serve") : null,
            'updated_at' => optional($doc->updated_at)->toDateTimeString(),
        ];
    }

    private function resolveReadableDocumentPath(string $rawPath): ?array
    {
        $rawPath = trim($rawPath);
        if ($rawPath === '') {
            return null;
        }

        $candidates = [
            ltrim($rawPath, '/'),
            preg_replace('#^storage/#', '', ltrim($rawPath, '/')),
        ];

        foreach ($candidates as $candidate) {
            if (!$candidate) {
                continue;
            }

            if (Storage::disk('public')->exists($candidate)) {
                return ['public', $candidate];
            }

            if (Storage::disk('local')->exists($candidate)) {
                return ['local', $candidate];
            }

            $publicStoragePath = public_path('storage/' . $candidate);
            if (is_file($publicStoragePath)) {
                return ['absolute', $publicStoragePath];
            }

            $publicDirectPath = public_path($candidate);
            if (is_file($publicDirectPath)) {
                return ['absolute', $publicDirectPath];
            }
        }

        return null;
    }
}
