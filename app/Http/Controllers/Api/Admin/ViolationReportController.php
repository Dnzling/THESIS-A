<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\ViolationReport;
use App\Models\Store\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ViolationReportController extends Controller
{
    public function index(Request $request)
    {
        if (!Schema::hasTable('violation_reports')) {
            $perPage = (int) $request->input('per_page', 15);
            $page = (int) $request->input('page', 1);

            return response()->json([
                'data' => [],
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => 0,
                'last_page' => 1,
                'from' => null,
                'to' => null,
            ]);
        }

        $query = ViolationReport::query()
            ->with(['store', 'reporter', 'actionBy'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->input('action_type'));
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->input('store_id'));
        }

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->whereHas('store', function ($storeQuery) use ($term) {
                $storeQuery->where('name', 'like', "%{$term}%")
                    ->orWhere('store_code', 'like', "%{$term}%");
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $reports = $query->paginate($perPage);

        return response()->json($reports);
    }

    public function show(ViolationReport $violationReport)
    {
        $violationReport->load(['store', 'reporter', 'actionBy']);

        return response()->json([
            'data' => $violationReport,
        ]);
    }

    public function suspend(Request $request, ViolationReport $violationReport)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        return $this->applyAction($violationReport, 'suspended', $validated['reason'], $request->user()->id);
    }

    public function ban(Request $request, ViolationReport $violationReport)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        return $this->applyAction($violationReport, 'banned', $validated['reason'], $request->user()->id);
    }

    private function applyAction(ViolationReport $report, string $storeStatus, string $reason, int $actionBy)
    {
        return DB::transaction(function () use ($report, $storeStatus, $reason, $actionBy) {
            $store = Store::query()->find($report->store_id);
            if ($store) {
                $store->update(['status' => $storeStatus]);
            }

            $report->update([
                'status' => 'actioned',
                'action_type' => $storeStatus,
                'action_reason' => $reason,
                'action_by' => $actionBy,
                'actioned_at' => now(),
            ]);

            $report->load(['store', 'reporter', 'actionBy']);

            return response()->json([
                'message' => 'Action applied successfully.',
                'data' => $report,
            ]);
        });
    }
}
