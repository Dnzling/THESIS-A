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
            'branch_range' => 'nullable|string|max:30',
            'modules' => 'required|array|min:1',
            'modules.*' => 'required|string|max:50',
            'primary_goal' => 'required|string|max:100',
            'first_team' => 'required|string|max:100',
        ]);

        $profile = null;

        DB::transaction(function () use ($request, $validated, &$profile): void {
            $profile = TrialOnboardingProfile::updateOrCreate(
                ['user_id' => $request->user()->id],
                [
                    'plan' => $validated['plan'],
                    'employee_range' => $validated['employee_range'],
                    'branch_range' => $validated['branch_range'] ?? '1',
                    'modules' => array_values(array_unique($validated['modules'])),
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
                        'enabled_modules' => $profile->modules,
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
                    $settings['enabled_modules'] = $profile->modules;
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
