<?php

namespace App\Http\Controllers\Api\Procurement\SupplierPortal;

use App\Http\Controllers\Controller;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use App\Models\Procurement\SupplierPortal\SupplierVerificationDocument;
use App\Models\Procurement\Supplier\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class SupplierVerificationController extends Controller
{
    /**
     * Get all pending supplier verification requests
     * GET /api/supplier-verifications/pending
     */
    public function getPending(Request $request): JsonResponse
    {
        $query = SupplierPortal::where('status', 'pending')
            ->with(['user', 'verificationDocuments']);

        // Filter
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $portals = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $portals,
        ]);
    }

    /**
     * Get all supplier verification records (all statuses)
     * GET /api/supplier-verifications
     */
    public function index(Request $request): JsonResponse
    {
        $query = SupplierPortal::with(['user', 'verificationDocuments']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $portals = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $portals,
        ]);
    }

    /**
     * Get single supplier verification record
     * GET /api/supplier-verifications/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            $portal = SupplierPortal::with(['user', 'verificationDocuments', 'verifiedBy'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $portal,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier portal not found',
            ], 404);
        }
    }

    /**
     * Approve supplier verification
     * POST /api/supplier-verifications/{id}/approve
     */
    public function approve($id, Request $request): JsonResponse
    {
        try {
            $portal = SupplierPortal::findOrFail($id);
            $admin = auth()->user();

            // Check if all documents are approved
            if (!$portal->allDocumentsApproved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not all verification documents have been approved yet.',
                ], 422);
            }

            // Update portal
            $portal->update([
                'status' => 'approved',
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ]);

            if (!$portal->supplier_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Supplier portal is missing a linked supplier record.',
                ], 422);
            }

            Supplier::where('id', $portal->supplier_id)->update(['status' => 'active']);

            return response()->json([
                'success' => true,
                'message' => 'Supplier verified successfully.',
                'data' => $portal,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error approving supplier: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject supplier verification
     * POST /api/supplier-verifications/{id}/reject
     */
    public function reject($id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $portal = SupplierPortal::findOrFail($id);
            $admin = auth()->user();

            $portal->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Supplier verification rejected.',
                'data' => $portal,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error rejecting supplier: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Review verification document
     * POST /api/supplier-verifications/documents/{id}/review
     */
    public function reviewDocument($id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $document = SupplierVerificationDocument::findOrFail($id);
            $admin = auth()->user();

            $document->update([
                'status' => $request->status,
                'rejection_reason' => $request->get('rejection_reason'),
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document review submitted.',
                'data' => $document,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error reviewing document: ' . $e->getMessage(),
            ], 500);
        }
    }
}
