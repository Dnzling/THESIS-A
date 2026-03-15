<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Expenses</h1>
        <p class="text-gray-500 mt-1">Submit, approve, and pay operational expenses</p>
      </div>
      <Button label="New Expense" icon="pi pi-plus" @click="showCreate = true" />
    </div>

    <Card>
      <template #content>
        <DataTable :value="expenses" :loading="loading" stripedRows responsiveLayout="scroll">
          <Column field="category" header="Category" />
          <Column field="amount" header="Amount" style="width: 140px">
            <template #body="{ data }">₱ {{ formatMoney(data.amount) }}</template>
          </Column>
          <Column field="expense_date" header="Date" style="width: 140px">
            <template #body="{ data }">{{ formatDate(data.expense_date) }}</template>
          </Column>
          <Column field="status" header="Status" style="width: 140px">
            <template #body="{ data }">
              <Tag :value="data.status" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
          <Column header="Actions" style="width: 200px">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button icon="pi pi-check" text rounded severity="success"
                  v-if="data.status === 'pending_approval'" @click="approveExpense(data.id)" />
                <Button icon="pi pi-times" text rounded severity="danger"
                  v-if="data.status === 'pending_approval'" @click="openReject(data)" />
                <Button icon="pi pi-credit-card" text rounded severity="info"
                  v-if="data.status === 'approved'" @click="openMarkPaid(data)" />
              </div>
            </template>
          </Column>
          <template #empty>
            <div class="text-center py-8 text-gray-500">No expenses found</div>
          </template>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="showCreate" header="Create Expense" modal :style="{ width: '520px' }">
      <form class="space-y-4" @submit.prevent="createExpense">
        <div>
          <label class="text-sm font-semibold">Category</label>
          <InputText v-model="form.category" class="w-full" placeholder="e.g. Utilities" />
        </div>
        <div>
          <label class="text-sm font-semibold">Amount</label>
          <InputNumber v-model="form.amount" class="w-full" :min="0" mode="currency" currency="PHP" />
        </div>
        <div>
          <label class="text-sm font-semibold">Expense Date</label>
          <DatePicker v-model="form.expense_date" class="w-full" />
        </div>
        <div>
          <label class="text-sm font-semibold">Description</label>
          <Textarea v-model="form.description" rows="3" class="w-full" />
        </div>
        <div class="flex justify-end gap-2">
          <Button label="Cancel" class="p-button-secondary" @click="showCreate = false" />
          <Button label="Create" type="submit" />
        </div>
      </form>
    </Dialog>

    <Dialog v-model:visible="showReject" header="Reject Expense" modal :style="{ width: '420px' }">
      <form class="space-y-4" @submit.prevent="rejectExpense">
        <Textarea v-model="rejectNotes" rows="3" class="w-full" placeholder="Reason for rejection" />
        <div class="flex justify-end gap-2">
          <Button label="Cancel" class="p-button-secondary" @click="showReject = false" />
          <Button label="Reject" severity="danger" type="submit" />
        </div>
      </form>
    </Dialog>

    <Dialog v-model:visible="showPaid" header="Mark Expense as Paid" modal :style="{ width: '420px' }">
      <form class="space-y-4" @submit.prevent="markPaid">
        <div>
          <label class="text-sm font-semibold">Payment Method</label>
          <Select v-model="paidForm.payment_method" :options="paymentMethods" class="w-full" />
        </div>
        <div>
          <label class="text-sm font-semibold">Payment Date</label>
          <DatePicker v-model="paidForm.payment_date" class="w-full" />
        </div>
        <div>
          <label class="text-sm font-semibold">Reference</label>
          <InputText v-model="paidForm.reference_number" class="w-full" />
        </div>
        <div class="flex justify-end gap-2">
          <Button label="Cancel" class="p-button-secondary" @click="showPaid = false" />
          <Button label="Mark Paid" type="submit" />
        </div>
      </form>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
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

const loadExpenses = async () => {
  loading.value = true
  try {
    const params: any = {}
    if (route.query.reference_type) params.reference_type = route.query.reference_type
    if (route.query.reference_id) params.reference_id = route.query.reference_id
    if (route.query.status) params.status = route.query.status

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
