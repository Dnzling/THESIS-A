<template>
  <div class="payroll-list p-4">
    <!-- Header with Batch Info -->
    <div v-if="batchInfo" class="mb-4 p-3 bg-gray-50 rounded-lg">
      <div class="flex justify-between items-center">
        <div>
          <h3 class="text-lg font-semibold">{{ batchInfo.name }}</h3>
          <p class="text-sm text-gray-600">
            {{ formatDate(batchInfo.start_date) }} - {{ formatDate(batchInfo.end_date) }}
            | Pay Date: {{ formatDate(batchInfo.pay_date) }}
          </p>
        </div>
        <div class="flex gap-2">
          <Tag :severity="getStatusSeverity(batchInfo.status)" :value="batchInfo.status" class="capitalize" />
          <Button label="Back to Batches" icon="pi pi-arrow-left" text @click="goBack" />
        </div>
      </div>
    </div>
  
    <!-- Search and Filters -->
    <div class="flex gap-2 mb-4">
      <IconField iconPosition="left" class="w-64">
        <InputIcon>
          <i class="pi pi-search" />
        </InputIcon>
        <InputText v-model="filters.search" placeholder="Search employee..." size="small" @input="debouncedFetch" />
      </IconField>
      <Select v-model="filters.branch" :options="branches" placeholder="All Branches" showClear class="w-40" size="small"
        @change="applyFilters" />
      <Select v-model="filters.department" :options="departments" placeholder="All Departments" showClear class="w-40"
        size="small" @change="applyFilters" />
      <Select v-model="filters.status" :options="statusOptions" placeholder="All Status" showClear class="w-36"
        size="small" @change="applyFilters" />
      <Button v-if="hasDraftPayrolls" label="Bulk Submit" icon="pi pi-send" severity="info" size="small" outlined
        :disabled="selectedItems.length === 0 || loading" :loading="bulkSubmitting" @click="bulkSubmitForApproval" />
      <Button label="Bulk Paid" icon="pi pi-money-bill" severity="success" size="small" outlined
        :disabled="selectedReleasedItems.length === 0 || loading" :loading="bulkMarkingPaid" @click="bulkMarkPaid" />
      <Button label="Export" icon="pi pi-file-excel" severity="success" outlined size="small" @click="exportPayroll"
        :disabled="loading || payrollItems.length === 0" />
    </div>
  
    <!-- Payroll Table -->
    <DataTable :value="filteredPayrollItems" :paginator="true" :rows="10" :rowsPerPageOptions="[10, 20, 50]"
      tableStyle="min-width: 110rem" :loading="loading" removableSort
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
      currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries" sortMode="multiple" rowHover
      v-model:selection="selectedItems" selectionMode="multiple">
      <!-- Selection Column -->
      <Column selectionMode="multiple" headerStyle="width: 3rem" />
  
      <!-- Employee Columns -->
      <Column class="text-xs" field="employeeName" header="Employee" sortable>
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
  
      <!-- Deductions -->
      <Column class="text-xs font-semibold" header="Deductions (Itemized)">
        <template #body="{ data }">
          <div v-if="!data.deductionItems.length" class="text-gray-500">No itemized deductions</div>
          <div v-for="deduction in data.deductionItems" :key="`${data.id}-${deduction.name}`">
            {{ deduction.name }}: {{ formatCurrency(deduction.amount) }}
          </div>
        </template>
      </Column>
  
      <Column class="text-xs" field="lateDeductions" header="Late" sortable>
        <template #body="{ data }">
          <span class="text-red-600">-{{ formatCurrency(data.lateDeductions) }}</span>
        </template>
      </Column>
  
      <Column class="text-xs" field="leaveDeductions" header="Leave" sortable>
        <template #body="{ data }">
          <span class="text-red-600">-{{ formatCurrency(data.leaveDeductions) }}</span>
        </template>
      </Column>
  
      <!-- Totals -->
      <Column class="text-xs" field="grossPay" header="Gross Pay" sortable>
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
          <Tag :severity="getStatusSeverity(data.status)" :value="data.status" class="capitalize" />
        </template>
      </Column>
  
      <!-- Actions -->
      <Column header="Actions" style="min-width: 120px">
        <template #body="{ data }">
          <div class="flex gap-2">
            <Button v-if="data.status === 'draft' || data.status === 'calculated'" icon="pi pi-send" severity="info" text
              @click="submitForApproval(data)" v-tooltip="'Submit for approval'" :loading="data.submitting" />
            <Button v-if="data.status === 'approved'" icon="pi pi-wallet" severity="help" text
              @click="releasePayroll(data)" v-tooltip="'Release payroll for distribution'" :loading="data.releasing" />
            <Button v-if="data.status === 'released'" icon="pi pi-money-bill" severity="success" text
              @click="markPayrollPaid(data)" v-tooltip="'Mark payroll as paid'" :loading="data.paying" />
            <Button v-if="data.status === 'paid'" icon="pi pi-receipt" severity="contrast" text
              @click="openPayslipDetails(data)" v-tooltip="'View payslip details'" />
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

    <Dialog v-model:visible="showPayslipDialog" header="Payslip Details" :style="{ width: '720px' }" modal>
      <div v-if="selectedPayslip" class="space-y-4 text-sm">
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
          <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
            <p><span class="font-semibold">Employee:</span> {{ selectedPayslip.employeeName }}</p>
            <p><span class="font-semibold">Employee #:</span> {{ selectedPayslip.employeeId || '-' }}</p>
            <p><span class="font-semibold">Branch:</span> {{ selectedPayslip.branch || '-' }}</p>
            <p><span class="font-semibold">Department:</span> {{ selectedPayslip.department || '-' }}</p>
            <p><span class="font-semibold">Period:</span> {{ batchInfo ? formatDate(batchInfo.start_date) : '-' }} - {{ batchInfo ? formatDate(batchInfo.end_date) : '-' }}</p>
            <p><span class="font-semibold">Pay Date:</span> {{ selectedPayslip.paymentDate ? formatDate(selectedPayslip.paymentDate) : (batchInfo ? formatDate(batchInfo.pay_date) : '-') }}</p>
            <p><span class="font-semibold">Payment Method:</span> {{ selectedPayslip.paymentMethod || '-' }}</p>
            <p><span class="font-semibold">Reference #:</span> {{ selectedPayslip.referenceNumber || '-' }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
            <p class="mb-2 font-semibold text-emerald-800">Earnings</p>
            <div class="space-y-1">
              <p class="flex justify-between"><span>Base Salary</span><span>{{ formatCurrency(selectedPayslip.baseSalary) }}</span></p>
              <p class="flex justify-between"><span>Overtime</span><span>{{ formatCurrency(selectedPayslip.overtimePay) }}</span></p>
              <p class="flex justify-between"><span>Allowance</span><span>{{ formatCurrency(selectedPayslip.allowanceAmount) }}</span></p>
              <p class="flex justify-between border-t border-emerald-300 pt-1 font-semibold"><span>Gross Pay</span><span>{{ formatCurrency(selectedPayslip.grossPay) }}</span></p>
            </div>
          </div>

          <div class="rounded-lg border border-rose-200 bg-rose-50 p-3">
            <p class="mb-2 font-semibold text-rose-800">Deductions</p>
            <div class="space-y-1" v-if="selectedPayslip.deductionItems.length">
              <p v-for="deduction in selectedPayslip.deductionItems" :key="`${selectedPayslip.id}-${deduction.name}`" class="flex justify-between">
                <span>{{ deduction.name }}</span>
                <span>-{{ formatCurrency(deduction.amount) }}</span>
              </p>
            </div>
            <p v-else class="text-gray-500">No itemized deductions</p>
            <p class="mt-1 flex justify-between"><span>Late Deductions</span><span>-{{ formatCurrency(selectedPayslip.lateDeductions) }}</span></p>
            <p class="flex justify-between"><span>Leave Deductions</span><span>-{{ formatCurrency(selectedPayslip.leaveDeductions) }}</span></p>
            <p class="flex justify-between border-t border-rose-300 pt-1 font-semibold"><span>Total Deductions</span><span>-{{ formatCurrency(selectedPayslip.totalDeductions) }}</span></p>
          </div>
        </div>

        <div class="rounded-lg border border-blue-200 bg-blue-50 p-3">
          <p class="flex justify-between text-base font-semibold text-blue-900">
            <span>Net Pay</span>
            <span>{{ formatCurrency(selectedPayslip.netPay) }}</span>
          </p>
          <p class="mt-1 text-xs text-blue-700">Status: {{ selectedPayslip.status.toUpperCase() }}</p>
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
  // Deductions
  deductionItems: DeductionItem[]
  lateDeductions: number
  leaveDeductions: number
  otherDeductions: number
  // Totals
  grossPay: number
  totalDeductions: number
  netPay: number
  status: 'draft' | 'calculated' | 'processing' | 'approved' | 'released' | 'paid' | 'cancelled'
  remarks?: string
  payroll_id?: number
  paymentDate?: string | null
  paymentMethod?: string | null
  referenceNumber?: string | null
}

