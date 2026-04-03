<?php

namespace App\Mail;

use App\Models\Hr\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApplicantEmployeeCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Employee $employee,
        public string $temporaryPassword
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Your Employee Account Credentials')
            ->view('emails.hr.applicant-employee-credentials');
    }
}
