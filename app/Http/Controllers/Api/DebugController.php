<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Hr\ShiftAssignment;
use Carbon\Carbon;

class DebugController extends Controller
{
    /**
     * Return shift assignments overlapping today. Optional ?store_id= to filter.
     */
    public function shiftsToday(Request $request)
    {
        $user = $request->user();
        $storeId = $user?->store_id;
        $today = Carbon::today();

        $query = ShiftAssignment::where('start_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })
            ->whereHas('employee', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->with([
                // don't select non-existent columns; eager-load relations instead
                'employee' => function ($qe) {
                    $qe->select('id', 'fname', 'lname')->with(['branch:id,name', 'role:id,name']);
                }
            ]);

        $items = $query->get()->map(function ($s) {
            $emp = $s->employee;
            return [
                'id' => $s->id,
                'employee_id' => $s->employee_id,
                'employee' => $emp?->full_name ?? ($emp?->fname . ' ' . $emp?->lname) ?? null,
                // ShiftAssignment has no branch relation; use employee.branch if available
                'branch' => $emp?->branch?->name ?? null,
                'role' => $emp?->role?->name ?? null,
                'start_date' => $s->start_date,
                'end_date' => $s->end_date,
            ];
        });

        return response()->json(['success' => true, 'data' => $items]);
    }
}
