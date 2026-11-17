<template>
  <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3"><Button icon="pi pi-arrow-left" text rounded
          @click="router.push({name:'finance.refunds'})" />
        <div>
          <h1 class="text-xl font-semibold text-slate-900">Refund Detail</h1>
          <p class="text-sm text-slate-500">Finance authorization and refund processing.</p>
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
              <Info label="Recipient Payout Method" :value="refundMethodLabel(refund.refund_method)" />
              <Info label="Recipient Account Name" :value="refund.refund_account_name || 'Missing payout details'" />
              <Info label="Recipient Account Number" :value="refund.refund_account_number || '-'" />
              <Info label="Reason" :value="refund.reason || '-'" class="sm:col-span-2" />
              <Info label="Branch" :value="refund.branch?.name || '-'" />
              <Info label="Requested" :value="dateTime(refund.created_at)" />
              <Info label="Requested By" :value="person(refund.requester)" />
              <Info label="Processed By" :value="person(refund.processor)" />
              <Info label="Processed At" :value="dateTime(refund.processed_at)" />
              <Info v-if="refund.payout_reference" label="Payout Reference" :value="refund.payout_reference" />
              <Info label="Payout Provider" :value="providerLabel" />
              <Info v-if="!isLegacyUnpaid" label="Processed By" :value="person(refund.sender)" />
              <Info v-if="!isLegacyUnpaid" label="Processed At" :value="dateTime(refund.sent_at)" />
              <Info label="Notes" :value="displayRefundNotes" class="sm:col-span-2" />
            </div>
          </template></Card>
        <Card class="rounded-2xl border border-slate-200 shadow-sm"><template #title><span class="text-base">Finance
              Action</span></template><template #content>
            <div class="space-y-4">
              <p v-if="refund.status==='pending_inspection'" class="rounded-xl bg-blue-50 p-3 text-sm text-blue-700">
                Awaiting return delivery and inventory inspection before Finance can review the payout.</p><Textarea
                v-model="notes" rows="5" fluid placeholder="Finance notes" />
              <p v-if="refund.status==='pending' && refund.refund_destination_required"
                class="rounded-xl bg-amber-50 p-3 text-sm text-amber-700">
                Add a valid GCash or Maya recipient account before approving this refund.
              </p>
              <div v-if="refund.status==='pending'" class="grid gap-2">
                <Button label="Approve Refund" icon="pi pi-check" :disabled="!canApprove"
                  :loading="processing==='approved'" @click="process('approved')" />
                <Button label="Reject Refund" icon="pi pi-times" severity="danger" outlined :disabled="!canReject"
                  :loading="processing==='rejected'" @click="process('rejected')" />
              </div>
              <div v-if="refund.status==='approved'" class="space-y-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="font-semibold text-emerald-900">Approved refund</p>
                <p class="text-sm text-emerald-800">Process {{ currency(displayRefundAmount) }} to {{ refund.refund_account_name }} via {{ refundMethodLabel(refund.refund_method) }}. Ecommerce shipping fees are excluded from cancellation refunds.</p>
                <Button label="Payout" icon="pi pi-send" fluid :loading="sending"
                  :disabled="!canProcessRefund" @click="startPayout" />
              </div>
              <div v-if="refund.status === 'transfer_failed'" class="space-y-3 rounded-xl bg-red-50 p-3 text-sm text-red-800">
                <p>The refund could not be processed: {{ refund.transfer_error || 'Refresh and try again.' }}</p>
              </div>
              <div v-if="isAwaitingPayout" class="space-y-3 rounded-xl bg-amber-50 p-3 text-sm text-amber-900">
                <p class="font-medium">The refund is ready to be processed to the selected recipient.</p>
                <Button label="Payout" icon="pi pi-send" fluid :loading="sending" :disabled="!canProcessRefund" @click="startPayout" />
              </div>
              <p v-if="refund.status === 'simulated'" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">
                Refund processed to {{ refundMethodLabel(refund.refund_method) }} account {{ refund.refund_account_number }}. Reference: {{ refund.payout_reference || '-' }}
              </p>
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
const shippingFeeExcluded = ref<number | null>(null)
const notes = ref('')
const processing = ref('')
const sending = ref(false)
const amountAdjusted = computed(() => refundAmount.value !== null
  && Math.abs(Number(refundAmount.value) - Number(amountCalculation.value?.amount ?? refund.value?.amount ?? 0)) >= 0.01)
