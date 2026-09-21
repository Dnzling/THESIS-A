<?php

namespace App\Http\Controllers\Api\Procurement\SupplierPortal;

use App\Http\Controllers\Controller;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use App\Models\Procurement\SupplierPortal\SupplierRFQFeedback;
use App\Models\Procurement\RFQ\RequestForQuotation;
use App\Models\Procurement\RFQ\RFQItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
                ], 409);
            }

            // Get all active RFQs (exclude drafts/cancelled)
            $relations = ['items.product', 'store'];
            if (Schema::hasTable('rfq_attachments')) {
                $relations[] = 'attachments';
            }

            $query = RequestForQuotation::whereNotIn('status', ['draft', 'cancelled'])
                ->with($relations)
                ->orderBy('created_at', 'desc');

            // Filter by search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('rfq_number', 'LIKE', "%{$search}%");
            }

            // Filter by status (exact)
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Date range filter (created_at)
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            if ($dateFrom && $dateTo) {
                $query->whereBetween('created_at', [
                    $dateFrom . ' 00:00:00',
                    $dateTo . ' 23:59:59',
                ]);
            } elseif ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            } elseif ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            }

            // Sorting (default: created_at desc)
            $sortField = $request->get('sort_field', 'created_at');
            $sortOrder = strtolower($request->get('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
            $allowedSorts = ['created_at', 'issue_date', 'rfq_number', 'status'];
            if (!in_array($sortField, $allowedSorts, true)) {
                $sortField = 'created_at';
            }
            $query->orderBy($sortField, $sortOrder);

            $rfqs = $query->paginate($request->get('per_page', 10));

            // Hide payment_terms and attachment_path from supplier-facing responses
            $rfqs->getCollection()->transform(function ($rfq) {
                $arr = $rfq->toArray();

                // expose store data & fallback store_name
                if ($rfq->relationLoaded('store') && $rfq->store) {
                    $arr['store'] = [
                        'id' => $rfq->store->id ?? null,
                        'store_name' => $rfq->store->store_name ?? null,
                        'name' => $rfq->store->name ?? null,
                        'store_code' => $rfq->store->store_code ?? null,
                    ];
                    $arr['store_name'] = $arr['store']['store_name']
                        ?? $arr['store']['name']
                        ?? $arr['store']['store_code']
                        ?? null;
                } else {
                    $arr['store'] = null;
                    $arr['store_name'] = $arr['store_name'] ?? null;
                }

                if (isset($arr['payment_terms'])) {
                    unset($arr['payment_terms']);
                }
                if (!empty($arr['attachments']) && is_array($arr['attachments'])) {
                    foreach ($arr['attachments'] as &$att) {
                        if (isset($att['file_path'])) unset($att['file_path']);
                    }
                }
                return $arr;
            });

            return response()->json([
                'success' => true,
                'data' => $rfqs,
            ]);
        } catch (\Exception $e) {
            Log::error('Supplier RFQ index failed.', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
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
            $portal = SupplierPortal::where('user_id', $user->id)->first();
            if (!$portal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Supplier portal not found.',
                ], 404);
            }

            if (!$portal->isVerified()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your supplier account is not verified yet.',
                ], 409);
            }

            // Only expose public variant attributes to suppliers; never serialize cost_price.
            $relations = [
                'items.product',
                'items.variation:id,product_id,variation_name,variation_sku,color,size,material,texture,finish',
                'store',
            ];
            if (Schema::hasTable('rfq_attachments')) {
                $relations[] = 'attachments';
            }

            $rfq = RequestForQuotation::with($relations)->findOrFail($id);

            // Mark RFQ as viewed for this supplier
            \App\Models\Procurement\RFQ\RFQSupplier::where('rfq_id', $id)
                ->where('supplier_id', $portal->supplier_id)
                ->whereNull('viewed_at')
                ->update([
                    'status' => 'viewed',
                    'viewed_at' => now(),
                ]);

            // Get supplier's existing feedback for this RFQ
            $feedback = $portal->rfqFeedbacks()
                ->where('rfq_id', $id)
                ->with(['negotiations'])
                ->get();

            // Hide payment_terms and attachment_path from supplier-facing detail
            $rfqArr = $rfq->toArray();
            $rfqArr['attachments'] = $rfqArr['attachments'] ?? [];
            if ($rfq->relationLoaded('store') && $rfq->store) {
                $rfqArr['store'] = [
                    'id' => $rfq->store->id ?? null,
                    'store_name' => $rfq->store->store_name ?? null,
                    'name' => $rfq->store->name ?? null,
                    'store_code' => $rfq->store->store_code ?? null,
                ];
                $rfqArr['store_name'] = $rfqArr['store']['store_name']
                    ?? $rfqArr['store']['name']
                    ?? $rfqArr['store']['store_code']
                    ?? null;
            } else {
                $rfqArr['store'] = null;
                $rfqArr['store_name'] = $rfqArr['store_name'] ?? null;
            }
            if (isset($rfqArr['payment_terms'])) {
                unset($rfqArr['payment_terms']);
            }
            if (!empty($rfqArr['attachments']) && is_array($rfqArr['attachments'])) {
                foreach ($rfqArr['attachments'] as &$att) {
                    if (isset($att['file_path'])) unset($att['file_path']);
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'rfq' => $rfqArr,
                    'supplier_feedback' => $feedback,
                ],
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'RFQ not found.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Supplier RFQ detail failed.', [
                'user_id' => auth()->id(),
                'rfq_id' => $id,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error fetching RFQ details.',
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
            'available_quantity' => 'required|numeric|min:0',
            'has_variant' => 'nullable|boolean',
            'variant_name' => 'required_if:has_variant,1|nullable|string|max:200',
            'supplier_sku' => 'nullable|string|max:100',
            'variant_size' => 'nullable|string|max:100',
            'variant_color' => 'nullable|string|max:100',
            'variant_texture' => 'nullable|string|max:100',
            'variant_finish' => 'nullable|string|max:100',
            'variant_material' => 'nullable|string|max:100',
            'unit_of_measurement' => 'nullable|string|max:50',
            'variant_images' => 'nullable|array|max:5',
            'variant_images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'length_cm' => 'required|numeric|min:0.01',
            'width_cm' => 'required|numeric|min:0.01',
            'height_cm' => 'required|numeric|min:0.01',
            'weight_kg' => 'required|numeric|min:0.001',
            'estimated_delivery_date' => 'required|date',
            'quotation_valid_until' => 'required|date|after_or_equal:today',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:5120',
            'product_specifications' => 'nullable|string|max:2000',
            'additional_notes' => 'nullable|string|max:2000',
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
                ], 409);
            }

            $rfq = RequestForQuotation::findOrFail($request->rfq_id);

            if ($rfq->status === 'closed') {
                return response()->json([
                    'success' => false,
                    'message' => 'This RFQ is already closed.',
                ], 422);
            }

            // Prevent submission if this RFQ item already has an approved feedback
            $alreadyApproved = \App\Models\Procurement\SupplierPortal\SupplierRFQFeedback::where('rfq_item_id', $request->rfq_item_id)
                ->where('status', 'approved')
                ->exists();

            if ($alreadyApproved) {
                return response()->json([
                    'success' => false,
                    'message' => 'This item has already been approved and cannot accept new quotes.',
                ], 422);
            }

            // Determine supplier contract tax rate (server authoritative)
            $rfq = RequestForQuotation::findOrFail($request->rfq_id);
            $contract = \App\Models\Procurement\Supplier\SupplierContract::where('supplier_id', $portal->supplier_id)
                ->where('store_id', $rfq->store_id)
                ->where('status', 'active')
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->first();

            $contractTaxRate = ($contract && !$contract->is_tax_exempt) ? ($contract->tax_rate ?? 0) : 0;

            // Create or update feedback (use contract tax rate)
            $attachmentPath = $request->hasFile('attachment')
                ? $request->file('attachment')->store('supplier/rfq-attachments', 'public')
                : null;
            $variantImagePaths = collect($request->file('variant_images', []))
                ->map(fn ($image) => $image->store('supplier/rfq-variant-images', 'public'))
                ->values()
                ->all();

            $feedback = SupplierRFQFeedback::updateOrCreate(
                [
                    'supplier_portal_id' => $portal->id,
                    'rfq_item_id' => $request->rfq_item_id,
                ],
                [
                    'rfq_id' => $request->rfq_id,
                    'quoted_price' => $request->quoted_price,
                    'available_quantity' => $request->available_quantity,
                    'has_variant' => $request->boolean('has_variant'),
                    'variant_name' => $request->boolean('has_variant') ? $request->variant_name : null,
                    'supplier_sku' => $request->boolean('has_variant') ? $request->supplier_sku : null,
                    'variant_size' => $request->boolean('has_variant') ? $request->variant_size : null,
                    'variant_color' => $request->boolean('has_variant') ? $request->variant_color : null,
                    'variant_texture' => $request->boolean('has_variant') ? $request->variant_texture : null,
                    'variant_finish' => $request->boolean('has_variant') ? $request->variant_finish : null,
                    'variant_material' => $request->boolean('has_variant') ? $request->variant_material : null,
                    'unit_of_measurement' => $request->boolean('has_variant') ? $request->unit_of_measurement : null,
                    'length_cm' => $request->length_cm,
                    'width_cm' => $request->width_cm,
                    'height_cm' => $request->height_cm,
                    'weight_kg' => $request->weight_kg,
                    'estimated_delivery_date' => $request->estimated_delivery_date,
                    'quotation_valid_until' => $request->quotation_valid_until,
                    'attachment_path' => $attachmentPath,
                    'variant_image_paths' => $variantImagePaths ?: null,
                    'merchandising_status' => $request->boolean('has_variant') ? 'awaiting_procurement_approval' : null,
                    'product_specifications' => $request->product_specifications,
                    'additional_notes' => $request->additional_notes,
                    'tax_rate' => $contractTaxRate,
                    'description' => $request->description,
                    'status' => 'pending',
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'rejection_reason' => null,
                    'submitted_at' => now(),
                ]
            );

            // Also record submitted prices into supplier_quotation_items and update quotation subtotal/total
            try {
                if (\Schema::hasTable('supplier_quotations')) {
                    $quotation = \App\Models\Procurement\RFQ\SupplierQuotation::where('rfq_id', $rfq->id)
                        ->where('supplier_id', $portal->supplier_id)
                        ->first();

                    if ($quotation) {
                        if (\Schema::hasTable('supplier_quotation_items')) {
                            $rfqItem = RFQItem::find($request->rfq_item_id);
                            $lineQty = $rfqItem?->quantity ?? 1;
                            $lineTotal = bcmul((string)$request->quoted_price, (string)$lineQty, 2);

                            \App\Models\Procurement\RFQ\SupplierQuotationItem::updateOrCreate([
                                'quotation_id' => $quotation->id,
                                'rfq_item_id' => $request->rfq_item_id,
                            ], [
                                'unit_price' => $request->quoted_price,
                                'quantity' => $lineQty,
                                'discount_percent' => 0,
                                'line_total' => $lineTotal,
                                'notes' => $request->description ?? null,
                            ]);

                            // Recompute subtotal and totals for the master quotation
                            $subtotal = \App\Models\Procurement\RFQ\SupplierQuotationItem::where('quotation_id', $quotation->id)
                                ->sum('line_total');

                            $discountPercent = (float) ($contract?->discount_percentage ?? 0);
                            $discountAmount = round((float) $subtotal * ($discountPercent / 100), 2);
                            $taxableAmount = max(0, (float) $subtotal - $discountAmount);
                            $taxAmount = 0;
                            if (!empty($contractTaxRate)) {
                                $taxAmount = bcmul((string)$taxableAmount, bcdiv((string)$contractTaxRate, '100', 4), 2);
                            }

                            $total = bcadd((string) $taxableAmount, $taxAmount, 2);

                            // Update columns if they exist
                            $updateData = [];
                            $cols = array_map(fn($c) => is_object($c) ? $c->Field : $c['Field'], DB::select("SHOW COLUMNS FROM supplier_quotations"));
                            if (in_array('subtotal', $cols, true)) $updateData['subtotal'] = $subtotal;
                            if (in_array('discount_amount', $cols, true)) $updateData['discount_amount'] = $discountAmount;
                            if (in_array('tax_amount', $cols, true)) $updateData['tax_amount'] = $taxAmount;
                            if (in_array('total_amount', $cols, true)) $updateData['total_amount'] = $total;

                            if (!empty($updateData)) {
                                $quotation->update($updateData);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // non-fatal: if quotation tables absent or insert fails, continue
                \Log::warning('Failed to persist supplier quotation line: ' . $e->getMessage());
            }

            // Update RFQ supplier status for this supplier
            \App\Models\Procurement\RFQ\RFQSupplier::where('rfq_id', $request->rfq_id)
                ->where('supplier_id', $portal->supplier_id)
                ->update([
                    'status' => 'submitted',
                    'viewed_at' => \DB::raw('COALESCE(viewed_at, NOW())'),
                    'responded_at' => now(),
                ]);

            $rfq = RequestForQuotation::with(['createdBy'])->find($request->rfq_id);
            if ($rfq) {
                $payload = [
                    'store_id' => $rfq->store_id,
                    'module' => 'procurement',
                    'entity_type' => 'request_for_quotation',
                    'entity_id' => $rfq->id,
                    'action' => 'supplier_quote_submitted',
                    'title' => 'Supplier Quote Received',
                    'message' => "A supplier submitted a quote for {$rfq->rfq_number}.",
                    'severity' => 'info',
                    'link' => "/system/procurement/rfqs/{$rfq->id}",
                ];

                $recipientUserIds = [];
                if ($rfq->createdBy?->user_id) {
                    $recipientUserIds[] = (int) $rfq->createdBy->user_id;
                }

                $procurementUserIds = $this->userIdsWithAnyPermission((int) $rfq->store_id, [
                    'procurement.rfq.manage',
                    'procurement.requisitions.manage',
                    'procurement.purchase_orders.manage',
                ]);

                $recipientUserIds = array_values(array_unique(array_merge($recipientUserIds, $procurementUserIds)));
                $recipientUserIds = array_values(array_filter($recipientUserIds, fn($id) => (int) $id !== (int) $user->id));

                if (!empty($recipientUserIds)) {
                    $this->notifyMany($recipientUserIds, $payload);
                }
            }

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
