<template>
  <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="rounded-3xl border border-slate-200/80 bg-linear-to-br from-indigo-50 via-white to-sky-50 p-6 shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <Button icon="pi pi-arrow-left" text rounded @click="goBack" />
          <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Delivery Detail</h1>
            <p class="mt-1 text-sm text-slate-600">{{ sourceLabel }} • {{ order?.order_number || '-' }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Button icon="pi pi-refresh" label="Refresh" outlined @click="loadAll" />
          <Button
            v-if="canAssignDelivery"
            icon="pi pi-send"
            label="Assign Delivery"
            severity="success"
            @click="openAssign"
          />
        </div>
      </div>
    </div>

    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Order & Delivery Snapshot</template>
      <template #content>
        <div v-if="loading" class="text-sm text-slate-500">Loading details...</div>
        <div v-else-if="!order" class="text-sm text-slate-500">No order data found.</div>
        <div v-else class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
          <div><span class="text-slate-500">Customer:</span> <strong>{{ customerName }}</strong></div>
          <div><span class="text-slate-500">Contact:</span> <strong>{{ customerContact }}</strong></div>
          <div class="md:col-span-2"><span class="text-slate-500">Address:</span> <strong>{{ deliveryAddress }}</strong></div>
          <div><span class="text-slate-500">Order Status:</span> <Tag :value="formatStatus(order?.status)" severity="secondary" /></div>
          <div>
            <span class="text-slate-500">Delivery Status:</span>
            <Tag :value="formatStatus(delivery?.status || 'pending')" :severity="deliverySeverity(delivery?.status || 'pending')" />
          </div>
          <div><span class="text-slate-500">Tracking #:</span> <strong>{{ delivery?.tracking_number || '-' }}</strong></div>
          <div v-if="delivery?.trip_id">
            <span class="text-slate-500">Trip:</span>
            <Button
              text
              severity="info"
              class="p-0"
              :label="`#${delivery.trip_id}`"
              @click="openTrip(delivery.trip_id)"
            />
          </div>
          <div><span class="text-slate-500">Courier Contact:</span> <strong>{{ delivery?.courier_contact || '-' }}</strong></div>
          <div><span class="text-slate-500">Driver:</span> <strong>{{ driverName }}</strong></div>
          <div><span class="text-slate-500">Delivered At:</span> <strong>{{ delivery?.delivered_at ? formatDateTime(delivery.delivered_at) : '-' }}</strong></div>
          <div><span class="text-slate-500">Order Total:</span> <strong>₱ {{ totalAmount }}</strong></div>
        </div>
      </template>
    </Card>

    <div v-if="delivery" class="grid grid-cols-1 gap-4 lg:grid-cols-2">
      <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
        <template #title>Status Logs</template>
        <template #content>
          <div v-if="isDelivered" class="mb-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
            Delivery is already marked as delivered. Inputs are now read-only.
          </div>

          <div class="space-y-3">
            <div>
              <label class="mb-1 block text-sm text-slate-600">Update Delivery Status</label>
              <Select
                v-model="statusForm.status"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                fluid
                :disabled="!canManageDeliveries || isDelivered"
              />
            </div>
            <Textarea
              v-model="statusForm.notes"
              rows="3"
              fluid
              placeholder="Status notes (optional)"
              :disabled="!canManageDeliveries || isDelivered"
            />
            <Button
              icon="pi pi-save"
              label="Save Status"
              :loading="statusUpdating"
              :disabled="!canManageDeliveries || isDelivered || !statusForm.status"
              @click="saveStatus"
            />
          </div>
        </template>
      </Card>

      <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
        <template #title>Delivered Proof</template>
        <template #content>
          <div v-if="delivery?.proof_photo_url || delivery?.proof_signature_url" class="mb-3 flex flex-wrap gap-2">
            <Button v-if="delivery?.proof_photo_url" icon="pi pi-image" label="View Proof Photo" outlined @click="openMedia(delivery.proof_photo_url)" />
            <Button v-if="delivery?.proof_signature_url" icon="pi pi-pencil" label="View Signature" outlined @click="openMedia(delivery.proof_signature_url)" />
          </div>

          <div class="space-y-3">
            <Message v-if="isDelivered" severity="success" :closable="false">This delivery is finalized.</Message>

            <template v-else>
              <Button
                icon="pi pi-check-circle"
                label="Mark Delivered"
                severity="success"
                :disabled="!canManageDeliveries"
                @click="openDeliveredDialog"
              />
            </template>
          </div>
        </template>
      </Card>
    </div>

    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>
        <div class="flex items-center justify-between gap-3">
          <span>Timeline</span>
          <Button
            v-if="canRecordTransitLog"
            icon="pi pi-plus"
            label="Record a Log"
            size="small"
            outlined
            @click="recordLogDialogVisible = true"
          />
        </div>
      </template>
      <template #content>
        <div v-if="!logs.length" class="text-sm text-slate-500">No timeline entries yet.</div>
        <div v-else class="space-y-3">
          <div v-for="entry in logs" :key="entry.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <Tag :value="formatStatus(entry.event_type || 'note')" severity="info" />
              <span class="text-xs text-slate-500">{{ formatDateTime(entry.created_at) }}</span>
            </div>
            <p class="mt-2 text-sm text-slate-800">{{ entry.message }}</p>
            <p v-if="entry.status_from || entry.status_to" class="mt-1 text-xs text-slate-500">
              {{ entry.status_from || '-' }} → {{ entry.status_to || '-' }}
            </p>
          </div>
        </div>
      </template>
    </Card>

    <Dialog v-model:visible="recordLogDialogVisible" modal header="Record Delivery Log" class="w-full max-w-xl">
      <div class="space-y-3">
        <Textarea
          v-model="recordLogMessage"
          rows="4"
          fluid
          placeholder="Enter delivery log message (e.g., Arrived at checkpoint, traffic delay, unloading started)."
        />
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="recordLogDialogVisible = false" />
        <Button
          icon="pi pi-check"
          label="Save Log"
          :loading="savingLog"
          :disabled="!recordLogMessage.trim()"
          @click="saveTransitLog"
        />
      </template>
    </Dialog>

    <Dialog v-model:visible="deliveredDialogVisible" modal header="Upload Proof of Delivery" class="w-full max-w-xl">
      <div class="space-y-3">
        <div>
          <label class="mb-1 block text-sm text-slate-600">Proof Photo</label>
          <input type="file" accept="image/*" class="block w-full text-sm" @change="onPhotoChange" />
        </div>
        <div>
          <label class="mb-1 block text-sm text-slate-600">Signature</label>
          <div class="rounded-2xl border border-slate-200 bg-white p-2">
            <canvas
              ref="signatureCanvas"
              class="h-40 w-full touch-none rounded-xl border border-slate-200"
              @pointerdown="startSignature"
              @pointermove="drawSignature"
              @pointerup="endSignature"
              @pointerleave="endSignature"
            ></canvas>
            <div class="mt-2 flex flex-wrap gap-2">
              <Button label="Clear" severity="secondary" outlined size="small" @click="clearSignature" />
            </div>
          </div>
        </div>
        <Textarea v-model="deliveredNotes" rows="3" fluid placeholder="Proof notes (optional)" />
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="deliveredDialogVisible = false" />
        <Button
          icon="pi pi-check-circle"
          label="Submit and Deliver"
          severity="success"
          :loading="delivering"
          :disabled="!photoFile"
          @click="markDelivered"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import Message from 'primevue/message'
import Dialog from 'primevue/dialog'
import logisticsService from '../../../../services/logistics.service'
import { useAuthStore } from '../../../../stores/auth'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const canManageDeliveries = authStore.hasPermission('logistics.deliveries.manage')

const source = computed(() => (String(route.params.source || '').toLowerCase() === 'sales' ? 'sales' : 'ecommerce'))
const orderId = computed(() => Number(route.params.orderId || 0))
const sourceLabel = computed(() => (source.value === 'sales' ? 'Sales' : 'Ecommerce'))

const loading = ref(false)
const statusUpdating = ref(false)
const delivering = ref(false)
const deliveredDialogVisible = ref(false)
const recordLogDialogVisible = ref(false)
const recordLogMessage = ref('')
const savingLog = ref(false)

const order = ref<any>(null)
const delivery = ref<any>(null)
const logs = ref<any[]>([])

const photoFile = ref<File | null>(null)
const signatureCanvas = ref<HTMLCanvasElement | null>(null)
const signatureDrawing = ref(false)
const signatureHasInk = ref(false)
const deliveredNotes = ref('')

const statusForm = reactive({
  status: '',
  notes: '',
})

const statusOptions = [
  { label: 'Assigned', value: 'assigned' },
  { label: 'Packed', value: 'packed' },
  { label: 'In Transit', value: 'in_transit' },
  { label: 'Out For Delivery', value: 'out_for_delivery' },
  { label: 'Failed Delivery', value: 'failed_delivery' },
  { label: 'Cancelled', value: 'cancelled' },
]

const customerName = computed(() => (source.value === 'sales' ? order.value?.customer_name : order.value?.shipping_name) || '-')
const customerContact = computed(() => (source.value === 'sales' ? order.value?.customer_phone : order.value?.shipping_phone) || '-')
const deliveryAddress = computed(() => (source.value === 'sales' ? order.value?.delivery_address : order.value?.shipping_address) || '-')
const totalAmount = computed(() => Number(order.value?.total_amount || 0).toFixed(2))
const driverName = computed(() => {
  const d = delivery.value?.driver
  return d ? `${d.fname || ''} ${d.lname || ''}`.trim() : delivery.value?.courier_name || '-'
})
const isDelivered = computed(() => String(delivery.value?.status || '').toLowerCase() === 'delivered')
const canRecordTransitLog = computed(() => {
  const status = String(delivery.value?.status || '').toLowerCase()
  return ['in_transit', 'out_for_delivery'].includes(status) && canManageDeliveries
})
const canAssignDelivery = computed(() => canManageDeliveries && !delivery.value && !!order.value)

const loadAll = async () => {
  if (!orderId.value) return
  loading.value = true
  try {
    const response = await logisticsService.getDeliveryOrderDetail(source.value as 'ecommerce' | 'sales', orderId.value)
    const payload = response?.data || {}
    order.value = payload.order || null
    delivery.value = payload.delivery || null
    logs.value = payload.logs || []

    if (delivery.value) {
      statusForm.status = delivery.value.status || 'assigned'
    }
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Load Failed', detail: error?.response?.data?.message || 'Failed to load detail.', life: 3500 })
  } finally {
    loading.value = false
  }
}

const saveStatus = async () => {
  if (!delivery.value) return

  statusUpdating.value = true
  try {
    await logisticsService.updateUnifiedDeliveryStatus(source.value as 'ecommerce' | 'sales', orderId.value, {
      status: statusForm.status,
      notes: statusForm.notes || null,
    })

    toast.add({ severity: 'success', summary: 'Updated', detail: 'Delivery status updated.', life: 2500 })
    await loadAll()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Update Failed', detail: error?.response?.data?.message || 'Failed to update delivery status.', life: 3500 })
  } finally {
    statusUpdating.value = false
  }
}

const saveTransitLog = async () => {
  if (!recordLogMessage.value.trim()) return

  savingLog.value = true
  try {
    await logisticsService.addUnifiedDeliveryLog(source.value as 'ecommerce' | 'sales', orderId.value, {
      message: recordLogMessage.value.trim(),
    })
    toast.add({ severity: 'success', summary: 'Logged', detail: 'Delivery log recorded.', life: 2200 })
    recordLogMessage.value = ''
    recordLogDialogVisible.value = false
    await loadAll()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Log Failed',
      detail: error?.response?.data?.message || 'Unable to record delivery log.',
      life: 3000,
    })
  } finally {
    savingLog.value = false
  }
}

