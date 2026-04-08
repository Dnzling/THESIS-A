<?php

namespace App\Models\Procurement\SupplierPortal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Procurement\RFQ\RequestForQuotation;
use App\Models\Procurement\RFQ\RFQItem;
use App\Models\Core\User;

class SupplierRFQNegotiation extends Model
{
    protected $table = 'supplier_rfq_negotiations';

    protected $fillable = [
        'supplier_rfq_feedback_id',
        'supplier_portal_id',
        'rfq_id',
        'rfq_item_id',
        'counter_price',
        'notes',
        'created_by',
        'status',
    ];

    protected $casts = [
        'counter_price' => 'decimal:2',
    ];

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(SupplierRFQFeedback::class, 'supplier_rfq_feedback_id');
    }

    public function supplierPortal(): BelongsTo
    {
        return $this->belongsTo(SupplierPortal::class);
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RequestForQuotation::class, 'rfq_id');
    }

    public function rfqItem(): BelongsTo
    {
        return $this->belongsTo(RFQItem::class, 'rfq_item_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
