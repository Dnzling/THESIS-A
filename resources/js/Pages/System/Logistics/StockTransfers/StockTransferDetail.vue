<template>
  <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <Button icon="pi pi-arrow-left" text rounded @click="goBack" />
          <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Stock Transfer Detail</h1>
            <p class="mt-1 text-sm text-slate-500">{{ detail?.transfer_number || '-' }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Tag :value="statusLabel" :severity="statusSeverity(detail?.status)" />
          <Button
            v-if="canCreateDelivery"
            icon="pi pi-truck"
            label="Create Delivery"
            severity="success"
            @click="deliveryDialogVisible = true"
          />
          <Button icon="pi pi-refresh" label="Refresh" outlined @click="loadDetail" />
        </div>
      </div>
    </div>

    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Transfer Summary</template>
      <template #content>
        <div v-if="loading" class="text-sm text-slate-500">Loading transfer detail...</div>
        <div v-else-if="!detail" class="text-sm text-slate-500">No transfer data found.</div>
        <div v-else class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
          <div><span class="text-slate-500">From Branch:</span> <strong>{{ detail?.from_branch?.name || detail?.fromBranch?.name || '-' }}</strong></div>
          <div><span class="text-slate-500">To Branch:</span> <strong>{{ detail?.to_branch?.name || detail?.toBranch?.name || '-' }}</strong></div>
          <div><span class="text-slate-500">Requested Date:</span> <strong>{{ formatDate(detail?.requested_date || detail?.created_at) }}</strong></div>
          <div><span class="text-slate-500">Expected Delivery:</span> <strong>{{ formatDate(detail?.expected_delivery_date) }}</strong></div>
          <div><span class="text-slate-500">Tracking Number:</span> <strong>{{ detail?.tracking_number || '-' }}</strong></div>
          <div><span class="text-slate-500">Driver:</span> <strong>{{ detail?.driver_name || '-' }}</strong></div>
          <div><span class="text-slate-500">Delivery Fee:</span> <strong>₱0.00 (Stock Transfer)</strong></div>
          <div class="md:col-span-2"><span class="text-slate-500">Reason:</span> <strong>{{ detail?.reason || '-' }}</strong></div>
          <div class="md:col-span-2"><span class="text-slate-500">Notes:</span> <strong>{{ detail?.notes || '-' }}</strong></div>
        </div>
      </template>
    </Card>

    <Card v-if="showShipmentOverview" class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>
        <div class="flex items-center gap-2">
          <i class="pi pi-truck text-emerald-600"></i>
          <span>Shipment Overview</span>
        </div>
      </template>
      <template #content>
        <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-3">
          <div
            v-for="step in shipmentSteps"
            :key="step.key"
            class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2"
          >
            <span
              class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-semibold"
              :class="step.active ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-500'"
            >
              {{ step.index }}
            </span>
            <span class="text-sm font-medium" :class="step.active ? 'text-slate-900' : 'text-slate-500'">{{ step.label }}</span>
          </div>
        </div>

        <div class="text-sm text-slate-700">
          <div class="mb-2 flex flex-wrap gap-4">
            <span class="font-semibold">{{ detail?.driver_name || 'Driver not set' }}</span>
            <span>Truck/Van: {{ detail?.vehicle_type || '-' }}</span>
            <span>Tracking #: {{ detail?.tracking_number || '-' }}</span>
            <span>Contact: {{ detail?.driver_contact || '-' }}</span>
          </div>
          <div v-if="proofImages.length" class="mb-3 rounded-xl border border-slate-200 px-4 py-3">
            <div class="mb-2 text-sm font-semibold text-slate-800">Proof of Delivery</div>
            <div class="flex flex-wrap gap-2">
              <Button
                v-for="(img, idx) in proofImages"
                :key="`${img.url}-${idx}`"
                icon="pi pi-image"
                outlined
                size="small"
                :label="img.name || `Photo ${idx + 1}`"
                @click="openMedia(img.url)"
              />
            </div>
          </div>
          <div class="rounded-xl border border-slate-200">
            <div class="flex items-center justify-between gap-2 border-b border-slate-200 px-4 py-2">
              <div class="text-sm font-semibold text-slate-800">Delivery Logs</div>
              <Button
                v-if="canRecordShipmentLog"
                :icon="canMarkDelivered ? 'pi pi-check-circle' : 'pi pi-plus'"
                :label="canMarkDelivered ? 'Mark as Delivered' : 'Record a Log'"
                size="small"
                text
                @click="onShipmentActionClick"
              />
            </div>
            <div v-if="deliveryLogs.length" class="divide-y divide-slate-100">
              <div v-for="(log, idx) in deliveryLogs" :key="`${log.label}-${idx}`" class="px-4 py-3">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                  <span class="font-medium text-slate-900">{{ log.label }}</span>
                  <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ log.time }}</span>
                </div>
                <p v-if="log.by" class="mt-1 text-xs text-slate-500">By {{ log.by }}</p>
              </div>
            </div>
            <div v-else class="px-4 py-4 text-sm text-slate-500">No delivery logs yet.</div>
          </div>
        </div>
      </template>
    </Card>

    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Line Items</template>
      <template #content>
        <DataTable :value="detail?.items || []" dataKey="id" stripedRows>
          <template #empty>
            <div class="py-8 text-center text-slate-500">No transfer items found.</div>
          </template>

          <Column header="Product">
            <template #body="{ data }">
              {{ data.product?.product_name || '-' }}
            </template>
          </Column>
          <Column header="Requested Qty">
            <template #body="{ data }">
              {{ data.requested_quantity ?? 0 }}
            </template>
          </Column>
          <Column header="Approved Qty">
            <template #body="{ data }">
              {{ data.approved_quantity ?? '-' }}
            </template>
          </Column>
          <Column header="Shipped Qty">
            <template #body="{ data }">
              {{ data.shipped_quantity ?? '-' }}
            </template>
          </Column>
          <Column header="Received Qty">
            <template #body="{ data }">
              {{ data.received_quantity ?? '-' }}
            </template>
          </Column>
          <Column field="notes" header="Notes" />
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="deliveryDialogVisible" modal header="Delivery Assignment Form" class="w-full max-w-3xl">
      <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="submitDelivery">
        <div>
          <label class="mb-1 block text-sm text-slate-600">Logistics Employee</label>
          <Select
            v-model="deliveryForm.driver_user_id"
            :options="employees"
            optionLabel="name"
            optionValue="id"
            fluid
            filter
            placeholder="Select logistics employee"
          />
        </div>
        <div>
          <label class="mb-1 block text-sm text-slate-600">Vehicle</label>
          <Select
            v-model="deliveryForm.vehicle_id"
            :options="vehicles"
            optionLabel="label"
            optionValue="id"
            fluid
            filter
            placeholder="Select truck/van"
          />
        </div>
        <div>
          <label class="mb-1 block text-sm text-slate-600">Courier Contact Number</label>
          <InputText v-model="deliveryForm.courier_contact" fluid placeholder="09xxxxxxxxx" />
        </div>
        <div>
          <label class="mb-1 block text-sm text-slate-600">Estimated Delivery Time</label>
          <DatePicker v-model="deliveryForm.estimated_delivery_at" showTime hourFormat="12" fluid />
        </div>
        <div>
          <label class="mb-1 block text-sm text-slate-600">Tracking Number (Optional)</label>
          <InputText v-model="deliveryForm.tracking_number" fluid placeholder="TRK-..." />
        </div>
        <div class="md:col-span-2">
          <label class="mb-1 block text-sm text-slate-600">Notes (Optional)</label>
          <Textarea v-model="deliveryForm.notes" rows="3" fluid placeholder="Delivery assignment notes" />
        </div>
        <div class="md:col-span-2">
          <Message severity="info" :closable="false">No delivery fee will be charged for stock transfer logistics.</Message>
        </div>
      </form>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="deliveryDialogVisible = false" />
        <Button
          label="Create Delivery"
          icon="pi pi-check-circle"
          severity="success"
          :loading="submittingDelivery"
          :disabled="!canSubmitDelivery"
          @click="submitDelivery"
        />
      </template>
    </Dialog>

    <Dialog v-model:visible="recordLogDialogVisible" modal header="Record Delivery Log" class="w-full max-w-xl">
      <div class="space-y-3">
        <Select
          v-model="recordLogEvent"
          :options="recordLogEventOptions"
          optionLabel="label"
          optionValue="value"
          fluid
          placeholder="Select log event"
        />
        <Textarea
          v-model="recordLogNotes"
          rows="3"
          fluid
          placeholder="Add notes (optional)"
        />
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="recordLogDialogVisible = false" />
        <Button
          icon="pi pi-check"
          label="Save Log"
          :loading="savingLog"
          :disabled="!recordLogEvent"
          @click="saveShipmentLog"
        />
      </template>
    </Dialog>

    <Dialog v-model:visible="markDeliveredDialogVisible" modal header="Proof of Delivery" class="w-full max-w-2xl">
      <div class="space-y-3">
        <p class="text-sm text-slate-600">
          Attach at least one image proof of delivery before confirming. Photos become part of the delivery log.
        </p>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Received By <span class="text-rose-500">*</span></label>
          <InputText
            v-model="podReceivedBy"
            fluid
            placeholder="Name of person who received"
          />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Notes (optional)</label>
          <Textarea
            v-model="markDeliveredNotes"
            rows="4"
            fluid
            placeholder="Describe any observations, gate codes, or special instructions"
          />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Attachments</label>
          <input
            type="file"
            accept="image/*"
            multiple
            class="block w-full text-sm text-slate-600"
            @change="onPodAttachmentChange"
          />
          <p class="mt-1 text-xs text-slate-500">Minimum 1 photo, up to 10MB each.</p>
        </div>
        <p v-if="podAttachmentNames" class="text-xs text-slate-500">{{ podAttachmentNames }}</p>
        <div v-if="podAttachmentPreviews.length" class="mt-2 grid grid-cols-2 gap-2 md:grid-cols-3">
          <button
            v-for="(preview, idx) in podAttachmentPreviews"
            :key="`${preview.url}-${idx}`"
            type="button"
            class="overflow-hidden rounded-lg border border-slate-200 bg-slate-50"
            @click="openMedia(preview.url)"
          >
            <img :src="preview.url" :alt="preview.name || `Attachment ${idx + 1}`" class="h-24 w-full object-cover" />
            <div class="truncate px-2 py-1 text-left text-[11px] text-slate-600">{{ preview.name || `Attachment ${idx + 1}` }}</div>
          </button>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="markDeliveredDialogVisible = false" />
        <Button
          icon="pi pi-check-circle"
          label="Confirm Delivery"
          severity="success"
          :loading="markingDelivered"
          :disabled="!canConfirmDelivered"
          @click="markAsDelivered"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Message from 'primevue/message'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import inventoryService from '../../../../services/inventory.service'
