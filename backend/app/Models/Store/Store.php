<?php

namespace App\Models\Store;

use App\Models\Core\User;
use App\Models\ProductCatalog\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Store extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = [
        'store_name',
        'email',
        'contact_person',
        'contact_number',
        'city',
        'address',
        'status',
        'longitude',
        'latitude',
        'verified_at',
        'verified_by',
        'settings',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'verified_at' => 'datetime',
        'settings' => 'array',
    ];

    protected $table = 'stores';

    // ======== Relationships =========
    // Relationship with verification
    public function verification(): HasOne
    {
        return $this->hasOne(StoreVerification::class, 'store_id', 'id');
    }


    // Check if store is verified
    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    // Check if store has submitted documents
    public function hasSubmittedDocuments(): bool
    {
        return $this->verification && $this->verification->submitted_at !== null;
    }

    // Scope for pending verification
    public function scopePendingVerification($query)
    {
        return $query->where('status', 'pending')
            ->whereHas('verification', function ($q) {
                $q->whereNotNull('submitted_at');
            });
    }

    public function users()
    {
        return $this->hasMany(User::class, 'store_id', 'id');
    }

    public function branches()
    {
        return $this->hasMany(Branch::class, 'store_id', 'id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'store_id', 'id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'store_id', 'id');
    }


    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeWithCategory($query, $categoryId)
    {
        return $query->whereHas('products', function ($q) use ($categoryId) {
            $q->where('category_id', $categoryId);
        });
    }

    /**
     * Scope: Search stores by name or contact person
     */
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('store_name', 'LIKE', "%{$searchTerm}%")
                ->orWhere('contact_person', 'LIKE', "%{$searchTerm}%");
        });
    }

    // ========== HELPER METHODS ==========

    /**
     * Check if store is active
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function scopeHasStore($query)
    {
        return $query->whereNotNull('store_id');
    }


    /**
     * Get total number of staff
     */
    public function getStaffCountAttribute()
    {
        return $this->users()->count();
    }

    /**
     * Get total products count
     */
    public function getProductsCountAttribute()
    {
        return $this->products()->count();
    }

    /**
     * Get today's sales total
     */
    public function getTodaysSalesAttribute()
    {
        return $this->sales()
            ->whereDate('created_at', today())
            ->sum('total_amount');
    }

    /**
     * Get this month's sales
     */
    public function getMonthlySalesAttribute()
    {
        return $this->sales()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');
    }

    /**
     * Get stores with low stock alerts (for DSS)
     */
    public function getLowStockProducts()
    {
        return $this->products()
            ->whereRaw('current_stock <= min_stock_threshold')
            ->get();
    }

    /**
     * Get store performance metrics (for DSS dashboard)
     */
    public function getPerformanceMetrics()
    {
        $salesThisMonth = $this->sales()
            ->whereMonth('created_at', now()->month)
            ->count();

        $salesLastMonth = $this->sales()
            ->whereMonth('created_at', now()->subMonth()->month)
            ->count();

        $growth = $salesLastMonth > 0
            ? (($salesThisMonth - $salesLastMonth) / $salesLastMonth) * 100
            : 0;

        return [
            'monthly_sales_count' => $salesThisMonth,
            'monthly_revenue' => $this->monthly_sales,
            'growth_percentage' => round($growth, 2),
            'active_staff' => $this->users()->active()->count(),
            'low_stock_items' => $this->getLowStockProducts()->count()
        ];
    }



    /**
     * Get store contact information formatted
     */
    public function getContactInfoAttribute()
    {
        return [
            'store_name' => $this->store_name,
            'contact_person' => $this->contact_person,
            'phone' => $this->contact_number,
            'email' => $this->email,
        ];
    }

    /**
     * Activate store
     */
    public function activate()
    {
        $this->update(['status' => 'active']);
        return $this;
    }

    /**
     * Deactivate store
     */
    public function deactivate()
    {
        $this->update(['status' => 'inactive']);
        return $this;
    }
}
