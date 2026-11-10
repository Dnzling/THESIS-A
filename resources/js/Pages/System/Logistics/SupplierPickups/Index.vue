<template>
  <div class="mx-auto space-y-5 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-900">Supplier Pickups</h1>
        <p class="mt-1 text-sm text-slate-500">Review supplier collections and follow each pickup through delivery to the store.</p>
      </div>
      <Button icon="pi pi-refresh" label="Refresh" size="small" severity="secondary" outlined :loading="loading" @click="load" />
    </div>

    <Card class="rounded-2xl border border-slate-200 shadow-sm">
      <template #content>
        <div class="mb-4 flex flex-wrap items-center gap-3">
          <IconField class="w-full sm:w-80">
            <InputIcon class="pi pi-search" />
            <InputText v-model="search" placeholder="Search reference or supplier" fluid />
          </IconField>
          <Select v-model="status" :options="statusOptions" option-label="label" option-value="value" class="w-full sm:w-52" />
          <Button v-if="search || status" label="Clear filters" icon="pi pi-filter-slash" size="small" severity="secondary" text @click="clearFilters" />
        </div>

        <div v-if="loading" class="space-y-3">
          <Skeleton v-for="row in 5" :key="row" height="2.75rem" border-radius="0.5rem" />
        </div>
        <DataTable
          v-else
          :value="pickups"
          data-key="id"
          paginator
          lazy
          row-hover
          :rows="pageState.rows"
          :first="(pageState.page - 1) * pageState.rows"
          :total-records="pageState.total"
          :rows-per-page-options="[10, 20, 50]"
          class="text-sm"
          @page="onPage"
        >
          <template #empty>
            <div class="py-8 text-center text-slate-500">No supplier pickups found.</div>
          </template>
          <Column field="created_at" header="Created" sortable>
            <template #body="{ data }">{{ formatDateTime(data.created_at) }}</template>
          </Column>
          <Column field="order_number" header="Reference">
            <template #body="{ data }"><span class="font-medium text-slate-900">{{ data.order_number || '-' }}</span></template>
          </Column>
          <Column field="customer_name" header="Supplier">
            <template #body="{ data }">{{ data.customer_name || '-' }}</template>
          </Column>
          <Column field="delivery_status" header="Status">
            <template #body="{ data }"><Badge :value="statusLabel(data.delivery_status)" :severity="statusSeverity(data.delivery_status)" /></template>
          </Column>
          <Column field="total_amount" header="Total" class="text-right">
            <template #body="{ data }"><span class="font-medium">{{ money(data.total_amount) }}</span></template>
          </Column>
          <Column field="shipping_fee" header="Fee" class="text-right">
            <template #body="{ data }">{{ data.shipping_fee == null ? '-' : money(data.shipping_fee) }}</template>
          </Column>
          <Column header="Actions" style="width: 13rem">
            <template #body="{ data }">
              <div class="flex flex-wrap items-center gap-2">
                <Button icon="pi pi-eye" label="View" size="small" outlined rounded @click="view(data)" />
                <Button
                  v-if="canManage && data.can_create_delivery"
                  icon="pi pi-user-plus"
                  label="Assign"
                  size="small"
                  severity="warn"
                  outlined
                  rounded
                  @click="assign(data)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import logisticsService from '@/services/logistics.service'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()
const canManage = auth.hasPermission('logistics.supplier-pickups.manage')
const loading = ref(false)
const pickups = ref<any[]>([])
const search = ref('')
const status = ref('')
const pageState = reactive({ page: 1, rows: 10, total: 0 })
const statusOptions = [
  { label: 'All statuses', value: '' },
  { label: 'Ready for Dispatch', value: 'ready_for_dispatch' },
  { label: 'Pending', value: 'pending' },
  { label: 'En Route to Supplier', value: 'en_route_to_supplier' },
  { label: 'Out for Delivery', value: 'out_for_delivery' },
  { label: 'Delivered', value: 'delivered' },
  { label: 'Failed Delivery', value: 'failed_delivery' },
  { label: 'Cancelled', value: 'cancelled' },
]

const load = async () => {
  loading.value = true
  try {
    const response = await logisticsService.getDeliveryOrders({
      source: 'pickup',
      search: search.value.trim() || undefined,
      status: status.value || undefined,
      page: pageState.page,
      per_page: pageState.rows,
    })
    const result = response?.data
    pickups.value = Array.isArray(result?.data) ? result.data : []
    pageState.total = Number(result?.total || 0)
  } catch (error) {
    console.error('Failed to load supplier pickups', error)
    pickups.value = []
    pageState.total = 0
  } finally {
    loading.value = false
  }
}

const onPage = (event: any) => {
  pageState.page = Math.floor(event.first / event.rows) + 1
  pageState.rows = event.rows
  load()
}
const clearFilters = () => {
  search.value = ''
  status.value = ''
  pageState.page = 1
  load()
}
const view = (pickup: any) => router.push({ name: 'logistics.supplier-pickups.detail', params: { orderId: pickup.order_id } })
const assign = (pickup: any) => router.push({ name: 'logistics.supplier-pickups.assign', params: { id: pickup.order_id } })
const statusLabel = (value: string) => value === 'ready_for_dispatch' ? 'Ready for Dispatch' : String(value || 'Pending').replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
const statusSeverity = (value: string) => {
  if (['delivered'].includes(String(value).toLowerCase())) return 'success'
  if (['cancelled', 'canceled'].includes(String(value).toLowerCase())) return 'secondary'
  if (['failed_delivery', 'failed'].includes(String(value).toLowerCase())) return 'danger'
  if (['en_route_to_supplier', 'out_for_delivery'].includes(String(value).toLowerCase())) return 'info'
  return 'warn'
}
const money = (value: string | number) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
const formatDateTime = (value?: string) => value ? new Intl.DateTimeFormat('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }).format(new Date(value)) : '-'

let searchTimer: ReturnType<typeof setTimeout> | null = null
watch([search, status], () => {
  if (searchTimer) clearTimeout(searchTimer)
  pageState.page = 1
  searchTimer = setTimeout(load, 300)
})
onMounted(load)
</script>