const markDelivered = async () => {
  if (!photoFile.value) return

  delivering.value = true
  try {
    const formData = new FormData()
    formData.append('photo', photoFile.value)
    const signatureBlob = await signatureToBlob()
    if (!signatureBlob) {
      throw new Error('Missing signature')
    }
    formData.append('signature', signatureBlob, 'signature.png')
    formData.append('notes', deliveredNotes.value)

    await logisticsService.markUnifiedDelivered(source.value as 'ecommerce' | 'sales', orderId.value, formData)

    toast.add({ severity: 'success', summary: 'Delivered', detail: 'Proof uploaded and marked as delivered.', life: 2500 })

    photoFile.value = null
    clearSignature()
    deliveredNotes.value = ''
    deliveredDialogVisible.value = false

    await loadAll()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Deliver Failed', detail: error?.response?.data?.message || 'Failed to mark as delivered.', life: 3500 })
  } finally {
    delivering.value = false
  }
}

const onPhotoChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  photoFile.value = target.files?.[0] || null
}

const setupSignatureCanvas = () => {
  if (!signatureCanvas.value) return
  const canvas = signatureCanvas.value
  const rect = canvas.getBoundingClientRect()
  const scale = window.devicePixelRatio || 1
  canvas.width = Math.max(1, Math.floor(rect.width * scale))
  canvas.height = Math.max(1, Math.floor(rect.height * scale))

  const ctx = canvas.getContext('2d')
  if (!ctx) return
  ctx.scale(scale, scale)
  ctx.lineWidth = 2
  ctx.lineCap = 'round'
  ctx.lineJoin = 'round'
  ctx.strokeStyle = '#0f172a'
}

