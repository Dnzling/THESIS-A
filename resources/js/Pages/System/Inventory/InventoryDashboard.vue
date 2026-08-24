<template>
  <div class="space-y-4 text-sm">
    <div v-if="loading" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Skeleton v-for="i in 4" :key="i" height="120px" class="rounded-lg" />
      </div>
      <Skeleton height="300px" class="rounded-lg" />
    </div>
  
    <div v-else>
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-lg font-semibold text-gray-900">Inventory Dashboard</h1>
        </div>
        <IconField class="w-full sm:w-72">
          <InputIcon class="pi pi-search" />
          <InputText v-model="searchQuery" placeholder="Search inventory" size="small" class="w-full text-sm"
            @keyup.enter="searchInventory" />
        </IconField>
      </div>
      <div class="grid grid-cols-2 gap-3 mb-4 lg:grid-cols-4">
        <Card class="hover:shadow-lg transition-shadow cursor-pointer" @click="router.push({ name: 'inventory.items' })">
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Total Items</p>
                <h3 class="text-3xl font-black text-gray-900">{{ dashboardData.inventory.total_items }}</h3>
              </div>
              <i class="pi pi-box text-xl text-emerald-600"></i>
            </div>
          </template>
        </Card>
  
        <Card class="hover:shadow-lg transition-shadow cursor-pointer" @click="router.push({ name: 'inventory.alerts' })">
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Low Stock</p>
                <h3 class="text-3xl font-bold text-gray-900">{{ dashboardData.inventory.low_stock }}</h3>
                <p class="text-xs text-red-600 mt-1">{{ dashboardData.inventory.out_of_stock }} Out of stock</p>
              </div>
              <i class="pi pi-exclamation-triangle text-xl text-red-600"></i>
            </div>
          </template>
        </Card>
  
        <Card class="hover:shadow-lg transition-shadow cursor-pointer"
          @click="router.push({ name: 'inventory.stock-movements' })">
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Pending Adjustments</p>
                <h3 class="text-3xl font-bold text-gray-900">{{ dashboardData.adjustments.pending_approvals }}</h3>
              </div>
              <i class="pi pi-sync text-xl text-amber-600"></i>
            </div>
          </template>
        </Card>
  
        <Card class="hover:shadow-lg transition-shadow cursor-pointer"
          @click="router.push({ name: 'inventory.stock-movements' })">
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Pending Transfers</p>
                <h3 class="text-3xl font-bold text-gray-900">{{ dashboardData.transfers.pending }}</h3>
              </div>
              <i class="pi pi-arrow-right-arrow-left text-xl text-blue-600"></i>
            </div>
          </template>
        </Card>

      </div>

      <div class="grid grid-cols-1 gap-3 lg:grid-cols-2 mb-4">
        <Card>
          <template #title><span class="text-sm font-semibold">Stock Health</span></template>
          <template #content>
            <div class="space-y-3">
              <div v-for="health in stockHealth" :key="health.label">
                <div class="mb-1 flex justify-between text-xs">
                  <span class="text-gray-600">{{ health.label }}</span>
                  <span class="font-medium text-gray-900">{{ health.value }}</span>
                </div>
                <div class="h-1.5 rounded-full bg-gray-100">
                  <div class="h-1.5 rounded-full" :class="health.color" :style="{ width: `${health.percent}%` }"></div>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <Card>
          <template #title>
            <div class="flex items-center justify-between">
              <span class="text-sm font-semibold">Stock Movement</span>
              <Select v-model="trendPeriod" :options="trendPeriods" optionLabel="label" optionValue="value" size="small" class="w-24 text-xs" />
            </div>
          </template>
          <template #content>
            <div class="flex h-32 items-end gap-2 border-b border-gray-100 px-2">
              <div v-for="point in movementTrend" :key="point.label" class="flex min-w-0 flex-1 flex-col items-center gap-1">
                <div class="w-full rounded-t bg-orange-400" :style="{ height: `${point.height}%` }"></div>
                <span class="truncate text-[10px] text-gray-500">{{ point.label }}</span>
              </div>
            </div>
          </template>
        </Card>

        <Card>
          <template #title><span class="text-sm font-semibold">Inventory Value</span></template>
          <template #content>
            <p class="text-2xl font-semibold text-gray-900">{{ formatPhpCurrency(dashboardData.inventory.total_value) }}</p>
            <p class="mt-1 text-xs text-gray-500">Based on quantity on hand and cost per unit.</p>
          </template>
        </Card>

        <Card>
          <template #title>
            <div class="flex items-center justify-between">
              <span class="text-sm font-semibold">Reorder Watchlist</span>
              <Button label="View All" text size="small" class="text-xs" @click="router.push({ name: 'inventory.reorder-suggestions' })" />
            </div>
          </template>
          <template #content>
            <div v-if="reorderWatchlist.length" class="space-y-2">
              <div v-for="item in reorderWatchlist.slice(0, 5)" :key="item.id" class="flex items-center justify-between border-b border-gray-100 pb-2 last:border-0">
                <div class="min-w-0">
                  <p class="truncate text-xs font-medium text-gray-800">{{ item.product?.product_name || 'N/A' }}</p>
                  <p class="text-[10px] text-gray-500">Stock {{ item.quantity_available }} / Reorder {{ item.reorder_point }}</p>
                </div>
                <Tag value="Reorder" severity="warn" size="small" />
              </div>
            </div>
            <p v-else class="py-4 text-center text-xs text-gray-500">No items need reordering.</p>
          </template>
        </Card>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Card class="lg:col-span-3 hover:shadow-lg transition-shadow cursor-pointer">
          <template #title>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="text-lg">Stock Inventory</span>
              </div>
              <div class="flex items-center gap-2">
                <IconField class="w-56">
                  <InputIcon class="pi pi-search" />
                  <InputText v-model="stockSearchQuery" placeholder="Search stock" size="small" class="w-full text-sm" />
                </IconField>
                <Button label="View All" size="small" @click="router.push({ name: 'inventory.items' })" />
              </div>
            </div>
          </template>
          <template #content>
            <div v-if="inventoryItemsLoading" class="space-y-2">
              <div class="grid grid-cols-6 gap-3">
                <Skeleton v-for="i in 6" :key="i" height="22px" />
              </div>
              <div v-for="row in 6" :key="row" class="grid grid-cols-6 gap-3">
                <Skeleton v-for="column in 6" :key="column" height="18px" />
              </div>
            </div>
            <DataTable v-else
              :value="filteredInventoryItems"
              class="p-datatable-xs text-xs"
              responsiveLayout="scroll"
              rowHover
              stripedRows
              size="small"
              @row-click="onInventoryRowClick"
            >
              <template #empty>
                <div class="text-center py-8">
                  <i class="pi pi-inbox text-4xl text-gray-300 mb-3"></i>
                  <p class="text-gray-500">No inventory records found</p>
                </div>
              </template>

              <Column field="created_at" header="Date" style="width: 12%">
                <template #body="{ data }">
                  {{ formatDate(data.created_at) }}
                </template>
              </Column>

              <Column field="product.sku" header="SKU" style="width: 12%">
                <template #body="{ data }">
                  {{ data.variation?.variation_sku || data.product?.sku || 'N/A' }}
                </template>
              </Column>

              <Column field="product.product_name" header="Item Name" style="width: 22%">
                <template #body="{ data }">
                  <div class="text-sm">
                    <div class="font-medium text-gray-900">{{ data.product?.product_name || 'N/A' }}</div>
                    <div class="text-xs text-gray-500">
                      {{ data.variation?.variation_name || (data.variation_id ? 'Variant' : 'Standard') }}
                    </div>
                  </div>
                </template>
              </Column>

              <Column field="product.product_type" header="Product Type" style="width: 16%">
                <template #body="{ data }">
                  <Tag :value="getProductTypeLabel(data.product?.product_type)"
                    :severity="getProductTypeSeverity(data.product?.product_type)" size="small" />
                </template>
              </Column>

              <Column field="quantity_available" header="Stock" style="width: 10%">
                <template #body="{ data }">
                  <span class="font-medium">{{ data.quantity_available }}</span>
                </template>
              </Column>

              <Column field="quantity_available" header="Available" style="width: 10%">
                <template #body="{ data }">
                  {{ data.quantity_available }}
                </template>
              </Column>

              <Column field="reorder_point" header="Reorder Point" style="width: 10%">
                <template #body="{ data }">
                  {{ data.reorder_point }}
                </template>
              </Column>

              <Column field="stock_status" header="Status" style="width: 12%">
                <template #body="{ data }">
                  <Tag :value="getStockLabel(data.stock_status)" :severity="getStockSeverity(data.stock_status)" />
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>

        <Card class="lg:col-span-3 hover:shadow-lg transition-shadow cursor-pointer">
          <template #title>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="text-lg">Recent Transactions</span>
              </div>
              <Button label="View All" size="small" @click="router.push({ name: 'inventory.transactions' })" />
            </div>
          </template>
          <template #content>
            <DataTable :value="dashboardData.recent_transactions" class="p-datatable-xs text-xs" responsiveLayout="scroll"
              :loading="loading" sortMode="multiple" removableSort rowHover stripedRows size="small">
              <Column field="transaction_number" header="Reference" sortable removableSort  />
  
              <Column field="transaction_type" header="Type" sortable removableSort>
                <template #body="{ data }">
                  <Tag :severity="getTransactionSeverity(data.transaction_type)"
                    :value="formatTransactionType(data.transaction_type)" />
                </template>
              </Column>
  
              <Column field="branch.name" header="Branch" sortable removableSort>
                <template #body="{ data }">
                  {{ data.branch?.name || 'N/A' }}
                </template>
              </Column>
  
              <Column field="product.product_name" header="Product" sortable>
                <template #body="{ data }">
                  <div class="flex flex-col">
                    <span class="font-medium">{{ data.product?.product_name || 'N/A' }}</span>
                    <span class="text-xs text-gray-500">{{ data.product?.sku || '' }}</span>
                  </div>
                </template>
              </Column>
  
              <Column field="quantity_change" header="Quantity" sortable removableSort>
                <template #body="{ data }">
                  <span :class="getQuantityClass(data.quantity_change)">
                    {{ data.quantity_change > 0 ? '+' : '' }}{{ data.quantity_change }}
                  </span>
                </template>
              </Column>
  
              <Column field="transaction_date" header="Date" sortable removableSort>
                <template #body="{ data }">
                  {{ formatDate(data.transaction_date || data.created_at) }}
                </template>
              </Column>
  
              <template #empty>
                <div class="text-center py-8 text-gray-500">
                  <i class="pi pi-inbox text-4xl text-gray-300 mb-3"></i>
                  <p>No recent transactions found</p>
                </div>
              </template>
            </DataTable>
          </template>
        </Card>

      </div>
  
      <!-- Period Info -->
      <div class="text-xs text-gray-400 text-right">
        Data for {{ dashboardData.period?.range || 'current' }} period:
        {{ formatDate(dashboardData.period?.start_date) }} - {{ formatDate(dashboardData.period?.end_date) }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'
import inventoryService from '@/services/inventory.service'

const router = useRouter()
const toast = useToast()
const loading = ref(true)
const inventoryItems = ref<any[]>([])
const inventoryItemsLoading = ref(false)
const stockSearchQuery = ref('')
const trendPeriod = ref('daily')
const trendPeriods = [
  { label: 'Daily', value: 'daily' },
  { label: 'Monthly', value: 'monthly' }
]

const stockHealth = computed(() => {
  const total = Math.max(Number(dashboardData.value.inventory.total_items || 0), 1)
  return [
    { label: 'In Stock', value: dashboardData.value.inventory.in_stock, color: 'bg-emerald-500', percent: Math.min(100, dashboardData.value.inventory.in_stock / total * 100) },
    { label: 'Low Stock', value: dashboardData.value.inventory.low_stock, color: 'bg-amber-500', percent: Math.min(100, dashboardData.value.inventory.low_stock / total * 100) },
    { label: 'Out of Stock', value: dashboardData.value.inventory.out_of_stock, color: 'bg-red-500', percent: Math.min(100, dashboardData.value.inventory.out_of_stock / total * 100) }
  ]
})

const reorderWatchlist = computed(() => inventoryItems.value.filter((item) =>
  Number(item.quantity_available || 0) <= Number(item.reorder_point || 0)
))

const movementTrend = computed(() => {
  const rows: any[] = Array.isArray(dashboardData.value.transaction_trends)
    ? dashboardData.value.transaction_trends
    : []
  const values = rows.map((row) => Number(row.total_quantity || row.quantity || row.total || 0))
  const max = Math.max(...values, 1)
  return rows.slice(-7).map((row, index) => ({
    label: row.date || row.period || row.label || `${index + 1}`,
    height: Math.max(8, Number(row.total_quantity || row.quantity || row.total || 0) / max * 100)
  }))
})

const formatPhpCurrency = (value: any) => new Intl.NumberFormat('en-PH', {
  style: 'currency', currency: 'PHP'
}).format(Number(value || 0))

const filteredInventoryItems = computed(() => {
  const search = stockSearchQuery.value.trim().toLowerCase()
  if (!search) return inventoryItems.value

  return inventoryItems.value.filter((item) => {
    const values = [
      item.product?.product_name,
      item.product?.sku,
      item.variation?.variation_name,
      item.product?.product_type
    ]
    return values.some((value) => String(value || '').toLowerCase().includes(search))
  })
})

const onInventoryRowClick = ({ data }: { data: any }) => {
  const productId = data.product?.id || data.product_id
  if (productId) {
    router.push({ name: 'inventory.products.detail', params: { id: productId } })
  }
}

const getProductTypeLabel = (type?: string) => {
  const labels: Record<string, string> = {
    finished_good: 'Finished Good',
    raw_material: 'Raw Material',
    supply: 'Supply'
  }
  return labels[String(type || '').toLowerCase()] || 'Product'
}

const getProductTypeSeverity = (type?: string) => {
  const severities: Record<string, string> = {
    finished_good: 'success',
    raw_material: 'info',
    supply: 'warn'
  }
  return severities[String(type || '').toLowerCase()] || 'secondary'
}
const searchQuery = ref('')

const searchInventory = () => {
  const query = searchQuery.value.trim()
  router.push({ name: 'inventory.items', query: query ? { search: query } : undefined })
}

const dashboardData = ref({
  inventory: {
    total_items: 0,
    in_stock: 0,
    low_stock: 0,
    out_of_stock: 0,
    total_quantity: 0,
    total_value: 0
  },
  alerts: {
    total: 0,
    active: 0,
    critical: 0,
    acknowledged: 0,
    resolved: 0
  },
  transfers: {
    total: 0,
    pending: 0,
    in_transit: 0,
    completed: 0
  },
  activity_logs_count: 0,
  recent_activity_logs: [] as any[],
  recent_transactions: [] as any[],
  transaction_trends: [] as any[]
})

const loadDashboard = async () => {
  loading.value = true
  inventoryItemsLoading.value = true
  try {
    const [statsResponse, inventoryResponse] = await Promise.all([
      axios.get('/api/inventory/dashboard/stats'),
      inventoryService.getInventoryItems({ page: 1, per_page: 8, sort_by: 'created_at', sort_order: 'desc' })
    ])

    if (statsResponse.data?.data) {
      dashboardData.value = {
        ...dashboardData.value,
        ...statsResponse.data.data
      }
    }

    const inventoryRows = Array.isArray(inventoryResponse?.data) ? inventoryResponse.data : []
    inventoryItems.value = inventoryRows
  } catch (error: any) {
    console.error('Failed to load inventory dashboard', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load dashboard data',
      life: 3000
    })
    inventoryItems.value = []
  } finally {
    loading.value = false
    inventoryItemsLoading.value = false
  }
}

