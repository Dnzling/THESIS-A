<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\InventoryConfigurationRequest;
use App\Models\Inventory\InventoryConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryConfigurationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('can:inventory.configure');
    }

    /**
     * Get configuration for store
     */
    public function show(): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;

            $config = InventoryConfiguration::where('store_id', $storeId)
                ->firstOrCreate(
                    ['store_id' => $storeId],
                    [
                        'model_type' => 'centralized',
                        'enable_transfer_approvals' => true,
                        'enable_finance_approval' => true,
                        'enable_auto_alerts' => true,
                    ]
                );

            return response()->json([
                'success' => true,
                'data' => $config,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update configuration
     */
    public function update(InventoryConfigurationRequest $request): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            $validated = $request->validated();

            $config = InventoryConfiguration::updateOrCreate(
                ['store_id' => $storeId],
                $validated
            );

            return response()->json([
                'success' => true,
                'message' => 'Configuration updated successfully',
                'data' => $config,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get description of configuration options
     */
    public function schema(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'model_type' => [
                    'description' => 'Inventory business model',
                    'options' => ['single_store', 'centralized', 'distributed', 'multi_store'],
                    'recommended' => 'centralized',
                ],
                'enable_transfer_approvals' => [
                    'description' => 'Require approval for inter-branch transfers',
                    'type' => 'boolean',
                ],
                'enable_finance_approval' => [
                    'description' => 'Require finance team approval for high-value transfers',
                    'type' => 'boolean',
                ],
                'enable_auto_alerts' => [
                    'description' => 'Auto-generate stock alerts',
                    'type' => 'boolean',
                ],
                'transfer_cost_model' => [
                    'description' => 'How to calculate transfer costs',
                    'options' => ['fixed', 'distance_based', 'weighted', 'none'],
                ],
            ],
        ]);
    }
}
