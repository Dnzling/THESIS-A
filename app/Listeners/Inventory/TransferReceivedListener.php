<?php

namespace App\Listeners\Inventory;

use App\Events\Inventory\TransferReceived;
use App\Models\Inventory\InventoryNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class TransferReceivedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event
     */
    public function handle(TransferReceived $event): void
    {
        try {
            // Notify the sender branch manager that transfer was received
            $transfer = \App\Models\StockTransfer::find($event->transferId);
            
            if (!$transfer) {
                return;
            }

            // Notify requesting user
            InventoryNotification::create([
                'user_id' => $transfer->requested_by,
                'store_id' => $transfer->store_id,
                'branch_id' => $transfer->to_branch_id,
                'notification_type' => 'transfer_received',
                'entity_type' => 'stock_transfer',
                'entity_id' => $transfer->id,
                'title' => 'Stock Transfer Received',
                'message' => "Stock transfer #{$transfer->transfer_number} has been successfully received and added to inventory",
                'action_required' => false,
                'is_read' => false,
            ]);

            // Notify warehouse/sender that confirmation was made
            $senderBranchManagers = \App\Models\User::where('store_id', $transfer->store_id)
                ->where('branch_id', $transfer->from_branch_id)
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', ['warehouse_manager', 'branch_manager']);
                })
                ->get();

            foreach ($senderBranchManagers as $manager) {
                InventoryNotification::create([
                    'user_id' => $manager->id,
                    'store_id' => $transfer->store_id,
                    'branch_id' => $transfer->from_branch_id,
                    'notification_type' => 'transfer_receipt_confirmed',
                    'entity_type' => 'stock_transfer',
                    'entity_id' => $transfer->id,
                    'title' => 'Transfer Receipt Confirmed',
                    'message' => "Stock transfer #{$transfer->transfer_number} has been confirmed as received by branch {$transfer->to_branch_id}",
                    'action_required' => false,
                    'is_read' => false,
                ]);
            }

            Log::channel('inventory')->info(
                'Transfer received notifications created',
                ['transfer_id' => $event->transferId]
            );
        } catch (\Exception $e) {
            Log::channel('inventory')->error(
                'Error in TransferReceivedListener: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