const getSignaturePoint = (event: PointerEvent) => {
  const canvas = signatureCanvas.value
  if (!canvas) return null
  const rect = canvas.getBoundingClientRect()
  return {
    x: event.clientX - rect.left,
    y: event.clientY - rect.top,
  }
}

const startSignature = (event: PointerEvent) => {
  if (!signatureCanvas.value) return
  signatureDrawing.value = true
  signatureCanvas.value.setPointerCapture(event.pointerId)
  const ctx = signatureCanvas.value.getContext('2d')
  const point = getSignaturePoint(event)
  if (!ctx || !point) return
  ctx.beginPath()
  ctx.moveTo(point.x, point.y)
}

const drawSignature = (event: PointerEvent) => {
  if (!signatureDrawing.value || !signatureCanvas.value) return
  const ctx = signatureCanvas.value.getContext('2d')
  const point = getSignaturePoint(event)
  if (!ctx || !point) return
  ctx.lineTo(point.x, point.y)
  ctx.stroke()
  signatureHasInk.value = true
}

const endSignature = (event: PointerEvent) => {
  if (!signatureDrawing.value || !signatureCanvas.value) return
  signatureDrawing.value = false
  signatureCanvas.value.releasePointerCapture(event.pointerId)
}

const clearSignature = () => {
  const canvas = signatureCanvas.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  if (!ctx) return
  ctx.clearRect(0, 0, canvas.width, canvas.height)
  signatureHasInk.value = false
}

