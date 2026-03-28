<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">Expenses</h1>
        <p class="mt-0.5 text-sm text-gray-500">Submit, approve, and pay operational expenses.</p>
      </div>
      <Button label="New Expense" icon="pi pi-plus" @click="showCreate = true" />
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <div class="mb-3 flex items-center justify-between">
              <span class="text-xs font-medium uppercase tracking-wider text-gray-500">Pending</span>
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-orange-100">
                <i class="pi pi-clock text-sm text-orange-600"></i>
              </div>
            </div>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.pending }}</p>
          </div>
        </template>
      </Card>

      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <div class="mb-3 flex items-center justify-between">
              <span class="text-xs font-medium uppercase tracking-wider text-gray-500">Approved</span>
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100">
                <i class="pi pi-check-circle text-sm text-green-600"></i>
              </div>
            </div>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.approved }}</p>
          </div>
        </template>
      </Card>

      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <div class="mb-3 flex items-center justify-between">
              <span class="text-xs font-medium uppercase tracking-wider text-gray-500">Paid</span>
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100">
                <i class="pi pi-wallet text-sm text-blue-600"></i>
              </div>
            </div>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.paid }}</p>
          </div>
        </template>
      </Card>

      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm bg-linear-to-br">
        <template #content>
          <div class="p-5">
            <div class="mb-3 flex items-center justify-between">
              <span class="text-xs font-medium uppercase tracking-wider">Total Amount</span>
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white/20">
                <i class="pi pi-credit-card text-sm"></i>
              </div>
            </div>
            <p class="text-xl font-bold">₱{{ formatMoney(stats.totalAmount) }}</p>
          </div>
        </template>
      </Card>
    </div>

    <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
      <template #header>
        <div class="px-6 pt-6">
          <h2 class="text-lg font-semibold text-gray-900">Filter Expenses</h2>
        </div>
      </template>
      <template #content>
        <div class="p-6 pt-2">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="space-y-2 md:col-span-2">
              <label class="text-xs font-medium uppercase tracking-wider text-gray-500">Search</label>
              <InputText
                v-model="filters.search"
                placeholder="Search category or description"
                class="w-full"
                @keyup.enter="loadExpenses"
              />
            </div>
            <div class="space-y-2">
              <label class="text-xs font-medium uppercase tracking-wider text-gray-500">Status</label>
              <Select
                v-model="filters.status"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                class="w-full"
                fluid
              />
            </div>
            <div class="flex items-end">
              <Button fluid icon="pi pi-refresh" label="Refresh" :loading="loading" @click="loadExpenses" />
            </div>
          </div>
        </div>
      </template>
    </Card>

    <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
      <template #header>
        <div class="px-6 pt-6">
          <h2 class="text-lg font-semibold text-gray-900">Expense Records</h2>
        </div>
      </template>
      <template #content>
        <div class="p-6 pt-2">
          <DataTable
            :value="expenses"
            :loading="loading"
            stripedRows
            responsiveLayout="scroll"
            class="p-datatable-sm"
            paginator
            :rows="10"
            :rowsPerPageOptions="[5, 10, 20, 50]"
          >
            <Column field="category" header="Category" style="min-width: 180px">
              <template #body="{ data }">
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ data.category || '-' }}</p>
                  <p class="text-xs text-gray-500">{{ data.description || 'No description' }}</p>
                </div>
              </template>
            </Column>
            <Column field="amount" header="Amount" style="width: 140px">
              <template #body="{ data }">
                <span class="text-sm font-semibold text-green-600">₱ {{ formatMoney(data.amount) }}</span>
              </template>
            </Column>
            <Column field="expense_date" header="Date" style="width: 140px">
              <template #body="{ data }">{{ formatDate(data.expense_date) }}</template>
            </Column>
            <Column field="status" header="Status" style="width: 150px">
              <template #body="{ data }">
                <Tag :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
              </template>
            </Column>
            <Column header="Actions" style="width: 190px" headerStyle="text-align:center">
              <template #body="{ data }">
                <div class="flex justify-center gap-1">
                  <Button
                    icon="pi pi-eye"
                    text
                    rounded
                    severity="secondary"
                    @click="viewExpense(data)"
                  />
                  <Button
                    v-if="data.status === 'pending_approval'"
                    icon="pi pi-check"
                    text
                    rounded
                    severity="success"
                    @click="approveExpense(data.id)"
                  />
                  <Button
                    v-if="data.status === 'pending_approval'"
                    icon="pi pi-times"
                    text
                    rounded
                    severity="danger"
                    @click="openReject(data)"
                  />
                  <!-- <Button
                    v-if="data.status === 'approved'"
                    icon="pi pi-credit-card"
                    text
                    rounded
                    severity="info"
                    @click="openMarkPaid(data)"
                  /> -->
                </div>
              </template>
            </Column>
            <template #empty>
              <div class="py-12 text-center text-gray-500">No expenses found</div>
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

    <Dialog v-model:visible="showReject" header="Reject Expense" modal :style="{ width: '440px' }">
      <form class="space-y-4" @submit.prevent="rejectExpense">
        <Textarea v-model="rejectNotes" rows="3" class="w-full" placeholder="Reason for rejection" />
        <div class="flex justify-end gap-2">
          <Button label="Cancel" severity="secondary" outlined @click="showReject = false" />
          <Button label="Reject" severity="danger" type="submit" />
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
          <label class="text-sm font-semibold">Reference</label>
          <InputText v-model="paidForm.reference_number" class="w-full" />
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
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import DatePicker from 'primevue/datepicker'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import financeService from '../../../services/finance.service'

