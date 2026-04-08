<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <!-- iOS-style Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold text-gray-900 tracking-tight">PO Finance Approvals</h1>
      </div>
    </div>

    <!-- iOS-style Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <template #content>
          <div class="p-5">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Pending</span>
              <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center">
                <i class="pi pi-clock text-orange-600 text-sm"></i>
              </div>
            </div>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.pending }}</p>
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <template #content>
          <div class="p-5">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Approved</span>
              <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                <i class="pi pi-check-circle text-green-600 text-sm"></i>
              </div>
            </div>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.approved }}</p>
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <template #content>
          <div class="p-5">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Rejected</span>
              <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                <i class="pi pi-times-circle text-red-600 text-sm"></i>
              </div>
            </div>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.rejected }}</p>
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden bg-linear-to-br">
        <template #content>
          <div class="p-5">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-medium uppercase tracking-wider">Total Amount</span>
              <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                <i class="pi pi-credit-cardtext-sm"></i>
              </div>
            </div>
            <p class="text-xl font-bold">₱{{ formatNumber(stats.totalAmount) }}</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- iOS-style Filters Card -->
    <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <template #header>
        <div class="px-6 pt-6">
          <div class="flex items-center gap-2">
            <h2 class="text-lg font-semibold text-gray-900">Filter Purchase Orders</h2>
          </div>
        </div>
      </template>
      
      <template #content>
        <div class="p-6 pt-2">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search Input -->
            <div class="md:col-span-2 space-y-2">
              <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Search</label>
              <div class="relative">
                <InputText size="small"
                  v-model="filters.search"
                  type="text"
                  placeholder="Search by PO number"
                  class="w-full pl-9 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                  @keyup.enter="loadPOs"
                />
              </div>
            </div>

            <!-- Status Filter -->
            <div class="space-y-2">
              <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Status</label>
              <Select
                v-model="filters.status"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="All Statuses"
                size="small"
                fluid
                @change="loadPOs"
              />
            </div>

            <!-- Refresh Button -->
            <div class="flex items-end">
              <button
                @click="loadPOs"
                class="w-full px-4 py-2.5 bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-xl text-sm transition-colors flex items-center justify-center gap-2"
              >
                <i class="pi pi-refresh" :class="{ 'animate-spin': loading }"></i>
                <span>Refresh</span>
              </button>
            </div>
          </div>
        </div>
      </template>
    </Card>

    <!-- POs Table Card -->
    <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <template #header>
        <div class="px-6 pt-6">
          <div class="flex items-center gap-2">
            <h2 class="text-lg font-semibold text-gray-900">Purchase Orders</h2>
          </div>
        </div>
      </template>

      <template #content>
        <div class="p-6 pt-2">
          <!-- Loading State -->
          <div v-if="loading" class="space-y-4">
            <div v-for="i in 3" :key="i" class="bg-gray-50 rounded-xl p-4">
              <div class="grid grid-cols-6 gap-4">
                <Skeleton width="120px" height="20px" />
                <Skeleton width="140px" height="20px" />
                <Skeleton width="100px" height="20px" />
                <Skeleton width="100px" height="20px" />
                <Skeleton width="80px" height="20px" />
                <Skeleton width="80px" height="20px" />
              </div>
            </div>
          </div>

          <!-- Data Table -->
          <DataTable 
            v-else
            :value="purchaseOrders" 
            :loading="loading"
            stripedRows
            responsiveLayout="scroll"
            class="p-datatable-sm text-xs"
            paginator
            
            :rows="10"
            :rowsPerPageOptions="[5, 10, 20, 50]"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
          >
            <!-- PO Number Column -->
            <Column field="po_number" header="PO Number" style="min-width: 160px" sortable>
              <template #body="{ data }">
                <div class="flex items-center gap-2">
                  <span class="font-medium text-xs text-blue-600 hover:underline cursor-pointer" @click="viewPO(data)">
                    {{ data.po_number }}
                  </span>
                </div>
              </template>
            </Column>

            <!-- Supplier Column -->
            <Column header="Supplier" style="min-width: 180px">
              <template #body="{ data }">
                <div class="flex items-center gap-2">
                  <div>
                    <p class="font-medium text-xs text-gray-900">{{ data.supplier?.supplier_name || '-' }}</p>
                  </div>
                </div>
              </template>
            </Column>

            <!-- Branch Column -->
            <Column header="Branch" style="min-width: 140px">
              <template #body="{ data }">
                <div class="flex items-center gap-2">
                  <i class="pi pi-map-marker text-gray-400 text-xs"></i>
                  <div>
                    <p class="font-medium text-gray-900">{{ data.branch?.name || '-' }}</p>
                    <p class="text-xs text-gray-500">{{ data.branch?.branch_code || '' }}</p>
                  </div>
                </div>
              </template>
            </Column>

            <!-- Order Date Column -->
            <Column header="Order Date" style="min-width: 120px">
              <template #body="{ data }">
                <div class="flex items-center gap-2">
                  <i class="pi pi-calendar text-gray-400 text-xs"></i>
                  <span class="text-gray-700">{{ formatDate(data.order_date) }}</span>
                </div>
              </template>
            </Column>

            <!-- Total Column -->
            <Column header="Total" style="min-width: 120px" class="text-right">
              <template #body="{ data }">
                <span class="font-semibold text-green-600">₱{{ formatNumber(data.total_amount || data.subtotal || 0) }}</span>
              </template>
            </Column>

            <!-- Status Column -->
            <Column header="Status" style="min-width: 140px">
              <template #body="{ data }">
                <div class="flex items-center gap-2">
                  <div :class="getStatusDot(data.status)" class="w-2 h-2 rounded-full"></div>
                  <Tag 
                    :value="formatStatus(data.status)" 
                    :severity="statusSeverity(data.status)"
                    class="rounded-full text-xs px-3 py-1"
                  />
                </div>
              </template>
            </Column>

            <!-- Actions Column -->
            <Column header="Actions" style="width: 100px" headerStyle="text-align: center">
              <template #body="{ data }">
                <div class="flex justify-center">
                  <button
                    @click="viewPO(data)"
                    class="w-8 h-8 rounded-full hover:bg-blue-50 flex items-center justify-center transition-colors"
                    v-tooltip="'View Details'"
                  >
                    <i class="pi pi-eye text-gray-500 hover:text-blue-600 text-sm"></i>
                  </button>
                </div>
              </template>
            </Column>

            <!-- Empty Template -->
            <template #empty>
              <div class="text-center py-12">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                  <i class="pi pi-inbox text-gray-400 text-3xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-700">No Purchase Orders Found</h3>
                <p class="text-gray-500 mt-2">No purchase orders match your current filters</p>
              </div>
            </template>
          </DataTable>

          <!-- Summary Footer -->
          <div v-if="purchaseOrders.length > 0" class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between text-sm">
            <span class="text-gray-500">Showing {{ purchaseOrders.length }} of {{ purchaseOrders.length }} orders</span>
            <span class="font-medium text-gray-900">Total: ₱{{ formatNumber(totalAmount) }}</span>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import { useToast } from 'primevue/usetoast'
