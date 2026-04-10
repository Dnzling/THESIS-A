<?php

namespace App\Models\Admin;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViolationReportResponse extends Model
{
    protected $fillable = [
        'violation_report_id',
        'responder_user_id',
        'responder_type',
        'message',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function violationReport(): BelongsTo
    {
        return $this->belongsTo(ViolationReport::class, 'violation_report_id');
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responder_user_id');
    }
}

