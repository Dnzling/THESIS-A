<?php

namespace App\Models\Merchandising;

use Illuminate\Database\Eloquent\Model;

class Model3dRequest extends Model
{
    protected $table = 'model_3d_requests';

    protected $fillable = ['owner_store_id', 'product_id', 'requested_by', 'status', 'length_cm', 'width_cm', 'height_cm', 'materials', 'notes', 'reference_photos', 'quoted_price', 'included_revisions', 'quote_notes', 'model_path', 'product_asset_id'];

    protected $casts = ['reference_photos' => 'array', 'quoted_price' => 'decimal:2'];

    public function product() { return $this->belongsTo(\App\Models\ProductCatalog\Product::class); }
    public function store() { return $this->belongsTo(\App\Models\Store\Store::class, 'owner_store_id'); }
    public function asset() { return $this->belongsTo(\App\Models\ProductCatalog\ProductAsset::class, 'product_asset_id'); }
}
