<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <div class="flex items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <Button text severity="secondary" icon="pi pi-arrow-left" @click="goBack" />
            <div>
              <h1 class="text-2xl font-semibold text-gray-900">Delivery Detail</h1>
              <p class="text-sm text-gray-500">{{ delivery?.order?.order_number || '-' }}</p>
            </div>
          </div>
          <Button severity="info" outlined icon="pi pi-refresh" label="Refresh" @click="loadAll" />
        </div>
      </template>
    </Card>

    <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <Skeleton height="12rem" />
      <Skeleton height="12rem" />
      <Skeleton height="12rem" />
    </div>

    <template v-else>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <Card class="rounded-2xl border border-gray-100 shadow-sm lg:col-span-2">
          <template #title>Delivery Overview</template>
          <template #content>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
              <div><span class="text-gray-500">Customer:</span> <span class="font-medium text-gray-900">{{ delivery?.order?.shipping_name || '-' }}</span></div>
              <div><span class="text-gray-500">Contact:</span> <span class="font-medium text-gray-900">{{ delivery?.order?.shipping_phone || '-' }}</span></div>
              <div><span class="text-gray-500">Tracking:</span> <span class="font-medium text-gray-900">{{ delivery?.tracking_number || '-' }}</span></div>
              <div><span class="text-gray-500">Status:</span> <Tag :value="formatStatus(delivery?.status || 'assigned')" :severity="statusSeverity(delivery?.status || 'assigned')" /></div>
              <div><span class="text-gray-500">Vehicle:</span> <span class="font-medium text-gray-900">{{ delivery?.vehicle ? `${delivery.vehicle.vehicle_name} (${delivery.vehicle.plate_number})` : '-' }}</span></div>
              <div><span class="text-gray-500">Courier:</span> <span class="font-medium text-gray-900">{{ courierName || '-' }}</span></div>
              <div><span class="text-gray-500">Courier Contact:</span> <span class="font-medium text-gray-900">{{ delivery?.courier_contact || '-' }}</span></div>
              <div><span class="text-gray-500">Delivered At:</span> <span class="font-medium text-gray-900">{{ delivery?.delivered_at ? formatDateTime(delivery.delivered_at) : '-' }}</span></div>
            </div>

            <div v-if="delivery?.proof_photo_url || delivery?.proof_signature_url" class="mt-4 border-t border-gray-100 pt-3">
              <p class="text-sm font-semibold text-gray-900 mb-2">Proof Attachments</p>
              <div class="flex flex-wrap gap-2">
                <Button
                  v-if="delivery?.proof_photo_url"
                  label="Preview Photo"
                  severity="secondary"
                  outlined
                  icon="pi pi-image"
                  @click="previewMedia(delivery.proof_photo_url, 'photo')"
                />
                <Button
                  v-if="delivery?.proof_signature_url"
                  label="Preview Signature"
                  severity="secondary"
                  outlined
                  icon="pi pi-pencil"
                  @click="previewMedia(delivery.proof_signature_url, 'signature')"
                />
              </div>
            </div>
          </template>
        </Card>

        <Card v-if="showPaymongoPanel" class="rounded-2xl border border-gray-100 shadow-sm">
          <template #title>PayMongo Payment</template>
          <template #content>
            <div v-if="!isPaymongoPaid" class="space-y-3">
              <p class="text-sm text-gray-600">Amount</p>
              <p class="text-lg font-semibold text-gray-900">₱ {{ humanPayable }}</p>
              <p class="text-xs text-gray-500">Status: <span class="font-medium">{{ paymongoStatus }}</span></p>
              <InputText v-model="payerName" placeholder="Payer full name" />
              <InputText v-model="payerEmail" type="email" placeholder="Payer email" />
              <InputText v-model="payerPhone" placeholder="Payer phone (e.g. 09xxxxxxxxx)" />
              <Button
                :label="paymongoActionLabel"
                :severity="paymongoActionSeverity"
                :loading="paymongoCreating"
                :disabled="isPaymongoPaid || !canManageDeliveries"
                fluid
                @click="handlePaymongoAction"
              />
            </div>

            <div v-else class="space-y-3 rounded-xl border border-green-200 bg-green-50 p-4">
              <div class="flex items-center justify-between">
                <h4 class="text-sm font-semibold text-green-800">PayMongo Invoice</h4>
                <Tag severity="success" value="Paid" />
              </div>
              <div class="grid grid-cols-1 gap-2 text-sm text-gray-700">
                <p><span class="font-medium text-gray-600">Intent ID:</span> {{ paymongoIntentId || '-' }}</p>
                <p><span class="font-medium text-gray-600">Payment ID:</span> {{ paymongoPaymentId || '-' }}</p>
                <p><span class="font-medium text-gray-600">Amount:</span> ₱ {{ humanPayable }}</p>
                <p><span class="font-medium text-gray-600">Status:</span> {{ paymongoStatus }}</p>
                <p><span class="font-medium text-gray-600">Paid At:</span> {{ paymongoPaidAt }}</p>
              </div>
              <div class="flex gap-2 pt-1">
                <Button v-if="paymongoReceiptUrl" label="Open Receipt" icon="pi pi-external-link" severity="success" outlined @click="openExternal(paymongoReceiptUrl)" />
                <Button v-if="paymongoIntentId" label="Refresh Payment" icon="pi pi-refresh" severity="secondary" outlined :loading="paymongoStatusLoading" @click="pollPaymongoStatus" />
              </div>
            </div>
          </template>
        </Card>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <Card v-if="!isDelivered && canManageDeliveries" class="rounded-2xl border border-gray-100 shadow-sm">
          <template #title>Delivery Proof Upload</template>
          <template #content>
            <div class="space-y-3">
              <Message severity="info" :closable="false">Upload proof to mark this delivery as Delivered.</Message>
              <div>
                <label class="text-sm text-gray-600">Photo</label>
                <input type="file" accept="image/*" @change="onPhotoChange" class="block w-full mt-1 text-sm" />
              </div>

              <div>
                <div class="flex items-center justify-between">
                  <label class="text-sm text-gray-600">Signature Pad</label>
                  <Button text severity="secondary" label="Clear" @click="clearSignature" />
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-2">
                  <canvas
                    ref="signatureCanvas"
                    class="w-full h-40 touch-none cursor-crosshair rounded-lg border border-gray-100"
                    @pointerdown="startDraw"
                    @pointermove="draw"
                    @pointerup="endDraw"
                    @pointerleave="endDraw"
                  />
                </div>
              </div>

              <Textarea v-model="proofNotes" rows="2" fluid placeholder="Proof notes (optional)" />
              <Button severity="info" :loading="uploadingProof" fluid label="Upload Proof and Mark Delivered" @click="uploadProof" />
            </div>
          </template>
        </Card>

        <Card v-if="!isDelivered && canManageDeliveries" class="rounded-2xl border border-gray-100 shadow-sm">
          <template #title>Add Timeline Note</template>
          <template #content>
            <div class="space-y-3">
              <Textarea v-model="newLogMessage" rows="4" fluid placeholder="e.g. Customer requested evening delivery..." />
              <Button severity="info" :loading="addingLog" fluid label="Add Log" @click="addLog" />
            </div>
          </template>
        </Card>
      </div>

      <Message v-if="isDelivered" severity="success" :closable="false">
        Delivery is already marked as Delivered. Timeline is now static.
      </Message>

      <Card class="rounded-2xl border border-gray-100 shadow-sm">
        <template #title>Timeline / Audit Trail</template>
        <template #content>
          <div v-if="!logs.length" class="text-sm text-gray-500">No timeline entries yet.</div>
          <div v-else class="space-y-3">
            <div v-for="log in logs" :key="log.id" class="rounded-xl border border-gray-100 bg-gray-50 p-3">
              <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                  <Tag severity="info" :value="formatStatus(log.event_type || 'note')" />
                  <span class="text-xs text-gray-500">{{ formatDateTime(log.created_at) }}</span>
                </div>
                <span class="text-xs text-gray-500">{{ log.creator ? `${log.creator.fname || ''} ${log.creator.lname || ''}`.trim() : 'System' }}</span>
              </div>
              <p class="text-sm text-gray-800 mt-2">{{ log.message }}</p>
              <p v-if="log.status_from || log.status_to" class="text-xs text-gray-500 mt-1">{{ log.status_from || '-' }} -> {{ log.status_to || '-' }}</p>
              <div v-if="log.meta?.proof_photo_url || log.meta?.proof_signature_url" class="mt-2 flex flex-wrap gap-2">
                <Button
                  v-if="log.meta?.proof_photo_url"
                  size="small"
                  outlined
                  icon="pi pi-image"
                  label="View Proof Photo"
                  @click="previewMedia(log.meta.proof_photo_url, 'photo')"
                />
                <Button
                  v-if="log.meta?.proof_signature_url"
                  size="small"
                  outlined
                  icon="pi pi-pencil"
                  label="View Signature"
                  @click="previewMedia(log.meta.proof_signature_url, 'signature')"
                />
              </div>
            </div>
          </div>
        </template>
      </Card>
    </template>

    <Dialog v-model:visible="mediaPreview.visible" modal :header="mediaPreview.title" class="w-full max-w-4xl">
      <div class="flex items-center justify-center bg-black/5 rounded-xl p-2">
        <img v-if="mediaPreview.url" :src="mediaPreview.url" alt="Proof preview" class="max-h-[70vh] w-auto rounded-lg object-contain" />
      </div>
      <template #footer>
        <Button label="Open Full View" severity="info" outlined icon="pi pi-external-link" @click="openExternal(mediaPreview.url)" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import logisticsService from '@/services/logistics.service'
