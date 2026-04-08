<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalWorkflow extends Model
{
    protected $fillable = [
        'workflowable_type',
        'workflowable_id',
        'current_step',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'workflow_data',
        'notes',
    ];

    protected $casts = [
        'workflow_data' => 'array',
        'approved_at' => 'datetime',
    ];

    public function workflowable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ApprovalTask::class, 'approval_workflow_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return in_array($this->status, ['approved', 'auto_approved'], true);
    }
}
