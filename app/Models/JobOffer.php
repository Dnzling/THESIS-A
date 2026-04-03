<?php

namespace App\Models;

use App\Models\Hr\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobOffer extends Model
{
    protected $fillable = [
        'application_id',
        'salary',
        'position',
        'department',
        'start_date',
        'benefits',
        'status',
        'expiry_date',
        'accepted_date',
        'employee_id'
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'benefits' => 'array',
        'start_date' => 'date',
        'expiry_date' => 'date',
        'accepted_date' => 'datetime'
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
