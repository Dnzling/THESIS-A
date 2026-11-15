<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Employee Payslip</title>
    <style>
        @page { margin: 28px 32px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #1f2937; font-family: Arial, sans-serif; font-size: 10px; }
        .header { width: 100%; border-bottom: 2px solid #f97316; padding-bottom: 12px; margin-bottom: 14px; }
        .header-table, .meta, .data { width: 100%; border-collapse: collapse; }
        .brand { color: #f97316; font-family: Arial, sans-serif; font-size: 18px; font-weight: 700; letter-spacing: .04em; }
        .document-title { margin-top: 5px; color: #111827; font-size: 17px; font-weight: 700; }
        .period { margin-top: 3px; color: #64748b; font-size: 10px; }
        .printed { color: #64748b; font-size: 9px; text-align: right; vertical-align: top; }
        .section-title { margin: 14px 0 6px; color: #334155; font-size: 10px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .meta { border: 1px solid #e2e8f0; }
        .meta td { width: 25%; padding: 7px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .meta tr:last-child td { border-bottom: 0; }
        .label { display: block; margin-bottom: 3px; color: #64748b; font-size: 8px; text-transform: uppercase; }
        .value { color: #1e293b; font-size: 10px; font-weight: 600; }
        .data th, .data td { padding: 7px 8px; border-bottom: 1px solid #e2e8f0; }
        .data th { background: #f8fafc; color: #475569; font-size: 8px; font-weight: 700; text-align: left; text-transform: uppercase; }
        .data td { font-size: 9px; }
        .amount { text-align: right !important; white-space: nowrap; }
        .subtotal td { background: #f8fafc; color: #0f172a; font-weight: 700; }
        .empty { color: #64748b; font-style: italic; }
        .net-table { width: 100%; margin-top: 14px; border-collapse: collapse; }
        .net-table td { padding: 11px 12px; background: #fff7ed; border: 1px solid #fed7aa; }
        .net-label { color: #9a3412; font-size: 11px; font-weight: 700; letter-spacing: .04em; }
        .net-value { color: #c2410c; font-size: 16px; font-weight: 700; text-align: right; }
        .footer { margin-top: 24px; padding-top: 8px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 8px; text-align: center; }
    </style>
</head>
<body>
    @php
        $earningItems = ($payroll?->items ?? collect())->where('type', 'earning');
        $deductionItems = ($payroll?->items ?? collect())->where('type', 'deduction');
        $taxItems = ($payroll?->items ?? collect())->where('type', 'tax');
        $hasItemizedEarnings = $earningItems->isNotEmpty();
        $hasItemizedDeductions = $deductionItems->isNotEmpty() || $taxItems->isNotEmpty();
        $baseSalary = (float) ($payroll?->base_salary ?? 0);
        $overtimeAmount = (float) ($payroll?->overtime_amount ?? 0);
        $bonusesTotal = (float) ($payroll?->bonuses_total ?? 0);
        $allowancesTotal = (float) ($payroll?->allowances_total ?? 0);
        $deductionsTotal = (float) ($payroll?->deductions_total ?? 0);
        $taxTotal = (float) ($payroll?->tax_amount ?? 0);
        $paymentDate = $payroll?->payment_date;
    @endphp

    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <div class="brand">FURNISYNC</div>
                    <div class="document-title">Employee Payslip</div>
                    <div class="period">Pay period: {{ $payPeriod?->name ?? 'N/A' }}</div>
                </td>
                <td class="printed">Printed on<br><strong>{{ $printedAt->format('M d, Y h:i A') }}</strong></td>
            </tr>
        </table>
    </div>

    <table class="meta">
        <tr>
            <td><span class="label">Employee</span><span class="value">{{ trim(($user?->fname ?? '') . ' ' . ($user?->lname ?? '')) ?: 'N/A' }}</span></td>
            <td><span class="label">Employee ID</span><span class="value">{{ $employee?->employee_number ?? 'N/A' }}</span></td>
            <td><span class="label">Department</span><span class="value">{{ $employee?->department ?: 'N/A' }}</span></td>
            <td><span class="label">Status</span><span class="value">{{ ucfirst(str_replace('_', ' ', $payroll?->status ?? 'N/A')) }}</span></td>
        </tr>
        <tr>
            <td colspan="2"><span class="label">Pay Period Dates</span><span class="value">{{ $payPeriod?->start_date?->format('M d, Y') ?? 'N/A' }} - {{ $payPeriod?->end_date?->format('M d, Y') ?? 'N/A' }}</span></td>
            <td><span class="label">Payment Date</span><span class="value">{{ $paymentDate?->format('M d, Y') ?? 'N/A' }}</span></td>
            <td><span class="label">Payment Method</span><span class="value">{{ ucfirst(str_replace('_', ' ', $payroll?->payment_method ?? 'N/A')) }}</span></td>
        </tr>
    </table>

    <div class="section-title">Earnings</div>
    <table class="data">
        <thead><tr><th>Description</th><th class="amount">Amount (PHP)</th></tr></thead>
        <tbody>
            @if($hasItemizedEarnings)
                @foreach($earningItems as $item)
                    <tr><td>{{ $item->name }}</td><td class="amount">{{ number_format((float) $item->amount, 2) }}</td></tr>
                @endforeach
            @else
                <tr><td>Base Salary</td><td class="amount">{{ number_format($baseSalary, 2) }}</td></tr>
                <tr><td>Overtime</td><td class="amount">{{ number_format($overtimeAmount, 2) }}</td></tr>
                <tr><td>Bonuses</td><td class="amount">{{ number_format($bonusesTotal, 2) }}</td></tr>
                <tr><td>Allowances</td><td class="amount">{{ number_format($allowancesTotal, 2) }}</td></tr>
            @endif
            <tr class="subtotal"><td>Gross Pay</td><td class="amount">{{ number_format($baseSalary + $overtimeAmount + $bonusesTotal + $allowancesTotal, 2) }}</td></tr>
        </tbody>
    </table>

    <div class="section-title">Deductions</div>
    <table class="data">
        <thead><tr><th>Description</th><th class="amount">Amount (PHP)</th></tr></thead>
        <tbody>
            @if($hasItemizedDeductions)
                @foreach($deductionItems as $item)
                    <tr><td>{{ $item->name }}</td><td class="amount">{{ number_format((float) $item->amount, 2) }}</td></tr>
                @endforeach
                @foreach($taxItems as $item)
                    <tr><td>{{ $item->name }}</td><td class="amount">{{ number_format((float) $item->amount, 2) }}</td></tr>
                @endforeach
            @else
                <tr><td>Employee Deductions</td><td class="amount">{{ number_format($deductionsTotal, 2) }}</td></tr>
                <tr><td>Withholding Tax</td><td class="amount">{{ number_format($taxTotal, 2) }}</td></tr>
            @endif
            <tr class="subtotal"><td>Total Deductions</td><td class="amount">{{ number_format($deductionsTotal + $taxTotal, 2) }}</td></tr>
        </tbody>
    </table>

    <table class="net-table">
        <tr><td class="net-label">NET PAY</td><td class="net-value">PHP {{ number_format((float) ($payroll?->net_salary ?? 0), 2) }}</td></tr>
    </table>

    <div class="footer">FURNISYNC - Employee payroll record</div>
</body>
</html>
