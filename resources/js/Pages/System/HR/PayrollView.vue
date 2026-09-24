<template>
  <div class="payroll-list p-4">
    <!-- Header with Batch Info -->
    <div v-if="batchInfo" class="mb-4 p-3 rounded-lg">
      <div class="flex justify-between items-center">
        <div>
          <h3 class="text-lg font-semibold">{{ batchInfo.name }}</h3>
          <p class="text-sm text-gray-600">
            {{ formatDate(batchInfo.start_date) }} - {{ formatDate(batchInfo.end_date) }}
            | Pay Date: {{ formatDate(batchInfo.pay_date) }}
          </p>
        </div>
        <div class="flex gap-2">
          <Badge :severity="getStatusSeverity(batchInfo.status)" :value="batchInfo.status" class="capitalize" />

        </div>
      </div>
    </div>

    <!-- Search and Filters -->
    <div class="mb-4 flex flex-wrap items-center gap-2">
      <IconField iconPosition="left" class="w-full sm:w-64">
        <InputIcon>
          <i class="pi pi-search" />
        </InputIcon>
        <InputText v-model="filters.search" placeholder="Search employee..." size="small" class="w-full" @input="debouncedFetch" />
      </IconField>
      <Select v-model="filters.branch" :options="branches" placeholder="All Branches" showClear class="w-full sm:w-40" size="small"
        @change="applyFilters" />
      <Select v-model="filters.department" :options="departments" placeholder="All Departments" showClear class="w-full sm:w-40"
        size="small" @change="applyFilters" />
      <Select v-model="filters.status" :options="statusOptions" placeholder="All Status" showClear class="w-full sm:w-36"
        size="small" @change="applyFilters" />
      <Button v-if="hasDraftPayrolls" label="Bulk Submit" icon="pi pi-send" size="small"
        :disabled="selectedItems.length === 0 || loading" :loading="bulkSubmitting" @click="bulkSubmitForApproval" />
      <Button label="Bulk Paid" icon="pi pi-money-bill" severity="success" size="small" outlined
        :disabled="selectedApprovedItems.length === 0 || loading" :loading="bulkMarkingPaid" @click="bulkMarkPaid" />
      <Button label="Export" icon="pi pi-file-excel" severity="success" outlined size="small" @click="exportPayroll"
        :disabled="loading || payrollItems.length === 0" />
    </div>
  
    <!-- Payroll Table -->
    <DataTable :value="filteredPayrollItems" :paginator="true" :rows="10" :rowsPerPageOptions="[10, 20, 50]"
      tableStyle="min-width: 125rem" :loading="loading" removableSort scrollable
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
      currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries" sortMode="multiple" rowHover
      v-model:selection="selectedItems" selectionMode="multiple">
      <!-- Selection Column -->
      <Column selectionMode="multiple" frozen alignFrozen="left" style="width: 3rem; min-width: 3rem"
        headerStyle="width: 3rem; min-width: 3rem" />
  
      <!-- Employee Columns -->
      <Column class="text-xs" field="employeeName" header="Employee" sortable frozen alignFrozen="left"
        style="width: 14rem; min-width: 14rem" headerStyle="width: 14rem; min-width: 14rem">
        <template #body="{ data }">
          <div class="font-medium">{{ data.employeeName }}</div>
          <small class="text-gray-500">{{ data.employeeId }}</small>
        </template>
      </Column>
  
      <Column class="text-xs" field="branch" header="Branch" sortable>
        <template #body="{ data }">
          {{ data.branch || 'N/A' }}
        </template>
      </Column>
  
      <Column class="text-xs" field="department" header="Department" sortable>
        <template #body="{ data }">
          {{ data.department || 'N/A' }}
        </template>
      </Column>

      <Column class="text-xs" field="absentDays" header="Absent" sortable />
      <Column class="text-xs" field="leaveDays" header="On Leave" sortable />
      <Column class="text-xs" field="breakMinutes" header="Break (min)" sortable />
      <Column class="text-xs" field="lateMinutes" header="Late (min)" sortable />
  
      <!-- Financial Columns -->
      <Column class="text-xs" field="baseSalary" header="Base Salary" sortable>
        <template #body="{ data }">
          {{ formatCurrency(data.baseSalary) }}
        </template>
      </Column>
  
      <Column class="text-xs" field="salaryPerHour" header="Rate/Hour" sortable>
        <template #body="{ data }">
          {{ formatCurrency(data.salaryPerHour) }}
        </template>
      </Column>
  
      <!-- Earnings -->
      <!-- <Column class="text-xs" field="basicPay" header="Basic Pay" sortable>
              <template #body="{ data }">
                {{ formatCurrency(data.basicPay) }}
              </template>
            </Column> -->
  
      <Column class="text-xs" field="overtimeHours" header="OT Hrs" sortable />
      <Column class="text-xs" field="overtimePay" header="OT Pay" sortable>
        <template #body="{ data }">
          {{ formatCurrency(data.overtimePay) }}
        </template>
      </Column>
  
      <Column class="text-xs" field="allowanceAmount" header="Allowance" sortable>
        <template #body="{ data }">
          {{ formatCurrency(data.allowanceAmount) }}
        </template>
      </Column>
      <Column class="text-xs" field="incentiveAmount" header="Incentives" sortable>
        <template #body="{ data }">{{ formatCurrency(data.incentiveAmount) }}</template>
      </Column>
  
      <!-- Deductions -->
      <Column class="text-xs" header="Itemized Deductions" style="min-width: 13rem">
        <template #body="{ data }">
          <div v-if="!data.deductionItems.length" class="text-slate-500">None</div>
          <div v-else class="space-y-1.5 py-1">
            <div v-for="deduction in data.deductionItems" :key="`${data.id}-${deduction.name}`"
              class="flex items-start justify-between gap-4 border-b border-slate-100 pb-1 last:border-0 last:pb-0">
              <span class="min-w-0 text-slate-600">{{ deduction.name }}</span>
              <span class="shrink-0 tabular-nums font-medium text-slate-900">{{ formatCurrency(deduction.amount) }}</span>
            </div>
          </div>
        </template>
      </Column>
  
      <Column class="text-xs" field="lateDeductions" header="Late" sortable>
        <template #body="{ data }">
          <span class="text-red-600">-{{ formatCurrency(data.lateDeductions) }}</span>
        </template>
      </Column>
      <Column class="text-xs" field="absenceDeduction" header="Absence Ded." sortable>
        <template #body="{ data }">-{{ formatCurrency(data.absenceDeduction) }}</template>
      </Column>

      <!-- Totals -->
      <Column class="text-xs" field="grossPay" style="width: 2%;" header="Gross Pay"  sortable>
        <template #body="{ data }">
          <span class="font-bold text-green-600">+{{ formatCurrency(data.grossPay) }}</span>
        </template>
      </Column>
  
      <Column class="text-xs" field="totalDeductions" header="Total Deductions" sortable>
        <template #body="{ data }">
          <span class="font-bold text-red-600">-{{ formatCurrency(data.totalDeductions) }}</span>
        </template>
      </Column>
  
      <Column class="text-xs" field="netPay" header="Net Pay" sortable>
        <template #body="{ data }">
          <span class="font-bold text-blue-600">{{ formatCurrency(data.netPay) }}</span>
        </template>
      </Column>
  
      <!-- Status -->
      <Column class="text-xs" field="status" header="Status" sortable>
        <template #body="{ data }">
          <Badge :severity="getStatusSeverity(data.status)" :value="data.status" class="capitalize" />
        </template>
      </Column>
  
      <!-- Actions -->
      <Column header="Actions" >
        <template #body="{ data }">
          <div class="flex gap-2">
            <Button icon="pi pi-eye" severity="secondary" text size="small"
              @click="openPayslipDetails(data)" v-tooltip="'View pay breakdown'" aria-label="View pay breakdown" />
            <Button v-if="data.status === 'draft' || data.status === 'calculated'" icon="pi pi-send" severity="info" text
              @click="submitForApproval(data)" v-tooltip="'Submit for approval'" :loading="data.submitting" />
            <Button v-if="data.status === 'approved'" icon="pi pi-money-bill" severity="success" text
              @click="markPayrollPaid(data)" v-tooltip="'Mark payroll as paid'" :loading="data.paying" />
            <Button icon="pi pi-print" severity="secondary" text @click="printPayslip(data)"
              v-tooltip="'Print payslip'" />
          </div>
        </template>
      </Column>
  
      <template #empty>
        <div class="text-center py-8 text-gray-500">
          <i class="pi pi-file text-4xl mb-2"></i>
          <p>No payroll data found for this period</p>
        </div>
      </template>
    </DataTable>

    <Dialog v-model:visible="showPayslipDialog" header="Payroll Breakdown" :style="{ width: 'min(760px, 95vw)' }" modal>
      <div v-if="selectedPayslip" class="space-y-4 text-sm text-slate-800">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div>
              <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Employee</p>
              <p class="font-semibold text-slate-900">{{ selectedPayslip.employeeName }}</p>
            </div>
            <Badge :severity="getStatusSeverity(selectedPayslip.status)" :value="selectedPayslip.status" class="capitalize" />
          </div>
          <div class="grid grid-cols-1 gap-x-6 gap-y-2 text-xs sm:grid-cols-2">
            <p><span class="text-slate-500">Employee #</span><span class="ml-2 font-medium">{{ selectedPayslip.employeeId || '-' }}</span></p>
            <p><span class="text-slate-500">Branch</span><span class="ml-2 font-medium">{{ selectedPayslip.branch || '-' }}</span></p>
            <p><span class="text-slate-500">Department</span><span class="ml-2 font-medium">{{ selectedPayslip.department || '-' }}</span></p>
            <p><span class="text-slate-500">Period</span><span class="ml-2 font-medium">{{ batchInfo ? formatDate(batchInfo.start_date) : '-' }} - {{ batchInfo ? formatDate(batchInfo.end_date) : '-' }}</span></p>
            <p><span class="text-slate-500">Pay date</span><span class="ml-2 font-medium">{{ selectedPayslip.paymentDate ? formatDate(selectedPayslip.paymentDate) : (batchInfo ? formatDate(batchInfo.pay_date) : '-') }}</span></p>
            <p><span class="text-slate-500">Payment method</span><span class="ml-2 font-medium">{{ selectedPayslip.paymentMethod || '-' }}</span></p>
            <p v-if="selectedPayslip.referenceNumber"><span class="text-slate-500">Reference #</span><span class="ml-2 font-medium">{{ selectedPayslip.referenceNumber }}</span></p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div class="rounded-xl border border-slate-200 p-4">
            <p class="mb-3 font-semibold text-slate-900">Earnings</p>
            <div class="space-y-2 text-xs">
              <p class="flex justify-between gap-4"><span>Base salary</span><span class="shrink-0 tabular-nums">{{ formatCurrency(selectedPayslip.baseSalary) }}</span></p>
              <p class="flex justify-between gap-4"><span>Overtime</span><span class="shrink-0 tabular-nums">{{ formatCurrency(selectedPayslip.overtimePay) }}</span></p>
              <p class="flex justify-between gap-4"><span>Allowance</span><span class="shrink-0 tabular-nums">{{ formatCurrency(selectedPayslip.allowanceAmount) }}</span></p>
              <p class="flex justify-between gap-4"><span>Incentives</span><span class="shrink-0 tabular-nums">{{ formatCurrency(selectedPayslip.incentiveAmount) }}</span></p>
              <p v-if="selectedPayslip.otherBonusPay" class="flex justify-between gap-4"><span>Other premiums</span><span class="shrink-0 tabular-nums">{{ formatCurrency(selectedPayslip.otherBonusPay) }}</span></p>
              <p class="flex justify-between gap-4 border-t border-slate-200 pt-2 font-semibold text-slate-900"><span>Gross pay</span><span class="shrink-0 tabular-nums">{{ formatCurrency(selectedPayslip.grossPay) }}</span></p>
            </div>
          </div>

          <div class="rounded-xl border border-slate-200 p-4">
            <p class="mb-3 font-semibold text-slate-900">Deductions</p>
            <div class="space-y-2 text-xs" v-if="selectedPayslip.deductionItems.length">
              <p v-for="deduction in selectedPayslip.deductionItems" :key="`${selectedPayslip.id}-${deduction.name}`" class="flex justify-between gap-4">
                <span>{{ deduction.name }}</span>
                <span class="shrink-0 tabular-nums">{{ formatCurrency(deduction.amount) }}</span>
              </p>
            </div>
            <p v-else class="text-xs text-slate-500">No itemized deductions</p>
            <div class="mt-2 space-y-2 text-xs">
              <p class="flex justify-between gap-4"><span>Late deduction</span><span class="shrink-0 tabular-nums">{{ formatCurrency(selectedPayslip.lateDeductions) }}</span></p>
              <p class="flex justify-between gap-4"><span>Absence</span><span class="shrink-0 tabular-nums">{{ formatCurrency(selectedPayslip.absenceDeduction) }}</span></p>
              <p class="flex justify-between gap-4"><span>Half-day</span><span class="shrink-0 tabular-nums">{{ formatCurrency(selectedPayslip.halfDayDeduction) }}</span></p>
              <p class="flex justify-between gap-4"><span>Income tax</span><span class="shrink-0 tabular-nums">{{ formatCurrency(selectedPayslip.taxAmount) }}</span></p>
              <p class="flex justify-between gap-4 border-t border-slate-200 pt-2 font-semibold text-slate-900"><span>Total deductions</span><span class="shrink-0 tabular-nums">{{ formatCurrency(selectedPayslip.totalDeductions) }}</span></p>
            </div>
          </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">
          <p class="font-semibold text-slate-900">Attendance in this pay period</p>
          <p class="mt-1">Absent {{ selectedPayslip.absentDays }} days | Leave {{ selectedPayslip.leaveDays }} days | Break {{ selectedPayslip.breakMinutes }} min | Late {{ selectedPayslip.lateMinutes }} min | OT {{ selectedPayslip.overtimeHours }} hrs</p>
        </div>

        <div class="rounded-xl border border-orange-200 bg-orange-50 p-4">
          <p class="flex justify-between gap-4 text-base font-semibold text-slate-900">
            <span>Net Pay</span>
            <span class="shrink-0 tabular-nums">{{ formatCurrency(selectedPayslip.netPay) }}</span>
          </p>
        </div>
      </div>

      <template #footer>
        <Button label="Close" text @click="showPayslipDialog = false" />
        <Button label="Print" icon="pi pi-print" severity="secondary" @click="selectedPayslip && printPayslip(selectedPayslip)" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRoute, useRouter } from 'vue-router'
