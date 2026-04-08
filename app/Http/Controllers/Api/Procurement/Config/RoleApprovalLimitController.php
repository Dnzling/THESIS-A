<?php
// backend/app/Http/Controllers/Procurement/Config/RoleApprovalLimitController.php

namespace App\Http\Controllers\Api\Procurement\Config;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Config\RoleApprovalLimit;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoleApprovalLimitController extends Controller
{
    /**
     * List all role approval limits
     * GET /api/procurement/role-limits
     */
    public function index(): JsonResponse
    {
        $limits = RoleApprovalLimit::with('role')
            ->where('store_id', auth()->user()->store_id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $limits,
        ]);
    }

    /**
     * Show role approval limit
     * GET /api/procurement/role-limits/{id}
     */
    public function show(int $id): JsonResponse
    {
        $limit = RoleApprovalLimit::with('role')
            ->where('store_id', auth()->user()->store_id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $limit,
        ]);
    }

    /**
     * Get approval limit for specific role
     * GET /api/procurement/role-limits/role/{roleId}
     */
    public function getByRole(int $roleId): JsonResponse
    {
        $limit = RoleApprovalLimit::with('role')
            ->where('store_id', auth()->user()->store_id)
            ->where('role_id', $roleId)
            ->first();

        if (!$limit) {
            return response()->json([
                'success' => false,
                'message' => 'No approval limit found for this role',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $limit,
        ]);
    }

    /**
     * Create role approval limit
     * POST /api/procurement/role-limits
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'max_approval_amount' => 'required|numeric|min:0',
            'can_approve_rfq' => 'nullable|boolean',
            'can_create_po' => 'nullable|boolean',
            'can_approve_po' => 'nullable|boolean',
            'can_approve_transfers' => 'nullable|boolean',
            'requires_dual_approval' => 'nullable|boolean',
            'can_manage_suppliers' => 'nullable|boolean',
            'can_negotiate_prices' => 'nullable|boolean',
        ]);

        // Check if limit already exists for this role
        $existing = RoleApprovalLimit::where('store_id', auth()->user()->store_id)
            ->where('role_id', $validated['role_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Approval limit already exists for this role',
            ], 422);
        }

        $validated['store_id'] = auth()->user()->store_id;

        $limit = RoleApprovalLimit::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Role approval limit created successfully',
            'data' => $limit->load('role'),
        ], 201);
    }

    /**
     * Update role approval limit
     * PUT /api/procurement/role-limits/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $limit = RoleApprovalLimit::where('store_id', auth()->user()->store_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'max_approval_amount' => 'nullable|numeric|min:0',
            'can_approve_rfq' => 'nullable|boolean',
            'can_create_po' => 'nullable|boolean',
            'can_approve_po' => 'nullable|boolean',
            'can_approve_transfers' => 'nullable|boolean',
            'requires_dual_approval' => 'nullable|boolean',
            'can_manage_suppliers' => 'nullable|boolean',
            'can_negotiate_prices' => 'nullable|boolean',
        ]);

        $limit->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Role approval limit updated successfully',
            'data' => $limit->fresh('role'),
        ]);
    }

    /**
     * Check if role can approve amount
     * POST /api/procurement/role-limits/check
     */
    public function checkApproval(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $limit = RoleApprovalLimit::where('store_id', auth()->user()->store_id)
            ->where('role_id', $validated['role_id'])
            ->first();

        if (!$limit) {
            return response()->json([
                'success' => false,
                'message' => 'No approval limit configured for this role',
                'can_approve' => false,
            ]);
        }

        $canApprove = $limit->canApproveAmount($validated['amount']);

        return response()->json([
            'success' => true,
            'data' => [
                'can_approve' => $canApprove,
                'max_approval_amount' => $limit->max_approval_amount,
                'amount_requested' => $validated['amount'],
                'difference' => $limit->max_approval_amount - $validated['amount'],
            ],
        ]);
    }

    /**
     * Delete role approval limit
     * DELETE /api/procurement/role-limits/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $limit = RoleApprovalLimit::where('store_id', auth()->user()->store_id)
            ->findOrFail($id);

        $limit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role approval limit deleted successfully',
        ]);
    }
}