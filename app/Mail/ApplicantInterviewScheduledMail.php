<?php

namespace App\Mail;

use App\Models\Interview;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApplicantInterviewScheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Interview $interview, public bool $updated = false)
    {
    }

    public function build(): self
    {
        return $this
            ->subject(($this->updated ? 'Updated Interview Schedule - ' : 'Interview Schedule - ') . config('app.name'))
            ->view('emails.hr.applicant-interview-scheduled');
    }
}
