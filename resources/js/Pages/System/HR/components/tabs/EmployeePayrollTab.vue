<template>
  <div class="space-y-4">
    <!-- Toolbar -->
    <div class="flex justify-between items-center">
      <div class="flex items-center gap-2">
        <Button
          :label="showFilters ? 'Hide Filters' : 'Show Filters'"
          icon="pi pi-filter"
          severity="secondary"
          size="small"
          outlined
          @click="showFilters = !showFilters"
        />
      </div>
      <div class="flex gap-2 items-center">
        <Button label="Generate" icon="pi pi-file-pdf" severity="info" size="small" @click="generatePayslip"
          :disabled="!selectedYear || !selectedMonth" />
        <Button label="Print Rundown" icon="pi pi-print" severity="warning" size="small" outlined
          @click="printPayrollRundown" :disabled="loading" />
        <Button label="Export All" icon="pi pi-download" severity="success" size="small" outlined
          @click="exportAllPayslips" :disabled="!payslipHistory.length" />
      </div>
    </div>

    <div v-if="showFilters" class="rounded-lg border border-slate-200 bg-slate-50 p-3">
      <div class="flex flex-wrap items-center gap-2">
        <Select v-model="selectedYear" :options="yearOptions" placeholder="All Years" class="w-32" showClear />
        <Select v-model="selectedMonth" :options="monthOptions" optionLabel="label" optionValue="value"
          placeholder="All Months" class="w-40" showClear />
        <Button label="Apply" icon="pi pi-check" size="small" severity="info" @click="fetchPayslipHistory(1)" />
        <Button label="Reset" icon="pi pi-refresh" size="small" severity="secondary" outlined @click="resetFilters" />
      </div>
      <p class="mt-2 text-xs text-slate-500">Showing all paid payslips by default. Use filters only if needed.</p>
    </div>

        <!-- Payslip Summary -->
    <!-- <div v-if="payrollSummary" class="grid grid-cols-1 gap-4 md:grid-cols-3">
      <Card class="rounded-xl border border-blue-100 bg-blue-50/70 shadow-sm">
        <template #content>
          <div class="p-4">
            <div class="text-sm text-blue-700">YTD Gross Pay</div>
            <div class="mt-1 text-2xl font-semibold text-blue-700">
              ₱{{ formatNumber(payrollSummary?.total_gross || 0) }}
            </div>
            <div class="mt-1 text-xs text-blue-500">
              {{ payrollSummary?.payroll_count || 0 }} payroll{{ payrollSummary?.payroll_count !== 1 ? 's' : '' }}
            </div>
          </div>
        </template>
      </Card>
      <Card class="rounded-xl border border-emerald-100 bg-emerald-50/70 shadow-sm">
        <template #content>
          <div class="p-4">
            <div class="text-sm text-emerald-700">YTD Net Pay</div>
            <div class="mt-1 text-2xl font-semibold text-emerald-700">
              ₱{{ formatNumber(payrollSummary?.total_net || 0) }}
            </div>
            <div class="mt-1 text-xs text-emerald-500">
              Total take-home pay
            </div>
          </div>
        </template>
      </Card>
      <Card class="rounded-xl border border-amber-100 bg-amber-50/70 shadow-sm">
        <template #content>
          <div class="p-4">
            <div class="text-sm text-amber-700">Avg Monthly</div>
            <div class="mt-1 text-2xl font-semibold text-amber-700">
              ₱{{ formatNumber(payrollSummary?.average_monthly || 0) }}
            </div>
            <div class="mt-1 text-xs text-amber-500">
              {{ payrollSummary?.month_count || 0 }} month{{ payrollSummary?.month_count !== 1 ? 's' : '' }}
            </div>
          </div>
        </template>
      </Card>
    </div> -->

    <!-- Pagination Info -->
    <div v-if="pagination.total > 0" class="flex justify-between items-center text-sm text-gray-500">
      <span>Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries</span>
      <div class="flex gap-1">
        <Button icon="pi pi-chevron-left" text rounded size="small" :disabled="!pagination.prev_page_url" @click="changePage(pagination.current_page - 1)" />
        <span class="px-3 py-1 bg-gray-100 rounded-md">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
        <Button icon="pi pi-chevron-right" text rounded size="small" :disabled="!pagination.next_page_url" @click="changePage(pagination.current_page + 1)" />
      </div>
    </div>

    <!-- Payslip Table -->
    <DataTable :value="payslipHistory" :paginator="false" class="p-datatable-sm" :loading="loading"
      dataKey="id" :globalFilterFields="['pay_period?.name', 'status']">
      <Column field="pay_period?.name" header="Pay Period" sortable>
        <template #body="{ data }">
          <div class="font-medium">{{ getPayPeriodName(data) || 'N/A' }}</div>
          <small class="text-gray-500">
            {{ formatDateRange(data.pay_period?.start_date, data.pay_period?.end_date) }}
          </small>
        </template>
      </Column>

      <Column field="base_salary" header="Base Salary" sortable>
        <template #body="{ data }">
          ₱{{ formatNumber(data.base_salary) }}
        </template>
      </Column>

      <Column field="overtime_amount" header="Overtime" sortable>
        <template #body="{ data }">
          <div v-if="data.overtime_amount > 0" class="text-green-600">
            ₱{{ formatNumber(data.overtime_amount) }}
            <small class="text-gray-500 block">({{ data.overtime_hours }} hrs)</small>
          </div>
          <span v-else class="text-gray-400">-</span>
        </template>
      </Column>

      <Column field="allowances_total" header="Allowances" sortable>
        <template #body="{ data }">
          <div v-if="data.allowances_total > 0">
            ₱{{ formatNumber(data.allowances_total) }}
          </div>
          <span v-else class="text-gray-400">-</span>
        </template>
      </Column>

      <Column field="gross_pay" header="Gross Pay" sortable>
        <template #body="{ data }">
          <span class="font-medium text-green-600">
            ₱{{ formatNumber(calculateGrossPay(data)) }}
          </span>
        </template>
      </Column>

      <Column field="deductions_total" header="Deductions" sortable>
        <template #body="{ data }">
          <div class="text-red-600">
            -₱{{ formatNumber(data.deductions_total + data.tax_amount) }}
          </div>
          <small class="text-gray-500">
            Tax: ₱{{ formatNumber(data.tax_amount) }}
          </small>
        </template>
      </Column>

      <Column field="net_salary" header="Net Pay" sortable>
        <template #body="{ data }">
          <span class="font-bold text-blue-600">₱{{ formatNumber(data.net_salary) }}</span>
        </template>
      </Column>

      <Column field="status" header="Status" sortable>
        <template #body="{ data }">
          <Tag :value="data.status" :severity="getStatusSeverity(data.status)" rounded class="capitalize" />
          <small v-if="data.approved_at" class="text-gray-500 block text-xs">
            {{ formatDate(data.approved_at) }}
          </small>
        </template>
      </Column>

      <Column field="payment_date" header="Release Date" sortable>
        <template #body="{ data }">
          <div v-if="data.payment_date">
            {{ formatDate(data.payment_date) }}
          </div>
          <span v-else class="text-gray-400">Not released</span>
        </template>
      </Column>

      <Column header="Actions" style="width: 120px">
        <template #body="{ data }">
          <div class="flex gap-1">
            <Button icon="pi pi-eye" text rounded severity="info" size="small" @click="viewPayslip(data)"
              v-tooltip.top="'View payslip'" :disabled="data.status !== 'paid'" />
            <!-- <Button icon="pi pi-download" text rounded severity="success" size="small"
              @click="downloadPayslip(data)" v-tooltip.top="'Download PDF'"
              :disabled="data.status !== 'paid'" /> -->
            <Button icon="pi pi-print" text rounded severity="warning" size="small" @click="printPayslip(data)"
              v-tooltip.top="'Print'" :disabled="data.status !== 'paid'" />
          </div>
        </template>
      </Column>

      <template #empty>
        <div class="text-center py-8 text-gray-500">
          <i class="pi pi-file-pdf text-4xl mb-2"></i>
          <p>No payslip history found</p>
          <p class="text-sm">Select a year and month to generate payslip</p>
        </div>
      </template>
    </DataTable>

    <!-- View Payslip Dialog -->
    <Dialog v-model:visible="showPayslipDialog" header="Payslip Details" :style="{ width: '700px' }" modal
      :closable="true">
      <div v-if="selectedPayslip" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-sm text-gray-500">Employee</label>
            <div class="font-medium">{{ selectedPayslip.employee_name || employeeName }}</div>
          </div>
          <div>
            <label class="text-sm text-gray-500">Period</label>
            <div class="font-medium">{{ getPayPeriodName(selectedPayslip) }}</div>
          </div>
        </div>

        <!-- Earnings -->
        <div>
          <h4 class="font-medium mb-2">Earnings</h4>
          <div class="space-y-2">
            <div v-for="item in getEarnings(selectedPayslip)" :key="item.id"
              class="flex justify-between">
              <span>{{ item.name }}</span>
              <span class="font-medium">₱{{ formatNumber(item.amount) }}</span>
            </div>
            <div v-if="getEarnings(selectedPayslip).length === 0"
              class="text-gray-400 text-sm">
              No earning items
            </div>
          </div>
        </div>

        <!-- Deductions -->
        <div>
          <h4 class="font-medium mb-2">Deductions</h4>
          <div class="space-y-2">
            <div v-for="item in getDeductions(selectedPayslip)" :key="item.id" class="flex justify-between">
              <span>{{ item.name }}</span>
              <span class="font-medium text-red-600">-₱{{ formatNumber(item.amount) }}</span>
            </div>
            <div v-if="getDeductions(selectedPayslip).length === 0"
              class="text-gray-400 text-sm">
              No deduction items
            </div>
          </div>
        </div>


        <!-- Summary -->
        <div class="bg-gray-50 p-3 rounded">
          <div class="flex justify-between font-bold">
            <span>Gross Pay</span>
            <span class="text-green-600">₱{{ formatNumber(calculateGrossPay(selectedPayslip)) }}</span>
          </div>
          <div class="flex justify-between font-bold mt-2">
            <span>Total Deductions</span>
            <span class="text-red-600">-₱{{ formatNumber(selectedPayslip.deductions_total +
              selectedPayslip.tax_amount) }}</span>
          </div>
          <div class="flex justify-between font-bold mt-2 text-lg">
            <span>Net Pay</span>
            <span class="text-blue-600">₱{{ formatNumber(selectedPayslip.net_salary) }}</span>
          </div>
        </div>
      </div>
      <template #footer>
        <Button label="Close" icon="pi pi-times" @click="showPayslipDialog = false" />
        <!-- <Button label="Download PDF" icon="pi pi-download" severity="success"
          @click="downloadPayslip(selectedPayslip)" /> -->
        <Button label="Print" icon="pi pi-print" severity="info" @click="printPayslip(selectedPayslip)" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import Card from 'primevue/card'
