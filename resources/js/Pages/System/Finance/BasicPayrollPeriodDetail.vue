<template>
  <div class="mx-auto space-y-5 px-4 py-5 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" label="Back to Payroll Periods" severity="secondary" text @click="router.push({ name: 'finance.payroll.basic' })" />
        <div><h1 class="text-xl font-semibold text-slate-900">{{ period?.name || 'Payroll Period' }}</h1><p class="text-xs text-slate-500">{{ formatDate(period?.start_date) }} – {{ formatDate(period?.end_date) }}</p></div>
      </div>
      <div class="flex flex-wrap gap-2">
        <Button label="Refresh" icon="pi pi-refresh" severity="secondary" outlined size="small" :loading="loading" @click="load" />
        <Button v-if="draftRows.length" label="Complete All Drafts" icon="pi pi-check" severity="warn" size="small" :loading="busy" @click="completeAll" />
        <Button v-if="completedRows.length" label="Pay All Completed" icon="pi pi-wallet" severity="success" size="small" :loading="busy" @click="showPay = true" />
      </div>
    </div>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
    <div class="grid gap-3 sm:grid-cols-3">
      <Card class="border border-slate-200 shadow-sm"><template #content><p class="text-xs text-slate-500">Employees</p><p class="mt-1 text-xl font-semibold">{{ payrolls.length }}</p></template></Card>
      <Card class="border border-slate-200 shadow-sm"><template #content><p class="text-xs text-slate-500">To Complete</p><p class="mt-1 text-xl font-semibold">{{ draftRows.length }}</p></template></Card>
      <Card class="border border-slate-200 shadow-sm"><template #content><p class="text-xs text-slate-500">Total Net Pay</p><p class="mt-1 text-xl font-semibold text-emerald-700">{{ money(payrolls.reduce((sum, row) => sum + Number(row.net_salary || 0), 0)) }}</p></template></Card>
    </div>

    <Card class="border border-slate-200 shadow-sm">
      <template #content>
        <DataTable v-model:expandedRows="expandedRows" :value="payrolls" :loading="loading" dataKey="id" rowHover size="small" paginator :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 1100px">
          <Column expander style="width: 3rem" />
          <Column header="Employee" style="min-width: 190px"><template #body="{ data }"><p class="font-medium text-slate-900">{{ data.employee_name || 'Employee' }}</p><p class="text-xs text-slate-500">{{ data.employee_number }}</p></template></Column>
          <Column header="Attendance" style="min-width: 130px"><template #body="{ data }"><p>{{ data.attendance?.completed_workdays || 0 }} workday(s)</p><p class="text-xs text-slate-500">{{ Number(data.attendance?.worked_hours || 0).toFixed(2) }} hrs</p></template></Column>
          <Column header="Base Pay" style="min-width: 130px"><template #body="{ data }"><InputNumber v-model="data.base_salary" mode="currency" currency="PHP" locale="en-PH" :min="0" size="small" fluid :disabled="!isDraft(data)" /></template></Column>
          <Column header="Overtime" style="min-width: 130px"><template #body="{ data }"><InputNumber v-model="data.overtime_amount" mode="currency" currency="PHP" locale="en-PH" :min="0" size="small" fluid :disabled="!isDraft(data)" /></template></Column>
          <Column header="Allowance" style="min-width: 130px"><template #body="{ data }"><InputNumber v-model="data.allowances_total" mode="currency" currency="PHP" locale="en-PH" :min="0" size="small" fluid :disabled="!isDraft(data)" /></template></Column>
          <Column header="Incentive" style="min-width: 130px"><template #body="{ data }"><InputNumber v-model="data.bonuses_total" mode="currency" currency="PHP" locale="en-PH" :min="0" size="small" fluid :disabled="!isDraft(data)" /></template></Column>
          <Column header="Deductions" style="min-width: 130px"><template #body="{ data }"><InputNumber v-model="data.deductions_total" mode="currency" currency="PHP" locale="en-PH" :min="0" size="small" fluid :disabled="!isDraft(data)" /></template></Column>
          <Column header="Net Pay" style="min-width: 130px"><template #body="{ data }"><span class="font-semibold">{{ money(estimate(data)) }}</span></template></Column>
          <Column header="Status" style="min-width: 110px"><template #body="{ data }"><Badge :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" /></template></Column>
          <Column header="Actions" style="min-width: 110px"><template #body="{ data }"><Button v-if="isDraft(data)" label="Save" icon="pi pi-save" size="small" severity="secondary" outlined :loading="savingId === data.id" @click="save(data)" /></template></Column>
          <template #expansion="{ data }">
            <div class="m-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
              <p class="mb-2 text-xs font-semibold text-slate-700">Daily attendance · {{ formatDate(period?.start_date) }} to {{ formatDate(period?.end_date) }}</p>
              <DataTable :value="data.attendance_by_day || []" size="small" class="text-xs">
                <Column field="date" header="Day"><template #body="{ data: day }">{{ formatAttendanceDate(day.date) }}</template></Column>
                <Column header="Attendance Status"><template #body="{ data: day }"><Badge :value="attendanceStatusLabel(day)" :severity="attendanceStatusSeverity(day.status)" /></template></Column>
                <Column header="Work Hours"><template #body="{ data: day }">{{ Number(day.worked_hours || 0).toFixed(2) }} hrs</template></Column>
                <Column header="Note"><template #body="{ data: day }"><span class="text-slate-500">{{ day.note || '—' }}</span></template></Column>
              </DataTable>
            </div>
          </template>
          <template #empty><div class="py-8 text-center text-sm text-slate-500">No Basic Payroll records in this period.</div></template>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="showPay" modal header="Pay Completed Payrolls" :style="{ width: 'min(32rem, 95vw)' }">
      <div class="space-y-4">
        <p class="text-sm text-slate-600">This records payout for {{ completedRows.length }} completed employee payroll(s), totaling {{ money(completedRows.reduce((sum, row) => sum + Number(row.net_salary || 0), 0)) }}.</p>
        <div><label class="mb-1 block text-xs font-medium text-slate-600">Payment date</label><DatePicker v-model="paymentDate" dateFormat="yy-mm-dd" fluid /></div>
        <div><label class="mb-1 block text-xs font-medium text-slate-600">Payment method</label><Select v-model="paymentMethod" :options="paymentMethods" optionLabel="label" optionValue="value" fluid /></div>
        <div><label class="mb-1 block text-xs font-medium text-slate-600">Reference number (optional)</label><InputText v-model="referenceNumber" fluid /></div>
        <Message severity="warn" :closable="false">Confirm that this payment has actually been made before continuing.</Message>
      </div>
      <template #footer><Button label="Cancel" severity="secondary" outlined @click="showPay = false" /><Button label="Confirm Bulk Payment" icon="pi pi-wallet" severity="success" :loading="busy" @click="payAll" /></template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import financeService from '@/services/finance.service'
