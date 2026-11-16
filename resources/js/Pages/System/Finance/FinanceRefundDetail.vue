<template>
  <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3"><Button icon="pi pi-arrow-left" text rounded
          @click="router.push({name:'finance.refunds'})" />
        <div>
          <h1 class="text-xl font-semibold text-slate-900">Refund Detail</h1>
          <p class="text-sm text-slate-500">Finance authorization and customer refund posting.</p>
        </div>
      </div>
      <div v-if="refund" class="text-right">
        <Tag :value="displayStatus" :severity="severity(displayStatusValue)" />
      </div>
    </header>
    <div v-if="loading" class="py-12 text-center text-slate-500">Loading refund...</div>
    <template v-else-if="refund">
      <div class="grid gap-6 lg:grid-cols-3">
        <Card class="rounded-2xl border border-slate-200 shadow-sm lg:col-span-2"><template #title><span
              class="text-base">Refund Request</span></template><template #content>
            <div class="grid gap-4 text-sm sm:grid-cols-2 p-1">
              <Info label="Order Reference" :value="refund.order_number || `Refund #${refund.id}`" />
              <Info label="Customer" :value="refund.customer_name || '-'" />
              <Info label="Source" :value="label(refund.order_type)" />
              <Info label="Amount" :value="currency(displayRefundAmount)" />
              <Info v-if="shippingFeeExcluded !== null" label="Shipping Fee Excluded" :value="currency(shippingFeeExcluded)" />
              <div v-if="amountCalculation" class="sm:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="font-semibold text-slate-800">Refund amount calculation</p>
                <div class="mt-2 grid gap-2 text-xs text-slate-600 sm:grid-cols-2">
                  <p>Inspected item subtotal: {{ currency(amountCalculation.item_subtotal) }}</p>
                  <p>Allocated order discount: −{{ currency(amountCalculation.order_discount_allocated) }}</p>
                  <p>Paid value of returned items: {{ currency(amountCalculation.paid_item_subtotal) }}</p>
                  <p>Return fee: −{{ currency(amountCalculation.return_fee) }}</p>
                  <p>Prior refunds reserved: {{ currency(amountCalculation.previously_reserved) }}</p>
                  <p>Remaining paid order balance: {{ currency(amountCalculation.available_paid_balance) }}</p>
                  <p class="font-semibold text-slate-900 sm:col-span-2">Calculated refund: {{ currency(amountCalculation.amount) }}</p>
                </div>
                <div v-if="refund.status === 'pending'" class="mt-4 grid gap-3 sm:grid-cols-2">
                  <label class="text-xs font-medium text-slate-700">Approved refund amount
                    <InputNumber v-model="refundAmount" class="mt-1 w-full" mode="currency" currency="PHP" locale="en-PH"
                      :min="0.01" :max="amountCalculation.available_paid_balance" :minFractionDigits="2" :maxFractionDigits="2" />
                  </label>
                  <label v-if="amountAdjusted" class="text-xs font-medium text-slate-700 sm:col-span-2">Reason for amount adjustment *
                    <Textarea v-model="adjustmentReason" class="mt-1 w-full" rows="2" maxlength="1000" fluid placeholder="Explain why the approved amount differs from the inspected calculation." />
                  </label>
                </div>
              </div>
              <Info label="Original Payment Method" :value="refundMethodLabel(originalPaymentMethod)" />
              <Info label="Reason" :value="refund.reason || '-'" class="sm:col-span-2" />
              <Info label="Branch" :value="refund.branch?.name || '-'" />
              <Info label="Requested" :value="dateTime(refund.created_at)" />
              <Info label="Requested By" :value="person(refund.requester)" />
              <Info label="Processed By" :value="person(refund.processor)" />
              <Info label="Processed At" :value="dateTime(refund.processed_at)" />
              <Info v-if="refund.payout_reference" :label="refund.payout_provider === 'simulation' ? 'Simulation Reference' : 'PayMongo Refund Reference'" :value="refund.payout_reference" />
              <Info label="Payout Provider" :value="providerLabel" />
              <Info label="PayMongo Refund ID" :value="refund.paymongo_refund_id || '-'" />
              <Info v-if="refund.paymongo_transfer_id" label="PayMongo Transfer ID" :value="refund.paymongo_transfer_id" />
              <Info v-if="!isLegacyUnpaid" :label="refund.status === 'simulated' ? 'Simulated By' : 'Sent By'" :value="person(refund.sender)" />
              <Info v-if="!isLegacyUnpaid" :label="refund.status === 'simulated' ? 'Simulated At' : 'Sent At'" :value="dateTime(refund.sent_at)" />
              <Info label="Notes" :value="displayRefundNotes" class="sm:col-span-2" />
            </div>
          </template></Card>
        <Card class="rounded-2xl border border-slate-200 shadow-sm"><template #title><span class="text-base">Finance
              Action</span></template><template #content>
            <div class="space-y-4">
              <p v-if="refund.status==='pending_inspection'" class="rounded-xl bg-blue-50 p-3 text-sm text-blue-700">
                Awaiting return delivery and inventory inspection before Finance can release funds.</p><Textarea
                v-model="notes" rows="5" fluid placeholder="Finance notes" />
              <p v-if="refund.status==='pending' && !automaticRefundAvailable && !refundSimulationAvailable"
                class="rounded-xl bg-amber-50 p-3 text-sm text-amber-700">
                This payment cannot be refunded through PayMongo. Finance can approve refunds only when the original payment was successfully processed online through PayMongo.
              </p>
              <div v-if="refund.status==='pending'" class="grid gap-2">
                <Button label="Approve Refund" icon="pi pi-check" :disabled="!canApprove"
                  :loading="processing==='approved'" @click="process('approved')" />
                <Button label="Reject Refund" icon="pi pi-times" severity="danger" outlined :disabled="!canReject"
                  :loading="processing==='rejected'" @click="process('rejected')" />
              </div>
              <div v-if="refund.status==='approved'" class="space-y-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="font-semibold text-emerald-900">Approved refund</p>
                <p v-if="automaticRefundAvailable" class="text-sm text-emerald-800">{{ currency(refund.amount) }} will be returned to the original {{ refundMethodLabel(originalPaymentMethod) }} payment method.</p>
                <p v-if="automaticRefundAvailable" class="text-xs text-emerald-700">The refund returns to the original checkout method.</p>
                <Button v-if="automaticRefundAvailable" label="Pay Refund via PayMongo" icon="pi pi-send" fluid :loading="sending"
                  :disabled="!canSendPaymongo" @click="markSent('paymongo')" />
                <div v-if="canSimulatePaymongo" class="space-y-2 rounded-lg border border-blue-200 bg-blue-50 p-3">
                  <p class="text-sm font-medium text-blue-900">Sandbox test only</p>
                  <p class="text-xs text-blue-800">Record the refund workflow without creating a charge or moving funds. The order will remain paid.</p>
                  <Button label="Simulate PayMongo Refund" icon="pi pi-play" severity="info" outlined fluid :loading="sending" @click="markSent('simulation')" />
                </div>
                <p v-if="!automaticRefundAvailable && !refundSimulationAvailable" class="text-xs text-amber-800">PayMongo cannot issue a refund for this payment. No manual payout entry is available.</p>
              </div>
              <div v-if="refund.status === 'transfer_failed'" class="space-y-3 rounded-xl bg-red-50 p-3 text-sm text-red-800">
                <p>{{ refund.payout_provider === 'paymongo' ? 'PayMongo reported that the refund failed:' : 'The payout could not be verified:' }} {{ refund.transfer_error || 'Review the provider status before taking further action.' }}</p>
                <Button v-if="refund.payout_provider === 'paymongo' && canSendPaymongo" label="Retry PayMongo Refund" icon="pi pi-refresh" severity="danger" outlined fluid :loading="sending" @click="markSent('paymongo')" />
              </div>
              <div v-if="refund.status === 'processing' && refund.payout_provider === 'paymongo'" class="space-y-3 rounded-xl bg-blue-50 p-3 text-sm text-blue-900">
                <p class="font-medium">PayMongo is processing this refund. It will be recorded as paid only after PayMongo confirms success.</p>
                <p v-if="refund.transfer_error" class="text-xs text-amber-800">{{ refund.transfer_error }}</p>
                <Button v-if="refund.paymongo_refund_id" label="Check PayMongo Status" icon="pi pi-refresh" severity="info" outlined fluid :loading="checkingPaymongo" @click="checkPaymongoStatus" />
                <p v-else class="text-xs">PayMongo did not return a refund reference. Verify the PayMongo dashboard before attempting another refund.</p>
              </div>
              <p v-if="refund.status === 'simulated'" class="rounded-xl border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900">
                Sandbox simulation recorded. No refund was issued, no money moved, and the order remains paid.
              </p>
              <div v-if="isAwaitingPayout" class="space-y-3 rounded-xl bg-amber-50 p-3 text-sm text-amber-900">
                <p class="font-medium">The refund is awaiting processing through the original PayMongo payment method.</p>
                <template v-if="automaticRefundAvailable">
                  <p>The refund will return to the original {{ refundMethodLabel(originalPaymentMethod) }} payment method.</p>
                  <Button label="Pay Refund via PayMongo" icon="pi pi-send" fluid :loading="sending" :disabled="!canSendPaymongo" @click="markSent('paymongo')" />
                </template>
                <p v-else class="text-xs">This refund is not eligible for PayMongo processing. No manual payout entry is available.</p>
              </div>
              <p v-else-if="refund.status==='sent'" class="rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">
                Refund paid. Reference: {{ refund.payout_reference || '-' }}
              </p>
            </div>
          </template></Card>
      </div>
      <Card v-if="refund.ecommerce_return" class="rounded-2xl border border-slate-200 shadow-sm"><template #title><span
            class="text-base">Linked CRM Return</span></template><template #content>
          <div class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <Info label="Return Number" :value="refund.ecommerce_return.return_number || `Return #${refund.order_id}`" />
            <Info label="Return Status" :value="label(refund.ecommerce_return.status)" />
            <Info label="Resolution" :value="label(refund.ecommerce_return.return_type)" />
            <Info label="Requested Quantity" :value="String(refund.ecommerce_return.requested_quantity || 1)" />
            <Info label="Product" :value="refund.ecommerce_return.order_item?.product_name || '-'" />
            <Info label="SKU" :value="refund.ecommerce_return.order_item?.sku || '-'" />
            <Info label="Pickup Status" :value="label(refund.ecommerce_return.pickup?.status)" />
            <Info label="Destination" :value="refund.ecommerce_return.pickup?.destination_branch?.name || '-'" />
          </div>
        </template></Card>
    </template>
  </div>
