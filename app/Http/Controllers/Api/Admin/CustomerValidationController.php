<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Models\Customer\CustomerVerificationDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerValidationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!auth()->user()->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $status = $request->get('status', 'pending');

        $query = User::with(['role', 'customerVerificationDocuments'])
            ->whereHas('role', function ($q) {
                $q->whereIn('name', ['customer', 'customer_user', 'client']);
            })
            ->with('customer');

        if ($status === 'pending') {
            $query->whereHas('customer', fn($q) => $q->where('verification_status', 'pending'));
        } elseif ($status === 'verified') {
            $query->whereHas('customer', fn($q) => $q->where('verification_status', 'verified'));
        } elseif ($status === 'rejected') {
            $query->whereHas('customer', fn($q) => $q->where('verification_status', 'rejected'));
        } elseif ($status === 'unverified') {
            $query->where(function ($q) {
                $q->whereDoesntHave('customer')
                  ->orWhereHas('customer', fn($sq) => $sq->where('verification_status', 'unverified'));
            });
        }

        $customers = $query->latest()->paginate($request->per_page ?? 20);
        $customers->setCollection(
            $this->attachDocuments($customers->getCollection())
        );

        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }

    public function show(int $id): JsonResponse
    {
        if (!auth()->user()->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $user = User::with(['role', 'customerVerificationDocuments', 'customer'])
            ->whereHas('role', function ($q) {
                $q->whereIn('name', ['customer', 'customer_user', 'client']);
            })
            ->findOrFail($id);

        $user->documents = $this->mapDocuments($user);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function review(Request $request, int $id): JsonResponse
    {
        if (!auth()->user()->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'rejection_reason' => 'nullable|string|max:500'
        ]);

        $user = User::with(['customer', 'customerVerificationDocuments'])->findOrFail($id);
        $action = (string) $validated['action'];
        $reviewedAt = now();

        DB::transaction(function () use ($user, $validated, $action, $reviewedAt): void {
            $customer = $user->customer()->firstOrCreate(
                ['user_id' => $user->id],
                ['verification_status' => 'unverified']
            );

            if ($action === 'approve') {
                $payload = [
                    'verification_status' => 'verified',
                    'verification_required' => false,
                    'verification_rejection_reason' => null,
                    'verification_reviewed_by' => auth()->id(),
                    'verification_reviewed_at' => $reviewedAt,
                ];
                $customer->update($payload);
                \App\Models\Customer\Customer::query()
                    ->where('user_id', $user->id)
                    ->update($payload);

                CustomerVerificationDocument::query()
                    ->where('user_id', $user->id)
                    ->update([
                        'status' => 'approved',
                        'rejection_reason' => null,
                        'reviewed_by' => auth()->id(),
                        'reviewed_at' => $reviewedAt,
                        'updated_at' => $reviewedAt,
                    ]);

                $this->notify((int) $user->id, [
                    'module' => 'ecommerce',
                    'entity_type' => 'customer_verification',
                    'action' => 'approved',
                    'title' => 'Verification Approved',
                    'message' => 'Your customer verification has been approved. You can now continue checkout.',
                    'link' => '/shop/profile?section=verification',
                    'severity' => 'success',
                ]);
            } else {
                $rejectionReason = $validated['rejection_reason'] ?? null;

                $payload = [
                    'verification_status' => 'rejected',
                    'verification_required' => false,
                    'verification_rejection_reason' => $rejectionReason,
                    'verification_reviewed_by' => auth()->id(),
                    'verification_reviewed_at' => $reviewedAt,
                ];
                $customer->update($payload);
                \App\Models\Customer\Customer::query()
                    ->where('user_id', $user->id)
                    ->update($payload);

                CustomerVerificationDocument::query()
                    ->where('user_id', $user->id)
                    ->update([
                        'status' => 'rejected',
                        'rejection_reason' => $rejectionReason,
                        'reviewed_by' => auth()->id(),
                        'reviewed_at' => $reviewedAt,
                        'updated_at' => $reviewedAt,
                    ]);

                $this->notify((int) $user->id, [
                    'module' => 'ecommerce',
                    'entity_type' => 'customer_verification',
                    'action' => 'rejected',
                    'title' => 'Verification Rejected',
                    'message' => $rejectionReason
                        ? 'Your verification was rejected: ' . $rejectionReason
                        : 'Your verification was rejected. Please resubmit your documents.',
                    'link' => '/shop/profile?section=verification',
                    'severity' => 'warn',
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => $action === 'approve' ? 'Customer approved' : 'Customer rejected',
            'data' => $user->fresh(['role', 'customer', 'customerVerificationDocuments']),
        ]);
    }

    public function serveDocument(Request $request, CustomerVerificationDocument $document): StreamedResponse|JsonResponse
    {
        if (!auth()->user()->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
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

    private function attachDocuments(Collection $customers): Collection
    {
        return $customers->map(function (User $user) {
            $user->documents = $this->mapDocuments($user);
            return $user;
        });
    }

    private function mapDocuments(User $user): array
    {
        return $user->customerVerificationDocuments->map(function ($doc) {
            return [
                'name' => $doc->original_filename ?: $doc->getDocumentTypeLabel(),
                'document_type' => $doc->document_type,
                'id_type' => $doc->id_type,
                'id_number' => $doc->id_number,
                'status' => $doc->status,
                'path' => $doc->file_path,
                'url' => url("/api/admin/customer-validations/documents/{$doc->id}/serve"),
                'reviewed_at' => $doc->reviewed_at,
            ];
        })->values()->all();
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