import { useToast } from 'primevue/usetoast'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const loading = ref(false)
const busy = ref(false)
const savingId = ref<number | null>(null)
const error = ref('')
const period = ref<any>(null)
const payrolls = ref<any[]>([])
const expandedRows = ref<Record<string, boolean>>({})
const showPay = ref(false)
const paymentDate = ref(new Date())
const paymentMethod = ref('cash')
const referenceNumber = ref('')
const paymentMethods = [{ label: 'Cash', value: 'cash' }, { label: 'Bank Transfer', value: 'bank_transfer' }, { label: 'GCash', value: 'gcash' }, { label: 'Cheque', value: 'cheque' }]
const isDraft = (row: any) => ['draft', 'calculated'].includes(String(row.status || '').toLowerCase())
const draftRows = computed(() => payrolls.value.filter(isDraft))
const completedRows = computed(() => payrolls.value.filter(row => ['approved', 'released'].includes(String(row.status || '').toLowerCase())))
const money = (amount: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(amount || 0))
const estimate = (row: any) => Number(row.base_salary || 0) + Number(row.overtime_amount || 0) + Number(row.allowances_total || 0) + Number(row.bonuses_total || 0) - Number(row.deductions_total || 0) - Number(row.tax_amount || 0)
const dateString = (value: Date) => `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`
const formatDate = (value?: string) => value ? new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }) : '—'
const formatAttendanceDate = (value: string) => value ? new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-PH', { weekday: 'short', month: 'short', day: 'numeric' }) : '—'
const attendanceStatusLabel = (day: any) => day?.missing_clock_out ? 'Missing clock-out' : ({ present: 'Present', absent: 'Absent', day_off: 'Day Off', on_leave: 'On Leave', holiday: 'Holiday', no_record: 'No Record' }[String(day?.status || '').toLowerCase()] || String(day?.status || 'No Record'))
const attendanceStatusSeverity = (status: string) => ({ present: 'success', absent: 'danger', day_off: 'secondary', on_leave: 'info', holiday: 'info', no_record: 'warn' }[String(status || '').toLowerCase()] || 'secondary')
const statusLabel = (status: string) => ({ draft: 'Draft', calculated: 'Draft', approved: 'Completed', released: 'Completed', paid: 'Paid' }[String(status || '').toLowerCase()] || status || 'Unknown')
const statusSeverity = (status: string) => ({ draft: 'secondary', calculated: 'secondary', approved: 'success', released: 'success', paid: 'info' }[String(status || '').toLowerCase()] || 'warn')

