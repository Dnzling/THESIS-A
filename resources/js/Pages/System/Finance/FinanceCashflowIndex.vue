<template>
  <div class="space-y-6 p-4 text-sm md:p-6">
    <!-- Header Controls -->
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-950">Cash Flow</h1>
      </div>
      <div class="flex items-center gap-2">
        <Button icon="pi pi-refresh" label="Refresh" severity="secondary" outlined size="small" :loading="loading" @click="loadCashflow" />
        <Button icon="pi pi-minus" label="Deduct" severity="secondary" outlined size="small" @click="openAdjustDialog('out')" />
        <Button icon="pi pi-plus" label="Add Funds" severity="warn" size="small" @click="openAdjustDialog('in')" />
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
      <!-- Available Balance Card -->
      <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <div class="flex min-h-24 flex-col justify-between">
            <div class="flex items-start justify-between gap-3">
              <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Available Balance</p>
              <Tag :value="formatAccountType(accountType)" severity="secondary" class="!text-[10px]" />
            </div>
            <div>
              <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">₱{{ formatMoney(availableBalance) }}</p>
              <p class="mt-1 truncate text-xs text-slate-500">{{ accountName }}</p>
            </div>
          </div>
        </template>
      </Card>

      <!-- Incoming -->
      <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <div class="flex min-h-24 flex-col justify-between">
            <p class="text-xs font-medium text-slate-500">Incoming</p>
            <p class="text-xl font-semibold text-emerald-600">₱{{ formatMoney(transactionSummary.incoming) }}</p>
            <p class="text-[11px] text-slate-400">For current filters</p>
          </div>
        </template>
      </Card>

      <!-- Outgoing -->
      <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <div class="flex min-h-24 flex-col justify-between">
            <p class="text-xs font-medium text-slate-500">Outgoing</p>
            <p class="text-xl font-semibold text-rose-600">₱{{ formatMoney(transactionSummary.outgoing) }}</p>
            <p class="text-[11px] text-slate-400">For current filters</p>
          </div>
        </template>
      </Card>

      <!-- Net Movement -->
      <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <div class="flex min-h-24 flex-col justify-between">
            <p class="text-xs font-medium text-slate-500">Net Movement</p>
            <p class="text-xl font-semibold" :class="transactionSummary.net >= 0 ? 'text-emerald-600' : 'text-rose-600'">
              {{ transactionSummary.net >= 0 ? '+' : '-' }}₱{{ formatMoney(Math.abs(transactionSummary.net)) }}
            </p>
            <p class="text-[11px] text-slate-400">Incoming minus outgoing</p>
          </div>
        </template>
      </Card>

      <!-- Cash Runway Metric -->
      <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <div class="flex min-h-24 flex-col justify-between">
            <div class="flex items-center justify-between">
              <p class="text-xs font-medium text-slate-500">Est. Runway</p>
              <i class="pi pi-clock text-xs text-slate-400" />
            </div>
            <p class="text-xl font-semibold text-slate-900">{{ cashRunwayMonths }} <span class="text-xs font-normal text-slate-500">Months</span></p>
            <p class="text-[11px] text-slate-400">At ~₱{{ formatMoney(estimatedMonthlyBurn) }}/mo burn</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- Main Ledger Card -->
    <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
      <template #header>
        <div class="m-4 mt-6 space-y-3 border-b border-slate-200 pb-3">
          <p class="text-xs text-slate-500">Select a cashflow row to inspect its source record and full transaction breakdown.</p>
          <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
            <IconField fluid>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" placeholder="Search transactions" size="small" fluid @input="scheduleTransactionLoad" />
            </IconField>

            <Select
              v-model="filters.direction"
              :options="directionOptions"
              optionLabel="label"
              optionValue="value"
              fluid
              size="small"
              placeholder="All Directions"
              @change="loadTransactions"
            />

            <Select
              v-model="filters.reference_type"
              :options="referenceTypeOptions"
              optionLabel="label"
              optionValue="value"
              fluid
              size="small"
              placeholder="All Sources"
              @change="loadTransactions"
            />

            <DatePicker
              v-model="filters.date_range"
              selectionMode="range"
              :manualInput="false"
              showIcon
              showButtonBar
              dateFormat="yy-mm-dd"
              placeholder="Date range"
              fluid
              :maxDate="new Date()"
              size="small"
              @update:modelValue="loadTransactions"
            />

            <div class="flex items-center"><Button label="Clear Filters" severity="secondary" outlined size="small" @click="resetFilters" /></div>
          </div>
        </div>
      </template>

      <template #content>
        <div class="p-0">
          <DataTable
            :value="transactions"
            :loading="loadingTransactions"
            responsiveLayout="scroll"
            rowHover
            @row-click="openTransactionDetail"
            :paginator="true"
            :rows="10"
            :rowsPerPageOptions="[10, 20, 50]"
            class="p-datatable-sm text-sm [&_tbody_tr]:cursor-pointer"
            sortField="created_at"
            :sortOrder="-1"
          >
            <Column field="created_at" header="Date" sortable style="width: 190px">
              <template #body="{ data }">
                <span class="text-md text-slate-600">{{ formatDateTime(data.created_at) }}</span>
              </template>
            </Column>

            <Column field="amount" header="Amount" sortable style="width: 140px">
              <template #body="{ data }">
                <span class="font-semibold" :class="data.direction === 'in' ? 'text-emerald-600' : 'text-rose-600'">
                  {{ data.direction === 'in' ? '+' : '-' }}₱{{ formatMoney(data.amount) }}
                </span>
              </template>
            </Column>

            <Column field="balance_after" header="After" sortable style="width: 140px">
              <template #body="{ data }">
                <span class="font-medium text-slate-800">₱{{ formatMoney(data.balance_after) }}</span>
              </template>
            </Column>

            <Column field="reference_type" header="Source" sortable style="width: 180px">
              <template #body="{ data }">{{ formatReferenceType(data.reference_type) }}</template>
            </Column>

            <Column field="payment_method" header="Method" sortable style="width: 160px">
              <template #body="{ data }">{{ formatPaymentMethod(data.payment_method) }}</template>
            </Column>

            <template #empty>
              <div class="py-8 text-center text-sm text-gray-500">No cashflow transactions found.</div>
            </template>
          </DataTable>
        </div>
      </template>
    </Card>

    <Dialog v-model:visible="showTransactionDetail" modal header="Cashflow Transaction Details" :style="{ width: 'min(860px, 94vw)' }" :breakpoints="{ '640px': '96vw' }">
      <div v-if="loadingTransactionDetail" class="space-y-3 py-3">
        <Skeleton height="5rem" />
        <Skeleton height="7rem" />
        <Skeleton height="7rem" />
      </div>
      <div v-else-if="selectedTransaction" class="space-y-4 text-xs">
        <section class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">{{ selectedSource?.label || formatReferenceType(selectedTransaction.reference_type) }}</p>
              <h2 class="mt-1 text-base font-semibold text-slate-900">{{ selectedTransaction.description || 'Cashflow transaction' }}</h2>
              <p class="mt-1 text-slate-500">{{ formatDateTime(selectedTransaction.created_at) }}</p>
            </div>
            <div class="text-right">
              <p class="text-xl font-semibold" :class="selectedTransaction.direction === 'in' ? 'text-emerald-600' : 'text-rose-600'">
                {{ selectedTransaction.direction === 'in' ? '+' : '-' }}₱{{ formatMoney(selectedTransaction.amount) }}
              </p>
              <Tag :value="selectedTransaction.direction === 'in' ? 'Incoming' : 'Outgoing'" :severity="selectedTransaction.direction === 'in' ? 'success' : 'warn'" class="mt-1 !text-[10px]" />
            </div>
          </div>
          <div class="mt-4 grid grid-cols-1 gap-3 border-t border-slate-200 pt-3 sm:grid-cols-3">
            <div><p class="text-slate-500">Balance before</p><p class="mt-1 font-semibold text-slate-800">₱{{ formatMoney(selectedTransaction.balance_before) }}</p></div>
            <div><p class="text-slate-500">Transaction amount</p><p class="mt-1 font-semibold text-slate-800">₱{{ formatMoney(selectedTransaction.amount) }}</p></div>
            <div><p class="text-slate-500">Balance after</p><p class="mt-1 font-semibold text-slate-800">₱{{ formatMoney(selectedTransaction.balance_after) }}</p></div>
          </div>
        </section>

        <section class="rounded-xl border border-slate-200 p-4">
          <h3 class="font-semibold text-slate-900">Transaction record</h3>
          <dl class="mt-3 grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
            <div><dt class="text-slate-500">Cashflow entry</dt><dd class="mt-1 font-medium text-slate-800">#{{ selectedTransaction.id }}</dd></div>
            <div><dt class="text-slate-500">Source reference</dt><dd class="mt-1 font-medium text-slate-800">{{ selectedTransaction.reference_type ? formatReferenceType(selectedTransaction.reference_type) : 'Manual entry' }}<span> · {{ selectedSource?.reference_number || 'Not recorded' }}</span></dd></div>
            <div><dt class="text-slate-500">Payment method</dt><dd class="mt-1 font-medium text-slate-800">{{ formatPaymentMethod(selectedTransaction.payment_method) }}</dd></div>
            <div><dt class="text-slate-500">Account</dt><dd class="mt-1 font-medium text-slate-800">{{ selectedTransaction.account?.name || accountName }} <span class="text-slate-500">({{ formatAccountType(selectedTransaction.account?.type || accountType) }})</span></dd></div>
            <div><dt class="text-slate-500">Recorded by</dt><dd class="mt-1 font-medium text-slate-800">{{ selectedTransaction.creator?.full_name || creatorName(selectedTransaction.creator) }}<span v-if="selectedTransaction.creator?.email" class="block font-normal text-slate-500">{{ selectedTransaction.creator.email }}</span></dd></div>
            <div v-if="selectedTransaction.updated_at"><dt class="text-slate-500">Last updated</dt><dd class="mt-1 font-medium text-slate-800">{{ formatDateTime(selectedTransaction.updated_at) }}</dd></div>
          </dl>
          <div v-if="transactionMetaFields.length" class="mt-4 border-t border-slate-100 pt-3">
            <h4 class="font-medium text-slate-800">Additional transaction data</h4>
            <dl class="mt-2 grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
              <div v-for="field in transactionMetaFields" :key="field.label">
                <dt class="text-slate-500">{{ field.label }}</dt>
                <dd class="mt-0.5 break-words font-medium text-slate-800">{{ field.value }}</dd>
              </div>
            </dl>
          </div>
        </section>

        <section v-if="selectedSource?.fields?.length" class="rounded-xl border border-slate-200 p-4">
          <h3 class="font-semibold text-slate-900">{{ selectedSource.label }} details</h3>
          <dl class="mt-3 grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
            <div v-for="field in selectedSource.fields" :key="field.label">
              <dt class="text-slate-500">{{ field.label }}</dt>
              <dd class="mt-1 break-words font-medium text-slate-800">{{ formatSourceValue(field.label, field.value) }}</dd>
            </div>
          </dl>
        </section>

        <section v-if="selectedSource?.items?.length" class="rounded-xl border border-slate-200 p-4">
          <h3 class="font-semibold text-slate-900">Related items</h3>
          <div class="mt-3 divide-y divide-slate-100">
            <div v-for="(item, index) in selectedSource.items" :key="index" class="grid grid-cols-2 gap-x-4 gap-y-2 py-3 sm:grid-cols-3">
              <div v-for="field in item" :key="field.label" :class="field.label === 'Product' ? 'col-span-2 sm:col-span-3' : ''">
                <p class="text-slate-500">{{ field.label }}</p>
                <p class="mt-0.5 break-words font-medium text-slate-800">{{ formatSourceValue(field.label, field.value) }}</p>
              </div>
            </div>
          </div>
        </section>

        <p v-if="!selectedSource?.fields?.length && selectedTransaction.description" class="rounded-lg bg-orange-50 px-3 py-2 text-slate-700">
          {{ selectedTransaction.description }}
        </p>
      </div>
    </Dialog>

    <!-- Funds Dialog -->
    <Dialog v-model:visible="showAdjustDialog" modal :header="adjustForm.direction === 'in' ? 'Add Budget' : 'Deduct Budget'" :style="{ width: '480px' }">
      <form class="space-y-4 pt-2" @submit.prevent="submitAdjustment">
        <div>
          <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Amount</label>
          <InputNumber v-model="adjustForm.amount" mode="currency" currency="PHP" locale="en-PH" :min="0.01" fluid size="small" />
        </div>

        <div>
          <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Payment Method</label>
          <Select
            v-model="adjustForm.payment_method"
            :options="paymentMethodOptions"
            optionLabel="label"
            optionValue="value"
            class="w-full"
            fluid
            size="small"
            :disabled="adjustForm.direction === 'in'"
          />
          <small v-if="adjustForm.direction === 'in'" class="mt-1 block text-xs text-gray-500">
            Top-up accepts Online Payment only.
          </small>
        </div>

        <div>
          <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Description</label>
          <InputText v-model="adjustForm.description" class="w-full" fluid size="small" placeholder="Manual budget adjustment" />
        </div>

        <div>
          <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Notes</label>
          <Textarea v-model="adjustForm.notes" rows="3" class="w-full" fluid size="small" placeholder="Optional internal memo" />
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <Button type="button" severity="secondary" outlined label="Cancel" size="small" @click="showAdjustDialog = false" />
          <Button
            type="submit"
            :severity="adjustForm.direction === 'in' ? 'success' : 'danger'"
            :label="adjustForm.direction === 'in' ? 'Proceed to Online Payment' : 'Deduct Budget'"
            :loading="savingAdjustment || topupPaymongoLoading"
            size="small"
          />
        </div>
      </form>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import DatePicker from 'primevue/datepicker'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'
