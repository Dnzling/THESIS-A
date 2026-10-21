<?php

use App\Http\Controllers\Api\Core\StaffChatController;
use Illuminate\Support\Facades\Route;

Route::middleware('can:admin.chats.manage')->prefix('staff-chats')->group(function () {
    Route::get('/staff', [StaffChatController::class, 'staff']);
    Route::get('/conversations', [StaffChatController::class, 'conversations']);
    Route::post('/conversations', [StaffChatController::class, 'start']);
    Route::get('/conversations/{conversationId}/messages', [StaffChatController::class, 'messages']);
    Route::post('/conversations/{conversationId}/messages', [StaffChatController::class, 'send']);
    Route::get('/attachments/{message}', [StaffChatController::class, 'attachment'])->name('staff-chat.attachments');
});
