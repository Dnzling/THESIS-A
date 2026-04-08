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
    @endphp

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
          @if($hasItemizedEarnings)
            @foreach($earningItems as $item)
              <tr>
                <td>{{ $item->name }}</td>
                <td class="totals">{{ number_format($item->amount, 2) }}</td>
              </tr>
            @endforeach
          @else
            <tr>
              <td>Base Salary</td>
              <td class="totals">{{ number_format($baseSalary, 2) }}</td>
            </tr>
            <tr>
              <td>Overtime</td>
              <td class="totals">{{ number_format($overtimeAmount, 2) }}</td>
            </tr>
            <tr>
              <td>Bonuses</td>
              <td class="totals">{{ number_format($bonusesTotal, 2) }}</td>
            </tr>
            <tr>
              <td>Allowances</td>
              <td class="totals">{{ number_format($allowancesTotal, 2) }}</td>
            </tr>
          @endif
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
          @if($hasItemizedDeductions)
            @foreach($deductionItems as $item)
              <tr>
                <td>{{ $item->name }}</td>
                <td class="totals">{{ number_format($item->amount, 2) }}</td>
              </tr>
            @endforeach
            @foreach($taxItems as $item)
              <tr>
                <td>{{ $item->name }}</td>
                <td class="totals">{{ number_format($item->amount, 2) }}</td>
              </tr>
            @endforeach
          @else
            <tr>
              <td>Employee Deductions</td>
              <td class="totals">{{ number_format($deductionsTotal, 2) }}</td>
            </tr>
            <tr>
              <td>Withholding Tax</td>
              <td class="totals">{{ number_format($taxTotal, 2) }}</td>
            </tr>
          @endif
          <tr>
            <td><strong>Total Deductions</strong></td>
            <td class="totals"><strong>{{ number_format($deductionsTotal + $taxTotal, 2) }}</strong></td>
          </tr>
        </tbody>
      </table>
    </div>
  </body>
</html>
