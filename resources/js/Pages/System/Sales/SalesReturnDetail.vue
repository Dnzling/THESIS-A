<template>
  <div class="max-w-6xl mx-auto space-y-6 pb-6 p-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'sales.returns' })" />
        <div>
          <h2 class="text-2xl font-bold text-gray-900">Return Detail</h2>
          <p class="text-sm text-gray-500 mt-1">Review evidence, item info, and status.</p>
        </div>
      </div>
      <div class="flex flex-wrap gap-2">
        <Button v-if="returnRequest?.order_id" icon="pi pi-external-link" label="Open Order" severity="secondary" outlined
          size="small" @click="openOrder" />
        <Button icon="pi pi-images" label="Attachments" severity="secondary" outlined size="small"
          :disabled="!attachmentsCount" @click="attachmentsDialogVisible = true" />
        <Tag :value="prettyStatus(returnRequest?.status)" :severity="statusSeverity(returnRequest?.status)" />
      </div>
    </div>
  
    <div v-if="loading" class="flex justify-center py-12">
      <ProgressSpinner />
    </div>
  
    <div v-else-if="returnRequest" class="space-y-4">
      <Card class="rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
            <div class="space-y-2">
              <div class="flex flex-wrap gap-2 items-center">
                <Tag :value="returnRequest.order?.order_number || `Order #${returnRequest.order_id}`" severity="secondary"
                  class="font-mono" />
                <Tag :value="`Item #${returnRequest.order_item_id}`" severity="secondary" />
                <Tag :value="`Qty: ${returnRequest.requested_quantity ?? 1}`" severity="info" />
              </div>
              <div class="text-sm text-gray-700">
                <span class="font-medium">Customer:</span>
                {{ returnRequest.user?.full_name || returnRequest.user?.email || 'N/A' }}
                <span class="text-gray-400">•</span>
                {{ returnRequest.user?.email || '' }}
              </div>
              <div class="text-sm text-gray-700">
                <span class="font-medium">Requested:</span> {{ formatDateTime(returnRequest.created_at) }}
              </div>
              <div v-if="returnRequest.reviewer" class="text-sm text-gray-700">
                <span class="font-medium">Reviewed by:</span>
                {{ returnRequest.reviewer?.name || 'N/A' }}
                <span v-if="returnRequest.reviewed_at" class="text-gray-400">•</span>
                <span v-if="returnRequest.reviewed_at">{{ formatDateTime(returnRequest.reviewed_at) }}</span>
              </div>
            </div>
  
            <div class="md:text-right">
              <p class="text-xs text-gray-500">Status</p>
              <p class="text-lg font-semibold text-gray-900">{{ prettyStatus(returnRequest.status) }}</p>
              <p v-if="attachmentsCount" class="text-xs text-gray-500 mt-1">{{ attachmentsCount }} attachment(s)</p>
            </div>
          </div>
        </template>
      </Card>
  
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card class="rounded-2xl border border-gray-100 shadow-sm">
          <template #content>
            <p class="text-xs text-gray-500 mb-1">Product</p>
            <p class="text-sm font-semibold text-gray-900">
              {{ returnRequest.order_item?.product?.product_name || returnRequest.order_item?.product_name || 'N/A' }}
            </p>
            <p class="text-xs text-gray-500 mt-1">
              SKU: {{ returnRequest.order_item?.product?.sku || returnRequest.order_item?.sku || 'N/A' }}
            </p>
          </template>
        </Card>
  
        <Card class="rounded-2xl border border-gray-100 shadow-sm">
          <template #content>
            <p class="text-xs text-gray-500 mb-1">Reason</p>
            <p class="text-sm font-semibold text-gray-900 whitespace-pre-line">{{ returnRequest.reason || '—' }}</p>
          </template>
        </Card>
  
        <Card class="rounded-2xl border border-gray-100 shadow-sm">
          <template #content>
            <p class="text-xs text-gray-500 mb-1">Details</p>
            <p class="text-sm text-gray-700 whitespace-pre-line">{{ returnRequest.details || '—' }}</p>
          </template>
        </Card>
      </div>
  
      <Card class="rounded-2xl border border-gray-100 shadow-sm">
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-info-circle text-indigo-600"></i>
            <span>Review Notes</span>
          </div>
        </template>
        <template #content>
          <div class="text-sm text-gray-700 whitespace-pre-line">
            {{ returnRequest.review_notes || 'No review notes yet.' }}
          </div>
        </template>
      </Card>
  
      <Card class="rounded-2xl border border-gray-100 shadow-sm">
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-history text-indigo-600"></i>
            <span>Activity</span>
          </div>
        </template>
        <template #content>
          <Timeline :value="activityTimeline" align="left" class="text-sm">
            <template #content="{ item }">
              <div class="flex flex-col">
                <span class="font-medium text-gray-900">{{ item.title }}</span>
                <span class="text-xs text-gray-500">{{ item.subtitle }}</span>
              </div>
            </template>
          </Timeline>
        </template>
      </Card>
  
      <div class="flex flex-wrap justify-end gap-2">
        <Button v-if="canReject" icon="pi pi-times" label="Reject" severity="danger" size="small"
          :loading="statusUpdating" @click="openNotesThenConfirm('rejected')" />
        <Button v-if="canApprove" icon="pi pi-check" label="Approve" severity="success" size="small"
          :loading="statusUpdating" @click="confirmUpdate('approved')" />
  
        <Button v-if="canMarkReceived" icon="pi pi-box" label="Mark Received" severity="info" outlined size="small"
          :loading="statusUpdating" @click="receiveDialogVisible = true" />
        <Button v-if="canMarkRefunded" icon="pi pi-credit-card" label="Mark Refunded" severity="help" outlined
          size="small" :loading="statusUpdating" @click="refundDialogVisible = true" />
        <Button v-if="canSchedulePickup" icon="pi pi-calendar-plus" label="Schedule Pickup" severity="info" outlined
          size="small" :loading="pickupScheduling" @click="pickupDialogVisible = true" />
        <Button v-if="returnRequest?.pickup?.id" icon="pi pi-truck" label="Open Pickup" severity="secondary" outlined
          size="small" @click="openPickup" />
      </div>
    </div>
  
  
    <div v-else class="py-10 text-center text-sm text-gray-600">
      Return request not found.
    </div>
  
    <Dialog v-model:visible="attachmentsDialogVisible" header="Attachments" modal class="w-full max-w-5xl">
      <div v-if="attachments.length" class="space-y-3">
        <div class="flex items-center justify-between gap-2">
          <div class="text-sm text-gray-700">
            {{ attachments.length }} attachment(s)
          </div>
          <div class="flex gap-2">
            <Button icon="pi pi-download" label="Download current" size="small" severity="secondary" outlined
              :disabled="!selectedAttachment" @click="downloadSelected" />
            <Button icon="pi pi-external-link" label="Open current" size="small" severity="secondary" outlined
              :disabled="!selectedAttachment" @click="openSelectedInNewTab" />
          </div>
        </div>
        <Galleria :value="attachments" :numVisible="6" :circular="true" :showItemNavigators="true" :showThumbnails="true"
          containerStyle="max-width: 100%" v-model:activeIndex="activeAttachmentIndex">
          <template #item="{ item }">
            <div class="flex justify-center bg-black/5 rounded-lg overflow-hidden">
              <img :src="item.url" :alt="item.name" class="max-h-[520px] w-auto object-contain cursor-zoom-in"
                @click="openImageZoom(item)" />
            </div>
          </template>
          <template #thumbnail="{ item }">
            <img :src="item.url" :alt="item.name" class="h-14 w-14 object-cover rounded-md" />
          </template>
        </Galleria>
      </div>
      <div v-else class="py-10 text-center text-sm text-gray-600">No attachments.</div>
      <template #footer>
        <Button label="Close" severity="secondary" outlined size="small" @click="attachmentsDialogVisible = false" />
      </template>
    </Dialog>
  
    <Dialog v-model:visible="pickupDialogVisible" header="Schedule Pickup" modal class="w-full max-w-xl">
      <div class="space-y-3">
        <p class="text-sm text-gray-600">Create a Logistics pickup job for this approved return.</p>
        <div>
          <label class="mb-1 block text-sm text-gray-600">Scheduled At</label>
          <DatePicker v-model="pickupForm.scheduled_at" :minDate="getTodayDate()" showIcon showTime hourFormat="12" class="w-full" />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="mb-1 block text-sm text-gray-600">Pickup Name</label>
            <InputText v-model="pickupForm.pickup_name" class="w-full" />
          </div>
          <div>
            <label class="mb-1 block text-sm text-gray-600">Pickup Phone</label>
            <InputText v-model="pickupForm.pickup_phone" class="w-full" />
          </div>
        </div>
        <div>
          <label class="mb-1 block text-sm text-gray-600">Pickup Address</label>
          <Textarea v-model="pickupForm.pickup_address" rows="3" class="w-full" autoResize />
        </div>
        <div>
          <label class="mb-1 block text-sm text-gray-600">Notes</label>
          <Textarea v-model="pickupForm.notes" rows="2" class="w-full" autoResize />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined size="small" @click="pickupDialogVisible = false" />
        <Button icon="pi pi-check" label="Create Pickup" size="small" :loading="pickupScheduling"
          :disabled="!pickupForm.scheduled_at" @click="schedulePickup" />
      </template>
    </Dialog>
  
    <Dialog v-model:visible="receiveDialogVisible" header="Receive Return (Inventory)" modal class="w-full max-w-xl">
      <div class="space-y-3">
        <p class="text-sm text-gray-600">This will post an inventory transaction and update stock/damaged counts.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="mb-1 block text-sm text-gray-600">Received Quantity</label>
            <InputNumber v-model="receiveForm.received_quantity" :min="1" :max="Number(returnRequest?.requested_quantity ?? 1)" class="w-full" />
          </div>
          <div>
            <label class="mb-1 block text-sm text-gray-600">Condition</label>
            <Select v-model="receiveForm.condition" :options="receiveConditionOptions" optionLabel="label"
              optionValue="value" class="w-full" />
          </div>
        </div>
        <div>
          <label class="mb-1 block text-sm text-gray-600">Notes</label>
          <Textarea v-model="receiveForm.notes" rows="2" class="w-full" autoResize />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined size="small" @click="receiveDialogVisible = false" />
        <Button icon="pi pi-check" label="Post Receive" size="small" :loading="receiving" @click="confirmReceive" />
      </template>
    </Dialog>
  
    <Dialog v-model:visible="refundDialogVisible" header="Create Refund (Finance)" modal class="w-full max-w-xl">
      <div class="space-y-3">
        <p class="text-sm text-gray-600">This will create a finance refund record. Optionally mark as approved to set
          return as refunded.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="mb-1 block text-sm text-gray-600">Amount</label>
            <InputNumber v-model="refundForm.amount" :min="0" mode="currency" currency="PHP" locale="en-PH"
              class="w-full" />
          </div>
          <div class="flex items-end">
            <div class="flex items-center gap-2">
              <InputSwitch v-model="refundForm.mark_as_approved" />
              <span class="text-sm text-gray-700">Mark as approved</span>
            </div>
          </div>
        </div>
        <div>
          <label class="mb-1 block text-sm text-gray-600">Reason</label>
          <Textarea v-model="refundForm.reason" rows="2" class="w-full" autoResize />
        </div>
        <div>
          <label class="mb-1 block text-sm text-gray-600">Notes</label>
          <Textarea v-model="refundForm.notes" rows="2" class="w-full" autoResize />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined size="small" @click="refundDialogVisible = false" />
        <Button icon="pi pi-check" label="Create Refund" size="small" :loading="refunding" @click="confirmRefund" />
      </template>
    </Dialog>
  
    <Dialog v-model:visible="notesDialogVisible" header="Review Notes" modal class="w-full max-w-xl">
      <div class="space-y-3">
        <p class="text-sm text-gray-600">
          Add a short note for the customer/internal team. Notes are required when rejecting.
        </p>
        <div v-if="pendingStatus === 'rejected'" class="space-y-2">
          <label class="block text-sm text-gray-600">Reject Reason</label>
          <Select
            v-model="pendingRejectReason"
            :options="rejectReasonOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Select a reason"
            fluid
            showClear
          />
        </div>
        <Textarea v-model="pendingReviewNotes" rows="5" class="w-full" placeholder="Type notes..." autoResize />
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined size="small" @click="closeNotesDialog" />
        <Button label="Continue" size="small" :disabled="notesContinueDisabled" @click="confirmUpdate(pendingStatus)" />
      </template>
    </Dialog>
  
    <Dialog v-model:visible="imageZoomVisible" header="Preview" modal class="w-full max-w-6xl">
      <div class="flex justify-center bg-black/5 rounded-lg overflow-hidden">
        <img v-if="zoomImageUrl" :src="zoomImageUrl" alt="Preview" class="max-h-[75vh] w-auto object-contain" />
      </div>
      <template #footer>
        <Button label="Close" severity="secondary" outlined size="small" @click="imageZoomVisible = false" />
      </template>
    </Dialog>
  
    <ConfirmDialog />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import salesService from '@/services/sales.service'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const confirm = useConfirm()

