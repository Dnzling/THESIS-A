<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Budgets</h1>
        <p class="text-gray-500 mt-1">Set and track budget allocations</p>
      </div>
      <Button label="New Budget" icon="pi pi-plus" @click="showCreate = true" />
    </div>

    <Card>
      <template #content>
        <DataTable :value="budgets" :loading="loading" stripedRows responsiveLayout="scroll">
          <Column field="department" header="Department" />
          <Column field="category" header="Category" />
          <Column field="period_start" header="Period">
            <template #body="{ data }">{{ formatDate(data.period_start) }} - {{ formatDate(data.period_end) }}</template>
          </Column>
          <Column field="amount" header="Amount">
            <template #body="{ data }">₱ {{ formatMoney(data.amount) }}</template>
          </Column>
          <Column field="spent_amount" header="Spent">
            <template #body="{ data }">₱ {{ formatMoney(data.spent_amount) }}</template>
          </Column>
          <Column field="status" header="Status">
            <template #body="{ data }">
              <Tag :value="data.status" :severity="data.status === 'active' ? 'success' : 'secondary'" />
            </template>
          </Column>
          <template #empty>
            <div class="text-center py-8 text-gray-500">No budgets found</div>
          </template>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="showCreate" header="Create Budget" modal :style="{ width: '520px' }">
      <form class="space-y-4" @submit.prevent="createBudget">
        <div>
          <label class="text-sm font-semibold">Department</label>
          <InputText v-model="form.department" class="w-full" />
        </div>
        <div>
          <label class="text-sm font-semibold">Category</label>
          <InputText v-model="form.category" class="w-full" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-sm font-semibold">Start</label>
            <DatePicker v-model="form.period_start" class="w-full" />
          </div>
          <div>
            <label class="text-sm font-semibold">End</label>
            <DatePicker v-model="form.period_end" class="w-full" />
          </div>
        </div>
        <div>
          <label class="text-sm font-semibold">Amount</label>
          <InputNumber v-model="form.amount" class="w-full" :min="0" mode="currency" currency="PHP" />
        </div>
        <div class="flex justify-end gap-2">
          <Button label="Cancel" class="p-button-secondary" @click="showCreate = false" />
          <Button label="Create" type="submit" />
        </div>
      </form>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import DatePicker from 'primevue/datepicker'
import financeService from '../../../services/finance.service'

const loading = ref(false)
const budgets = ref<any[]>([])
const showCreate = ref(false)

const form = ref({
  department: '',
  category: '',
  period_start: new Date(),
  period_end: new Date(),
  amount: 0,
})

const formatMoney = (value: number | string) => {
  const amount = typeof value === 'string' ? parseFloat(value) : value || 0
  return new Intl.NumberFormat('en-PH').format(amount)
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

const loadBudgets = async () => {
  loading.value = true
  try {
    const res = await financeService.getBudgets()
    budgets.value = res.data?.data || res.data || []
  } finally {
    loading.value = false
  }
}

const createBudget = async () => {
  await financeService.createBudget(form.value)
  showCreate.value = false
  form.value = { department: '', category: '', period_start: new Date(), period_end: new Date(), amount: 0 }
  loadBudgets()
}

onMounted(loadBudgets)
</script>