import logisticsService from '../../../../services/logistics.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const detail = ref<any>(null)
const transferId = computed(() => Number(route.params.id || 0))
const deliveryDialogVisible = ref(false)
const submittingDelivery = ref(false)
const recordLogDialogVisible = ref(false)
const recordLogEvent = ref<string | null>(null)
const recordLogNotes = ref('')
const savingLog = ref(false)
const markDeliveredDialogVisible = ref(false)
const markDeliveredNotes = ref('')
const markingDelivered = ref(false)
const podReceivedBy = ref('')
const podAttachments = ref<File[]>([])
const podAttachmentPreviewUrls = ref<string[]>([])
const recordLogEventOptions = [
  { label: 'Arrived at Location', value: 'arrived_at_location' },
  { label: 'Unloading Started', value: 'unloading_started' },
  { label: 'Unloading Completed', value: 'unloading_completed' },
  { label: 'Delivery Delay', value: 'delivery_delay' },
  { label: 'Delivery Issue', value: 'delivery_issue' },
  { label: 'Received by Branch', value: 'received_by_branch' },
  { label: 'Custom Note', value: 'custom_note' },
]
const employees = ref<any[]>([])
const vehicles = ref<any[]>([])
const deliveryForm = reactive({
  driver_user_id: null as number | null,
  vehicle_id: null as number | null,
  courier_contact: '',
  estimated_delivery_at: null as Date | null,
  tracking_number: '',
  notes: '',
})

