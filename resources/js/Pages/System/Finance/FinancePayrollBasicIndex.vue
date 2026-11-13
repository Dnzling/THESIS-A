<template>
  <div class="mx-auto max-w-7xl space-y-5 px-4 py-5 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-900">Basic Payroll</h1>
        <p class="mt-1 text-sm text-slate-500">Review each pay period, complete calculations, and record payouts.</p>
      </div>
      <div class="flex w-full flex-wrap gap-2 sm:w-auto">
        <Button icon="pi pi-refresh" label="Refresh" severity="secondary" outlined size="small" :loading="loading" @click="loadPayrolls" />
        <Button icon="pi pi-plus" label="Generate Payroll" severity="warn" size="small" @click="generateDialogVisible = true" />
      </div>
    </div>

    <SimplePayrollRunDialog v-model:visible="generateDialogVisible" @generated="onPayrollGenerated" />

    <Card class="rounded-xl border border-slate-200 shadow-sm">
      <template #content>
        <div class="space-y-4">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="text-base font-semibold text-slate-900">Payroll by Pay Period</h2><p class="mt-0.5 text-xs text-slate-500">Open a period to review, edit, complete, and pay payrolls.</p></div>
            <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
              <div class="w-full sm:w-72">
                <IconField>
                  <InputIcon class="pi pi-search" />
                  <InputText v-model="filters.search" fluid size="small" placeholder="Search pay period" />
                </IconField>
              </div>
              <Button v-if="filters.search" label="Clear Filter" icon="pi pi-filter-slash" severity="danger" size="small" @click="filters.search = ''" />
            </div>
          </div>
          <DataTable
            :value="filteredPayrolls"
            :loading="loading"
            rowHover
            size="small"
            class="text-xs"
            paginator
            :rows="10"
            :rowsPerPageOptions="[5, 10, 20, 50]"
            removableSort
            tableStyle="min-width: 62rem"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} pay periods"
          >
            <Column field="start_date" header="Pay Period" sortable style="min-width: 240px">
              <template #body="{ data }"><div><p class="font-medium text-slate-900">{{ data.name }}</p><p class="mt-0.5 text-xs text-slate-500">{{ formatDate(data.start_date) }} – {{ formatDate(data.end_date) }}</p></div></template>
            </Column>
            <Column field="employee_count" header="Employees" sortable style="width: 140px"><template #body="{ data }">{{ data.employee_count }}</template></Column>
            <Column field="total_net" header="Total Net Pay" sortable style="width: 180px"><template #body="{ data }"><span class="font-semibold tabular-nums text-slate-900">{{ formatCurrency(data.total_net) }}</span></template></Column>
            <Column header="Progress" style="min-width: 190px">
              <template #body="{ data }"><div class="flex flex-wrap gap-1"><Badge v-if="data.statuses?.draft || data.statuses?.calculated" :value="`${(data.statuses?.draft || 0) + (data.statuses?.calculated || 0)} Draft`" severity="secondary" /><Badge v-if="data.statuses?.approved" :value="`${data.statuses.approved} Completed`" severity="success" /><Badge v-if="data.statuses?.paid" :value="`${data.statuses.paid} Paid`" severity="info" /></div></template>
            </Column>
            <Column header="Actions" style="width: 130px" headerStyle="text-align:center">
              <template #body="{ data }">
                <div class="flex justify-center">
                  <Button icon="pi pi-eye" label="View" size="small" severity="secondary" outlined rounded @click="openPeriod(data)" />
                </div>
              </template>
            </Column>

            <template #empty>
              <div class="py-10 text-center"><i class="pi pi-inbox text-2xl text-slate-300" /><p class="mt-2 text-sm font-medium text-slate-700">No payroll periods found</p><p class="mt-1 text-xs text-slate-500">Generate a payroll draft to begin.</p></div>
            </template>
          </DataTable>
        </div>
      </template>
    </Card>

    <Dialog v-model:visible="payoutDialogVisible" modal class="w-full max-w-xl" :closable="!savingPayout" header="Confirm Payout">
      <div class="space-y-4">
        <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm">
          <p class="font-semibold text-blue-900">Review</p>
          <div class="mt-2 grid grid-cols-1 gap-2 md:grid-cols-2">
            <p><span class="text-blue-700">Employee:</span> <span class="font-medium text-slate-900">{{ payoutPreview.name }}</span></p>
            <p><span class="text-blue-700">Pay Period:</span> <span class="font-medium text-slate-900">{{ payoutPreview.period }}</span></p>
            <p><span class="text-blue-700">Net Pay:</span> <span class="font-semibold text-emerald-700">{{ formatCurrency(payoutPreview.amount) }}</span></p>
            <p><span class="text-blue-700">Current Status:</span> <span class="font-medium text-slate-900">{{ formatStatus(payoutPreview.status) }}</span></p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Payment Date</label>
            <DatePicker v-model="payoutForm.payment_date" fluid date-format="yy-mm-dd" />
            <small v-if="errors.payment_date" class="text-red-500">{{ errors.payment_date }}</small>
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Payment Method</label>
            <Select v-model="payoutForm.payment_method" :options="paymentMethods" optionLabel="label" optionValue="value" fluid />
            <small v-if="errors.payment_method" class="text-red-500">{{ errors.payment_method }}</small>
          </div>
          <div class="md:col-span-2">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Reference Number</label>
            <InputText v-model="payoutForm.reference_number" fluid placeholder="e.g., bank transfer reference" />
          </div>
          <div class="md:col-span-2">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</label>
            <Textarea v-model="payoutForm.notes" rows="3" fluid placeholder="Optional notes" />
          </div>
        </div>

        <div class="flex items-start gap-2">
          <Checkbox v-model="payoutForm.confirmed" binary inputId="confirmPayout" />
          <label for="confirmPayout" class="text-sm text-gray-600">I confirm this payout information is correct.</label>
        </div>
        <small v-if="errors.confirmed" class="text-red-500">{{ errors.confirmed }}</small>
      </div>

      <template #footer>
        <Button label="Cancel" severity="secondary" outlined :disabled="savingPayout" @click="payoutDialogVisible = false" />
        <Button label="Mark Paid" severity="success" :loading="savingPayout" @click="submitPayout" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Checkbox from 'primevue/checkbox'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import financeService from '../../../services/finance.service'
