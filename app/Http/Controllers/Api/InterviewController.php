<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Interview;
use App\Models\JobApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InterviewController extends Controller
{
    public function indexByApplication(JobApplication $application): JsonResponse
    {
        $interviews = $application->interviews()
            ->with('interviewer')
            ->orderBy('interview_date', 'desc')
            ->get();

        return response()->json($interviews);
    }

    public function show(Interview $interview): JsonResponse
    {
        $interview->load(['application', 'interviewer']);

        return response()->json($interview);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'application_id' => 'required|exists:job_applications,id',
            'interviewer_id' => 'required|exists:users,id',
            'interview_date' => 'required|date|after:now',
            'interview_type' => 'required|in:Screening,Technical/Skills Test,Final Interview',
            'feedback' => 'nullable|string',
            'score' => 'nullable|numeric|min:0|max:10',
            'notes' => 'nullable|string',
        ]);

        $application = JobApplication::query()->with('jobPosting')->findOrFail($validated['application_id']);
        $storeId = $request->user()?->store_id;
        if ($storeId && $application->jobPosting?->store_id && (int) $application->jobPosting->store_id !== (int) $storeId) {
            return response()->json([
                'success' => false,
                'message' => 'Interview scheduling is restricted to your store.',
            ], 403);
        }

        $settings = is_array($request->user()?->store?->settings) ? $request->user()->store->settings : [];
        $dailyLimit = (int) ($settings['hr_interview_daily_limit'] ?? 10);
        $interviewDate = \Carbon\Carbon::parse($validated['interview_date'])->toDateString();
        $dailyCount = Interview::query()
            ->whereDate('interview_date', $interviewDate)
            ->whereHas('application.jobPosting', fn($q) => $q->where('store_id', $storeId))
            ->count();

        if ($dailyCount >= $dailyLimit) {
            return response()->json([
                'success' => false,
                'message' => "Interview schedule is full for {$interviewDate}. Max {$dailyLimit} applicants per day.",
            ], 422);
        }

        $interview = Interview::create($validated);

        return response()->json($interview->load(['application', 'interviewer']), 201);
    }

    public function update(Request $request, Interview $interview): JsonResponse
    {
        $validated = $request->validate([
            'feedback' => 'nullable|string',
            'score' => 'nullable|numeric|min:0|max:10',
            'notes' => 'nullable|string',
            'interview_date' => 'sometimes|required|date',
            'interview_type' => 'sometimes|required|in:Screening,Technical/Skills Test,Final Interview'
        ]);

        $interview->update($validated);

        return response()->json($interview);
    }

    public function destroy(Interview $interview): JsonResponse
    {
        $interview->delete();

        return response()->json(['message' => 'Interview deleted successfully']);
    }

    public function bulkUpdate(Request $request, JobApplication $application): JsonResponse
    {
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
