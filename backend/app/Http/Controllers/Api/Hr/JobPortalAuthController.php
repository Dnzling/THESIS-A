<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Mail\OtpVerificationMail;
use App\Models\Core\Role;
use App\Models\Core\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class JobPortalAuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:255',
        ]);

        $roleId = $this->getApplicantRoleId();

        $user = User::create([
            'fname' => $validated['fname'],
            'lname' => $validated['lname'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $roleId,
            'is_active' => true,
        ]);

        $otp = $user->generateOtp();
        Mail::to($user->email)->send(new OtpVerificationMail($otp, $user->fname));

        $token = $user->createToken('job-portal')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Account created. Verify your email to continue.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'fname' => $user->fname,
                'lname' => $user->lname,
                'email' => $user->email,
                'role' => $user->role?->name,
            ],
            'requires_verification' => true,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::with('role')->where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        if (!$user->hasRole('applicant')) {
            return response()->json([
                'success' => false,
                'message' => 'This login is only for job portal applicants.',
            ], 403);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is inactive.',
            ], 403);
        }

        if (!$user->email_verified_at) {
            $token = $user->createToken('job-portal-verification')->plainTextToken;
            $user->generateOtp();

            return response()->json([
                'success' => false,
                'message' => 'Email verification required.',
                'requires_verification' => true,
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
            ], 403);
        }

        $user->tokens()->whereIn('name', ['job-portal', 'job-portal-verification'])->delete();
        $token = $user->createToken('job-portal')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'fname' => $user->fname,
                'lname' => $user->lname,
                'email' => $user->email,
                'role' => $user->role?->name,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('role');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'fname' => $user->fname,
                'lname' => $user->lname,
                'email' => $user->email,
                'role' => $user->role?->name,
                'verified' => (bool) $user->email_verified_at,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    private function getApplicantRoleId(): int
    {
        return Role::query()->firstOrCreate(
            ['name' => 'applicant'],
            [
                'display_name' => 'Applicant',
                'description' => 'Public job portal applicant',
                'code' => 'APP',
                'is_active' => true,
            ]
        )->id;
    }
}
