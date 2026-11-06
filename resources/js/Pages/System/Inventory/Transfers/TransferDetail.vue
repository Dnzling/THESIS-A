<template>
  <div class="mx-auto max-w-7xl space-y-5 px-4 py-5 sm:px-6 lg:px-8">
    <header class="flex flex-wrap items-start justify-between gap-4">
      <div class="flex min-w-0 items-start gap-3">
        <Button icon="pi pi-arrow-left" rounded severity="secondary" outlined size="small" aria-label="Go back" @click="goBack" />
        <div class="min-w-0">
          <p class="text-xs font-semibold uppercase tracking-wide text-orange-600">Inventory stock transfer</p>
          <h1 class="mt-1 break-words text-xl font-bold text-slate-900 sm:text-2xl">{{ detail?.transfer_number || 'Transfer Details' }}</h1>
          <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-slate-600">
            <span>{{ detail?.from_branch?.name || detail?.fromBranch?.name || 'Source branch' }}</span>
            <i class="pi pi-arrow-right text-xs text-orange-500" />
            <span>{{ detail?.to_branch?.name || detail?.toBranch?.name || 'Receiving branch' }}</span>
          </p>
        </div>
      </div>
      <div v-if="detail" class="flex flex-wrap items-center gap-2 sm:justify-end">
        <Badge :value="transferStatusLabel" :severity="statusSeverity(detail.status)" />
        <Button label="Print" icon="pi pi-print" severity="secondary" outlined rounded size="small" :loading="printing" :disabled="loading" @click="printTransfer" />
      </div>
    </header>

    <Message v-if="loadError" severity="error" :closable="false">{{ loadError }}</Message>
    <div v-if="loading" class="space-y-4">
      <Skeleton height="8rem" class="rounded-2xl" />
      <Skeleton height="14rem" class="rounded-2xl" />
      <Skeleton height="12rem" class="rounded-2xl" />
    </div>

    <template v-else-if="detail">
      <div class="grid gap-4 lg:grid-cols-3">
        <Card class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm lg:col-span-2">
          <template #title><span class="text-base">Transfer Route</span></template>
          <template #content>
            <div class="grid gap-4 sm:grid-cols-[1fr_auto_1fr] sm:items-center">
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">From branch</p>
                <p class="mt-1 text-base font-semibold text-slate-900">{{ detail.from_branch?.name || detail.fromBranch?.name || '—' }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ [detail.from_branch?.city || detail.fromBranch?.city, detail.from_branch?.province || detail.fromBranch?.province].filter(Boolean).join(', ') || 'Source location' }}</p>
              </div>
              <i class="pi pi-arrow-right hidden text-orange-500 sm:block" />
              <div class="rounded-xl border border-orange-200 bg-orange-50/60 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-orange-700">Receiving branch</p>
                <p class="mt-1 text-base font-semibold text-slate-900">{{ detail.to_branch?.name || detail.toBranch?.name || '—' }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ [detail.to_branch?.city || detail.toBranch?.city, detail.to_branch?.province || detail.toBranch?.province].filter(Boolean).join(', ') || 'Destination location' }}</p>
              </div>
            </div>
            <dl class="mt-4 grid gap-x-6 gap-y-4 border-t border-slate-100 pt-4 sm:grid-cols-2 xl:grid-cols-4">
              <div><dt class="detail-label">Requested date</dt><dd class="detail-value">{{ formatDate(detail.requested_date || detail.created_at) }}</dd></div>
              <div><dt class="detail-label">Expected arrival</dt><dd class="detail-value">{{ formatDate(detail.expected_delivery_date) }}</dd></div>
              <div><dt class="detail-label">Requested by</dt><dd class="detail-value">{{ employeeName(detail.requested_by) }}</dd></div>
              <div><dt class="detail-label">Distance</dt><dd class="detail-value">{{ detail.distance_km ? `${formatNumber(detail.distance_km)} km` : '—' }}</dd></div>
            </dl>
          </template>
        </Card>

        <Card class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
          <template #title><span class="text-base">Transfer Summary</span></template>
          <template #content>
            <dl class="space-y-3">
              <div class="flex items-center justify-between gap-3 text-sm"><dt class="text-slate-500">Goods value</dt><dd class="font-medium text-slate-800">{{ money(detail.goods_value) }}</dd></div>
              <div class="flex items-center justify-between gap-3 text-sm"><dt class="text-slate-500">Transfer fee</dt><dd class="font-medium text-slate-800">{{ money(detail.transfer_cost) }}</dd></div>
              <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-3"><dt class="text-sm font-semibold text-slate-700">Estimated total value</dt><dd class="text-lg font-bold text-slate-900">{{ money(Number(detail.goods_value || 0) + Number(detail.transfer_cost || 0)) }}</dd></div>
              <div v-if="detail.reason" class="border-t border-slate-100 pt-3"><dt class="detail-label">Reason</dt><dd class="mt-1 text-sm text-slate-800">{{ detail.reason }}</dd></div>
              <div v-if="detail.notes" class="border-t border-slate-100 pt-3"><dt class="detail-label">Notes</dt><dd class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ detail.notes }}</dd></div>
              <div v-if="detail.rejection_reason" class="rounded-lg border border-red-200 bg-red-50 p-3"><dt class="text-xs font-semibold uppercase tracking-wide text-red-700">Rejection reason</dt><dd class="mt-1 text-sm text-red-800">{{ detail.rejection_reason }}</dd></div>
            </dl>
          </template>
        </Card>
      </div>

      <Card v-if="showShipmentOverview" class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
        <template #title><div class="flex items-center gap-2"><i class="pi pi-truck text-orange-600" /><span class="text-base">Delivery Details</span></div></template>
        <template #content>
          <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div><p class="detail-label">Driver</p><p class="detail-value">{{ detail.driver_name || 'Not assigned' }}</p></div>
            <div><p class="detail-label">Vehicle</p><p class="detail-value">{{ detail.vehicle_type || '—' }}</p></div>
            <div><p class="detail-label">Tracking reference</p><p class="detail-value break-all">{{ detail.tracking_number || '—' }}</p></div>
            <div><p class="detail-label">Driver contact</p><p class="detail-value">{{ detail.driver_contact || '—' }}</p></div>
          </div>
          <div class="mt-5 grid gap-3 sm:grid-cols-3">
            <div v-for="step in shipmentSteps" :key="step.key" class="flex items-center gap-3 rounded-xl border border-slate-200 p-3">
              <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-xs font-semibold" :class="step.active ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-500'">{{ step.index }}</span>
              <span class="text-sm font-medium" :class="step.active ? 'text-slate-900' : 'text-slate-500'">{{ step.label }}</span>
            </div>
          </div>
        </template>
      </Card>

      <Card class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
        <template #title>
          <div class="flex flex-wrap items-center justify-between gap-2">
            <span class="text-base">Transfer Items</span>
            <span class="text-xs font-normal text-slate-500">{{ detail.items?.length || 0 }} item(s)</span>
          </div>
        </template>
        <template #content>
          <DataTable :value="detail.items || []" class="p-datatable-sm" rowHover responsiveLayout="scroll">
            <Column header="Product" style="min-width: 14rem">
              <template #body="{ data }">
                <p class="font-semibold text-slate-900">{{ data.product?.product_name || data.product_name || 'Product unavailable' }}</p>
                <p class="mt-0.5 text-xs text-slate-500">SKU: {{ data.variation?.variation_sku || data.product?.sku || '—' }}</p>
              </template>
            </Column>
            <Column header="Variant" style="min-width: 9rem"><template #body="{ data }">{{ data.variation?.variation_name || 'Standard' }}</template></Column>
            <Column header="Requested" style="width: 8rem"><template #body="{ data }">{{ data.requested_quantity ?? data.quantity ?? 0 }}</template></Column>
            <Column header="Approved" style="width: 8rem"><template #body="{ data }">{{ data.approved_quantity ?? '—' }}</template></Column>
            <Column header="Received" style="width: 8rem"><template #body="{ data }">{{ data.received_quantity ?? '—' }}</template></Column>
            <Column header="Unit" style="width: 7rem"><template #body="{ data }">{{ data.variation?.unit_of_measurement || data.product?.unit_of_measurement || '—' }}</template></Column>
            <Column header="Item notes" style="min-width: 12rem"><template #body="{ data }">{{ data.notes || '—' }}</template></Column>
            <template #empty><div class="py-8 text-center text-sm text-slate-500">No items were included in this transfer.</div></template>
          </DataTable>
        </template>
      </Card>

      <Card class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
        <template #title><span class="text-base">Activity</span></template>
        <template #content>
          <ol v-if="transferTimeline.length" class="space-y-0">
            <li v-for="(event, index) in transferTimeline" :key="`${event.label}-${index}`" class="relative flex gap-3 pb-5 last:pb-0">
              <div class="flex w-5 shrink-0 flex-col items-center"><span class="mt-1 h-3 w-3 rounded-full" :class="index === 0 ? 'bg-orange-500' : 'bg-slate-300'" /><span v-if="index < transferTimeline.length - 1" class="mt-1 w-px flex-1 bg-slate-200" /></div>
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1"><p class="text-sm font-semibold text-slate-900">{{ event.label }}</p><time class="text-xs text-slate-500">{{ formatDateTime(event.date) }}</time></div>
                <p class="mt-0.5 text-xs text-slate-500">{{ event.by }}</p>
              </div>
            </li>
          </ol>
          <p v-else class="text-sm text-slate-500">No activity has been recorded yet.</p>
        </template>
      </Card>

      <div v-if="canApprove || canSendToLogistics || canCreateReceipt" class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-4 sm:flex-row sm:justify-end">
        <Button v-if="canApprove" label="Approve Transfer" icon="pi pi-check-circle" severity="warn" size="small" :loading="processing" @click="approveTransfer" />
        <Button v-if="canSendToLogistics" label="Send to Logistics" icon="pi pi-truck" severity="warn" size="small" :loading="processing" @click="sendToLogistics" />
        <Button v-if="canCreateReceipt" label="Receive Transfer" icon="pi pi-inbox" severity="warn" size="small" @click="openReceipt" />
      </div>
    </template>
    <Dialog v-model:visible="receiptVisible" modal header="Receive Delivered Transfer" :style="{ width: 'min(92vw, 680px)' }">
      <p class="mb-4 text-sm text-slate-600">Confirm the quantities physically received at the destination branch. Stock updates only after you submit.</p>
      <div class="space-y-3">
        <div v-for="item in receiptItems" :key="item.id" class="grid gap-3 rounded-lg border border-slate-200 p-3 sm:grid-cols-[1fr_110px_110px] sm:items-end">
          <div><p class="text-sm font-medium text-slate-900">{{ item.name }}</p><p class="text-xs text-slate-500">Approved: {{ item.approved }}</p></div>
          <div><label class="mb-1 block text-xs text-slate-600">Good</label><InputNumber v-model="item.received_quantity" :min="0" :max="item.approved" size="small" fluid /></div>
          <div><label class="mb-1 block text-xs text-slate-600">Damaged</label><InputNumber v-model="item.damaged_quantity" :min="0" :max="item.approved" size="small" fluid /></div>
        </div>
      </div>
      <p v-if="receiptError" class="mt-3 text-xs text-red-600">{{ receiptError }}</p>
      <template #footer><Button label="Cancel" severity="secondary" text size="small" @click="receiptVisible = false" /><Button label="Confirm Receipt" severity="warn" size="small" :loading="processing" @click="receiveTransfer" /></template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import axiosClient from '@/axios'
