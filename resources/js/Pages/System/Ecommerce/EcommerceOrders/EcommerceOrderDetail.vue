<template>
  <div class="mx-auto max-w-7xl space-y-6 pb-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
          <div class="flex items-center gap-2">
            <Button text rounded severity="secondary" icon="pi pi-arrow-left" @click="goBack" />
            <div>
              <h1 class="text-2xl font-semibold text-gray-900">Ecommerce Order Details</h1>
              <p class="mt-1 text-sm text-gray-500">View complete order, payment, inventory, and delivery information</p>
            </div>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <Button severity="secondary" text size="small" icon="pi pi-refresh" label="Refresh" @click="loadOrder" />
            <Button
              v-if="canSendToLogistics"
              severity="success" size="small"
              icon="pi pi-send"
              label="Send To Logistics"
              :loading="sendingToLogistics"
              @click="sendToLogistics"
            />
            <Button
              severity="secondary" size="small" text
              icon="pi pi-print"
              label="Print Receipt"
              @click="printReceipt"
            />
          </div>
        </div>

    <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <Skeleton height="12rem" />
      <Skeleton height="12rem" />
      <Skeleton height="12rem" />
    </div>

    <template v-else>
      <div v-if="order" class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white p-4">
          <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Order Number</p>
          <p class="mt-1 text-lg font-semibold text-gray-900">{{ order.order_number || '—' }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
          <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Order Date</p>
          <p class="mt-1 text-lg font-semibold text-gray-900">{{ formatDateTime(order.placed_at || order.created_at) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
          <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Order Status</p>
          <Tag class="mt-2" :value="formatStatus(order.primary_status || order.status || 'pending')" :severity="statusSeverity(order.primary_status || order.status || 'pending')" />
        </div>
      </div>


        <Card class="rounded-2xl border border-gray-100 shadow-sm lg:col-span-2">
          <template #title><span class="text-base font-semibold text-gray-800">Customer &amp; Shipping Information</span></template>
          <template #content>
            <div class="grid grid-cols-1 gap-x-8 gap-y-4 text-sm sm:grid-cols-2">
              <div><p class="text-xs font-medium uppercase tracking-wide text-gray-500">Customer</p><p class="mt-1 font-medium text-gray-900">{{ order?.shipping_name || customerName }}</p></div>
              <div><p class="text-xs font-medium uppercase tracking-wide text-gray-500">Email</p><p class="mt-1 font-medium text-gray-900">{{ order?.shipping_email || order?.user?.email || '—' }}</p></div>
              <div><p class="text-xs font-medium uppercase tracking-wide text-gray-500">Contact Number</p><p class="mt-1 font-medium text-gray-900">{{ order?.shipping_phone || '—' }}</p></div>
              <div><p class="text-xs font-medium uppercase tracking-wide text-gray-500">Assigned Branch</p><p class="mt-1 font-medium text-gray-900">{{ order?.assigned_branch?.name || '—' }}</p></div>
              <div class="sm:col-span-2">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Delivery Address</p>
                <p class="mt-1 font-medium leading-6 text-gray-900">{{ order?.shipping_address || '—' }}</p>
              </div>
            </div>

            <Divider />

            <div class="flex items-center justify-between mb-2">
              <h4 class="text-sm font-semibold text-gray-900">Delivery Information</h4>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
              <div><span class="text-gray-500">Delivery Status:</span> <Tag :value="formatStatus(order?.delivery?.status || 'not_assigned')" :severity="deliverySeverity(order?.delivery?.status || 'assigned')" /></div>
              <div><span class="text-gray-500">Tracking:</span> <span class="font-medium text-gray-900">{{ order?.delivery?.tracking_number || '-' }}</span></div>
              <div><span class="text-gray-500">Courier:</span> <span class="font-medium text-gray-900">{{ order?.delivery?.courier_name || '-' }}</span></div>
              <div><span class="text-gray-500">Courier Contact:</span> <span class="font-medium text-gray-900">{{ order?.delivery?.courier_contact || '-' }}</span></div>
              <div class="rounded-xl border border-blue-100 bg-blue-50/60 p-3 md:col-span-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Assigned Driver</p>
                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                  <div><span class="text-xs text-gray-500">Name</span><p class="font-semibold text-gray-900">{{ driverName }}</p></div>
                  <div><span class="text-xs text-gray-500">Employee No.</span><p class="font-medium text-gray-900">{{ order?.delivery?.driver?.employee?.employee_number || '—' }}</p></div>
                  <div><span class="text-xs text-gray-500">Contact</span><p class="font-medium text-gray-900">{{ order?.delivery?.driver?.phone_number || order?.delivery?.courier_contact || '—' }}</p></div>
                  <div><span class="text-xs text-gray-500">Email / Role</span><p class="font-medium text-gray-900">{{ order?.delivery?.driver?.email || '—' }} · {{ driverRole }}</p></div>
                </div>
              </div>
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


      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white lg:col-span-2">
          <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
            <div class="flex items-center gap-2">
              <i class="pi pi-shopping-cart text-gray-500"></i>
              <h3 class="font-medium text-gray-700">Order Items ({{ order?.items?.length || 0 }})</h3>
            </div>
          </div>
          <DataTable :value="order?.items || []" dataKey="id" rowHover class="text-sm">
            <Column header="#" style="width: 4rem"><template #body="{ index }">{{ index + 1 }}</template></Column>
            <Column header="Product" style="min-width: 220px">
              <template #body="{ data }">
                <p class="font-medium text-gray-900">{{ data.product_name || data.product?.product_name || '—' }}</p>
                <p class="mt-1 text-xs text-gray-500">SKU: {{ data.sku || data.product?.sku || '—' }}</p>
                <p v-if="data.branch_inventory?.variation" class="mt-1 text-xs text-orange-600">{{ data.branch_inventory.variation.variation_name }}</p>
              </template>
            </Column>
            <Column header="Quantity / Unit" style="min-width: 145px">
              <template #body="{ data }"><span class="font-medium">{{ Number(data.quantity || 0).toLocaleString() }} {{ data.product?.unit_of_measurement || 'unit' }}</span></template>
            </Column>
            <Column header="Stock Status" style="min-width: 125px">
              <template #body="{ data }"><Tag :value="formatStatus(data.branch_inventory?.stock_status || 'unknown')" :severity="stockSeverity(data.branch_inventory?.stock_status)" /></template>
            </Column>
            <Column header="Unit Price" style="min-width: 125px"><template #body="{ data }"><span class="font-mono">{{ formatMoney(data.unit_price) }}</span></template></Column>
            <Column header="Line Total" style="min-width: 135px"><template #body="{ data }"><span class="font-mono font-semibold text-gray-900">{{ formatMoney(data.line_total) }}</span></template></Column>
            <template #empty><div class="py-8 text-center text-sm text-gray-500">No order items found.</div></template>
          </DataTable>
        </div>

        <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:sticky lg:top-6">
          <div class="mb-5 flex items-center gap-2">
            <i class="pi pi-calculator text-slate-500"></i>
            <h3 class="font-semibold text-slate-900">Order Cost Summary</h3>
          </div>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between gap-4 text-slate-600"><span>Subtotal</span><span class="font-medium text-slate-900">{{ formatMoney(order?.subtotal) }}</span></div>
            <div class="flex justify-between gap-4 text-slate-600"><span>Discount</span><span class="font-medium text-slate-900">- {{ formatMoney(order?.discount_amount) }}</span></div>
            <div class="flex justify-between gap-4 text-slate-600"><span>VAT</span><span class="font-medium text-slate-900">{{ formatMoney(order?.tax_amount) }}</span></div>
            <div class="flex justify-between gap-4 text-slate-600"><span>Shipping Fee</span><span class="font-medium text-slate-900">{{ formatMoney(order?.shipping_fee) }}</span></div>
            <div class="flex justify-between gap-4 text-slate-600"><span>Payment Method</span><span class="font-medium text-slate-900">{{ formatStatus(order?.payment_method || '—') }}</span></div>
            <div class="flex items-center justify-between gap-4 text-slate-600"><span>Payment Status</span><Tag :value="formatStatus(order?.payment_status || 'pending')" :severity="paymentSeverity(order?.payment_status)" /></div>
          </div>
          <div class="my-5 border-t border-slate-200"></div>
          <div class="flex items-center justify-between gap-4 rounded-xl bg-slate-900 px-4 py-3 text-white">
            <span class="font-semibold">Total Amount</span><span class="text-xl font-bold">{{ formatMoney(order?.total_amount) }}</span>
          </div>
        </aside>
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
                <p v-if="item.meta?.location_address" class="mt-2 rounded-xl bg-blue-50 px-3 py-2 text-sm text-blue-800">
                  <i class="pi pi-map-marker mr-1" />{{ item.meta.location_address }}
                </p>
                <a v-if="item.meta?.proof_photo_url" :href="item.meta.proof_photo_url" target="_blank" rel="noopener"
                  class="group relative mt-3 block w-fit overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                  <img :src="item.meta.proof_photo_url" alt="Delivery proof"
                    class="h-28 w-36 object-cover transition group-hover:scale-105" />
                  <span class="absolute inset-x-0 bottom-0 bg-slate-950/65 px-2 py-1 text-center text-xs text-white">View attachment</span>
                </a>
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
import SystemLayout from '@/Layouts/SystemLayout.vue'
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

defineOptions({ layout: SystemLayout })

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

const customerName = computed(() => {
  const firstName = String(order.value?.user?.fname || '').trim()
  const lastName = String(order.value?.user?.lname || '').trim()
  return `${firstName} ${lastName}`.trim() || '—'
})

const driverName = computed(() => {
  const driver = order.value?.delivery?.driver
  const name = `${driver?.fname || ''} ${driver?.lname || ''}`.trim()
  return name || order.value?.delivery?.courier_name || '—'
})

const driverRole = computed(() => order.value?.delivery?.driver?.role?.display_name || order.value?.delivery?.driver?.role?.name || 'Driver')

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

const formatStatus = (status: string) => String(status || '—').replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase())
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

const stockSeverity = (status?: string) => {
  const value = String(status || '').toLowerCase()
  if (value.includes('out')) return 'danger'
  if (value.includes('low')) return 'warn'
  if (value === 'in_stock') return 'success'
  return 'secondary'
}

const paymentSeverity = (status?: string) => {
  const value = String(status || '').toLowerCase()
  if (['paid', 'succeeded', 'completed'].includes(value)) return 'success'
  if (['failed', 'cancelled', 'canceled', 'refunded'].includes(value)) return 'danger'
  return 'warn'
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
