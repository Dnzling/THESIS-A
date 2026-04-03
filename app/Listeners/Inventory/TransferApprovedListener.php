<?php

namespace App\Listeners\Inventory;

use App\Events\Inventory\TransferApproved;
use App\Models\Inventory\InventoryNotification;
use App\Models\StockTransfer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class TransferApprovedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event
     */
    public function handle(TransferApproved $event): void
    {
        try {
            $transfer = StockTransfer::find($event->transferId);
            
            if (!$transfer) {
                return;
            }

            // Notify the requesting user
            InventoryNotification::create([
                'user_id' => $transfer->requested_by,
                'store_id' => $transfer->store_id,
                'branch_id' => $transfer->to_branch_id,
                'notification_type' => 'transfer_approved',
                'entity_type' => 'stock_transfer',
                'entity_id' => $transfer->id,
                'title' => 'Stock Transfer Approved',
                'message' => "Your stock transfer request #{$transfer->transfer_number} has been approved and will be shipped soon",
                'action_required' => false,
                'is_read' => false,
            ]);

            // If finance approval is required, notify finance managers
            if (strpos($transfer->approval_policy_used, 'finance') !== false) {
                $financeManagers = \App\Models\User::where('store_id', $transfer->store_id)
                    ->whereHas('roles', function ($query) {
                        $query->where('name', 'finance_manager');
                    })
                    ->get();

                foreach ($financeManagers as $manager) {
                    InventoryNotification::create([
                        'user_id' => $manager->id,
                        'store_id' => $transfer->store_id,
                        'branch_id' => $transfer->from_branch_id,
                        'notification_type' => 'finance_approval_required',
                        'entity_type' => 'stock_transfer',
                        'entity_id' => $transfer->id,
                        'title' => 'Finance Approval Required',
                        'message' => "Stock transfer #{$transfer->transfer_number} (Value: {$transfer->goods_value}) needs finance approval",
                        'action_required' => true,
                        'is_read' => false,
                    ]);
                }
            }

            Log::channel('inventory')->info(
                'Transfer approved notifications created',
                ['transfer_id' => $event->transferId]
            );
        } catch (\Exception $e) {
            Log::channel('inventory')->error(
                'Error in TransferApprovedListener: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