</template>
<script setup lang="ts">
import { computed, defineComponent, h, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import financeService from '@/services/finance.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'
import InputNumber from 'primevue/inputnumber'

const Info = defineComponent({
  props: { label: String, value: String },
  setup: p => () => h('div', [
    h('p', { class: 'text-xs font-medium uppercase tracking-wide text-slate-500' }, p.label),
    h('p', { class: 'mt-1 font-semibold text-slate-900' }, p.value || '-'),
  ]),
})

const route = useRoute()
const router = useRouter()
const toast = useToast()
const auth = useAuthStore()
const loading = ref(false)
const refund = ref<any>(null)
const amountCalculation = ref<any>(null)
const refundAmount = ref<number | null>(null)
const adjustmentReason = ref('')
const paymongoRefundsAvailable = ref(false)
const refundSimulationAvailable = ref(false)
const automaticRefundAvailable = ref(false)
const shippingFeeExcluded = ref<number | null>(null)
const originalPaymentMethod = ref('')
const notes = ref('')
const processing = ref('')
const sending = ref(false)
const checkingPaymongo = ref(false)
const amountAdjusted = computed(() => refundAmount.value !== null
  && Math.abs(Number(refundAmount.value) - Number(amountCalculation.value?.amount ?? refund.value?.amount ?? 0)) >= 0.01)
const isLegacyUnpaid = computed(() => ['simulation', 'paymongo_wallet_test'].includes(refund.value?.payout_provider) && refund.value?.status === 'sent')
const isAwaitingPayout = computed(() => refund.value?.status === 'awaiting_payout' || isLegacyUnpaid.value)
const displayRefundAmount = computed(() => amountCalculation.value?.amount ?? refund.value?.payable_refund_amount ?? refund.value?.amount ?? 0)
const displayStatusValue = computed(() => isAwaitingPayout.value ? 'awaiting_payout' : refund.value?.status)
const displayStatus = computed(() => label(displayStatusValue.value))
const providerLabel = computed(() => ({ paymongo: 'PayMongo original payment', manual: 'Manual payout', simulation: 'PayMongo sandbox simulation', paymongo_wallet_test: 'Legacy test transfer' } as Record<string, string>)[refund.value?.payout_provider] || refund.value?.payout_provider || '-')
const displayRefundNotes = computed(() => {
  const notes = String(refund.value?.notes || '-').trim()
  if (refund.value?.status === 'simulated') return notes
  if (/^(?:(?:TEST|SANDBOX|PAYMONGO TEST|PAYMONGO SANDBOX) )?(?:SIMULATION|TRANSFER SIMULATION)|^PAYMONGO (?:TEST|SANDBOX) TRANSFER|^No money was transferred/i.test(notes)) {
    return 'Awaiting payout.'
  }
  return notes
})
const canApprove = computed(() => auth.hasPermission('finance.refunds.approve')
  && refund.value?.status === 'pending'
  && (!amountCalculation.value || (Number(refundAmount.value) > 0
    && Number(refundAmount.value) <= Number(amountCalculation.value.available_paid_balance)
    && (!amountAdjusted.value || Boolean(adjustmentReason.value.trim()))))
  && (automaticRefundAvailable.value || refundSimulationAvailable.value))
const canReject = computed(() => auth.hasPermission('finance.refunds.approve') && refund.value?.status === 'pending')
const canSendPaymongo = computed(() => auth.hasPermission('finance.refunds.approve')
  && (refund.value?.status === 'approved' || isAwaitingPayout.value || (refund.value?.status === 'transfer_failed' && refund.value?.payout_provider === 'paymongo'))
  && paymongoRefundsAvailable.value
  && automaticRefundAvailable.value)
const canSimulatePaymongo = computed(() => auth.hasPermission('finance.refunds.approve')
  && refundSimulationAvailable.value && ['approved', 'awaiting_payout'].includes(refund.value?.status))
const label = (v: any) => v === 'pending_inspection' ? 'For Inspection' : v === 'awaiting_payout' ? 'Awaiting Payout' : v === 'sent' ? 'Refunded' : v === 'simulated' ? 'Sandbox Simulated' : String(v || '-').replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase())
const refundMethodLabel = (v: any) => ({ gcash: 'GCash', card: 'Card', maya: 'Maya', qrph: 'QR Ph', grab_pay: 'GrabPay', bank_transfer: 'Bank Transfer' } as any)[String(v || '')] || '-'
const severity = (v: string) => v === 'sent' || v === 'approved' ? 'success' : ['rejected', 'transfer_failed'].includes(v) ? 'danger' : ['pending_inspection', 'processing', 'simulated'].includes(v) ? 'info' : 'warn'
const currency = (v: any) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(v || 0))
const dateTime = (v: any) => v ? new Date(v).toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : '-'
const person = (u: any) => u ? [u.fname, u.lname].filter(Boolean).join(' ') || u.email : '-'

