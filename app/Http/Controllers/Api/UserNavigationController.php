<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Core\NavigationItem;
use App\Models\Core\Permission;
use App\Services\Core\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class UserNavigationController extends Controller
{
    private const ALL_STORE_MODULES = [
        'inventory',
        'procurement',
        'sales',
        'hr',
        'logistics',
        'finance',
        'supplier',
        'ecommerce',
    ];

    public function __construct(protected PermissionService $permissionService)
    {
    }
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

            // Refresh user to ensure store_id comes from users table
            $user = $user->fresh(['role', 'store', 'trialOnboardingProfile']);
            
            // Get permissions based on role
            $permissionPayload = $this->getUserPermissionsWithMeta($user);
            $permissions = $permissionPayload['permissions'];
            
            // Get navigation items user has access to
            $navigation = $this->getUserNavigationItems($user, $permissions);

            return response()->json([
                'success' => true,
                'permissions' => $permissions,
                'permissions_meta' => $permissionPayload['meta'],
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
        return $this->getUserPermissionsWithMeta($user)['permissions'];
    }

    /**
     * Get user permissions plus counts before/after filtering.
     */
    protected function getUserPermissionsWithMeta($user = null): array
    {
        $user = $user ?? auth()->user();

        if (!$user || !$user->role) {
            return [
                'permissions' => [],
                'meta' => [
                    'raw_count' => 0,
                    'filtered_count' => 0,
                    'filtered_out_count' => 0,
                ],
            ];
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
        $basePermissions = array_diff($allPermissions, $userRevokes);
        $basePermissions = array_values(array_unique($basePermissions));

        $finalPermissions = $this->filterPermissionsByModules($user, $basePermissions);
        $finalPermissions = $this->filterPermissionsByTier($user, $finalPermissions);
        $finalPermissions = array_values(array_unique($finalPermissions));

        $filteredOut = array_values(array_diff($basePermissions, $finalPermissions));
        sort($filteredOut);

        return [
            'permissions' => $finalPermissions,
            'meta' => [
                'raw_count' => count($basePermissions),
                'filtered_count' => count($finalPermissions),
                'filtered_out_count' => max(0, count($basePermissions) - count($finalPermissions)),
                'filtered_out' => $filteredOut,
            ],
        ];
    }

    /**
     * Get navigation items user can access
     */
    private function getUserNavigationItems($user, array $permissions): array
    {
        $roleName = strtolower($user->role->name ?? '');
        $isSupplierRole = str_contains($roleName, 'supplier');
        $allowedModules = $this->getStoreAdminAllowedModules($user);
        $tier = $this->getStoreAdminTier($user);

        // Get all active navigation items
        $navigationItems = NavigationItem::where('is_active', true)
            ->whereNull('deleted_at')
            ->with(['permissions'])
            ->orderBy('display_order')
            ->get();

        $accessibleNavigation = [];

        foreach ($navigationItems as $navItem) {
            // Module-level gating to avoid showing routes for unrelated roles
            if ($navItem->module === 'hr' && ($navItem->section === 'job_hiring')) {
                if (!in_array($roleName, ['hr_manager', 'hr_admin', 'hr'])) {
                    continue;
                }
            }

            if ($navItem->module === 'merchandising') {
                $allowedRoles = [
                    'super_admin',
                    'owner',
                    'store_admin',
                    'store_manager',
                    'warehouse_manager',
                    'inventory_staff',
                    'sales_staff',
                    'supplier_coordinator',
                ];
                if (!in_array($roleName, $allowedRoles)) {
                    continue;
                }
            }

            if ($roleName === 'store_admin' && !empty($allowedModules)) {
                $whitelist = ['store_admin', 'store', 'system', 'admin'];
                if (!in_array($navItem->module, $allowedModules, true) && !in_array($navItem->module, $whitelist, true)) {
                    continue;
                }
            }

        if ($roleName === 'store_admin' && $tier === 'small' && in_array($navItem->module, ['hr', 'finance'], true)) {
            continue;
        }

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

        if ($roleName === 'store_admin' && $tier === 'small') {
            $accessibleNavigation[] = [
                'id' => 9001,
                'name' => 'store.employees',
                'display_name' => 'Employees',
                'module' => 'store_admin',
                'route_name' => 'store.employees',
                'route_path' => '/system/employees',
                'icon' => 'pi pi-users',
                'parent_id' => null,
                'display_order' => 20,
                'section' => 'Quick Start',
                'meta' => [],
                'is_active' => true,
                'badge_count' => 0,
            ];

            $accessibleNavigation[] = [
                'id' => 9002,
                'name' => 'store.finance',
                'display_name' => 'Finance',
                'module' => 'store_admin',
                'route_name' => 'store.finance',
                'route_path' => '/system/finance',
                'icon' => 'pi pi-wallet',
                'parent_id' => null,
                'display_order' => 21,
                'section' => 'Quick Start',
                'meta' => [],
                'is_active' => true,
                'badge_count' => 0,
            ];
        }

        return $accessibleNavigation;
    }

    private function getStoreAdminAllowedModules($user): array
    {
        if (!$user || strtolower($user->role?->name ?? '') !== 'store_admin') {
            return [];
        }

        $subscriptionTier = strtolower((string) ($user->store?->subscription_tier ?? ''));
        $onboardingPlan = strtolower((string) ($user->trialOnboardingProfile?->plan ?? ''));
        if ($subscriptionTier === 'unlimited' || $onboardingPlan === 'unlimited') {
            return self::ALL_STORE_MODULES;
        }

        $storeModules = [];
        if ($user->store && is_array($user->store->settings ?? null)) {
            $storeModules = $user->store->settings['enabled_modules'] ?? [];
        }

        $profileModules = $user->trialOnboardingProfile?->modules ?? [];

        return array_values(array_unique(array_filter(array_merge($storeModules, $profileModules))));
    }

    private function filterPermissionsByModules($user, array $permissions): array
    {
        $allowedModules = $this->getStoreAdminAllowedModules($user);

        if (empty($allowedModules)) {
            return $permissions;
        }

        $alwaysAllowPrefixes = ['store.', 'store_admin.', 'system.', 'profile.', 'auth.'];

        return array_values(array_filter($permissions, function ($permission) use ($allowedModules, $alwaysAllowPrefixes) {
            foreach ($alwaysAllowPrefixes as $prefix) {
                if (str_starts_with($permission, $prefix)) {
                    return true;
                }
            }

            foreach ($allowedModules as $module) {
                if (str_starts_with($permission, $module . '.')) {
                    return true;
                }
            }

            return false;
        }));
    }

    private function filterPermissionsByTier($user, array $permissions): array
    {
        $tier = $this->getStoreAdminTier($user);

        if ($tier === 'enterprise') {
            return $permissions;
        }

        $modulePrefixes = ['inventory.', 'procurement.', 'sales.', 'finance.', 'hr.'];
        $alwaysAllowPrefixes = ['store.', 'store_admin.', 'system.', 'profile.', 'auth.'];

        $smallDeny = [
            '.approve', '.reject', '.export', '.import', '.delete', '.manage',
            '.reports', '.analytics', '.settings', '.roles', '.permissions',
            '.budget', '.price', '.audit',
        ];

        $midDeny = [
            '.export', '.import', '.settings', '.roles', '.permissions',
            '.audit',
        ];

        $denyList = $tier === 'small' ? $smallDeny : $midDeny;

        return array_values(array_filter($permissions, function ($permission) use ($modulePrefixes, $alwaysAllowPrefixes, $denyList) {
            foreach ($alwaysAllowPrefixes as $prefix) {
                if (str_starts_with($permission, $prefix)) {
                    return true;
                }
            }

            $matchesModule = false;
            foreach ($modulePrefixes as $prefix) {
                if (str_starts_with($permission, $prefix)) {
                    $matchesModule = true;
                    break;
                }
            }

            if (!$matchesModule) {
                return false;
            }

            foreach ($denyList as $fragment) {
                if (str_contains($permission, $fragment)) {
                    return false;
                }
            }

            return true;
        }));
    }

    private function getStoreAdminTier($user): string
    {
        if (!$user || strtolower($user->role?->name ?? '') !== 'store_admin') {
            return 'enterprise';
        }

        $range = $user->trialOnboardingProfile?->employee_range ?? '';
        if ($range === '1-5') return 'small';
        if (in_array($range, ['6-20', '21-50'], true)) return 'mid';
        return 'enterprise';
    }

    /**
     * Check if user can access navigation item
     */
    private function canAccessNavigationItem($navItem, array $userPermissions): bool
    {
        $roleName = strtolower(auth()->user()?->role?->name ?? '');

        // Supplier portal should always be visible to supplier roles
        if ($navItem->module === 'supplier' && str_contains($roleName, 'supplier')) {
            return true;
        }

        // Guard admin/finance modules if no permissions are linked yet
        if ($navItem->permissions->isEmpty() && in_array($navItem->module, ['admin', 'finance'], true)) {
            return false;
        }

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
                $storeId = (int) ($user?->store_id ?? 0);
                if ($storeId <= 0) {
                    return 0;
                }
                return DB::table('products')
                    ->where('store_id', $storeId)
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

    /**
     * DEBUG: Return full permission context for the authenticated user.
     */
    public function debugPermissions(Request $request)
    {
        if (!config('app.debug')) {
            abort(404);
        }

        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $roleName = $user->role?->name;
        $rolePermissions = DB::table('role_permissions')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('role_permissions.role_id', $user->role_id)
            ->where('permissions.is_active', true)
            ->whereNull('permissions.deleted_at')
            ->orderBy('permissions.name')
            ->pluck('permissions.name')
            ->all();

        $userPermissions = $this->permissionService->getUserPermissions($user, $user->store_id);

        $probe = [
            'merchandising.products.view',
            'merchandising.categories.view',
            'merchandising.tags.view',
            'merchandising.attributes.view',
        ];

        $probeResults = [];
        $probeGateResults = [];
        foreach ($probe as $permission) {
            $probeResults[$permission] = in_array($permission, $userPermissions, true);
            $probeGateResults[$permission] = Gate::forUser($user)->allows($permission);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'role_name' => $roleName,
                'store_id' => $user->store_id,
                'branch_id' => $user->branch_id,
            ],
            'role_permissions' => $rolePermissions,
            'user_permissions' => $userPermissions,
            'probe' => $probeResults,
            'probe_gate' => $probeGateResults,
        ]);
    }
}
