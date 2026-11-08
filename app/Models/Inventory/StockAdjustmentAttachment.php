<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class StockAdjustmentAttachment extends Model
{
    protected $fillable = ['adjustment_id', 'file_path', 'file_name', 'mime_type', 'file_size'];

    protected $hidden = ['file_path'];

    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }
}
