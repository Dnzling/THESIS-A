<template>
  <div class="mx-auto space-y-5 p-4 text-sm md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">Expenses</h1>
        <p class="mt-0.5 text-sm text-gray-500">Submit, approve, and pay operational expenses.</p>
      </div>
      <Button label="New Expense" icon="pi pi-plus" severity="warn" size="small" @click="showCreate = true" />
    </div>

    <Message v-if="loadError" severity="error" :closable="false">{{ loadError }}</Message>

    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
      <Card class="rounded-xl border border-slate-200 shadow-sm">
        <template #content>
          <div class="p-4">
            <div class="mb-3 flex items-center justify-between">
              <span class="text-xs font-medium uppercase tracking-wider text-gray-500">Pending · 28 days</span>
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-orange-100">
                <i class="pi pi-clock text-sm text-orange-600"></i>
              </div>
            </div>
            <Skeleton v-if="loading" width="3rem" height="1.75rem" />
            <template v-else>
              <p class="text-2xl font-semibold text-gray-900">{{ summary.current.pending }}</p>
              <p class="mt-1 flex items-center gap-1 text-[11px]" :class="comparisonClass('pending')"><i :class="comparisonIcon('pending')" />{{ comparisonLabel('pending') }} vs previous 28 days</p>
            </template>
          </div>
        </template>
      </Card>

      <Card class="rounded-xl border border-slate-200 shadow-sm">
        <template #content>
          <div class="p-4">
            <div class="mb-3 flex items-center justify-between">
              <span class="text-xs font-medium uppercase tracking-wider text-gray-500">Approved · 28 days</span>
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100">
                <i class="pi pi-check-circle text-sm text-green-600"></i>
              </div>
            </div>
            <Skeleton v-if="loading" width="3rem" height="1.75rem" />
            <template v-else>
              <p class="text-2xl font-semibold text-gray-900">{{ summary.current.approved }}</p>
              <p class="mt-1 flex items-center gap-1 text-[11px]" :class="comparisonClass('approved')"><i :class="comparisonIcon('approved')" />{{ comparisonLabel('approved') }} vs previous 28 days</p>
            </template>
          </div>
        </template>
      </Card>

      <Card class="rounded-xl border border-slate-200 shadow-sm">
        <template #content>
          <div class="p-4">
            <div class="mb-3 flex items-center justify-between">
              <span class="text-xs font-medium uppercase tracking-wider text-gray-500">Paid · 28 days</span>
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100">
                <i class="pi pi-wallet text-sm text-blue-600"></i>
              </div>
            </div>
            <Skeleton v-if="loading" width="3rem" height="1.75rem" />
            <template v-else>
              <p class="text-2xl font-semibold text-gray-900">{{ summary.current.paid }}</p>
              <p class="mt-1 flex items-center gap-1 text-[11px]" :class="comparisonClass('paid')"><i :class="comparisonIcon('paid')" />{{ comparisonLabel('paid') }} vs previous 28 days</p>
            </template>
          </div>
        </template>
      </Card>

      <Card class="rounded-xl border border-orange-200 bg-orange-50/60 shadow-sm">
        <template #content>
          <div class="p-4">
            <div class="mb-3 flex items-center justify-between">
              <span class="text-xs font-medium uppercase tracking-wider text-orange-800">Total Amount · 28 days</span>
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white">
                <i class="pi pi-credit-card text-sm"></i>
              </div>
            </div>
            <Skeleton v-if="loading" width="7rem" height="1.75rem" />
            <template v-else>
              <p class="text-xl font-semibold text-slate-900">{{ formatMoney(summary.current.total_amount) }}</p>
              <p class="mt-1 flex items-center gap-1 text-[11px]" :class="comparisonClass('total_amount')"><i :class="comparisonIcon('total_amount')" />{{ comparisonLabel('total_amount') }} vs previous 28 days</p>
            </template>
          </div>
        </template>
      </Card>
    </div>

    <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
      <template #header>
        <div class="m-4 flex flex-wrap items-center justify-between gap-3">
          <h2 class="text-sm font-semibold text-gray-900">Expense Records</h2>
          <Button icon="pi pi-refresh" aria-label="Refresh expenses" severity="secondary" outlined rounded size="small" :loading="loading" @click="loadExpenses" />
          <div class="flex w-full flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
            <div class="w-full sm:max-w-sm">
              <InputText
                v-model="filters.search"
                placeholder="Search reference, category, or description"
                class="w-full" size="small"
                @keyup.enter="loadExpenses"
              />
            </div>
            <div class="w-full sm:w-48">
              <Select
                v-model="filters.status"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                class="w-full"
                fluid size="small"
                aria-label="Filter by status"
                @change="loadExpenses"
              />
            </div>
            <div class="flex items-end gap-2">
              <Button v-if="filters.search || filters.status" label="Clear Filter" severity="danger" size="small" @click="clearFilters" />
            </div>
          </div>
        </div>
      </template>
      <template #content>
        <div v-if="loading" class="space-y-3 px-4 pb-4"><div class="grid grid-cols-7 gap-4 border-b border-slate-100 px-3 py-3"><Skeleton v-for="cell in 7" :key="`head-${cell}`" height="0.75rem" /></div><div v-for="row in 6" :key="`expense-${row}`" class="grid grid-cols-7 gap-4 border-b border-slate-50 px-3 py-3"><Skeleton v-for="cell in 7" :key="cell" height="1.25rem" /></div></div>
        <div v-else>
          <DataTable
            :value="expenses"
            rowHover
            responsiveLayout="scroll"
            class="p-datatable-sm expense-table text-xs"
            paginator
            :rows="10"
            :rowsPerPageOptions="[5, 10, 20, 50]"
          >
            <Column field="created_at" header="Created Date & Time" sortable style="width: 180px">
              <template #body="{ data }">
                <div class="whitespace-nowrap">
                  <p class="font-medium text-slate-800">{{ formatDate(data.created_at) }}</p>
                  <p class="mt-0.5 text-[11px] text-slate-500">{{ formatTime(data.created_at) }}</p>
                </div>
              </template>
            </Column>
            <Column header="Reference" style="min-width: 190px">
              <template #body="{ data }">
                <div class="min-w-0">
                  <p class="truncate font-mono text-xs font-semibold text-slate-800" :title="expenseReference(data)">{{ expenseReference(data) }}</p>
                  <p v-if="data.payment_reference || (data.reference_number && data.reference_number !== data.branch_expense_request?.reference_number)" class="mt-1 truncate text-[11px] text-slate-500">
                    Payment: {{ data.payment_reference || data.reference_number }}
                  </p>
                </div>
              </template>
            </Column>
            <Column field="branch.name" header="Branch" style="min-width: 150px">
              <template #body="{ data }">{{ data.branch?.name || 'Store-wide' }}</template>
            </Column>
            <Column field="category" header="Expense" style="min-width: 180px">
              <template #body="{ data }">
                <div>
                  <p class="text-sm font-medium text-gray-900 capitalize">{{ data.category || '-' }}</p>
                  <p class="text-xs text-gray-500">{{ data.description || 'No description' }}</p>
                </div>
              </template>
            </Column>
            <Column field="amount" header="Amount" style="width: 150px" headerStyle="text-align:right">
              <template #body="{ data }">
                <span class="block text-right text-sm font-semibold tabular-nums text-slate-900">{{ formatMoney(data.amount) }}</span>
              </template>
            </Column>
            <Column field="status" header="Status" style="width: 150px">
              <template #body="{ data }">
                <Badge :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
              </template>
            </Column>
            <Column header="Actions" style="width: 110px" headerStyle="text-align:center">
              <template #body="{ data }">
                <div class="flex justify-center gap-1">
                  <Button icon="pi pi-eye" label="View" outlined rounded size="small" @click="viewExpense(data)" />
                </div>
              </template>
            </Column>
            <template #empty>
              <div class="px-4 py-12 text-center">
                <i class="pi pi-receipt mb-3 block text-2xl text-slate-300"></i>
                <p class="font-medium text-slate-700">No expenses found</p>
                <p class="mt-1 text-xs text-slate-500">Try changing the filters or create a new expense.</p>
              </div>
            </template>
          </DataTable>
        </div>
      </template>
    </Card>

    <Dialog v-model:visible="showCreate" header="Create Expense" modal :style="{ width: '540px' }">
      <form class="space-y-4" @submit.prevent="createExpense">
        <div>
          <label class="text-sm font-semibold">Category</label>
          <InputText v-model="form.category" class="w-full" placeholder="e.g. Utilities" />
        </div>
        <div>
          <label class="text-sm font-semibold">Amount</label>
          <InputNumber v-model="form.amount" class="w-full" :min="0" mode="currency" currency="PHP" fluid />
        </div>
        <div>
          <label class="text-sm font-semibold">Expense Date</label>
          <DatePicker v-model="form.expense_date" class="w-full" fluid />
        </div>
        <div>
          <label class="text-sm font-semibold">Description</label>
          <Textarea v-model="form.description" rows="3" class="w-full" />
        </div>
        <div class="flex justify-end gap-2">
          <Button label="Cancel" severity="secondary" outlined @click="showCreate = false" />
          <Button label="Create" type="submit" />
        </div>
      </form>
    </Dialog>

    <Dialog v-model:visible="showPaid" header="Mark Expense as Paid" modal :style="{ width: '440px' }">
      <form class="space-y-4" @submit.prevent="markPaid">
        <div>
          <label class="text-sm font-semibold">Payment Method</label>
          <Select v-model="paidForm.payment_method" :options="paymentMethods" class="w-full" fluid />
        </div>
        <div>
          <label class="text-sm font-semibold">Payment Date</label>
          <DatePicker v-model="paidForm.payment_date" class="w-full" fluid />
        </div>
        <div>
          <label class="text-sm font-semibold">Payment Reference</label>
          <InputText v-model="paidForm.payment_reference" class="w-full" />
        </div>
        <div class="flex justify-end gap-2">
          <Button label="Cancel" severity="secondary" outlined @click="showPaid = false" />
          <Button label="Mark Paid" type="submit" />
        </div>
      </form>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Badge from 'primevue/badge'