import paymongoService from '@/services/paymongo.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'
import Skeleton from 'primevue/skeleton'
import Message from 'primevue/message'
import InputText from 'primevue/inputtext'
import Dialog from 'primevue/dialog'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const canManageDeliveries = authStore.hasPermission('logistics.deliveries.manage')

const loading = ref(false)
const uploadingProof = ref(false)
const addingLog = ref(false)
const paymongoIntentId = ref<string | null>(null)
const paymongoStatus = ref<string>('idle')
const paymongoCreating = ref(false)
const paymongoPolling = ref<ReturnType<typeof setInterval> | null>(null)
const paymongoStatusLoading = ref(false)
const paymongoIntentPayload = ref<any>(null)
const payerName = ref('')
const payerEmail = ref('')
const payerPhone = ref('')

const mediaPreview = reactive({
  visible: false,
  url: '',
  title: 'Proof Preview',
})

const normalizedPaymongoStatus = computed(() => String(paymongoStatus.value || '').toLowerCase())
const isPaymongoPaid = computed(() => ['succeeded', 'paid'].includes(normalizedPaymongoStatus.value))
const hasOpenPaymongoIntent = computed(() => !!paymongoIntentId.value && !['failed', 'canceled', 'cancelled'].includes(normalizedPaymongoStatus.value))
const paymongoActionLabel = computed(() => {
  if (isPaymongoPaid.value) return 'Payment Completed'
  return hasOpenPaymongoIntent.value ? 'Open GCash Checkout' : 'Create PayMongo Intent'
})
const paymongoActionSeverity = computed(() => (hasOpenPaymongoIntent.value ? 'info' : 'success'))
const paymongoPayment = computed(() => {
  const payments = paymongoIntentPayload.value?.data?.attributes?.payments
  return Array.isArray(payments) && payments.length ? payments[0] : null
})
const paymongoPaymentId = computed(() => paymongoPayment.value?.id || '-')
const paymongoReceiptUrl = computed(() => paymongoPayment.value?.attributes?.receipt_url || paymongoPayment.value?.attributes?.access_url || null)
const paymongoPaidAt = computed(() => {
  const value = paymongoPayment.value?.attributes?.paid_at || paymongoPayment.value?.attributes?.created_at
  return value ? new Date(value).toLocaleString('en-PH') : '-'
})

