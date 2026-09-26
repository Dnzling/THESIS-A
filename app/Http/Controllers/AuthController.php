<?php

namespace App\Http\Controllers;

use App\Http\Resources\LoginResponseResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use App\Models\Core\User;
use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use App\Models\Customer\Customer;
use App\Models\Store\Store;
use App\Models\Store\Branch;
use App\Models\Core\Role;
use App\Services\Modules\ModuleAccessService;
use App\Services\Hr\AttendanceClockInService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use \Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class AuthController extends Controller
{
    public function superAdminLogin(Request $request)
    {
        $request->merge(['login_portal' => 'super_admin']);

        return $this->login($request);
    }

    private const STRONG_PASSWORD_RULE = 'required|string|min:8|max:255|regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/';

    public function registerSupplier(Request $request)
    {
        try {
            // Backward-compatible mapping for different frontend key names.
            if (!$request->has('password_confirmation') && $request->has('confirmPassword')) {
                $request->merge(['password_confirmation' => $request->input('confirmPassword')]);
            }

            $validated = $request->validate([
                'supplier_name' => 'nullable|string|max:255',
                'fname' => 'required|string|max:255',
                'lname' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => self::STRONG_PASSWORD_RULE . '|confirmed',
                'phone' => 'nullable|string|max:50',
            ]);

            $user = DB::transaction(function () use ($validated) {
                $supplierRole = Role::query()->firstOrCreate(
                    ['name' => 'supplier'],
                    [
                        'display_name' => 'Supplier',
                        'description' => 'Supplier portal user',
                        'code' => 'SUPP',
                        'is_active' => true,
                    ]
                );

                $user = User::create([
                    'user_id' => User::generateUserId(),
                    'fname' => $validated['fname'],
                    'lname' => $validated['lname'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'role_id' => (int) $supplierRole->id,
                    'is_active' => 1,
                ]);

                return $user;
            });

            $user->load(['role' => function ($query) {
                $query->select('id', 'name', 'display_name');
            }]);

            $token = $user->createToken('web-browser')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Supplier account created. Open the verification page to receive your OTP.',
                'user' => [
                    'firstname' => $user->fname,
                    'lastname' => $user->lname,
                    'email' => $user->email,
                    'role' => $user->role_name,
                    'is_active' => $user->is_active,
                    'access_token' => $token,
                ],
                'requires_verification' => true,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier registration failed',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function generateSupplierCode(): string
    {
        $year = date('Y');
        $lastCode = Supplier::where('supplier_code', 'LIKE', "SUP-{$year}-%")
            ->orderBy('supplier_code', 'desc')
            ->value('supplier_code');

        if ($lastCode) {
            $parts = explode('-', $lastCode);
            $lastNumber = (int) ($parts[2] ?? 0);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('SUP-%s-%03d', $year, $nextNumber);
    }

    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'fname' => 'required|string|max:255',
                'lname' => 'required|string|max:255',
                'email' => 'required|email',
                'password' => self::STRONG_PASSWORD_RULE,
                'role_id' => 'nullable|integer|exists:roles,id',
                'account_type' => 'nullable|string|in:store_admin,customer',
                'birthday' => 'nullable|date|before_or_equal:today',
                'store_name' => 'nullable|string|max:255',
                'store_type' => 'nullable|string|max:100',
                'plan' => 'nullable|string|exists:subscription_plans,plan_key',
            ]);

            $registrationType = $validated['account_type'] ?? null;
            if (!$registrationType && !empty($validated['role_id'])) {
                $requestedRoleName = Role::query()
                    ->whereKey((int) $validated['role_id'])
                    ->value('name');
                $registrationType = strtolower((string) $requestedRoleName) === 'customer'
                    ? 'customer'
                    : 'store_admin';
            }
            $registrationType ??= 'store_admin';
            $isCustomerRegistration = $registrationType === 'customer';
            $targetRole = Role::query()
                ->where('name', $registrationType)
                ->whereNull('store_id')
                ->first();

            if (!$targetRole && $registrationType === 'store_admin') {
                // Older installations may only have a store-scoped default role.
                // Registration and onboarding need a global role shared by new stores.
                $targetRole = DB::transaction(function () {
                    $role = Role::firstOrCreate(
                        ['name' => 'store_admin', 'store_id' => null],
                        [
                            'display_name' => 'Store Administrator',
                            'description' => 'Manages store configuration and operations',
                            'code' => 'SADM',
                            'is_active' => true,
                        ]
                    );

                    if ($role->wasRecentlyCreated) {
                        $existingRoleId = Role::query()
                            ->where('name', 'store_admin')
                            ->whereNotNull('store_id')
                            ->orderBy('id')
                            ->value('id');

                        if ($existingRoleId) {
                            $now = now();
                            $permissions = DB::table('role_permissions')
                                ->where('role_id', $existingRoleId)
                                ->pluck('permission_id')
                                ->map(fn ($permissionId) => [
                                    'role_id' => $role->id,
                                    'permission_id' => $permissionId,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ])
                                ->all();

                            if ($permissions) {
                                DB::table('role_permissions')->insert($permissions);
                            }
                        }
                    }

                    return $role;
                });
            }

            if (!$targetRole) {
                throw ValidationException::withMessages([
                    'account_type' => ["The {$registrationType} role is not configured."],
                ]);
            }

            $existingUser = User::query()->where('email', $validated['email'])->first();

            // A customer whose email has not been verified is still a pending
            // registration. Allow them to retry and receive a fresh OTP.
            if ($existingUser && (!$isCustomerRegistration
                || (int) $existingUser->role_id !== (int) $targetRole->id
                || $existingUser->email_verified_at !== null)) {
                throw ValidationException::withMessages([
                    'email' => ['The email has already been taken.'],
                ]);
            }

            if ($isCustomerRegistration) {
                $birthday = $validated['birthday'] ?? null;
                if (!$birthday) {
                    throw ValidationException::withMessages([
                        'birthday' => ['Birthday is required for customer registration.'],
                    ]);
                }

                $isAdult = Carbon::parse($birthday)->lte(Carbon::today()->subYears(18));
                if (!$isAdult) {
                    throw ValidationException::withMessages([
                        'birthday' => ['You must be at least 18 years old to register.'],
                    ]);
                }
            }

            $user = DB::transaction(function () use ($validated, $existingUser, $targetRole) {
                $attributes = [
                    'fname' => $validated['fname'],
                    'lname' => $validated['lname'],
                    'birthday' => $validated['birthday'] ?? null,
                    'password' => Hash::make($validated['password']),
                    'role_id' => $targetRole->id,
                    'is_active' => 1,
                ];

                if ($existingUser) {
                    $existingUser->update($attributes);
                    $user = $existingUser->fresh();
                } else {
                    $user = User::create($attributes + ['email' => $validated['email']]);
                }

                $user->loadMissing('role');

                if ($user->hasRole('customer')) {
                    Customer::firstOrCreate(
                        ['user_id' => $user->id],
                        ['verification_status' => 'unverified']
                    );
                }

                return $user;
            });

            $user->load(['role' => function ($query) {
                $query->select('id', 'name', 'display_name');
            }]);

            // Check if request expects JSON (API) or web
            if ($request->expectsJson() || $request->is('api/*')) {
                // For API requests, return JSON
                $token = $user->createToken('web-browser')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'message' => 'Registration successful. Open the verification page to receive your OTP.',
                    'user' => [
                        'firstname' => $user->fname,
                        'lastname' => $user->lname,
                        'email' => $user->email,
                        'role' => $user->role_name,
                        'is_active' => $user->is_active,
                        // 'employee_id_formatted' => $user->user_id,
                        'access_token' => $token,
                    ],
                    'requires_verification' => true,
                    'verification_notice' => 'Check your email for OTP verification code.'
                ], 201);
            } else {
                // For web requests, redirect to OTP verification page
                Auth::login($user); // Log the user in
                return redirect()->route('verification.notice');
            }
        } catch (ValidationException $e) {
            if ($request->expectsJson() || $request->is(patterns: 'api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            } else {
                return back()->withErrors($e->errors())->withInput();
            }
        } catch (TransportExceptionInterface $th) {
            \Log::error('Registration OTP email delivery failed', [
                'email' => $request->input('email'),
                'exception' => get_class($th),
                'message' => $th->getMessage(),
            ]);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to send the verification code. The email service is not configured correctly.',
                    'error' => 'Configure a valid sender Gmail and Google App Password, then try again.',
                ], 503);
            }

            return back()->with('error', 'Unable to send the verification code. Please try again later.')->withInput();
        } catch (\Throwable $th) {
            \Log::error('Registration failed', [
                'email' => $request->input('email'),
                'exception' => get_class($th),
                'message' => $th->getMessage(),
            ]);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registration Failed',
                    'error' => $th->getMessage(),
                ], 500);
            } else {
                return back()->with('error', 'Registration failed: ' . $th->getMessage())->withInput();
            }
        }
    }
    public function login(Request $request)
    {
        try {
            $loginInput = trim((string) ($request->input('login') ?? $request->input('email') ?? ''));
            if ($loginInput !== '') {
                $request->merge(['login' => $loginInput]);
            }
            // Validate input
            $credentials = $request->validate([
                'login' => 'required|string',
                'password' => 'required|string|min:6',
                'device_name' => 'nullable|string|max:100',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
            ]);

            $identifier = trim((string) ($credentials['login'] ?? $request->input('email')));
            $email = null;

            if ($identifier && filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                $email = Str::lower($identifier);
            } else {
                // Try employee number first
                $employee = Employee::where('employee_number', $identifier)->first();
                if (!$employee && is_numeric($identifier)) {
                    $employee = Employee::where('id', (int) $identifier)->first();
                }
                // Drivers may also sign in using their employee name. Resolve the
                // employee first, then only accept it when the linked user is a driver.
                if (!$employee) {
                    $name = preg_replace('/\s+/', ' ', $identifier);
                    $employee = Employee::query()
                        ->with(['user.role'])
                        ->whereHas('user', function ($query) use ($name) {
                            $query->where(function ($query) use ($name) {
                                $query->whereRaw("CONCAT_WS(' ', fname, lname) = ?", [$name])
                                    ->orWhereRaw("CONCAT_WS(' ', lname, fname) = ?", [$name]);
                            })->whereHas('role', fn ($query) => $query->where('name', 'driver'));
                        })
                        ->first();
                }
                if ($employee?->user?->email) {
                    $email = $employee->user->email;
                } else {
                    // Try supplier code (e.g., SUPP-2026-00001)
                    $supplier = Supplier::where('supplier_code', $identifier)->first();
                    if ($supplier) {
                        $portal = SupplierPortal::where('supplier_id', $supplier->id)->first();
                        if ($portal?->user?->email) {
                            $email = $portal->user->email;
                        }
                    }
                }
            }

            if (!$email) {
                throw ValidationException::withMessages([
                    'login' => ['Invalid credentials.']
                ]);
            }

            $user = User::with(['role', 'store', 'branch'])
                ->where('email', $email)
                ->first();

            if (!$user || !Hash::check($credentials['password'], (string) $user->password)) {
                throw ValidationException::withMessages([
                    'login' => ['Invalid credentials.']
                ]);
            }

            if ($request->input('login_portal') === 'super_admin' && !$user->isSuperAdmin()) {
                throw ValidationException::withMessages([
                    'login' => ['This account is not authorized for Super Admin access.']
                ]);
            }

            Auth::login($user);
            $deviceName = trim((string) ($credentials['device_name'] ?? 'web_browser'));
            if ($deviceName === '') {
                $deviceName = 'web_browser';
            }

            // Check if user is active
            if (!$user->is_active) {
                Auth::logout();
                return response()->json([
                    'success' => false,
                    'message' => 'Account is inactive.'
                ], 403);
            }

            if (!$this->passesGeofence($user, $request->input('latitude'), $request->input('longitude'))) {
                Auth::logout();
                return response()->json([
                    'success' => false,
                    'message' => 'You are outside the allowed attendance radius.',
                ], 403);
            }

            // Check email verification
            if (!$user->email_verified_at) {
                // Issue a temporary token for OTP verification flow.
                $tempToken = $user->createToken('otp_verification')->plainTextToken;
                $user->loadMissing('role');

                return response()->json([
                    'success' => false,
                    'message' => 'Email verification required.',
                    'requires_verification' => true,
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'access_token' => $tempToken,
                    'role' => strtolower((string) ($user->role?->name ?? $user->role_name ?? '')),
                ], 403);
            }

            // Revoke existing tokens for this device
            $user->tokens()->where('name', $deviceName)->delete();

            // Get abilities based on role
            $abilities = $this->getTokenAbilities($user->role_id);

            // Create token
            $token = $user->createToken($deviceName, $abilities)->plainTextToken;

            // Update last login
            $user->update(['last_login_at' => now()]);

            // Log activity
            $user->logActivity('login', "Logged in from {$deviceName}");

            // Return using LoginResponseResource
            return new LoginResponseResource([
                'user' => $user,
                'token' => $token,
                'abilities' => $abilities,
                'status_code' => 200
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed. Please try again.',
                'error' => config('app.debug') ? $th->getMessage() : null
            ], 500);
        }
    }
    /**
     * Login with automatic clock-in if needed
     */
    public function loginWithClockIn(Request $request)
    {
        if (!$request->filled('login') && $request->filled('email')) {
            $request->merge(['login' => $request->input('email')]);
        }
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
            'password' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $identifier = $request->input('login') ?? $request->input('email');
        $email = null;

        if ($identifier && filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $email = $identifier;
        } else {
            $employee = Employee::where('employee_number', $identifier)->first();
            if (!$employee && is_numeric($identifier)) {
                $employee = Employee::where('id', (int) $identifier)->first();
            }
            if ($employee?->user?->email) {
                $email = $employee->user->email;
            } else {
                $supplier = Supplier::where('supplier_code', $identifier)->first();
                if ($supplier) {
                    $portal = SupplierPortal::where('supplier_id', $supplier->id)->first();
                    if ($portal?->user?->email) {
                        $email = $portal->user->email;
                    }
                }
            }
        }

        if (!$email || !Auth::attempt(['email' => $email, 'password' => $request->input('password')])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::user();
        $request->session()->regenerate();

        if (!$this->passesGeofence($user, $request->input('latitude'), $request->input('longitude'))) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'You are outside the allowed attendance radius.',
            ], 403);
        }

        // Get user data
        $userData = [
            'id' => $user->id,
            'fname' => $user->fname,
            'lname' => $user->lname,
            'email' => $user->email,
            'store_id' => $user->store_id,
            'role' => $user->role
        ];

        // Find employee
        $employee = Employee::where('user_id', $user->id)
            ->where('store_id', $user->store_id)
            ->first();

        $clockInData = null;
        $alreadyClockedIn = false;

        if ($employee) {
            $storeSettings = is_array($employee->store?->settings) ? $employee->store->settings : [];
            $attendanceRules = $storeSettings['hr_attendance_rules'] ?? [];
            $timezone = $attendanceRules['timezone'] ?? config('app.timezone', 'UTC');
            $now = now($timezone);
            $today = $now->toDateString();

            // Check if already clocked in today
            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('attendance_date', $today)
                ->first();

            if ($attendance && $attendance->clock_in) {
                // Already clocked in
                $alreadyClockedIn = true;
                $clockInData = [
                    'id' => $attendance->id,
                    'clock_in' => $attendance->clock_in->format('Y-m-d H:i:s'),
                    'clock_in_formatted' => $attendance->clock_in->format('h:i A'),
                    'status' => $attendance->status,
                    'late_minutes' => $attendance->late_minutes,
                    'shift_name' => $attendance->shift->name ?? 'No Shift'
                ];
            } else {
                $attendanceClockIn = app(AttendanceClockInService::class);
                $clockInAt = $now->copy();
                $resolved = $attendanceClockIn->resolveSchedule($employee, $today, $clockInAt, $user->id);
                $schedule = $resolved['schedule'];
                $calculated = $attendanceClockIn->calculateStatus($resolved, $clockInAt, $today, $timezone, $attendanceRules);

                if ($attendance) {
                    // Reuse existing row for the date (e.g. absent record created earlier)
                    $attendance->update([
                        'schedule_id' => $schedule?->id ?? $attendance->schedule_id,
                        'shift_id' => $resolved['shift']?->id ?? $attendance->shift_id,
                        'clock_in' => $now,
                        'clock_in_method' => 'web',
                        'clock_in_ip' => $request->ip(),
                        'status' => $calculated['status'],
                    ]);
                } else {
                    // Create new attendance with clock-in
                    $attendance = Attendance::create([
                        'employee_id' => $employee->id,
                        'schedule_id' => $schedule->id ?? null,
                        'shift_id' => $resolved['shift']?->id,
                        'attendance_date' => $today,
                        'clock_in' => $now,
                        'clock_in_method' => 'web',
                        'clock_in_ip' => $request->ip(),
                        'status' => $calculated['status'],
                    ]);
                }

                $attendance->late_minutes = $calculated['late_minutes'];
                $attendance->is_restday_work = $resolved['is_rest_day'];
                if ($calculated['status'] === 'unscheduled') {
                    $attendance->notes = trim(($attendance->notes ? $attendance->notes . "\n" : '') . 'Clock-in recorded without an assigned shift; review schedule.');
                }
                $attendance->save();

                $clockInData = [
                    'id' => $attendance->id,
                    'clock_in' => $attendance->clock_in->format('Y-m-d H:i:s'),
                    'clock_in_formatted' => $attendance->clock_in->format('h:i A'),
                    'status' => $attendance->status,
                    'late_minutes' => $attendance->late_minutes,
                    'shift_name' => $attendance->shift?->name ?? 'No Shift'
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged in successfully',
            'already_clocked_in' => $alreadyClockedIn,
            'data' => [
                'user' => $userData,
                'attendance' => $clockInData
            ]
        ]);
    }

    private function passesGeofence(User $user, ?float $latitude, ?float $longitude): bool
    {
        if ($user->isSuperAdmin() || $user->isStoreAdmin()) {
            return true;
        }

        $employee = Employee::where('user_id', $user->id)
            ->where('store_id', $user->store_id)
            ->first();

        if (!$employee) {
            return true;
        }

        $branch = $this->resolveGeofenceBranch($user, $employee);
        if (!$branch || !$branch->geofence_enabled) {
            return true;
        }

        if ($branch->latitude === null || $branch->longitude === null) {
            return true;
        }

        if ($latitude === null || $longitude === null) {
            return false;
        }

        $radius = (int) ($branch->geofence_radius_m ?? 5);
        return $this->isWithinRadius($latitude, $longitude, (float) $branch->latitude, (float) $branch->longitude, $radius);
    }

    private function resolveGeofenceBranch(User $user, Employee $employee): ?Branch
    {
        $branchId = $user->branch_id ?: $employee->branch_id;
        if ($branchId) {
            return Branch::find($branchId);
        }

        if ($user->store_id) {
            return Branch::query()
                ->where('store_id', $user->store_id)
                ->orderByDesc('is_main_branch')
                ->orderBy('id')
                ->first();
        }

        return null;
    }

    private function isWithinRadius(float $userLat, float $userLng, float $targetLat, float $targetLng, int $radiusMeters): bool
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($targetLat - $userLat);
        $dLng = deg2rad($targetLng - $userLng);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($userLat)) * cos(deg2rad($targetLat)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;
        return $distance <= $radiusMeters;
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.'
                ], 401);
            }
            if ($user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }
            $user->logActivity('logout', 'User logged out');

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully.'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed.'
            ], 500);
        }
    }

    /**
     * Logout with automatic clock-out if needed
     */
    public function logoutWithClockOut(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }
        $storeId = $user->store_id;

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $employee = Employee::where('user_id', $request->user_id)
            ->where('store_id', $storeId)
            ->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found'
            ], 200);
        }

        $today = now()->format('Y-m-d');

        // Find and clock out today's attendance if not already clocked out
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->whereNull('clock_out')
            ->first();

        if ($attendance) {
            $attendance->update([
                'clock_out' => now(),
                'clock_out_method' => 'web',
                'clock_out_ip' => $request->ip()
            ]);
            $attendance->calculateTotalWorked();
        }

        // Perform logout (API vs web)
        if ($request->expectsJson() || $request->is('api/*')) {
            if ($user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }
        } else {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
            'clocked_out' => $attendance ? true : false
        ]);
    }


    public function me(Request $request)
    {
        try {
            $user = $request->user()->load(['role', 'store', 'branch']);

            return response()->json([
                'success' => true,
                'data' => new UserResource($user)
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch profile.'
            ], 500);
        }
    }
    public static function generateUserId()
    {
        $currentYear = date('Y');
        $yearPrefix = 'USR-' . $currentYear . '-';

        // Get max number for current year
        $maxId = DB::table('users')
            ->select(DB::raw("MAX(CAST(SUBSTRING(user_id, 10) AS UNSIGNED)) as max_num"))
            ->where('user_id', 'LIKE', $yearPrefix . '%')
            ->value('max_num');

        $nextNumber = ($maxId ?? 0) + 1;
        $formattedNumber = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        return $yearPrefix . $formattedNumber;
    }

    public function forgetPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $status = Password::sendResetLink($request->only('email'));

        return response()->json([
            'success' => $status === Password::RESET_LINK_SENT,
            'message' => __($status)
        ], 303);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        return response()->json([
            'success' => $status === Password::PASSWORD_RESET,
            'message' => __($status)
        ], 301);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|min:8|confirmed|different:current_password',
        ]);

            $request->user()->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }





    // === PRIVATE === 

    private function getTokenAbilities($roleId): array
    {
        $abilities = config("roles.abilities.default", [
            'view-profile',
            'update-profile'
        ]);

        $roleAbilities = config("roles.abilities.{$roleId}", []);

        return array_merge($abilities, $roleAbilities);
    }

    private function getRoleDashboardData($user)
    {
        switch ($user->role) {
            case 'sales':
                return [
                    'today_sales' => $user->sales()->whereDate('created_at', today())->count(),
                    'today_revenue' => $user->sales()->whereDate('created_at', today())->sum('total_amount'),
                    'monthly_target' => 500000, // Example target
                    'achieved' => $user->sales()->whereMonth('created_at', now()->month)->sum('total_amount')
                ];
            case 'clerk':
                return [
                    'products_added_today' => $user->createdProducts()->whereDate('created_at', today())->count(),
                    'total_products' => $user->createdProducts()->count(),
                    'pending_3d_models' => $user->createdProducts()->where('is_3d_available', false)->count()
                ];
            case 'manager':
                return $user->store->getPerformanceMetrics();
            default:
                return [];
        }
    }
}