import { useToast } from 'primevue/usetoast'
import hrService from '@/services/hr.services'

// ==================== INTERFACES ====================
interface PayPeriod {
  id: number
  name: string
  start_date: string
  end_date: string
  status: string
}

interface PayslipItem {
  id: number
  payroll_id: number
  type: string
  name: string
  amount: number | string
  calculation_type?: string
  rate?: number | string
  quantity?: number | string
}

interface Payslip {
  id: number
  employee_name?: string
  employee_id: number
  pay_period_id: number
  base_salary: number
  overtime_hours: number
  overtime_amount: number
  deductions_total: number
  bonuses_total: number
  allowances_total: number
  tax_amount: number
  net_salary: number
  late_minutes: number
  late_deduction: number
  late_occurrences: number
  status: 'draft' | 'processing' | 'approved' | 'paid' | 'cancelled'
  payment_date: string | null
  payment_method: string | null
  reference_number: string | null
  notes: string | null
  approved_by: number | null
  approved_at: string | null
  paid_by: number | null
  paid_at: string | null
  created_at: string
  updated_at: string
  gross_pay?: number
  pay_period?: PayPeriod | null
  items?: {
    earnings: PayslipItem[]
    deductions: PayslipItem[]
    allowances: PayslipItem[]
    bonuses: PayslipItem[]
    all: PayslipItem[]
  }
}

