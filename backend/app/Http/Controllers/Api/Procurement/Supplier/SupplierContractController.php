<?php
// backend/app/Http/Controllers/Procurement/Supplier/SupplierContractController.php

namespace App\Http\Controllers\Api\Procurement\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Supplier\SupplierContract;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SupplierContractController extends Controller
{
    /**
     * List all supplier contracts
     * GET /api/procurement/contracts
     */
    public function index(Request $request): JsonResponse
    {
        $query = SupplierContract::with(['supplier', 'createdBy'])
            ->where('store_id', auth()->user()->store_id);

        // Filters
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('contract_type')) {
            $query->where('contract_type', $request->contract_type);
        }

        if ($request->has('active')) {
            $query->active();
        }

        if ($request->has('expiring_soon')) {
            $days = $request->get('days', 30);
            $query->expiringSoon($days);
        }

        $contracts = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $contracts,
        ]);
    }

    /**
     * Show single contract
     * GET /api/procurement/contracts/{id}
     */
    public function show(int $id): JsonResponse
    {
        $contract = SupplierContract::with(['supplier', 'createdBy'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $contract,
        ]);
    }

    /**
     * Create new contract
     * POST /api/procurement/contracts
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'contract_title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'contract_type' => 'required|in:fixed_price,volume_discount,consignment,exclusive',
            'minimum_order_value' => 'nullable|numeric|min:0',
            'payment_terms_days' => 'required|integer|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'terms_conditions' => 'nullable|string',
            'contract_file_path' => 'nullable|string',
        ]);

        // Generate contract number
        $lastContract = SupplierContract::latest()->first();
        $contractNumber = 'CON-' . date('Y') . '-' . str_pad(($lastContract?->id ?? 0) + 1, 3, '0', STR_PAD_LEFT);

        $validated['contract_number'] = $contractNumber;
        $validated['store_id'] = auth()->user()->store_id;
        $validated['status'] = 'draft';
        $validated['created_by'] = auth()->id();

        $contract = SupplierContract::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Supplier contract created successfully',
            'data' => $contract->load('supplier'),
        ], 201);
    }

    /**
     * Update contract
     * PUT /api/procurement/contracts/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $contract = SupplierContract::findOrFail($id);

        $validated = $request->validate([
            'contract_title' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'contract_type' => 'nullable|in:fixed_price,volume_discount,consignment,exclusive',
            'minimum_order_value' => 'nullable|numeric|min:0',
            'payment_terms_days' => 'nullable|integer|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'terms_conditions' => 'nullable|string',
            'contract_file_path' => 'nullable|string',
            'status' => 'nullable|in:draft,active,expired,terminated',
        ]);

        $contract->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Contract updated successfully',
            'data' => $contract->fresh(),
        ]);
    }

    /**
     * Activate contract
     * POST /api/procurement/contracts/{id}/activate
     */
    public function activate(int $id): JsonResponse
    {
        $contract = SupplierContract::findOrFail($id);

        if ($contract->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft contracts can be activated',
            ], 422);
        }

        $contract->update(['status' => 'active']);

        return response()->json([
            'success' => true,
            'message' => 'Contract activated successfully',
            'data' => $contract,
        ]);
    }

    /**
     * Terminate contract
     * POST /api/procurement/contracts/{id}/terminate
     */
    public function terminate(Request $request, int $id): JsonResponse
    {
        $contract = SupplierContract::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $contract->update([
            'status' => 'terminated',
            'terms_conditions' => ($contract->terms_conditions ?? '') . "\n\nTermination reason: " . $validated['reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contract terminated successfully',
        ]);
    }

    /**
     * Get expiring contracts
     * GET /api/procurement/contracts/expiring
     */
    public function expiring(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);

        $contracts = SupplierContract::with('supplier')
            ->where('store_id', auth()->user()->store_id)
            ->expiringSoon($days)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $contracts,
        ]);
    }

    /**
     * Delete contract
     * DELETE /api/procurement/contracts/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $contract = SupplierContract::findOrFail($id);

        if ($contract->status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete active contract',
            ], 422);
        }

        $contract->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contract deleted successfully',
        ]);
    }
}