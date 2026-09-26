<template>
  <div class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6">
    <div class="flex items-start justify-between gap-4">
      <div class="flex items-start gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push('/supplier-portal/pos')" />
        <div><h1 class="text-2xl font-semibold text-slate-900">Review Purchase Order</h1><p class="text-sm text-slate-500">Review order specifications and contract rates before accepting.</p></div>
      </div>
      <Badge v-if="po" :value="formatStatus(po.status)" :severity="statusSeverity(po.status)" />
    </div>

    <div v-if="loading" class="grid gap-6 lg:grid-cols-3">
      <Skeleton height="24rem" class="rounded-2xl lg:col-span-2" /><Skeleton height="24rem" class="rounded-2xl" />
    </div>
    <template v-else-if="po">
      <Card class="rounded-2xl border border-slate-200 shadow-sm">
        <template #content><div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <Info label="PO Number" :value="po.po_number" /><Info label="Destination Branch" :value="po.branch?.name || '—'" />
          <Info label="Order Date" :value="formatDate(po.order_date)" /><Info label="Expected Date" :value="formatDate(po.expected_delivery_date)" />
        </div></template>
      </Card>

      <div class="grid items-start gap-6 lg:grid-cols-3">
        <Card class="rounded-2xl border border-slate-200 shadow-sm lg:col-span-2">
          <template #title>Items to Ship</template>
          <template #content><div class="space-y-3">
            <div v-for="item in po.items || []" :key="item.id" class="rounded-xl border border-slate-200 p-4">
              <div class="flex flex-wrap items-start justify-between gap-3">
                <div><p class="font-semibold text-slate-900">{{ item.product?.product_name || 'Item' }}</p><p class="text-xs text-slate-500">{{ item.product?.sku || 'No SKU' }}</p></div>
                <p class="font-semibold text-emerald-700">{{ money(item.line_total) }}</p>
              </div>
              <div class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                <Info label="Quantity" :value="`${whole(item.quantity_ordered)} ${uom(item)}`" /><Info label="Unit Cost" :value="money(item.unit_cost)" />
                <Info label="Unit Weight" :value="`${decimal(item.weight_kg, 3)} kg`" /><Info label="Total Weight" :value="`${decimal(Number(item.weight_kg || 0) * Number(item.quantity_ordered || 0), 3)} kg`" />
                <Info label="Dimensions (L × W × H)" :value="dimensions(item)" class="col-span-2" />
              </div>
            </div>
          </div></template>
        </Card>

        <div class="space-y-6">
          <Card class="rounded-2xl border border-slate-200 shadow-sm">
            <template #title>Contract Fee Summary</template>
            <template #content><div class="space-y-3 text-sm">
              <FeeRow label="Items subtotal" :value="money(po.subtotal)" />
              <FeeRow :label="`Contract discount (${decimal(contractDiscountPercent)}%)`" :value="`− ${money(po.discount_amount)}`" value-class="text-rose-600" />
              <FeeRow label="Taxable amount" :value="money(taxableAmount)" /><FeeRow :label="`VAT / Tax (${decimal(contractTaxRate)}%)`" :value="money(po.tax_amount)" />
              <FeeRow label="Shipping fee" :value="money(po.shipping_cost)" />
              <div class="flex justify-between border-t border-slate-200 pt-3 text-base font-semibold"><span>Total</span><span class="text-emerald-700">{{ money(po.total_amount) }}</span></div>
              <p class="rounded-lg bg-blue-50 p-3 text-xs text-blue-700">Rates are taken from your active supplier contract and locked into this PO.</p>
            </div></template>
          </Card>

          <Card v-if="!isReadOnly" class="rounded-2xl border border-slate-200 shadow-sm">
            <template #title>Fulfillment</template>
            <template #content>
              <div class="rounded-xl border border-orange-200 bg-orange-50 p-4"><p class="font-semibold text-slate-900">Store Pickup</p><p class="mt-1 text-xs leading-5 text-slate-600">After you accept, the store will assign a driver and vehicle to collect the order. No delivery method selection is needed.</p></div>
            </template>
          </Card>
          <div v-if="!isReadOnly" class="grid grid-cols-2 gap-3"><Button label="Reject" severity="danger" outlined @click="rejectionDialog = true" /><Button label="Approve PO" icon="pi pi-check" :loading="submitting" @click="approvePO" /></div>
        </div>
      </div>
    </template>

    <Dialog v-model:visible="rejectionDialog" modal header="Reject Purchase Order" :style="{ width: '32rem', maxWidth: '95vw' }">
      <div class="space-y-4"><Textarea v-model="rejectionReason" rows="4" class="w-full" placeholder="Reason for rejection" /><div class="flex justify-end gap-2"><Button label="Cancel" text @click="rejectionDialog = false" /><Button label="Confirm Rejection" severity="danger" :loading="submitting" @click="rejectPO" /></div></div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Badge from 'primevue/badge'; import Button from 'primevue/button'; import Card from 'primevue/card'; import Dialog from 'primevue/dialog'; import Skeleton from 'primevue/skeleton'; import Textarea from 'primevue/textarea'