import financeService from '../../../services/finance.service'
import paymongoService from '../../../services/paymongo.service'

const toast = useToast()

const loading = ref(false)
const loadingTransactions = ref(false)
const savingAdjustment = ref(false)
const topupPaymongoLoading = ref(false)
const topupPaymongoIntentId = ref<string | null>(null)
const topupPaymongoPolling = ref<ReturnType<typeof setInterval> | null>(null)
let transactionSearchTimer: ReturnType<typeof setTimeout> | null = null
const showAdjustDialog = ref(false)

const account = ref<any>(null)
const availableBalance = ref(0)
const transactions = ref<any[]>([])
const transactionSummary = ref({ incoming: 0, outgoing: 0, net: 0 })
const showTransactionDetail = ref(false)
const loadingTransactionDetail = ref(false)
const selectedTransaction = ref<any>(null)
const selectedSource = ref<any>(null)

const filters = ref({
  direction: '',
  reference_type: '',
  search: '',
  date_range: null as Date[] | null,
})
const directionOptions = [
  { label: 'All Directions', value: '' },
  { label: 'In', value: 'in' },
  { label: 'Out', value: 'out' },
]

const referenceTypeOptions = [
  { label: 'All Sources', value: '' },
  { label: 'Sales', value: 'sales_order' },
  { label: 'Ecommerce', value: 'ecommerce_order' },
  { label: 'Supplier Invoices', value: 'invoice' },
  { label: 'Expenses', value: 'expense' },
  { label: 'Payroll', value: 'payroll' },
  { label: 'Cash Advances', value: 'cash_advance' },
  { label: 'Returned Advance Cash', value: 'cash_advance_return' },
  { label: 'Liquidation Reimbursements', value: 'liquidation_reimbursement' },
  { label: 'Manual Adjustment', value: 'manual_adjustment' },
]

