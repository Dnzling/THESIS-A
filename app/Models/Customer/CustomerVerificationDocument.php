<?php

namespace App\Models\Customer;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerVerificationDocument extends Model
{
    protected $fillable = [
        'user_id',
        'document_type',
        'id_type',
        'id_number',
        'file_path',
        'original_filename',
        'file_mime_type',
        'file_size',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'file_size' => 'integer',
    ];

    public static array $DOCUMENT_TYPES = [
        'government_id' => 'Government ID',
        'primary_id' => 'Primary ID',
        'selfie_with_id' => 'Selfie with ID',
        'proof_of_address' => 'Proof of Address',
        'selfie' => 'Selfie Photo',
        'business_permit' => 'Business Permit',
        'tax_id' => 'Tax ID',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'id');
    }

    public function getDocumentTypeLabel(): string
    {
        return self::$DOCUMENT_TYPES[$this->document_type] ?? $this->document_type;
    }
}
