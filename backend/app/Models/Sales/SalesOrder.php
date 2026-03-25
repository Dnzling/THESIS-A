<?php

namespace App\Models\Sales;

use App\Models\Core\User;
use App\Models\Store\Branch;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Sales\SalesOrderDelivery;

class SalesOrder extends Model
{
    protected $table = 'sales_pos_orders';

    protected $fillable = [
        'store_id',
        'branch_id',
        'order_number',
        'status',
        'customer_name',
        'customer_phone',
        'payment_method',
        'payment_status',
        'payment_channel',
        'payment_reference',
        'paid_at',
        'receipt_number',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'amount_tendered',
        'change_amount',
        'notes',
        'delivery_required',
        'delivery_address',
        'delivery_notes',
        'delivery_province',
        'delivery_city',
        'delivery_barangay',
        'delivery_address_line',
        'delivery_latitude',
        'delivery_longitude',
        'delivery_email',
        'created_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_tendered' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'delivery_required' => 'boolean',
        'delivery_latitude' => 'decimal:6',
        'delivery_longitude' => 'decimal:6',
    ];

    public function store(): BelongsTo { return $this->belongsTo(Store::class, 'store_id'); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class, 'branch_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(SalesOrderItem::class, 'order_id'); }
    public function payment(): HasOne { return $this->hasOne(SalesPayment::class, 'sales_order_id'); }
    public function receipt(): HasOne { return $this->hasOne(SalesReceipt::class, 'sales_order_id'); }
    public function delivery(): HasOne { return $this->hasOne(SalesOrderDelivery::class, 'sales_order_id'); }
}
