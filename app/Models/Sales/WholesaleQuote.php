<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\CRM\CrmLead;

class WholesaleQuote extends Model
{
    protected $table = 'sales_wholesale_quotes';

    protected $fillable = [
        'store_id', 'branch_id', 'crm_lead_id', 'quote_number', 'status', 'items',
        'subtotal', 'valid_until', 'payment_terms', 'notes', 'sales_order_id', 'created_by',
    ];

    protected $casts = ['items' => 'array', 'subtotal' => 'decimal:2', 'valid_until' => 'date'];

    public function crmLead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'crm_lead_id');
    }
}
