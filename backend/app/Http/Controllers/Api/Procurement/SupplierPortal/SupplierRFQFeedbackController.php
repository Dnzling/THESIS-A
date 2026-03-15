<?php

namespace App\Http\Controllers\Api\Procurement\SupplierPortal;

use App\Http\Controllers\Controller;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use App\Models\Procurement\SupplierPortal\SupplierRFQFeedback;
use App\Models\Procurement\RFQ\RequestForQuotation;
use App\Models\Procurement\RFQ\RFQItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class SupplierRFQFeedbackController extends Controller
{
    /**
     * Get all RFQs available for supplier response
     * GET /api/supplier-portal/rfqs
     */
    public function getRFQs(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $portal = SupplierPortal::where('user_id', $user->id)->firstOrFail();

            // Only approved suppliers can view RFQs
            if (!$portal->isVerified()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your supplier account is not verified yet.',
                ], 403);
            }

            // Get all active RFQs (exclude drafts/cancelled)
            $query = RequestForQuotation::whereNotIn('status', ['draft', 'cancelled'])
                ->with(['items.product', 'attachments'])
                ->orderBy('created_at', 'desc');

            // Filter by search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('rfq_number', 'LIKE', "%{$search}%");
            }

            $rfqs = $query->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'data' => $rfqs,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching RFQs: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific RFQ details
     * GET /api/supplier-portal/rfqs/{id}
     */
    public function getRFQDetail($id): JsonResponse
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

            $rfq = RequestForQuotation::with(['items.product', 'attachments'])
                ->findOrFail($id);

            // Get supplier's existing feedback for this RFQ
            $feedback = $portal->rfqFeedbacks()
                ->where('rfq_id', $id)
                ->with(['negotiations'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'rfq' => $rfq,
                    'supplier_feedback' => $feedback,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching RFQ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Submit RFQ feedback (quote)
     * POST /api/supplier-portal/rfq-feedbacks
     */
    public function submitFeedback(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rfq_id' => 'required|exists:request_for_quotations,id',
            'rfq_item_id' => 'required|exists:rfq_items,id',
            'quoted_price' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:1000',
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

            $rfq = RequestForQuotation::findOrFail($request->rfq_id);
            
            if ($rfq->status === 'closed') {
                return response()->json([
                    'success' => false,
                    'message' => 'This RFQ is already closed.',
                ], 422);
            }

            // Create or update feedback
            $feedback = SupplierRFQFeedback::updateOrCreate(
                [
                    'supplier_portal_id' => $portal->id,
                    'rfq_item_id' => $request->rfq_item_id,
                ],
                [
                    'rfq_id' => $request->rfq_id,
                    'quoted_price' => $request->quoted_price,
                    'description' => $request->description,
                    'status' => 'pending',
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'rejection_reason' => null,
                    'submitted_at' => now(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Quote submitted successfully.',
                'data' => $feedback,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error submitting quote: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get supplier's RFQ responses
     * GET /api/supplier-portal/rfq-feedbacks
     */
    public function getMyFeedbacks(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $portal = SupplierPortal::where('user_id', $user->id)->firstOrFail();

            $feedbacks = $portal->rfqFeedbacks()
                ->with(['rfq', 'rfqItem'])
                ->orderBy('submitted_at', 'desc')
                ->paginate($request->get('per_page', 10));

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

    /**
     * Supplier accepts a negotiation offer
     * POST /api/supplier-portal/rfq-negotiations/{id}/accept
     */
    public function acceptNegotiation(Request $request, int $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $portal = SupplierPortal::where('user_id', $user->id)->firstOrFail();

            $negotiation = \App\Models\Procurement\SupplierPortal\SupplierRFQNegotiation::with(['feedback'])
                ->where('supplier_portal_id', $portal->id)
                ->findOrFail($id);

            if ($negotiation->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Negotiation is already resolved.',
                ], 422);
            }

            // Accept negotiation and update quote price
            $negotiation->update([
                'status' => 'accepted',
            ]);

            if ($negotiation->feedback) {
                $negotiation->feedback->update([
                    'quoted_price' => $negotiation->counter_price,
                    'status' => 'pending',
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'rejection_reason' => null,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Negotiation accepted.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error accepting negotiation: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supplier rejects a negotiation offer
     * POST /api/supplier-portal/rfq-negotiations/{id}/reject
     */
    public function rejectNegotiation(Request $request, int $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $portal = SupplierPortal::where('user_id', $user->id)->firstOrFail();

            $negotiation = \App\Models\Procurement\SupplierPortal\SupplierRFQNegotiation::with(['feedback'])
                ->where('supplier_portal_id', $portal->id)
                ->findOrFail($id);

            if ($negotiation->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Negotiation is already resolved.',
                ], 422);
            }

            $negotiation->update([
                'status' => 'rejected',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Negotiation rejected.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error rejecting negotiation: ' . $e->getMessage(),
            ], 500);
        }
    }
}
