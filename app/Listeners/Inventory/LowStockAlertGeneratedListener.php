<?php

namespace App\Listeners\Inventory;

use App\Events\Inventory\LowStockAlertGenerated;
use App\Models\Inventory\InventoryNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LowStockAlertGeneratedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event
     */
    public function handle(LowStockAlertGenerated $event): void
    {
        try {
            // Find branch managers for the branch
            $branchManagers = User::where('store_id', $event->storeId)
                ->where('branch_id', $event->branchId)
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', ['branch_manager', 'warehouse_manager']);
                })
                ->get();

            // Create notifications for each branch manager
            foreach ($branchManagers as $manager) {
                InventoryNotification::create([
                    'user_id' => $manager->id,
                    'store_id' => $event->storeId,
                    'branch_id' => $event->branchId,
                    'notification_type' => $event->alertType,
                    'entity_type' => 'product',
                    'entity_id' => $event->productId,
                    'title' => $event->alertType === 'out_of_stock' ? 'Out Of Stock Alert' : 'Low Stock Alert',
                    'message' => $event->message,
                    'action_required' => true,
                    'is_read' => false,
                ]);
            }

            // Also notify warehouse managers if this is a central warehouse alert
            if ($event->alertType === 'out_of_stock' || $event->alertType === 'low_stock') {
                $store = \App\Models\Store::find($event->storeId);
                if ($store && $store->mainBranch) {
                    $warehouseManagers = User::where('store_id', $event->storeId)
                        ->where('branch_id', $store->mainBranch->id)
                        ->whereHas('roles', function ($query) {
                            $query->where('name', 'warehouse_manager');
                        })
                        ->get();

                    foreach ($warehouseManagers as $manager) {
                        InventoryNotification::create([
                            'user_id' => $manager->id,
                            'store_id' => $event->storeId,
                            'branch_id' => $event->branchId,
                            'notification_type' => 'supply_required',
                            'entity_type' => 'product',
                            'entity_id' => $event->productId,
                            'title' => 'Supply Required',
                            'message' => "Branch {$event->branchId} requires stock for product {$event->productId}",
                            'action_required' => true,
                            'is_read' => false,
                        ]);
                    }
                }
            }

            Log::channel('inventory')->info(
                'Low stock alert notifications created',
                [
                    'product_id' => $event->productId,
                    'branch_id' => $event->branchId,
                    'alert_type' => $event->alertType,
                ]
            );
        } catch (\Exception $e) {
            Log::channel('inventory')->error(
                'Error in LowStockAlertGeneratedListener: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
