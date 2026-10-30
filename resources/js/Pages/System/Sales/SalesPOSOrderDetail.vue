<template>
  <div class="mx-auto max-w-7xl space-y-6 pb-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div class="flex items-center gap-2">
        <Button icon="pi pi-arrow-left" severity="secondary" text rounded aria-label="Back to POS orders" @click="goBack" />
        <div>
          <h1 class="text-2xl font-semibold text-slate-900">POS Order Details</h1>
          <p class="mt-1 text-sm text-slate-500">Review the order, payment, items, and fulfillment progress.</p>
        </div>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <Button icon="pi pi-refresh" severity="secondary" text rounded aria-label="Refresh order" :loading="loading" @click="loadOrder" />
        <Button icon="pi pi-history" label="Timeline" severity="secondary" outlined size="small" :disabled="!order" @click="timelineDialogVisible = true" />
        <Button v-if="canSendToLogistics" icon="pi pi-send" label="Mark Ready for Dispatch" size="small" :loading="sendingToLogistics" :disabled="sendDisabled" @click="confirmReadyForDispatch" />
        <Button icon="pi pi-print" label="Print Receipt" severity="secondary" outlined size="small" :loading="printingReceipt" :disabled="!order || order.payment_status !== 'paid'" @click="printReceipt" />
        <Button v-if="order?.payment_status === 'paid' && !order?.refund_request && authStore.hasPermission('sales.refunds.manage')" label="Request Refund" severity="secondary" outlined size="small" @click="refundDialogVisible = true" />
      </div>
    </div>

    <div v-if="loading && !order" class="grid gap-4 md:grid-cols-3">
      <Skeleton v-for="n in 3" :key="n" height="7.5rem" />
    </div>
    <div v-else-if="!order" class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-sm text-slate-500">
      Unable to load this POS order. <Button label="Try again" text size="small" @click="loadOrder" />
    </div>
    <template v-else>
      <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Order Number</p>
          <p class="mt-2 break-all text-lg font-semibold text-slate-900">{{ order.order_number || '-' }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Order Date</p>
          <p class="mt-2 text-lg font-semibold text-slate-900">{{ formatDateTime(order.created_at) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Order Status</p>
          <Badge class="mt-3" :value="formatLabel(order.status)" :severity="statusSeverity(order.status)" />
          <p class="mt-2 text-xs text-slate-500">{{ order.delivery_required ? 'Store delivery' : 'In-store purchase' }}</p>
        </div>
      </div>

      <Card class="rounded-2xl border border-slate-200 shadow-sm">
        <template #title><span class="text-base font-semibold text-slate-900">Customer &amp; Fulfillment</span></template>
        <template #content>
          <div class="grid gap-x-8 gap-y-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Customer</p><p class="mt-1 font-medium text-slate-900">{{ order.customer_name || 'Walk-in customer' }}</p></div>
            <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Email</p><p class="mt-1 break-all font-medium text-slate-900">{{ order.delivery_email || '-' }}</p></div>
            <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Contact Number</p><p class="mt-1 font-medium text-slate-900">{{ order.customer_phone || '-' }}</p></div>
            <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Fulfillment</p><p class="mt-1 font-medium text-slate-900">{{ order.delivery_required ? 'Delivery' : 'In-store' }}</p></div>
            <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Branch</p><p class="mt-1 font-medium text-slate-900">{{ order.branch?.name || '-' }}</p></div>
            <div v-if="order.delivery_required"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Delivery Status</p><Badge class="mt-1" :value="formatLabel(order.delivery?.status || 'pending')" :severity="statusSeverity(order.delivery?.status || 'pending')" /></div>
            <div v-if="order.delivery_required" class="sm:col-span-2 lg:col-span-3"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Delivery Address</p><p class="mt-1 font-medium leading-6 text-slate-900">{{ order.delivery_address || '-' }}</p></div>
          </div>

          <div v-if="order.delivery_required && order.delivery" class="mt-5 grid gap-4 border-t border-slate-100 pt-5 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Tracking Number</p><p class="mt-1 font-medium text-slate-900">{{ order.delivery.tracking_number || 'Not assigned' }}</p></div>
            <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Courier</p><p class="mt-1 font-medium text-slate-900">{{ order.delivery.courier_name || 'Not assigned' }}</p></div>
            <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Courier Contact</p><p class="mt-1 font-medium text-slate-900">{{ order.delivery.courier_contact || '-' }}</p></div>
            <div v-if="order.delivery.scheduled_delivery_at"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Scheduled Delivery</p><p class="mt-1 font-medium text-slate-900">{{ formatDateTime(order.delivery.scheduled_delivery_at) }}</p></div>
            <div v-if="order.delivery_notes" class="sm:col-span-2"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Delivery Notes</p><p class="mt-1 whitespace-pre-line font-medium text-slate-900">{{ order.delivery_notes }}</p></div>
          </div>
        </template>
      </Card>

      <div class="grid gap-5 lg:grid-cols-3">
        <div class="min-w-0 rounded-2xl border border-slate-200 bg-white lg:col-span-2">
          <div class="border-b border-slate-100 bg-slate-50 px-5 py-4"><h2 class="font-semibold text-slate-900">Order Items ({{ order.items?.length || 0 }})</h2></div>
          <div class="overflow-x-auto"><DataTable :value="order.items || []" dataKey="id" rowHover size="small" class="min-w-[640px] text-sm">
            <Column header="Product" style="min-width: 260px"><template #body="{ data }"><p class="font-medium text-slate-900">{{ data.product_name || data.product?.product_name || '-' }}</p><p class="mt-1 text-xs text-slate-500">SKU: {{ data.sku || data.variation?.variation_sku || data.product?.sku || '-' }}</p><p v-if="data.variation?.variation_name" class="mt-1 text-xs text-orange-600">{{ data.variation.variation_name }}</p><div v-if="data.customization_request" class="mt-2 rounded-lg border border-orange-100 bg-orange-50 p-2 text-xs"><p class="font-semibold text-orange-800"><i class="pi pi-palette mr-1" />Upholstery customization</p><p class="mt-1 whitespace-pre-line text-slate-700">{{ data.customization_request }}</p><p class="mt-1 text-slate-500">Estimated lead time: {{ data.customization_lead_time_days }} days</p></div></template></Column>
            <Column header="Quantity" style="min-width: 115px"><template #body="{ data }">{{ Number(data.quantity || 0).toLocaleString() }} {{ data.product?.unit_of_measurement || 'unit' }}</template></Column>
            <Column header="Unit Price" style="min-width: 145px"><template #body="{ data }"><p>{{ money(data.unit_price) }}</p><p v-if="Number(data.customization_fee || 0) > 0" class="mt-1 text-xs text-orange-700">+ {{ money(data.customization_fee) }} customization</p></template></Column>
            <Column header="Line Total" style="min-width: 135px"><template #body="{ data }"><span class="font-semibold text-slate-900">{{ money(data.line_total) }}</span></template></Column>
            <template #empty><div class="py-8 text-center text-sm text-slate-500">No items recorded for this order.</div></template>
          </DataTable></div>
        </div>

        <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:sticky lg:top-6">
          <h2 class="mb-5 font-semibold text-slate-900">Order Cost Summary</h2>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between gap-4"><span class="text-slate-500">Subtotal</span><span class="font-medium text-slate-900">{{ money(order.subtotal) }}</span></div>
            <div v-if="Number(order.discount_amount || 0) > 0" class="flex justify-between gap-4"><span class="text-slate-500">Discount</span><span class="font-medium text-slate-900">- {{ money(order.discount_amount) }}</span></div>
            <div v-if="Number(order.tax_amount || 0) > 0" class="flex justify-between gap-4"><span class="text-slate-500">VAT</span><span class="font-medium text-slate-900">{{ money(order.tax_amount) }}</span></div>
            <div v-if="order.delivery_required" class="flex justify-between gap-4"><span class="text-slate-500">Shipping Fee</span><span class="font-medium text-slate-900">{{ money(order.shipping_fee) }}</span></div>
            <div v-if="order.refund_request" class="flex items-center justify-between gap-4"><span class="text-slate-500">Refund</span><Badge :value="formatLabel(order.refund_request.status)" :severity="statusSeverity(order.refund_request.status)" /></div>
            <div class="flex items-center justify-between gap-4"><span class="text-slate-500">Payment Status</span><Badge :value="formatLabel(order.payment_status)" :severity="paymentStatusSeverity(order.payment_status)" /></div>
          </div>
          <div class="mt-5 flex items-center justify-between gap-4 border-t border-slate-200 pt-4"><span class="font-semibold text-slate-900">Total Amount</span><span class="text-xl font-bold text-orange-600">{{ money(order.total_amount) }}</span></div>
        </aside>
      </div>

      <Card class="rounded-2xl border border-slate-200 shadow-sm">
        <template #title><span class="text-base font-semibold text-slate-900">Payment Details</span></template>
        <template #content>
          <div class="grid gap-x-8 gap-y-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Method</p><p class="mt-1 font-medium text-slate-900">{{ formatLabel(order.payment?.payment_method || order.payment_method) }}</p></div>
            <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Status</p><Badge class="mt-1" :value="formatLabel(order.payment_status)" :severity="paymentStatusSeverity(order.payment_status)" /></div>
            <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Reference</p><p class="mt-1 break-all font-mono text-xs font-medium text-slate-900">{{ order.payment_reference || order.payment?.provider_reference || '-' }}</p></div>
            <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Paid At</p><p class="mt-1 font-medium text-slate-900">{{ formatDateTime(order.paid_at || order.payment?.paid_at) }}</p></div>
            <div v-if="order.payment_method === 'cash'"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Amount Tendered</p><p class="mt-1 font-medium text-slate-900">{{ money(order.amount_tendered) }}</p></div>
            <div v-if="order.payment_method === 'cash'"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Change</p><p class="mt-1 font-medium text-emerald-700">{{ money(order.change_amount) }}</p></div>
          </div>
          <div v-if="order.payment?.payment_provider === 'paymongo' && order.payment_status !== 'paid'" class="mt-5 flex flex-wrap gap-2 border-t border-slate-100 pt-5">
            <Button label="Continue Payment" icon="pi pi-external-link" severity="warn" size="small" :loading="continuingPayment" @click="continueOnlinePayment" />
            <Button label="Refresh Payment Status" icon="pi pi-refresh" severity="secondary" outlined size="small" :loading="syncingPayment" @click="syncPayment" />
          </div>
        </template>
      </Card>

    </template>
    <Dialog v-model:visible="timelineDialogVisible" modal header="Order Timeline" class="w-full max-w-2xl">
      <div class="max-h-[70vh] overflow-y-auto">
        <ol v-if="timeline.length" class="divide-y divide-slate-100">
          <li v-for="(event, index) in timeline" :key="`${event.title}-${event.created_at}-${index}`" class="flex gap-4 py-4 first:pt-0 last:pb-0">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-orange-50 text-orange-600"><i :class="event.icon" /></div>
            <div class="min-w-0 flex-1"><p class="text-sm font-semibold text-slate-900">{{ event.title }}</p><p v-if="event.description" class="mt-1 text-sm text-slate-600">{{ event.description }}</p><p class="mt-1 text-xs text-slate-500">{{ formatDateTime(event.created_at) }}</p></div>
          </li>
        </ol>
        <p v-else class="py-8 text-center text-sm text-slate-500">No timeline entries yet.</p>
      </div>
    </Dialog>
    <Dialog v-model:visible="refundDialogVisible" modal header="Request POS Refund" class="w-full max-w-lg">
      <div class="space-y-4 text-sm">
        <p class="text-slate-600">Finance will review and send this refund. Maximum: {{ money(order?.total_amount) }}.</p>
        <div><label class="mb-1 block font-medium">Amount *</label><InputNumber v-model="refundAmount" mode="currency" currency="PHP" locale="en-PH" :min="0.01" :max="Number(order?.total_amount || 0)" fluid /></div>
        <div><label class="mb-1 block font-medium">Reason *</label><Textarea v-model="refundReason" rows="2" fluid /></div>
        <div><label class="mb-1 block font-medium">Manual payout method *</label><Select v-model="refundMethod" :options="refundMethods" optionLabel="label" optionValue="value" fluid /></div>
        <div><label class="mb-1 block font-medium">Account name *</label><InputText v-model="refundAccountName" fluid /></div>
        <div><label class="mb-1 block font-medium">Account / mobile number *</label><InputText v-model="refundAccountNumber" fluid /></div>
        <p class="text-xs text-slate-500">Do not enter a PIN, CVV, or full card number.</p>
      </div>
      <template #footer><Button label="Cancel" severity="secondary" outlined @click="refundDialogVisible = false" /><Button label="Submit Request" :loading="requestingRefund" :disabled="!canRequestRefund" @click="requestRefund" /></template>
    </Dialog>
    <ConfirmDialog />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import salesService from '@/services/sales.service'
import { openReceiptPreview } from '@/utils/receiptPreview'
import paymongoService from '@/services/paymongo.service'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Badge from 'primevue/badge'
import Skeleton from 'primevue/skeleton'
import ConfirmDialog from 'primevue/confirmdialog'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()
const confirm = useConfirm()
const order = ref<any>(null)
const loading = ref(false)
const sendingToLogistics = ref(false)
const continuingPayment = ref(false)
const syncingPayment = ref(false)
const printingReceipt = ref(false)
const timelineDialogVisible = ref(false)
const refundDialogVisible = ref(false)
const requestingRefund = ref(false)
const refundAmount = ref<number | null>(null)
const refundReason = ref('')
const refundMethod = ref('')
const refundAccountName = ref('')
const refundAccountNumber = ref('')
const refundMethods = [{ label: 'GCash', value: 'gcash' }, { label: 'Maya', value: 'maya' }, { label: 'Bank Transfer', value: 'bank_transfer' }]
const canRequestRefund = computed(() => Number(refundAmount.value) > 0
  && Number(refundAmount.value) <= Number(order.value?.total_amount || 0)
  && Boolean(refundReason.value.trim() && refundMethod.value && refundAccountName.value.trim() && refundAccountNumber.value.trim()))

const requestRefund = async () => {
  if (!order.value || !canRequestRefund.value || requestingRefund.value) return
  requestingRefund.value = true
  try {
    await salesService.createRefund({
      order_id: order.value.id, amount: refundAmount.value, reason: refundReason.value.trim(),
      refund_method: refundMethod.value, refund_account_name: refundAccountName.value.trim(),
      refund_account_number: refundAccountNumber.value.trim(),
    })
    refundDialogVisible.value = false
    await loadOrder()
    toast.add({ severity: 'success', summary: 'Refund Requested', detail: 'Finance can now review this refund.', life: 3000 })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Refund Not Created', detail: error?.response?.data?.message || 'Unable to request the refund.', life: 3500 })
  } finally {
    requestingRefund.value = false
  }
}

const timeline = computed(() => {
  if (!order.value) return []
  const events = [
    { title: 'Order Placed', description: 'The POS order was created.', created_at: order.value.created_at, icon: 'pi pi-shopping-bag' },
  ]
  if (order.value.paid_at || order.value.payment?.paid_at) {
    events.push({ title: 'Payment Confirmed', description: `${formatLabel(order.value.payment_method)} payment received.`, created_at: order.value.paid_at || order.value.payment?.paid_at, icon: 'pi pi-check-circle' })
  }
  for (const log of order.value.delivery?.logs || []) {
    events.push({ title: formatLabel(log.status_to || log.event_type || 'Delivery Updated'), description: log.message || '', created_at: log.created_at, icon: 'pi pi-truck' })
  }
  return events.sort((a, b) => new Date(a.created_at || 0).getTime() - new Date(b.created_at || 0).getTime())
})

const loadOrder = async () => {
  loading.value = true
  try {
    const response = await salesService.getPosOrder(Number(route.params.id))
    order.value = response?.data || response
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Order unavailable', detail: error?.response?.data?.message || 'Unable to load this POS order.', life: 3000 })
  } finally {
    loading.value = false
  }
}

const syncPayment = async () => {
  if (!order.value || syncingPayment.value) return
  syncingPayment.value = true
  try {
    await salesService.syncPosOrderPayment(order.value.id)
    await loadOrder()
    toast.add({ severity: 'success', summary: 'Payment Refreshed', detail: 'The latest payment status has been loaded.', life: 2500 })
  } catch (error: any) {
    toast.add({ severity: 'warn', summary: 'Payment Not Confirmed', detail: error?.response?.data?.message || 'Try refreshing again in a moment.', life: 3000 })
  } finally {
    syncingPayment.value = false
  }
}

const continueOnlinePayment = async () => {
  if (!order.value || continuingPayment.value) return
  const existingUrl = String(order.value.payment?.checkout_url || '')
  if (existingUrl.startsWith('https://checkout.paymongo.com/')) {
    window.location.assign(existingUrl)
    return
  }

  continuingPayment.value = true
  try {
    const successUrl = new URL(router.resolve({ name: 'sales.pos.order-detail', params: { id: order.value.id }, query: { paymongo_success: '1' } }).href, window.location.origin).toString()
    const cancelUrl = new URL(router.resolve({ name: 'sales.pos.order-detail', params: { id: order.value.id }, query: { paymongo_cancel: '1' } }).href, window.location.origin).toString()
    const response = await paymongoService.createCheckoutSession({
      amount: Math.max(1, Math.round(Number(order.value.total_amount || 0) * 100)),
      currency: 'PHP',
      description: `POS Order ${order.value.order_number || order.value.id}`,
      store_id: Number(order.value.store_id),
      payable_type: 'sales_order',
      payable_id: Number(order.value.id),
      payment_method_allowed: [String(order.value.payment_method)],
      metadata: { order_id: Number(order.value.id), source: 'sales_pos' },
      success_url: successUrl,
      cancel_url: cancelUrl,
    })
    const checkoutUrl = String(response?.data?.checkout_url || '')
    if (!checkoutUrl.startsWith('https://checkout.paymongo.com/')) throw new Error('Secure checkout URL was not returned.')
    window.location.assign(checkoutUrl)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Payment Unavailable', detail: error?.response?.data?.message || error?.message || 'Unable to open secure payment checkout.', life: 3500 })
  } finally {
    continuingPayment.value = false
  }
}

const money = (value: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
const formatLabel = (value: string) => {
  if (value === 'card') return 'Credit Card'
  if (value === 'gcash') return 'GCash'
  if (value === 'paymaya') return 'Maya'
  return String(value || '-').replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
}
const formatDateTime = (value: string | null) => value
  ? new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
  : '-'
const paymentStatusSeverity = (value: string) => {
  if (value === 'paid') return 'success'
  if (value === 'failed' || value === 'cancelled') return 'danger'
  if (value === 'processing') return 'info'
  return 'warn'
}
const statusSeverity = (value: string) => {
  if (['completed', 'delivered'].includes(value)) return 'success'
  if (['assigned', 'out_for_delivery', 'in_transit'].includes(value)) return 'info'
  if (['failed_delivery', 'cancelled', 'rejected'].includes(value)) return 'danger'
  if (value === 'draft') return 'secondary'
  return 'warn'
}
const goBack = () => router.push({ name: 'sales.pos' })

const canSendToLogistics = computed(() => {
  if (!order.value || !authStore.hasPermission('sales.orders.manage') || !order.value.delivery_required) return false
  const orderStatus = String(order.value.status || '').toLowerCase()
  const deliveryStatus = String(order.value.delivery?.status || '').toLowerCase()
  return ['pending', 'pending_payment', 'completed'].includes(orderStatus) && (!order.value.delivery || ['pending', 'ready_for_dispatch'].includes(deliveryStatus))
})
const sendDisabled = computed(() => !order.value || order.value.payment_status !== 'paid' || !order.value.delivery_address)

const confirmReadyForDispatch = () => {
  if (!order.value) return
  confirm.require({
    header: 'Mark Order Ready for Dispatch?',
    message: `Mark ${order.value.order_number || 'this order'} and its delivery as Ready for Dispatch? Logistics can then assign a driver.`,
    icon: 'pi pi-exclamation-triangle',
    rejectProps: { label: 'Cancel', outlined: true },
    acceptProps: { label: 'Mark Ready for Dispatch', severity: 'success' },
    accept: () => sendToLogistics(),
  })
}

const sendToLogistics = async () => {
  if (!order.value) return
  sendingToLogistics.value = true
  try {
    await salesService.sendPosOrderToLogistics(order.value.id)
    toast.add({ severity: 'success', summary: 'Ready for Dispatch', detail: 'Order is now available for logistics assignment.', life: 3000 })
    await loadOrder()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Action Failed', detail: error?.response?.data?.message || 'Failed to send order to logistics.', life: 3000 })
  } finally {
    sendingToLogistics.value = false
  }
}

const printReceipt = async () => {
  if (!order.value || printingReceipt.value) return
  printingReceipt.value = true
  try {
    await openReceiptPreview(() => salesService.getPosOrderReceipt(order.value.id))
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Receipt Unavailable', detail: error?.response?.data?.message || error?.message || 'Unable to prepare the receipt for printing.', life: 3500 })
  } finally {
    printingReceipt.value = false
  }
}

onMounted(async () => {
  await loadOrder()
  if (order.value && (route.query.paymongo_success || route.query.paymongo_cancel)) {
    try {
      await salesService.syncPosOrderPayment(Number(route.params.id))
      await loadOrder()
      const paid = String(order.value?.payment_status || '').toLowerCase() === 'paid'
      toast.add({ severity: paid ? 'success' : 'info', summary: paid ? 'Payment Confirmed' : 'Payment Pending', detail: paid ? 'POS payment was confirmed.' : 'Payment is not confirmed yet. Refresh the status shortly.', life: 3500 })
    } catch (error: any) {
      toast.add({ severity: 'info', summary: 'Payment Pending', detail: error?.response?.data?.message || 'Payment status could not be confirmed yet.', life: 3500 })
    } finally {
      const query = { ...route.query }
      delete query.paymongo_success
      delete query.paymongo_cancel
      await router.replace({ query })
    }
  }
})

</script>
