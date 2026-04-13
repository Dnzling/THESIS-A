<?php

namespace App\Http\Controllers\Api\Procurement\SupplierPortal;

use App\Http\Controllers\Controller;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use App\Models\Procurement\SupplierPortal\SupplierVerificationDocument;
use App\Models\Procurement\SupplierPortal\SupplierPOFeedback;
use App\Models\Procurement\SupplierPortal\SupplierRFQFeedback;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Procurement\Supplier\SupplierContract;
use App\Models\Store\Store;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SupplierPortalController extends Controller
{
    /**
     * Get stores currently linked to supplier account.
     * GET /api/supplier-portal/stores/linked
     */
    public function getLinkedStores(Request $request): JsonResponse
    {
        $user = auth()->user();
        $portal = SupplierPortal::with('supplier')->where('user_id', $user->id)->firstOrFail();

        $supplierEmail = strtolower(trim((string) ($portal->supplier?->email ?: $user->email)));
        if ($supplierEmail === '') {
            return response()->json(['success' => true, 'data' => []]);
        }

        $items = Supplier::query()
            ->select([
                'suppliers.id as supplier_id',
                'suppliers.store_id',
                'suppliers.supplier_name',
                'suppliers.contact_person',
                'suppliers.email',
                'suppliers.phone',
                'suppliers.status as supplier_status',
                'suppliers.created_at as linked_at',
                'stores.name as store_name',
                'stores.city',
                'stores.province',
                'stores.address',
                'stores.status as store_status',
            ])
            ->join('stores', 'stores.id', '=', 'suppliers.store_id')
            ->whereRaw('LOWER(suppliers.email) = ?', [$supplierEmail])
            ->orderByDesc('suppliers.created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Search stores to request/link supplier account.
     * GET /api/supplier-portal/stores/search
     */
    public function searchStores(Request $request): JsonResponse
    {
        $user = auth()->user();
        $portal = SupplierPortal::with('supplier')->where('user_id', $user->id)->firstOrFail();
        $supplierEmail = strtolower(trim((string) ($portal->supplier?->email ?: $user->email)));

        $search = trim((string) $request->get('search', ''));
        $limit = max(1, min((int) $request->get('limit', 30), 100));

        $linkedStoreIds = [];
        if ($supplierEmail !== '') {
            $linkedStoreIds = Supplier::query()
                ->whereRaw('LOWER(email) = ?', [$supplierEmail])
                ->pluck('store_id')
                ->filter()
                ->values()
                ->all();
        }

        $query = Store::query()
            ->select(['id', 'name', 'store_code', 'city', 'province', 'address', 'status'])
            ->where('status', 'active')
            ->whereNotIn('id', $linkedStoreIds)
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('store_code', 'LIKE', "%{$search}%")
                    ->orWhere('city', 'LIKE', "%{$search}%")
                    ->orWhere('province', 'LIKE', "%{$search}%")
                    ->orWhere('address', 'LIKE', "%{$search}%");
            });
        }

        $stores = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $stores,
        ]);
    }

    /**
     * Link supplier account to selected store by creating supplier record for that store.
     * POST /api/supplier-portal/stores/link
     */
    public function linkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|integer|exists:stores,id',
        ]);

        $user = auth()->user();
        $portal = SupplierPortal::with('supplier')->where('user_id', $user->id)->firstOrFail();
        $sourceSupplier = $portal->supplier;

        $supplierEmail = strtolower(trim((string) ($sourceSupplier?->email ?: $user->email)));
        if ($supplierEmail === '') {
            return response()->json([
                'success' => false,
                'message' => 'Supplier email is required before linking a store.',
            ], 422);
        }

        $existing = Supplier::query()
            ->where('store_id', $validated['store_id'])
            ->whereRaw('LOWER(email) = ?', [$supplierEmail])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Store already linked.',
                'data' => $existing,
            ]);
        }

        $supplierName = trim((string) ($sourceSupplier?->supplier_name ?: ($portal->supplier_name ?: ($user->fname . ' ' . $user->lname))));
        $contactPerson = trim((string) ($sourceSupplier?->contact_person ?: ($portal->contact_person ?: ($user->fname . ' ' . $user->lname))));

        $created = DB::transaction(function () use ($validated, $sourceSupplier, $supplierEmail, $supplierName, $contactPerson) {
            return Supplier::create([
                'store_id' => $validated['store_id'],
                'supplier_code' => $this->generateSupplierCode(),
                'supplier_name' => $supplierName ?: 'Supplier',
                'company_name' => $sourceSupplier?->company_name ?: $supplierName,
                'contact_person' => $contactPerson ?: null,
                'email' => $supplierEmail,
                'phone' => $sourceSupplier?->phone ?? '',
                'address' => $sourceSupplier?->address,
                'city' => $sourceSupplier?->city,
                'province' => $sourceSupplier?->province,
                'postal_code' => $sourceSupplier?->postal_code,
                'country' => $sourceSupplier?->country ?: 'Philippines',
                'tin' => $sourceSupplier?->tin,
                'supplier_type' => $sourceSupplier?->supplier_type ?: 'wholesaler',
                'payment_terms' => $sourceSupplier?->payment_terms ?: 'net_30',
                'status' => 'active',
                'rating' => 5.00,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Store linked successfully.',
            'data' => $created,
        ], 201);
    }

    /**
     * Get linked store detail with transactions and active contracts.
     * GET /api/supplier-portal/stores/{storeId}
     */
    public function getLinkedStoreDetail(Request $request, int $storeId): JsonResponse
    {
        $user = auth()->user();
        $portal = SupplierPortal::with('supplier')->where('user_id', $user->id)->firstOrFail();
        $supplierEmail = strtolower(trim((string) ($portal->supplier?->email ?: $user->email)));

        if ($supplierEmail === '') {
            return response()->json([
                'success' => false,
                'message' => 'Supplier email is missing.',
            ], 422);
        }

        $linkedSupplier = Supplier::query()
            ->where('store_id', $storeId)
            ->whereRaw('LOWER(email) = ?', [$supplierEmail])
            ->first();

        if (!$linkedSupplier) {
            return response()->json([
                'success' => false,
                'message' => 'Store is not linked to your supplier account.',
            ], 404);
        }

        $store = Store::query()
            ->select(['id', 'name', 'store_code', 'phone', 'email', 'city', 'province', 'address', 'status'])
            ->findOrFail($storeId);

        $contracts = SupplierContract::query()
            ->where('store_id', $storeId)
            ->where('supplier_id', $linkedSupplier->id)
            ->orderByRaw("FIELD(status,'active','pending','draft','rejected','terminated','completed') ASC")
            ->orderByDesc('created_at')
            ->get();

        $poTransactions = SupplierPOFeedback::query()
            ->with(['purchaseOrder:id,po_number,status,total_amount,order_date,expected_delivery_date,store_id,supplier_id'])
            ->where('supplier_portal_id', $portal->id)
            ->whereHas('purchaseOrder', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->orderByDesc('submitted_at')
            ->limit(200)
            ->get();

        $rfqTransactions = SupplierRFQFeedback::query()
            ->with(['rfq:id,rfq_number,title,status,issue_date,store_id'])
            ->where('supplier_portal_id', $portal->id)
            ->whereHas('rfq', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->orderByDesc('submitted_at')
            ->limit(200)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'store' => $store,
                'supplier' => [
                    'id' => $linkedSupplier->id,
                    'supplier_name' => $linkedSupplier->supplier_name,
                    'contact_person' => $linkedSupplier->contact_person,
                    'email' => $linkedSupplier->email,
                    'phone' => $linkedSupplier->phone,
                ],
                'contracts' => $contracts,
                'po_transactions' => $poTransactions,
                'rfq_transactions' => $rfqTransactions,
                'can_create_contract' => !$contracts->contains(fn($c) => (string) $c->status === 'active'),
            ],
        ]);
    }

    /**
     * Get supplier portal details (current logged-in supplier)
     * GET /api/supplier-portal/my-portal
     */
    public function getMyPortal(Request $request): JsonResponse
    {
        $user = auth()->user();
        $portal = SupplierPortal::where('user_id', $user->id)
            ->with(['supplier', 'verificationDocuments', 'rfqFeedbacks', 'poFeedbacks'])
            ->first();

        if (!$portal) {
            return response()->json([
                'success' => false,
                'message' => 'No portal found. Please register as a supplier.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $portal,
        ]);
    }

    /**
     * Update portal coordinates (supplier profile)
     * PUT /api/supplier-portal/coordinates
     */
    public function updateCoordinates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $user = auth()->user();
        $portal = SupplierPortal::where('user_id', $user->id)->firstOrFail();

        $portal->update([
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location updated.',
            'data' => $portal,
        ]);
    }

    /**
     * Update supplier payment account (bank details)
     * PUT /api/supplier-portal/payment-account
     */
    public function updatePaymentAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:120',
            'bank_account_name' => 'required|string|max:160',
            'bank_account_number' => 'required|string|max:80',
            'bank_account_type' => 'nullable|in:savings,checking,current,other',
            'bank_branch' => 'nullable|string|max:120',
        ]);

        $user = auth()->user();
        $portal = SupplierPortal::with('supplier')->where('user_id', $user->id)->firstOrFail();
        $supplier = $portal->supplier;

        if (!$supplier) {
            return response()->json([
                'success' => false,
                'message' => 'No supplier record is linked to this portal.',
            ], 422);
        }

        $supplier->update([
            'bank_name' => $validated['bank_name'],
            'bank_account_name' => $validated['bank_account_name'],
            'bank_account_number' => $validated['bank_account_number'],
            'bank_account_type' => $validated['bank_account_type'] ?? null,
            'bank_branch' => $validated['bank_branch'] ?? null,
            'payment_account_updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment account updated.',
            'data' => [
                'supplier' => $supplier->fresh(),
            ],
        ]);
    }

    /**
     * Register as a supplier
     * POST /api/supplier-portal/register
     */
    public function register(Request $request): JsonResponse
    {
        // Accept legacy `company_name` from older frontends by mapping it to `supplier_name`
        if (!$request->has('supplier_name') && $request->has('company_name')) {
            $request->merge(['supplier_name' => $request->company_name]);
        }

        $validator = Validator::make($request->all(), [
            'supplier_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'tin' => 'nullable|string|max:50',
            'payment_terms' => 'required|in:cash_on_delivery,net_7,net_15,net_30,net_60,advance_payment',
            'supplier_type' => 'required|in:raw_materials,furniture,accessories,services',
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
            $storeId = $user->store_id ?? Store::query()->value('id');

            if (!$storeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to determine store for supplier registration.',
                ], 422);
            }

            // Check if supplier portal already exists
            $existingPortal = SupplierPortal::where('user_id', $user->id)->first();
            if ($existingPortal && $existingPortal->status === 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have an approved supplier portal.',
                ], 409);
            }

            $portal = $existingPortal ?: new SupplierPortal(['user_id' => $user->id]);

            // Create or update supplier record
            $supplierTypeMap = [
                'raw_materials' => 'manufacturer',
                'furniture' => 'wholesaler',
                'accessories' => 'distributor',
                'services' => 'local_artisan',
            ];

            $paymentTermMap = [
                'cash_on_delivery' => 'cash_on_delivery',
                'net_7' => 'net_7',
                'net_15' => 'net_15',
                'net_30' => 'net_30',
                'net_60' => 'net_60',
                'advance_payment' => 'advance_payment',
            ];

            $paymentTerm = $paymentTermMap[$request->payment_terms] ?? 'net_30';
            $supplierType = $supplierTypeMap[$request->supplier_type] ?? 'wholesaler';

            $supplier = null;
            if ($portal->supplier_id) {
                $supplier = Supplier::withTrashed()->find($portal->supplier_id);
                if ($supplier && $supplier->trashed()) {
                    $supplier->restore();
                }
            }

            if (!$supplier) {
                $supplierCode = $this->generateSupplierCode();
                $supplier = Supplier::create([
                    'store_id' => $storeId,
                    'supplier_code' => $supplierCode,
                    'supplier_name' => $request->supplier_name,
                    'company_name' => $request->supplier_name,
                    'contact_person' => $request->contact_person,
                    'email' => $user->email,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'city' => $request->city,
                    'province' => $request->province,
                    'postal_code' => $request->postal_code,
                    'country' => $request->country,
                    'tin' => $request->tin,
                    'supplier_type' => $supplierType,
                    'payment_terms' => $paymentTerm,
                    'status' => 'inactive',
                ]);
            } else {
                $supplier->update([
                    'supplier_name' => $request->supplier_name,
                    'company_name' => $request->supplier_name,
                    'contact_person' => $request->contact_person,
                    'email' => $user->email,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'city' => $request->city,
                    'province' => $request->province,
                    'postal_code' => $request->postal_code,
                    'country' => $request->country,
                    'tin' => $request->tin,
                    'supplier_type' => $supplierType,
                    'payment_terms' => $paymentTerm,
                ]);
            }

            $portal->supplier_id = $supplier->id;
            // Persist submission metadata on portal but keep contact details on `suppliers` table
            $portal->status = 'pending';
            $portal->rejection_reason = null;
            $portal->last_submission_at = now();
            $portal->resubmission_count = ($portal->resubmission_count ?? 0) + 1;
            $portal->save();

            $portal->load(['supplier', 'verificationDocuments']);

            return response()->json([
                'success' => true,
                'message' => 'Supplier portal created. Please upload verification documents.',
                'data' => $portal,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error registering supplier: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function generateSupplierCode(): string
    {
        $year = date('Y');
        $lastCode = Supplier::where('supplier_code', 'LIKE', "SUP-{$year}-%")
            ->orderBy('supplier_code', 'desc')
            ->value('supplier_code');

        if ($lastCode) {
            $parts = explode('-', $lastCode);
            $lastNumber = (int) ($parts[2] ?? 0);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('SUP-%s-%03d', $year, $nextNumber);
    }

    /**
     * Upload verification document
     * POST /api/supplier-portal/documents
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'document_type' => 'required|in:business_license,tax_id,company_registration,bank_details',
            'file' => 'required|file|max:5120', // 5MB
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

            $file = $request->file('file');
            $path = 'supplier-documents/' . $portal->id . '/' . $request->document_type;
            
            // Store file
            $filename = $file->store($path, 'private');
            
            // Create or update document record
            SupplierVerificationDocument::updateOrCreate(
                [
                    'supplier_portal_id' => $portal->id,
                    'document_type' => $request->document_type,
                ],
                [
                    'file_path' => $filename,
                    'original_filename' => $file->getClientOriginalName(),
                    'file_mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'status' => 'pending',
                ]
            );

            // Update portal submission time
            $portal->update([
                'last_submission_at' => now(),
                'resubmission_count' => $portal->resubmission_count + 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully.',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error uploading document: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get supplier's verification documents
     * GET /api/supplier-portal/my-documents
     */
    public function getMyDocuments(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $portal = SupplierPortal::where('user_id', $user->id)->firstOrFail();

            $documents = $portal->verificationDocuments()
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $documents,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No supplier portal found for current user.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching documents: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download verification document
     * GET /api/supplier-portal/documents/{id}/download
     */
    public function downloadDocument($id): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        try {
            $user = auth()->user();
            $document = SupplierVerificationDocument::findOrFail($id);

            // Verify authorization
            if ($document->supplierPortal->user_id !== $user->id) {
                abort(403);
            }

            return Storage::disk('private')->download($document->file_path, $document->original_filename);
        } catch (\Exception $e) {
            abort(404);
        }
    }

    /**
     * Get portal dashboard stats
     * GET /api/supplier-portal/stats
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $portal = SupplierPortal::where('user_id', $user->id)->firstOrFail();

            $stats = [
                'portal_status' => $portal->status,
                'is_verified' => $portal->isVerified(),
                'total_rfqs' => $portal->rfqFeedbacks()->count(),
                'total_pos' => $portal->poFeedbacks()->count(),
                'pending_pos' => $portal->poFeedbacks()->where('receipt_status', 'pending')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching stats: ' . $e->getMessage(),
            ], 500);
        }
    }
}
