<table class="header">
    <tr>
        <td>
            <div class="brand">FURNISYNC</div>
            <div class="store-name">{{ $store?->name ?? 'Store' }}</div>
        </td>
        <td class="header-right">
            <div class="title">{{ $documentTitle }}</div>
            <div class="reference">{{ $reference }}</div>
            @if (!empty($status))
                <div class="meta">Status: {{ ucwords(str_replace('_', ' ', $status)) }}</div>
            @endif
            <div class="meta">Printed: {{ $printedAt->format('M d, Y h:i A') }}</div>
            <div class="meta">Printed by: {{ $printedBy }}</div>
        </td>
    </tr>
</table>