import inventoryService from '../../../../services/inventory.service'
import { useAuthStore } from '../../../../stores/auth'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const loadError = ref('')
const processing = ref(false)
const printing = ref(false)
const receiptVisible = ref(false)
const receiptError = ref('')
const receiptItems = ref<Array<{ id: number; name: string; approved: number; received_quantity: number; damaged_quantity: number }>>([])
const detail = ref<any>(null)
const authStore = useAuthStore()

const transferId = computed(() => Number(route.params.id))
const goBack = () => {
  if (window.history.state?.back) router.back()
  else router.push({ name: 'inventory.stock-requests' })
}
const isLogisticsProcessing = computed(() => {
  const status = String(detail.value?.status || '').toLowerCase()
  const notes = String(detail.value?.notes || '').toLowerCase()
  const hasDeliveryAssignment = !!detail.value?.driver_name || !!detail.value?.vehicle_type || !!detail.value?.tracking_number
  return status === 'in_transit' && notes.includes('sent to logistics') && !hasDeliveryAssignment
})
const canAction = computed(() => {
  if (isLogisticsProcessing.value) return false
  return ['approved', 'shipped', 'in_transit'].includes(detail.value?.status)
})
const canApprove = computed(() => ['pending_approval', 'requested'].includes(detail.value?.status) && authStore.hasPermission('inventory.transfers.approve'))
const canSendToLogistics = computed(() =>
  ['receiver_acknowledge', 'receiver_acknowledged'].includes(String(detail.value?.status || '').toLowerCase())
)
const canCreateReceipt = computed(() => detail.value?.delivery_status === 'delivered' && detail.value?.status !== 'received')
const transferStatusLabel = computed(() => detail.value?.delivery_status === 'delivered' && detail.value?.status !== 'received'
  ? 'Delivered · Awaiting Receipt'
  : formatStatusLabel(detail.value?.status || 'draft'))
