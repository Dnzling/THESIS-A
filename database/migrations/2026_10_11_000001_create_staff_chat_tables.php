<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->index(['store_id', 'last_message_at']);
        });

        Schema::create('staff_chat_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('staff_chat_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();
            $table->unique(['conversation_id', 'user_id']);
        });

        Schema::create('staff_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('staff_chat_conversations')->cascadeOnDelete();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_mime', 120)->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();
            $table->timestamps();
            $table->index(['conversation_id', 'created_at']);
        });

        $now = now();
        DB::table('permissions')->updateOrInsert(
            ['name' => 'admin.chats.manage'],
            ['display_name' => 'Manage Staff Chats', 'module' => 'admin', 'description' => 'Message staff in the same store', 'is_active' => true, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now]
        );
        DB::table('permissions')->updateOrInsert(
            ['name' => 'admin.chats.cross-branch'],
            ['display_name' => 'Chat Across Branches', 'module' => 'admin', 'description' => 'Message staff in other branches of the same store', 'is_active' => true, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now]
        );
        $permissionId = DB::table('permissions')->where('name', 'admin.chats.manage')->value('id');
        DB::table('navigation_items')->updateOrInsert(
            ['name' => 'admin.chats'],
            ['display_name' => 'Staff Chats', 'module' => 'store', 'section' => 'General', 'route_name' => 'staff.chats', 'route_path' => '/staff-chats', 'icon' => 'pi pi-comments', 'parent_id' => null, 'display_order' => 90, 'is_active' => true, 'meta' => null, 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now]
        );
        $navigationId = DB::table('navigation_items')->where('name', 'admin.chats')->value('id');
        DB::table('navigation_permissions')->updateOrInsert(
            ['navigation_item_id' => $navigationId, 'permission_id' => $permissionId],
            ['created_at' => $now, 'updated_at' => $now]
        );
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')->whereIn('name', ['admin.chats.manage', 'admin.chats.cross-branch'])->pluck('id');
        $navigationId = DB::table('navigation_items')->where('name', 'admin.chats')->value('id');
        if ($navigationId) DB::table('navigation_permissions')->where('navigation_item_id', $navigationId)->delete();
        DB::table('navigation_items')->where('name', 'admin.chats')->delete();
        if ($permissionIds->isNotEmpty()) DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('name', ['admin.chats.manage', 'admin.chats.cross-branch'])->delete();
        Schema::dropIfExists('staff_chat_messages');
        Schema::dropIfExists('staff_chat_participants');
        Schema::dropIfExists('staff_chat_conversations');
    }
};
