<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Procurement\Supplier\Supplier;

class SupplierVerificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Return suppliers with status 'pending' (adjust column if different)
        $suppliers = Supplier::where('status', 'pending')
            ->select('id', 'supplier_code', 'supplier_name', 'contact_person', 'email', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($suppliers);
    }

    public function approve(Request $request, $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->status = 'active';
        $supplier->save();

        return response()->json(['success' => true, 'message' => 'Supplier approved', 'data' => $supplier]);
    }

    public function reject(Request $request, $id): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'rejection_reason' => 'required|string|min:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $supplier = Supplier::findOrFail($id);
        $supplier->status = 'rejected';
        $supplier->rejection_reason = $request->rejection_reason;
        $supplier->save();

        return response()->json(['success' => true, 'message' => 'Supplier rejected', 'data' => $supplier]);
    }

    /**
     * Show supplier details with documents
     */
    public function show(Request $request, $id): JsonResponse
    {
        $supplier = Supplier::with(['documents'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $supplier]);
    }

    /**
     * Request resubmission from supplier
     */
    public function requestResubmission(Request $request, $id): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'notes' => 'required|string|min:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $supplier = Supplier::findOrFail($id);
        $supplier->status = 'needs_resubmission';
        $supplier->resubmission_notes = $request->notes;
        $supplier->save();

        // Optionally notify supplier via email/notification (omitted)

        return response()->json(['success' => true, 'message' => 'Resubmission requested', 'data' => $supplier]);
    }
}
