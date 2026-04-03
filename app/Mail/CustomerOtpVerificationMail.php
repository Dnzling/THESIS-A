<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerOtpVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $userName;

    public function __construct($otp, $userName = null)
    {
        $this->otp = $otp;
        $this->userName = $userName;
    }

    public function build()
    {
        return $this
            ->from(config('mail.from.address'), 'Furnisync Shop')
            ->subject('Furnisync Shop Account Verification OTP')
            ->view('emails.customer-otp-verification')
            ->with([
                'otp' => $this->otp,
                'userName' => $this->userName,
            ]);
    }
}
