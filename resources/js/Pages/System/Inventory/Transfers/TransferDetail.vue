<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'inventory.transfers' })" />
        <div>
          <h2 class="text-2xl font-bold text-gray-800">Transfer Details</h2>
          <p class="text-sm text-gray-500 mt-1">Review and process stock transfer</p>
        </div>
      </div>
      <span
        class="inline-flex items-center rounded-full px-3 py-1.5 text-sm font-semibold"
        :class="statusBadgeClass(detail?.status || 'draft')"
      >
        {{ formatStatusLabel(detail?.status || 'draft') }}
      </span>
    </div>

    <div v-if="loading" class="space-y-4">
      <Skeleton height="180px" class="rounded-lg" />
      <Skeleton height="250px" class="rounded-lg" />
    </div>

    <div v-else class="space-y-6">
      <Card>
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <p class="text-xs text-gray-600">Transfer No.</p>
              <p class="font-semibold text-gray-900">{{ detail?.transfer_number || detail?.transfer_no || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">From</p>
              <p class="font-semibold text-gray-900">{{ detail?.from_branch?.name || detail?.fromBranch?.name || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">To</p>
              <p class="font-semibold text-gray-900">{{ detail?.to_branch?.name || detail?.toBranch?.name || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Date</p>
              <p class="font-semibold text-gray-900">{{ formatDate(detail?.requested_date || detail?.created_at) }}</p>
            </div>
          </div>
        </template>
      </Card>

      <Card>
        <template v-if="showShipmentOverview" #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-truck text-emerald-600"></i>
            <span>Shipment Overview</span>
          </div>
        </template>
        <template v-if="showShipmentOverview" #content>
          <div class="space-y-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
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
              <div class="rounded-xl border border-slate-200">
                <div class="border-b border-slate-200 px-4 py-2 text-sm font-semibold text-slate-800">Delivery Logs</div>
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
          </div>
        </template>
      </Card>

      <Card>
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-list text-emerald-600"></i>
            <span>Transfer Items</span>
          </div>
        </template>
        <template #content>
          <DataTable :value="detail?.items || []" class="p-datatable-sm" stripedRows>
            <Column header="Item">
              <template #body="{ data }">
                {{ data.product?.product_name || data.product_name || '-' }}
              </template>
            </Column>
            <Column header="Requested">
              <template #body="{ data }">
                {{ data.requested_quantity ?? data.quantity ?? 0 }}
              </template>
            </Column>
            <Column header="Approved">
              <template #body="{ data }">
                {{ data.approved_quantity ?? '-' }}
              </template>
            </Column>
            <Column header="Received">
              <template #body="{ data }">
                {{ data.received_quantity ?? '-' }}
              </template>
            </Column>
            <Column field="notes" header="Notes" />
          </DataTable>

          <div v-if="canApprove" class="pt-4 flex gap-2 justify-end">
            <Button label="Approve Transfer" icon="pi pi-check-circle" severity="success" :loading="processing" @click="approveTransfer" />
          </div>
          <div v-if="canSendToLogistics" class="pt-4 flex gap-2 justify-end">
            <Button
              label="Send to Logistics"
              icon="pi pi-truck"
              severity="help"
              :loading="processing"
              @click="sendToLogistics"
            />
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '../../../../services/inventory.service'
import { useAuthStore } from '../../../../stores/auth'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const processing = ref(false)
const detail = ref<any>(null)
const authStore = useAuthStore()

const transferId = computed(() => Number(route.params.id))
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
const showShipmentOverview = computed(() => {
  const status = String(detail.value?.status || '').toLowerCase()
  return ['in_transit', 'received'].includes(status)
})
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
const deliveryLogs = computed(() => {
  const logs: Array<{ label: string; time: string; by?: string }> = []

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

  return logs
})

const loadDetail = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getTransfer(transferId.value)
    detail.value = response?.data || null
  } catch (error) {
    console.error('Failed to load transfer detail', error)
    detail.value = null
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
  processing.value = true
  try {
    await inventoryService.receiveTransfer(transferId.value)
    await loadDetail()
  } catch (error) {
    console.error('Failed to receive transfer', error)
  } finally {
    processing.value = false
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
    requested: 'warning',
    pending_approval: 'warning',
    submitted: 'info',
    approved: 'warning',
    shipped: 'info',
    in_transit: 'info',
    received: 'success',
    completed: 'success',
    cancelled: 'danger',
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
    shipped: 'Shipped',
    in_transit: 'In Transit',
    receiver_acknowledge: 'Receiver Acknowledged',
    receiver_acknowledged: 'Receiver Acknowledged',
    received: 'Received',
    completed: 'Completed',
    cancelled: 'Cancelled',
  }
  return map[normalized] || normalized.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

const statusBadgeClass = (status: string) => {
  const normalized = String(status || '').toLowerCase()
  if (normalized === 'in_transit' && isLogisticsProcessing.value) return 'bg-purple-100 text-purple-700'
  const map: Record<string, string> = {
    draft: 'bg-slate-100 text-slate-700',
    requested: 'bg-amber-100 text-amber-700',
    pending_approval: 'bg-amber-100 text-amber-700',
    submitted: 'bg-sky-100 text-sky-700',
    approved: 'bg-indigo-100 text-indigo-700',
    shipped: 'bg-blue-100 text-blue-700',
    in_transit: 'bg-cyan-100 text-cyan-700',
    receiver_acknowledge: 'bg-violet-100 text-violet-700',
    receiver_acknowledged: 'bg-violet-100 text-violet-700',
    received: 'bg-emerald-100 text-emerald-700',
    completed: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
  }
  return map[normalized] || 'bg-slate-100 text-slate-700'
}

const formatDate = (value?: string) => {
  if (!value) return '-'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return '-'
  return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })
}

onMounted(() => {
  loadDetail()
})
</script>
