<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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

        $user = User::with('customer')->findOrFail($id);
        $customer = $user->customer()->firstOrCreate(
            ['user_id' => $user->id],
            ['verification_status' => 'unverified']
        );

        if ($validated['action'] === 'approve') {
            $customer->update([
                'verification_status' => 'verified',
                'verification_required' => false,
                'verification_rejection_reason' => null,
                'verification_reviewed_by' => auth()->id(),
                'verification_reviewed_at' => now(),
            ]);
        } else {
            $customer->update([
                'verification_status' => 'rejected',
                'verification_required' => false,
                'verification_rejection_reason' => $validated['rejection_reason'] ?? null,
                'verification_reviewed_by' => auth()->id(),
                'verification_reviewed_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $validated['action'] === 'approve' ? 'Customer approved' : 'Customer rejected',
            'data' => $user->fresh(['role', 'customer']),
        ]);
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
                'status' => $doc->status,
                'path' => $doc->file_path,
                'reviewed_at' => $doc->reviewed_at,
            ];
        })->values()->all();
    }
}
