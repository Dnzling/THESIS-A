<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Stock Return {{ $document->return_number }}</title>@include('inventory.print.styles')</head><body>
    @php($documentTitle = 'Stock Return Note')
    @php($reference = $document->return_number)
    @php($status = $document->status)
    @include('inventory.print.header')
    <div class="section"><h2 class="section-title">Return Information</h2><table class="details"><tr>
        <td><span class="label">Return Type</span><span class="value">{{ ucwords(str_replace('_', ' ', $document->return_type ?? '-')) }}</span></td>
        <td><span class="label">Requested Date</span><span class="value">{{ optional($document->requested_date)->format('M d, Y') ?? '-' }}</span></td>
    </tr><tr>
        <td><span class="label">From Branch</span><span class="value">{{ $document->fromBranch?->name ?? '-' }}</span></td>
        <td><span class="label">Destination</span><span class="value">{{ $document->supplier?->supplier_name ?? $document->toBranch?->name ?? '-' }}</span></td>
    </tr><tr>
        <td><span class="label">Return Reason</span><span class="value">{{ ucwords(str_replace('_', ' ', $document->return_reason ?? '-')) }}</span>@if($document->reason_details)<br>{{ $document->reason_details }}@endif</td>
        <td><span class="label">Total Return Value</span><span class="value">₱{{ number_format((float)$document->total_value, 2) }}</span></td>
    </tr></table></div>
    <div class="section"><h2 class="section-title">Returned Items</h2><table class="items"><thead><tr><th>Product / SKU</th><th class="center">Quantity</th><th>Condition</th><th class="num">Unit Value</th><th class="num">Total Value</th><th>Notes</th></tr></thead><tbody>
        @forelse($document->items as $item)<tr><td><div class="product">{{ $item->product?->product_name ?? 'Unknown Product' }}</div><div class="sku">SKU: {{ $item->variation?->variation_sku ?? $item->product?->sku ?? '-' }}</div>@if($item->variation?->variation_name)<div class="sku">Variant: {{ $item->variation->variation_name }}</div>@endif</td><td class="center">{{ number_format((int)$item->quantity_returned) }}</td><td>{{ ucwords(str_replace('_', ' ', $item->condition ?? '-')) }}</td><td class="num">₱{{ number_format((float)($item->unit_value ?? $item->unit_cost), 2) }}</td><td class="num">₱{{ number_format((float)($item->total_value ?? $item->total_cost), 2) }}</td><td>{{ $item->notes ?: '-' }}</td></tr>@empty<tr><td colspan="6" class="center muted">No items recorded.</td></tr>@endforelse
    </tbody></table></div>
    @if($document->notes)<div class="section"><h2 class="section-title">Additional Notes</h2><div class="notes">{{ $document->notes }}</div></div>@endif
    <table class="signatures"><tr><td><div class="signature-line"><span class="label">Requested By</span>{{ trim(($document->requestedBy?->fname ?? '').' '.($document->requestedBy?->lname ?? '')) ?: '-' }}</div></td><td><div class="signature-line"><span class="label">Approved By</span>{{ trim(($document->approvedBy?->fname ?? '').' '.($document->approvedBy?->lname ?? '')) ?: '-' }}</div></td><td><div class="signature-line"><span class="label">Received By</span>{{ trim(($document->receivedBy?->fname ?? '').' '.($document->receivedBy?->lname ?? '')) ?: '-' }}</div></td></tr></table>
    @include('inventory.print.footer')
</body></html>
