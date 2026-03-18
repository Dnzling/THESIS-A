<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Payslip</title>
    <style>
      body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #1f2937; }
      .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
      .title { font-size: 18px; font-weight: bold; }
      .muted { color: #6b7280; }
      .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; margin-bottom: 12px; }
      .grid { width: 100%; border-collapse: collapse; }
      .grid th, .grid td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
      .totals { text-align: right; font-weight: bold; }
    </style>
  </head>
  <body>
    <div class="header">
      <div>
        <div class="title">Payslip</div>
        <div class="muted">Pay Period: {{ $payPeriod?->name ?? 'N/A' }}</div>
      </div>
      <div class="muted">Printed: {{ $printedAt->format('Y-m-d H:i') }}</div>
    </div>

    <div class="card">
      <table class="grid">
        <tr>
          <th>Employee</th>
          <td>{{ $employee?->fname }} {{ $employee?->lname }}</td>
          <th>Employee ID</th>
          <td>{{ $employee?->employee_number }}</td>
        </tr>
        <tr>
          <th>Department</th>
          <td>{{ $employee?->department ?? 'N/A' }}</td>
          <th>Status</th>
          <td>{{ $payroll?->status ?? 'N/A' }}</td>
        </tr>
        <tr>
          <th>Net Pay</th>
          <td>{{ number_format($payroll?->net_salary ?? 0, 2) }}</td>
          <th>Payment Date</th>
          <td>{{ optional($payroll?->payment_date)->format('Y-m-d') ?? 'N/A' }}</td>
        </tr>
      </table>
    </div>

    <div class="card">
      <div class="title" style="font-size: 14px; margin-bottom: 6px;">Earnings</div>
      <table class="grid">
        <thead>
          <tr>
            <th>Description</th>
            <th class="totals">Amount</th>
          </tr>
        </thead>
        <tbody>
          @foreach(($payroll?->items?->where('type', 'earning') ?? []) as $item)
            <tr>
              <td>{{ $item->name }}</td>
              <td class="totals">{{ number_format($item->amount, 2) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="card">
      <div class="title" style="font-size: 14px; margin-bottom: 6px;">Deductions</div>
      <table class="grid">
        <thead>
          <tr>
            <th>Description</th>
            <th class="totals">Amount</th>
          </tr>
        </thead>
        <tbody>
          @foreach(($payroll?->items?->where('type', 'deduction') ?? []) as $item)
            <tr>
              <td>{{ $item->name }}</td>
              <td class="totals">{{ number_format($item->amount, 2) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </body>
</html>
