<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Core\NavigationItem;
use App\Models\Core\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserNavigationController extends Controller
{
    /**
     * Get user's navigation items and permissions based on their role
     */
    public function getUserNavigation(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Load user's role with permissions
            $user->load(['role']);
            
            // Get permissions based on role
            $permissions = $this->getUserPermissions($user);
            
            // Get navigation items user has access to
            $navigation = $this->getUserNavigationItems($user, $permissions);

            return response()->json([
                'success' => true,
                'permissions' => $permissions,
                'navigation' => $navigation
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load user navigation', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load navigation',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get user permissions from their role
     */
    protected function getUserPermissions($user = null): array
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return [];
        }

        if (!$user->role) {
            return [];
        }

        // Get permissions from role_permissions pivot table
        $rolePermissions = DB::table('role_permissions')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('role_permissions.role_id', $user->role_id)
            ->where('permissions.is_active', true)
            ->whereNull('permissions.deleted_at')
            ->pluck('permissions.name')
            ->toArray();

        // Get user-specific permission overrides
        $userGrants = DB::table('user_permissions')
            ->join('permissions', 'user_permissions.permission_id', '=', 'permissions.id')
            ->where('user_permissions.user_id', $user->id)
            ->where('user_permissions.type', 'grant')
            ->where('permissions.is_active', true)
            ->whereNull('permissions.deleted_at')
            ->pluck('permissions.name')
            ->toArray();

        $userRevokes = DB::table('user_permissions')
            ->join('permissions', 'user_permissions.permission_id', '=', 'permissions.id')
            ->where('user_permissions.user_id', $user->id)
            ->where('user_permissions.type', 'revoke')
            ->pluck('permissions.name')
            ->toArray();

        // Merge role permissions with grants, then remove revokes
        $allPermissions = array_merge($rolePermissions, $userGrants);
        $finalPermissions = array_diff($allPermissions, $userRevokes);

        return array_values(array_unique($finalPermissions));
    }

    /**
     * Get navigation items user can access
     */
    private function getUserNavigationItems($user, array $permissions): array
    {
        // Get all active navigation items
        $navigationItems = NavigationItem::where('is_active', true)
            ->whereNull('deleted_at')
            ->with(['permissions'])
            ->orderBy('display_order')
            ->get();

        $accessibleNavigation = [];

        foreach ($navigationItems as $navItem) {
            // Check if user has permission to access this navigation item
            if ($this->canAccessNavigationItem($navItem, $permissions)) {
                $accessibleNavigation[] = [
                    'id' => $navItem->id,
                    'name' => $navItem->name,
                    'display_name' => $navItem->display_name,
                    'module' => $navItem->module,
                    'route_name' => $navItem->route_name,
                    'route_path' => $navItem->route_path,
                    'icon' => $navItem->icon,
                    'parent_id' => $navItem->parent_id,
                    'display_order' => $navItem->display_order,
                    'section' => $navItem->meta['section'] ?? null, // Get section from meta
                    'meta' => $navItem->meta,
                    'is_active' => $navItem->is_active,
                    'badge_count' => $this->getBadgeCount($navItem, $user)
                ];
            }
        }

        return $accessibleNavigation;
    }

    /**
     * Check if user can access navigation item
     */
    private function canAccessNavigationItem($navItem, array $userPermissions): bool
    {
        // If no permissions required, everyone can access
        if ($navItem->permissions->isEmpty()) {
            return true;
        }

        // Check if user has any of the required permissions
        $requiredPermissions = $navItem->permissions->pluck('name')->toArray();
        
        return !empty(array_intersect($requiredPermissions, $userPermissions));
    }

    /**
     * Get badge count for navigation item (e.g., pending items)
     */
    private function getBadgeCount($navItem, $user): int
    {
        // You can customize this based on your needs
        // For example, show count of pending approvals, new items, etc.
        
        switch ($navItem->name) {
            case 'merchandising.products':
                // Count inactive products
                return DB::table('products')
                    ->where('is_active', false)
                    ->count();
                
            default:
                return 0;
        }
    }

    /**
     * Check if user has specific permission
     */
    public function checkPermission(Request $request)
    {
        $request->validate([
            'permission' => 'required|string'
        ]);

        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'has_permission' => false
            ], 401);
        }

        $permissions = $this->getUserPermissions($user);
        $hasPermission = in_array($request->permission, $permissions);

        return response()->json([
            'success' => true,
            'has_permission' => $hasPermission
        ]);
    }
}