const formatTransactionType = (type: string) => {
  const types: Record<string, string> = {
    'purchase': 'Purchase',
    'sale': 'Sale',
    'adjustment': 'Adjustment',
    'transfer': 'Transfer',
    'return': 'Return',
    'damage': 'Damage',
    'receipt': 'Receipt'
  }
  return types[type] || type
}

const getTransactionSeverity = (type: string) => {
  const severities: Record<string, string> = {
    'purchase': 'success',
    'sale': 'info',
    'adjustment': 'warning',
    'transfer': 'info',
    'return': 'danger',
    'damage': 'danger',
    'receipt': 'success'
  }
  return severities[type] || 'info'
}

const getQuantityClass = (quantity: number) => {
  if (quantity > 0) return 'text-green-600 font-medium'
  if (quantity < 0) return 'text-red-600 font-medium'
  return 'text-gray-600'
}

const formatDate = (dateString: string) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getStockLabel = (status: string) => {
  const labels: Record<string, string> = {
    in_stock: 'In Stock',
    low_stock: 'Low Stock',
    out_of_stock: 'Out of Stock',
    overstock: 'Overstock'
  }
  return labels[status] ?? status
}

const getStockSeverity = (status: string) => {
  const severities: Record<string, string> = {
    in_stock: 'success',
    low_stock: 'warning',
    out_of_stock: 'danger',
    overstock: 'info'
  }
  return severities[status] ?? 'secondary'
}

const formatAction = (action: string) => {
  if (!action) return 'N/A'
  return action.replace('inventory.', '').replaceAll('.', ' ')
}

onMounted(() => {
  loadDashboard()
})
</script>

<style scoped>
:deep(.p-card) {
  @apply h-full;
}

:deep(.p-card-body) {
  padding: 0.75rem;
}
</style>
