<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Cashflow Transactions</title>
    <style>
        @page { margin: 28px 30px; }
        * { box-sizing: border-box; }
        body { color: #172033; font-family: Arial, sans-serif; font-size: 9px; }
        .header { border-bottom: 2px solid #f97316; margin-bottom: 14px; padding-bottom: 10px; }
        .brand { color: #ea580c; font-size: 17px; font-weight: 700; letter-spacing: .4px; }
        .store { color: #334155; font-size: 11px; margin-top: 3px; }
        .title { color: #0f172a; font-size: 15px; font-weight: 700; margin: 12px 0 4px; }
        .meta { color: #64748b; line-height: 1.5; }
        table { border-collapse: collapse; margin-top: 14px; width: 100%; }
        th { background: #f1f5f9; color: #334155; font-size: 8px; text-align: left; }
        th, td { border: 1px solid #dbe2ea; padding: 6px 7px; vertical-align: top; }
        .amount, .balance { text-align: right; white-space: nowrap; }
        .incoming { color: #047857; }
        .outgoing { color: #be123c; }
        .footer { border-top: 1px solid #cbd5e1; color: #64748b; font-size: 8px; margin-top: 14px; padding-top: 6px; }
        .empty { color: #64748b; padding: 20px; text-align: center; }
    </style>
</head>
<body>
    <header class="header">
        <div class="brand">FURNISYNC</div>
        <div class="store">{{ $store->name ?? 'Store' }}</div>
        <div class="title">Cashflow Transactions</div>
        <div class="meta">
            Period:
            @if($filters['date_from'] || $filters['date_to'])
                {{ $filters['date_from'] ?: 'Beginning' }} to {{ $filters['date_to'] ?: 'Today' }}
            @else
                All dates
            @endif
            @if($filters['direction']) · Direction: {{ $filters['direction'] === 'in' ? 'Incoming' : 'Outgoing' }} @endif
            @if($filters['source']) · Source: {{ ucwords(str_replace('_', ' ', $filters['source'])) }} @endif
            <br>Printed: {{ $printedAt->format('M d, Y h:i A') }} · Printed by: {{ $printedBy }}
        </div>
    </header>

    <table>
        <thead>
            <tr>
                <th>Date &amp; Time</th>
                <th>Direction</th>
                <th class="amount">Amount</th>
                <th class="balance">Recorded Balance After</th>
                <th>Source</th>
                <th>Reference</th>
                <th>Method</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
                <tr>
                    <td>{{ $transaction->created_at?->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
                    <td>{{ $transaction->direction === 'in' ? 'Incoming' : 'Outgoing' }}</td>
                    <td class="amount {{ $transaction->direction === 'in' ? 'incoming' : 'outgoing' }}">
                        {{ $transaction->direction === 'in' ? '+' : '-' }}&#8369;{{ number_format((float) $transaction->amount, 2) }}
                    </td>
                    <td class="balance">
                        @if($transaction->balance_after === null)
                            -
                        @else
                            &#8369;{{ number_format((float) $transaction->balance_after, 2) }}
                        @endif
                    </td>
                    <td>{{ $transaction->source_label }}</td>
                    <td>{{ $transaction->source_reference ?: '-' }}</td>
                    <td>{{ $transaction->formatted_payment_method }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">No cashflow transactions match these filters.</td></tr>
            @endforelse
        </tbody>
    </table>

    <footer class="footer">FurniSync · Cashflow report · {{ $transactions->count() }} transaction(s)</footer>
</body>
</html>