import SimplePayrollRunDialog from './SimplePayrollRunDialog.vue'

const loading = ref(false)
const router = useRouter()
const toast = useToast()
const savingPayout = ref(false)
const payrolls = ref<any[]>([])
const payoutDialogVisible = ref(false)
const generateDialogVisible = ref(false)
const submittingId = ref<number | null>(null)
const selectedPayroll = ref<any>(null)

const filters = reactive({
  search: '',
})

const payoutForm = reactive({
  payment_date: new Date(),
  payment_method: 'cash',
  reference_number: '',
  notes: '',
  confirmed: false,
})

const errors = reactive({
  payment_date: '',
  payment_method: '',
  confirmed: '',
})

const paymentMethods = [
  { label: 'Cash', value: 'cash' },
  { label: 'Bank Transfer', value: 'bank_transfer' },
  { label: 'GCash', value: 'gcash' },
  { label: 'Cheque', value: 'cheque' },
]

const formatMoney = (value: number | string) => {
  const amount = typeof value === 'string' ? parseFloat(value) : value || 0
  return new Intl.NumberFormat('en-PH').format(amount)
}
const formatCurrency = (value: number | string) => `PHP ${formatMoney(value)}`

const filteredPayrolls = computed(() => {
  if (!filters.search) return payrolls.value
  const keyword = filters.search.toLowerCase()
  return payrolls.value.filter((row) => String(row?.name || '').toLowerCase().includes(keyword))
})

const openPeriod = (period: any) => router.push({ name: 'finance.payroll.basic.detail', params: { payPeriodId: String(period.id) } })

const formatEmployeeName = (row: any) => {
  const employee = row?.employee || {}
  const fname = employee.user?.fname || employee.fname || row?.fname || ''
  const lname = employee.user?.lname || employee.lname || row?.lname || ''
  return `${fname} ${lname}`.trim() || row?.employee_name || 'N/A'
}

