<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Core\ApprovalRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ApprovalRuleController extends Controller
{
    /**
     * Display approval rules with optional store/global filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ApprovalRule::query()->with(['store:id,name', 'creator:id,fname,lname']);

        if ($request->filled('store_id')) {
            $storeId = (int) $request->input('store_id');
            $query->where(function ($q) use ($storeId): void {
                $q->whereNull('store_id')->orWhere('store_id', $storeId);
            });
        }

        if ($request->filled('trigger_event')) {
            $query->where('trigger_event', $request->string('trigger_event')->toString());
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOL));
        }

        $rules = $query->orderBy('priority')->paginate((int) $request->input('per_page', 20));

        return response()->json(['success' => true, 'data' => $rules]);
    }

    /**
     * Store a new approval rule.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trigger_event' => ['required', 'string', 'max:100'],
            'conditions' => ['nullable', 'array'],
            'actions' => ['nullable', 'array'],
            'priority' => ['nullable', 'integer', 'min:1'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $rule = DB::transaction(function () use ($validated): ApprovalRule {
            $validated['created_by'] = Auth::id();
            return ApprovalRule::create($validated);
        });

        return response()->json(['success' => true, 'data' => $rule], 201);
    }

    /**
     * Update an existing approval rule.
     */
    public function update(Request $request, ApprovalRule $approvalRule): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'trigger_event' => ['sometimes', 'required', 'string', 'max:100'],
            'conditions' => ['nullable', 'array'],
            'actions' => ['nullable', 'array'],
            'priority' => ['nullable', 'integer', 'min:1'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $approvalRule->update($validated);

        return response()->json(['success' => true, 'data' => $approvalRule->refresh()]);
    }

    /**
     * Delete an approval rule.
     */
    public function destroy(ApprovalRule $approvalRule): JsonResponse
    {
        $approvalRule->delete();

        return response()->json(['success' => true, 'message' => 'Approval rule deleted.']);
    }

    /**
     * Return available trigger events for rule authoring UI.
     */
    public function getAvailableTriggers(): JsonResponse
    {
        $triggers = [
            'inventory.adjust',
            'inventory.transfer',
            'inventory.writeoff',
            'procurement.requisition',
            'procurement.rfq',
            'procurement.po.create',
            'procurement.payment',
        ];

        return response()->json(['success' => true, 'data' => $triggers]);
    }

    /**
     * Duplicate an existing rule as a starting template.
     */
    public function duplicate(ApprovalRule $approvalRule): JsonResponse
    {
        $clone = DB::transaction(function () use ($approvalRule): ApprovalRule {
            $copy = $approvalRule->replicate(['created_at', 'updated_at']);
            $copy->name = $approvalRule->name . ' (Copy)';
            $copy->created_by = Auth::id();
            $copy->is_active = false;
            $copy->save();
            return $copy;
        });

        return response()->json(['success' => true, 'data' => $clone], 201);
    }
}
