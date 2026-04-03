<?php

namespace App\Models;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationTimeline extends Model
{
    protected $table = 'application_timeline';

    protected $fillable = [
        'application_id',
        'stage_id',
        'status',
        'changed_by',
        'changed_at',
        'notes',
        'feedback'
    ];

    protected $casts = [
        'changed_at' => 'datetime'
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(JobPostingScreeningStage::class, 'stage_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