interface PayrollSummary {
  total_gross: number
  total_net: number
  average_monthly: number
  payroll_count: number
  month_count: number
  by_status: Record<string, { count: number; total: number }>
  ytd: {
    gross: number
    net: number
    tax: number
    deductions: number
  }
}

interface Pagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
  next_page_url: string | null
  prev_page_url: string | null
  links: any[]
}

// ==================== PROPS ====================
const props = defineProps<{
  employeeId: number
  employeeName?: string
}>()

// ==================== EMITS ====================
const emit = defineEmits<{
  (e: 'view-payslip', payslip: Payslip): void
  (e: 'download-payslip', payslip: Payslip): void
  (e: 'print-payslip', payslip: Payslip): void
  (e: 'generate-payslip', year: number, month: number): void
  (e: 'export-all', year: number, month: number): void
}>()

// ==================== STATE ====================
const toast = useToast()
const loading = ref(false)
const showPayslipDialog = ref(false)
const selectedPayslip = ref<Payslip | null>(null)
const showFilters = ref(false)

// Year and Month selection
const currentYear = new Date().getFullYear()
const selectedYear = ref<number | null>(null)
const selectedMonth = ref<number | null>(null)

// Data
const payslipHistory = ref<Payslip[]>([])
const payrollSummary = ref<PayrollSummary | null>(null)
const pagination = ref<Pagination>({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0,
  next_page_url: null,
  prev_page_url: null,
  links: []
})