const signatureToBlob = () => {
  const canvas = signatureCanvas.value
  if (!canvas) return Promise.resolve(null)
  return new Promise<Blob | null>((resolve) => {
    canvas.toBlob((blob) => resolve(blob), 'image/png')
  })
}

const goBack = () => router.push({ name: 'logistics.deliveries' })

const openDeliveredDialog = () => {
  deliveredDialogVisible.value = true
}

const openAssign = () => {
  router.push({
    name: 'logistics.deliveries.create',
    query: { source: source.value, order_id: String(orderId.value) },
  })
}

const normalizeMediaUrl = (raw: string) => {
  if (!raw) return ''
  if (raw.startsWith('http://') || raw.startsWith('https://') || raw.startsWith('data:')) return raw
  if (raw.startsWith('/storage/')) return raw
  if (raw.startsWith('storage/')) return `/${raw}`
  return `/storage/${raw.replace(/^\//, '')}`
}

const openMedia = (url: string) => {
  const targetUrl = normalizeMediaUrl(url)
  if (!targetUrl) return
  window.open(targetUrl, '_blank')
}

const openTrip = (tripId: number) => {
  router.push({ name: 'logistics.trips.detail', params: { id: String(tripId) } })
}

const formatStatus = (value: string) => value?.replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase()) || '-'

const deliverySeverity = (status: string) => {
  if (status === 'delivered') return 'success'
  if (status === 'failed_delivery' || status === 'cancelled') return 'danger'
  if (status === 'out_for_delivery') return 'warning'
  if (status === 'pending') return 'warn'
  return 'info'
}

const formatDateTime = (value: string) => (value ? new Date(value).toLocaleString('en-PH') : '-')

onMounted(() => {
  loadAll()
  setupSignatureCanvas()
  window.addEventListener('resize', setupSignatureCanvas)
})
</script>
