<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Store\Branch;
use App\Models\Store\Store;
use App\Models\Store\TrialOnboardingProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\Modules\ModuleAccessService;

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
            'plan' => 'required|string|exists:subscription_plans,plan_key',
            'setup_mode' => 'nullable|string|in:free,paid',
            'store_name' => 'required|string|max:255',
            'store_type' => 'required|string|max:100',
            'employee_range' => 'nullable|string|max:30',
            'branch_range' => 'nullable|string|max:30',
            'modules' => 'nullable|array',
            'modules.*' => 'required|string|max:50',
            'primary_goal' => 'required|string|max:100',
            'first_team' => 'required|string|max:100',
        ]);

        $selectedPlan = strtolower((string) ($validated['plan'] ?? 'simple'));
        $setupMode = strtolower((string) ($validated['setup_mode'] ?? 'free'));
        $fixedModules = app(ModuleAccessService::class)->enabledModuleKeysForPlan($selectedPlan);
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
                $storeName = trim((string) $validated['store_name']);
                $storeType = trim((string) $validated['store_type']);

                $subscriptionTier = $setupMode === 'paid' ? $selectedPlan : 'free';
                $store = Store::create([
                    'name' => $storeName,
                    'store_code' => $storeCode,
                    'type' => $storeType,
                    'status' => 'pending',
                    'subscription_tier' => $subscriptionTier,
                ]);

                if ($setupMode === 'free') {
                    $trialFields = [
                        'subscription_ends_at' => now()->addDays(7)->toDateString(),
                    ];
                    if (Schema::hasColumn('stores', 'trial_started_at')) {
                        $trialFields['trial_started_at'] = now();
                    }
                    if (Schema::hasColumn('stores', 'trial_ends_at')) {
                        $trialFields['trial_ends_at'] = now()->addDays(7);
                    }
                    $store->forceFill($trialFields)->save();
                }

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

                app(ModuleAccessService::class)->syncStoreModulesFromPlan((int) $store->id);
            } else {
                $store = $user->store;
                if ($store) {
                if ($setupMode === 'free') {
                    $store->subscription_tier = 'free';
                    $store->subscription_ends_at = now()->addDays(7)->toDateString();
                    if (Schema::hasColumn('stores', 'trial_started_at')) {
                        $store->trial_started_at = $store->trial_started_at ?? now();
                    }
                    if (Schema::hasColumn('stores', 'trial_ends_at')) {
                        $store->trial_ends_at = now()->addDays(7);
                    }
                } else {
                    $store->subscription_tier = $selectedPlan;
                    $store->subscription_ends_at = null;
                    if (Schema::hasColumn('stores', 'trial_started_at')) {
                        $store->trial_started_at = null;
                    }
                    if (Schema::hasColumn('stores', 'trial_ends_at')) {
                        $store->trial_ends_at = null;
                    }
                }
                $store->save();
                app(ModuleAccessService::class)->syncStoreModulesFromPlan((int) $store->id);
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
