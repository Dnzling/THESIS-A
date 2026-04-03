<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPerformanceMetric extends Model
{
    use HasFactory;

    protected $table = 'supplier_performance_metrics';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'supplier_id',
        'on_time_percentage',
        'quality_score',
        'avg_delivery_days',
        'risk_score',
        'risk_level',
        'date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'date' => 'date',
        'on_time_percentage' => 'float',
        'quality_score' => 'float',
        'avg_delivery_days' => 'integer',
        'risk_score' => 'integer',
    ];

    /**
     * Get the supplier this metric belongs to.
     *
     * @return BelongsTo
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
