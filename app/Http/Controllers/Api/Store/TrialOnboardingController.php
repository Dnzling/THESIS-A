<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Store\Branch;
use App\Models\Store\Store;
use App\Models\Store\TrialOnboardingProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrialOnboardingController extends Controller
{
    private const FIXED_TRIAL_MODULES = [
        'inventory',
        'sales',
        'procurement',
        'finance',
        'hr',
    ];

    private const ALL_STORE_MODULES = [
        'inventory',
        'procurement',
        'sales',
        'hr',
        'logistics',
        'finance',
        'supplier',
        'ecommerce',
    ];

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
            'employee_range' => 'nullable|string|max:30',
            'branch_range' => 'nullable|string|max:30',
            'modules' => 'nullable|array',
            'modules.*' => 'required|string|max:50',
            'primary_goal' => 'required|string|max:100',
            'first_team' => 'required|string|max:100',
        ]);

        $selectedPlan = strtolower((string) ($validated['plan'] ?? 'simple'));
        $fixedModules = $selectedPlan === 'unlimited'
            ? self::ALL_STORE_MODULES
            : self::FIXED_TRIAL_MODULES;
        $employeeRange = $validated['employee_range'] ?? '1-5';

        $profile = null;

        DB::transaction(function () use ($request, $validated, $employeeRange, $fixedModules, &$profile): void {
            $profile = TrialOnboardingProfile::updateOrCreate(
                ['user_id' => $request->user()->id],
                [
                    'plan' => $validated['plan'],
                    'employee_range' => $employeeRange,
                    'branch_range' => $validated['branch_range'] ?? '1',
                    'modules' => $fixedModules,
                    'primary_goal' => $validated['primary_goal'],
                    'first_team' => $validated['first_team'],
                    'completed_at' => now(),
                ]
            );

            $user = $request->user();

            if (!$user->store_id) {
                $storeCode = 'TRIAL-' . str_pad((string) $user->id, 6, '0', STR_PAD_LEFT);
                $storeName = 'Trial Store ' . $user->id;

                $store = Store::create([
                    'name' => $storeName,
                    'store_code' => $storeCode,
                    'type' => 'trial',
                    'status' => 'pending',
                    'subscription_tier' => 'free',
                    'settings' => [
                        'trial' => true,
                        'enabled_modules' => $fixedModules,
                    ],
                ]);

                $branchCode = $storeCode . '-MAIN';
                $branch = Branch::create([
                    'store_id' => $store->id,
                    'name' => $storeName . ' - Main',
                    'contact_number' => '0000000000',
                    'branch_code' => $branchCode,
                    'is_main_branch' => true,
                    'status' => 'active',
                ]);

                $user->update([
                    'store_id' => $store->id,
                    'branch_id' => $branch->id,
                ]);
            } else {
                $store = $user->store;
                if ($store) {
                    $settings = is_array($store->settings) ? $store->settings : [];
                    $settings['trial'] = true;
                    $settings['enabled_modules'] = $fixedModules;
                    $store->settings = $settings;
                    $store->save();
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Trial onboarding profile saved successfully.',
            'data' => $profile,
        ]);
    }
}
