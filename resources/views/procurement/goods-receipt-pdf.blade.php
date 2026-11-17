<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Goods Receipt {{ $grn_number }}</title>
    <style>
        @page { margin: 28px 34px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #172033; font-family: Arial, sans-serif; font-size: 10px; line-height: 1.45; }
        .brand { color: #f26716; font-family: Arial, sans-serif; font-size: 20px; font-weight: 700; letter-spacing: .5px; }
        .store-name { color: #334155; font-size: 10px; font-weight: 700; margin-top: 4px; }
        .document-title { color: #172033; font-size: 19px; font-weight: 700; }
        .muted { color: #64748b; }
        .small { font-size: 8px; }
        .header-table, .details-table, .section-table, .items-table, .footer-table { width: 100%; border-collapse: collapse; }
        .header-table { border-bottom: 2px solid #f26716; margin-bottom: 16px; }
        .header-table td { padding: 0 0 12px; vertical-align: bottom; }
        .header-right { text-align: right; }
        .reference { color: #172033; font-size: 14px; font-weight: 700; }
        .status { display: inline-block; border: 1px solid #cbd5e1; border-radius: 10px; color: #334155; font-size: 8px; font-weight: 700; padding: 3px 8px; text-transform: uppercase; }
        .section-title { border-bottom: 1px solid #cbd5e1; color: #172033; font-size: 10px; font-weight: 700; letter-spacing: .4px; margin: 0 0 8px; padding-bottom: 5px; text-transform: uppercase; }
        .details-table { margin-bottom: 14px; }
        .details-table td { border: 1px solid #dbe1e8; padding: 9px 10px; vertical-align: top; width: 50%; }
        .field-label { color: #64748b; display: block; font-size: 8px; margin-bottom: 2px; text-transform: uppercase; }
        .field-value { color: #172033; font-size: 9px; font-weight: 600; }
        .section { margin-top: 15px; }
        .items-table th { background: #f1f4f7; border: 1px solid #cbd5e1; color: #334155; font-size: 8px; padding: 7px 6px; text-align: left; text-transform: uppercase; }
        .items-table td { border: 1px solid #dbe1e8; padding: 7px 6px; vertical-align: top; }
        .items-table .number { text-align: center; white-space: nowrap; }
        .items-table .product-name { color: #172033; font-weight: 700; }
        .items-table .sku { color: #64748b; font-size: 8px; margin-top: 2px; }
        .empty { color: #64748b; padding: 14px; text-align: center; }
        .summary-row td { background: #f8fafc; font-weight: 700; }
        .notes-table { width: 100%; border-collapse: collapse; }
        .notes-table td { border: 1px solid #dbe1e8; padding: 8px 10px; vertical-align: top; }
        .note-content { color: #334155; min-height: 28px; white-space: pre-wrap; }
        .signature-table { margin-top: 28px; width: 100%; border-collapse: collapse; }
        .signature-table td { padding: 24px 18px 0 0; vertical-align: bottom; width: 50%; }
        .signature-line { border-top: 1px solid #64748b; padding-top: 5px; }
        .footer { border-top: 1px solid #cbd5e1; color: #64748b; font-size: 8px; margin-top: 22px; padding-top: 7px; text-align: center; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td>
                <div class="brand">FURNISYNC</div>
                <div class="store-name">{{ $store?->name ?? 'Store' }}</div>
            </td>
            <td class="header-right">
                <div class="document-title">Goods Receipt Note</div>
                <div class="reference">{{ $grn_number }}</div>
                <div style="margin-top: 5px;"><span class="status">{{ strtoupper(str_replace('_', ' ', $receipt_status ?? '')) }}</span></div>
                <div class="muted small" style="margin-top: 5px;">Printed: {{ $printed_at?->format('M d, Y h:i A') ?? now()->format('M d, Y h:i A') }}</div>
                <div class="muted small">Printed by: {{ $printed_by ?? 'Authenticated user' }}</div>
            </td>
        </tr>
    </table>

    <table class="details-table">
        <tr>
            <td>
                <h2 class="section-title">Supplier</h2>
                <span class="field-label">Supplier name</span>
                <div class="field-value">{{ $supplier?->supplier_name ?? '-' }}</div>
                @if (!empty($supplier?->company_name))
                    <div class="muted small">{{ $supplier->company_name }}</div>
                @endif
                <div style="margin-top: 6px;"><span class="field-label">Contact person</span><span>{{ $supplier?->contact_person ?? '-' }}</span></div>
                <div><span class="field-label">Contact details</span><span>{{ $supplier?->phone ?? '-' }}@if (!empty($supplier?->email)) · {{ $supplier->email }}@endif</span></div>
            </td>
            <td>
                <h2 class="section-title">Receipt and Purchase Order</h2>
                <table class="section-table">
                    <tr>
                        <td><span class="field-label">Reference PO</span><span class="field-value">{{ $purchase_order?->po_number ?? '-' }}</span></td>
                        <td><span class="field-label">Receiving branch</span><span class="field-value">{{ $branch?->name ?? '-' }}</span></td>
                    </tr>
                    <tr>
                        <td><span class="field-label">Receipt date</span><span>{{ optional($receipt_date)->format('M d, Y') ?? '-' }}</span></td>
                        <td><span class="field-label">Receipt time</span><span>{{ optional($receipt_time)->format('h:i A') ?? '-' }}</span></td>
                    </tr>
                    <tr>
                        <td><span class="field-label">PO date</span><span>{{ optional($purchase_order?->order_date)->format('M d, Y') ?? '-' }}</span></td>
                        <td><span class="field-label">Expected delivery</span><span>{{ optional($purchase_order?->expected_delivery_date)->format('M d, Y') ?? '-' }}</span></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section">
        <h2 class="section-title">Received Items</h2>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 31%;">Product / SKU</th>
                    <th class="number" style="width: 11%;">Expected</th>
                    <th class="number" style="width: 11%;">Received</th>
                    <th class="number" style="width: 10%;">Damaged</th>
                    <th style="width: 15%;">Condition</th>
                    <th style="width: 22%;">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>
                            <div class="product-name">{{ $item->product?->product_name ?? 'Unknown Product' }}</div>
                            <div class="sku">SKU: {{ $item->variation?->variation_sku ?? $item->product?->sku ?? '-' }}</div>
                            @if (!empty($item->variation?->variation_name))
                                <div class="sku">Variant: {{ $item->variation->variation_name }}</div>
                            @endif
                        </td>
                        <td class="number">{{ number_format((int) ($item->quantity_expected ?? 0)) }}</td>
                        <td class="number">{{ number_format((int) ($item->quantity_received ?? 0)) }}</td>
                        <td class="number">{{ number_format((int) ($item->quantity_damaged ?? 0)) }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $item->condition ?? '—')) }}</td>
                        <td>{{ $item->notes ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">No items were recorded for this receipt.</td></tr>
                @endforelse
                @if (count($items))
                    <tr class="summary-row">
                        <td>Total quantities</td>
                        <td class="number">{{ number_format(collect($items)->sum('quantity_expected')) }}</td>
                        <td class="number">{{ number_format(collect($items)->sum('quantity_received')) }}</td>
                        <td class="number">{{ number_format(collect($items)->sum('quantity_damaged')) }}</td>
                        <td colspan="2"></td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">Delivery Details</h2>
        <table class="details-table">
            <tr>
                <td><span class="field-label">Delivery note number</span><span>{{ $delivery_note_number ?: '-' }}</span></td>
                <td><span class="field-label">Vehicle number</span><span>{{ $vehicle_number ?: '-' }}</span></td>
            </tr>
            <tr>
                <td colspan="2"><span class="field-label">Driver</span><span>{{ $driver_name ?: '-' }}</span></td>
            </tr>
        </table>
    </div>

    @if (!empty($discrepancy_notes) || !empty($quality_notes))
        <div class="section">
            <h2 class="section-title">Quality and Discrepancy Notes</h2>
            <table class="notes-table">
                @if (!empty($discrepancy_notes))
                    <tr><td style="width: 22%;"><strong>Discrepancy</strong></td><td class="note-content">{{ $discrepancy_notes }}</td></tr>
                @endif
                @if (!empty($quality_notes))
                    <tr><td style="width: 22%;"><strong>Quality notes</strong></td><td class="note-content">{{ $quality_notes }}</td></tr>
                @endif
            </table>
        </div>
    @endif

    <table class="signature-table">
        <tr>
            <td><div class="signature-line"><span class="field-label">Received by</span><strong>{{ trim(($received_by?->fname ?? '').' '.($received_by?->lname ?? '')) ?: ' ' }}</strong></div></td>
            <td><div class="signature-line"><span class="field-label">Verified by</span><strong>{{ trim(($verified_by?->fname ?? '').' '.($verified_by?->lname ?? '')) ?: ' ' }}</strong></div></td>
        </tr>
    </table>

    <div class="footer">Generated by FurniSync · Goods Receipt {{ $grn_number }}</div>
</body>
</html>
