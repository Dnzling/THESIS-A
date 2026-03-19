<template>
  <div class="max-w-4xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <!-- iOS-style Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <button 
          @click="goBack"
          class="w-10 h-10 rounded-full bg-white hover:bg-gray-100 flex items-center justify-center transition-colors shadow-sm border border-gray-200"
        >
          <i class="pi pi-chevron-left text-gray-600 text-lg"></i>
        </button>
        <div>
          <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">
            Shipment {{ shipment?.purchase_order?.po_number || '' }}
          </h1>
          <p class="text-sm text-gray-500 mt-1">Capture milestone logs for this delivery</p>
        </div>
      </div>
    </div>

    <!-- Shipment Overview Card -->
    <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <template #header>
        <div class="px-6 pt-6">
          <div class="flex items-center gap-2">
            <h2 class="text-lg font-semibold text-gray-900">Shipment Overview</h2>
          </div>
        </div>
      </template>
      <template #content>
        <div class="p-6 pt-0">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-50 rounded-xl p-4">
              <p class="text-xs text-gray-500 mb-1">Driver</p>
              <div class="flex items-center gap-2">
                <span class="font-medium text-gray-900">{{ shipment?.driver_name || 'N/A' }}</span>
              </div>
            </div>
            
            <div class="bg-gray-50 rounded-xl p-4">
              <p class="text-xs text-gray-500 mb-1">Expected Delivery</p>
              <div class="flex items-center gap-2">
                <span class="font-medium text-gray-900">{{ formatDate(shipment?.expected_delivery_date) }}</span>
              </div>
            </div>
            
            <div class="bg-gray-50 rounded-xl p-4">
              <p class="text-xs text-gray-500 mb-1">Current Status</p>
              <div class="flex items-center gap-2">
                <div :class="getStatusDot(shipment?.status)" class="w-2 h-2 rounded-full"></div>
                <Tag 
                  :value="formatStatus(shipment?.status)" 
                  :severity="statusSeverity(shipment?.status)"
                  class="rounded-full text-xs px-3 py-1"
                />
              </div>
            </div>
          </div>

          <!-- Additional Shipment Details -->
          <div v-if="shipment" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-blue-50/50 rounded-xl p-4 border border-blue-100">
              <p class="text-xs text-blue-600 mb-2">Vehicle Information</p>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-gray-500">Truck:</span>
                  <span class="font-medium text-gray-900">{{ shipment.truck_brand || '-' }} ({{ shipment.truck_type || '-' }})</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-500">Plate Number:</span>
                  <span class="font-medium text-gray-900">{{ shipment.plate_number || '-' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-500">Driver Contact:</span>
                  <span class="font-medium text-gray-900">{{ shipment.driver_contact || '-' }}</span>
                </div>
              </div>
            </div>

            <div class="bg-green-50/50 rounded-xl p-4 border border-green-100">
              <p class="text-xs text-green-600 mb-2">Route Information</p>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-gray-500">Distance:</span>
                  <span class="font-medium text-gray-900">{{ shipment.distance_km || '-' }} km</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-500">Shipping Cost:</span>
                  <span class="font-medium text-emerald-600">₱{{ formatMoney(shipment.shipping_cost) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-500">Tax Rate:</span>
                  <span class="font-medium text-gray-900">{{ Number(shipment.tax_rate || 0).toFixed(2) }}%</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </Card>

     <!-- Record Log Card -->
    <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden bg-linear-to-br from-gray-50 to-white">
      <template #header>
        <div class="px-6 pt-6">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
              <i class="pi pi-plus-circle text-green-600 text-sm"></i>
            </div>
            <h2 class="text-lg font-semibold text-gray-900">Record a Log</h2>
          </div>
        </div>
      </template>
      <template #content>
        <div class="p-6 pt-0">
          <div class="space-y-4">
            <div class="relative">
              <i class="pi pi-tag absolute left-3 top-3 text-gray-400 text-sm"></i>
              <Select 
                v-model="logEventType" 
                :options="logTypes" 
                optionLabel="label" 
                optionValue="value" 
                placeholder="Select event type"
                class="w-full pl-10 bg-white border-gray-200 rounded-xl"
              />
            </div>

            <div class="relative">
              <Textarea 
                v-model="logNotes" 
                rows="3" 
                placeholder="Add notes (optional)"
                class="w-full pl-10 bg-white border-gray-200 rounded-xl resize-none"
              />
            </div>

            <div class="flex flex-col gap-3 md:flex-row">
              <Button 
                label="Record Log" 
                icon="pi pi-save" 
                class="w-full bg-blue-500 hover:bg-blue-600 border-none text-white font-medium rounded-xl py-3 md:!w-auto flex-1"
                :loading="logSubmitting" 
                @click="submitLog" 
              />
              <Button
                label="Mark Delivered"
                icon="pi pi-check"
                severity="success"
                class="w-full border-none text-white font-medium rounded-xl py-3 md:!w-auto flex-1"
                :class="shipment?.status === 'delivered' ? 'bg-gray-300 hover:bg-gray-300' : 'bg-emerald-500 hover:bg-emerald-600'"
                :disabled="shipment?.status === 'delivered'"
                @click="openDeliveryDialog"
              />
            </div>
          </div>
        </div>
      </template>
    </Card>

    <!-- Delivery Logs Card -->
    <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <template #header>
        <div class="px-6 pt-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
                <i class="pi pi-list text-purple-600 text-sm"></i>
              </div>
              <h2 class="text-lg font-semibold text-gray-900">Delivery Logs</h2>
            </div>
            <Tag 
              :value="logCountText" 
              severity="info" 
              class="rounded-full px-3 py-1 text-xs"
            />
          </div>
        </div>
      </template>
      <template #content>
        <div class="p-6 pt-0">
          <!-- Loading State -->
          <div v-if="logsLoading" class="space-y-3">
            <div v-for="i in 3" :key="i" class="bg-gray-50 rounded-xl p-4">
              <Skeleton width="120px" height="16px" class="mb-2" />
              <Skeleton width="200px" height="14px" />
            </div>
          </div>

          <!-- Empty State -->
          <div v-else-if="!deliveryLogs.length" class="text-center py-8">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="pi pi-clock text-gray-400 text-xl"></i>
            </div>
            <p class="text-gray-500 text-sm">No logs yet. Record your first event below.</p>
          </div>

          <!-- Logs Timeline -->
          <div v-else class="space-y-2">
            <div v-for="log in deliveryLogs" :key="log.id" class="relative pl-6 pb-4 border-l-2 border-gray-200 last:pb-0">
              <!-- Timeline Dot -->
              <div class="absolute left-1.5 top-0">
                <div class="w-2 h-2 rounded-full" :class="getLogDotColor(log.event_type)"></div>
              </div>
              
              <!-- Log Content -->
              <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between mb-2">
                  <Tag 
                    :value="log.event_type" 
                    :severity="getLogSeverity(log.event_type)"
                    class="rounded-full text-xs px-3 py-1"
                  />
                  <span class="text-xs text-gray-500">{{ formatDate(log.logged_at, true) }}</span>
                </div>
                <p class="text-sm text-gray-700">{{ log.notes || 'No notes recorded' }}</p>
                <div v-if="log.attachments?.length" class="mt-3 flex flex-wrap gap-2">
                  <a
                    v-for="attachment in log.attachments"
                    :key="attachment.id"
                    :href="attachment.public_url"
                    target="_blank"
                    rel="noreferrer"
                    class="inline-flex items-center gap-1 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-medium text-blue-600 hover:bg-blue-100 transition"
                  >
                    <i class="pi pi-image text-sm"></i>
                    <span class="truncate max-w-[140px]">{{ attachment.filename }}</span>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </Card>
    <Dialog 
      v-model:visible="showDeliveredDialog" 
      header="Proof of Delivery" 
      class="w-full max-w-2xl" 
      :breakpoints="{ '960px': '90vw' }" 
      :closable="!deliverySubmitting"
      modal
    >
      <div class="space-y-4">
        <p class="text-sm text-gray-500">
          Attach at least one image proof of delivery before confirming. Photos become part of the delivery log.
        </p>
        <div class="space-y-2">
          <label class="text-xs font-semibold text-gray-600">Notes (optional)</label>
          <Textarea 
            v-model="deliveryNotes" 
            rows="3" 
            placeholder="Describe any observations, gate codes, or special instructions"
            class="w-full bg-white border border-gray-200 rounded-xl resize-none"
          />
        </div>
        <div class="space-y-1">
          <label class="text-xs font-semibold text-gray-600">Attachments</label>
          <input 
            :key="deliveryAttachmentKey"
            type="file" 
            accept="image/*" 
            multiple 
            class="block w-full text-sm text-gray-600 file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-blue-600 file:rounded-xl file:font-semibold"
            @change="handleDeliveryAttachments"
          />
          <p class="text-xs text-gray-500">Minimum 1 photo, up to 10MB each.</p>
          <ul v-if="deliveryAttachments.length" class="mt-2 space-y-1">
            <li 
              v-for="(file, index) in deliveryAttachments" 
              :key="file.name + index" 
              class="flex items-center justify-between rounded-xl border border-dashed border-gray-200 bg-white px-3 py-2"
            >
              <span class="text-sm text-gray-700 truncate">{{ file.name }}</span>
              <Button 
                icon="pi pi-times" 
                text 
                severity="danger" 
                class="text-red-500"
                @click="removeDeliveryAttachment(index)"
              />
            </li>
          </ul>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" text @click="showDeliveredDialog = false" :disabled="deliverySubmitting" />
        <Button 
          label="Confirm Delivery" 
          icon="pi pi-check" 
          severity="success" 
          :loading="deliverySubmitting" 
          :disabled="!deliveryAttachments.length || deliverySubmitting" 
          @click="submitDeliveryEvidence" 
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import Skeleton from 'primevue/skeleton'
import Dialog from 'primevue/dialog'
import supplierService from '../../../services/supplier.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const shippingId = Number(route.params.id)

// State
const shipment = ref<any>(null)
const deliveryLogs = ref<any[]>([])
const logsLoading = ref(false)
const logEventType = ref('Arrived')
const logNotes = ref('')
const logSubmitting = ref(false)

const logTypes = [
  { label: 'Arrived at Location', value: 'Arrived' },
  { label: 'Start Unloading', value: 'Start Unloading' },
  { label: 'Finish Unloading', value: 'Finish Unloading' },
  { label: 'Delivered', value: 'Delivered' },
  { label: 'Issue / Delay', value: 'Issue' },
]

const showDeliveredDialog = ref(false)
const deliveryNotes = ref('')
const deliveryAttachments = ref<File[]>([])
const deliveryAttachmentKey = ref(0)
const deliverySubmitting = ref(false)

// Computed
const logCountText = computed(() => `${deliveryLogs.value.length} ${deliveryLogs.value.length === 1 ? 'event' : 'events'}`)

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

const getLogSeverity = (eventType: string): 'success' | 'info' | 'warn' | 'danger' | 'secondary' => {
  const map: Record<string, any> = {
    'Arrived': 'info',
    'Start Unloading': 'warn',
    'Finish Unloading': 'success',
    'Delivered': 'success',
    'Issue': 'danger',
  }
  return map[eventType] || 'info'
}

const getLogDotColor = (eventType: string): string => {
  const map: Record<string, string> = {
    'Arrived': 'bg-blue-500',
    'Start Unloading': 'bg-orange-500',
    'Finish Unloading': 'bg-green-500',
    'Delivered': 'bg-green-600',
    'Issue': 'bg-red-500',
  }
  return map[eventType] || 'bg-gray-400'
}

const formatDate = (value?: string, includeTime = false): string => {
  if (!value) return '-'
  const parsed = new Date(value)
  if (isNaN(parsed.getTime())) return value
  
  const options: Intl.DateTimeFormatOptions = { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  }
  if (includeTime) {
    options.hour = '2-digit'
    options.minute = '2-digit'
  }
  return parsed.toLocaleDateString('en-PH', options)
}

const formatMoney = (value: number): string => {
  return new Intl.NumberFormat('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value || 0)
}

// Data loading
const loadShipment = async () => {
  try {
    const res = await supplierService.getShipment(shippingId)
    const payload = res.data.data || res
    shipment.value = payload?.data?.shipment || payload?.data || payload
    await loadLogs()
  } catch (error: any) {
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: error.response?.data?.message || 'Failed to load shipment', 
      life: 3000 
    })
  }
}

const loadLogs = async () => {
  if (!shipment.value?.id) return
  logsLoading.value = true
  try {
    const res = await supplierService.getShipmentLogs(shipment.value.id)
    const payload = res.data.data || res
    deliveryLogs.value = payload?.data?.logs || []
  } catch (error) {
    deliveryLogs.value = []
  } finally {
    logsLoading.value = false
  }
}

const submitLog = async () => {
  if (!shipment.value?.id) return

  logSubmitting.value = true
  try {
    const res = await supplierService.addShipmentLog(shipment.value.id, {
      event_type: logEventType.value,
      notes: logNotes.value || undefined,
    })
    const payload = res.data.data || res
    if (payload?.data) {
      deliveryLogs.value = [payload.data, ...deliveryLogs.value]
      logNotes.value = ''
      
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Log recorded successfully',
        life: 2000
      })
    }
  } catch (error: any) {
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: error.response?.data?.message || 'Failed to record log', 
      life: 3000 
    })
  } finally {
    logSubmitting.value = false
  }
}

