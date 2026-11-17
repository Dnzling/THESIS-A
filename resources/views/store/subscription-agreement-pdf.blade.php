<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>FurniSync Store Subscription Agreement</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #172033; font-size: 11px; line-height: 1.55; }
        .brand { font-size: 20px; font-weight: 700; color: #f97316; letter-spacing: .4px; }
        .muted { color: #64748b; }
        h1 { font-size: 18px; margin: 8px 0 2px; }
        h2 { font-size: 12px; margin: 18px 0 5px; }
        .rule { border: 0; border-top: 1px solid #cbd5e1; margin: 12px 0 18px; }
        .summary { width: 100%; border-collapse: collapse; margin: 14px 0; }
        .summary td { border: 1px solid #dbe2ea; padding: 7px 8px; vertical-align: top; }
        .label { color: #64748b; font-size: 9px; text-transform: uppercase; }
        .signature { width: 260px; height: 85px; object-fit: contain; border-bottom: 1px solid #64748b; }
        .footer { margin-top: 24px; padding-top: 8px; border-top: 1px solid #cbd5e1; font-size: 9px; color: #64748b; }
    </style>
</head>
<body>
    <div class="brand">FURNISYNC</div>
    <h1>Store Subscription Service Agreement</h1>
    <div class="muted">Agreement version {{ $agreement->agreement_version }} · Signed {{ $agreement->signed_at->format('F j, Y g:i A') }}</div>
    <hr class="rule">

    <p>This agreement records the subscription selected by the store below. FurniSync provides access to its business management platform according to the selected plan, its included modules, limits, and the plan terms shown at checkout.</p>

    <table class="summary">
        <tr><td><div class="label">Store</div><strong>{{ $store->name }}</strong></td><td><div class="label">Store code</div>{{ $store->store_code ?: '—' }}</td></tr>
        <tr><td><div class="label">Selected plan</div><strong>{{ $agreement->plan_name }}</strong></td><td><div class="label">Billing cycle</div>{{ ucfirst($agreement->billing_cycle) }}</td></tr>
        <tr><td><div class="label">Plan amount</div>₱{{ number_format((float) $agreement->plan_price, 2) }} / {{ $agreement->billing_cycle === 'yearly' ? 'year' : 'month' }}</td><td><div class="label">Agreement status</div>{{ ucfirst(str_replace('_', ' ', $agreement->status)) }}</td></tr>
    </table>

    <h2>Plan features and limits at signing</h2>
    @php($terms = $agreement->plan_terms ?? [])
    @if (!empty($terms['features']))
        <ul>
            @foreach ($terms['features'] as $feature)
                <li>{{ $feature }}</li>
            @endforeach
        </ul>
    @endif
    @if (!empty($terms['limits']))
        <table class="summary">
            @foreach ($terms['limits'] as $label => $limit)
                @php($displayLimit = $limit === null ? 'Unlimited' : (($label === '3d_requests_per_month' && (int) $limit === 0) ? 'Disabled' : number_format((int) $limit)))
                <tr><td><div class="label">{{ str_replace('_', ' ', ucfirst($label)) }}</div></td><td>{{ $displayLimit }}</td></tr>
            @endforeach
        </table>
    @endif

    <h2>Service and subscription terms</h2>
    <p>1. The store may use the plan features and limits shown during checkout. FurniSync may restrict features that exceed those limits.</p>
    <p>2. Paid subscriptions are activated after payment is confirmed. If a one-month trial is offered for this selection, it starts when FurniSync activates the trial and ends on the date shown in the store billing page. No trial charge is collected at activation.</p>
    <p>3. The store is responsible for keeping its account, business, employee, product, and transaction information accurate and for protecting its login credentials.</p>
    <p>4. Subscription renewal, expiration, cancellation, and any applicable fees are governed by the plan and billing details presented in FurniSync at the time of purchase or renewal.</p>
    <p>5. The store authorizes FurniSync to provide the selected subscription service and acknowledges that this document records the store representative's electronic signature and acceptance.</p>

    <h2>Electronic signature</h2>
    <p>I confirm that I am authorized to accept this subscription agreement on behalf of the store and agree to the terms above.</p>
    <img class="signature" src="{{ $signatureDataUri }}" alt="Electronic signature">
    <p><strong>{{ $agreement->signer_name }}</strong><br>{{ $agreement->signer_email ?: 'Store representative' }}<br>For {{ $store->name }}</p>

    <div class="footer">FurniSync · Printed {{ $printedAt->format('F j, Y g:i A') }} · Signed electronically by {{ $agreement->signer_name }}</div>
</body>
</html>
