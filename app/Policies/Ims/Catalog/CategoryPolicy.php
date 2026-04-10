<?php

namespace App\Policies\Ims\Catalog;

use App\Models\Core\User;
use App\Models\Ims\Catalog\Category;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'storeAdmin', 'inventory', 'sales']);
    }
    
    public function view(User $user, Category $category): bool
    {
        return $user->hasAnyRole(['admin', 'storeAdmin', 'inventory', 'sales']);
    }
    
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'storeAdmin']);
    }
    
    public function update(User $user, Category $category): bool
    {
        return $user->hasAnyRole(['admin', 'storeAdmin']);
    }
    
    public function delete(User $user, Category $category): bool
    {
        // Only admin can delete, and only if no products
        return $user->hasRole('admin') && $category->canBeDeleted();
    }
}