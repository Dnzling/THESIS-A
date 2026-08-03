<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'store_admin' => '/login',
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
            return response()->json([
                'success' => true,
                'message' => 'Email already verified',
                'verified' => true,
                'requires_login' => true,
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

        // OTP verification tokens are temporary; after verification, require login for a proper session token.
        $requiresLogin = true;
        if ($accessToken) {
            $accessToken->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully!',
            'requires_login' => $requiresLogin,
            'next_path' => $nextPath,
            'role' => $roleName,
            'user' => [
                'user_id' => $user->user_id,
                'name' => $user->fname . ' ' . $user->lname,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'status' => $user->status
            ]
        ], 200);
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