const loadDetail = async () => {
  if (!transferId.value) return
  loading.value = true
  try {
    const response = await inventoryService.getTransfer(transferId.value)
    detail.value = response?.data || null
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Load Failed',
      detail: error?.response?.data?.message || 'Failed to load stock transfer detail.',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const statusLabel = computed(() => {
  const status = String(detail.value?.status || '').toLowerCase()
  const notes = String(detail.value?.notes || '').toLowerCase()
  const hasDelivery = !!detail.value?.driver_name || !!detail.value?.vehicle_type || !!detail.value?.tracking_number
  if (status === 'in_transit' && notes.includes('sent to logistics') && !hasDelivery) return 'Logistics Processing'
  if (!status) return '-'
  return status.replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase())
})

const canCreateDelivery = computed(() => {
  const status = String(detail.value?.status || '').toLowerCase()
  const notes = String(detail.value?.notes || '').toLowerCase()
  const logisticsProcessing = status === 'in_transit' && notes.includes('sent to logistics')
  return logisticsProcessing && !detail.value?.driver_name
})

const showShipmentOverview = computed(() => {
  const status = String(detail.value?.status || '').toLowerCase()
  return ['in_transit', 'received'].includes(status)
})
const canRecordShipmentLog = computed(() => {
  const status = String(detail.value?.status || '').toLowerCase()
  return ['in_transit', 'received'].includes(status)
})
const isReceived = computed(() => String(detail.value?.status || '').toLowerCase() === 'received')

const shipmentSteps = computed(() => {
  const status = String(detail.value?.status || '').toLowerCase()
  const created = !!detail.value?.driver_name || !!detail.value?.vehicle_type
  const inTransit = status === 'in_transit' || status === 'received'
  const delivered = status === 'received'

  return [
    { key: 'created', label: 'Delivery Created', index: 1, active: created },
    { key: 'transit', label: 'In Transit', index: 2, active: inTransit },
    { key: 'delivered', label: 'Delivered', index: 3, active: delivered },
  ]
})

const parsedDeliveryLogs = computed(() => {
  const logs: Array<{ label: string; time: string; by?: string; eventKey?: string }> = []

  if (detail.value?.shipped_date) {
    logs.push({
      label: 'In Transit',
      time: formatDate(detail.value.shipped_date),
      by: detail.value?.driver_name || 'Logistics',
    })
  }

  if (detail.value?.received_date) {
    logs.unshift({
      label: 'Delivered',
      time: formatDate(detail.value.received_date),
      by: detail.value?.receivedBy?.fname
        ? `${detail.value.receivedBy.fname} ${detail.value.receivedBy.lname || ''}`.trim()
        : 'Receiver',
    })
  }

  const eventLabelMap: Record<string, string> = {
    arrived_at_location: 'Arrived at Location',
    unloading_started: 'Unloading Started',
    unloading_completed: 'Unloading Completed',
    delivery_delay: 'Delivery Delay',
    delivery_issue: 'Delivery Issue',
    received_by_branch: 'Received by Branch',
    custom_note: 'Custom Note',
  }

  const noteLines = String(detail.value?.notes || '')
    .split('\n')
    .map((line) => line.trim())
    .filter((line) => line.startsWith('LOG2|') || line.startsWith('LOG|'))

  noteLines.forEach((line) => {
    if (line.startsWith('LOG2|')) {
      const [, ts, by, eventKey, notes] = line.split('|')
      const baseLabel = eventLabelMap[String(eventKey || '').trim()] || 'Delivery Log'
      const label = String(notes || '').trim() ? `${baseLabel} - ${String(notes).trim()}` : baseLabel
      logs.unshift({
        label,
        time: formatDate(ts || ''),
        by: by || 'Logistics',
        eventKey: String(eventKey || ''),
      })
      return
    }

    // Backward compatibility for old LOG format.
    const [, ts, by, msg] = line.split('|')
    logs.unshift({
      label: msg || 'Delivery Log',
      time: formatDate(ts || ''),
      by: by || 'Logistics',
    })
  })

  return logs
})

const deliveryLogs = computed(() => parsedDeliveryLogs.value.map(({ label, time, by }) => ({ label, time, by })))

const hasUnloadingCompleted = computed(() =>
  parsedDeliveryLogs.value.some((log) => {
    const key = String(log.eventKey || '').toLowerCase()
    const label = String(log.label || '').toLowerCase()
    return key === 'unloading_completed' || label.startsWith('unloading completed')
  })
)

const canMarkDelivered = computed(() => canRecordShipmentLog.value && hasUnloadingCompleted.value && !isReceived.value)
const podAttachmentNames = computed(() => podAttachments.value.map((file) => file.name).join(', '))
const canConfirmDelivered = computed(() => podReceivedBy.value.trim().length > 0 && podAttachments.value.length > 0 && !markingDelivered.value)
const podAttachmentPreviews = computed(() =>
  podAttachments.value.map((file, index) => ({
    name: file.name,
    url: podAttachmentPreviewUrls.value[index] || '',
  })).filter((item) => !!item.url)
)
const proofImages = computed(() => {
  const lines = String(detail.value?.notes || '')
    .split('\n')
    .map((line) => line.trim())
    .filter((line) => line.startsWith('POD|'))

  return lines
    .map((line) => {
      const [, url, name] = line.split('|')
      return {
        url: String(url || '').trim(),
        name: String(name || '').trim(),
      }
    })
    .filter((entry) => !!entry.url)
})

const canSubmitDelivery = computed(() =>
  !!deliveryForm.driver_user_id && !!deliveryForm.vehicle_id && !!deliveryForm.courier_contact.trim()
)

const submitDelivery = async () => {
  if (!canSubmitDelivery.value) return
  submittingDelivery.value = true
  try {
    const selectedEmployee = employees.value.find((e: any) => Number(e.id) === Number(deliveryForm.driver_user_id))
    const selectedVehicle = vehicles.value.find((v: any) => Number(v.id) === Number(deliveryForm.vehicle_id))

    const driverName = selectedEmployee?.name || selectedEmployee?.full_name || 'Assigned Driver'
    const driverContact =
      deliveryForm.courier_contact.trim() ||
      selectedEmployee?.contact_number ||
      selectedEmployee?.phone ||
      selectedEmployee?.mobile ||
      ''
    const vehicleType = selectedVehicle?.vehicle_name || selectedVehicle?.label || 'Assigned Vehicle'

    await inventoryService.createTransferDelivery(transferId.value, {
      vehicle_type: vehicleType,
      driver_name: String(driverName).trim(),
      driver_contact: String(driverContact).trim(),
      tracking_number: deliveryForm.tracking_number.trim() || undefined,
      notes: deliveryForm.notes.trim() || undefined,
    })
    toast.add({
      severity: 'success',
      summary: 'Delivery Created',
      detail: 'Stock transfer delivery created with no charge.',
      life: 2500,
    })
    deliveryDialogVisible.value = false
    await loadDetail()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Create Failed',
      detail: error?.response?.data?.message || 'Failed to create delivery.',
      life: 3000,
    })
  } finally {
    submittingDelivery.value = false
  }
}

