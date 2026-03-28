<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">Basic Payroll</h1>
        <p class="mt-0.5 text-sm text-gray-500">Simple payouts list for small teams with safer payment steps.</p>
      </div>
      <div class="flex items-center gap-2">
        <Button icon="pi pi-refresh" label="Refresh" :loading="loading" @click="loadPayrolls" />
      </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Entries</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ summary.totalEntries }}</p>
          </div>
        </template>
      </Card>
      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Total Paid</p>
            <p class="mt-2 text-xl font-semibold text-green-700">? {{ formatMoney(summary.totalPaid) }}</p>
          </div>
        </template>
      </Card>
      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Total Pending</p>
            <p class="mt-2 text-xl font-semibold text-orange-600">? {{ formatMoney(summary.totalPending) }}</p>
          </div>
        </template>
      </Card>
    </div>

    <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
      <template #header>
        <div class="px-6 pt-6 flex flex-wrap items-center justify-between gap-3">
          <h2 class="text-lg font-semibold text-gray-900">Payouts</h2>
          <div class="w-full max-w-xs">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" fluid placeholder="Search employee or pay period" />
            </IconField>
          </div>
        </div>
      </template>
      <template #content>
        <div class="p-6 pt-2">
          <DataTable
            :value="filteredPayrolls"
            :loading="loading"
            stripedRows
            responsiveLayout="scroll"
            class="p-datatable-sm"
            paginator
            :rows="10"
            :rowsPerPageOptions="[5, 10, 20, 50]"
          >
            <Column header="Employee" style="min-width: 200px">
              <template #body="{ data }">
                <div>
                  <p class="text-sm font-semibold text-gray-900">{{ formatEmployeeName(data) }}</p>
                  <p class="text-xs text-gray-500">{{ data.employee?.employee_number || data.employee_number || '-' }}</p>
                </div>
              </template>
            </Column>

            <Column header="Pay Period" style="min-width: 200px">
              <template #body="{ data }">
                <span class="text-sm text-gray-700">{{ data.pay_period?.name || data.payPeriod?.name || '-' }}</span>
              </template>
            </Column>

            <Column header="Net Pay" style="width: 160px">
              <template #body="{ data }">
                <span class="text-sm font-semibold text-green-700">? {{ formatMoney(data.net_salary || 0) }}</span>
              </template>
            </Column>

            <Column header="Status" style="width: 140px">
              <template #body="{ data }">
                <Tag :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
              </template>
            </Column>

            <Column header="Pay Date" style="width: 180px">
              <template #body="{ data }">
                <span class="text-sm text-gray-700">{{ formatDate(data.paid_at || data.pay_date || data.created_at) }}</span>
              </template>
            </Column>

            <Column header="Actions" style="width: 140px" headerStyle="text-align:center">
              <template #body="{ data }">
                <div class="flex justify-center">
                  <Button
                    v-if="!isPaid(data)"
                    icon="pi pi-wallet"
                    label="Mark Paid"
                    size="small"
                    severity="success"
                    @click="openPayoutDialog(data)"
                  />
                </div>
              </template>
            </Column>

            <template #empty>
              <div class="py-12 text-center text-gray-500">No payroll records found</div>
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
            <p><span class="text-gray-600">Employee:</span> <span class="font-medium text-gray-900">{{ payoutPreview.name }}</span></p>
            <p><span class="text-gray-600">Pay Period:</span> <span class="font-medium text-gray-900">{{ payoutPreview.period }}</span></p>
            <p><span class="text-gray-600">Net Pay:</span> <span class="font-semibold text-green-700">? {{ formatMoney(payoutPreview.amount) }}</span></p>
            <p><span class="text-gray-600">Current Status:</span> <span class="font-medium text-gray-900">{{ formatStatus(payoutPreview.status) }}</span></p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Payment Date</label>
            <DatePicker v-model="payoutForm.payment_date" fluid date-format="yy-mm-dd" />
            <small v-if="errors.payment_date" class="text-red-500">{{ errors.payment_date }}</small>
          </div>
          <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Payment Method</label>
            <Select v-model="payoutForm.payment_method" :options="paymentMethods" optionLabel="label" optionValue="value" fluid />
            <small v-if="errors.payment_method" class="text-red-500">{{ errors.payment_method }}</small>
          </div>
          <div class="md:col-span-2">
            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Reference Number</label>
            <InputText v-model="payoutForm.reference_number" fluid placeholder="e.g., bank transfer reference" />
          </div>
          <div class="md:col-span-2">
            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Notes</label>
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
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
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

const loading = ref(false)
const savingPayout = ref(false)
const payrolls = ref<any[]>([])
const payoutDialogVisible = ref(false)
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

const summary = computed(() => {
  const totalEntries = payrolls.value.length
  const totalPaid = payrolls.value.reduce((sum, row) => {
    const status = String(row?.status || '').toLowerCase()
    return sum + (status === 'paid' ? Number(row?.net_salary || 0) : 0)
  }, 0)
  const totalPending = payrolls.value.reduce((sum, row) => {
    const status = String(row?.status || '').toLowerCase()
    return sum + (status === 'paid' ? 0 : Number(row?.net_salary || 0))
  }, 0)

  return { totalEntries, totalPaid, totalPending }
})

const filteredPayrolls = computed(() => {
  if (!filters.search) return payrolls.value
  const keyword = filters.search.toLowerCase()
  return payrolls.value.filter((row) => {
    const name = formatEmployeeName(row).toLowerCase()
    const period = String(row?.pay_period?.name || row?.payPeriod?.name || '').toLowerCase()
    return name.includes(keyword) || period.includes(keyword)
  })
})

const formatEmployeeName = (row: any) => {
  const employee = row?.employee || {}
  const fname = employee.fname || row?.fname || ''
  const lname = employee.lname || row?.lname || ''
  return `${fname} ${lname}`.trim() || row?.employee_name || 'N/A'
}

const formatStatus = (value: string) => (value ? value.replace(/_/g, ' ') : 'unknown')
const statusSeverity = (value: string) => {
  const status = String(value || '').toLowerCase()
  if (status === 'paid') return 'success'
  if (status === 'approved') return 'info'
  if (status === 'processing') return 'warning'
  if (status === 'draft') return 'secondary'
  return 'help'
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

const isPaid = (row: any) => String(row?.status || '').toLowerCase() === 'paid'

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

    await financeService.markPayrollPaid(selectedPayroll.value.id, payload)
    payoutDialogVisible.value = false
    await loadPayrolls()
  } finally {
    savingPayout.value = false
  }
}

const loadPayrolls = async () => {
  loading.value = true
  try {
    const res = await financeService.getPayroll({ per_page: 500 })
    payrolls.value = res.data?.data || res.data || []
  } finally {
    loading.value = false
  }
}

onMounted(loadPayrolls)
</script>
