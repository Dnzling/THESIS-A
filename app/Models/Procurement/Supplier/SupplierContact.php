<?php
// backend/app/Models/Procurement/Supplier/SupplierContact.php

namespace App\Models\Procurement\Supplier;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierContact extends Model
{
    protected $fillable = [
        'supplier_id',
        'contact_name',
        'contact_title',
        'email',
        'phone',
        'mobile',
        'fax',
        'contact_type',
        'preferred_contact_method',
        'is_primary',
        'is_emergency_contact',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_emergency_contact' => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Scope to get primary contacts
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope to get emergency contacts
     */
    public function scopeEmergency($query)
    {
        return $query->where('is_emergency_contact', true);
    }

    /**
     * Scope by contact type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('contact_type', $type);
    }

    /**
     * Contact type options
     */
    public static function getContactTypes(): array
    {
        return [
            'Sales' => 'Sales',
            'Technical' => 'Technical',
            'Support' => 'Support',
            'Billing' => 'Billing',
            'Logistics' => 'Logistics',
        ];
    }

    /**
     * Preferred contact methods
     */
    public static function getContactMethods(): array
    {
        return [
            'Email' => 'Email',
            'Phone' => 'Phone',
            'Mobile' => 'Mobile',
            'WhatsApp' => 'WhatsApp',
            'Fax' => 'Fax',
        ];
    }
}
