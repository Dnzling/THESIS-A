<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $userName;

    public function __construct($otp, $userName=null)
    {
        $this->otp = $otp;
        $this->userName = $userName;
    }

    public function build()
      {
        return $this->subject('Your OTP Code for ' . config('app.name'))
                    ->view('emails.otp-verification')
                    ->with([
                        'otp' => $this->otp,
                        'userName' => $this->userName,
                    ]);
    }
}