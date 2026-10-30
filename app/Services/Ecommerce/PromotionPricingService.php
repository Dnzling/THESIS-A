<?php

namespace App\Services\Ecommerce;

use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;
use App\Models\ProductCatalog\Promotion;

class PromotionPricingService
{
    private array $promotionsByStore = [];

    public function price(Product $product, ?ProductVariation $variation = null, float $orderSubtotal = 0): array
    {
        $productRegular = (float) ($product->base_price ?? 0);
        $regular = $variation
            ? (float) ($variation->base_price ?? ($productRegular + (float) ($variation->price_adjustment ?? 0)))
            : $productRegular;
        $individual = $variation
            ? ($variation->discounted_price ?? $product->discounted_price)
            : $product->discounted_price;
        $best = $individual !== null && (float) $individual < $regular
            ? (float) $individual
            : $regular;
        $source = $best < $regular ? 'individual' : 'regular';
        $promotionId = null;

        foreach ($this->activePromotions((int) $product->store_id) as $promotion) {
            if ((float) $promotion->minimum_order_amount > $orderSubtotal || !$this->targetsProduct($promotion, $product)) {
                continue;
            }
            $candidate = $promotion->discount_type === 'percentage'
                ? $regular * (1 - (float) $promotion->discount_value / 100)
                : $regular - (float) $promotion->discount_value;
            $candidate = round(max(0, $candidate), 2);
            if ($candidate < $best) {
                $best = $candidate;
                $source = 'promotion';
                $promotionId = $promotion->id;
            }
        }

        return [
            'price' => round($best, 2),
            'regular_price' => round($regular, 2),
            'source' => $source,
            'promotion_id' => $promotionId,
        ];
    }

    private function activePromotions(int $storeId)
    {
        if (!isset($this->promotionsByStore[$storeId])) {
            $now = now();
            $this->promotionsByStore[$storeId] = Promotion::query()
                ->byStore($storeId)
                ->where('is_active', true)
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $now))
                ->with(['categories', 'products'])
                ->orderByDesc('priority')
                ->get();
        }

        return $this->promotionsByStore[$storeId];
    }

    private function targetsProduct(Promotion $promotion, Product $product): bool
    {
        return match ($promotion->target_type) {
            'all' => true,
            'products' => $promotion->products->contains('id', $product->id),
            'categories' => $promotion->categories->contains('id', $product->category_id)
                || $promotion->categories->contains('id', $product->subcategory_id),
            default => false,
        };
    }
}