// Generate year options (last 5 years)
const yearOptions = computed(() => {
  const years = []
  for (let i = 0; i < 5; i++) {
    years.push(currentYear - i)
  }
  return years.sort((a, b) => b - a)
})

// Month options
const monthOptions = ref([
  { label: 'January', value: 1 },
  { label: 'February', value: 2 },
  { label: 'March', value: 3 },
  { label: 'April', value: 4 },
  { label: 'May', value: 5 },
  { label: 'June', value: 6 },
  { label: 'July', value: 7 },
  { label: 'August', value: 8 },
  { label: 'September', value: 9 },
  { label: 'October', value: 10 },
  { label: 'November', value: 11 },
  { label: 'December', value: 12 }
])

// ==================== METHODS ====================
const fetchPayslipHistory = async (page = 1) => {
  if (!props.employeeId) {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Employee ID not found',
      life: 3000
    })
    return
  }

  loading.value = true
  try {
    // Set auth token

    const response = await hrService.api.get(`/api/payroll/payslip/${props.employeeId}`, {
      params: {
        year: selectedYear.value || undefined,
        month: selectedMonth.value || undefined,
        page: page
      }
    })

    if (response.data.success) {
      // The data is paginated, so we need to extract the array from data.data
      const responseData = response.data.data
      
      // Set payslip history from the paginated data
      payslipHistory.value = responseData.data || []
      
      // Set pagination info
      pagination.value = {
        current_page: responseData.current_page,
        last_page: responseData.last_page,
        per_page: responseData.per_page,
        total: responseData.total,
        from: responseData.from,
        to: responseData.to,
        next_page_url: responseData.next_page_url,
        prev_page_url: responseData.prev_page_url,
        links: responseData.links || []
      }
      
      // Set summary from the main response
      payrollSummary.value = response.data.summary
    }
  } catch (error) {
    console.error('Failed to fetch payslip history:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to fetch payslip history',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const changePage = (page: number) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    fetchPayslipHistory(page)
  }
}

const getPayPeriodName = (payslip: Payslip): string => {
  // Try to get from pay_period object first
  if (payslip.pay_period?.name) {
    return payslip.pay_period.name
  }
  
  // Fallback: try to get from items or create a default name
  return `Period ${payslip.pay_period_id || ''}`
}

const getEarnings = (payslip: Payslip): PayslipItem[] => {
  return payslip.items?.earnings || []
}

const getDeductions = (payslip: Payslip): PayslipItem[] => {
  const fromItems = payslip.items?.deductions || []
  if (fromItems.length > 0) return fromItems

  const fallback: PayslipItem[] = []
  if (Number(payslip.deductions_total || 0) > 0) {
    fallback.push({
      id: -1,
      payroll_id: payslip.id,
      type: 'deduction',
      name: 'Employee Deductions',
      amount: Number(payslip.deductions_total || 0),
    })
  }
  if (Number(payslip.tax_amount || 0) > 0) {
    fallback.push({
      id: -2,
      payroll_id: payslip.id,
      type: 'tax',
      name: 'Withholding Tax',
      amount: Number(payslip.tax_amount || 0),
    })
  }
  return fallback
}

