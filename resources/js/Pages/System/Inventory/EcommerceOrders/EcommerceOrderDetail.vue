<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <div class="flex items-center justify-between gap-4">
          <div class="flex items-center gap-2">
            <Button text severity="secondary" icon="pi pi-arrow-left" @click="goBack" />
            <div>
              <h1 class="text-2xl font-semibold text-gray-900">{{ order?.order_number || 'Order Detail' }}</h1>
              <p class="text-sm text-gray-500">View order and delivery details.</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <Button severity="info" outlined icon="pi pi-refresh" label="Refresh" @click="loadOrder" />
            <Button
              v-if="canSendToLogistics"
              severity="success"
              icon="pi pi-send"
              label="Send To Logistics"
              :loading="sendingToLogistics"
              @click="sendToLogistics"
            />
            <Button
              severity="secondary"
              icon="pi pi-print"
              label="Print Receipt"
              @click="printReceipt"
            />
          </div>
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
          <template #title>Order Summary</template>
          <template #content>
            <div class="grid grid-cols-2 gap-3 text-sm">
              <div><span class="text-gray-500">Customer:</span> <span class="font-medium text-gray-900">{{ order?.shipping_name || '-' }}</span></div>
              <div><span class="text-gray-500">Contact:</span> <span class="font-medium text-gray-900">{{ order?.shipping_phone || '-' }}</span></div>
              <div><span class="text-gray-500">Status:</span> <Tag :value="formatStatus(order?.primary_status || order?.status || 'pending')" :severity="statusSeverity(order?.primary_status || order?.status || 'pending')" /></div>
              <div><span class="text-gray-500">Payment:</span> <span class="font-medium text-gray-900">{{ order?.payment_method }}</span></div>
              <div><span class="text-gray-500">Payment Status:</span> <span class="font-medium text-gray-900">{{ order?.payment_status }}</span></div>
              <div class="col-span-2"><span class="text-gray-500">Address:</span> <span class="font-medium text-gray-900">{{ order?.shipping_address || '-' }}</span></div>
            </div>

            <Divider />

            <div class="flex items-center justify-between mb-2">
              <h4 class="text-sm font-semibold text-gray-900">Delivery Information</h4>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
              <div><span class="text-gray-500">Assigned Branch:</span> <span class="font-medium text-gray-900">{{ order?.assigned_branch?.name || '-' }}</span></div>
              <div><span class="text-gray-500">Delivery Status:</span> <Tag :value="formatStatus(order?.delivery?.status || 'not_assigned')" :severity="deliverySeverity(order?.delivery?.status || 'assigned')" /></div>
              <div><span class="text-gray-500">Tracking:</span> <span class="font-medium text-gray-900">{{ order?.delivery?.tracking_number || '-' }}</span></div>
              <div><span class="text-gray-500">Courier:</span> <span class="font-medium text-gray-900">{{ order?.delivery?.courier_name || '-' }}</span></div>
              <div><span class="text-gray-500">Courier Contact:</span> <span class="font-medium text-gray-900">{{ order?.delivery?.courier_contact || '-' }}</span></div>
              <div><span class="text-gray-500">Vehicle:</span> <span class="font-medium text-gray-900">{{ order?.delivery?.vehicle ? `${order.delivery.vehicle.vehicle_name} (${order.delivery.vehicle.plate_number})` : '-' }}</span></div>
              <div><span class="text-gray-500">ETA:</span> <span class="font-medium text-gray-900">{{ order?.delivery?.estimated_delivery_at ? formatDateTime(order.delivery.estimated_delivery_at) : '-' }}</span></div>
            </div>
          </template>
        </Card>

        <Card v-if="latestCancellation" class="rounded-2xl border border-gray-100 shadow-sm">
          <template #title>Cancellation Request</template>
          <template #content>
            <div class="space-y-3 text-sm">
              <div><span class="text-gray-500">Status:</span> <Tag :value="formatStatus(latestCancellation.status)" severity="warning" /></div>
              <div><span class="text-gray-500">Reason:</span> <span class="font-medium text-gray-900">{{ latestCancellation.reason || '-' }}</span></div>
              <div><span class="text-gray-500">Details:</span> <span class="font-medium text-gray-900">{{ latestCancellation.details || '-' }}</span></div>
              <div v-if="latestCancellation.review_notes"><span class="text-gray-500">Review Notes:</span> <span class="font-medium text-gray-900">{{ latestCancellation.review_notes }}</span></div>

              <div v-if="latestCancellation.status === 'pending_verification'" class="space-y-2">
                <label class="text-xs font-semibold text-gray-600">Review Notes (optional)</label>
                <Textarea v-model="reviewNotes" rows="3" autoResize class="w-full" placeholder="Add notes for this decision" />
              </div>

              <div class="flex flex-wrap gap-2" v-if="latestCancellation.status === 'pending_verification'">
                <Button
                  label="Approve Cancellation"
                  severity="danger"
                  :loading="reviewingCancellation"
                  @click="confirmReviewCancellation('approved')"
                />
                <Button
                  label="Reject"
                  severity="secondary"
                  outlined
                  :loading="reviewingCancellation"
                  @click="openRejectDialog"
                />
              </div>
            </div>
          </template>
        </Card>
      </div>

      <Card class="rounded-2xl border border-gray-100 shadow-sm">
        <template #title>Ordered Items</template>
        <template #content>
          <DataTable :value="order?.items || []" dataKey="id" stripedRows>
            <Column field="product_name" header="Product" />
            <Column field="sku" header="SKU" />
            <Column field="quantity" header="Qty" />
            <Column header="Stock Status">
              <template #body="{ data }">
                <Tag :value="formatStatus(data.branch_inventory?.stock_status || 'unknown')" :severity="(data.branch_inventory?.stock_status || '').includes('out') ? 'danger' : ((data.branch_inventory?.stock_status || '').includes('low') ? 'warning' : 'success')" />
              </template>
            </Column>
            <Column field="unit_price" header="Unit Price">
              <template #body="{ data }">{{ formatMoney(data.unit_price) }}</template>
            </Column>
            <Column field="line_total" header="Line Total">
              <template #body="{ data }">{{ formatMoney(data.line_total) }}</template>
            </Column>
          </DataTable>
        </template>
      </Card>

      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <Card class="rounded-2xl border border-gray-100 shadow-sm">
          <template #title>Customer Chat</template>
          <template #content>
            <div class="space-y-3">
              <Message severity="info" :closable="false">
                Chat replies are now handled in the Sales module for better team workflow.
              </Message>
              <Button
                label="Open Sales Chat Inbox"
                icon="pi pi-comments"
                severity="info"
                outlined
                @click="openSalesChat"
              />
            </div>
          </template>
        </Card>
      </div>

      <Card class="rounded-2xl border border-gray-100 shadow-sm">
        <template #title>Order Timeline</template>
        <template #content>
          <Timeline v-if="(order?.timeline || []).length" :value="order.timeline" class="w-full">
            <template #content="{ item }">
              <div class="pb-4">
                <div class="flex flex-wrap items-center gap-2">
                  <p class="text-sm font-semibold text-gray-900">{{ item.title }}</p>
                  <Tag v-if="item.status_to" :value="formatStatus(item.status_to)" severity="secondary" class="text-xs" />
                </div>
                <p class="mt-1 text-xs text-gray-600">{{ item.description || '-' }}</p>
                <p class="mt-1 text-xs text-gray-400">{{ formatDateTime(item.created_at) }} • {{ item.actor || 'System' }}</p>
              </div>
            </template>
          </Timeline>
          <div v-else class="text-sm text-gray-500">No timeline entries yet.</div>
        </template>
      </Card>
    </template>

    <ConfirmDialog />

    <Dialog v-model:visible="rejectDialogVisible" header="Reject Cancellation" modal class="w-full max-w-xl">
      <div class="space-y-3">
        <p class="text-sm text-gray-600">Select a reason and optionally add notes. This will reject the customer cancellation request.</p>
        <div class="space-y-2">
          <label class="text-sm text-gray-600">Reject Reason</label>
          <Select
            v-model="rejectDialog.reason"
            :options="rejectReasonOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Select a reason"
            fluid
            showClear
          />
          <small v-if="rejectDialogAttempted && !String(rejectDialog.reason || '').trim()" class="text-xs text-red-600">
            Reject reason is required.
          </small>
        </div>
        <div class="space-y-2">
          <label class="text-sm text-gray-600">Notes (optional)</label>
          <Textarea v-model="rejectDialog.notes" rows="4" autoResize class="w-full" placeholder="Add notes (optional)" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined size="small" @click="rejectDialogVisible = false" />
        <Button label="Reject" severity="danger" size="small" :loading="reviewingCancellation" @click="submitReject" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import salesService from '@/services/sales.service'
