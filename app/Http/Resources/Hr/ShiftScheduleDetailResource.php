<?php

namespace App\Http\Resources\Hr;

use Illuminate\Http\Resources\Json\JsonResource;

class ShiftScheduleDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'schedule_date' => $this->schedule_date->format('Y-m-d'),
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            
            'employee' => [
                'id' => $this->employee?->id,
                'full_name' => $this->employee?->fname . ' ' . $this->employee?->lname,
                'employee_number' => $this->employee?->employee_number,
                'department' => $this->employee?->department,
                'position' => $this->employee?->role?->name ?? $this->employee?->user?->role_name,
                'phone' => $this->employee?->phone,
                'email' => $this->employee?->user?->email,
                'branch' => $this->employee?->branch?->name ?? $this->employee?->user?->branch?->name,
                'hire_date' => $this->employee?->hire_date?->format('Y-m-d'),
                'employment_type' => $this->employee?->employment_type,
                'status' => $this->employee?->status,
            ],
            
            'shift' => [
                'id' => $this->shift?->id,
                'name' => $this->shift?->name,
                'code' => $this->shift?->code,
                'start_time' => $this->shift?->start_time,
                'end_time' => $this->shift?->end_time,
                'break_start' => $this->shift?->break_start,
                'break_end' => $this->shift?->break_end,
                'total_hours' => $this->shift?->total_hours,
                'color' => $this->shift?->color,
                'description' => $this->shift?->description,
            ],
            
            'assigned_by' => $this->assignedBy ? [
                'id' => $this->assignedBy->id,
                'full_name' => $this->assignedBy->fname . ' ' . $this->assignedBy->lname,
                'email' => $this->assignedBy->email,
                'role' => $this->assignedBy->role_name,
            ] : null,
            
            'attendance' => $this->whenLoaded('attendance', function() {
                return $this->attendance ? [
                    'id' => $this->attendance->id,
                    'clock_in' => $this->attendance->clock_in?->format('h:i A'),
                    'clock_out' => $this->attendance->clock_out?->format('h:i A'),
                    'clock_in_location' => $this->attendance->clock_in_location,
                    'clock_out_location' => $this->attendance->clock_out_location,
                    'status' => $this->attendance->status,
                    'total_worked' => $this->attendance->total_worked_minutes ? 
                        floor($this->attendance->total_worked_minutes / 60) . 'h ' . 
                        ($this->attendance->total_worked_minutes % 60) . 'm' : null,
                ] : null;
            }),
            
            'schedule_time_range' => $this->getScheduleTimeRange(),
            'duration' => $this->shift?->total_hours . ' hours',
        ];
    }

    private function getScheduleTimeRange()
    {
        if (!$this->shift) return null;
        
        $start = \Carbon\Carbon::parse($this->shift->start_time)->format('h:i A');
        $end = \Carbon\Carbon::parse($this->shift->end_time)->format('h:i A');
        
        return $start . ' - ' . $end;
    }
}