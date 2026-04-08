<?php
// backend/app/Models/Procurement/Invoice/InvoiceItem.php

namespace App\Models\Procurement\Invoice;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ProductCatalog\Product;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'description',
        'quantity_invoiced',
        'unit_price',
        'line_amount',
        'tax_rate',
        'tax_amount',
        'remarks',
    ];

    protected $casts = [
        'quantity_invoiced' => 'integer',
        'unit_price' => 'decimal:2',
        'line_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calculate line total including tax
     */
    public function getLineTotalAttribute(): float
    {
        $lineAmount = $this->line_amount ?? 0;
        $taxAmount = $this->tax_amount ?? 0;
        return $lineAmount + $taxAmount;
    }
}
