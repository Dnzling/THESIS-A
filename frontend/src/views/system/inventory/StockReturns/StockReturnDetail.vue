<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <Button
              icon="pi pi-arrow-left"
              severity="secondary"
              text
              @click="goBack"
              v-tooltip.top="'Back to Stock Returns'"
            />
            <div>
              <h1 class="text-3xl font-bold text-gray-800">{{ stockReturn?.reference_number || 'Stock Return Details' }}</h1>
              <p class="text-gray-600 mt-1">Stock return transaction details</p>
            </div>
          </div>
          <div class="flex gap-2">
            <Button
              label="Edit"
              icon="pi pi-pencil"
              severity="warning"
              @click="editStockReturn"
              v-tooltip.top="'Edit Return'"
              :disabled="stockReturn?.status === 'completed'"
            />
            <Button
              label="Delete"
              icon="pi pi-trash"
              severity="danger"
              @click="confirmDelete"
              v-tooltip.top="'Delete Return'"
              :disabled="stockReturn?.status === 'completed'"
            />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <ProgressSpinner />
      </div>

      <!-- Stock Return Details -->
      <div v-else-if="stockReturn" class="space-y-6">
        <!-- Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <Card>
            <template #content>
              <div class="text-center">
                <i class="pi pi-tag text-blue-600 text-3xl mb-2"></i>
                <div class="text-2xl font-bold text-gray-800">{{ stockReturn.reference_number }}</div>
                <div class="text-sm text-gray-600">Reference Number</div>
              </div>
            </template>
          </Card>

          <Card>
            <template #content>
              <div class="text-center">
                <i class="pi pi-calendar text-green-600 text-3xl mb-2"></i>
                <div class="text-2xl font-bold text-gray-800">{{ formatDate(stockReturn.return_date) }}</div>
                <div class="text-sm text-gray-600">Return Date</div>
              </div>
            </template>
          </Card>

          <Card>
            <template #content>
              <div class="text-center">
                <i class="pi pi-box text-purple-600 text-3xl mb-2"></i>
                <div class="text-2xl font-bold text-gray-800">{{ stockReturn.total_quantity || 0 }}</div>
                <div class="text-sm text-gray-600">Total Quantity</div>
              </div>
            </template>
          </Card>

          <Card>
            <template #content>
              <div class="text-center">
                <i class="pi pi-dollar text-orange-600 text-3xl mb-2"></i>
                <div class="text-2xl font-bold text-gray-800">${{ stockReturn.total_value?.toFixed(2) || '0.00' }}</div>
                <div class="text-sm text-gray-600">Total Value</div>
              </div>
            </template>
          </Card>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Return Information -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <Card>
              <template #header>
                <div class="flex items-center gap-2">
                  <i class="pi pi-info-circle text-blue-600"></i>
                  <h3 class="text-lg font-semibold text-gray-800">Return Information</h3>
                </div>
              </template>
              <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reference Number</label>
                    <p class="text-gray-900">{{ stockReturn.reference_number }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Return Date</label>
                    <p class="text-gray-900">{{ formatDate(stockReturn.return_date) }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <Tag
                      :value="stockReturn.status"
                      :severity="getStatusSeverity(stockReturn.status)"
                      class="capitalize"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Return Reason</label>
                    <p class="text-gray-900 capitalize">{{ stockReturn.return_reason.replace('_', ' ') }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Processed By</label>
                    <div class="text-sm">
                      <div class="font-medium">{{ stockReturn.processed_by_user?.name }}</div>
                      <div class="text-gray-500">{{ stockReturn.processed_by_user?.email }}</div>
                    </div>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Created</label>
                    <p class="text-gray-900">{{ formatDateTime(stockReturn.created_at) }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Updated</label>
                    <p class="text-gray-900">{{ formatDateTime(stockReturn.updated_at) }}</p>
                  </div>
                </div>
              </template>
            </Card>

            <!-- Return Items -->
            <Card>
              <template #header>
                <div class="flex items-center gap-2">
                  <i class="pi pi-list text-green-600"></i>
                  <h3 class="text-lg font-semibold text-gray-800">Return Items</h3>
                </div>
              </template>
              <template #content>
                <DataTable
                  :value="stockReturn.items"
                  class="p-datatable-sm"
                  tableStyle="min-width: 50rem"
                >
                  <Column field="product.name" header="Product" style="min-width: 200px">
                    <template #body="slotProps">
                      <div class="text-sm">
                        <div class="font-medium">{{ slotProps.data.product?.name }}</div>
                        <div class="text-gray-500">{{ slotProps.data.product?.code }}</div>
                      </div>
                    </template>
                  </Column>
                  <Column field="quantity" header="Quantity" style="width: 100px" />
                  <Column field="unit_cost" header="Unit Cost" style="width: 120px">
                    <template #body="slotProps">
                      ${{ slotProps.data.unit_cost?.toFixed(2) }}
                    </template>
                  </Column>
                  <Column field="total_cost" header="Total Cost" style="width: 120px">
                    <template #body="slotProps">
                      ${{ slotProps.data.total_cost?.toFixed(2) }}
                    </template>
                  </Column>
                  <Column field="condition" header="Condition" style="width: 120px">
                    <template #body="slotProps">
                      <Tag
                        :value="slotProps.data.condition"
                        :severity="getConditionSeverity(slotProps.data.condition)"
                        class="capitalize"
                      />
                    </template>
                  </Column>
                  <Column field="notes" header="Notes" style="min-width: 150px" />
                </DataTable>
              </template>
            </Card>

            <!-- Notes -->
            <Card v-if="stockReturn.notes">
              <template #header>
                <div class="flex items-center gap-2">
                  <i class="pi pi-file-text text-orange-600"></i>
                  <h3 class="text-lg font-semibold text-gray-800">Notes</h3>
                </div>
              </template>
              <template #content>
                <p class="text-gray-900 whitespace-pre-wrap">{{ stockReturn.notes }}</p>
              </template>
            </Card>
          </div>

          <!-- Status Sidebar -->
          <div>
            <Card>
              <template #header>
                <div class="flex items-center gap-2">
                  <i class="pi pi-cog text-indigo-600"></i>
                  <h3 class="text-lg font-semibold text-gray-800">Status & Actions</h3>
                </div>
              </template>
              <template #content>
                <div class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Status</label>
                    <Tag
                      :value="stockReturn.status"
                      :severity="getStatusSeverity(stockReturn.status)"
                      class="capitalize text-lg px-3 py-2"
                    />
                  </div>

                  <!-- Status Actions -->
                  <div v-if="stockReturn.status === 'pending'" class="space-y-2">
                    <Button
                      label="Start Processing"
                      severity="info"
                      @click="updateStatus('processing')"
                      class="w-full"
                      :loading="statusLoading"
                    />
                    <Button
                      label="Cancel Return"
                      severity="danger"
                      @click="updateStatus('cancelled')"
                      class="w-full"
                      :loading="statusLoading"
                    />
                  </div>

                  <div v-if="stockReturn.status === 'processing'" class="space-y-2">
                    <Button
                      label="Complete Return"
                      severity="success"
                      @click="updateStatus('completed')"
                      class="w-full"
                      :loading="statusLoading"
                    />
                    <Button
                      label="Cancel Return"
                      severity="danger"
                      @click="updateStatus('cancelled')"
                      class="w-full"
                      :loading="statusLoading"
                    />
                  </div>

                  <!-- Print/Export Actions -->
                  <div class="border-t pt-4 space-y-2">
                    <Button
                      label="Print Return"
                      icon="pi pi-print"
                      severity="secondary"
                      @click="printReturn"
                      class="w-full"
                    />
                    <Button
                      label="Export PDF"
                      icon="pi pi-file-pdf"
                      severity="secondary"
                      @click="exportPDF"
                      class="w-full"
                    />
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>
      </div>

      <!-- Not Found -->
      <div v-else class="text-center py-12">
        <i class="pi pi-exclamation-triangle text-4xl text-gray-400 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Stock Return Not Found</h3>
        <p class="text-gray-600 mb-4">The stock return you're looking for doesn't exist or has been deleted.</p>
        <Button label="Back to Stock Returns" @click="goBack" />
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Dialog -->
  <Dialog
    v-model:visible="deleteDialog"
    modal
    header="Confirm Delete"
    :style="{ width: '450px' }"
  >
    <div class="flex items-center gap-3">
      <i class="pi pi-exclamation-triangle text-red-500 text-2xl"></i>
      <div>
        <p class="font-medium">Are you sure you want to delete this stock return?</p>
        <p class="text-sm text-gray-600 mt-1">
          Reference: <strong>{{ stockReturn?.reference_number }}</strong>
        </p>
        <p v-if="stockReturn?.status === 'completed'" class="text-sm text-red-600 mt-2">
          Warning: This return has been completed and may affect inventory records.
        </p>
      </div>
    </div>
    <template #footer>
      <Button
        label="Cancel"
        severity="secondary"
        @click="deleteDialog = false"
      />
      <Button
        label="Delete"
        severity="danger"
        @click="deleteStockReturn"
        :loading="deleteLoading"
      />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter, useRoute } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'

const loading = ref(false)
const deleteLoading = ref(false)
const statusLoading = ref(false)
const deleteDialog = ref(false)
const stockReturn = ref<any>(null)
const toast = useToast()
const router = useRouter()
const route = useRoute()

const loadStockReturn = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getStockReturn(route.params.id as string)

    if (response.success) {
      stockReturn.value = response.data
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to load stock return',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load stock return',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'inventory.stock-returns.index' })
}

const editStockReturn = () => {
  router.push({ name: 'inventory.stock-returns.edit', params: { id: route.params.id } })
}

const confirmDelete = () => {
  deleteDialog.value = true
}

const deleteStockReturn = async () => {
  deleteLoading.value = true
  try {
    const response = await inventoryService.deleteStockReturn(route.params.id as string)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Stock return deleted successfully',
        life: 3000
      })
      router.push({ name: 'inventory.stock-returns.index' })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to delete stock return',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to delete stock return',
      life: 3000
    })
  } finally {
    deleteLoading.value = false
  }
}

const updateStatus = async (newStatus: string) => {
  statusLoading.value = true
  try {
    const response = await inventoryService.updateStockReturn(route.params.id as string, {
      status: newStatus
    })

    if (response.success) {
      stockReturn.value = response.data
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `Stock return status updated to ${newStatus}`,
        life: 3000
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to update status',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to update status',
      life: 3000
    })
  } finally {
    statusLoading.value = false
  }
}

const printReturn = () => {
  window.print()
}

const exportPDF = () => {
  // Implementation for PDF export would go here
  toast.add({
    severity: 'info',
    summary: 'Info',
    detail: 'PDF export feature coming soon',
    life: 3000
  })
}

const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'completed': return 'success'
    case 'processing': return 'info'
    case 'pending': return 'warning'
    case 'cancelled': return 'danger'
    default: return 'secondary'
  }
}

const getConditionSeverity = (condition: string) => {
  switch (condition) {
    case 'new': return 'success'
    case 'good': return 'info'
    case 'fair': return 'warning'
    case 'poor': return 'danger'
    case 'damaged': return 'danger'
    default: return 'secondary'
  }
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDateTime = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  loadStockReturn()
})
</script>