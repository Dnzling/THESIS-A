<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Store\Branch;
use App\Models\Store\BranchVerification;
use App\Services\Store\DocumentAutoValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BranchVerificationController extends Controller
{
    public function __construct(private readonly DocumentAutoValidationService $ocr) {}

    public function status(Request $request, Branch $branch)
    {
        $this->authorizeBranchOwner($request, $branch);
        $verification = BranchVerification::query()->where('branch_id', $branch->id)->first();

        return response()->json(['success' => true, 'data' => [
            'status' => $branch->status,
            'documents_submitted' => (bool) $verification?->submitted_at,
            'submitted_at' => $verification?->submitted_at,
            'rejection_reason' => $verification?->rejection_reason,
        ]]);
    }

    public function extract(Request $request, DocumentAutoValidationService $ocr)
    {
        abort_unless($request->user()?->hasRole('owner'), 403);
        $request->validate(['document_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240']]);
        $file = $request->file('document_file');
        $path = $file->store('branch-verifications/tmp', 'public');
        try {
            $text = $ocr->extractTextFromDocument($path);
            return response()->json(['success' => true, 'data' => [
                'reference_number' => $ocr->extractDocumentReference($text, 'other'),
                'expires_at' => $ocr->extractDocumentExpiration($text),
            ]]);
        } finally {
            Storage::disk('public')->delete($path);
        }
    }

    public function submit(Request $request, Branch $branch)
    {
        $this->authorizeBranchOwner($request, $branch);
        if (in_array($branch->status, ['active', 'pending'], true)) {
            return response()->json(['success' => false, 'message' => 'This branch already has an active or pending verification status.'], 409);
        }
        $request->validate([
            'registration_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'permit_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'additional_files' => ['nullable', 'array', 'max:5'],
            'additional_files.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'required_metadata' => ['required', 'json'],
            'additional_metadata' => ['nullable', 'json'],
        ]);

        $requiredMetadata = json_decode((string) $request->input('required_metadata'), true);
        $requiredMetadata = is_array($requiredMetadata) ? collect($requiredMetadata)->keyBy('key') : collect();
        $metadata = json_decode((string) $request->input('additional_metadata', '[]'), true);
        $metadata = is_array($metadata) ? array_values($metadata) : [];
        Validator::make($metadata, [
            '*.label' => ['nullable', 'string', 'max:255'],
            '*.reference_number' => ['nullable', 'string', 'max:100'],
            '*.expires_at' => ['nullable', 'date'],
        ])->validate();
        Validator::make($requiredMetadata->values()->all(), [
            '*.key' => ['required', 'in:registration,permit'],
            '*.reference_number' => ['nullable', 'string', 'max:100'],
            '*.expires_at' => ['nullable', 'date'],
        ])->validate();
        $fileCount = count($request->file('additional_files', []));
        if ($fileCount !== count($metadata)) {
            throw ValidationException::withMessages(['additional_files' => 'Each additional attachment needs matching review details.']);
        }

        $documents = [];
        foreach ([
            ['key' => 'registration', 'label' => 'Store Registration', 'field' => 'registration_file'],
            ['key' => 'permit', 'label' => 'Store Permit', 'field' => 'permit_file'],
        ] as $required) {
            $documentMetadata = $requiredMetadata->get($required['key'], []);
            $documents[] = [
                'key' => $required['key'],
                'label' => $required['label'],
                'required' => true,
                'reference_number' => filled($documentMetadata['reference_number'] ?? null) ? (string) $documentMetadata['reference_number'] : null,
                'expires_at' => filled($documentMetadata['expires_at'] ?? null) ? (string) $documentMetadata['expires_at'] : null,
                'path' => $request->file($required['field'])->store("branch-verifications/{$branch->id}", 'public'),
            ];
        }

        foreach ($request->file('additional_files', []) as $index => $file) {
            $meta = $metadata[$index] ?? [];
            $documents[] = [
                'key' => 'additional',
                'label' => trim((string) ($meta['label'] ?? 'Additional document')) ?: 'Additional document',
                'required' => false,
                'reference_number' => filled($meta['reference_number'] ?? null) ? (string) $meta['reference_number'] : null,
                'expires_at' => filled($meta['expires_at'] ?? null) ? (string) $meta['expires_at'] : null,
                'path' => $file->store("branch-verifications/{$branch->id}", 'public'),
            ];
        }

        $verification = DB::transaction(function () use ($branch, $documents) {
            $verification = BranchVerification::query()->updateOrCreate(
                ['branch_id' => $branch->id],
                [
                    'store_id' => $branch->store_id,
                    'documents' => $documents,
                    'rejection_reason' => null,
                    'reviewed_at' => null,
                    'reviewed_by' => null,
                    'submitted_at' => now(),
                ]
            );
            $branch->update(['status' => 'pending']);
            return $verification;
        });

        return response()->json(['success' => true, 'message' => 'Branch documents submitted for review.', 'data' => [
            'id' => $verification->id,
            'status' => $branch->fresh()->status,
        ]], 201);
    }

    public function index(Request $request)
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);
        $query = BranchVerification::query()->whereNotNull('submitted_at')->with(['branch', 'store', 'reviewer:id,fname,lname,email'])->latest('submitted_at');
        $this->applyStatus($query, (string) $request->input('status', 'pending'));
        $records = $query->limit(min(100, max(1, (int) $request->input('per_page', 100))))->get();

        return response()->json(['success' => true, 'data' => $records->map(fn (BranchVerification $record) => $this->adminPayload($record))]);
    }

    public function show(Request $request, BranchVerification $verification)
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);
        abort_unless($verification->submitted_at, 404);
        $verification->load(['branch', 'store', 'reviewer:id,fname,lname,email']);
        $payload = $this->adminPayload($verification, true);
        return response()->json(['success' => true, 'data' => [
            'verification_type' => 'branch',
            'verification' => $verification,
            'store' => $verification->store,
            'branch' => $verification->branch,
            'owner' => $payload['owner'],
            'documents' => $payload['documents'],
            'status' => $payload['status'],
            'documents_summary' => $payload['documents']['summary'],
        ]]);
    }

    public function review(Request $request, BranchVerification $verification)
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);
        abort_unless($verification->submitted_at, 404);
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'rejection_reason' => ['required_if:action,reject', 'nullable', 'string', 'max:500'],
        ]);
        DB::transaction(function () use ($verification, $validated, $request) {
            $verification->update([
                'reviewed_at' => now(),
                'reviewed_by' => $request->user()->id,
                'rejection_reason' => $validated['action'] === 'reject' ? $validated['rejection_reason'] : null,
            ]);
            $verification->branch()->update(['status' => $validated['action'] === 'approve' ? 'active' : 'rejected']);
        });

        return response()->json(['success' => true, 'message' => 'Branch verification ' . ($validated['action'] === 'approve' ? 'approved.' : 'rejected.')]);
    }

    public function document(Request $request, BranchVerification $verification, int $index, string $mode): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user?->hasRole('super_admin') || ((int) $user?->store_id === (int) $verification->store_id && $user?->hasRole('owner')), 403);
        abort_unless(in_array($mode, ['preview', 'download'], true), 404);
        $documents = $verification->documents ?? [];
        abort_unless(isset($documents[$index]['path']), 404);
        $path = (string) $documents[$index]['path'];
        abort_unless(Storage::disk('public')->exists($path), 404);
        $mime = Storage::disk('public')->mimeType($path) ?: 'application/octet-stream';
        return Storage::disk('public')->response($path, basename($path), [
            'Content-Type' => $mime,
            'Content-Disposition' => ($mode === 'download' ? 'attachment' : 'inline') . '; filename="' . basename($path) . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function adminPayload(BranchVerification $record, bool $includeDocuments = false): array
    {
        $branch = $record->branch;
        $store = $record->store;
        $documents = collect($record->documents ?? [])->values()->map(function (array $document, int $index) use ($record) {
            $path = (string) ($document['path'] ?? '');
            $exists = $path !== '' && Storage::disk('public')->exists($path);
            return [
                ...$document,
                'index' => $index,
                'submitted' => $exists,
                'exists' => $exists,
                'mime_type' => $exists ? Storage::disk('public')->mimeType($path) : null,
                'size_kb' => $exists ? (int) ceil(Storage::disk('public')->size($path) / 1024) : null,
                'is_valid' => $exists,
                'issues' => $exists ? [] : ['File not found in storage.'],
                'preview_url' => url("/api/branch-verification/{$record->id}/documents/{$index}/preview"),
                'download_url' => url("/api/branch-verification/{$record->id}/documents/{$index}/download"),
            ];
        });
        $status = !$record->reviewed_at ? 'pending' : ($record->rejection_reason ? 'rejected' : 'approved');
        return [
            'id' => $record->id,
            'verification_type' => 'branch',
            'store' => $store,
            'branch' => $branch,
            'owner' => ['fname' => $store?->owner_name, 'email' => $store?->email],
            'submitted_at' => $record->submitted_at,
            'created_at' => $record->created_at,
            'updated_at' => $record->updated_at,
            'reviewed_at' => $record->reviewed_at,
            'reviewer' => $record->reviewer,
            'rejection_reason' => $record->rejection_reason,
            'verification_status' => $status,
            'status' => $status,
            'documents_summary' => [
                'total' => $documents->count(),
                'total_submitted' => $documents->where('submitted', true)->count(),
                'required_total' => $documents->where('required', true)->count(),
                'required_submitted' => $documents->where('required', true)->where('submitted', true)->count(),
                'required_valid' => $documents->where('required', true)->where('is_valid', true)->count(),
                'invalid_count' => $documents->where('is_valid', false)->count(),
                'can_approve' => $documents->where('required', true)->count() === 2 && $documents->where('required', true)->where('is_valid', true)->count() === 2,
            ],
            'documents' => $includeDocuments ? [
                'documents' => $documents,
                'summary' => $documents->where('submitted', true)->count() > 0 ? [
                    'total' => $documents->count(),
                    'total_submitted' => $documents->where('submitted', true)->count(),
                    'required_total' => $documents->where('required', true)->count(),
                    'required_submitted' => $documents->where('required', true)->where('submitted', true)->count(),
                    'required_valid' => $documents->where('required', true)->where('is_valid', true)->count(),
                    'invalid_count' => $documents->where('is_valid', false)->count(),
                    'can_approve' => $documents->where('required', true)->count() === 2 && $documents->where('required', true)->where('is_valid', true)->count() === 2,
                ] : [],
            ] : ['summary' => [
                'total' => $documents->count(),
                'total_submitted' => $documents->where('submitted', true)->count(),
                'required_total' => $documents->where('required', true)->count(),
                'required_submitted' => $documents->where('required', true)->where('submitted', true)->count(),
            ]],
        ];
    }

    private function applyStatus($query, string $status): void
    {
        if ($status === 'pending') $query->whereNull('reviewed_at');
        elseif ($status === 'approved') $query->whereNotNull('reviewed_at')->whereNull('rejection_reason');
        elseif ($status === 'rejected') $query->whereNotNull('reviewed_at')->whereNotNull('rejection_reason');
    }

    private function authorizeBranchOwner(Request $request, Branch $branch): void
    {
        abort_unless($request->user()?->hasRole('owner') && (int) $request->user()->store_id === (int) $branch->store_id, 403);
    }
}
