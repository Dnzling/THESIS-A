<template>
  <div class="payroll-list">
    <!-- Header with Batch Info -->
    <div v-if="batchInfo" class="mb-4 p-3 bg-gray-50 rounded-lg border">
      <div class="flex justify-between items-center">
        <div>
          <h3 class="text-lg font-semibold">{{ batchInfo.name }}</h3>
          <p class="text-sm text-gray-600">
            {{ formatDate(batchInfo.start_date) }} {{ formatDate(batchInfo.end_date) }}
            | Pay Date: {{ formatDate(batchInfo.pay_date) }}
          </p>
        </div>
        <div class="flex gap-2 items-center">
          <Tag :severity="getStatusSeverity(batchInfo.status)" :value="batchInfo.status" class="capitalize" />
          <Button label="Back" icon="pi pi-arrow-left" text size="small" @click="goBack" />
        </div>
      </div>
    </div>
  
    <!-- Search and Filters -->
    <div class="flex gap-2 mb-4">
      <IconField iconPosition="left" class="w-64">
        <InputIcon>
          <i class="pi pi-search" />
        </InputIcon>
        <InputText v-model="filters.search" placeholder="Search employee..." class="w-full" size="small" />
      </IconField>
      <Select v-model="filters.branch" :options="branches" placeholder="All Branches" showClear class="w-40"
        size="small" />
      <Select v-model="filters.department" :options="departments" placeholder="All Departments" showClear class="w-40"
        size="small" />
      <Select v-model="filters.status" :options="statusOptions" placeholder="All Status" showClear class="w-36"
        size="small" />
      <Button label="Save All Changes" icon="pi pi-save" severity="success" size="small"
        :disabled="loading || saveAllLoading" :loading="saveAllLoading" @click="saveAllChanges" />
      <Button label="Bulk Submit" icon="pi pi-send" severity="info" size="small"
        :disabled="!hasSelectedItems || bulkSubmitting" :loading="bulkSubmitting" @click="submitBatchForApproval" />
    </div>
  
    <!-- Payroll Table -->
    <DataTable :value="filteredPayrollItems" :paginator="true" :rows="10" :rowsPerPageOptions="[10, 20, 50]"
      tableStyle="min-width: 175rem" :loading="loading"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
      currentPageReportTemplate="Showing {first} to {last} of {totalRecords} employees" sortMode="multiple" rowHover
      scrollable scrollHeight="calc(100vh - 300px)" v-model:selection="selectedItems" removableSort
      selectionMode="multiple">
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
  
      <Column class="text-xs" field="branch" header="Branch" sortable />
      <Column class="text-xs" field="department" header="Department" sortable />
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
      <Column class="text-xs" field="basicPay" header="Basic Pay" sortable>
        <template #body="{ data }">
          {{ formatCurrency(data.basicPay) }}
        </template>
      </Column>
  
      <Column class="text-xs" field="overtimeHours" header="OT Hrs" sortable />
      <Column class="text-xs" field="overtimePay" header="OT Pay" sortable>
        <template #body="{ data }">
          {{ formatCurrency(data.overtimePay) }}
        </template>
      </Column>
  
      <Column class="text-xs" field="allowanceAmount" header="Allowance" sortable>
        <template #body="{ data }">
          <InputNumber size="small" fluid v-model="data.allowanceAmount" mode="currency" currency="PHP" locale="en-PH"
            :min="0" @blur="recalculateTotals(data)" />
        </template>
      </Column>
      <Column class="text-xs" field="incentiveAmount" header="Dated Incentives" sortable>
        <template #body="{ data }">{{ formatCurrency(data.incentiveAmount) }}</template>
      </Column>
  
      <!-- Deductions -->
      <Column class="text-xs font-semibold" header="Gov't Deductions" style="min-width: 180px">
        <template #body="{ data }">
          <div class="text-xs space-y-1">
            <div class="flex items-center gap-1">
              <span class="w-16">Tax:</span>
              <span class="font-medium">{{ formatCurrency(data.governmentDeductions.tax) }}</span>
            </div>
            <div class="flex items-center gap-1">
              <span class="w-16">SSS:</span>
              <span class="font-medium">{{ formatCurrency(data.governmentDeductions.sss) }}</span>
            </div>
            <div class="flex items-center gap-1">
              <span class="w-16">PhilHealth:</span>
              <span class="font-medium">{{ formatCurrency(data.governmentDeductions.philhealth) }}</span>
            </div>
            <div class="flex items-center gap-1">
              <span class="w-16">Pag-IBIG:</span>
              <span class="font-medium">{{ formatCurrency(data.governmentDeductions.pagibig) }}</span>
            </div>
          </div>
        </template>
      </Column>
  
      <Column class="text-xs" field="lateDeductions" header="Late" sortable>
          <template #body="{ data }">
          <span class="font-medium">{{ formatCurrency(data.lateDeductions) }}</span>
        </template>
      </Column>
      <Column class="text-xs" field="absenceDeduction" header="Absence Ded." sortable>
        <template #body="{ data }">{{ formatCurrency(data.absenceDeduction) }}</template>
      </Column>
  
      <Column class="text-xs" field="bonusPay" header="Bonus / Incentive Override" sortable>
        <template #body="{ data }">
          <InputNumber size="small" fluid v-model="data.bonusPay" mode="currency" currency="PHP" locale="en-PH" :min="0"
            @blur="recalculateTotals(data)" />
        </template>
      </Column>
  
      <!-- Totals -->
      <Column class="text-xs" field="grossPay" header="Gross Pay" sortable>
        <template #body="{ data }">
          <span class="font-bold text-green-600">{{ formatCurrency(data.grossPay) }}</span>
        </template>
      </Column>
  
      <Column class="text-xs" field="totalDeductions" header="Total Deductions" sortable>
        <template #body="{ data }">
          <span class="font-bold text-red-600">- {{ formatCurrency(data.totalDeductions) }}</span>
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
          <Tag :severity="getStatusSeverity(data.status)" :value="data.status" />
        </template>
      </Column>
  
      <!-- Actions -->
      <Column class="text-xs" header="Actions" style="min-width: 130px">
        <template #body="{ data }">
          <div class="flex gap-1">
            <Button icon="pi pi-save" severity="success" text size="small" @click="savePayrollItem(data)"
              v-tooltip.top="'Save changes'" :loading="data.saving" :disabled="data.status !== 'draft'" />
            <Button icon="pi pi-send" severity="info" text size="small" @click="submitForApproval(data)"
              v-tooltip.top="'Submit for approval'" :loading="data.submitting" :disabled="data.status !== 'draft'" />
            <Button icon="pi pi-print" severity="secondary" text size="small" @click="printPayslip(data)"
              v-tooltip.top="'Print payslip'" />
          </div>
        </template>
      </Column>
  
      <template #empty>
        <div class="text-center py-8 text-gray-500">
          <i class="pi pi-file text-4xl mb-2 block"></i>
          <p>No payroll data found for this period</p>
        </div>
      </template>
    </DataTable>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRoute, useRouter } from 'vue-router'