const calculateGrossPay = (payslip: Payslip): number => {
  // Use pre-calculated gross_pay if available
  if (payslip.gross_pay) {
    return payslip.gross_pay
  }
  
  // Otherwise calculate it
  return Number(payslip.base_salary || 0) +
    Number(payslip.overtime_amount || 0) +
    Number(payslip.bonuses_total || 0) +
    Number(payslip.allowances_total || 0)
}

const getStatusSeverity = (status: string): 'success' | 'info' | 'warn' | 'secondary' | 'danger' => {
  const map: Record<string, any> = {
    'draft': 'secondary',
    'processing': 'info',
    'approved': 'success',
    'paid': 'success',
    'cancelled': 'danger'
  }
  return map[status] || 'info'
}

const formatNumber = (value: number | string): string => {
  if (value === null || value === undefined) return '0.00'
  const num = typeof value === 'string' ? parseFloat(value) : value
  return num.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')
}

const formatDate = (dateString: string | null): string => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDateRange = (start: string | null, end: string | null): string => {
  if (!start || !end) return ''
  return `${formatDate(start)} - ${formatDate(end)}`
}

const formatEmployeeCode = (employeeId: number | string | null | undefined): string => {
  const numericId = Number(employeeId || 0)
  if (!numericId || Number.isNaN(numericId)) return 'N/A'
  const year = new Date().getFullYear()
  return `SM-${year}-${String(numericId).padStart(5, '0')}`
}

const viewPayslip = async (payslip: Payslip) => {
  try {
    // Fetch detailed payslip data
    const response = await hrService.api.get(`/api/payrolls/${payslip.id}/payslip`)

    // Merge with existing data
    selectedPayslip.value = {
      ...payslip,
      ...response.data.payslip
    }

    showPayslipDialog.value = true

    // Emit event to parent
    emit('view-payslip', payslip)
  } catch (error) {
    // If API fails, just show what we have
    selectedPayslip.value = payslip
    showPayslipDialog.value = true

    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Showing basic payslip data',
      life: 3000
    })
  }
}

