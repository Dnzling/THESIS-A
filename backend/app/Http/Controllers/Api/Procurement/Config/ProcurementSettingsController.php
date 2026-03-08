<?php
// backend/app/Http/Controllers/Procurement/Config/ProcurementSettingsController.php

namespace App\Http\Controllers\Api\Procurement\Config;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Config\ProcurementSettings;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProcurementSettingsController extends Controller
{
    /**
     * Get procurement settings for store
     * GET /api/procurement/settings
     */
    public function show(): JsonResponse
    {
        $settings = ProcurementSettings::where('store_id', auth()->user()->store_id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update procurement settings
     * PUT /api/procurement/settings
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rfq_policy' => 'nullable|in:always,never,amount_based,contract_based',
            'rfq_threshold_amount' => 'nullable|numeric|min:0',
            'rfq_minimum_suppliers' => 'nullable|integer|min:1',
            'rfq_response_days' => 'nullable|integer|min:1',
            'rfq_skip_if_contract' => 'nullable|boolean',
            'approval_tiers' => 'nullable|array',
            'approval_tiers.*.max_amount' => 'required|numeric|min:0',
            'approval_tiers.*.level' => 'required|integer|min:1',
            'approval_tiers.*.approvers' => 'required|array',
            'allow_branch_overrides' => 'nullable|boolean',
            'transfer_approval_policy' => 'nullable|in:sender_only,both_branches,finance_required,auto_approve',
            'transfer_cost_method' => 'nullable|in:none,distance_based,manual_entry,fixed_fee,value_percentage',
            'transfer_fixed_fee' => 'nullable|numeric|min:0',
            'transfer_cost_per_km' => 'nullable|numeric|min:0',
            'transfer_value_percentage' => 'nullable|numeric|min:0|max:100',
            'transfer_approval_threshold' => 'nullable|numeric|min:0',
            'auto_evaluate_suppliers' => 'nullable|boolean',
            'min_orders_for_rating' => 'nullable|integer|min:1',
            'default_payment_terms' => 'nullable|in:cash_on_delivery,net_7,net_15,net_30,net_60',
        ]);

        $settings = ProcurementSettings::where('store_id', auth()->user()->store_id)
            ->firstOrFail();

        $settings->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Procurement settings updated successfully',
            'data' => $settings->fresh(),
        ]);
    }

    /**
     * Get default approval tiers
     * GET /api/procurement/settings/default-tiers
     */
    public function defaultTiers(): JsonResponse
    {
        $defaultTiers = [
            [
                'max_amount' => 20000,
                'level' => 1,
                'approvers' => ['warehouse_manager'],
            ],
            [
                'max_amount' => 100000,
                'level' => 2,
                'approvers' => ['warehouse_manager', 'branch_manager'],
            ],
            [
                'max_amount' => 500000,
                'level' => 3,
                'approvers' => ['warehouse_manager', 'finance_manager'],
            ],
            [
                'max_amount' => 999999999,
                'level' => 4,
                'approvers' => ['warehouse_manager', 'finance_manager', 'store_admin'],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $defaultTiers,
        ]);
    }

    /**
     * Test RFQ requirement for amount
     * POST /api/procurement/settings/test-rfq
     */
    public function testRfq(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'has_contract' => 'nullable|boolean',
        ]);

        $settings = ProcurementSettings::where('store_id', auth()->user()->store_id)
            ->firstOrFail();

        $requiresRfq = $settings->shouldRequireRFQ(
            $validated['amount'],
            $validated['has_contract'] ?? false
        );

        $approvalTier = $settings->getApprovalTierForAmount($validated['amount']);

        return response()->json([
            'success' => true,
            'data' => [
                'requires_rfq' => $requiresRfq,
                'approval_tier' => $approvalTier,
                'rfq_policy' => $settings->rfq_policy,
                'rfq_threshold_amount' => $settings->rfq_threshold_amount,
            ],
        ]);
    }

    /**
     * Calculate transfer cost
     * POST /api/procurement/settings/calculate-transfer-cost
     */
    public function calculateTransferCost(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'distance_km' => 'nullable|numeric|min:0',
            'goods_value' => 'nullable|numeric|min:0',
        ]);

        $settings = ProcurementSettings::where('store_id', auth()->user()->store_id)
            ->firstOrFail();

        $cost = $settings->calculateTransferCost(
            $validated['distance_km'] ?? null,
            $validated['goods_value'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => [
                'transfer_cost' => $cost,
                'cost_method' => $settings->transfer_cost_method,
                'calculation_details' => [
                    'distance_km' => $validated['distance_km'] ?? null,
                    'goods_value' => $validated['goods_value'] ?? null,
                    'cost_per_km' => $settings->transfer_cost_per_km,
                    'fixed_fee' => $settings->transfer_fixed_fee,
                    'value_percentage' => $settings->transfer_value_percentage,
                ],
            ],
        ]);
    }
}