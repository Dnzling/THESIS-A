<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\User;
use App\Models\Store\Store;
use App\Models\Store\Branch;
use App\Models\Store\BranchExpenseRequest;

class FinanceExpense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'branch_id',
        'department',
        'category',
        'amount',
        'expense_date',
        'status',
        'payment_method',
        'payment_date',
        'reference_number',
        'payment_reference',
        'reference_type',
        'reference_id',
        'currency',
        'description',
        'notes',
        'requested_by',
        'approved_by',
        'approved_at',
        'paid_by',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'payment_date' => 'date',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function branchExpenseRequest(): HasOne
    {
        return $this->hasOne(BranchExpenseRequest::class, 'finance_expense_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
