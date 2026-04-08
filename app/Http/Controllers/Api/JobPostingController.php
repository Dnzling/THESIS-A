<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobPosting;
use App\Models\JobPostingScreeningStage;
use App\Models\Core\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobPostingController extends Controller
{
    public function index(): JsonResponse
    {
        $postings = JobPosting::with(['store', 'role', 'screeningStages', 'applications'])
            ->where('store_id', Auth::user()->store_id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($postings);
    }

    public function show(JobPosting $posting): JsonResponse
    {
        abort_unless($posting->store_id === Auth::user()->store_id, 404);

        $posting->load(['store', 'role', 'createdBy', 'screeningStages', 'applications.employee']);

        return response()->json($posting);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(Auth::user()?->store_id, 422, 'Authenticated user does not have a store assigned.');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'department' => 'required|string|max:100',
            'role_id' => 'nullable|integer|exists:roles,id',
            'salary_min' => 'required|numeric|min:0',
            'salary_max' => 'required|numeric|min:0|gte:salary_min',
            'requirements' => 'nullable|array',
            'benefits' => 'nullable|array',
            'status' => 'required|in:Open,Closed,On Hold',
            'screening_stages' => 'required|array|min:1',
            'screening_stages.*.name' => 'required|string|max:255',
            'screening_stages.*.description' => 'nullable|string',
        ]);

        if (!empty($validated['role_id'])) {
            $roleExists = Role::query()
                ->where('id', $validated['role_id'])
                ->where(function ($query) {
                    $query->whereNull('store_id')
                        ->orWhere('store_id', Auth::user()->store_id);
                })
                ->exists();

            abort_unless($roleExists, 422, 'Selected role is not available for this store.');
        }

        $posting = JobPosting::create([
            'store_id' => Auth::user()->store_id,
            'role_id' => $validated['role_id'] ?? null,
            'created_by' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'department' => $validated['department'],
            'salary_min' => $validated['salary_min'],
            'salary_max' => $validated['salary_max'],
            'requirements' => $validated['requirements'] ?? [],
            'benefits' => $validated['benefits'] ?? null,
            'status' => $validated['status']
        ]);

        // Create screening stages
        foreach ($validated['screening_stages'] as $index => $stage) {
            JobPostingScreeningStage::create([
                'job_posting_id' => $posting->id,
                'stage_name' => $stage['name'],
                'description' => $stage['description'] ?? null,
                'order' => $index + 1
            ]);
        }

        return response()->json($posting->load(['role', 'screeningStages']), 201);
    }

    public function update(Request $request, JobPosting $posting): JsonResponse
    {
        abort_unless($posting->store_id === Auth::user()->store_id, 404);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'department' => 'sometimes|required|string|max:100',
            'role_id' => 'nullable|integer|exists:roles,id',
            'salary_min' => 'sometimes|required|numeric|min:0',
            'salary_max' => 'sometimes|required|numeric|min:0',
            'requirements' => 'nullable|array',
            'benefits' => 'nullable|array',
            'status' => 'sometimes|required|in:Open,Closed,On Hold'
        ]);

        if (array_key_exists('role_id', $validated) && !empty($validated['role_id'])) {
            $roleExists = Role::query()
                ->where('id', $validated['role_id'])
                ->where(function ($query) {
                    $query->whereNull('store_id')
                        ->orWhere('store_id', Auth::user()->store_id);
                })
                ->exists();

            abort_unless($roleExists, 422, 'Selected role is not available for this store.');
        }

        $posting->update($validated);

        return response()->json($posting->fresh(['role']));
    }

    public function destroy(JobPosting $posting): JsonResponse
    {
        abort_unless($posting->store_id === Auth::user()->store_id, 404);

        $posting->delete();

        return response()->json(['message' => 'Job posting deleted successfully']);
    }

    public function updateScreeningStages(Request $request, JobPosting $posting): JsonResponse
    {
        abort_unless($posting->store_id === Auth::user()->store_id, 404);

        $validated = $request->validate([
            'stages' => 'required|array|min:1',
            'stages.*.name' => 'required|string|max:255',
            'stages.*.description' => 'nullable|string',
        ]);

        // Delete existing stages
        $posting->screeningStages()->delete();

        // Create new stages
        foreach ($validated['stages'] as $index => $stage) {
            JobPostingScreeningStage::create([
                'job_posting_id' => $posting->id,
                'stage_name' => $stage['name'],
                'description' => $stage['description'] ?? null,
                'order' => $index + 1
            ]);
        }

        return response()->json($posting->load(['role', 'screeningStages']));
    }
}
