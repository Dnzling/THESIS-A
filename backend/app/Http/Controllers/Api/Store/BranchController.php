<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Store\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();
            
            // Get branches for the user's store
            $branches = Branch::where('store_id', $user->store_id)
                ->select('id', 'name', 'branch_code', 'address', 'status', 'contact_number')
                ->orderBy('name')
                ->get();
            
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
            $validated = $request->validate([
                'store_id' => 'required|integer|exists:stores,id',
                'name' => 'required|string|max:255',
                'address' => 'required|string|max:255',
                'latitude' => 'nullable|numeric|between:-90, 90',
                'longitude' => 'nullable|numeric|between:-180, 180',
                'contact_number' => 'nullable|string|max:20',
                'branch_code' => 'required|string|max:20|unique:branches,branch_code',
                'is_main_branch' => 'nullable|boolean',
            ]);

            $branch = Branch::create(array_merge($validated, ['status' => 'active']));

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

    public function show($id) {}

    public function update() {}

    public function delete() {}

    public function active() {}
}
