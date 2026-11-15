<?php

namespace App\Models\Procurement\SupplierPortal;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPOFulfillmentUpdate extends Model
{
    protected $fillable = ['purchase_order_id', 'supplier_portal_id', 'created_by', 'status', 'notes', 'photo_paths'];

    protected $casts = ['photo_paths' => 'array'];

    protected $appends = ['photos'];

    public function getPhotosAttribute(): array
    {
        return collect($this->photo_paths ?? [])->map(fn (string $path) => \Illuminate\Support\Facades\Storage::disk('public')->url($path))->all();
    }

    public function supplierPortal(): BelongsTo
    {
        return $this->belongsTo(SupplierPortal::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
