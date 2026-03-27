<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Store\TrialOnboardingProfile;
use Illuminate\Http\Request;

class TrialOnboardingController extends Controller
{
    public function show(Request $request)
    {
        $profile = TrialOnboardingProfile::where('user_id', $request->user()->id)->first();

        return response()->json([
            'success' => true,
            'data' => $profile,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'plan' => 'required|string|in:simple,unlimited',
            'employee_range' => 'required|string|max:30',
            'branch_range' => 'required|string|max:30',
            'modules' => 'required|array|min:1',
            'modules.*' => 'required|string|max:50',
            'primary_goal' => 'required|string|max:100',
            'first_team' => 'required|string|max:100',
        ]);

        $profile = TrialOnboardingProfile::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'plan' => $validated['plan'],
                'employee_range' => $validated['employee_range'],
                'branch_range' => $validated['branch_range'],
                'modules' => array_values(array_unique($validated['modules'])),
                'primary_goal' => $validated['primary_goal'],
                'first_team' => $validated['first_team'],
                'completed_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Trial onboarding profile saved successfully.',
            'data' => $profile,
        ]);
    }
}
