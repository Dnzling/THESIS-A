<template>
  <div class="space-y-4">
    <div class="rounded-2xl md:rounded-3xl border border-slate-200 bg-white/70 p-3 md:p-4">
      <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="-mx-1 flex w-full overflow-x-auto px-1 sm:w-auto">
          <div class="flex flex-nowrap gap-2">
            <Button
              v-for="tab in tabs"
              :key="tab.value"
              :label="tab.label"
              :outlined="activeTab !== tab.value"
              :severity="activeTab === tab.value ? 'warn' : 'secondary'"
              rounded
              size="small"
              class="shrink-0"
              @click="activeTab = tab.value"
            />
          </div>
        </div>

        <IconField class="w-full sm:w-auto">
          <InputIcon class="pi pi-search" />
          <InputText v-model="search" class="w-full sm:w-80" placeholder="Search order, product or variant" />
        </IconField>
      </div>

      <div v-if="loading" class="space-y-3">
        <Skeleton v-for="idx in 5" :key="idx" height="7rem" class="rounded-2xl" />
      </div>

      <div v-else class="space-y-4">
        <Card v-for="group in groupedOrders" :key="group.order_id" class="border border-slate-200 shadow-none">
          <template #content>
            <div class="space-y-3">
              <div class="border-b border-slate-100 pb-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                  <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-800">Order No: {{ group.order_number }}</p>
                    <p class="text-xs text-slate-500 truncate">Store: {{ group.store_name }}</p>
                  </div>
                  <div class="flex flex-wrap items-center gap-2">
                    <Tag :value="statusLabel(group.status)" :class="statusTagClass(group.status)" />
                    <Button label="View" severity="warn" outlined size="small" @click="goOrderDetail(group.order_id)" />
                  </div>
                </div>
              </div>

              <div
                v-for="item in group.items"
                :key="`${item.order_id}-${item.item_id}`"
                class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 px-3 py-3"
              >
                <div class="flex min-w-0 items-center gap-3">
                  <img :src="normalizeImageUrl(item.image) || '/F.svg'" alt="Product"
                    class="h-14 w-14 rounded-xl border border-slate-200 object-cover" @error="onImageError" />
                  <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ item.product_name }}</p>
                    <p class="truncate text-xs text-slate-500">Variant: {{ item.sku || 'Standard' }}</p>
                  </div>
                </div>

                <div class="grid w-full grid-cols-2 items-center gap-2 sm:flex sm:w-auto sm:items-center sm:gap-4 md:gap-5">
                  <p class="text-xs sm:text-sm text-slate-600">{{ formatDate(item.created_at) }}</p>
                  <p class="text-sm sm:text-lg font-semibold text-slate-900">PHP {{ Number(item.unit_price || 0).toFixed(2) }}</p>
                  <p class="text-xs sm:text-sm font-semibold text-slate-700">Qty {{ item.quantity }}</p>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <div v-if="!groupedOrders.length" class="py-14 text-center text-slate-500">
          No orders found for this filter.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import ecommerceService from '@/services/ecommerce.service'
import InputText from 'primevue/inputtext'
defineOptions({
  layout: EcommerceMobileWrapper,
})


type OrderItemRow = {
  order_id: number
  item_id: number
  store_name: string
  order_number: string
  status: string
  delivery_status: string
  created_at: string
  product_name: string
  sku: string | null
  unit_price: number
  quantity: number
  image: string | null
}

const router = useRouter()
const orders = ref<any[]>([])
const loading = ref(false)
const search = ref('')
const activeTab = ref<'all' | 'ongoing' | 'returns' | 'cancelled' | 'completed'>('all')

const tabs = [
  { label: 'All', value: 'all' as const },
  { label: 'Ongoing Orders', value: 'ongoing' as const },
  { label: 'Returns', value: 'returns' as const },
  { label: 'Cancellations', value: 'cancelled' as const },
  { label: 'Completed', value: 'completed' as const },
]

const ongoingStatuses = new Set(['pending', 'processing', 'packed', 'shipped', 'in_transit', 'on_delivery'])
const completedStatuses = new Set(['completed', 'delivered'])
const returnStatuses = new Set(['return_pending', 'return_approved', 'return_received', 'refunded'])
const cancellationStatuses = new Set(['cancel_pending', 'cancelled'])

const flattenedItems = computed<OrderItemRow[]>(() => {
  return orders.value.flatMap((order: any) => {
    const items = Array.isArray(order.items) ? order.items : []
    const effectiveStatus = String(order.primary_status || order.status || '')
    const deliveryStatus = String(order.delivery?.status || 'pending')
    return items.map((item: any) => ({
      order_id: Number(order.id),
      item_id: Number(item.id),
      store_name: String(order.store_name || 'Store'),
      order_number: String(order.order_number || ''),
      status: effectiveStatus,
      delivery_status: deliveryStatus,
      created_at: String(order.created_at || ''),
      product_name: String(item.product_name || ''),
      sku: item.sku || null,
      unit_price: Number(item.unit_price || 0),
      quantity: Number(item.quantity || 0),
      image: item.image || null,
    }))
  })
})