const delivery = ref<any>(null)
const logs = ref<any[]>([])

const photoFile = ref<File | null>(null)
const proofNotes = ref('')
const newLogMessage = ref('')

const signatureCanvas = ref<HTMLCanvasElement | null>(null)
const isDrawing = ref(false)
const hasDrawnSignature = ref(false)

const courierName = computed(() => {
  const d = delivery.value?.driver
  if (d) return `${d.fname || ''} ${d.lname || ''}`.trim()
  return delivery.value?.courier_name || '-'
})

const isDelivered = computed(() => String(delivery.value?.status || '').toLowerCase() === 'delivered')
const showPaymongoPanel = computed(() => String(delivery.value?.order?.payment_method || '').toLowerCase() === 'e_wallet')

const invoiceTotal = computed(() => {
  const raw = delivery.value?.order?.total_amount ?? 0
  const numeric = Number.parseFloat(String(raw))
  return Number.isFinite(numeric) ? numeric : 0
})
const paymongoAmount = computed(() => Math.max(Math.round(invoiceTotal.value * 100), 0))
const humanPayable = computed(() => invoiceTotal.value.toFixed(2))

const loadDelivery = async () => {
  const res = await logisticsService.getDelivery(String(route.params.id))
  delivery.value = res?.data || null
  if (!payerName.value) payerName.value = delivery.value?.order?.shipping_name || ''
  if (!payerPhone.value) payerPhone.value = delivery.value?.order?.shipping_phone || ''
  if (!payerEmail.value) payerEmail.value = delivery.value?.order?.shipping_email || ''
}

