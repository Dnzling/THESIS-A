<?php

namespace App\Models\Store;

use App\Models\Core\User;
use App\Models\Ims\Catalog\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Branch extends Model
{
    use HasFactory;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_id',
        'name',
        'address',
        'city',
        // 'province',
        // 'postal_code',
        'contact_number',
        'email',
        'manager_id',
        'latitude',
        'longitude',
        // 'opening_hours',
        'status',
        'branch_code',
        'is_main_branch'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_main_branch' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'opening_hours' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'branches';

    // ========== RELATIONSHIPS ==========

    /**
     * A branch belongs to a store
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'store_id');
    }

    /**
     * A branch has many users (staff)
     */
    public function users()
    {
        return $this->hasMany(User::class, 'branch_id', 'branch_id');
    }

    /**
     * A branch has many products
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'branch_id', 'branch_id');
    }

    /**
     * A branch has many sales
     */
    public function sales()
    {
        return $this->hasMany(Sale::class, 'branch_id', 'branch_id');
    }

    /**
     * Branch manager (if assigned)
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id', 'user_id');
    }

    // ========== SCOPES ==========

    /**
     * Scope: Active branches only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Main branches only
     */
    public function scopeMain($query)
    {
        return $query->where('is_main_branch', true);
    }

    /**
     * Scope: Branches by city
     */
    public function scopeInCity($query, $city)
    {
        return $query->where('city', $city);
    }

    /**
     * Scope: Branches by province (Cavite)
     */
    public function scopeInProvince($query, $province = 'Cavite')
    {
        return $query->where('province', $province);
    }

    /**
     * Scope: Search branches
     */
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function($q) use ($searchTerm) {
            $q->where('name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('address', 'LIKE', "%{$searchTerm}%")
              ->orWhere('city', 'LIKE', "%{$searchTerm}%")
              ->orWhere('branch_code', 'LIKE', "%{$searchTerm}%");
        });
    }

    /**
     * Scope: Branches for a specific store
     */
    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    // ========== HELPER METHODS ==========

    /**
     * Check if branch is active
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if this is the main branch
     */
    public function isMainBranch()
    {
        return $this->is_main_branch === true;
    }

    /**
     * Get total staff count
     */
    public function getStaffCountAttribute()
    {
        return $this->users()->count();
    }

    /**
     * Get active staff count
     */
    public function getActiveStaffCountAttribute()
    {
        return $this->users()->where('status', 'active')->count();
    }

    /**
     * Get total products count
     */
    public function getProductsCountAttribute()
    {
        return $this->products()->count();
    }

    /**
     * Get today's sales count
     */
    public function getTodaysSalesAttribute()
    {
        return $this->sales()
            ->whereDate('created_at', today())
            ->count();
    }

    /**
     * Get today's revenue
     */
    public function getTodaysRevenueAttribute()
    {
        return $this->sales()
            ->whereDate('created_at', today())
            ->sum('total_amount');
    }

    /**
     * Get this month's revenue
     */
    public function getMonthlyRevenueAttribute()
    {
        return $this->sales()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');
    }

    /**
     * Get low stock products for this branch (DSS feature)
     */
    public function getLowStockProducts()
    {
        return $this->products()
            ->whereRaw('current_stock <= min_stock_threshold')
            ->get();
    }

    /**
     * Get branch performance metrics (for DSS)
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
            'monthly_revenue' => $this->monthly_revenue,
            'growth_percentage' => round($growth, 2),
            'active_staff' => $this->active_staff_count,
            'low_stock_items' => $this->getLowStockProducts()->count(),
            'todays_sales' => $this->todays_sales,
            'todays_revenue' => $this->todays_revenue
        ];
    }

    /**
     * Get full address
     */
    public function getFullAddressAttribute()
    {
        $address = $this->address;
        if ($this->city) {
            $address .= ', ' . $this->city;
        }
        if ($this->province) {
            $address .= ', ' . $this->province;
        }
        if ($this->postal_code) {
            $address .= ' ' . $this->postal_code;
        }
        return $address;
    }

    /**
     * Get branch contact information
     */
    public function getContactInfoAttribute()
    {
        return [
            'name' => $this->name,
            'address' => $this->full_address,
            'contact_number' => $this->contact_number,
            'email' => $this->email,
            'manager' => $this->manager ? $this->manager->name : 'Not assigned',
            'store' => $this->store ? $this->store->store_name : 'Unknown'
        ];
    }

    /**
     * Generate branch code (e.g., "MF-DASMA-01")
     */
    public static function generateBranchCode($storeName, $city, $branchNumber = null)
    {
        // Get store initials
        $storeInitials = '';
        $words = explode(' ', $storeName);
        foreach ($words as $word) {
            $storeInitials .= strtoupper(substr($word, 0, 1));
        }
        
        // Get city code (first 4 letters uppercase)
        $cityCode = strtoupper(substr($city, 0, 4));
        
        // If branch number not provided, get next number for this store+city
        if (!$branchNumber) {
            $lastBranch = self::where('store_id', function($query) use ($storeName) {
                $query->select('store_id')
                    ->from('stores')
                    ->where('store_name', 'LIKE', "%{$storeName}%")
                    ->limit(1);
            })
            ->where('city', $city)
            ->orderBy('branch_code', 'desc')
            ->first();
            
            $branchNumber = $lastBranch ? 
                intval(substr($lastBranch->branch_code, -2)) + 1 : 1;
        }
        
        return $storeInitials . '-' . $cityCode . '-' . str_pad($branchNumber, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Activate branch
     */
    public function activate()
    {
        $this->update(['status' => 'active']);
        return $this;
    }

    /**
     * Deactivate branch
     */
    public function deactivate()
    {
        $this->update(['status' => 'inactive']);
        return $this;
    }

    /**
     * Set as main branch
     */
    public function setAsMain()
    {
        // First, unset any other main branch for this store
        self::where('store_id', $this->store_id)
            ->where('branch_id', '!=', $this->branch_id)
            ->update(['is_main_branch' => false]);
        
        $this->update(['is_main_branch' => true]);
        return $this;
    }

    /**
     * Get nearby branches (for mobile app)
     */
    public static function getNearbyBranches($latitude, $longitude, $radius = 10)
    {
        return self::select('*')
            ->selectRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
                [$latitude, $longitude, $latitude]
            )
            ->where('status', 'active')
            ->having('distance', '<', $radius)
            ->orderBy('distance')
            ->get();
    }

    /**
     * Get Cavite cities where branches exist
     */
    public static function getCaviteCities()
    {
        return self::where('province', 'Cavite')
            ->whereNotNull('city')
            ->distinct()
            ->pluck('city')
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Get branch statistics for dashboard
     */
    public function getDashboardStats()
    {
        return [
            'overview' => [
                'total_staff' => $this->staff_count,
                'total_products' => $this->products_count,
                'monthly_sales' => $this->sales()->whereMonth('created_at', now()->month)->count(),
                'monthly_revenue' => $this->monthly_revenue
            ],
            'staff_by_role' => $this->users()
                ->select('role', DB::raw('count(*) as count'))
                ->groupBy('role')
                ->pluck('count', 'role')
                ->toArray(),
            'top_selling_products' => $this->products()
                ->withCount(['saleItems as total_sold' => function($query) {
                    $query->select(DB::raw('COALESCE(SUM(quantity), 0)'));
                }])
                ->orderBy('total_sold', 'desc')
                ->limit(5)
                ->get()
                ->map(function($product) {
                    return [
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'total_sold' => $product->total_sold,
                        'current_stock' => $product->current_stock
                    ];
                })
        ];
    }
}