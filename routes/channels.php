<?php

use App\Models\Core\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('users.{userId}.notifications', function (User $user, int $userId): bool {
    return (int) $user->id === $userId;
});