import { useAuthStore } from '@/stores/auth'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Skeleton from 'primevue/skeleton'
import Timeline from 'primevue/timeline'
import Divider from 'primevue/divider'
import Message from 'primevue/message'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import ConfirmDialog from 'primevue/confirmdialog'
import Dialog from 'primevue/dialog'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const authStore = useAuthStore()

const loading = ref(false)
const order = ref<any>(null)
const sendingToLogistics = ref(false)
const reviewingCancellation = ref(false)
const reviewNotes = ref('')
const rejectDialogVisible = ref(false)
const rejectDialogAttempted = ref(false)
const rejectDialog = ref<{ reason: string | null; notes: string }>({
  reason: null,
  notes: '',
})
const rejectReasonOptions = [
  { label: 'Changed mind', value: 'Changed mind' },
  { label: 'Found better price elsewhere', value: 'Found better price elsewhere' },
  { label: 'Incorrect order details', value: 'Incorrect order details' },
  { label: 'Cannot cancel (already in transit)', value: 'Cannot cancel (already in transit)' },
  { label: 'Other', value: 'Other' },
]

const openRejectDialog = () => {
  rejectDialogAttempted.value = false
  rejectDialog.value = { reason: null, notes: '' }
  rejectDialogVisible.value = true
}

const latestCancellation = computed(() => {
  const requests = order.value?.cancellation_requests || order.value?.cancellationRequests || []
  if (!Array.isArray(requests) || requests.length === 0) return null
  return [...requests].sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())[0]
})

