<?php

use App\Services\Inventory\IncomingPurchaseOrderStockService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $service = app(IncomingPurchaseOrderStockService::class);
        DB::table('purchase_order_items as poi')
            ->join('purchase_orders as po', 'po.id', '=', 'poi.purchase_order_id')
            ->whereNull('po.deleted_at')
            ->whereIn('po.status', IncomingPurchaseOrderStockService::ACTIVE_STATUSES)
            ->select('po.store_id', 'po.branch_id', 'poi.product_id', 'poi.variation_id')
            ->distinct()
            ->orderBy('po.store_id')
            ->orderBy('po.branch_id')
            ->orderBy('poi.product_id')
            ->get()
            ->each(fn ($item) => $service->syncItem(
                (int) $item->store_id,
                (int) $item->branch_id,
                (int) $item->product_id,
                $item->variation_id === null ? null : (int) $item->variation_id
            ));
    }

    public function down(): void
    {
        // Incoming quantities are operational stock data; do not discard them on rollback.
    }
};
