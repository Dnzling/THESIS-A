<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4">
      <h1 class="text-2xl font-semibold text-gray-900">Orders</h1>
      <Button severity="info" outlined icon="pi pi-refresh" label="Refresh" @click="loadOrders" />
    </div>

    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
          <div class="md:col-span-7">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" fluid placeholder="Search order number, customer, contact..." />
            </IconField>
          </div>
          <div class="md:col-span-3">
            <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Filter status" showClear fluid />
          </div>
          <div class="md:col-span-2">
            <Button severity="info" fluid label="Clear" outlined @click="clearFilters" />
          </div>
        </div>
      </template>
    </Card>

    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <DataTable
          :value="orders"
          :loading="loading"
          dataKey="id"
          stripedRows
          paginator
          lazy
          :rows="pageState.rows"
          :first="(pageState.page - 1) * pageState.rows"
          :totalRecords="pageState.total"
          :rowsPerPageOptions="[10, 20, 50]"
          @page="onPage"
        >
          <Column field="order_number" header="Order">
            <template #body="{ data }">
              <button class="font-medium text-blue-600 hover:underline" @click="openDetail(data.id)">
                {{ data.order_number }}
              </button>
              <p class="text-xs text-gray-500 mt-1">{{ formatDateTime(data.created_at) }}</p>
            </template>
          </Column>
          <Column header="Customer">
            <template #body="{ data }">
              <p class="text-sm font-medium text-gray-900">{{ data.shipping_name || '-' }}</p>
              <p class="text-xs text-gray-500">{{ data.shipping_phone || '-' }}</p>
            </template>
          </Column>
          <Column field="placed_at" header="Order Date">
            <template #body="{ data }">
              {{ formatDateTime(data.placed_at || data.created_at) }}
            </template>
          </Column>
          <Column field="items_count" header="Items" />
          <Column field="total_amount" header="Total">
            <template #body="{ data }">{{ formatMoney(data.total_amount) }}</template>
          </Column>
          <Column field="payment_method" header="Payment">
            <template #body="{ data }">
              <Tag severity="info" :value="String(data.payment_method || '-').toUpperCase()" />
              <p class="text-xs text-gray-500 mt-1">{{ data.payment_status }}</p>
            </template>
          </Column>
          <Column field="status" header="Order Status">
            <template #body="{ data }">
              <Tag :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
          <Column header="Delivery">
            <template #body="{ data }">
              <div class="space-y-1">
                <Tag v-if="data.delivery?.status" :value="formatStatus(data.delivery.status)" :severity="deliverySeverity(data.delivery.status)" />
                <span v-else class="text-xs text-gray-500">Not assigned</span>
                <p v-if="data.delivery?.tracking_number" class="text-xs text-gray-500">{{ data.delivery.tracking_number }}</p>
              </div>
            </template>
          </Column>
          <Column header="Actions">
            <template #body="{ data }">
              <div class="flex items-center gap-1">
                <Button severity="info" text icon="pi pi-eye" @click="openDetail(data.id)" />
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
import salesService from '@/services/sales.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'

const router = useRouter()
const toast = useToast()
const loading = ref(false)
const orders = ref<any[]>([])

const filters = reactive({
  search: '',
  status: null as string | null,
})

const pageState = reactive({
  page: 1,
  rows: 10,
  total: 0,
})

const statusOptions = [
  { label: 'Pending', value: 'pending' },
  { label: 'Processing', value: 'processing' },
  { label: 'Packed', value: 'packed' },
  { label: 'Shipped', value: 'shipped' },
  { label: 'In Transit', value: 'in_transit' },
  { label: 'Out For Delivery', value: 'out_for_delivery' },
  { label: 'Delivered', value: 'delivered' },
  { label: 'Cancelled', value: 'cancelled' },
]

const loadOrders = async () => {
  loading.value = true
  try {
    const res = await salesService.getEcommerceOrders({
      page: pageState.page,
      per_page: pageState.rows,
      search: filters.search || undefined,
      status: filters.status || undefined,
    })
    const payload = res?.data
    orders.value = payload?.data || []
    pageState.total = Number(payload?.total || 0)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load orders', life: 3000 })
  } finally {
    loading.value = false
  }
}

const clearFilters = () => {
  filters.search = ''
  filters.status = null
}

const onPage = (event: any) => {
  pageState.page = Number(event.page || 0) + 1
  pageState.rows = Number(event.rows || 10)
  loadOrders()
}

const openDetail = (id: number) => {
  router.push({ name: 'sales.ecommerce-orders.detail', params: { id } })
}

const formatStatus = (status: string) => status.replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase())
const formatDateTime = (value: string) => new Date(value).toLocaleString('en-PH')
const formatMoney = (value: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))

const statusSeverity = (status: string) => {
  if (status === 'delivered') return 'success'
  if (status === 'cancelled') return 'danger'
  if (status === 'pending') return 'warning'
  return 'info'
}
const deliverySeverity = (status: string) => {
  if (status === 'delivered') return 'success'
  if (status === 'failed_delivery' || status === 'cancelled') return 'danger'
  if (status === 'out_for_delivery') return 'warning'
  return 'info'
}

watch(() => [filters.search, filters.status], () => {
  pageState.page = 1
  loadOrders()
})

onMounted(loadOrders)
</script>
