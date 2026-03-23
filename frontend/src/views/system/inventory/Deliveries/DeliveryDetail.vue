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
              <div><span class="text-gray-500">Driver:</span> <span class="font-medium text-gray-900">{{ driverName }}</span></div>
            </div>
          </template>
        </Card>

        <Card class="rounded-2xl border border-gray-100 shadow-sm">
          <template #title>Assign Driver</template>
          <template #content>
            <div class="space-y-3">
              <Select v-model="driverId" :options="drivers" optionLabel="label" optionValue="id" fluid showClear placeholder="Select driver/user" />
              <Button severity="info" :loading="assigningDriver" fluid label="Assign Driver" @click="assignDriver" />
            </div>
          </template>
        </Card>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <Card class="rounded-2xl border border-gray-100 shadow-sm">
          <template #title>Delivery Proof Upload</template>
          <template #content>
            <div class="space-y-3">
              <Message severity="info" :closable="false">Upload photo and draw signature to reduce delivery disputes.</Message>
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
              <Button severity="info" :loading="uploadingProof" fluid label="Upload Proof" @click="uploadProof" />
              <div v-if="proofPhotoUrl || proofSignatureUrl" class="grid grid-cols-2 gap-2">
                <a v-if="proofPhotoUrl" :href="proofPhotoUrl" target="_blank" class="text-sm text-blue-600 hover:underline">View Photo Proof</a>
                <a v-if="proofSignatureUrl" :href="proofSignatureUrl" target="_blank" class="text-sm text-blue-600 hover:underline">View Signature Proof</a>
              </div>
            </div>
          </template>
        </Card>

        <Card class="rounded-2xl border border-gray-100 shadow-sm">
          <template #title>Add Timeline Note</template>
          <template #content>
            <div class="space-y-3">
              <Textarea v-model="newLogMessage" rows="4" fluid placeholder="e.g. Customer requested evening delivery..." />
              <Button severity="info" :loading="addingLog" fluid label="Add Log" @click="addLog" />
            </div>
          </template>
        </Card>
      </div>

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
              <p v-if="log.status_from || log.status_to" class="text-xs text-gray-500 mt-1">
                {{ log.status_from || '-' }} -> {{ log.status_to || '-' }}
              </p>
            </div>
          </div>
        </template>
      </Card>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '@/services/inventory.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import Skeleton from 'primevue/skeleton'
import Message from 'primevue/message'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const assigningDriver = ref(false)
const uploadingProof = ref(false)
const addingLog = ref(false)

const delivery = ref<any>(null)
const logs = ref<any[]>([])
const drivers = ref<any[]>([])
const driverId = ref<number | null>(null)

const photoFile = ref<File | null>(null)
const proofNotes = ref('')
const proofPhotoUrl = ref('')
const proofSignatureUrl = ref('')
const newLogMessage = ref('')

const signatureCanvas = ref<HTMLCanvasElement | null>(null)
const isDrawing = ref(false)
const hasDrawnSignature = ref(false)

const driverName = computed(() => {
  const d = delivery.value?.driver
  return d ? `${d.fname || ''} ${d.lname || ''}`.trim() : '-'
})

const loadDelivery = async () => {
  const res = await inventoryService.getEcommerceDelivery(String(route.params.id))
  delivery.value = res?.data || null
  driverId.value = delivery.value?.driver_user_id || null
}

const loadLogs = async () => {
  const res = await inventoryService.getDeliveryLogs(String(route.params.id), { per_page: 50 })
  logs.value = res?.data?.data || []
}

const loadDrivers = async () => {
  const res = await inventoryService.getDeliveryDrivers()
  drivers.value = (res?.data || []).map((u: any) => ({ ...u, label: `${u.name} (${u.role})` }))
}

const loadAll = async () => {
  loading.value = true
  try {
    await Promise.all([loadDelivery(), loadLogs(), loadDrivers()])
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load delivery detail', life: 3000 })
  } finally {
    loading.value = false
  }
}

const assignDriver = async () => {
  if (!driverId.value) return
  assigningDriver.value = true
  try {
    await inventoryService.assignDeliveryDriver(String(route.params.id), { driver_user_id: driverId.value })
    toast.add({ severity: 'success', summary: 'Assigned', detail: 'Driver assigned successfully.', life: 2200 })
    await loadAll()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Failed to assign driver', life: 3000 })
  } finally {
    assigningDriver.value = false
  }
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
  if (!photoFile.value && !hasDrawnSignature.value) {
    toast.add({ severity: 'warn', summary: 'No proof', detail: 'Please upload photo or draw signature.', life: 2500 })
    return
  }
  uploadingProof.value = true
  try {
    const fd = new FormData()
    if (photoFile.value) fd.append('photo', photoFile.value)
    const signature = await canvasSignatureToFile()
    if (signature) fd.append('signature', signature)
    if (proofNotes.value) fd.append('notes', proofNotes.value)

    const res = await inventoryService.uploadDeliveryProof(String(route.params.id), fd)
    proofPhotoUrl.value = res?.data?.proof_photo_url || ''
    proofSignatureUrl.value = res?.data?.proof_signature_url || ''
    proofNotes.value = ''
    photoFile.value = null
    clearSignature()
    toast.add({ severity: 'success', summary: 'Uploaded', detail: 'Delivery proof uploaded.', life: 2200 })
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
    await inventoryService.addDeliveryLog(String(route.params.id), { message })
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
const formatDateTime = (value: string) => new Date(value).toLocaleString('en-PH')
const statusSeverity = (status: string) => {
  if (status === 'delivered') return 'success'
  if (status === 'failed_delivery' || status === 'cancelled') return 'danger'
  if (status === 'out_for_delivery') return 'warning'
  return 'info'
}

const goBack = () => router.push({ name: 'inventory.ecommerce-deliveries' })

onMounted(async () => {
  await loadAll()
  await nextTick()
  setupSignatureCanvas()
  window.addEventListener('resize', setupSignatureCanvas)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', setupSignatureCanvas)
})
</script>

