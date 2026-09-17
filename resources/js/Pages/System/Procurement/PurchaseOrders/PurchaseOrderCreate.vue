<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 pb-8 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="flex items-center gap-3 px-5 py-4 shadow-sm">
      <Button icon="pi pi-arrow-left" text rounded severity="secondary" aria-label="Back to purchase orders" @click="router.push({ name: 'procurement.purchase-orders' })" />
      <div class="min-w-0">
        <h2 class="text-2xl font-bold text-slate-900">{{ isEditing ? 'Edit' : 'Create' }} Purchase Order</h2>
        <p class="mt-1 text-sm text-slate-500">Review the assigned supplier, destination branch, items, and totals before submission.</p>
      </div>
    </div>
  
    <!-- Alert for supplier issues -->
    <Toast />
    <!-- <Message v-if="supplierWarning.show" :severity="supplierWarning.severity" :text="supplierWarning.message"/> -->
  
    <!-- Main Form -->
    <Card class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <template #content>
        <form class="space-y-8" @submit.prevent="openSplitPoPreview('submit')">
          <!-- Order context is determined by the source PR/RFQ. -->
          <section class="border-b border-slate-200 pb-7">
            <div class="mb-4 flex items-center justify-between gap-3">
              <div>
                <h3 class="text-lg font-semibold text-slate-900">Order Information</h3>
                <p class="mt-1 text-sm text-slate-500">Supplier and branch are assigned from the approved procurement request.</p>
              </div>
              <Tag v-if="form.purchase_requisition_id" value="From Purchase Requisition" severity="info" />
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
              <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Supplier</p>
                <p class="mt-1 font-semibold text-slate-900">{{ selectedSupplierName }}</p>
                <p v-if="splitPoMode" class="mt-1 text-xs text-blue-600">Separate PO will be created for each supplier.</p>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Destination Branch</p>
                <p class="mt-1 font-semibold text-slate-900">{{ selectedBranchName }}</p>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Currency</p>
                <p class="mt-1 font-semibold text-slate-900">{{ storeCurrency }}</p>
              </div>
            </div>
          </section>

          <!-- Supplier Information -->
          <section class="border-b border-slate-200 pb-7">
            <div class="mb-4">
              <h3 class="text-lg font-semibold text-slate-900">Supplier Information</h3>
              <p class="mt-1 text-sm text-slate-500">Contact and contract terms applied to this purchase order.</p>
            </div>
  
            <!-- Supplier Details Card (Auto-populated) -->
            <div v-if="selectedSupplier" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
              <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-5">
                <div>
                  <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Contact Person</p>
                  <p class="mt-1 font-medium text-slate-900">{{ selectedSupplier.contact_person || '-' }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Email</p>
                  <p class="mt-1 break-all font-medium text-slate-900">{{ selectedSupplier.email || '-' }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Phone</p>
                  <p class="mt-1 font-medium text-slate-900">{{ selectedSupplier.phone || '-' }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Contract Discount</p>
                  <p class="mt-1 font-medium text-slate-900">
                    {{ contractDiscountDisplay }}
                  </p>
                </div>
                <div>
                  <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Tax Rate</p>
                  <p class="mt-1 font-medium text-slate-900">
                    {{ supplierTaxRateDisplay }} <span v-if="selectedContract?.is_tax_exempt" class="text-xs text-slate-500">(Exempt)</span>
                  </p>
                </div>
              </div>
            </div>
  
            <div v-else class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm text-slate-500">
              {{ splitPoMode ? 'Supplier details will be applied separately to each generated purchase order.' : 'Supplier details are being loaded from the source request.' }}
            </div>
          </section>
  
          <!-- Section 3: Line Items -->
          <section class="border-b border-slate-200 pb-7">
            <div class="mb-4 flex items-start justify-between gap-4">
              <div>
                <h3 class="text-lg font-semibold text-slate-900">Purchase Items</h3>
                <p class="mt-1 text-sm text-slate-500">Verify the ordered quantity and supplier-quoted cost for each item.</p>
              </div>
              <Button label="Add Item" icon="pi pi-plus" size="small" @click="addLineItem"/>
            </div>
  
           
  
            <!-- Line Items Table -->
            <Transition name="slide-fade" mode="out-in">
              <div v-if="form.items.length > 0" class="space-y-4">
                <TransitionGroup name="list" tag="div" class="space-y-4">
                  <div
                    v-for="(item, index) in form.items"
                    :key="item.id || index"
                    class="rounded-xl border border-slate-200 bg-slate-50/50 p-4"
                  >
                    <div class="flex items-center justify-between mb-4">
                      <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Item {{ index + 1 }}</div>
                      <Button
                        icon="pi pi-trash"
                        text
                        severity="danger"
                        size="small"
                        @click="removeLineItem(index)"
                        v-tooltip="'Remove item'"
                      />
                    </div>

                    <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-12">
                      <div class="lg:col-span-4">
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wide block mb-1">Product</label>
                        <Select
                          :model-value="item.product_id"
                          :options="products"
                          option-label="product_name"
                          option-value="id"
                          placeholder="Select product..."
                          class="w-full"
                          filter fluid
                          @update:model-value="(value) => onProductChange(index, value)"
                        />
                      </div>

                      <div class="lg:col-span-2">
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wide block mb-1">Qty</label>
                        <InputNumber
                          v-model="item.quantity_ordered"
                          :min="1"
                          @input="calculateItemTotal(index)"
                          class="w-full text-center"
                          fluid
                        />
                        <p v-if="budgetWarnings[index]" class="text-xs text-red-500 mt-1">
                          Warning: {{ budgetWarnings[index] }}
                        </p>
                      </div>
                      <div class="lg:col-span-1">
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wide block mb-1">Unit</label>
                        <p class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-700">
                          {{ getItemUnitOfMeasurement(item) }}
                        </p>
                      </div>

                      <div class="lg:col-span-2">
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wide block mb-1">Unit Price</label>
                        <InputNumber
                          v-model="item.unit_cost"
                          :min="0"
                          mode="currency"
                          currency="PHP"
                          fluid
                          disabled
                          @input="calculateItemTotal(index)"
                          class="w-full text-right"
                        />
                      </div>

                      <div class="lg:col-span-3">
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                          <label class="text-xs font-medium text-gray-500 uppercase tracking-wide block mb-1">Line Total</label>
                          <div class="text-lg font-semibold text-slate-900">
                            {{ formatCurrency(Number(item.line_total) || 0) }}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </TransitionGroup>
              </div>
              <div v-else class="rounded-xl border border-dashed border-slate-300 bg-slate-50 py-10 text-center">
                <i class="pi pi-inbox mb-2 text-3xl text-slate-400"></i>
                <p class="text-slate-500">No items added yet. Click "Add Item" to start.</p>
              </div>
            </Transition>
          </section>
  
          <!-- Section 4: Running Totals -->
           
           <div class="grid grid-cols-2 items-center gap-2">
               <!-- Section 5: Notes -->
          <section>
            <label class="mb-2 block text-sm font-semibold text-slate-900">Notes / Special Instructions</label>
            <p class="mb-3 text-sm text-slate-500">Add delivery instructions, packaging requirements, or other supplier notes.</p>
            <Textarea v-model="form.notes" rows="4" auto-resize class="w-full"
              placeholder="Add any special instructions or notes for this purchase order..." />
          </section>
              <div class="flex justify-end">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
              <div class="space-y-3 text-sm">
                <div class="flex items-center justify-between gap-6 text-slate-600">
                  <span>Subtotal</span>
                  <span class="font-medium text-slate-900">{{ formatCurrency(totals.subtotal) }}</span>
                </div>
                <div class="flex items-center justify-between gap-6 text-slate-600">
                  <span>Discount Amount</span>
                  <span class="font-medium text-slate-900">- {{ formatCurrency(form.discount_amount) }}</span>
                </div>
                <div class="flex items-center justify-between gap-6 text-slate-600">
                  <span>Taxable Amount</span>
                  <span class="font-medium text-slate-900">{{ formatCurrency(Math.max(0, totals.subtotal - form.discount_amount)) }}</span>
                </div>
                <div class="flex items-center justify-between gap-6 text-slate-600">
                  <span>Tax ({{ supplierTaxRateDisplay }})</span>
                  <span class="font-medium text-slate-900">{{ formatCurrency(totals.tax_amount) }}</span>
                </div>
                <template v-if="shippingEstimate?.breakdown">
                  <div class="flex items-center justify-between gap-6 text-slate-600"><span>Supplier route</span><span class="font-medium text-slate-900">{{ shippingEstimate.breakdown.distance_km }} km</span></div>
                  <div class="flex items-center justify-between gap-6 text-slate-600"><span>Base / distance / weight</span><span class="font-medium text-slate-900">{{ formatCurrency(shippingEstimate.breakdown.base_fee) }} / {{ formatCurrency(shippingEstimate.breakdown.distance_fee) }} / {{ formatCurrency(shippingEstimate.breakdown.weight_fee) }}</span></div>
                  <div v-if="shippingEstimate.breakdown.bulky_item_surcharge || shippingEstimate.breakdown.remote_area_surcharge" class="flex items-center justify-between gap-6 text-slate-600"><span>Surcharges</span><span class="font-medium text-slate-900">{{ formatCurrency((shippingEstimate.breakdown.bulky_item_surcharge || 0) + (shippingEstimate.breakdown.remote_area_surcharge || 0)) }}</span></div>
                </template>
                <div class="flex items-center justify-between gap-6 text-slate-600"><span>Shipping Fee</span><span class="font-medium text-slate-900">{{ shippingEstimateLoading ? 'Calculating…' : formatCurrency(shippingFee) }}</span></div>
                <p v-if="shippingEstimateError" class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">{{ shippingEstimateError }}</p>
              </div>
              <div class="my-4 border-t border-slate-200"></div>
              <div class="flex items-center justify-between gap-6 rounded-xl bg-slate-900 px-4 py-3 text-white">
                <span class="font-semibold">Total Amount</span>
                <span class="text-xl font-bold">{{ formatCurrency(totals.total_amount) }}</span>
              </div>
            </div>
          </div>
  
           </div>
        
       
  
          <!-- Section 6: Action Buttons -->
          <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-end">
            <Button label="Cancel" severity="secondary" text type="button"
              @click="router.push({ name: 'procurement.purchase-orders' })" />
            <Button label="Save as Draft" severity="secondary" :loading="saving"
              @click="openSplitPoPreview('draft')" />
            <Button label="Create & Submit" type="submit" :loading="saving" />
          </div>
        </form>
      </template>
    </Card>

    <Dialog v-model:visible="splitPoPreviewVisible" modal header="Confirm Split Purchase Orders" :style="{ width: '42rem' }">
      <div class="space-y-4">
        <p class="text-sm text-gray-700">
          This PR contains multiple supplier groups. The system will create separate purchase orders per supplier.
        </p>

        <div class="space-y-3 max-h-72 overflow-y-auto">
          <div v-for="group in splitPoSummary" :key="group.supplier_id" class="border rounded-lg p-3 bg-gray-50">
            <div class="flex items-center justify-between mb-2">
              <p class="font-semibold text-gray-900">{{ group.supplier_name }}</p>
              <p class="text-xs text-gray-600">{{ group.item_count }} items • Qty {{ group.total_qty }}</p>
            </div>
            <ul class="text-xs text-gray-700 space-y-1">
              <li v-for="line in group.lines" :key="line.key">{{ line.product_name }} • Qty {{ line.qty }}</li>
            </ul>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-3">
          <Button label="No" severity="secondary" text @click="splitPoPreviewVisible = false" />
          <Button
            :label="splitPoPreviewAction === 'draft' ? 'Confirm' : 'Confirm'"
            icon="pi pi-check"
            severity="success"
            @click="confirmSplitPoPreview"
          />
        </div>
      </template>
    </Dialog>

    <Dialog v-model:visible="submitConfirmationVisible" modal header="Submit Purchase Order" :style="{ width: '26rem' }">
      <p class="text-sm text-slate-600">Submit this purchase order for finance approval?</p>
      <template #footer>
        <div class="flex justify-end gap-3">
          <Button label="No" severity="secondary" text @click="submitConfirmationVisible = false" />
          <Button label="Confirm" icon="pi pi-check" :loading="saving" @click="confirmPurchaseOrderSubmission" />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted, watch, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'
import { usePoAutomation } from '../../../../composables/procurement/usePoAutomation'

const router = useRouter()
const route = useRoute()

const toast = useToast()

const {
  autoFillSupplierDetails,
  generatePoNumber,
  validateQuantityAgainstBudget,
  checkSupplierStatus,
  getFrequentlyPurchasedProducts,
  formatCurrency
} = usePoAutomation()

// Form State
  const form = reactive({
    supplier_id: null as number | null,
    branch_id: null as number | null,
    purchase_requisition_id: null as number | null,
    order_date: new Date(),
  discount_amount: 0,
  notes: '',
  items: [] as any[]
})

// UI State
const saving = ref(false)
const saveDraft = ref(false)
const isEditing = ref(false)
const loadingSuppliers = ref(false)
const suppliers = ref<any[]>([])
const products = ref<any[]>([])
const branches = ref<any[]>([])
const frequentProducts = ref<any[]>([])
const selectedSupplier = ref<any>(null)
const selectedContract = ref<any>(null)
const contractDiscountPercent = ref(0)
const contractTaxRate = ref(0)
const storeCurrency = ref('PHP')
const budgetWarnings = ref<Record<number, string>>({})
const supplierWarning = reactive({ show: false, message: '', severity: 'warning' as string })
const splitPoMode = ref(false)
const splitPoSupplierGroups = ref<number>(0)
const splitPoPreviewVisible = ref(false)
const splitPoPreviewAction = ref<'draft' | 'submit'>('submit')
const submitConfirmationVisible = ref(false)

const totals = reactive({
  subtotal: 0,
  tax_amount: 0,
  total_amount: 0
})
const shippingEstimate = ref<any>(null)
const shippingEstimateLoading = ref(false)
const shippingEstimateError = ref('')
const shippingFee = computed(() => Number(shippingEstimate.value?.shipping_fee || 0))
let shippingEstimateTimer: ReturnType<typeof setTimeout> | null = null

const supplierTaxRate = computed(() =>
  selectedContract.value?.is_tax_exempt ? 0 : 12
)
const supplierTaxRateDisplay = computed(() => `${Number(supplierTaxRate.value || 0).toFixed(2)}%`)
const contractDiscountDisplay = computed(() => `${Number(contractDiscountPercent.value || 0).toFixed(2)}%`)
const selectedBranchName = computed(() => {
  const branch = branches.value.find((item: any) => Number(item.id) === Number(form.branch_id))
  return branch?.name || branch?.branch_name || (form.branch_id ? `Branch #${form.branch_id}` : 'Not assigned')
})
const selectedSupplierName = computed(() => {
  if (splitPoMode.value) {
    return `${splitPoSupplierGroups.value} assigned suppliers`
  }

  const supplier = selectedSupplier.value
    || suppliers.value.find((item: any) => Number(item.id) === Number(form.supplier_id))

  return supplier?.supplier_name
    || supplier?.company_name
    || (loadingSuppliers.value ? 'Loading supplier...' : form.supplier_id ? `Supplier #${form.supplier_id}` : 'Not assigned')
})
const splitPoSummary = computed(() => {
  const groups = new Map<number, {
    supplier_id: number
    supplier_name: string
    item_count: number
    total_qty: number
    lines: Array<{ key: string; product_name: string; qty: number }>
  }>()

  for (const item of form.items) {
    const supplierId = Number(item?.selected_supplier_id || 0)
    if (!supplierId) continue

    const supplierName = item?.selected_supplier_name
      || suppliers.value.find((s: any) => Number(s.id) === supplierId)?.supplier_name
      || `Supplier #${supplierId}`

    if (!groups.has(supplierId)) {
      groups.set(supplierId, {
        supplier_id: supplierId,
        supplier_name: supplierName,
        item_count: 0,
        total_qty: 0,
        lines: [],
      })
    }

    const group = groups.get(supplierId)!
    const qty = Number(item?.quantity_ordered || 0)
    group.item_count += 1
    group.total_qty += qty
    group.lines.push({
      key: `${supplierId}-${item?.product_id}-${group.lines.length}`,
      product_name: item?.product_name || `Product #${item?.product_id}`,
      qty,
    })
  }

  return Array.from(groups.values())
})

// Load initial data
onMounted(async () => {
  await loadInitialData()
  // Check if editing an existing PO
  const poId = route.params.id
  if (poId) {
    await loadPOForEdit(Number(poId))
    return
  }

  if (route.query.requisition_id) {
    const requisitionId = parseInt(route.query.requisition_id as string)
    await prefillFromRequisition(requisitionId)
    // If split mode requested via query, fetch canonical server grouping and show preview
    if (route.query.split === '1') {
      try {
        const splitRes = await procurementService.getPurchaseRequisitionSplitPreview(requisitionId)
        const payload = splitRes?.data || splitRes
        if (payload) {
          // Populate form items with server-provided items (keep existing mapping)
          if (Array.isArray(payload.requisition?.items)) {
            form.items = payload.requisition.items.map((item: any) => ({
              id: `req-${item.id}`,
              product_id: item.product_id,
              product_name: item.product?.product_name || item.product_name || '',
              unit_of_measurement: item.product?.unit_of_measurement || item.unit_of_measurement || item.unit || '',
              selected_supplier_id: item.selected_supplier_id || null,
              selected_supplier_name: item.selected_supplier_id ? (item.selected_supplier_name || null) : null,
              quantity_ordered: item.quantity_requested || 1,
              unit_cost: parseFloat(item.product?.cost_price || item.estimated_unit_cost || item.product?.base_price || '0') || 0,
              line_total: 0
            }))

            form.items.forEach((_, index) => calculateItemTotal(index))
          }

          // Use server grouping to set split mode and supplier group count
          if (Array.isArray(payload.supplier_groups)) {
            splitPoSupplierGroups.value = payload.supplier_groups.length
            splitPoMode.value = payload.supplier_groups.length > 1
          }

          // Open preview dialog
          if (splitPoMode.value) {
            splitPoPreviewVisible.value = true
            splitPoPreviewAction.value = 'submit'
          }
        }
      } catch (err) {
        console.error('Failed to load split preview', err)
      }
    }
    return
  }

  if (route.query.rfq_id) {
    const rfqId = parseInt(route.query.rfq_id as string)
    await prefillFromRFQ(rfqId)
  }
})

const loadInitialData = async () => {
  try {
    loadingSuppliers.value = true
    const [suppliersRes, branchesRes] = await Promise.all([
      procurementService.getSuppliers({ per_page: 100, active_contract_only: true }),
      procurementService.getBranches ? procurementService.getBranches() : Promise.resolve({ data: [] })
    ])

    suppliers.value = suppliersRes.data?.data || suppliersRes.data || []
    const branchData = branchesRes.data?.data || branchesRes.data || []
    branches.value = branchData.map((branch: any) => ({
      ...branch,
      name: branch.name || branch.branch_name || branch.branch || branch.code || `Branch ${branch.id}`
    }))
    products.value = [] // Will be populated from supplier dropdown

    // Set default branch if only one exists
    if (branches.value.length === 1) {
      form.branch_id = branches.value[0].id
      await loadProductsByBranch(form.branch_id)
    }

    // Load frequently purchased products
    const frequent = await getFrequentlyPurchasedProducts()
    frequentProducts.value = frequent.slice(0, 5)
  } catch (error) {
    console.error('Failed to load initial data', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load initial data',
      life: 3000
    })
  } finally {
    loadingSuppliers.value = false
  }
}

const syncItemUnitCostsFromProducts = () => {
  if (!Array.isArray(form.items) || form.items.length === 0) return
  if (!Array.isArray(products.value) || products.value.length === 0) return

  form.items.forEach((item) => {
    if (!item || !item.product_id) return
    const product = products.value.find((p) => Number(p.id) === Number(item.product_id))
    if (!product) return
    const current = Number(item.unit_cost || 0)
    if (current > 0) return
    const nextCost = Number(product.cost_price ?? 0) || 0
    item.unit_cost = nextCost
  })
}

const prefillFromRequisition = async (requisitionId: number) => {
  try {
      const requisitionRes = await procurementService.getPurchaseRequisition(requisitionId)
      const requisition = requisitionRes?.data || requisitionRes?.data?.data || requisitionRes

      if (!requisition) return

      form.purchase_requisition_id = requisition.id || null

    form.branch_id = requisition.branch_id || requisition.branch?.id || form.branch_id
    form.notes = requisition.reason || form.notes
    if (form.branch_id) {
      await loadProductsByBranch(form.branch_id)
    }

    if (Array.isArray(requisition.items)) {
      form.items = requisition.items.map((item: any) => ({
        id: `req-${item.id}`,
        product_id: item.product_id,
        product_name: item.product?.product_name || item.product_name || '',
        unit_of_measurement: item.product?.unit_of_measurement || item.unit_of_measurement || item.unit || '',
        selected_supplier_id: item.selected_supplier_id || null,
        selected_supplier_name: item.selected_supplier_id
          ? (Array.isArray(item.product?.suppliers)
            ? item.product.suppliers.find((s: any) => Number(s.id) === Number(item.selected_supplier_id))?.supplier_name
            : null)
          : null,
        quantity_ordered: item.quantity_requested || 1,
        unit_cost: parseFloat(item.product?.cost_price || item.estimated_unit_cost || item.product?.base_price || '0') || 0,
        line_total: 0
      }))

      form.items.forEach((_, index) => calculateItemTotal(index))
    }

    const resolvedSupplierIds = (Array.isArray(requisition.items) ? requisition.items : [])
      .map((item: any) => {
        if (item?.selected_supplier_id) return Number(item.selected_supplier_id)
        const productSuppliers = Array.isArray(item?.product?.suppliers) ? item.product.suppliers : []
        if (productSuppliers.length === 1) return Number(productSuppliers[0].id)
        return null
      })
      .filter((id: any) => Number(id) > 0)

    const uniqueSupplierIds = Array.from(new Set(resolvedSupplierIds))
    splitPoSupplierGroups.value = uniqueSupplierIds.length
    splitPoMode.value = uniqueSupplierIds.length > 1

    if (products.value.length === 0 && Array.isArray(requisition.items)) {
      products.value = requisition.items
        .filter((item: any) => item.product_id)
        .map((item: any) => ({
          id: item.product_id,
          product_name: item.product?.product_name || item.product_name || 'Unknown Product',
          sku: item.product?.sku || item.sku || '',
          stock_level: 0,
          cost_price: parseFloat(item.product?.cost_price || item.estimated_unit_cost || item.product?.base_price || '0') || 0
        }))
    }

    syncItemUnitCostsFromProducts()

    const firstSupplierId = requisition.items?.[0]?.selected_supplier_id || requisition.items?.[0]?.product?.suppliers?.[0]?.id
    if (!splitPoMode.value && firstSupplierId) {
      form.supplier_id = Number(firstSupplierId)
      await onSupplierChange()
    } else if (splitPoMode.value) {
      form.supplier_id = null
    }
  } catch (error) {
    console.error('Failed to prefill from requisition', error)
  }
}

const prefillFromRFQ = async (rfqId: number) => {
  try {
    const rfqRes = await procurementService.getRFQ(rfqId)
    const rfq = rfqRes?.data?.data || rfqRes?.data || rfqRes
    if (!rfq) return

    form.purchase_requisition_id = rfq.purchase_requisition_id || form.purchase_requisition_id

    form.notes = `From ${rfq.rfq_number || rfqId}`
    form.branch_id = rfq.purchase_requisition?.branch_id || rfq.branch_id || form.branch_id
    const approvedFeedbacks = Array.isArray(rfq.supplier_portal_feedbacks)
      ? rfq.supplier_portal_feedbacks.filter((f: any) => f.status === 'approved')
      : []
    const targetRfqItemId = Number(route.query.rfq_item_id || 0)
    const selectedApprovedFeedbacks = targetRfqItemId > 0
      ? approvedFeedbacks.filter((f: any) => Number(f.rfq_item_id || f.rfqItem?.id) === targetRfqItemId)
      : approvedFeedbacks

    const supplierIds = selectedApprovedFeedbacks
      .map((f: any) => f?.supplier_portal?.supplier_id || f?.supplier_portal?.supplier?.id)
      .filter((id: any) => !!id)

    const uniqueSupplierIds = Array.from(new Set(supplierIds)).map((id: any) => Number(id)).filter((id: number) => id > 0)
    if (rfq.awarded_to_supplier_id) {
      form.supplier_id = rfq.awarded_to_supplier_id
    } else if (uniqueSupplierIds.length === 1) {
      form.supplier_id = uniqueSupplierIds[0]
    } else if (uniqueSupplierIds.length > 1) {
      toast.add({
        severity: 'warn',
        summary: 'Multiple Suppliers',
        detail: 'RFQ has multiple approved suppliers. Please select one.',
        life: 4000
      })
    }

      const rfqItemsRaw = selectedApprovedFeedbacks.length > 0
        ? selectedApprovedFeedbacks.map((feedback: any) => ({
            ...feedback.rfq_item,
            id: feedback.rfq_item_id || feedback.rfq_item?.id,
            product: feedback.rfq_item?.product,
            quantity: feedback.rfq_item?.quantity,
            selected_supplier_id: feedback.supplier_portal?.supplier_id || feedback.supplier_portal?.supplier?.id,
            approved_unit_price: feedback.quoted_price,
            length_cm: feedback.length_cm,
            width_cm: feedback.width_cm,
            height_cm: feedback.height_cm,
            weight_kg: feedback.weight_kg,
          }))
        : []

      if (Array.isArray(rfqItemsRaw)) {
        form.items = rfqItemsRaw.map((item: any) => {
          const product = item.product || {}
          return {
            id: `rfq-item-${item.id || Date.now()}`,
            rfq_item_id: item.id || null,
            product_id: item.product_id || product.id || null,
            product_name: product.product_name || item.product_name || '',
            unit_of_measurement: product.unit_of_measurement || item.unit_of_measurement || item.unit || '',
            quantity_ordered: item.quantity || 1,
            unit_cost: Number(item.approved_unit_price || 0),
            selected_supplier_id: item.selected_supplier_id || null,
            line_total: 0,
            stock_level: 0
          }
        })
      } else {
        form.items = []
      }

      if (form.supplier_id) {
        await onSupplierChange()
      }

      if (products.value.length === 0) {
        products.value = form.items
          .filter(item => item.product_id)
          .map(item => ({
            id: item.product_id,
            product_name: item.product_name || 'Unknown Product',
            sku: item.sku || ''
          }))
      }

    syncItemUnitCostsFromProducts()

    form.items.forEach((_, index) => calculateItemTotal(index))
    updateTotals()
  } catch (error) {
    console.error('Failed to prefill from RFQ', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load RFQ data for PO creation',
      life: 3000
    })
  }
}

const loadPOForEdit = async (poId: number) => {
  try {
    isEditing.value = true
      const response = await procurementService.getPurchaseOrder(poId)
      const po = response.data

      form.purchase_requisition_id = po.purchase_requisition_id || null
    // Pre-fill form with existing PO data
    form.supplier_id = po.supplier_id
    form.branch_id = po.branch_id
    form.order_date = new Date(po.order_date)
    form.discount_amount = po.discount_amount ?? form.discount_amount
    form.discount_amount = po.discount_amount
    form.notes = po.notes
    
    // Load branch products
    if (po.branch_id) {
      await loadProductsByBranch(po.branch_id)
    }

    // Pre-fill items
    if (po.items && po.items.length > 0) {
      form.items = po.items.map((item: any) => ({
        product_id: item.product_id,
        variation_id: item.variation_id,
        unit_of_measurement: item.product?.unit_of_measurement || item.unit_of_measurement || item.unit || '',
        quantity_ordered: item.quantity_ordered,
        unit_cost: item.unit_cost
      }))
      form.items.forEach((_, index) => calculateItemTotal(index))
    }

    // Load supplier details to auto-fill
    if (po.supplier_id) {
      await onSupplierChange()
    }

    // Recalculate totals
    updateTotals()
  } catch (error) {
    console.error('Failed to load PO for editing', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load purchase order for editing',
      life: 3000
    })
  }
}