const load = async () => {
  loading.value = true
  try {
    const response = await financeService.getRefund(String(route.params.id))
    refund.value = response?.data || null
    amountCalculation.value = response?.amount_calculation || null
    refund.value.payable_refund_amount = response?.payable_refund_amount ?? refund.value.amount
    refundAmount.value = Number(response?.amount_calculation?.amount ?? refund.value?.amount ?? 0)
    adjustmentReason.value = ''
    paymongoRefundsAvailable.value = Boolean(response?.paymongo_refunds_available)
    refundSimulationAvailable.value = Boolean(response?.refund_simulation_available)
    automaticRefundAvailable.value = Boolean(response?.automatic_refund_available)
    shippingFeeExcluded.value = response?.shipping_fee_excluded === null || response?.shipping_fee_excluded === undefined
      ? null
      : Number(response.shipping_fee_excluded)
    originalPaymentMethod.value = response?.original_payment_method || ''
    notes.value = displayRefundNotes.value === '-' ? '' : displayRefundNotes.value
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Load Failed', detail: error?.response?.data?.message || 'Unable to load refund.', life: 3000 })
  } finally {
    loading.value = false
  }
}

const process = async (status: 'approved' | 'rejected') => {
  processing.value = status
  try {
    await financeService.updateRefundStatus(String(route.params.id), {
      status,
      notes: notes.value,
      ...(status === 'approved' && amountCalculation.value ? {
        amount: Number(refundAmount.value ?? amountCalculation.value.amount),
        adjustment_reason: adjustmentReason.value.trim() || undefined,
      } : {}),
    })
    toast.add({ severity: 'success', summary: 'Refund Updated', detail: status === 'approved'
      ? refundSimulationAvailable.value ? 'Refund approved. You can record the test-mode simulation or process it through PayMongo in live mode.' : 'Refund approved. Process it through the original PayMongo payment.'
      : 'Refund rejected.', life: 3000 })
    await load()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Processing Failed', detail: error?.response?.data?.message || 'Unable to process refund.', life: 3500 })
  } finally {
    processing.value = ''
  }
}