const isLegacyUnpaid = computed(() => ['simulation', 'paymongo_wallet_test'].includes(refund.value?.payout_provider) && refund.value?.status === 'sent')
const isAwaitingPayout = computed(() => refund.value?.status === 'awaiting_payout' || isLegacyUnpaid.value)
const displayRefundAmount = computed(() => amountCalculation.value?.amount ?? refund.value?.payable_refund_amount ?? refund.value?.amount ?? 0)
const displayStatusValue = computed(() => isAwaitingPayout.value ? 'awaiting_payout' : refund.value?.status)
const displayStatus = computed(() => label(displayStatusValue.value))
const providerLabel = computed(() => ({ simulation: 'Recipient payout', manual: 'Manual payout', paymongo_test: 'Recipient payout', paymongo_wallet_test: 'Recipient payout' } as Record<string, string>)[refund.value?.payout_provider] || refund.value?.payout_provider || '-')
const displayRefundNotes = computed(() => {
  const notes = String(refund.value?.notes || '-').trim()
  if (refund.value?.status === 'simulated') return notes.replace(/^Simulated payout only\. No funds moved\.\s*/i, '').trim() || 'Refund processed.'
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
  && !refund.value?.refund_destination_required)
const canReject = computed(() => auth.hasPermission('finance.refunds.approve') && refund.value?.status === 'pending')
const canProcessRefund = computed(() => auth.hasPermission('finance.refunds.approve')
  && (refund.value?.status === 'approved' || isAwaitingPayout.value)
  && !refund.value?.refund_destination_required)
const label = (v: any) => v === 'pending_inspection' ? 'For Inspection' : v === 'awaiting_payout' ? 'Awaiting Payout' : ['sent', 'simulated'].includes(v) ? 'Refunded' : String(v || '-').replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase())
const refundMethodLabel = (v: any) => ({ gcash: 'GCash', card: 'Card', maya: 'Maya', qrph: 'QR Ph', grab_pay: 'GrabPay', bank_transfer: 'Bank Transfer' } as any)[String(v || '')] || '-'
const severity = (v: string) => ['sent', 'simulated', 'approved'].includes(v) ? 'success' : ['rejected', 'transfer_failed'].includes(v) ? 'danger' : ['pending_inspection', 'processing'].includes(v) ? 'info' : 'warn'
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
    shippingFeeExcluded.value = response?.shipping_fee_excluded === null || response?.shipping_fee_excluded === undefined
      ? null
      : Number(response.shipping_fee_excluded)
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
      ? 'Refund approved. Finance can now process the payout to the recipient.'
      : 'Refund rejected.', life: 3000 })
    await load()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Processing Failed', detail: error?.response?.data?.message || 'Unable to process refund.', life: 3500 })
  } finally {
    processing.value = ''
  }
}

const startPayout = async () => {
  if (!canProcessRefund.value) return
  sending.value = true
  try {
    const detailRoute = router.resolve({ name: 'finance.refunds.detail', params: { id: route.params.id } })
    const returnUrl = new URL(detailRoute.href, window.location.origin)
    const successUrl = new URL(returnUrl)
    successUrl.searchParams.set('refund_payout', 'success')
    const cancelUrl = new URL(returnUrl)
    cancelUrl.searchParams.set('refund_payout', 'cancelled')
    const response = await financeService.createRefundPayoutCheckout(String(route.params.id), {
      success_url: successUrl.toString(),
      cancel_url: cancelUrl.toString(),
    })
    const checkoutUrl = String(response?.data?.checkout_url || '')
    if (!checkoutUrl || new URL(checkoutUrl).hostname !== 'checkout.paymongo.com') {
      throw new Error('PayMongo did not return a valid authorization page.')
    }
    window.location.assign(checkoutUrl)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Payout Failed', detail: error?.response?.data?.message || error?.message || 'Unable to open PayMongo authorization.', life: 3500 })
  } finally {
    sending.value = false
  }
}

const handlePayoutReturn = async () => {
  const outcome = String(route.query.refund_payout || '')
  if (!outcome) return
  if (outcome === 'cancelled') {
    toast.add({ severity: 'info', summary: 'Payout Not Authorized', detail: 'The refund remains ready for payout.', life: 3500 })
    await router.replace({ name: 'finance.refunds.detail', params: { id: route.params.id } })
    return
  }
  for (let attempt = 0; attempt < 5; attempt += 1) {
    try {
      const response = await financeService.confirmRefundPayout(String(route.params.id))
      if (response?.data?.status === 'sent') {
        await load()
        toast.add({ severity: 'success', summary: 'Refund Processed', detail: 'The payout was authorized and processed.', life: 3500 })
        await router.replace({ name: 'finance.refunds.detail', params: { id: route.params.id } })
        return
      }
    } catch (error: any) {
      if (error?.response?.status !== 202 && attempt === 4) {
        toast.add({ severity: 'error', summary: 'Payout Confirmation Failed', detail: error?.response?.data?.message || 'Unable to confirm the PayMongo authorization.', life: 3500 })
      }
    }
    await new Promise(resolve => window.setTimeout(resolve, 2000))
  }
  await load()
  toast.add({ severity: 'warn', summary: 'Payout Pending', detail: 'PayMongo has not confirmed the authorization yet.', life: 3500 })
  await router.replace({ name: 'finance.refunds.detail', params: { id: route.params.id } })
}

onMounted(async () => {
  await load()
  await handlePayoutReturn()
})
</script>
