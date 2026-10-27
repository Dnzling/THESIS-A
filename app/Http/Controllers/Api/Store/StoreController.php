<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Store\Store;
use App\Models\Store\Branch;
use App\Models\Admin\SubscriptionPlan;
use App\Models\Hr\Employee;
use App\Models\Core\Role;
use App\Models\Hr\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Services\Modules\ModuleAccessService;
use App\Services\ProductCatalog\DefaultFurnitureCategoryService;
use App\Services\ProductCatalog\DefaultFurnitureTagService;
use App\Services\Store\CaviteLocationService;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    public function validateLocation(Request $request, CaviteLocationService $locationService)
    {
        $coordinates = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        try {
            $valid = $locationService->contains((float) $coordinates['latitude'], (float) $coordinates['longitude']);
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }

        return response()->json([
            'valid' => $valid,
            'message' => $valid ? 'Pin is within Cavite.' : 'Place the store pin inside Cavite province.',
        ]);
    }

    public function index(Request $request)
    {
        try {
            // Get all stores
            $stores = Store::all();

            // Return as JSON
            return response()->json([
                'success' => true,
                'message' => 'Stores retrieved successfully',
                'count' => $stores->count(),
                'data' => $stores
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve stores',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $store = Store::find($id);

            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $store
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch store'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            if ($request->user()?->store_id) {
                return response()->json(['success' => false, 'message' => 'This account already has a store.'], 409);
            }
            $validated = $request->validate([
                'store_name' => 'required|string|max:255',
                'email' => 'nullable|string|max:255',
                'contact_person' => 'required|string|max:255',
                'contact_number' => 'nullable|string|max:20',
                'business_type' => 'nullable|string|max:100',
                'province' => 'nullable|string|max:100',
                'city' => 'required|string|max:100',
                'barangay' => 'required|string|max:150',
                'address' => 'required|string|max:200',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
            ]);

            if (strcasecmp(trim((string) ($validated['province'] ?? 'Cavite')), 'Cavite') !== 0) {
                throw ValidationException::withMessages(['province' => 'Store registration is available only in Cavite province.']);
            }

            try {
                $insideCavite = app(CaviteLocationService::class)->contains(
                    (float) $validated['latitude'],
                    (float) $validated['longitude'],
                );
            } catch (\RuntimeException $exception) {
                return response()->json(['success' => false, 'message' => $exception->getMessage()], 503);
            }

            if (! $insideCavite) {
                throw ValidationException::withMessages(['latitude' => 'Place the store pin inside Cavite province.']);
            }

            $ownerRole = null;
            if ($request->user()) {
                $ownerRole = Role::query()
                    ->whereNull('store_id')
                    ->whereRaw('LOWER(name) = ?', ['owner'])
                    ->first();

                if (!$ownerRole) {
                    throw new \RuntimeException('The global owner role is not configured.');
                }
            }

            // The store needs a valid FK while awaiting plan selection. This
            // placeholder does not activate a subscription or start the trial.
            $placeholderPlan = SubscriptionPlan::query()
                ->where('is_active', true)
                ->whereRaw('LOWER(plan_key) != ?', ['free'])
                ->orderBy('sort_order')
                ->first();

            if (!$placeholderPlan) {
                throw new \RuntimeException('No active subscription plans are available for selection.');
            }

            return DB::transaction(function () use ($request, $validated, $ownerRole, $placeholderPlan) {
            $payload = [
                'name' => $validated['store_name'],
                'type' => $validated['business_type'] ?? 'retail',
                'phone' => $validated['contact_number'] ?? null,
                'email' => $validated['email'] ?? null,
                'province' => $validated['province'] ?? 'Cavite',
                'city' => $validated['city'],
                'barangay' => $validated['barangay'],
                'address' => $validated['address'],
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'status' => 'pending',
                'subscription_status' => 'pending',
            ];

            $storeSettings = [
                'contact_person' => $validated['contact_person'],
            ];

            $payload['settings'] = $storeSettings;
            $payload['subscription_tier'] = $placeholderPlan->id;
            $payload['subscription_ends_at'] = null;
            $payload['trial_started_at'] = null;
            $payload['trial_ends_at'] = null;

            $store = Store::create($payload);
            $store->update(['store_code' => 'FS-' . now()->format('Y') . '-' . str_pad((string) $store->id, 6, '0', STR_PAD_LEFT)]);
            app(DefaultFurnitureCategoryService::class)->populateForStore((int) $store->id);
            app(DefaultFurnitureTagService::class)->populateForStore((int) $store->id);

            if ($request->user()) {
                $request->user()->update([
                    'store_id' => $store->id,
                ]);
            }

            // Auto-create main branch for the store
            $branchCode = Branch::generateBranchCode($store->name, $store->city ?? 'MAIN', 1);
            if (Branch::query()->where('branch_code', $branchCode)->exists()) {
                $branchCode = $branchCode . '-' . str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
            }

            $branch = Branch::create([
                'store_id' => $store->id,
                'name' => $store->name . ' - Main',
                'address' => $store->address,
                'city' => $store->city,
                'barangay' => $store->barangay,
                'province' => $store->province,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'contact_number' => $store->phone ?? ($validated['contact_number'] ?? '0000000000'),
                'branch_code' => $branchCode,
                'is_main_branch' => true,
                'status' => 'active',
                'branch_type' => 'storefront',
                'geofence_enabled' => false,
            ]);

            if ($request->user()) {
                $request->user()->update(['branch_id' => $branch->id]);

                $ownerRoleId = (int) $ownerRole->id;

                $managementDepartment = Department::query()->firstOrCreate(
                    ['store_id' => $store->id, 'name' => 'Management'],
                    [
                        'description' => 'Store management department',
                        'status' => 'active',
                        'created_by' => $request->user()->id,
                    ]
                );

                // The registering account is the first employee and store owner.
                $request->user()->update(['role_id' => $ownerRoleId]);

                Employee::query()->firstOrCreate(
                    ['user_id' => $request->user()->id, 'store_id' => $store->id],
                    [
                        'store_id' => $store->id,
                        'branch_id' => $branch->id,
                        'role_id' => $ownerRoleId,
                        'employee_number' => Employee::generateEmployeeNumber($ownerRoleId),
                        'fname' => (string) $request->user()->fname,
                        'lname' => (string) $request->user()->lname,
                        'department' => $managementDepartment->name,
                        'employment_type' => 'full_time',
                        'status' => 'active',
                        'hire_date' => now()->toDateString(),
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Store created. Choose a subscription plan to start your one-month free trial.',
                'store' => [
                    'store_id' => $store->id,
                    'store_name' => $store->name,
                    'store_code' => $store->store_code,
                    'trial_ends_at' => null,
                    'contact_person' => $validated['contact_person'],
                ]
            ], 201);
            });
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Store registration failed', [
                'user_id' => optional($request->user())->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Store registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateSubscription(Request $request, Store $store)
    {
        try {
            abort_unless((int) $request->user()?->store_id === (int) $store->id && $request->user()?->hasRole('owner'), 403);
            $validated = $request->validate([
                'subscription_tier' => 'required|string|exists:subscription_plans,plan_key',
                'setup_mode' => 'nullable|string|in:free,paid,trial',
                'months' => 'nullable|integer|min:1|max:36',
                'billing_cycle' => 'nullable|string|in:monthly,yearly',
            ]);

            $planKey = strtolower((string) $validated['subscription_tier']);
            $plan = SubscriptionPlan::query()->where('plan_key', $planKey)->where('is_active', true)->firstOrFail();
            if (($validated['setup_mode'] ?? null) === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Paid subscriptions must be activated through checkout after payment confirmation.',
                ], 422);
            }

            $activation = DB::transaction(function () use ($store, $plan): array {
                $lockedStore = Store::query()->lockForUpdate()->findOrFail($store->id);

                if ($lockedStore->subscription_status === 'trial') {
                    if (!$lockedStore->trial_ends_at || !$lockedStore->trial_ends_at->isFuture()) {
                        return ['error' => 'Your free trial has ended. Use checkout to activate a paid plan.'];
                    }

                    $lockedStore->subscription_tier = $plan->id;
                    $lockedStore->save();

                    return ['message' => 'Plan updated for your active free trial.'];
                }

                if (!in_array($lockedStore->subscription_status, ['free', 'pending'], true)) {
                    return ['error' => 'A free trial is not available for this store. Use checkout to activate a paid plan.'];
                }

                if ($lockedStore->trial_started_at !== null) {
                    return ['error' => 'This store has already used its free trial. Use checkout to activate a paid plan.'];
                }

                $lockedStore->subscription_tier = $plan->id;
                $lockedStore->subscription_status = 'trial';
                $lockedStore->subscription_ends_at = null;
                $lockedStore->trial_started_at = now();
                $lockedStore->trial_ends_at = now()->addMonth();
                $lockedStore->save();

                return ['message' => 'Your one-month free trial has started.'];
            });

            if (isset($activation['error'])) {
                return response()->json(['success' => false, 'message' => $activation['error']], 403);
            }

            app(ModuleAccessService::class)->syncStoreModulesFromPlan((int) $store->id);
            app(\App\Services\Core\PermissionService::class)->clearStoreCache((int) $store->id);

            return response()->json([
                'success' => true,
                'message' => $activation['message'],
                'data' => $store->fresh(),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $store = Store::where('id', $id)->first();

            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store not Found'
                ], 404);
            }

            $validated = $request->validate([
                'store_name' => 'sometimes|string|max:255',
                'contact_person' => 'sometimes|string|max:255',
                'contact_number' => 'sometimes|string|max:20',
            ]);

            $updateData = [];
            if (array_key_exists('store_name', $validated)) {
                $updateData['name'] = $validated['store_name'];
            }
            if (array_key_exists('contact_number', $validated)) {
                $updateData['phone'] = $validated['contact_number'];
            }
            if (!empty($updateData)) {
                $store->update($updateData);
            }

            if (array_key_exists('contact_person', $validated)) {
                $settings = is_array($store->settings) ? $store->settings : [];
                $settings['contact_person'] = $validated['contact_person'];
                $store->settings = $settings;
                $store->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Store updated successfully',
                'data' => $store,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Failed',
                'errors' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update store',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function destroy($id)
    {
        try {
            $store = Store::find($id);

            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store not found'
                ], 404);
            }

            $store->delete();

            return response()->json([
                'success' => true,
                'message' => 'Store deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete store'
            ], 500);
        }
    }
    public function hasStore($userId)
    {
        $hasStore = Store::scopeHasStore($userId);
        return response()->json([
            'hasStore' => $hasStore
        ]);
    }
}