const loadProductsByBranch = async (branchId: number) => {
  try {
    const response = await procurementService.getBranchInventory(branchId, { per_page: 100 })
    const data = response.data?.data || response.data || []

    // Map the API response to product format
    products.value = data.map((item: any) => ({
      id: item.product_id || item.product?.id,
      product_name: item.product?.product_name || item.product_name || 'Unknown Product',
      sku: item.product?.sku || item.sku,
      stock_level: item.quantity_available || 0,
      cost_price: item.product?.cost_price ?? item.unit_cost ?? 0,
      quantity_on_hand: item.quantity_on_hand,
      reorder_point: item.reorder_point,
      category_id: item.product?.category_id || item.category_id
    })).filter((p: any) => !!p.id)

    if (products.value.length === 0) {
      const fallbackRes = await procurementService.getProcurementProducts({ per_page: 500 })
      const fallbackList = fallbackRes?.data?.data || fallbackRes?.data || []
      products.value = fallbackList.map((product: any) => ({
        id: product.id,
        product_name: product.product_name || 'Unknown Product',
        sku: product.sku || '',
        stock_level: product.stock_level || 0,
        cost_price: product.cost_price ?? 0,
        category_id: product.category_id
      }))
    }

    syncItemUnitCostsFromProducts()
  } catch (error) {
    console.error('Failed to load products for branch', error)
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Failed to load products for this branch',
      life: 2000
    })
  }
}

