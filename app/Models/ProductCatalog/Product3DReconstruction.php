<?php

namespace App\Models\ProductCatalog;

use Illuminate\Database\Eloquent\Model;

class Product3DReconstruction extends Model
{
    protected $table = 'product_3d_reconstructions';

    protected $fillable = [
        'store_id',
        'product_id',
        'created_by',
        'status',
        'input_count',
        'progress',
        'output_path',
        'output_format',
        'output_asset_id',
        'options',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'options' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function images()
    {
        return $this->hasMany(Product3DReconstructionImage::class, 'reconstruction_id');
    }

    public function outputAsset()
    {
        return $this->belongsTo(ProductAsset::class, 'output_asset_id');
    }
}
