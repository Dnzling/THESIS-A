<template>
  <div class="mx-auto space-y-6 px-4 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Logistics Delivery Orders</h1>
      </div>
      <Button icon="pi pi-refresh" label="Refresh" outlined @click="loadOrders" />
    </div>
  
    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #content>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
          <div class="md:col-span-5">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" placeholder="Search order, customer, contact" fluid />
            </IconField>
          </div>
          <div class="md:col-span-3">
            <Select v-model="filters.source" :options="sourceOptions" optionLabel="label" optionValue="value" fluid />
          </div>
          <div class="md:col-span-3">
            <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" fluid />
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
        <DataTable v-else :value="orders" dataKey="id" stripedRows paginator :rows="pageState.rows"
          :first="(pageState.page - 1) * pageState.rows" :totalRecords="pageState.total"
          :rowsPerPageOptions="[10, 20, 50]" @page="onPage" class="text-xs">
          <template #empty>
            <div class="py-8 text-center text-slate-500">No delivery orders found.</div>
          </template>
  
          <Column header="Source" style="width: 7.5rem">
            <template #body="{ data }">
              <Tag :value="data.source_type === 'ecommerce' ? 'Ecommerce' : 'Sales'"
                :severity="data.source_type === 'ecommerce' ? 'info' : 'contrast'" />
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
              <p class="font-medium text-slate-800">{{ data.customer_name || '-' }}</p>
              <p class="text-[11px] text-slate-500">{{ data.customer_contact || '-' }}</p>
            </template>
          </Column>
  
          <Column header="Ordered" style="width: 9rem">
            <template #body="{ data }">
              <span class="text-[11px] text-slate-600">{{ formatDateTime(data.created_at) }}</span>
            </template>
          </Column>

          <Column field="delivery_address" header="Address" style="min-width: 14rem" />
  
          <Column header="Order Status" style="width: 8rem">
            <template #body="{ data }">
              <Tag :value="formatStatus(data.order_status)" severity="secondary" />
            </template>
          </Column>
  
          <Column header="Delivery" style="width: 10rem">
            <template #body="{ data }">
              <Tag v-if="data.delivery_status" :value="formatStatus(data.delivery_status)"
                :severity="deliverySeverity(data.delivery_status)" />
              <Tag v-else value="Ready For Dispatch" severity="warn" />
            </template>
          </Column>
  
          <Column header="Total" style="width: 7rem">
            <template #body="{ data }">₱ {{ toMoney(data.total_amount) }}</template>
          </Column>
  
          <Column header="Actions" style="width: 6rem">
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <Button icon="pi pi-eye" text rounded severity="info" v-tooltip.bottom="'View details'"
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
  status: 'ready_for_dispatch',
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
]

const statusOptions = [
  { label: 'All Delivery Statuses', value: '' },
  { label: 'Ready For Dispatch', value: 'ready_for_dispatch' },
  { label: 'Assigned', value: 'assigned' },
  { label: 'Packed', value: 'packed' },
  { label: 'In Transit', value: 'in_transit' },
  { label: 'Out For Delivery', value: 'out_for_delivery' },
  { label: 'Delivered', value: 'delivered' },
  { label: 'Failed Delivery', value: 'failed_delivery' },
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
  filters.status = 'ready_for_dispatch'
  pageState.page = 1
  loadOrders()
}

const openDetail = (order: any) => {
  router.push({
    name: 'logistics.deliveries.detail',
    params: { source: order.source_type, orderId: order.order_id },
  })
}

const deliverySeverity = (status: string) => {
  if (status === 'ready_for_dispatch') return 'warning'
  if (status === 'delivered') return 'success'
  if (status === 'failed_delivery' || status === 'cancelled') return 'danger'
  if (status === 'out_for_delivery') return 'warning'
  return 'info'
}

const formatStatus = (status?: string) => {
  if (!status) return '-'
  return status.replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase())
}

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

const toMoney = (value: string | number) => {
  const amount = Number(value || 0)
  return Number.isFinite(amount) ? amount.toFixed(2) : '0.00'
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
