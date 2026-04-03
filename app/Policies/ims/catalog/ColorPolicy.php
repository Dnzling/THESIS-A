<?php

namespace App\Policies\Ims\Catalog;

use App\Models\Core\User;
use App\Models\Ims\Catalog\Color;

class ColorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'storeAdmin', 'inventory', 'sales']);
    }
    
    public function view(User $user, Color $color): bool
    {
        return $user->hasAnyRole(['admin', 'storeAdmin', 'inventory', 'sales']);
    }
    
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'storeAdmin']);
    }
    
    public function update(User $user, Color $color): bool
    {
        return $user->hasAnyRole(['admin', 'storeAdmin']);
    }
    
    public function delete(User $user, Color $color): bool
    {
        // Only admin can delete, and only if not in use
        return $user->hasRole('admin') && !$color->isInUse();
    }
}