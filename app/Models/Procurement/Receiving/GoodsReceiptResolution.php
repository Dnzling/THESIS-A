<?php

namespace App\Models\Procurement\Receiving;

use App\Models\Core\User;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Procurement\Supplier\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptResolution extends Model
{
    protected $fillable = [
        'resolution_number', 'original_goods_receipt_id', 'follow_up_goods_receipt_id',
        'purchase_order_id', 'supplier_id', 'resolution_type', 'items',
        'procurement_notes', 'status', 'supplier_rejection_reason',
        'promised_delivery_date', 'delivery_note_number', 'supplier_delivery_notes',
        'proof_path', 'flagged_by', 'supplier_responded_by', 'supplier_responded_at', 'resolved_at',
    ];

    protected $casts = [
        'items' => 'array',
        'promised_delivery_date' => 'date',
        'supplier_responded_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function originalReceipt(): BelongsTo { return $this->belongsTo(GoodsReceipt::class, 'original_goods_receipt_id'); }
    public function followUpReceipt(): BelongsTo { return $this->belongsTo(GoodsReceipt::class, 'follow_up_goods_receipt_id'); }
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function flaggedBy(): BelongsTo { return $this->belongsTo(User::class, 'flagged_by'); }
    public function supplierRespondedBy(): BelongsTo { return $this->belongsTo(User::class, 'supplier_responded_by'); }
}