const openDeliveryDialog = () => {
  if (shipment.value?.status === 'delivered') {
    toast.add({
      severity: 'info',
      summary: 'Already delivered',
      detail: 'This shipment has already been confirmed delivered.',
      life: 2500,
    })
    return
  }

  showDeliveredDialog.value = true
  deliveryNotes.value = ''
  deliveryAttachments.value = []
  deliveryAttachmentKey.value += 1
}

const handleDeliveryAttachments = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (!target?.files) {
    deliveryAttachments.value = []
    return
  }
  deliveryAttachments.value = Array.from(target.files)
}

const removeDeliveryAttachment = (index: number) => {
  deliveryAttachments.value.splice(index, 1)
}

const submitDeliveryEvidence = async () => {
  if (!shipment.value?.id) return
  if (!deliveryAttachments.value.length) {
    toast.add({
      severity: 'warn',
      summary: 'Attachments required',
      detail: 'Please add at least one photo proof of delivery.',
      life: 3000,
    })
    return
  }

  deliverySubmitting.value = true
  try {
    const response = await supplierService.markShipmentDelivered(shipment.value.id, {
      notes: deliveryNotes.value || undefined,
      attachments: deliveryAttachments.value,
    })

    const apiResponse = response?.data || response
    const payload = apiResponse?.data || apiResponse
    if (payload?.shipment) {
      shipment.value = payload.shipment
    }
    if (payload?.delivery_log) {
      deliveryLogs.value = [payload.delivery_log, ...deliveryLogs.value]
    }

    toast.add({
      severity: 'success',
      summary: 'Delivered',
      detail: 'Shipment confirmed and PO status updated.',
      life: 3000,
    })
    showDeliveredDialog.value = false
    deliveryAttachments.value = []
    deliveryNotes.value = ''
    deliveryAttachmentKey.value += 1
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to confirm delivery.',
      life: 3000,
    })
  } finally {
    deliverySubmitting.value = false
  }
}

const goBack = () => router.push('/supplier-portal/deliveries')

onMounted(loadShipment)
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

/* iOS-style inputs */
:deep(.p-inputtext),
:deep(.p-select),
:deep(.p-textarea) {
  border-radius: 12px;
  border-color: #e5e7eb;
  background-color: #ffffff;
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  transition: all 0.2s ease;
}

:deep(.p-inputtext:focus),
:deep(.p-select:focus),
:deep(.p-textarea:focus) {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* iOS-style buttons */
:deep(.p-button) {
  border-radius: 12px;
  font-weight: 500;
  transition: all 0.2s ease;
}

/* iOS-style tags */
:deep(.p-tag) {
  border-radius: 9999px;
  font-weight: 500;
}

/* Timeline animation */
.border-l-2 {
  transition: border-color 0.2s ease;
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
</style>