import hrService from '@/services/hr.services'
import { useAuthStore } from '../../../stores/auth'
import { printPayrollPayslip } from '@/utils/payrollPayslipPrint'

// ==================== INTERFACES ====================
interface GovernmentDeductions {
  tax: number
  sss: number
  philhealth: number
  pagibig: number
}

interface PayrollItem {
  id: string
  payroll_id: number
  employeeId: string
  employeeName: string
  branch: string
  department: string
  baseSalary: number
  salaryPerHour: number
  basicPay: number
  overtimePay: number
  allowanceAmount: number
  bonusPay: number
  incentiveAmount: number
  absentDays: number
  leaveDays: number
  breakMinutes: number
  lateMinutes: number
  overtimeHours: number
  governmentDeductions: GovernmentDeductions
  lateDeductions: number
  absenceDeduction: number
  halfDayDeduction: number
  leaveDeductions: number
  otherDeductions: number
  grossPay: number
  totalDeductions: number
  netPay: number
  status: 'draft' | 'approved' | 'paid' | 'cancelled'
  remarks?: string
  saving?: boolean
  submitting?: boolean
  dirty?: boolean  // tracks unsaved changes
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

// ==================== PROPS ====================
const props = defineProps<{
  batchId?: string
}>()

// ==================== STATE ====================
const toast = useToast()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const loading = ref(false)
const bulkSubmitting = ref(false)
const saveAllLoading = ref(false)
const selectedItems = ref<PayrollItem[]>([])
const payrollItems = ref<PayrollItem[]>([])
const batchInfo = ref<BatchInfo | null>(null)

// Resolve pay period ID from prop or route param
const periodId = computed(() => props.batchId || (route.params.id as string))

// Filters
const filters = ref<Filters>({
  search: '',
  branch: null,
  department: null,
  status: null
})

const branches = ref<string[]>([])
const departments = ref<string[]>([])
const statusOptions = ref(['draft', 'approved', 'paid', 'cancelled'])

// ==================== COMPUTED ====================
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

const hasSelectedItems = computed(() => selectedItems.value.length > 0)

// ==================== METHODS ====================
const fetchPayrollData = async () => {
  if (!periodId.value) return
  loading.value = true
  try {

    const [payrollRes] = await Promise.all([
      hrService.api.get('/api/payroll', { params: { pay_period_id: periodId.value } }),
      fetchBatchInfo()
    ])

    if (payrollRes.data.success) {
      payrollItems.value = transformPayrollData(payrollRes.data.data)
      extractFilterOptions(payrollItems.value)
    }
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to fetch payroll data', life: 3000 })
  } finally {
    loading.value = false
  }
}

const fetchBatchInfo = async () => {
  try {
    const response = await hrService.api.get(`/api/payroll/pay-periods/${periodId.value}`)
    if (response.data.success) {
      batchInfo.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to fetch batch info:', error)
  }
}

const transformPayrollData = (apiData: any[]): PayrollItem[] => {
  return apiData.map((item: any) => {
    // Build itemized deductions (parity with PayrollView)
    const rawDeductionItems = Array.isArray(item.deduction_items) ? item.deduction_items : []
    const deductionItems = rawDeductionItems.map((d: any) => ({
      name: String(d?.name || ''),
      amount: Number(d?.amount || 0)
    }))

    // Aggregate gov't deductions from itemized entries if explicit fields aren't present
    const govFromItems = { tax: 0, sss: 0, philhealth: 0, pagibig: 0 }
    deductionItems.forEach(d => {
      const name = d.name.toLowerCase()
      if (name.includes('tax')) govFromItems.tax += d.amount
      else if (name.includes('sss')) govFromItems.sss += d.amount
      else if (name.includes('philhealth')) govFromItems.philhealth += d.amount
      else if (name.includes('pag-ibig') || name.includes('pag ibig') || name.includes('pagibig')) govFromItems.pagibig += d.amount
    })

    const govDeductions = {
      tax: parseFloat(item.tax_amount) || parseFloat(item.income_tax) || govFromItems.tax || 0,
      sss: parseFloat(item.sss_amount) || parseFloat(item.sss) || govFromItems.sss || 0,
      philhealth: parseFloat(item.philhealth_amount) || parseFloat(item.philhealth) || govFromItems.philhealth || 0,
      pagibig: parseFloat(item.pagibig_amount) || parseFloat(item.pagibig) || govFromItems.pagibig || 0
    }

    const grossPay = (parseFloat(item.base_salary) || 0)
      + (parseFloat(item.overtime_amount) || 0)
      + (parseFloat(item.bonuses_total) || 0)
      + (parseFloat(item.allowances_total) || 0)

    const totalDeductionsValue = parseFloat(item.deductions_total) || deductionItems.reduce((s: number, d: any) => s + (d.amount || 0), 0) || 0

    return {
      id: item.id?.toString() || '',
      payroll_id: item.id,
      employeeId: item.employee?.employee_number || '',
      employeeName: item.employee ? `${item.employee.fname} ${item.employee.lname}` : '',
      branch: item.employee?.branch || 'N/A',
      department: item.employee?.department || 'N/A',
      baseSalary: parseFloat(item.base_salary) || 0,
      salaryPerHour: Number(item.hourly_rate || 0),
      basicPay: parseFloat(item.base_salary) || 0,
      overtimePay: parseFloat(item.overtime_amount) || 0,
      allowanceAmount: parseFloat(item.allowances_total) || 0,
      bonusPay: parseFloat(item.bonuses_total) || 0,
      incentiveAmount: Number(item.period_metrics?.incentive_total || 0),
      absentDays: Number(item.period_metrics?.absent_days || 0),
      leaveDays: Number(item.period_metrics?.leave_days || 0),
      breakMinutes: Number(item.period_metrics?.break_minutes || 0),
      lateMinutes: Number(item.period_metrics?.late_minutes || 0),
      overtimeHours: Number(item.overtime_hours || 0),
      governmentDeductions: govDeductions,
      deductionItems: deductionItems,
      lateDeductions: parseFloat(item.late_deduction) || 0,
      absenceDeduction: Number(item.period_metrics?.absence_deduction || 0),
      halfDayDeduction: Number(item.period_metrics?.half_day_deduction || 0),
      leaveDeductions: parseFloat(item.leave_deduction) || 0,
      otherDeductions: Math.max(0, totalDeductionsValue + govDeductions.tax
        - Object.values(govDeductions).reduce((sum, value) => sum + value, 0)
        - (parseFloat(item.late_deduction) || 0)),
      grossPay,
      totalDeductions: totalDeductionsValue + govDeductions.tax,
      netPay: parseFloat(item.net_salary) || 0,
      status: item.status || 'draft',
      saving: false,
      submitting: false
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

const formatDate = (date: string): string => {
  if (!date) return 'N/A'
  return new Intl.DateTimeFormat('en-PH', { year: 'numeric', month: 'short', day: '2-digit' }).format(new Date(date))
}

const formatCurrency = (value: number): string => {
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 2 }).format(value || 0)
}

const getStatusSeverity = (status: string): 'info' | 'success' | 'warn' | 'secondary' | 'danger' => {
  const map: Record<string, any> = {
    draft: 'secondary', approved: 'success', paid: 'success', cancelled: 'danger'
  }
  return map[status] || 'info'
}

const recalculateTotals = (item: PayrollItem) => {
  item.grossPay = item.basicPay + item.overtimePay + item.allowanceAmount + item.bonusPay
  const govTotal = Object.values(item.governmentDeductions).reduce((a, b) => a + b, 0)
  item.totalDeductions = govTotal + item.lateDeductions + item.leaveDeductions + item.otherDeductions
  item.netPay = item.grossPay - item.totalDeductions
  item.dirty = true  // mark as modified
}

const saveAllChanges = async () => {
  // Only save rows that were actually modified (dirty flag)
  const draftItems = payrollItems.value.filter(i => i.status === 'draft' && i.dirty)
  if (draftItems.length === 0) {
    toast.add({ severity: 'info', summary: 'Nothing to Save', detail: 'No unsaved changes detected', life: 2000 })
    return
  }

  saveAllLoading.value = true
  let savedCount = 0
  let errorCount = 0

  try {
    await Promise.all(
      draftItems.map(async (item) => {
        try {
          const response = await hrService.api.put(`/api/payroll/${item.payroll_id}`, {
            allowances_total: item.allowanceAmount,
            bonuses_total: item.bonusPay,
            late_deduction: item.lateDeductions,
            deductions_total: item.totalDeductions - item.governmentDeductions.tax,
          })
          if (response.data.success) {
            item.netPay = parseFloat(response.data.data.net_salary) || item.netPay
            item.dirty = false  // clear dirty flag after successful save
            savedCount++
          }
        } catch {
          errorCount++
        }
      })
    )

    if (savedCount > 0) {
      await fetchPayrollData()
      toast.add({
        severity: 'success',
        summary: 'All Changes Saved',
        detail: `${savedCount} payroll record${savedCount > 1 ? 's' : ''} saved successfully${errorCount > 0 ? ` (${errorCount} failed)` : ''}`,
        life: 3000
      })
    } else {
      toast.add({ severity: 'error', summary: 'Save Failed', detail: 'Failed to save payroll changes', life: 3000 })
    }
  } finally {
    saveAllLoading.value = false
  }
}

const savePayrollItem = async (item: PayrollItem) => {
  item.saving = true
  try {
    const response = await hrService.api.put(`/api/payroll/${item.payroll_id}`, {
      allowances_total: item.allowanceAmount,
      bonuses_total: item.bonusPay,
      late_deduction: item.lateDeductions,
      deductions_total: item.totalDeductions - item.governmentDeductions.tax,
    })

    if (response.data.success) {
      await fetchPayrollData()
      toast.add({ severity: 'success', summary: 'Saved', detail: `${item.employeeName}'s payroll saved`, life: 2000 })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to save payroll',
      life: 3000
    })
  } finally {
    item.saving = false
  }
}

const submitForApproval = async (item: PayrollItem) => {
  item.submitting = true
  try {
    const response = await hrService.api.post(`/api/payroll/${item.payroll_id}/submit`)
    await fetchPayrollData()
    toast.add({ severity: 'success', summary: 'Payroll Submitted', detail: response.data.message, life: 3000 })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to submit for approval',
      life: 3000
    })
  } finally {
    item.submitting = false
  }
}

const submitBatchForApproval = async () => {
  const eligibleItems = selectedItems.value.filter(i => i.status === 'draft')
  if (eligibleItems.length === 0) {
    toast.add({ severity: 'warn', summary: 'No Eligible Items', detail: 'Selected payrolls must be in draft status', life: 3000 })
    return
  }

  bulkSubmitting.value = true
  try {
    const ids = eligibleItems.map(i => i.payroll_id)
    const response = await hrService.api.post('/api/payroll/bulk-submit', { payroll_ids: ids })

    if (response.data.success) {
      selectedItems.value = []
      await fetchPayrollData()
      toast.add({ severity: 'success', summary: 'Bulk Submitted', detail: response.data.message, life: 3000 })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to bulk approve payrolls',
      life: 3000
    })
  } finally {
    bulkSubmitting.value = false
  }
}

