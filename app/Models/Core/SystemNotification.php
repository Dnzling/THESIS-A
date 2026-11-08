<?php

namespace App\Models\Core;

use App\Events\SystemNotificationCreated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Store\Store;
use App\Models\Store\Branch;

class SystemNotification extends Model
{
    protected $table = 'system_notifications';

    protected $fillable = [
        'store_id',
        'branch_id',
        'user_id',
        'module',
        'entity_type',
        'entity_id',
        'action',
        'title',
        'message',
        'data',
        'link',
        'severity',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (self $notification): void {
            SystemNotificationCreated::dispatch($notification);
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}
