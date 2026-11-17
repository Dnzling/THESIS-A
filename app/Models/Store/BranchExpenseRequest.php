<?php

namespace App\Models\Store;

use App\Models\Core\User;
use App\Models\Finance\FinanceExpense;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchExpenseRequest extends Model
{
    protected $fillable = [
        'reference_number',
        'store_id',
        'branch_id',
        'category',
        'title',
        'description',
        'requested_date',
        'estimated_amount',
        'actual_amount',
        'actual_expense_date',
        'status',
        'attachments',
        'receipt_path',
        'completion_notes',
        'review_notes',
        'created_by',
        'reviewed_by',
        'reviewed_at',
        'finance_expense_id',
    ];

    protected $casts = [
        'requested_date' => 'date',
        'actual_expense_date' => 'date',
        'estimated_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'attachments' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function financeExpense(): BelongsTo
    {
        return $this->belongsTo(FinanceExpense::class, 'finance_expense_id');
    }
}