async function load() {
  loading.value = true
  error.value = ''
  try {
    const response = await financeService.getBasicPayrollPeriod(String(route.params.payPeriodId))
    period.value = response?.data?.period
    payrolls.value = response?.data?.payrolls || []
  } catch (err: any) {
    error.value = err?.response?.data?.message || 'Unable to load this pay period.'
  } finally { loading.value = false }
}

async function save(row: any) {
  savingId.value = Number(row.id)
  try {
    await financeService.updateBasicPayrollDraft(Number(row.id), {
      base_salary: row.base_salary, overtime_amount: row.overtime_amount,
      allowances_total: row.allowances_total, bonuses_total: row.bonuses_total,
      deductions_total: row.deductions_total, tax_amount: row.tax_amount,
    })
    toast.add({ severity: 'success', summary: 'Payroll saved', detail: `${row.employee_name}’s draft was updated.`, life: 2500 })
    await load()
  } catch (err: any) { toast.add({ severity: 'error', summary: 'Unable to save', detail: err?.response?.data?.message || 'Review the payroll values.', life: 3500 }) }
  finally { savingId.value = null }
}

async function completeAll() {
  if (!draftRows.value.length) return
  busy.value = true
  try {
    await financeService.completeBasicPayrollDrafts(draftRows.value.map(row => row.id))
    toast.add({ severity: 'success', summary: 'Payrolls completed', detail: 'All drafts in this period are finalized.', life: 3000 })
    await load()
  } catch (err: any) { toast.add({ severity: 'error', summary: 'Completion failed', detail: err?.response?.data?.message || 'Unable to complete all drafts.', life: 3500 }) }
  finally { busy.value = false }
}

async function payAll() {
  if (!completedRows.value.length) return
  busy.value = true
  try {
    await financeService.payBasicPayrolls(completedRows.value.map(row => row.id), {
      payment_date: dateString(paymentDate.value),
      payment_method: paymentMethod.value, reference_number: referenceNumber.value || undefined,
    })
    showPay.value = false
    toast.add({ severity: 'success', summary: 'Payrolls marked paid', detail: 'Bulk payout was recorded.', life: 3000 })
    await load()
  } catch (err: any) { toast.add({ severity: 'error', summary: 'Payment failed', detail: err?.response?.data?.message || 'Unable to record the payout.', life: 3500 }) }
  finally { busy.value = false }
}

onMounted(load)
</script>
