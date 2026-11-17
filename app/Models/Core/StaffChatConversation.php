<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class StaffChatConversation extends Model
{
    protected $fillable = ['store_id', 'last_message_at'];
    protected $casts = ['last_message_at' => 'datetime'];

    public function participants() { return $this->belongsToMany(User::class, 'staff_chat_participants', 'conversation_id', 'user_id')->withPivot('last_read_at')->withTimestamps(); }
    public function messages() { return $this->hasMany(StaffChatMessage::class, 'conversation_id'); }
}
