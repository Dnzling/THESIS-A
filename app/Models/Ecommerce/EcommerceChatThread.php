<?php

namespace App\Models\Ecommerce;

use App\Models\Core\User;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EcommerceChatThread extends Model
{
    protected $table = 'ecommerce_chat_threads';

    protected $fillable = [
        'store_id',
        'customer_user_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(EcommerceChatMessage::class, 'thread_id');
    }
}

