<?php

namespace App\Services\Hr;

use App\Models\Core\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EmployeeAvatarService
{
    public function save(User $user, UploadedFile $file): string
    {
        $oldPath = $user->avatar_path;
        $path = $file->store("employee-avatars/{$user->store_id}/{$user->id}", 'public');

        $user->avatar_path = $path;
        $user->save();

        if ($oldPath && str_starts_with($oldPath, 'employee-avatars/') && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }

        return $user->avatar_url;
    }
}
