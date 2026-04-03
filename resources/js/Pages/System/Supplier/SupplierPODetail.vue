<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <!-- iOS-style Header -->
    <div class="flex items-start justify-between">
      <div class="flex items-start gap-4">
        <button 
          @click="router.push('/supplier-portal/pos')"
          class="w-10 h-10 rounded-full bg-white hover:bg-gray-100 flex items-center justify-center transition-colors shadow-sm border border-gray-200"
        >
          <i class="pi pi-chevron-left text-gray-600 text-lg"></i>
        </button>
        <div>
          <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Purchase Order Details</h1>
          <p class="text-sm text-gray-500 mt-1">Full PO details with delivery and shipment information</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Button
          v-if="canCreateInvoice"
          :loading="invoiceCreating"
          :disabled="invoiceCreating"
          label="Create Invoice"
          icon="pi pi-file"
          severity="success"
          text
          @click="createInvoiceFromReceipt"
        />
        <Button
          v-else-if="existingInvoice"
          label="View Invoice"
          icon="pi pi-eye"
          severity="info"
          text
          @click="router.push({ name: 'supplier.pos.invoice-view', params: { id: existingInvoice.id } })"
        />
        <Tag 
          v-if="po" 
          :value="formatStatus(po.status)" 
          :severity="getStatusSeverity(po.status)"
          class="rounded-full px-3 py-1 text-xs font-medium"
        />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 space-y-6">
        <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <template #content>
            <div class="p-6">
              <Skeleton width="150px" height="20px" class="mb-4" />
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Skeleton height="60px" class="rounded-xl" />
                <Skeleton height="60px" class="rounded-xl" />
                <Skeleton height="60px" class="rounded-xl" />
              </div>
            </div>
          </template>
        </Card>
        <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <template #content>
            <div class="p-6">
              <Skeleton width="150px" height="20px" class="mb-4" />
              <Skeleton height="200px" class="rounded-xl" />
            </div>
          </template>
        </Card>
      </div>
      <div class="space-y-6">
        <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <template #content>
            <div class="p-6">
              <Skeleton width="150px" height="20px" class="mb-4" />
              <div class="space-y-3">
                <Skeleton height="20px" v-for="i in 5" :key="i" />
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>

    <!-- Main Content -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left Column - PO Details -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Rejection Banner -->
        <Card v-if="isDeclined" class="rounded-2xl border border-red-200 bg-red-50/60 shadow-sm overflow-hidden">
          <template #content>
            <div class="p-6">
              <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                  <i class="pi pi-exclamation-circle text-red-600 text-lg"></i>
                </div>
                <div>
                  <h3 class="font-semibold text-red-800">Rejected by Supplier</h3>
                  <p class="mt-2 text-sm text-red-700">{{ rejectionReason || 'No rejection reason provided.' }}</p>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <!-- PO Summary Card -->
        <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <template #header>
            <div class="px-6 pt-6">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                  <i class="pi pi-file text-blue-600 text-sm"></i>
                </div>
                <h2 class="text-lg font-semibold text-gray-900">PO Summary</h2>
              </div>
            </div>
          </template>
          <template #content>
            <div class="p-6 pt-0">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 rounded-xl p-4">
                  <p class="text-xs text-gray-500 mb-1">PO Number</p>
                  <p class="font-semibold text-gray-900">{{ po?.po_number || '-' }}</p>
                </div>
                <div class="bg-gray-50 rounded-xl p-4">
                  <p class="text-xs text-gray-500 mb-1">Created</p>
                  <p class="font-medium text-gray-900">{{ formatDate(po?.created_at) }}</p>
                </div>
                <div class="bg-gray-50 rounded-xl p-4">
                  <p class="text-xs text-gray-500 mb-1">Supplier</p>
                  <p class="font-medium text-gray-900">{{ po?.supplier?.supplier_name || '-' }}</p>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <!-- Branch Information Card -->
        <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <template #header>
            <div class="px-6 pt-6">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                  <i class="pi pi-map-marker text-green-600 text-sm"></i>
                </div>
                <h2 class="text-lg font-semibold text-gray-900">Branch Information</h2>
              </div>
            </div>
          </template>
          <template #content>
            <div class="p-6 pt-0">
              <div class="bg-gray-50 rounded-xl p-4">
                <p class="font-semibold text-gray-900">{{ po?.branch?.name || '-' }}</p>
                <p class="text-sm text-gray-600 mt-1">{{ po?.branch?.address || '-' }} {{ po?.branch?.city || '' }}</p>
                <p v-if="po?.branch?.contact_number" class="text-sm text-gray-600 mt-2">📞 {{ po?.branch?.contact_number }}</p>
              </div>
            </div>
          </template>
        </Card>

        <!-- Items Card -->
        <Card v-if="!isDeclined" class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <template #header>
            <div class="px-6 pt-6">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
                  <i class="pi pi-box text-purple-600 text-sm"></i>
                </div>
                <h2 class="text-lg font-semibold text-gray-900">Items</h2>
              </div>
            </div>
          </template>
          <template #content>
            <div class="p-6 pt-0">
              <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="w-full text-sm">
                  <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                    <tr>
                      <th class="px-4 py-3 text-left">Item</th>
                      <th class="px-4 py-3 text-right">Qty</th>
                      <th class="px-4 py-3 text-right">Price</th>
                      <th class="px-4 py-3 text-right">Line Total</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                    <tr v-for="item in po?.items || []" :key="item.id" class="hover:bg-gray-50 transition-colors">
                      <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{{ item.product?.product_name || 'Item' }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ item.product?.sku || '' }}</div>
                      </td>
                      <td class="px-4 py-3 text-right font-medium">{{ item.quantity_ordered }}</td>
                      <td class="px-4 py-3 text-right">₱{{ formatMoney(item.unit_cost) }}</td>
                      <td class="px-4 py-3 text-right font-semibold text-green-600">₱{{ formatMoney(item.line_total) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Totals Summary -->
              <div class="mt-4 bg-gray-50 rounded-xl p-4">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Subtotal (Items)</span>
                  <span class="font-semibold text-gray-900">₱{{ formatMoney(po?.subtotal) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm mt-2">
                  <span class="text-gray-600">Delivery Charge</span>
                  <span class="font-semibold text-emerald-600">₱{{ formatMoney(deliveryCharge) }}</span>
                </div>
                <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-200 text-base font-semibold">
                  <span class="text-gray-900">Total</span>
                  <span class="text-blue-600">₱{{ formatMoney(totalWithDelivery) }}</span>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Right Column - Shipment Information -->
      <div v-if="!isDeclined" class="space-y-6">
        <!-- Delivery Information Card -->
        <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <template #header>
            <div class="px-6 pt-6">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center">
                  <i class="pi pi-truck text-orange-600 text-sm"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Delivery Information</h3>
              </div>
            </div>
          </template>
          <template #content>
            <div class="p-6 pt-0">
              <div class="space-y-3">
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                  <span class="text-sm text-gray-500">Driver</span>
                  <span class="font-medium text-gray-900">{{ shipment?.driver_name || '-' }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                  <span class="text-sm text-gray-500">Plate Number</span>
                  <span class="font-medium text-gray-900">{{ shipment?.plate_number || '-' }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                  <span class="text-sm text-gray-500">Truck</span>
                  <span class="font-medium text-gray-900">{{ shipment?.truck_brand || shipment?.truck_type || '-' }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                  <span class="text-sm text-gray-500">Distance</span>
                  <span class="font-medium text-gray-900">{{ shipment?.distance_km || '-' }} km</span>
                </div>
                <div class="flex items-center justify-between py-2">
                  <span class="text-sm text-gray-500">Cost per KM</span>
                  <span class="font-medium text-gray-900">₱{{ formatMoney(shipment?.cost_per_km) }}</span>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <!-- Shipment Summary Card -->
        <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <template #header>
            <div class="px-6 pt-6">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                  <i class="pi pi-send text-blue-600 text-sm"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Shipment Summary</h3>
              </div>
            </div>
          </template>
          <template #content>
            <div class="p-6 pt-0">
              <div class="space-y-3">
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                  <span class="text-sm text-gray-500">Status</span>
                  <Tag 
                    :value="formatStatus(shipment?.status)" 
                    :severity="getShipmentStatusSeverity(shipment?.status)"
                    class="rounded-full text-xs px-3 py-1"
                  />
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                  <span class="text-sm text-gray-500">Shipping Cost</span>
                  <span class="font-medium text-emerald-600">₱{{ formatMoney(shipment?.shipping_cost) }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                  <span class="text-sm text-gray-500">Tax Rate</span>
                  <span class="font-medium text-gray-900">{{ Number(shipment?.tax_rate || 0).toFixed(2) }}%</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                  <span class="text-sm text-gray-500">Expected Delivery</span>
                  <span class="font-medium text-gray-900">{{ formatDate(shipment?.expected_delivery_date) }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                  <span class="text-sm text-gray-500">Dispatched</span>
                  <span class="font-medium text-gray-900">{{ formatDate(shipment?.dispatched_at, true) }}</span>
                </div>
              </div>

              <!-- Addresses -->
              <div class="mt-4 pt-4 border-t border-gray-200">
                <p class="text-xs text-gray-500 mb-2">Origin</p>
                <p class="text-sm text-gray-700 bg-gray-50 rounded-xl p-3">{{ shipment?.origin_address || '-' }}</p>
              </div>
              <div class="mt-3">
                <p class="text-xs text-gray-500 mb-2">Destination</p>
                <p class="text-sm text-gray-700 bg-gray-50 rounded-xl p-3">{{ shipment?.destination_address || '-' }}</p>
              </div>
            </div>
          </template>
        </Card>
        <Card v-if="goodsReceipt.value" class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <template #header>
            <div class="px-6 pt-6">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center">
                  <i class="pi pi-check-circle text-emerald-600 text-sm"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Goods Receipt</h3>
              </div>
            </div>
          </template>
          <template #content>
            <div class="p-6 pt-0 space-y-5">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-1">
                  <p class="text-xs text-gray-500 uppercase tracking-wide">GRN Number</p>
                  <p class="font-semibold text-gray-900">{{ goodsReceipt?.grn_number || '-' }}</p>
                </div>
                <div class="space-y-1">
                  <p class="text-xs text-gray-500 uppercase tracking-wide">Status</p>
                  <Tag
                    :value="formatGoodsReceiptStatus(goodsReceipt?.receipt_status)"
                    :severity="goodsReceiptStatusSeverity(goodsReceipt?.receipt_status)"
                    class="rounded-full text-xs px-3 py-1"
                  />
                </div>
                <div class="space-y-1">
                  <p class="text-xs text-gray-500 uppercase tracking-wide">Received Date</p>
                  <p class="font-semibold text-gray-900">{{ formatDate(goodsReceiptReceivedDate) }}</p>
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-1">
                  <p class="text-xs text-gray-500 uppercase tracking-wide">Qty Expected</p>
                  <p class="font-semibold text-gray-900">{{ formatQuantity(goodsReceiptTotals.expected) }}</p>
                </div>
                <div class="space-y-1">
                  <p class="text-xs text-gray-500 uppercase tracking-wide">Qty Received</p>
                  <p class="font-semibold text-emerald-600">{{ formatQuantity(goodsReceiptTotals.received) }}</p>
                </div>
                <div class="space-y-1">
                  <p class="text-xs text-gray-500 uppercase tracking-wide">Qty Damaged</p>
                  <p class="font-semibold text-red-600">{{ formatQuantity(goodsReceiptTotals.damaged) }}</p>
                </div>
              </div>
              <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="min-w-full text-sm">
                  <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                    <tr>
                      <th class="px-4 py-3 text-left">Item</th>
                      <th class="px-4 py-3 text-right">Expected</th>
                      <th class="px-4 py-3 text-right">Received</th>
                      <th class="px-4 py-3 text-right">Damaged</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                    <tr
                      v-for="item in goodsReceiptItems"
                      :key="item.id"
                      class="hover:bg-gray-50 transition-colors"
                    >
                      <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{{ getGoodsReceiptProductName(item) }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ item.product?.sku || 'SKU unavailable' }}</div>
                      </td>
                      <td class="px-4 py-3 text-right font-medium">{{ formatQuantity(item.quantity_expected) }}</td>
                      <td class="px-4 py-3 text-right font-medium text-emerald-600">{{ formatQuantity(item.quantity_received) }}</td>
                      <td class="px-4 py-3 text-right font-medium text-red-600">{{ formatQuantity(item.quantity_damaged) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
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
import supplierService from '../../../services/supplier.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const po = ref<any>(null)
const shipment = ref<any>(null)
const goodsReceipt = ref<any>(null)
const existingInvoice = ref<any>(null)
const invoiceCreating = ref(false)
const rejectionReason = ref<string | null>(null)

// Computed properties
const deliveryCharge = computed(() => {
  return Number(shipment.value?.shipping_cost || 0)
})

const totalWithDelivery = computed(() => {
  const subtotal = Number(po.value?.subtotal || 0)
  return subtotal + deliveryCharge.value
})

const isDeclined = computed(() => {
  const status = po.value?.status
  return status === 'declined_supplier' || status === 'declined_by_supplier'
})

const canCreateInvoice = computed(() => {
  return (
    po.value?.status === 'goods_received' &&
    goodsReceipt.value &&
    !existingInvoice.value
  )
})


const goodsReceiptItems = computed(() => goodsReceipt.value?.items || [])

const goodsReceiptTotals = computed(() => {
  const items = goodsReceiptItems.value
  return items.reduce<{ expected: number; received: number; damaged: number }>(
    (acc, item) => {
      acc.expected += Number(item.quantity_expected || 0)
      acc.received += Number(item.quantity_received || 0)
      acc.damaged += Number(item.quantity_damaged || 0)
      return acc
    },
    { expected: 0, received: 0, damaged: 0 }
  )
})

const goodsReceiptReceivedDate = computed(() => {
  return goodsReceipt.value?.receipt_date || goodsReceipt.value?.received_date || goodsReceipt.value?.created_at
})


// Helper functions
const getStatusSeverity = (status: string): 'success' | 'info' | 'warn' | 'danger' | 'secondary' => {
  const map: Record<string, any> = {
    draft: 'secondary',
    pending_finance_approval: 'warn',
    approved: 'success',
    sent_to_supplier: 'info',
    supplier_accepted: 'success',
    in_transit: 'warn',
    delivered: 'success',
    rejected_finance: 'danger',
    declined_supplier: 'danger',
    cancelled: 'danger',
  }
  return map[status] || 'info'
}

const getShipmentStatusSeverity = (status: string): 'success' | 'info' | 'warn' | 'danger' | 'secondary' => {
  const map: Record<string, any> = {
    pending: 'secondary',
    dispatched: 'info',
    in_transit: 'warn',
    delivered: 'success',
    cancelled: 'danger',
  }
  return map[status] || 'info'
}

const formatGoodsReceiptStatus = (status: string): string => {
  if (!status) return 'Pending'
  const map: Record<string, string> = {
    full: 'Full',
    partial: 'Partial',
    damaged: 'Damaged',
    rejected: 'Rejected',
  }
  return map[status] || status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const goodsReceiptStatusSeverity = (status: string): 'success' | 'warn' | 'danger' | 'info' => {
  if (!status) return 'info'
  const map: Record<string, 'success' | 'warn' | 'danger' | 'info'> = {
    full: 'success',
    partial: 'warn',
    damaged: 'danger',
    rejected: 'danger',
  }
  return map[status] || 'info'
}

const getGoodsReceiptProductName = (item: any): string => {
  return item?.product?.product_name || 'Item'
}

const formatQuantity = (value?: number): string => {
  return Number(value ?? 0).toLocaleString('en-PH')
}

const formatStatus = (status: string): string => {
  if (!status) return '-'
  return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const formatDate = (date?: string, withTime = false): string => {
  if (!date) return '-'
  const parsed = new Date(date)
  if (isNaN(parsed.getTime())) return date
  
  const options: Intl.DateTimeFormatOptions = { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  }
  if (withTime) {
    options.hour = '2-digit'
    options.minute = '2-digit'
  }
  return parsed.toLocaleDateString('en-PH', options)
}

const formatMoney = (value: number): string => {
  return new Intl.NumberFormat('en-PH', { 
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(value || 0)
}

const goToInvoice = () => {
  if (!po.value) return
  router.push({ name: 'supplier.pos.invoice', params: { id: po.value.id } })
}

// Load detail
  const loadDetail = async () => {
    try {
      loading.value = true
      const id = Number(route.params.id)
      const res = await supplierService.getSupplierPODetail(id)
      const payload = res.data || res
      po.value = payload?.data?.po || payload?.po || null
      rejectionReason.value = payload?.data?.rejection_reason || payload?.rejection_reason || null
      shipment.value = payload?.data?.shipment || null
      goodsReceipt.value = payload?.data?.goods_receipt || null
      existingInvoice.value = payload?.data?.invoice || null

      if (!isDeclined.value && po.value?.id && !shipment.value) {
        const shipmentRes = await supplierService.getPOShipment(id)
        const shipmentPayload = shipmentRes.data.data || shipmentRes
        shipment.value = shipmentPayload?.data?.shipment || shipmentPayload?.data || shipmentPayload
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

  const createInvoiceFromReceipt = async () => {
    if (!po.value?.id || !goodsReceipt.value?.id) return
    invoiceCreating.value = true
    try {
      const response = await supplierService.createInvoiceFromGoodsReceipt({
        purchase_order_id: po.value.id,
        goods_receipt_id: goodsReceipt.value.id,
      })

      const invoicePayload = response?.data || response
      existingInvoice.value = invoicePayload?.data || invoicePayload

      toast.add({
        severity: 'success',
        summary: 'Invoice Draft Created',
        detail: 'A draft invoice has been generated and sent to finance.',
        life: 4000,
      })

      if (existingInvoice.value?.id) {
        router.push({ name: 'supplier.pos.invoice-view', params: { id: existingInvoice.value.id } })
      }
    } catch (error: any) {
      toast.add({
        severity: 'error',
        summary: 'Invoice Error',
        detail: error.response?.data?.message || 'Failed to create invoice from receipt.',
        life: 4000,
      })
    } finally {
      invoiceCreating.value = false
    }
  }

onMounted(loadDetail)
</script>

<style scoped>
/* Smooth transitions */
* {
  transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

/* iOS-style shadows */
:deep(.p-card) {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02);
}

:deep(.p-card .p-card-content) {
  padding: 0;
}

/* iOS-style tags */
:deep(.p-tag) {
  border-radius: 9999px;
  font-weight: 500;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}
</style>
