<?php

namespace App\Models\Ecommerce;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcommerceAddressTemplate extends Model
{
    protected $table = 'ecommerce_address_templates';

    protected $fillable = [
        'user_id',
        'full_name',
        'contact_number',
        'province',
        'city',
        'barangay',
        'address_line',
        'latitude',
        'longitude',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
