<x-mail::message>
# Request for Quotation: {{ $rfq->rfq_number }}

Dear {{ $supplier->contact_person ?: $supplier->supplier_name }},

{{ $rfq->store?->name ?: config('app.name') }} invites you to submit a quotation for **{{ $rfq->title }}**.

**Issue date:** {{ $rfq->issue_date?->format('F d, Y') }}  
**Response deadline:** {{ $rfq->deadline_date?->format('F d, Y') ?: 'Please reply at your earliest convenience' }}  
**Currency:** {{ $rfq->currency }}

## Requested items

| Product | Quantity | Unit |
|:--|--:|:--|
@foreach($rfq->items as $item)
| {{ $item->product?->product_name ?: 'Product' }}{{ $item->variation?->variation_name ? ' - ' . $item->variation->variation_name : '' }} | {{ $item->quantity }} | {{ $item->variation?->unit_of_measurement ?: $item->product?->unit_of_measurement ?: '-' }} |
@endforeach

@if($rfq->description)
## Details
{{ $rfq->description }}
@endif

@if($rfq->instructions)
## Instructions
{{ $rfq->instructions }}
@endif

Reply directly to this email with your quotation and any supporting files. Please include **{{ $rfq->rfq_number }}** in your reply so we can match it to this request.

Regards,  
{{ config('app.name') }} Procurement
</x-mail::message>
