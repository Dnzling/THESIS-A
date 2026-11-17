<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobPortalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = JobPosting::query()
            ->with(['store.logoBranch', 'branch', 'role'])
            ->where('status', 'Open')
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('role', function ($roleQuery) use ($search) {
                        $roleQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('display_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('location')) {
            $location = $request->string('location')->toString();
            $query->whereHas('branch', function ($branchQuery) use ($location) {
                $branchQuery->where('name', 'like', "%{$location}%")
                    ->orWhere('city', 'like', "%{$location}%")
                    ->orWhere('province', 'like', "%{$location}%");
            });
        }

        $employmentType = $request->string('employment_type')->toString();
        if (in_array($employmentType, ['full_time', 'part_time', 'contract', 'intern'], true)) {
            $query->where('employment_type', $employmentType);
        }

        if ($request->filled('department')) {
            $department = $request->string('department')->toString();
            $query->where('department', 'like', "%{$department}%");
        }

        if (is_numeric($request->input('salary_min'))) {
            $query->where('salary_max', '>=', (float) $request->input('salary_min'));
        }
        if (is_numeric($request->input('salary_max'))) {
            $query->where('salary_min', '<=', (float) $request->input('salary_max'));
        }

        $postedWithin = $request->string('posted_within')->toString();
        $postedSince = match ($postedWithin) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            default => null,
        };
        if ($postedSince) {
            $query->where('created_at', '>=', $postedSince);
        }

        match ($request->string('sort')->toString()) {
            'oldest' => $query->orderBy('created_at'),
            'salary_high' => $query->orderByDesc('salary_max')->orderByDesc('created_at'),
            'salary_low' => $query->orderBy('salary_min')->orderByDesc('created_at'),
            default => $query->orderByDesc('created_at'),
        };

        return response()->json([
            'success' => true,
            'data' => $query->paginate($request->integer('per_page', 10)),
        ]);
    }

    public function show(Request $request, JobPosting $posting): JsonResponse
    {
        abort_unless($posting->status === 'Open', 404);

        $posting->load([
            'store.logoBranch',
            'branch',
            'role',
            'screeningStages',
        ]);

        $hasApplied = $request->user()
            ? JobApplication::query()
                ->where('job_posting_id', $posting->id)
                ->where('user_id', $request->user()->id)
                ->exists()
            : false;

        return response()->json([
            'success' => true,
            'data' => $posting,
            'has_applied' => $hasApplied,
        ]);
    }
}