const filteredItems = computed(() => {
  const keyword = search.value.trim().toLowerCase()
  return flattenedItems.value.filter((item) => {
    const status = item.status.toLowerCase()
    const matchesKeyword =
      !keyword ||
      item.order_number.toLowerCase().includes(keyword) ||
      item.product_name.toLowerCase().includes(keyword) ||
      String(item.sku || '').toLowerCase().includes(keyword)

    if (!matchesKeyword) return false
    if (activeTab.value === 'all') return true
    if (activeTab.value === 'ongoing') return ongoingStatuses.has(status)
    if (activeTab.value === 'completed') return completedStatuses.has(status)
    if (activeTab.value === 'returns') return returnStatuses.has(status)
    if (activeTab.value === 'cancelled') return cancellationStatuses.has(status)
    return true
  })
})

type OrderGroup = {
  order_id: number
  order_number: string
  store_name: string
  status: string
  delivery_status: string
  created_at: string
  items: OrderItemRow[]
}

const groupedOrders = computed<OrderGroup[]>(() => {
  const groups = new Map<number, OrderGroup>()

  for (const item of filteredItems.value) {
    const key = item.order_id
    if (!groups.has(key)) {
      groups.set(key, {
        order_id: item.order_id,
        order_number: item.order_number,
        store_name: item.store_name,
        status: item.status,
        delivery_status: item.delivery_status,
        created_at: item.created_at,
        items: [],
      })
    }
    groups.get(key)!.items.push(item)
  }

  return Array.from(groups.values()).sort((a, b) => (new Date(b.created_at).getTime() || 0) - (new Date(a.created_at).getTime() || 0))
})

async function loadOrders() {
  loading.value = true
  try {
    const response = await ecommerceService.getOrders({ per_page: 20 })
    orders.value = response.data?.data?.data || []
  } finally {
    loading.value = false
  }
}

function statusLabel(status: string) {
  const value = String(status || '').toLowerCase()
  if (ongoingStatuses.has(value) || value === 'pending') return 'Pending'
  if (value === 'packing') return 'Packing'
  if (value === 'in_transit') return 'In Transit'
  if (completedStatuses.has(value) || value === 'delivered') return 'Delivered'
  if (returnStatuses.has(value)) {
    if (value === 'return_pending') return 'Return Pending'
    if (value === 'return_approved') return 'Return Approved'
    if (value === 'return_received') return 'Return Received'
    if (value === 'refunded') return 'Refunded'
    return 'Return'
  }
  if (value === 'cancel_pending') return 'Cancel Pending'
  if (cancellationStatuses.has(value)) return 'Cancelled'
  return status || 'Unknown'
}

function deliveryLabel(status: string) {
  const value = String(status || '').toLowerCase()
  if (value === 'pending') return 'Delivery pending'
  if (value === 'assigned') return 'Delivery assigned'
  if (value === 'packed') return 'Packed'
  if (value === 'in_transit') return 'In transit'
  if (value === 'out_for_delivery') return 'Out for delivery'
  if (value === 'delivered') return 'Delivered'
  if (value === 'failed_delivery') return 'Failed delivery'
  if (value === 'cancelled') return 'Cancelled'
  return status || 'Delivery'
}

function statusTagClass(status: string) {
  const value = String(status || '').toLowerCase()
  if (completedStatuses.has(value)) return '!bg-emerald-100 !text-emerald-700 !border-0'
  if (ongoingStatuses.has(value)) return '!bg-amber-100 !text-amber-700 !border-0'
  if (returnStatuses.has(value)) return '!bg-sky-100 !text-sky-700 !border-0'
  if (cancellationStatuses.has(value)) return '!bg-rose-100 !text-rose-700 !border-0'
  return '!bg-slate-100 !text-slate-700 !border-0'
}

function goOrderDetail(orderId: number) {
  router.push({ name: 'ecommerce.order-detail', params: { id: orderId } })
}

function formatDate(value: string) {
  if (!value) return '-'
  return new Date(value).toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' })
}

function normalizeImageUrl(raw: string) {
  if (!raw) return ''
  if (raw.startsWith('http://') || raw.startsWith('https://') || raw.startsWith('data:')) return raw
  if (raw.startsWith('/storage/')) return raw
  if (raw.startsWith('storage/')) return `/${raw}`
  return `/storage/${raw.replace(/^\//, '')}`
}

function onImageError(event: Event) {
  const target = event.target as HTMLImageElement | null
  if (target) target.src = '/F.svg'
}

onMounted(loadOrders)
</script>
