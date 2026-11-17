<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Calendar\PhilippineHolidayService;
use Illuminate\Http\Request;

class PublicHolidayController extends Controller
{
    public function index(Request $request, PhilippineHolidayService $holidays)
    {
        $validated = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $holidays->between($validated['from'], $validated['to']),
        ]);
    }
}
