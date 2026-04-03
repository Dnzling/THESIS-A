<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Store\TrialOnboardingProfile;
use App\Models\Store\Branch;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StoreSettingsController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $store = $user?->store;
        $profile = $user?->trialOnboardingProfile;

        $endsAt = $store?->subscription_ends_at ? Carbon::parse($store->subscription_ends_at) : null;
        $daysRemaining = $endsAt ? $endsAt->diffInDays(Carbon::now(), false) * -1 : null;

        $status = 'trial';
        if ($store?->subscription_tier && $store->subscription_tier !== 'free') {
            $status = $endsAt && $daysRemaining !== null && $daysRemaining < 0 ? 'expired' : 'active';
        } elseif ($endsAt && $daysRemaining !== null && $daysRemaining < 0) {
            $status = 'expired';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'store' => [
                    'id' => $store?->id,
                    'name' => $store?->name,
                    'email' => $store?->email,
                    'phone' => $store?->phone,
                    'contact_person' => is_array($store?->settings) ? ($store->settings['contact_person'] ?? null) : null,
                ],
                'attendance' => $this->resolveAttendanceSettings($store?->id),
                'subscription' => [
                    'tier' => $store?->subscription_tier ?? 'free',
                    'ends_at' => $endsAt?->toDateString(),
                    'days_remaining' => $daysRemaining,
                    'status' => $status,
                ],
                'onboarding' => [
                    'plan' => $profile?->plan ?? 'simple',
                    'modules' => $profile?->modules ?? [],
                    'completed_at' => $profile?->completed_at?->toDateTimeString(),
                    'tier' => $this->resolveTier($profile?->employee_range ?? ''),
                ],
            ],
        ]);
    }

    private function resolveAttendanceSettings(?int $storeId): array
    {
        if (!$storeId) {
            return [
                'branch_id' => null,
                'branch_name' => null,
                'address' => null,
                'barangay' => null,
                'city' => null,
                'province' => null,
                'latitude' => null,
                'longitude' => null,
                'geofence_radius_m' => 5,
            ];
        }

        $branch = Branch::query()
            ->where('store_id', $storeId)
            ->orderByDesc('is_main_branch')
            ->orderBy('id')
            ->first();

        return [
            'branch_id' => $branch?->id,
            'branch_name' => $branch?->name,
            'address' => $branch?->address,
            'barangay' => $branch?->barangay,
            'city' => $branch?->city,
            'province' => $branch?->province,
            'latitude' => $branch?->latitude,
            'longitude' => $branch?->longitude,
            'geofence_radius_m' => $branch?->geofence_radius_m ?? 5,
            'geofence_enabled' => $this->resolveGeofenceEnabled($storeId),
        ];
    }

    private function resolveGeofenceEnabled(?int $storeId): bool
    {
        if (!$storeId) return true;
        $store = \App\Models\Store\Store::find($storeId);
        $settings = is_array($store?->settings) ? $store->settings : [];
        return (bool) ($settings['attendance_geofence_enabled'] ?? true);
    }

    private function resolveTier(string $range): string
    {
        if ($range === '1-5') return 'small';
        if (in_array($range, ['6-20', '21-50'], true)) return 'mid';
        return 'enterprise';
    }

    public function updateModules(Request $request)
    {
        $validated = $request->validate([
            'modules' => 'required|array|min:1',
            'modules.*' => 'required|string|max:50',
        ]);

        $user = $request->user();
        $store = $user?->store;

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Store not found for this user.',
            ], 404);
        }

        $profile = TrialOnboardingProfile::where('user_id', $user->id)->first();

        if (!$profile || !$profile->completed_at) {
            return response()->json([
                'success' => false,
                'message' => 'Complete trial onboarding before updating modules.',
            ], 422);
        }

        $settings = is_array($store->settings) ? $store->settings : [];
        $settings['enabled_modules'] = array_values(array_unique($validated['modules']));
        $store->settings = $settings;
        $store->save();

        $profile->modules = $settings['enabled_modules'];
        $profile->save();

        return response()->json([
            'success' => true,
            'message' => 'Modules updated successfully.',
            'data' => [
                'modules' => $settings['enabled_modules'],
            ],
        ]);
    }

    public function showHrSettings(Request $request)
    {
        $user = $request->user();
        $store = $user?->store;

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Store not found for this user.',
            ], 404);
        }

        $settings = is_array($store->settings) ? $store->settings : [];
        $defaultLeaveSettings = [
            'vacation' => 15,
            'sick' => 10,
            'personal' => 5,
            'maternity' => 0,
            'paternity' => 0,
            'bereavement' => 0,
            'others' => 0,
        ];
        $leaveDefaults = $settings['hr_leave_defaults'] ?? [];
        if (!is_array($leaveDefaults)) {
            $leaveDefaults = [];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'daily_interview_limit' => (int) ($settings['hr_interview_daily_limit'] ?? 10),
                'leave_defaults' => array_merge($defaultLeaveSettings, $leaveDefaults),
            ],
        ]);
    }

    public function updateHrSettings(Request $request)
    {
        $validated = $request->validate([
            'daily_interview_limit' => 'sometimes|integer|min:1|max:50',
            'leave_defaults' => 'sometimes|array',
            'leave_defaults.*' => 'nullable|numeric|min:0',
        ]);

        $user = $request->user();
        $store = $user?->store;

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Store not found for this user.',
            ], 404);
        }

        $settings = is_array($store->settings) ? $store->settings : [];
        if (array_key_exists('daily_interview_limit', $validated)) {
            $settings['hr_interview_daily_limit'] = (int) $validated['daily_interview_limit'];
        }
        if (array_key_exists('leave_defaults', $validated)) {
            $cleanDefaults = array_filter($validated['leave_defaults'], fn($value) => $value !== null);
            $settings['hr_leave_defaults'] = $cleanDefaults;
        }
        $store->settings = $settings;
        $store->save();

        return response()->json([
            'success' => true,
            'message' => 'HR settings updated.',
            'data' => [
                'daily_interview_limit' => (int) ($settings['hr_interview_daily_limit'] ?? 10),
                'leave_defaults' => $settings['hr_leave_defaults'] ?? [],
            ],
        ]);
    }

    public function updateAttendanceSettings(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'address' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:150',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'geofence_radius_m' => 'nullable|integer|min:0|max:100',
            'geofence_enabled' => 'nullable|boolean',
        ]);

        $user = $request->user();
        $store = $user?->store;

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Store not found for this user.',
            ], 404);
        }

        $branchQuery = Branch::query()->where('store_id', $store->id);
        if (!empty($validated['branch_id'])) {
            $branchQuery->where('id', $validated['branch_id']);
        }

        $branch = $branchQuery->orderByDesc('is_main_branch')->first();

        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Main branch not found for this store.',
            ], 404);
        }

        $branch->update([
            'address' => $validated['address'] ?? $branch->address,
            'barangay' => $validated['barangay'] ?? $branch->barangay,
            'city' => $validated['city'] ?? $branch->city,
            'province' => $validated['province'] ?? $branch->province,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'geofence_radius_m' => $validated['geofence_radius_m'] ?? $branch->geofence_radius_m ?? 5,
        ]);

        $store->update([
            'address' => $branch->address,
            'city' => $branch->city,
            'province' => $branch->province,
            'latitude' => $branch->latitude,
            'longitude' => $branch->longitude,
        ]);

        if (array_key_exists('geofence_enabled', $validated)) {
            $settings = is_array($store->settings) ? $store->settings : [];
            $settings['attendance_geofence_enabled'] = (bool) $validated['geofence_enabled'];
            $store->settings = $settings;
            $store->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Attendance location updated.',
            'data' => $this->resolveAttendanceSettings($store->id),
        ]);
    }
}
