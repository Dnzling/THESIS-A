<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Shift;
use App\Models\Store\Store;
use App\Models\Core\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;
        
        $query = Shift::with('store')
            ->where('store_id', $storeId);
        
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        if ($request->has('shift_type')) {
            $query->where('shift_type', $request->shift_type);
        }
        
        $shifts = $query->paginate(15);
        
        return response()->json([
            'success' => true,
            'data' => $shifts
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:shifts',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'break_start' => 'nullable|date_format:H:i',
            'break_end' => 'nullable|date_format:H:i|after:break_start',
            'total_hours' => 'required|numeric|min:0|max:24',
            'shift_type' => 'required|in:fixed,rotating,flexible',
            'week_days' => 'nullable|array',
            'week_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'grace_period_minutes' => 'required|integer|min:0|max:60',
            'has_night_diff' => 'boolean',
            'night_diff_rate' => 'required_if:has_night_diff,true|numeric|min:1|max:3',
            'min_employees_required' => 'required|integer|min:1',
            'color' => 'nullable|string|max:20',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $shift = Shift::create(array_merge(
            $request->all(),
            ['store_id' => $storeId]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Shift created successfully',
            'data' => $shift->load('store')
        ], 201);
    }

    public function show($id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $shift = Shift::with(['store', 'assignments.employee'])
            ->where('store_id', $storeId)
            ->find($id);
        
        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $shift
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $shift = Shift::where('store_id', $storeId)->find($id);
        
        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'code' => 'sometimes|string|max:50|unique:shifts,code,' . $id,
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'break_start' => 'nullable|date_format:H:i',
            'break_end' => 'nullable|date_format:H:i|after:break_start',
            'total_hours' => 'sometimes|numeric|min:0|max:24',
            'shift_type' => 'sometimes|in:fixed,rotating,flexible',
            'week_days' => 'nullable|array',
            'week_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'grace_period_minutes' => 'sometimes|integer|min:0|max:60',
            'has_night_diff' => 'boolean',
            'night_diff_rate' => 'required_if:has_night_diff,true|numeric|min:1|max:3',
            'min_employees_required' => 'sometimes|integer|min:1',
            'color' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $shift->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Shift updated successfully',
            'data' => $shift->fresh(['store'])
        ]);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $shift = Shift::where('store_id', $storeId)->find($id);
        
        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift not found'
            ], 404);
        }

        if ($shift->assignments()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete shift with active assignments'
            ], 422);
        }

        $shift->delete();

        return response()->json([
            'success' => true,
            'message' => 'Shift deleted successfully'
        ]);
    }

    public function getStats($id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $shift = Shift::where('store_id', $storeId)->find($id);
        
        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift not found'
            ], 404);
        }

        $stats = [
            'total_employees_assigned' => $shift->assignments()->count(),
            'active_employees' => $shift->assignments()->active()->count(),
            'total_schedules' => $shift->schedules()->count(),
            'upcoming_schedules' => $shift->schedules()->where('schedule_date', '>=', now())->count(),
            'completion_rate' => $shift->attendances()->whereNotNull('clock_in')->count() / max($shift->schedules()->count(), 1) * 100
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}