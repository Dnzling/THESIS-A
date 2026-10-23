<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\CustomerOtpVerificationMail;
use App\Mail\OtpVerificationMail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Laravel\Sanctum\PersonalAccessToken;

class VerifyEmailController extends Controller
{
    private function nextPathForRole($user): string
    {
        $roleName = strtolower((string) ($user?->role?->name ?? $user?->role_name ?? ''));

        return match ($roleName) {
            'customer' => '/customer/login',
            'applicant' => '/job-portal/login',
            'owner' => '/login',
            default => '/login',
        };
    }

    /**
     * Get user from Bearer token in Authorization header
     */
    private function getTokenFromRequest(Request $request): ?PersonalAccessToken
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return null;
        }

        return PersonalAccessToken::findToken($token);
    }

    /**
     * Get user from Bearer token in Authorization header
     */
    private function getUserFromToken(Request $request)
    {
        $accessToken = $this->getTokenFromRequest($request);

        if (!$accessToken) {
            return null;
        }

        return $accessToken->tokenable;
    }

    /**
     * Show OTP verification form
     */
    public function showOtpForm(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        return view('auth.verify-otp');
    }

    /**
     * Original method - keep for compatibility if needed
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        // You can keep this as is or modify it to use OTP
        // For now, redirect to OTP form
        return redirect()->route('verification.otp');
    }

    public function verifyOtpApi(Request $request): JsonResponse
    {
        $request->validate([
            'otp' => 'required|string|size:6'
        ]);

        // Get user from Bearer token
        $user = $this->getUserFromToken($request);
        $accessToken = $this->getTokenFromRequest($request);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please provide a valid access token.',
                'errors' => ['auth' => 'Invalid or missing token']
            ], 401);
        }

        if ($user->hasVerifiedEmail()) {
            $session = $this->createAuthenticatedSession($request, $user, $accessToken);
            return response()->json([
                'success' => true,
                'message' => 'Email already verified',
                'verified' => true,
                'requires_login' => false,
                'access_token' => $session['token'],
                'user' => $session['user'],
                'next_path' => $this->nextPathForRole($user),
            ], 200);
        }

        if (!$user->isValidOtp($request->otp)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP code.',
                'errors' => ['otp' => 'Invalid or expired OTP']
            ], 422);
        }

        // Mark email as verified
        if ($user->markEmailAsVerified()) {
            $user->clearOtp(); // Clear OTP after successful verification
            $user->update(['status' => 'active']); // Update status to active
            event(new Verified($user));
        }

        $user->loadMissing('role');
        $roleName = strtolower((string) ($user->role?->name ?? $user->role_name ?? ''));
        $nextPath = $this->nextPathForRole($user);

        $session = $this->createAuthenticatedSession($request, $user, $accessToken);

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully!',
            'requires_login' => false,
            'access_token' => $session['token'],
            'next_path' => $nextPath,
            'role' => $roleName,
            'user' => $session['user'],
        ], 200);
    }

    private function createAuthenticatedSession(Request $request, $user, ?PersonalAccessToken $temporaryToken): array
    {
        $temporaryToken?->delete();

        $deviceName = 'web_browser';
        $user->tokens()->where('name', $deviceName)->delete();
        $abilities = array_merge(
            config('roles.abilities.default', ['*']),
            config("roles.abilities.{$user->role_id}", [])
        );
        $token = $user->createToken($deviceName, $abilities)->plainTextToken;

        Auth::guard('web')->login($user);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $user->loadMissing(['role', 'store', 'branch']);

        return [
            'token' => $token,
            'user' => [
                'id' => (int) $user->id,
                'user_id' => $user->user_id,
                'fname' => $user->fname,
                'lname' => $user->lname,
                'first_name' => $user->fname,
                'last_name' => $user->lname,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'role' => $user->role?->name,
                'display_role' => $user->role?->display_name ?? $user->role?->name,
                'store_id' => $user->store_id,
                'branch_id' => $user->branch_id,
                'is_active' => (bool) $user->is_active,
                'avatar_url' => $user->avatar_url,
            ],
        ];
    }

    /**
     * Resend OTP via API
     */
    public function resendOtpApi(Request $request): JsonResponse
    {
        // Get user from Bearer token
        $user = $this->getUserFromToken($request);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please provide a valid access token.',
                'errors' => ['auth' => 'Invalid or missing token']
            ], 401);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Email already verified',
                'verified' => true
            ], 200);
        }

        // Generate new OTP
        $otp = $user->generateOtp();

        // Send OTP email using customer branding for customer accounts
        if ($user->hasRole('customer')) {
            Mail::to($user->email)->send(new CustomerOtpVerificationMail($otp, $user->fname));
        } else {
            Mail::to($user->email)->send(new OtpVerificationMail($otp, $user->fname));
        }

        return response()->json([
            'success' => true,
            'message' => 'A new OTP has been sent to your email address.',
            'expires_in' => '15 minutes'
        ], 200);
    }
}
