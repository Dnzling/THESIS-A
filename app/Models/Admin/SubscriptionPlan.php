<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    use SoftDeletes;
    protected $table = 'subscription_plans';

    protected $fillable = [
        'plan_key',
        'name',
        'description',
        'monthly_price',
        'yearly_price',
        'commission_percentage',
        'max_user_accounts',
        'max_branches',
        'max_products',
        'max_warehouses',
        'max_trucks',
        'max_suppliers',
        'commission_rate',
        'features',
        'is_featured',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'max_user_accounts' => 'integer',
        'max_branches' => 'integer',
        'max_products' => 'integer',
        'max_warehouses' => 'integer',
        'max_trucks' => 'integer',
        'max_suppliers' => 'integer',
        'features' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];
}
