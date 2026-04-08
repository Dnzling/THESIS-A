<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Models\Store\Store;
use App\Models\Store\StoreVerification;
use App\Services\Store\DocumentAutoValidationService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StoreVerificationController extends Controller
{
    public function __construct(
        private readonly DocumentAutoValidationService $documentAutoValidationService
    ) {
    }

    private const DOC_SPECS = [
        'business_registration_file' => [
            'label' => "Mayor's Permit",
            'required' => true,
            'allowed_mimes' => ['application/pdf', 'image/jpeg', 'image/png'],
            'max_kb' => 5120,
        ],
        'business_permit_file' => [
            'label' => 'Business Permit',
            'required' => true,
            'allowed_mimes' => ['application/pdf', 'image/jpeg', 'image/png'],
            'max_kb' => 5120,
        ],
        'tax_certificate_file' => [
            'label' => 'Tax Certificate',
            'required' => true,
            'allowed_mimes' => ['application/pdf', 'image/jpeg', 'image/png'],
            'max_kb' => 5120,
        ],
    ];

    public function submitDocuments(Request $request, Store $store)
    {
        try {
            // Check if user owns this store
            // First, you need to add user_id to stores table or have another way to link
            // For now, let's assume the authenticated user can submit

            $validated = $request->validate([
                'business_registration_number' => ['nullable', 'string', 'max:100'],
                'business_registration_date' => 'required|date|before_or_equal:today',
                'business_registration_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'business_permit_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'tax_certificate_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'other_documents' => 'nullable|array',
                'other_documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);

            $legitimacyIssues = $this->validateUploadedDocuments($request);
            if (!empty($legitimacyIssues)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some uploaded documents failed validation checks.',
                    'errors' => $legitimacyIssues,
                ], 422);
            }

            // Upload files
            $uploads = [];
            $fileFields = [
                'business_registration_file',
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
            if ($request->hasFile('other_documents')) {
                $otherDocs = [];
                foreach ($request->file('other_documents') as $file) {
                    $path = $file->store("store-verifications/{$store->id}/other", 'public');
                    $otherDocs[] = $path;
                }
                $uploads['other_documents'] = $otherDocs;
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

            // Use valid DB enum status; "pending" means submitted/under review.
            $store->update(['status' => 'pending']);

            return response()->json([
                'success' => true,
                'message' => 'Verification documents submitted successfully. Your store is now under review.',
                'data' => [
                    'verification' => $verification->load('store'),
                    'documents' => $this->buildDocumentPayload($verification),
                ],
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

    public function getDocuments(Store $store)
    {
        try {
            $user = Auth::user();
            $isSuperAdmin = (bool) $user?->hasRole('super_admin');
            $isStoreOwner = (int) ($user?->store_id ?? 0) === (int) $store->id;
            if (!$isSuperAdmin && !$isStoreOwner) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $verification = StoreVerification::where('store_id', $store->id)->latest('submitted_at')->first();
            if (!$verification) {
                return response()->json([
                    'success' => false,
                    'message' => 'No verification documents submitted yet.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $this->buildDocumentPayload($verification),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch verification documents.',
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

            $workflowStatus = 'pending';
            if ($verification) {
                if (!is_null($verification->reviewed_at) && !is_null($verification->rejection_reason)) {
                    $workflowStatus = 'rejected';
                } elseif (!is_null($verification->reviewed_at)) {
                    $workflowStatus = 'approved';
                } else {
                    $workflowStatus = 'reviewing';
                }
            }

            $status = [
                'store_status' => $workflowStatus,
                'raw_store_status' => $store->status,
                'is_verified' => $workflowStatus === 'approved',
                'is_under_review' => $workflowStatus === 'reviewing',
                'is_pending' => $workflowStatus === 'pending',
                'is_rejected' => $workflowStatus === 'rejected',
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

        $verifications->getCollection()->transform(function (StoreVerification $verification) {
            $payload = $this->buildDocumentPayload($verification);
            $verification->setAttribute('documents_summary', $payload['summary']);
            return $verification;
        });

        return response()->json([
            'success' => true,
            'data' => $verifications
        ]);
    }

    /**
     * Admin: Get verifications by status
     * GET /api/store-verifications?status=pending|approved|rejected|all
     */
    public function index(Request $request)
    {
        if (!Auth::user()->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $status = $request->get('status', 'pending');

        $query = StoreVerification::with(['store', 'reviewer:id,fname,lname,email'])
            ->latest('submitted_at');

        if ($status === 'pending') {
            $query->whereNull('reviewed_at');
        } elseif ($status === 'approved') {
            $query->whereNotNull('reviewed_at')->whereNull('rejection_reason');
        } elseif ($status === 'rejected') {
            $query->whereNotNull('reviewed_at')->whereNotNull('rejection_reason');
        }

        $verifications = $query->paginate($request->per_page ?? 20);

        $verifications->getCollection()->transform(function (StoreVerification $verification) {
            $payload = $this->buildDocumentPayload($verification);
            $verification->setAttribute('documents_summary', $payload['summary']);
            return $verification;
        });

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
            'rejection_reason' => 'required_if:action,reject|string|max:500',
        ]);

        $documentPayload = $this->buildDocumentPayload($verification);
        if ($validated['action'] === 'approve' && !$documentPayload['summary']['can_approve']) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot approve verification. Required documents are missing or invalid.',
                'data' => $documentPayload,
            ], 422);
        }

        if ($validated['action'] === 'approve') {
            // Start transaction
            DB::transaction(function () use ($verification) {
                // 1. Update verification as approved
                $verification->update([
                    'reviewed_at' => now(),
                    'reviewed_by' => Auth::id(),
                    'rejection_reason' => null
                ]);

                // 2. Update store status using valid enum values.
                $store = $verification->store;
                $store->update(['status' => 'active']);

                // 3. Find store owner user by store email and link to store.
                $storeOwner = User::where('email', $store->email)->first();

                if ($storeOwner) {
                    $storeAdminRoleId = (int) (\App\Models\Core\Role::query()->where('name', 'store_admin')->value('id') ?? 2);
                    $defaultBranchId = (int) (\App\Models\Store\Branch::query()
                        ->where('store_id', (int) $store->id)
                        ->orderByDesc('is_main_branch')
                        ->orderBy('id')
                        ->value('id') ?? 0);

                    $storeOwner->update([
                        'store_id' => $store->id,
                        'role_id' => $storeAdminRoleId,
                        'branch_id' => $storeOwner->branch_id ?: ($defaultBranchId ?: null),
                    ]);

                    // 5. Check if employee record already exists
                    $existingEmployee = \App\Models\Hr\Employee::where('user_id', $storeOwner->id)->first();

                    if (!$existingEmployee) {
                        if ($defaultBranchId <= 0) {
                            throw new \RuntimeException('Cannot create store admin employee profile: no branch exists for the store.');
                        }

                        // 6. Create employee record for store owner
                        \App\Models\Hr\Employee::create([
                            'user_id' => $storeOwner->id,
                            'store_id' => $store->id,
                            'branch_id' => $defaultBranchId,
                            'role_id' => $storeAdminRoleId,
                            'employee_number' => \App\Models\Hr\Employee::generateEmployeeNumber($storeAdminRoleId),
                            'fname' => (string) $storeOwner->fname,
                            'lname' => (string) $storeOwner->lname,
                            'department' => 'Management',
                            'employment_type' => 'full_time',
                            'status' => 'active',
                            'hire_date' => now()->toDateString(),
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

                // 2. Keep store in pending state after rejection.
                $verification->store->update(['status' => 'pending']);
            });

            $message = 'Store verification rejected';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'verification' => $verification->fresh(['store', 'reviewer']),
                'documents' => $this->buildDocumentPayload($verification->fresh()),
            ],
        ]);
    }

    public function inspectDocument(Request $request, StoreVerification $verification, string $document)
    {
        if (!Auth::user()?->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $documentInfo = $this->resolveDocument($verification, $document, (int) $request->query('index', -1));
        if (!$documentInfo) {
            return response()->json(['success' => false, 'message' => 'Document not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => array_merge($documentInfo, [
                'auto_validation' => $this->documentAutoValidationService->validateDocument(
                    (string) $documentInfo['path'],
                    $document,
                    $documentInfo['index']
                ),
            ]),
        ]);
    }

    public function autoValidateDocument(Request $request, StoreVerification $verification, string $document)
    {
        if (!Auth::user()?->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $documentInfo = $this->resolveDocument($verification, $document, (int) $request->query('index', -1));
        if (!$documentInfo) {
            return response()->json(['success' => false, 'message' => 'Document not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->documentAutoValidationService->validateDocument(
                (string) $documentInfo['path'],
                $document,
                $documentInfo['index']
            ),
        ]);
    }

    public function autoValidateAllDocuments(StoreVerification $verification)
    {
        if (!Auth::user()?->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $payload = $this->buildDocumentPayload($verification);
        $results = [];
        foreach ($payload['documents'] as $document) {
            if (empty($document['path'])) {
                continue;
            }
            $results[] = [
                'key' => $document['key'],
                'label' => $document['label'],
                'index' => $document['index'],
                'validation' => $this->documentAutoValidationService->validateDocument(
                    (string) $document['path'],
                    (string) $document['key'],
                    isset($document['index']) ? (int) $document['index'] : null
                ),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'verification_id' => $verification->id,
                'results' => $results,
            ],
        ]);
    }

    public function downloadDocument(Request $request, StoreVerification $verification, string $document)
    {
        $user = Auth::user();
        $isSuperAdmin = (bool) $user?->hasRole('super_admin');
        $isStoreOwner = (int) ($user?->store_id ?? 0) === (int) $verification->store_id;
        if (!$isSuperAdmin && !$isStoreOwner) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $documentInfo = $this->resolveDocument($verification, $document, (int) $request->query('index', -1));
        if (!$documentInfo || empty($documentInfo['path']) || !Storage::disk('public')->exists($documentInfo['path'])) {
            return response()->json(['success' => false, 'message' => 'Document file not found'], 404);
        }

        return Storage::disk('public')->download($documentInfo['path'], basename($documentInfo['path']));
    }

    private function validateUploadedDocuments(Request $request): array
    {
        $issues = [];
        foreach (self::DOC_SPECS as $field => $spec) {
            if (!$request->hasFile($field)) {
                continue;
            }

            $file = $request->file($field);
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $fileIssues = $this->runFileChecks($file, $spec['allowed_mimes'], (int) $spec['max_kb']);
            if (!empty($fileIssues)) {
                $issues[$field] = $fileIssues;
            }
        }

        if ($request->hasFile('other_documents')) {
            foreach ((array) $request->file('other_documents') as $index => $file) {
                if (!$file instanceof UploadedFile) {
                    continue;
                }
                $fileIssues = $this->runFileChecks($file, ['application/pdf', 'image/jpeg', 'image/png'], 5120);
                if (!empty($fileIssues)) {
                    $issues["other_documents.$index"] = $fileIssues;
                }
            }
        }

        return $issues;
    }

    private function runFileChecks(UploadedFile $file, array $allowedMimes, int $maxKb): array
    {
        $issues = [];
        $sizeKb = (int) ceil($file->getSize() / 1024);
        $mime = strtolower((string) $file->getMimeType());
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $name = strtolower((string) $file->getClientOriginalName());

        if ($sizeKb <= 0) {
            $issues[] = 'File appears empty.';
        }
        if ($sizeKb > $maxKb) {
            $issues[] = "File exceeds {$maxKb}KB limit.";
        }
        if (!in_array($mime, $allowedMimes, true)) {
            $issues[] = 'File MIME type is not allowed.';
        }
        if (str_contains($name, '..') || preg_match('/[<>:"|?*]/', $name)) {
            $issues[] = 'File name contains invalid characters.';
        }
        if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            $issues[] = 'File extension is not allowed.';
        }

        return $issues;
    }

    private function buildDocumentPayload(StoreVerification $verification): array
    {
        $documents = [];
        foreach (self::DOC_SPECS as $field => $spec) {
            $path = $verification->{$field};
            $documents[] = $this->buildDocumentItem(
                $verification,
                $field,
                $spec['label'],
                $spec['required'],
                $path,
                $spec['allowed_mimes'],
                (int) $spec['max_kb']
            );
        }

        $otherDocuments = is_array($verification->other_documents) ? $verification->other_documents : [];
        foreach ($otherDocuments as $index => $path) {
            $documents[] = $this->buildDocumentItem(
                $verification,
                'other_documents',
                'Other Document ' . ($index + 1),
                false,
                (string) $path,
                ['application/pdf', 'image/jpeg', 'image/png'],
                5120,
                $index
            );
        }

        $requiredDocs = array_filter($documents, fn($doc) => (bool) $doc['required']);
        $requiredSubmitted = array_filter($requiredDocs, fn($doc) => (bool) $doc['submitted']);
        $requiredValid = array_filter($requiredDocs, fn($doc) => (bool) $doc['is_valid']);

        return [
            'summary' => [
                'required_total' => count($requiredDocs),
                'required_submitted' => count($requiredSubmitted),
                'required_valid' => count($requiredValid),
                'invalid_count' => count(array_filter($documents, fn($doc) => !$doc['is_valid'] && $doc['submitted'])),
                'can_approve' => count($requiredDocs) > 0 && count($requiredDocs) === count($requiredValid),
            ],
            'documents' => $documents,
        ];
    }

    private function buildDocumentItem(
        StoreVerification $verification,
        string $key,
        string $label,
        bool $required,
        ?string $path,
        array $allowedMimes,
        int $maxKb,
        ?int $index = null
    ): array {
        $disk = Storage::disk('public');
        $submitted = filled($path);
        $exists = $submitted ? $disk->exists((string) $path) : false;
        $sizeKb = $exists ? (int) ceil($disk->size((string) $path) / 1024) : null;
        $mime = $exists ? strtolower((string) ($disk->mimeType((string) $path) ?: '')) : null;
        $issues = [];

        if ($required && !$submitted) {
            $issues[] = 'Required document not submitted.';
        }
        if ($submitted && !$exists) {
            $issues[] = 'File path does not exist in storage.';
        }
        if ($exists && $sizeKb !== null && $sizeKb > $maxKb) {
            $issues[] = "File exceeds {$maxKb}KB limit.";
        }
        if ($exists && $mime && !in_array($mime, $allowedMimes, true)) {
            $issues[] = 'Unexpected MIME type for this document.';
        }

        return [
            'key' => $key,
            'label' => $label,
            'index' => $index,
            'required' => $required,
            'submitted' => $submitted,
            'path' => $path,
            'exists' => $exists,
            'size_kb' => $sizeKb,
            'mime_type' => $mime,
            'is_valid' => $submitted ? empty($issues) : !$required,
            'issues' => $issues,
            'download_url' => $submitted
                ? url("/api/store-verification/{$verification->id}/documents/{$key}/download" . ($index !== null ? "?index={$index}" : ''))
                : null,
            'inspect_url' => $submitted
                ? url("/api/store-verification/{$verification->id}/documents/{$key}/inspect" . ($index !== null ? "?index={$index}" : ''))
                : null,
        ];
    }

    private function resolveDocument(StoreVerification $verification, string $document, int $index = -1): ?array
    {
        if ($document === 'other_documents') {
            $otherDocs = is_array($verification->other_documents) ? $verification->other_documents : [];
            if (!isset($otherDocs[$index])) {
                return null;
            }

            return [
                'key' => $document,
                'index' => $index,
                'path' => (string) $otherDocs[$index],
            ];
        }

        if (!array_key_exists($document, self::DOC_SPECS)) {
            return null;
        }

        return [
            'key' => $document,
            'index' => null,
            'path' => (string) ($verification->{$document} ?? ''),
        ];
    }
}
