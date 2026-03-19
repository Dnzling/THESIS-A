<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <!-- iOS-style Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Delivery Board</h1>
        <p class="text-sm text-gray-500 mt-1">Active shipments assigned to your supplier account</p>
      </div>
      <button 
        @click="loadShipments" 
        :disabled="loading"
        class="w-10 h-10 rounded-full bg-white hover:bg-gray-100 flex items-center justify-center transition-colors shadow-sm border border-gray-200 disabled:opacity-50"
      >
        <i class="pi pi-sync text-gray-600" :class="{ 'animate-spin': loading }"></i>
      </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <template #content>
          <div class="p-5">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Shipments</span>
              <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                <i class="pi pi-truck text-blue-600 text-sm"></i>
              </div>
            </div>
            <p class="text-3xl font-semibold text-gray-900">{{ stats.total }}</p>
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <template #content>
          <div class="p-5">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">In Transit</span>
              <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center">
                <i class="pi pi-spinner text-orange-600 text-sm"></i>
              </div>
            </div>
            <p class="text-3xl font-semibold text-orange-600">{{ stats.inTransit }}</p>
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <template #content>
          <div class="p-5">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Delivered</span>
              <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                <i class="pi pi-check-circle text-green-600 text-sm"></i>
              </div>
            </div>
            <p class="text-3xl font-semibold text-green-600">{{ stats.delivered }}</p>
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden bg-linear-to-br from-purple-500 to-purple-600 border-none">
        <template #content>
          <div class="p-5">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-medium text-purple-100 uppercase tracking-wider">Pending</span>
              <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                <i class="pi pi-clock text-white text-sm"></i>
              </div>
            </div>
            <p class="text-3xl font-bold text-white">{{ stats.pending }}</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- Main Content Card -->
    <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <template #header>
        <div class="px-6 pt-6">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
              <i class="pi pi-list text-blue-600 text-sm"></i>
            </div>
            <h2 class="text-lg font-semibold text-gray-900">Active Shipments</h2>
          </div>
          <p class="text-sm text-gray-500 mt-2">Click the eye icon to open the detailed log for a shipment</p>
        </div>
      </template>
      
      <template #content>
        <div class="p-6 pt-0">
          <!-- Loading State -->
          <div v-if="loading" class="space-y-4">
            <div v-for="i in 3" :key="i" class="bg-gray-50 rounded-xl p-4">
              <div class="grid grid-cols-5 gap-4">
                <Skeleton width="120px" height="20px" />
                <Skeleton width="100px" height="20px" />
                <Skeleton width="100px" height="20px" />
                <Skeleton width="120px" height="20px" />
                <Skeleton width="80px" height="20px" />
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else-if="shipments.length === 0" class="text-center py-12">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <i class="pi pi-truck text-gray-400 text-3xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-700">No Shipments Found</h3>
            <p class="text-gray-500 mt-2">There are no active shipments assigned to your account</p>
          </div>

          <!-- Data Table -->
          <DataTable 
            v-else 
            :value="shipments" 
            :loading="loading"
            responsive-layout="scroll"
            class="p-datatable-sm"
            stripedRows
          >
            <Column field="purchase_order.po_number" header="PO Number" style="min-width: 120px">
              <template #body="{ data }">
                <span class="font-medium text-gray-900">{{ data.purchase_order?.po_number || '-' }}</span>
              </template>
            </Column>

            <Column field="driver_name" header="Driver" style="min-width: 140px">
              <template #body="{ data }">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center">
                    <i class="pi pi-user text-gray-500 text-xs"></i>
                  </div>
                  <span class="text-gray-700">{{ data.driver_name || 'Unassigned' }}</span>
                </div>
              </template>
            </Column>

            <Column field="branch.name" header="Branch" style="min-width: 120px">
              <template #body="{ data }">
                <div class="flex items-center gap-2">
                  <i class="pi pi-map-marker text-gray-400 text-xs"></i>
                  <span class="text-gray-700">{{ data.branch?.name || '-' }}</span>
                </div>
              </template>
            </Column>

            <Column header="Expected Delivery" style="min-width: 140px">
              <template #body="{ data }">
                <div class="flex items-center gap-2">
                  <i class="pi pi-calendar text-gray-400 text-xs"></i>
                  <span class="text-gray-700">{{ formatDate(data.expected_delivery_date) }}</span>
                </div>
              </template>
            </Column>

            <Column header="Status" style="min-width: 120px">
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

            <Column header="Actions" style="width: 100px" headerStyle="text-align: center">
              <template #body="{ data }">
                <div class="flex justify-center">
                  <Button 
                    label="View" 
                    icon="pi pi-eye" 
                    text 
                    rounded
                    @click="viewDetail(data.id)"
                    class="text-gray-500 hover:text-blue-600 hover:bg-blue-50"
                    v-tooltip="'View shipment details'"
                  />
                </div>
              </template>
            </Column>

            <!-- Empty Template -->
            <template #empty>
              <div class="text-center py-8">
                <p class="text-gray-500">No shipments found</p>
              </div>
            </template>
          </DataTable>

          <!-- Shipment Summary Footer -->
          <div v-if="shipments.length > 0" class="mt-4 pt-4 border-t border-gray-100">
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-500">Total shipments</span>
              <span class="font-semibold text-gray-900">{{ shipments.length }}</span>
            </div>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import supplierService from '../../../services/supplier.service'

const router = useRouter()
const toast = useToast()
const shipments = ref<any[]>([])
const loading = ref(false)

// Computed stats
const stats = computed(() => {
  const total = shipments.value.length
  const inTransit = shipments.value.filter(s => s.status === 'in_transit').length
  const delivered = shipments.value.filter(s => s.status === 'delivered').length
  const pending = shipments.value.filter(s => ['pending', 'dispatched'].includes(s.status)).length
  
  return { total, inTransit, delivered, pending }
})

// Helper functions
const formatStatus = (status?: string): string => {
  if (!status) return '-'
  return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const statusSeverity = (status?: string): 'success' | 'info' | 'warn' | 'danger' | 'secondary' => {
  const map: Record<string, any> = {
    pending: 'secondary',
    dispatched: 'info',
    in_transit: 'warn',
    delivered: 'success',
    cancelled: 'danger',
  }
  return map[status || ''] || 'info'
}

const getStatusDot = (status?: string): string => {
  const map: Record<string, string> = {
    pending: 'bg-gray-400',
    dispatched: 'bg-blue-500',
    in_transit: 'bg-orange-500',
    delivered: 'bg-green-500',
    cancelled: 'bg-red-500',
  }
  return map[status || ''] || 'bg-gray-400'
}

const formatDate = (date?: string): string => {
  if (!date) return '-'
  const parsed = new Date(date)
  if (isNaN(parsed.getTime())) return date
  return parsed.toLocaleDateString('en-PH', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  })
}

// Data loading
const loadShipments = async () => {
  loading.value = true
  try {
    const res = await supplierService.getShipments()
    const payload = res.data.data || res
    shipments.value = payload?.data?.shipments || []
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load shipments',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const viewDetail = (id: number) => {
  router.push(`/supplier-portal/deliveries/${id}`)
}

onMounted(() => {
  loadShipments()
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

/* iOS-style buttons */
:deep(.p-button.p-button-text) {
  border-radius: 9999px;
}

:deep(.p-button.p-button-text:hover) {
  background-color: #f3f4f6;
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