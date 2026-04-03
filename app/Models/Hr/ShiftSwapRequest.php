<?php

namespace App\Models\Hr;

use App\Models\Hr\ShiftSchedule;
use App\Models\Hr\Employee;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftSwapRequest extends Model
{
    use HasFactory;

    protected $table = 'shift_swap_requests';

    protected $fillable = [
        'requestor_id',
        'receiver_id',
        'requestor_schedule_id',
        'receiver_schedule_id',
        'swap_type',
        'status',
        'reason',
        'approved_by',
        'approved_at'
    ];

    protected $casts = [
        'approved_at' => 'datetime'
    ];
    

    // Relationships
    public function requestor()
    {
        return $this->belongsTo(Employee::class, 'requestor_id');
    }

    public function receiver()
    {
        return $this->belongsTo(Employee::class, 'receiver_id');
    }

    public function requestorSchedule()
    {
        return $this->belongsTo(ShiftSchedule::class, 'requestor_schedule_id');
    }

    public function receiverSchedule()
    {
        return $this->belongsTo(ShiftSchedule::class, 'receiver_schedule_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('requestor_id', $employeeId)
                     ->orWhere('receiver_id', $employeeId);
    }

    // Methods
    public function accept($userId = null)
    {
        if ($this->swap_type === 'full_swap') {
            // Swap the shifts
            $tempDate = $this->requestorSchedule->schedule_date;
            
            $this->requestorSchedule->update([
                'schedule_date' => $this->receiverSchedule->schedule_date
            ]);
            
            $this->receiverSchedule->update([
                'schedule_date' => $tempDate
            ]);
        } elseif ($this->swap_type === 'give_away') {
            // Transfer shift to receiver
            $this->requestorSchedule->update([
                'employee_id' => $this->receiver_id
            ]);
        } elseif ($this->swap_type === 'pick_up') {
            // Receiver takes the shift
            $this->receiverSchedule->update([
                'employee_id' => $this->requestor_id
            ]);
        }

        $this->update([
            'status' => 'accepted',
            'approved_by' => $userId,
            'approved_at' => now()
        ]);
    }

    public function reject($userId = null)
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $userId,
            'approved_at' => now()
        ]);
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
    }
}