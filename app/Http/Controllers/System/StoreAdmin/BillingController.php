<?php

namespace App\Http\Controllers\System\StoreAdmin;

use App\Http\Controllers\Controller;
use App\Models\Admin\SubscriptionPlan;
use App\Models\Core\User;
use App\Models\PaymongoIntent;
use App\Models\Store\Branch;
use App\Models\Store\Store;
use App\Models\Hr\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BillingController extends Controller
{
    public function show(Request $request)
    {
        $store = $this->resolveStoreForUser($request->user());
        if (!$store) {
            abort(404, 'Store not found for this user.');
        }

        $plan = SubscriptionPlan::query()->find((int) $store->getRawOriginal('subscription_tier'));
        $endsAt = $store->subscription_ends_at ? Carbon::parse($store->subscription_ends_at) : null;
        $today = now()->startOfDay();
        $daysRemaining = $endsAt ? $today->diffInDays($endsAt->copy()->startOfDay(), false) : null;
        $enabledModuleRows = $plan
            ? DB::table('plan_modules')
            ->join('modules', 'modules.id', '=', 'plan_modules.module_id')
            ->where('plan_modules.plan_id', $plan->id)
            ->where('plan_modules.included', true)
            ->where('modules.is_active', true)
            ->orderBy('name')
            ->get(['modules.key', 'modules.name'])
            ->map(fn ($module) => [
                'key' => (string) $module->key,
                'name' => (string) $module->name,
            ])
            ->values()
            ->all()
            : [];

        $records = PaymongoIntent::query()
            ->where('store_id', $store->id)
            ->where('payable_type', 'subscription_upgrade')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (PaymongoIntent $intent) {
                $metadata = is_array($intent->metadata) ? $intent->metadata : [];
                return [
                    'id' => $intent->id,
                    'reference' => $intent->payment_intent_id,
                    'plan' => (string) ($metadata['plan_label'] ?? $metadata['plan_key'] ?? $metadata['subscription_tier'] ?? 'Subscription'),
                    'billing_cycle' => (string) ($metadata['billing_cycle'] ?? (((int) ($metadata['months'] ?? 1)) >= 12 ? 'yearly' : 'monthly')),
                    'months' => (int) ($metadata['months'] ?? 1),
                    'amount' => ((float) $intent->amount) / 100,
                    'currency' => $intent->currency ?: 'PHP',
                    'status' => (string) $intent->status,
                    'payment_method' => $this->formatPaymentMethod($intent->payment_method_allowed),
                    'paid_at' => $this->paidAt($intent),
                    'created_at' => optional($intent->created_at)->toDateTimeString(),
                ];
            })
            ->values()
            ->all();

        return Inertia::render('System/StoreAdmin/Billing', [
            'title' => 'Billing',
            'subtitle' => 'Subscription Plan',
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'email' => $store->email,
            ],
            'subscription' => [
                'plan_key' => $plan?->plan_key ?? 'free',
                'plan_name' => $plan?->name ?? 'Free',
                'monthly_price' => (float) ($plan?->monthly_price ?? 0),
                'yearly_price' => (float) ($plan?->yearly_price ?? 0),
                'ends_at' => $endsAt?->toDateString(),
                'days_remaining' => $daysRemaining,
                'is_expired' => $endsAt ? $endsAt->lt($today) : false,
                'module_count' => count($enabledModuleRows),
                'enabled_modules' => $enabledModuleRows,
            ],
            'billing_records' => $records,
        ]);
    }

    private function resolveStoreForUser(?User $user): ?Store
    {
        if (!$user) {
            return null;
        }

        if ($user->relationLoaded('store') && $user->store) {
            return $user->store;
        }

        if (!empty($user->store_id)) {
            return Store::query()->find((int) $user->store_id);
        }

        $employee = Employee::query()
            ->where('user_id', $user->id)
            ->first(['store_id', 'branch_id']);

        if (!empty($employee?->store_id)) {
            return Store::query()->find((int) $employee->store_id);
        }

        if (!empty($employee?->branch_id)) {
            $branchStoreId = Branch::query()
                ->where('id', (int) $employee->branch_id)
                ->value('store_id');

            if (!empty($branchStoreId)) {
                return Store::query()->find((int) $branchStoreId);
            }
        }

        return null;
    }

    private function formatPaymentMethod(?string $value): string
    {
        if (!$value) {
            return 'PayMongo';
        }

        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return collect($decoded)
                ->map(fn ($item) => ucfirst(str_replace('_', ' ', (string) $item)))
                ->join(', ');
        }

        return ucfirst(str_replace('_', ' ', $value));
    }

    private function paidAt(PaymongoIntent $intent): ?string
    {
        if (strtolower((string) $intent->status) !== 'succeeded') {
            return null;
        }

        return optional($intent->updated_at ?? $intent->created_at)->toDateTimeString();
    }
}
