<?php

namespace App\Http\Resources\Hr;

use Illuminate\Http\Resources\Json\JsonResource;

class PayrollIndexResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'pay_period_id' => $this->pay_period_id,
            'net_salary' => $this->net_salary,
            'status' => $this->status,
            'payment_date' => $this->payment_date,
            'created_at' => $this->created_at,
            'base_salary' => $this->base_salary,
            'overtime_hours' => $this->overtime_hours,
            'overtime_amount' => $this->overtime_amount,
            'deductions_total' => $this->deductions_total,
            'bonuses_total' => $this->bonuses_total,
            'allowances_total' => $this->allowances_total,
            'tax_amount' => $this->tax_amount,
            
            'employee' => [
                'id' => $this->employee->id,
                'fname' => $this->employee->fname,
                'lname' => $this->employee->lname,
                'employee_number' => $this->employee->employee_number,
                'department' => $this->employee->department,
                'branch' => $this->employee->branch->name
            ],
            
            'pay_period' => [
                'id' => $this->payPeriod->id,
                'name' => $this->payPeriod->name,
            ],
            
            // Add only if explicitly requested
            'approved_by' => $this->when($request->boolean('include_details'), [
                'id' => $this->approvedBy?->id,
                'name' => $this->approvedBy?->full_name,
            ]),
        ];
    }
}