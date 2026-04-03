<!-- Quotation Received Notification -->
<x-mail::message>
# Quotation Received: {{ $rfq->rfq_number }}

Hello,

A quotation has been received for RFQ **{{ $rfq->rfq_number }}**.

## RFQ Information
- **RFQ Number**: {{ $rfq->rfq_number }}
- **Title**: {{ $rfq->title }}
- **Supplier**: {{ $supplier->name }}
- **Quotation Number**: {{ $quotation->quotation_number }}

## Quotation Details
- **Submitted Date**: {{ $quotation->submitted_date->format('F d, Y H:i') }}
- **Total Price**: {{ $rfq->currency }} {{ number_format($quotation->total_price, 2) }}
- **Delivery Days**: {{ $quotation->delivery_days }}
- **Payment Terms**: {{ $quotation->payment_terms ?? 'Standard' }}
- **Validity**: Until {{ $quotation->validity_date->format('F d, Y') }}

## Quotation Status
- **Items Quoted**: {{ $rfq->items_count }}
- **Status**: SUBMITTED

## Message from Supplier
@if($quotation->notes)
{{ $quotation->notes }}
@else
No additional notes provided.
@endif

## Next Action
Review this quotation and any others received. When all quotations are in, proceed with comparison and evaluation.

<x-mail::button :url="$portalUrl">
Review Quotation
</x-mail::button>

---
This is an automated notification. Please do not reply to this email.

Best regards,  
{{ config('app.name') }} - Procurement System
</x-mail::message>
