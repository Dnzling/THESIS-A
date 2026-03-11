<?php

namespace App\Models\Procurement\RFQ;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Hr\Employee;

class RfqAttachment extends Model
{
    protected $table = 'rfq_attachments';

    protected $fillable = [
        'rfq_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'attachment_type',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RequestForQuotation::class, 'rfq_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'uploaded_by');
    }

    public function getDownloadUrl(): string
    {
        return route('rfq.attachment.download', $this->id);
    }
}
