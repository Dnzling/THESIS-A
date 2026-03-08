<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\ScheduleTemplate;
use App\Models\Hr\Shift;
use App\Models\Hr\Employee;
use App\Models\Core\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ScheduleTemplateController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $query = ScheduleTemplate::with(['store', 'creator'])
            ->where('store_id', $storeId);
        
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        $templates = $query->paginate(15);
        
        return response()->json([
            'success' => true,
            'data' => $templates
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'pattern' => 'required|array',
            'pattern.*.day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'pattern.*.is_working' => 'required|boolean',
            'pattern.*.shift_id' => 'required_if:pattern.*.is_working,true|exists:shifts,id',
            'pattern.*.start_time' => 'nullable|date_format:H:i',
            'pattern.*.end_time' => 'nullable|date_format:H:i',
            'valid_from' => 'required|date',
            'valid_to' => 'nullable|date|after:valid_from',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify shifts belong to user's store
        $shiftIds = collect($request->pattern)
            ->where('is_working', true)
            ->pluck('shift_id')
            ->unique();

        $validShifts = Shift::whereIn('id', $shiftIds)
            ->where('store_id', $storeId)
            ->count();

        if ($validShifts !== $shiftIds->count()) {
            return response()->json([
                'success' => false,
                'message' => 'One or more shifts do not belong to your store'
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            $template = ScheduleTemplate::create([
                'name' => $request->name,
                'store_id' => $storeId,
                'pattern' => $request->pattern,
                'valid_from' => $request->valid_from,
                'valid_to' => $request->valid_to,
                'is_active' => $request->is_active ?? true,
                'created_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Schedule template created successfully',
                'data' => $template->load('store')
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $template = ScheduleTemplate::with(['store', 'creator', 'assignments.employee'])
            ->where('store_id', $storeId)
            ->find($id);
        
        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $template
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $template = ScheduleTemplate::where('store_id', $storeId)->find($id);
        
        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'pattern' => 'sometimes|array',
            'pattern.*.day' => 'required_with:pattern|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'pattern.*.is_working' => 'required_with:pattern|boolean',
            'pattern.*.shift_id' => 'required_if:pattern.*.is_working,true|exists:shifts,id',
            'pattern.*.start_time' => 'nullable|date_format:H:i',
            'pattern.*.end_time' => 'nullable|date_format:H:i',
            'valid_from' => 'sometimes|date',
            'valid_to' => 'nullable|date|after:valid_from',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify shifts belong to user's store if pattern is being updated
        if ($request->has('pattern')) {
            $shiftIds = collect($request->pattern)
                ->where('is_working', true)
                ->pluck('shift_id')
                ->unique();

            $validShifts = Shift::whereIn('id', $shiftIds)
                ->where('store_id', $storeId)
                ->count();

            if ($validShifts !== $shiftIds->count()) {
                return response()->json([
                    'success' => false,
                    'message' => 'One or more shifts do not belong to your store'
                ], 422);
            }
        }

        $template->update(array_merge(
            $request->except(['store_id', 'created_by']),
            ['updated_by' => auth()->id()]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Template updated successfully',
            'data' => $template->fresh(['store'])
        ]);
    }

    public function generateSchedule(Request $request, $id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $template = ScheduleTemplate::with('store')
            ->where('store_id', $storeId)
            ->find($id);
        
        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify employees belong to user's store
        $employeesCount = Employee::whereIn('id', $request->employee_ids)
            ->where('store_id', $storeId)
            ->count();

        if ($employeesCount !== count($request->employee_ids)) {
            return response()->json([
                'success' => false,
                'message' => 'One or more employees do not belong to your store'
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            $generated = [];
            $startDate = \Carbon\Carbon::parse($request->start_date);
            $endDate = \Carbon\Carbon::parse($request->end_date);
            
            foreach ($request->employee_ids as $employeeId) {
                $schedules = $template->generateSchedule($employeeId, $startDate, $endDate);
                
                foreach ($schedules as $schedule) {
                    $shiftSchedule = \App\Models\Hr\ShiftSchedule::create([
                        'employee_id' => $schedule['employee_id'],
                        'shift_id' => $schedule['shift_id'],
                        'template_id' => $id,
                        'schedule_date' => $schedule['schedule_date'],
                        'generation_method' => 'auto_template',
                        'status' => $schedule['status'],
                        'assigned_by' => auth()->id()
                    ]);
                    
                    $generated[] = $shiftSchedule;
                }
            }
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($generated) . ' schedules generated successfully',
                'data' => $generated
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate schedules',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $template = ScheduleTemplate::where('store_id', $storeId)->find($id);
        
        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        }

        if ($template->assignments()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete template with active assignments'
            ], 422);
        }

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template deleted successfully'
        ]);
    }
}