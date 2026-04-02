<?php

namespace App\Models\ProductCatalog;

use Illuminate\Database\Eloquent\Model;

class Product3DReconstructionImage extends Model
{
    protected $table = 'product_3d_reconstruction_images';

    protected $fillable = [
        'reconstruction_id',
        'file_name',
        'file_path',
        'file_size_kb',
        'mime_type',
        'display_order',
    ];

    public function reconstruction()
    {
        return $this->belongsTo(Product3DReconstruction::class, 'reconstruction_id');
    }
}
