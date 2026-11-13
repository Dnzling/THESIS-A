<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class StaffChatMessage extends Model
{
    protected $fillable = ['conversation_id', 'sender_user_id', 'message', 'attachment_path', 'attachment_name', 'attachment_mime', 'attachment_size'];
    protected $appends = ['attachment_url'];
    public function sender() { return $this->belongsTo(User::class, 'sender_user_id'); }
    public function getAttachmentUrlAttribute(): ?string { return $this->attachment_path ? route('staff-chat.attachments', $this->id) : null; }
}