import hrService from '@/services/hr.services'
import financeService from '@/services/finance.service'
import { debounce } from 'lodash'
import { printPayrollPayslip } from '@/utils/payrollPayslipPrint'

// ==================== INTERFACES ====================
interface DeductionItem {
  name: string
  amount: number
  calculation_type?: string
  rate?: number | null
}

interface PayrollItem {
  id: string
  employeeId: string
  employeeName: string
  branch: string
  department: string
  baseSalary: number
  salaryPerHour: number
  // Earnings
  basicPay: number
  overtimePay: number
  allowanceAmount: number
  incentiveAmount: number
  otherBonusPay: number
  absentDays: number
  leaveDays: number
  breakMinutes: number
  lateMinutes: number
  overtimeHours: number
  // Deductions
  deductionItems: DeductionItem[]
  lateDeductions: number
  absenceDeduction: number
  halfDayDeduction: number
  taxAmount: number
  otherDeductions: number
  // Totals
  grossPay: number
  totalDeductions: number
  netPay: number
  status: 'draft' | 'calculated' | 'processing' | 'approved' | 'paid' | 'cancelled'
  remarks?: string
  payroll_id?: number
  paymentDate?: string | null
  paymentMethod?: string | null
  referenceNumber?: string | null
}

interface BatchInfo {
  id: number
  name: string
  store_name?: string | null
  start_date: string
  end_date: string
  pay_date: string
  status: string
}

