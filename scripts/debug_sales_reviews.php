<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$storeId = null;
if (isset($argv[1]) && is_numeric($argv[1])) {
    $storeId = (int) $argv[1];
}

if (!$storeId) {
    $storeId = (int) (\App\Models\Sales\SalesReview::query()->value('store_id') ?: 0);
}

echo "store_id={$storeId}\n";
echo "sales_reviews_total=" . \App\Models\Sales\SalesReview::query()->where('store_id', $storeId)->count() . "\n";
echo "ecommerce_product_reviews_published=" . \App\Models\Ecommerce\EcommerceProductReview::query()->where('store_id', $storeId)->where('status', 'published')->count() . "\n";

$sample = \App\Models\Ecommerce\EcommerceProductReview::query()
    ->where('store_id', $storeId)
    ->where('status', 'published')
    ->orderByDesc('id')
    ->first();

if ($sample) {
    echo "sample_ecom_review_id={$sample->id}, order_id={$sample->order_id}, product_id={$sample->product_id}, user_id={$sample->user_id}, rating={$sample->rating}\n";
} else {
echo "sample_ecom_review_id=null\n";
}

$topStores = \App\Models\Ecommerce\EcommerceProductReview::query()
    ->selectRaw('store_id, count(*) as total')
    ->where('status', 'published')
    ->groupBy('store_id')
    ->orderByDesc('total')
    ->limit(10)
    ->get();

echo "top_stores_with_published_ecom_reviews:\n";
foreach ($topStores as $row) {
    echo "- store_id={$row->store_id} total={$row->total}\n";
}
