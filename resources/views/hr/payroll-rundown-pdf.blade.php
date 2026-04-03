<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Payroll Rundown</title>
    <style>
      body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #1f2937; }
      .header { margin-bottom: 12px; }
      .title { font-size: 18px; font-weight: bold; }
      .muted { color: #6b7280; }
      .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; margin-bottom: 12px; }
      table { width: 100%; border-collapse: collapse; }
      th, td { border-bottom: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
      th { background: #f8fafc; }
      .num { text-align: right; }
      .totals td { font-weight: bold; background: #f8fafc; }
    </style>
  </head>
  <body>
    <div class="header">
      <div class="title">Employee Payroll Rundown</div>
      <div class="muted">Employee: {{ $employee?->fname }} {{ $employee?->lname }} ({{ $employee?->employee_number ?? 'N/A' }})</div>
      <div class="muted">Printed At: {{ $printedAt->format('Y-m-d H:i') }}</div>
      <div class="muted">Filters: Year {{ $filters['year'] ?? 'All' }}, Month {{ $filters['month'] ?? 'All' }}, Status {{ $filters['status'] ?? 'All' }}</div>
    </div>

    <div class="card">
      <table>
        <tr>
          <td><strong>Total Payrolls</strong></td>
          <td class="num">{{ number_format($totals['count'] ?? 0) }}</td>
          <td><strong>Total Gross</strong></td>
          <td class="num">{{ number_format($totals['gross'] ?? 0, 2) }}</td>
          <td><strong>Total Deductions</strong></td>
          <td class="num">{{ number_format($totals['deductions'] ?? 0, 2) }}</td>
          <td><strong>Total Net</strong></td>
          <td class="num">{{ number_format($totals['net'] ?? 0, 2) }}</td>
        </tr>
      </table>
    </div>

    <div class="card">
      <strong>Status Breakdown</strong>
      <table style="margin-top:8px;">
        <thead>
          <tr>
            <th>Status</th>
            <th class="num">Count</th>
            <th class="num">Net Total</th>
          </tr>
        </thead>
        <tbody>
          @forelse($byStatus as $row)
            <tr>
              <td>{{ strtoupper($row['status']) }}</td>
              <td class="num">{{ number_format($row['count']) }}</td>
              <td class="num">{{ number_format($row['net_total'], 2) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="3">No records</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Period</th>
          <th class="num">Gross</th>
          <th class="num">Deductions</th>
          <th class="num">Net</th>
          <th>Status</th>
          <th>Payment Date</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $index => $row)
          <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $row['period'] }}</td>
            <td class="num">{{ number_format($row['gross_pay'], 2) }}</td>
            <td class="num">{{ number_format($row['deductions_total'], 2) }}</td>
            <td class="num">{{ number_format($row['net_salary'], 2) }}</td>
            <td>{{ strtoupper($row['status']) }}</td>
            <td>{{ $row['payment_date'] ? \Illuminate\Support\Carbon::parse($row['payment_date'])->format('Y-m-d') : 'N/A' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="7">No payroll records found for selected filters.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </body>
</html>
