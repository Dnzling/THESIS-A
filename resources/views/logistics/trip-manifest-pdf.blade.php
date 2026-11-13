<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Trip Manifest {{ $reference }}</title>
    <style>
        @page { margin: 26px 30px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #172033; font-family: Arial, sans-serif; font-size: 10px; }
        .topline { border-bottom: 2px solid #f97316; padding-bottom: 12px; margin-bottom: 16px; }
        .brand { color: #f97316; font-size: 20px; font-weight: 700; letter-spacing: .4px; }
        .store { margin-top: 4px; color: #475569; font-size: 11px; }
        .print-meta { margin-top: 7px; color: #64748b; font-size: 8px; }
        .title-row { width: 100%; margin-bottom: 14px; }
        .title-row td { vertical-align: top; }
        h1 { margin: 0; font-size: 19px; }
        .reference { margin-top: 4px; color: #475569; font-size: 11px; font-weight: 700; }
        .status { display: inline-block; padding: 5px 9px; border: 1px solid #fed7aa; border-radius: 12px; color: #c2410c; font-size: 9px; font-weight: 700; }
        .section-title { margin: 15px 0 7px; color: #334155; font-size: 9px; font-weight: 700; letter-spacing: .7px; text-transform: uppercase; }
        .details { width: 100%; border-collapse: collapse; }
        .details td { width: 25%; padding: 8px 9px; border: 1px solid #e2e8f0; vertical-align: top; }
        .label { display: block; margin-bottom: 4px; color: #64748b; font-size: 8px; text-transform: uppercase; }
        .value { color: #172033; font-size: 10px; font-weight: 700; }
        .subvalue { display: block; margin-top: 3px; color: #475569; font-size: 9px; }
        .items { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .items th { padding: 7px 6px; background: #f1f5f9; color: #334155; font-size: 8px; text-align: left; text-transform: uppercase; }
        .items td { padding: 7px 6px; border-bottom: 1px solid #e2e8f0; color: #334155; font-size: 8px; vertical-align: top; overflow-wrap: anywhere; }
        .items tr:nth-child(even) td { background: #fafbfc; }
        .right { text-align: right !important; }
        .center { text-align: center !important; }
        .summary { margin-top: 9px; text-align: right; color: #475569; font-size: 9px; }
        .summary strong { color: #172033; }
        .notes { padding: 8px 9px; border: 1px solid #e2e8f0; color: #475569; font-size: 9px; white-space: pre-wrap; }
        .signatures { width: 100%; margin-top: 28px; border-collapse: separate; border-spacing: 18px 0; }
        .signatures td { width: 50%; padding-top: 21px; border-top: 1px solid #94a3b8; color: #475569; font-size: 8px; text-transform: uppercase; }
        .signatures strong { display: block; margin-top: 5px; color: #172033; font-size: 9px; text-transform: none; }
        .footer { margin-top: 16px; padding-top: 7px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 7px; text-align: center; }
    </style>
</head>
<body>
    <table class="topline" width="100%">
        <tr>
            <td>
                <div class="brand">FURNISYNC</div>
                <div class="store">{{ $store?->name ?? 'Store' }}</div>
                <div class="print-meta">Printed {{ $printedAt->format('M d, Y h:i A') }} by {{ $printedBy }}</div>
            </td>
            <td class="right" style="vertical-align:top;">
                <span class="status">{{ ucwords(str_replace('_', ' ', $trip->status)) }}</span>
            </td>
        </tr>
    </table>

    <table class="title-row" width="100%">
        <tr>
            <td>
                <h1>Delivery Trip Manifest</h1>
                <div class="reference">{{ $reference }}</div>
            </td>
            <td class="right" style="vertical-align:bottom; color:#64748b; font-size:9px;">
                {{ $stops->count() }} assigned stop{{ $stops->count() === 1 ? '' : 's' }}
            </td>
        </tr>
    </table>

    <div class="section-title">Trip and Vehicle</div>
    <table class="details">
        <tr>
            <td><span class="label">Scheduled Departure</span><span class="value">{{ $trip->scheduled_departure_at?->format('M d, Y') ?? '-' }}</span></td>
            <td><span class="label">Driver</span><span class="value">{{ trim(($trip->driver?->fname ?? '') . ' ' . ($trip->driver?->lname ?? '')) ?: '-' }}</span><span class="subvalue">{{ $trip->driver?->phone_number ?: $trip->driver?->email ?: 'No contact recorded' }}</span></td>
            <td><span class="label">Vehicle</span><span class="value">{{ $trip->vehicle?->vehicle_name ?? '-' }}</span><span class="subvalue">{{ trim(($trip->vehicle?->vehicle_type ?? '') . ' ' . ($trip->vehicle?->plate_number ?? '')) ?: '-' }}</span></td>
            <td><span class="label">Truck Capacity</span><span class="value">{{ $trip->vehicle?->capacity_kg ? number_format((float) $trip->vehicle->capacity_kg, 2) . ' kg' : 'Not set' }}</span><span class="subvalue">Current onboard: {{ number_format((float) $onboardWeight, 2) }} kg</span></td>
        </tr>
        <tr>
            <td colspan="4"><span class="label">Accompanying Employees</span><span class="value">{{ $assistants->map(fn ($employee) => trim(($employee->fname ?? '') . ' ' . ($employee->lname ?? '')) ?: ($employee->email ?? ''))->filter()->implode(', ') ?: 'None assigned' }}</span></td>
        </tr>
    </table>

    <div class="section-title">Assigned Stops</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width:9%;">Source</th>
                <th style="width:14%;">Reference</th>
                <th style="width:15%;">Recipient / Destination</th>
                <th style="width:27%;">Address</th>
                <th style="width:11%;">Scheduled</th>
                <th style="width:10%;">Status</th>
                <th class="right" style="width:8%;">Weight</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stops as $stop)
                <tr>
                    <td>{{ $stop['source'] }}</td>
                    <td><strong>{{ $stop['reference'] ?: '-' }}</strong></td>
                    <td>{{ $stop['recipient'] ?: '-' }}</td>
                    <td>{{ $stop['address'] ?: '-' }}</td>
                    <td>{{ $stop['date'] ? \Illuminate\Support\Carbon::parse($stop['date'])->format('M d, Y') : '-' }}</td>
                    <td>{{ $stop['status'] ? ucwords(str_replace('_', ' ', $stop['status'])) : '-' }}</td>
                    <td class="right">{{ number_format((float) $stop['weight'], 2) }} kg</td>
                </tr>
            @empty
                <tr><td colspan="7" class="center">No stops assigned.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($trip->notes)
        <div class="section-title">Trip Notes</div>
        <div class="notes">{{ $trip->notes }}</div>
    @endif

    <table class="signatures">
        <tr>
            <td>Driver Signature<strong>{{ trim(($trip->driver?->fname ?? '') . ' ' . ($trip->driver?->lname ?? '')) ?: ' ' }}</strong></td>
            <td>Dispatcher / Prepared By<strong>{{ $printedBy }}</strong></td>
        </tr>
    </table>

    <div class="footer">FurniSync Delivery Trip Manifest · {{ $reference }} · Printed {{ $printedAt->format('M d, Y h:i A') }}</div>
</body>
</html>
