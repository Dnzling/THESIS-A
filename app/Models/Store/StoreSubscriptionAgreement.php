<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Model;

class StoreSubscriptionAgreement extends Model
{
    protected $fillable = [
        'store_id', 'user_id', 'plan_key', 'plan_name', 'billing_cycle', 'plan_price', 'plan_terms',
        'agreement_version', 'signer_name', 'signer_email', 'signature_path',
        'document_path', 'status', 'signed_at', 'activated_at', 'signed_ip',
    ];

    protected $casts = [
        'plan_price' => 'decimal:2',
        'plan_terms' => 'array',
        'signed_at' => 'datetime',
        'activated_at' => 'datetime',
    ];
}
