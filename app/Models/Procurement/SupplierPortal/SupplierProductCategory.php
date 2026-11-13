<?php

namespace App\Models\Procurement\SupplierPortal;

use Illuminate\Database\Eloquent\Model;

class SupplierProductCategory extends Model
{
    protected $table = 'supplier_product_categories';

    protected $fillable = ['supplier_id', 'parent_category_id', 'category_name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_category_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_category_id');
    }
}