const saveShipmentLog = async () => {
  if (!recordLogEvent.value) return
  savingLog.value = true
  try {
    await inventoryService.addTransferDeliveryLog(transferId.value, {
      event: recordLogEvent.value as any,
      notes: recordLogNotes.value.trim() || undefined,
    })
    toast.add({
      severity: 'success',
      summary: 'Logged',
      detail: 'Delivery log recorded.',
      life: 2200,
    })
    recordLogEvent.value = null
    recordLogNotes.value = ''
    recordLogDialogVisible.value = false
    await loadDetail()
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

const onShipmentActionClick = () => {
  if (canMarkDelivered.value) {
    markDeliveredDialogVisible.value = true
    return
  }
  recordLogDialogVisible.value = true
}

const onPodAttachmentChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = Array.from(target.files || [])
  podAttachmentPreviewUrls.value.forEach((url) => URL.revokeObjectURL(url))
  podAttachmentPreviewUrls.value = files.map((file) => URL.createObjectURL(file))
  podAttachments.value = files
}

const openMedia = (url: string) => {
  if (!url) return
  window.open(url, '_blank')
}

const markAsDelivered = async () => {
  if (!detail.value?.items?.length) {
    toast.add({
      severity: 'error',
      summary: 'No Items',
      detail: 'No transfer items found to complete delivery.',
      life: 2800,
    })
    return
  }

  if (!podReceivedBy.value.trim()) {
    toast.add({
      severity: 'error',
      summary: 'Missing Receiver',
      detail: 'Please enter who received the stock transfer.',
      life: 2800,
    })
    return
  }

  if (!podAttachments.value.length) {
    toast.add({
      severity: 'error',
      summary: 'Missing Attachment',
      detail: 'Please attach at least one proof-of-delivery image.',
      life: 2800,
    })
    return
  }

  markingDelivered.value = true
  try {
    const items = detail.value.items.map((item: any) => ({
      id: Number(item.id),
      received_quantity: Number(item.shipped_quantity ?? item.approved_quantity ?? item.requested_quantity ?? 0),
      damaged_quantity: 0,
    }))

    const notesParts = [
      `Received By: ${podReceivedBy.value.trim()}`,
      markDeliveredNotes.value.trim() ? `Notes: ${markDeliveredNotes.value.trim()}` : null,
      podAttachments.value.length ? `Attachments: ${podAttachments.value.map((file) => file.name).join(', ')}` : null,
    ].filter(Boolean) as string[]

    const formData = new FormData()
    formData.append('notes', notesParts.join('\n'))
    formData.append('items_json', JSON.stringify(items))
    podAttachments.value.forEach((file) => {
      formData.append('photos[]', file)
    })

    await inventoryService.receiveTransfer(transferId.value, formData)

    toast.add({
      severity: 'success',
      summary: 'Delivered',
      detail: 'Stock transfer marked as delivered successfully.',
      life: 2500,
    })

    markDeliveredDialogVisible.value = false
    markDeliveredNotes.value = ''
    podReceivedBy.value = ''
    podAttachmentPreviewUrls.value.forEach((url) => URL.revokeObjectURL(url))
    podAttachmentPreviewUrls.value = []
    podAttachments.value = []
    await loadDetail()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Deliver Failed',
      detail: error?.response?.data?.message || 'Unable to mark transfer as delivered.',
      life: 3200,
    })
  } finally {
    markingDelivered.value = false
  }
}