const openReceipt = () => {
  receiptError.value = ''
  receiptItems.value = (detail.value?.items || []).map((item: any) => ({
    id: item.id,
    name: item.product?.product_name || 'Product',
    approved: Number(item.approved_quantity ?? item.requested_quantity ?? 0),
    received_quantity: Number(item.approved_quantity ?? item.requested_quantity ?? 0),
    damaged_quantity: 0,
  }))
  receiptVisible.value = true
}
const showShipmentOverview = computed(() => {
  const status = String(detail.value?.status || '').toLowerCase()
  return ['in_transit', 'out_for_delivery', 'received'].includes(status)
})
const shipmentSteps = computed(() => {
  const status = String(detail.value?.status || '').toLowerCase()
  const created = !!detail.value?.driver_name || !!detail.value?.vehicle_type
  const inTransit = ['in_transit', 'out_for_delivery', 'received'].includes(status)
  const delivered = status === 'received'

  return [
    { key: 'created', label: 'Delivery Created', index: 1, active: created },
    { key: 'transit', label: 'In Transit', index: 2, active: inTransit },
    { key: 'delivered', label: 'Delivered', index: 3, active: delivered },
  ]
})
const transferTimeline = computed(() => {
  const transfer = detail.value
  if (!transfer) return []

  const events = [
    { label: 'Request created', date: transfer.requested_date || transfer.created_at, by: employeeName(transfer.requested_by) },
    { label: 'Transfer approved', date: transfer.sender_approved_date, by: employeeName(transfer.sender_approved_by) },
    { label: 'Receiving branch acknowledged', date: transfer.receiver_acknowledged_date, by: employeeName(transfer.receiver_acknowledged_by) },
    { label: 'Finance approved', date: transfer.finance_approved_date, by: employeeName(transfer.finance_approved_by) },
    { label: 'Sent to logistics', date: transfer.shipped_date, by: employeeName(transfer.shipped_by) },
    { label: 'Out for delivery', date: transfer.out_for_delivery_at, by: transfer.driver_name || 'Driver' },
    { label: 'Delivered', date: transfer.delivered_at, by: transfer.driver_name || 'Driver' },
    { label: 'Received at destination', date: transfer.received_date, by: employeeName(transfer.received_by) },
  ]

  return events
    .filter(event => event.date)
    .sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime())
})

