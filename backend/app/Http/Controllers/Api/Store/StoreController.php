<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Store\Store;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
                'city' => 'required|string|max:100',
                'address' => 'required|string|max:200',
                'latitude' => 'nullable|numeric|between:-90, 90',
                'longitude' => 'nullable|numeric|between:-180, 180',
            ]);

            $store = Store::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Store successfully registered',
                'store' => [
                    'store_id' => $store->store_id,
                    'store_name' => $store->store_name,
                    'contact_person' => $store->contact_person,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Store registration failed',
                'error' => $e->getMessage(),
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

            $store->update($validated);

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
