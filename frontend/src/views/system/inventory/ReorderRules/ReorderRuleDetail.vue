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
              v-tooltip.top="'Back to Reorder Rules'"
            />
            <div>
              <h1 class="text-3xl font-bold text-gray-800">{{ reorderRule?.product?.name }}</h1>
              <p class="text-gray-600 mt-1">Reorder rule details and management</p>
            </div>
          </div>
          <div class="flex gap-2">
            <Button
              label="Edit"
              icon="pi pi-pencil"
              severity="warning"
              @click="editReorderRule"
            />
            <Button
              label="Toggle Status"
              :icon="reorderRule?.status === 'active' ? 'pi pi-power-off' : 'pi pi-play'"
              :severity="reorderRule?.status === 'active' ? 'danger' : 'success'"
              @click="toggleStatus"
            />
            <Button
              label="Print"
              icon="pi pi-print"
              severity="info"
              outlined
              @click="printReorderRule"
            />
            <Button
              label="Export"
              icon="pi pi-download"
              severity="secondary"
              outlined
              @click="exportReorderRule"
            />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <ProgressSpinner />
      </div>

      <!-- Reorder Rule Details -->
      <div v-else-if="reorderRule" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
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
                  <label class="block text-sm font-medium text-gray-700">Product</label>
                  <p class="text-lg font-semibold text-gray-900">{{ reorderRule.product?.name }}</p>
                  <p class="text-sm text-gray-600">{{ reorderRule.product?.code }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Warehouse</label>
                  <p class="text-lg font-semibold text-gray-900">{{ reorderRule.warehouse?.name }}</p>
                  <p class="text-sm text-gray-600">{{ reorderRule.warehouse?.code }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Trigger Type</label>
                  <Tag
                    :value="reorderRule.trigger_type"
                    :severity="getTriggerTypeSeverity(reorderRule.trigger_type)"
                    class="capitalize"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Status</label>
                  <Tag
                    :value="reorderRule.status"
                    :severity="getStatusSeverity(reorderRule.status)"
                    class="capitalize"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Priority</label>
                  <Tag
                    :value="reorderRule.priority"
                    :severity="getPrioritySeverity(reorderRule.priority)"
                    class="capitalize"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Auto Order</label>
                  <p class="text-lg font-semibold text-gray-900 capitalize">{{ reorderRule.auto_order?.replace('_', ' ') }}</p>
                </div>
              </div>
            </template>
          </Card>

          <!-- Stock Levels -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-chart-line"></i>
                Stock Levels & Reorder Settings
              </div>
            </template>
            <template #content>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                  <div class="text-3xl font-bold text-blue-600">{{ reorderRule.min_stock_level }}</div>
                  <div class="text-sm text-gray-600">Minimum Stock Level</div>
                </div>
                <div class="text-center">
                  <div class="text-3xl font-bold text-green-600">{{ reorderRule.max_stock_level }}</div>
                  <div class="text-sm text-gray-600">Maximum Stock Level</div>
                </div>
                <div class="text-center">
                  <div class="text-3xl font-bold text-purple-600">{{ reorderRule.reorder_quantity }}</div>
                  <div class="text-sm text-gray-600">Reorder Quantity</div>
                </div>
              </div>

              <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Lead Time</label>
                  <p class="text-lg font-semibold text-gray-900">{{ reorderRule.lead_time_days || 0 }} days</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Safety Stock Level</label>
                  <p class="text-lg font-semibold text-gray-900">{{ reorderRule.safety_stock_level || 0 }}</p>
                </div>
              </div>

              <!-- Current Stock Status -->
              <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                <h4 class="text-sm font-medium text-gray-800 mb-3">Current Stock Status</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <span class="text-sm text-gray-600">Current Stock:</span>
                    <span class="font-semibold ml-2">{{ currentStock }}</span>
                  </div>
                  <div>
                    <span class="text-sm text-gray-600">Available Stock:</span>
                    <span class="font-semibold ml-2">{{ availableStock }}</span>
                  </div>
                  <div>
                    <span class="text-sm text-gray-600">Stock Status:</span>
                    <Tag
                      :value="stockStatus"
                      :severity="getStockStatusSeverity(stockStatus)"
                      class="ml-2"
                    />
                  </div>
                </div>
              </div>
            </template>
          </Card>

          <!-- Trigger History -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-clock"></i>
                Trigger History
              </div>
            </template>
            <template #content>
              <DataTable
                :value="triggerHistory"
                class="p-datatable-sm"
                tableStyle="min-width: 50rem"
                :paginator="true"
                :rows="5"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
              >
                <Column field="triggered_at" header="Triggered At" style="min-width: 140px">
                  <template #body="slotProps">
                    {{ formatDateTime(slotProps.data.triggered_at) }}
                  </template>
                </Column>
                <Column field="trigger_reason" header="Reason" style="min-width: 150px">
                  <template #body="slotProps">
                    <Tag
                      :value="slotProps.data.trigger_reason"
                      :severity="getTriggerReasonSeverity(slotProps.data.trigger_reason)"
                      class="capitalize"
                    />
                  </template>
                </Column>
                <Column field="stock_level_at_trigger" header="Stock Level" style="min-width: 120px">
                  <template #body="slotProps">
                    {{ slotProps.data.stock_level_at_trigger }}
                  </template>
                </Column>
                <Column field="suggested_quantity" header="Suggested Qty" style="min-width: 120px">
                  <template #body="slotProps">
                    {{ slotProps.data.suggested_quantity }}
                  </template>
                </Column>
                <Column field="status" header="Status" style="min-width: 120px">
                  <template #body="slotProps">
                    <Tag
                      :value="slotProps.data.status"
                      :severity="getTriggerStatusSeverity(slotProps.data.status)"
                      class="capitalize"
                    />
                  </template>
                </Column>
              </DataTable>
            </template>
          </Card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Notifications -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-bell"></i>
                Notifications
              </div>
            </template>
            <template #content>
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-700">Notify on Trigger</span>
                  <i :class="reorderRule.notify_on_trigger ? 'pi pi-check text-green-600' : 'pi pi-times text-gray-400'"></i>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-700">Notify on Reorder</span>
                  <i :class="reorderRule.notify_on_reorder ? 'pi pi-check text-green-600' : 'pi pi-times text-gray-400'"></i>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-700">Notify on Low Stock</span>
                  <i :class="reorderRule.notify_on_low_stock ? 'pi pi-check text-green-600' : 'pi pi-times text-gray-400'"></i>
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
                  label="Trigger Manually"
                  icon="pi pi-play"
                  severity="info"
                  outlined
                  class="w-full"
                  @click="triggerManually"
                  :disabled="reorderRule.status !== 'active'"
                />
                <Button
                  label="View Product"
                  icon="pi pi-eye"
                  severity="secondary"
                  outlined
                  class="w-full"
                  @click="viewProduct"
                />
                <Button
                  label="View Warehouse"
                  icon="pi pi-building"
                  severity="secondary"
                  outlined
                  class="w-full"
                  @click="viewWarehouse"
                />
                <Button
                  label="Generate Report"
                  icon="pi pi-file-pdf"
                  severity="secondary"
                  outlined
                  class="w-full"
                  @click="generateReport"
                />
              </div>
            </template>
          </Card>

          <!-- Statistics -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-chart-bar"></i>
                Statistics
              </div>
            </template>
            <template #content>
              <div class="space-y-4">
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Times Triggered</span>
                  <span class="font-semibold">{{ triggerHistory.length }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Last Triggered</span>
                  <span class="font-semibold">{{ formatDateTime(reorderRule.last_triggered_at) }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Created</span>
                  <span class="font-semibold">{{ formatDateTime(reorderRule.created_at) }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Updated</span>
                  <span class="font-semibold">{{ formatDateTime(reorderRule.updated_at) }}</span>
                </div>
              </div>
            </template>
          </Card>

          <!-- Notes -->
          <Card v-if="reorderRule.notes">
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-sticky-note"></i>
                Notes
              </div>
            </template>
            <template #content>
              <p class="text-gray-700">{{ reorderRule.notes }}</p>
            </template>
          </Card>
        </div>
      </div>

      <!-- Not Found -->
      <div v-else class="text-center py-12">
        <i class="pi pi-exclamation-triangle text-4xl text-gray-400 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Reorder Rule Not Found</h3>
        <p class="text-gray-600 mb-4">The reorder rule you're looking for doesn't exist or has been deleted.</p>
        <Button label="Back to Reorder Rules" @click="goBack" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter, useRoute } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'

const loading = ref(false)
const reorderRule = ref<any>(null)
const triggerHistory = ref<any[]>([])
const toast = useToast()
const router = useRouter()
const route = useRoute()

const currentStock = computed(() => reorderRule.value?.product?.current_stock || 0)
const availableStock = computed(() => reorderRule.value?.product?.available_stock || 0)

const stockStatus = computed(() => {
  if (!reorderRule.value) return 'Unknown'
  const stock = currentStock.value
  if (stock === 0) return 'Out of Stock'
  if (stock <= reorderRule.value.min_stock_level) return 'Low Stock'
  if (stock >= reorderRule.value.max_stock_level) return 'Overstock'
  return 'In Stock'
})

const loadReorderRule = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getReorderRule(route.params.id as string)

    if (response.success) {
      reorderRule.value = response.data
      loadTriggerHistory()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to load reorder rule',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load reorder rule',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadTriggerHistory = async () => {
  // This would typically load from a trigger history endpoint
  // For now, we'll create mock history based on the reorder rule data
  triggerHistory.value = [
    ...(reorderRule.value.last_triggered_at ? [{
      triggered_at: reorderRule.value.last_triggered_at,
      trigger_reason: reorderRule.value.trigger_type,
      stock_level_at_trigger: currentStock.value,
      suggested_quantity: reorderRule.value.reorder_quantity,
      status: 'completed'
    }] : [])
  ]
}

const goBack = () => {
  router.push({ name: 'inventory.reorder-rules.index' })
}

const editReorderRule = () => {
  router.push({ name: 'inventory.reorder-rules.edit', params: { id: route.params.id } })
}

const toggleStatus = async () => {
  const newStatus = reorderRule.value.status === 'active' ? 'inactive' : 'active'

  try {
    const response = await inventoryService.updateReorderRule(route.params.id as string, { status: newStatus })

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `Reorder rule ${newStatus === 'active' ? 'activated' : 'deactivated'} successfully`,
        life: 3000
      })
      reorderRule.value.status = newStatus
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to update reorder rule status',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to update reorder rule status',
      life: 3000
    })
  }
}

const triggerManually = () => {
  // This would trigger the reorder rule manually
  toast.add({
    severity: 'info',
    summary: 'Info',
    detail: 'Manual trigger functionality would be implemented here',
    life: 3000
  })
}

const viewProduct = () => {
  // Navigate to product detail page
  toast.add({
    severity: 'info',
    summary: 'Info',
    detail: 'Product detail navigation would be implemented here',
    life: 3000
  })
}

const viewWarehouse = () => {
  // Navigate to warehouse detail page
  toast.add({
    severity: 'info',
    summary: 'Info',
    detail: 'Warehouse detail navigation would be implemented here',
    life: 3000
  })
}

const printReorderRule = () => {
  window.print()
}

const exportReorderRule = () => {
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

const formatDateTime = (dateTime: string) => {
  if (!dateTime) return '-'
  return new Date(dateTime).toLocaleString()
}

const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'active': return 'success'
    case 'inactive': return 'secondary'
    default: return 'secondary'
  }
}

