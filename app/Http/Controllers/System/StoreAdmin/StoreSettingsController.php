<?php

namespace App\Http\Controllers\System\StoreAdmin;

use App\Http\Controllers\Controller;
use App\Models\Admin\SubscriptionPlan;
use App\Models\Admin\ViolationReport;
use App\Models\Core\User;
use App\Models\Hr\Employee;
use App\Models\Store\Branch;
use App\Models\Store\Store;
use App\Models\Store\StoreSubscriptionAgreement;
use App\Services\Modules\ModuleAccessService;
use App\Services\Store\CaviteLocationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Mail\OtpVerificationMail;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

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
        $mainBranch = $store?->branches()->orderByDesc('is_main_branch')->first();
        $storeSettings = is_array($store?->settings) ? $store->settings : [];
        $activeAgreement = $store
            ? StoreSubscriptionAgreement::query()->where('store_id', $store->id)->where('status', 'active')->latest('activated_at')->first()
            : null;
        $firstBranch = $store?->branches()->orderBy('id')->first();
        $operatingHours = $firstBranch?->opening_hours ?: ($storeSettings['simple_operating_hours'] ?? []);
        $savedWindows = $storeSettings['fulfillment_windows'] ?? [];
        $branchSchedule = is_array($operatingHours['schedule'] ?? null) ? $operatingHours['schedule'] : [];
        $hasValidOperatingHours = $branchSchedule
            ? collect($branchSchedule)->contains(fn ($hours) => is_array($hours)
                && !empty($hours['opens_at'])
                && !empty($hours['closes_at'])
                && (string) $hours['closes_at'] > (string) $hours['opens_at'])
            : (!empty($operatingHours['days'])
                && !empty($operatingHours['opens_at'])
                && !empty($operatingHours['closes_at'])
                && (string) $operatingHours['closes_at'] > (string) $operatingHours['opens_at']);
        $isSimpleSetupPlan = in_array(strtolower((string) ($subscriptionPlan?->plan_key ?? $store?->subscription_tier)), ['free', 'simple'], true);
        $hasValidWindow = static fn ($window): bool => is_array($window)
            && !empty($window['days'])
            && !empty($window['start'])
            && !empty($window['end'])
            && (string) $window['end'] > (string) $window['start'];
        $setupAttention = [
            'photo_upload' => empty($mainBranch?->logo_path),
            'operating_hours' => $isSimpleSetupPlan && !$hasValidOperatingHours,
            'pickup_window' => !$hasValidWindow($savedWindows['pickup'] ?? null),
            'delivery_window' => !$hasValidWindow($savedWindows['delivery'] ?? null),
        ];
        $setupAttention['fulfillment_windows'] = $setupAttention['pickup_window'] || $setupAttention['delivery_window'];

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
                'barangay' => $mainBranch?->barangay,
                'province' => $store?->province,
                'type' => $store?->type,
                'store_code' => $store?->store_code,
                'status' => $store?->status,
                'status_details' => $store ? $this->resolveStoreStatusDetails((int) $store->id, (string) $store->status) : null,
                'contact_person' => is_array($store?->settings) ? ($store->settings['contact_person'] ?? null) : null,
                'logo_url' => $this->resolveBranchLogoUrl($mainBranch),
                'logo_dimensions' => is_array($mainBranch?->logo_dimensions) ? $mainBranch->logo_dimensions : null,
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
                    'email',
                    'latitude',
                    'longitude',
                    'geofence_enabled',
                    'geofence_radius_m',
                    'logo_path',
                ])
                ->toArray() ?? [],
            'attendance' => $this->resolveAttendanceSettings($store?->id),
            'attendance_geolocation_available' => $store && $this->planIncludesAttendanceRecording($store),
            'operating_hours' => $operatingHours,
            'fulfillment_windows' => $store ? app(\App\Services\Ecommerce\FulfillmentWindowService::class)->settings($store) : null,
            'setup_attention' => $setupAttention,
            'subscription' => [
                'tier' => $subscriptionPlan?->plan_key,
                'plan_label' => $subscriptionPlan?->name ?? 'No plan',
                'ends_at' => $endsAt?->toDateString(),
                'modules' => $enabledModules,
            ],
            'agreement' => $activeAgreement ? [
                'plan_name' => $activeAgreement->plan_name,
                'billing_cycle' => $activeAgreement->billing_cycle,
                'signer_name' => $activeAgreement->signer_name,
                'signed_at' => $activeAgreement->signed_at?->toIso8601String(),
            ] : null,
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

    public function updateOperatingHours(Request $request)
    {
        $store = $this->resolveStoreForUser($request->user());
        abort_unless($store, 404);
        abort_unless(in_array($store->subscription_tier, ['free', 'simple'], true), 403);

        $validated = $request->validate([
            'schedule' => ['required', 'array'],
            'schedule.*.enabled' => ['required', 'boolean'],
            'schedule.*.opens_at' => ['nullable', 'date_format:H:i'],
            'schedule.*.closes_at' => ['nullable', 'date_format:H:i'],
        ]);
        $allowedDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $schedule = [];
        foreach ($allowedDays as $day) {
            $entry = $validated['schedule'][$day] ?? ['enabled' => false];
            if (!$entry['enabled']) continue;
            if (empty($entry['opens_at']) || empty($entry['closes_at']) || $entry['closes_at'] <= $entry['opens_at']) {
                return back()->withErrors(["schedule.$day.closes_at" => 'Each closing time must be later than opening time.']);
            }
            $schedule[$day] = ['day' => $day, 'opens_at' => $entry['opens_at'], 'closes_at' => $entry['closes_at']];
        }
        if (!$schedule) return back()->withErrors(['schedule' => 'Enable at least one operating day.']);

        $settings = is_array($store->settings) ? $store->settings : [];
        $first = reset($schedule);
        $settings['simple_operating_hours'] = ['days' => array_keys($schedule), 'opens_at' => $first['opens_at'], 'closes_at' => $first['closes_at'], 'schedule' => $schedule];
        $store->settings = $settings;
        $store->save();
        $branch = $store->branches()->orderBy('id')->first();
        if ($branch) {
            $openingHours = is_array($branch->opening_hours) ? $branch->opening_hours : [];
            $openingHours['days'] = array_keys($schedule);
            $openingHours['schedule'] = $schedule;
            $openingHours['fulfillment_windows'] = $settings['fulfillment_windows'] ?? ($openingHours['fulfillment_windows'] ?? []);
            $branch->opening_hours = $openingHours;
            $branch->save();
        }

        return back()->with('success', 'Operating hours updated.');
    }

    public function updateFulfillmentWindows(Request $request)
    {
        $store = $this->resolveStoreForUser($request->user());
        abort_unless($store, 404);

        $validated = $request->validate([
            'pickup.days' => ['required', 'array', 'min:1'],
            'pickup.days.*' => ['required', Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])],
            'pickup.start' => ['required', 'date_format:H:i'],
            'pickup.end' => ['required', 'date_format:H:i', 'after:pickup.start'],
            'pickup.lead_days' => ['required', 'integer', 'between:0,14'],
            'delivery.days' => ['required', 'array', 'min:1'],
            'delivery.days.*' => ['required', Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])],
            'delivery.start' => ['required', 'date_format:H:i'],
            'delivery.end' => ['required', 'date_format:H:i', 'after:delivery.start'],
            'delivery.lead_days' => ['required', 'integer', 'between:0,14'],
        ]);

        $settings = is_array($store->settings) ? $store->settings : [];
        $settings['fulfillment_windows'] = $validated;
        $store->settings = $settings;
        $store->save();
        $branch = $store->branches()->orderBy('id')->first();
        if ($branch) {
            $openingHours = is_array($branch->opening_hours) ? $branch->opening_hours : [];
            $openingHours['fulfillment_windows'] = $validated;
            $branch->opening_hours = $openingHours;
            $branch->save();
        }

        return back()->with('success', 'Pickup and delivery windows updated.');
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

    public function updateLogo(Request $request)
    {
        $user = $request->user();
        $store = $this->resolveStoreForUser($user);

        if (!$store) {
            abort(404, 'Store not found for this user.');
        }

        $validated = $request->validate([
            'logo' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
            'original_width' => 'nullable|integer|min:1|max:20000',
            'original_height' => 'nullable|integer|min:1|max:20000',
        ]);

        $file = $validated['logo'];
        $imageSize = @getimagesize($file->getRealPath());
        $savedWidth = (int) ($imageSize[0] ?? 0);
        $savedHeight = (int) ($imageSize[1] ?? 0);

        if ($savedWidth <= 0 || $savedHeight <= 0) {
            return back()->withErrors(['logo' => 'Unable to read the uploaded logo dimensions.']);
        }

        $branch = $store->branches()->orderByDesc('is_main_branch')->orderBy('id')->first();
        if (!$branch) {
            abort(404, 'Main branch not found for this store.');
        }

        $oldPath = $branch->logo_path;
        $path = $file->store("branch-logos/{$store->id}/{$branch->id}", 'public');
        $branch->update([
            'logo_path' => $path,
            'logo_dimensions' => [
                'width' => $savedWidth,
                'height' => $savedHeight,
                'original_width' => (int) ($validated['original_width'] ?? $savedWidth),
                'original_height' => (int) ($validated['original_height'] ?? $savedHeight),
            ],
        ]);

        if ($oldPath && $oldPath !== $path && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return back()->with('success', 'Store logo updated.');
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|nullable|email|max:255',
            'phone' => ['sometimes', 'nullable', 'regex:/^\+63\d{10}$/'],
            'address' => 'sometimes|nullable|string|max:255',
            'city' => 'sometimes|nullable|string|max:255',
            'barangay' => 'sometimes|nullable|string|max:150',
            'province' => 'sometimes|nullable|string|max:255',
            'type' => 'sometimes|nullable|string|max:50',
            'contact_person' => 'sometimes|nullable|string|max:255',
        ]);

        $user = $request->user();
        $store = $this->resolveStoreForUser($user);

        if (!$store) {
            abort(404, 'Store not found for this user.');
        }

        $store->fill(collect($validated)->except(['contact_person'])->toArray());

        if (array_key_exists('barangay', $validated)) {
            $store->branches()->orderByDesc('is_main_branch')->first()?->update(['barangay' => $validated['barangay']]);
        }

        $settings = is_array($store->settings) ? $store->settings : [];
        if (array_key_exists('contact_person', $validated)) {
            $settings['contact_person'] = $validated['contact_person'];
        }
        $store->settings = $settings;
        $store->save();

        return back()->with('success', 'Store profile updated.');
    }

    public function prepareProfileUpdate(Request $request)
    {
        $user = $request->user();
        $store = $this->resolveStoreForUser($user);

        if (!$store) {
            abort(404, 'Store not found for this user.');
        }

        $validated = $request->validate($this->profileUpdateRules());

        if (!$user?->email) {
            return back()->withErrors(['email' => 'No email address is available for verification.']);
        }

        $request->session()->put('store_profile_update.pending', $validated);

        $otp = $user->generateOtp();
        Mail::to($user->email)->send(new OtpVerificationMail($otp, $user->fname));

        return redirect()
            ->route('store.settings.profile.otp')
            ->with('success', 'A verification code was sent to your email.');
    }

    public function showProfileUpdateOtp(Request $request)
    {
        if (!$request->session()->has('store_profile_update.pending')) {
            return redirect()
                ->route('store.settings')
                ->withErrors(['profile' => 'No pending store profile update was found.']);
        }

        return Inertia::render('System/StoreAdmin/ProfileUpdateOtp', [
            'title' => 'Verify Store Update',
            'subtitle' => 'Store Settings',
            'email' => $request->user()?->email,
        ]);
    }

    public function resendProfileUpdateOtp(Request $request)
    {
        if (!$request->session()->has('store_profile_update.pending')) {
            return back()->withErrors(['otp' => 'No pending store profile update was found.']);
        }

        $user = $request->user();
        if (!$user?->email) {
            return back()->withErrors(['email' => 'No email address is available for verification.']);
        }

        $otp = $user->generateOtp();
        Mail::to($user->email)->send(new OtpVerificationMail($otp, $user->fname));

        return back()->with('success', 'A new verification code was sent to your email.');
    }

    public function verifyProfileUpdateOtp(Request $request)
    {
        $validated = $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $user = $request->user();
        $store = $this->resolveStoreForUser($user);
        $pending = $request->session()->get('store_profile_update.pending');

        if (!$store || !is_array($pending)) {
            return redirect()
                ->route('store.settings')
                ->withErrors(['profile' => 'No pending store profile update was found.']);
        }

        if (!$user->isValidOtp($validated['otp'])) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP code.']);
        }

        $user->clearOtp();
        $this->applyProfileUpdate($store, $pending);
        $request->session()->forget('store_profile_update.pending');

        return redirect()
            ->route('store.settings', ['profile_updated' => 1])
            ->with('success', 'Store profile updated.');
    }

    public function requestProfileUpdateOtp(Request $request)
    {
        $user = $request->user();
        if (!$user?->email) {
            return response()->json(['message' => 'No email address is available for verification.'], 422);
        }

        $otp = $user->generateOtp();
        Mail::to($user->email)->send(new OtpVerificationMail($otp, $user->fname));

        return response()->json(['message' => 'A verification code was sent to your email.']);
    }

    private function profileUpdateRules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|nullable|email|max:255',
            'phone' => ['sometimes', 'nullable', 'regex:/^\+63\d{10}$/'],
            'address' => 'sometimes|nullable|string|max:255',
            'city' => 'sometimes|nullable|string|max:255',
            'barangay' => 'sometimes|nullable|string|max:150',
            'province' => 'sometimes|nullable|string|max:255',
            'type' => 'sometimes|nullable|string|max:50',
            'contact_person' => 'sometimes|nullable|string|max:255',
        ];
    }

    private function applyProfileUpdate(Store $store, array $validated): void
    {
        $store->fill(collect($validated)->except(['contact_person'])->toArray());

        if (array_key_exists('barangay', $validated)) {
            $store->branches()->orderByDesc('is_main_branch')->first()?->update(['barangay' => $validated['barangay']]);
        }

        $settings = is_array($store->settings) ? $store->settings : [];
        if (array_key_exists('contact_person', $validated)) {
            $settings['contact_person'] = $validated['contact_person'];
        }
        $store->settings = $settings;
        $store->save();
    }

    public function updateAttendanceSettings(Request $request, CaviteLocationService $locationService)
    {
        $user = $request->user();
        $store = $this->resolveStoreForUser($user);

        if (!$store) {
            abort(404, 'Store not found for this user.');
        }

        $validated = $request->validate($this->attendanceUpdateRules());

        try {
            if (!$locationService->contains((float) $validated['latitude'], (float) $validated['longitude'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'latitude' => 'Choose an attendance location inside Cavite province.',
                ]);
            }
        } catch (\RuntimeException $exception) {
            abort(503, $exception->getMessage());
        }

        $this->applyAttendanceUpdate($store, $validated);

        return back()->with('success', 'Attendance location updated.');
    }

    private function planIncludesAttendanceRecording(Store $store): bool
    {
        return DB::table('plan_permissions')
            ->join('permissions', 'permissions.id', '=', 'plan_permissions.permission_id')
            ->where('plan_permissions.plan_id', $store->getRawOriginal('subscription_tier'))
            ->where('plan_permissions.included', true)
            ->where('permissions.name', 'hr.attendance.record')
            ->where('permissions.is_active', true)
            ->whereNull('permissions.deleted_at')
            ->exists();
    }

    private function attendanceUpdateRules(): array
    {
        return [
            'branch_id' => 'nullable|exists:branches,id',
            'address' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:150',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'geofence_radius_m' => 'required|integer|min:50|max:500',
            'geofence_enabled' => 'nullable|boolean',
        ];
    }

    private function applyAttendanceUpdate(Store $store, array $validated): void
    {
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
            'geofence_radius_m' => $validated['geofence_radius_m'],
            'geofence_enabled' => array_key_exists('geofence_enabled', $validated)
                ? (bool) $validated['geofence_enabled']
                : (bool) $branch->geofence_enabled,
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
                'geofence_radius_m' => 50,
                'geofence_enabled' => false,
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
                'geofence_enabled',
            ]);

        return [
            'branch_id' => $branch?->id,
            'address' => (string) ($branch?->address ?? ''),
            'barangay' => (string) ($branch?->barangay ?? ''),
            'city' => (string) ($branch?->city ?? ''),
            'province' => (string) ($branch?->province ?? ''),
            'latitude' => $branch?->latitude !== null ? (float) $branch->latitude : null,
            'longitude' => $branch?->longitude !== null ? (float) $branch->longitude : null,
            'geofence_radius_m' => max(50, min(500, (int) ($branch?->geofence_radius_m ?? 50))),
            'geofence_enabled' => (bool) ($branch?->geofence_enabled ?? false),
        ];
    }

    private function resolveBranchLogoUrl(?Branch $branch): ?string
    {
        $path = $branch?->logo_path;
        if (!$path) {
            return null;
        }

        $path = (string) $path;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
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
                'store_status' => 'unverified',
                'submitted_at' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
                'documents_submitted' => false,
            ];
        }

        $store->loadMissing('verification');
        $verification = $store->verification;
        $status = 'unverified';
        if ($verification?->submitted_at) {
            $status = 'reviewing';
        }
        if ($verification?->reviewed_at && $verification?->rejection_reason) {
            $status = 'rejected';
        } elseif ($verification?->reviewed_at) {
            $status = 'approved';
        }

        return [
            'store_status' => $status,
            'submitted_at' => $verification?->submitted_at?->toDateTimeString(),
            'reviewed_at' => $verification?->reviewed_at?->toDateTimeString(),
            'rejection_reason' => $verification?->rejection_reason,
            'documents_submitted' => (bool) $verification?->submitted_at,
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
