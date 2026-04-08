<?php
// backend/app/Models/Procurement/Analytics/SupplierPerformance.php

namespace App\Models\Procurement\Analytics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Store\Store;

class SupplierPerformance extends Model
{
    protected $fillable = [
        'store_id',
        'supplier_id',
        'period_start',
        'period_end',
        'total_orders',
        'total_amount',
        'on_time_deliveries',
        'late_deliveries',
        'early_deliveries',
        'quality_issues',
        'returns_count',
        'returns_amount',
        'average_lead_time',
        'lead_time_variance',
        'pricing_consistency_score',
        'responsiveness_score',
        'overall_rating',
        'recommendation',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_orders' => 'integer',
        'total_amount' => 'decimal:2',
        'on_time_deliveries' => 'integer',
        'late_deliveries' => 'integer',
        'early_deliveries' => 'integer',
        'quality_issues' => 'integer',
        'returns_count' => 'integer',
        'returns_amount' => 'decimal:2',
        'average_lead_time' => 'float',
        'lead_time_variance' => 'float',
        'pricing_consistency_score' => 'decimal:2',
        'responsiveness_score' => 'decimal:2',
        'overall_rating' => 'decimal:2',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Calculate on-time delivery percentage
     */
    public function getOnTimeDeliveryPercentAttribute(): float
    {
        $total = $this->on_time_deliveries + $this->late_deliveries;
        
        if ($total === 0) {
            return 0;
        }

        return round(($this->on_time_deliveries / $total) * 100, 2);
    }

    /**
     * Calculate quality score
     */
    public function getQualityScoreAttribute(): float
    {
        if ($this->total_orders === 0) {
            return 100;
        }

        $defectRate = ($this->quality_issues / $this->total_orders) * 100;
        $returnsRate = ($this->returns_count / $this->total_orders) * 100;
        
        $quality = 100 - ($defectRate * 0.7) - ($returnsRate * 0.3);

        return max(0, min(100, round($quality, 2)));
    }

    /**
     * Get recommendation based on metrics
     */
    public function getRecommendationAttribute(): string
    {
        if ($this->overall_rating >= 4.5) {
            return 'Continue';
        } elseif ($this->overall_rating >= 3.5) {
            return 'Review';
        } else {
            return 'Replace';
        }
    }

    /**
     * Get performance status color
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->overall_rating >= 4.5) {
            return 'success';
        } elseif ($this->overall_rating >= 3.5) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    /**
     * Scope to get current period performance
     */
    public function scopeCurrent($query)
    {
        return $query->orderBy('period_end', 'desc')
            ->limit(1);
    }

    /**
     * Scope to get performance for last N months
     */
    public function scopeLast($query, int $months = 12)
    {
        $startDate = now()->subMonths($months)->startOfMonth();
        return $query->whereBetween('period_start', [$startDate, now()])
            ->orderBy('period_start', 'asc');
    }

    /**
     * Scope to get top performers
     */
    public function scopeTopPerformers($query, float $minRating = 4.5)
    {
        return $query->where('overall_rating', '>=', $minRating)
            ->orderBy('overall_rating', 'desc');
    }

    /**
     * Scope to get underperformers
     */
    public function scopeUnderPerformers($query, float $maxRating = 3.0)
    {
        return $query->where('overall_rating', '<=', $maxRating)
            ->orderBy('overall_rating', 'asc');
    }
}
