<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApplicantProfile extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'birthday',
        'city',
        'province',
        'barangay',
        'address',
        'current_position',
        'current_company',
    ];

    protected $casts = [
        'birthday' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Core\User::class, 'user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicantProfileDocument::class);
    }
}
