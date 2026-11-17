<?php

namespace App\Http\Controllers\Api\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Inventory\BranchInventory;
use App\Models\Store\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WholesaleAccessController extends Controller
{
    public function publicStores(): JsonResponse
    {
        $stores = Store::query()->where('status', 'active')->get(['id', 'name', 'city', 'province', 'settings'])
            ->filter(fn (Store $store) => (bool) data_get($store->settings, 'wholesale.enabled', false))
            ->map(fn (Store $store) => [
                'id' => (int) $store->id,
                'name' => $store->name,
                'city' => $store->city,
                'province' => $store->province,
            ])->values();

        return response()->json(['success' => true, 'data' => $stores]);
    }

    public function myRequests(Request $request): JsonResponse
    {
        $rows = DB::table('store_wholesale_customers as access')
            ->join('stores', 'stores.id', '=', 'access.store_id')
            ->where('access.user_id', $request->user()->id)
            ->select('access.id', 'access.store_id', 'stores.name as store_name', 'access.business_name', 'access.status', 'access.review_note', 'access.created_at')
            ->orderByDesc('access.updated_at')->get();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function myQuotes(Request $request): JsonResponse
    {
        abort_unless($request->user()?->customer()->exists(), 403, 'A customer account is required.');

        $rows = DB::table('sales_wholesale_quotes as quotes')
            ->join('stores', 'stores.id', '=', 'quotes.store_id')
            ->leftJoin('store_wholesale_quote_requests as quote_requests', 'quote_requests.wholesale_quote_id', '=', 'quotes.id')
            ->where('quotes.customer_user_id', $request->user()->id)
            ->select('quotes.id', 'quotes.quote_number', 'quotes.status', 'quotes.items', 'quotes.subtotal', 'quotes.shipping_fee', 'quotes.valid_until', 'quotes.created_at', 'quotes.customer_response', 'quotes.customer_responded_at', 'quotes.payment_terms', 'quotes.delivery_required', 'quotes.delivery_address', 'quotes.delivery_notes', 'quote_requests.id as customer_request_id', 'stores.name as store_name')
            ->orderByDesc('quotes.created_at')->get();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function storeProducts(Request $request, int $storeId): JsonResponse
    {
        $this->assertApprovedCustomer($request, $storeId);

        $stocks = BranchInventory::query()
            ->where('branch_inventory.store_id', $storeId)
            ->where('branch_inventory.quantity_available', '>', 0)
            ->whereHas('branch', fn ($query) => $query->where('store_id', $storeId)
                ->where('status', 'active')->whereIn('branch_type', ['storefront', 'wholesale']))
            ->whereHas('product', fn ($query) => $query
                ->where('product_type', 'finished_good')->where('is_active', true)->whereNull('deleted_at'))
            ->with([
                'product:id,product_name,sku,base_price,category_id',
                'product.assets:id,product_id,asset_type,file_path,is_primary,display_order',
                'variation:id,product_id,variation_name,variation_sku,color,size,material,base_price,is_active',
                'variation.assets:id,variation_id,asset_type,file_path,is_primary,display_order',
            ])
            ->get();

        $products = $stocks->filter(fn ($stock) => !$stock->variation || $stock->variation->is_active)
            ->groupBy(fn ($stock) => $stock->product_id . ':' . ($stock->variation_id ?: 'standard'))
            ->map(function ($rows) {
                $stock = $rows->first();
                $variation = $stock->variation;
                $assets = $variation?->assets?->isNotEmpty() ? $variation->assets : $stock->product?->assets;
                $image = $assets?->whereIn('asset_type', ['Image_Main', 'Image_Gallery', 'Image_360'])
                    ->sortByDesc('is_primary')->sortBy('display_order')->first();

                return [
                    'product_id' => (int) $stock->product_id,
                    'variation_id' => $variation ? (int) $variation->id : null,
                    'product_name' => (string) ($stock->product?->product_name ?? 'Product'),
                    'variant_name' => $variation?->variation_name,
                    'variant_specs' => collect([$variation?->color, $variation?->size, $variation?->material])->filter()->implode(' / '),
                    'sku' => $variation?->variation_sku ?: $stock->product?->sku,
                    'image_url' => $image?->url,
                    'reference_price' => (float) ($variation?->base_price ?? $stock->product?->base_price ?? 0),
                    'available_stock' => (int) $rows->sum('quantity_available'),
                ];
            })->values();

        return response()->json(['success' => true, 'data' => $products]);
    }

    public function myQuoteRequests(Request $request): JsonResponse
    {
        abort_unless($request->user()?->customer()->exists(), 403, 'A customer account is required.');
        $rows = DB::table('store_wholesale_quote_requests as requests')
            ->join('stores', 'stores.id', '=', 'requests.store_id')
            ->where('requests.customer_user_id', $request->user()->id)
            ->select('requests.*', 'stores.name as store_name')
            ->orderByDesc('requests.created_at')->get();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function showStoreQuoteRequest(Request $request, int $id): JsonResponse
    {
        $storeId = (int) $request->user()->store_id;
        abort_unless($storeId > 0, 403);
        $row = DB::table('store_wholesale_quote_requests as requests')
            ->join('users', 'users.id', '=', 'requests.customer_user_id')
            ->leftJoin('store_wholesale_customers as access', function ($join) {
                $join->on('access.store_id', '=', 'requests.store_id')
                    ->on('access.user_id', '=', 'requests.customer_user_id');
            })
            ->where('requests.store_id', $storeId)->where('requests.id', $id)
            ->select('requests.*', 'users.fname', 'users.lname', 'users.email', 'users.phone_number', 'access.business_name')
            ->first();
        abort_unless($row, 404);
        return response()->json(['success' => true, 'data' => $row]);
    }

    public function createQuoteRequest(Request $request, int $storeId): JsonResponse
    {
        $this->assertApprovedCustomer($request, $storeId);
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.variation_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'customer_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $snapshot = [];
        $seenItems = [];
        foreach ($validated['items'] as $item) {
            $itemKey = $item['product_id'] . ':' . ($item['variation_id'] ?? 'standard');
            if (isset($seenItems[$itemKey])) {
                return response()->json(['success' => false, 'message' => 'Each product or variant can only be added once. Adjust its quantity instead.'], 422);
            }
            $seenItems[$itemKey] = true;
            $stockQuery = BranchInventory::query()
                ->where('store_id', $storeId)
                ->where('product_id', $item['product_id'])
                ->where('quantity_available', '>', 0)
                ->whereHas('branch', fn ($query) => $query->where('store_id', $storeId)
                    ->where('status', 'active')->whereIn('branch_type', ['storefront', 'wholesale']));
            $item['variation_id']
                ? $stockQuery->where('variation_id', $item['variation_id'])
                : $stockQuery->whereNull('variation_id');
            $stock = $stockQuery->with(['product:id,product_name,sku', 'variation:id,variation_sku,variation_name'])->first();
            if (!$stock || ($stock->variation && !$stock->variation->is_active)) {
                return response()->json(['success' => false, 'message' => 'One selected product is no longer available for wholesale quotation. Refresh the product list.'], 422);
            }

            $snapshot[] = [
                'product_id' => (int) $stock->product_id,
                'variation_id' => $stock->variation_id ? (int) $stock->variation_id : null,
                'product_name' => (string) $stock->product->product_name,
                'sku' => $stock->variation?->variation_sku ?: $stock->product->sku,
                'variant_name' => $stock->variation?->variation_name,
                'quantity' => (int) $item['quantity'],
            ];
        }

        $id = DB::table('store_wholesale_quote_requests')->insertGetId([
            'store_id' => $storeId,
            'customer_user_id' => $request->user()->id,
            'request_number' => 'WR-' . now()->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4))),
            'items' => json_encode($snapshot, JSON_THROW_ON_ERROR),
            'customer_note' => $validated['customer_note'] ?? null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Your product quote request was sent to the store.',
            'data' => DB::table('store_wholesale_quote_requests')->where('id', $id)->first(),
        ], 201);
    }

    public function respondToQuote(Request $request, int $quoteId): JsonResponse
    {
        abort_unless($request->user()?->customer()->exists(), 403, 'A customer account is required.');
        $validated = $request->validate(['response' => ['required', Rule::in(['accepted', 'declined'])]]);
        $quote = DB::table('sales_wholesale_quotes')
            ->where('id', $quoteId)
            ->where('customer_user_id', $request->user()->id)
            ->where('status', 'approved')
            ->first();
        abort_unless($quote, 404, 'Approved quotation not found for this customer.');
        if (DB::table('store_wholesale_quote_requests')->where('wholesale_quote_id', $quoteId)->where('status', 'quoted')->doesntExist()) {
            return response()->json(['success' => false, 'message' => 'This quotation is not awaiting a customer response.'], 422);
        }
        if ($quote->customer_response) {
            return response()->json(['success' => false, 'message' => 'A response has already been recorded for this quotation.'], 422);
        }
        if ($quote->valid_until && now()->startOfDay()->gt($quote->valid_until)) {
            return response()->json(['success' => false, 'message' => 'This quotation has expired. Ask the store for an updated quotation.'], 422);
        }

        $updated = DB::table('sales_wholesale_quotes')->where('id', $quoteId)->whereNull('customer_response')->update([
            'customer_response' => $validated['response'],
            'customer_responded_at' => now(),
            'updated_at' => now(),
        ]);
        if (!$updated) {
            return response()->json(['success' => false, 'message' => 'A response was just recorded for this quotation. Refresh to see it.'], 409);
        }
        return response()->json(['success' => true, 'message' => 'Your quotation response was sent to the store.']);
    }

    public function storeQuoteRequests(Request $request): JsonResponse
    {
        $storeId = (int) $request->user()->store_id;
        abort_unless($storeId > 0, 403);
        $rows = DB::table('store_wholesale_quote_requests as requests')
            ->join('users', 'users.id', '=', 'requests.customer_user_id')
            ->leftJoin('store_wholesale_customers as access', function ($join) {
                $join->on('access.store_id', '=', 'requests.store_id')
                    ->on('access.user_id', '=', 'requests.customer_user_id');
            })
            ->where('requests.store_id', $storeId)
            ->select('requests.*', 'users.fname', 'users.lname', 'users.email', 'users.phone_number', 'access.business_name')
            ->orderByRaw("CASE WHEN requests.status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('requests.created_at')->get();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    private function assertApprovedCustomer(Request $request, int $storeId): void
    {
        abort_unless($request->user()?->customer()->exists(), 403, 'A customer account is required.');
        $store = Store::query()->where('status', 'active')->findOrFail($storeId);
        abort_unless((bool) data_get($store->settings, 'wholesale.enabled', false), 404, 'This store is not accepting wholesale customers.');
        abort_unless(DB::table('store_wholesale_customers')
            ->where('store_id', $storeId)->where('user_id', $request->user()->id)->where('status', 'approved')->exists(),
            403, 'The store must approve your wholesale access before you can request product quotations.');
    }

    public function requestAccess(Request $request, int $storeId): JsonResponse
    {
        abort_unless($request->user()?->customer()->exists(), 403, 'A customer account is required to request wholesale access.');

        $validated = $request->validate([
            'business_name' => ['required', 'string', 'min:2', 'max:255'],
            'request_note' => ['nullable', 'string', 'max:1000'],
        ]);
        $store = Store::query()->where('status', 'active')->findOrFail($storeId);
        abort_unless((bool) data_get($store->settings, 'wholesale.enabled', false), 404, 'This store is not accepting wholesale customers.');

        $existing = DB::table('store_wholesale_customers')->where('store_id', $store->id)->where('user_id', $request->user()->id)->first();
        if ($existing?->status === 'approved') {
            return response()->json(['success' => true, 'message' => 'Your wholesale access is already approved.', 'data' => $existing]);
        }

        DB::table('store_wholesale_customers')->updateOrInsert(
            ['store_id' => $store->id, 'user_id' => $request->user()->id],
            [
                'business_name' => trim($validated['business_name']),
                'request_note' => $validated['request_note'] ?? null,
                'status' => 'pending',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_note' => null,
                'updated_at' => now(),
                'created_at' => $existing?->created_at ?? now(),
            ]
        );

        $row = DB::table('store_wholesale_customers')->where('store_id', $store->id)->where('user_id', $request->user()->id)->first();
        return response()->json(['success' => true, 'message' => 'Wholesale access request sent to the store for review.', 'data' => $row], 201);
    }

    public function storeRequests(Request $request): JsonResponse
    {
        $storeId = (int) $request->user()->store_id;
        abort_unless($storeId > 0, 403);

        $rows = DB::table('store_wholesale_customers as access')
            ->join('users', 'users.id', '=', 'access.user_id')
            ->where('access.store_id', $storeId)
            ->select('access.*', 'users.fname', 'users.lname', 'users.email')
            ->orderByRaw("CASE WHEN access.status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('access.created_at')->get();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function reviewRequest(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);
        $storeId = (int) $request->user()->store_id;
        $updated = DB::table('store_wholesale_customers')
            ->where('store_id', $storeId)->where('id', $id)->where('status', 'pending')
            ->update([
                'status' => $validated['status'],
                'review_note' => $validated['review_note'] ?? null,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);

        abort_unless($updated, 404, 'Pending wholesale access request not found.');
        return response()->json(['success' => true, 'message' => 'Wholesale access request updated.']);
    }
}
