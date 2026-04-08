<template>
  <div class="space-y-6">
    <div v-if="loading" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Skeleton v-for="i in 4" :key="i" height="120px" class="rounded-lg" />
      </div>
      <Skeleton height="300px" class="rounded-lg" />
    </div>
  
    <div v-else>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <Card class="hover:shadow-lg transition-shadow cursor-pointer" @click="router.push({ name: 'inventory.items' })">
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Total Items</p>
                <h3 class="text-3xl font-bold text-gray-900">{{ dashboardData.inventory.total_items }}</h3>
              </div>
              <div class="bg-emerald-100 p-4 rounded-full">
                <i class="pi pi-box text-3xl text-emerald-600"></i>
              </div>
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
              <div class="bg-red-100 p-4 rounded-full">
                <i class="pi pi-exclamation-triangle text-3xl text-red-600"></i>
              </div>
            </div>
          </template>
        </Card>
  
        <Card class="hover:shadow-lg transition-shadow cursor-pointer"
          @click="router.push({ name: 'inventory.adjustments' })">
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Pending Adjustments</p>
                <h3 class="text-3xl font-bold text-gray-900">{{ dashboardData.adjustments.pending_approvals }}</h3>
              </div>
              <div class="bg-amber-100 p-4 rounded-full">
                <i class="pi pi-sync text-3xl text-amber-600"></i>
              </div>
            </div>
          </template>
        </Card>
  
        <Card class="hover:shadow-lg transition-shadow cursor-pointer"
          @click="router.push({ name: 'inventory.transfers' })">
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Pending Transfers</p>
                <h3 class="text-3xl font-bold text-gray-900">{{ dashboardData.transfers.pending }}</h3>
              </div>
              <div class="bg-blue-100 p-4 rounded-full">
                <i class="pi pi-arrow-right-arrow-left text-3xl text-blue-600"></i>
              </div>
            </div>
          </template>
        </Card>

        <Card class="hover:shadow-lg transition-shadow cursor-pointer"
          @click="router.push({ name: 'inventory.activity-logs' })">
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Activity Logs</p>
                <h3 class="text-3xl font-bold text-gray-900">{{ dashboardData.activity_logs_count }}</h3>
                <p class="text-xs text-blue-600 mt-1">Recent inventory actions</p>
              </div>
              <div class="bg-indigo-100 p-4 rounded-full">
                <i class="pi pi-history text-3xl text-indigo-600"></i>
              </div>
            </div>
          </template>
        </Card>
      </div>
  
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Card class="lg:col-span-3 hover:shadow-lg transition-shadow cursor-pointer">
          <template #title>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span>Stock Inventory</span>
              </div>
              <Button label="View All" text size="small" @click="router.push({ name: 'inventory.items' })" />
            </div>
          </template>
          <template #content>
            <DataTable
              :value="inventoryItems"
              class="p-datatable-sm"
              responsiveLayout="scroll"
              :loading="inventoryItemsLoading"
              rowHover
              stripedRows
              size="small"
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

              <Column header="Variant" style="width: 16%">
                <template #body="{ data }">
                  <div v-if="data.variation_id" class="text-xs text-gray-700">
                    <div>{{ data.variation?.color || '-' }} / {{ data.variation?.size || '-' }}</div>
                    <div class="text-gray-500">{{ data.variation?.material || '-' }}</div>
                  </div>
                  <span v-else class="text-xs text-gray-500">Standard</span>
                </template>
              </Column>

              <Column field="quantity_on_hand" header="On Hand" style="width: 10%">
                <template #body="{ data }">
                  <span class="font-medium">{{ data.quantity_on_hand }}</span>
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
                <span>Recent Transactions</span>
              </div>
              <Button label="View All" text size="small" @click="router.push({ name: 'inventory.transactions' })" />
            </div>
          </template>
          <template #content>
            <DataTable :value="dashboardData.recent_transactions" class="p-datatable-sm" responsiveLayout="scroll"
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

        <Card class="lg:col-span-3 hover:shadow-lg transition-shadow cursor-pointer">
          <template #title>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span>Recent Activity Logs</span>
              </div>
              <Button label="View All" text size="small" @click="router.push({ name: 'inventory.activity-logs' })" />
            </div>
          </template>
          <template #content>
            <DataTable :value="dashboardData.recent_activity_logs" class="p-datatable-sm" responsiveLayout="scroll"
              :loading="loading" rowHover stripedRows size="small">
              <Column field="created_at" header="Date">
                <template #body="{ data }">
                  {{ formatDate(data.created_at) }}
                </template>
              </Column>
              <Column field="action" header="Action">
                <template #body="{ data }">
                  <Tag :value="formatAction(data.action)" severity="info" />
                </template>
              </Column>
              <Column field="description" header="Description">
                <template #body="{ data }">
                  {{ data.description || 'N/A' }}
                </template>
              </Column>
              <Column field="entity_id" header="Source ID">
                <template #body="{ data }">
                  {{ data.entity_id || '-' }}
                </template>
              </Column>
              <template #empty>
                <div class="text-center py-8 text-gray-500">
                  <i class="pi pi-inbox text-4xl text-gray-300 mb-3"></i>
                  <p>No recent activity logs found</p>
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
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'
import inventoryService from '@/services/inventory.service'

const router = useRouter()
const toast = useToast()
const loading = ref(true)
const inventoryItems = ref<any[]>([])
const inventoryItemsLoading = ref(false)

const dashboardData = ref({
  inventory: {
    total_items: 0,
    in_stock: 0,
    low_stock: 0,
    out_of_stock: 0,
    total_quantity: 0
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
  recent_transactions: [] as any[]
})

const loadDashboard = async () => {
  loading.value = true
  inventoryItemsLoading.value = true
  try {
    // Load main dashboard data + recent logs
    const [statsResponse, logsResponse, inventoryResponse] = await Promise.all([
      axios.get('/api/inventory/dashboard/stats'),
      axios.get('/api/inventory/activity-logs', { params: { per_page: 5 } }),
      inventoryService.getInventoryItems({ page: 1, per_page: 8, sort_by: 'created_at', sort_order: 'desc' })
    ])

    if (statsResponse.data?.data) {
      dashboardData.value = {
        ...dashboardData.value,
        ...statsResponse.data.data
      }
    }

    if (logsResponse.data?.success) {
      const rows = Array.isArray(logsResponse.data?.data?.data) ? logsResponse.data.data.data : []
      dashboardData.value.recent_activity_logs = rows
      dashboardData.value.activity_logs_count = Number(logsResponse.data?.data?.total || rows.length)
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
</style>
