<?php

namespace App\Models\Finance;

use App\Models\Core\User;
use App\Models\Store\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceCashAdvance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id', 'branch_id', 'advance_number', 'purpose', 'advance_amount',
        'liquidated_amount', 'cash_returned', 'reimbursement_amount', 'needed_date',
        'payment_method', 'status', 'notes', 'review_notes', 'requested_by',
        'approved_by', 'approved_at', 'released_by', 'released_at', 'submitted_by',
        'submitted_at', 'settled_by', 'settled_at',
    ];

    protected $casts = [
        'advance_amount' => 'decimal:2', 'liquidated_amount' => 'decimal:2',
        'cash_returned' => 'decimal:2', 'reimbursement_amount' => 'decimal:2',
        'needed_date' => 'date', 'approved_at' => 'datetime', 'released_at' => 'datetime',
        'submitted_at' => 'datetime', 'settled_at' => 'datetime',
    ];

    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function releaser(): BelongsTo { return $this->belongsTo(User::class, 'released_by'); }
    public function submitter(): BelongsTo { return $this->belongsTo(User::class, 'submitted_by'); }
    public function settler(): BelongsTo { return $this->belongsTo(User::class, 'settled_by'); }
    public function items(): HasMany { return $this->hasMany(FinanceLiquidationItem::class, 'cash_advance_id'); }
}
