<?php
// app/Http/Controllers/Api/Hr/DeductionTypeController.php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\DeductionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DeductionTypeController extends Controller
{
    /**
     * Get all deduction types for the authenticated user's store
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $storeId = $user->store_id;

            // Check if user has store access
            if (!$storeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not associated with any store'
                ], 403);
            }

            $query = DeductionType::where('store_id', $storeId);
            
            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }
            
            // Filter by category
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }
            
            // Filter by calculation type
            if ($request->has('calculation_type')) {
                $query->where('calculation_type', $request->calculation_type);
            }
            
            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Include employee count if requested
            if ($request->boolean('with_counts')) {
                $query->withCount('employeeDeductions');
            }
            
            $deductionTypes = $query->orderBy('sort_order')
                ->orderBy('name')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $deductionTypes,
                'store_id' => $storeId,
                'total' => $deductionTypes->count()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch deduction types: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new deduction type for the authenticated user's store
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $storeId = $user->store_id;

            // Check if user has store access
            if (!$storeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not associated with any store'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100',
                'code' => 'required|string|max:50|unique:deduction_types,code,NULL,id,store_id,' . $storeId,
                'category' => 'required|in:government,company,loan,benefit,other',
                'calculation_type' => 'required|in:fixed,percentage,formula',
                'frequency' => 'sometimes|in:one-time,monthly,bi-monthly,quarterly,annual',
                
                // For fixed type
                'default_amount' => 'required_if:calculation_type,fixed|nullable|numeric|min:0',
                
                // For percentage type
                'percentage_value' => 'required_if:calculation_type,percentage|nullable|numeric|min:0|max:100',
                'percentage_basis' => 'required_if:calculation_type,percentage|nullable|in:basic,gross,taxable',
                'min_amount' => 'nullable|numeric|min:0',
                'max_amount' => 'nullable|numeric|min:0|gt:min_amount',
                
                // For formula type
                'formula_data' => 'required_if:calculation_type,formula|nullable|json',
                
                // General fields
                'is_mandatory' => 'boolean',
                'is_taxable' => 'boolean',
                'is_active' => 'boolean',
                'show_on_payslip' => 'boolean',
                'sort_order' => 'integer|min:0',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Prepare data
            $data = $validator->validated();
            $data['store_id'] = $storeId;
            $data['created_by'] = $user->id;
            
            // Handle JSON fields
            if (isset($data['formula_data']) && is_string($data['formula_data'])) {
                $data['formula_data'] = json_decode($data['formula_data'], true);
            }

            $deductionType = DeductionType::create($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Deduction type created successfully',
                'data' => $deductionType
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create deduction type: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a single deduction type (must belong to user's store)
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            $storeId = $user->store_id;

            if (!$storeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not associated with any store'
                ], 403);
            }

            $deductionType = DeductionType::where('store_id', $storeId)
                ->withCount('employeeDeductions')
                ->find($id);

            if (!$deductionType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Deduction type not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $deductionType
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch deduction type: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a deduction type (must belong to user's store)
     */
    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $storeId = $user->store_id;

            if (!$storeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not associated with any store'
                ], 403);
            }

            $deductionType = DeductionType::where('store_id', $storeId)->find($id);

            if (!$deductionType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Deduction type not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:100',
                'code' => 'sometimes|string|max:50|unique:deduction_types,code,' . $id . ',id,store_id,' . $storeId,
                'category' => 'sometimes|in:government,company,loan,benefit,other',
                'calculation_type' => 'sometimes|in:fixed,percentage,formula',
                'frequency' => 'sometimes|in:one-time,monthly,bi-monthly,quarterly,annual',
                
                // For fixed type
                'default_amount' => 'required_if:calculation_type,fixed|nullable|numeric|min:0',
                
                // For percentage type
                'percentage_value' => 'required_if:calculation_type,percentage|nullable|numeric|min:0|max:100',
                'percentage_basis' => 'required_if:calculation_type,percentage|nullable|in:basic,gross,taxable',
                'min_amount' => 'nullable|numeric|min:0',
                'max_amount' => 'nullable|numeric|min:0|gt:min_amount',
                
                // For formula type
                'formula_data' => 'required_if:calculation_type,formula|nullable|json',
                
                // General fields
                'is_mandatory' => 'boolean',
                'is_taxable' => 'boolean',
                'is_active' => 'boolean',
                'show_on_payslip' => 'boolean',
                'sort_order' => 'integer|min:0',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $data['updated_by'] = $user->id;

            // Handle JSON fields
            if (isset($data['formula_data']) && is_string($data['formula_data'])) {
                $data['formula_data'] = json_decode($data['formula_data'], true);
            }

            $deductionType->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Deduction type updated successfully',
                'data' => $deductionType->fresh()
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update deduction type: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a deduction type (must belong to user's store)
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $storeId = $user->store_id;

            if (!$storeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not associated with any store'
                ], 403);
            }

            $deductionType = DeductionType::where('store_id', $storeId)->find($id);

            if (!$deductionType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Deduction type not found'
                ], 404);
            }

            // Check if deduction type is being used
            if ($deductionType->employeeDeductions()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete deduction type that is assigned to employees'
                ], 422);
            }

            $deductionType->delete();

            return response()->json([
                'success' => true,
                'message' => 'Deduction type deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete deduction type: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle active status
     */
    public function toggleActive($id)
    {
        try {
            $user = Auth::user();
            $storeId = $user->store_id;

            if (!$storeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not associated with any store'
                ], 403);
            }

            $deductionType = DeductionType::where('store_id', $storeId)->find($id);

            if (!$deductionType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Deduction type not found'
                ], 404);
            }

            $deductionType->update([
                'is_active' => !$deductionType->is_active,
                'updated_by' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Deduction type ' . ($deductionType->is_active ? 'activated' : 'deactivated') . ' successfully',
                'is_active' => $deductionType->is_active
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle deduction type status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get deduction types grouped by category
     */
    public function getByCategory()
    {
        try {
            $user = Auth::user();
            $storeId = $user->store_id;

            if (!$storeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not associated with any store'
                ], 403);
            }

            $types = DeductionType::where('store_id', $storeId)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->groupBy('category');

            return response()->json([
                'success' => true,
                'data' => $types
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch deduction types: ' . $e->getMessage()
            ], 500);
        }
    }
}