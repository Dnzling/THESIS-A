<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierPortalsController extends Controller
{
    public function index(Request $request)
    {
        // return paginated supplier_portals with supplier relation if present
        $perPage = (int) $request->query('per_page', 25);
        $items = DB::table('supplier_portals')
            ->select('*')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($items);
    }

    public function show($id)
    {
        $item = DB::table('supplier_portals')->where('id', $id)->first();
        if (! $item) return response()->json(['message' => 'Not found'], 404);
        return response()->json($item);
    }
}
