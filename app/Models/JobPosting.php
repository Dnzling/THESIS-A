<?php

namespace App\Models;

use App\Models\Core\Role;
use App\Models\Core\User;
use App\Models\Store\Store;
use App\Models\Store\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobPosting extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'branch_id',
        'role_id',
        'title',
        'department',
        'employment_type',
        'description',
        'requirements',
        'hiring_documents',
        'requires_signed_contract',
        'minimum_age',
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
        'hiring_documents' => 'array',
        'requires_signed_contract' => 'boolean',
        'minimum_age' => 'integer',
        'benefits' => 'array'
    ];

    protected $appends = ['company_logo_url'];

    public function getCompanyLogoUrlAttribute(): ?string
    {
        $path = $this->store?->logoBranch?->logo_path;

        return $path ? asset('storage/' . ltrim($path, '/')) : null;
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
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
