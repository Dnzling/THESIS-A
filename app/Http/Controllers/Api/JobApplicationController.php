<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\ApplicationTimeline;
use App\Models\ApplicationDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class JobApplicationController extends Controller
{
    public function index(JobPosting $posting): JsonResponse
    {
        Gate::authorize('view-job-applications');

        $applications = $posting->applications()
            ->with(['employee', 'timeline.stage', 'documents', 'interviews', 'offer'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($applications);
    }

    public function show(JobApplication $application): JsonResponse
    {
        Gate::authorize('view-job-applications');

        $application->load([
            'jobPosting.store',
            'jobPosting.role',
            'employee',
            'timeline',
            'documents',
            'interviews',
            'offer'
        ]);

        return response()->json($application);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'job_posting_id' => 'required|exists:job_postings,id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'current_position' => 'nullable|string|max:255',
            'current_company' => 'nullable|string|max:255',
            'is_internal' => 'required|boolean',
            'employee_id' => 'nullable|exists:employees,id|required_if:is_internal,true',
            'documents' => 'nullable|array',
            'documents.*' => 'file|max:5120|mimes:pdf,doc,docx,jpg,jpeg,png'
        ]);

        $application = JobApplication::create([
            'job_posting_id' => $validated['job_posting_id'],
            'user_id' => auth()->id(),
            'employee_id' => $validated['employee_id'] ?? null,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'current_position' => $validated['current_position'] ?? null,
            'current_company' => $validated['current_company'] ?? null,
            'status' => 'Applied',
            'application_date' => now(),
        ]);

        // Create initial timeline entry
        ApplicationTimeline::create([
            'application_id' => $application->id,
            'status' => 'Applied',
            'changed_by' => auth()->id(),
            'changed_at' => now(),
            'notes' => 'Application submitted'
        ]);

        // Handle document uploads
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $document) {
                $path = $document->store('job-applications/' . $application->id, 'public');

                ApplicationDocument::create([
                    'application_id' => $application->id,
                    'document_type' => $request->input('document_types.' . $document->getClientOriginalName()),
                    'file_name' => $document->getClientOriginalName(),
                    'file_path' => $path,
                    'file_size' => $document->getSize(),
                    'mime_type' => $document->getMimeType()
                ]);
            }
        }

        return response()->json($application->load('documents'), 201);
    }

    public function updateStatus(Request $request, JobApplication $application): JsonResponse
    {
        Gate::authorize('update-application-status');

        $validated = $request->validate([
            'status' => 'required|in:Applied,Screening,Interview,Offer,Accepted,Hired,Rejected',
            'stage_id' => 'nullable|exists:job_posting_screening_stages,id',
            'notes' => 'nullable|string'
        ]);

        $application->update(['status' => $validated['status']]);

        // Create timeline entry
        ApplicationTimeline::create([
            'application_id' => $application->id,
            'stage_id' => $validated['stage_id'] ?? null,
            'status' => $validated['status'],
            'changed_by' => auth()->id(),
            'changed_at' => now(),
            'notes' => $validated['notes']
        ]);

        return response()->json($application);
    }

    public function downloadDocument(JobApplication $application, ApplicationDocument $document)
    {
        Gate::authorize('view-job-applications');

        abort_unless($document->application_id === $application->id, 404);

        if (Storage::disk('public')->exists($document->file_path)) {
            return Storage::disk('public')->download($document->file_path);
        }

        return response()->json(['message' => 'Document not found'], 404);
    }

    public function destroy(JobApplication $application): JsonResponse
    {
        Gate::authorize('delete-job-applications');

        // Delete associated documents from storage
        $application->documents()->each(function ($doc) {
            Storage::disk('public')->delete($doc->file_path);
        });

        $application->delete();

        return response()->json(['message' => 'Application deleted successfully']);
    }
}
