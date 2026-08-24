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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Mail\OtpVerificationMail;
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
        $mainBranch = $store?->branches()->orderByDesc('is_main_branch')->first();

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
                'logo_url' => $this->resolveStoreLogoUrl($store),
                'logo_dimensions' => is_array($store?->settings) ? ($store->settings['logo_dimensions'] ?? null) : null,
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

        $settings = is_array($store->settings) ? $store->settings : [];
        $oldPath = $settings['logo_path'] ?? null;
        $path = $file->store("store-logos/{$store->id}", 'public');

        $settings['logo'] = $path;
        $settings['logo_path'] = $path;
        $settings['logo_dimensions'] = [
            'width' => $savedWidth,
            'height' => $savedHeight,
            'original_width' => (int) ($validated['original_width'] ?? $savedWidth),
            'original_height' => (int) ($validated['original_height'] ?? $savedHeight),
        ];

        $store->settings = $settings;
        $store->save();

        if ($oldPath && $oldPath !== $path && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return back()->with('success', 'Store logo updated.');
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'otp' => 'required|string|size:6',
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|nullable|email|max:255',
            'phone' => 'sometimes|nullable|string|max:50',
            'address' => 'sometimes|nullable|string|max:255',
            'city' => 'sometimes|nullable|string|max:255',
            'barangay' => 'sometimes|nullable|string|max:150',
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

        if (!$user->isValidOtp($validated['otp'])) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP code.']);
        }
        $user->clearOtp();

        $store->fill(collect($validated)->except(['contact_person', 'otp'])->toArray());

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

    public function prepareAttendanceUpdate(Request $request)
    {
        $user = $request->user();
        $store = $this->resolveStoreForUser($user);

        if (!$store) {
            abort(404, 'Store not found for this user.');
        }

        $validated = $request->validate($this->attendanceUpdateRules());

        if (!$user?->email) {
            return back()->withErrors(['email' => 'No email address is available for verification.']);
        }

        $request->session()->put('store_attendance_update.pending', $validated);

        $otp = $user->generateOtp();
        Mail::to($user->email)->send(new OtpVerificationMail($otp, $user->fname));

        return redirect()
            ->route('store.settings.attendance.otp')
            ->with('success', 'A verification code was sent to your email.');
    }

    public function showAttendanceUpdateOtp(Request $request)
    {
        if (!$request->session()->has('store_attendance_update.pending')) {
            return redirect()
                ->route('store.settings')
                ->withErrors(['attendance' => 'No pending attendance geolocation update was found.']);
        }

        return Inertia::render('System/StoreAdmin/ProfileUpdateOtp', [
            'title' => 'Verify Attendance Update',
            'subtitle' => 'Store Settings',
            'email' => $request->user()?->email,
            'contextLabel' => 'Attendance Geolocation Verification',
            'heading' => 'Verify attendance update',
            'description' => 'Enter the 6-digit OTP before we save your attendance geolocation changes.',
            'notice' => 'This verification is only for Attendance Geolocation updates.',
            'verifyEndpoint' => '/store/settings/attendance/otp/verify',
            'resendEndpoint' => '/store/settings/attendance/otp/resend',
            'successRedirect' => '/store/settings?attendance_updated=1',
        ]);
    }

    public function resendAttendanceUpdateOtp(Request $request)
    {
        if (!$request->session()->has('store_attendance_update.pending')) {
            return back()->withErrors(['otp' => 'No pending attendance geolocation update was found.']);
        }

        $user = $request->user();
        if (!$user?->email) {
            return back()->withErrors(['email' => 'No email address is available for verification.']);
        }

        $otp = $user->generateOtp();
        Mail::to($user->email)->send(new OtpVerificationMail($otp, $user->fname));

        return back()->with('success', 'A new verification code was sent to your email.');
    }

    public function verifyAttendanceUpdateOtp(Request $request)
    {
        $validated = $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $user = $request->user();
        $store = $this->resolveStoreForUser($user);
        $pending = $request->session()->get('store_attendance_update.pending');

        if (!$store || !is_array($pending)) {
            return redirect()
                ->route('store.settings')
                ->withErrors(['attendance' => 'No pending attendance geolocation update was found.']);
        }

        if (!$user->isValidOtp($validated['otp'])) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP code.']);
        }

        $user->clearOtp();
        $this->applyAttendanceUpdate($store, $pending);
        $request->session()->forget('store_attendance_update.pending');

        return redirect()
            ->route('store.settings', ['attendance_updated' => 1])
            ->with('success', 'Attendance location updated.');
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
            'phone' => 'sometimes|nullable|string|max:50',
            'address' => 'sometimes|nullable|string|max:255',
            'city' => 'sometimes|nullable|string|max:255',
            'barangay' => 'sometimes|nullable|string|max:150',
            'province' => 'sometimes|nullable|string|max:255',
            'type' => 'sometimes|nullable|string|max:50',
            'store_code' => 'sometimes|nullable|string|max:50',
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

    public function updateAttendanceSettings(Request $request)
    {
        $validated = $request->validate($this->attendanceUpdateRules());

        $user = $request->user();
        $store = $this->resolveStoreForUser($user);

        if (!$store) {
            abort(404, 'Store not found for this user.');
        }

        $this->applyAttendanceUpdate($store, $validated);

        return back()->with('success', 'Attendance location updated.');
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
            'geofence_radius_m' => 'nullable|integer|min:0|max:100',
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

    private function resolveStoreLogoUrl(?Store $store): ?string
    {
        if (!$store || !is_array($store->settings)) {
            return null;
        }

        $path = $store->settings['logo'] ?? $store->settings['logo_path'] ?? null;
        if (!$path) {
            return null;
        }

        $path = (string) $path;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
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
