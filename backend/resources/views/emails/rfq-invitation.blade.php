<!-- RFQ Invitation Email Template -->
<x-mail::message>
# RFQ Invitation: {{ $rfq->rfq_number }}

Dear {{ $supplier->contact_name ?? $supplier->name }},

You have been invited to submit a quotation for our Request for Quotation (RFQ): **{{ $rfq->title }}**

## RFQ Details
- **RFQ Number**: {{ $rfq->rfq_number }}
- **Issue Date**: {{ $rfq->issue_date->format('F d, Y') }}
- **Deadline**: {{ $rfq->deadline_date->format('F d, Y') }}
- **Currency**: {{ $rfq->currency }}
- **Payment Terms**: {{ str_replace('_', ' ', ucwords($rfq->payment_terms)) }}

## Items Requested
| Item | Quantity | Unit | Target Price |
|------|----------|------|--------------|
@foreach($rfq->items as $item)
| {{ $item->product?->name ?? 'Product' }} | {{ $item->quantity }} | {{ $item->unit }} | {{ $rfq->currency }} {{ number_format($item->target_price ?? 0, 2) }} |
@endforeach

## Requirements
@if($rfq->instructions)
**Submission Instructions**: {{ $rfq->instructions }}
@endif

@if($rfq->qualification_requirements)
**Qualification Requirements**: {{ $rfq->qualification_requirements }}
@endif

## Next Steps
Please review the RFQ details and submit your quotation before the deadline.

<x-mail::button :url="$portalUrl">
Submit Quotation
</x-mail::button>

If you have any questions or concerns, please don't hesitate to contact us.

Best regards,  
{{ config('app.name') }} - Procurement Team
</x-mail::message>
