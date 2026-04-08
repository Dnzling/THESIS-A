<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">Cashflow</h1>
        <p class="mt-0.5 text-sm text-gray-500">Unified finance ledger: incoming sales, outgoing supplier/payroll/expenses, and manual budget adjustments.</p>
      </div>
      <div class="flex gap-2">
        <Button icon="pi pi-refresh" label="Refresh" :loading="loading" @click="loadCashflow" />
        <Button icon="pi pi-plus" label="Top Up" severity="success" @click="openAdjustDialog('in')" />
        <Button icon="pi pi-minus" label="Deduct" severity="danger" outlined @click="openAdjustDialog('out')" />
      </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Available Balance</p>
            <p class="mt-2 text-2xl font-semibold text-emerald-700">PHP {{ formatMoney(availableBalance) }}</p>
          </div>
        </template>
      </Card>

      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Account</p>
            <p class="mt-2 text-sm font-semibold text-gray-900">{{ accountName }}</p>
            <p class="mt-1 text-xs text-gray-500">Type: {{ accountType }}</p>
          </div>
        </template>
      </Card>

      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Incoming (Filtered)</p>
            <p class="mt-2 text-2xl font-semibold text-emerald-700">PHP {{ formatMoney(transactionSummary.incoming) }}</p>
          </div>
        </template>
      </Card>

      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Outgoing (Filtered)</p>
            <p class="mt-2 text-2xl font-semibold text-rose-700">PHP {{ formatMoney(transactionSummary.outgoing) }}</p>
          </div>
        </template>
      </Card>

      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Net (Filtered)</p>
            <p class="mt-2 text-2xl font-semibold" :class="transactionSummary.net >= 0 ? 'text-emerald-700' : 'text-rose-700'">
              PHP {{ formatMoney(transactionSummary.net) }}
            </p>
          </div>
        </template>
      </Card>
    </div>

    <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
      <template #header>
        <div class="px-6 pt-6 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900">Cashflow Transactions</h2>
          <div class="flex flex-wrap gap-2 items-center">
            <InputText v-model="filters.search" placeholder="Search description/source..." class="w-64" @input="loadTransactions" />
            <Select
              v-model="filters.direction"
              :options="directionOptions"
              optionLabel="label"
              optionValue="value"
              class="w-44"
              placeholder="All Directions"
              @change="loadTransactions"
            />
            <Select
              v-model="filters.reference_type"
              :options="referenceTypeOptions"
              optionLabel="label"
              optionValue="value"
              class="w-56"
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
              class="w-72"
              @update:modelValue="loadTransactions"
            />
          </div>
        </div>
      </template>
      <template #content>
        <div class="p-6 pt-2">
          <DataTable :value="transactions" :loading="loadingTransactions" responsiveLayout="scroll" stripedRows class="p-datatable-sm">
            <Column field="id" header="#" style="width: 70px" />
            <Column field="direction" header="Direction" style="width: 120px">
              <template #body="{ data }">
                <Tag :value="String(data.direction || '').toUpperCase()" :severity="data.direction === 'in' ? 'success' : 'warn'" />
              </template>
            </Column>
            <Column field="amount" header="Amount" style="width: 140px">
              <template #body="{ data }">PHP {{ formatMoney(data.amount) }}</template>
            </Column>
            <Column field="balance_before" header="Before" style="width: 140px">
              <template #body="{ data }">PHP {{ formatMoney(data.balance_before) }}</template>
            </Column>
            <Column field="balance_after" header="After" style="width: 140px">
              <template #body="{ data }">PHP {{ formatMoney(data.balance_after) }}</template>
            </Column>
            <Column field="reference_type" header="Source" style="width: 180px">
              <template #body="{ data }">{{ formatReferenceType(data.reference_type) }}</template>
            </Column>
            <Column field="payment_method" header="Method" style="width: 140px" />
            <Column field="description" header="Description" style="min-width: 220px" />
            <Column field="created_at" header="Date" style="width: 160px">
              <template #body="{ data }">{{ formatDateTime(data.created_at) }}</template>
            </Column>
            <template #empty>
              <div class="py-8 text-center text-sm text-gray-500">No cashflow transactions found.</div>
            </template>
          </DataTable>
        </div>
      </template>
    </Card>

    <Dialog v-model:visible="showAdjustDialog" modal :header="adjustForm.direction === 'in' ? 'Add Budget' : 'Deduct Budget'" :style="{ width: '480px' }">
      <form class="space-y-4" @submit.prevent="submitAdjustment">
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Amount</label>
          <InputNumber v-model="adjustForm.amount" mode="currency" currency="PHP" :min="0.01" fluid />
        </div>
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Payment Method</label>
          <Select v-model="adjustForm.payment_method" :options="paymentMethods" class="w-full" fluid />
        </div>
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Description</label>
          <InputText v-model="adjustForm.description" class="w-full" fluid placeholder="Manual budget adjustment" />
        </div>
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Notes</label>
          <Textarea v-model="adjustForm.notes" rows="3" class="w-full" fluid />
        </div>
        <div class="flex justify-end gap-2">
          <Button type="button" severity="secondary" outlined label="Cancel" @click="showAdjustDialog = false" />
          <Button type="submit" :severity="adjustForm.direction === 'in' ? 'success' : 'danger'" :label="adjustForm.direction === 'in' ? 'Add Budget' : 'Deduct Budget'" :loading="savingAdjustment" />
        </div>
      </form>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import DatePicker from 'primevue/datepicker'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'
import financeService from '../../../services/finance.service'

const toast = useToast()

const loading = ref(false)
const loadingTransactions = ref(false)
const savingAdjustment = ref(false)
const showAdjustDialog = ref(false)

const account = ref<any>(null)
const availableBalance = ref(0)
const transactions = ref<any[]>([])
const transactionSummary = ref({ incoming: 0, outgoing: 0, net: 0 })

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
  { label: 'Manual Adjustment', value: 'manual_adjustment' },
]

const paymentMethods = ['cash', 'bank_transfer', 'check', 'gcash', 'system']

const adjustForm = ref({
  direction: 'in' as 'in' | 'out',
  amount: null as number | null,
  payment_method: 'cash',
  description: 'Manual budget adjustment',
  notes: '',
})

const accountName = computed(() => String(account.value?.name || 'Main Operating Account'))
const accountType = computed(() => String(account.value?.type || 'operating'))

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

const formatReferenceType = (value: string) => {
  const map: Record<string, string> = {
    sales_order: 'Sales',
    ecommerce_order: 'Ecommerce',
    invoice: 'Supplier Invoice',
    expense: 'Expense',
    payroll: 'Payroll',
    manual_adjustment: 'Manual Adjustment',
  }
  return map[value] || (value ? value.replace(/_/g, ' ') : '-')
}

const openAdjustDialog = (direction: 'in' | 'out') => {
  adjustForm.value.direction = direction
  adjustForm.value.amount = null
  adjustForm.value.payment_method = 'cash'
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
      payment_method: 'cash',
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

onMounted(loadCashflow)
</script>
