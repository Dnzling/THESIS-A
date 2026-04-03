<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Finance</h1>
        <p class="text-sm text-slate-600">Track expenses and simple cashflow.</p>
      </div>
      <Button label="Add Expense" icon="pi pi-plus" @click="showExpenseDialog = true" />
    </div>

    <Card>
      <template #content>
        <div class="grid gap-4 md:grid-cols-3 text-sm text-slate-700">
          <div class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="text-xs uppercase text-slate-400">Total Expenses</div>
            <div class="text-xl font-semibold mt-2">₱{{ totalExpenses.toLocaleString() }}</div>
          </div>
          <div class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="text-xs uppercase text-slate-400">Pending</div>
            <div class="text-xl font-semibold mt-2">{{ pendingCount }}</div>
          </div>
          <div class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="text-xs uppercase text-slate-400">Paid</div>
            <div class="text-xl font-semibold mt-2">{{ paidCount }}</div>
          </div>
        </div>
      </template>
    </Card>

    <Card>
      <template #content>
        <DataTable :value="expenses" :loading="loading" class="p-datatable-sm">
          <Column field="expense_date" header="Date" />
          <Column field="category" header="Category" />
          <Column field="amount" header="Amount">
            <template #body="{ data }">
              ₱{{ Number(data.amount || 0).toLocaleString() }}
            </template>
          </Column>
          <Column field="status" header="Status" />
          <Column field="description" header="Description" />
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="showExpenseDialog" header="Add Expense" :style="{ width: '520px' }" modal>
      <div class="grid gap-3">
        <InputText v-model="expenseForm.category" placeholder="Category" />
        <InputNumber v-model="expenseForm.amount" mode="currency" currency="PHP" locale="en-PH" placeholder="Amount" />
        <Calendar v-model="expenseForm.expense_date" dateFormat="yy-mm-dd" showIcon placeholder="Expense date" />
        <Textarea v-model="expenseForm.description" rows="3" placeholder="Description (optional)" />
      </div>
      <template #footer>
        <Button label="Cancel" text @click="showExpenseDialog = false" />
        <Button label="Save" icon="pi pi-check" :loading="saving" @click="submitExpense" />
      </template>
    </Dialog>

    <Dialog v-model:visible="responseDialog.visible" :header="responseDialog.title" :style="{ width: '420px' }" modal>
      <div class="text-sm text-slate-700">{{ responseDialog.message }}</div>
      <template #footer>
        <Button label="OK" @click="responseDialog.visible = false" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import axiosClient from '@/axios'
import Button from 'primevue/button'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Calendar from 'primevue/calendar'
import Textarea from 'primevue/textarea'

const expenses = ref<any[]>([])
const loading = ref(false)
const saving = ref(false)
const showExpenseDialog = ref(false)

const expenseForm = ref({
  category: '',
  amount: 0,
  expense_date: '',
  description: '',
})

const responseDialog = ref({
  visible: false,
  title: 'Success',
  message: '',
})

const showResponse = (title: string, message: string) => {
  responseDialog.value = { visible: true, title, message }
}

const totalExpenses = computed(() => {
  return expenses.value.reduce((sum, item) => sum + Number(item.amount || 0), 0)
})

const pendingCount = computed(() => expenses.value.filter(item => item.status === 'pending_approval').length)
const paidCount = computed(() => expenses.value.filter(item => item.status === 'paid').length)

const loadExpenses = async () => {
  loading.value = true
  try {
    const response = await axiosClient.get('/api/finance/expenses')
    expenses.value = response?.data?.data?.data || response?.data?.data || []
  } catch (error: any) {
    showResponse('Error', error?.response?.data?.message || 'Failed to load expenses.')
  } finally {
    loading.value = false
  }
}

const submitExpense = async () => {
  saving.value = true
  try {
    await axiosClient.post('/api/finance/expenses', {
      ...expenseForm.value,
      expense_date: expenseForm.value.expense_date,
    })
    showExpenseDialog.value = false
    showResponse('Success', 'Expense created successfully.')
    await loadExpenses()
  } catch (error: any) {
    showResponse('Failed', error?.response?.data?.message || 'Unable to save expense.')
  } finally {
    saving.value = false
  }
}

onMounted(loadExpenses)
</script>
