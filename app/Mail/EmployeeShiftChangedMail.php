<?php

namespace App\Mail;

use App\Models\Hr\Employee;
use App\Models\Hr\Shift;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmployeeShiftChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Employee $employee,
        public Shift $shift,
        public string $reason,
        public string $effectiveDate
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Your Shift Assignment Has Been Updated')
            ->view('emails.hr.employee-shift-changed');
    }
}

