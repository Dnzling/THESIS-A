<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use Illuminate\Support\Facades\DB;

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
        $adminId = auth()->id();

        $result = DB::transaction(function () use ($id, $adminId) {
            $supplier = Supplier::findOrFail($id);
            $portal = SupplierPortal::where('supplier_id', $supplier->id)->first();

            // 1) Supplier operational status
            $supplier->status = 'active';
            if (isset($supplier->rejection_reason)) {
                $supplier->rejection_reason = null;
            }
            $supplier->save();

            if ($portal) {
                // 2) Supplier portal verification status
                $portal->status = 'approved';
                $portal->rejection_reason = null;
                $portal->verified_by = $adminId;
                $portal->verified_at = now();
                $portal->save();

                // 3) Supplier verification documents status
                $portal->verificationDocuments()->update([
                    'status' => 'approved',
                    'rejection_reason' => null,
                    'reviewed_by' => $adminId,
                    'reviewed_at' => now(),
                ]);
            }

            return [$supplier, $portal];
        });

        return response()->json([
            'success' => true,
            'message' => 'Supplier approved. Supplier, portal, and verification documents were updated.',
            'data' => [
                'supplier' => $result[0],
                'portal' => $result[1],
            ],
        ]);
    }

    public function reject(Request $request, $id): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'rejection_reason' => 'required|string|min:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $adminId = auth()->id();
        $reason = (string) $request->rejection_reason;

        $result = DB::transaction(function () use ($id, $adminId, $reason) {
            $supplier = Supplier::findOrFail($id);
            $portal = SupplierPortal::where('supplier_id', $supplier->id)->first();

            // 1) Supplier operational status
            $supplier->status = 'rejected';
            if (isset($supplier->rejection_reason)) {
                $supplier->rejection_reason = $reason;
            }
            $supplier->save();

            if ($portal) {
                // 2) Supplier portal verification status
                $portal->status = 'rejected';
                $portal->rejection_reason = $reason;
                $portal->verified_by = $adminId;
                $portal->verified_at = now();
                $portal->save();

                // 3) Supplier verification documents status
                $portal->verificationDocuments()->update([
                    'status' => 'rejected',
                    'rejection_reason' => $reason,
                    'reviewed_by' => $adminId,
                    'reviewed_at' => now(),
                ]);
            }

            return [$supplier, $portal];
        });

        return response()->json([
            'success' => true,
            'message' => 'Supplier rejected. Supplier, portal, and verification documents were updated.',
            'data' => [
                'supplier' => $result[0],
                'portal' => $result[1],
            ],
        ]);
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
