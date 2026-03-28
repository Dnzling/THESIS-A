<template>
  <div class="mx-auto space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="rounded-3xl border border-gray-100 bg-linear-to-r from-slate-50 via-white to-blue-50 p-5 shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <Button icon="pi pi-arrow-left" rounded text @click="goBack" />
          <div>
            <h1 class="text-xl font-semibold tracking-tight text-gray-900">Payroll Period Detail</h1>
            <p class="mt-0.5 text-sm text-gray-500">Walkthrough and verification before approval.</p>
          </div>
        </div>
        <div class="flex gap-2">
          <Button icon="pi pi-refresh" label="Refresh" :loading="loading" @click="loadDetail" />
          <Button
            icon="pi pi-check"
            :label="bulkActionLabel"
            severity="success"
            v-if="hasBulkActionPermission"
            :disabled="!selectedPayrollIdsForAction.length || bulkApproving"
            :loading="bulkApproving"
            @click="bulkApprove"
          />
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4" v-if="period">
      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Period</p>
            <p class="mt-2 text-sm font-semibold text-gray-900">{{ period.name || `#${period.id}` }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ period.formatted?.start_date_formatted }} - {{ period.formatted?.end_date_formatted }}</p>
          </div>
        </template>
      </Card>

      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Employees</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ employees.length }}</p>
          </div>
        </template>
      </Card>

      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">For Approval</p>
            <p class="mt-2 text-2xl font-semibold text-orange-600">{{ approvableCount }}</p>
          </div>
        </template>
      </Card>

      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Total Net</p>
            <p class="mt-2 text-xl font-semibold text-green-700">₱ {{ formatMoney(totalNet) }}</p>
          </div>
        </template>
      </Card>
    </div>

    <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
      <template #header>
        <div class="px-6 pt-6">
          <h2 class="text-lg font-semibold text-gray-900">Payroll Walkthrough</h2>
        </div>
      </template>
      <template #content>
        <div class="p-6 pt-2">
          <DataTable
            :value="employees"
            :loading="loading"
            dataKey="payrollId"
            stripedRows
            responsiveLayout="scroll"
            class="p-datatable-sm text-xs"
            paginator
            :rows="10"
            :rowsPerPageOptions="[5, 10, 20, 50]"
            v-model:selection="selectedRows"
          >
            <Column selectionMode="multiple" headerStyle="width: 44px" />

            <Column header="Employee" style="min-width: 220px">
              <template #body="{ data }">
                <div>
                  <p class="text-sm font-semibold text-gray-900">{{ data.name }}</p>
                  <p class="text-xs text-gray-500">{{ data.employee_number }} | {{ data.department || '-' }}</p>
                </div>
              </template>
            </Column>

            <Column header="Base" style="width: 150px">
              <template #body="{ data }">₱ {{ formatMoney(data.payroll.base_salary) }}</template>
            </Column>

            <Column header="Overtime" style="width: 120px">
              <template #body="{ data }">₱ {{ formatMoney(data.payroll.overtime_amount) }}</template>
            </Column>

            <Column header="Allowances" style="width: 120px">
              <template #body="{ data }">₱ {{ formatMoney(data.payroll.allowances_total) }}</template>
            </Column>

            <Column header="Bonuses" style="width: 120px">
              <template #body="{ data }">₱ {{ formatMoney(data.payroll.bonuses_total) }}</template>
            </Column>

            <Column header="Deductions" style="width: 120px">
              <template #body="{ data }">₱ {{ formatMoney(data.payroll.deductions_total) }}</template>
            </Column>

            <Column header="Itemized Deductions" style="min-width: 220px">
              <template #body="{ data }">
                <div v-if="!deductionItems(data).length" class="text-xs text-gray-400">No itemized deductions</div>
                <div v-for="deduction in deductionItems(data)" :key="`${data.payrollId}-${deduction.name}`" class="text-xs text-gray-700">
                  {{ deduction.name }}: ₱ {{ formatMoney(deduction.amount) }}
                </div>
              </template>
            </Column>

            <Column header="Tax" style="width: 110px">
              <template #body="{ data }">₱ {{ formatMoney(data.payroll.tax_amount) }}</template>
            </Column>

            <Column header="Net Salary" style="width: 140px">
              <template #body="{ data }">
                <span class="text-sm font-semibold text-green-700">₱ {{ formatMoney(data.payroll.net_salary) }}</span>
              </template>
            </Column>

            <Column header="Total Deductions" style="width: 150px">
              <template #body="{ data }">
                <span class="text-sm font-semibold text-rose-600">₱ {{ formatMoney(totalDeductions(data)) }}</span>
              </template>
            </Column>

            <Column header="Status" style="width: 120px">
              <template #body="{ data }">
                <Tag :value="formatStatus(data.payroll.status)" :severity="statusSeverity(data.payroll.status)" />
              </template>
            </Column>

            <Column header="Actions" style="width: 130px" headerStyle="text-align:center">
              <template #body="{ data }">
                <div class="flex justify-center gap-1">
                  <Button
                    v-if="canSubmitToFinance(data.payroll.status) || canFinanceApprove(data.payroll.status)"
                    :icon="canFinanceApprove(data.payroll.status) ? 'pi pi-check' : 'pi pi-send'"
                    text
                    rounded
                    severity="success"
                    :aria-label="canFinanceApprove(data.payroll.status) ? 'Finance Approve Payroll' : 'Submit Payroll to Finance'"
                    :loading="approvingPayrollId === data.payrollId"
                    @click="approveOne(data.payrollId, data.payroll.status)"
                  />
                  <Button
                    v-if="canReleasePayroll(data.payroll.status)"
                    icon="pi pi-wallet"
                    text
                    rounded
                    severity="info"
                    aria-label="Release Payroll"
                    :loading="releasingPayrollId === data.payrollId"
                    @click="releaseOne(data.payrollId)"
                  />
                  <Button
                    icon="pi pi-info-circle"
                    text
                    rounded
                    severity="contrast"
                    @click="openBreakdown(data)"
                  />
                </div>
              </template>
            </Column>

            <template #empty>
              <div class="py-12 text-center text-gray-500">No payroll entries found for this period.</div>
            </template>
          </DataTable>
        </div>
      </template>
    </Card>

    <Dialog
      v-model:visible="showBreakdownDialog"
      modal
      :dismissableMask="true"
      header="Payroll Breakdown Transparency"
      :style="{ width: 'min(95vw, 860px)' }"
    >
      <div v-if="breakdownEmployee" class="space-y-4">
        <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
          <p class="text-sm font-semibold text-gray-900">{{ breakdownEmployee.name }}</p>
          <p class="text-xs text-gray-500">{{ breakdownEmployee.employee_number }} | {{ breakdownEmployee.department || '-' }}</p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-4">
            <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-emerald-700">Benefits / Earnings</p>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-600">Base Salary</span>
                <span class="font-medium text-gray-900">₱ {{ formatMoney(breakdownEmployee.payroll.base_salary) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Overtime</span>
                <span class="font-medium text-gray-900">₱ {{ formatMoney(breakdownEmployee.payroll.overtime_amount) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Allowances</span>
                <span class="font-medium text-gray-900">₱ {{ formatMoney(breakdownEmployee.payroll.allowances_total) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Bonuses</span>
                <span class="font-medium text-gray-900">₱ {{ formatMoney(breakdownEmployee.payroll.bonuses_total) }}</span>
              </div>
              <div class="mt-2 border-t border-emerald-200 pt-2 flex justify-between">
                <span class="font-semibold text-emerald-800">Gross Pay</span>
                <span class="font-semibold text-emerald-800">₱ {{ formatMoney(grossPay(breakdownEmployee)) }}</span>
              </div>
            </div>
          </div>

          <div class="rounded-xl border border-rose-100 bg-rose-50/50 p-4">
            <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-rose-700">Deductions Transparency</p>
            <div class="space-y-2 text-sm">
              <div v-if="!deductionItems(breakdownEmployee).length" class="text-gray-500 text-xs">No itemized deductions</div>
              <div
                v-for="deduction in deductionItems(breakdownEmployee)"
                :key="`breakdown-${breakdownEmployee?.payrollId}-${deduction.name}`"
                class="flex justify-between"
              >
                <span class="text-gray-600">{{ deduction.name }}</span>
                <span class="font-medium text-gray-900">₱ {{ formatMoney(deduction.amount) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Deductions (Govt/Loans/Others)</span>
                <span class="font-medium text-gray-900">₱ {{ formatMoney(breakdownEmployee.payroll.deductions_total) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Late Deduction</span>
                <span class="font-medium text-gray-900">₱ {{ formatMoney(breakdownEmployee.payroll.late_deduction || 0) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Tax Withholding</span>
                <span class="font-medium text-gray-900">₱ {{ formatMoney(breakdownEmployee.payroll.tax_amount) }}</span>
              </div>
              <div class="mt-2 border-t border-rose-200 pt-2 flex justify-between">
                <span class="font-semibold text-rose-800">Total Deductions</span>
                <span class="font-semibold text-rose-800">₱ {{ formatMoney(totalDeductions(breakdownEmployee)) }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="rounded-xl border border-blue-100 bg-blue-50/50 p-4">
          <div class="flex items-center justify-between">
            <p class="text-sm font-semibold text-blue-800">Take-Home Pay</p>
            <p class="text-lg font-bold text-blue-900">₱ {{ formatMoney(breakdownEmployee.payroll.net_salary) }}</p>
          </div>
          <p class="mt-1 text-xs text-blue-700">
            Net = Gross Pay - Total Deductions
          </p>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import { useToast } from 'primevue/usetoast'
import financeService from '../../../services/finance.service'
import { useAuthStore } from '../../../stores/auth'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const loading = ref(false)
const bulkApproving = ref(false)
const approvingPayrollId = ref<number | null>(null)
const releasingPayrollId = ref<number | null>(null)

const period = ref<any>(null)
const employees = ref<any[]>([])
const selectedRows = ref<any[]>([])
const statistics = ref<any>(null)
const showBreakdownDialog = ref(false)
const breakdownEmployee = ref<any | null>(null)

const normalizedUserRole = computed(() => String(authStore.currentUser?.role || '').toLowerCase())
const isHrUser = computed(() => ['hr_manager', 'store_admin', 'super_admin'].includes(normalizedUserRole.value))
const isFinanceUser = computed(() => ['accountant', 'store_admin', 'super_admin'].includes(normalizedUserRole.value))

const formatMoney = (value: number | string) => {
  const amount = typeof value === 'string' ? parseFloat(value) : value || 0
  return new Intl.NumberFormat('en-PH').format(amount)
}

const formatStatus = (status: string) => {
  const normalized = String(status || '').toLowerCase()
  const map: Record<string, string> = {
    draft: 'Draft',
    calculated: 'Calculated',
    processing: 'For Finance Review',
    approved: 'Finance Approved',
    released: 'Released by HR',
    paid: 'Paid',
    cancelled: 'Cancelled',
  }

  return map[normalized] || (status ? status.split('_').map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(' ') : '-')
}

const statusSeverity = (status: string) => {
  const normalized = String(status || '').toLowerCase()
  if (normalized === 'released') return 'warn'
  if (normalized === 'approved') return 'success'
  if (normalized === 'paid') return 'info'
  if (normalized === 'submitted' || normalized === 'processing') return 'warn'
  if (normalized === 'draft' || normalized === 'calculated') return 'secondary'
  return 'secondary'
}

const isApprovable = (status: string) => {
  const normalized = String(status || '').toLowerCase()
  return ['draft', 'calculated', 'submitted', 'processing'].includes(normalized)
}

const canSubmitToFinance = (status: string) => {
  const normalized = String(status || '').toLowerCase()
  return isHrUser.value && ['draft', 'calculated'].includes(normalized)
}

const canFinanceApprove = (status: string) => {
  const normalized = String(status || '').toLowerCase()
  return isFinanceUser.value && normalized === 'processing'
}

const canReleasePayroll = (status: string) => {
  const normalized = String(status || '').toLowerCase()
  return isHrUser.value && normalized === 'approved'
}

const hasBulkActionPermission = computed(() => isHrUser.value || isFinanceUser.value)

const approvableCount = computed(() =>
  employees.value.filter((item) => isApprovable(item.payroll?.status)).length
)

const selectedPayrollIdsForAction = computed(() => {
  const rows = selectedRows.value || []
  return rows
    .filter((row) => canSubmitToFinance(row.payroll?.status) || canFinanceApprove(row.payroll?.status))
    .map((row) => Number(row.payrollId || 0))
    .filter((id) => id > 0)
})

const selectedStatusesForAction = computed(() =>
  (selectedRows.value || []).map((row) => String(row?.payroll?.status || '').toLowerCase())
)

const isBulkFinanceApproval = computed(() => {
  const statuses = selectedStatusesForAction.value
  return statuses.length > 0 && statuses.every((status) => status === 'processing')
})

const isBulkSubmitToFinance = computed(() => {
  const statuses = selectedStatusesForAction.value
  return statuses.length > 0 && statuses.every((status) => ['draft', 'calculated'].includes(status))
})

const bulkActionLabel = computed(() => {
  if (isBulkFinanceApproval.value) return 'Bulk Finance Approve'
  if (isBulkSubmitToFinance.value) return 'Bulk Submit to Finance'
  if (isFinanceUser.value && !isHrUser.value) return 'Bulk Finance Approve'
  if (isHrUser.value) return 'Bulk Submit to Finance'
  return 'Bulk Action'
})

const totalNet = computed(() =>
  Number(statistics.value?.total_net_pay || 0) ||
  employees.value.reduce((sum, item) => sum + Number(item.payroll?.net_salary || 0), 0)
)

const grossPay = (item: any) =>
  Number(item?.payroll?.base_salary || 0) +
  Number(item?.payroll?.overtime_amount || 0) +
  Number(item?.payroll?.allowances_total || 0) +
  Number(item?.payroll?.bonuses_total || 0)

const totalDeductions = (item: any) =>
  Number(item?.payroll?.deductions_total || 0) + Number(item?.payroll?.tax_amount || 0)

const deductionItems = (item: any) => {
  const items = Array.isArray(item?.payroll?.deduction_items) ? item.payroll.deduction_items : []
  return items.filter((deduction: any) => String(deduction?.name || '').toLowerCase() !== 'late deduction')
}

const openBreakdown = (item: any) => {
  breakdownEmployee.value = item
  showBreakdownDialog.value = true
}

const normalizeEmployees = (rawEmployees: any[]) => {
  return (rawEmployees || []).map((row: any) => ({
    ...row,
    payrollId: Number(row?.payroll?.id || 0),
    payroll: {
      base_salary: Number(row?.payroll?.base_salary || 0),
      overtime_amount: Number(row?.payroll?.overtime_amount || 0),
      deductions_total: Number(row?.payroll?.deductions_total || 0),
      late_deduction: Number(row?.payroll?.late_deduction || 0),
      bonuses_total: Number(row?.payroll?.bonuses_total || 0),
      allowances_total: Number(row?.payroll?.allowances_total || 0),
      tax_amount: Number(row?.payroll?.tax_amount || 0),
      net_salary: Number(row?.payroll?.net_salary || 0),
      status: String(row?.payroll?.status || ''),
      deduction_items: Array.isArray(row?.payroll?.deduction_items)
        ? row.payroll.deduction_items.map((deduction: any) => ({
            id: deduction?.id,
            name: String(deduction?.name || ''),
            amount: Number(deduction?.amount || 0),
            calculation_type: deduction?.calculation_type,
            rate: deduction?.rate,
          }))
        : [],
    },
  }))
}

const loadDetail = async () => {
  loading.value = true
  try {
    const payPeriodId = Number(route.params.payPeriodId)
    if (!payPeriodId) {
      period.value = null
      employees.value = []
      statistics.value = null
      return
    }

    const res = await financeService.getPayrollPeriodDetail(payPeriodId, {
      include_department_breakdown: 1,
      include_status_breakdown: 1,
    })

    const payload = (res?.data && (res?.success !== undefined || res?.message !== undefined)) ? res.data : (res?.data || res)

    period.value = payload?.period || null
    statistics.value = payload?.statistics || null
    employees.value = normalizeEmployees(payload?.employees || [])
    selectedRows.value = []
  } catch (error: any) {
    period.value = null
    employees.value = []
    statistics.value = null
    selectedRows.value = []
    toast.add({
      severity: 'error',
      summary: 'Load Failed',
      detail: error?.response?.data?.message || 'Failed to fetch payroll period details.',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const approveOne = async (payrollId: number, status: string) => {
  approvingPayrollId.value = payrollId
  try {
    if (String(status || '').toLowerCase() === 'processing') {
      await financeService.approvePayroll(payrollId)
      toast.add({
        severity: 'success',
        summary: 'Finance Approval Complete',
        detail: 'Payroll has been finance-approved.',
        life: 2500,
      })
    } else {
      await financeService.submitPayroll(payrollId)
      toast.add({
        severity: 'success',
        summary: 'Submitted to Finance',
        detail: 'Payroll has been submitted for finance review.',
        life: 2500,
      })
    }
    await loadDetail()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: String(status || '').toLowerCase() === 'processing' ? 'Finance Approval Failed' : 'Submit Failed',
      detail: error?.response?.data?.message || 'Unable to process payroll action.',
      life: 3000,
    })
  } finally {
    approvingPayrollId.value = null
  }
}

const releaseOne = async (payrollId: number) => {
  releasingPayrollId.value = payrollId
  try {
    await financeService.releasePayroll(payrollId)
    toast.add({
      severity: 'success',
      summary: 'Payroll Released',
      detail: 'Payroll has been released by HR.',
      life: 2500,
    })
    await loadDetail()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Release Failed',
      detail: error?.response?.data?.message || 'Unable to release payroll.',
      life: 3000,
    })
  } finally {
    releasingPayrollId.value = null
  }
}

const bulkApprove = async () => {
  if (!selectedPayrollIdsForAction.value.length) {
    toast.add({
      severity: 'warn',
      summary: 'No Eligible Rows',
      detail: 'Select rows with the same workflow stage (all Processing for finance approval, or all Draft/Calculated for submit).',
      life: 2500,
    })
    return
  }

  if (!isBulkFinanceApproval.value && !isBulkSubmitToFinance.value) {
    toast.add({
      severity: 'warn',
      summary: 'Mixed Status Selection',
      detail: 'Please select only Processing rows or only Draft/Calculated rows for bulk action.',
      life: 3000,
    })
    return
  }

  bulkApproving.value = true
  try {
    if (isBulkFinanceApproval.value) {
      await financeService.bulkApprovePayroll(selectedPayrollIdsForAction.value)
      toast.add({
        severity: 'success',
        summary: 'Bulk Finance Approval Complete',
        detail: 'Selected payroll entries were finance-approved.',
        life: 2500,
      })
    } else {
      await financeService.bulkSubmitPayroll(selectedPayrollIdsForAction.value)
      toast.add({
        severity: 'success',
        summary: 'Bulk Submit Complete',
        detail: 'Selected payroll entries were submitted to finance.',
        life: 2500,
      })
    }
    await loadDetail()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: isBulkFinanceApproval.value ? 'Bulk Finance Approval Failed' : 'Bulk Submit Failed',
      detail: error?.response?.data?.message || 'Unable to complete bulk payroll action.',
      life: 3000,
    })
  } finally {
    bulkApproving.value = false
  }
}

const goBack = () => router.push({ name: 'finance.payroll' })

loadDetail()
</script>
