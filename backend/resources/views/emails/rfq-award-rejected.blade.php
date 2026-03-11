<!-- RFQ Award Notification - Rejected -->
<x-mail::message>
# Quotation Status Update: {{ $rfq->rfq_number }}

Dear {{ $supplier->contact_name ?? $supplier->name }},

Thank you for submitting your quotation for RFQ **{{ $rfq->rfq_number }}**: {{ $rfq->title }}.

## Quotation Status
- **RFQ Number**: {{ $rfq->rfq_number }}
- **Your Quotation Number**: {{ $quotation->quotation_number }}
- **Status**: **NOT SELECTED**

We received {{ $rfq->quotations_count }} qualified quotations for this RFQ. After careful evaluation based on our selection criteria (price, delivery time, quality, and payment terms), we have selected another supplier for this purchase.

## Your Quotation Summary
- **Submitted Price**: {{ $rfq->currency }} {{ number_format($quotation->total_price, 2) }}
- **Proposed Delivery**: {{ $quotation->delivery_days }} days

## Evaluation Feedback
@if($rfq->evaluation_notes)
{{ $rfq->evaluation_notes }}
@else
We appreciate your competitive offer and would like to keep you in mind for future procurement opportunities.
@endif

## Next Opportunity
We value our relationship with your company and would like to continue doing business with you. Please stay tuned for future RFQs and opportunities.

<x-mail::button :url="$portalUrl">
View RFQ Details
</x-mail::button>

We encourage you to participate in future procurement activities. If you have any questions about this decision, please contact us.

Best regards,  
{{ config('app.name') }} - Procurement Team
</x-mail::message>
