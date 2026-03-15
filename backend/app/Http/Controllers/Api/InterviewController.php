<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Interview;
use App\Models\JobApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InterviewController extends Controller
{
    public function indexByApplication(JobApplication $application): JsonResponse
    {
        Gate::authorize('view-interviews');

        $interviews = $application->interviews()
            ->with('interviewer')
            ->orderBy('interview_date', 'desc')
            ->get();

        return response()->json($interviews);
    }

    public function show(Interview $interview): JsonResponse
    {
        Gate::authorize('view-interviews');

        $interview->load(['application', 'interviewer']);

        return response()->json($interview);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('schedule-interviews');

        $validated = $request->validate([
            'application_id' => 'required|exists:job_applications,id',
            'interviewer_id' => 'required|exists:users,id',
            'interview_date' => 'required|date|after:now',
            'interview_type' => 'required|in:Phone Screen,Technical,HR Round,Final Round,Practical Test',
            'feedback' => 'nullable|string',
            'score' => 'nullable|numeric|min:0|max:10',
            'notes' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:15|max:480'
        ]);

        $interview = Interview::create($validated);

        return response()->json($interview->load(['application', 'interviewer']), 201);
    }

    public function update(Request $request, Interview $interview): JsonResponse
    {
        Gate::authorize('update-interviews');

        $validated = $request->validate([
            'feedback' => 'nullable|string',
            'score' => 'nullable|numeric|min:0|max:10',
            'notes' => 'nullable|string',
            'interview_date' => 'sometimes|required|date',
            'interview_type' => 'sometimes|required|in:Phone Screen,Technical,HR Round,Final Round,Practical Test',
            'duration_minutes' => 'nullable|integer|min:15|max:480'
        ]);

        $interview->update($validated);

        return response()->json($interview);
    }

    public function destroy(Interview $interview): JsonResponse
    {
        Gate::authorize('delete-interviews');

        $interview->delete();

        return response()->json(['message' => 'Interview deleted successfully']);
    }

    public function bulkUpdate(Request $request, JobApplication $application): JsonResponse
    {
        Gate::authorize('update-interviews');

        $validated = $request->validate([
            'interviews' => 'required|array',
            'interviews.*.id' => 'required|exists:interviews,id',
            'interviews.*.feedback' => 'nullable|string',
            'interviews.*.score' => 'nullable|numeric|min:0|max:10'
        ]);

        foreach ($validated['interviews'] as $interviewData) {
            $interview = Interview::find($interviewData['id']);
            $interview->update([
                'feedback' => $interviewData['feedback'] ?? $interview->feedback,
                'score' => $interviewData['score'] ?? $interview->score
            ]);
        }

        return response()->json([
            'message' => 'Interviews updated successfully',
            'interviews' => $application->interviews()->get()
        ]);
    }
}
