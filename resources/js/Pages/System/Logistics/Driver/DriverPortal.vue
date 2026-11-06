<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Driver Portal</h1>
        <p class="mt-1 text-sm text-slate-500">Manage your assigned deliveries and keep the store updated.</p>
      </div>
      <Button icon="pi pi-refresh" label="Refresh" outlined :loading="loading" @click="loadDeliveries" />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
      <Card v-for="stat in stats" :key="stat.label" class="rounded-2xl border border-slate-200/80 shadow-sm">
        <template #content><div class="p-2"><p class="text-xs font-medium uppercase tracking-wider text-slate-500">{{ stat.label }}</p><p class="mt-2 text-3xl font-semibold" :class="stat.class">{{ stat.value }}</p></div></template>
      </Card>
    </div>

    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title><div class="flex items-center gap-2"><i class="pi pi-map-marker text-blue-600" /><span>My Assigned Deliveries</span></div></template>
      <template #subtitle>Location sharing can be enabled here when GPS tracking is connected.</template>
      <template #content>
        <DataTable :value="deliveries" dataKey="id" stripedRows paginator :rows="10" :loading="loading" responsiveLayout="scroll">
          <template #empty><div class="py-8 text-center text-slate-500">No deliveries are assigned to you.</div></template>
          <Column field="order_number" header="Reference" />
          <Column header="Customer / Supplier"><template #body="{ data }"><span class="font-medium">{{ data.customer_name || '-' }}</span><small class="block text-slate-500">{{ data.customer_contact || '' }}</small></template></Column>
          <Column field="delivery_address" header="Address" />
          <Column header="Status"><template #body="{ data }"><Tag :value="statusLabel(data.delivery_status)" :severity="statusSeverity(data.delivery_status)" /></template></Column>
          <Column header="Action" style="width: 8rem"><template #body="{ data }"><Button size="small" icon="pi pi-eye" label="View" outlined @click="openDetail(data)" /></template></Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import logisticsService from '@/services/logistics.service'

const toast = useToast(); const router = useRouter(); const loading = ref(false); const deliveries = ref<any[]>([])
const stats = computed(() => [{ label: 'Assigned', value: deliveries.value.length, class: 'text-blue-600' }, { label: 'In Progress', value: deliveries.value.filter(d => ['assigned', 'packed', 'in_transit', 'out_for_delivery'].includes(d.delivery_status)).length, class: 'text-orange-600' }, { label: 'Completed', value: deliveries.value.filter(d => d.delivery_status === 'delivered').length, class: 'text-green-600' }])
const loadDeliveries = async () => { loading.value = true; try { const response = await logisticsService.getDeliveryOrders({ source: 'all', per_page: 100, assigned_driver_only: true }); deliveries.value = response?.data?.data || [] } catch (error: any) { toast.add({ severity: 'error', summary: 'Load Failed', detail: error?.response?.data?.message || 'Failed to load assignments.', life: 3000 }) } finally { loading.value = false } }
const statusLabel = (status: string) => String(status || 'Pending').replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase())
const statusSeverity = (status: string) => status === 'delivered' ? 'success' : ['in_transit', 'out_for_delivery'].includes(status) ? 'info' : 'warn'
const openDetail = (delivery: any) => router.push({ name: 'driver.deliveries.view', params: { source: delivery.source_type, orderId: delivery.order_id } })
onMounted(loadDeliveries)
</script>