const loadProductsBySupplier = async (supplierId: number) => {
  try {
    const response = await procurementService.getSupplierProducts(supplierId, {
      branch_id: form.branch_id
    })
    const payload = response?.data ?? response
    const list = payload?.data ?? payload ?? []
    products.value = list.map((product: any) => ({
      id: product.id,
      product_name: product.product_name || 'Unknown Product',
      sku: product.sku || '',
      stock_level: product.stock_level || 0,
      cost_price: product.unit_cost ?? product.cost_price ?? 0,
      category_id: product.category_id
    }))
    syncItemUnitCostsFromProducts()
  } catch (error) {
    console.error('Failed to load supplier products', error)
    products.value = []
  }
}

watch(
  () => form.branch_id,
  async (branchId) => {
    if (branchId) {
      if (form.supplier_id) {
        await loadProductsBySupplier(form.supplier_id)
      } else {
        await loadProductsByBranch(branchId)
      }
    } else {
      products.value = []
    }
  }
)

watch(
  () => contractDiscountPercent.value,
  () => {
    updateTotals()
  }
)

watch(
  () => form.items.map((item) => ({
    id: item.id,
    quantity: item.quantity_ordered,
    unit_cost: item.unit_cost
  })),
  () => {
    form.items.forEach((_, index) => calculateItemTotal(index))
  },
  { deep: true }
)