const downloadPayslip = async (payslip: Payslip | null) => {
  if (!payslip) return

  try {
    toast.add({
      severity: 'info',
      summary: 'Downloading',
      detail: `Downloading payslip for ${getPayPeriodName(payslip)}`,
      life: 2000
    })

    // Emit event to parent
    emit('download-payslip', payslip)

    // Download PDF
    const response = await hrService.api.get(`/api/payrolls/${payslip.id}/payslip/pdf`, {
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `payslip_${getPayPeriodName(payslip).replace(/\s+/g, '_')}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to download payslip',
      life: 3000
    })
  }
}

const printPayslip = async (payslip: Payslip | null) => {
  if (!payslip) return

  try {
    // Emit event to parent
    emit('print-payslip', payslip)

    // Reuse PayrollView-style print rendering (client-side HTML -> browser print).
    const deductionRows = getDeductions(payslip).length
      ? getDeductions(payslip)
          .map((item) => `<tr><td>${item.name}</td><td>- ${formatNumber(Number(item.amount || 0))}</td></tr>`)
          .join('')
      : '<tr><td colspan="2" style="text-align:left;color:#666;">No deduction items</td></tr>'

    const html = `
      <!DOCTYPE html>
      <html>
      <head>
        <title>Payslip - ${props.employeeName || payslip.employee_name || 'Employee'}</title>
        <style>
          body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; color: #333; }
          h2 { text-align: center; margin-bottom: 4px; }
          .subtitle { text-align: center; color: #666; margin-bottom: 16px; font-size: 11px; }
          .section { margin-bottom: 12px; }
          .section-title { font-weight: bold; background: #f0f0f0; padding: 4px 8px; border-left: 3px solid #333; margin-bottom: 6px; }
          table { width: 100%; border-collapse: collapse; }
          td { padding: 4px 8px; }
          td:last-child { text-align: right; }
          .total-row td { font-weight: bold; border-top: 2px solid #333; }
          .net-row td { font-weight: bold; font-size: 14px; background: #e8f5e9; }
          .header-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px; font-size: 11px; }
          .header-grid div { padding: 2px 0; }
          .label { color: #666; }
          @media print { body { margin: 10px; } }
        </style>
      </head>
      <body>
        <h2>PAYSLIP</h2>
        <div class="subtitle">${getPayPeriodName(payslip)}</div>

        <div class="header-grid">
          <div><span class="label">Employee:</span> <strong>${props.employeeName || payslip.employee_name || 'N/A'}</strong></div>
          <div><span class="label">Employee ID:</span> ${formatEmployeeCode(payslip.employee_id)}</div>
          <div><span class="label">Status:</span> ${(payslip.status || '').toUpperCase()}</div>
          <div><span class="label">Payment Date:</span> ${formatDate(payslip.payment_date)}</div>
        </div>

        <div class="section">
          <div class="section-title">EARNINGS</div>
          <table>
            <tr><td>Base Salary</td><td>${formatNumber(payslip.base_salary || 0)}</td></tr>
            <tr><td>Overtime</td><td>${formatNumber(payslip.overtime_amount || 0)}</td></tr>
            <tr><td>Bonuses</td><td>${formatNumber(payslip.bonuses_total || 0)}</td></tr>
            <tr><td>Allowances</td><td>${formatNumber(payslip.allowances_total || 0)}</td></tr>
            <tr class="total-row"><td>Gross Pay</td><td>${formatNumber(calculateGrossPay(payslip))}</td></tr>
          </table>
        </div>

        <div class="section">
          <div class="section-title">DEDUCTIONS</div>
          <table>
            ${deductionRows}
            <tr class="total-row"><td>Total Deductions</td><td>- ${formatNumber((payslip.deductions_total || 0) + (payslip.tax_amount || 0))}</td></tr>
          </table>
        </div>

        <div class="section">
          <table>
            <tr class="net-row"><td>NET PAY</td><td>${formatNumber(payslip.net_salary || 0)}</td></tr>
          </table>
        </div>

        <div style="margin-top:20px; text-align:center; font-size:10px; color:#999;">
          Printed on: ${new Date().toLocaleString('en-PH')}
        </div>
      </body>
      </html>
    `

    const win = window.open('', '_blank', 'width=700,height=900')
    if (win) {
      win.document.write(html)
      win.document.close()
      win.focus()
      setTimeout(() => win.print(), 400)
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to print payslip',
      life: 3000
    })
  }
}

const generatePayslip = () => {
  if (!selectedYear.value || !selectedMonth.value) {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Please select year and month',
      life: 3000
    })
    return
  }

  // Emit event to parent
  emit('generate-payslip', selectedYear.value, selectedMonth.value)

  toast.add({
    severity: 'info',
    summary: 'Generating',
    detail: `Generating payslip for ${selectedMonth.value}/${selectedYear.value}`,
    life: 2000
  })

  // After generation, refresh the list
  setTimeout(() => {
    fetchPayslipHistory()
  }, 1000)
}

const resetFilters = () => {
  selectedYear.value = null
  selectedMonth.value = null
  fetchPayslipHistory(1)
}

const exportAllPayslips = async () => {
  if (!payslipHistory.value.length) return

  try {
    // Emit event to parent
    emit('export-all', selectedYear.value ?? currentYear, selectedMonth.value ?? (new Date().getMonth() + 1))

    const response = await hrService.api.get('/api/payrolls/export', {
      params: {
        employee_id: props.employeeId,
        year: selectedYear.value,
        month: selectedMonth.value
      },
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `payslips_${props.employeeId}_${selectedYear.value}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()

    toast.add({
      severity: 'success',
      summary: 'Exported',
      detail: 'Payslips exported successfully',
      life: 3000
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to export payslips',
      life: 3000
    })
  }
}

const printPayrollRundown = async () => {
  try {
    const response = await hrService.api.get(`/api/payroll/payslip/${props.employeeId}/rundown/print`, {
      params: {
        year: selectedYear.value || undefined,
        month: selectedMonth.value || undefined,
      },
      responseType: 'blob'
    })

    const file = new Blob([response.data], { type: 'application/pdf' })
    const fileURL = window.URL.createObjectURL(file)
    window.open(fileURL, '_blank')
    setTimeout(() => window.URL.revokeObjectURL(fileURL), 60_000)
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to print payroll rundown',
      life: 3000
    })
  }
}

// ==================== WATCHERS ====================
watch(() => props.employeeId, (newId) => {
  if (newId) {
    fetchPayslipHistory()
  }
}, { immediate: true })

// ==================== EXPOSE ====================
defineExpose({
  fetchPayslipHistory,
  refresh: fetchPayslipHistory
})
</script>













