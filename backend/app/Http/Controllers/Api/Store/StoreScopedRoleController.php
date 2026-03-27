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
}