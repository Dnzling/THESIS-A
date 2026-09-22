<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <h1 class="text-lg font-semibold tracking-tight text-slate-900">My Deliveries</h1>
      </div><Button icon="pi pi-refresh" size="small" label="Refresh" :loading="loading" @click="load" />
    </div>
    <Card class="rounded-3xl border border-slate-200/80 shadow-sm"><template #content>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
          <InputText v-model="search" placeholder="Search reference, customer, address" fluid /><Select v-model="source"
            :options="sources" optionLabel="label" optionValue="value" fluid /><Select v-model="status"
            :options="statuses" optionLabel="label" optionValue="value" fluid /><Select v-model="truck"
            :options="truckOptions" optionLabel="label" optionValue="value" placeholder="All trucks" filter fluid /><Button label="Clear"
            icon="pi pi-filter-slash" outlined @click="clear" />
        </div>
      </template></Card>
    <Card class="rounded-3xl border border-slate-200/80 shadow-sm"><template #content>
        <div class="hidden md:block">
        <DataTable :value="deliveries" paginator :rows="10" :loading="loading" stripedRows responsiveLayout="scroll">
          <template #empty>
            <div class="py-8 text-center text-slate-500">No active deliveries assigned to you.</div>
          </template>
          <Column header="Reference Number"><template #body="{ data }"><span class="font-medium">{{ data.order_number
                }}</span></template></Column>
          <Column header="Source"><template #body="{ data }">{{ sourceLabel(data.source_type) }}</template></Column>
          <Column header="Date"><template #body="{ data }">{{ formatDate(data.created_at)
              }}</template></Column>
          <Column header="Expected Day Delivery"><template #body="{ data }">{{ formatDate(data.expected_pickup_date)
              }}</template></Column>
          <Column header="Recipient"><template #body="{ data }">
              <span class="font-medium text-slate-800">{{ data.customer_name || '-' }}</span>
              <small class="block text-slate-500">{{ data.customer_contact || '-' }}</small>
            </template></Column>
          <Column header="Weight"><template #body="{ data }">{{ weight(data.weight_kg) }}</template></Column>
          <Column header="Truck"><template #body="{ data }">
              <span class="font-medium text-slate-800">{{ truckName(data) }}</span>
              <small v-if="data.vehicle?.plate_number" class="block text-slate-500">{{ data.vehicle.plate_number }}</small>
            </template></Column>
          <Column header="Status"><template #body="{ data }">
              <Tag :value="label(data.delivery_status)" :severity="severity(data.delivery_status)" />
            </template></Column>
          <Column header="Action"><template #body="{ data }"><Button icon="pi pi-eye" label="View" size="small" outlined
                @click="view(data)" /></template></Column>
        </DataTable>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:hidden">
          <div v-if="loading" class="col-span-full py-8 text-center text-sm text-slate-500">Loading deliveries...</div>
          <div v-else-if="!deliveries.length" class="col-span-full py-8 text-center text-sm text-slate-500">No active deliveries assigned to you.</div>
          <article v-for="delivery in deliveries" v-else :key="`${delivery.source_type}-${delivery.order_id}`" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-start justify-between gap-3 border-b border-slate-100 bg-slate-50/80 p-4">
              <div class="min-w-0"><p class="truncate font-semibold text-slate-900">{{ delivery.order_number }}</p><p class="mt-0.5 text-xs text-slate-500">{{ sourceLabel(delivery.source_type) }}</p></div>
              <Tag :value="label(delivery.delivery_status)" :severity="severity(delivery.delivery_status)" />
            </div>
            <div class="grid grid-cols-2 gap-x-4 gap-y-3 p-4 text-sm">
              <div><p class="text-xs text-slate-500">Date</p><p class="mt-1 font-medium text-slate-800">{{ formatDate(delivery.created_at) }}</p></div>
              <div><p class="text-xs text-slate-500">Expected day delivery</p><p class="mt-1 font-medium text-slate-800">{{ formatDate(delivery.expected_pickup_date) }}</p></div>
              <div><p class="text-xs text-slate-500">Weight</p><p class="mt-1 font-medium text-slate-800">{{ weight(delivery.weight_kg) }}</p></div>
              <div><p class="text-xs text-slate-500">Status</p><Tag class="mt-1" :value="label(delivery.delivery_status)" :severity="severity(delivery.delivery_status)" /></div>
              <div class="col-span-2"><p class="text-xs text-slate-500">Assigned truck</p><p class="mt-1 font-medium text-slate-800">{{ truckName(delivery) }}</p><p v-if="delivery.vehicle?.plate_number" class="text-xs text-slate-500">{{ delivery.vehicle.plate_number }}</p></div>
              <div class="col-span-2"><p class="text-xs text-slate-500">Recipient</p><p class="mt-1 font-medium text-slate-800">{{ delivery.customer_name || '-' }}</p><p class="text-xs text-slate-500">{{ delivery.customer_contact || '-' }}</p></div>
            </div>
            <div class="border-t border-slate-100 p-3"><Button icon="pi pi-eye" label="View Delivery" size="small" fluid @click="view(delivery)" /></div>
          </article>
        </div>
      </template></Card>
  </div>