const onSupplierChange = async () => {
  if (!form.supplier_id) {
    selectedSupplier.value = null
    selectedContract.value = null
    contractDiscountPercent.value = 0
    contractTaxRate.value = 0
    supplierWarning.show = false
    return
  }

  try {
    const details = await autoFillSupplierDetails(form.supplier_id)
    if (details) {
      // Check supplier status
      selectedSupplier.value = details
      const status = checkSupplierStatus(details)
      if (status.status !== 'ok') {
        supplierWarning.show = true
        supplierWarning.message = status.message
        supplierWarning.severity = status.severity
      } else {
        supplierWarning.show = false
      }

      try {
        const contractRes = await procurementService.getSupplierContracts({
          supplier_id: form.supplier_id,
          active: 1,
          per_page: 1
        })
        const contractPayload = contractRes?.data ?? contractRes
        const contractPage = contractPayload?.data ?? []
        const contractList = Array.isArray(contractPage) ? contractPage : (contractPage?.data ?? [])
        const contract = Array.isArray(contractList) ? contractList[0] : null
        selectedContract.value = contract || null
        contractDiscountPercent.value = Number(contract?.discount_percentage || 0) || 0
        contractTaxRate.value = Number(contract?.tax_rate || 0) || 0
      } catch (err) {
        selectedContract.value = null
        contractDiscountPercent.value = 0
        contractTaxRate.value = 0
      }

      await loadProductsBySupplier(form.supplier_id)
      syncItemUnitCostsFromProducts()
      updateTotals()
    }
  } catch (error) {
    console.error('Failed to auto-fill supplier details', error)
  }
}

