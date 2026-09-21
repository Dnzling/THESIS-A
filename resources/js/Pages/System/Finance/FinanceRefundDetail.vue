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
      <Tag v-if="refund" :value="label(refund.status)" :severity="severity(refund.status)" />
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
              <Info label="Amount" :value="currency(refund.amount)" />
              <Info label="Reason" :value="refund.reason || '-'" class="sm:col-span-2" />
              <Info label="Branch" :value="refund.branch?.name || '-'" />
              <Info label="Requested" :value="dateTime(refund.created_at)" />
              <Info label="Requested By" :value="person(refund.requester)" />
              <Info label="Processed By" :value="person(refund.processor)" />
              <Info label="Processed At" :value="dateTime(refund.processed_at)" />
              <Info label="Notes" :value="refund.notes || '-'" class="sm:col-span-2" />
            </div>
          </template></Card>
        <Card class="rounded-2xl border border-slate-200 shadow-sm"><template #title><span class="text-base">Finance
              Action</span></template><template #content>
            <div class="space-y-4">
              <p v-if="refund.status==='pending_inspection'" class="rounded-xl bg-blue-50 p-3 text-sm text-blue-700">
                Awaiting return delivery and inventory inspection before Finance can release funds.</p><Textarea
                v-model="notes" rows="5" fluid placeholder="Finance notes" />
              <div class="grid gap-2"><Button label="Approve & Release" icon="pi pi-check"
                   :loading="processing==='approved'" @click="process('approved')" /><Button
                  label="Reject Refund" icon="pi pi-times" severity="danger" outlined :disabled="!canProcess"
                  :loading="processing==='rejected'" @click="process('rejected')" /></div>
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
import { computed, defineComponent, h, onMounted, ref } from 'vue'; import { useRoute, useRouter } from 'vue-router'; import { useToast } from 'primevue/usetoast'; import { useAuthStore } from '@/stores/auth'; import financeService from '@/services/finance.service'; import Card from 'primevue/card'; import Button from 'primevue/button'; import Tag from 'primevue/tag'; import Textarea from 'primevue/textarea'
const Info = defineComponent({ props: { label: String, value: String }, setup: p => () => h('div', [h('p', { class: 'text-xs font-medium uppercase tracking-wide text-slate-500' }, p.label), h('p', { class: 'mt-1 font-semibold text-slate-900' }, p.value || '-')]) }); const route = useRoute(), router = useRouter(), toast = useToast(), auth = useAuthStore(); const loading = ref(false), refund = ref<any>(null), notes = ref(''), processing = ref(''); const canProcess = computed(() => auth.hasPermission('finance.refunds.approve') && refund.value?.status === 'pending'); const label = (v: any) => String(v || '-').replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase()); const severity = (v: string) => v === 'approved' ? 'success' : v === 'rejected' ? 'danger' : v === 'pending_inspection' ? 'info' : 'warn'; const currency = (v: any) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(v || 0)); const dateTime = (v: any) => v ? new Date(v).toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : '-'; const person = (u: any) => u ? [u.fname, u.lname].filter(Boolean).join(' ') || u.email : '-'; const load = async () => { loading.value = true; try { const r = await financeService.getRefund(String(route.params.id)); refund.value = r?.data || null; notes.value = refund.value?.notes || '' } catch (e: any) { toast.add({ severity: 'error', summary: 'Load Failed', detail: e?.response?.data?.message || 'Unable to load refund.', life: 3000 }) } finally { loading.value = false } }; const process = async (status: 'approved' | 'rejected') => { processing.value = status; try { await financeService.updateRefundStatus(String(route.params.id), { status, notes: notes.value }); toast.add({ severity: 'success', summary: 'Refund Updated', detail: `Refund ${status}.`, life: 2500 }); await load() } catch (e: any) { toast.add({ severity: 'error', summary: 'Processing Failed', detail: e?.response?.data?.message || 'Unable to process refund.', life: 3500 }) } finally { processing.value = '' } }; onMounted(load)
</script>
