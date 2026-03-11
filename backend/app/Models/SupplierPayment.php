<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPayment extends Model
{
    use HasFactory;

    protected $table = 'supplier_payments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'supplier_id',
        'amount',
        'payment_date',
        'due_date',
        'status',
        'days_overdue',
        'payment_method',
        'reference_number',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'payment_date' => 'date',
        'due_date' => 'date',
        'amount' => 'float',
        'days_overdue' => 'integer',
    ];

    /**
     * Get the supplier this payment belongs to.
     *
     * @return BelongsTo
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Calculate days overdue.
     *
     * @return int
     */
    public function calculateDaysOverdue(): int
    {
        if ($this->status === 'paid' || !$this->due_date) {
            return 0;
        }

        $today = now()->startOfDay();
        $dueDate = $this->due_date instanceof \DateTime ? $this->due_date : new \DateTime($this->due_date);
        $diff = $today->diffInDays($dueDate);

        return max(0, $diff);
    }
}
