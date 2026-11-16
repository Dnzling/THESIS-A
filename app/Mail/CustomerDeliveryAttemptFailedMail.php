<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerDeliveryAttemptFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $attempt)
    {
    }

    public function build(): self
    {
        return $this
            ->subject("Delivery update for order {$this->attempt['order_number']}")
            ->view('emails.sales.delivery-attempt-failed');
    }
}
