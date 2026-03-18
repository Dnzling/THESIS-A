<?php

namespace App\Http\Controllers\Api\Hr;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Hr\Department;
use App\Models\Hr\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    /**
     * Display a listing of departments for the user's store.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $query = Department::where('store_id', $storeId)->with(['roles']);
            // ->with(['employee']);

        // Optional filtering
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%')
                  ->orWhere('location', 'like', '%' . $request->search . '%');
            });
        }

        // Sorting
        $sortField = $request->get('sort_field', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        $departments = $request->get('per_page') 
            ? $query->paginate($request->per_page)
            : $query->get();

        if ($request->get('per_page') && method_exists($departments, 'getCollection')) {
            $departments->setCollection(
                $departments->getCollection()->map(function ($department) use ($storeId) {
                    $department->employee_count = $this->countEmployeesForDepartment($department, $storeId);
                    $department->status = $this->normalizeStatus($department->status);
                    return $department;
                })
            );
        } else {
            $departments = $departments->map(function ($department) use ($storeId) {
                $department->employee_count = $this->countEmployeesForDepartment($department, $storeId);
                $department->status = $this->normalizeStatus($department->status);
                return $department;
            });
        }

        return response()->json([
            'success' => true,
            'data' => $departments
        ],202);
    }

    /**
     * Store a newly created department.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:departments,code,NULL,id,store_id,' . $storeId,
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'integer|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $department = Department::create([
            'store_id' => $storeId,
            'name' => $request->name,
            'code' => $request->code,
            'location' => $request->location,
            'description' => $request->description,
            'created_by' => $user->id,
            'status' => $request->status ?? 'active',
        ]);

        if (is_array($request->role_ids)) {
            $department->roles()->sync($request->role_ids);
        }

        $department->load('roles');
        $department->employee_count = $this->countEmployeesForDepartment($department, $storeId);
        $department->status = $this->normalizeStatus($department->status);

        return response()->json([
            'success' => true,
            'message' => 'Department created successfully',
            'data' => $department
        ], 201);
    }

    /**
     * Display the specified department.
     */
    public function show($id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $department = Department::where('store_id', $storeId)
            ->with(['creator', 'roles'])
            ->find($id);

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Department not found'
            ], 404);
        }

        $department->employee_count = $this->countEmployeesForDepartment($department, $storeId);
        $department->status = $this->normalizeStatus($department->status);

        return response()->json([
            'success' => true,
            'data' => $department
        ]);
    }

    /**
     * Update the specified department.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $department = Department::where('store_id', $storeId)->find($id);

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Department not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50|unique:departments,code,' . $id . ',id,store_id,' . $storeId,
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'integer|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $department->update($request->only([
            'name', 'code', 'location', 'description', 'status'
        ]));

        if (is_array($request->role_ids)) {
            $department->roles()->sync($request->role_ids);
        }

        $department->load('roles');
        $department->employee_count = $this->countEmployeesForDepartment($department, $storeId);
        $department->status = $this->normalizeStatus($department->status);

        return response()->json([
            'success' => true,
            'message' => 'Department updated successfully',
            'data' => $department
        ]);
    }

    /**
     * Remove the specified department.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $department = Department::where('store_id', $storeId)->find($id);

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Department not found'
            ], 404);
        }

        // Check if department has employees
        if ($this->countEmployeesForDepartment($department, $storeId) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete department with existing employees'
            ], 422);
        }

        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'Department deleted successfully'
        ]);
    }

    /**
     * Get department statistics.
     */
    public function statistics()
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $stats = [
            'total_departments' => Department::where('store_id', $storeId)->count(),
            'departments_with_employees' => Department::where('store_id', $storeId)->get()
                ->filter(fn($dept) => $this->countEmployeesForDepartment($dept, $storeId) > 0)
                ->count(),
            'total_employees' => Department::where('store_id', $storeId)->get()
                ->sum(fn($dept) => $this->countEmployeesForDepartment($dept, $storeId)),
            'recent_departments' => Department::where('store_id', $storeId)
                ->latest()
                ->take(5)
                ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get departments as options list (for dropdowns).
     */
    public function options()
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $departments = Department::where('store_id', $storeId)
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $departments
        ]);
    }

    /**
     * Bulk delete departments.
     */
    public function bulkDestroy(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:departments,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Ensure departments belong to user's store
            $departments = Department::where('store_id', $storeId)
                ->whereIn('id', $request->ids)
                ->get();

        $deletedCount = 0;
        $failedIds = [];

        foreach ($departments as $department) {
            if ($this->countEmployeesForDepartment($department, $storeId) === 0) {
                $department->delete();
                $deletedCount++;
            } else {
                $failedIds[] = $department->id;
            }
        }

        return response()->json([
            'success' => true,
            'message' => $deletedCount . ' departments deleted successfully',
            'data' => [
                'deleted_count' => $deletedCount,
                'failed_ids' => $failedIds
            ]
        ]);
    }

    private function countEmployeesForDepartment(Department $department, int $storeId): int
    {
        $roleIds = $department->roles?->pluck('id')->filter()->values() ?? collect();
        $query = Employee::where('store_id', $storeId);

        if ($roleIds->count() > 0) {
            $query->whereIn('role_id', $roleIds->all());
        } else {
            $query->where('department', $department->name);
        }

        return $query->count();
    }

    private function normalizeStatus($status): string
    {
        if (is_bool($status)) {
            return $status ? 'active' : 'inactive';
        }
        if (!$status) {
            return 'inactive';
        }
        return strtolower($status);
    }
}
