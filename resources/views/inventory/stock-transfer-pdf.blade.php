<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Stock Transfer {{ $document->transfer_number }}</title>@include('inventory.print.styles')</head><body>
    @php($documentTitle = 'Stock Transfer Slip')
    @php($reference = $document->transfer_number)
    @php($status = $document->status)
    @include('inventory.print.header')
    <div class="section"><h2 class="section-title">Transfer Information</h2><table class="details"><tr>
        <td><span class="label">Sending Branch</span><span class="value">{{ $document->fromBranch?->name ?? '-' }}</span><br>{{ $document->fromBranch?->address ?? '' }}</td>
        <td><span class="label">Receiving Branch</span><span class="value">{{ $document->toBranch?->name ?? '-' }}</span><br>{{ $document->toBranch?->address ?? '' }}</td>
    </tr><tr>
        <td><span class="label">Requested Date</span><span class="value">{{ optional($document->requested_date)->format('M d, Y') ?? '-' }}</span></td>
        <td><span class="label">Expected Delivery</span><span class="value">{{ optional($document->expected_delivery_date)->format('M d, Y') ?? '-' }}</span></td>
    </tr><tr>
        <td><span class="label">Reason</span><span class="value">{{ $document->reason ?: '-' }}</span></td>
        <td><span class="label">Tracking</span><span class="value">{{ $document->tracking_number ?: '-' }}</span></td>
    </tr></table></div>
    <div class="section"><h2 class="section-title">Transfer Items</h2><table class="items"><thead><tr><th>Product / SKU</th><th class="center">Requested</th><th class="center">Approved</th><th class="center">Shipped</th><th class="center">Received</th><th class="num">Unit Value</th></tr></thead><tbody>
        @forelse($document->items as $item)<tr><td><div class="product">{{ $item->product?->product_name ?? 'Unknown Product' }}</div><div class="sku">SKU: {{ $item->variation?->variation_sku ?? $item->product?->sku ?? '-' }}</div>@if($item->variation?->variation_name)<div class="sku">Variant: {{ $item->variation->variation_name }}</div>@endif</td><td class="center">{{ number_format((int)$item->requested_quantity) }}</td><td class="center">{{ number_format((int)$item->approved_quantity) }}</td><td class="center">{{ number_format((int)$item->shipped_quantity) }}</td><td class="center">{{ number_format((int)$item->received_quantity) }}</td><td class="num">₱{{ number_format((float)$item->unit_value, 2) }}</td></tr>@empty<tr><td colspan="6" class="center muted">No items recorded.</td></tr>@endforelse
    </tbody></table></div>
    @if($document->notes)<div class="section"><h2 class="section-title">Notes</h2><div class="notes">{{ $document->notes }}</div></div>@endif
    <table class="signatures"><tr><td><div class="signature-line"><span class="label">Requested By</span>{{ trim(($document->requestedBy?->user?->fname ?? '').' '.($document->requestedBy?->user?->lname ?? '')) ?: '-' }}</div></td><td><div class="signature-line"><span class="label">Dispatched By</span>{{ trim(($document->shippedBy?->user?->fname ?? '').' '.($document->shippedBy?->user?->lname ?? '')) ?: '-' }}</div></td><td><div class="signature-line"><span class="label">Received By</span>{{ trim(($document->receivedBy?->user?->fname ?? '').' '.($document->receivedBy?->user?->lname ?? '')) ?: '-' }}</div></td></tr></table>
    @include('inventory.print.footer')
</body></html>
