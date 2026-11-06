<?php

namespace App\Models\Ecommerce;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcommerceFeedback extends Model
{
    protected $table = 'ecommerce_feedback';
    protected $fillable = ['user_id', 'page_url', 'topic', 'rating', 'message', 'contact_allowed', 'status', 'admin_note', 'handled_by', 'handled_at'];
    protected $casts = ['rating' => 'integer', 'contact_allowed' => 'boolean', 'handled_at' => 'datetime'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function handler(): BelongsTo { return $this->belongsTo(User::class, 'handled_by'); }
}