</template>
<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'; import Button from 'primevue/button'; import DataTable from 'primevue/datatable'; import Column from 'primevue/column'; import Tag from 'primevue/tag'; import InputText from 'primevue/inputtext'; import Select from 'primevue/select'; import logisticsService from '@/services/logistics.service'
const router = useRouter(); const loading = ref(false); const allDeliveries = ref<any[]>([]); const search = ref(''); const source = ref('all'); const status = ref(''); const truck = ref('all'); const sources = [{ label: 'All sources', value: 'all' }, { label: 'Sales', value: 'sales' }, { label: 'Ecommerce', value: 'ecommerce' }, { label: 'Supplier pickups', value: 'pickup' }, { label: 'Return pickups', value: 'return_pickup' }, { label: 'Stock transfers', value: 'stock_transfer' }]; const statuses = [{ label: 'All statuses', value: '' }, { label: 'Pending', value: 'pending' }, { label: 'Ready for Dispatch', value: 'ready_for_dispatch' }, { label: 'Assigned', value: 'assigned' }, { label: 'Packed', value: 'packed' }, { label: 'In Transit', value: 'in_transit' }, { label: 'Out for Delivery', value: 'out_for_delivery' }, { label: 'Failed Delivery', value: 'failed_delivery' }, { label: 'Picked Up', value: 'picked_up' }, { label: 'Cancelled', value: 'cancelled' }]
const truckKey = (row: any) => String(row.vehicle?.id ?? row.vehicle_id ?? row.vehicle?.vehicle_name ?? 'unassigned'); const truckName = (row: any) => row.vehicle?.vehicle_name || 'Unassigned'; const truckOptions = computed(() => [{ label: 'All trucks', value: 'all' }, ...Array.from(new Map(allDeliveries.value.map(row => [truckKey(row), { label: row.vehicle ? `${truckName(row)}${row.vehicle.plate_number ? ` (${row.vehicle.plate_number})` : ''}` : 'Unassigned', value: truckKey(row) }])).values())]); const deliveries = computed(() => { const needle = search.value.trim().toLowerCase(); return allDeliveries.value.filter((row: any) => (source.value === 'all' || row.source_type === source.value) && (!status.value || String(row.delivery_status).toLowerCase() === status.value) && (truck.value === 'all' || truckKey(row) === truck.value) && (!needle || [row.order_number, row.customer_name, row.customer_contact, row.delivery_address, truckName(row), row.vehicle?.plate_number].some(value => String(value || '').toLowerCase().includes(needle)))) })
const load = async () => { loading.value = true; try { const response = await logisticsService.getDeliveryOrders({ source: 'all', per_page: 100, assigned_driver_only: true }); allDeliveries.value = (response?.data?.data || []).filter((row: any) => String(row.delivery_status || '').toLowerCase() !== 'delivered') } catch (error) { console.error('Failed to load assigned deliveries', error); allDeliveries.value = [] } finally { loading.value = false } }
const clear = () => { search.value = ''; source.value = 'all'; status.value = ''; truck.value = 'all' }; const view = (row: any) => router.push({ name: 'driver.deliveries.view', params: { source: row.source_type, orderId: row.order_id } }); const label = (v: string) => String(v || 'Pending').replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase()); const sourceLabel = (v: string) => ({ ecommerce: 'Ecommerce', sales: 'Sales', pickup: 'Supplier Pickup', return_pickup: 'Return Pickup', stock_transfer: 'Stock Transfer' } as Record<string, string>)[v] || label(v); const severity = (v: string) => v === 'delivered' || v === 'picked_up' ? 'success' : ['in_transit', 'out_for_delivery'].includes(v) ? 'info' : 'warn'; const formatDate = (v: any) => v ? new Intl.DateTimeFormat('en-US', { month: 'long', day: 'numeric', year: 'numeric' }).format(new Date(v)) : '-'; const weight = (v: any) => `${Number(v || 0).toLocaleString(undefined, { maximumFractionDigits: 2 })} kg`; onMounted(load)
</script>
