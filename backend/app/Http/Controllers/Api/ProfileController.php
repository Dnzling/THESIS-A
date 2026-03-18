<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Core\ActivityLog;
use App\Models\Hr\Employee;
use App\Mail\OtpVerificationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $employee = Employee::with('user', 'user.branch')
            ->where('user_id', $user->id)
            ->where('store_id', $user->store_id)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'employee' => $employee
            ]
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'fname' => 'sometimes|string|max:100',
            'lname' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|max:30',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
        ]);

        $user->fill([
            'fname' => $validated['fname'] ?? $user->fname,
            'lname' => $validated['lname'] ?? $user->lname,
            'email' => $validated['email'] ?? $user->email,
            'phone_number' => $validated['phone_number'] ?? $user->phone_number,
        ]);

        $requiresVerification = false;
        if (array_key_exists('email', $validated) && $user->isDirty('email')) {
            $user->email_verified_at = null;
            $requiresVerification = true;
        }

        $user->save();

        if ($requiresVerification) {
            $otp = $user->generateOtp();
            Mail::to($user->email)->send(new OtpVerificationMail($otp, $user->fname));
        }

        $employee = Employee::where('user_id', $user->id)->first();
        if ($employee) {
            $phoneValue = $validated['phone'] ?? $validated['phone_number'] ?? $employee->phone;
            $employee->fill([
                'fname' => $validated['fname'] ?? $employee->fname,
                'lname' => $validated['lname'] ?? $employee->lname,
                'phone' => $phoneValue,
                'address' => $validated['address'] ?? $employee->address,
                'province' => $validated['province'] ?? $employee->province,
                'city' => $validated['city'] ?? $employee->city,
            ]);
            $employee->save();
        }

        ActivityLog::record(
            'profile.update',
            'Updated profile information',
            ['updated_fields' => array_keys($validated)],
            'User',
            $user->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'requires_verification' => $requiresVerification,
            'data' => [
                'user' => $user->fresh(),
                'employee' => $employee?->fresh()
            ]
        ]);
    }

    public function updateAvatar(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Avatar upload not implemented'
        ], 501);
    }

    public function removeAvatar(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Avatar removal not implemented'
        ], 501);
    }
}
