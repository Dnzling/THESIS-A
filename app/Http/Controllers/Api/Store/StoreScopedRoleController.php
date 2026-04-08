<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StoreScopedRoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $storeId = Auth::user()?->store_id;

        if (empty($storeId)) {
            $fallbackStoreId = $request->input('user_store_id');
            $storeId = is_numeric($fallbackStoreId) ? (int) $fallbackStoreId : null;
        }

        if (empty($storeId)) {
            return response()->json(['data' => []]);
        }

        $roles = DB::table('roles')
            ->select('roles.*')
            ->selectRaw('COALESCE(NULLIF(roles.display_name, ""), roles.name) as display_name')
            ->selectRaw('(SELECT COUNT(*) FROM role_permissions WHERE role_id = roles.id) as permissions_count')
            ->selectRaw('(SELECT COUNT(*) FROM users WHERE role_id = roles.id AND users.store_id = ?) as users_count', [$storeId])
            ->where('store_id', $storeId)
            ->orderByRaw('COALESCE(NULLIF(roles.display_name, ""), roles.name) ASC')
            ->get();

        return response()->json(['data' => $roles]);
    }

    public function scopedByDepartment(Request $request): JsonResponse
    {
        $storeId = Auth::user()?->store_id;

        if (empty($storeId)) {
            $fallbackStoreId = $request->input('user_store_id');
            $storeId = is_numeric($fallbackStoreId) ? (int) $fallbackStoreId : null;
        }

        if (empty($storeId)) {
            return response()->json(['data' => []]);
        }

        $departmentId = $request->input('department_id');
        $departmentName = $request->input('department');

        $query = DB::table('roles')
            ->select('roles.*')
            ->selectRaw('COALESCE(NULLIF(roles.display_name, ""), roles.name) as display_name')
            ->selectRaw('(SELECT COUNT(*) FROM role_permissions WHERE role_id = roles.id) as permissions_count')
            ->selectRaw('(SELECT COUNT(*) FROM users WHERE role_id = roles.id AND users.store_id = ?) as users_count', [$storeId])
            ->where('roles.store_id', $storeId);

        if ($departmentId || $departmentName) {
            $query->join('department_roles', 'department_roles.role_id', '=', 'roles.id')
                ->join('departments', 'departments.id', '=', 'department_roles.department_id')
                ->where('departments.store_id', $storeId);

            if ($departmentId) {
                $query->where('departments.id', (int) $departmentId);
            } else {
                $query->where('departments.name', $departmentName);
            }
        }

        $roles = $query
            ->orderByRaw('COALESCE(NULLIF(roles.display_name, ""), roles.name) ASC')
            ->get();

        return response()->json(['data' => $roles]);
    }
}
