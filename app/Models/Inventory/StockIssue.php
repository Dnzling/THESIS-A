<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Store\Store;
use App\Models\Store\Branch;
use App\Models\Core\User;

class StockIssue extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'branch_id',
        'issue_number',
        'issue_date',
        'issue_type',
        'description',
        'remarks',
        'status',
        'total_value',
        'requested_by',
        'approved_by',
        'approved_at',
        'approval_notes',
        'issued_by',
        'issued_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'total_value' => 'decimal:2',
        'approved_at' => 'datetime',
        'issued_at' => 'datetime',
    ];

    /**
     * Get the store that owns the stock issue
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the branch that owns the stock issue
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who requested the issue
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who approved the issue
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who issued the items
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Get the user who created the issue
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the issue
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the items in this stock issue
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockIssueItem::class);
    }

    /**
     * Scope to get issues by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get issues by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('issue_type', $type);
    }

    /**
     * Check if the issue can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'submitted';
    }

    /**
     * Check if the issue can be issued
     */
    public function canBeIssued(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Calculate total value from items
     */
    public function calculateTotalValue(): float
    {
        return $this->items->sum('total_value');
    }
}
