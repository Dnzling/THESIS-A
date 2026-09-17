<?php

namespace App\Models\CRM;

use App\Models\Core\User;
use App\Models\Hr\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ReturnInvestigationTicket extends Model
{
    protected $table = 'crm_return_investigation_tickets';

    protected $fillable = [
        'return_id',
        'store_id',
        'created_by',
        'expected_investigation_date',
        'notes',
        'status',
    ];

    protected $casts = [
        'expected_investigation_date' => 'date:Y-m-d',
    ];

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrderReturn::class, 'return_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(
            Employee::class,
            'crm_return_investigation_assignees',
            'ticket_id',
            'employee_id'
        )->withPivot('user_id')->withTimestamps();
    }
}