const printPayslip = (item: PayrollItem) => {
  const period = batchInfo.value
  const listedDeductions = [
    { label: 'Income tax', amount: item.governmentDeductions.tax },
    { label: 'SSS', amount: item.governmentDeductions.sss },
    { label: 'PhilHealth', amount: item.governmentDeductions.philhealth },
    { label: 'Pag-IBIG', amount: item.governmentDeductions.pagibig },
    { label: 'Late deduction', amount: item.lateDeductions },
    { label: 'Absence deduction', amount: item.absenceDeduction },
    { label: 'Half-day deduction', amount: item.halfDayDeduction },
  ]
  const remainingDeductions = Math.max(
    0,
    item.totalDeductions - listedDeductions.reduce((total, line) => total + Number(line.amount || 0), 0)
  )
  const opened = printPayrollPayslip({
    storeName: period?.store_name || 'Store name unavailable',
    employeeName: item.employeeName,
    employeeId: item.employeeId,
    department: item.department,
    branch: item.branch,
    periodName: period?.name || 'Payroll period',
    periodStart: period ? formatDate(period.start_date) : '-',
    periodEnd: period ? formatDate(period.end_date) : '-',
    payDate: period ? formatDate(period.pay_date) : '-',
    payrollId: String(item.payroll_id || item.id || '-'),
    status: item.dirty ? 'Unsaved preview' : item.status,
    earnings: [
      { label: 'Base salary', amount: item.baseSalary },
      { label: 'Overtime pay', amount: item.overtimePay },
      { label: 'Allowances', amount: item.allowanceAmount },
      { label: 'Bonuses and incentives', amount: item.bonusPay },
    ],
    deductions: [
      ...listedDeductions,
      { label: 'Other deductions', amount: remainingDeductions },
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

const goBack = () => {
  router.push({ name: 'hr.payroll' })
}

// ==================== LIFECYCLE ====================
onMounted(() => {
  fetchPayrollData()
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
