<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Procurement\SupplierPortal\SupplierPortal;

class Supplier extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'supplier_name',
        'company_name',
        'contact_person',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'category',
        'payment_terms',
        'status',
        'tax_id',
        // removed bank_details from supplier registration
        'rating',
        'quality_score',
        'on_time_percentage',
        'avg_delivery_days',
        'risk_score',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'rating' => 'float',
        'quality_score' => 'float',
        'on_time_percentage' => 'float',
        'avg_delivery_days' => 'integer',
        'risk_score' => 'integer',
    ];

    /**
     * Get the performance metrics for this supplier.
     *
     * @return HasMany
     */
    public function performanceMetrics(): HasMany
    {
        return $this->hasMany(SupplierPerformanceMetric::class);
    }

    /**
     * Get the payments for this supplier.
     *
     * @return HasMany
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    /**
     * Get the ratings for this supplier.
     *
     * @return HasMany
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(SupplierRating::class);
    }

    /**
     * Get related supplier portal (if any).
     *
     * @return HasOne
     */
    public function supplierPortal(): HasOne
    {
        return $this->hasOne(SupplierPortal::class, 'supplier_id');
    }

    /**
     * Scope: Get active suppliers only.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Get suppliers by category.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $category
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Get at-risk suppliers.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAtRisk($query)
    {
        return $query->where('risk_score', '>=', 50);
    }

    /**
     * Get the latest performance metrics for this supplier.
     *
     * @return SupplierPerformanceMetric|null
     */
    public function getLatestPerformanceMetrics()
    {
        return $this->performanceMetrics()
            ->orderByDesc('date')
            ->first();
    }

    /**
     * Get the current payment status for this supplier.
     *
     * @return array{total_due: int, paid_count: int, total_paid: int, due_count: int, overdue_count: int, overdue_amount: int}
     */
    public function getPaymentStatus()
    {
        $payments = $this->payments();

        return [
            'total_due' => $payments->where('status', 'pending')->sum('amount'),
            'total_paid' => $payments->where('status', 'paid')->sum('amount'),
            'due_count' => $payments->where('status', 'pending')->count(),
            'paid_count' => $payments->where('status', 'paid')->count(),
            'overdue_count' => $payments->where('status', 'overdue')->count(),
            'overdue_amount' => $payments->where('status', 'overdue')->sum('amount'),
        ];
    }

    /**
     * Format phone number.
     *
     * @param string $phone
     * @return string
     */
    public static function formatPhoneNumber(string $phone): string
    {
        // Remove all non-digit characters
        $digits = preg_replace('/\D/', '', $phone);

        // Format as (XXX) XXX-XXXX
        if (strlen($digits) == 10) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6));
        }

        return $phone;
    }

    /**
     * Get risk level based on score.
     *
     * @param int $score
     * @return string
     */
    public static function getRiskLevel(int $score): string
    {
        if ($score < 20) return 'Low';
        if ($score < 50) return 'Medium';
        if ($score < 75) return 'High';
        return 'Critical';
    }
}
