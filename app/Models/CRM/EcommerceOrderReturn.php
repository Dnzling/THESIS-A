<?php

namespace App\Models\CRM;

use App\Models\Ecommerce\EcommerceOrder;
use App\Models\Ecommerce\EcommerceOrderItem;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;

use App\Models\Core\User;
use App\Models\Logistics\ReturnPickup;
use App\Models\Finance\FinanceRefund;
use App\Models\Ecommerce\EcommerceDeliveryVehicle;
use App\Models\Store\Branch;
use App\Models\Store\Store;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use stdClass;

class EcommerceOrderReturn extends Model
{
    protected $table = 'ecommerce_order_returns';

    protected $fillable = [
        'return_number',
        'order_id',
        'order_item_id',
        'store_id',
        'source_type',
        'return_contact_name',
        'return_contact_phone',
        'return_pickup_address',
        'user_id',
        'sales_order_id',
        'sales_order_item_id',
        'requested_quantity',
        'reason',
        'details',
        'evidence_urls',
        'status',
        'return_type',
        'charge_responsibility',
        'return_fee',
        'return_fee_breakdown',
        'refund_exception_reason',
        'product_condition',
        'inventory_disposition',
        'received_quantity',
        'inspected_by',
        'inspected_at',
        'inspection_notes',
        'resolved_at',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'replacement_status',
        'replacement_branch_id',
        'replacement_inventory_id',
        'replacement_product_id',
        'replacement_variation_id',
        'replacement_unit_price',
        'replacement_price_difference',
        'replacement_payment_status',
        'replacement_payment_method',
        'replacement_payment_reference',
        'replacement_customer_accepted_by',
        'replacement_customer_accepted_at',
        'replacement_paid_by',
        'replacement_paid_at',
        'replacement_trip_id',
        'replacement_driver_id',
        'replacement_vehicle_id',
        'replacement_assigned_at',
        'replacement_dispatched_at',
        'current_latitude',
        'current_longitude',
        'current_address',
        'replacement_delivered_at',
        'replacement_proof_path',
        'replacement_received_by',
        'replacement_failure_reason',
    ];

    protected $casts = [
        'requested_quantity' => 'integer',
        'reviewed_at' => 'datetime',
        'received_quantity' => 'integer',
        'inspected_at' => 'datetime',
        'resolved_at' => 'datetime',
        'evidence_urls' => 'array',
        'return_fee' => 'decimal:2',
        'replacement_unit_price' => 'decimal:2',
        'replacement_price_difference' => 'decimal:2',
        'return_fee_breakdown' => 'array',
        'replacement_customer_accepted_at' => 'datetime',
        'replacement_paid_at' => 'datetime',
        'replacement_assigned_at' => 'datetime',
        'replacement_dispatched_at' => 'datetime',
        'current_latitude' => 'decimal:7',
        'current_longitude' => 'decimal:7',
        'replacement_delivered_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $return): void {
            if (filled($return->return_number)) {
                return;
            }

            $timestamp = now();
            do {
                $number = 'RET-' . $timestamp->format('YmdHis');
                $timestamp->addSecond();
            } while (static::query()->where('return_number', $number)->exists());

            $return->return_number = $number;
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrder::class, 'order_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrderItem::class, 'order_item_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class, 'sales_order_item_id');
    }

    public function sourceOrder(): ?EloquentModel
    {
        return $this->source_type === 'pos' ? $this->salesOrder : $this->order;
    }

    public function sourceOrderItem(): ?EloquentModel
    {
        return $this->source_type === 'pos' ? $this->salesOrderItem : $this->orderItem;
    }

    public function normalizeSourceRelationsForDisplay(): self
    {
        if ($this->source_type !== 'pos') {
            return $this;
        }

        $order = $this->salesOrder;
        if ($order) {
            $order->setAttribute('shipping_name', $this->return_contact_name ?: $order->customer_name);
            $order->setAttribute('shipping_phone', $this->return_contact_phone ?: $order->customer_phone);
            $order->setAttribute('shipping_email', $order->delivery_email);
            $order->setAttribute('shipping_address', $this->return_pickup_address ?: $order->delivery_address);
            $order->setAttribute('assigned_branch_id', $order->branch_id);
            $order->setAttribute('customer_latitude', $order->delivery_latitude);
            $order->setAttribute('customer_longitude', $order->delivery_longitude);
            if ($order->relationLoaded('branch')) {
                $order->setRelation('assignedBranch', $order->getRelation('branch'));
            }
            $this->setRelation('order', $order);
        }

        if ($this->salesOrderItem) {
            $this->setRelation('orderItem', $this->salesOrderItem);
        }

        if (! $this->user && $order) {
            $name = trim((string) ($this->return_contact_name ?: $order->customer_name));
            $parts = preg_split('/\\s+/', $name, 2) ?: [];
            $customer = new stdClass();
            $customer->fname = $parts[0] ?? '';
            $customer->lname = $parts[1] ?? '';
            $customer->full_name = $name;
            $customer->email = $order->delivery_email;
            $this->setRelation('user', $customer);
        }

        return $this;
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function pickup(): HasOne
    {
        return $this->hasOne(ReturnPickup::class, 'return_id');
    }

    public function replacementDriver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replacement_driver_id');
    }

    public function replacementVehicle(): BelongsTo
    {
        return $this->belongsTo(EcommerceDeliveryVehicle::class, 'replacement_vehicle_id');
    }

    public function replacementBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'replacement_branch_id');
    }

    public function replacementProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'replacement_product_id');
    }

    public function replacementVariation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'replacement_variation_id');
    }

    public function replacementInventory(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Inventory\BranchInventory::class, 'replacement_inventory_id');
    }

    public function replacementCustomerAcceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replacement_customer_accepted_by');
    }

    public function replacementPaidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replacement_paid_by');
    }

    public function replacementTrip(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Logistics\DeliveryTrip::class, 'replacement_trip_id');
    }

    public function investigationTicket(): HasOne
    {
        return $this->hasOne(ReturnInvestigationTicket::class, 'return_id');
    }

    public function financeRefund(): HasOne
    {
        return $this->hasOne(FinanceRefund::class, 'order_id')
            ->where('order_type', 'ecommerce_return');
    }
}
