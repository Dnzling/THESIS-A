<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <!-- Header -->
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'procurement.purchase-orders' })" />
      <div>
        <h2 class="text-2xl font-bold text-gray-800">{{ isEditing ? 'Edit' : 'Create' }} Purchase Order</h2>
        <p class="text-sm text-gray-500 mt-1">{{ isEditing ? 'Update PO details' : 'Use smart automation to speed up PO creation' }}</p>
      </div>
    </div>
  
    <!-- Alert for supplier issues -->
    <Toast />
    <Message v-if="supplierWarning.show" :severity="supplierWarning.severity" :text="supplierWarning.message"
      class="w-full" />
  
    <!-- Main Form -->
    <Card>
      <template #content>
        <form class="space-y-6" @submit.prevent="submitForm">
          <!-- Section 1: Basic Information -->
          <div class="border-b pb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <i class="pi pi-info-circle text-blue-600"></i>
              Purchase Order Information
            </h3>
  
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
              <!-- Currency (Pre-filled from store settings) -->
              <div class="md:col-span-3">
                <label class="text-sm font-semibold text-gray-700 block mb-2">Currency</label>
                <InputText v-model="storeCurrency" disabled class="w-full bg-gray-100" />
              </div>
  
              <!-- Branch Selection -->
              <div class="md:col-span-3">
                <label class="text-sm font-semibold text-gray-700 block mb-2">
                  <span class="text-red-500">*</span> Branch
                </label>
                <Select v-model="form.branch_id" :options="branches" option-label="name" option-value="id"
                  placeholder="Select branch" class="w-full" />
              </div>
  
              <!-- Order Date -->
              <div class="md:col-span-3">
                <label class="text-sm font-semibold text-gray-700 block mb-2">
                  <span class="text-red-500">*</span> Order Date
                </label>
                <DatePicker v-model="form.order_date" date-format="yy-mm-dd" class="w-full" fluid />
              </div>
            </div>
          </div>
  
          <!-- Section 2: Supplier Information -->
          <div class="border-b pb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <i class="pi pi-building text-purple-600"></i>
              Supplier Information
            </h3>
  
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-4">
              <!-- Supplier Selection -->
              <div class="md:col-span-6">
                <label class="text-sm font-semibold text-gray-700 block mb-2">
                  <span v-if="!splitPoMode" class="text-red-500">*</span> Supplier
                </label>
                <Select v-model="form.supplier_id" :options="suppliers" option-label="supplier_name" option-value="id"
                  placeholder="Select supplier" class="w-full" filter @change="onSupplierChange"
                  :loading="loadingSuppliers" :disabled="splitPoMode" />
                <p class="text-xs text-gray-500 mt-1" v-if="!splitPoMode">Auto-populates supplier details when selected</p>
                <p class="text-xs text-blue-600 mt-1" v-else>
                  Split mode active: PR has {{ splitPoSupplierGroups }} supplier groups. System will create one PO per supplier automatically.
                </p>
              </div>
  
              <div class="md:col-span-6">
                <label class="text-sm font-semibold text-gray-700 block mb-2">Contract Discount Amount</label>
                <InputNumber v-model="form.discount_amount" :min="0" mode="currency" currency="PHP" fluid
                  disabled />
                <p class="text-xs text-gray-500 mt-1">Auto-calculated from contract discount %</p>
              </div>
            </div>
  
            <!-- Supplier Details Card (Auto-populated) -->
            <div v-if="selectedSupplier" class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
              <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                <div>
                  <p class="text-gray-600 font-semibold">Contact Person</p>
                  <p class="text-gray-800">{{ selectedSupplier.contact_person || '-' }}</p>
                </div>
                <div>
                  <p class="text-gray-600 font-semibold">Email</p>
                  <p class="text-gray-800">{{ selectedSupplier.email || '-' }}</p>
                </div>
                <div>
                  <p class="text-gray-600 font-semibold">Phone</p>
                  <p class="text-gray-800">{{ selectedSupplier.phone || '-' }}</p>
                </div>
                <div>
                  <p class="text-gray-600 font-semibold">Contract Discount</p>
                  <p class="text-gray-800">
                    {{ contractDiscountDisplay }}
                  </p>
                </div>
                <div>
                  <p class="text-gray-600 font-semibold">Tax Rate</p>
                  <p class="text-gray-800">
                    {{ supplierTaxRateDisplay }} <span v-if="selectedContract?.is_tax_exempt" class="text-xs text-orange-600">(Exempt)</span>
                  </p>
                </div>
              </div>
            </div>
  
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mt-4"></div>
          </div>
  
          <!-- Section 3: Line Items -->
          <div class="border-b pb-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                <i class="pi pi-list text-green-600"></i>
                Purchase Items
              </h3>
              <Button label="Add Item" icon="pi pi-plus" size="small" @click="addLineItem"
                v-tooltip="'Or select from Quick Add below'" />
            </div>
  
            <!-- Quick Add Frequently Purchased Products -->
            <div v-if="frequentProducts.length > 0" class="mb-6 p-4 bg-amber-50 border-l-4 border-amber-400 rounded">
              <p class="text-sm font-semibold text-gray-700 mb-3">Quick Add (Top Products)</p>
              <div class="flex gap-2 flex-wrap">
                <Button v-for="product in frequentProducts" :key="product.id"
                  :label="`${product.product_name} (${product.quantity_ordered})`" size="small" severity="secondary"
                  outlined @click="addQuickProduct(product)" class="text-xs" />
              </div>
            </div>
  
            <!-- Line Items Table -->
            <Transition name="slide-fade" mode="out-in">
              <div v-if="form.items.length > 0" class="space-y-4">
                <TransitionGroup name="list" tag="div" class="space-y-4">
                  <div
                    v-for="(item, index) in form.items"
                    :key="item.id || index"
                    class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 md:p-5"
                  >
                    <div class="flex items-center justify-between mb-4">
                      <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Item {{ index + 1 }}</div>
                      <Button
                        icon="pi pi-trash"
                        text
                        severity="danger"
                        size="small"
                        @click="removeLineItem(index)"
                        v-tooltip="'Remove item'"
                      />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
                      <div class="md:col-span-6">
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

                      <div class="md:col-span-2">
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

                      <div class="md:col-span-4">
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
                        <p class="text-xs text-gray-500 mt-2">Cost price from products table</p>
                      </div>

                      <div class="md:col-span-4">
                        <div class="bg-gray-50 rounded-xl border border-gray-200 px-3 py-2">
                          <label class="text-xs font-medium text-gray-500 uppercase tracking-wide block mb-1">Line Total</label>
                          <div class="text-lg font-semibold text-green-600">
                            ₱ {{ item.line_total?.toFixed(2) || '0.00' }}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </TransitionGroup>
              </div>
              <div v-else class="text-center py-10 bg-gray-50 border border-dashed border-gray-300 rounded-2xl">
                <i class="pi pi-inbox text-gray-400 text-3xl mb-2"></i>
                <p class="text-gray-500">No items added yet. Click "Add Item" to start.</p>
              </div>
            </Transition>
          </div>
  
          <!-- Section 4: Running Totals -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <Card class="bg-linear-to-br from-blue-50 to-blue-100 border border-blue-200">
              <template #content>
                <p class="text-xs text-blue-600 font-semibold">Subtotal</p>
                <p class="text-2xl font-bold text-blue-900">{{ formatCurrency(totals.subtotal) }}</p>
              </template>
            </Card>

            <Card class="bg-linear-to-br from-orange-50 to-orange-100 border border-orange-200">
              <template #content>
                <p class="text-xs text-orange-600 font-semibold">Supplier Discount</p>
                <p class="text-2xl font-bold text-orange-900">
                  {{ formatCurrency(form.discount_amount || 0) }}
                </p>
              </template>
            </Card>

            <Card class="bg-linear-to-br from-yellow-50 to-yellow-100 border border-yellow-200">
              <template #content>
                <p class="text-xs text-amber-600 font-semibold">Tax</p>
                <p class="text-2xl font-bold text-amber-900">
                  {{ formatCurrency(totals.tax_amount) }}
                </p>
              </template>
            </Card>
 
            <Card class="bg-linear-to-br from-green-50 to-green-100 border border-green-300 shadow-lg">
              <template #content>
                <p class="text-xs text-green-600 font-semibold">TOTAL AMOUNT</p>
                <p class="text-3xl font-bold text-green-900">{{ formatCurrency(totals.total_amount) }}</p>
              </template>
            </Card>
          </div>
  
          <!-- Section 5: Notes -->
          <div>
            <label class="text-sm font-semibold text-gray-700 block mb-2">Notes / Special Instructions</label>
            <Textarea v-model="form.notes" rows="5" auto-resize class="w-full"
              placeholder="Add any special instructions or notes for this purchase order..." />
          </div>
  
          <!-- Section 6: Action Buttons -->
          <div class="pt-4 flex justify-end gap-3 border-t">
            <Button label="Cancel" severity="secondary" text type="button"
              @click="router.push({ name: 'procurement.purchase-orders' })" />
            <Button label="Save as Draft" icon="pi pi-download" severity="info" :loading="saving"
              @click="openSplitPoPreview('draft')" />
            <Button label="Create & Submit" icon="pi pi-check" severity="success" :loading="saving"
              @click="openSplitPoPreview('submit')" />
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
          <Button label="Cancel" severity="secondary" text @click="splitPoPreviewVisible = false" />
          <Button
            :label="splitPoPreviewAction === 'draft' ? 'Confirm Save Draft' : 'Confirm Create & Submit'"
            icon="pi pi-check"
            severity="success"
            @click="confirmSplitPoPreview"
          />
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