const paymentMethodOptions = computed(() => {
  if (adjustForm.value.direction === 'in') {
    return [{ label: 'GCash', value: 'paymongo_gcash' }]
  }
  return [
    { label: 'Cash', value: 'cash' },
    { label: 'Bank Transfer', value: 'bank_transfer' },
    { label: 'Check', value: 'check' },
    { label: 'GCash', value: 'gcash' },
    { label: 'System', value: 'system' },
  ]
})

const adjustForm = ref({
  direction: 'in' as 'in' | 'out',
  amount: null as number | null,
  payment_method: 'cash',
  description: 'Manual budget adjustment',
  notes: '',
})

const accountName = computed(() => String(account.value?.name || 'Main Operating Account'))
const accountType = computed(() => String(account.value?.type || 'operating'))
const formatAccountType = (value: string) => value
  .replace(/_/g, ' ')
  .replace(/\b\w/g, (letter) => letter.toUpperCase())

const formatMoney = (value: number | string) => {
  const amount = typeof value === 'string' ? parseFloat(value) : Number(value || 0)
  return new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(
    Number.isFinite(amount) ? amount : 0
  )
}

const formatDateTime = (value: string) => {
  if (!value) return '-'
  return new Date(value).toLocaleString('en-PH', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const loadAccount = async () => {
  const res = await financeService.getCashflowAccount()
  const data = res?.data || {}
  account.value = data.account || null
  availableBalance.value = Number(data.available_balance || 0)
}

const loadTransactions = async () => {
  loadingTransactions.value = true
  try {
    const from = filters.value.date_range?.[0] ? new Date(filters.value.date_range[0]).toISOString().slice(0, 10) : undefined
    const to = filters.value.date_range?.[1] ? new Date(filters.value.date_range[1]).toISOString().slice(0, 10) : undefined

    const res = await financeService.getCashflowTransactions({
      direction: filters.value.direction || undefined,
      reference_type: filters.value.reference_type || undefined,
      search: filters.value.search || undefined,
      date_from: from,
      date_to: to,
      per_page: 100,
    })
    const payload = res?.data || {}
    const paginated = payload?.transactions || payload
    transactions.value = paginated?.data || []
    transactionSummary.value = payload?.summary || { incoming: 0, outgoing: 0, net: 0 }
  } finally {
    loadingTransactions.value = false
  }
}

const scheduleTransactionLoad = () => {
  if (transactionSearchTimer) clearTimeout(transactionSearchTimer)
  transactionSearchTimer = setTimeout(loadTransactions, 350)
}

const resetFilters = () => {
  filters.value = {
    direction: '',
    reference_type: '',
    search: '',
    date_range: null,
  }
  loadTransactions()
}

const formatReferenceType = (value: string) => {
  const map: Record<string, string> = {
    sales_order: 'Sales',
    sales_pos_order: 'POS Sale',
    ecommerce_order: 'Ecommerce',
    invoice: 'Supplier Invoice',
    expense: 'Expense',
    finance_expense: 'Expense',
    finance_refund: 'Customer Refund',
    cash_advance: 'Cash Advance',
    cash_advance_return: 'Returned Advance Cash',
    liquidation_reimbursement: 'Liquidation Reimbursement',
    payroll: 'Payroll',
    manual_adjustment: 'Manual Adjustment',
    cashflow_topup: 'Cashflow Top-up',
  }
  return map[value] || (value ? value.replace(/_/g, ' ') : '-')
}

const creatorName = (creator: any) => {
  if (!creator) return 'System or unavailable'
  return [creator.fname, creator.lname].filter(Boolean).join(' ') || `User #${creator.id}`
}

const formatMetaLabel = (value: string) => value.replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())

const transactionMetaFields = computed(() => {
  const meta = selectedTransaction.value?.meta
  if (!meta || typeof meta !== 'object') return []
  return Object.entries(meta).map(([key, value]) => ({
    label: formatMetaLabel(key),
    value: formatDetailValue(value),
  }))
})

const formatDetailValue = (value: unknown) => {
  if (value === null || value === undefined || value === '') return '-'
  if (typeof value === 'boolean') return value ? 'Yes' : 'No'
  if (typeof value === 'object') return JSON.stringify(value)
  return String(value)
}

const formatSourceValue = (label: string, value: unknown) => {
  if (value === null || value === undefined || value === '') return '-'
  const normalizedLabel = label.toLowerCase()
  if ((typeof value === 'number' || (typeof value === 'string' && value.trim() !== '' && Number.isFinite(Number(value)))) && /amount|total|subtotal|discount|tax|shipping|salary|overtime|allowance|bonus|deduction|price|tendered|change/.test(normalizedLabel)) {
    return `₱${formatMoney(value)}`
  }
  return formatDetailValue(value)
}

const openTransactionDetail = async (event: any) => {
  const row = event?.data
  if (!row?.id) return

  selectedTransaction.value = row
  selectedSource.value = null
  showTransactionDetail.value = true
  loadingTransactionDetail.value = true
  try {
    const response = await financeService.getCashflowTransactionDetail(row.id)
    const detail = response?.data || {}
    selectedTransaction.value = detail.transaction || row
    selectedSource.value = detail.source || null
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Unable to Load Details',
      detail: error?.response?.data?.message || 'Transaction details could not be loaded.',
      life: 3000,
    })
  } finally {
    loadingTransactionDetail.value = false
  }
}