import supplierService from '../../../services/supplier.service'

const Info = defineComponent({ props: { label: String, value: [String, Number] }, setup: p => () => h('div', [h('p', { class: 'text-xs text-slate-500' }, p.label), h('p', { class: 'mt-1 font-medium text-slate-900' }, String(p.value ?? '—'))]) })
const FeeRow = defineComponent({ props: { label: String, value: String, valueClass: String }, setup: p => () => h('div', { class: 'flex justify-between gap-3' }, [h('span', { class: 'text-slate-500' }, p.label), h('span', { class: ['font-medium text-slate-900', p.valueClass] }, p.value)]) })
const route = useRoute(); const router = useRouter(); const toast = useToast()
const po = ref<any>(null); const contractTaxRate = ref(0); const contractDiscountPercent = ref(0); const submitting = ref(false); const rejectionDialog = ref(false); const rejectionReason = ref(''); const loading = ref(false)
const isReadOnly = computed(() => po.value?.status !== 'sent_to_supplier')
const taxableAmount = computed(() => Math.max(0, Number(po.value?.subtotal || 0) - Number(po.value?.discount_amount || 0)))
const loadPO = async () => { loading.value = true; try { const res = await supplierService.getSupplierPODetail(Number(route.params.id)); const payload = res.data || res; po.value = payload?.data?.po || payload?.po || null; contractTaxRate.value = Number(payload?.data?.contract_tax_rate || po.value?.contract_tax_rate || 0); contractDiscountPercent.value = Number(payload?.data?.contract_discount_percent || po.value?.contract_discount_percentage || 0) } finally { loading.value = false } }
const approvePO = async () => { if (!po.value) return; submitting.value = true; try { await supplierService.submitPOFeedback({ purchase_order_id: po.value.id, response: 'accepted', fulfillment_method: 'store_pickup' }); toast.add({ severity: 'success', summary: 'PO Approved', detail: 'The store will arrange pickup.', life: 2500 }); router.push(`/supplier-portal/pos/${po.value.id}/view`) } catch (e: any) { toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.message || 'Failed to approve PO', life: 3000 }) } finally { submitting.value = false } }
const rejectPO = async () => { if (!po.value || !rejectionReason.value.trim()) return; submitting.value = true; try { await supplierService.submitPOFeedback({ purchase_order_id: po.value.id, response: 'rejected', rejection_reason: rejectionReason.value.trim() }); toast.add({ severity: 'success', summary: 'PO Rejected', detail: 'The business has been notified.', life: 2500 }); router.push('/supplier-portal/pos') } catch (e: any) { toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.message || 'Failed to reject PO', life: 3000 }) } finally { submitting.value = false } }
const money = (v: any) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(v || 0)); const decimal = (v: any, d = 2) => Number(v || 0).toLocaleString('en-PH', { maximumFractionDigits: d }); const whole = (v: any) => Number(v || 0).toLocaleString('en-PH', { maximumFractionDigits: 0 }); const uom = (i: any) => i.product?.unit_of_measurement || 'unit'; const dimensions = (i: any) => i.length_cm && i.width_cm && i.height_cm ? `${decimal(i.length_cm)} × ${decimal(i.width_cm)} × ${decimal(i.height_cm)} cm` : '—'; const formatDate = (d?: string) => d ? new Date(d).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }) : '—'; const formatStatus = (s: string) => s?.split('_').map(w => w[0]?.toUpperCase() + w.slice(1)).join(' ') || '—'; const statusSeverity = (s: string) => s === 'supplier_accepted' ? 'success' : s === 'declined_supplier' ? 'danger' : 'info'
onMounted(loadPO)
</script>