const formatStatus = (value: string) => {
  if (['approved', 'released'].includes(String(value || '').toLowerCase())) return 'Completed'
  return value ? value.replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase()) : 'Unknown'
}
const statusSeverity = (value: string) => {
  const status = String(value || '').toLowerCase()
  if (status === 'paid') return 'success'
  if (status === 'approved' || status === 'released') return 'success'
  if (status === 'processing' || status === 'pending') return 'warn'
  if (status === 'draft') return 'secondary'
  if (status === 'rejected' || status === 'failed') return 'danger'
  return 'info'
}

const formatDate = (value: string) => {
  if (!value) return '-'
  return new Date(value).toLocaleDateString('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const payoutPreview = computed(() => ({
  name: selectedPayroll.value ? formatEmployeeName(selectedPayroll.value) : '-',
  period: selectedPayroll.value?.pay_period?.name || selectedPayroll.value?.payPeriod?.name || '-',
  amount: selectedPayroll.value?.net_salary || 0,
  status: selectedPayroll.value?.status || 'unknown',
}))

const isDraft = (row: any) => ['draft', 'calculated'].includes(String(row?.status || '').toLowerCase())
const isPayable = (row: any) => ['approved', 'released'].includes(String(row?.status || '').toLowerCase())

const completePayroll = async (row: any) => {
  submittingId.value = Number(row.id)
  try {
    await financeService.completeBasicPayroll(Number(row.id))
    await loadPayrolls()
    toast.add({ severity: 'success', summary: 'Payroll completed', detail: 'The payroll calculation is finalized and ready for payout.', life: 3000 })
  } catch (error: any) {
    const message = error?.response?.data?.message || 'Unable to complete this payroll.'
    toast.add({ severity: 'error', summary: 'Completion failed', detail: message, life: 4000 })
  } finally {
    submittingId.value = null
  }
}

const onPayrollGenerated = async () => {
  await loadPayrolls()
  toast.add({ severity: 'success', summary: 'Payroll drafts created', detail: 'Open the pay period to edit, complete, or pay its payrolls.', life: 3500 })
}

const resetErrors = () => {
  errors.payment_date = ''
  errors.payment_method = ''
  errors.confirmed = ''
}

const validatePayout = () => {
  resetErrors()
  let valid = true
  if (!payoutForm.payment_date) {
    errors.payment_date = 'Payment date is required.'
    valid = false
  }
  if (!payoutForm.payment_method) {
    errors.payment_method = 'Select a payment method.'
    valid = false
  }
  if (!payoutForm.confirmed) {
    errors.confirmed = 'Please confirm the payout details.'
    valid = false
  }
  return valid
}

const openPayoutDialog = (row: any) => {
  selectedPayroll.value = row
  payoutForm.payment_date = new Date()
  payoutForm.payment_method = 'cash'
  payoutForm.reference_number = ''
  payoutForm.notes = ''
  payoutForm.confirmed = false
  resetErrors()
  payoutDialogVisible.value = true
}

const submitPayout = async () => {
  if (!selectedPayroll.value) return
  if (!validatePayout()) return

  savingPayout.value = true
  try {
    const payload = {
      payment_date: payoutForm.payment_date instanceof Date
        ? payoutForm.payment_date.toISOString().slice(0, 10)
        : payoutForm.payment_date,
      payment_method: payoutForm.payment_method,
      reference_number: payoutForm.reference_number || undefined,
      notes: payoutForm.notes || undefined,
    }

    await financeService.payBasicPayrolls([selectedPayroll.value.id], payload)
    payoutDialogVisible.value = false
    await loadPayrolls()
  } finally {
    savingPayout.value = false
  }
}

const loadPayrolls = async () => {
  loading.value = true
  try {
    const res = await financeService.getBasicPayrollPeriods()
    payrolls.value = res?.data || []
  } finally {
    loading.value = false
  }
}

onMounted(loadPayrolls)
</script>
