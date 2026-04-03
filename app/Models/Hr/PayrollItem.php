<?php
// app/Models/Hr/PayrollItem.php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    protected $table = 'payroll_items';

    protected $fillable = [
        'payroll_id',
        'type',
        'name',
        'amount',
        'calculation_type',
        'rate',
        'quantity',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'rate' => 'decimal:2',
        'quantity' => 'decimal:2',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }
}