import financeService from '../../../services/finance.service'

const router = useRouter()
const toast = useToast()
const loading = ref(false)
const purchaseOrders = ref<any[]>([])

const filters = ref({
  search: '',
  status: 'pending_finance_approval', // Default to pending approval
})

// Status options for filter
const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Pending Finance Approval', value: 'pending_finance_approval' },
  { label: 'Approved', value: 'approved' },
  { label: 'Sent to Supplier', value: 'sent_to_supplier' },
  { label: 'Supplier Accepted', value: 'supplier_accepted' },
  { label: 'In Transit', value: 'in_transit' },
  { label: 'Delivered', value: 'delivered' },
  { label: 'Rejected (Finance)', value: 'rejected_finance' },
  { label: 'Cancelled', value: 'cancelled' },
]

// Computed stats
const stats = computed(() => {
  const allOrders = purchaseOrders.value
  return {
    pending: allOrders.filter(po => po.status === 'pending_finance_approval').length,
    approved: allOrders.filter(po => po.status === 'approved').length,
    rejected: allOrders.filter(po => po.status === 'rejected_finance').length,
    totalAmount: allOrders.reduce((sum, po) => sum + (parseFloat(po.total_amount || po.subtotal || 0)), 0)
  }
})

const totalAmount = computed(() => {
  return purchaseOrders.value.reduce((sum, po) => sum + (parseFloat(po.total_amount || po.subtotal || 0)), 0)
})

