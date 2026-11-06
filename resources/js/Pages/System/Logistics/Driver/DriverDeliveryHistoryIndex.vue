<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Delivery History</h1>
      <p class="mt-1 text-sm text-slate-500">Completed deliveries assigned to you.</p>
    </div>

    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #content>
        <div class="hidden md:block">
          <DataTable :value="deliveries" paginator :rows="10" :loading="loading" stripedRows responsiveLayout="scroll">
            <template #empty><div class="py-8 text-center text-slate-500">No completed deliveries found.</div></template>
            <Column field="order_number" header="Reference" />
            <Column header="Date"><template #body="{ data }">{{ formatDate(data.delivery_date || data.created_at) }}</template></Column>
            <Column field="customer_name" header="Recipient" />
            <Column field="delivery_address" header="Address" />
            <Column header="Items"><template #body="{ data }">{{ data.quantity_items ?? 0 }}</template></Column>
            <Column header="Weight"><template #body="{ data }">{{ weight(data.weight_kg) }}</template></Column>
            <Column header="Expected Pickup"><template #body="{ data }">{{ formatDate(data.expected_pickup_date) }}</template></Column>
            <Column header="Status"><template #body><Tag value="Delivered" severity="success" /></template></Column>
            <Column header="Action"><template #body="{ data }"><Button icon="pi pi-eye" label="View" size="small" outlined @click="view(data)" /></template></Column>
          </DataTable>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:hidden">
          <div v-if="loading" class="col-span-full py-8 text-center text-sm text-slate-500">Loading delivery history...</div>
          <div v-else-if="!deliveries.length" class="col-span-full py-8 text-center text-sm text-slate-500">No completed deliveries found.</div>
          <article v-for="delivery in deliveries" v-else :key="`${delivery.source_type}-${delivery.order_id}`" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-start justify-between gap-3 border-b border-slate-100 bg-emerald-50/70 p-4">
              <div class="min-w-0"><p class="truncate font-semibold text-slate-900">{{ delivery.order_number }}</p><p class="mt-0.5 text-xs capitalize text-slate-500">{{ delivery.source_type }}</p></div>
              <Tag value="Delivered" severity="success" />
            </div>
            <div class="grid grid-cols-2 gap-x-4 gap-y-3 p-4 text-sm">
              <div><p class="text-xs text-slate-500">Delivered date</p><p class="mt-1 font-medium text-slate-800">{{ formatDate(delivery.delivery_date || delivery.created_at) }}</p></div>
              <div><p class="text-xs text-slate-500">Expected pickup</p><p class="mt-1 font-medium text-slate-800">{{ formatDate(delivery.expected_pickup_date) }}</p></div>
              <div><p class="text-xs text-slate-500">Items</p><p class="mt-1 font-medium text-slate-800">{{ delivery.quantity_items ?? 0 }}</p></div>
              <div><p class="text-xs text-slate-500">Weight</p><p class="mt-1 font-medium text-slate-800">{{ weight(delivery.weight_kg) }}</p></div>
              <div class="col-span-2"><p class="text-xs text-slate-500">Recipient</p><p class="mt-1 font-medium text-slate-800">{{ delivery.customer_name || '-' }}</p></div>
              <div class="col-span-2"><p class="text-xs text-slate-500">Address</p><p class="mt-1 text-slate-700"><i class="pi pi-map-marker mr-1 text-blue-500"></i>{{ delivery.delivery_address || '-' }}</p></div>
            </div>
            <div class="border-t border-slate-100 p-3"><Button icon="pi pi-eye" label="View Delivery" size="small" fluid outlined @click="view(delivery)" /></div>
          </article>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import logisticsService from '@/services/logistics.service'

const router = useRouter()
const loading = ref(false)
const deliveries = ref<any[]>([])
const formatDate = (value: any) => value ? new Date(value).toLocaleDateString() : '-'
const weight = (value: any) => `${Number(value || 0).toLocaleString(undefined, { maximumFractionDigits: 2 })} kg`
const view = (row: any) => router.push({ name: 'driver.deliveries.view', params: { source: row.source_type, orderId: row.order_id } })

onMounted(async () => {
  loading.value = true
  try {
    const response = await logisticsService.getDeliveryOrders({ source: 'all', status: 'delivered', per_page: 100, assigned_driver_only: true })
    deliveries.value = response?.data?.data || []
  } catch (error) {
    console.error('Failed to load delivery history', error)
    deliveries.value = []
  } finally {
    loading.value = false
  }
})
</script>
