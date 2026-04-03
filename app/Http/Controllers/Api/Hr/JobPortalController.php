<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Models\JobPosting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobPortalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = JobPosting::query()
            ->with(['store', 'role'])
            ->where('status', 'Open')
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate($request->integer('per_page', 12)),
        ]);
    }

    public function show(JobPosting $posting): JsonResponse
    {
        abort_unless($posting->status === 'Open', 404);

        $posting->load([
            'store',
            'role',
            'screeningStages',
        ]);

        return response()->json([
            'success' => true,
            'data' => $posting,
        ]);
    }
}
