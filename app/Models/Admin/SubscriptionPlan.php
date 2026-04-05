<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $table = 'subscription_plans';

    protected $fillable = [
        'plan_key',
        'name',
        'description',
        'monthly_price',
        'yearly_price',
        'features',
        'is_featured',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'features' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];
}
