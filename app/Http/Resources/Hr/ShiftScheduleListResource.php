<?php

namespace App\Http\Resources\Hr;

use Illuminate\Http\Resources\Json\JsonResource;

class ShiftScheduleListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'schedule_date' => $this->schedule_date->format('Y-m-d'),
            'status' => $this->status,
            'status_badge' => [
                'label' => ucfirst($this->status),
                'color' => $this->getStatusColor($this->status),
            ],
            'employee' => [
                'id' => $this->employee?->id,
                'full_name' => $this->employee?->fname . ' ' . $this->employee?->lname,
                'employee_number' => $this->employee?->employee_number,
                'department' => $this->employee?->department,
            ],
            'shift' => [
                'id' => $this->shift?->id,
                'name' => $this->shift?->name,
                'code' => $this->shift?->code,
                'start_time' => $this->shift?->start_time,
                'end_time' => $this->shift?->end_time,
                'color' => $this->shift?->color,
            ],
            'assigned_by' => $this->assignedBy ? [
                'id' => $this->assignedBy->id,
                'full_name' => $this->assignedBy->fname . ' ' . $this->assignedBy->lname,
            ] : null,
            'schedule_time' => $this->getScheduleTimeRange(),
        ];
    }

    private function getStatusColor($status)
    {
        return match($status) {
            'scheduled' => 'info',
            'completed' => 'success',
            'cancelled' => 'danger',
            'pending' => 'warning',
            default => 'secondary',
        };
    }

    private function getScheduleTimeRange()
    {
        if (!$this->shift) return null;
        
        $start = \Carbon\Carbon::parse($this->shift->start_time)->format('h:i A');
        $end = \Carbon\Carbon::parse($this->shift->end_time)->format('h:i A');
        
        return $start . ' - ' . $end;
    }
}