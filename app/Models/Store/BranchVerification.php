<?php

namespace App\Models\Store;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchVerification extends Model
{
    protected $fillable = [
        'store_id', 'branch_id', 'documents', 'rejection_reason',
        'submitted_at', 'reviewed_at', 'reviewed_by',
    ];

    protected $casts = [
        'documents' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
