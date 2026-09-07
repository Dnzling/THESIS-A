<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <h1 class="text-lg font-semibold tracking-tight text-slate-900">My Deliveries</h1>
      </div><Button icon="pi pi-refresh" size="small" label="Refresh" :loading="loading" @click="load" />
    </div>
    <Card class="rounded-3xl border border-slate-200/80 shadow-sm"><template #content>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
          <InputText v-model="search" placeholder="Search reference, customer, address" fluid /><Select v-model="source"
            :options="sources" optionLabel="label" optionValue="value" fluid /><Button label="Clear"
            icon="pi pi-filter-slash" outlined @click="clear" />
        </div>
      </template></Card>
    <Card class="rounded-3xl border border-slate-200/80 shadow-sm"><template #content>
        <div class="hidden md:block">
        <DataTable :value="deliveries" paginator :rows="10" :loading="loading" stripedRows responsiveLayout="scroll">
          <template #empty>
            <div class="py-8 text-center text-slate-500">No active deliveries assigned to you.</div>
          </template>
          <Column header="Reference"><template #body="{ data }"><span class="font-medium">{{ data.order_number
                }}</span><small class="block text-slate-500">{{ data.source_type }}</small></template></Column>
          <Column header="Date"><template #body="{ data }">{{ formatDate(data.delivery_date || data.created_at)
              }}</template></Column>
          <Column header="Recipient"><template #body="{ data }"><span>{{ data.customer_name || '-' }}</span><small
                class="block text-slate-500">{{ data.customer_contact || '' }}</small></template></Column>
          <Column field="delivery_address" header="Address" />
          <Column header="Items"><template #body="{ data }">{{ data.quantity_items ?? 0 }}</template></Column>
          <Column header="Weight"><template #body="{ data }">{{ weight(data.weight_kg) }}</template></Column>
          <Column header="Expected Pickup"><template #body="{ data }">{{ formatDate(data.expected_pickup_date)
              }}</template></Column>
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
              <div class="min-w-0"><p class="truncate font-semibold text-slate-900">{{ delivery.order_number }}</p><p class="mt-0.5 text-xs capitalize text-slate-500">{{ delivery.source_type }}</p></div>
              <Tag :value="label(delivery.delivery_status)" :severity="severity(delivery.delivery_status)" />
            </div>
            <div class="grid grid-cols-2 gap-x-4 gap-y-3 p-4 text-sm">
              <div><p class="text-xs text-slate-500">Date</p><p class="mt-1 font-medium text-slate-800">{{ formatDate(delivery.delivery_date || delivery.created_at) }}</p></div>
              <div><p class="text-xs text-slate-500">Expected pickup</p><p class="mt-1 font-medium text-slate-800">{{ formatDate(delivery.expected_pickup_date) }}</p></div>
              <div><p class="text-xs text-slate-500">Items</p><p class="mt-1 font-medium text-slate-800">{{ delivery.quantity_items ?? 0 }}</p></div>
              <div><p class="text-xs text-slate-500">Weight</p><p class="mt-1 font-medium text-slate-800">{{ weight(delivery.weight_kg) }}</p></div>
              <div class="col-span-2"><p class="text-xs text-slate-500">Recipient</p><p class="mt-1 font-medium text-slate-800">{{ delivery.customer_name || '-' }}</p><p v-if="delivery.customer_contact" class="text-xs text-slate-500">{{ delivery.customer_contact }}</p></div>
              <div class="col-span-2"><p class="text-xs text-slate-500">Address</p><p class="mt-1 text-slate-700"><i class="pi pi-map-marker mr-1 text-blue-500"></i>{{ delivery.delivery_address || '-' }}</p></div>
            </div>
            <div class="border-t border-slate-100 p-3"><Button icon="pi pi-eye" label="View Delivery" size="small" fluid @click="view(delivery)" /></div>
          </article>
        </div>
      </template></Card>
  </div>
</template>
<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'; import Button from 'primevue/button'; import DataTable from 'primevue/datatable'; import Column from 'primevue/column'; import Tag from 'primevue/tag'; import InputText from 'primevue/inputtext'; import Select from 'primevue/select'; import logisticsService from '@/services/logistics.service'
const router = useRouter(); const loading = ref(false); const deliveries = ref<any[]>([]); const search = ref(''); const source = ref('all'); const sources = [{ label: 'All sources', value: 'all' }, { label: 'Sales', value: 'sales' }, { label: 'Ecommerce', value: 'ecommerce' }, { label: 'Supplier pickups', value: 'pickup' }]
const load = async () => { loading.value = true; try { const response = await logisticsService.getDeliveryOrders({ source: source.value, per_page: 100, assigned_driver_only: true, search: search.value || undefined }); deliveries.value = (response?.data?.data || []).filter((row: any) => !['delivered', 'cancelled'].includes(String(row.delivery_status || '').toLowerCase())) } catch (error) { console.error('Failed to load assigned deliveries', error); deliveries.value = [] } finally { loading.value = false } }
const clear = () => { search.value = ''; source.value = 'all'; load() }; const view = (row: any) => router.push({ name: 'driver.deliveries.view', params: { source: row.source_type, orderId: row.order_id } }); const label = (v: string) => String(v || 'Pending').replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase()); const severity = (v: string) => v === 'delivered' ? 'success' : ['in_transit', 'out_for_delivery'].includes(v) ? 'info' : 'warn'; const formatDate = (v: any) => v ? new Date(v).toLocaleDateString() : '-'; const weight = (v: any) => `${Number(v || 0).toLocaleString(undefined, { maximumFractionDigits: 2 })} kg`; watch([search, source], load); onMounted(load)
</script>
