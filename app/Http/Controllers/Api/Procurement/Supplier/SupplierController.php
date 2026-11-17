<?php
// backend/app/Http/Controllers/Procurement/Supplier/SupplierController.php

namespace App\Http\Controllers\Api\Procurement\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use App\Models\ProductCatalog\Product;
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
                'supplier:id,supplier_name,company_name,contact_person,email,phone,address,city,province,country,payment_terms,supplier_type,logo_path',
                'supplier.products:id,product_name,sku',
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

        $portals = $query->limit($limit)->get();
        $ratingsByPortal = $this->ratingsFromOtherStores($portals, (int) $storeId);
        $rows = $portals->map(function (SupplierPortal $portal) use ($linkedEmails, $linkedSupplierIdsByEmail, $ratingsByPortal) {
            return $this->mapVerifiedPortal($portal, $linkedEmails, $linkedSupplierIdsByEmail, $ratingsByPortal);
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
                'supplier.products' => fn ($query) => $query->with([
                    'category:id,category_name',
                    'subcategory:id,category_name',
                    'assets' => fn ($assetQuery) => $assetQuery
                        ->whereNull('variation_id')
                        ->whereIn('asset_type', ['Image_Main', 'Image_Gallery', 'Image_360'])
                        ->orderByDesc('is_primary')
                        ->orderBy('display_order'),
                    'variations' => fn ($variationQuery) => $variationQuery
                        ->orderBy('id')
                        ->with(['assets' => fn ($assetQuery) => $assetQuery
                            ->whereIn('asset_type', ['Image_Main', 'Image_Gallery', 'Image_360'])
                            ->orderByDesc('is_primary')
                            ->orderBy('display_order')]),
                ]),
            ])
            ->first();

        if (!$portal) {
            return response()->json([
                'success' => false,
                'message' => 'Verified supplier not found.',
            ], 404);
        }

        $ratingsByPortal = $this->ratingsFromOtherStores(collect([$portal]), (int) $storeId);
        $data = $this->mapVerifiedPortal($portal, $linkedEmails, $linkedSupplierIdsByEmail, $ratingsByPortal, true);
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
        $supplier = Supplier::where('store_id', Auth::user()->store_id)->with([
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
            'company_name' => 'nullable|string|max:255',
            'contact_person' => ['required_without:supplier_portal_id', 'string', 'max:255', 'not_regex:/\d/'],
            'email' => 'required_without:supplier_portal_id|email|max:255',
            'phone' => ['required_without:supplier_portal_id', 'string', 'max:50', 'regex:/^09\d{2} ?\d{3} ?\d{4}$/'],
            'supplier_type' => 'required_without:supplier_portal_id|in:manufacturer,wholesaler,distributor,importer,local_artisan',
            'address' => 'required_without:supplier_portal_id|string|max:500',
            'province' => 'required_without:supplier_portal_id|string|max:120',
            'city' => 'required_without:supplier_portal_id|string|max:120',
            'barangay' => 'required_without:supplier_portal_id|string|max:120',
            'latitude' => 'required_without:supplier_portal_id|numeric|between:-90,90',
            'longitude' => 'required_without:supplier_portal_id|numeric|between:-180,180',
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
                if ($existing->supplier_portal_id && (int) $existing->supplier_portal_id !== (int) $portal->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This supplier record is already linked to another supplier portal account.',
                    ], 409);
                }

                if (!$existing->supplier_portal_id || $existing->is_external) {
                    $existing->supplier_portal_id = $portal->id;
                    $existing->is_external = false;
                    $existing->save();
                }

                $this->syncPortalProductsToStore($portal, $existing);

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
                'supplier_portal_id' => $portal->id,
                'supplier_code' => $this->generateSupplierCode(),
                'supplier_name' => $supplierName ?: 'Unnamed Supplier',
                'company_name' => $sourceSupplier?->company_name ?: $supplierName,
                'contact_person' => $contactPerson ?: null,
                'email' => $email,
                'phone' => $sourceSupplier?->phone ?: ($portal->phone ?? ''),
                'logo_path' => $sourceSupplier?->logo_path,
                'address' => $sourceSupplier?->address ?: $portal->address,
                'city' => $sourceSupplier?->city ?: $portal->city,
                'province' => $sourceSupplier?->province ?: $portal->province,
                'postal_code' => $sourceSupplier?->postal_code,
                'country' => $sourceSupplier?->country ?: ($portal->country ?: 'Philippines'),
                'tin' => $sourceSupplier?->tin,
                'supplier_type' => $sourceSupplier?->supplier_type ?: 'wholesaler',
                'payment_terms' => $sourceSupplier?->payment_terms ?: 'net_30',
                'status' => 'active',
                'is_external' => false,
                'rating' => 5.00,
            ]);

            $this->syncPortalProductsToStore($portal, $supplier);

            return response()->json([
                'success' => true,
                'message' => 'Supplier linked successfully.',
                'data' => $supplier,
            ], 201);
        }

        $storeId = (int) Auth::user()->store_id;
        $supplier = Supplier::create([
            'store_id' => $storeId,
            'supplier_portal_id' => null,
            'is_external' => true,
            'supplier_code' => $this->generateSupplierCode(),
            'supplier_name' => trim($validated['supplier_name']),
            'company_name' => $validated['company_name'] ?? null,
            'contact_person' => trim($validated['contact_person']),
            'email' => strtolower(trim($validated['email'])),
            'phone' => $validated['phone'] ?? '',
            'supplier_type' => $validated['supplier_type'],
            'address' => trim($validated['address']),
            'province' => trim($validated['province']),
            'city' => trim($validated['city']),
            'barangay' => trim($validated['barangay']),
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'country' => 'Philippines',
            'status' => 'active',
            'rating' => 5.00,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'External supplier recorded. No supplier portal account was created.',
            'data' => $supplier,
            'user_created' => false,
        ], 201);
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

    private function syncPortalProductsToStore(SupplierPortal $portal, Supplier $targetSupplier): void
    {
        $sourceSupplier = Supplier::query()
            ->whereKey($portal->supplier_id)
            ->with(['products.variations'])
            ->first();

        if (!$sourceSupplier) {
            return;
        }

        DB::transaction(function () use ($sourceSupplier, $targetSupplier): void {
            foreach ($sourceSupplier->products as $sourceProduct) {
                $existingOffer = DB::table('supplier_products')
                    ->where('supplier_id', $targetSupplier->id)
                    ->where(function ($query) use ($sourceProduct, $targetSupplier): void {
                        $query->where('source_product_id', $sourceProduct->id)
                            ->orWhere(function ($legacy) use ($sourceProduct, $targetSupplier): void {
                                $legacy->whereNull('source_product_id')
                                    ->whereIn('product_id', Product::query()
                                        ->where('store_id', $targetSupplier->store_id)
                                        ->where('sku', $sourceProduct->sku)
                                        ->select('id'));
                            });
                    })
                    ->first();

                if ($existingOffer) {
                    $linkedProduct = $existingOffer->product_id
                        ? Product::query()->where('store_id', $targetSupplier->store_id)->with('variations')->find($existingOffer->product_id)
                        : null;
                    $keepMatch = $linkedProduct && $this->supplierProductsHaveMatchingVariants($sourceProduct, $linkedProduct);
                    DB::table('supplier_products')->where('id', $existingOffer->id)->update([
                        'source_product_id' => $sourceProduct->id,
                        'product_id' => $keepMatch ? $linkedProduct->id : null,
                        'updated_at' => now(),
                    ]);
                    continue;
                }

                $matchedProduct = Product::query()
                    ->where('store_id', $targetSupplier->store_id)
                    ->where('sku', $sourceProduct->sku)
                    ->with('variations')
                    ->first();

                if ($matchedProduct && !$this->supplierProductsHaveMatchingVariants($sourceProduct, $matchedProduct)) {
                    $matchedProduct = null;
                }
                if ($matchedProduct && DB::table('supplier_products')->where('supplier_id', $targetSupplier->id)->where('product_id', $matchedProduct->id)->exists()) {
                    $matchedProduct = null;
                }

                DB::table('supplier_products')->insert([
                    'supplier_id' => $targetSupplier->id,
                    'product_id' => $matchedProduct?->id,
                    'source_product_id' => $sourceProduct->id,
                    'supplier_sku' => $sourceProduct->pivot->supplier_sku,
                    'supplier_price' => $sourceProduct->pivot->supplier_price,
                    'minimum_order_quantity' => $sourceProduct->pivot->minimum_order_quantity,
                    'lead_time_days' => $sourceProduct->pivot->lead_time_days,
                    'availability_status' => $sourceProduct->pivot->availability_status ?? 'available',
                    'is_preferred_supplier' => $sourceProduct->pivot->is_preferred_supplier ?? false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    private function supplierProductsHaveMatchingVariants(Product $source, Product $target): bool
    {
        $sourceVariations = $source->relationLoaded('variations') ? $source->variations : $source->variations()->get();
        $targetVariations = $target->relationLoaded('variations') ? $target->variations : $target->variations()->get();
        if ($sourceVariations->count() !== $targetVariations->count()) {
            return false;
        }

        $unmatchedTargets = $targetVariations->values();
        foreach ($sourceVariations as $sourceVariation) {
            $targetIndex = $unmatchedTargets->search(fn ($targetVariation) => $this->supplierVariationMatches($sourceVariation, $targetVariation));
            if ($targetIndex === false) {
                return false;
            }
            $unmatchedTargets->forget($targetIndex);
        }

        return $unmatchedTargets->isEmpty();
    }

    private function supplierVariationMatches($source, $target): bool
    {
        $sourceSku = strtolower(trim((string) $source->variation_sku));
        $targetSku = strtolower(trim((string) $target->variation_sku));
        if ($sourceSku !== '' && $targetSku !== '' && $sourceSku === $targetSku) {
            return true;
        }

        $key = static function ($variation): string {
            $attributes = collect([$variation->color, $variation->size, $variation->material, $variation->texture, $variation->finish])
                ->map(fn ($value) => strtolower(trim((string) $value)))
                ->filter()
                ->values();
            return $attributes->isNotEmpty()
                ? 'attributes:' . $attributes->implode('|')
                : 'name:' . strtolower(trim((string) $variation->variation_name));
        };

        return $key($source) !== 'name:' && $key($source) === $key($target);
    }

    private function ratingsFromOtherStores($portals, int $storeId): array
    {
        $portalSupplierIds = $portals->mapWithKeys(fn (SupplierPortal $portal) => [
            (int) $portal->supplier_id => (int) $portal->id,
        ])->filter(fn ($portalId, $supplierId) => $supplierId > 0);
        $portalIds = $portals->pluck('id')->map(fn ($id) => (int) $id)->all();
        $sourceSupplierIds = $portalSupplierIds->keys()->all();

        $suppliers = Supplier::query()
            ->where(function ($query) use ($portalIds, $sourceSupplierIds) {
                $query->whereIn('supplier_portal_id', $portalIds);
                if ($sourceSupplierIds) $query->orWhereIn('id', $sourceSupplierIds);
            })
            ->get(['id', 'supplier_portal_id']);

        $portalBySupplierId = [];
        foreach ($suppliers as $supplier) {
            $portalId = (int) ($supplier->supplier_portal_id ?: ($portalSupplierIds[(int) $supplier->id] ?? 0));
            if ($portalId > 0) $portalBySupplierId[(int) $supplier->id] = $portalId;
        }

        if (!$portalBySupplierId) return [];

        $ratingRows = DB::table('supplier_ratings as ratings')
            ->join('suppliers as rated_supplier', 'rated_supplier.id', '=', 'ratings.supplier_id')
            ->whereIn('ratings.supplier_id', array_keys($portalBySupplierId))
            ->where('rated_supplier.store_id', '<>', $storeId)
            ->select('ratings.supplier_id', DB::raw('AVG(ratings.rating) as average_rating'), DB::raw('COUNT(*) as rating_count'))
            ->groupBy('ratings.supplier_id')
            ->get();

        $summaries = [];
        foreach ($ratingRows as $row) {
            $portalId = $portalBySupplierId[(int) $row->supplier_id] ?? null;
            if (!$portalId) continue;
            $summaries[$portalId]['weighted_total'] = ($summaries[$portalId]['weighted_total'] ?? 0)
                + ((float) $row->average_rating * (int) $row->rating_count);
            $summaries[$portalId]['rating_count'] = ($summaries[$portalId]['rating_count'] ?? 0) + (int) $row->rating_count;
        }

        foreach ($summaries as &$summary) {
            $summary['rating'] = round($summary['weighted_total'] / max(1, $summary['rating_count']), 2);
            unset($summary['weighted_total']);
        }

        return $summaries;
    }

    private function mapVerifiedPortal(SupplierPortal $portal, array $linkedEmails = [], array $linkedSupplierIdsByEmail = [], array $ratingsByPortal = [], bool $includeProductDetails = false): array
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
            'logo_url' => $supplier?->logo_url,
            'rating' => $ratingsByPortal[(int) $portal->id]['rating'] ?? null,
            'rating_count' => $ratingsByPortal[(int) $portal->id]['rating_count'] ?? 0,
            'products' => $supplier?->products?->map(fn ($product) => $this->mapVerifiedSupplierProduct($product, $supplier, $includeProductDetails))->unique('id')->values()->all() ?? [],
            'verified_at' => optional($portal->verified_at)->toDateTimeString(),
            'already_linked' => $emailNormalized !== '' && in_array($emailNormalized, $linkedEmails, true),
        ];
    }

    private function mapVerifiedSupplierProduct($product, Supplier $supplier, bool $includeDetails): array
    {
        $baseCost = round((float) ($product->pivot?->supplier_price ?? 0), 2);
        $taxRate = $supplier->is_tax_exempt ? 0.0 : (float) ($supplier->default_tax_rate ?? 12);
        $taxAmount = round($baseCost * $taxRate / 100, 2);
        $productData = [
            'id' => (int) $product->id,
            'name' => $product->product_name,
            'product_name' => $product->product_name,
            'sku' => $product->sku,
            'supplier_sku' => $product->pivot?->supplier_sku ?: $product->sku,
        ];

        if (!$includeDetails) {
            return $productData;
        }

        $dimensions = fn ($item) => [
            'length_cm' => $item->length_cm,
            'width_cm' => $item->width_cm,
            'height_cm' => $item->height_cm,
            'depth_cm' => $item->depth_cm,
            'weight_kg' => $item->weight_kg,
            'additional_measurements' => $item->additional_measurements ?: [],
        ];
        $images = fn ($assets, string $fallback) => $assets->map(fn ($asset) => [
            'url' => $asset->thumbnail_url,
            'alt_text' => $asset->alt_text ?: $fallback,
        ])->values()->all();

        return array_merge($productData, [
            'description' => $product->description,
            'product_type' => $product->product_type,
            'category_name' => $product->category?->category_name,
            'subcategory_name' => $product->subcategory?->category_name,
            'unit_of_measurement' => $product->unit_of_measurement,
            'is_active' => (bool) $product->is_active,
            'availability_status' => $product->pivot?->availability_status ?: 'available',
            'minimum_order_quantity' => (int) ($product->pivot?->minimum_order_quantity ?? 1),
            'lead_time_days' => (int) ($product->pivot?->lead_time_days ?? 7),
            'is_preferred_supplier' => (bool) ($product->pivot?->is_preferred_supplier ?? false),
            'base_cost' => $baseCost,
            'tax_rate' => round($taxRate, 2),
            'tax_amount' => $taxAmount,
            'cost_including_tax' => round($baseCost + $taxAmount, 2),
            'dimensions' => $dimensions($product),
            'variation_attributes' => $product->variation_attributes ?: [],
            'images' => $images($product->assets ?? collect(), $product->product_name),
            'variants' => $product->variations->map(function ($variation) use ($product, $baseCost, $taxRate, $dimensions, $images) {
                $variantBaseCost = $variation->cost_price !== null ? round((float) $variation->cost_price, 2) : $baseCost;
                $variantTax = round($variantBaseCost * $taxRate / 100, 2);
                return [
                    'id' => (int) $variation->id,
                    'name' => $variation->variation_name,
                    'sku' => $variation->variation_sku,
                    'color' => $variation->color,
                    'color_hex' => $variation->color_hex,
                    'size' => $variation->size,
                    'material' => $variation->material,
                    'texture' => $variation->texture,
                    'finish' => $variation->finish,
                    'unit_of_measurement' => $variation->unit_of_measurement ?: $product->unit_of_measurement,
                    'is_active' => (bool) $variation->is_active,
                    'base_cost' => $variantBaseCost,
                    'tax_amount' => $variantTax,
                    'cost_including_tax' => round($variantBaseCost + $variantTax, 2),
                    'dimensions' => $dimensions($variation),
                    'images' => $images($variation->assets ?? collect(), $variation->variation_name ?: $product->product_name),
                ];
            })->values()->all(),
        ]);
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
        $supplier = Supplier::query()->where('store_id', $request->user()->store_id)->findOrFail($id);
        $storeId = (int) $supplier->store_id;

        $validated = $request->validate([
            'products' => 'required|array|min:1',
            'products.*.product_id' => "required|exists:products,id,store_id,{$storeId}",
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
        $supplier = Supplier::where('store_id', $request->user()->store_id)->findOrFail($id);
        $branchId = $request->get('branch_id');

        $productsQuery = $supplier->products()
            ->with([
                'category:id,category_name',
                'variations' => fn ($query) => $query->active(),
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

    public function availableProducts(int $id, Request $request): JsonResponse
    {
        $supplier = Supplier::query()->where('store_id', $request->user()->store_id)->findOrFail($id);
        if ($supplier->supplier_portal_id) {
            $portal = SupplierPortal::query()->find($supplier->supplier_portal_id);
            if ($portal) {
                $this->syncPortalProductsToStore($portal, $supplier);
            }
        }

        $offers = DB::table('supplier_products')->where('supplier_id', $supplier->id)->orderBy('id')->get();
        $approved = DB::table('supplier_rfq_feedbacks as feedback')
            ->join('rfq_items as item', 'item.id', '=', 'feedback.rfq_item_id')
            ->join('request_for_quotations as rfq', 'rfq.id', '=', 'feedback.rfq_id')
            ->where('rfq.store_id', $supplier->store_id)->whereNull('rfq.deleted_at')
            ->where('feedback.status', 'approved')
            ->where(function ($query) use ($supplier): void {
                $query->where('feedback.supplier_id', $supplier->id);
                if ($supplier->supplier_portal_id) $query->orWhere('feedback.supplier_portal_id', $supplier->supplier_portal_id);
            })->get(['item.product_id', 'item.variation_id'])
            ->mapWithKeys(fn ($row) => [(int) $row->product_id . ':' . (int) ($row->variation_id ?? 0) => true]);

        $taxRate = $supplier->is_tax_exempt ? 0.0 : (float) ($supplier->default_tax_rate ?? 12);
        $rows = collect();
        foreach ($offers as $offer) {
            $matched = $offer->product_id
                ? Product::query()->where('store_id', $supplier->store_id)->with(['category:id,category_name', 'subcategory:id,category_name', 'variations' => fn ($q) => $q->where('is_active', true), 'assets'])->find($offer->product_id)
                : null;
            $source = $offer->source_product_id
                ? Product::query()->with(['category:id,category_name', 'subcategory:id,category_name', 'variations' => fn ($q) => $q->where('is_active', true), 'assets'])->find($offer->source_product_id)
                : null;
            $product = $matched ?: $source;
            if (!$product) continue;

            $isMatched = $matched !== null;
            $productId = (int) ($matched?->id ?? $source?->id ?? $product->id);
            $availability = $offer->availability_status ?? 'available';
            $baseCost = round((float) $offer->supplier_price, 2);
            $common = [
                'product_id' => $productId,
                'matched_product_id' => $matched?->id,
                'source_product_id' => $source?->id,
                'offer_id' => (int) $offer->id,
                'supplier_sku' => $offer->supplier_sku,
                'match_status' => $isMatched ? 'matched' : 'needs_match',
                'category_id' => $product->category_id,
                'category_name' => $product->category?->category_name,
                'subcategory_name' => $product->subcategory?->category_name,
                'product_type' => $product->product_type,
                'unit_of_measurement' => $product->unit_of_measurement,
                'availability_status' => $availability,
                'minimum_order_quantity' => (int) ($offer->minimum_order_quantity ?? 1),
                'lead_time_days' => (int) ($offer->lead_time_days ?? 7),
                'tax_rate' => round($taxRate, 2),
                'is_tax_exempt' => (bool) $supplier->is_tax_exempt,
                'is_active' => (bool) $product->is_active && !in_array($availability, ['unavailable', 'out_of_stock', 'inactive'], true),
                'image_url' => $product->assets->whereIn('asset_type', ['Image_Main', 'Image_Gallery', 'Image_360'])->sortByDesc('is_primary')->first()?->thumbnail_url,
            ];
            $variations = $product->variations;
            if ($variations->isNotEmpty()) {
                foreach ($variations as $variation) {
                    $targetVariation = $isMatched
                        ? $matched->variations->first(fn ($candidate) => $this->supplierVariationMatches($variation, $candidate))
                        : null;
                    $variationId = $targetVariation?->id ?? (!$isMatched ? $variation->id : null);
                    $supplierVariation = $source?->variations->first(fn ($candidate) => $this->supplierVariationMatches($candidate, $variation));
                    $costSource = $supplierVariation ?? $variation;
                    $cost = $costSource->cost_price !== null ? round((float) $costSource->cost_price, 2) : $baseCost;
                    $tax = round($cost * ($taxRate / 100), 2);
                    $rows->push(array_merge($common, [
                        'id' => (int) ($variationId ?? $variation->id), 'variation_id' => $variationId ? (int) $variationId : null,
                        'product_name' => $product->product_name . ' — ' . $variation->variation_name,
                        'variation_name' => $variation->variation_name, 'variation_sku' => $variation->variation_sku,
                        'is_active' => $common['is_active'] && (bool) $variation->is_active && (!$isMatched || $variationId !== null),
                        'is_acquired' => $isMatched && $variationId ? $approved->has($productId . ':' . (int) $variationId) : false,
                        'base_cost' => $cost, 'tax_amount' => $tax, 'cost_including_tax' => round($cost + $tax, 2),
                    ]));
                }
                continue;
            }
            $tax = round($baseCost * ($taxRate / 100), 2);
            $rows->push(array_merge($common, [
                'id' => $productId, 'variation_id' => null, 'product_name' => $product->product_name,
                'variation_name' => null, 'variation_sku' => null,
                'is_acquired' => $isMatched && $approved->has($productId . ':0'),
                'base_cost' => $baseCost, 'tax_amount' => $tax, 'cost_including_tax' => round($baseCost + $tax, 2),
            ]));
        }

        return response()->json([
            'success' => true,
            'data' => [
                'supplier' => ['id' => (int) $supplier->id, 'supplier_name' => $supplier->supplier_name, 'contract_status' => $supplier->hasActiveContract() ? 'active' : 'inactive'],
                'products' => $rows->values(),
            ],
        ]);
    }

    public function matchSupplierProduct(Request $request, int $id, int $offerId): JsonResponse
    {
        $supplier = Supplier::query()->where('store_id', $request->user()->store_id)->findOrFail($id);
        $validated = $request->validate(['product_id' => 'required|integer']);
        $offer = DB::table('supplier_products')->where('supplier_id', $supplier->id)->where('id', $offerId)->first();
        abort_unless($offer && $offer->source_product_id, 404);

        $portalSupplierId = $supplier->supplier_portal_id
            ? SupplierPortal::query()->whereKey($supplier->supplier_portal_id)->value('supplier_id')
            : null;
        abort_unless($portalSupplierId && DB::table('supplier_products')->where('supplier_id', $portalSupplierId)->where('product_id', $offer->source_product_id)->exists(), 404);

        $source = Product::query()->with('variations')->findOrFail($offer->source_product_id);
        $target = Product::query()->where('store_id', $supplier->store_id)->where('is_active', true)->with('variations')->findOrFail($validated['product_id']);
        if (!$this->supplierProductsHaveMatchingVariants($source, $target)) {
            return response()->json(['success' => false, 'message' => 'Match products only when both have the same variation SKUs or attributes.', 'errors' => ['product_id' => ['The selected store product has different variations.']]], 422);
        }
        if (DB::table('supplier_products')->where('supplier_id', $supplier->id)->where('product_id', $target->id)->where('id', '!=', $offerId)->exists()) {
            return response()->json(['success' => false, 'message' => 'This store product is already linked to another offer from this supplier.'], 422);
        }

        DB::table('supplier_products')->where('id', $offerId)->update(['product_id' => $target->id, 'updated_at' => now()]);
        return response()->json(['success' => true, 'message' => 'Supplier offer matched to the store product.']);
    }

    public function matchableProducts(Request $request, int $id): JsonResponse
    {
        $supplier = Supplier::query()->where('store_id', $request->user()->store_id)->findOrFail($id);
        $search = trim((string) $request->query('search', ''));
        $products = Product::query()->where('store_id', $supplier->store_id)->where('is_active', true)
            ->when($search !== '', fn ($query) => $query->where(fn ($nested) => $nested->where('product_name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")))
            ->with(['variations' => fn ($query) => $query->where('is_active', true)->orderBy('id')])
            ->orderBy('product_name')->limit(50)->get(['id', 'product_name', 'sku'])
            ->map(fn (Product $product) => [
                'id' => (int) $product->id, 'product_name' => $product->product_name, 'sku' => $product->sku,
                'variations' => $product->variations->map(fn ($variation) => ['variation_sku' => $variation->variation_sku, 'variation_name' => $variation->variation_name])->values(),
            ]);
        return response()->json(['success' => true, 'data' => $products]);
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