const formatPaymentMethod = (value: string) => {
  if (!value) return '-'
  const map: Record<string, string> = {
    bank_transfer: 'Bank Transfer',
    credit_card: 'Credit Card',
    debit_card: 'Debit Card',
    paymongo_gcash: 'Online Payment GCash',
    gcash: 'GCash',
  }
  return map[value] || value.replace(/_/g, ' ').replace(/\b\w/g, (s) => s.toUpperCase())
}

const openAdjustDialog = (direction: 'in' | 'out') => {
  adjustForm.value.direction = direction
  adjustForm.value.amount = null
  adjustForm.value.payment_method = direction === 'in' ? 'paymongo_gcash' : 'cash'
  adjustForm.value.description = direction === 'in' ? 'Manual budget add' : 'Manual budget deduction'
  adjustForm.value.notes = ''
  showAdjustDialog.value = true
}

const loadCashflow = async () => {
  loading.value = true
  try {
    await Promise.all([loadAccount(), loadTransactions()])
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Load Failed',
      detail: error?.response?.data?.message || 'Unable to load cashflow data.',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const submitAdjustment = async () => {
  if (!adjustForm.value.amount || Number(adjustForm.value.amount) <= 0) {
    toast.add({ severity: 'warn', summary: 'Invalid Amount', detail: 'Enter a valid amount.', life: 2500 })
    return
  }

  if (adjustForm.value.direction === 'in') {
    await startPaymongoTopUp()
    return
  }

  savingAdjustment.value = true
  try {
    await financeService.adjustCashflow({
      direction: adjustForm.value.direction,
      amount: Number(adjustForm.value.amount),
      payment_method: adjustForm.value.payment_method,
      description: adjustForm.value.description,
      notes: adjustForm.value.notes,
    })

    toast.add({
      severity: 'success',
      summary: 'Budget Updated',
      detail: adjustForm.value.direction === 'in' ? 'Budget add recorded.' : 'Budget deduction recorded.',
      life: 2500,
    })
    showAdjustDialog.value = false
    adjustForm.value = {
      direction: 'in',
      amount: null,
      payment_method: 'paymongo_gcash',
      description: 'Manual budget adjustment',
      notes: '',
    }
    await loadCashflow()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Adjustment Failed',
      detail: error?.response?.data?.message || 'Unable to save adjustment.',
      life: 3000,
    })
  } finally {
    savingAdjustment.value = false
  }
}