const loadDetail = async () => {
  loading.value = true
  loadError.value = ''
  try {
    const response = await inventoryService.getTransfer(transferId.value)
    detail.value = response?.data || null
    if (!detail.value) loadError.value = 'Transfer details could not be found.'
  } catch (error: any) {
    console.error('Failed to load transfer detail', error)
    detail.value = null
    loadError.value = error?.response?.data?.message || 'Unable to load this transfer. Please try again.'
  } finally {
    loading.value = false
  }
}

const shipTransfer = async () => {
  processing.value = true
  try {
    await inventoryService.shipTransfer(transferId.value)
    await loadDetail()
  } catch (error) {
    console.error('Failed to ship transfer', error)
  } finally {
    processing.value = false
  }
}

const receiveTransfer = async () => {
  if (receiptItems.value.some(item => item.received_quantity < 0 || item.damaged_quantity < 0 || item.received_quantity + item.damaged_quantity > item.approved)) {
    receiptError.value = 'Good and damaged quantities together cannot exceed the approved quantity.'
    return
  }
  processing.value = true
  try {
    await inventoryService.receiveTransfer(transferId.value, { items: receiptItems.value.map(({ id, received_quantity, damaged_quantity }) => ({ id, received_quantity, damaged_quantity })) })
    receiptVisible.value = false
    toast.add({ severity: 'success', summary: 'Transfer received', detail: 'Destination stock has been updated.', life: 2500 })
    await loadDetail()
  } catch (error: any) {
    console.error('Failed to receive transfer', error)
    receiptError.value = error?.response?.data?.message || 'Unable to receive the transfer.'
  } finally {
    processing.value = false
  }
}

