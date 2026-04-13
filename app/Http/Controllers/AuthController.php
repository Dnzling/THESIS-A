<?php

namespace App\Http\Controllers;

use App\Http\Resources\LoginResponseResource;
use App\Http\Resources\UserResource;
use App\Mail\CustomerOtpVerificationMail;
use App\Mail\OtpVerificationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use App\Models\Core\User;
use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;
use App\Models\Hr\ShiftAssignment;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use App\Models\Customer\Customer;
use App\Models\Hr\ShiftSchedule;
use App\Models\Store\Store;
use App\Models\Store\Branch;
use App\Models\Core\Role;
use App\Services\Modules\ModuleAccessService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use \Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
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
                'password' => 'required|string|min:8|max:255|confirmed',
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

                $storeId = Store::query()->value('id');
                if (!$storeId) {
                    throw new \RuntimeException('No store available for supplier registration.');
                }

                $fullName = trim($validated['fname'] . ' ' . $validated['lname']);
                $supplierCode = $this->generateSupplierCode();

                $supplierName = trim((string) ($validated['supplier_name'] ?? ''));
                if ($supplierName === '') {
                    $supplierName = trim($validated['fname'] . ' ' . $validated['lname']);
                }

                $supplier = Supplier::create([
                    'store_id' => $storeId,
                    'supplier_code' => $supplierCode,
                    'supplier_name' => $supplierName,
                    'company_name' => $supplierName,
                    'contact_person' => $fullName,
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? '',
                    'country' => 'Philippines',
                    'status' => 'inactive',
                    'supplier_type' => 'wholesaler',
                    'payment_terms' => 'net_30',
                ]);

                $user = User::create([
                    'user_id' => $supplierCode,
                    'fname' => $validated['fname'],
                    'lname' => $validated['lname'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'role_id' => (int) $supplierRole->id,
                    'is_active' => 1,
                ]);

                SupplierPortal::create([
                    'user_id' => $user->id,
                    'supplier_id' => $supplier->id,
                    'status' => 'pending',
                    'resubmission_count' => 0,
                ]);

                return $user;
            });

            $otp = $user->generateOtp();
            Mail::to($user->email)->send(new OtpVerificationMail($otp, $user->fname));

            $user->load(['role' => function ($query) {
                $query->select('id', 'name', 'display_name');
            }]);

            $token = $user->createToken('web-browser')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Supplier account created. Please verify OTP sent to your email.',
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
                'email' => 'required|email|unique:users',
                'password' => 'required|string|min:8|max:255',
                'role_id' => 'nullable|integer|exists:roles,id',
                'birthday' => 'nullable|date|before_or_equal:today',
                'plan' => 'nullable|string|exists:subscription_plans,plan_key',
            ]);

            $targetRoleId = (int) ($validated['role_id'] ?? 2);
            $isCustomerRegistration = $targetRoleId === 16;
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

            $user = DB::transaction(function () use ($validated) {
                $user = User::create([
                    'fname' => $validated['fname'],
                    'lname' => $validated['lname'],
                    'email' => $validated['email'],
                    'birthday' => $validated['birthday'] ?? null,
                    'password' => Hash::make($validated['password']),
                    'role_id' => $validated['role_id'] ?? 2,
                    'is_active' => 1,
                ]);

                // Auto-assign trial store/branch for store_admin registrations
                $user->loadMissing('role');
                if ($user->role?->name === 'store_admin' && !$user->store_id) {
                    $requestedPlan = strtolower((string) ($validated['plan'] ?? 'simple'));
                    $storeCode = 'TRIAL-' . str_pad((string) $user->id, 6, '0', STR_PAD_LEFT);
                    $storeName = 'Trial Store ' . $user->id;

                    $store = Store::create([
                        'name' => $storeName,
                        'store_code' => $storeCode,
                        'type' => 'trial',
                        'status' => 'pending',
                        'subscription_tier' => $requestedPlan,
                    ]);

                    $branchCode = $storeCode . '-MAIN';
                    if (Branch::query()->where('branch_code', $branchCode)->exists()) {
                        $branchCode = $storeCode . '-MAIN-' . str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
                    }

                    $branch = Branch::create([
                        'store_id' => $store->id,
                        'name' => $storeName . ' - Main',
                        'contact_number' => '0000000000',
                        'branch_code' => $branchCode,
                        'is_main_branch' => true,
                        'status' => 'active',
                    ]);

                    $user->update([
                        'store_id' => $store->id,
                        'branch_id' => $branch->id,
                    ]);

                    app(ModuleAccessService::class)->syncStoreModulesFromPlan((int) $store->id);
                }

                if ($user->role?->name === 'store_admin') {
                    $user->refresh();
                    $storeId = (int) ($user->store_id ?? 0);
                    $branchId = (int) ($user->branch_id ?? 0);

                    if ($storeId > 0 && $branchId > 0) {
                        $storeAdminRoleId = (int) (Role::query()->where('name', 'store_admin')->value('id') ?? $user->role_id);

                        Employee::query()->firstOrCreate(
                            ['user_id' => $user->id],
                            [
                                'store_id' => $storeId,
                                'branch_id' => $branchId,
                                'role_id' => $storeAdminRoleId,
                                'employee_number' => Employee::generateEmployeeNumber($storeAdminRoleId),
                                'fname' => (string) $user->fname,
                                'lname' => (string) $user->lname,
                                'hire_date' => now()->toDateString(),
                                'department' => 'Management',
                                'employment_type' => 'full_time',
                                'status' => 'active',
                            ]
                        );
                    }
                }

                return $user;
            });

            if ($user->hasRole('customer') || (int) $user->role_id === 16) {
                Customer::firstOrCreate(
                    ['user_id' => $user->id],
                    ['verification_status' => 'unverified']
                );
            }

            // Generate and send OTP
            $otp = $user->generateOtp();
            if ($user->hasRole('customer')) {
                Mail::to($user->email)->send(new CustomerOtpVerificationMail($otp, $user->fname));
            } else {
                Mail::to($user->email)->send(new OtpVerificationMail($otp, $user->fname));
            }

            $user->load(['role' => function ($query) {
                $query->select('id', 'name', 'display_name');
            }]);

            // Check if request expects JSON (API) or web
            if ($request->expectsJson() || $request->is('api/*')) {
                // For API requests, return JSON
                $token = $user->createToken('web-browser')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'message' => 'Registration successful. Please check your email for OTP.',
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
        } catch (\Throwable $th) {
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
            if (!$request->filled('login') && $request->filled('email')) {
                $request->merge(['login' => $request->input('email')]);
            }
            // Validate input
            $credentials = $request->validate([
                'login' => 'required|string',
                'password' => 'required|string|min:6',
                'device_name' => 'required|string|max:100',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
            ]);

            $identifier = $credentials['login'] ?? $request->input('email');
            $email = null;

            if ($identifier && filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                $email = $identifier;
            } else {
                // Try employee number first
                $employee = Employee::where('employee_number', $identifier)->first();
                if (!$employee && is_numeric($identifier)) {
                    $employee = Employee::where('id', (int) $identifier)->first();
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

            // Attempt authentication
            if (!Auth::attempt(['email' => $email, 'password' => $credentials['password']])) {
                throw ValidationException::withMessages([
                    'login' => ['Invalid credentials.']
                ]);
            }

            // Get authenticated user
            $user = User::with(['role', 'store', 'branch'])
                ->where('email', $email)
                ->firstOrFail();

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
                $user->generateOtp();

                // Issue a temporary token for OTP verification flow.
                $tempToken = $user->createToken('otp_verification')->plainTextToken;

                return response()->json([
                    'success' => false,
                    'message' => 'Email verification required.',
                    'requires_verification' => true,
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'access_token' => $tempToken,
                ], 403);
            }

            // Revoke existing tokens for this device
            $user->tokens()->where('name', $credentials['device_name'])->delete();

            // Get abilities based on role
            $abilities = $this->getTokenAbilities($user->role_id);

            // Create token
            $token = $user->createToken($credentials['device_name'], $abilities)->plainTextToken;

            // Update last login
            $user->update(['last_login_at' => now()]);

            // Log activity
            $user->logActivity('login', "Logged in from {$credentials['device_name']}");

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
            $today = now()->format('Y-m-d');
            $now = now();

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
                // Get today's schedule
                $schedule = ShiftSchedule::with('shift')
                    ->where('employee_id', $employee->id)
                    ->whereDate('schedule_date', $today)
                    ->first();

                // Fallback: derive and auto-create schedule from active assignment
                if (!$schedule) {
                    $assignment = ShiftAssignment::query()
                        ->where('employee_id', $employee->id)
                        ->active($today)
                        ->latest('start_date')
                        ->first();

                    if ($assignment) {
                        $schedule = ShiftSchedule::firstOrCreate(
                            [
                                'employee_id' => $employee->id,
                                'schedule_date' => $today,
                            ],
                            [
                                'shift_id' => $assignment->shift_id,
                                'assignment_id' => $assignment->id,
                                'generation_method' => 'manual',
                                'status' => 'scheduled',
                                'assigned_by' => $user->id,
                            ]
                        )->load('shift');
                    }
                }

                if ($attendance) {
                    // Reuse existing row for the date (e.g. absent record created earlier)
                    $attendance->update([
                        'schedule_id' => $attendance->schedule_id ?? ($schedule->id ?? null),
                        'shift_id' => $attendance->shift_id ?? ($schedule->shift_id ?? null),
                        'clock_in' => $now,
                        'clock_in_method' => 'web',
                        'clock_in_ip' => $request->ip(),
                        'status' => 'present',
                    ]);
                } else {
                    // Create new attendance with clock-in
                    $attendance = Attendance::create([
                        'employee_id' => $employee->id,
                        'schedule_id' => $schedule->id ?? null,
                        'shift_id' => $schedule->shift_id ?? null,
                        'attendance_date' => $today,
                        'clock_in' => $now,
                        'clock_in_method' => 'web',
                        'clock_in_ip' => $request->ip(),
                        'status' => 'present',
                    ]);
                }

                // Calculate late minutes
                if ($schedule && $schedule->shift) {
                    $startTime = $schedule->shift->start_time;

                    // Handle both time-only and datetime formats
                    if (preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $startTime)) {
                        $shiftStart = Carbon::parse($startTime);
                    } else {
                        $shiftStart = Carbon::parse($today . ' ' . $startTime);
                    }

                    $minutesLate = $shiftStart->diffInMinutes($now, false);
                    $gracePeriod = $schedule->shift->grace_period_minutes ?? 15;

                    if ($minutesLate > $gracePeriod) {
                        $attendance->late_minutes = $minutesLate - $gracePeriod;
                        $attendance->status = 'late';
                    } else {
                        $attendance->late_minutes = 0;
                        $attendance->status = 'present';
                    }

                    $attendance->save();
                }

                $clockInData = [
                    'id' => $attendance->id,
                    'clock_in' => $attendance->clock_in->format('Y-m-d H:i:s'),
                    'clock_in_formatted' => $attendance->clock_in->format('h:i A'),
                    'status' => $attendance->status,
                    'late_minutes' => $attendance->late_minutes,
                    'shift_name' => $schedule->shift->name ?? 'No Shift'
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

        $settings = is_array($user->store?->settings) ? $user->store->settings : [];
        if (array_key_exists('attendance_geofence_enabled', $settings) && !$settings['attendance_geofence_enabled']) {
            return true;
        }

        $employee = Employee::where('user_id', $user->id)
            ->where('store_id', $user->store_id)
            ->first();

        if (!$employee) {
            return true;
        }

        $branch = $this->resolveGeofenceBranch($user, $employee);
        if (!$branch || $branch->latitude === null || $branch->longitude === null) {
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
        $yearPrefix = $currentYear . '-';

        // Get max number for current year
        $maxId = DB::table('users')
            ->select(DB::raw("MAX(CAST(SUBSTRING(user_id, 6) AS UNSIGNED)) as max_num"))
            ->where('user_id', 'LIKE', $yearPrefix . '%')
            ->value('max_num');

        $nextNumber = ($maxId ?? 0) + 1;
        $formattedNumber = str_pad($nextNumber, 7, '0', STR_PAD_LEFT);

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
