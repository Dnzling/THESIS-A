<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPostingScreeningStage extends Model
{
    protected $fillable = [
        'job_posting_id',
        'stage_name',
        'order',
        'description'
    ];

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function applicationTimelines(): HasMany
    {
        return $this->hasMany(ApplicationTimeline::class, 'stage_id');
    }
}
