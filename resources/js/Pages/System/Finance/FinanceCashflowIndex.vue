<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">Cashflow</h1>
        <p class="mt-0.5 text-sm text-gray-500">Operating account balance, top-ups, and transaction ledger.</p>
      </div>
      <div class="flex gap-2">
        <Button icon="pi pi-refresh" label="Refresh" :loading="loading" @click="loadCashflow" />
        <Button icon="pi pi-plus" label="Top Up" severity="success" @click="showTopUpDialog = true" />
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
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Recent Entries</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ transactions.length }}</p>
          </div>
        </template>
      </Card>
    </div>

    <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
      <template #header>
        <div class="px-6 pt-6 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900">Cashflow Transactions</h2>
          <div class="flex gap-2">
            <Select
              v-model="filters.direction"
              :options="directionOptions"
              optionLabel="label"
              optionValue="value"
              class="w-44"
              placeholder="All Directions"
              @change="loadTransactions"
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
            <Column field="reference_type" header="Reference" style="width: 160px" />
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

    <Dialog v-model:visible="showTopUpDialog" modal header="Top Up Operating Balance" :style="{ width: '480px' }">
      <form class="space-y-4" @submit.prevent="submitTopUp">
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Amount</label>
          <InputNumber v-model="topUpForm.amount" mode="currency" currency="PHP" :min="0.01" fluid />
        </div>
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Payment Method</label>
          <Select v-model="topUpForm.payment_method" :options="paymentMethods" class="w-full" fluid />
        </div>
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Description</label>
          <InputText v-model="topUpForm.description" class="w-full" fluid placeholder="Manual top-up" />
        </div>
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Notes</label>
          <Textarea v-model="topUpForm.notes" rows="3" class="w-full" fluid />
        </div>
        <div class="flex justify-end gap-2">
          <Button type="button" severity="secondary" outlined label="Cancel" @click="showTopUpDialog = false" />
          <Button type="submit" severity="success" label="Submit Top Up" :loading="savingTopUp" />
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
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'
import financeService from '../../../services/finance.service'

const toast = useToast()

const loading = ref(false)
const loadingTransactions = ref(false)
const savingTopUp = ref(false)
const showTopUpDialog = ref(false)

const account = ref<any>(null)
const availableBalance = ref(0)
const transactions = ref<any[]>([])

const filters = ref({ direction: '' })
const directionOptions = [
  { label: 'All Directions', value: '' },
  { label: 'In', value: 'in' },
  { label: 'Out', value: 'out' },
]

const paymentMethods = ['cash', 'bank_transfer', 'check', 'gcash', 'system']

const topUpForm = ref({
  amount: null as number | null,
  payment_method: 'cash',
  description: 'Manual top-up',
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
    const res = await financeService.getCashflowTransactions({
      direction: filters.value.direction || undefined,
      per_page: 100,
    })
    const payload = res?.data || {}
    transactions.value = payload?.data || payload || []
  } finally {
    loadingTransactions.value = false
  }
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

const submitTopUp = async () => {
  if (!topUpForm.value.amount || Number(topUpForm.value.amount) <= 0) {
    toast.add({ severity: 'warn', summary: 'Invalid Amount', detail: 'Enter a valid top-up amount.', life: 2500 })
    return
  }

  savingTopUp.value = true
  try {
    await financeService.topUpCashflow({
      amount: Number(topUpForm.value.amount),
      payment_method: topUpForm.value.payment_method,
      description: topUpForm.value.description,
      notes: topUpForm.value.notes,
    })

    toast.add({ severity: 'success', summary: 'Top Up Saved', detail: 'Cashflow top-up recorded.', life: 2500 })
    showTopUpDialog.value = false
    topUpForm.value = {
      amount: null,
      payment_method: 'cash',
      description: 'Manual top-up',
      notes: '',
    }
    await loadCashflow()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Top Up Failed',
      detail: error?.response?.data?.message || 'Unable to save top-up.',
      life: 3000,
    })
  } finally {
    savingTopUp.value = false
  }
}

onMounted(loadCashflow)
</script>
