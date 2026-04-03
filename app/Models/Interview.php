<?php

namespace App\Models;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interview extends Model
{
    protected $fillable = [
        'application_id',
        'interviewer_id',
        'interview_date',
        'interview_type',
        'feedback',
        'score',
        'notes'
    ];

    protected $casts = [
        'interview_date' => 'datetime',
        'score' => 'decimal:1'
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }
}