const onProductChange = (index: number, productId: any) => {
  if (productId) {
    // Ensure we have a numeric ID
    const id = typeof productId === 'object' ? productId?.id : productId

    if (id) {
      const numericId = Number(id)
      const product = products.value.find((p) => Number(p.id) === numericId)
      if (product) {
        form.items[index].product_id = numericId
        form.items[index].product_name = product.product_name
        form.items[index].unit_of_measurement = product.unit_of_measurement || product.unit || ''
        form.items[index].stock_level = product.stock_level || 0
        form.items[index].unit_cost = Number(product.cost_price ?? 0) || 0
      }
    }
  }
  calculateItemTotal(index)
}

const getItemUnitOfMeasurement = (item: any) => {
  if (item?.unit_of_measurement) return item.unit_of_measurement
  if (item?.product?.unit_of_measurement) return item.product.unit_of_measurement

  const selectedProduct = products.value.find((product: any) => Number(product.id) === Number(item?.product_id))
  return selectedProduct?.unit_of_measurement || selectedProduct?.unit || '-'
}

const addLineItem = () => {
  form.items.push({
    id: `item-${Date.now()}`,
    product_id: null,
    unit_of_measurement: '',
    selected_supplier_id: null,
    selected_supplier_name: null,
    quantity_ordered: 1,
    unit_cost: 0,
    line_total: 0
  })
}

