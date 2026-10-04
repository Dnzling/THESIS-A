<template>
  <div class="max-w-6xl mx-auto space-y-4 pb-6">
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <Button icon="pi pi-arrow-left" text rounded size="small"
          @click="router.push({ name: 'procurement.suppliers' })" />
        <div>
          <h2 class="text-lg font-semibold text-gray-900">Supplier Relationship</h2>
  
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Button v-if="!loading && supplier && activeContractsCount === 0" label="Create Contract" icon="pi pi-plus"
          severity="warn" size="small" @click="createContract" />
        <Tag :value="humanize(supplier?.contract_status || 'no_contract')"
          :severity="contractStatusSeverity(supplier?.contract_status || 'no_contract')" />
      </div>
    </div>
  
    <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <Skeleton height="200px" class="rounded-lg" />
      <Skeleton height="200px" class="rounded-lg" />
      <Skeleton height="200px" class="rounded-lg" />
    </div>
  
    <template v-else-if="supplier">
  
  
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <Card>
          <template #content>
            <div class="space-y-3">
              <div>
                <h3 class="text-xl font-bold text-slate-900">{{ supplier.supplier_name || '-' }}</h3>
                <p class="text-xs text-slate-500">{{ supplier.company_name || 'No company name' }}</p>
              </div>
  
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                <InfoRow label="Supplier Code" :value="supplier.supplier_code" />
                <InfoRow label="Supplier Type" :value="humanize(supplier.supplier_type)" />
                <InfoRow label="Contact Person" :value="supplier.contact_person" />
                <InfoRow label="Email" :value="supplier.email" />
                <InfoRow label="Phone" :value="supplier.phone || supplier.mobile" />
                <InfoRow label="TIN" :value="supplier.tin" />
                <InfoRow label="Website" :value="supplier.website" />
              </div>
  
              <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                <p class="text-[11px] uppercase tracking-wide text-slate-500">Address</p>
                <p class="text-sm text-slate-800">{{ supplierAddress }}</p>
              </div>
            </div>
          </template>
        </Card>
  
        <Card>
          <template #content>
            <div class="space-y-2 text-sm">
              <h4 class="text-sm font-semibold text-slate-900">Store Relationship</h4>
              <InfoRow label="Linked Since" :value="formatDateTime(supplier.created_at)" />
              <div
                class="flex items-center justify-between gap-2 rounded-md border border-slate-200 px-2 py-1.5 bg-white">
                <div class="min-w-0 flex-1">
                  <p class="text-[11px] uppercase tracking-wide text-slate-500">Total Contracts</p>
                  <p class="text-sm font-medium text-slate-900">{{ contracts.length }}</p>
                </div>
                <Button icon="pi pi-eye" text rounded size="small" v-tooltip="'View contracts'"
                  @click="openContractsDialog" />
              </div>
              <InfoRow label="Products Linked" :value="productsCount" />
              <InfoRow label="Total Orders" :value="performance?.total_orders ?? supplier.total_orders ?? 0" />
              <InfoRow label="Rating" :value="`${performance?.rating ?? supplier.rating ?? 0}/5`" />
            </div>
          </template>
        </Card>
      </div>
  
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
        <Card v-for="metric in performanceCards" :key="metric.label" class="border border-slate-200 shadow-sm">
          <template #content>
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="text-xs uppercase tracking-wide text-black">{{ metric.label }}</p>
                <p class="mt-1 text-xl font-semibold text-black">{{ metric.value }}</p>
              </div>
              <i :class="[metric.icon, metric.iconColor, 'text-2xl']" aria-hidden="true" />
            </div>
          </template>
        </Card>
      </div>

      <Card>
        <template #header>
          <div class="flex items-center justify-between px-4 pb-2 pt-4">
            <div>
              <h3 class="text-sm font-semibold text-slate-900">Goods Receipt Evaluations</h3>
              <p class="mt-1 text-xs text-slate-500">Ratings submitted by Inventory after receiving supplier orders.</p>
            </div>
            <Badge :value="`${performance?.evaluation_count ?? 0} evaluations`" severity="info" />
          </div>
        </template>
        <template #content>
          <div v-if="performance?.evaluation_count" class="space-y-5">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
              <div v-for="metric in evaluationCards" :key="metric.label" class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-xs text-slate-500">{{ metric.label }}</p>
                <div class="mt-2 flex items-center justify-between gap-2">
                  <span class="text-lg font-semibold text-slate-900">{{ metric.value }}/5</span>
                  <i class="pi pi-star-fill text-amber-500" aria-hidden="true" />
                </div>
              </div>
            </div>

            <div>
              <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Recent evaluations</h4>
              <div class="divide-y divide-slate-100 rounded-lg border border-slate-200">
                <div v-for="evaluation in performance.recent_evaluations || []" :key="evaluation.id" class="grid gap-2 p-3 text-sm md:grid-cols-[1fr_auto_auto] md:items-center">
                  <div>
                    <p class="font-medium text-slate-900">
                      {{ evaluation.goods_receipt?.grn_number || `Receipt #${evaluation.goods_receipt_id}` }}
                      <span class="font-normal text-slate-400">·</span>
                      {{ evaluation.purchase_order?.po_number || `PO #${evaluation.purchase_order_id}` }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                      {{ evaluatorName(evaluation.evaluator) }} · {{ formatDate(evaluation.created_at) }}
                    </p>
                    <p v-if="evaluation.remarks" class="mt-2 text-slate-600">{{ evaluation.remarks }}</p>
                  </div>
                  <Badge :value="`${Number(evaluation.overall_rating).toFixed(2)}/5`" severity="success" />
                  <Button label="View Receipt" size="small" text @click="viewGoodsReceipt(evaluation.goods_receipt_id)" />
                </div>
              </div>
            </div>
          </div>
          <div v-else class="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">
            No goods receipt evaluations have been submitted for this supplier.
          </div>
        </template>
      </Card>

      <div class="grid grid-cols-1 gap-4">
        <Card>
          <template #header>
            <div class="px-4 pt-4 pb-2 flex items-center justify-between">
              <div class="text-sm font-semibold text-slate-900">Recent Purchase Orders</div>
              <Tag :value="`${purchaseOrders.length}`" severity="secondary" />
            </div>
          </template>
          <template #content>
            <div v-if="purchaseOrders.length" class="space-y-2">
              <div v-for="po in purchaseOrders" :key="po.id"
                class="rounded-lg border border-slate-200 px-3 py-2 text-sm flex items-center justify-between gap-3">
                <div>
                  <div class="font-semibold text-slate-900">{{ po.po_number || `PO #${po.id}` }}</div>
                  <div class="text-xs text-slate-500">Order Date: {{ formatDate(po.order_date || po.created_at) }}</div>
                </div>
                <div class="justify-end">
                  <Tag :value="humanize(po.status)" :severity="poStatusSeverity(po.status)" />
                  <div class="text-xs text-slate-600 mt-1">PHP {{ formatCurrency(po.total_amount) }}</div>
                </div>
                <Button icon="pi pi-eye" text rounded size="small" v-tooltip="'View purchase order'"
                  @click="viewPurchaseOrder(po)" />
              </div>
            </div>
            <div v-else class="text-xs text-slate-500">No purchase orders yet.</div>
          </template>
        </Card>
  
      </div>
  
  
    </template>
  
    <Dialog v-model:visible="contractsDialogVisible" modal header="Supplier Contracts" class="w-full max-w-4xl">
      <div v-if="activeContractsCount === 0" class="mb-4 flex flex-col items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-center sm:flex-row sm:text-left">
        <div>
          <p class="font-semibold text-amber-900">No active contract with this supplier</p>
          <p class="mt-1 text-sm text-amber-800">Create a contract to enable this supplier for procurement.</p>
        </div>
        <Button label="Create Contract" icon="pi pi-plus" severity="warning" @click="createContract" />
      </div>
      <div v-if="contracts.length" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div v-for="contract in contracts" :key="contract.id"
          class="rounded-xl border border-slate-200 p-4 bg-slate-50/50">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="font-semibold text-slate-900">{{ contract.contract_title || 'Untitled Contract' }}</p>
              <p class="text-xs text-slate-500 mt-1">{{ contract.contract_number || `Contract #${contract.id}` }}</p>
            </div>
            <Tag :value="humanize(contract.status || 'draft')"
              :severity="contractStatusSeverity(contract.status || 'draft')" />
          </div>
          <div class="mt-4 grid grid-cols-2 gap-3 text-xs text-slate-700">
            <span>Type: {{ humanize(contract.contract_type) }}</span>
            <span>Discount: {{ contract.discount_percentage || 0 }}%</span>
            <span>Start: {{ formatDate(contract.start_date) }}</span>
            <span>End: {{ formatDate(contract.end_date) }}</span>
          </div>
          <div v-if="contract.status === 'rejected'" class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800">
            <p class="font-semibold">Rejection details</p>
            <p class="mt-1 whitespace-pre-line">{{ contract.rejection_reason || 'No reason provided.' }}</p>
            <p v-if="contract.rejected_by || contract.rejected_at" class="mt-2 text-rose-700">
              {{ contract.rejected_by ? `By ${[contract.rejected_by.fname, contract.rejected_by.lname].filter(Boolean).join(' ')}` : 'Reviewer not recorded' }}
              <span v-if="contract.rejected_at"> · {{ formatDateTime(contract.rejected_at) }}</span>
            </p>
          </div>
          <div class="mt-4 flex justify-end">
            <Button label="View Contract" icon="pi pi-eye" size="small" outlined @click="viewContract(contract)" />
          </div>
        </div>
      </div>
      <div v-else class="py-8 text-center text-sm text-slate-500">No contracts available.</div>
    </Dialog>
  
    <Dialog v-model:visible="purchaseOrderDialogVisible" modal header="Purchase Order Details" class="w-full max-w-5xl">
      <div v-if="purchaseOrderLoading" class="flex justify-center py-10">
        <ProgressSpinner />
      </div>
      <div v-else-if="selectedPurchaseOrder" class="space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 pb-4">
          <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Purchase Order</p>
            <h3 class="text-xl font-semibold text-slate-900">{{ selectedPurchaseOrder.po_number || `PO
              #${selectedPurchaseOrder.id}` }}</h3>
            <p class="text-sm text-slate-500">Created {{ formatDateTime(selectedPurchaseOrder.created_at) }}</p>
          </div>
          <Tag :value="humanize(selectedPurchaseOrder.status)"
            :severity="poStatusSeverity(selectedPurchaseOrder.status)" />
        </div>
  
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <InfoRow label="Supplier" :value="selectedPurchaseOrder.supplier?.supplier_name || '-'" />
          <InfoRow label="Order Date" :value="formatDate(selectedPurchaseOrder.order_date)" />
          <InfoRow label="Expected Delivery" :value="formatDate(selectedPurchaseOrder.expected_delivery_date)" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Supplier Information</p>
            <p class="mt-1 font-semibold text-slate-900">{{ selectedPurchaseOrder.supplier?.supplier_name || '-' }}</p>
            <p class="text-sm text-slate-600">{{ selectedPurchaseOrder.supplier?.email || '-' }}</p>
            <p class="text-sm text-slate-600">{{ selectedPurchaseOrder.supplier?.phone || '-' }}</p>
            <p class="text-sm text-slate-600">{{ selectedPurchaseOrder.supplier?.address || '-' }}</p>
          </div>
          <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Delivery Information</p>
            <p class="mt-1 font-semibold text-slate-900">{{ selectedPurchaseOrder.branch?.name || '-' }}</p>
            <p class="text-sm text-slate-600">{{ selectedPurchaseOrder.branch?.address || 'No address provided' }}</p>
            <p class="text-sm text-slate-600">Created {{ formatDateTime(selectedPurchaseOrder.created_at) }}</p>
          </div>
        </div>
  
        <div>
          <h4 class="mb-3 text-sm font-semibold text-slate-900">Order Items</h4>
          <div v-if="selectedPurchaseOrder.items?.length" class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="w-full text-sm">
              <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                  <th class="px-3 py-2">Product</th>
                  <th class="px-3 py-2 text-right">Quantity</th>
                  <th class="px-3 py-2 text-right">Unit Price</th>
                  <th class="px-3 py-2 text-right">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in selectedPurchaseOrder.items" :key="item.id" class="border-t border-slate-100">
                  <td class="px-3 py-3 text-slate-900">
                    <p>{{ item.product?.product_name || item.product_name || '-' }}</p>
                    <p v-if="item.variation" class="mt-1 text-xs text-slate-500">
                      Variant: {{ item.variation.variation_name || item.variation.name || `Variant #${item.variation_id}` }}
                    </p>
                  </td>
                  <td class="px-3 py-3 text-right text-slate-700">{{ item.quantity_ordered ?? item.quantity ?? 0 }}</td>
                  <td class="px-3 py-3 text-right text-slate-700">PHP {{ formatCurrency(item.unit_cost ?? item.unit_price)
                    }}</td>
                  <td class="px-3 py-3 text-right font-medium text-slate-900">PHP {{ formatCurrency(item.line_total ??
                    item.total_price ?? item.total_amount) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="rounded-lg border border-dashed border-slate-300 px-3 py-6 text-center text-sm text-slate-500">
            No order items available.</p>
        </div>
  
        <div class="ml-auto max-w-xs space-y-2 border-t border-slate-200 pt-3 text-sm">
          <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span>PHP {{
              formatCurrency(selectedPurchaseOrder.subtotal) }}</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Tax</span><span>PHP {{
              formatCurrency(selectedPurchaseOrder.tax_amount) }}</span></div>
          <div class="flex justify-between text-base font-semibold"><span>Total</span><span>PHP {{
              formatCurrency(selectedPurchaseOrder.total_amount) }}</span></div>
        </div>

        <div v-if="selectedPurchaseOrder.notes || selectedPurchaseOrder.terms_conditions" class="space-y-3">
          <div v-if="selectedPurchaseOrder.notes" class="rounded-lg border border-slate-200 p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Notes</p>
            <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700">{{ selectedPurchaseOrder.notes }}</p>
          </div>
          <div v-if="selectedPurchaseOrder.terms_conditions" class="rounded-lg border border-slate-200 p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Terms & Conditions</p>
            <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700">{{ selectedPurchaseOrder.terms_conditions }}</p>
          </div>
        </div>

        <div v-if="selectedPurchaseOrder.activity_logs?.length" class="rounded-lg border border-slate-200 overflow-hidden">
          <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 flex items-center gap-2">
            <i class="pi pi-history text-slate-500"></i><h4 class="font-medium text-slate-700">Activity Timeline</h4>
          </div>
          <div class="divide-y divide-slate-100">
            <div v-for="activity in selectedPurchaseOrder.activity_logs" :key="activity.id" class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ humanize(activity.action || activity.event_type || activity.type) }}</p>
              <p class="text-sm text-slate-600">{{ activity.description || activity.message || '-' }}</p>
              <p class="text-xs text-slate-500 mt-1">{{ formatDateTime(activity.created_at) }}</p>
            </div>
          </div>
        </div>

        <div v-if="selectedPurchaseOrder.goods_receipts?.length" class="rounded-lg border border-slate-200 overflow-hidden">
          <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 flex items-center gap-2">
            <i class="pi pi-inbox text-slate-500"></i><h4 class="font-medium text-slate-700">Goods Receipts</h4>
          </div>
          <div class="divide-y divide-slate-100">
            <div v-for="receipt in selectedPurchaseOrder.goods_receipts" :key="receipt.id" class="px-4 py-3 flex items-center justify-between gap-3">
              <div><p class="font-medium text-slate-900">{{ receipt.grn_number || receipt.gr_number || `Receipt #${receipt.id}` }}</p><p class="text-xs text-slate-500">{{ formatDate(receipt.receipt_date || receipt.received_date) }}</p></div>
              <Tag :value="humanize(receipt.receipt_status || receipt.status || 'received')" :severity="receiptStatusSeverity(receipt.receipt_status || receipt.status)" />
            </div>
          </div>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const supplierId = Number(route.params.id)

const loading = ref(false)
const supplier = ref<any>(null)
const contracts = ref<any[]>([])
const performance = ref<any>(null)
const purchaseOrders = ref<any[]>([])
const contractsDialogVisible = ref(false)
const purchaseOrderDialogVisible = ref(false)
const purchaseOrderLoading = ref(false)
const selectedPurchaseOrder = ref<any>(null)

const InfoRow = defineComponent({
  name: 'InfoRow',
  props: {
    label: { type: String, required: true },
    value: { type: [String, Number], default: '-' },
  },
  setup(props) {
    return () =>
      h('div', { class: 'rounded-md border border-slate-200 px-2 py-1.5 bg-white' }, [
        h('p', { class: 'text-[11px] uppercase tracking-wide text-slate-500' }, props.label),
        h('p', { class: 'text-sm font-medium text-slate-900 truncate' }, String(props.value ?? '-')),
      ])
  },
})

const supplierAddress = computed(() => {
  if (!supplier.value) return '-'
  return [supplier.value.address, supplier.value.city, supplier.value.province, supplier.value.country]
    .filter(Boolean)
    .join(', ') || '-'
})

const linkedStoreName = computed(() => {
  if (!supplier.value) return '-'
  return supplier.value.store?.name || supplier.value.store?.store_name || '-'
})

const activeContractsCount = computed(() => {
  if (supplier.value?.active_contracts_count !== undefined && supplier.value?.active_contracts_count !== null) {
    return Number(supplier.value.active_contracts_count)
  }
  if (performance.value?.active_contracts !== undefined && performance.value?.active_contracts !== null) {
    return performance.value.active_contracts
  }
  return contracts.value.filter((c: any) => c?.status === 'active').length
})

const productsCount = computed(() => {
  if (!supplier.value) return 0
  return Array.isArray(supplier.value.products) ? supplier.value.products.length : 0
})

const performanceCards = computed(() => [
  { label: 'On-Time Delivery', value: `${performance.value?.on_time_delivery_rate ?? 0}%`, icon: 'pi pi-check-circle', iconColor: 'text-green-600' },
  { label: 'Total Spend', value: `PHP ${formatCurrency(performance.value?.total_amount_purchased)}`, icon: 'pi pi-wallet', iconColor: 'text-blue-600' },
  { label: 'Average Order', value: `PHP ${formatCurrency(performance.value?.average_order_value)}`, icon: 'pi pi-chart-line', iconColor: 'text-purple-600' },
  { label: 'Late Deliveries', value: performance.value?.late_deliveries ?? 0, icon: 'pi pi-clock', iconColor: 'text-orange-600' },
  { label: 'Current Balance', value: `PHP ${formatCurrency(performance.value?.current_balance)}`, icon: 'pi pi-credit-card', iconColor: 'text-red-600' },
  { label: 'Credit Available', value: `PHP ${formatCurrency(performance.value?.credit_available)}`, icon: 'pi pi-dollar', iconColor: 'text-teal-600' },
])

const evaluationCards = computed(() => {
  const averages = performance.value?.evaluation_averages || {}
  return [
    { label: 'Overall', value: formatRating(averages.overall) },
    { label: 'Item Quality', value: formatRating(averages.quality) },
    { label: 'Quantity Accuracy', value: formatRating(averages.quantity_accuracy) },
    { label: 'Delivery Timeliness', value: formatRating(averages.delivery_timeliness) },
    { label: 'Packaging & Condition', value: formatRating(averages.packaging_condition) },
  ]
})

const loadSupplierData = async () => {
  loading.value = true
  try {
    const supplierResponse = await procurementService.getSupplier(supplierId)
    const supplierPayload = supplierResponse?.data ?? supplierResponse ?? null
    supplier.value = supplierPayload
    contracts.value = Array.isArray(supplierPayload?.contracts) ? supplierPayload.contracts : []
    purchaseOrders.value = Array.isArray(supplierPayload?.purchase_orders) ? supplierPayload.purchase_orders : []

    try {
      const performanceResponse = await procurementService.getSupplierPerformance(supplierId)
      performance.value = performanceResponse?.data ?? performanceResponse ?? null
    } catch (error) {
      console.warn('Failed to load performance metrics', error)
      performance.value = null
    }
  } catch (error) {
    console.error('Failed to load supplier details', error)
    supplier.value = null
    contracts.value = []
    purchaseOrders.value = []
    performance.value = null
  } finally {
    loading.value = false
  }
}

const contractStatusSeverity = (status: string) => {
  if (status === 'active') return 'success'
  if (status === 'terminated' || status === 'rejected') return 'danger'
  if (status === 'draft' || status === 'pending' || status === 'expiring') return 'warn'
  return 'secondary'
}

const poStatusSeverity = (status: string) => {
  if (['delivered'].includes(status)) return 'success'
  if (['in_transit', 'supplier_accepted', 'sent_to_supplier'].includes(status)) return 'info'
  if (['pending_finance_approval', 'approved'].includes(status)) return 'warning'
  if (['rejected_finance', 'declined_supplier', 'cancelled'].includes(status)) return 'danger'
  return 'secondary'
}

const receiptStatusSeverity = (status?: string) => {
  if (['completed', 'complete', 'received', 'approved'].includes(status || '')) return 'success'
  if (['partial', 'damaged', 'pending'].includes(status || '')) return 'warning'
  if (['rejected', 'cancelled'].includes(status || '')) return 'danger'
  return 'secondary'
}

const openContractsDialog = () => {
  contractsDialogVisible.value = true
}

const viewContract = (contract: any) => {
  contractsDialogVisible.value = false
  router.push({ name: 'procurement.supplier-contracts.detail', params: { id: contract.id } })
}

const createContract = () => {
  contractsDialogVisible.value = false
  router.push({ name: 'procurement.supplier-contracts.create', query: { supplier_id: supplierId, supplier_name: supplier.value?.supplier_name || '' } })
}

const viewPurchaseOrder = async (purchaseOrder: any) => {
  selectedPurchaseOrder.value = null
  purchaseOrderDialogVisible.value = true
  purchaseOrderLoading.value = true
  try {
    const response = await procurementService.getPurchaseOrder(purchaseOrder.id)
    selectedPurchaseOrder.value = response?.data ?? response ?? purchaseOrder
  } catch (error: any) {
    selectedPurchaseOrder.value = purchaseOrder
    toast.add({ severity: 'error', summary: 'Unable to load purchase order', detail: error?.response?.data?.message || 'Showing the available order summary.', life: 4000 })
  } finally {
    purchaseOrderLoading.value = false
  }
}

const viewGoodsReceipt = (id: number) => {
  router.push({ name: 'inventory.goods-receipts.detail', params: { id } })
}

const evaluatorName = (evaluator: any): string => {
  if (!evaluator) return 'Inventory staff'
  return [evaluator.fname || evaluator.first_name, evaluator.lname || evaluator.last_name].filter(Boolean).join(' ') || 'Inventory staff'
}

const formatRating = (value: any): string => value === null || value === undefined
  ? '0.00'
  : Number(value).toFixed(2)

const humanize = (value: string | null | undefined): string => {
  if (!value) return '-'
  return value.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

const formatCurrency = (value: any): string => {
  if (!value) return '0.00'
  return Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatDate = (dateString: string): string => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatDateTime = (dateString: string): string => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  if (Number.isNaN(date.getTime())) return 'N/A'
  return date.toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

onMounted(() => {
  loadSupplierData()
})
</script>
