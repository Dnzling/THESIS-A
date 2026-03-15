<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceReceivablesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Sales module not yet implemented; return empty list for MVP.
        return response()->json([
            'success' => true,
            'data' => [],
        ]);
    }
}
