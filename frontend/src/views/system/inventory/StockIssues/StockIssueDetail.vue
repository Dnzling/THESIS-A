<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-6xl mx-auto">
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-800">Stock Issue Details</h1>
            <p class="text-gray-600 mt-1">Reference: {{ stockIssue?.reference_number }}</p>
          </div>
          <div class="flex gap-3">
            <Button
              v-if="stockIssue?.status === 'pending'"
              label="Edit"
              severity="secondary"
              @click="editStockIssue"
              :disabled="loading"
            />
            <Button
              v-if="stockIssue?.status === 'pending'"
              label="Cancel Issue"
              severity="danger"
              @click="confirmCancel"
              :disabled="loading"
            />
            <Button
              label="Back to List"
              severity="secondary"
              @click="goBack"
              :disabled="loading"
            />
          </div>
        </div>
      </div>

      <div v-if="loading" class="flex justify-center py-12">
        <ProgressSpinner />
      </div>

      <div v-else-if="stockIssue" class="space-y-6">
        <!-- Issue Overview -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2">
            <Card>
              <template #title>Issue Information</template>
              <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reference Number</label>
                    <p class="text-gray-900 font-mono">{{ stockIssue.reference_number }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <Tag
                      :value="stockIssue.type"
                      :severity="getTypeSeverity(stockIssue.type)"
                      class="capitalize"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <Tag
                      :value="stockIssue.status"
                      :severity="getStatusSeverity(stockIssue.status)"
                      class="capitalize"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Issued Date</label>
                    <p class="text-gray-900">{{ formatDate(stockIssue.issued_at) }}</p>
                  </div>
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <p class="text-gray-900">{{ stockIssue.notes || 'No notes provided' }}</p>
                  </div>
                </div>
              </template>
            </Card>
          </div>

          <div>
            <Card>
              <template #title>Cost Summary</template>
              <template #content>
                <div class="space-y-4">
                  <div>
                    <div class="text-2xl font-bold text-red-600">-{{ stockIssue.quantity }}</div>
                    <div class="text-sm text-gray-600">Quantity Issued</div>
                  </div>
                  <div>
                    <div class="text-2xl font-bold text-blue-600">
                      ${{ stockIssue.unit_cost?.toFixed(2) || '0.00' }}
                    </div>
                    <div class="text-sm text-gray-600">Unit Cost</div>
                  </div>
                  <div>
                    <div class="text-2xl font-bold text-red-600">
                      ${{ stockIssue.total_cost?.toFixed(2) || '0.00' }}
                    </div>
                    <div class="text-sm text-gray-600">Total Cost</div>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>

        <!-- Product & Location Details -->
        <Card>
          <template #title>Product & Location Details</template>
          <template #content>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
                <p class="text-gray-900 font-medium">{{ stockIssue.product?.name }}</p>
                <p class="text-sm text-gray-600">SKU: {{ stockIssue.product?.sku }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Warehouse</label>
                <p class="text-gray-900">{{ stockIssue.warehouse?.name }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                <p class="text-gray-900">{{ stockIssue.location?.name || 'N/A' }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                <p class="text-gray-900">{{ stockIssue.product?.unit?.name }}</p>
              </div>
            </div>
          </template>
        </Card>

        <!-- Stock Movement History -->
        <Card>
          <template #title>Stock Movement History</template>
          <template #content>
            <DataTable
              :value="stockMovements"
              :loading="stockMovementsLoading"
              paginator
              :rows="5"
              :rowsPerPageOptions="[5, 10]"
              tableStyle="min-width: 50rem"
              class="p-datatable-sm"
            >
              <Column field="type" header="Movement Type" style="width: 150px">
                <template #body="slotProps">
                  <Tag
                    :value="slotProps.data.type"
                    :severity="getMovementTypeSeverity(slotProps.data.type)"
                    class="capitalize"
                  />
                </template>
              </Column>
              <Column field="quantity" header="Quantity" style="width: 100px">
                <template #body="slotProps">
                  <span :class="slotProps.data.quantity > 0 ? 'text-green-600' : 'text-red-600'">
                    {{ slotProps.data.quantity > 0 ? '+' : '' }}{{ slotProps.data.quantity }}
                  </span>
                </template>
              </Column>
              <Column field="reference" header="Reference" style="width: 150px" />
              <Column field="notes" header="Notes" />
              <Column field="created_at" header="Date" style="width: 150px">
                <template #body="slotProps">
                  {{ formatDate(slotProps.data.created_at) }}
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>

        <!-- Serial Numbers (if applicable) -->
        <Card v-if="stockIssue.product?.track_serial_numbers && serialNumbers.length > 0">
          <template #title>Serial Numbers Issued</template>
          <template #content>
            <DataTable
              :value="serialNumbers"
              :loading="serialNumbersLoading"
              paginator
              :rows="10"
              :rowsPerPageOptions="[5, 10, 25]"
              tableStyle="min-width: 50rem"
              class="p-datatable-sm"
            >
              <Column field="serial_number" header="Serial Number" style="width: 200px" />
              <Column field="batch_number" header="Batch" style="width: 150px" />
              <Column field="status" header="Status" style="width: 120px">
                <template #body="slotProps">
                  <Tag
                    :value="slotProps.data.status"
                    :severity="getSerialStatusSeverity(slotProps.data.status)"
                    class="capitalize"
                  />
                </template>
              </Column>
              <Column field="location" header="Location" />
              <Column field="created_at" header="Created" style="width: 150px">
                <template #body="slotProps">
                  {{ formatDate(slotProps.data.created_at) }}
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>
      </div>

      <div v-else class="text-center py-12">
        <p class="text-gray-500">Stock issue not found</p>
      </div>
    </div>
  </div>

  <!-- Cancel Confirmation Dialog -->
  <Dialog
    v-model:visible="cancelDialog"
    modal
    header="Confirm Cancellation"
    :style="{ width: '450px' }"
  >
    <div class="flex items-center gap-3">
      <i class="pi pi-exclamation-triangle text-orange-500 text-2xl"></i>
      <div>
        <p class="font-medium">Are you sure you want to cancel this stock issue?</p>
        <p class="text-sm text-gray-600 mt-1">
          Reference: <strong>{{ stockIssue?.reference_number }}</strong>
        </p>
        <p class="text-sm text-gray-600 mt-1">
          This will reverse the stock reduction and mark the issue as cancelled.
        </p>
      </div>
    </div>
    <template #footer>
      <Button
        label="Cancel"
        severity="secondary"
        @click="cancelDialog = false"
      />
      <Button
        label="Confirm Cancellation"
        severity="danger"
        @click="cancelStockIssue"
        :loading="cancelLoading"
      />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter, useRoute } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'

const loading = ref(true)
const cancelLoading = ref(false)
const cancelDialog = ref(false)
const stockMovementsLoading = ref(false)
const serialNumbersLoading = ref(false)
const stockIssue = ref<any>(null)
const stockMovements = ref<any[]>([])
const serialNumbers = ref<any[]>([])
const toast = useToast()
const router = useRouter()
const route = useRoute()

const loadStockIssue = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getStockIssue(route.params.id as string)
    if (response.success) {
      stockIssue.value = response.data
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Failed to load stock issue details',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load stock issue details',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadStockMovements = async () => {
  if (!stockIssue.value?.product) return

  stockMovementsLoading.value = true
  try {
    const response = await inventoryService.getProductStockMovements(stockIssue.value.product.id, {
      reference: stockIssue.value.reference_number
    })
    if (response.success) {
      stockMovements.value = response.data || []
    }
  } catch (error) {
    console.error('Failed to load stock movements', error)
  } finally {
    stockMovementsLoading.value = false
  }
}

const loadSerialNumbers = async () => {
  if (!stockIssue.value?.product?.track_serial_numbers) return

  serialNumbersLoading.value = true
  try {
    const response = await inventoryService.getProductSerialNumbers(stockIssue.value.product.id, {
      status: 'sold',
      issued_reference: stockIssue.value.reference_number
    })
    if (response.success) {
      serialNumbers.value = response.data || []
    }
  } catch (error) {
    console.error('Failed to load serial numbers', error)
  } finally {
    serialNumbersLoading.value = false
  }
}

const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'completed': return 'success'
    case 'pending': return 'warning'
    case 'cancelled': return 'danger'
    default: return 'secondary'
  }
}

const getTypeSeverity = (type: string) => {
  switch (type) {
    case 'sale': return 'success'
    case 'damage': return 'danger'
    case 'loss': return 'warning'
    case 'transfer': return 'info'
    case 'return': return 'secondary'
    default: return 'secondary'
  }
}

const getMovementTypeSeverity = (type: string) => {
  switch (type) {
    case 'in': return 'success'
    case 'out': return 'danger'
    case 'adjustment': return 'warning'
    default: return 'secondary'
  }
}

const getSerialStatusSeverity = (status: string) => {
  switch (status) {
    case 'available': return 'success'
    case 'sold': return 'info'
    case 'damaged': return 'danger'
    case 'returned': return 'warning'
    default: return 'secondary'
  }
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const editStockIssue = () => {
  router.push({ name: 'inventory.stock-issues.edit', params: { id: stockIssue.value.id } })
}

const confirmCancel = () => {
  cancelDialog.value = true
}

const cancelStockIssue = async () => {
  if (!stockIssue.value) return

  cancelLoading.value = true
  try {
    const response = await inventoryService.cancelStockIssue(stockIssue.value.id)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Stock issue cancelled successfully',
        life: 3000
      })
      cancelDialog.value = false
      loadStockIssue() // Reload to get updated status
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to cancel stock issue',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to cancel stock issue',
      life: 3000
    })
  } finally {
    cancelLoading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'inventory.stock-issues' })
}

onMounted(async () => {
  await loadStockIssue()
  if (stockIssue.value) {
    loadStockMovements()
    loadSerialNumbers()
  }
})
</script>