const printTransfer = async () => {
  if (printing.value || !detail.value) return
  printing.value = true
  try {
    const response = await axiosClient.get(`/api/inventory/transfers/${transferId.value}/print`, { responseType: 'blob' })
    const url = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const printWindow = window.open(url, '_blank')
    if (printWindow) printWindow.opener = null
    setTimeout(() => URL.revokeObjectURL(url), 60_000)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Print Failed', detail: error?.response?.data?.message || 'Unable to create the transfer slip.', life: 3500 })
  } finally {
    printing.value = false
  }
}

const cancelTransfer = async () => {
  processing.value = true
  try {
    await inventoryService.cancelTransfer(transferId.value)
    await loadDetail()
  } catch (error) {
    console.error('Failed to cancel transfer', error)
  } finally {
    processing.value = false
  }
}

const approveTransfer = async () => {
  processing.value = true
  try {
    await inventoryService.approveTransfer(transferId.value)
    toast.add({ severity: 'success', summary: 'Approved', detail: 'Transfer approved successfully.', life: 2200 })
    await loadDetail()
  } catch (error) {
    console.error('Failed to approve transfer', error)
    toast.add({ severity: 'error', summary: 'Failed', detail: 'Unable to approve transfer.', life: 2600 })
  } finally {
    processing.value = false
  }
}

const sendToLogistics = async () => {
  processing.value = true
  try {
    await inventoryService.sendTransferToLogistics(transferId.value)
    toast.add({ severity: 'success', summary: 'Queued for Logistics', detail: 'Transfer was sent to logistics.', life: 2500 })
    await loadDetail()
  } catch (error: any) {
    console.error('Failed to send transfer to logistics', error)
    toast.add({
      severity: 'error',
      summary: 'Action Failed',
      detail: error?.response?.data?.message || 'Failed to send transfer to logistics.',
      life: 3000,
    })
  } finally {
    processing.value = false
  }
}

const statusSeverity = (status: string) => {
  const normalized = String(status || '').toLowerCase()
  const map: Record<string, string> = {
    draft: 'secondary',
    requested: 'warn',
    pending_approval: 'warn',
    submitted: 'info',
    sender_approved: 'info',
    receiver_acknowledge: 'warn',
    receiver_acknowledged: 'warn',
    approved: 'info',
    shipped: 'info',
    in_transit: 'info',
    out_for_delivery: 'info',
    received: 'success',
    completed: 'success',
    cancelled: 'secondary',
    rejected: 'danger',
  }
  return map[normalized] || 'secondary'
}

const formatStatusLabel = (status: string) => {
  const normalized = String(status || '').toLowerCase()
  if (normalized === 'in_transit' && isLogisticsProcessing.value) return 'Logistics Processing'
  const map: Record<string, string> = {
    draft: 'Draft',
    requested: 'Pending Approval',
    pending_approval: 'Pending Approval',
    submitted: 'Submitted',
    approved: 'Approved',
    sender_approved: 'Approved',
    shipped: 'Shipped',
    in_transit: 'In Transit',
    out_for_delivery: 'Out for Delivery',
    receiver_acknowledge: 'Receiver Acknowledged',
    receiver_acknowledged: 'Receiver Acknowledged',
    received: 'Received',
    completed: 'Completed',
    cancelled: 'Cancelled',
  }
  return map[normalized] || normalized.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

const formatDate = (value?: string) => {
  if (!value) return '-'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return '-'
  return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })
}

const formatDateTime = (value?: string) => {
  if (!value) return '—'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '—'
  return date.toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' })
}

const formatNumber = (value: unknown) => Number(value || 0).toLocaleString('en-PH', { maximumFractionDigits: 2 })

const employeeName = (employee: any) => {
  const user = employee?.user || employee
  const name = [user?.fname, user?.lname].filter(Boolean).join(' ').trim()
  return name || (employee?.id ? `Employee #${employee.id}` : 'System')
}

const money = (value: unknown) => new Intl.NumberFormat('en-PH', {
  style: 'currency',
  currency: 'PHP',
}).format(Number(value || 0))

onMounted(() => {
  loadDetail()
})
</script>

<style scoped>
.detail-label {
  @apply text-[11px] font-medium uppercase tracking-wide text-slate-500;
}

.detail-value {
  @apply mt-1 text-sm font-semibold text-slate-900;
}
</style>