// Helper functions
const getStatusDot = (status: string): string => {
  const map: Record<string, string> = {
    pending_finance_approval: 'bg-orange-400',
    approved: 'bg-green-500',
    sent_to_supplier: 'bg-blue-400',
    supplier_accepted: 'bg-green-400',
    in_transit: 'bg-purple-400',
    delivered: 'bg-green-600',
    rejected_finance: 'bg-red-500',
    cancelled: 'bg-red-400',
  }
  return map[status] || 'bg-gray-400'
}

const loadPOs = async () => {
  loading.value = true
  try {
    const params: any = { 
      per_page: 50 
    }
    
    // Only add status to params if it's selected (not empty string)
    if (filters.value.status && filters.value.status !== '') {
      params.status = filters.value.status
    }
    
    if (filters.value.search) {
      params.search = filters.value.search
    }

    const response = await financeService.getPurchaseOrders(params)
    
    // Handle different response structures
    if (response.data?.data) {
      purchaseOrders.value = response.data.data
    } else if (Array.isArray(response.data)) {
      purchaseOrders.value = response.data
    } else if (Array.isArray(response)) {
      purchaseOrders.value = response
    } else {
      purchaseOrders.value = []
    }

    // Show success message
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `Loaded ${purchaseOrders.value.length} purchase orders`,
      life: 2000
    })
  } catch (error: any) {
    console.error('Failed to load purchase orders:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load purchase orders',
      life: 3000
    })
    purchaseOrders.value = []
  } finally {
    loading.value = false
  }
}

const viewPO = (po: any) => {
  router.push({ 
    name: 'finance.purchase-orders.detail', 
    params: { id: po.id } 
  })
}

const formatDate = (date: string): string => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-PH', { 
    month: 'short', 
    day: 'numeric', 
    year: 'numeric' 
  })
}

const formatNumber = (value: number | string): string => {
  const numValue = typeof value === 'string' ? parseFloat(value) : value
  return new Intl.NumberFormat('en-PH', { 
    minimumFractionDigits: 2, 
    maximumFractionDigits: 2 
  }).format(numValue || 0)
}

const formatStatus = (status: string): string => {
  if (!status) return '-'
  return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const statusSeverity = (status: string): 'success' | 'info' | 'warn' | 'danger' | 'secondary' => {
  const map: Record<string, any> = {
    pending_finance_approval: 'warn',
    approved: 'success',
    sent_to_supplier: 'info',
    supplier_accepted: 'success',
    in_transit: 'info',
    delivered: 'success',
    rejected_finance: 'danger',
    cancelled: 'danger',
  }
  return map[status] || 'info'
}

onMounted(() => {
  loadPOs()
})
</script>

<style scoped>
/* Smooth transitions */
* {
  transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

/* iOS-style shadows */
:deep(.p-card) {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02);
}

:deep(.p-card .p-card-content) {
  padding: 0;
}

:deep(.p-card .p-card-body) {
  padding: 0;
}

/* iOS-style table */
:deep(.p-datatable) {
  border-radius: 16px;
  overflow: hidden;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  background-color: #f9fafb;
  color: #6b7280;
  font-weight: 500;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 1rem 1rem;
  border-bottom: 1px solid #f3f4f6;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 1rem 1rem;
  border-bottom: 1px solid #f3f4f6;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: #f9fafb;
}

/* iOS-style tags */
:deep(.p-tag) {
  border-radius: 9999px;
  font-weight: 500;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}

/* Animation for refresh icon */
@keyframes spin {
  to { transform: rotate(360deg); }
}

.animate-spin {
  animation: spin 0.8s linear infinite;
}
</style>
