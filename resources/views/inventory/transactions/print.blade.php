<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction {{ $transaction->transaction_number }}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        body { font-family: Arial, sans-serif; color: #1f2937; font-size: 12px; }
        .header { border-bottom: 2px solid #111827; padding-bottom: 10px; margin-bottom: 14px; }
        .title { font-size: 18px; font-weight: 700; margin: 0; }
        .sub { color: #4b5563; margin-top: 4px; }
        .grid { display: table; width: 100%; table-layout: fixed; margin-top: 8px; }
        .row { display: table-row; }
        .cell { display: table-cell; vertical-align: top; padding: 6px 8px; }
        .label { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: .4px; }
        .value { font-size: 12px; font-weight: 600; margin-top: 2px; word-wrap: break-word; }
        .card { border: 1px solid #d1d5db; border-radius: 8px; margin-top: 10px; }
        .card-head { background: #f9fafb; padding: 8px 10px; border-bottom: 1px solid #e5e7eb; font-weight: 700; }
        .card-body { padding: 8px 10px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .table th, .table td { border: 1px solid #e5e7eb; padding: 6px; text-align: left; }
        .table th { background: #f9fafb; font-size: 11px; }
        .pill { display: inline-block; border: 1px solid #9ca3af; border-radius: 999px; padding: 2px 8px; font-size: 11px; }
        .pos { color: #047857; font-weight: 700; }
        .neg { color: #b91c1c; font-weight: 700; }
        .footer { margin-top: 14px; color: #6b7280; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">Inventory Transaction Detail</p>
        <p class="sub">Reference: {{ $transaction->transaction_number }} | Printed: {{ $generatedAt->format('M d, Y h:i A') }}</p>
    </div>

    <div class="grid">
        <div class="row">
            <div class="cell">
                <div class="label">Type</div>
                <div class="value"><span class="pill">{{ $transactionTypeLabel }}</span></div>
            </div>
            <div class="cell">
                <div class="label">Transaction Date</div>
                <div class="value">{{ optional($transaction->transaction_date)->format('M d, Y h:i A') ?? '-' }}</div>
            </div>
            <div class="cell">
                <div class="label">Quantity Change</div>
                <div class="value {{ (int) $transaction->quantity_change >= 0 ? 'pos' : 'neg' }}">
                    {{ (int) $transaction->quantity_change >= 0 ? '+' : '' }}{{ $transaction->quantity_change }}
                </div>
            </div>
            <div class="cell">
                <div class="label">Total Value</div>
                <div class="value">₱{{ number_format((float) ($transaction->total_value ?? 0), 2) }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-head">Core Information</div>
        <div class="card-body">
            <table class="table">
                <tbody>
                <tr>
                    <th>Branch</th>
                    <td>{{ $transaction->branch->name ?? '-' }}</td>
                    <th>Related Branch</th>
                    <td>{{ $transaction->relatedBranch->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Product</th>
                    <td>{{ $transaction->product->product_name ?? '-' }}</td>
                    <th>Variation</th>
                    <td>{{ $transaction->variation->variation_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Before</th>
                    <td>{{ $transaction->quantity_before ?? 0 }}</td>
                    <th>After</th>
                    <td>{{ $transaction->quantity_after ?? 0 }}</td>
                </tr>
                <tr>
                    <th>Unit Cost</th>
                    <td>₱{{ number_format((float) ($transaction->unit_cost ?? 0), 2) }}</td>
                    <th>Reference</th>
                    <td>{{ $transaction->reference_type ?? '-' }} #{{ $transaction->reference_id ?? '-' }}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head">Notes</div>
        <div class="card-body">
            {{ $transaction->notes ?: 'No notes provided.' }}
        </div>
    </div>

    <div class="card">
        <div class="card-head">Audit Trail</div>
        <div class="card-body">
            <table class="table">
                <tbody>
                <tr>
                    <th>Created By</th>
                    <td>{{ trim(($transaction->createdBy->fname ?? '') . ' ' . ($transaction->createdBy->lname ?? '')) ?: '-' }}</td>
                    <th>Employee No.</th>
                    <td>{{ $transaction->createdBy->employee_number ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Created At</th>
                    <td>{{ optional($transaction->created_at)->format('M d, Y h:i A') ?? '-' }}</td>
                    <th>Transaction No.</th>
                    <td>{{ $transaction->transaction_number ?? '-' }}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer">Generated by FurniSync Inventory Module</div>

    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>

