<?php

namespace App\Models\Procurement\SupplierPortal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Procurement\Supplier\Supplier;
use Illuminate\Support\Collection;

class SupplierPortal extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'supplier_id',
        'supplier_name',
        'contact_person',
        'phone',
        'address',
        'city',
        'province',
        'country',
        'latitude',
        'longitude',
        'tin',
        'status',
        'rejection_reason',
        'verified_by',
        'verified_at',
        'resubmission_count',
        'last_submission_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'last_submission_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    /**
     * Supplier records used by stores can have different IDs from the supplier
     * account's original record. Include both when resolving portal visibility.
     */
    public function linkedSupplierIds(): Collection
    {
        $ids = Supplier::query()
            ->where('supplier_portal_id', $this->id)
            ->pluck('id');

        if ($this->supplier_id) {
            $ids->push((int) $this->supplier_id);
        }

        return $ids->map(fn ($id) => (int) $id)->unique()->values();
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by', 'id');
    }

    public function verificationDocuments(): HasMany
    {
        return $this->hasMany(SupplierVerificationDocument::class);
    }

    public function rfqFeedbacks(): HasMany
    {
        return $this->hasMany(SupplierRFQFeedback::class);
    }

    public function poFeedbacks(): HasMany
    {
        return $this->hasMany(SupplierPOFeedback::class);
    }

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
    public function isVerified(): bool
    {
        return $this->status === 'approved';
    }

    public function canResubmit(): bool
    {
        return $this->status === 'rejected';
    }

    public function allDocumentsApproved(): bool
    {
        $requiredDocs = ['business_license', 'tax_id', 'company_registration', 'bank_details'];
        $approvedDocs = $this->verificationDocuments()
            ->where('status', 'approved')
            ->pluck('document_type')
            ->toArray();

        return count(array_intersect($requiredDocs, $approvedDocs)) === count($requiredDocs);
    }

    public function getPendingDocuments()
    {
        return $this->verificationDocuments()
            ->where('status', 'pending')
            ->get();
    }
}
