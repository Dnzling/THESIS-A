<?php

namespace App\Listeners\Inventory;

use App\Events\Inventory\TransferRequested;
use App\Models\Inventory\InventoryNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class TransferRequestedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event
     */
    public function handle(TransferRequested $event): void
    {
        try {
            // Find warehouse managers who need to approve
            $warehouseManagers = User::where('store_id', $event->storeId)
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'warehouse_manager');
                })
                ->where('branch_id', $event->fromBranchId)
                ->get();

            // Create notifications for each warehouse manager
            foreach ($warehouseManagers as $manager) {
                InventoryNotification::create([
                    'user_id' => $manager->id,
                    'store_id' => $event->storeId,
                    'branch_id' => $event->fromBranchId,
                    'notification_type' => 'transfer_requested',
                    'entity_type' => 'stock_transfer',
                    'entity_id' => $event->transferId,
                    'title' => 'Stock Transfer Request',
                    'message' => "New stock transfer request from branch {$event->fromBranchId} to branch {$event->toBranchId}",
                    'action_required' => true,
                    'is_read' => false,
                ]);
            }

            Log::channel('inventory')->info(
                'Transfer requested notifications created',
                [
                    'transfer_id' => $event->transferId,
                    'from_branch_id' => $event->fromBranchId,
                    'to_branch_id' => $event->toBranchId,
                    'notifications_sent' => count($warehouseManagers),
                ]
            );
        } catch (\Exception $e) {
            Log::channel('inventory')->error(
                'Error in TransferRequestedListener: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
