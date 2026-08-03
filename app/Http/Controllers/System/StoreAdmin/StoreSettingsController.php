<?php

namespace App\Http\Controllers\System\StoreAdmin;

use App\Http\Controllers\Controller;
use App\Models\Admin\SubscriptionPlan;
use App\Models\Admin\ViolationReport;
use App\Models\Core\User;
use App\Models\Hr\Employee;
use App\Models\Store\Branch;
use App\Models\Store\Store;
use App\Services\Modules\ModuleAccessService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StoreSettingsController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $store = $this->resolveStoreForUser($user);
        if ($store) {
            $store->loadMissing('subscriptionPlan');
        }
        $subscriptionPlan = $store
            ? SubscriptionPlan::query()->find((int) $store->getRawOriginal('subscription_tier'))
            : null;
        $profile = $user?->trialOnboardingProfile;
        $storeId = (int) ($store?->id ?? 0);

        $enabledModuleKeys = $storeId > 0
            ? app(ModuleAccessService::class)->enabledModuleKeysForStore($storeId)
            : [];

        $enabledModules = empty($enabledModuleKeys)
            ? []
            : DB::table('modules')
                ->whereIn('key', $enabledModuleKeys)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['key', 'name'])
                ->map(fn ($row) => ['key' => (string) $row->key, 'name' => (string) $row->name])
                ->values()
                ->all();

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
            ->values()
            ->all();

        $endsAt = $store?->subscription_ends_at ? Carbon::parse($store->subscription_ends_at) : null;
        return Inertia::render('System/StoreAdmin/Settings', [
            'title' => 'Settings',
            'subtitle' => 'Trial & Configuration',
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
                'status_details' => $store ? $this->resolveStoreStatusDetails((int) $store->id, (string) $store->status) : null,
                'contact_person' => is_array($store?->settings) ? ($store->settings['contact_person'] ?? null) : null,
            ],
            'payments' => $this->resolvePaymentSettings($store),
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
                ])
                ->toArray() ?? [],
            'attendance' => $this->resolveAttendanceSettings($store?->id),
            'subscription' => [
                'tier' => $subscriptionPlan?->plan_key ?? 'free',
                'plan_label' => $subscriptionPlan?->name ?? 'Free',
                'ends_at' => $endsAt?->toDateString(),
                'modules' => $enabledModules,
            ],
            'available_plans' => $availablePlans,
            'verification' => $this->resolveVerificationStatus($store),
            'onboarding' => [
                'plan' => $profile?->plan ?? 'simple',
                'modules' => $profile?->modules ?? [],
                'completed_at' => $profile?->completed_at?->toDateTimeString(),
                'tier' => $this->resolveTier($profile?->employee_range ?? ''),
            ],
        ]);
    }

    public function updatePaymentSettings(Request $request)
    {
        $user = $request->user();
        $store = $this->resolveStoreForUser($user);
        if (!$store) {
            abort(404, 'Store not found for this user.');
        }

        $validated = $request->validate([
            'paymongo_payment_methods' => 'required|array|min:1',
            'paymongo_payment_methods.*' => 'string|in:card,gcash,grab_pay,paymaya',
        ]);

        $settings = is_array($store->settings) ? $store->settings : [];
        $payments = is_array($settings['payments'] ?? null) ? $settings['payments'] : [];
        $paymongo = is_array($payments['paymongo'] ?? null) ? $payments['paymongo'] : [];

        $paymongo['payment_method_allowed'] = array_values(array_unique($validated['paymongo_payment_methods']));
        $payments['paymongo'] = $paymongo;
        $settings['payments'] = $payments;

        $store->settings = $settings;
        $store->save();

        return back()->with('success', 'Payment settings updated.');
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
            abort(404, 'Store not found for this user.');
        }

        $store->fill(collect($validated)->except('contact_person')->toArray());

        $settings = is_array($store->settings) ? $store->settings : [];
        if (array_key_exists('contact_person', $validated)) {
            $settings['contact_person'] = $validated['contact_person'];
        }
        $store->settings = $settings;
        $store->save();

        return back()->with('success', 'Store profile updated.');
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
            abort(404, 'Store not found for this user.');
        }

        $branchQuery = Branch::query()->where('store_id', $store->id);
        if (!empty($validated['branch_id'])) {
            $branchQuery->where('id', $validated['branch_id']);
        }

        $branch = $branchQuery->orderByDesc('is_main_branch')->first();

        if (!$branch) {
            abort(404, 'Main branch not found for this store.');
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

        return back()->with('success', 'Attendance location updated.');
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

    private function resolvePaymentSettings(?Store $store): array
    {
        $methods = ['gcash'];
        if ($store && is_array($store->settings)) {
            $payments = $store->settings['payments'] ?? null;
            if (is_array($payments) && is_array($payments['paymongo'] ?? null)) {
                $allowed = $payments['paymongo']['payment_method_allowed'] ?? null;
                if (is_array($allowed) && count($allowed) > 0) {
                    $methods = array_values(array_unique(array_map('strval', $allowed)));
                }
            }
        }

        return [
            'paymongo' => [
                'payment_method_allowed' => $methods,
            ],
        ];
    }

    private function resolveAttendanceSettings(?int $storeId): array
    {
        if (!$storeId) {
            return [
                'branch_id' => null,
                'address' => '',
                'barangay' => '',
                'city' => '',
                'province' => '',
                'latitude' => null,
                'longitude' => null,
                'geofence_radius_m' => 5,
                'geofence_enabled' => true,
            ];
        }

        $branch = Branch::query()
            ->where('store_id', $storeId)
            ->orderByDesc('is_main_branch')
            ->first([
                'id',
                'address',
                'barangay',
                'city',
                'province',
                'latitude',
                'longitude',
                'geofence_radius_m',
            ]);

        $store = Store::query()->find($storeId, ['id', 'settings']);
        $settings = is_array($store?->settings) ? $store->settings : [];

        return [
            'branch_id' => $branch?->id,
            'address' => (string) ($branch?->address ?? ''),
            'barangay' => (string) ($branch?->barangay ?? ''),
            'city' => (string) ($branch?->city ?? ''),
            'province' => (string) ($branch?->province ?? ''),
            'latitude' => $branch?->latitude !== null ? (float) $branch->latitude : null,
            'longitude' => $branch?->longitude !== null ? (float) $branch->longitude : null,
            'geofence_radius_m' => (int) ($branch?->geofence_radius_m ?? 5),
            'geofence_enabled' => array_key_exists('attendance_geofence_enabled', $settings)
                ? (bool) $settings['attendance_geofence_enabled']
                : true,
        ];
    }

    private function resolveTier(string $employeeRange): string
    {
        $range = strtolower(trim($employeeRange));
        if ($range === 'unlimited') {
            return 'unlimited';
        }
        if ($range === 'simple') {
            return 'simple';
        }
        return 'simple';
    }

    private function resolveVerificationStatus(?Store $store): array
    {
        if (!$store) {
            return [
                'store_status' => 'pending',
                'submitted_at' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
                'documents_submitted' => false,
            ];
        }

        $store->loadMissing('verification');
        $verification = $store->verification;
        $status = (string) ($verification?->status ?? 'pending');

        return [
            'store_status' => $status,
            'submitted_at' => $verification?->submitted_at?->toDateTimeString(),
            'reviewed_at' => $verification?->reviewed_at?->toDateTimeString(),
            'rejection_reason' => $verification?->rejection_reason,
            'documents_submitted' => (bool) ($verification?->documents_submitted ?? false),
        ];
    }

    private function resolveStoreStatusDetails(int $storeId, string $status): ?array
    {
        $status = strtolower(trim($status));
        if (!in_array($status, ['suspended', 'banned'], true)) {
            return null;
        }

        $latest = ViolationReport::query()
            ->where('store_id', $storeId)
            ->orderByDesc('id')
            ->first([
                'actioned_at',
                'action_reason',
                'suspension_days_remaining',
            ]);

        if (!$latest) {
            return null;
        }

        return [
            'actioned_at' => $latest->actioned_at?->toDateTimeString(),
            'action_reason' => (string) ($latest->action_reason ?? ''),
            'suspension_days_remaining' => $latest->suspension_days_remaining !== null ? (int) $latest->suspension_days_remaining : null,
        ];
    }
}