const loadLogs = async () => {
  const res = await logisticsService.getDeliveryLogs(String(route.params.id), { per_page: 50 })
  logs.value = res?.data?.data || []
}

const loadAll = async () => {
  loading.value = true
  try {
    await Promise.all([loadDelivery(), loadLogs()])
    await loadLatestPaymongoIntent()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load delivery detail', life: 3000 })
  } finally {
    loading.value = false
  }
}

const loadLatestPaymongoIntent = async () => {
  if (!delivery.value?.id || !showPaymongoPanel.value) return
  try {
    const res = await paymongoService.getLatestIntentByPayable('delivery', Number(delivery.value.id))
    const latest = res?.data
    if (!latest) return
    paymongoIntentId.value = latest.payment_intent_id || null
    paymongoStatus.value = latest.status || paymongoStatus.value
    if (paymongoIntentId.value) {
      await pollPaymongoStatus()
      if (!['succeeded', 'failed', 'canceled', 'cancelled', 'paid'].includes(String(paymongoStatus.value).toLowerCase())) {
        startPaymongoPolling()
      }
    }
  } catch {
    // ignore
  }
}

const createPaymongoIntent = async () => {
  if (!delivery.value?.id || !showPaymongoPanel.value) return
  if (paymongoAmount.value <= 0) {
    toast.add({ severity: 'warn', summary: 'No Payable Amount', detail: 'Delivery order amount is zero.', life: 3000 })
    return
  }
  paymongoCreating.value = true
  try {
    const response = await paymongoService.createIntent({
      amount: paymongoAmount.value,
      payment_method_allowed: ['gcash'],
      store_id: Number(delivery.value.store_id),
      payable_type: 'delivery',
      payable_id: delivery.value.id,
    })
    const data = response.data?.data?.attributes || {}
    paymongoIntentId.value = response.data?.data?.id
    paymongoStatus.value = data.status || 'awaiting_payment_method'
    paymongoIntentPayload.value = response.data
    startPaymongoPolling()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Payment Error', detail: error?.response?.data?.message || 'Unable to create PayMongo intent.', life: 4000 })
  } finally {
    paymongoCreating.value = false
  }
}

const handlePaymongoAction = async () => {
  if (isPaymongoPaid.value) return
  if (hasOpenPaymongoIntent.value) {
    await openPaymongoCheckout()
    return
  }
  await createPaymongoIntent()
}

const pollPaymongoStatus = async () => {
  if (!paymongoIntentId.value || paymongoStatusLoading.value) return
  try {
    paymongoStatusLoading.value = true
    const response = await paymongoService.getIntent(paymongoIntentId.value)
    paymongoIntentPayload.value = response.data
    const latestStatus = response.data?.data?.attributes?.status || paymongoStatus.value
    paymongoStatus.value = latestStatus
    if (['succeeded', 'failed', 'canceled'].includes(String(latestStatus).toLowerCase())) {
      stopPaymongoPolling()
    }
  } finally {
    paymongoStatusLoading.value = false
  }
}

const startPaymongoPolling = () => {
  stopPaymongoPolling()
  pollPaymongoStatus()
  paymongoPolling.value = setInterval(pollPaymongoStatus, 8000)
}

const stopPaymongoPolling = () => {
  if (paymongoPolling.value) {
    clearInterval(paymongoPolling.value)
    paymongoPolling.value = null
  }
}

const openPaymongoCheckout = async () => {
  if (!paymongoIntentId.value) return
  if (!payerName.value.trim() || !payerEmail.value.trim() || !payerPhone.value.trim()) {
    toast.add({ severity: 'warn', summary: 'Missing Payer Info', detail: 'Name, email, and phone are required.', life: 3000 })
    return
  }
  paymongoCreating.value = true
  try {
    const response = await paymongoService.startGcash(paymongoIntentId.value, {
      name: payerName.value.trim(),
      email: payerEmail.value.trim(),
      phone: payerPhone.value.trim(),
      return_url: window.location.href,
    })
    const url = response?.data?.redirect_url
    if (!url) throw new Error('Missing redirect URL from PayMongo.')
    window.open(url, '_blank')
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Checkout Error', detail: error?.response?.data?.message || 'Unable to open GCash checkout.', life: 4000 })
  } finally {
    paymongoCreating.value = false
  }
}

const openExternal = (url: string) => {
  if (!url) return
  window.open(url, '_blank')
}

