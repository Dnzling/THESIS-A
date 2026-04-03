<?php

namespace App\Models;

use App\Models\Core\User;
use App\Models\Hr\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class JobApplication extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'job_posting_id',
        'user_id',
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'current_position',
        'current_company',
        'birthday',
        'city',
        'province',
        'barangay',
        'address',
        'status',
        'application_date'
    ];

    protected $casts = [
        'application_date' => 'datetime'
    ];

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function timeline(): HasMany
    {
        return $this->hasMany(ApplicationTimeline::class, 'application_id')->orderBy('changed_at', 'desc');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class, 'application_id');
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class, 'application_id')->orderBy('interview_date', 'desc');
    }

    public function offer(): HasOne
    {
        return $this->hasOne(JobOffer::class, 'application_id');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