const removeLineItem = (index: number) => {
  form.items.splice(index, 1)
  delete budgetWarnings.value[index]
  updateTotals()
}

const calculateItemTotal = (index: number) => {
  const item = form.items[index]
  const qty = Number(item.quantity_ordered) || 0
  const price = Number(item.unit_cost) || 0
  item.line_total = qty * price

  // Check budget warning
  const budgetResult = validateQuantityAgainstBudget(item.quantity_ordered, item.unit_cost, 1000000) // Demo budget
  if (!budgetResult.valid) {
    budgetWarnings.value[index] = budgetResult.message || 'Budget exceeded'
  } else {
    delete budgetWarnings.value[index]
  }

  updateTotals()
}

const updateTotals = () => {
  const subtotal = form.items.reduce((sum, item) => sum + (Number(item.line_total) || 0), 0)
  const discount = subtotal * (Number(contractDiscountPercent.value || 0) / 100)
  const taxableAmount = Math.max(0, subtotal - discount)
  const taxRate = Number(supplierTaxRate.value || 0) / 100
  const taxAmount = taxableAmount * taxRate
  form.discount_amount = discount
  totals.subtotal = subtotal
  totals.tax_amount = taxAmount
  totals.total_amount = subtotal + taxAmount - discount + shippingFee.value
  scheduleShippingEstimate(subtotal)
}

