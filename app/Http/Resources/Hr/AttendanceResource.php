<?php

namespace App\Http\Resources\Hr;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class AttendanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $employee = $this->employee;
        $user = $employee?->user;
        
        // Determine if we need full details or just table view
        $includeFullDetails = $request->has('with_details') || $request->is('api/attendance/*');
        
        $baseData = [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'attendance_date' => $this->attendance_date?->format('Y-m-d'),
            'status' => $this->status,
        ];

        // Table view data (always included)
        $tableData = [
            'employee' => [
                'id' => $employee?->id,
                'full_name' => $employee?->fname . ' ' . $employee?->lname,
                'employee_number' => $employee?->employee_number,
                'role_name' => $user?->role_name,
                'department' => $employee?->department,
                'branch' => $user?->branch?->name ?? $employee?->branch?->name,
            ],
            'shift' => [
                'id' => $this->shift?->id,
                'name' => $this->shift?->name,
                'start_time' => $this->shift?->start_time,
                'end_time' => $this->shift?->end_time,
            ],
            'clock_in' => $this->clock_in ? Carbon::parse($this->clock_in)->format('h:i A') : null,
            'clock_out' => $this->clock_out ? Carbon::parse($this->clock_out)->format('h:i A') : null,
            'total_worked_hours' => $this->when($this->total_worked_minutes, function() {
                return floor($this->total_worked_minutes / 60) . 'h ' . ($this->total_worked_minutes % 60) . 'm';
            }),
            'status_badge' => [
                'label' => ucfirst($this->status),
                'color' => $this->getStatusColor($this->status),
            ],
        ];

        // Full details for view/edit modal
        $fullDetails = $includeFullDetails ? [
            'clock_in_details' => [
                'time' => $this->clock_in ? Carbon::parse($this->clock_in)->format('h:i A') : null,
                'ip' => $this->clock_in_ip,
                'location' => $this->clock_in_location,
                'raw' => $this->clock_in,
            ],
            'clock_out_details' => [
                'time' => $this->clock_out ? Carbon::parse($this->clock_out)->format('h:i A') : null,
                'ip' => $this->clock_out_ip,
                'location' => $this->clock_out_location,
                'raw' => $this->clock_out,
            ],
            'break_details' => [
                'break_start' => $this->break_start ? Carbon::parse($this->break_start)->format('h:i A') : null,
                'break_end' => $this->break_end ? Carbon::parse($this->break_end)->format('h:i A') : null,
                'break_minutes' => $this->break_minutes,
                'break_hours' => $this->break_minutes ? floor($this->break_minutes / 60) . 'h ' . ($this->break_minutes % 60) . 'm' : null,
            ],
            'time_summary' => [
                'late_minutes' => $this->late_minutes,
                'late_hours' => $this->late_minutes ? floor($this->late_minutes / 60) . 'h ' . ($this->late_minutes % 60) . 'm' : null,
                'early_departure_minutes' => $this->early_departure_minutes,
                'early_departure_hours' => $this->early_departure_minutes ? floor(abs($this->early_departure_minutes) / 60) . 'h ' . (abs($this->early_departure_minutes) % 60) . 'm' : null,
                'overtime_minutes' => $this->overtime_minutes,
                'overtime_hours' => $this->overtime_minutes ? floor($this->overtime_minutes / 60) . 'h ' . ($this->overtime_minutes % 60) . 'm' : null,
                'total_worked_minutes' => $this->total_worked_minutes,
                'total_worked_hours' => $this->total_worked_minutes ? floor($this->total_worked_minutes / 60) . 'h ' . ($this->total_worked_minutes % 60) . 'm' : null,
            ],
            'shift_details' => [
                'id' => $this->shift?->id,
                'name' => $this->shift?->name,
                'code' => $this->shift?->code,
                'start_time' => $this->shift?->start_time,
                'end_time' => $this->shift?->end_time,
                'break_start' => $this->shift?->break_start,
                'break_end' => $this->shift?->break_end,
                'total_hours' => $this->shift?->total_hours,
                'color' => $this->shift?->color,
            ],
            'approval' => [
                'approved_by' => $this->approvedBy ? [
                    'id' => $this->approvedBy->id,
                    'name' => $this->approvedBy->fname . ' ' . $this->approvedBy->lname,
                    'role' => $this->approvedBy->role_name,
                ] : null,
                'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            ],
            'notes' => $this->notes,
            'timestamps' => [
                'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            ],
            'employee_details' => [
                'id' => $employee?->id,
                'full_name' => $employee?->fname . ' ' . $employee?->lname,
                'employee_number' => $employee?->employee_number,
                'phone' => $employee?->phone,
                'email' => $user?->email,
                'department' => $employee?->department,
                'role_name' => $user?->role_name,
                'hire_date' => $employee?->hire_date?->format('Y-m-d'),
                'employment_type' => $employee?->employment_type,
                'branch' => $user?->branch?->name ?? $employee?->branch?->name,
            ],
        ] : [];

        return array_merge($baseData, $tableData, $fullDetails);
    }

    /**
     * Get status color for badge
     */
    private function getStatusColor($status)
    {
        return match($status) {
            'present' => 'green',
            'late' => 'yellow',
            'absent' => 'red',
            'half_day' => 'orange',
            'on_leave' => 'purple',
            'holiday' => 'blue',
            default => 'gray',
        };
    }
}