import Skeleton from 'primevue/skeleton'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import DatePicker from 'primevue/datepicker'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Message from 'primevue/message'
import financeService from '../../../services/finance.service'

const loading = ref(true)
const loadError = ref('')
const expenses = ref<any[]>([])
const showCreate = ref(false)
const showPaid = ref(false)
const currentExpenseId = ref<number | null>(null)

const form = ref({
  category: '',
  amount: 0,
  expense_date: new Date(),
  description: '',
})

const paidForm = ref({
  payment_method: '',
  payment_date: new Date(),
  payment_reference: '',
})

const paymentMethods = ['cash', 'bank_transfer', 'check', 'card']
const route = useRoute()
const router = useRouter()
const filters = ref({
  search: '',
  status: '',
})

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Pending Approval', value: 'pending_approval' },
  { label: 'Approved', value: 'approved' },
  { label: 'Paid', value: 'paid' },
  { label: 'Rejected', value: 'rejected' },
]

const emptySummary = () => ({ pending: 0, approved: 0, paid: 0, total_amount: 0 })
const summary = ref({ current: emptySummary(), previous: emptySummary() })

const formatMoney = (value: number | string) => {
  const amount = typeof value === 'string' ? parseFloat(value) : value || 0
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number.isFinite(amount) ? amount : 0)
}