const scheduleShippingEstimate = (subtotal: number) => {
  if (shippingEstimateTimer) clearTimeout(shippingEstimateTimer)
  const hasCompleteItems = form.items.length > 0 && form.items.every((item) => Number(item.product_id) > 0 && Number(item.quantity_ordered) > 0)
  if (!form.supplier_id || !form.branch_id || !hasCompleteItems) {
    shippingEstimate.value = null
    shippingEstimateError.value = ''
    return
  }
  shippingEstimateTimer = setTimeout(async () => {
    shippingEstimateLoading.value = true
    shippingEstimateError.value = ''
    try {
      const response = await procurementService.estimatePurchaseOrderShipping({ supplier_id: form.supplier_id, branch_id: form.branch_id, subtotal, items: form.items.map(item => ({ product_id: item.product_id, quantity_ordered: item.quantity_ordered })) })
      shippingEstimate.value = response?.data || null
      totals.total_amount = totals.subtotal + totals.tax_amount - form.discount_amount + shippingFee.value
    } catch (error: any) {
      shippingEstimate.value = null
      shippingEstimateError.value = error?.response?.data?.errors?.shipping_fee?.[0]
        || error?.response?.data?.message
        || 'Shipping fee could not be calculated. Check the supplier and branch locations.'
      totals.total_amount = totals.subtotal + totals.tax_amount - form.discount_amount
    } finally { shippingEstimateLoading.value = false }
  }, 500)
}

