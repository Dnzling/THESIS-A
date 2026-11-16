<!-- RFQ Invitation Email Template -->
<x-mail::message>
# RFQ Invitation: {{ $rfq->rfq_number }}

Dear {{ $supplier->contact_person ?: $supplier->supplier_name }},

You have been invited to submit a quotation for our Request for Quotation (RFQ): **{{ $rfq->title }}**

## RFQ Details
- **RFQ Number**: {{ $rfq->rfq_number }}
- **Issue Date**: {{ $rfq->issue_date->format('F d, Y') }}
- **Currency**: {{ $rfq->currency }}

## Items Requested
| Item | Quantity | Unit |
|------|----------|------|
@foreach($rfq->items as $item)
| {{ $item->product?->product_name ?? 'Product' }}{{ $item->variation?->variation_name ? ' - ' . $item->variation->variation_name : '' }} | {{ $item->quantity }} | {{ $item->variation?->unit_of_measurement ?: $item->product?->unit_of_measurement ?: '-' }} |
@endforeach

## Requirements
@if($rfq->instructions)
**Submission Instructions**: {{ $rfq->instructions }}
@endif

@if($rfq->qualification_requirements)
**Qualification Requirements**: {{ $rfq->qualification_requirements }}
@endif

## Next Steps
Please review the RFQ details and submit your quotation at your earliest convenience.

<x-mail::button :url="$portalUrl">
Submit Quotation
</x-mail::button>

If you have any questions or concerns, please don't hesitate to contact us.

Best regards,  
{{ config('app.name') }} - Procurement Team
</x-mail::message>
