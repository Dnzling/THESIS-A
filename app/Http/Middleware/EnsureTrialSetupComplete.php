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

        $profile = TrialOnboardingProfile::where('user_id', $user->id)->first();
        $isComplete = $profile && $profile->completed_at !== null;

        if (!$isComplete) {
            return redirect('/trial-onboarding');
        }

        $allowedModules = $this->getAllowedModules($user);
        $moduleFromPath = $this->resolveModuleFromPath($path);
        $tier = $this->resolveTier($profile);

        if ($tier === 'small') {
            if ($moduleFromPath === 'hr' && !$this->isLitePath($path, '/system/employees')) {
                return redirect('/system/employees');
            }
            if ($moduleFromPath === 'finance' && !$this->isLitePath($path, '/finance')) {
                return redirect('/finance');
            }
        }

        if ($moduleFromPath && !empty($allowedModules) && !in_array($moduleFromPath, $allowedModules, true)) {
            return redirect('/system/setup-required?module=' . $moduleFromPath);
        }

        return $next($request);
    }

    private function isAllowedPath(string $path): bool
    {
        $allow = [
            '/trial-onboarding',
            '/system/setup-required',
            '/system/settings',
            '/system/store/verification',
            '/system/employees',
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

    private function resolveTier(?TrialOnboardingProfile $profile): string
    {
        $range = $profile?->employee_range ?? '';
        if ($range === '1-5') return 'small';
        if (in_array($range, ['6-20', '21-50'], true)) return 'mid';
        return 'enterprise';
    }

    private function isLitePath(string $path, string $litePath): bool
    {
        return $path === $litePath || str_starts_with($path, $litePath . '/');
    }
}
