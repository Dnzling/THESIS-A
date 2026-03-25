<template>
  <div class="space-y-6 p-6">
    <div class="flex items-start justify-between gap-4">
      <div class="flex items-start gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push('/supplier-portal/pos')" />
        <div>
          <h1 class="text-2xl font-semibold text-gray-900">Review Purchase Order</h1>
          <p class="text-sm text-gray-500">Approve or reject this PO before delivery setup.</p>
        </div>
      </div>
      <Tag v-if="po" :value="formatStatus(po.status)" :severity="statusSeverity(po.status)" />
    </div>

    <PortalStepper class="mb-4" :steps="steps" :current="0" />

    <Card v-if="loading" class="rounded-2xl border border-slate-200/70 shadow-sm">
      <template #content>
        <div class="space-y-3">
          <Skeleton height="24px" class="rounded-lg" />
          <Skeleton height="24px" class="rounded-lg" />
          <Skeleton height="24px" class="rounded-lg" />
        </div>
      </template>
    </Card>

    <Card v-else-if="po" class="rounded-2xl border border-slate-200/70 shadow-sm">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <p class="text-xs text-gray-500">PO Number</p>
            <p class="text-lg font-semibold">{{ po.po_number }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Expected Delivery</p>
            <p class="text-sm">{{ formatDate(po.expected_delivery_date) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Total Amount</p>
            <p class="text-lg font-semibold text-green-600">₱ {{ formatMoney(po.total_amount) }}</p>
          </div>
        </div>
            <div>
              <p class="text-xs text-gray-500">Discount</p>
              <p class="text-sm text-slate-700">
                PHP {{ formatMoney(po.discount_amount) }}
                <span v-if="contractDiscountPercent" class="text-xs text-slate-400">({{ contractDiscountPercent.toFixed(2) }}%)</span>
              </p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Tax</p>
              <p class="text-sm text-slate-700">
                PHP {{ formatMoney(po.tax_amount) }}
                <span v-if="contractTaxRate" class="text-xs text-slate-400">({{ contractTaxRate.toFixed(2) }}%)</span>
              </p>
            </div>
      </template>
    </Card>

    <Card v-if="!loading && po" title="Items" class="rounded-2xl border border-slate-200/70 shadow-sm">
      <template #content>
        <DataTable :value="po.items || []" striped-rows class="p-datatable-sm">
          <Column header="Product">
            <template #body="{ data }">
              <div>
                <div class="font-semibold">{{ data.product?.product_name || 'N/A' }}</div>
                <div class="text-xs text-gray-500">{{ data.product?.sku || '' }}</div>
              </div>
            </template>
          </Column>
          <Column field="quantity_ordered" header="Qty" />
          <Column field="unit_cost" header="Unit Cost" />
          <Column header="Total">
            <template #body="{ data }">
              ₱ {{ formatMoney(data.line_total) }}
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Card v-if="!loading && isReadOnly" class="rounded-2xl border border-slate-200/70 shadow-sm">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Delivery Details</h3>
            <div class="mt-3 space-y-2 text-sm text-gray-600">
              <div>Driver: <span class="font-medium text-gray-900">{{ shipment?.driver_name || '-' }}</span></div>
              <div>Plate: <span class="font-medium text-gray-900">{{ shipment?.plate_number || '-' }}</span></div>
              <div>Distance: <span class="font-medium text-gray-900">{{ shipment?.distance_km || '-' }} km</span></div>
              <div>Shipping Cost: <span class="font-medium text-emerald-600">₱ {{ formatMoney(shipment?.shipping_cost) }}</span></div>
            </div>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Invoice</h3>
            <div class="mt-3 space-y-2 text-sm text-gray-600">
              <div>Invoice #: <span class="font-medium text-gray-900">{{ invoice?.invoice_number || '-' }}</span></div>
              <div>Status: <span class="font-medium text-gray-900">{{ formatStatus(invoice?.status) }}</span></div>
              <div>Total: <span class="font-medium text-gray-900">₱ {{ formatMoney(invoice?.net_amount || invoice?.invoice_amount) }}</span></div>
            </div>
            <Button
              v-if="invoice"
              label="Print Invoice"
              icon="pi pi-print"
              class="mt-4"
              outlined
              @click="printInvoice"
            />
          </div>
        </div>
      </template>
    </Card>

    <div v-if="!loading && !isReadOnly" class="flex items-center justify-end gap-3">
      <Button label="Reject" severity="danger" outlined @click="rejectionDialog = true" />
      <Button label="Approve" icon="pi pi-check" :loading="submitting" @click="approvePO" />
    </div>

    <Dialog v-model:visible="rejectionDialog" modal header="Reject Purchase Order" :style="{ width: '32rem' }">
      <div class="space-y-4">
        <p class="text-sm text-gray-600">Please provide a reason for rejection.</p>
        <Textarea v-model="rejectionReason" rows="4" class="w-full" placeholder="Reason for rejection" />
        <div class="flex justify-end gap-2">
          <Button label="Cancel" text @click="rejectionDialog = false" />
          <Button label="Confirm Rejection" severity="danger" :loading="submitting" @click="rejectPO" />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Skeleton from 'primevue/skeleton'
import PortalStepper from '../../../components/system/supplier/PortalStepper.vue'
import supplierService from '../../../services/supplier.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const po = ref<any>(null)
const shipment = ref<any>(null)
const invoice = ref<any>(null)
const contractTaxRate = ref(0)
const contractDiscountPercent = ref(0)
const submitting = ref(false)
const rejectionDialog = ref(false)
const rejectionReason = ref('')
const loading = ref(false)

const steps = [
  { label: 'Review PO', description: 'Approve or reject' },
  { label: 'Delivery Form', description: 'Set delivery details' },
  { label: 'Shipment', description: 'Confirm shipment' },
]

const isReadOnly = computed(() => {
  return ['in_transit', 'delivered'].includes(po.value?.status)
})

const loadPO = async () => {
  const id = Number(route.params.id)
  loading.value = true
  try {
    const res = await supplierService.getSupplierPODetail(id)
    const payload = res.data || res
    po.value = payload?.data?.po || payload?.po || null
    contractTaxRate.value = Number(payload?.data?.contract_tax_rate || 0) || 0
    contractDiscountPercent.value = Number(payload?.data?.contract_discount_percent || 0) || 0

    if (po.value?.status === 'in_transit' || po.value?.status === 'delivered') {
      const shipRes = await supplierService.getPOShipment(id)
      const shipPayload = shipRes.data || shipRes
      shipment.value = shipPayload?.data?.shipment || shipPayload?.data || shipPayload
      invoice.value = shipPayload?.data?.invoice || null
    }
  } finally {
    loading.value = false
  }
}

const statusSeverity = (status: string) => {
  if (status === 'sent_to_supplier') return 'info'
  if (status === 'supplier_accepted') return 'success'
  if (status === 'declined_supplier') return 'danger'
  return 'secondary'
}

const formatStatus = (status: string) => status?.split('_').map(word => word[0]?.toUpperCase() + word.slice(1)).join(' ') || '-'

const formatDate = (date?: string) => (date ? new Date(date).toLocaleDateString() : '-')
const formatMoney = (value: any) => new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2 }).format(Number(value || 0))

const approvePO = async () => {
  if (!po.value) return
  submitting.value = true
  try {
    await supplierService.submitPOFeedback({
      purchase_order_id: po.value.id,
      response: 'accepted',
    })
    toast.add({ severity: 'success', summary: 'Approved', detail: 'PO approved. Continue to delivery form.', life: 2500 })
    router.push(`/supplier-portal/pos/${po.value.id}/delivery-template`)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to approve PO', life: 3000 })
  } finally {
    submitting.value = false
  }
}

const rejectPO = async () => {
  if (!po.value || !rejectionReason.value.trim()) return
  submitting.value = true
  try {
    await supplierService.submitPOFeedback({
      purchase_order_id: po.value.id,
      response: 'rejected',
      rejection_reason: rejectionReason.value.trim(),
    })
    toast.add({ severity: 'success', summary: 'Rejected', detail: 'PO rejected.', life: 2500 })
    rejectionDialog.value = false
    router.push('/supplier-portal/pos')
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to reject PO', life: 3000 })
  } finally {
    submitting.value = false
  }
}

const printInvoice = () => {
  window.print()
}

onMounted(loadPO)
</script>
