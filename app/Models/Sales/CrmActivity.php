<?php

namespace App\Models\Sales;

use App\Models\Core\User;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmActivity extends Model
{
    protected $table = 'sales_crm_activities';

    protected $fillable = [
        'lead_id',
        'store_id',
        'activity_type',
        'description',
        'activity_at',
        'meta',
        'created_by',
    ];

    protected $casts = [
        'activity_at' => 'datetime',
        'meta' => 'array',
    ];

    public function lead(): BelongsTo { return $this->belongsTo(CrmLead::class, 'lead_id'); }
    public function store(): BelongsTo { return $this->belongsTo(Store::class, 'store_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}

