<?php
// backend/app/Models/Procurement/Supplier/Supplier.php

namespace App\Models\Procurement\Supplier;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Store\Store;
use App\Models\ProductCatalog\Product;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Procurement\RFQ\SupplierQuotation;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'supplier_code',
        'supplier_name',
        'company_name',
        'contact_person',
        'email',
        'phone',
        'mobile',
        'fax',
        'website',
        'address',
        'city',
        'province',
        'postal_code',
        'country',
        'tin',
        'business_registration',
        'supplier_type',
        'payment_terms',
        'credit_limit',
        'current_balance',
        'rating',
        'total_orders',
        'total_amount_purchased',
        'on_time_deliveries',
        'late_deliveries',
        'status',
        'notes',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'rating' => 'decimal:2',
        'total_orders' => 'integer',
        'total_amount_purchased' => 'decimal:2',
        'on_time_deliveries' => 'integer',
        'late_deliveries' => 'integer',
    ];

    // Relationships
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'supplier_products')
            ->withPivot('supplier_sku', 'supplier_price', 'minimum_order_quantity', 'lead_time_days', 'is_preferred_supplier')
            ->withTimestamps();
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(SupplierContract::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(SupplierQuotation::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('supplier_type', $type);
    }

    public function scopeTopRated($query, float $minRating = 4.0)
    {
        return $query->where('rating', '>=', $minRating);
    }

    // Accessors
    public function getOnTimeDeliveryRateAttribute(): float
    {
        $total = $this->on_time_deliveries + $this->late_deliveries;
        
        if ($total === 0) {
            return 0;
        }

        return round(($this->on_time_deliveries / $total) * 100, 2);
    }

    public function getAverageOrderValueAttribute(): float
    {
        if ($this->total_orders === 0) {
            return 0;
        }

        return round($this->total_amount_purchased / $this->total_orders, 2);
    }

    // Helper Methods
    public function hasActiveContract(): bool
    {
        return $this->contracts()
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->exists();
    }

    public function updateRating(): void
    {
        $onTimeRate = $this->on_time_delivery_rate;
        
        if ($onTimeRate >= 95) {
            $this->rating = 5.0;
        } elseif ($onTimeRate >= 85) {
            $this->rating = 4.0;
        } elseif ($onTimeRate >= 75) {
            $this->rating = 3.0;
        } elseif ($onTimeRate >= 60) {
            $this->rating = 2.0;
        } else {
            $this->rating = 1.0;
        }

        $this->save();
    }

    public function incrementOrders(float $amount): void
    {
        $this->increment('total_orders');
        $this->increment('total_amount_purchased', $amount);
    }

    public function recordDelivery(bool $onTime): void
    {
        if ($onTime) {
            $this->increment('on_time_deliveries');
        } else {
            $this->increment('late_deliveries');
        }

        $this->updateRating();
    }
}