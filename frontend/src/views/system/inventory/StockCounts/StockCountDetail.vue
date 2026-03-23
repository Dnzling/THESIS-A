<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <Button
              icon="pi pi-arrow-left"
              severity="secondary"
              text
              @click="goBack"
              v-tooltip.top="'Back to Stock Counts'"
            />
            <div>
              <h1 class="text-3xl font-bold text-gray-800">{{ stockCount?.reference_number }}</h1>
              <p class="text-gray-600 mt-1">Stock count details and management</p>
            </div>
          </div>
          <div class="flex gap-2">
            <Button
              v-if="stockCount?.status === 'pending_approval' && authStore.hasPermission('inventory.stock-counts.approve')"
              label="Approve Count Request"
              icon="pi pi-check-circle"
              severity="success"
              @click="approveStockCountRequest"
            />
            <Button
              v-if="stockCount?.status !== 'completed'"
              label="Edit"
              icon="pi pi-pencil"
              severity="warning"
              @click="editStockCount"
            />
            <Button
              v-if="stockCount?.status !== 'completed'"
              label="Complete Count"
              icon="pi pi-check"
              severity="success"
              @click="completeStockCount"
            />
            <Button
              label="Print"
              icon="pi pi-print"
              severity="info"
              outlined
              @click="printStockCount"
            />
            <Button
              label="Export"
              icon="pi pi-download"
              severity="secondary"
              outlined
              @click="exportStockCount"
            />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <ProgressSpinner />
      </div>

      <!-- Stock Count Details -->
      <div v-else-if="stockCount" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Basic Information -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-info-circle"></i>
                Basic Information
              </div>
            </template>
            <template #content>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Reference Number</label>
                  <p class="text-lg font-semibold text-gray-900">{{ stockCount.reference_number }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Count Date</label>
                  <p class="text-lg font-semibold text-gray-900">{{ formatDate(stockCount.count_date) }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Warehouse</label>
                  <p class="text-lg font-semibold text-gray-900">{{ stockCount.warehouse?.name }}</p>
                  <p class="text-sm text-gray-600">{{ stockCount.warehouse?.code }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Location</label>
                  <p class="text-lg font-semibold text-gray-900">
                    {{ stockCount.location?.name || 'All Locations' }}
                  </p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Status</label>
                  <Tag
                    :value="stockCount.status"
                    :severity="getStatusSeverity(stockCount.status)"
                    class="capitalize"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Count Type</label>
                  <p class="text-lg font-semibold text-gray-900 capitalize">{{ stockCount.count_type?.replace('_', ' ') }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Count Method</label>
                  <p class="text-lg font-semibold text-gray-900 capitalize">{{ stockCount.count_method?.replace('_', ' ') }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Counted By</label>
                  <p class="text-lg font-semibold text-gray-900">{{ stockCount.counted_by_user?.name }}</p>
                  <p class="text-sm text-gray-600">{{ formatDateTime(stockCount.created_at) }}</p>
                </div>
              </div>
              <div v-if="stockCount.notes" class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                <p class="text-gray-700">{{ stockCount.notes }}</p>
              </div>
            </template>
          </Card>

          <!-- Count Items -->
          <Card>
            <template #title>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <i class="pi pi-list"></i>
                  Count Items ({{ stockCount.items?.length || 0 }})
                </div>
                <div class="text-sm text-gray-600">
                  Discrepancies: <span class="font-medium text-red-600">{{ discrepanciesCount }}</span>
                </div>
              </div>
            </template>
            <template #content>
              <DataTable
                :value="stockCount.items"
                class="p-datatable-sm"
                tableStyle="min-width: 50rem"
                :paginator="true"
                :rows="10"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
              >
                <Column field="product.name" header="Product" style="min-width: 200px">
                  <template #body="slotProps">
                    <div class="text-sm">
                      <div class="font-medium">{{ slotProps.data.product?.name }}</div>
                      <div class="text-gray-500">{{ slotProps.data.product?.code }}</div>
                    </div>
                  </template>
                </Column>
                <Column field="expected_quantity" header="Expected Qty" style="min-width: 120px">
                  <template #body="slotProps">
                    {{ slotProps.data.expected_quantity || 0 }}
                  </template>
                </Column>
                <Column field="counted_quantity" header="Counted Qty" style="min-width: 120px">
                  <template #body="slotProps">
                    <span v-if="slotProps.data.counted_quantity !== null" class="font-medium">
                      {{ slotProps.data.counted_quantity }}
                    </span>
                    <span v-else class="text-gray-400 italic">Not counted</span>
                  </template>
                </Column>
                <Column field="discrepancy" header="Discrepancy" style="min-width: 120px">
                  <template #body="slotProps">
                    <span :class="getDiscrepancyClass(slotProps.data)">
                      {{ calculateDiscrepancy(slotProps.data) }}
                    </span>
                  </template>
                </Column>
                <Column field="variance_percentage" header="Variance %" style="min-width: 120px">
                  <template #body="slotProps">
                    <span :class="getVarianceClass(slotProps.data)">
                      {{ calculateVariance(slotProps.data) }}%
                    </span>
                  </template>
                </Column>
                <Column field="notes" header="Notes" style="min-width: 150px">
                  <template #body="slotProps">
                    {{ slotProps.data.notes || '-' }}
                  </template>
                </Column>
              </DataTable>
            </template>
          </Card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Summary Statistics -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-chart-bar"></i>
                Summary
              </div>
            </template>
            <template #content>
              <div class="space-y-4">
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Total Items</span>
                  <span class="font-semibold">{{ stockCount.total_items || 0 }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Items Counted</span>
                  <span class="font-semibold">{{ itemsCounted }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Discrepancies</span>
                  <span class="font-semibold text-red-600">{{ discrepanciesCount }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Accuracy Rate</span>
                  <span class="font-semibold">{{ accuracyRate }}%</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Total Variance</span>
                  <span class="font-semibold" :class="totalVariance >= 0 ? 'text-green-600' : 'text-red-600'">
                    {{ totalVariance }}
                  </span>
                </div>
              </div>
            </template>
          </Card>

          <!-- Actions -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-cog"></i>
                Actions
              </div>
            </template>
            <template #content>
              <div class="space-y-3">
                <Button
                  v-if="stockCount.status !== 'completed'"
                  label="Update Counts"
                  icon="pi pi-refresh"
                  severity="info"
                  outlined
                  class="w-full"
                  @click="updateCounts"
                />
                <Button
                  v-if="stockCount.status !== 'completed'"
                  label="Mark as Complete"
                  icon="pi pi-check"
                  severity="success"
                  class="w-full"
                  @click="completeStockCount"
                />
                <Button
                  label="Generate Report"
                  icon="pi pi-file-pdf"
                  severity="secondary"
                  outlined
                  class="w-full"
                  @click="generateReport"
                />
                <Button
                  label="Export Data"
                  icon="pi pi-download"
                  severity="secondary"
                  outlined
                  class="w-full"
                  @click="exportData"
                />
              </div>
            </template>
          </Card>

          <!-- Recent Activity -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-clock"></i>
                Recent Activity
              </div>
            </template>
            <template #content>
              <div class="space-y-3">
                <div v-for="activity in recentActivities" :key="activity.id" class="flex items-start gap-3">
                  <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                  <div class="flex-1">
                    <p class="text-sm text-gray-900">{{ activity.description }}</p>
                    <p class="text-xs text-gray-500">{{ formatDateTime(activity.created_at) }}</p>
                  </div>
                </div>
                <div v-if="recentActivities.length === 0" class="text-center text-gray-500 py-4">
                  No recent activity
                </div>
              </div>
            </template>
          </Card>
        </div>
      </div>

      <!-- Not Found -->
      <div v-else class="text-center py-12">
        <i class="pi pi-exclamation-triangle text-4xl text-gray-400 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Stock Count Not Found</h3>
        <p class="text-gray-600 mb-4">The stock count you're looking for doesn't exist or has been deleted.</p>
        <Button label="Back to Stock Counts" @click="goBack" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter, useRoute } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'
import { useAuthStore } from '../../../../stores/auth'

const loading = ref(false)
const stockCount = ref<any>(null)
const recentActivities = ref<any[]>([])
const toast = useToast()
const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const itemsCounted = computed(() => {
  return stockCount.value?.items?.filter((item: any) => item.counted_quantity !== null).length || 0
})

const discrepanciesCount = computed(() => {
  return stockCount.value?.items?.filter((item: any) => calculateDiscrepancy(item) !== 0).length || 0
})

const accuracyRate = computed(() => {
  if (!stockCount.value?.items?.length) return 0
  const accurateItems = stockCount.value.items.filter((item: any) => calculateDiscrepancy(item) === 0).length
  return Math.round((accurateItems / stockCount.value.items.length) * 100)
})

const totalVariance = computed(() => {
  return stockCount.value?.items?.reduce((sum: number, item: any) => sum + calculateDiscrepancy(item), 0) || 0
})

const loadStockCount = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getStockCount(route.params.id as string)

    if (response.success) {
      stockCount.value = response.data
      loadRecentActivities()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to load stock count',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load stock count',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadRecentActivities = async () => {
  // This would typically load from an activities/audit log endpoint
  // For now, we'll create mock activities based on the stock count data
  recentActivities.value = [
    {
      id: 1,
      description: `Stock count ${stockCount.value.reference_number} created`,
      created_at: stockCount.value.created_at
    },
    ...(stockCount.value.status === 'completed' ? [{
      id: 2,
      description: `Stock count ${stockCount.value.reference_number} completed`,
      created_at: stockCount.value.updated_at
    }] : [])
  ]
}

const goBack = () => {
  router.push({ name: 'inventory.stock-counts.index' })
}

const editStockCount = () => {
  router.push({ name: 'inventory.stock-counts.edit', params: { id: route.params.id } })
}

const completeStockCount = async () => {
  try {
    const response = await inventoryService.completeStockCount(route.params.id as string)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Stock count completed successfully',
        life: 3000
      })
      loadStockCount()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to complete stock count',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to complete stock count',
      life: 3000
    })
  }
}

const approveStockCountRequest = async () => {
  try {
    const response = await inventoryService.approveStockCount(Number(route.params.id))

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Approved',
        detail: 'Stock count request approved and scheduled',
        life: 3000
      })
      loadStockCount()
      return
    }

    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: response.message || 'Failed to approve stock count request',
      life: 3000
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to approve stock count request',
      life: 3000
    })
  }
}

