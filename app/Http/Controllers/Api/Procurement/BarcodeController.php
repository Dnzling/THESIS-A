<?php
// backend/app/Http/Controllers/Api/Procurement/BarcodeController.php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Controller;
use App\Models\ProductCatalog\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class BarcodeController extends Controller
{
    /**
     * Lookup product by barcode
     * GET /api/procurement/barcode/lookup
     */
    public function lookup(Request $request): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            $barcode = $request->get('code');

            if (!$barcode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Barcode code is required',
                ], 400);
            }

            // Search for product by EAN, SKU, or product name
            $product = Product::where('store_id', $storeId)
                ->where(function($q) use ($barcode) {
                    $q->where('sku', (string)$barcode)
                      ->orWhere('ean', (string)$barcode)
                      ->orWhere('upc', (string)$barcode)
                      ->orWhereRaw('SOUNDEX(product_name) = SOUNDEX(?)', [$barcode])
                      ->orWhere('product_name', 'like', "%{$barcode}%");
                })
                ->with(['category:id,category_name'])
                ->first();

            if (!$product) {
                Log::warning("Barcode not found: {$barcode}", ['store_id' => $storeId]);

                return response()->json([
                    'success' => false,
                    'message' => 'Product not found for barcode: ' . $barcode,
                    'data' => null,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'product_name' => $product->product_name,
                    'ean' => $product->ean,
                    'upc' => $product->upc,
                    'category_name' => $product->category?->category_name,
                    'base_price' => $product->base_price,
                    'description' => $product->description,
                    'image_url' => $product->main_image_url,
                ],
                'message' => 'Product found',
            ]);

        } catch (\Exception $e) {
            Log::error('Barcode lookup error', [
                'error' => $e->getMessage(),
                'barcode' => $request->get('code')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error looking up barcode',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate barcode format
     * POST /api/procurement/barcode/validate
     */
    public function validate(Request $request): JsonResponse
    {
        try {
            $barcode = $request->get('code');

            if (!$barcode) {
                return response()->json([
                    'success' => false,
                    'valid' => false,
                    'message' => 'Barcode is required',
                ]);
            }

            // EAN-13 validation
            if ($this->validateEAN13($barcode)) {
                return response()->json([
                    'success' => true,
                    'valid' => true,
                    'format' => 'EAN-13',
                ]);
            }

            // Code-128 (more flexible, usually any printable ASCII)
            if ($this->validateCode128($barcode)) {
                return response()->json([
                    'success' => true,
                    'valid' => true,
                    'format' => 'Code-128',
                ]);
            }

            return response()->json([
                'success' => true,
                'valid' => true,
                'format' => 'Custom', // Allow custom formats
                'message' => 'Barcode format is recognized',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error validating barcode',
            ], 500);
        }
    }

    /**
     * Validate EAN-13 checksum
     */
    private function validateEAN13(string $barcode): bool
    {
        if (!preg_match('/^\d{13}$/', $barcode)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = intval($barcode[$i]);
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }

        $checksum = (10 - ($sum % 10)) % 10;
        return intval($barcode[12]) === $checksum;
    }

    /**
     * Validate Code-128
     */
    private function validateCode128(string $barcode): bool
    {
        // Code-128 can contain printable ASCII characters
        return preg_match('/^[\x00-\x7F]+$/', $barcode) === 1 && strlen($barcode) >= 1;
    }

    /**
     * Get barcode suggestions (for autocomplete)
     * GET /api/procurement/barcode/suggestions
     */
    public function suggestions(Request $request): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            $search = $request->get('q', '');

            if (strlen($search) < 2) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ]);
            }

            $products = Product::where('store_id', $storeId)
                ->where(function($q) use ($search) {
                    $q->where('sku', 'like', "%{$search}%")
                      ->orWhere('ean', 'like', "%{$search}%")
                      ->orWhere('upc', 'like', "%{$search}%")
                      ->orWhere('product_name', 'like', "%{$search}%");
                })
                ->select('id', 'sku', 'product_name', 'ean', 'upc')
                ->limit(10)
                ->get();

            $suggestions = $products->map(function($product) {
                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->product_name,
                    'barcode' => $product->ean ?? $product->upc ?? $product->sku,
                    'label' => "{$product->sku} - {$product->product_name}",
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $suggestions,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting suggestions',
            ], 500);
        }
    }
}
