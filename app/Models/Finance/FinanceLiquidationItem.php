<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FinanceLiquidationItem extends Model
{
    protected $fillable = ['cash_advance_id', 'expense_date', 'category', 'description', 'amount', 'receipt_path', 'receipt_name'];
    protected $casts = ['expense_date' => 'date', 'amount' => 'decimal:2'];
    protected $appends = ['receipt_url'];

    public function cashAdvance(): BelongsTo { return $this->belongsTo(FinanceCashAdvance::class); }
    public function getReceiptUrlAttribute(): ?string { return $this->receipt_path ? Storage::disk('public')->url($this->receipt_path) : null; }
}