interface BatchInfo {
  id: number
  name: string
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
const statusOptions = ref(['draft', 'calculated', 'processing', 'approved', 'released', 'paid', 'cancelled'])

// ==================== COMPUTED ====================
const hasDraftPayrolls = computed(() =>
  payrollItems.value.some(i => i.status === 'draft' || i.status === 'calculated')
)

const selectedReleasedItems = computed(() =>
  selectedItems.value.filter(i => i.status === 'released' && !!i.payroll_id)
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
      salaryPerHour: item.base_salary ? item.base_salary / 160 : 0,
      basicPay: item.base_salary || 0,
      overtimePay: item.overtime_amount || 0,
      allowanceAmount: item.allowances_total || 0,
      deductionItems,
      lateDeductions: Number(item.late_deduction || 0),
      leaveDeductions: 0, // You'll need to calculate this from leaves
      otherDeductions: 0,
      grossPay: grossPay,
      totalDeductions: item.deductions_total || 0,
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
    'released': 'info',
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

const releasePayroll = async (item: PayrollItem) => {
  ;(item as any).releasing = true
  try {
    await hrService.api.post(`/api/payroll/${item.payroll_id}/release`, {})
    item.status = 'released'
    calculateStatistics(payrollItems.value)
    await fetchBatchInfo()
    toast.add({
      severity: 'success',
      summary: 'Released',
      detail: `${item.employeeName}'s payroll has been released.`,
      life: 3000,
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Release Failed',
      detail: error?.response?.data?.message || 'Failed to release payroll.',
      life: 3000,
    })
  } finally {
    ;(item as any).releasing = false
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
  const releasedItems = selectedReleasedItems.value

  if (!releasedItems.length) {
    toast.add({
      severity: 'warn',
      summary: 'No Eligible Items',
      detail: 'Select payroll rows with Released status to mark them as paid.',
      life: 3000
    })
    return
  }

  bulkMarkingPaid.value = true
  try {
    const ids = releasedItems.map(i => Number(i.payroll_id)).filter(id => id > 0)
    const response = await financeService.bulkMarkPayrollPaid(ids, {
      payment_date: new Date().toISOString().slice(0, 10),
      payment_method: 'bank_transfer'
    })

    releasedItems.forEach(item => {
      item.status = 'paid'
    })
    calculateStatistics(payrollItems.value)
    selectedItems.value = []
    await fetchBatchInfo()

    toast.add({
      severity: 'success',
      summary: 'Bulk Paid Complete',
      detail: response?.message || `${releasedItems.length} payroll(s) marked as paid.`,
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
  const itemizedDeductionRows = item.deductionItems.length
    ? item.deductionItems.map(d => `<tr><td>${d.name}</td><td>- ${formatCurrency(d.amount)}</td></tr>`).join('')
    : '<tr><td colspan="2" style="text-align:left;color:#666;">No itemized deductions</td></tr>'

  const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Payslip - ${item.employeeName}</title>
      <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; color: #333; }
        h2 { text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 16px; font-size: 11px; }
        .section { margin-bottom: 12px; }
        .section-title { font-weight: bold; background: #f0f0f0; padding: 4px 8px; border-left: 3px solid #333; margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 4px 8px; }
        td:last-child { text-align: right; }
        .divider { border-top: 1px solid #ccc; margin: 8px 0; }
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
      <div class="subtitle">${period?.name ?? ''} &nbsp;|&nbsp; ${period ? formatDate(period.start_date) + ' – ' + formatDate(period.end_date) : ''}</div>

      <div class="header-grid">
        <div><span class="label">Employee:</span> <strong>${item.employeeName}</strong></div>
        <div><span class="label">Employee #:</span> ${item.employeeId}</div>
        <div><span class="label">Department:</span> ${item.department}</div>
        <div><span class="label">Branch:</span> ${item.branch}</div>
        <div><span class="label">Pay Date:</span> ${period ? formatDate(period.pay_date) : 'N/A'}</div>
        <div><span class="label">Status:</span> ${item.status.toUpperCase()}</div>
      </div>

      <div class="section">
        <div class="section-title">EARNINGS</div>
        <table>
          <tr><td>Basic Salary</td><td>${formatCurrency(item.baseSalary)}</td></tr>
          <tr><td>Overtime Pay</td><td>${formatCurrency(item.overtimePay)}</td></tr>
          <tr><td>Allowances</td><td>${formatCurrency(item.allowanceAmount)}</td></tr>
          <tr class="total-row"><td>Gross Pay</td><td>${formatCurrency(item.grossPay)}</td></tr>
        </table>
      </div>

      <div class="section">
        <div class="section-title">DEDUCTIONS</div>
        <table>
          ${itemizedDeductionRows}
          <tr><td>Late Deductions</td><td>- ${formatCurrency(item.lateDeductions)}</td></tr>
          <tr><td>Leave Deductions</td><td>- ${formatCurrency(item.leaveDeductions)}</td></tr>
          <tr class="total-row"><td>Total Deductions</td><td>- ${formatCurrency(item.totalDeductions)}</td></tr>
        </table>
      </div>

      <div class="section">
        <table>
          <tr class="net-row"><td>NET PAY</td><td>${formatCurrency(item.netPay)}</td></tr>
        </table>
      </div>

      <div style="margin-top:40px; display:grid; grid-template-columns:1fr 1fr; gap:20px; font-size:11px;">
        <div style="border-top:1px solid #333; padding-top:4px; text-align:center;">Prepared by</div>
        <div style="border-top:1px solid #333; padding-top:4px; text-align:center;">Received by</div>
      </div>

      <div style="margin-top:30px; text-align:center; font-size:10px; color:#999;">
        Printed on: ${new Date().toLocaleString('en-PH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: true
  })}
      </div>
    </body>
    </html>
  `
  const win = window.open('', '_blank', 'width=700,height=900')
  if (win) {
    win.document.write(html)
    win.document.close()
    win.focus()
    setTimeout(() => win.print(), 500)
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


