export interface PayslipLine {
  label: string
  amount: number
}

export interface PayslipPrintData {
  storeName: string
  employeeName: string
  employeeId: string
  department: string
  branch: string
  periodName: string
  periodStart: string
  periodEnd: string
  payDate: string
  payrollId: string
  status: string
  earnings: PayslipLine[]
  deductions: PayslipLine[]
  grossPay: number
  totalDeductions: number
  netPay: number
  absentDays: number
  leaveDays: number
  lateMinutes: number
  overtimeHours: number
}

const escapeHtml = (value: unknown): string => String(value ?? '')
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')
  .replace(/'/g, '&#39;')

const peso = (amount: number): string => new Intl.NumberFormat('en-PH', {
  style: 'currency',
  currency: 'PHP',
  minimumFractionDigits: 2,
}).format(Number(amount) || 0)

const lineRows = (lines: PayslipLine[], deduction = false): string => lines
  .filter((line) => Number(line.amount) !== 0)
  .map((line) => `<tr><td>${escapeHtml(line.label)}</td><td>${deduction ? '-' : ''}${escapeHtml(peso(line.amount))}</td></tr>`)
  .join('') || '<tr><td class="muted">No entries</td><td>-</td></tr>'

export const buildPayslipHtml = (data: PayslipPrintData, logoUrl: string): string => {
  const paid = data.status.toLowerCase() === 'paid'
  const unsaved = data.status.toLowerCase() === 'unsaved preview'
  const recordNote = unsaved
    ? 'This is an unsaved preview and not proof of payment.'
    : paid
      ? 'Payment is recorded in the system.'
      : 'This is not proof of payment until the payroll is marked paid.'
  const printedAt = new Intl.DateTimeFormat('en-PH', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date())

  return `<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Payslip - ${escapeHtml(data.employeeName)}</title>
  <style>
    @page { size: A4; margin: 12mm; }
    * { box-sizing: border-box; }
    body { margin: 0; background: #f1f5f9; color: #172033; font: 12px/1.5 Arial, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .sheet { max-width: 780px; min-height: 980px; margin: 24px auto; padding: 34px 38px; background: #fff; box-shadow: 0 16px 40px #16243b18; }
    .brand-row, .title-row, .total, .footer { display: flex; justify-content: space-between; align-items: center; gap: 20px; }
    .brand { display: flex; align-items: center; gap: 10px; }
    .mark { width: 40px; height: 40px; padding: 5px; border-radius: 10px; background: #16243b; object-fit: contain; }
    .wordmark { font-size: 18px; font-weight: 800; letter-spacing: -.5px; }
    .platform { color: #64748b; font-size: 9px; letter-spacing: 1.2px; text-transform: uppercase; }
    .issuer { text-align: right; }
    .issuer-name { font-size: 14px; font-weight: 700; }
    .eyebrow { color: #64748b; font-size: 9px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; }
    .rule { height: 3px; margin: 24px 0 26px; background: linear-gradient(90deg, #f97316 0%, #fdba74 55%, #fff 100%); }
    h1 { margin: 2px 0 0; font-size: 27px; line-height: 1.2; letter-spacing: -.8px; }
    .status { padding: 5px 10px; border-radius: 999px; background: ${paid ? '#e8f8ee' : '#fff3e6'}; color: ${paid ? '#166534' : '#9a3412'}; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
    .period { margin: 8px 0 20px; color: #475569; }
    .metadata { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px 20px; padding: 17px 19px; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; }
    .meta-value { margin-top: 3px; font-weight: 700; overflow-wrap: anywhere; }
    .attendance { display: flex; flex-wrap: wrap; gap: 8px; margin: 17px 0 22px; }
    .attendance span { padding: 5px 9px; border: 1px solid #e2e8f0; border-radius: 6px; color: #475569; font-size: 10px; }
    .attendance strong { color: #172033; }
    .columns { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start; }
    .section { break-inside: avoid; }
    h2 { margin: 0 0 8px; padding-bottom: 8px; border-bottom: 1px solid #cbd5e1; font-size: 12px; letter-spacing: .8px; text-transform: uppercase; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 6px 0; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
    td:first-child { padding-right: 10px; color: #475569; }
    td:last-child { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; font-weight: 600; }
    .subtotal td { padding-top: 10px; border-top: 1px solid #94a3b8; border-bottom: 0; color: #172033; font-weight: 700; }
    .muted { color: #94a3b8 !important; }
    .total { margin-top: 25px; padding: 17px 20px; border-radius: 12px; background: #fff2e8; border: 1px solid #fed7aa; break-inside: avoid; }
    .total-label { color: #9a3412; font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
    .total-amount { font-size: 27px; font-weight: 800; font-variant-numeric: tabular-nums; letter-spacing: -.6px; white-space: nowrap; }
    .footer { margin-top: 32px; padding-top: 12px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 9px; }
    .note { margin: 12px 0 0; color: #64748b; font-size: 9px; }
    @media print { body { background: #fff; } .sheet { max-width: none; min-height: 0; margin: 0; padding: 0; box-shadow: none; } }
  </style>
</head>
<body>
  <main class="sheet">
    <div class="brand-row">
      <div class="brand"><img class="mark" src="${escapeHtml(logoUrl)}" alt="FurniSync logo"><div><div class="wordmark">FurniSync</div><div class="platform">Business platform</div></div></div>
      <div class="issuer"><div class="eyebrow">Issued by</div><div class="issuer-name">${escapeHtml(data.storeName)}</div></div>
    </div>
    <div class="rule"></div>
    <div class="title-row"><div><div class="eyebrow">Employee pay statement</div><h1>Payslip</h1></div><span class="status">${paid ? 'Paid' : escapeHtml(data.status || 'Draft')}</span></div>
    <p class="period">${escapeHtml(data.periodName)} &nbsp; | &nbsp; ${escapeHtml(data.periodStart)} - ${escapeHtml(data.periodEnd)}</p>
    <div class="metadata">
      <div><div class="eyebrow">Employee</div><div class="meta-value">${escapeHtml(data.employeeName)}</div></div>
      <div><div class="eyebrow">Employee ID</div><div class="meta-value">${escapeHtml(data.employeeId || '-')}</div></div>
      <div><div class="eyebrow">Department</div><div class="meta-value">${escapeHtml(data.department || '-')}</div></div>
      <div><div class="eyebrow">Branch</div><div class="meta-value">${escapeHtml(data.branch || '-')}</div></div>
      <div><div class="eyebrow">Pay date</div><div class="meta-value">${escapeHtml(data.payDate)}</div></div>
      <div><div class="eyebrow">Payroll reference</div><div class="meta-value">${escapeHtml(data.payrollId)}</div></div>
    </div>
    <div class="attendance"><span>Absent <strong>${escapeHtml(data.absentDays)}</strong> days</span><span>Leave <strong>${escapeHtml(data.leaveDays)}</strong> days</span><span>Late <strong>${escapeHtml(data.lateMinutes)}</strong> min</span><span>Overtime <strong>${escapeHtml(data.overtimeHours)}</strong> hrs</span></div>
    <div class="columns">
      <section class="section"><h2>Earnings</h2><table><tbody>${lineRows(data.earnings)}<tr class="subtotal"><td>Gross pay</td><td>${escapeHtml(peso(data.grossPay))}</td></tr></tbody></table></section>
      <section class="section"><h2>Deductions</h2><table><tbody>${lineRows(data.deductions, true)}<tr class="subtotal"><td>Total deductions</td><td>-${escapeHtml(peso(data.totalDeductions))}</td></tr></tbody></table></section>
    </div>
    <div class="total"><div><div class="total-label">Take-home pay</div><strong>Net pay</strong></div><div class="total-amount">${escapeHtml(peso(data.netPay))}</div></div>
    <p class="note">${unsaved ? 'Preview of current payroll edits.' : "Generated from the store's payroll records."} ${recordNote}</p>
    <div class="footer"><span>FurniSync | ${escapeHtml(data.storeName)}</span><span>Printed ${escapeHtml(printedAt)}</span></div>
  </main>
</body>
</html>`
}

export const printPayrollPayslip = (data: PayslipPrintData): boolean => {
  const popup = window.open('', '_blank', 'width=900,height=1000')
  if (!popup) return false

  const logoUrl = new URL('/F.svg', window.location.origin).href
  popup.document.open()
  popup.document.write(buildPayslipHtml(data, logoUrl))
  popup.document.close()

  let started = false
  const print = () => {
    if (started || popup.closed) return
    started = true
    popup.focus()
    popup.print()
  }
  const logo = popup.document.querySelector<HTMLImageElement>('.mark')
  if (logo?.complete) popup.setTimeout(print, 100)
  else {
    logo?.addEventListener('load', print, { once: true })
    logo?.addEventListener('error', print, { once: true })
    popup.setTimeout(print, 1500)
  }

  return true
}