const previewMedia = (url: string, type: 'photo' | 'signature') => {
  mediaPreview.url = url
  mediaPreview.title = type === 'photo' ? 'Proof Photo Preview' : 'Signature Preview'
  mediaPreview.visible = true
}

const onPhotoChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  photoFile.value = target.files?.[0] || null
}

const getCanvasPoint = (event: PointerEvent) => {
  const canvas = signatureCanvas.value
  if (!canvas) return { x: 0, y: 0 }
  const rect = canvas.getBoundingClientRect()
  return { x: event.clientX - rect.left, y: event.clientY - rect.top }
}

const startDraw = (event: PointerEvent) => {
  const canvas = signatureCanvas.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  if (!ctx) return
  const { x, y } = getCanvasPoint(event)
  ctx.beginPath()
  ctx.moveTo(x, y)
  isDrawing.value = true
}

const draw = (event: PointerEvent) => {
  if (!isDrawing.value) return
  const canvas = signatureCanvas.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  if (!ctx) return
  const { x, y } = getCanvasPoint(event)
  ctx.lineTo(x, y)
  ctx.stroke()
  hasDrawnSignature.value = true
}

const endDraw = () => {
  isDrawing.value = false
}

const setupSignatureCanvas = () => {
  const canvas = signatureCanvas.value
  if (!canvas) return
  const ratio = window.devicePixelRatio || 1
  const rect = canvas.getBoundingClientRect()
  canvas.width = Math.max(1, Math.floor(rect.width * ratio))
  canvas.height = Math.max(1, Math.floor(rect.height * ratio))
  const ctx = canvas.getContext('2d')
  if (!ctx) return
  ctx.scale(ratio, ratio)
  ctx.lineWidth = 2
  ctx.lineCap = 'round'
  ctx.lineJoin = 'round'
  ctx.strokeStyle = '#111827'
}

const clearSignature = () => {
  const canvas = signatureCanvas.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  if (!ctx) return
  ctx.clearRect(0, 0, canvas.width, canvas.height)
  hasDrawnSignature.value = false
}

const canvasSignatureToFile = async (): Promise<File | null> => {
  const canvas = signatureCanvas.value
  if (!canvas || !hasDrawnSignature.value) return null
  const blob: Blob | null = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'))
  if (!blob) return null
  return new File([blob], `signature-${Date.now()}.png`, { type: 'image/png' })
}

const uploadProof = async () => {
  if (!photoFile.value || !hasDrawnSignature.value) {
    toast.add({ severity: 'warn', summary: 'Incomplete Proof', detail: 'Photo and signature are both required.', life: 2500 })
    return
  }
  uploadingProof.value = true
  try {
    const fd = new FormData()
    fd.append('photo', photoFile.value)
    const signature = await canvasSignatureToFile()
    if (!signature) {
      toast.add({ severity: 'warn', summary: 'Signature', detail: 'Please draw a signature.', life: 2500 })
      return
    }
    fd.append('signature', signature)
    if (proofNotes.value) fd.append('notes', proofNotes.value)

    await logisticsService.uploadProof(String(route.params.id), fd)
    proofNotes.value = ''
    photoFile.value = null
    clearSignature()
    toast.add({ severity: 'success', summary: 'Uploaded', detail: 'Proof uploaded and delivery marked as Delivered.', life: 2500 })
    await loadAll()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Upload failed', detail: error?.response?.data?.message || 'Failed to upload proof', life: 3000 })
  } finally {
    uploadingProof.value = false
  }
}

const addLog = async () => {
  const message = newLogMessage.value.trim()
  if (!message) return
  addingLog.value = true
  try {
    await logisticsService.addDeliveryLog(String(route.params.id), { message })
    newLogMessage.value = ''
    toast.add({ severity: 'success', summary: 'Added', detail: 'Timeline note added.', life: 2200 })
    await loadLogs()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Failed to add log', life: 3000 })
  } finally {
    addingLog.value = false
  }
}

const formatStatus = (status: string) => String(status || '').replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase())
const formatDateTime = (value: string) => (value ? new Date(value).toLocaleString('en-PH') : '-')
const statusSeverity = (status: string) => {
  if (status === 'delivered') return 'success'
  if (status === 'failed_delivery' || status === 'cancelled') return 'danger'
  if (status === 'out_for_delivery') return 'warning'
  return 'info'
}

const goBack = () => router.push({ name: 'logistics.deliveries' })

onMounted(async () => {
  await loadAll()
  await nextTick()
  setupSignatureCanvas()
  window.addEventListener('resize', setupSignatureCanvas)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', setupSignatureCanvas)
  stopPaymongoPolling()
})
</script>
