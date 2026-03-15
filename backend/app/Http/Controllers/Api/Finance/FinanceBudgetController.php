<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceBudget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceBudgetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = FinanceBudget::where('store_id', auth()->user()->store_id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        $budgets = $query->orderBy('period_start', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $budgets,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $budget = FinanceBudget::where('store_id', auth()->user()->store_id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $budget,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department' => 'nullable|string',
            'category' => 'nullable|string',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $budget = FinanceBudget::create([
            'store_id' => auth()->user()->store_id,
            'department' => $validated['department'] ?? null,
            'category' => $validated['category'] ?? null,
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'amount' => $validated['amount'],
            'status' => 'active',
            'created_by' => auth()->id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Budget created successfully',
            'data' => $budget,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $budget = FinanceBudget::where('store_id', auth()->user()->store_id)->findOrFail($id);

        $validated = $request->validate([
            'department' => 'nullable|string',
            'category' => 'nullable|string',
            'period_start' => 'sometimes|required|date',
            'period_end' => 'sometimes|required|date|after_or_equal:period_start',
            'amount' => 'sometimes|required|numeric|min:0',
            'status' => 'nullable|in:active,closed',
            'notes' => 'nullable|string',
        ]);

        $budget->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Budget updated successfully',
            'data' => $budget->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $budget = FinanceBudget::where('store_id', auth()->user()->store_id)->findOrFail($id);
        $budget->delete();

        return response()->json([
            'success' => true,
            'message' => 'Budget deleted successfully',
        ]);
    }
}