const loading = ref(false)
const expenses = ref<any[]>([])
const showCreate = ref(false)
const showReject = ref(false)
const showPaid = ref(false)
const currentExpenseId = ref<number | null>(null)
const rejectNotes = ref('')

const form = ref({
  category: '',
  amount: 0,
  expense_date: new Date(),
  description: '',
})

const paidForm = ref({
  payment_method: '',
  payment_date: new Date(),
  reference_number: '',
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

const stats = computed(() => {
  const rows = expenses.value
  return {
    pending: rows.filter((item: any) => item.status === 'pending_approval').length,
    approved: rows.filter((item: any) => item.status === 'approved').length,
    paid: rows.filter((item: any) => item.status === 'paid').length,
    totalAmount: rows.reduce((sum: number, item: any) => sum + Number(item.amount || 0), 0),
  }
})

const formatMoney = (value: number | string) => {
  const amount = typeof value === 'string' ? parseFloat(value) : value || 0
  return new Intl.NumberFormat('en-PH').format(amount)
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

const statusSeverity = (status: string) => {
  if (status === 'approved') return 'success'
  if (status === 'paid') return 'info'
  if (status === 'rejected') return 'danger'
  return 'warning'
}

const formatStatus = (status: string) => {
  if (!status) return '-'
  return status.split('_').map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const loadExpenses = async () => {
  loading.value = true
  try {
    const params: any = {}
    if (route.query.reference_type) params.reference_type = route.query.reference_type
    if (route.query.reference_id) params.reference_id = route.query.reference_id
    if (filters.value.status) params.status = filters.value.status
    else if (route.query.status) params.status = route.query.status
    if (filters.value.search) params.search = filters.value.search

    const res = await financeService.getExpenses(params)
    expenses.value = res.data?.data || res.data || []
  } finally {
    loading.value = false
  }
}

const createExpense = async () => {
  await financeService.createExpense(form.value)
  showCreate.value = false
  form.value = { category: '', amount: 0, expense_date: new Date(), description: '' }
  loadExpenses()
}

const approveExpense = async (id: number) => {
  await financeService.approveExpense(id)
  loadExpenses()
}

const openReject = (expense: any) => {
  currentExpenseId.value = expense.id
  rejectNotes.value = ''
  showReject.value = true
}

const rejectExpense = async () => {
  if (!currentExpenseId.value) return
  await financeService.rejectExpense(currentExpenseId.value, { notes: rejectNotes.value })
  showReject.value = false
  loadExpenses()
}

const openMarkPaid = (expense: any) => {
  currentExpenseId.value = expense.id
  paidForm.value = { payment_method: '', payment_date: new Date(), reference_number: '' }
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
    reference_number: paidForm.value.reference_number,
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
  background-color: #f9fafb;
  color: #6b7280;
  font-weight: 500;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 1rem 1rem;
  border-bottom: 1px solid #f3f4f6;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 1rem 1rem;
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
