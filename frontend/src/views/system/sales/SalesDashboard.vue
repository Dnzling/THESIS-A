<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <Card class="border border-gray-100 shadow-sm rounded-2xl">
      <template #content>
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900">Sales Dashboard</h1>
            <p class="text-sm text-gray-500">CRM + In-store POS overview.</p>
          </div>
          <Button severity="info" outlined icon="pi pi-refresh" label="Refresh" @click="load" />
        </div>
      </template>
    </Card>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card class="border border-gray-100 shadow-sm rounded-2xl"><template #content><p class="text-xs text-gray-500 uppercase">Today Orders</p><p class="text-3xl font-semibold">{{ stats.today_orders }}</p></template></Card>
      <Card class="border border-gray-100 shadow-sm rounded-2xl"><template #content><p class="text-xs text-gray-500 uppercase">Today Sales</p><p class="text-3xl font-semibold">{{ money(stats.today_sales) }}</p></template></Card>
      <Card class="border border-gray-100 shadow-sm rounded-2xl"><template #content><p class="text-xs text-gray-500 uppercase">Month Orders</p><p class="text-3xl font-semibold">{{ stats.month_orders }}</p></template></Card>
      <Card class="border border-gray-100 shadow-sm rounded-2xl"><template #content><p class="text-xs text-gray-500 uppercase">Month Sales</p><p class="text-3xl font-semibold">{{ money(stats.month_sales) }}</p></template></Card>
    </div>

    <Card class="border border-gray-100 shadow-sm rounded-2xl">
      <template #title>Recent POS Orders</template>
      <template #content>
        <DataTable :value="stats.recent_orders || []" stripedRows>
          <Column field="order_number" header="Order" />
          <Column field="customer_name" header="Customer" />
          <Column field="payment_method" header="Payment" />
          <Column field="total_amount" header="Total"><template #body="{data}">{{ money(data.total_amount) }}</template></Column>
          <Column field="created_at" header="Date"><template #body="{data}">{{ dt(data.created_at) }}</template></Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive } from 'vue'
import salesService from '@/services/sales.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

const stats = reactive<any>({
  today_orders: 0,
  today_sales: 0,
  month_orders: 0,
  month_sales: 0,
  recent_orders: [],
})

const load = async () => {
  const res = await salesService.getDashboard()
  Object.assign(stats, res?.data || {})
}

const money = (v: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(v || 0))
const dt = (v: string) => (v ? new Date(v).toLocaleString('en-PH') : '-')

onMounted(load)
</script>

