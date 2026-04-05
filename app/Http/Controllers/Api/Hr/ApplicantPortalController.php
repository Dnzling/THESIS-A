<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\ApplicationTimeline;
use App\Models\ApplicantProfile;
use App\Models\ApplicantProfileDocument;
use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ApplicantPortalController extends Controller
{
    private const ALLOWED_DOCUMENT_TYPES = [
        'Resume',
        'CoverLetter',
        'ID',
        'Certificate',
        'Portfolio',
        'Other',
    ];

    public function index(Request $request): JsonResponse
    {
        $applications = JobApplication::query()
            ->where('user_id', $request->user()->id)
            ->with(['jobPosting.store', 'jobPosting.role', 'documents', 'interviews', 'offer'])
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $applications,
        ]);
    }

    public function show(Request $request, JobApplication $application): JsonResponse
    {
        abort_unless($application->user_id === $request->user()->id, 403);

        $application->load([
            'jobPosting.store',
            'jobPosting.role',
            'timeline.stage',
            'documents',
            'interviews',
            'offer',
        ]);

        return response()->json([
            'success' => true,
            'data' => $application,
        ]);
    }

    public function apply(Request $request, JobPosting $posting): JsonResponse
    {
        abort_unless($posting->status === 'Open', 422, 'This job posting is not open for applications.');

        $user = $request->user();

        $useProfile = $request->boolean('use_profile');
        $profile = null;

        if (JobApplication::where('job_posting_id', $posting->id)->where('user_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You already submitted an application for this job posting.',
            ], 422);
        }

        if ($useProfile) {
            $profile = ApplicantProfile::with('documents')
                ->where('user_id', $user->id)
                ->first();

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please complete your applicant profile before applying.',
                ], 422);
            }

            if ($profile->documents->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please upload at least one document in your applicant profile before applying.',
                ], 422);
            }

            $validated = [
                'first_name' => $profile->first_name,
                'last_name' => $profile->last_name,
                'email' => $profile->email,
                'phone' => $profile->phone,
                'birthday' => $profile->birthday,
                'city' => $profile->city,
                'province' => $profile->province,
                'barangay' => $profile->barangay,
                'address' => $profile->address,
                'current_position' => $profile->current_position,
                'current_company' => $profile->current_company,
            ];
        } else {
            $validated = $request->validate([
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'email' => ['required', 'email', 'max:255', Rule::unique('job_applications')->where(fn ($q) => $q->where('job_posting_id', $posting->id))],
                'phone' => 'required|string|max:20',
                'birthday' => 'required|date',
                'city' => 'required|string|max:255',
                'province' => 'required|string|max:255',
                'barangay' => 'required|string|max:255',
                'address' => 'required|string|max:255',
                'current_position' => 'nullable|string|max:255',
                'current_company' => 'nullable|string|max:255',
                'documents' => 'nullable|array',
                'documents.*' => 'file|max:5120|mimes:pdf,doc,docx,jpg,jpeg,png',
                'document_types' => 'nullable|array',
                'document_types.*' => ['nullable', Rule::in(self::ALLOWED_DOCUMENT_TYPES)],
            ]);
        }

        $application = JobApplication::create([
            'job_posting_id' => $posting->id,
            'user_id' => $user->id,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'birthday' => $validated['birthday'] ?? null,
            'city' => $validated['city'] ?? null,
            'province' => $validated['province'] ?? null,
            'barangay' => $validated['barangay'] ?? null,
            'address' => $validated['address'] ?? null,
            'current_position' => $validated['current_position'] ?? null,
            'current_company' => $validated['current_company'] ?? null,
            'status' => 'Applied',
            'application_date' => now(),
        ]);

        ApplicationTimeline::create([
            'application_id' => $application->id,
            'status' => 'Applied',
            'changed_by' => $user->id,
            'changed_at' => now(),
            'notes' => 'Application submitted via job portal',
        ]);

        if ($useProfile && $profile) {
            foreach ($profile->documents as $document) {
                $newPath = 'job-applications/' . $application->id . '/' . basename($document->file_path);
                if (Storage::disk('public')->exists($document->file_path)) {
                    Storage::disk('public')->copy($document->file_path, $newPath);
                }
                ApplicationDocument::create([
                    'application_id' => $application->id,
                    'document_type' => $document->document_type,
                    'file_name' => $document->file_name,
                    'file_path' => $newPath,
                    'file_size' => $document->file_size,
                    'mime_type' => $document->mime_type,
                ]);
            }
        } elseif ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $index => $document) {
                $path = $document->store('job-applications/' . $application->id, 'public');
                ApplicationDocument::create([
                    'application_id' => $application->id,
                    'document_type' => $request->input("document_types.{$index}") ?: 'Other',
                    'file_name' => $document->getClientOriginalName(),
                    'file_path' => $path,
                    'file_size' => $document->getSize(),
                    'mime_type' => $document->getMimeType(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Application submitted successfully.',
            'data' => $application->load(['documents', 'jobPosting.store', 'jobPosting.role']),
        ], 201);
    }

    public function downloadDocument(Request $request, JobApplication $application, ApplicationDocument $document)
    {
        abort_unless($application->user_id === $request->user()->id, 403);
        abort_unless($document->application_id === $application->id, 404);

        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Document file not found.',
            ], 404);
        }

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }
}
