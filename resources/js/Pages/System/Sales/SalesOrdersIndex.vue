<template>
  <div class="p-4 space-y-6 py-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">Orders</h1>
        <p class="text-sm text-gray-500">Unified view of in-store and online orders.</p>
      </div>
      <Button outlined icon="pi pi-refresh" text  @click="loadOrders" />
    </div>

    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #header>
        <div class="grid grid-cols-7 gap-3 border-b p-6">
          <div class="xl:col-span-2">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" fluid placeholder="Search order number, customer, contact..." />
            </IconField>
          </div>
          <div>
            <Select v-model="filters.channel" :options="channelOptions" optionLabel="label" optionValue="value" placeholder="Channel" showClear fluid />
          </div>
          <div>
            <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Status" showClear fluid />
          </div>
          <div>
            <Select v-model="filters.payment_method" :options="paymentMethodOptions" optionLabel="label" optionValue="value" placeholder="Payment Method" showClear fluid />
          </div>

          <div class="xl:col-span-2">
            <DatePicker v-model="filters.date_range" selectionMode="range" placeholder="Order date range"
              :maxDate="new Date()" fluid showIcon />
          </div>
          <div class="flex justify-end">
            <Button v-if="hasActiveFilters" icon="pi pi-filter-slash" label="Reset" severity="secondary" outlined @click="resetFilters" />
          </div>
        </div>
      </template>
      <template #content>
        <DataTable
          :value="filteredOrders"
          :loading="loading"
          dataKey="key"
          class="text-sm"
          rowHover
          paginator
          :rows="15"
          :rowsPerPageOptions="[10, 15, 25, 50]"
        >
          <Column field="order_number" header="Order">
            <template #body="{ data }">
              <button class="font-medium text-blue-600 hover:underline" @click="openDetail(data)">
                {{ data.order_number }}
              </button>
              <p class="text-xs text-gray-500 mt-1">{{ formatDateTime(data.created_at) }}</p>
            </template>
          </Column>
          <Column header="Customer">
            <template #body="{ data }">
              <p class="text-sm font-medium text-gray-900">{{ data.customer_name || '-' }}</p>
              <p class="text-xs text-gray-500">{{ data.customer_contact || '-' }}</p>
            </template>
          </Column>
          <Column field="channel" header="Channel" style="width: 8%;">
            <template #body="{ data }">
              <Badge :value="data.channel" :severity="data.channel === 'Online' ? 'info' : 'success'" />
            </template>
          </Column>
          <Column field="branch_name" header="Branch">
            <template #body="{ data }">{{ data.branch_name || 'Unassigned' }}</template>
          </Column>
          <Column field="items_count" header="Items">
            <template #body="{ data }">{{ Number(data.items_count || 0).toLocaleString() }}</template>
          </Column>
          <Column field="total_amount" header="Total" class="text-sm font-semibold">
            <template #body="{ data }">{{ formatMoney(data.total_amount) }}</template>
          </Column>
          <Column field="payment_method" header="Payment">
            <template #body="{ data }">
              <Badge severity="secondary" :value="paymentMethodLabel(data.payment_method)" />
              <p class="text-xs text-gray-500 mt-1">{{ formatStatus(data.payment_status || '-') }}</p>
            </template>
          </Column>
          <Column field="status" header="Status">
            <template #body="{ data }">
              <Badge :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
          <Column header="Actions">
            <template #body="{ data }">
              <div class="flex items-center gap-1">
                <Button  text icon="pi pi-eye" @click="openDetail(data)" />
                <Button severity="secondary" text icon="pi pi-print" @click="printReceipt(data)" />
              
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import salesService from '@/services/sales.service'
import { useAuthStore } from '@/stores/auth'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import DatePicker from 'primevue/datepicker'

type UnifiedOrder = {
  key: string
  id: number
  order_number: string
  customer_name?: string
  customer_contact?: string
  payment_method?: string
  payment_status?: string
  status: string
  total_amount: number
  created_at: string
  channel: 'In-Store' | 'Online'
  order_type: 'pos' | 'ecommerce'
  branch_name?: string
  items_count?: number
  route_name: string
  delivery_required?: boolean
  delivery?: any
}

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const loading = ref(false)
const orders = ref<UnifiedOrder[]>([])

const filters = reactive({
  search: '',
  status: null as string | null,
  channel: null as 'In-Store' | 'Online' | null,
  payment_method: null as string | null,
  payment_status: null as string | null,
  date_range: null as [Date | null, Date | null] | null,
})

const hasActiveFilters = computed(() => Boolean(
  filters.search.trim() || filters.status || filters.channel || filters.payment_method || filters.payment_status
  || (Array.isArray(filters.date_range) && (filters.date_range[0] || filters.date_range[1]))
))

const channelOptions = [
  { label: 'In-Store', value: 'In-Store' },
  { label: 'Online', value: 'Online' },
]

const statusOptions = [
  { label: 'Pending', value: 'pending' },
  { label: 'Ready for Dispatch', value: 'ready_for_dispatch' },
  { label: 'Assigned', value: 'assigned' },
  { label: 'Paid', value: 'paid' },
  { label: 'Completed', value: 'completed' },
  { label: 'Processing', value: 'processing' },
  { label: 'Packed', value: 'packed' },
  { label: 'Shipped', value: 'shipped' },
  { label: 'In Transit', value: 'in_transit' },
  { label: 'Out For Delivery', value: 'out_for_delivery' },
  { label: 'Delivered', value: 'delivered' },
  { label: 'Cancelled', value: 'cancelled' },
]

