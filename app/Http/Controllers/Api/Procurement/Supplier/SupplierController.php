<?php
// backend/app/Http/Controllers/Procurement/Supplier/SupplierController.php

namespace App\Http\Controllers\Api\Procurement\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use App\Models\User;
use App\Models\Core\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\SupplierInvite;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    /**
     * Directory of verified suppliers registered in supplier portal.
     * GET /api/procurement/suppliers/verified-directory
     */
    public function verifiedDirectory(Request $request): JsonResponse
    {
        $search = trim((string) $request->get('search', ''));
        $limit = (int) $request->get('limit', 100);
        $limit = max(1, min($limit, 300));

        $storeId = Auth::user()->store_id;
        $linkedSuppliers = Supplier::where('store_id', $storeId)
            ->whereNotNull('email')
            ->get(['id', 'email']);
        $linkedEmails = $linkedSuppliers
            ->pluck('email')
            ->map(fn($email) => strtolower(trim((string) $email)))
            ->filter()
            ->values()
            ->all();
        $linkedSupplierIdsByEmail = $linkedSuppliers
            ->mapWithKeys(function ($row) {
                $key = strtolower(trim((string) $row->email));
                return $key !== '' ? [$key => (int) $row->id] : [];
            })
            ->toArray();

        $query = SupplierPortal::query()
            ->where('status', 'approved')
            ->with([
                'user:id,email,fname,lname',
                'supplier:id,supplier_name,company_name,contact_person,email,phone,address,city,province,country,payment_terms,supplier_type',
            ])
            ->orderByDesc('verified_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                        $uq->where('email', 'LIKE', "%{$search}%")
                            ->orWhere('fname', 'LIKE', "%{$search}%")
                            ->orWhere('lname', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('supplier', function ($sq) use ($search) {
                        $sq->where('supplier_name', 'LIKE', "%{$search}%")
                            ->orWhere('company_name', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%")
                            ->orWhere('contact_person', 'LIKE', "%{$search}%");
                    });
            });
        }

        $rows = $query->limit($limit)->get()->map(function (SupplierPortal $portal) use ($linkedEmails, $linkedSupplierIdsByEmail) {
            return $this->mapVerifiedPortal($portal, $linkedEmails, $linkedSupplierIdsByEmail);
        })->values();

        if ($request->boolean('available_only', true)) {
            $rows = $rows->where('already_linked', false)->values();
        }

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    /**
     * Show one verified supplier from directory.
     * GET /api/procurement/suppliers/verified-directory/{portalId}
     */
    public function verifiedDirectoryShow(int $portalId): JsonResponse
    {
        $storeId = Auth::user()->store_id;
        $linkedSuppliers = Supplier::where('store_id', $storeId)
            ->whereNotNull('email')
            ->get(['id', 'email']);
        $linkedEmails = $linkedSuppliers
            ->pluck('email')
            ->map(fn($email) => strtolower(trim((string) $email)))
            ->filter()
            ->values()
            ->all();
        $linkedSupplierIdsByEmail = $linkedSuppliers
            ->mapWithKeys(function ($row) {
                $key = strtolower(trim((string) $row->email));
                return $key !== '' ? [$key => (int) $row->id] : [];
            })
            ->toArray();

        $portal = SupplierPortal::query()
            ->where('id', $portalId)
            ->where('status', 'approved')
            ->with([
                'user:id,email,fname,lname',
                'supplier',
                'verificationDocuments',
            ])
            ->first();

        if (!$portal) {
            return response()->json([
                'success' => false,
                'message' => 'Verified supplier not found.',
            ], 404);
        }

        $data = $this->mapVerifiedPortal($portal, $linkedEmails, $linkedSupplierIdsByEmail);
        $data['verification_documents'] = $portal->verificationDocuments;

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * List all suppliers
     * GET /api/procurement/suppliers
     */
    public function index(Request $request): JsonResponse
    {
        $contractStatusQuery = $this->contractStatusQuery();
        $query = Supplier::where('store_id', Auth::user()->store_id)
            ->select('suppliers.*')
            ->selectSub($contractStatusQuery, 'contract_status');

        if ($request->filled('contract_status')) {
            $status = $request->input('contract_status');
            $query->whereRaw(
                'COALESCE((' . $this->contractStatusQuery()->toSql() . "), 'no_contract') = ?",
                [...$this->contractStatusQuery()->getBindings(), $status]
            );
        }

        if ($request->boolean('active_contract_only')) {
            $query->whereHas('contracts', function ($contractQuery) {
                $contractQuery->active();
            });
        }

        $productIds = $request->input('product_ids', []);
        if (!is_array($productIds)) {
            $productIds = explode(',', (string) $productIds);
        }
        $productIds = collect($productIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        // When a PO has listed products, only return suppliers linked to every
        // product so the form cannot create a mixed-supplier PO.
        foreach ($productIds as $productId) {
            $query->whereHas('products', function ($productQuery) use ($productId) {
                $productQuery->where('products.id', $productId);
            });
        }

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('supplier_type')) {
            $query->where('supplier_type', $request->supplier_type);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('supplier_name', 'LIKE', "%{$search}%")
                    ->orWhere('supplier_code', 'LIKE', "%{$search}%")
                    ->orWhere('company_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('min_rating')) {
            $query->where('rating', '>=', $request->min_rating);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $suppliers = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $suppliers,
        ]);
    }

    /**
     * Show single supplier
     * GET /api/procurement/suppliers/{id}
     */
    public function show(int $id): JsonResponse
    {
        $supplier = Supplier::with([
            'store:id,name,store_code,city,province,address',
            'contracts.rejectedBy:id,fname,lname',
            'products',
            'purchaseOrders' => function ($query) {
                $query->latest()->limit(10);
            }
        ])->select('suppliers.*')
            ->selectSub($this->contractStatusQuery(), 'contract_status')
            ->withCount(['contracts as active_contracts_count' => fn ($query) => $query->active()])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $supplier,
        ]);
    }

    private function contractStatusQuery()
    {
        $today = now()->toDateString();

        return \Illuminate\Support\Facades\DB::table('supplier_contracts')
            ->select('status')
            ->whereColumn('supplier_contracts.supplier_id', 'suppliers.id')
            ->orderByRaw("CASE WHEN status = 'active' AND start_date <= ? AND end_date >= ? THEN 0 ELSE 1 END", [$today, $today])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(1);
    }

    /**
     * Create new supplier
     * POST /api/procurement/suppliers
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_portal_id' => 'nullable|integer|exists:supplier_portals,id',
            'supplier_name' => 'required_without:supplier_portal_id|string|max:255',
            'contact_person' => 'required_without:supplier_portal_id|string|max:255',
            'email' => 'required_without:supplier_portal_id|email|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        if (!empty($validated['supplier_portal_id'])) {
            $portal = SupplierPortal::with(['supplier', 'user'])
                ->where('id', $validated['supplier_portal_id'])
                ->where('status', 'approved')
                ->first();

            if (!$portal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected supplier is not verified or no longer available.',
                ], 422);
            }

            $sourceSupplier = $portal->supplier;
            $sourceUser = $portal->user;
            $email = $sourceSupplier?->email ?: $sourceUser?->email;
            if (!$email) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected supplier has no email record.',
                ], 422);
            }

            $existing = Supplier::where('store_id', Auth::user()->store_id)
                ->where('email', $email)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => true,
                    'message' => 'Supplier already linked to this store.',
                    'data' => $existing,
                    'linked_existing' => true,
                ]);
            }

            $supplierName = $sourceSupplier?->supplier_name
                ?: ($portal->supplier_name ?: trim(($sourceUser?->fname ?? '') . ' ' . ($sourceUser?->lname ?? '')));
            $contactPerson = $sourceSupplier?->contact_person
                ?: ($portal->contact_person ?: trim(($sourceUser?->fname ?? '') . ' ' . ($sourceUser?->lname ?? '')));

            $supplier = Supplier::create([
                'store_id' => Auth::user()->store_id,
                'supplier_code' => $this->generateSupplierCode(),
                'supplier_name' => $supplierName ?: 'Unnamed Supplier',
                'company_name' => $sourceSupplier?->company_name ?: $supplierName,
                'contact_person' => $contactPerson ?: null,
                'email' => $email,
                'phone' => $sourceSupplier?->phone ?: ($portal->phone ?? ''),
                'address' => $sourceSupplier?->address ?: $portal->address,
                'city' => $sourceSupplier?->city ?: $portal->city,
                'province' => $sourceSupplier?->province ?: $portal->province,
                'postal_code' => $sourceSupplier?->postal_code,
                'country' => $sourceSupplier?->country ?: ($portal->country ?: 'Philippines'),
                'tin' => $sourceSupplier?->tin,
                'supplier_type' => $sourceSupplier?->supplier_type ?: 'wholesaler',
                'payment_terms' => $sourceSupplier?->payment_terms ?: 'net_30',
                'status' => 'active',
                'rating' => 5.00,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Supplier linked successfully.',
                'data' => $supplier,
            ], 201);
        }

        \Log::info('[Supplier] Starting supplier creation', ['email' => $validated['email']]);

        // Generate supplier code like SUP-2026-005 (incrementing per year)
        $supplierCode = $this->generateSupplierCode();

        // Split supplier_name into first/last for supplier record
        $supplierNameParts = preg_split('/\s+/', trim($validated['supplier_name']), 2);
        $supplierFirstName = $supplierNameParts[0] ?? $validated['supplier_name'];
        $supplierLastName = $supplierNameParts[1] ?? '';

        // Split contact_person into first/last for user account
        $contactNameParts = preg_split('/\s+/', trim($validated['contact_person']), 2);
        $contactFirstName = $contactNameParts[0] ?? $validated['contact_person'];
        $contactLastName = $contactNameParts[1] ?? '';

        $data = [
            'supplier_name' => $validated['supplier_name'],
            'first_name' => $supplierFirstName,
            'last_name' => $supplierLastName,
            'contact_person' => $validated['contact_person'],
            'email' => $validated['email'],
            'store_id' => Auth::user()->store_id,
            'supplier_code' => $supplierCode,
            'status' => 'active',
            'rating' => 5.00,
            'country' => 'Philippines',
            'phone' => $validated['phone'] ?? '',
        ];

        \Log::info('[Supplier] Creating supplier record', ['supplier_code' => $supplierCode]);
        $supplier = Supplier::create($data);
        \Log::info('[Supplier] Supplier created successfully', ['supplier_id' => $supplier->id, 'supplier_code' => $supplierCode]);

        // Create a corresponding user account for the supplier's contact person
        $user = null;
        $mailError = null;

        try {
            // Determine role id for 'supplier'
            $roleId = Role::where('name', 'supplier')->value('id');

            if (!$roleId) {
                \Log::error('[Supplier] Role "supplier" not found in database');
                throw new \Exception('Supplier role not found');
            }

            \Log::info('[Supplier] Found supplier role', ['role_id' => $roleId]);

            // Use supplier code as the user's user_id


            // Generate a random password for email
            $plainPassword = Str::random(10);

            \Log::info('[Supplier] Attempting to create user account for contact person', [
                'email' => $validated['email'],
                'user_id' => User::generateUserId(),
                'fname' => $contactFirstName,
                'lname' => $contactLastName
            ]);

            $user = User::create([
                'fname' => $contactFirstName,  // Using contact person's first name
                'lname' => $contactLastName,   // Using contact person's last name
                'email' => $validated['email'],
                'password' => Hash::make($plainPassword),
                'role_id' => $roleId,
                'is_active' => true,
                'user_id' => $supplierCode,
                'store_id' => Auth::user()->store_id,
                'registered_by' => Auth::id(),
                'created_at' => now(),
                'email_verified_at' => now(),
            ]);

            if ($user && $user->id) {
                \Log::info('[Supplier] User account created successfully', [
                    'user_id' => $user->user_id,
                    'email' => $user->email,
                    'role_id' => $user->role_id,
                    'fname' => $user->fname,
                    'lname' => $user->lname
                ]);
            } else {
                \Log::error('[Supplier] User creation failed - user object is empty or missing ID');
                throw new \Exception('User creation returned invalid object');
            }

            // Send invite email with credentials
            if ($user && filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                $storeName = Auth::user()->store->store_name ?? null;
                $systemName = config('app.name');

                \Log::info('[Supplier] Attempting to send invite email', ['email' => $user->email]);

                try {
                    Mail::to($user->email)->send(new SupplierInvite($user, $plainPassword, $storeName, $systemName));
                    \Log::info('[Supplier] Invite email sent successfully', ['email' => $user->email]);
                } catch (\Throwable $mailEx) {
                    $mailError = $mailEx->getMessage();
                    \Log::error('[Supplier] Mail send failed', [
                        'email' => $user->email,
                        'error' => $mailError
                    ]);
                }
            } else {
                \Log::warning('[Supplier] Cannot send email - invalid email or user object', [
                    'email' => $validated['email'],
                    'has_user' => !is_null($user)
                ]);
            }

        } catch (\Exception $e) {
            // Log the full error details
            \Log::error('[Supplier] FAILED to create user account', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'supplier_code' => $supplierCode,
                'email' => $validated['email']
            ]);

            // Delete the supplier if user creation failed
            if ($supplier && $supplier->id) {
                \Log::warning('[Supplier] Rolling back supplier creation due to user creation failure', [
                    'supplier_id' => $supplier->id
                ]);
                $supplier->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create user account for supplier: ' . $e->getMessage()
                ], 500);
            }
        }

        $response = [
            'success' => true,
            'message' => 'Supplier created successfully',
            'data' => $supplier,
        ];

        if ($user && $user->id) {
            $response['user_created'] = true;
            $response['user_id'] = $user->id;
        } else {
            $response['user_created'] = false;
            $response['user_error'] = 'User account was not created';
        }

        if (!empty($mailError)) {
            $response['mail_error'] = $mailError;
        }

        \Log::info('[Supplier] Store method completed', [
            'supplier_id' => $supplier->id,
            'user_created' => !is_null($user),
            'mail_sent' => empty($mailError)
        ]);

        return response()->json($response, 201);
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

    private function mapVerifiedPortal(SupplierPortal $portal, array $linkedEmails = [], array $linkedSupplierIdsByEmail = []): array
    {
        $supplier = $portal->supplier;
        $user = $portal->user;

        $supplierName = $supplier?->supplier_name ?: ($portal->supplier_name ?: trim(($user?->fname ?? '') . ' ' . ($user?->lname ?? '')));
        $contactPerson = $supplier?->contact_person ?: ($portal->contact_person ?: trim(($user?->fname ?? '') . ' ' . ($user?->lname ?? '')));
        $email = $supplier?->email ?: $user?->email;
        $emailNormalized = strtolower(trim((string) $email));

        return [
            'supplier_portal_id' => $portal->id,
            'supplier_id' => $supplier?->id,
            'linked_supplier_id' => $linkedSupplierIdsByEmail[$emailNormalized] ?? null,
            'supplier_name' => $supplierName ?: 'Unnamed Supplier',
            'company_name' => $supplier?->company_name,
            'contact_person' => $contactPerson ?: null,
            'email' => $email,
            'phone' => $supplier?->phone ?: $portal->phone,
            'address' => $supplier?->address ?: $portal->address,
            'city' => $supplier?->city ?: $portal->city,
            'province' => $supplier?->province ?: $portal->province,
            'country' => $supplier?->country ?: $portal->country,
            'payment_terms' => $supplier?->payment_terms,
            'supplier_type' => $supplier?->supplier_type,
            'verified_at' => optional($portal->verified_at)->toDateTimeString(),
            'already_linked' => $emailNormalized !== '' && in_array($emailNormalized, $linkedEmails, true),
        ];
    }

    /**
     * Update supplier
     * PUT /api/procurement/suppliers/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'supplier_name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'fax' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'tin' => 'nullable|string|max:50',
            'business_registration' => 'nullable|string|max:100',
            'supplier_type' => 'nullable|in:manufacturer,wholesaler,distributor,importer,local_artisan',
            'payment_terms' => 'nullable|in:cash_on_delivery,net_7,net_15,net_30,net_60,advance_payment',
            'bank_name' => 'nullable|string|max:120',
            'bank_account_name' => 'nullable|string|max:160',
            'bank_account_number' => 'nullable|string|max:80',
            'bank_account_type' => 'nullable|in:savings,checking,current,other',
            'bank_branch' => 'nullable|string|max:120',
            'credit_limit' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,inactive,blacklisted',
            'notes' => 'nullable|string',
        ]);

        if (
            array_key_exists('bank_name', $validated) ||
            array_key_exists('bank_account_name', $validated) ||
            array_key_exists('bank_account_number', $validated) ||
            array_key_exists('bank_account_type', $validated) ||
            array_key_exists('bank_branch', $validated)
        ) {
            $validated['payment_account_updated_at'] = now();
        }

        $supplier->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Supplier updated successfully',
            'data' => $supplier,
        ]);
    }

    /**
     * Delete supplier
     * DELETE /api/procurement/suppliers/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        // Check if supplier has active purchase orders
        $activePOs = $supplier->purchaseOrders()
            ->whereIn('status', ['draft', 'pending_finance_approval', 'approved', 'sent_to_supplier', 'supplier_accepted', 'in_transit'])
            ->count();

        if ($activePOs > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete supplier with active purchase orders',
            ], 422);
        }

        $supplier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Supplier deleted successfully',
        ]);
    }

    /**
     * Attach products to supplier
     * POST /api/procurement/suppliers/{id}/products
     */
    public function attachProducts(Request $request, int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.supplier_sku' => 'nullable|string|max:100',
            'products.*.supplier_price' => 'required|numeric|min:0',
            'products.*.minimum_order_quantity' => 'nullable|integer|min:1',
            'products.*.lead_time_days' => 'nullable|integer|min:1',
            'products.*.is_preferred_supplier' => 'nullable|boolean',
        ]);

        foreach ($validated['products'] as $product) {
            $supplier->products()->syncWithoutDetaching([
                $product['product_id'] => [
                    'supplier_sku' => $product['supplier_sku'] ?? null,
                    'supplier_price' => $product['supplier_price'],
                    'minimum_order_quantity' => $product['minimum_order_quantity'] ?? 1,
                    'lead_time_days' => $product['lead_time_days'] ?? 7,
                    'is_preferred_supplier' => $product['is_preferred_supplier'] ?? false,
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Products attached to supplier successfully',
            'data' => $supplier->load('products'),
        ]);
    }

    /**
     * Get products linked to a supplier (from pivot)
     * GET /api/procurement/suppliers/{id}/products
     */
    public function products(int $id, Request $request): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);
        $branchId = $request->get('branch_id');

        $productsQuery = $supplier->products()
            ->with([
                'category:id,category_name',
                'variations' => fn ($query) => $query->active()
                    ->select([
                        'id', 'product_id', 'variation_name', 'variation_sku',
                        'cost_price', 'reorder_point', 'unit_of_measurement',
                    ]),
                'inventory' => function ($q) use ($branchId) {
                    if ($branchId) {
                        $q->where('branch_id', $branchId);
                    }
                }
            ])
            ->select(
                'products.id', 'products.product_name', 'products.sku',
                'products.category_id', 'products.cost_price', 'products.unit_of_measurement'
            );

        $products = $productsQuery->get()->flatMap(function ($product) use ($branchId) {
            if ($product->variations->isNotEmpty()) {
                return $product->variations->map(function ($variation) use ($product, $branchId) {
                    $inventory = $branchId
                        ? $product->inventory->firstWhere('variation_id', $variation->id)
                        : null;

                    return [
                        'id' => $product->id,
                        'product_name' => "{$product->product_name} — {$variation->variation_name}",
                        'sku' => $variation->variation_sku ?: $product->sku,
                        'category_id' => $product->category_id,
                        'variation_id' => $variation->id,
                        'variation' => $variation,
                        'unit_of_measurement' => $variation->unit_of_measurement ?: $product->unit_of_measurement,
                        'stock_level' => $inventory?->quantity_available ?? 0,
                        'unit_cost' => $variation->cost_price ?? $product->getRawOriginal('cost_price'),
                    ];
                });
            }

            $inventory = $branchId ? $product->inventory->first() : null;
            return [[
                'id' => $product->id,
                'product_name' => $product->product_name,
                'sku' => $product->sku,
                'category_id' => $product->category_id,
                'variation_id' => null,
                'variation' => null,
                'unit_of_measurement' => $product->unit_of_measurement,
                'stock_level' => $inventory?->quantity_available ?? 0,
                'unit_cost' => $product->getRawOriginal('cost_price'),
            ]];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Get supplier performance metrics
     * GET /api/procurement/suppliers/{id}/performance
     */
    public function performance(Request $request, int $id): JsonResponse
    {
        $supplier = Supplier::where('store_id', $request->user()->store_id)->findOrFail($id);
        $evaluations = $supplier->performanceEvaluations();
        $evaluationAverages = (clone $evaluations)->selectRaw('
            AVG(quality_score) as quality,
            AVG(quantity_accuracy_score) as quantity_accuracy,
            AVG(delivery_timeliness_score) as delivery_timeliness,
            AVG(packaging_condition_score) as packaging_condition,
            AVG(overall_rating) as overall
        ')->first();

        $recentEvaluations = (clone $evaluations)
            ->with([
                'goodsReceipt:id,grn_number,receipt_date',
                'purchaseOrder:id,po_number',
                'evaluator:id,fname,lname',
            ])
            ->latest()
            ->limit(10)
            ->get();

        $performance = [
            'rating' => $supplier->rating,
            'total_orders' => $supplier->total_orders,
            'total_amount_purchased' => $supplier->total_amount_purchased,
            'average_order_value' => $supplier->average_order_value,
            'on_time_deliveries' => $supplier->on_time_deliveries,
            'late_deliveries' => $supplier->late_deliveries,
            'on_time_delivery_rate' => $supplier->on_time_delivery_rate,
            'current_balance' => $supplier->current_balance,
            'credit_limit' => $supplier->credit_limit,
            'credit_available' => $supplier->credit_limit - $supplier->current_balance,
            'active_contracts' => $supplier->contracts()->active()->count(),
            'evaluation_count' => (clone $evaluations)->count(),
            'evaluation_averages' => [
                'quality' => $evaluationAverages?->quality !== null ? round((float) $evaluationAverages->quality, 2) : null,
                'quantity_accuracy' => $evaluationAverages?->quantity_accuracy !== null ? round((float) $evaluationAverages->quantity_accuracy, 2) : null,
                'delivery_timeliness' => $evaluationAverages?->delivery_timeliness !== null ? round((float) $evaluationAverages->delivery_timeliness, 2) : null,
                'packaging_condition' => $evaluationAverages?->packaging_condition !== null ? round((float) $evaluationAverages->packaging_condition, 2) : null,
                'overall' => $evaluationAverages?->overall !== null ? round((float) $evaluationAverages->overall, 2) : null,
            ],
            'recent_evaluations' => $recentEvaluations,
        ];

        return response()->json([
            'success' => true,
            'data' => $performance,
        ]);
    }

    /**
     * Update supplier rating
     * POST /api/procurement/suppliers/{id}/update-rating
     */
    public function updateRating(int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->updateRating();

        return response()->json([
            'success' => true,
            'message' => 'Supplier rating updated successfully',
            'data' => [
                'rating' => $supplier->rating,
                'on_time_delivery_rate' => $supplier->on_time_delivery_rate,
            ],
        ]);
    }

    /**
     * Manage supplier contacts
     * GET /api/procurement/suppliers/{id}/contacts
     */
    public function getContacts(int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);
        $contacts = $supplier->contacts()->orderBy('is_primary', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $contacts,
        ]);
    }

    /**
     * Add supplier contact
     * POST /api/procurement/suppliers/{id}/contacts
     */
    public function addContact(Request $request, int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'contact_name' => 'required|string|max:255',
            'contact_title' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'fax' => 'nullable|string|max:50',
            'contact_type' => 'required|in:Sales,Technical,Support,Billing,Logistics',
            'preferred_contact_method' => 'nullable|in:Email,Phone,Mobile,WhatsApp,Fax',
            'is_primary' => 'nullable|boolean',
            'is_emergency_contact' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $contact = $supplier->contacts()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Contact added successfully',
            'data' => $contact,
        ], 201);
    }

    /**
     * Update supplier contact
     * PUT /api/procurement/suppliers/{id}/contacts/{contactId}
     */
    public function updateContact(Request $request, int $id, int $contactId): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);
        $contact = $supplier->contacts()->findOrFail($contactId);

        $validated = $request->validate([
            'contact_name' => 'nullable|string|max:255',
            'contact_title' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'fax' => 'nullable|string|max:50',
            'contact_type' => 'nullable|in:Sales,Technical,Support,Billing,Logistics',
            'preferred_contact_method' => 'nullable|in:Email,Phone,Mobile,WhatsApp,Fax',
            'is_primary' => 'nullable|boolean',
            'is_emergency_contact' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $contact->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Contact updated successfully',
            'data' => $contact,
        ]);
    }

    /**
     * Delete supplier contact
     * DELETE /api/procurement/suppliers/{id}/contacts/{contactId}
     */
    public function deleteContact(int $id, int $contactId): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);
        $contact = $supplier->contacts()->findOrFail($contactId);
        $contact->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contact deleted successfully',
        ]);
    }

    /**
     * Get supplier pricing history
     * GET /api/procurement/suppliers/{id}/pricing-history
     */
    public function getPricingHistory(Request $request, int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        $query = $supplier->priceHistory();

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $prices = $query->with('product:id,product_name,sku')
            ->orderBy('effective_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $prices,
        ]);
    }

    /**
     * Update supplier pricing
     * POST /api/procurement/suppliers/{id}/update-price
     */
    public function updatePrice(Request $request, int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'unit_price' => 'required|numeric|min:0',
            'minimum_order_quantity' => 'nullable|integer|min:1',
            'lead_time_days' => 'nullable|integer|min:1',
            'pack_size' => 'nullable|integer|min:1',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:effective_date',
        ]);

        // Create new price record (deactivate old one if exists)
        \App\Models\Procurement\Supplier\SupplierPrice::where('supplier_id', $id)
            ->where('product_id', $validated['product_id'])
            ->active()
            ->update(['is_active' => false]);

        $price = \App\Models\Procurement\Supplier\SupplierPrice::create([
            'supplier_id' => $id,
            'product_id' => $validated['product_id'],
            'unit_price' => $validated['unit_price'],
            'minimum_order_quantity' => $validated['minimum_order_quantity'] ?? 1,
            'lead_time_days' => $validated['lead_time_days'] ?? 7,
            'pack_size' => $validated['pack_size'] ?? 1,
            'effective_date' => $validated['effective_date'] ?? now(),
            'expiry_date' => $validated['expiry_date'],
            'is_active' => true,
            'currency' => 'PHP',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier price updated successfully',
            'data' => $price,
        ], 201);
    }

    /**
     * Blacklist supplier
     * POST /api/procurement/suppliers/{id}/blacklist
     */
    public function blacklist(Request $request, int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|in:quality_issues,late_delivery,non_payment,other',
            'notes' => 'nullable|string',
        ]);

        $supplier->update([
            'status' => 'blacklisted',
        ]);

        // Log the blacklist action
        \Illuminate\Support\Facades\Log::info("Supplier blacklisted: {$supplier->supplier_name}", [
            'supplier_id' => $id,
            'reason' => $validated['reason'],
            'notes' => $validated['notes'],
            'blacklisted_by' => Auth::id(),
            'blacklisted_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier blacklisted successfully',
            'data' => $supplier,
        ]);
    }

    /**
     * Activate blacklisted supplier
     * POST /api/procurement/suppliers/{id}/activate
     */
    public function activate(int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        if ($supplier->status !== 'blacklisted') {
            return response()->json([
                'success' => false,
                'message' => 'Supplier is not blacklisted',
            ], 422);
        }

        $supplier->update(['status' => 'active']);

        return response()->json([
            'success' => true,
            'message' => 'Supplier activated successfully',
            'data' => $supplier,
        ]);
    }
}