interface Filters {
  search: string
  branch: string | null
  department: string | null
  status: string | null
}

interface Statistics {
  totalEmployees: number
  totalGross: number
  totalDeductions: number
  totalNet: number
  byDepartment: Record<string, any>
  byStatus: Record<string, any>
}

// ==================== PROPS & EMITS ====================
const props = defineProps<{
  batchId?: string
}>()

// ==================== STATE ====================
const route = useRoute()
const router = useRouter()
const toast = useToast()
const loading = ref(false)
const bulkSubmitting = ref(false)
const bulkMarkingPaid = ref(false)
const selectedItems = ref<PayrollItem[]>([])
const showPayslipDialog = ref(false)
const selectedPayslip = ref<PayrollItem | null>(null)

// Get batch ID from route params if not passed as prop
const batchId = computed(() => props.batchId || route.params.id as string)

// Data
const payrollItems = ref<PayrollItem[]>([])
const batchInfo = ref<BatchInfo | null>(null)
const statistics = ref<Statistics>({
  totalEmployees: 0,
  totalGross: 0,
  totalDeductions: 0,
  totalNet: 0,
  byDepartment: {},
  byStatus: {}
})

// Filters
const filters = ref<Filters>({
  search: '',
  branch: null,
  department: null,
  status: null
})

