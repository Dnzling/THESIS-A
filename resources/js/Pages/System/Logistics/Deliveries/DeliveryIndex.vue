<template>
  <div class="mx-auto space-y-6 px-4 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <h1 class="text-lg font-semibold tracking-tight text-slate-900">Logistics Delivery Orders</h1>
      </div>
    
    </div>
  
    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #content>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
          <div class="md:col-span-4">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" placeholder="Search order, customer, contact" fluid />
            </IconField>
          </div>
          <div class="md:col-span-2">
            <Select v-model="filters.source" :options="sourceOptions" optionLabel="label" optionValue="value" fluid />
          </div>
          <div class="md:col-span-2">
            <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" fluid />
          </div>
          <div class="md:col-span-3">
            <DatePicker
              v-model="filters.date_range"
              selectionMode="range"
              dateFormat="M dd, yy"
              placeholder="Order date range"
              showIcon
              showButtonBar
              fluid
              @date-select="onDateRangeChange"
              @clear="onDateRangeChange"
            />
          </div>
          <div class="md:col-span-1">
            <Button icon="pi pi-filter-slash" outlined class="w-full" @click="resetFilters" />
          </div>
        </div>
      </template>
    </Card>
  
    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #content>
        <div v-if="loading" class="space-y-3">
          <Skeleton height="2.25rem" borderRadius="12px" />
          <Skeleton height="2.25rem" borderRadius="12px" />
          <Skeleton height="2.25rem" borderRadius="12px" />
          <Skeleton height="2.25rem" borderRadius="12px" />
          <Skeleton height="2.25rem" borderRadius="12px" />
        </div>
        <DataTable v-else :value="orders" dataKey="id" rowHover paginator :rows="pageState.rows"
          :first="(pageState.page - 1) * pageState.rows" :totalRecords="pageState.total"
          :rowsPerPageOptions="[10, 20, 50]" @page="onPage" class="text-sm">
          <template #empty>
            <div class="py-8 text-center text-slate-500">No delivery orders found.</div>
          </template>

           <Column header="Ordered" style="width: 9rem">
            <template #body="{ data }">
              <span class="text-sm text-slate-600">{{ formatDateTime(data.created_at) }}</span>
            </template>
          </Column>
  
          <Column header="Source" style="width: 7.5rem">
            <template #body="{ data }">
              <Tag :value="sourceLabel(data.source_type)" :severity="sourceSeverity(data.source_type)" />
            </template>
          </Column>
  
          <Column header="Order" style="min-width: 11rem">
            <template #body="{ data }">
              <p class="font-medium text-slate-900">{{ data.order_number }}</p>
              <p class="text-[11px] text-slate-500">{{ data.branch_name || 'No branch' }}</p>
            </template>
          </Column>
  
          <Column header="Customer" style="min-width: 11rem">
            <template #body="{ data }">
              <p class="font-medium text-slate-800">{{ data.source_type === 'pickup' ? 'Supplier: ' : '' }}{{ data.customer_name || '-' }}</p>
              <p class="text-[11px] text-slate-500">{{ data.customer_contact || '-' }}</p>
            </template>
          </Column>
  

          <Column header="Status" style="width: 11rem">
            <template #body="{ data }">
              <Tag v-if="data.delivery_status" :value="formatDeliveryStatus(data.delivery_status)"
                :severity="deliverySeverity(data.delivery_status)" />
              <Tag v-else value="Pending" severity="warn" />
            </template>
          </Column>
  
          <Column header="Total" style="width: 7rem">
            <template #body="{ data }">{{ formatCurrency(data.total_amount) }}</template>
          </Column>

          <Column header="Shipping Fee" style="width: 8rem">
            <template #body="{ data }">
              <span v-if="data.shipping_fee != null || data.transfer_cost != null">{{ formatCurrency(data.shipping_fee ?? data.transfer_cost) }}</span>
              <span v-else class="text-slate-400">-</span>
            </template>
          </Column>
  
          <Column header="Actions" style="width: 6rem">
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <Button v-if="data.source_type === 'pickup' && data.can_create_delivery && canManageDeliveries"
                  label="Assign Driver" icon="pi pi-user-plus" severity="warn" size="small"
                  @click="assignPickup(data)" />
                <Button v-if="data.source_type === 'replacement' && data.can_create_delivery && canManageDeliveries"
                  label="Assign Driver" icon="pi pi-user-plus" severity="warn" size="small"
                  @click="openDetail(data)" />
                <Button icon="pi pi-eye" text rounded  v-tooltip.bottom="'View details'"
                  @click="openDetail(data)" />
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
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Skeleton from 'primevue/skeleton'
import { useAuthStore } from '../../../../stores/auth'
import logisticsService from '../../../../services/logistics.service'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const loading = ref(false)
const orders = ref<any[]>([])
const canManageDeliveries = authStore.hasPermission('logistics.deliveries.manage')

const filters = reactive({
  search: '',
  source: 'all',
  status: '',
  date_range: null as Date[] | null,
})

const pageState = reactive({
  page: 1,
  rows: 10,
  total: 0,
})