const id = computed(() => Number((route as any).params?.id))
const loading = ref(false)
const returnRequest = ref<any>(null)
const statusUpdating = ref(false)

const attachmentsDialogVisible = ref(false)
const activeAttachmentIndex = ref(0)

const selectedAttachment = computed(() => attachments.value[activeAttachmentIndex.value] || null)

const openSelectedInNewTab = () => {
  if (!selectedAttachment.value) return
  window.open(selectedAttachment.value.url, '_blank')
}

const downloadSelected = () => {
  if (!selectedAttachment.value) return
  const link = document.createElement('a')
  link.href = selectedAttachment.value.url
  link.download = selectedAttachment.value.name || 'attachment'
  document.body.appendChild(link)
  link.click()
  link.remove()
}

const imageZoomVisible = ref(false)
const zoomImageUrl = ref<string | null>(null)
const openImageZoom = (item: { url: string }) => {
  zoomImageUrl.value = item.url
  imageZoomVisible.value = true
}

const attachments = computed<{ url: string; name: string }[]>(() => {
  const urls: string[] = Array.isArray(returnRequest.value?.evidence_urls) ? returnRequest.value.evidence_urls : []
  return urls.map((url, idx) => ({ url, name: `Attachment ${idx + 1}` }))
})

const attachmentsCount = computed(() => attachments.value.length)

