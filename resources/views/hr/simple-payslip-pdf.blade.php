<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Employee Payslip</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1f2937; }
        .header { border-bottom: 2px solid #ea580c; padding-bottom: 12px; margin-bottom: 18px; }
        .brand { color: #ea580c; font-size: 19px; font-weight: 700; letter-spacing: .04em; }
        .muted { color: #64748b; }
        .title { font-size: 17px; font-weight: 700; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        th, td { padding: 8px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #f8fafc; }
        .amount { text-align: right; }
        .net { font-size: 14px; font-weight: 700; border-top: 2px solid #94a3b8; }
        .meta td { width: 25%; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">FURNISYNC</div>
        <div class="title">Employee Payslip</div>
        <div class="muted">Pay Period: {{ $payPeriod?->name ?? 'N/A' }}</div>
        <div class="muted">Printed at: {{ $printedAt->format('M d, Y h:i A') }}</div>
    </div>
    <table class="meta">
        <tr><th>Employee</th><td>{{ trim(($user?->fname ?? '') . ' ' . ($user?->lname ?? '')) ?: 'N/A' }}</td><th>Employee ID</th><td>{{ $employee?->employee_number ?? 'N/A' }}</td></tr>
        <tr><th>Store</th><td>{{ $employee?->store?->name ?? 'N/A' }}</td><th>Branch</th><td>{{ $employee?->branch?->name ?? 'N/A' }}</td></tr>
        <tr><th>Payment Date</th><td>{{ optional($payroll?->payment_date)->format('M d, Y') ?? 'N/A' }}</td><th>Payment Method</th><td>{{ ucfirst(str_replace('_', ' ', $payroll?->payment_method ?? 'N/A')) }}</td></tr>
    </table>
    <table>
        <thead><tr><th>Earnings</th><th class="amount">Amount</th></tr></thead>
        <tbody>
            <tr><td>Base Pay</td><td class="amount">PHP {{ number_format((float) $payroll->base_salary, 2) }}</td></tr>
            <tr><td>Overtime</td><td class="amount">PHP {{ number_format((float) $payroll->overtime_amount, 2) }}</td></tr>
            <tr><td>Allowances</td><td class="amount">PHP {{ number_format((float) $payroll->allowances_total, 2) }}</td></tr>
            <tr><td>Incentives</td><td class="amount">PHP {{ number_format((float) $payroll->bonuses_total, 2) }}</td></tr>
            <tr><th>Gross Pay</th><th class="amount">PHP {{ number_format((float) $payroll->base_salary + (float) $payroll->overtime_amount + (float) $payroll->allowances_total + (float) $payroll->bonuses_total, 2) }}</th></tr>
        </tbody>
    </table>
    <table>
        <thead><tr><th>Deductions</th><th class="amount">Amount</th></tr></thead>
        <tbody>
            <tr><td>Other deductions</td><td class="amount">PHP {{ number_format((float) $payroll->deductions_total, 2) }}</td></tr>
            <tr><td>Tax</td><td class="amount">PHP {{ number_format((float) $payroll->tax_amount, 2) }}</td></tr>
            <tr><td class="net">NET PAY</td><td class="amount net">PHP {{ number_format((float) $payroll->net_salary, 2) }}</td></tr>
        </tbody>
    </table>
</body>
</html>
