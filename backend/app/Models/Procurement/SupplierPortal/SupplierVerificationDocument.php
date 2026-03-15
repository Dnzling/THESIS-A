<?php

namespace App\Models\Procurement\SupplierPortal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class SupplierVerificationDocument extends Model
{
    protected $fillable = [
        'supplier_portal_id',
        'document_type',
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

    // Relationships
    public function supplierPortal(): BelongsTo
    {
        return $this->belongsTo(SupplierPortal::class);
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'id');
    }

    // Constants
    public static array $DOCUMENT_TYPES = [
        'business_license' => 'Business License',
        'tax_id' => 'Tax ID',
        'company_registration' => 'Company Registration',
        'bank_details' => 'Bank Details',
    ];

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Helper Methods
    public function getDocumentTypeLabel(): string
    {
        return self::$DOCUMENT_TYPES[$this->document_type] ?? $this->document_type;
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
