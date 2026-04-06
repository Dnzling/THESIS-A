<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Models\Hr\Employee;
use App\Models\Admin\SubscriptionPlan;
use App\Models\Store\Store;
use App\Models\Store\TrialOnboardingProfile;
use App\Models\Store\Branch;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StoreSettingsController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $store = $this->resolveStoreForUser($user);
        $profile = $user?->trialOnboardingProfile;
        $availablePlans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get([
                'plan_key',
                'name',
                'description',
                'monthly_price',
                'yearly_price',
                'features',
                'is_featured',
            ])
            ->map(function (SubscriptionPlan $plan) {
                return [
                    'key' => $plan->plan_key,
                    'label' => $plan->name,
                    'description' => $plan->description,
                    'amount_php' => (float) $plan->monthly_price,
                    'months' => 1,
                    'tier' => $plan->plan_key,
                    'features' => is_array($plan->features) ? $plan->features : [],
                    'is_featured' => (bool) $plan->is_featured,
                ];
            })
            ->values();

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
                    'address' => $store?->address,
                    'city' => $store?->city,
                    'province' => $store?->province,
                    'type' => $store?->type,
                    'store_code' => $store?->store_code,
                    'status' => $store?->status,
                    'contact_person' => is_array($store?->settings) ? ($store->settings['contact_person'] ?? null) : null,
                ],
                'branches' => $store?->branches()
                    ->orderByDesc('is_main_branch')
                    ->orderBy('name')
                    ->get([
                        'id',
                        'name',
                        'address',
                        'city',
                        'province',
                        'barangay',
                        'contact_number',
                        'status',
                        'branch_code',
                        'is_main_branch',
                        'branch_type',
                    ]) ?? [],
                'attendance' => $this->resolveAttendanceSettings($store?->id),
                'subscription' => [
                    'tier' => $store?->subscription_tier ?? 'free',
                    'ends_at' => $endsAt?->toDateString(),
                    'days_remaining' => $daysRemaining,
                    'status' => $status,
                ],
                'available_plans' => $availablePlans,
                'verification' => $this->resolveVerificationStatus($store),
                'onboarding' => [
                    'plan' => $profile?->plan ?? 'simple',
                    'modules' => $profile?->modules ?? [],
                    'completed_at' => $profile?->completed_at?->toDateTimeString(),
                    'tier' => $this->resolveTier($profile?->employee_range ?? ''),
                ],
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|nullable|email|max:255',
            'phone' => 'sometimes|nullable|string|max:50',
            'address' => 'sometimes|nullable|string|max:255',
            'city' => 'sometimes|nullable|string|max:255',
            'province' => 'sometimes|nullable|string|max:255',
            'type' => 'sometimes|nullable|string|max:50',
            'store_code' => 'sometimes|nullable|string|max:50',
            'contact_person' => 'sometimes|nullable|string|max:255',
        ]);

        $user = $request->user();
        $store = $this->resolveStoreForUser($user);

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Store not found for this user.',
            ], 404);
        }

        $store->fill(collect($validated)->except('contact_person')->toArray());

        $settings = is_array($store->settings) ? $store->settings : [];
        if (array_key_exists('contact_person', $validated)) {
            $settings['contact_person'] = $validated['contact_person'];
        }
        $store->settings = $settings;
        $store->save();

        return response()->json([
            'success' => true,
            'message' => 'Store profile updated successfully.',
            'data' => [
                'store' => [
                    'id' => $store->id,
                    'name' => $store->name,
                    'email' => $store->email,
                    'phone' => $store->phone,
                    'address' => $store->address,
                    'city' => $store->city,
                    'province' => $store->province,
                    'type' => $store->type,
                    'store_code' => $store->store_code,
                    'status' => $store->status,
                    'contact_person' => $settings['contact_person'] ?? null,
                ],
            ],
        ]);
    }

    private function resolveVerificationStatus($store): array
    {
        if (!$store) {
            return [
                'store_status' => 'pending',
                'is_verified' => false,
                'is_under_review' => false,
                'is_pending' => true,
                'is_rejected' => false,
                'submitted_at' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
                'documents_submitted' => false,
            ];
        }

        $store->loadMissing('verification');
        $verification = $store->verification;

        $workflowStatus = 'pending';
        if ($verification) {
            if (!is_null($verification->reviewed_at) && !is_null($verification->rejection_reason)) {
                $workflowStatus = 'rejected';
            } elseif (!is_null($verification->reviewed_at)) {
                $workflowStatus = 'approved';
            } else {
                $workflowStatus = 'reviewing';
            }
        }

        return [
            'store_status' => $workflowStatus,
            'is_verified' => $workflowStatus === 'approved',
            'is_under_review' => $workflowStatus === 'reviewing',
            'is_pending' => $workflowStatus === 'pending',
            'is_rejected' => $workflowStatus === 'rejected',
            'submitted_at' => $verification->submitted_at ?? null,
            'reviewed_at' => $verification->reviewed_at ?? null,
            'rejection_reason' => $verification->rejection_reason ?? null,
            'documents_submitted' => (bool) $verification,
        ];
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
        $store = $this->resolveStoreForUser($user);

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
        $store = $this->resolveStoreForUser($user);

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
        $store = $this->resolveStoreForUser($user);

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
        $store = $this->resolveStoreForUser($user);

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

    private function resolveStoreForUser(?User $user): ?Store
    {
        if (!$user) {
            return null;
        }

        if ($user->relationLoaded('store') && $user->store) {
            return $user->store;
        }

        if (!empty($user->store_id)) {
            return Store::query()->find((int) $user->store_id);
        }

        $employee = Employee::query()
            ->where('user_id', $user->id)
            ->first(['store_id', 'branch_id']);

        if (!empty($employee?->store_id)) {
            return Store::query()->find((int) $employee->store_id);
        }

        if (!empty($employee?->branch_id)) {
            $branchStoreId = Branch::query()
                ->where('id', (int) $employee->branch_id)
                ->value('store_id');

            if (!empty($branchStoreId)) {
                return Store::query()->find((int) $branchStoreId);
            }
        }

        return null;
    }
}