const paymentMethodOptions = [
  { label: 'Cash', value: 'cash' },
  { label: 'GCash', value: 'gcash' },
  { label: 'Credit / Debit Card', value: 'card' },
  { label: 'Cash on Delivery', value: 'cod' },
  { label: 'Bank Transfer', value: 'bank_transfer' },
]

const paymentStatusOptions = [
  { label: 'Paid', value: 'paid' },
  { label: 'Pending', value: 'pending' },
  { label: 'Processing', value: 'processing' },
  { label: 'Failed', value: 'failed' },
  { label: 'Refunded', value: 'refunded' },
]

const canViewPosOrders = computed(() =>
  authStore.hasPermission('sales.pos.view') || authStore.hasPermission('sales.pos.manage')
)
const canViewEcommerceOrders = computed(() =>
  authStore.hasPermission('sales.ecommerce-orders.view') || authStore.hasPermission('sales.ecommerce-orders.manage')
)

const loadOrders = async () => {
  loading.value = true
  try {
    const response = await salesService.getUnifiedOrders()
    orders.value = (response?.data || []).map((order: any) => ({
      ...order,
      total_amount: Number(order.total_amount || 0),
    }))
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load orders', life: 3000 })
  } finally {
    loading.value = false
  }
}

const filteredOrders = computed(() => {
  let results = [...orders.value]

  if (filters.channel) {
    results = results.filter(order => order.channel === filters.channel)
  }

  if (filters.status) {
    results = results.filter(order => String(order.status || '').toLowerCase() === String(filters.status).toLowerCase())
  }

  if (filters.payment_method) {
    results = results.filter(order => paymentMethodKey(order.payment_method) === filters.payment_method)
  }

  if (filters.payment_status) {
    results = results.filter(order => paymentStatusKey(order.payment_status) === filters.payment_status)
  }

  if (Array.isArray(filters.date_range) && (filters.date_range[0] || filters.date_range[1])) {
    const start = filters.date_range[0] ? startOfDay(filters.date_range[0]).getTime() : Number.NEGATIVE_INFINITY
    const end = filters.date_range[1] ? endOfDay(filters.date_range[1]).getTime() : Number.POSITIVE_INFINITY
    results = results.filter(order => {
      const timestamp = new Date(order.created_at).getTime()
      return Number.isFinite(timestamp) && timestamp >= start && timestamp <= end
    })
  }

  if (filters.search) {
    const term = filters.search.toLowerCase()
    results = results.filter(order =>
      String(order.order_number || '').toLowerCase().includes(term) ||
      String(order.customer_name || '').toLowerCase().includes(term) ||
      String(order.customer_contact || '').toLowerCase().includes(term)
    )
  }

  return results
})

const openDetail = (order: UnifiedOrder) => {
  router.push({ name: order.route_name, params: { id: order.id } })
}

const sendToLogistics = async (order: UnifiedOrder) => {
  try {
    if (order.channel === 'Online') {
      await salesService.updateEcommerceOrderStatus(String(order.id), {
        status: 'ready_for_dispatch',
        notes: 'Sent to logistics for delivery assignment.',
      })
    } else {
      await salesService.sendPosOrderToLogistics(order.id)
    }
    toast.add({ severity: 'success', summary: 'Queued', detail: 'Order sent to logistics.', life: 2500 })
    await loadOrders()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Action Failed', detail: error?.response?.data?.message || 'Unable to send to logistics.', life: 3000 })
  }
}

const printReceipt = (order: UnifiedOrder) => {
  const url = order.channel === 'Online'
    ? `/api/sales/ecommerce-orders/${order.id}/receipt`
    : `/api/sales/pos/orders/${order.id}/receipt`
  window.open(url, '_blank')
}

const formatStatus = (status: string) => status.replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase())
const paymentMethodKey = (value: any) => {
  const method = String(value || '').toLowerCase().trim()
  if (['e_wallet', 'ewallet', 'gcash'].includes(method)) return 'gcash'
  if (['credit_card', 'debit_card'].includes(method)) return 'card'
  if (['cash_on_delivery', 'cash on delivery'].includes(method)) return 'cod'
  return method
}
const paymentMethodLabel = (value: any) => {
  const method = paymentMethodKey(value)
  const labels: Record<string, string> = {
    gcash: 'GCASH',
    card: 'Credit / Debit Card',
    cash: 'Cash',
    cod: 'Cash on Delivery',
    bank_transfer: 'Bank Transfer',
  }
  return labels[method] || (method ? formatStatus(method) : '-')
}
const paymentStatusKey = (value: any) => {
  const status = String(value || '').toLowerCase().trim()
  if (['succeeded', 'completed'].includes(status)) return 'paid'
  if (['awaiting_payment_method', 'pending_payment', 'unpaid'].includes(status)) return 'pending'
  if (['cancelled', 'canceled', 'expired'].includes(status)) return 'failed'
  return status
}
const startOfDay = (date: Date) => new Date(date.getFullYear(), date.getMonth(), date.getDate())
const endOfDay = (date: Date) => new Date(date.getFullYear(), date.getMonth(), date.getDate(), 23, 59, 59, 999)
const formatDateTime = (value: string) => new Date(value).toLocaleString('en-PH')
const formatMoney = (value: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))

const resetFilters = () => {
  filters.search = ''
  filters.status = null
  filters.channel = null
  filters.payment_method = null
  filters.payment_status = null
  filters.date_range = null
}

const statusSeverity = (status: string) => {
  const normalized = String(status || '').toLowerCase()
  if (normalized === 'delivered' || normalized === 'paid' || normalized === 'completed') return 'success'
  if (normalized === 'cancelled' || normalized === 'failed') return 'danger'
  if (normalized === 'ready_for_dispatch') return 'warn'
  if (normalized === 'pending') return 'secondary'
  return 'info'
}

onMounted(loadOrders)
</script>
