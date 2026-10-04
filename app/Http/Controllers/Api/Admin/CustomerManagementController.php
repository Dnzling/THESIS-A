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

        $query = User::with(['role'])
            ->whereHas('role', function ($q) {
                $q->whereIn('name', ['customer', 'customer_user', 'client']);
            })
            ->with('customer');

        if ($request->filled('status')) {
            $status = (string) $request->input('status');
            $query->where('is_active', $status === 'active');
        }

        $customers = $query->latest()->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    public function show(User $customer): JsonResponse
    {
        if (!auth()->user()->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $customer->load(['role', 'customer']);

        if (!$customer->role || !in_array($customer->role->name, ['customer', 'customer_user', 'client'], true)) {
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $customer]);
    }

}
