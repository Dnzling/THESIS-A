<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymongoIntent extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'payment_intent_id',
        'amount',
        'currency',
        'status',
        'client_key',
        'description',
        'statement_descriptor',
        'payment_method_allowed',
        'payment_method_id',
        'metadata',
        'payable_type',
        'payable_id',
        'webhook_payload',
    ];

    protected $casts = [
        'metadata' => 'array',
        'webhook_payload' => 'array',
    ];
}