const markSent = async (provider: 'paymongo' | 'simulation') => {
  if (provider === 'paymongo' ? !canSendPaymongo.value : !canSimulatePaymongo.value) return
  sending.value = true
  try {
    const response = await financeService.markRefundSent(String(route.params.id), {
      provider,
      notes: notes.value,
    })
    toast.add({ severity: 'success', summary: provider === 'simulation' ? 'Sandbox Simulation Recorded' : 'Refund Processing',
      detail: provider === 'simulation' ? 'No funds moved; the order remains paid.' : (response?.message || 'PayMongo is processing the refund.'), life: 3500 })
    await load()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to Mark Sent', detail: error?.response?.data?.message || 'Unable to mark the refund as sent.', life: 3500 })
  } finally {
    sending.value = false
  }
}

const checkPaymongoStatus = async () => {
  checkingPaymongo.value = true
  try {
    const response = await financeService.checkPaymongoRefund(String(route.params.id))
    toast.add({ severity: response?.data?.status === 'sent' ? 'success' : 'info', summary: 'PayMongo Status', detail: response?.message || 'Refund status refreshed.', life: 3000 })
    await load()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Status Check Failed', detail: error?.response?.data?.message || 'Unable to check PayMongo refund status.', life: 3500 })
  } finally {
    checkingPaymongo.value = false
  }
}

onMounted(load)
</script>
