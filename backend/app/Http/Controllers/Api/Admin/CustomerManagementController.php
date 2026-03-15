<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
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
            });

        if ($request->filled('status')) {
            $query->where('customer_verification_status', $request->input('status'));
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

        $user->update([
            'customer_verification_required' => true,
            'customer_verification_status' => 'pending',
            'customer_verification_triggered_at' => now(),
            'customer_verification_reviewed_by' => auth()->id(),
            'customer_verification_reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Customer verification required',
            'data' => $user->fresh('role'),
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

        $count = User::whereIn('id', $validated['ids'])
            ->update([
                'customer_verification_required' => true,
                'customer_verification_status' => 'pending',
                'customer_verification_triggered_at' => now(),
                'customer_verification_reviewed_by' => auth()->id(),
                'customer_verification_reviewed_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Customers flagged for verification',
            'updated' => $count,
        ]);
    }
}
