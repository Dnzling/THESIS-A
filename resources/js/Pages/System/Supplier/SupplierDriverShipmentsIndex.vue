<template>
  <div class="max-w-7xl mx-auto space-y-4 py-4 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold text-slate-900">Delivery Board</h1>
        <p class="mt-1 text-sm text-slate-500">Shipments assigned to your supplier account</p>
      </div>
      <button 
        @click="loadShipments" 
        :disabled="loading"
        class="h-9 w-9 rounded-lg bg-white hover:bg-slate-50 flex items-center justify-center transition-colors border border-slate-200 disabled:opacity-50"
      >
        <i class="pi pi-sync text-gray-600" :class="{ 'animate-spin': loading }"></i>
      </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
      <Card class="rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <template #content>
          <div class="px-4 py-3">
            <p class="text-xs text-slate-500">Total Shipments</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ stats.total }}</p>
          </div>
        </template>
      </Card>

      <Card class="rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <template #content>
          <div class="px-4 py-3">
            <p class="text-xs text-slate-500">In Transit</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ stats.inTransit }}</p>
          </div>
        </template>
      </Card>

      <Card class="rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <template #content>
          <div class="px-4 py-3">
            <p class="text-xs text-slate-500">Delivered</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ stats.delivered }}</p>
          </div>
        </template>
      </Card>

      <Card class="rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <template #content>
          <div class="px-4 py-3">
            <p class="text-xs text-slate-500">Pending</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ stats.pending }}</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- Main Content Card -->
    <Card class="rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <template #header>
        <div class="px-4 pt-4">
          <h2 class="text-sm font-semibold text-slate-800">Shipments</h2>
          <p class="mt-1 text-xs text-slate-500">Open a shipment to review its delivery log.</p>
        </div>
      </template>
      
      <template #content>
        <div class="px-4 pb-4 pt-3">
          <!-- Loading State -->
          <div v-if="loading" class="space-y-4">
            <div v-for="i in 3" :key="i" class="bg-gray-50 rounded-xl p-4">
              <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                <Skeleton width="120px" height="20px" />
                <Skeleton width="100px" height="20px" />
                <Skeleton width="100px" height="20px" />
                <Skeleton width="120px" height="20px" />
                <Skeleton width="80px" height="20px" />
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else-if="shipments.length === 0" class="text-center py-8">
            <i class="pi pi-truck mb-2 text-2xl text-slate-400"></i>
            <h3 class="text-sm font-medium text-slate-700">No shipments found</h3>
            <p class="mt-1 text-sm text-slate-500">There are no shipments assigned to your account.</p>
          </div>

          <!-- Data Table -->
          <DataTable 
            v-else 
            :value="shipments" 
            :loading="loading"
            responsive-layout="scroll"
            class="p-datatable-sm text-sm"
            rowHover
            paginator
            :rows="15"
            :rowsPerPageOptions="[15, 25, 50]"
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
                  <Badge
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
                    outlined
                    rounded
                    size="small"
                    @click="viewDetail(data.id)"
                    class="text-xs"
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
            <div class="flex items-center justify-between text-xs">
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
import Badge from 'primevue/badge'
import supplierService from '../../../services/supplier.service'

const router = useRouter()
const toast = useToast()
const shipments = ref<any[]>([])
const loading = ref(true)

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
    pending: 'warn',
    dispatched: 'info',
    in_transit: 'info',
    delivered: 'success',
    cancelled: 'secondary',
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
:deep(.p-card .p-card-content) {
  padding: 0;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  background-color: #f8fafc;
  color: #475569;
  font-weight: 600;
  font-size: 0.75rem;
  padding: 0.75rem;
  border-color: #e2e8f0;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 0.75rem;
  border-color: #e2e8f0;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: #fff7ed;
}

/* Animation for refresh icon */
@keyframes spin {
  to { transform: rotate(360deg); }
}

.animate-spin {
  animation: spin 0.8s linear infinite;
}
</style>
