<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'name',
        'type',
        'currency',
        'current_balance',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Store\Store::class, 'store_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceCashflowTransaction::class, 'finance_account_id');
    }
}
