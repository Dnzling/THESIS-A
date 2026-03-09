<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-6xl mx-auto">
      <!-- Header -->
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <Button
              icon="pi pi-arrow-left"
              severity="secondary"
              text
              @click="goBack"
              v-tooltip.top="'Back to Batches'"
            />
            <div>
              <h1 class="text-3xl font-bold text-gray-800">{{ batch?.batch_number }}</h1>
              <p class="text-gray-600 mt-1">Batch details and expiry tracking information</p>
            </div>
          </div>
          <div class="flex gap-2">
            <Button
              label="Print Labels"
              icon="pi pi-print"
              severity="secondary"
              outlined
              @click="printLabels"
            />
            <Button
              label="Edit"
              icon="pi pi-pencil"
              severity="warning"
              @click="editBatch"
            />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <ProgressSpinner />
      </div>

      <!-- Batch Details -->
      <div v-else-if="batch" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Basic Information -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-info-circle"></i>
                Batch Information
              </div>
            </template>
            <template #content>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Batch Number</label>
                  <p class="text-lg font-semibold text-blue-600">{{ batch.batch_number }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Product</label>
                  <div>
                    <p class="text-lg font-semibold text-gray-900">{{ batch.product?.name }}</p>
                    <p class="text-sm text-gray-600">{{ batch.product?.code }}</p>
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Status</label>
                  <Tag
                    :value="batch.status"
                    :severity="getStatusSeverity(batch.status)"
                    class="capitalize"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Total Quantity</label>
                  <p class="text-lg font-semibold text-gray-900">{{ batch.quantity || 0 }} {{ batch.unit?.name }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Remaining Quantity</label>
                  <p class="text-lg font-semibold text-green-600">{{ batch.remaining_quantity || 0 }} {{ batch.unit?.name }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Manufacture Date</label>
                  <p class="text-lg font-semibold text-gray-900">{{ formatDate(batch.manufacture_date) }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Expiry Date</label>
                  <div v-if="batch.expiry_date">
                    <p class="text-lg font-semibold" :class="isExpired(batch.expiry_date) ? 'text-red-600' : 'text-green-600'">
                      {{ formatDate(batch.expiry_date) }}
                    </p>
                    <p class="text-sm" :class="isExpired(batch.expiry_date) ? 'text-red-600' : 'text-green-600'">
                      {{ isExpired(batch.expiry_date) ? 'Expired' : 'Valid' }}
                    </p>
                  </div>
                  <div v-else class="text-gray-500 italic">No expiry date</div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Cost Price</label>
                  <p class="text-lg font-semibold text-green-600">${{ batch.cost_price?.toFixed(2) }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Supplier</label>
                  <p class="text-lg font-semibold text-gray-900">{{ batch.supplier || 'Not specified' }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Storage Conditions</label>
                  <p class="text-gray-700">{{ batch.storage_conditions || 'Not specified' }}</p>
                </div>
              </div>
            </template>
          </Card>

          <!-- Quality Control -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-check-circle"></i>
                Quality Control
              </div>
            </template>
            <template #content>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Quality Check Date</label>
                  <p class="text-lg font-semibold text-gray-900">{{ formatDate(batch.quality_check_date) }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Quality Status</label>
                  <Tag
                    v-if="batch.quality_status"
                    :value="batch.quality_status"
                    :severity="getQualitySeverity(batch.quality_status)"
                    class="capitalize"
                  />
                  <div v-else class="text-gray-500 italic">Not checked</div>
                </div>
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Quality Notes</label>
                  <p class="text-gray-700">{{ batch.quality_notes || 'No quality notes available' }}</p>
                </div>
              </div>
            </template>
          </Card>

          <!-- Serial Numbers -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-list"></i>
                Serial Numbers ({{ serialNumbers.length }})
              </div>
            </template>
            <template #content>
              <DataTable
                :value="serialNumbers"
                :loading="loadingSerialNumbers"
                class="p-datatable-sm"
                striped-rows
                show-gridlines
                responsive-layout="scroll"
                :paginator="serialNumbers.length > 10"
                :rows="10"
              >
                <template #empty>
                  <div class="text-center py-8">
                    <i class="pi pi-inbox text-4xl text-gray-400 mb-4"></i>
                    <p class="text-gray-600">No serial numbers in this batch</p>
                  </div>
                </template>

                <Column field="serial_number" header="Serial Number">
                  <template #body="slotProps">
                    <div class="font-medium text-blue-600">{{ slotProps.data.serial_number }}</div>
                  </template>
                </Column>

                <Column field="status" header="Status">
                  <template #body="slotProps">
                    <Tag
                      :value="slotProps.data.status"
                      :severity="getStatusSeverity(slotProps.data.status)"
                      class="capitalize"
                    />
                  </template>
                </Column>

                <Column field="condition" header="Condition">
                  <template #body="slotProps">
                    <Tag
                      :value="slotProps.data.condition"
                      :severity="getConditionSeverity(slotProps.data.condition)"
                      class="capitalize"
                    />
                  </template>
                </Column>

                <Column header="Actions">
                  <template #body="slotProps">
                    <Button
                      icon="pi pi-eye"
                      severity="info"
                      outlined
                      size="small"
                      v-tooltip.top="'View Serial Number'"
                      @click="viewSerialNumber(slotProps.data)"
                    />
                  </template>
                </Column>
              </DataTable>
            </template>
          </Card>

          <!-- Notes -->
          <Card v-if="batch.notes">
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-sticky-note"></i>
                Notes
              </div>
            </template>
            <template #content>
              <p class="text-gray-700">{{ batch.notes }}</p>
            </template>
          </Card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Expiry Status -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-clock"></i>
                Expiry Status
              </div>
            </template>
            <template #content>
              <div class="text-center">
                <div v-if="batch.expiry_date" class="mb-4">
                  <div class="text-3xl font-bold" :class="isExpired(batch.expiry_date) ? 'text-red-600' : 'text-green-600'">
                    {{ daysUntilExpiry }}
                  </div>
                  <div class="text-sm text-gray-600">
                    {{ isExpired(batch.expiry_date) ? 'Days Expired' : 'Days Until Expiry' }}
                  </div>
                </div>

                <!-- Expiry Progress Bar -->
                <div v-if="batch.expiry_date && !isExpired(batch.expiry_date)" class="mb-4">
                  <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                    <span>Time Remaining</span>
                    <span>{{ Math.round(expiryProgress) }}%</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-3">
                    <div
                      class="bg-gradient-to-r from-green-500 via-yellow-500 to-red-500 h-3 rounded-full transition-all duration-300"
                      :style="{ width: expiryProgress + '%' }"
                    ></div>
                  </div>
                </div>

                <div class="text-sm text-gray-600">
                  <div>Manufactured: {{ formatDate(batch.manufacture_date) }}</div>
                  <div v-if="batch.expiry_date">Expires: {{ formatDate(batch.expiry_date) }}</div>
                </div>
              </div>
            </template>
          </Card>

          <!-- Quick Actions -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-cog"></i>
                Quick Actions
              </div>
            </template>
            <template #content>
              <div class="space-y-3">
                <Button
                  label="Add Serial Numbers"
                  icon="pi pi-plus"
                  severity="success"
                  outlined
                  class="w-full"
                  @click="addSerialNumbers"
                />
                <Button
                  label="Update Quality Check"
                  icon="pi pi-check-circle"
                  severity="info"
                  outlined
                  class="w-full"
                  @click="updateQualityCheck"
                />
                <Button
                  label="Mark as Expired"
                  icon="pi pi-ban"
                  severity="danger"
                  outlined
                  class="w-full"
                  @click="markAsExpired"
                  :disabled="batch.status === 'expired'"
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
                  <span class="text-sm text-gray-600">Age</span>
                  <span class="font-semibold">{{ ageInDays }} days</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Utilization</span>
                  <span class="font-semibold">{{ utilizationPercentage.toFixed(1) }}%</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Serial Numbers</span>
                  <span class="font-semibold">{{ serialNumbers.length }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Total Value</span>
                  <span class="font-semibold text-green-600">${{ totalValue.toFixed(2) }}</span>
                </div>
              </div>
            </template>
          </Card>
        </div>
      </div>

      <!-- Not Found -->
      <div v-else class="text-center py-12">
        <i class="pi pi-exclamation-triangle text-4xl text-gray-400 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Batch Not Found</h3>
        <p class="text-gray-600 mb-4">The batch you're looking for doesn't exist or has been deleted.</p>
        <Button label="Back to Batches" @click="goBack" />
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
const loadingSerialNumbers = ref(false)
const batch = ref<any>(null)
const serialNumbers = ref<any[]>([])
const toast = useToast()
const router = useRouter()
const route = useRoute()

const ageInDays = computed(() => {
  if (!batch.value?.manufacture_date) return 0
  const manufactureDate = new Date(batch.value.manufacture_date)
  const now = new Date()
  const diffTime = Math.abs(now.getTime() - manufactureDate.getTime())
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
})

const daysUntilExpiry = computed(() => {
  if (!batch.value?.expiry_date) return 0
  const expiryDate = new Date(batch.value.expiry_date)
  const now = new Date()
  const diffTime = expiryDate.getTime() - now.getTime()
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
})

const expiryProgress = computed(() => {
  if (!batch.value?.manufacture_date || !batch.value?.expiry_date) return 0
  const manufactureDate = new Date(batch.value.manufacture_date)
  const expiryDate = new Date(batch.value.expiry_date)
  const now = new Date()

  const totalDuration = expiryDate.getTime() - manufactureDate.getTime()
  const elapsed = now.getTime() - manufactureDate.getTime()

  if (totalDuration <= 0) return 100
  return Math.min((elapsed / totalDuration) * 100, 100)
})

const utilizationPercentage = computed(() => {
  if (!batch.value?.quantity || batch.value.quantity === 0) return 0
  const used = batch.value.quantity - (batch.value.remaining_quantity || 0)
  return (used / batch.value.quantity) * 100
})

const totalValue = computed(() => {
  const remaining = batch.value?.remaining_quantity || 0
  const costPrice = batch.value?.cost_price || 0
  return remaining * costPrice
})

const loadBatch = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getBatch(route.params.id as string)

    if (response.success) {
      batch.value = response.data
      loadSerialNumbers()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to load batch',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load batch',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadSerialNumbers = async () => {
  loadingSerialNumbers.value = true
  try {
    const response = await inventoryService.getSerialNumbers({
      batch_id: route.params.id,
      per_page: 1000
    })

    if (response.success) {
      serialNumbers.value = response.data || []
    }
  } catch (error) {
    // Ignore serial numbers loading errors
  } finally {
    loadingSerialNumbers.value = false
  }
}

const goBack = () => {
  router.push({ name: 'inventory.batches.index' })
}

const editBatch = () => {
  router.push({
    name: 'inventory.batches.edit',
    params: { id: route.params.id }
  })
}

const printLabels = () => {
  // This would open a label printing dialog
  toast.add({
    severity: 'info',
    summary: 'Info',
    detail: 'Label printing functionality would be implemented here',
    life: 3000
  })
}

const addSerialNumbers = () => {
  // This would open a dialog to add serial numbers to the batch
  toast.add({
    severity: 'info',
    summary: 'Info',
    detail: 'Add serial numbers functionality would be implemented here',
    life: 3000
  })
}

const updateQualityCheck = () => {
  // This would open a dialog to update quality check information
  toast.add({
    severity: 'info',
    summary: 'Info',
    detail: 'Update quality check functionality would be implemented here',
    life: 3000
  })
}

const markAsExpired = async () => {
  try {
    const response = await inventoryService.updateBatch(batch.value.id, {
      status: 'expired'
    })

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Batch marked as expired',
        life: 3000
      })
      batch.value.status = 'expired'
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to update batch',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to update batch',
      life: 3000
    })
  }
}

const generateReport = () => {
  // This would generate and download a batch report
  toast.add({
    severity: 'info',
    summary: 'Info',
    detail: 'Report generation functionality would be implemented here',
    life: 3000
  })
}

const viewSerialNumber = (serialNumber: any) => {
  router.push({
    name: 'inventory.serial-numbers.show',
    params: { id: serialNumber.id }
  })
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString()
}

const isExpired = (expiryDate: string) => {
  if (!expiryDate) return false
  return new Date(expiryDate) < new Date()
}

const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'available': return 'success'
    case 'sold': return 'info'
    case 'reserved': return 'warning'
    case 'damaged': return 'danger'
    case 'lost': return 'danger'
    case 'active': return 'success'
    case 'expired': return 'danger'
    case 'recalled': return 'danger'
    case 'quarantined': return 'warning'
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

const getQualitySeverity = (quality: string) => {
  switch (quality) {
    case 'passed': return 'success'
    case 'failed': return 'danger'
    case 'pending': return 'warning'
    case 'not_required': return 'secondary'
    default: return 'secondary'
  }
}

onMounted(() => {
  loadBatch()
})
</script>