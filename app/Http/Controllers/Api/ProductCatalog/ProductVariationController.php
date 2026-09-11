<?php
// app/Http/Controllers/Api/ProductCatalog/ProductVariationController.php

namespace App\Http\Controllers\Api\ProductCatalog;

use App\Models\Inventory\BranchInventory;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductAsset;
use App\Models\ProductCatalog\ProductVariation;
use App\Models\Procurement\SupplierPortal\SupplierRFQFeedback;
use App\Models\Store\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProductVariationController extends BaseController
{
    public function requests(Request $request)
    {
        $requests = SupplierRFQFeedback::query()
            ->with(['rfqItem.product:id,product_name,sku,store_id', 'supplierPortal.supplier:id,supplier_name'])
            ->whereHas('rfqItem.product', fn ($query) => $query->where('store_id', $this->getStoreId()))
            ->where('has_variant', true)
            ->where('status', 'approved')
            ->where('merchandising_status', 'pending')
            ->latest('reviewed_at')
            ->get()
            ->map(function (SupplierRFQFeedback $feedback) {
                $data = $feedback->toArray();
                $data['image_urls'] = collect($feedback->variant_image_paths ?? [])->map(fn ($path) => asset('storage/' . ltrim($path, '/')))->values();
                return $data;
            });

        return $this->successResponse($requests, 'Variant requests retrieved successfully');
    }

    /**
     * Display a listing of variations.
     */
    public function index(Request $request)
    {
        try {
            $query = ProductVariation::byStore($this->getStoreId())
                                    ->with('product:id,product_name,sku,base_price');

            // Filter by product
            if ($request->has('product_id')) {
                $query->where('product_id', $request->product_id);
            }

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Filter by in stock
            if ($request->boolean('in_stock_only')) {
                $query->inStock();
            }

            // Search
            if ($request->has('search')) {
                $query->where(function($q) use ($request) {
                    $q->where('variation_name', 'like', '%' . $request->search . '%')
                      ->orWhere('variation_sku', 'like', '%' . $request->search . '%')
                      ->orWhere('color', 'like', '%' . $request->search . '%')
                      ->orWhere('size', 'like', '%' . $request->search . '%')
                      ->orWhere('material', 'like', '%' . $request->search . '%');
                });
            }

            $variations = $query->orderBy('created_at', 'desc')
                               ->paginate($request->get('per_page', 15));

            return $this->successResponse($variations, 'Variations retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve variations', [
                'store_id' => $this->getStoreId(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to retrieve variations',
                500,
                [],
                $e
            );
        }
    }

    /**
     * Store a newly created variation.
     */
    public function store(Request $request)
    {
        try {
            $validated = $this->validateRequest($request, [
                'product_id' => 'required|exists:products,id',
                'variation_sku' => 'required|string|max:50',
                'variation_name' => 'required|string|max:200',
                'color' => 'nullable|string|max:50',
                'color_hex' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
                'size' => 'nullable|string|max:50',
                'material' => 'nullable|string|max:100',
                'texture' => 'nullable|string|max:100',
                'finish' => 'nullable|string|max:100',
                'cost_price' => 'nullable|numeric|min:0',
                'reorder_point' => 'nullable|integer|min:0',
                'unit_of_measurement' => 'nullable|string|max:50',
                'initial_stock' => 'nullable|integer|min:0',
                'custom_3d_model_id' => 'nullable|exists:product_assets,id',
                'custom_image_id' => 'nullable|exists:product_assets,id',
                'length_cm' => 'nullable|numeric|min:0',
                'width_cm' => 'nullable|numeric|min:0',
                'height_cm' => 'nullable|numeric|min:0',
                'weight_kg' => 'nullable|numeric|min:0',
                'is_active' => 'boolean'
                ,'proposal_id' => 'nullable|integer|exists:supplier_rfq_feedbacks,id'
            ]);

            $routeProductId = (int) ($request->route('id') ?? 0);
            if ($routeProductId > 0 && $routeProductId !== (int) $validated['product_id']) {
                return $this->errorResponse('The selected product does not match the inventory product route.', 422);
            }

            // Verify product belongs to this store
            $product = Product::byStore($this->getStoreId())->find($validated['product_id']);
            
            if (!$product) {
                return $this->errorResponse('Product not found or does not belong to this store', 404);
            }

            if (!$product->variations()->exists() && strcasecmp($validated['variation_sku'], (string) $product->sku) === 0) {
                $validated['variation_sku'] = $product->sku . '-V2';
            }

            DB::beginTransaction();

            try {
                if (!empty($validated['custom_3d_model_id'])) {
                    $modelAsset = ProductAsset::byStore($this->getStoreId())
                        ->where('id', $validated['custom_3d_model_id'])
                        ->where('product_id', $product->id)
                        ->where('asset_type', '3D_Model')
                        ->first();

                    if (!$modelAsset) {
                        DB::rollBack();
                        return $this->errorResponse('Selected 3D model is invalid for this product/store', 422);
                    }
                }

                if (!empty($validated['custom_image_id'])) {
                    $imageAsset = ProductAsset::byStore($this->getStoreId())
                        ->where('id', $validated['custom_image_id'])
                        ->where('product_id', $product->id)
                        ->whereIn('asset_type', ['Image_Main', 'Image_Gallery'])
                        ->first();

                    if (!$imageAsset) {
                        DB::rollBack();
                        return $this->errorResponse('Selected variation image is invalid for this product/store', 422);
                    }
                }

                // Check if variation SKU is unique for this store
                $exists = ProductVariation::withTrashed()
                    ->where('store_id', $this->getStoreId())
                    ->where('variation_sku', $validated['variation_sku'])
                    ->exists();

                if ($exists) {
                    DB::rollBack();
                    return $this->errorResponse('Variation SKU already exists for this store', 422);
                }

                if (!$product->variations()->exists()) {
                    $this->convertProductInventoryToBaselineVariation($product);
                }

                $initialStock = (int) ($validated['initial_stock'] ?? 0);
                $proposalId = (int) ($validated['proposal_id'] ?? 0);
                $data = $validated;
                unset($data['initial_stock'], $data['proposal_id']);

                if ($proposalId && empty($data['custom_image_id'])) {
                    $proposal = SupplierRFQFeedback::where('id', $proposalId)->where('merchandising_status', 'pending')->first();
                    $sourceImage = $proposal?->variant_image_paths[0] ?? null;
                    if ($proposal && $sourceImage) {
                        $data['custom_image_id'] = ProductAsset::create([
                            'store_id' => $this->getStoreId(), 'product_id' => $product->id,
                            'asset_type' => 'Image_Gallery', 'file_name' => basename($sourceImage),
                            'file_path' => $sourceImage, 'is_primary' => false, 'display_order' => 0,
                            'alt_text' => $proposal->variant_name,
                        ])->id;
                    }
                }
                $data['store_id'] = $this->getStoreId();
                $data['base_price'] = $product->base_price;
                $data['discounted_price'] = $product->discounted_price;
                $data['price_adjustment'] = 0;
                $data['cost_price'] = $data['cost_price'] ?? $product->getRawOriginal('cost_price');
                $data['reorder_point'] = $data['reorder_point'] ?? (int) ($product->reorder_point ?? 0);
                $data['supplier_name'] = $product->supplier_name;
                $data['unit_of_measurement'] = $data['unit_of_measurement'] ?? $product->unit_of_measurement;
                $data['length_cm'] = $data['length_cm'] ?? $product->length_cm;
                $data['width_cm'] = $data['width_cm'] ?? $product->width_cm;
                $data['height_cm'] = $data['height_cm'] ?? $product->height_cm;
                $data['weight_kg'] = $data['weight_kg'] ?? $product->weight_kg;
                
                $variation = ProductVariation::create($data);
                $this->ensureVariationInventoryRows($product, $variation);
                $this->seedVariationOpeningStock($variation, $initialStock);

                if ($proposalId) {
                    SupplierRFQFeedback::query()
                        ->where('id', $proposalId)
                        ->where('has_variant', true)
                        ->where('merchandising_status', 'pending')
                        ->update(['merchandising_status' => 'created', 'created_variation_id' => $variation->id]);
                }

                DB::commit();

                return $this->successResponse(
                    $variation->load('product'),
                    'Variation created successfully',
                    201
                );

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ValidationException $e) {
            return $this->errorResponse(
                'Validation error',
                422,
                $e->errors()
            );
        } catch (\Exception $e) {
            Log::error('Failed to create variation', [
                'store_id' => $this->getStoreId(),
                'user_id' => $this->getUserId(),
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to create variation: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Display the specified variation.
     */
    public function show($id)
    {
        try {
            $variation = ProductVariation::byStore($this->getStoreId())
                                        ->with([
                                            'product',
                                            'custom3dModel',
                                            'customImage',
                                            'pricingHistory' => function($query) {
                                                $query->orderBy('effective_date', 'desc')->limit(10);
                                            }
                                        ])
                                        ->findOrFail($id);

            // Add computed attributes
            $variationData = $variation->toArray();
            $variationData['final_price'] = $variation->final_price;
            $variationData['display_name'] = $variation->display_name;

            return $this->successResponse($variationData, 'Variation retrieved successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Variation not found', 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve variation', [
                'store_id' => $this->getStoreId(),
                'variation_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to retrieve variation',
                500,
                [],
                $e
            );
        }
    }

    /**
     * Update the specified variation.
     */
    public function update(Request $request, $id)
    {
        try {
            $variation = ProductVariation::byStore($this->getStoreId())->findOrFail($id);

            $validated = $this->validateRequest($request, [
                'variation_sku' => 'sometimes|string|max:50|unique:product_variations,variation_sku,' . $id . ',id,store_id,' . $this->getStoreId(),
                'variation_name' => 'sometimes|string|max:200',
                'color' => 'nullable|string|max:50',
                'color_hex' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
                'size' => 'nullable|string|max:50',
                'material' => 'nullable|string|max:100',
                'texture' => 'nullable|string|max:100',
                'finish' => 'nullable|string|max:100',
                'cost_price' => 'nullable|numeric|min:0',
                'reorder_point' => 'nullable|integer|min:0',
                'unit_of_measurement' => 'nullable|string|max:50',
                'custom_3d_model_id' => 'nullable|exists:product_assets,id',
                'custom_image_id' => 'nullable|exists:product_assets,id',
                'length_cm' => 'nullable|numeric|min:0',
                'width_cm' => 'nullable|numeric|min:0',
                'height_cm' => 'nullable|numeric|min:0',
                'weight_kg' => 'nullable|numeric|min:0',
                'is_active' => 'boolean'
            ]);

            if (array_key_exists('is_active', $validated) && !$validated['is_active'] && $variation->is_active) {
                return $this->errorResponse('Use Archive for deactivation so inventory can be checked first.', 422);
            }

            DB::beginTransaction();

            try {
                if (array_key_exists('custom_3d_model_id', $validated) && !empty($validated['custom_3d_model_id'])) {
                    $modelAsset = ProductAsset::byStore($this->getStoreId())
                        ->where('id', $validated['custom_3d_model_id'])
                        ->where('product_id', $variation->product_id)
                        ->where('asset_type', '3D_Model')
                        ->first();

                    if (!$modelAsset) {
                        DB::rollBack();
                        return $this->errorResponse('Selected 3D model is invalid for this variation product/store', 422);
                    }
                }

                if (array_key_exists('custom_image_id', $validated) && !empty($validated['custom_image_id'])) {
                    $imageAsset = ProductAsset::byStore($this->getStoreId())
                        ->where('id', $validated['custom_image_id'])
                        ->where('product_id', $variation->product_id)
                        ->whereIn('asset_type', ['Image_Main', 'Image_Gallery'])
                        ->first();

                    if (!$imageAsset) {
                        DB::rollBack();
                        return $this->errorResponse('Selected variation image is invalid for this product/store', 422);
                    }
                }

                // Inventory edits must not change ecommerce pricing or supplier ownership.
                unset(
                    $validated['price_adjustment'],
                    $validated['base_price'],
                    $validated['discounted_price'],
                    $validated['price_change_reason']
                );
                $validated['supplier_name'] = $variation->product->supplier_name;
                
                $variation->update($validated);
                if ((bool) $variation->is_active) {
                    $this->ensureVariationInventoryRows($variation->product, $variation);
                    BranchInventory::query()
                        ->where('store_id', $this->getStoreId())
                        ->where('variation_id', $variation->id)
                        ->update([
                            'reorder_point' => (int) ($variation->reorder_point ?? 0),
                        ]);
                }

                DB::commit();

                return $this->successResponse(
                    $variation->fresh(['product', 'custom3dModel', 'customImage']),
                    'Variation updated successfully'
                );

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ValidationException $e) {
            return $this->errorResponse(
                'Validation error',
                422,
                $e->errors()
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Variation not found', 404);
        } catch (\Exception $e) {
            Log::error('Failed to update variation', [
                'store_id' => $this->getStoreId(),
                'variation_id' => $id,
                'user_id' => $this->getUserId(),
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to update variation: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Remove the specified variation.
     */
    public function destroy($id)
    {
        return $this->errorResponse(
            'Permanent variant deletion is disabled. Archive the variant to preserve inventory and sales history.',
            405
        );
    }

    public function archive($id)
    {
        $variation = ProductVariation::byStore($this->getStoreId())->find($id);

        if (!$variation) {
            return $this->errorResponse('Variation not found', 404);
        }

        $hasStock = BranchInventory::query()
            ->where('store_id', $this->getStoreId())
            ->where('variation_id', $variation->id)
            ->where(function ($query) {
                $query->where('quantity_on_hand', '>', 0)
                    ->orWhere('quantity_available', '>', 0)
                    ->orWhere('quantity_reserved', '>', 0)
                    ->orWhere('quantity_incoming', '>', 0);
            })
            ->exists();

        if ($hasStock) {
            return $this->errorResponse(
                'This variant still has on-hand, available, reserved, or incoming stock. Clear its inventory before archiving.',
                422
            );
        }

        $hasActiveSibling = ProductVariation::byStore($this->getStoreId())
            ->where('product_id', $variation->product_id)
            ->where('id', '!=', $variation->id)
            ->where('is_active', true)
            ->exists();

        if (!$hasActiveSibling) {
            return $this->errorResponse('At least one active variant must remain for this product.', 422);
        }

        $variation->update(['is_active' => false]);

        return $this->successResponse(
            $variation->fresh(),
            'Variant archived successfully'
        );
    }

    /**
     * Get all variations for a specific product.
     */
    public function getByProduct($productId)
    {
        try {
            // Verify product belongs to store
            $product = Product::byStore($this->getStoreId())->find($productId);
            
            if (!$product) {
                return $this->errorResponse('Product not found or does not belong to this store', 404);
            }

            $variations = ProductVariation::byStore($this->getStoreId())
                                         ->where('product_id', $productId)
                                         ->with(['custom3dModel', 'customImage'])
                                         ->orderBy('is_active', 'desc')
                                         ->orderBy('created_at', 'desc')
                                         ->get();

            // Add computed fields
            $variationsData = $variations->map(function($variation) {
                $data = $variation->toArray();
                $data['final_price'] = $variation->final_price;
                $data['display_name'] = $variation->display_name;
                return $data;
            });

            return $this->successResponse([
                'product_id' => (int) $productId,
                'product_name' => $product->product_name,
                'base_price' => $product->base_price,
                'total_variations' => $variations->count(),
                'variations' => $variationsData
            ], 'Product variations retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve product variations', [
                'store_id' => $this->getStoreId(),
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to retrieve variations: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Ensure every active branch has a branch_inventory row for this product+variation.
     * Initial quantity is zero; stock will be adjusted via receiving/transactions.
     */
    private function ensureVariationInventoryRows(Product $product, ProductVariation $variation): void
    {
        $branches = Branch::query()
            ->where('store_id', $this->getStoreId())
            ->where('status', 'active')
            ->get(['id']);

        foreach ($branches as $branch) {
            BranchInventory::query()->firstOrCreate(
                [
                    'store_id' => $this->getStoreId(),
                    'branch_id' => (int) $branch->id,
                    'product_id' => (int) $product->id,
                    'variation_id' => (int) $variation->id,
                ],
                [
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'quantity_available' => 0,
                    'quantity_damaged' => 0,
                    'quantity_incoming' => 0,
                    'reorder_point' => (int) ($variation->reorder_point ?? 0),
                    'reorder_quantity' => 0,
                    'maximum_stock' => 0,
                    'safety_stock' => 0,
                    'stock_status' => 'out_of_stock',
                ]
            );
        }
    }

    private function convertProductInventoryToBaselineVariation(Product $product): ProductVariation
    {
        $baselineSku = (string) $product->sku;
        if (ProductVariation::withTrashed()->where('store_id', $this->getStoreId())->where('variation_sku', $baselineSku)->exists()) {
            $baseCandidate = $baselineSku . '-STD';
            $baselineSku = $baseCandidate;
            $suffix = 2;
            while (ProductVariation::withTrashed()->where('store_id', $this->getStoreId())->where('variation_sku', $baselineSku)->exists()) {
                $baselineSku = $baseCandidate . '-' . $suffix++;
            }
        }

        $baseline = ProductVariation::create([
            'store_id' => $this->getStoreId(),
            'product_id' => $product->id,
            'variation_sku' => $baselineSku,
            'variation_name' => 'Standard',
            'price_adjustment' => 0,
            'base_price' => $product->base_price,
            'discounted_price' => $product->discounted_price,
            'cost_price' => $product->getRawOriginal('cost_price'),
            'reorder_point' => (int) ($product->reorder_point ?? 0),
            'supplier_name' => $product->supplier_name,
            'unit_of_measurement' => $product->unit_of_measurement,
            'is_baseline' => true,
            'is_active' => true,
        ]);

        BranchInventory::query()
            ->where('store_id', $this->getStoreId())
            ->where('product_id', $product->id)
            ->whereNull('variation_id')
            ->update([
                'variation_id' => $baseline->id,
                'reorder_point' => (int) ($baseline->reorder_point ?? 0),
            ]);

        $this->ensureVariationInventoryRows($product, $baseline);

        return $baseline;
    }

    private function seedVariationOpeningStock(ProductVariation $variation, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        $user = auth()->user();
        $branchId = (int) ($user?->branch_id ?? 0);
        if ($branchId <= 0) {
            $branchId = (int) Branch::query()
                ->where('store_id', $this->getStoreId())
                ->where('status', 'active')
                ->orderByDesc('is_main_branch')
                ->orderBy('id')
                ->value('id');
        }

        if ($branchId <= 0) {
            return;
        }

        BranchInventory::query()
            ->where('store_id', $this->getStoreId())
            ->where('branch_id', $branchId)
            ->where('variation_id', $variation->id)
            ->update([
                'quantity_on_hand' => $quantity,
                'quantity_available' => $quantity,
                'stock_status' => $quantity <= (int) ($variation->reorder_point ?? 0) ? 'low_stock' : 'in_stock',
            ]);
    }
}
