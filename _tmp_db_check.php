<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$storeId = 16;
$total = Illuminate\Support\Facades\DB::table('products')->where('store_id',$storeId)->count();
$inactive = Illuminate\Support\Facades\DB::table('products')->where('store_id',$storeId)->where('is_active',0)->count();
$latest = Illuminate\Support\Facades\DB::table('products')->where('store_id',$storeId)->orderByDesc('id')->limit(5)->get();
echo json_encode(['store_id'=>$storeId,'total'=>$total,'inactive'=>$inactive,'latest'=>$latest], JSON_PRETTY_PRINT);
?>
