<?php

namespace App\Mail\Procurement;

use App\Models\Procurement\RFQ\RequestForQuotation;
use App\Models\Procurement\Supplier\Supplier;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExternalRFQInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RequestForQuotation $rfq,
        public Supplier $supplier,
        public ?string $replyAddress = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "RFQ Invitation - {$this->rfq->rfq_number}: {$this->rfq->title}",
            replyTo: $this->replyAddress ? [new Address($this->replyAddress, 'FurniSync RFQ Replies')] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.external-rfq-invitation',
            with: ['rfq' => $this->rfq, 'supplier' => $this->supplier],
        );
    }
}
