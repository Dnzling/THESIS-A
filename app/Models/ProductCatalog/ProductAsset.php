<?php
// app/Models/ProductCatalog/ProductAsset.php

namespace App\Models\ProductCatalog;

use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ProductAsset extends Model
{
    use SoftDeletes;

    protected $table = 'product_assets';

    protected $fillable = [
        'store_id',
        'product_id',
        'asset_type',
        'file_name',
        'file_path',
        'file_size_kb',
        'mime_type',
        'model_format',
        'is_ar_compatible',
        'is_primary',
        'display_order',
        'default_camera_angle_x',
        'default_camera_angle_y',
        'default_zoom_level',
        'alt_text',
        'caption'
    ];

    protected $casts = [
        'is_ar_compatible' => 'boolean',
        'is_primary' => 'boolean',
        'display_order' => 'integer',
        'file_size_kb' => 'integer',
        'default_camera_angle_x' => 'float',
        'default_camera_angle_y' => 'float',
        'default_zoom_level' => 'float'
    ];


    protected $appends = ['url', 'thumbnail_url', 'camera_settings'];

    /**
     * ✅ Get full URL for the asset
     */
    public function getUrlAttribute()
    {
        if (!$this->file_path) {
            return null;
        }

        // ✅ Use the serve route instead of direct storage URL
        return url("/api/product-catalog/assets/{$this->id}/serve");
    }

    /**
     * Get thumbnail URL (for images/videos)
     */
    public function getThumbnailUrlAttribute()
    {
        if (in_array($this->asset_type, ['Image_Main', 'Image_Gallery', 'Image_360'])) {
            return $this->url;
        }
        return null;
    }

    /**
     * Get camera settings (for 3D models)
     */
    public function getCameraSettingsAttribute()
    {
        if ($this->asset_type === '3D_Model') {
            return [
                'angle_x' => $this->default_camera_angle_x ?? 0,
                'angle_y' => $this->default_camera_angle_y ?? 15,
                'zoom' => $this->default_zoom_level ?? 1.5
            ];
        }
        return null;
    }

    /**
     * Get file exists status
     */
    public function getFileExistsAttribute()
    {
        return Storage::disk('public')->exists($this->file_path);
    }

    // Relationships
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variation()
    {
        return $this->hasOne(ProductVariation::class, 'custom_3d_model_id');
    }

    // Scopes
    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('asset_type', $type);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scope3dModels($query)
    {
        return $query->where('asset_type', '3D_Model');
    }

    public function scopeArCompatible($query)
    {
        return $query->where('is_ar_compatible', true);
    }
}
