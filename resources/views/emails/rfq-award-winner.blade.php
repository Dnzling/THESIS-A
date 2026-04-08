<!-- RFQ Award Notification - Winner -->
<x-mail::message>
# Quotation Accepted: {{ $rfq->rfq_number }}

Dear {{ $supplier->contact_name ?? $supplier->name }},

We are pleased to inform you that your quotation for RFQ **{{ $rfq->rfq_number }}** has been **ACCEPTED**.

## Award Details
- **RFQ Number**: {{ $rfq->rfq_number }}
- **RFQ Title**: {{ $rfq->title }}
- **Award Date**: {{ now()->format('F d, Y') }}
- **Your Quotation Number**: {{ $quotation->quotation_number }}

## Accepted Terms
- **Total Price**: {{ $rfq->currency }} {{ number_format($quotation->total_price, 2) }}
- **Delivery Days**: {{ $quotation->delivery_days }} days
- **Payment Terms**: {{ $quotation->payment_terms ?? 'Standard' }}
- **Validity**: Valid until {{ $quotation->validity_date->format('F d, Y') }}

## Line Items
| Item | Quantity | Unit | Unit Price | Total |
|------|----------|------|-----------|-------|
@foreach($rfq->items as $item)
| {{ $item->product?->name ?? 'Product' }} | {{ $item->quantity }} | {{ $item->unit }} | {{ $rfq->currency }} - | {{ $rfq->currency }} - |
@endforeach

## Next Steps
A Purchase Order will be generated shortly and sent to you for processing. Please review all details and confirm receipt.

<x-mail::button :url="$portalUrl">
View Purchase Order
</x-mail::button>

Thank you for your competitive quotation. We look forward to working with you.

Best regards,  
{{ config('app.name') }} - Procurement Team
</x-mail::message>
