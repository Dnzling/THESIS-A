<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\CrmActivity;
use App\Models\Sales\CrmLead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalesCrmController extends Controller
{
    private const STAGES = ['new', 'contacted', 'qualified', 'proposal', 'won', 'lost'];

    public function leads(Request $request): JsonResponse
    {
        $query = CrmLead::query()
            ->with(['assignee:id,fname,lname,email', 'branch:id,name'])
            ->withCount('activities');

        $this->applyStoreScope($request, $query);

        if ($request->filled('stage')) {
            $query->where('stage', (string) $request->input('stage'));
        }
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('lead_code', 'like', "%{$search}%");
            });
        }

        $leads = $query->orderByDesc('created_at')->paginate((int) $request->input('per_page', 20));
        return response()->json(['success' => true, 'data' => $leads]);
    }

    public function storeLead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'source' => 'nullable|string|max:80',
            'estimated_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $user = $request->user();
        $storeId = $user->store_id;
        if (!$storeId && !$user->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'No store assigned.'], 422);
        }

        $lead = CrmLead::create([
            'store_id' => (int) $storeId,
            'branch_id' => $user->branch_id,
            'lead_code' => $this->nextLeadCode(),
            'full_name' => $validated['full_name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'source' => $validated['source'] ?? 'walk_in',
            'stage' => 'new',
            'estimated_value' => $validated['estimated_value'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'assigned_to' => $validated['assigned_to'] ?? null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        CrmActivity::create([
            'lead_id' => $lead->id,
            'store_id' => $lead->store_id,
            'activity_type' => 'note',
            'description' => 'Lead created.',
            'activity_at' => now(),
            'created_by' => $user->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Lead created.', 'data' => $lead], 201);
    }

    public function updateLead(Request $request, int $id): JsonResponse
    {
        $lead = $this->resolveLead($request, $id);
        $validated = $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'source' => 'nullable|string|max:80',
            'estimated_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $lead->update([
            ...$validated,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Lead updated.', 'data' => $lead->fresh()]);
    }

    public function changeStage(Request $request, int $id): JsonResponse
    {
        $lead = $this->resolveLead($request, $id);
        $validated = $request->validate([
            'stage' => ['required', Rule::in(self::STAGES)],
            'note' => 'nullable|string|max:1000',
        ]);

        $from = $lead->stage;
        $lead->update([
            'stage' => $validated['stage'],
            'updated_by' => $request->user()->id,
        ]);

        CrmActivity::create([
            'lead_id' => $lead->id,
            'store_id' => $lead->store_id,
            'activity_type' => 'stage_change',
            'description' => "Stage updated from {$from} to {$validated['stage']}. " . trim((string) ($validated['note'] ?? '')),
            'activity_at' => now(),
            'meta' => ['from' => $from, 'to' => $validated['stage']],
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Lead stage updated.', 'data' => $lead->fresh()]);
    }

    public function activities(Request $request, int $id): JsonResponse
    {
        $lead = $this->resolveLead($request, $id);
        $rows = CrmActivity::query()
            ->with('creator:id,fname,lname,email')
            ->where('lead_id', $lead->id)
            ->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function addActivity(Request $request, int $id): JsonResponse
    {
        $lead = $this->resolveLead($request, $id);
        $validated = $request->validate([
            'activity_type' => ['required', Rule::in(['note', 'call', 'email', 'meeting'])],
            'description' => 'required|string|max:1000',
            'activity_at' => 'nullable|date',
        ]);

        $activity = CrmActivity::create([
            'lead_id' => $lead->id,
            'store_id' => $lead->store_id,
            'activity_type' => $validated['activity_type'],
            'description' => $validated['description'],
            'activity_at' => $validated['activity_at'] ?? now(),
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Activity added.', 'data' => $activity->load('creator:id,fname,lname,email')], 201);
    }

    private function applyStoreScope(Request $request, $query): void
    {
        $user = $request->user();
        if (!$user->hasRole('super_admin')) {
            $query->where('store_id', $user->store_id);
            return;
        }
        if ($request->filled('store_id')) {
            $query->where('store_id', (int) $request->input('store_id'));
        }
    }

    private function resolveLead(Request $request, int $id): CrmLead
    {
        $query = CrmLead::query();
        $this->applyStoreScope($request, $query);
        return $query->findOrFail($id);
    }

    private function nextLeadCode(): string
    {
        $date = now()->format('Ymd');
        $prefix = "LEAD-{$date}-";
        $last = CrmLead::query()->where('lead_code', 'like', "{$prefix}%")->orderByDesc('id')->value('lead_code');
        $seq = 1;
        if ($last && preg_match('/(\d+)$/', (string) $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}