const startPaymongoTopUp = async () => {
  topupPaymongoLoading.value = true
  try {
    const amount = Number(adjustForm.value.amount || 0)
    const storeId = Number(account.value?.store_id || 0) || null

    const intentResponse = await paymongoService.createIntent({
      amount: Math.max(Math.round(amount * 100), 1),
      payment_method_allowed: ['gcash'],
      store_id: storeId,
      payable_type: 'cashflow_topup',
      payable_id: Number(storeId || 0),
      description: adjustForm.value.description || 'Cashflow top-up',
      metadata: {
        store_id: storeId,
        notes: adjustForm.value.notes || null,
      },
    })

    topupPaymongoIntentId.value = intentResponse?.data?.data?.id || null
    if (!topupPaymongoIntentId.value) {
      throw new Error(intentResponse?.message || 'Failed to create Online Payment top-up intent.')
    }

    const gcashResponse = await paymongoService.startGcash(topupPaymongoIntentId.value, {
      name: 'Finance Top-up',
      email: 'finance@example.com',
      phone: '09170000000',
      return_url: window.location.href,
    })

    const redirectUrl = gcashResponse?.data?.redirect_url
    if (!redirectUrl) {
      throw new Error('Online Payment checkout URL is missing.')
    }

    window.open(redirectUrl, '_blank')
    startTopupPaymongoPolling()
    toast.add({
      severity: 'info',
      summary: 'Online Payment',
      detail: 'Complete the payment in Online Payment checkout. Balance will update automatically after success.',
      life: 3500,
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Online Payment',
      detail: error?.response?.data?.message || error?.message || 'Unable to start Online Payment top-up.',
      life: 3500,
    })
  } finally {
    topupPaymongoLoading.value = false
  }
}

