<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">Orders</h1>
        <p class="text-sm text-gray-500">Unified view of in-store and online orders.</p>
      </div>
      <Button severity="info" outlined icon="pi pi-refresh" label="Refresh" @click="loadOrders" />
    </div>

    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
          <div class="md:col-span-6">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" fluid placeholder="Search order number, customer, contact..." />
            </IconField>
          </div>
          <div class="md:col-span-3">
            <Select v-model="filters.channel" :options="channelOptions" optionLabel="label" optionValue="value" placeholder="Channel" showClear fluid />
          </div>
          <div class="md:col-span-3">
            <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Status" showClear fluid />
          </div>
        </div>
      </template>
    </Card>

    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <DataTable
          :value="filteredOrders"
          :loading="loading"
          dataKey="key"
          stripedRows
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
          <Column field="channel" header="Channel">
            <template #body="{ data }">
              <Tag :value="data.channel" :severity="data.channel === 'Online' ? 'info' : 'success'" />
            </template>
          </Column>
          <Column field="total_amount" header="Total">
            <template #body="{ data }">{{ formatMoney(data.total_amount) }}</template>
          </Column>
          <Column field="payment_method" header="Payment">
            <template #body="{ data }">
              <Tag severity="secondary" :value="String(data.payment_method || '-').toUpperCase()" />
              <p class="text-xs text-gray-500 mt-1">{{ data.payment_status || '-' }}</p>
            </template>
          </Column>
          <Column field="status" header="Status">
            <template #body="{ data }">
              <Tag :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
          <Column header="Actions">
            <template #body="{ data }">
              <div class="flex items-center gap-1">
                <Button severity="info" text icon="pi pi-eye" @click="openDetail(data)" />
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
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'

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
  route_name: string
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
})

const channelOptions = [
  { label: 'In-Store', value: 'In-Store' },
  { label: 'Online', value: 'Online' },
]

const statusOptions = [
  { label: 'Pending', value: 'pending' },
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

const canViewPosOrders = computed(() =>
  authStore.hasPermission('sales.pos.view') || authStore.hasPermission('sales.pos.manage')
)
const canViewEcommerceOrders = computed(() =>
  authStore.hasPermission('sales.ecommerce-orders.view') || authStore.hasPermission('sales.ecommerce-orders.manage')
)

const loadOrders = async () => {
  loading.value = true
  try {
    const tasks: Promise<any>[] = []
    if (canViewPosOrders.value) {
      tasks.push(salesService.getPosOrders({ per_page: 50 }))
    } else {
      tasks.push(Promise.resolve({ data: { data: [] } }))
    }

    if (canViewEcommerceOrders.value) {
      tasks.push(salesService.getEcommerceOrders({ per_page: 50 }))
    } else {
      tasks.push(Promise.resolve({ data: { data: [] } }))
    }

    const [posRes, ecommerceRes] = await Promise.all(tasks)
    const posOrders = posRes?.data?.data || []
    const ecommerceOrders = ecommerceRes?.data?.data || []

    const unifiedPos = posOrders.map((order: any) => ({
      key: `pos-${order.id}`,
      id: order.id,
      order_number: order.order_number || `POS-${order.id}`,
      customer_name: order.customer_name || order.customer?.name || 'Walk-in',
      customer_contact: order.customer_phone || order.customer?.phone || '',
      payment_method: order.payment_method || order.payment?.method || '',
      payment_status: order.payment_status || order.payment?.status || '',
      status: order.payment_status || 'completed',
      total_amount: Number(order.total_amount || 0),
      created_at: order.created_at || order.placed_at,
      channel: 'In-Store',
      route_name: 'sales.pos.order-detail',
    })) as UnifiedOrder[]

    const unifiedEcommerce = ecommerceOrders.map((order: any) => ({
      key: `online-${order.id}`,
      id: order.id,
      order_number: order.order_number || `WEB-${order.id}`,
      customer_name: order.shipping_name || order.customer_name || '',
      customer_contact: order.shipping_phone || order.contact_number || '',
      payment_method: order.payment_method || order.payment?.method || '',
      payment_status: order.payment_status || order.payment?.status || '',
      status: order.status || 'pending',
      total_amount: Number(order.total_amount || 0),
      created_at: order.placed_at || order.created_at,
      channel: 'Online',
      route_name: 'sales.ecommerce-orders.detail',
    })) as UnifiedOrder[]

    orders.value = [...unifiedPos, ...unifiedEcommerce].sort((a, b) => {
      return new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
    })
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

const formatStatus = (status: string) => status.replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase())
const formatDateTime = (value: string) => new Date(value).toLocaleString('en-PH')
const formatMoney = (value: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))

const statusSeverity = (status: string) => {
  const normalized = String(status || '').toLowerCase()
  if (normalized === 'delivered' || normalized === 'paid' || normalized === 'completed') return 'success'
  if (normalized === 'cancelled' || normalized === 'failed') return 'danger'
  if (normalized === 'pending') return 'warning'
  return 'info'
}

onMounted(loadOrders)
</script>
