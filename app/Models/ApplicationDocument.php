<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationDocument extends Model
{
    protected $fillable = [
        'application_id',
        'document_type',
        'file_name',
        'file_path',
        'file_size',
        'mime_type'
    ];

    protected $casts = [
        'file_size' => 'integer'
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }
}
