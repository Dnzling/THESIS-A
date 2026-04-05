<?php

namespace App\Models\Procurement\Supplier;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Procurement\RFQ\RequestForQuotation;
use App\Models\Procurement\RFQ\RFQItem;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Procurement\SupplierPortal\SupplierPortal;

class SupplierQuotation extends Model
{
    use SoftDeletes;

    protected $table = 'supplier_quotations';

    protected $fillable = [
        'rfq_id',
        'rfq_item_id',
        'supplier_id',
        'supplier_portal_id',
        'quoted_price',
        'tax_rate',
        'status',
        'description',
        'approved_by',
        'approved_at',
        'submitted_at',
    ];

    protected $casts = [
        'quoted_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'approved_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RequestForQuotation::class, 'rfq_id');
    }

    public function rfqItem(): BelongsTo
    {
        return $this->belongsTo(RFQItem::class, 'rfq_item_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function supplierPortal(): BelongsTo
    {
        return $this->belongsTo(SupplierPortal::class, 'supplier_portal_id');
    }
}
