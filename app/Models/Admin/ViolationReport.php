<?php

namespace App\Models\Admin;

use App\Models\Core\User;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ViolationReport extends Model
{
    protected $fillable = [
        'store_id',
        'reporter_user_id',
        'reporter_type',
        'report_reason',
        'report_details',
        'evidence_urls',
        'status',
        'action_type',
        'action_reason',
        'action_by',
        'actioned_at',
    ];

    protected $casts = [
        'evidence_urls' => 'array',
        'actioned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function actionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_by');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ViolationReportResponse::class, 'violation_report_id')->orderBy('created_at');
    }
}
