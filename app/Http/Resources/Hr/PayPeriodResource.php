<?php

namespace App\Http\Resources\Hr;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class PayPeriodResource extends JsonResource
{
    public function toArray($request)
    {
        $startDate = Carbon::parse($this->start_date);
        $endDate = Carbon::parse($this->end_date);
        
        // Format period name
        if ($startDate->format('F Y') === $endDate->format('F Y')) {
            $month = $startDate->format('F Y');
            $half = $startDate->day <= 15 ? '1st Half' : '2nd Half';
            $period = $month . ' (' . $half . ')';
        } else {
            $period = $startDate->format('F') . ' - ' . $endDate->format('F Y');
        }

        return [
            'id' => $this->id,
            'period' => $period,
            'cutoffStart' => $startDate->format('M j, Y'),
            'cutoffEnd' => $endDate->format('M j, Y'),
            'payDate' => Carbon::parse($this->cutoff_date)->format('M j, Y'),
            'status' => ucfirst($this->status),
            'notes' => $this->notes,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at
        ];
    }
}