const sourceOptions = [
  { label: 'All Sources', value: 'all' },
  { label: 'Ecommerce', value: 'ecommerce' },
  { label: 'Sales', value: 'sales' },
  { label: 'Supplier Pickups', value: 'pickup' },
  { label: 'Replacements', value: 'replacement' },
  { label: 'Customer Return Pickups', value: 'return_pickup' },
  { label: 'Internal Stock Transfers', value: 'stock_transfer' },
]

const statusOptions = [
  { label: 'All Delivery Statuses', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Ready For Dispatch', value: 'ready_for_dispatch' },
  { label: 'Assigned', value: 'assigned' },
  { label: 'Packed', value: 'packed' },
  { label: 'In Transit', value: 'in_transit' },
  { label: 'Out For Delivery', value: 'out_for_delivery' },
  { label: 'Delivered', value: 'delivered' },
  { label: 'Picked Up', value: 'picked_up' },
  { label: 'Failed Delivery', value: 'failed_delivery' },
  { label: 'Replacement Delivery Failed', value: 'delivery_failed' },
  { label: 'Cancelled', value: 'cancelled' },
]

const loadOrders = async () => {
  loading.value = true
  try {
    const response = await logisticsService.getDeliveryOrders({
      page: pageState.page,
      per_page: pageState.rows,
      source: filters.source,
      status: filters.status || undefined,
      search: filters.search || undefined,
      date_from: formatDateParam(filters.date_range?.[0]),
      date_to: formatDateParam(filters.date_range?.[1]),
    })

    const payload = response?.data || {}
    orders.value = payload.data || []
    pageState.total = Number(payload.total || 0)
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Load Failed',
      detail: error?.response?.data?.message || 'Failed to load delivery orders.',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const onPage = (event: any) => {
  pageState.page = Number(event.page || 0) + 1
  pageState.rows = Number(event.rows || 10)
  loadOrders()
}

const resetFilters = () => {
  filters.search = ''
  filters.source = 'all'
  filters.status = ''
  filters.date_range = null
  pageState.page = 1
  loadOrders()
}

const onDateRangeChange = () => {
  if (filters.date_range?.length === 2 || !filters.date_range?.length) {
    pageState.page = 1
    loadOrders()
  }
}

const openDetail = (order: any) => {
  if (order.source_type === 'replacement') {
    router.push({ name: 'logistics.replacements.detail', params: { id: order.order_id } })
    return
  }
  if (order.source_type === 'stock_transfer') {
    router.push({ name: 'logistics.stock-transfers.detail', params: { id: order.order_id } })
    return
  }
  if (order.source_type === 'pickup') {
    router.push({ name: 'procurement.purchase-orders.detail', params: { id: order.order_id } })
    return
  }
  if (order.source_type === 'return_pickup') {
    router.push({ name: 'logistics.return-pickups.detail', params: { id: order.order_id } })
    return
  }
  router.push({
    name: 'logistics.deliveries.detail',
    params: { source: order.source_type, orderId: order.order_id },
  })
}

const assignPickup = (order: any) => {
  router.push({ name: 'procurement.purchase-orders.pickup', params: { id: order.order_id }, query: { from: 'logistics' } })
}

const deliverySeverity = (status: string) => {
  if (status === 'pending' || status === 'ready_for_dispatch') return 'warning'
  if (status === 'delivered' || status === 'picked_up') return 'success'
  if (status === 'failed_delivery' || status === 'delivery_failed' || status === 'cancelled') return 'danger'
  if (status === 'out_for_delivery') return 'warning'
  return 'info'
}

const formatStatus = (status?: string) => {
  if (!status) return '-'
  return status.replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase())
}

const formatDeliveryStatus = (status?: string) => formatStatus(status)

const formatDateTime = (value?: string) => {
  if (!value) return '-'
  return new Date(value).toLocaleString('en-PH', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

const sourceLabel = (source: string) => ({
  ecommerce: 'Ecommerce',
  sales: 'Sales',
  pickup: 'Supplier Pickup',
  replacement: 'Replacement',
  return_pickup: 'Return Pickup',
  stock_transfer: 'Stock Transfer',
} as Record<string, string>)[source] || 'Delivery'

const sourceSeverity = (source: string) => source === 'pickup' || source === 'return_pickup' || source === 'replacement'
  ? 'warning'
  : source === 'stock_transfer'
    ? 'success'
    : source === 'ecommerce' ? 'info' : 'contrast'

const formatCurrency = (value: string | number) => new Intl.NumberFormat('en-PH', {
  style: 'currency',
  currency: 'PHP',
}).format(Number(value || 0))

const formatDateParam = (value: Date | null | undefined) => {
  if (!value) return undefined
  const year = value.getFullYear()
  const month = String(value.getMonth() + 1).padStart(2, '0')
  const day = String(value.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

let searchTimer: ReturnType<typeof setTimeout> | null = null
watch(
  () => [filters.source, filters.status],
  () => {
    pageState.page = 1
    loadOrders()
  }
)
watch(
  () => filters.search,
  () => {
    if (searchTimer) clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
      pageState.page = 1
      loadOrders()
    }, 300)
  }
)

onMounted(loadOrders)
</script>
