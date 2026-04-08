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
              v-tooltip.top="'Back to Serial Numbers'"
            />
            <div>
              <h1 class="text-3xl font-bold text-gray-800">{{ serialNumber?.serial_number }}</h1>
              <p class="text-gray-600 mt-1">Serial number details and tracking information</p>
            </div>
          </div>
          <div class="flex gap-2">
            <Button
              label="Print QR Code"
              icon="pi pi-qrcode"
              severity="secondary"
              outlined
              @click="printQRCode"
            />
            <Button
              label="Edit"
              icon="pi pi-pencil"
              severity="warning"
              @click="editSerialNumber"
            />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <ProgressSpinner />
      </div>

      <!-- Serial Number Details -->
      <div v-else-if="serialNumber" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Basic Information -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-info-circle"></i>
                Serial Number Information
              </div>
            </template>
            <template #content>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Serial Number</label>
                  <p class="text-lg font-semibold text-blue-600">{{ serialNumber.serial_number }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Product</label>
                  <div>
                    <p class="text-lg font-semibold text-gray-900">{{ serialNumber.product?.name }}</p>
                    <p class="text-sm text-gray-600">{{ serialNumber.product?.code }}</p>
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Status</label>
                  <Tag
                    :value="serialNumber.status"
                    :severity="getStatusSeverity(serialNumber.status)"
                    class="capitalize"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Condition</label>
                  <Tag
                    :value="serialNumber.condition"
                    :severity="getConditionSeverity(serialNumber.condition)"
                    class="capitalize"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Warehouse</label>
                  <div>
                    <p class="text-lg font-semibold text-gray-900">{{ serialNumber.warehouse?.name }}</p>
                    <p class="text-sm text-gray-600">{{ serialNumber.location?.name }}</p>
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Batch</label>
                  <div v-if="serialNumber.batch">
                    <p class="text-lg font-semibold text-gray-900">{{ serialNumber.batch.batch_number }}</p>
                    <p class="text-sm text-gray-600">Expiry: {{ formatDate(serialNumber.batch.expiry_date) }}</p>
                  </div>
                  <div v-else class="text-gray-500 italic">No batch assigned</div>
                </div>
              </div>
            </template>
          </Card>

          <!-- Purchase & Warranty Information -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-shopping-cart"></i>
                Purchase & Warranty Information
              </div>
            </template>
            <template #content>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Purchase Date</label>
                  <p class="text-lg font-semibold text-gray-900">{{ formatDate(serialNumber.purchase_date) }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Purchase Price</label>
                  <p class="text-lg font-semibold text-green-600">${{ serialNumber.purchase_price?.toFixed(2) }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Supplier</label>
                  <p class="text-lg font-semibold text-gray-900">{{ serialNumber.supplier || 'Not specified' }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Warranty Expiry</label>
                  <div v-if="serialNumber.warranty_expiry">
                    <p class="text-lg font-semibold" :class="isWarrantyExpired(serialNumber.warranty_expiry) ? 'text-red-600' : 'text-green-600'">
                      {{ formatDate(serialNumber.warranty_expiry) }}
                    </p>
                    <p class="text-sm" :class="isWarrantyExpired(serialNumber.warranty_expiry) ? 'text-red-600' : 'text-green-600'">
                      {{ isWarrantyExpired(serialNumber.warranty_expiry) ? 'Expired' : 'Active' }}
                    </p>
                  </div>
                  <div v-else class="text-gray-500 italic">No warranty</div>
                </div>
              </div>

              <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                <p class="text-gray-700">{{ serialNumber.notes || 'No notes available' }}</p>
              </div>
            </template>
          </Card>

          <!-- Movement History -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-history"></i>
                Movement History
              </div>
            </template>
            <template #content>
              <div class="space-y-4">
                <div v-for="movement in movementHistory" :key="movement.id" class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg">
                  <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="pi pi-arrow-right text-blue-600"></i>
                  </div>
                  <div class="flex-1">
                    <div class="flex items-center justify-between">
                      <p class="font-medium text-gray-900">{{ movement.type }}</p>
                      <p class="text-sm text-gray-500">{{ formatDateTime(movement.created_at) }}</p>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">{{ movement.description }}</p>
                    <div class="flex items-center gap-4 mt-2">
                      <div class="text-sm">
                        <span class="text-gray-500">From:</span>
                        <span class="font-medium">{{ movement.from_warehouse?.name || 'N/A' }}</span>
                      </div>
                      <div class="text-sm">
                        <span class="text-gray-500">To:</span>
                        <span class="font-medium">{{ movement.to_warehouse?.name || 'N/A' }}</span>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-if="movementHistory.length === 0" class="text-center text-gray-500 py-8">
                  No movement history available
                </div>
              </div>
            </template>
          </Card>

          <!-- Custom Fields -->
          <Card v-if="customFields.length > 0">
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-tags"></i>
                Custom Fields
              </div>
            </template>
            <template #content>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="field in customFields" :key="field.id">
                  <label class="block text-sm font-medium text-gray-700">{{ field.name }}</label>
                  <p class="text-gray-900">{{ serialNumber.custom_fields?.[field.id] || 'Not set' }}</p>
                </div>
              </div>
            </template>
          </Card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- QR Code -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-qrcode"></i>
                QR Code
              </div>
            </template>
            <template #content>
              <div class="text-center">
                <div class="bg-white p-4 rounded-lg border-2 border-gray-200 inline-block">
                  <!-- QR Code would be generated here -->
                  <div class="w-32 h-32 bg-gray-100 flex items-center justify-center">
                    <i class="pi pi-qrcode text-4xl text-gray-400"></i>
                  </div>
                </div>
                <p class="text-sm text-gray-600 mt-2">{{ serialNumber.serial_number }}</p>
                <Button
                  label="Download QR"
                  icon="pi pi-download"
                  severity="secondary"
                  outlined
                  size="small"
                  class="mt-2"
                  @click="downloadQR"
                />
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
                  label="Move to Location"
                  icon="pi pi-arrow-right"
                  severity="info"
                  outlined
                  class="w-full"
                  @click="moveSerialNumber"
                />
                <Button
                  label="Update Condition"
                  icon="pi pi-refresh"
                  severity="warning"
                  outlined
                  class="w-full"
                  @click="updateCondition"
                />
                <Button
                  label="Mark as Sold"
                  icon="pi pi-shopping-cart"
                  severity="success"
                  outlined
                  class="w-full"
                  @click="markAsSold"
                  :disabled="serialNumber.status === 'sold'"
                />
                <Button
                  label="Report Issue"
                  icon="pi pi-exclamation-triangle"
                  severity="danger"
                  outlined
                  class="w-full"
                  @click="reportIssue"
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
                  <span class="text-sm text-gray-600">Movements</span>
                  <span class="font-semibold">{{ movementHistory.length }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Current Value</span>
                  <span class="font-semibold text-green-600">${{ currentValue.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Depreciation</span>
                  <span class="font-semibold text-red-600">{{ depreciationPercentage.toFixed(1) }}%</span>
                </div>
              </div>
            </template>
          </Card>
        </div>
      </div>

      <!-- Not Found -->
      <div v-else class="text-center py-12">
        <i class="pi pi-exclamation-triangle text-4xl text-gray-400 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Serial Number Not Found</h3>
        <p class="text-gray-600 mb-4">The serial number you're looking for doesn't exist or has been deleted.</p>
        <Button label="Back to Serial Numbers" @click="goBack" />
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
const serialNumber = ref<any>(null)
const movementHistory = ref<any[]>([])
const customFields = ref<any[]>([])
const toast = useToast()
const router = useRouter()
const route = useRoute()

const ageInDays = computed(() => {
  if (!serialNumber.value?.purchase_date) return 0
  const purchaseDate = new Date(serialNumber.value.purchase_date)
  const now = new Date()
  const diffTime = Math.abs(now.getTime() - purchaseDate.getTime())
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
})

const currentValue = computed(() => {
  if (!serialNumber.value?.purchase_price) return 0
  // Simple depreciation calculation (assuming 10% annual depreciation)
  const depreciationRate = 0.1
  const ageInYears = ageInDays.value / 365
  return serialNumber.value.purchase_price * Math.pow(1 - depreciationRate, ageInYears)
})

const depreciationPercentage = computed(() => {
  if (!serialNumber.value?.purchase_price || serialNumber.value.purchase_price === 0) return 0
  const originalValue = serialNumber.value.purchase_price
  const depreciatedValue = originalValue - currentValue.value
  return (depreciatedValue / originalValue) * 100
})

const loadSerialNumber = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getSerialNumber(route.params.id as string)

    if (response.success) {
      serialNumber.value = response.data
      loadRelatedData()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to load serial number',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load serial number',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadRelatedData = async () => {
  // Load movement history
  try {
    const movementsResponse = await inventoryService.getStockMovements({
      serial_number: serialNumber.value.serial_number,
      limit: 20
    })
    if (movementsResponse.success) {
      movementHistory.value = movementsResponse.data || []
    }
  } catch (error) {
    // Ignore errors for related data
  }

  // Load custom fields configuration
  // This would typically come from a settings endpoint
  customFields.value = [
    { id: 'color', name: 'Color', type: 'text' },
    { id: 'size', name: 'Size', type: 'text' },
    { id: 'weight', name: 'Weight', type: 'number' }
  ]
}

const goBack = () => {
  router.push({ name: 'inventory.serial-numbers.index' })
}

const editSerialNumber = () => {
  router.push({
    name: 'inventory.serial-numbers.edit',
    params: { id: route.params.id }
  })
}

const printQRCode = () => {
  // This would open a QR code printing dialog
  toast.add({
    severity: 'info',
    summary: 'Info',
    detail: 'QR code printing functionality would be implemented here',
    life: 3000
  })
}

const downloadQR = () => {
  // This would download the QR code image
  toast.add({
    severity: 'info',
    summary: 'Info',
    detail: 'QR code download functionality would be implemented here',
    life: 3000
  })
}

const moveSerialNumber = () => {
  // This would open a dialog to move the serial number to a different location
  toast.add({
    severity: 'info',
    summary: 'Info',
    detail: 'Move serial number functionality would be implemented here',
    life: 3000
  })
}

const updateCondition = () => {
  // This would open a dialog to update the condition
  toast.add({
    severity: 'info',
    summary: 'Info',
    detail: 'Update condition functionality would be implemented here',
    life: 3000
  })
}

const markAsSold = async () => {
  try {
    const response = await inventoryService.updateSerialNumber(serialNumber.value.id, {
      status: 'sold'
    })

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Serial number marked as sold',
        life: 3000
      })
      serialNumber.value.status = 'sold'
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to update serial number',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to update serial number',
      life: 3000
    })
  }
}

const reportIssue = () => {
  // This would open a dialog to report an issue with the serial number
  toast.add({
    severity: 'info',
    summary: 'Info',
    detail: 'Report issue functionality would be implemented here',
    life: 3000
  })
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString()
}

const formatDateTime = (dateTime: string) => {
  if (!dateTime) return '-'
  return new Date(dateTime).toLocaleString()
}

const isWarrantyExpired = (warrantyExpiry: string) => {
  if (!warrantyExpiry) return false
  return new Date(warrantyExpiry) < new Date()
}

const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'available': return 'success'
    case 'sold': return 'info'
    case 'reserved': return 'warning'
    case 'damaged': return 'danger'
    case 'lost': return 'danger'
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

onMounted(() => {
  loadSerialNumber()
})
</script>