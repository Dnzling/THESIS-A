<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$invalidPr = Illuminate\Support\Facades\DB::table('purchase_requisition_items as pri')
    ->join('product_variations as pv', 'pv.id', '=', 'pri.variation_id')
    ->whereNotNull('pri.variation_id')
    ->whereColumn('pv.product_id', '!=', 'pri.product_id')
    ->count();

$invalidPo = Illuminate\Support\Facades\DB::table('purchase_order_items as poi')
    ->join('product_variations as pv', 'pv.id', '=', 'poi.variation_id')
    ->whereNotNull('poi.variation_id')
    ->whereColumn('pv.product_id', '!=', 'poi.product_id')
    ->count();

echo "invalid_pr_variation_links={$invalidPr}" . PHP_EOL;
echo "invalid_po_variation_links={$invalidPo}" . PHP_EOL;

if ($invalidPr > 0 || $invalidPo > 0) {
    exit(1);
}

