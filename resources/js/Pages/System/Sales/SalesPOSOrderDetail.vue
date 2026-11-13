<template>
  <div class="mx-auto max-w-7xl space-y-6 pb-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div class="flex items-center gap-2">
        <Button icon="pi pi-arrow-left" severity="secondary" text rounded aria-label="Back to POS orders" @click="goBack" />
        <div>
          <h1 class="text-2xl font-semibold text-gray-900">POS Order Details</h1>
          <p class="mt-1 text-sm text-gray-500">Review the order, payment, items, and fulfillment progress.</p>
        </div>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <Button icon="pi pi-refresh" severity="secondary" text rounded aria-label="Refresh order" :loading="loading" @click="loadOrder" />
        <Button icon="pi pi-history" label="Timeline" severity="secondary" outlined size="small" :disabled="!order" @click="timelineDialogVisible = true" />
        <Button v-if="order?.payment_status === 'paid'" icon="pi pi-print" label="Print Receipt" severity="secondary" text size="small" :loading="printingReceipt" @click="printReceipt" />
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
        <div class="rounded-lg border border-gray-200 bg-white p-4">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Order Number</p>
          <p class="mt-2 break-all text-lg font-semibold text-slate-900">{{ order.order_number || '-' }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Order Date</p>
          <p class="mt-2 text-lg font-semibold text-slate-900">{{ formatDateTime(order.created_at) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Order Status</p>
          <Badge class="mt-3" :value="formatLabel(order.status)" :severity="statusSeverity(order.status)" />
          <p class="mt-2 text-xs text-slate-500">{{ order.delivery_required ? 'Store delivery' : 'In-store purchase' }}</p>
        </div>
      </div>

      <Card class="rounded-2xl border border-gray-100 shadow-sm">
        <template #title><span class="text-base font-semibold text-gray-800">Customer &amp; Fulfillment Information</span></template>
        <template #content>
          <div class="grid grid-cols-1 gap-x-8 gap-y-4 text-sm sm:grid-cols-2">
            <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Customer</p><p class="mt-1 font-medium text-slate-900">{{ order.customer_name || 'Walk-in customer' }}</p></div>
            <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Email</p><p class="mt-1 break-all font-medium text-slate-900">{{ order.delivery_email || '-' }}</p></div>
            <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Contact Number</p><p class="mt-1 font-medium text-slate-900">{{ order.customer_phone || '-' }}</p></div>
            <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Fulfillment</p><p class="mt-1 font-medium text-slate-900">{{ order.delivery_required ? 'Delivery' : 'In-store' }}</p></div>
            <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Branch</p><p class="mt-1 font-medium text-slate-900">{{ order.branch?.name || '-' }}</p></div>
            <div v-if="order.delivery_required"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Delivery Status</p><Badge class="mt-1" :value="formatLabel(order.delivery?.status || 'pending')" :severity="statusSeverity(order.delivery?.status || 'pending')" /></div>
            <div v-if="order.delivery_required" class="sm:col-span-2"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Delivery Address</p><p class="mt-1 font-medium leading-6 text-slate-900">{{ order.delivery_address || '-' }}</p></div>
          </div>

          <div v-if="order.delivery_required && order.delivery" class="mt-5 grid gap-4 border-t border-slate-100 pt-5 text-sm sm:grid-cols-2">
            <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Tracking Number</p><p class="mt-1 font-medium text-slate-900">{{ order.delivery.tracking_number || 'Not assigned' }}</p></div>
            <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Courier</p><p class="mt-1 font-medium text-slate-900">{{ order.delivery.courier_name || 'Not assigned' }}</p></div>
            <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Courier Contact</p><p class="mt-1 font-medium text-slate-900">{{ order.delivery.courier_contact || '-' }}</p></div>
            <div v-if="order.delivery.scheduled_delivery_at"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Preferred Delivery Date</p><p class="mt-1 font-medium text-slate-900">{{ formatDate(order.delivery.scheduled_delivery_at) }}</p></div>
            <div v-if="order.delivery_notes" class="sm:col-span-2"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Delivery Notes</p><p class="mt-1 whitespace-pre-line font-medium text-slate-900">{{ order.delivery_notes }}</p></div>
          </div>
        </template>
      </Card>

      <Card v-if="order.return_requests?.length || order.refund_request" class="rounded-2xl border border-orange-200 shadow-sm">
        <template #title><span class="text-base font-semibold text-slate-900">Returns, Refunds &amp; Replacements</span></template>
        <template #content>
          <div class="space-y-3">
            <article v-if="order.refund_request" class="rounded-xl border border-slate-200 p-4">
              <div class="flex flex-wrap items-center justify-between gap-2"><div><p class="font-semibold text-slate-900">POS refund request</p><p class="mt-1 text-xs text-slate-500">{{ order.refund_request.reason || 'Refund requested for this sale' }}</p></div><Badge :value="formatLabel(order.refund_request.status)" :severity="statusSeverity(order.refund_request.status)" /></div>
              <div class="mt-3 grid gap-3 text-sm sm:grid-cols-3"><div><p class="text-xs text-slate-500">Amount</p><p class="font-semibold text-slate-900">{{ money(order.refund_request.amount) }}</p></div><div><p class="text-xs text-slate-500">Payout destination</p><p class="font-medium text-slate-900">{{ formatLabel(order.refund_request.refund_method) }}<span v-if="order.refund_request.refund_account_name"> · {{ order.refund_request.refund_account_name }}</span><span v-if="order.refund_request.refund_account_number"> · {{ order.refund_request.refund_account_number }}</span></p></div><div><p class="text-xs text-slate-500">Reference</p><p class="break-all font-medium text-slate-900">{{ order.refund_request.payout_reference || '-' }}</p></div></div>
            </article>
            <article v-for="item in order.return_requests" :key="item.id" class="rounded-xl border border-slate-200 p-4">
              <div class="flex flex-wrap items-start justify-between gap-2">
                <div class="min-w-0">
                  <p class="font-semibold text-slate-900">{{ item.return_number || `Return #${item.id}` }} <span class="font-normal text-slate-500">· {{ item.item_name || 'Order item' }}</span></p>
                  <p class="mt-1 text-xs text-slate-500">{{ formatLabel(item.return_type || 'return') }} · {{ item.reason || 'No reason provided' }}</p>
                  <p v-if="item.pickup" class="mt-1 text-xs text-slate-500">Return pickup: {{ item.pickup.branch_name || 'Branch not assigned' }}<span v-if="item.pickup.scheduled_at"> · {{ formatDateTime(item.pickup.scheduled_at) }}</span><span v-if="item.pickup.trip_reference"> · {{ item.pickup.trip_reference }}</span></p>
                </div>
                <Badge :value="formatLabel(item.status)" :severity="statusSeverity(item.status)" />
              </div>
              <div v-if="item.return_type === 'refund'" class="mt-3 grid gap-3 rounded-lg bg-slate-50 p-3 text-sm sm:grid-cols-3">
                <div><p class="text-xs text-slate-500">Refund amount</p><p class="font-semibold text-slate-900">{{ money(item.refund?.amount) }}</p></div>
                <div><p class="text-xs text-slate-500">Finance status</p><Badge v-if="item.refund?.status" class="mt-1" :value="formatLabel(item.refund.status)" :severity="statusSeverity(item.refund.status)" /><p v-else class="mt-1 font-medium text-slate-700">Awaiting Finance</p></div>
                <div><p class="text-xs text-slate-500">Payout</p><p class="font-medium text-slate-900">{{ item.refund?.method ? formatLabel(item.refund.method) : 'Not set' }}<span v-if="item.refund?.account_name"> · {{ item.refund.account_name }}</span><span v-if="item.refund?.account_number"> · {{ item.refund.account_number }}</span><span v-if="item.refund?.payout_reference"> · Ref {{ item.refund.payout_reference }}</span></p></div>
              </div>
              <div v-if="item.return_type === 'replacement'" class="mt-3 rounded-lg border border-orange-100 bg-orange-50/60 p-3">
                <div class="flex flex-wrap items-center justify-between gap-2"><p class="text-sm font-semibold text-slate-900">Replacement Fulfillment Information</p><Badge :value="formatLabel(item.replacement?.status || 'awaiting_stock')" :severity="statusSeverity(item.replacement?.status || 'awaiting_stock')" /></div>
                <div class="mt-3 grid gap-x-5 gap-y-2 text-sm sm:grid-cols-2">
                  <p><span class="text-slate-500">Replacement item:</span> <span class="font-medium text-slate-900">{{ item.replacement?.item_name || 'Not reserved' }}<span v-if="item.replacement?.sku"> · {{ item.replacement.sku }}</span></span></p>
                  <p><span class="text-slate-500">Fulfillment branch:</span> <span class="font-medium text-slate-900">{{ item.replacement?.branch_name || 'Not assigned' }}</span></p>
                  <p><span class="text-slate-500">Trip:</span> <span class="font-medium text-slate-900">{{ item.replacement?.trip_reference || 'Not assigned' }}</span></p>
                  <p><span class="text-slate-500">Driver / vehicle:</span> <span class="font-medium text-slate-900">{{ [item.replacement?.driver_name, item.replacement?.vehicle].filter(Boolean).join(' · ') || 'Not assigned' }}</span></p>
                  <p v-if="Number(item.replacement?.price_difference || 0) > 0"><span class="text-slate-500">Price difference:</span> <span class="font-medium text-slate-900">{{ money(item.replacement.price_difference) }} · {{ formatLabel(item.replacement.payment_status || 'pending') }}</span></p>
                  <p v-if="item.replacement?.delivered_at"><span class="text-slate-500">Delivered:</span> <span class="font-medium text-slate-900">{{ formatDateTime(item.replacement.delivered_at) }}<span v-if="item.replacement.received_by"> · {{ item.replacement.received_by }}</span></span></p>
                  <p v-if="item.replacement?.failure_reason" class="text-red-700 sm:col-span-2">Delivery issue: {{ item.replacement.failure_reason }}</p>
                </div>
                <p v-if="item.replacement?.status === 'awaiting_stock'" class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-900">Sales follow-up is needed while replacement stock is unavailable. The returned unit stays quarantined, and no delivery trip should be assigned until replacement stock is reserved.</p>
                <a v-if="item.replacement?.proof_url" :href="item.replacement.proof_url" target="_blank" rel="noopener" class="mt-3 inline-flex text-xs font-medium text-orange-700 hover:underline">View replacement delivery proof</a>
              </div>
            </article>
          </div>
        </template>
      </Card>

      <div class="grid gap-5 lg:grid-cols-3">
        <div class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white lg:col-span-2">
          <div class="border-b border-gray-200 bg-gray-50 px-6 py-4"><div class="flex items-center gap-2"><i class="pi pi-shopping-cart text-gray-500" /><h2 class="font-medium text-gray-700">Order Items ({{ order.items?.length || 0 }})</h2></div></div>
          <div class="overflow-x-auto"><DataTable :value="order.items || []" dataKey="id" rowHover size="small" class="min-w-[640px] text-sm">
            <Column header="#" style="width: 4rem"><template #body="{ index }">{{ index + 1 }}</template></Column>
            <Column header="Product" style="min-width: 240px"><template #body="{ data }"><p class="font-medium text-gray-900">{{ data.product_name || data.product?.product_name || '-' }}</p><p class="mt-1 text-xs text-gray-500">SKU: {{ data.sku || data.variation?.variation_sku || data.product?.sku || '-' }}</p><p v-if="data.variation?.variation_name" class="mt-1 text-xs text-orange-600">{{ data.variation.variation_name }}</p><div v-if="data.customization_request" class="mt-2 rounded-lg border border-orange-100 bg-orange-50 p-2 text-xs"><p class="font-semibold text-orange-800"><i class="pi pi-palette mr-1" />Upholstery customization</p><p class="mt-1 whitespace-pre-line text-slate-700">{{ data.customization_request }}</p><p v-if="data.customization_lead_time_days" class="mt-1 text-slate-500">Estimated lead time: {{ data.customization_lead_time_days }} days</p></div></template></Column>
            <Column header="Quantity / Unit" style="min-width: 145px"><template #body="{ data }"><span class="font-medium">{{ Number(data.quantity || 0).toLocaleString() }} {{ data.product?.unit_of_measurement || 'unit' }}</span></template></Column>
            <Column header="Unit Price" style="min-width: 145px"><template #body="{ data }"><div v-if="itemDiscount(data) > 0" class="flex flex-col"><del class="text-xs text-slate-400">{{ money(originalUnitPrice(data)) }}</del><span class="font-semibold text-slate-900">{{ money(data.unit_price) }}</span></div><span v-else>{{ money(data.unit_price) }}</span><p v-if="Number(data.customization_fee || 0) > 0" class="mt-1 text-xs text-orange-700">+ {{ money(data.customization_fee) }} customization</p></template></Column>
            <Column header="Line Total" style="min-width: 135px"><template #body="{ data }"><span class="font-semibold text-slate-900">{{ money(data.line_total) }}</span></template></Column>
            <template #empty><div class="py-8 text-center text-sm text-slate-500">No items recorded for this order.</div></template>
          </DataTable></div>
        </div>

        <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:sticky lg:top-6">
          <div class="mb-5 flex items-center gap-2"><i class="pi pi-calculator text-slate-500" /><h2 class="font-semibold text-slate-900">Order Cost Summary</h2></div>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between gap-4 text-slate-600"><span>{{ productDiscountTotal > 0 ? 'Original subtotal' : 'Subtotal' }}</span><span class="font-medium text-slate-900">{{ money(order.subtotal) }}</span></div>
            <div v-if="productDiscountTotal > 0" class="flex justify-between gap-4 text-rose-600"><span>Product discount</span><span class="font-medium">- {{ money(productDiscountTotal) }}</span></div>
            <div v-if="orderLevelDiscount > 0" class="flex justify-between gap-4 text-rose-600"><span>Order discount</span><span class="font-medium">- {{ money(orderLevelDiscount) }}</span></div>
            <div v-if="Number(order.tax_amount || 0) > 0" class="flex justify-between gap-4 text-slate-600"><span>VAT</span><span class="font-medium text-slate-900">{{ money(order.tax_amount) }}</span></div>
            <div v-if="order.delivery_required" class="flex justify-between gap-4 text-slate-600"><span>Shipping Fee</span><span class="font-medium text-slate-900">{{ money(order.shipping_fee) }}</span></div>
            <div class="flex justify-between gap-4 text-slate-600"><span>Payment Method</span><span class="font-medium text-slate-900">{{ formatLabel(order.payment?.payment_method || order.payment_method) }}</span></div>
            <div v-if="order.refund_request" class="flex items-center justify-between gap-4 text-slate-600"><span>Refund</span><Badge :value="formatLabel(order.refund_request.status)" :severity="statusSeverity(order.refund_request.status)" /></div>
            <div class="flex items-center justify-between gap-4 text-slate-600"><span>Payment Status</span><Badge :value="formatLabel(order.payment_status)" :severity="paymentStatusSeverity(order.payment_status)" /></div>
          </div>
          <div class="my-5 border-t border-slate-200" />
          <div class="flex items-center justify-between gap-4 rounded-xl bg-slate-900 px-4 py-3 text-white"><span class="font-semibold">Total Amount</span><span class="text-xl font-bold">{{ money(order.total_amount) }}</span></div>
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

      <div class="flex flex-wrap items-center justify-end gap-2">
        <Button v-if="order.payment_status === 'paid' && !order.refund_request && authStore.hasPermission('sales.refunds.manage')" label="Request Refund" severity="secondary" outlined size="small" @click="refundDialogVisible = true" />
        <Button v-if="canSendToLogistics && order.payment_status === 'paid'" icon="pi pi-send" label="Mark Ready for Dispatch" size="small" :loading="sendingToLogistics" :disabled="sendDisabled" @click="confirmReadyForDispatch" />
      </div>

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
const productDiscountTotal = computed(() => (order.value?.items || []).reduce(
  (total: number, item: any) => total + Math.max(0, Number(item.line_discount || 0)), 0,
))
const orderLevelDiscount = computed(() => Math.max(0,
  Number(order.value?.discount_amount || 0) - productDiscountTotal.value,
))
const originalUnitPrice = (item: any) => {
  const currentPrice = Number(item?.unit_price || 0)
  const storedOriginal = Number(item?.original_unit_price || 0)
  if (storedOriginal > currentPrice) return storedOriginal
  const quantity = Math.max(1, Number(item?.quantity || 1))
  return currentPrice + Math.max(0, Number(item?.line_discount || 0)) / quantity
}
const itemDiscount = (item: any) => Math.max(0, Number(item?.line_discount || 0))
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
  const directRefund = order.value.refund_request
  if (directRefund) {
    const addRefundEvent = (title: string, at: string | null, description = '') => {
      if (at) events.push({ title, description: [directRefund.order_number || order.value.order_number, description].filter(Boolean).join(' · '), created_at: at, icon: 'pi pi-wallet' })
    }
    addRefundEvent('Refund requested', directRefund.created_at, directRefund.reason || '')
    if (directRefund.processed_at) addRefundEvent(directRefund.status === 'rejected' ? 'Refund declined' : 'Refund processed', directRefund.processed_at, money(directRefund.amount))
    if (directRefund.sent_at) addRefundEvent('Refund sent to customer', directRefund.sent_at, directRefund.payout_reference ? `Reference ${directRefund.payout_reference}` : '')
  }
  for (const item of order.value.return_requests || []) {
    const reference = item.return_number || `Return #${item.id}`
    const addEvent = (title: string, description: string, createdAt: string | null, icon: string) => {
      if (createdAt) events.push({ title, description: [reference, description].filter(Boolean).join(' · '), created_at: createdAt, icon })
    }
    addEvent('Return requested', item.reason || '', item.created_at, 'pi pi-replay')
    if (item.reviewed_at) addEvent(item.status === 'rejected' ? 'Return declined' : 'Return approved', item.review_notes || '', item.reviewed_at, 'pi pi-check-circle')
    if (item.inspected_at) addEvent('Returned item inspected', item.inspection_notes || '', item.inspected_at, 'pi pi-search')
    if (item.pickup?.assigned_at) addEvent('Return pickup assigned', [item.pickup.trip_reference, item.pickup.branch_name].filter(Boolean).join(' · '), item.pickup.assigned_at, 'pi pi-truck')
    if (item.pickup?.picked_up_at) addEvent('Return picked up from customer', '', item.pickup.picked_up_at, 'pi pi-replay')
    if (item.pickup?.out_for_delivery_at) addEvent('Return on the way to branch', item.pickup.branch_name || '', item.pickup.out_for_delivery_at, 'pi pi-send')
    if (item.pickup?.delivered_at) addEvent('Returned item delivered to branch', item.pickup.branch_name || '', item.pickup.delivered_at, 'pi pi-check')
    if (item.return_type === 'replacement' && (item.replacement?.status || 'awaiting_stock') === 'awaiting_stock' && item.inspected_at) {
      addEvent('Awaiting replacement stock', 'Sales follow-up required; returned item remains quarantined.', item.inspected_at, 'pi pi-box')
    }
    if (item.return_type === 'refund' && item.refund) {
      addEvent('Refund sent to Finance', `${money(item.refund.amount)} · ${formatLabel(item.refund.status)}`, item.refund.created_at || item.inspected_at, 'pi pi-wallet')
      if (item.refund.processed_at) addEvent(item.refund.status === 'rejected' ? 'Refund declined' : 'Refund processed', item.refund.notes || '', item.refund.processed_at, 'pi pi-check-circle')
      if (item.refund.sent_at) addEvent('Refund sent to customer', item.refund.payout_reference ? `Reference ${item.refund.payout_reference}` : '', item.refund.sent_at, 'pi pi-send')
    }
    const replacement = item.replacement
    if (replacement?.status === 'reserved') addEvent('Replacement stock reserved', replacement.item_name || '', item.updated_at, 'pi pi-box')
    if (replacement?.assigned_at) addEvent('Replacement delivery assigned', replacement.trip_reference || 'Trip assigned', replacement.assigned_at, 'pi pi-truck')
    if (replacement?.dispatched_at) addEvent('Replacement out for delivery', replacement.trip_reference || '', replacement.dispatched_at, 'pi pi-send')
    if (replacement?.status === 'delivery_failed') addEvent('Replacement delivery attempt failed', replacement.failure_reason || '', item.updated_at, 'pi pi-exclamation-circle')
    if (replacement?.delivered_at) addEvent('Replacement delivered', replacement.received_by ? `Received by ${replacement.received_by}` : '', replacement.delivered_at, 'pi pi-check')
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
  if (value === 'qrph') return 'QR Ph (GCash, Maya, bank apps)'
  return String(value || '-').replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
}
const formatDateTime = (value: string | null) => value
  ? new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
  : '-'
const formatDate = (value: string | null) => value
  ? new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium' }).format(new Date(value))
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
