<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Finance Dashboard</h1>
        <p class="text-gray-500 mt-1">Overview of payables, payroll, and expenses</p>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <Card>
        <template #content>
          <p class="text-xs text-gray-500">Payables</p>
          <p class="text-2xl font-bold text-blue-700">₱ {{ formatMoney(stats.payables) }}</p>
        </template>
      </Card>
      <Card>
        <template #content>
          <p class="text-xs text-gray-500">Invoices Due</p>
          <p class="text-2xl font-bold text-indigo-700">₱ {{ formatMoney(stats.invoices_due) }}</p>
        </template>
      </Card>
      <Card>
        <template #content>
          <p class="text-xs text-gray-500">Payments Completed</p>
          <p class="text-2xl font-bold text-green-700">₱ {{ formatMoney(stats.payments_completed) }}</p>
        </template>
      </Card>
      <Card>
        <template #content>
          <p class="text-xs text-gray-500">Expenses Pending</p>
          <p class="text-2xl font-bold text-amber-700">₱ {{ formatMoney(stats.expenses_pending) }}</p>
        </template>
      </Card>
      <Card>
        <template #content>
          <p class="text-xs text-gray-500">Payroll Pending</p>
          <p class="text-2xl font-bold text-rose-700">₱ {{ formatMoney(stats.payroll_pending) }}</p>
        </template>
      </Card>
    </div>

    <Card>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="p-4 border rounded-lg">
            <p class="text-xs text-gray-500">AP Actions</p>
            <p class="text-sm text-gray-700 mt-2">Review payables and process supplier payments.</p>
          </div>
          <div class="p-4 border rounded-lg">
            <p class="text-xs text-gray-500">Expenses</p>
            <p class="text-sm text-gray-700 mt-2">Approve and mark expenses as paid.</p>
          </div>
          <div class="p-4 border rounded-lg">
            <p class="text-xs text-gray-500">Payroll</p>
            <p class="text-sm text-gray-700 mt-2">Approve payroll runs and release payouts.</p>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import Card from 'primevue/card'
import financeService from '../../../services/finance.service'

const stats = ref({
  payables: 0,
  invoices_due: 0,
  payments_completed: 0,
  expenses_pending: 0,
  payroll_pending: 0,
})

const formatMoney = (value: number | string) => {
  const amount = typeof value === 'string' ? parseFloat(value) : value || 0
  return new Intl.NumberFormat('en-PH').format(amount)
}

const loadDashboard = async () => {
  const res = await financeService.getDashboard()
  stats.value = res.data || stats.value
}

onMounted(loadDashboard)
</script>