// Options for filters
const branches = ref<string[]>([])
const departments = ref<string[]>([])
const statusOptions = ref(['draft', 'calculated', 'processing', 'approved', 'paid', 'cancelled'])

// ==================== COMPUTED ====================
const hasDraftPayrolls = computed(() =>
  payrollItems.value.some(i => i.status === 'draft' || i.status === 'calculated')
)

const selectedApprovedItems = computed(() =>
  selectedItems.value.filter(i => i.status === 'approved' && !!i.payroll_id)
)

const filteredPayrollItems = computed(() => {
  return payrollItems.value.filter(item => {
    const matchesSearch = !filters.value.search ||
      item.employeeName.toLowerCase().includes(filters.value.search.toLowerCase()) ||
      item.employeeId.toLowerCase().includes(filters.value.search.toLowerCase())

    const matchesBranch = !filters.value.branch || item.branch === filters.value.branch
    const matchesDept = !filters.value.department || item.department === filters.value.department
    const matchesStatus = !filters.value.status || item.status === filters.value.status

    return matchesSearch && matchesBranch && matchesDept && matchesStatus
  })
})

// ==================== METHODS ====================
const fetchPayrollData = async () => {
  if (!batchId.value) return

  loading.value = true
  try {
    // Set auth token

    // Fetch payrolls for this batch/period
    const response = await hrService.api.get('/api/payroll', {
      params: {
        pay_period_id: batchId.value
      }
    })

    if (response.data.success) {
      // Transform API data to match component interface
      payrollItems.value = transformPayrollData(response.data.data)

      // Extract unique branches and departments for filters
      extractFilterOptions(payrollItems.value)

      // Calculate statistics
      calculateStatistics(payrollItems.value)

      // Fetch batch info separately if needed
      await fetchBatchInfo()
    }
  } catch (error) {
    console.error('Failed to fetch payroll data:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to fetch payroll data',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const fetchBatchInfo = async () => {
  try {
    const response = await hrService.api.get(`/api/payroll/pay-periods/${batchId.value}`)
    if (response.data.success) {
      batchInfo.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to fetch batch info:', error)
  }
}

const transformPayrollData = (apiData: any[]): PayrollItem[] => {
  return apiData.map((item: any) => {
    const rawDeductionItems = Array.isArray(item.deduction_items) ? item.deduction_items : []
    const deductionItems = rawDeductionItems
      .map((d: any) => ({
        name: String(d?.name || ''),
        amount: Number(d?.amount || 0),
        calculation_type: d?.calculation_type,
        rate: d?.rate ?? null
      }))
      // Late deduction has a dedicated column in this table.
      .filter((d: DeductionItem) => d.name.toLowerCase() !== 'late deduction')

    const grossPay = [
      item.base_salary,
      item.overtime_amount,
      item.bonuses_total,
      item.allowances_total
    ].reduce((sum, value) => sum + (parseFloat(value) || 0), 0)

    const branchName = typeof item.employee?.branch === 'string'
      ? item.employee.branch
      : (item.employee?.branch?.name || item.employee?.branch_name || null)

    const employeeFullName = item.employee
      ? `${item.employee.fname || ''} ${item.employee.lname || ''}`.trim()
      : (item.employee_name || '')

    return {
      id: item.id?.toString() || '',
      employeeId: item.employee?.employee_number || item.employee_id?.toString() || '',
      employeeName: employeeFullName || 'Unknown Employee',
      branch: branchName || 'N/A',
      department: item.employee?.department || item.department || 'N/A',
      baseSalary: item.base_salary || 0,
      salaryPerHour: Number(item.hourly_rate || 0),
      basicPay: item.base_salary || 0,
      overtimePay: item.overtime_amount || 0,
      allowanceAmount: item.allowances_total || 0,
      incentiveAmount: Array.isArray(item.incentive_items)
        ? item.incentive_items.reduce((sum: number, entry: any) => sum + Number(entry.amount || 0), 0)
        : 0,
      otherBonusPay: Math.max(0, Number(item.bonuses_total || 0)
        - (Array.isArray(item.incentive_items)
          ? item.incentive_items.reduce((sum: number, entry: any) => sum + Number(entry.amount || 0), 0)
          : 0)),
      absentDays: Number(item.period_metrics?.absent_days || 0),
      leaveDays: Number(item.period_metrics?.leave_days || 0),
      breakMinutes: Number(item.period_metrics?.break_minutes || 0),
      lateMinutes: Number(item.period_metrics?.late_minutes || 0),
      overtimeHours: Number(item.overtime_hours || 0),
      deductionItems,
      lateDeductions: Number(item.late_deduction || 0),
      absenceDeduction: Number(item.period_metrics?.absence_deduction || 0),
      halfDayDeduction: Number(item.period_metrics?.half_day_deduction || 0),
      taxAmount: Number(item.tax_amount || 0),
      otherDeductions: 0,
      grossPay: grossPay,
      totalDeductions: Number(item.deductions_total || 0) + Number(item.tax_amount || 0),
      netPay: item.net_salary || 0,
      status: item.status || 'draft',
      payroll_id: item.id,
      paymentDate: item.payment_date || item.paid_at || null,
      paymentMethod: item.payment_method || null,
      referenceNumber: item.reference_number || null,
    }
  })
}

const extractFilterOptions = (items: PayrollItem[]) => {
  const branchSet = new Set<string>()
  const deptSet = new Set<string>()

  items.forEach(item => {
    if (item.branch && item.branch !== 'N/A') branchSet.add(item.branch)
    if (item.department && item.department !== 'N/A') deptSet.add(item.department)
  })

  branches.value = Array.from(branchSet).sort()
  departments.value = Array.from(deptSet).sort()
}

const calculateStatistics = (items: PayrollItem[]) => {
  const totalGross = items.reduce((sum, item) => sum + item.grossPay, 0)
  const totalDeductions = items.reduce((sum, item) => sum + item.totalDeductions, 0)
  const totalNet = items.reduce((sum, item) => sum + item.netPay, 0)

  // Group by department
  const byDepartment: Record<string, any> = {}
  items.forEach(item => {
    const dept = item.department || 'Unassigned'
    if (!byDepartment[dept]) {
      byDepartment[dept] = {
        count: 0,
        totalNet: 0
      }
    }
    byDepartment[dept].count++
    byDepartment[dept].totalNet += item.netPay
  })

  // Group by status
  const byStatus: Record<string, any> = {}
  items.forEach(item => {
    if (!byStatus[item.status]) {
      byStatus[item.status] = {
        count: 0,
        totalNet: 0
      }
    }
    byStatus[item.status].count++
    byStatus[item.status].totalNet += item.netPay
  })

  statistics.value = {
    totalEmployees: items.length,
    totalGross,
    totalDeductions,
    totalNet,
    byDepartment,
    byStatus
  }
}

const formatDate = (date: string): string => {
  if (!date) return 'N/A'
  return new Intl.DateTimeFormat('en-PH', {
    year: 'numeric',
    month: 'short',
    day: '2-digit'
  }).format(new Date(date))
}

const formatCurrency = (value: number): string => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2
  }).format(value)
}

const getStatusSeverity = (status: string): 'info' | 'success' | 'warn' | 'secondary' | 'danger' => {
  const map: Record<string, any> = {
    'draft': 'secondary',
    'calculated': 'secondary',
    'processing': 'warn',
    'approved': 'success',
    'paid': 'success',
    'cancelled': 'danger',
    // pay period statuses
    'locked': 'info',
    'completed': 'success',
  }
  return map[status] || 'info'
}

const debouncedFetch = debounce(() => {
  // Client-side filtering only, no need to refetch
}, 300)

const applyFilters = () => {
  // Client-side filtering handled by computed property
  toast.add({
    severity: 'info',
    summary: 'Filters Applied',
    detail: `Showing ${filteredPayrollItems.value.length} of ${payrollItems.value.length} employees`,
    life: 3000
  })
}

const goBack = () => {
  router.push({ name: 'hr.payroll.list' })
}

const submitForApproval = async (item: PayrollItem) => {
  ; (item as any).submitting = true
  try {
    await hrService.api.post(`/api/payroll/${item.payroll_id}/submit`)
    item.status = 'processing'
    calculateStatistics(payrollItems.value)
    // Refresh batch info so period status badge updates
    await fetchBatchInfo()
    toast.add({
      severity: 'success',
      summary: 'Submitted',
      detail: `${item.employeeName}'s payroll submitted for approval`,
      life: 3000
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to submit for approval',
      life: 3000
    })
  } finally {
    ; (item as any).submitting = false
  }
}

const markPayrollPaid = async (item: PayrollItem) => {
  ;(item as any).paying = true
  try {
    await hrService.api.post(`/api/payroll/${item.payroll_id}/mark-paid`, {
      payment_date: new Date().toISOString().slice(0, 10),
      payment_method: 'bank_transfer',
    })
    item.status = 'paid'
    calculateStatistics(payrollItems.value)
    await fetchBatchInfo()
    toast.add({
      severity: 'success',
      summary: 'Payroll Paid',
      detail: `${item.employeeName}'s payroll has been marked as paid.`,
      life: 3000,
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Mark Paid Failed',
      detail: error?.response?.data?.message || 'Failed to mark payroll as paid.',
      life: 3000,
    })
  } finally {
    ;(item as any).paying = false
  }
}

const openPayslipDetails = (item: PayrollItem) => {
  selectedPayslip.value = item
  showPayslipDialog.value = true
}

const bulkSubmitForApproval = async () => {
  if (selectedItems.value.length === 0) return

  const eligibleItems = selectedItems.value.filter(
    i => i.status === 'draft' || i.status === 'calculated'
  )
  if (eligibleItems.length === 0) {
    toast.add({
      severity: 'warn',
      summary: 'No Eligible Items',
      detail: 'Selected payrolls must be in draft status to submit for approval',
      life: 3000
    })
    return
  }

  bulkSubmitting.value = true
  try {
    const ids = eligibleItems.map(i => i.payroll_id).filter(Boolean)
    const response = await hrService.api.post('/api/payroll/bulk-submit', { payroll_ids: ids })

    if (response.data.success) {
      // Update local statuses
      eligibleItems.forEach(item => { item.status = 'processing' })
      calculateStatistics(payrollItems.value)
      selectedItems.value = []
      // Refresh batch info so period status badge updates
      await fetchBatchInfo()
      toast.add({
        severity: 'success',
        summary: 'Submitted',
        detail: response.data.message,
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to bulk submit payrolls',
      life: 3000
    })
  } finally {
    bulkSubmitting.value = false
  }
}

const bulkMarkPaid = async () => {
  const approvedItems = selectedApprovedItems.value

  if (!approvedItems.length) {
    toast.add({
      severity: 'warn',
      summary: 'No Eligible Items',
      detail: 'Select payroll rows with Approved status to mark them as paid.',
      life: 3000
    })
    return
  }

  bulkMarkingPaid.value = true
  try {
    const ids = approvedItems.map(i => Number(i.payroll_id)).filter(id => id > 0)
    const response = await financeService.bulkMarkPayrollPaid(ids, {
      payment_date: new Date().toISOString().slice(0, 10),
      payment_method: 'bank_transfer'
    })

    approvedItems.forEach(item => {
      item.status = 'paid'
    })
    calculateStatistics(payrollItems.value)
    selectedItems.value = []
    await fetchBatchInfo()

    toast.add({
      severity: 'success',
      summary: 'Bulk Paid Complete',
      detail: response?.message || `${approvedItems.length} payroll(s) marked as paid.`,
      life: 3000
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Bulk Paid Failed',
      detail: error?.response?.data?.message || 'Failed to bulk mark payrolls as paid.',
      life: 3000
    })
  } finally {
    bulkMarkingPaid.value = false
  }
}

const printPayslip = (item: PayrollItem) => {
  const period = batchInfo.value
  const opened = printPayrollPayslip({
    storeName: period?.store_name || 'Store name unavailable',
    employeeName: item.employeeName,
    employeeId: item.employeeId,
    department: item.department,
    branch: item.branch,
    periodName: period?.name || 'Payroll period',
    periodStart: period ? formatDate(period.start_date) : '-',
    periodEnd: period ? formatDate(period.end_date) : '-',
    payDate: item.paymentDate ? formatDate(item.paymentDate) : (period ? formatDate(period.pay_date) : '-'),
    payrollId: String(item.payroll_id || item.id || '-'),
    status: item.status,
    earnings: [
      { label: 'Base salary', amount: item.baseSalary },
      { label: 'Overtime pay', amount: item.overtimePay },
      { label: 'Allowances', amount: item.allowanceAmount },
      { label: 'Incentives', amount: item.incentiveAmount },
      { label: 'Other premiums', amount: item.otherBonusPay },
    ],
    deductions: [
      ...item.deductionItems.map((deduction) => ({ label: deduction.name, amount: deduction.amount })),
      { label: 'Late deduction', amount: item.lateDeductions },
      { label: 'Absence deduction', amount: item.absenceDeduction },
      { label: 'Half-day deduction', amount: item.halfDayDeduction },
      { label: 'Income tax', amount: item.taxAmount },
    ],
    grossPay: item.grossPay,
    totalDeductions: item.totalDeductions,
    netPay: item.netPay,
    absentDays: item.absentDays,
    leaveDays: item.leaveDays,
    lateMinutes: item.lateMinutes,
    overtimeHours: item.overtimeHours,
  })

  if (!opened) {
    toast.add({ severity: 'warn', summary: 'Print blocked', detail: 'Allow pop-ups to print this payslip.', life: 3000 })
  }
}

const exportPayroll = async () => {
  try {
    const response = await hrService.api.get(`/api/payroll/pay-periods/${batchId.value}/export`, {
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `payroll_${batchInfo.value?.name.replace(/\s+/g, '_')}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()

    toast.add({
      severity: 'success',
      summary: 'Exported',
      detail: 'Payroll exported successfully',
      life: 3000
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to export payroll',
      life: 3000
    })
  }
}

// ==================== WATCHERS ====================
watch(() => batchId.value, (newId) => {
  if (newId) {
    fetchPayrollData()
  }
})

// ==================== LIFECYCLE ====================
onMounted(() => {
  if (batchId.value) {
    fetchPayrollData()
  }
})
</script>

<style scoped>
.payroll-list {
  container-type: inline-size;
}

@container (min-width: 0px) and (max-width: 639px) {
  .payroll-list :deep(.p-datatable) {
    font-size: 0.8rem;
  }

  .payroll-list :deep(.p-inputnumber) {
    width: 100px;
  }
}

@container (min-width: 640px) and (max-width: 1023px) {
  .payroll-list :deep(.p-datatable) {
    font-size: 0.9rem;
  }

  .payroll-list :deep(.p-inputnumber) {
    width: 120px;
  }
}

@container (min-width: 1024px) {
  .payroll-list :deep(.p-datatable) {
    font-size: 1rem;
  }

  .payroll-list :deep(.p-inputnumber) {
    width: 140px;
  }
}
</style>
