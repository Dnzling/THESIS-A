<template>
  <div class="space-y-6">
    <div v-if="loading" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Skeleton v-for="i in 4" :key="i" height="120px" class="rounded-lg" />
      </div>
      <Skeleton height="300px" class="rounded-lg" />
    </div>
  
    <div v-else>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
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
      </div>
  
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
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

const router = useRouter()
const toast = useToast()
const loading = ref(true)

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
  recent_transactions: [] as any[]
})

const loadDashboard = async () => {
  loading.value = true
  try {
    // Load main dashboard data
    const response = await axios.get('/api/inventory/dashboard/stats')

    if (response.data?.data) {
      dashboardData.value = {
        ...dashboardData.value,
        ...response.data.data
      }

      console.log('Dashboard loaded:', dashboardData.value)
    }
  } catch (error: any) {
    console.error('Failed to load inventory dashboard', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load dashboard data',
      life: 3000
    })
  } finally {
    loading.value = false
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

onMounted(() => {
  loadDashboard()
})
</script>

<style scoped>
:deep(.p-card) {
  @apply h-full;
}
</style>