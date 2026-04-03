<?php

namespace App\Listeners\Inventory;

use App\Events\Inventory\StockLevelChanged;
use App\Models\Inventory\InventoryNotification;
use App\Services\Inventory\AlertService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class StockLevelChangedListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected $alertService;

    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    /**
     * Handle the event
     */
    public function handle(StockLevelChanged $event): void
    {
        try {
            // Log the stock level change for audit trail
            Log::channel('inventory')->info(
                'Stock level changed',
                [
                    'product_id' => $event->productId,
                    'branch_id' => $event->branchId,
                    'quantity_before' => $event->quantityBefore,
                    'quantity_after' => $event->quantityAfter,
                    'transaction_type' => $event->transactionType,
                ]
            );

            // Check if this change triggers any alerts
            if ($event->branchId && $event->productId) {
                $this->alertService->generateAlerts($event->branchId, $event->productId);
            }
        } catch (\Exception $e) {
            Log::channel('inventory')->error(
                'Error in StockLevelChangedListener: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
