<?php

namespace App\Http\Resources\Hr;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class LeaveResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // Determine if we need full details or just basic view
        $includeFullDetails = $request->has('with_details') || $request->is('api/hr/leaves/*');
        
        // Base data for both index and show
        $data = [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'leave_type' => $this->leave_type,
            'leave_type_label' => $this->getLeaveTypeLabel($this->leave_type),
            'start_date' => $this->start_date ? Carbon::parse($this->start_date)->format('Y-m-d') : null,
            'end_date' => $this->end_date ? Carbon::parse($this->end_date)->format('Y-m-d') : null,
            'total_days' => $this->total_days,
            'reason' => $this->reason,
            'status' => $this->status,
            'status_badge' => [
                'label' => ucfirst($this->status),
                'color' => $this->getStatusColor($this->status),
            ],
            'date_range' => $this->getDateRange(),
            'duration' => $this->getDurationText(),
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->format('Y-m-d H:i:s') : null,
        ];

        // Employee summary (always included)
        $data['employee'] = [
            'id' => $this->employee?->id,
            'full_name' => $this->employee?->fname . ' ' . $this->employee?->lname,
            'employee_number' => $this->employee?->employee_number,
            'department' => $this->employee?->department,
            'branch' => $this->employee?->branch?->name ?? null,
            'avatar' => $this->getInitials($this->employee?->fname, $this->employee?->lname),
        ];

        // Approved by summary (if approved)
        if ($this->approved_by) {
            $data['approved_by'] = [
                'id' => $this->approvedBy?->id,
                'name' => $this->approvedBy?->fname . ' ' . $this->approvedBy?->lname,
                'role' => $this->approvedBy?->role_name,
            ];
            $data['approved_at'] = $this->approved_at ? Carbon::parse($this->approved_at)->format('Y-m-d H:i:s') : null;
        }

        // Rejection reason (if rejected)
        if ($this->rejected_reason) {
            $data['rejected_reason'] = $this->rejected_reason;
        }

        // Attachment (if exists)
        if ($this->attachment_path) {
            $data['attachment'] = [
                'url' => asset('storage/' . $this->attachment_path),
                'name' => basename($this->attachment_path),
            ];
        }

        // Full details for show view
        if ($includeFullDetails) {
            $data['full_details'] = [
                'employee_details' => [
                    'id' => $this->employee?->id,
                    'full_name' => $this->employee?->fname . ' ' . $this->employee?->lname,
                    'employee_number' => $this->employee?->employee_number,
                    'email' => $this->employee?->user?->email,
                    'phone' => $this->employee?->phone,
                    'department' => $this->employee?->department,
                    'position' => $this->employee?->user?->role_name,
                    'hire_date' => $this->employee?->hire_date ? Carbon::parse($this->employee?->hire_date)->format('Y-m-d') : null,
                    'employment_type' => $this->employee?->employment_type,
                    'branch' => $this->employee?->branch?->name ?? null,
                ],
                'leave_details' => [
                    'leave_type' => $this->leave_type,
                    'leave_type_label' => $this->getLeaveTypeLabel($this->leave_type),
                    'start_date_formatted' => $this->start_date ? Carbon::parse($this->start_date)->format('F j, Y') : null,
                    'end_date_formatted' => $this->end_date ? Carbon::parse($this->end_date)->format('F j, Y') : null,
                    'total_days' => $this->total_days,
                    'duration_text' => $this->getDurationText(),
                    'reason' => $this->reason,
                ],
                'timestamps' => [
                    'created_at' => $this->created_at ? Carbon::parse($this->created_at)->format('Y-m-d H:i:s') : null,
                    'created_at_formatted' => $this->created_at ? Carbon::parse($this->created_at)->format('F j, Y g:i A') : null,
                    'updated_at' => $this->updated_at ? Carbon::parse($this->updated_at)->format('Y-m-d H:i:s') : null,
                    'updated_at_formatted' => $this->updated_at ? Carbon::parse($this->updated_at)->format('F j, Y g:i A') : null,
                ],
            ];

            // Approval history
            if ($this->approved_by) {
                $data['full_details']['approval_history'] = [
                    'approved_by' => [
                        'id' => $this->approvedBy?->id,
                        'name' => $this->approvedBy?->fname . ' ' . $this->approvedBy?->lname,
                        'email' => $this->approvedBy?->email,
                        'role' => $this->approvedBy?->role_name,
                    ],
                    'approved_at' => $this->approved_at ? Carbon::parse($this->approved_at)->format('Y-m-d H:i:s') : null,
                    'approved_at_formatted' => $this->approved_at ? Carbon::parse($this->approved_at)->format('F j, Y g:i A') : null,
                ];
            }
        }

        return $data;
    }

    /**
     * Get leave type label
     */
    private function getLeaveTypeLabel($type)
    {
        $labels = [
            'sick' => 'Sick Leave',
            'vacation' => 'Vacation Leave',
            'personal' => 'Personal Leave',
            'maternity' => 'Maternity Leave',
            'paternity' => 'Paternity Leave',
            'bereavement' => 'Bereavement Leave',
            'others' => 'Others',
        ];

        return $labels[$type] ?? ucfirst($type);
    }

    /**
     * Get status color for badge
     */
    private function getStatusColor($status)
    {
        return match($status) {
            'approved' => 'success',
            'pending' => 'warning',
            'rejected' => 'danger',
            'cancelled' => 'secondary',
            default => 'info',
        };
    }

    /**
     * Get formatted date range
     */
    private function getDateRange()
    {
        if (!$this->start_date || !$this->end_date) {
            return null;
        }

        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);

        if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
            return $start->format('M j, Y');
        }

        if ($start->format('Y') === $end->format('Y')) {
            if ($start->format('M') === $end->format('M')) {
                return $start->format('M j') . ' - ' . $end->format('j, Y');
            }
            return $start->format('M j') . ' - ' . $end->format('M j, Y');
        }

        return $start->format('M j, Y') . ' - ' . $end->format('M j, Y');
    }

    /**
     * Get duration text
     */
    private function getDurationText()
    {
        if (!$this->total_days) {
            return null;
        }

        if ($this->total_days === 1) {
            return '1 day';
        }

        return $this->total_days . ' days';
    }

    /**
     * Get initials for avatar
     */
    private function getInitials($fname, $lname)
    {
        if (!$fname && !$lname) {
            return '?';
        }
        
        $first = $fname ? substr($fname, 0, 1) : '';
        $last = $lname ? substr($lname, 0, 1) : '';
        
        return strtoupper($first . $last);
    }
}