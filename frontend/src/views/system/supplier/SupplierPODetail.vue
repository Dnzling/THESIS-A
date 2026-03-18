<template>
  <div class="supplier-po-detail space-y-6 p-6">
    <div class="flex items-start justify-between gap-4">
      <div class="flex items-start gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push('/supplier-portal/pos')" />
        <div>
          <h1 class="text-2xl font-semibold text-slate-900">Purchase Order Details</h1>
          <p class="text-sm text-slate-500">Full PO details with delivery and invoice information.</p>
        </div>
      </div>
      <Tag v-if="po" :value="formatStatus(po.status)" :severity="getStatusSeverity(po.status)" />
    </div>

    <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <Card class="lg:col-span-2 rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <Skeleton height="220px" class="rounded-lg" />
        </template>
      </Card>
      <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <Skeleton height="220px" class="rounded-lg" />
        </template>
      </Card>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 space-y-6">
        <Card v-if="isDeclined" class="rounded-2xl border border-red-200/70 bg-red-50/60 shadow-sm">
          <template #content>
            <h2 class="text-lg font-semibold text-red-700">Rejected by Supplier</h2>
            <p class="mt-2 text-sm text-red-600">{{ rejectionReason || 'No rejection reason provided.' }}</p>
          </template>
        </Card>

        <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
          <template #content>
            <h2 class="text-lg font-semibold text-slate-900">PO Summary</h2>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-slate-600">
              <div>
                <p class="text-xs text-slate-500">PO Number</p>
                <p class="font-semibold text-slate-900">{{ po?.po_number || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500">Created</p>
                <p>{{ formatDate(po?.created_at) }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500">Supplier</p>
                <p>{{ po?.supplier?.supplier_name || '-' }}</p>
              </div>
            </div>
          </template>
        </Card>

        <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
          <template #content>
            <h2 class="text-lg font-semibold text-slate-900">Branch Information</h2>
            <div class="mt-4 text-sm text-slate-600">
              <p class="font-semibold text-slate-900">{{ po?.branch?.name || '-' }}</p>
              <p>{{ po?.branch?.address || '-' }} {{ po?.branch?.city || '' }}</p>
            </div>
          </template>
        </Card>

        <Card v-if="!isDeclined" class="rounded-2xl border border-slate-200/70 shadow-sm">
          <template #content>
            <h2 class="text-lg font-semibold text-slate-900 mb-3">Items</h2>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
              <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                  <tr>
                    <th class="px-4 py-2">Item</th>
                    <th class="px-4 py-2 text-right">Qty</th>
                    <th class="px-4 py-2 text-right">Price</th>
                    <th class="px-4 py-2 text-right">Line Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in po?.items || []" :key="item.id" class="border-t">
                    <td class="px-4 py-2">
                      <div class="font-medium text-slate-900">{{ item.product?.product_name || 'Item' }}</div>
                      <div class="text-xs text-slate-400">{{ item.product?.sku || '' }}</div>
                    </td>
                    <td class="px-4 py-2 text-right">{{ item.quantity_ordered }}</td>
                    <td class="px-4 py-2 text-right">₱ {{ formatMoney(item.unit_cost) }}</td>
                    <td class="px-4 py-2 text-right font-semibold">₱ {{ formatMoney(item.line_total) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="mt-4 rounded-xl border border-slate-200 p-4 text-sm text-slate-600">
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Subtotal (Items)</span>
                <span class="font-semibold text-slate-900">₱ {{ formatMoney(po?.subtotal) }}</span>
              </div>
              <div class="flex items-center justify-between mt-2">
                <span class="text-slate-500">Delivery Charge</span>
                <span class="font-semibold text-emerald-600">₱ {{ formatMoney(deliveryCharge) }}</span>
              </div>
              <div class="flex items-center justify-between mt-3 border-t border-slate-200 pt-3 text-base font-semibold">
                <span>Total</span>
                <span>₱ {{ formatMoney(totalWithDelivery) }}</span>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <div v-if="!isDeclined" class="space-y-6">
        <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
          <template #content>
            <h3 class="text-lg font-semibold text-slate-900">Delivery Information</h3>
            <div class="mt-4 space-y-2 text-sm text-slate-600">
              <div>Driver: <span class="font-medium text-slate-900">{{ shipment?.driver_name || '-' }}</span></div>
              <div>Plate: <span class="font-medium text-slate-900">{{ shipment?.plate_number || '-' }}</span></div>
              <div>Distance: <span class="font-medium text-slate-900">{{ shipment?.distance_km || '-' }} km</span></div>
              <div>Status: <span class="font-medium text-slate-900">{{ formatStatus(shipment?.status) }}</span></div>
            </div>
          </template>
        </Card>

        <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
          <template #content>
            <h3 class="text-lg font-semibold text-slate-900">Invoice</h3>
            <div v-if="invoice" class="mt-4 space-y-2 text-sm text-slate-600">
              <div>Invoice #: <span class="font-medium text-slate-900">{{ invoice.invoice_number }}</span></div>
              <div>Status: <span class="font-medium text-slate-900">{{ formatStatus(invoice.status) }}</span></div>
              <div>Invoice Date: <span class="font-medium text-slate-900">{{ formatDate(invoice.invoice_date) }}</span></div>
              <div>Amount: <span class="font-medium text-slate-900">₱ {{ formatMoney(invoice.net_amount || invoice.invoice_amount) }}</span></div>
              <div class="flex flex-wrap gap-2 mt-4">
                <Button label="View Invoice" icon="pi pi-file" outlined @click="viewInvoice" />
                <Button label="Print Invoice" icon="pi pi-print" outlined @click="printInvoice" />
              </div>
            </div>
            <div v-else class="text-sm text-slate-500 mt-4">No invoice available yet.</div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Skeleton from 'primevue/skeleton'
import supplierService from '../../../services/supplier.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const po = ref<any>(null)
const shipment = ref<any>(null)
const invoice = ref<any>(null)
const rejectionReason = ref<string | null>(null)

const deliveryCharge = computed(() => {
  return Number(invoice.value?.shipping_cost || shipment.value?.shipping_cost || 0)
})

const totalWithDelivery = computed(() => {
  const subtotal = Number(po.value?.subtotal || 0)
  return subtotal + deliveryCharge.value
})

const isDeclined = computed(() => {
  const status = po.value?.status
  return status === 'declined_supplier' || status === 'declined_by_supplier'
})

const getStatusSeverity = (status: string) => {
  const map: { [key: string]: string } = {
    draft: 'secondary',
    pending_finance_approval: 'warning',
    approved: 'success',
    sent_to_supplier: 'info',
    supplier_accepted: 'success',
    in_transit: 'warning',
    delivered: 'success',
    rejected_finance: 'danger',
    declined_supplier: 'danger',
    cancelled: 'danger',
  }
  return map[status] || 'info'
}

const formatStatus = (status: string) => {
  if (!status) return '-'
  return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString()
}

const formatMoney = (value: number) => new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2 }).format(value || 0)

const loadDetail = async () => {
  try {
    loading.value = true
    const id = Number(route.params.id)
    const res = await supplierService.getSupplierPODetail(id)
    const payload = res.data || res
    po.value = payload?.data?.po || payload?.po || null
    rejectionReason.value = payload?.data?.rejection_reason || payload?.rejection_reason || null
    shipment.value = payload?.data?.shipment || null
    invoice.value = payload?.data?.invoice || null

    if (!isDeclined.value && po.value?.id) {
      if (!shipment.value) {
        const shipmentRes = await supplierService.getPOShipment(id)
        const shipmentPayload = shipmentRes.data || shipmentRes
        shipment.value = shipmentPayload?.data?.shipment || shipmentPayload?.data || shipmentPayload
      }
      if (!invoice.value) {
        try {
          const invoiceRes = await supplierService.getPOInvoice(id)
          const invoicePayload = invoiceRes.data || invoiceRes
          invoice.value = invoicePayload?.data || invoicePayload || null
        } catch {
          invoice.value = null
        }
      }
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load PO details.',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const printInvoice = () => {
  window.print()
}

const viewInvoice = () => {
  if (!po.value?.id) return
  router.push(`/supplier-portal/pos/${po.value.id}/invoice-view`)
}

onMounted(loadDetail)
</script>

<style scoped lang="scss">
.supplier-po-detail {
  padding: 20px;
}
</style>
