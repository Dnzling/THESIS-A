<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePayAdjustment extends Model
{
    protected $fillable = [
        'store_id', 'employee_id', 'effective_date', 'type', 'name', 'amount', 'status', 'notes',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