type SummaryMetric = 'pending' | 'approved' | 'paid' | 'total_amount'

const comparisonLabel = (key: SummaryMetric) => {
  const current = Number(summary.value.current[key] || 0)
  const previous = Number(summary.value.previous[key] || 0)
  if (previous === 0) return current === 0 ? '0.0%' : 'New'
  const percent = ((current - previous) / Math.abs(previous)) * 100
  return `${percent > 0 ? '+' : ''}${percent.toFixed(1)}%`
}

const comparisonIcon = (key: SummaryMetric) => {
  const current = Number(summary.value.current[key] || 0)
  const previous = Number(summary.value.previous[key] || 0)
  if (current === previous) return 'pi pi-minus'
  return current > previous ? 'pi pi-arrow-up' : 'pi pi-arrow-down'
}

const comparisonClass = (key: SummaryMetric) => {
  const current = Number(summary.value.current[key] || 0)
  const previous = Number(summary.value.previous[key] || 0)
  if (current === previous) return 'text-slate-500'
  const higherIsGood = key === 'approved' || key === 'paid'
  const favorable = higherIsGood ? current > previous : current < previous
  return favorable ? 'text-emerald-700' : 'text-rose-700'
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-PH', { timeZone: 'Asia/Manila', month: 'short', day: 'numeric', year: 'numeric' })
}

