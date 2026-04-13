<?php

namespace App\Http\Middleware;

use App\Models\Store\TrialOnboardingProfile;
use Closure;
use Illuminate\Http\Request;

class EnsureTrialSetupComplete
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !$user->hasRole('store_admin')) {
            return $next($request);
        }

        $path = '/' . ltrim($request->path(), '/');

        if ($this->isAllowedPath($path)) {
            return $next($request);
        }

        $allowedModules = $this->getAllowedModules($user);
        $moduleFromPath = $this->resolveModuleFromPath($path);

        if ($moduleFromPath && !empty($allowedModules) && !in_array($moduleFromPath, $allowedModules, true)) {
            return redirect('/store/setup-required?module=' . $moduleFromPath);
        }

        return $next($request);
    }

    private function isAllowedPath(string $path): bool
    {
        $allow = [
            '/trial-onboarding',
            '/store/setup-required',
            '/store/settings',
            '/system/settings',
            '/system/store/verification',
            '/hr/employees',
            '/finance',
            '/profile',
        ];

        foreach ($allow as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    private function getAllowedModules($user): array
    {
        if (!$user?->store_id) {
            return [];
        }

        /** @var \App\Services\Modules\ModuleAccessService $modules */
        $modules = app(\App\Services\Modules\ModuleAccessService::class);
        return $modules->enabledModuleKeysForStore((int) $user->store_id);
    }

    private function resolveModuleFromPath(string $path): ?string
    {
        $map = [
            '/inventory' => 'inventory',
            '/procurement' => 'procurement',
            '/sales' => 'sales',
            '/hr' => 'hr',
            '/logistics' => 'logistics',
            '/finance' => 'finance',
            '/merchandising' => 'merchandising',
            '/supplier-portal' => 'supplier',
            '/supplier' => 'supplier',
            '/ecommerce' => 'ecommerce',
            '/shop' => 'ecommerce',
        ];

        foreach ($map as $prefix => $module) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return $module;
            }
        }

        return null;
    }

    private function isLitePath(string $path, string $litePath): bool
    {
        return $path === $litePath || str_starts_with($path, $litePath . '/');
    }
}