const pollTopupPaymongoStatus = async () => {
  if (!topupPaymongoIntentId.value) return
  try {
    const response = await paymongoService.getIntent(topupPaymongoIntentId.value)
    const status = String(response?.data?.attributes?.status || '').toLowerCase()

    if (['succeeded', 'paid'].includes(status)) {
      stopTopupPaymongoPolling()
      showAdjustDialog.value = false
      adjustForm.value = {
        direction: 'in',
        amount: null,
        payment_method: 'paymongo_gcash',
        description: 'Manual budget adjustment',
        notes: '',
      }
      await loadCashflow()
      toast.add({
        severity: 'success',
        summary: 'Top-up Success',
        detail: 'Budget was added to finance balance.',
        life: 3000,
      })
      return
    }

    if (['failed', 'canceled', 'cancelled'].includes(status)) {
      stopTopupPaymongoPolling()
      toast.add({
        severity: 'warn',
        summary: 'Top-up Not Completed',
        detail: 'Online payment was not completed.',
        life: 3000,
      })
    }
  } catch {
    // keep polling
  }
}

const startTopupPaymongoPolling = () => {
  stopTopupPaymongoPolling()
  pollTopupPaymongoStatus()
  topupPaymongoPolling.value = setInterval(pollTopupPaymongoStatus, 8000)
}

const stopTopupPaymongoPolling = () => {
  if (topupPaymongoPolling.value) {
    clearInterval(topupPaymongoPolling.value)
    topupPaymongoPolling.value = null
  }
}

onMounted(loadCashflow)
onBeforeUnmount(() => {
  stopTopupPaymongoPolling()
  if (transactionSearchTimer) clearTimeout(transactionSearchTimer)
})
</script>
