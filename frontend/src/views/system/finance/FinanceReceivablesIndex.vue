<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-3xl font-bold text-gray-900">Accounts Receivable</h1>
      <p class="text-gray-500 mt-1">Customer invoices and collections (Sales integration)</p>
    </div>

    <Card>
      <template #content>
        <DataTable :value="receivables" :loading="loading" stripedRows responsiveLayout="scroll">
          <Column field="reference" header="Invoice" />
          <Column field="customer" header="Customer" />
          <Column field="amount" header="Amount">
            <template #body="{ data }">₱ {{ formatMoney(data.amount) }}</template>
          </Column>
          <Column field="due_date" header="Due Date" />
          <Column field="status" header="Status" />
          <template #empty>
            <div class="text-center py-8 text-gray-500">Sales module not connected yet.</div>
          </template>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import financeService from '../../../services/finance.service'

const loading = ref(false)
const receivables = ref<any[]>([])

const formatMoney = (value: number | string) => {
  const amount = typeof value === 'string' ? parseFloat(value) : value || 0
  return new Intl.NumberFormat('en-PH').format(amount)
}

const loadReceivables = async () => {
  loading.value = true
  try {
    const res = await financeService.getReceivables()
    receivables.value = res.data || []
  } finally {
    loading.value = false
  }
}

onMounted(loadReceivables)
</script>
