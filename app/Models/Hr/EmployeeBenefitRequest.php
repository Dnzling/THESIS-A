<?php

namespace App\Models\Hr;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeBenefitRequest extends Model
{
    protected $fillable = [
        'store_id', 'employee_id', 'deduction_type_id', 'provider', 'service_type',
        'service_date', 'requested_amount', 'approved_amount', 'used_amount', 'company_payment_amount',
        'status', 'notes', 'review_notes', 'request_attachment_path', 'receipt_path',
        'reviewed_by', 'reviewed_at', 'settled_at',
    ];

    protected $casts = [
        'service_date' => 'date', 'reviewed_at' => 'datetime', 'settled_at' => 'datetime',
        'requested_amount' => 'decimal:2', 'approved_amount' => 'decimal:2', 'used_amount' => 'decimal:2',
        'company_payment_amount' => 'decimal:2',
    ];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function deductionType(): BelongsTo { return $this->belongsTo(DeductionType::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