const loadOptions = async () => {
  try {
    const [employeeRes, vehicleRes] = await Promise.all([
      logisticsService.getLogisticsEmployees(),
      logisticsService.getVehicles({ per_page: 100 }),
    ])

    employees.value = employeeRes?.data || []
    const vehicleRows = vehicleRes?.data?.data || []
    vehicles.value = vehicleRows.map((vehicle: any) => ({
      ...vehicle,
      label: `${vehicle.vehicle_name} (${vehicle.plate_number})`,
    }))
  } catch (error: any) {
    toast.add({
      severity: 'warn',
      summary: 'Options Incomplete',
      detail: error?.response?.data?.message || 'Failed to load drivers/vehicles.',
      life: 2500,
    })
  }
}

watch(
  () => deliveryForm.driver_user_id,
  (id) => {
    if (!id) return
    const selectedEmployee = employees.value.find((e: any) => Number(e.id) === Number(id))
    if (!deliveryForm.courier_contact && selectedEmployee) {
      deliveryForm.courier_contact =
        selectedEmployee.contact_number ||
        selectedEmployee.phone ||
        selectedEmployee.mobile ||
        ''
    }
  }
)

const statusSeverity = (status?: string) => {
  const s = String(status || '').toLowerCase()
  if (s === 'received') return 'success'
  if (s === 'in_transit') return 'info'
  if (s === 'receiver_acknowledged' || s === 'receiver_acknowledge') return 'warning'
  if (s === 'cancelled') return 'danger'
  return 'secondary'
}

const formatDate = (value?: string) => {
  if (!value) return '-'
  return new Date(value).toLocaleString('en-PH', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

const goBack = () => router.push({ name: 'logistics.stock-transfers' })

onMounted(async () => {
  await Promise.all([loadDetail(), loadOptions()])
})
</script>
