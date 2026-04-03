<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Store\Store;
use App\Models\Store\Branch;
use App\Models\Store\TrialOnboardingProfile;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class StoreController extends Controller
{
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
            $validated = $request->validate([
                'store_name' => 'required|string|max:255',
                'email' => 'nullable|string|max:255',
                'contact_person' => 'required|string|max:255',
                'contact_number' => 'nullable|string|max:20',
                'business_type' => 'nullable|string|max:100',
                'province' => 'nullable|string|max:100',
                'city' => 'required|string|max:100',
                'address' => 'required|string|max:200',
                'latitude' => 'nullable|numeric|between:-90, 90',
                'longitude' => 'nullable|numeric|between:-180, 180',
            ]);

            $payload = [
                'name' => $validated['store_name'],
                'type' => $validated['business_type'] ?? 'retail',
                'phone' => $validated['contact_number'] ?? null,
                'email' => $validated['email'] ?? null,
                'province' => $validated['province'] ?? 'Cavite',
                'city' => $validated['city'],
                'address' => $validated['address'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
            ];

            $onboarding = TrialOnboardingProfile::where('user_id', optional($request->user())->id)->first();
            $storeSettings = [
                'contact_person' => $validated['contact_person'],
            ];

            if ($onboarding) {
                $storeSettings = array_merge($storeSettings, [
                    'enabled_modules' => $onboarding->modules ?? [],
                    'trial_onboarding' => [
                        'plan' => $onboarding->plan,
                        'employee_range' => $onboarding->employee_range,
                        'branch_range' => $onboarding->branch_range,
                        'primary_goal' => $onboarding->primary_goal,
                        'first_team' => $onboarding->first_team,
                        'completed_at' => optional($onboarding->completed_at)->toDateTimeString(),
                    ],
                ]);
            }

            $payload['settings'] = $storeSettings;

            $store = Store::create($payload);

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

            Branch::create([
                'store_id' => $store->id,
                'name' => $store->name . ' - Main',
                'address' => $store->address,
                'city' => $store->city,
                'province' => $store->province,
                'latitude' => $store->latitude,
                'longitude' => $store->longitude,
                'contact_number' => $store->phone ?? ($validated['contact_number'] ?? '0000000000'),
                'branch_code' => $branchCode,
                'is_main_branch' => true,
                'status' => 'active',
                'branch_type' => 'main',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Store successfully registered',
                'store' => [
                    'store_id' => $store->id,
                    'store_name' => $store->name,
                    'contact_person' => $validated['contact_person'],
                ]
            ], 201);
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
