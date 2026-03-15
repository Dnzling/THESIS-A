<?php

namespace App\Models;

use App\Models\Core\Role;
use App\Models\Core\User;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobPosting extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'role_id',
        'title',
        'department',
        'description',
        'requirements',
        'salary_min',
        'salary_max',
        'status',
        'created_by',
        'benefits',
    ];

    protected $casts = [
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'requirements' => 'array',
        'benefits' => 'array'
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function screeningStages(): HasMany
    {
        return $this->hasMany(JobPostingScreeningStage::class)->orderBy('order');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }
}