const updateCounts = () => {
  // This would open a dialog or navigate to an update counts page
  toast.add({
    severity: 'info',
    summary: 'Info',
    detail: 'Update counts functionality would be implemented here',
    life: 3000
  })
}

const printStockCount = () => {
  window.print()
}

const exportStockCount = () => {
  // This would trigger a CSV/PDF export
  toast.add({
    severity: 'info',
    summary: 'Info',
    detail: 'Export functionality would be implemented here',
    life: 3000
  })
}

const generateReport = () => {
  // This would generate a detailed PDF report
  toast.add({
    severity: 'info',
    summary: 'Info',
    detail: 'Report generation would be implemented here',
    life: 3000
  })
}

const exportData = () => {
  // This would export the data in various formats
  toast.add({
    severity: 'info',
    summary: 'Info',
    detail: 'Data export would be implemented here',
    life: 3000
  })
}

const calculateDiscrepancy = (item: any) => {
  if (item.counted_quantity === null || item.counted_quantity === undefined) return 0
  return item.counted_quantity - (item.expected_quantity || 0)
}

const calculateVariance = (item: any) => {
  if (!item.expected_quantity) return 0
  const discrepancy = calculateDiscrepancy(item)
  return Math.round((discrepancy / item.expected_quantity) * 100)
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString()
}

const formatDateTime = (dateTime: string) => {
  if (!dateTime) return '-'
  return new Date(dateTime).toLocaleString()
}

const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'draft': return 'secondary'
    case 'pending_approval': return 'warning'
    case 'in_progress': return 'info'
    case 'completed': return 'success'
    case 'cancelled': return 'danger'
    default: return 'secondary'
  }
}

const getDiscrepancyClass = (item: any) => {
  const discrepancy = calculateDiscrepancy(item)
  if (discrepancy === 0) return 'text-green-600'
  return 'text-red-600 font-medium'
}

const getVarianceClass = (item: any) => {
  const variance = calculateVariance(item)
  if (variance === 0) return 'text-green-600'
  return variance > 0 ? 'text-blue-600' : 'text-red-600'
}

onMounted(() => {
  loadStockCount()
})
</script>
