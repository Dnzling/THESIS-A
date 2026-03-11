<?php

namespace App\Mail\Procurement;

use App\Models\Procurement\RFQ\RequestForQuotation;
use App\Models\Procurement\Supplier\Supplier;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RFQInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RequestForQuotation $rfq,
        public Supplier $supplier,
        public string $portalUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "RFQ Invitation - {$this->rfq->rfq_number}: {$this->rfq->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.rfq-invitation',
            with: [
                'rfq' => $this->rfq,
                'supplier' => $this->supplier,
                'portalUrl' => $this->portalUrl,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
