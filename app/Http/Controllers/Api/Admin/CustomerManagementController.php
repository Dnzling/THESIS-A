<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use App\Models\Core\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!auth()->user()->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $query = User::with(['role', 'customerVerificationDocuments'])
            ->whereHas('role', function ($q) {
                $q->whereIn('name', ['customer', 'customer_user', 'client']);
            })
            ->with('customer');

        if ($request->filled('status')) {
            $status = (string) $request->input('status');
            if ($status === 'unverified') {
                $query->where(function ($q) {
                    $q->whereDoesntHave('customer')
                        ->orWhereHas('customer', fn($sq) => $sq->where('verification_status', 'unverified'));
                });
            } else {
                $query->whereHas('customer', fn($q) => $q->where('verification_status', $status));
            }
        }

        $customers = $query->latest()->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    public function requireVerification(Request $request, int $id): JsonResponse
    {
        if (!auth()->user()->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);

        $customer = Customer::firstOrCreate(
            ['user_id' => $user->id],
            ['verification_status' => 'unverified']
        );
        $customer->update([
            'verification_required' => true,
            'verification_status' => 'pending',
            'verification_triggered_at' => now(),
            'verification_reviewed_by' => auth()->id(),
            'verification_reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Customer verification required',
            'data' => $user->fresh(['role', 'customer']),
        ]);
    }

    public function requireVerificationBulk(Request $request): JsonResponse
    {
        if (!auth()->user()->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:users,id',
        ]);

        $targetIds = User::whereIn('id', $validated['ids'])->pluck('id');
        $now = now();
        $payload = $targetIds->map(fn($id) => [
            'user_id' => $id,
            'verification_required' => true,
            'verification_status' => 'pending',
            'verification_triggered_at' => $now,
            'verification_reviewed_by' => auth()->id(),
            'verification_reviewed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();
        Customer::upsert(
            $payload,
            ['user_id'],
            ['verification_required', 'verification_status', 'verification_triggered_at', 'verification_reviewed_by', 'verification_reviewed_at', 'updated_at']
        );
        $count = count($payload);

        return response()->json([
            'success' => true,
            'message' => 'Customers flagged for verification',
            'updated' => $count,
        ]);
    }
}
