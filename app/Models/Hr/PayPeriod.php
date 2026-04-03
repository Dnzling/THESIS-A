<?php
// app/Models/Hr/PayPeriod.php

namespace App\Models\Hr;

use App\Models\Core\User;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayPeriod extends Model
{
    use SoftDeletes;

    protected $table = 'pay_periods';

    protected $fillable = [
        'store_id',
        'name',
        'start_date',
        'end_date',
        'cutoff_date',
        'status',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'cutoff_date' => 'date',
    ];

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function store(){
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function deletedBy()
{
    return $this->belongsTo(User::class, 'deleted_by');
}

    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'completed');
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('start_date', '<=', $date)
                    ->where('end_date', '>=', $date);
    }
}