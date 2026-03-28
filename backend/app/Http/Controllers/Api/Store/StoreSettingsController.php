<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Store\TrialOnboardingProfile;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StoreSettingsController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $store = $user?->store;
        $profile = $user?->trialOnboardingProfile;

        $endsAt = $store?->subscription_ends_at ? Carbon::parse($store->subscription_ends_at) : null;
        $daysRemaining = $endsAt ? $endsAt->diffInDays(Carbon::now(), false) * -1 : null;

        $status = 'trial';
        if ($store?->subscription_tier && $store->subscription_tier !== 'free') {
            $status = $endsAt && $daysRemaining !== null && $daysRemaining < 0 ? 'expired' : 'active';
        } elseif ($endsAt && $daysRemaining !== null && $daysRemaining < 0) {
            $status = 'expired';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'store' => [
                    'id' => $store?->id,
                    'name' => $store?->name,
                    'email' => $store?->email,
                    'phone' => $store?->phone,
                    'contact_person' => is_array($store?->settings) ? ($store->settings['contact_person'] ?? null) : null,
                ],
                'subscription' => [
                    'tier' => $store?->subscription_tier ?? 'free',
                    'ends_at' => $endsAt?->toDateString(),
                    'days_remaining' => $daysRemaining,
                    'status' => $status,
                ],
                'onboarding' => [
                    'plan' => $profile?->plan ?? 'simple',
                    'modules' => $profile?->modules ?? [],
                    'completed_at' => $profile?->completed_at?->toDateTimeString(),
                    'tier' => $this->resolveTier($profile?->employee_range ?? ''),
                ],
            ],
        ]);
    }

    private function resolveTier(string $range): string
    {
        if ($range === '1-5') return 'small';
        if (in_array($range, ['6-20', '21-50'], true)) return 'mid';
        return 'enterprise';
    }

    public function updateModules(Request $request)
    {
        $validated = $request->validate([
            'modules' => 'required|array|min:1',
            'modules.*' => 'required|string|max:50',
        ]);

        $user = $request->user();
        $store = $user?->store;

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Store not found for this user.',
            ], 404);
        }

        $profile = TrialOnboardingProfile::where('user_id', $user->id)->first();

        if (!$profile || !$profile->completed_at) {
            return response()->json([
                'success' => false,
                'message' => 'Complete trial onboarding before updating modules.',
            ], 422);
        }

        $settings = is_array($store->settings) ? $store->settings : [];
        $settings['enabled_modules'] = array_values(array_unique($validated['modules']));
        $store->settings = $settings;
        $store->save();

        $profile->modules = $settings['enabled_modules'];
        $profile->save();

        return response()->json([
            'success' => true,
            'message' => 'Modules updated successfully.',
            'data' => [
                'modules' => $settings['enabled_modules'],
            ],
        ]);
    }
}