const loadOrder = async () => {
  loading.value = true
  try {
    const res = await salesService.getEcommerceOrder(String(route.params.id))
    order.value = res?.data
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load order', life: 3000 })
  } finally {
    loading.value = false
  }
}

const openSalesChat = () => {
  if (!order.value) {
    router.push({ name: 'sales.chats' })
    return
  }

  router.push({
    name: 'sales.chats',
    query: {
      customer_user_id: order.value.user_id,
      order_id: order.value.id,
    },
  })
}

const canSendToLogistics = computed(() => {
  if (!order.value) return false
  if (!authStore.hasPermission('sales.order.approve')) return false
  if (order.value.delivery) return false
  const status = String(order.value.primary_status || order.value.status || '').toLowerCase()
  return ['pending', 'processing'].includes(status)
})

const sendToLogistics = async () => {
  if (!order.value) return
  sendingToLogistics.value = true
  try {
    await salesService.updateEcommerceOrderStatus(String(order.value.id), {
      status: 'ready_for_dispatch',
      notes: 'Sent to logistics for delivery assignment.',
    })
    toast.add({
      severity: 'success',
      summary: 'Queued for Logistics',
      detail: 'Order is ready for dispatch.',
      life: 3000,
    })
    await loadOrder()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Action Failed',
      detail: error?.response?.data?.message || 'Failed to send order to logistics.',
      life: 3000,
    })
  } finally {
    sendingToLogistics.value = false
  }
}

const printReceipt = () => {
  if (!order.value) return
  window.open(`/api/sales/ecommerce-orders/${order.value.id}/receipt`, '_blank')
}

const submitReject = async () => {
  if (!order.value || !latestCancellation.value) return
  rejectDialogAttempted.value = true
  const reason = String(rejectDialog.value.reason || '').trim()
  if (!reason) {
    toast.add({ severity: 'warn', summary: 'Required', detail: 'Please select a reject reason.', life: 2500 })
    return
  }

  rejectDialogVisible.value = false
  await confirmReviewCancellation('rejected', { reason, notes: String(rejectDialog.value.notes || '').trim() })
}

const confirmReviewCancellation = (status: 'approved' | 'rejected', rejectPayload?: { reason: string; notes?: string }) => {
  if (!order.value || !latestCancellation.value) return

  const label = status === 'approved' ? 'Approve cancellation?' : 'Reject cancellation?'
  const message = status === 'approved'
    ? 'This will approve the customer cancellation request.'
    : 'This will reject the customer cancellation request.'

  confirm.require({
    header: label,
    message,
    icon: 'pi pi-exclamation-triangle',
    rejectProps: { label: 'Cancel', outlined: true, size: 'small' },
    acceptProps: { label: 'Confirm', size: 'small', severity: status === 'rejected' ? 'danger' : 'success' },
    accept: async () => {
      reviewingCancellation.value = true
      try {
        const notes = String(reviewNotes.value || '').trim()
        const rejectReason = String(rejectPayload?.reason || '').trim()
        const rejectNotes = String(rejectPayload?.notes || '').trim()
        const combined = status === 'rejected'
          ? (rejectReason && rejectNotes ? `${rejectReason}\n\n${rejectNotes}` : (rejectReason || rejectNotes))
          : (notes || undefined)

        const response = await salesService.reviewEcommerceOrderCancellation(
          String(order.value.id),
          String(latestCancellation.value.id),
          { status, review_notes: combined }
        )
        order.value = response?.data || order.value
        reviewNotes.value = ''
        rejectDialogAttempted.value = false
        toast.add({
          severity: 'success',
          summary: 'Updated',
          detail: `Cancellation request ${status}.`,
          life: 3000,
        })
      } catch (error: any) {
        toast.add({
          severity: 'error',
          summary: 'Failed',
          detail: error?.response?.data?.message || 'Unable to review cancellation request.',
          life: 3000,
        })
      } finally {
        reviewingCancellation.value = false
      }
    },
  })
}

const formatStatus = (status: string) => status.replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase())
const formatMoney = (value: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
const formatDateTime = (value: string) => {
  if (!value) return '-'
  return new Date(value).toLocaleString('en-PH', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}
const deliverySeverity = (status: string) => {
  if (status === 'delivered') return 'success'
  if (status === 'failed_delivery' || status === 'cancelled') return 'danger'
  if (status === 'out_for_delivery') return 'warning'
  return 'info'
}

const statusSeverity = (status: string) => {
  if (status === 'delivered') return 'success'
  if (status === 'cancelled') return 'danger'
  if (status === 'pending' || status === 'pending_cancellation') return 'warning'
  return 'info'
}

const goBack = () => router.push({ name: 'sales.ecommerce-orders' })

onMounted(loadOrder)
</script>
