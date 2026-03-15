<?php

namespace App\Http\Controllers\Api\Procurement\SupplierPortal;

use App\Http\Controllers\Controller;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use App\Models\Procurement\SupplierPortal\SupplierPOFeedback;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;

class SupplierPOFeedbackController extends Controller
{
    /**
     * Get all POs sent to supplier
     * GET /api/supplier-portal/pos
     */
    public function getPOs(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $portal = SupplierPortal::where('user_id', $user->id)->firstOrFail();

            if (!$portal->isVerified()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your supplier account is not verified yet.',
                ], 403);
            }

            if (!$portal->supplier_id) {
                $perPage = (int) $request->get('per_page', 10);
                $page = (int) $request->get('page', 1);
                $empty = new LengthAwarePaginator([], 0, $perPage, $page, [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $empty,
                ]);
            }

            // Get POs for this supplier
            $query = PurchaseOrder::where('supplier_id', $portal->supplier_id)
                ->with(['items.product', 'supplier'])
                ->orderBy('created_at', 'desc');

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('po_number', 'LIKE', "%{$search}%");
            }

            $pos = $query->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'data' => $pos,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching POs: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific PO details
     * GET /api/supplier-portal/pos/{id}
     */
    public function getPODetail($id): JsonResponse
    {
        try {
            $user = auth()->user();
            $portal = SupplierPortal::where('user_id', $user->id)->firstOrFail();

            if (!$portal->isVerified()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your supplier account is not verified yet.',
                ], 403);
            }

            if (!$portal->supplier_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Supplier portal is not linked to a supplier.',
                ], 403);
            }

            $po = PurchaseOrder::with(['items.product', 'supplier', 'branch'])
                ->findOrFail($id);

            if ($po->supplier_id !== $portal->supplier_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this purchase order.',
                ], 403);
            }

            // Get supplier's feedback for this PO
            $feedback = $portal->poFeedbacks()
                ->where('purchase_order_id', $id)
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'po' => $po,
                    'supplier_feedback' => $feedback,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching PO: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Submit PO feedback (accept/reject + delivery schedule)
     * POST /api/supplier-portal/po-feedbacks
     */
    public function submitFeedback(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'response' => 'required|in:accepted,rejected',
            'rejection_reason' => 'required_if:response,rejected|string',
            'expected_delivery_date' => 'required_if:response,accepted|date|after_or_equal:today',
            'delivery_quantity' => 'required_if:response,accepted|integer|min:1',
            'delivery_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = auth()->user();
            $portal = SupplierPortal::where('user_id', $user->id)->firstOrFail();

            if (!$portal->isVerified()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your supplier account is not verified yet.',
                ], 403);
            }

            $po = PurchaseOrder::findOrFail($request->purchase_order_id);

            // Create or update feedback
            $feedback = SupplierPOFeedback::updateOrCreate(
                [
                    'supplier_portal_id' => $portal->id,
                    'purchase_order_id' => $request->purchase_order_id,
                ],
                [
                    'response' => $request->response,
                    'rejection_reason' => $request->get('rejection_reason'),
                    'expected_delivery_date' => $request->get('expected_delivery_date'),
                    'delivery_quantity' => $request->get('delivery_quantity'),
                    'delivery_notes' => $request->get('delivery_notes'),
                    'submitted_at' => now(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'PO feedback submitted successfully.',
                'data' => $feedback,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error submitting feedback: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirm receipt and update delivery schedule
     * POST /api/supplier-portal/po-feedbacks/{id}/confirm-receipt
     */
    public function confirmReceipt($id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'delivery_quantity' => 'required|integer|min:1',
            'delivery_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = auth()->user();
            $portal = SupplierPortal::where('user_id', $user->id)->firstOrFail();

            $feedback = SupplierPOFeedback::where('id', $id)
                ->where('supplier_portal_id', $portal->id)
                ->firstOrFail();

            if (!$feedback->canUpdateDeliverySchedule()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update delivery schedule for this PO.',
                ], 422);
            }

            $feedback->update([
                'receipt_status' => 'confirmed',
                'delivery_quantity' => $request->delivery_quantity,
                'delivery_notes' => $request->get('delivery_notes'),
                'receipt_confirmed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Receipt confirmed successfully.',
                'data' => $feedback,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error confirming receipt: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get supplier's PO feedbacks
     * GET /api/supplier-portal/po-feedbacks
     */
    public function getMyFeedbacks(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $portal = SupplierPortal::where('user_id', $user->id)->firstOrFail();

            $feedbacksQuery = $portal->poFeedbacks()
                ->with(['purchaseOrder'])
                ->orderBy('submitted_at', 'desc');

            if ($request->has('purchase_order_id')) {
                $feedbacksQuery->where('purchase_order_id', $request->purchase_order_id);
            }

            $feedbacks = $feedbacksQuery->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'data' => $feedbacks,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching feedbacks: ' . $e->getMessage(),
            ], 500);
        }
    }
}
