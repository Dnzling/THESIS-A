<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Shift;
use App\Models\Store\Store;
use App\Models\Core\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

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
            // Code is auto-generated when omitted.
            'code' => 'nullable|string|max:50',
            'start_time' => 'required|date_format:H:i',
            'break_start' => 'nullable|date_format:H:i',
            'shift_type' => 'required|in:fixed,rotating,flexible',
            'week_days' => 'nullable|array',
            'week_days' => 'nullable|array|max:5',
            'week_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday',
            'grace_period_minutes' => 'required|integer|min:0|max:60',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $payload = $validator->validated();
        if (empty($payload['code'])) {
            $payload['code'] = $this->generateShiftCode((string) $payload['name'], (int) $storeId);
        }

        // Auto-calculate end_time as start_time + 8 hours.
        $start = Carbon::createFromFormat('H:i', (string) $payload['start_time']);
        $end = (clone $start)->addHours(8);
        $payload['end_time'] = $end->format('H:i');

        // Auto-fill break_end as break_start + 1 hour, when break_start is provided.
        if (!empty($payload['break_start'])) {
            $breakStart = Carbon::createFromFormat('H:i', (string) $payload['break_start']);
            $payload['break_end'] = (clone $breakStart)->addHour()->format('H:i');

            // Validate break window is within shift window (same-day time arithmetic; overnight not supported here).
            if ($breakStart->lt($start) || $breakStart->gt($end)) {
                return response()->json([
                    'success' => false,
                    'errors' => ['break_start' => ['Break start must be within the shift schedule.']],
                ], 422);
            }
        } else {
            $payload['break_end'] = null;
        }

        // Total hours derived from start/end difference.
        $payload['total_hours'] = round($start->diffInMinutes($end) / 60, 2);

        $shift = Shift::create(array_merge(
            $payload,
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
            // Code is optional; keep existing if omitted.
            'code' => 'sometimes|string|max:50',
            'start_time' => 'sometimes|date_format:H:i',
            'break_start' => 'nullable|date_format:H:i',
            'shift_type' => 'sometimes|in:fixed,rotating,flexible',
            'week_days' => 'nullable|array',
            'week_days' => 'nullable|array|max:5',
            'week_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday',
            'grace_period_minutes' => 'sometimes|integer|min:0|max:60',
            'is_active' => 'boolean',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $payload = $validator->validated();

        // If name is updated and code is missing, keep existing code.
        if (array_key_exists('name', $payload) && !array_key_exists('code', $payload)) {
            // no-op
        }

        // Always keep schedule aligned: end_time is derived from start_time.
        $resolvedStart = array_key_exists('start_time', $payload) ? (string) $payload['start_time'] : (string) $shift->start_time;
        $start = Carbon::createFromFormat('H:i', substr($resolvedStart, 0, 5));
        $end = (clone $start)->addHours(8);
        $payload['end_time'] = $end->format('H:i');
        $payload['total_hours'] = round($start->diffInMinutes($end) / 60, 2);

        // Break end derived from break start.
        if (array_key_exists('break_start', $payload)) {
            if (!empty($payload['break_start'])) {
                $breakStart = Carbon::createFromFormat('H:i', (string) $payload['break_start']);
                $payload['break_end'] = (clone $breakStart)->addHour()->format('H:i');

                if ($breakStart->lt($start) || $breakStart->gt($end)) {
                    return response()->json([
                        'success' => false,
                        'errors' => ['break_start' => ['Break start must be within the shift schedule.']],
                    ], 422);
                }
            } else {
                $payload['break_end'] = null;
            }
        }

        // Force-remove legacy fields if sent by older UI.
        unset($payload['color'], $payload['min_employees_required'], $payload['has_night_diff'], $payload['night_diff_rate']);

        $shift->update($payload);

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

    private function generateShiftCode(string $name, int $storeId): string
    {
        $prefix = $this->codePrefixFromName($name, 'SHIFT');
        $base = "SHF{$storeId}-{$prefix}";
        $code = $base;
        $suffix = 1;

        while (Shift::where('code', $code)->exists()) {
            $code = "{$base}-{$suffix}";
            $suffix++;
        }

        return substr($code, 0, 50);
    }

    private function codePrefixFromName(string $name, string $fallback): string
    {
        $clean = preg_replace('/[^A-Za-z0-9 ]/', ' ', strtoupper(trim($name))) ?? '';
        $words = array_values(array_filter(preg_split('/\s+/', $clean) ?: []));

        if (empty($words)) {
            return $fallback;
        }

        $initials = '';
        foreach ($words as $word) {
            $initials .= substr($word, 0, 1);
        }

        if (strlen($initials) < 3) {
            $joined = implode('', $words);
            $initials = substr($joined, 0, 6);
        }

        return substr($initials, 0, 6);
    }
}
