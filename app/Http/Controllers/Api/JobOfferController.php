<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobOffer;
use App\Models\JobApplication;
use App\Models\ApplicationTimeline;
use App\Services\EmployeeIdGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobOfferController extends Controller
{
    public function show(JobOffer $offer): JsonResponse
    {
        $offer->load(['application', 'employee']);

        return response()->json($offer);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'application_id' => 'required|exists:job_applications,id|unique:job_offers,application_id',
            'salary' => 'required|numeric|min:0',
            'position' => 'required|string|max:100',
            'department' => 'required|string|max:100',
            'start_date' => 'required|date|after:today',
            'benefits' => 'nullable|array',
            'expiry_date' => 'required|date|after:start_date'
        ]);

        $offer = JobOffer::create([
            'application_id' => $validated['application_id'],
            'salary' => $validated['salary'],
            'position' => $validated['position'],
            'department' => $validated['department'],
            'start_date' => $validated['start_date'],
            'benefits' => $validated['benefits'],
            'status' => 'Pending',
            'expiry_date' => $validated['expiry_date']
        ]);

        // Update application status to 'Offer'
        $application = $offer->application;
        $application->update(['status' => 'Offer']);

        // Create timeline entry
        ApplicationTimeline::create([
            'application_id' => $application->id,
            'status' => 'Offer',
            'changed_by' => auth()->id(),
            'changed_at' => now(),
            'notes' => 'Job offer created and pending acceptance'
        ]);

        return response()->json($offer->load('application'), 201);
    }

    public function update(Request $request, JobOffer $offer): JsonResponse
    {
        $validated = $request->validate([
            'salary' => 'sometimes|required|numeric|min:0',
            'position' => 'sometimes|required|string|max:100',
            'department' => 'sometimes|required|string|max:100',
            'start_date' => 'sometimes|required|date',
            'benefits' => 'nullable|array',
            'expiry_date' => 'sometimes|required|date'
        ]);

        // Don't allow updates if already accepted
        if ($offer->status === 'Accepted') {
            return response()->json(['message' => 'Cannot modify accepted offers'], 422);
        }

        $offer->update($validated);

        return response()->json($offer);
    }

    public function accept(Request $request, JobOffer $offer): JsonResponse
    {
        if ($offer->status === 'Accepted') {
            return response()->json(['message' => 'Offer already accepted'], 422);
        }

        if ($offer->status === 'Declined') {
            return response()->json(['message' => 'Cannot accept declined offers'], 422);
        }

        if (now() > $offer->expiry_date) {
            return response()->json(['message' => 'Offer has expired'], 422);
        }

        DB::beginTransaction();

        try {
            $offer->update([
                'status' => 'Accepted',
                'accepted_date' => now()
            ]);

            $application = $offer->application;

            // Create employee from offer
            $employee = EmployeeIdGenerationService::createEmployeeFromOffer($offer);

            // Link employee to offer
            $offer->update(['employee_id' => $employee->id]);

            // Update application status
            $application->update([
                'status' => 'Hired',
                'employee_id' => $employee->id
            ]);

            // Create timeline entry
            ApplicationTimeline::create([
                'application_id' => $application->id,
                'status' => 'Hired',
                'changed_by' => auth()->id(),
                'changed_at' => now(),
                'notes' => 'Offer accepted and employee created with ID: ' . $employee->employee_id
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Offer accepted and employee created successfully',
                'employee_id' => $employee->employee_id,
                'offer' => $offer->fresh(['application', 'employee'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to accept offer: ' . $e->getMessage()], 500);
        }
    }

    public function decline(Request $request, JobOffer $offer): JsonResponse
    {
        if ($offer->status !== 'Pending') {
            return response()->json(['message' => 'Only pending offers can be declined'], 422);
        }

        $offer->update(['status' => 'Declined']);

        $application = $offer->application;
        $application->update(['status' => 'Rejected']);

        // Create timeline entry
        ApplicationTimeline::create([
            'application_id' => $application->id,
            'status' => 'Rejected',
            'changed_by' => auth()->id(),
            'changed_at' => now(),
            'notes' => 'Job offer declined by candidate'
        ]);

        return response()->json(['message' => 'Offer declined successfully']);
    }

    public function destroy(JobOffer $offer): JsonResponse
    {
        if ($offer->status === 'Accepted') {
            return response()->json(['message' => 'Cannot delete accepted offers'], 422);
        }

        $offer->delete();

        return response()->json(['message' => 'Offer deleted successfully']);
    }
}