const loadReturn = async () => {
  loading.value = true
  try {
    const res = await salesService.getReturn(id.value)
    returnRequest.value = res?.data || null
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to load return detail',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const canApprove = computed(() => String(returnRequest.value?.status || '') === 'pending_verification')
const canReject = computed(() => ['pending_verification', 'approved'].includes(String(returnRequest.value?.status || '')))
const canMarkReceived = computed(() => {
  if (String(returnRequest.value?.status || '') !== 'approved') return false
  const pickupStatus = String(returnRequest.value?.pickup?.status || '')
  return pickupStatus === 'picked_up'
})
const canMarkRefunded = computed(() => String(returnRequest.value?.status || '') === 'received')
const canSchedulePickup = computed(() => String(returnRequest.value?.status || '') === 'approved' && !returnRequest.value?.pickup?.id)

const openOrder = () => {
  const orderId = returnRequest.value?.order_id
  if (!orderId) return
  router.push({ name: 'sales.ecommerce-orders.detail', params: { id: orderId } })
}

const openPickup = () => {
  const pickupId = returnRequest.value?.pickup?.id
  if (!pickupId) return
  router.push({ name: 'logistics.return-pickups.detail', params: { id: pickupId } })
}

const pickupDialogVisible = ref(false)
const pickupScheduling = ref(false)
const pickupForm = reactive({
  scheduled_at: null as any,
  pickup_name: '',
  pickup_phone: '',
  pickup_address: '',
  notes: '',
})

const getTodayDate = () => new Date()

const toIsoDateTime = (date: Date) => {
  const d = new Date(date)
  const yyyy = d.getFullYear()
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const dd = String(d.getDate()).padStart(2, '0')
  const hh = String(d.getHours()).padStart(2, '0')
  const mi = String(d.getMinutes()).padStart(2, '0')
  const ss = String(d.getSeconds()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd} ${hh}:${mi}:${ss}`
}

const schedulePickup = async () => {
  if (!pickupForm.scheduled_at) return
  pickupScheduling.value = true
  try {
    const payload = {
      scheduled_at: toIsoDateTime(pickupForm.scheduled_at),
      pickup_name: pickupForm.pickup_name || undefined,
      pickup_phone: pickupForm.pickup_phone || undefined,
      pickup_address: pickupForm.pickup_address || undefined,
      notes: pickupForm.notes || undefined,
    }
    const res = await salesService.scheduleReturnPickup(id.value, payload)
    returnRequest.value = res?.data || returnRequest.value
    toast.add({ severity: 'success', summary: 'Scheduled', detail: res?.message || 'Pickup scheduled.', life: 2500 })
    pickupDialogVisible.value = false
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Failed to schedule pickup.', life: 3000 })
  } finally {
    pickupScheduling.value = false
  }
}

watch(
  () => pickupDialogVisible.value,
  (visible) => {
    if (!visible) return
    const order = returnRequest.value?.order || {}
    pickupForm.scheduled_at = null
    pickupForm.pickup_name = order.shipping_name || returnRequest.value?.user?.name || ''
    pickupForm.pickup_phone = order.shipping_phone || ''
    pickupForm.pickup_address = order.shipping_address || ''
    pickupForm.notes = ''
  },
)

const notesDialogVisible = ref(false)
const pendingStatus = ref<'approved' | 'rejected' | 'received' | 'refunded'>('approved')
const pendingReviewNotes = ref('')
const pendingRejectReason = ref<string | null>(null)
const rejectReasonOptions = [
  { label: 'Not eligible / outside return policy', value: 'Not eligible / outside return policy' },
  { label: 'Insufficient evidence', value: 'Insufficient evidence' },
  { label: 'Item already used / damaged by customer', value: 'Item already used / damaged by customer' },
  { label: 'Return window expired', value: 'Return window expired' },
  { label: 'Other', value: 'Other' },
]

const receiveDialogVisible = ref(false)
const receiving = ref(false)
const receiveConditionOptions = [
  { label: 'Resellable (Back to stock)', value: 'resellable' },
  { label: 'Damaged (Write to damaged)', value: 'damaged' },
]
const receiveForm = reactive({
  received_quantity: 1,
  condition: 'resellable' as 'resellable' | 'damaged',
  notes: '',
})

const confirmReceive = () => {
  const maxQty = Number(returnRequest.value?.requested_quantity ?? 1)
  const qty = Number(receiveForm.received_quantity || 1)
  if (qty < 1 || qty > maxQty) {
    toast.add({ severity: 'warn', summary: 'Invalid', detail: `Received quantity must be between 1 and ${maxQty}.`, life: 2500 })
    return
  }
  confirm.require({
    header: 'Post inventory receive?',
    message: 'This will update inventory and mark the return as received.',
    icon: 'pi pi-exclamation-triangle',
    rejectProps: { label: 'Cancel', outlined: true, size: 'small' },
    acceptProps: { label: 'Confirm', size: 'small', severity: 'success' },
    accept: async () => {
      await postReceive()
    },
  })
}

const postReceive = async () => {
  receiving.value = true
  try {
    const res = await salesService.receiveReturn(id.value, {
      received_quantity: Number(receiveForm.received_quantity || 1),
      condition: receiveForm.condition,
      notes: receiveForm.notes || undefined,
    })
    returnRequest.value = res?.data || returnRequest.value
    toast.add({ severity: 'success', summary: 'Received', detail: res?.message || 'Inventory updated.', life: 2500 })
    receiveDialogVisible.value = false
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Failed to receive return.', life: 3000 })
  } finally {
    receiving.value = false
  }
}

const refundDialogVisible = ref(false)
const refunding = ref(false)
const refundForm = reactive({
  amount: 0,
  reason: '',
  notes: '',
  mark_as_approved: true,
})

const confirmRefund = () => {
  confirm.require({
    header: 'Create refund record?',
    message: refundForm.mark_as_approved ? 'This will create an approved refund and mark the return as refunded.' : 'This will create a pending refund record.',
    icon: 'pi pi-exclamation-triangle',
    rejectProps: { label: 'Cancel', outlined: true, size: 'small' },
    acceptProps: { label: 'Confirm', size: 'small', severity: 'success' },
    accept: async () => {
      await postRefund()
    },
  })
}

const postRefund = async () => {
  refunding.value = true
  try {
    const res = await salesService.createReturnRefund(id.value, {
      amount: Number(refundForm.amount || 0),
      reason: refundForm.reason || undefined,
      notes: refundForm.notes || undefined,
      mark_as_approved: !!refundForm.mark_as_approved,
    })
    returnRequest.value = res?.data?.return || returnRequest.value
    toast.add({ severity: 'success', summary: 'Refund', detail: res?.message || 'Refund record created.', life: 2500 })
    refundDialogVisible.value = false
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Failed to create refund.', life: 3000 })
  } finally {
    refunding.value = false
  }
}

watch(
  () => receiveDialogVisible.value,
  (visible) => {
    if (!visible) return
    receiveForm.received_quantity = Number(returnRequest.value?.requested_quantity ?? 1)
    receiveForm.condition = 'resellable'
    receiveForm.notes = ''
  },
)

watch(
  () => refundDialogVisible.value,
  (visible) => {
    if (!visible) return
    const unitPrice = Number(returnRequest.value?.order_item?.unit_price || 0)
    const qty = Number(returnRequest.value?.requested_quantity ?? 1)
    refundForm.amount = unitPrice * qty
    refundForm.reason = `Refund for return #${id.value}`
    refundForm.notes = ''
    refundForm.mark_as_approved = true
  },
)

const notesContinueDisabled = computed(() => {
  if (pendingStatus.value !== 'rejected') return false
  const hasReason = !!String(pendingRejectReason.value || '').trim().length
  const hasNotes = !!String(pendingReviewNotes.value || '').trim().length
  return !hasReason && !hasNotes
})

const openNotesThenConfirm = (nextStatus: 'rejected') => {
  pendingStatus.value = nextStatus
  pendingReviewNotes.value = returnRequest.value?.review_notes || ''
  pendingRejectReason.value = null
  notesDialogVisible.value = true
}

const closeNotesDialog = () => {
  notesDialogVisible.value = false
}

const confirmUpdate = (nextStatus: 'approved' | 'rejected' | 'received' | 'refunded') => {
  if (nextStatus === 'rejected') {
    const hasReason = !!String(pendingRejectReason.value || '').trim().length
    const hasNotes = !!String(pendingReviewNotes.value || '').trim().length
    if (!hasReason && !hasNotes) {
      toast.add({ severity: 'warn', summary: 'Required', detail: 'Please select a reject reason or add notes.', life: 2500 })
      return
    }
  }

  const labels: Record<string, string> = {
    approved: 'Approve this return?',
    rejected: 'Reject this return?',
    received: 'Mark as received?',
    refunded: 'Mark as refunded?',
  }
  const messages: Record<string, string> = {
    approved: 'This will set the return request to Approved.',
    rejected: 'This will set the return request to Rejected.',
    received: 'This will set the return request to Received.',
    refunded: 'This will set the return request to Refunded.',
  }

  confirm.require({
    header: labels[nextStatus] || 'Confirm',
    message: messages[nextStatus] || 'Continue?',
    icon: 'pi pi-exclamation-triangle',
    rejectProps: { label: 'Cancel', outlined: true, size: 'small' },
    acceptProps: {
      label: 'Confirm',
      size: 'small',
      severity: nextStatus === 'rejected' ? 'danger' : 'success',
    },
    accept: async () => {
      notesDialogVisible.value = false
      await updateStatus(nextStatus)
    },
  })
}

const updateStatus = async (nextStatus: 'approved' | 'rejected' | 'received' | 'refunded') => {
  if (!id.value) return
  statusUpdating.value = true
  try {
    const rejectReason = String(pendingRejectReason.value || '').trim()
    const rejectNotes = String(pendingReviewNotes.value || '').trim()
    const combinedRejectNotes = rejectReason && rejectNotes ? `${rejectReason}\n\n${rejectNotes}` : (rejectReason || rejectNotes)
    const res = await salesService.updateReturnStatus(id.value, {
      status: nextStatus,
      review_notes: nextStatus === 'rejected' ? combinedRejectNotes : undefined,
    })
    returnRequest.value = res?.data || returnRequest.value
    toast.add({
      severity: 'success',
      summary: 'Updated',
      detail: res?.message || 'Return status updated.',
      life: 2500,
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to update status',
      life: 3000,
    })
  } finally {
    statusUpdating.value = false
  }
}

const activityTimeline = computed(() => {
  const items: any[] = []
  const createdAt = returnRequest.value?.created_at
  if (createdAt) {
    items.push({
      title: 'Return requested',
      subtitle: formatDateTime(createdAt),
    })
  }
  const reviewedAt = returnRequest.value?.reviewed_at
  const reviewer = returnRequest.value?.reviewer?.full_name || returnRequest.value?.reviewer?.email
  if (reviewedAt) {
    items.push({
      title: `Reviewed${reviewer ? ` by ${reviewer}` : ''}`,
      subtitle: formatDateTime(reviewedAt),
    })
  }
  const updatedAt = returnRequest.value?.updated_at
  if (updatedAt) {
    items.push({
      title: `Last updated (${prettyStatus(returnRequest.value?.status)})`,
      subtitle: formatDateTime(updatedAt),
    })
  }
  return items
})

const prettyStatus = (value: any) => {
  const normalized = String(value || '').toLowerCase()
  if (normalized === 'pending_verification') return 'Return Pending'
  const v = String(value || '').replace(/_/g, ' ')
  return v ? v.charAt(0).toUpperCase() + v.slice(1) : '—'
}

const statusSeverity = (status: any) => {
  const normalized = String(status || '').toLowerCase()
  if (normalized === 'approved' || normalized === 'received' || normalized === 'refunded') return 'success'
  if (normalized === 'rejected') return 'danger'
  return 'warning'
}

const formatDateTime = (value: any) => {
  if (!value) return '—'
  const date = new Date(value)
  return new Intl.DateTimeFormat('en-PH', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

onMounted(loadReturn)
</script>
