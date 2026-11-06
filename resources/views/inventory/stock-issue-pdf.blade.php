<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Stock Issue {{ $document->issue_number }}</title>@include('inventory.print.styles')</head><body>
    @php($documentTitle = 'Stock Issuance Slip')
    @php($reference = $document->issue_number)
    @php($status = $document->status)
    @include('inventory.print.header')
    <div class="section"><h2 class="section-title">Issuance Information</h2><table class="details"><tr>
        <td><span class="label">Branch</span><span class="value">{{ $document->branch?->name ?? '-' }}</span></td>
        <td><span class="label">Issue Date</span><span class="value">{{ optional($document->issue_date)->format('M d, Y') ?? '-' }}</span></td>
    </tr><tr>
        <td><span class="label">Reason</span><span class="value">{{ ucwords(str_replace('_', ' ', $document->issue_type ?? '-')) }}</span>@if($document->description)<br>{{ $document->description }}@endif</td>
        <td><span class="label">Total Value</span><span class="value">₱{{ number_format((float)$document->total_value, 2) }}</span></td>
    </tr></table></div>
    <div class="section"><h2 class="section-title">Issued Items</h2><table class="items"><thead><tr><th>Product / SKU</th><th class="center">Quantity</th><th class="num">Unit Cost</th><th class="num">Total Value</th><th>Reason / Notes</th></tr></thead><tbody>
        @forelse($document->items as $item)<tr><td><div class="product">{{ $item->inventoryItem?->product?->product_name ?? 'Unknown Product' }}</div><div class="sku">SKU: {{ $item->inventoryItem?->variation?->variation_sku ?? $item->inventoryItem?->product?->sku ?? '-' }}</div></td><td class="center">{{ number_format((int)$item->quantity) }}</td><td class="num">₱{{ number_format((float)$item->unit_cost, 2) }}</td><td class="num">₱{{ number_format((float)$item->total_value, 2) }}</td><td>{{ $item->reason ?: $item->remarks ?: '-' }}</td></tr>@empty<tr><td colspan="5" class="center muted">No items recorded.</td></tr>@endforelse
    </tbody></table></div>
    @if($document->remarks)<div class="section"><h2 class="section-title">Additional Notes</h2><div class="notes">{{ $document->remarks }}</div></div>@endif
    <table class="signatures"><tr><td><div class="signature-line"><span class="label">Requested By</span>{{ $document->requester?->fname }} {{ $document->requester?->lname }}</div></td><td><div class="signature-line"><span class="label">Approved By</span>{{ $document->approver?->fname }} {{ $document->approver?->lname }}</div></td><td><div class="signature-line"><span class="label">Issued By</span>{{ $document->issuer?->fname }} {{ $document->issuer?->lname }}</div></td></tr></table>
    @include('inventory.print.footer')
</body></html>
