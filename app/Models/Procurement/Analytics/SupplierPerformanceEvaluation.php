<?php

namespace App\Models\Procurement\Analytics;

use App\Models\Core\User;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Procurement\Receiving\GoodsReceipt;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPerformanceEvaluation extends Model
{
    protected $fillable = [
        'store_id',
        'supplier_id',
        'purchase_order_id',
        'goods_receipt_id',
        'evaluated_by_user_id',
        'quality_score',
        'quantity_accuracy_score',
        'delivery_timeliness_score',
        'packaging_condition_score',
        'overall_rating',
        'remarks',
    ];

    protected $casts = [
        'quality_score' => 'integer',
        'quantity_accuracy_score' => 'integer',
        'delivery_timeliness_score' => 'integer',
        'packaging_condition_score' => 'integer',
        'overall_rating' => 'decimal:2',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by_user_id');
    }
}