const formatTime = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleTimeString('en-PH', { timeZone: 'Asia/Manila', hour: 'numeric', minute: '2-digit', hour12: true })
}

const expenseReference = (expense: any) => expense.branch_expense_request?.reference_number || expense.reference_number || `EXP-${expense.id}`

const statusSeverity = (status: string) => {
  if (status === 'approved') return 'success'
  if (status === 'paid') return 'success'
  if (status === 'rejected') return 'danger'
  if (status === 'pending_approval') return 'warn'
  if (status === 'cancelled' || status === 'draft') return 'secondary'
  return 'info'
}

const formatStatus = (status: string) => {
  if (!status) return '-'
  return status.split('_').map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const loadExpenses = async () => {
  loading.value = true
  loadError.value = ''
  try {
    const params: any = {}
    if (route.query.reference_type) params.reference_type = route.query.reference_type
    if (route.query.reference_id) params.reference_id = route.query.reference_id
    if (filters.value.status) params.status = filters.value.status
    else if (route.query.status) params.status = route.query.status
    if (filters.value.search) params.search = filters.value.search

    const res = await financeService.getExpenses(params)
    expenses.value = res.data?.data || res.data || []
    summary.value = {
      current: { ...emptySummary(), ...(res.summary?.current || {}) },
      previous: { ...emptySummary(), ...(res.summary?.previous || {}) },
    }
  } catch (error: any) {
    loadError.value = error?.response?.data?.message || 'Unable to load expenses. Please try again.'
  } finally {
    loading.value = false
  }
}

const clearFilters = () => {
  filters.value = { search: '', status: '' }
  loadExpenses()
}

const createExpense = async () => {
  await financeService.createExpense(form.value)
  showCreate.value = false
  form.value = { category: '', amount: 0, expense_date: new Date(), description: '' }
  loadExpenses()
}

const openMarkPaid = (expense: any) => {
  currentExpenseId.value = expense.id
  paidForm.value = { payment_method: '', payment_date: new Date(), payment_reference: '' }
  showPaid.value = true
}

const viewExpense = (expense: any) => {
  if (!expense?.id) return
  router.push({ name: 'finance.expenses.detail', params: { id: String(expense.id) } })
}

const markPaid = async () => {
  if (!currentExpenseId.value) return
  await financeService.markExpensePaid(currentExpenseId.value, {
    payment_method: paidForm.value.payment_method,
    payment_date: paidForm.value.payment_date,
    payment_reference: paidForm.value.payment_reference,
  })
  showPaid.value = false
  loadExpenses()
}

onMounted(loadExpenses)
</script>

<style scoped>
* {
  transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

:deep(.p-card) {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02);
}

:deep(.p-card .p-card-content) {
  padding: 0;
}

:deep(.p-card .p-card-body) {
  padding: 0;
}

:deep(.p-datatable) {
  border-radius: 16px;
  overflow: hidden;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  background-color: #f8fafc;
  color: #6b7280;
  font-weight: 500;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 0.75rem 0.875rem;
  border-bottom: 1px solid #f3f4f6;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 0.75rem 0.875rem;
  border-bottom: 1px solid #f3f4f6;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: #f9fafb;
}

:deep(.p-tag) {
  border-radius: 9999px;
  font-weight: 500;
}
</style>
