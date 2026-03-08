<?php

namespace App\Http\Controllers;

use App\Http\Resources\LoginResponseResource;
use App\Http\Resources\UserResource;
use App\Mail\OtpVerificationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use App\Models\Core\User;
use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;
use App\Models\Hr\ShiftSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use \Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'fname' => 'required|string|max:255',
                'lname' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|string|min:8|max:255',
            ]);

            $user = User::create([
                'fname' => $validated['fname'],
                'lname' => $validated['lname'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role_id' => $validated['role_id'] ?? 2,
                'is_active' => 1,
            ]);

            // Generate and send OTP
            $otp = $user->generateOtp();
            Mail::to($user->email)->send(mailable: new OtpVerificationMail($otp, $user->fname));

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
            // Validate input
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string|min:6',
                'device_name' => 'required|string|max:100'
            ]);

            // Attempt authentication
            if (!Auth::attempt($request->only('email', 'password'))) {
                throw ValidationException::withMessages([
                    'email' => ['Invalid credentials.']
                ]);
            }

            // Get authenticated user
            $user = User::with(['role', 'store', 'branch'])
                ->where('email', $credentials['email'])
                ->firstOrFail();

            // Check if user is active
            if (!$user->is_active) {
                Auth::logout();
                return response()->json([
                    'success' => false,
                    'message' => 'Account is inactive.'
                ], 403);
            }

            // Check email verification
            if (!$user->email_verified_at) {
                $user->generateOtp();

                return response()->json([
                    'success' => false,
                    'message' => 'Email verification required.',
                    'requires_verification' => true,
                    'user_id' => $user->id
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
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::user();
        $request->session()->regenerate();

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

                // Create new attendance with clock-in
                $attendance = Attendance::create([
                    'employee_id' => $employee->id,
                    'schedule_id' => $schedule->id ?? null,
                    'shift_id' => $schedule->shift_id ?? null,
                    'attendance_date' => $today,
                    'clock_in' => $now,
                    'clock_in_method' => 'web',
                    'status' => 'present',
                ]);

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

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            $user->currentAccessToken()->delete();
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
        $user = Auth::user();
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
                'clock_out_method' => 'web'
            ]);
            $attendance->calculateTotalWorked();
        }

        // Perform logout
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

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
