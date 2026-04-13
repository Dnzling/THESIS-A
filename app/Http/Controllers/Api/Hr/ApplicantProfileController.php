<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Mail\OtpVerificationMail;
use App\Models\ApplicantProfile;
use App\Models\ApplicantProfileDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ApplicantProfileController extends Controller
{
    private const ALLOWED_DOCUMENT_TYPES = [
        'Resume',
        'CoverLetter',
        'ID',
        'Certificate',
        'Portfolio',
        'Other',
    ];

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = ApplicantProfile::with('documents')
            ->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => $user->fname ?? '',
                    'last_name' => $user->lname ?? '',
                    'email' => $user->email ?? '',
                    'phone' => $user->phone ?? '',
                    'birthday' => now()->subYears(18),
                    'city' => '',
                    'province' => '',
                    'barangay' => '',
                    'address' => '',
                ]
            );

        return response()->json([
            'success' => true,
            'data' => $profile,
        ]);
    }

    public function upsert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
            'phone' => 'required|string|max:20',
            'birthday' => 'required|date',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'current_position' => 'nullable|string|max:255',
            'current_company' => 'nullable|string|max:255',
        ]);

        $profile = ApplicantProfile::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Applicant profile saved.',
            'data' => $profile->load('documents'),
        ]);
    }

    public function uploadDocument(Request $request): JsonResponse
    {
        $profile = ApplicantProfile::firstOrCreate(
            ['user_id' => $request->user()->id],
            [
                'first_name' => $request->user()->fname ?? '',
                'last_name' => $request->user()->lname ?? '',
                'email' => $request->user()->email ?? '',
                'phone' => $request->user()->phone ?? '',
                'birthday' => now()->subYears(18),
                'city' => '',
                'province' => '',
                'barangay' => '',
                'address' => '',
            ]
        );

        $validated = $request->validate([
            'document' => 'required|file|max:5120|mimes:pdf,doc,docx,jpg,jpeg,png',
            'document_type' => ['nullable', Rule::in(self::ALLOWED_DOCUMENT_TYPES)],
        ]);

        $file = $request->file('document');
        $path = $file->store('job-portal/profiles/' . $profile->id, 'public');

        $document = ApplicantProfileDocument::create([
            'applicant_profile_id' => $profile->id,
            'document_type' => $validated['document_type'] ?? 'Other',
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded.',
            'data' => $document,
        ]);
    }

    public function deleteDocument(Request $request, ApplicantProfileDocument $document): JsonResponse
    {
        $profile = $document->profile;
        abort_unless($profile && $profile->user_id === $request->user()->id, 403);

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document removed.',
        ]);
    }

    public function downloadDocument(Request $request, ApplicantProfileDocument $document)
    {
        $profile = $document->profile;
        abort_unless($profile && $profile->user_id === $request->user()->id, 403);

        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found.',
            ], 404);
        }

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    public function requestEmailChange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
        ]);

        $user = $request->user();
        $user->update([
            'pending_email' => $validated['email'],
        ]);

        $otp = $user->generateOtp();
        Mail::to($validated['email'])->send(new OtpVerificationMail($otp, $user->fname));

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to the new email address.',
        ]);
    }

    public function verifyEmailChange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (!$user->pending_email) {
            return response()->json([
                'success' => false,
                'message' => 'No pending email change request found.',
            ], 422);
        }

        if (!$user->isValidOtp($validated['otp'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP.',
            ], 422);
        }

        $user->update([
            'email' => $user->pending_email,
            'pending_email' => null,
            'email_verified_at' => now(),
        ]);
        $user->clearOtp();

        $profile = ApplicantProfile::where('user_id', $user->id)->first();
        if ($profile) {
            $profile->update(['email' => $user->email]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Email updated successfully.',
            'data' => [
                'email' => $user->email,
            ],
        ]);
    }
}
