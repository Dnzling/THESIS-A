<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\CRM\CrmLead;
use App\Models\Core\User;

class WholesaleQuote extends Model
{
    protected $table = 'sales_wholesale_quotes';

    protected $fillable = [
        'store_id', 'branch_id', 'crm_lead_id', 'customer_name', 'customer_email', 'customer_phone', 'quote_number', 'status', 'items',
        'subtotal', 'valid_until', 'payment_terms', 'notes', 'sales_order_id', 'invoice_number', 'created_by',
        'delivery_required', 'delivery_address', 'delivery_latitude', 'delivery_longitude', 'shipping_fee',
        'delivery_province', 'delivery_city', 'delivery_barangay', 'delivery_address_line', 'delivery_notes',
    ];

    protected $casts = [
        'items' => 'array', 'subtotal' => 'decimal:2', 'valid_until' => 'date',
        'delivery_required' => 'boolean', 'shipping_fee' => 'decimal:2',
        'delivery_latitude' => 'decimal:7', 'delivery_longitude' => 'decimal:7',
    ];

    public function crmLead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'crm_lead_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
