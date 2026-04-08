<?php

namespace App\Models\Store;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreVerification extends Model
{
    protected $fillable = [
        'store_id',
        'city',
        'address',
        'business_registration_number',
        'business_registration_date',
        'business_registration_file',
        'tax_certificate_file',
        'business_permit_file',
        'gov_id_type',
        'gov_id_number',
        'gov_id_front_file',
        'gov_id_back_file',
        'selfie_with_id_file',
        'other_documents',
        'rejection_reason',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'latitude',
        'longitude'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'business_registration_date' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'other_documents' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
    
    // Helper methods
    public function isPending(): bool
    {
        return is_null($this->reviewed_at);
    }
    
    public function isApproved(): bool
    {
        return !is_null($this->reviewed_at) && is_null($this->rejection_reason);
    }
    
    public function isRejected(): bool
    {
        return !is_null($this->reviewed_at) && !is_null($this->rejection_reason);
    }
}