const getTriggerTypeSeverity = (triggerType: string) => {
  switch (triggerType) {
    case 'min_stock': return 'warning'
    case 'max_stock': return 'info'
    case 'reorder_point': return 'success'
    case 'demand_forecast': return 'primary'
    default: return 'secondary'
  }
}

const getPrioritySeverity = (priority: string) => {
  switch (priority) {
    case 'low': return 'secondary'
    case 'medium': return 'info'
    case 'high': return 'warning'
    case 'critical': return 'danger'
    default: return 'secondary'
  }
}

const getStockStatusSeverity = (status: string) => {
  switch (status) {
    case 'Out of Stock': return 'danger'
    case 'Low Stock': return 'warning'
    case 'Overstock': return 'info'
    case 'In Stock': return 'success'
    default: return 'secondary'
  }
}

const getTriggerReasonSeverity = (reason: string) => {
  switch (reason) {
    case 'min_stock': return 'warning'
    case 'max_stock': return 'info'
    case 'reorder_point': return 'success'
    case 'demand_forecast': return 'primary'
    case 'manual': return 'secondary'
    default: return 'secondary'
  }
}

const getTriggerStatusSeverity = (status: string) => {
  switch (status) {
    case 'pending': return 'warning'
    case 'completed': return 'success'
    case 'failed': return 'danger'
    case 'cancelled': return 'secondary'
    default: return 'secondary'
  }
}

onMounted(() => {
  loadReorderRule()
})
</script>