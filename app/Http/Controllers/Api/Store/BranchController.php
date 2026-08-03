<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Store\Branch;
use App\Models\Store\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Get branches for the user's store
            $query = Branch::where('store_id', $user->store_id)
                ->select('id', 'name', 'branch_code', 'address', 'status', 'contact_number', 'branch_type', 'latitude', 'longitude', 'geofence_radius_m', 'geofence_enabled')
                ->orderBy('name');

            if ($request->filled('branch_type')) {
                $query->where('branch_type', (string) $request->input('branch_type'));
            }

            $branches = $query->get();
            
            return response()->json([
                'success' => true,
                'data' => $branches
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch branches',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'address' => 'required|string|max:255',
                'city' => 'required|string|max:100',
                'province' => 'nullable|string|max:50',
                'barangay' => 'required|string|max:100',
                'latitude' => 'nullable|numeric|between:-90, 90',
                'longitude' => 'nullable|numeric|between:-180, 180',
                'branch_code' => 'nullable|string|max:20|unique:branches,branch_code',
                'geofence_radius_m' => 'nullable|integer|min:0|max:5000',
                'geofence_enabled' => 'nullable|boolean',
                'is_main_branch' => 'nullable|boolean',
                'branch_type' => 'nullable|in:storefront,warehouse',
            ]);

            $storeId = $user?->store_id;
            $branchCode = $validated['branch_code'] ?? $this->generateBranchCode($validated['name'], $storeId);
            $storePhone = Store::query()->where('id', $storeId)->value('phone');
            $contactNumber = $validated['contact_number'] ?? null;
            if (!$contactNumber) {
                $contactNumber = $storePhone ?: '0000000000';
            }

            $branch = Branch::create(array_merge($validated, [
                'store_id' => $storeId,
                'branch_code' => $branchCode,
                'contact_number' => $contactNumber,
                'status' => 'active',
                'province' => $validated['province'] ?? 'Cavite',
                'branch_type' => $validated['branch_type'] ?? 'storefront',
                'is_main_branch' => $validated['is_main_branch'] ?? false,
                'geofence_enabled' => $validated['geofence_enabled'] ?? true,
                'geofence_radius_m' => $validated['geofence_radius_m'] ?? 0,
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Branch is created successfully',
                'data' => [
                    'name' => $branch->name,
                    'branch_code' => $branch->branch_code,
                    'status' => $branch->status,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Adding Branch failed',
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function generateBranchCode(string $name, ?int $storeId): string
    {
        $prefix = Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]+/', '', $name) ?: 'BR', 0, 6));
        $storeSegment = $storeId ? sprintf('%02d', $storeId % 100) : '00';

        do {
            $suffix = Str::upper(Str::random(3));
            $code = "{$prefix}-{$storeSegment}-{$suffix}";
        } while (Branch::where('branch_code', $code)->exists());

        return $code;
    }

    public function show($id)
    {
        $branch = Branch::findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $branch,
        ]);
    }

    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);
        $validated = $request->validate([
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'geofence_radius_m' => 'nullable|integer|min:0|max:5000',
            'geofence_enabled' => 'nullable|boolean',
        ]);

        $branch->fill($validated);
        $branch->save();

        return response()->json([
            'success' => true,
            'data' => $branch,
            'message' => 'Branch updated successfully',
        ]);
    }
}
