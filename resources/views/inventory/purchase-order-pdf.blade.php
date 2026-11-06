<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Purchase Order {{ $document->po_number }}</title>
    @include('inventory.print.styles')
</head>
<body>
    @php($documentTitle = 'Purchase Order')
    @php($reference = $document->po_number)
    @php($status = $document->status)
    @include('inventory.print.header')

    <div class="section">
        <h2 class="section-title">Supplier and Delivery</h2>
        <table class="details"><tr>
            <td><span class="label">Supplier</span><span class="value">{{ $document->supplier?->supplier_name ?? '-' }}</span><br>{{ $document->supplier?->contact_person ?? '-' }}<br>{{ $document->supplier?->email ?? '-' }} · {{ $document->supplier?->phone ?? '-' }}</td>
            <td><span class="label">Receiving Branch</span><span class="value">{{ $document->branch?->name ?? '-' }}</span><br>{{ $document->branch?->address ?? '' }}</td>
        </tr><tr>
            <td><span class="label">Order Date</span><span class="value">{{ optional($document->order_date)->format('M d, Y') ?? '-' }}</span></td>
            <td><span class="label">Expected Delivery</span><span class="value">{{ optional($document->expected_delivery_date)->format('M d, Y') ?? '-' }}</span></td>
        </tr><tr>
            <td><span class="label">Fulfillment</span><span class="value">{{ ucwords(str_replace('_', ' ', $document->fulfillment_method ?? '-')) }}</span></td>
            <td><span class="label">Payment Terms</span><span class="value">{{ ucwords(str_replace('_', ' ', $document->payment_terms ?? '-')) }}</span></td>
        </tr></table>
    </div>

    <div class="section">
        <h2 class="section-title">Ordered Items</h2>
        <table class="items">
            <thead><tr><th>Product / SKU</th><th class="center">Qty</th><th class="num">Unit Cost</th><th class="center">Discount</th><th class="center">Tax</th><th class="num">Line Total</th></tr></thead>
            <tbody>
            @forelse ($document->items as $item)
                <tr>
                    <td><div class="product">{{ $item->product?->product_name ?? 'Unknown Product' }}</div><div class="sku">SKU: {{ $item->variation?->variation_sku ?? $item->product?->sku ?? '-' }}</div>@if($item->variation?->variation_name)<div class="sku">Variant: {{ $item->variation->variation_name }}</div>@endif</td>
                    <td class="center">{{ number_format((int) $item->quantity_ordered) }}</td>
                    <td class="num">₱{{ number_format((float) $item->unit_cost, 2) }}</td>
                    <td class="center">{{ number_format((float) ($item->discount_percent ?? 0), 2) }}%</td>
                    <td class="center">{{ number_format((float) ($item->tax_rate ?? 0), 2) }}%</td>
                    <td class="num">₱{{ number_format((float) $item->line_total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="center muted">No items recorded.</td></tr>
            @endforelse
            </tbody>
        </table>
        <table class="summary">
            <tr><td>Subtotal</td><td class="num">₱{{ number_format((float) $document->subtotal, 2) }}</td></tr>
            <tr><td>Discount</td><td class="num">- ₱{{ number_format((float) $document->discount_amount, 2) }}</td></tr>
            <tr><td>Tax</td><td class="num">₱{{ number_format((float) $document->tax_amount, 2) }}</td></tr>
            <tr><td>Shipping</td><td class="num">₱{{ number_format((float) $document->shipping_cost, 2) }}</td></tr>
            <tr><td>Total Amount</td><td class="num">₱{{ number_format((float) $document->total_amount, 2) }}</td></tr>
        </table>
    </div>

    @if($document->notes || $document->terms_conditions)
        <div class="section"><h2 class="section-title">Notes and Terms</h2>
            @if($document->notes)<p><strong>Notes</strong><br>{{ $document->notes }}</p>@endif
            @if($document->terms_conditions)<div class="notes">{{ $document->terms_conditions }}</div>@endif
        </div>
    @endif
    <table class="signatures"><tr>
        <td><div class="signature-line"><span class="label">Prepared by</span><strong>{{ trim(($document->createdBy?->user?->fname ?? '').' '.($document->createdBy?->user?->lname ?? '')) ?: '-' }}</strong></div></td>
        <td><div class="signature-line"><span class="label">Supplier confirmation</span></div></td>
    </tr></table>
    @include('inventory.print.footer')
</body>
</html>
