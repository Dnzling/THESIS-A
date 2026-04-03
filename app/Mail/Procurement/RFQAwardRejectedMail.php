<?php

namespace App\Mail\Procurement;

use App\Models\Procurement\RFQ\RequestForQuotation;
use App\Models\Procurement\RFQ\SupplierQuotation;
use App\Models\Procurement\Supplier\Supplier;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RFQAwardRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RequestForQuotation $rfq,
        public Supplier $supplier,
        public SupplierQuotation $quotation,
        public string $portalUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Quotation Status Update - {$this->rfq->rfq_number}: {$this->rfq->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.rfq-award-rejected',
            with: [
                'rfq' => $this->rfq->load('items'),
                'supplier' => $this->supplier,
                'quotation' => $this->quotation,
                'portalUrl' => $this->portalUrl,
                'rfq_quotations_count' => $this->rfq->quotations()->count(),
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
