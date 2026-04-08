<?php

namespace App\Models\Hr;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Hr\Employee;

class EmployeeCreditCard extends Model
{
    use HasFactory;

    protected $table = 'employee_credit_cards';

    protected $fillable = [
        'employee_id',
        'card_number',
        'card_type',
        'status',
        'metadata',
        'assigned_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'assigned_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
