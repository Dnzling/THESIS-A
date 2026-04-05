<?php

namespace App\Http\Controllers\Api\Procurement\SupplierPortal;

use App\Http\Controllers\Controller;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use App\Models\Procurement\SupplierPortal\SupplierVerificationDocument;
use App\Models\Procurement\Supplier\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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
            ->with(['user', 'supplier', 'verificationDocuments']);

        // Filter
        if ($request->has('search')) {
            $search = $request->search;
            // search portal fields first (company_name, supplier_name, contact_person)
            $query->where(function ($q) use ($search) {
                if (Schema::hasColumn('supplier_portals', 'company_name')) {
                    $q->orWhere('company_name', 'LIKE', "%{$search}%");
                }
                if (Schema::hasColumn('supplier_portals', 'supplier_name')) {
                    $q->orWhere('supplier_name', 'LIKE', "%{$search}%");
                }
                if (Schema::hasColumn('supplier_portals', 'contact_person')) {
                    $q->orWhere('contact_person', 'LIKE', "%{$search}%");
                }
                $q->orWhereHas('user', function ($uq) use ($search) {
                    $uq->where('email', 'LIKE', "%{$search}%");
                });
            });
        }

        $portals = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        // Map paginator items to include supplier table fields for admin display
        $mapped = $portals->getCollection()->map(function ($portal) {
            $supplier = $portal->supplier;
            return [
                'id' => $portal->id,
                'portal_id' => $portal->id,
                'supplier_id' => $supplier?->id,
                'created_at' => $supplier?->created_at ?? $portal->created_at,
                'supplier_name' => $supplier?->supplier_name ?? $portal->supplier_name ?? $portal->company_name,
                'contact_person' => $supplier?->contact_person ?? $portal->contact_person,
                'email' => $supplier?->email ?? $portal->user?->email ?? null,
                'verificationDocuments' => $portal->verificationDocuments()->get(),
                'status' => $portal->status,
            ];
        });

        // Replace paginator collection with mapped items
        $portals->setCollection($mapped);

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
        try {
            $query = SupplierPortal::with(['user', 'supplier', 'verificationDocuments']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            // search portal fields first (company_name, supplier_name, contact_person)
            $query->where(function ($q) use ($search) {
                if (Schema::hasColumn('supplier_portals', 'company_name')) {
                    $q->orWhere('company_name', 'LIKE', "%{$search}%");
                }
                if (Schema::hasColumn('supplier_portals', 'supplier_name')) {
                    $q->orWhere('supplier_name', 'LIKE', "%{$search}%");
                }
                if (Schema::hasColumn('supplier_portals', 'contact_person')) {
                    $q->orWhere('contact_person', 'LIKE', "%{$search}%");
                }
                $q->orWhereHas('user', function ($uq) use ($search) {
                    $uq->where('email', 'LIKE', "%{$search}%");
                });
            });
        }

            $portals = $query->orderBy('created_at', 'desc')
                ->paginate((int) $request->get('per_page', 15));

        $mapped = $portals->getCollection()->map(function ($portal) {
            $supplier = $portal->supplier;
            return [
                'id' => $portal->id,
                'portal_id' => $portal->id,
                'supplier_id' => $supplier?->id,
                'created_at' => $supplier?->created_at ?? $portal->created_at,
                'supplier_name' => $supplier?->supplier_name ?? $portal->supplier_name ?? $portal->company_name,
                'contact_person' => $supplier?->contact_person ?? $portal->contact_person,
                'email' => $supplier?->email ?? $portal->user?->email ?? null,
                'verificationDocuments' => $portal->verificationDocuments()->get(),
                'status' => $portal->status,
            ];
        });

            $portals->setCollection($mapped);

            return response()->json([
                'success' => true,
                'data' => $portals,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            logger()->error('[SupplierVerification][index] QueryException', ['error' => $e->getMessage(), 'request' => $request->all()]);
            return response()->json(['success' => false, 'message' => 'Database error: '.$e->getMessage()], 500);
        } catch (\Exception $e) {
            logger()->error('[SupplierVerification][index] Exception', ['error' => $e->getMessage(), 'request' => $request->all()]);
            return response()->json(['success' => false, 'message' => 'Server error: '.$e->getMessage()], 500);
        }
    }

    /**
     * Get single supplier verification record
     * GET /api/supplier-verifications/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            $portal = SupplierPortal::with(['user', 'supplier', 'verificationDocuments', 'verifiedBy'])
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
            // support calling approve with either portal id or supplier id
            $portal = SupplierPortal::find($id);
            if (!$portal) {
                $portal = SupplierPortal::where('supplier_id', $id)->first();
            }
            if (!$portal) {
                throw new \Exception("Supplier portal not found for id {$id}");
            }
            $admin = auth()->user();

            // Ensure portal has verification documents
            $documentsCount = $portal->verificationDocuments()->count();
            if ($documentsCount === 0) {
                logger()->info('[SupplierVerification][approve] No documents', ['id' => $id, 'portal_id' => $portal->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'No verification documents found for this portal.',
                ], 422);
            }

            // Check if all required documents are approved; if not, mark them approved by admin
            if (!$portal->allDocumentsApproved()) {
                logger()->info('[SupplierVerification][approve] Documents not all approved - auto-approving', ['id' => $id, 'portal_id' => $portal->id]);
                // mark pending documents as approved by this admin
                $admin = auth()->user();
                $portal->verificationDocuments()->where('status', '!=', 'approved')->get()->each(function ($doc) use ($admin) {
                    $doc->status = 'approved';
                    $doc->reviewed_by = $admin->id;
                    $doc->reviewed_at = now();
                    $doc->save();
                });
            }

            if (!$portal->supplier_id) {
                logger()->info('[SupplierVerification][approve] Missing supplier link', ['id' => $id, 'portal_id' => $portal->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Supplier portal is missing a linked supplier record.',
                ], 422);
            }

            // perform status updates inside transaction
            \DB::transaction(function () use ($portal, $admin) {
                $portal->update([
                    'status' => 'approved',
                    'verified_by' => $admin->id,
                    'verified_at' => now(),
                ]);

                $supplier = Supplier::find($portal->supplier_id);
                if (!$supplier) {
                    throw new \Exception('Linked supplier record not found.');
                }

                $supplier->status = 'active';
                $supplier->save();
            });

            return response()->json([
                'success' => true,
                'message' => 'Supplier verified successfully.',
                'data' => $portal,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // log DB errors for debugging
            logger()->error('[SupplierVerification][approve] QueryException', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Database error during approval: ' . $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            logger()->error('[SupplierVerification][approve] Exception', ['id' => $id, 'error' => $e->getMessage()]);
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

    /**
     * Download a verification document (admin access)
     * GET /api/supplier-verifications/documents/{id}/download
     */
    public function downloadDocument($id)
    {
        try {
            $document = SupplierVerificationDocument::findOrFail($id);

            return \Illuminate\Support\Facades\Storage::disk('private')->download($document->file_path, $document->original_filename);
        } catch (\Exception $e) {
            abort(404);
        }
    }
}
