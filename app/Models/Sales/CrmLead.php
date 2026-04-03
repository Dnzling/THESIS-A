<?php

namespace App\Models\Sales;

use App\Models\Core\User;
use App\Models\Store\Branch;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmLead extends Model
{
    protected $table = 'sales_crm_leads';

    protected $fillable = [
        'store_id',
        'branch_id',
        'lead_code',
        'full_name',
        'email',
        'phone',
        'source',
        'stage',
        'estimated_value',
        'notes',
        'assigned_to',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
    ];

    public function store(): BelongsTo { return $this->belongsTo(Store::class, 'store_id'); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class, 'branch_id'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function activities(): HasMany { return $this->hasMany(CrmActivity::class, 'lead_id'); }
}