const totals = reactive({
  subtotal: 0,
  tax_amount: 0,
  total_amount: 0
})

const supplierTaxRate = computed(() =>
  selectedContract.value?.is_tax_exempt ? 0 : (contractTaxRate.value ?? 0)
)
const supplierTaxRateDisplay = computed(() => `${Number(supplierTaxRate.value || 0).toFixed(2)}%`)
const contractDiscountDisplay = computed(() => `${Number(contractDiscountPercent.value || 0).toFixed(2)}%`)
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
      procurementService.getSuppliers({ per_page: 100 }),
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

    const supplierIds = approvedFeedbacks
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

      const rfqItemsRaw =
        rfq.items?.data || rfq.items || rfq.rfq_items || rfq.rfqItems || []

      if (Array.isArray(rfqItemsRaw)) {
        form.items = rfqItemsRaw.map((item: any) => {
          const product = item.product || {}
          return {
            id: `rfq-item-${item.id || Date.now()}`,
            product_id: item.product_id || product.id || null,
            product_name: product.product_name || item.product_name || '',
            quantity_ordered: item.quantity || 1,
            unit_cost: 0,
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
        form.items[index].stock_level = product.stock_level || 0
        form.items[index].unit_cost = Number(product.cost_price ?? 0) || 0
      }
    }
  }
  calculateItemTotal(index)
}

const addLineItem = () => {
  form.items.push({
    id: `item-${Date.now()}`,
    product_id: null,
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
  const taxRate = Number(supplierTaxRate.value || 0) / 100
  const taxAmount = subtotal * taxRate
  const discount = subtotal * (Number(contractDiscountPercent.value || 0) / 100)
  form.discount_amount = discount
  totals.subtotal = subtotal
  totals.tax_amount = taxAmount
  totals.total_amount = subtotal + taxAmount - discount
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

  submitForm()
}

const confirmSplitPoPreview = () => {
  splitPoPreviewVisible.value = false
  submitForm(true)
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
        branch_id: form.branch_id,
        purchase_requisition_id: form.purchase_requisition_id,
        order_date: orderDate,
        discount_amount: form.discount_amount,
        notes: form.notes,
        items: form.items.map((item) => ({
          product_id: item.product_id,
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
