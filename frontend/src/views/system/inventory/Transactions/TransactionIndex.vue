<template>
  <div class="p-6 bg-gray-50 min-h-screen space-y-6">
    <div class="mb-2">
      <h1 class="text-3xl font-bold text-gray-800">Inventory Transactions</h1>
      <p class="text-gray-600 mt-1">Review movement history and totals</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <Card>
        <template #content>
          <p class="text-sm text-gray-600 mb-1">Total In</p>
          <h3 class="text-2xl font-bold text-green-600">{{ summary.total_in }}</h3>
        </template>
      </Card>
      <Card>
        <template #content>
          <p class="text-sm text-gray-600 mb-1">Total Out</p>
          <h3 class="text-2xl font-bold text-red-600">{{ summary.total_out }}</h3>
        </template>
      </Card>
      <Card>
        <template #content>
          <p class="text-sm text-gray-600 mb-1">Net Movement</p>
          <h3 class="text-2xl font-bold text-blue-600">{{ summary.net_movement }}</h3>
        </template>
      </Card>
    </div>

    <Card>
      <template #content>
        <DataTable :value="transactions" :loading="loading" class="p-datatable-sm" stripedRows>
          <template #empty>
            <div class="text-center py-8">
              <i class="pi pi-inbox text-4xl text-gray-400"></i>
              <p class="text-gray-600 mt-2">No transactions found</p>
            </div>
          </template>
          <Column field="reference" header="Reference" />
          <Column field="type" header="Type" />
          <Column field="name" header="Branch" />
          <Column field="item_name" header="Item" />
          <Column field="quantity" header="Qty" />
          <Column field="created_at" header="Date" />
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import inventoryService from '../../../../services/inventory.service'

const loading = ref(false)
const transactions = ref<any[]>([])
const summary = ref({
  total_in: 0,
  total_out: 0,
  net_movement: 0
})

const loadTransactions = async () => {
  loading.value = true
  try {
    const [transactionsRes, summaryRes] = await Promise.all([
      inventoryService.getTransactions(),
      inventoryService.getTransactionSummary()
    ])
    transactions.value = transactionsRes.data?.data || []
    summary.value = summaryRes.data || summary.value
  } catch (error) {
    console.error('Failed to load transactions', error)
    transactions.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadTransactions()
})
</script>
