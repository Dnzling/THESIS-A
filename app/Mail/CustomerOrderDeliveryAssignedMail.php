<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerOrderDeliveryAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $assignment)
    {
    }

    public function build(): self
    {
        return $this
            ->subject("Delivery arranged for order {$this->assignment['order_number']}")
            ->view('emails.sales.delivery-assigned');
    }
}
