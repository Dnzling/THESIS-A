<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Services\Hr\EmployeeAvatarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EmployeeAvatarController extends Controller
{
    public function store(Request $request, string $employeeId, EmployeeAvatarService $avatars): JsonResponse
    {
        abort_unless(ctype_digit($employeeId), 404);
        $actor = $request->user();
        abort_unless(
            $actor->hasRole('owner') || $actor->hasRole('hr_manager') || $actor->hasPermissionTo('hr.employees.manage'),
            403
        );

        $employee = Employee::query()
            ->where('store_id', $actor->store_id)
            ->with('user')
            ->findOrFail((int) $employeeId);
        abort_unless($employee->user, 404);

        $validated = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $url = $avatars->save($employee->user, $validated['avatar']);
        Cache::forget("employee_details_{$employeeId}_" . now()->year . '_' . now()->format('Y-m-d'));

        return response()->json([
            'data' => ['avatar_url' => $url],
            'message' => 'Employee photo updated.',
        ]);
    }
}