const addQuickProduct = (product: any) => {
  const newItem = {
    id: `item-${Date.now()}`,
    product_id: product.product_id,
    product_name: product.product_name,
    selected_supplier_id: null,
    selected_supplier_name: null,
    quantity_ordered: product.quantity_ordered,
    unit_cost: product.cost_price ?? product.unit_cost ?? 0,
    line_total: 0
  }
  form.items.push(newItem)
  calculateItemTotal(form.items.length - 1)

  toast.add({
    severity: 'success',
    summary: 'Added',
    detail: `${product.product_name} added to order`,
    life: 2000
  })
}

const openSplitPoPreview = (action: 'draft' | 'submit') => {
  saveDraft.value = action === 'draft'
  const shouldUseSplitPoMode = !isEditing.value && splitPoMode.value && Number(form.purchase_requisition_id || 0) > 0

  if (shouldUseSplitPoMode) {
    splitPoPreviewAction.value = action
    splitPoPreviewVisible.value = true
    return
  }

  if (action === 'submit') {
    submitConfirmationVisible.value = true
    return
  }

  submitForm()
}

const confirmSplitPoPreview = () => {
  splitPoPreviewVisible.value = false
  submitForm(true)
}

const confirmPurchaseOrderSubmission = () => {
  submitConfirmationVisible.value = false
  submitForm()
}

const submitForm = async (confirmedSplitMode = false) => {
  const shouldUseSplitPoMode = !isEditing.value && splitPoMode.value && Number(form.purchase_requisition_id || 0) > 0

  if (shouldUseSplitPoMode && !confirmedSplitMode) {
    splitPoPreviewVisible.value = true
    return
  }

  // Validation
  if (!shouldUseSplitPoMode && !form.supplier_id) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Please select a supplier', life: 3000 })
    return
  }

  if (!form.branch_id) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Please select a branch', life: 3000 })
    return
  }

  if (form.items.length === 0) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Please add at least one item', life: 3000 })
    return
  }

  saving.value = true
  try {
    // Convert Date objects to ISO string dates for API
    const orderDate = (form.order_date instanceof Date
      ? form.order_date.toISOString().split('T')[0]
      : form.order_date) || new Date().toISOString().split('T')[0]

    const payload: Record<string, any> = shouldUseSplitPoMode
      ? {
        purchase_requisition_id: form.purchase_requisition_id,
        order_date: orderDate,
        notes: form.notes,
        status: saveDraft.value ? 'draft' : 'pending_finance_approval',
      }
      : {
        supplier_id: form.supplier_id,
        rfq_id: Number(route.query.rfq_id || 0) || null,
        branch_id: form.branch_id,
        purchase_requisition_id: form.purchase_requisition_id,
        order_date: orderDate,
        discount_amount: form.discount_amount,
        shipping_cost: shippingFee.value,
        notes: form.notes,
        items: form.items.map((item) => ({
          product_id: item.product_id,
          rfq_item_id: item.rfq_item_id || null,
          quantity_ordered: item.quantity_ordered,
          unit_cost: item.unit_cost
        })),
        status: saveDraft.value ? 'draft' : 'pending_finance_approval'
      }

    // Call create or update based on isEditing flag
    if (isEditing.value && route.params.id) {
      await procurementService.updatePurchaseOrder(Number(route.params.id), payload as any)
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Purchase Order updated successfully',
        life: 2000
      })
    } else {
      const result = await procurementService.createPurchaseOrder(payload as any)
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: shouldUseSplitPoMode
          ? `Created ${result?.data?.created_count || splitPoSupplierGroups.value || 'multiple'} supplier-split purchase orders successfully`
          : `Purchase Order ${saveDraft.value ? 'saved as draft' : 'submitted'} successfully`,
        life: 2000
      })
    }

    setTimeout(() => {
      router.push({ name: 'procurement.purchase-orders' })
    }, 1500)
  } catch (error: any) {
    console.error('Failed to save purchase order', error)
    const detailMessage =
      (error.response?.data?.message ||
        error.response?.data?.error ||
        error.message ||
        (isEditing.value ? 'Failed to update purchase order' : 'Failed to create purchase order'))
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: detailMessage,
      life: 5000
    })
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
/* Animations */
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.3s ease;
}

.slide-fade-enter-from {
  transform: translateX(10px);
  opacity: 0;
}

.slide-fade-leave-to {
  transform: translateX(-10px);
  opacity: 0;
}

.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}

.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}

.list-move {
  transition: transform 0.3s ease;
}
</style>
