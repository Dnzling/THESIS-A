<?php

namespace App\Models\Procurement\RFQ;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Hr\Employee;

class RfqEvaluationCriterion extends Model
{
    protected $table = 'rfq_evaluation_criteria';

    protected $fillable = [
        'rfq_id',
        'criterion_name',
        'weight_percentage',
        'description',
        'order',
        'created_by',
    ];

    protected $casts = [
        'weight_percentage' => 'integer',
        'order' => 'integer',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RequestForQuotation::class, 'rfq_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }
}
