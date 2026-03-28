<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'inventory.adjustments' })" />
        <div>
          <h2 class="text-2xl font-bold text-gray-800">Adjustment Details</h2>
          <p class="text-sm text-gray-500 mt-1">Review and process stock adjustment</p>
        </div>
      </div>
      <Tag :value="formatStatus(detail?.status)" :severity="statusSeverity(detail?.status)" />
    </div>

    <div v-if="loading" class="space-y-4">
      <Skeleton height="180px" class="rounded-lg" />
      <Skeleton height="250px" class="rounded-lg" />
    </div>

    <div v-else-if="detail" class="space-y-6">
      <!-- Header Card -->
      <Card>
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <p class="text-xs text-gray-600">Reference</p>
              <p class="font-semibold text-gray-900">{{ detail.adjustment_number || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Date</p>
              <p class="font-semibold text-gray-900">{{ formatDate(detail.adjustment_date) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Type</p>
              <p class="font-semibold text-gray-900">{{ formatType(detail.type) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Branch</p>
              <p class="font-semibold text-gray-900">{{ detail.branch?.name || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Reason</p>
              <p class="font-semibold text-gray-900">{{ formatReason(detail.reason) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Created By</p>
              <p class="font-semibold text-gray-900">{{ getCreatedByName(detail.created_by) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Created At</p>
              <p class="font-semibold text-gray-900">{{ formatDate(detail.created_at) }}</p>
            </div>
            <div v-if="detail.approved_by">
              <p class="text-xs text-gray-600">Approved By</p>
              <p class="font-semibold text-gray-900">{{ getApprovedByName(detail.approved_by) }}</p>
            </div>
          </div>
        </template>
      </Card>

      <!-- Items Card -->
      <Card>
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-list text-emerald-600"></i>
            <span>Adjustment Items</span>
          </div>
        </template>
        <template #content>
          <DataTable :value="detail.items || []" class="p-datatable-sm" stripedRows showGridlines>
            <Column header="Product" style="width: 25%">
              <template #body="{ data }">
                <div class="flex flex-col">
                  <span class="font-medium">{{ data.product?.product_name || 'Unknown' }}</span>
                  <span class="text-xs text-gray-500">SKU: {{ data.product?.sku || 'N/A' }}</span>
                </div>
              </template>
            </Column>
            
            <Column header="System Qty" style="width: 10%">
              <template #body="{ data }">
                {{ data.system_quantity }}
              </template>
            </Column>
            
            <Column header="Actual Qty" style="width: 10%">
              <template #body="{ data }">
                {{ data.actual_quantity }}
              </template>
            </Column>
            
            <Column header="Difference" style="width: 10%">
              <template #body="{ data }">
                <Tag 
                  :value="data.difference > 0 ? '+' + data.difference : data.difference"
                  :severity="data.difference > 0 ? 'success' : data.difference < 0 ? 'danger' : 'secondary'"
                />
              </template>
            </Column>
            
            <Column header="Unit Cost" style="width: 10%">
              <template #body="{ data }">
                ₱{{ Number(data.unit_cost).toFixed(2) }}
              </template>
            </Column>
            
            <Column header="Value Diff" style="width: 10%">
              <template #body="{ data }">
                ₱{{ Number(data.value_difference).toFixed(2) }}
              </template>
            </Column>
            
            <Column field="notes" header="Notes" style="width: 15%">
              <template #body="{ data }">
                {{ data.notes || '-' }}
              </template>
            </Column>
          </DataTable>

          <!-- Summary -->
          <div class="mt-4 bg-gray-50 p-4 rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <p class="text-sm text-gray-600">Total Items</p>
                <p class="text-xl font-bold">{{ detail.items?.length || 0 }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Total Difference</p>
                <p class="text-xl font-bold" :class="totalDifference > 0 ? 'text-green-600' : totalDifference < 0 ? 'text-red-600' : ''">
                  {{ totalDifference > 0 ? '+' : '' }}{{ totalDifference }}
                </p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Total Value Difference</p>
                <p class="text-xl font-bold" :class="totalValueDifference > 0 ? 'text-green-600' : totalValueDifference < 0 ? 'text-red-600' : ''">
                  ₱{{ Number(totalValueDifference).toFixed(2) }}
                </p>
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Not Found Message -->
    <div v-else class="text-center py-12">
      <i class="pi pi-exclamation-triangle text-4xl text-gray-400"></i>
      <p class="text-gray-600 mt-2">Adjustment not found</p>
      <Button label="Back to List" icon="pi pi-arrow-left" class="mt-4" @click="router.push({ name: 'inventory.adjustments' })" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '../../../../services/inventory.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const processing = ref(false)
const detail = ref<any>(null)

const adjustmentId = computed(() => Number(route.params.id))

// Check if user can approve/reject (pending_approval status)
const canAction = computed(() => {
  return detail.value?.status === 'pending_approval'
})

// Calculate totals
const totalDifference = computed(() => {
  if (!detail.value?.items) return 0
  return detail.value.items.reduce((sum: number, item: any) => sum + (item.difference || 0), 0)
})

const totalValueDifference = computed(() => {
  if (!detail.value?.items) return 0
  return detail.value.items.reduce((sum: number, item: any) => sum + (Number(item.value_difference) || 0), 0)
})

// Helper functions
const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  })
}

const formatStatus = (status: string) => {
  if (!status) return 'N/A'
  
  const statusMap: Record<string, string> = {
    'draft': 'Draft',
    'pending_approval': 'Pending Approval',
    'approved': 'Approved',
    'applied': 'Applied',
    'rejected': 'Rejected',
    'cancelled': 'Cancelled'
  }
  
  return statusMap[status] || status.split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

const formatType = (type: string) => {
  if (!type) return '-'
  
  const typeMap: Record<string, string> = {
    'physical_count': 'Physical Count',
    'cycle_count': 'Cycle Count',
    'spot_check': 'Spot Check',
    'damage': 'Damage',
    'loss': 'Loss',
    'found': 'Found',
    'correction': 'Correction',
    'writeoff': 'Write Off'
  }
  
  return typeMap[type] || type.split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

const formatReason = (reason: string) => {
  if (!reason) return '-'
  
  const reasonMap: Record<string, string> = {
    'physical_count': 'Physical Count Correction',
    'damaged': 'Damaged Goods',
    'expired': 'Expired Items',
    'theft': 'Theft/Loss',
    'wrong_delivery': 'Wrong Delivery',
    'quality_control': 'Quality Control',
    'sample': 'Sample/Demo Usage',
    'other': 'Other'
  }
  
  return reasonMap[reason] || reason.split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

const getCreatedByName = (createdBy: any) => {
  if (!createdBy) return '-'
  return `${createdBy.fname || ''} ${createdBy.lname || ''}`.trim() || `Employee #${createdBy.id}`
}

const getApprovedByName = (approvedBy: any) => {
  if (!approvedBy) return '-'
  return `${approvedBy.fname || ''} ${approvedBy.lname || ''}`.trim() || `Employee #${approvedBy.id}`
}

const statusSeverity = (status: string) => {
  const severities: Record<string, string> = {
    'draft': 'secondary',
    'pending_approval': 'warning',
    'approved': 'info',
    'applied': 'success',
    'rejected': 'danger',
    'cancelled': 'danger'
  }
  return severities[status] || 'secondary'
}

// API calls
const loadDetail = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getAdjustment(adjustmentId.value)
    // Handle nested response structure
    detail.value = response.data?.data || response.data || null
    
    if (!detail.value) {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Adjustment not found',
        life: 3000
      })
    }
  } catch (error: any) {
    console.error('Failed to load adjustment detail', error)
    detail.value = null
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load adjustment details',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadDetail()
})
</script>