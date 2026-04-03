<?php

namespace App\Models\Customer;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    protected $table = 'customers';

    protected $fillable = [
        'user_id',
        'contact_number',
        'verification_status',
        'verification_required',
        'verification_trigger_amount',
        'verification_triggered_at',
        'verification_rejection_reason',
        'verification_reviewed_by',
        'verification_reviewed_at',
    ];

    protected $casts = [
        'verification_required' => 'boolean',
        'verification_trigger_amount' => 'decimal:2',
        'verification_triggered_at' => 'datetime',
        'verification_reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
