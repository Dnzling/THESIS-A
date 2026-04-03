<?php

namespace App\Models\Procurement\Shipping;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PurchaseOrderDeliveryLogAttachment extends Model
{
    protected $fillable = [
        'delivery_log_id',
        'file_path',
        'mime_type',
        'size',
    ];

    protected $appends = ['public_url', 'filename'];

    public function deliveryLog(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderDeliveryLog::class, 'delivery_log_id');
    }

    public function getPublicUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        if (!Storage::disk('public')->exists($this->file_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->file_path);
    }

    public function getFilenameAttribute(): string
    {
        return basename($this->file_path ?? '');
    }
}
