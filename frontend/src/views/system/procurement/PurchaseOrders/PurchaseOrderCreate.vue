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
                  placeholder="Select branch" class="w-full" @change="onBranchChange" />
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
                  <span class="text-red-500">*</span> Supplier
                </label>
                <Select v-model="form.supplier_id" :options="suppliers" option-label="supplier_name" option-value="id"
                  placeholder="Select supplier" class="w-full" filter @change="onSupplierChange"
                  :loading="loadingSuppliers" />
                <p class="text-xs text-gray-500 mt-1">Auto-populates supplier details when selected</p>
              </div>
  
              <!-- Expected Delivery Date (Auto-calculated) -->
              <div class="md:col-span-6">
                <label class="text-sm font-semibold text-gray-700 block mb-2">
                  <span class="text-red-500">*</span> Expected Delivery Date
                </label>
                <div class="flex gap-2">
                  <DatePicker v-model="form.expected_delivery_date" date-format="yy-mm-dd" class="flex-1"
                    :min-date="minDeliveryDate" @change="validateDeliveryDate" fluid />
                  <Button icon="pi pi-bolt" outlined severity="warning" @click="setDeliveryDateAuto"
                    v-tooltip="`Auto-set based on ${selectedSupplier?.average_delivery_days || 7} day delivery`" />
                </div>
                <p class="text-xs text-red-500 mt-1" v-if="deliveryDateError">{{ deliveryDateError }}</p>
              </div>
            </div>
  
            <!-- Supplier Details Card (Auto-populated) -->
            <div v-if="selectedSupplier" class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
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
              </div>
            </div>
  
            <!-- Payment Terms and Shipping -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mt-4">
              <div class="md:col-span-4">
                <label class="text-sm font-semibold text-gray-700 block mb-2">
                  <span class="text-red-500">*</span> Payment Terms
                </label>
                <Select v-model="form.payment_terms" :options="paymentTerms" option-label="label" option-value="value"
                  placeholder="Select payment terms..." class="w-full" />
              </div>
  
              <div class="md:col-span-4">
                <label class="text-sm font-semibold text-gray-700 block mb-2">Shipping Cost</label>
                <InputNumber v-model="form.shipping_cost" :min="0" mode="currency" currency="PHP" @input="updateTotals" fluid />
              </div>
  
              <div class="md:col-span-4">
                <label class="text-sm font-semibold text-gray-700 block mb-2">Discount Amount</label>
                <InputNumber v-model="form.discount_amount" :min="0" mode="currency" currency="PHP" fluid
                  @input="updateTotals" />
              </div>
            </div>
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
              <p class="text-sm font-semibold text-gray-700 mb-3">🔥 Quick Add (Top Products)</p>
              <div class="flex gap-2 flex-wrap">
                <Button v-for="product in frequentProducts" :key="product.id"
                  :label="`${product.product_name} (${product.quantity_ordered})`" size="small" severity="secondary"
                  outlined @click="addQuickProduct(product)" class="text-xs" />
              </div>
            </div>
  
            <!-- Line Items Table -->
            <Transition name="slide-fade" mode="out-in">
              <div v-if="form.items.length > 0" class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead class="bg-gray-100 border-b-2 border-gray-300">
                    <tr>
                      <th class="text-left p-3">Product</th>
                      <th class="text-center p-3">Qty</th>
                      <th class="text-right p-3">Unit Price</th>
                      <th class="text-center p-3">Tax %</th>
                      <th class="text-center p-3">Discount %</th>
                      <th class="text-right p-3">Line Total</th>
                      <th class="text-center p-3">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <TransitionGroup name="list" tag="tbody">
                      <tr v-for="(item, index) in form.items" :key="item.id || index"
                        class="border-b hover:bg-gray-50 transition-colors">
                        <td class="p-3">
                          <Select :model-value="item.product_id" :options="products" option-label="product_name"
                            option-value="id" placeholder="Select product..." class="w-full" filter
                            @update:model-value="(value) => onProductChange(index, value)" />
                        </td>
                        <td class="p-3">
                          <InputNumber v-model="item.quantity_ordered" :min="1" @input="calculateItemTotal(index)"
                            class="w-full text-center" />
                          <p v-if="budgetWarnings[index]" class="text-xs text-red-500 mt-1">
                            ⚠️ {{ budgetWarnings[index] }}
                          </p>
                        </td>
                        <td class="p-3">
                          <InputNumber v-model="item.unit_cost" :min="0" mode="currency" currency="PHP"
                            @input="calculateItemTotal(index)" class="w-full text-right" />
                        </td>
                        <td class="p-3">
                          <InputNumber v-model="item.tax_rate" :min="0" :max="100" suffix="%"
                            @input="calculateItemTotal(index)" class="w-full text-center" />
                        </td>
                        <td class="p-3">
                          <InputNumber v-model="item.discount_percent" :min="0" :max="100" suffix="%"
                            @input="calculateItemTotal(index)" class="w-full text-center" />
                        </td>
                        <td class="p-3 text-right font-semibold text-green-600">
                          ₱ {{ item.line_total?.toFixed(2) || '0.00' }}
                        </td>
                        <td class="p-3 text-center">
                          <Button icon="pi pi-trash" text severity="danger" size="small" @click="removeLineItem(index)"
                            v-tooltip="'Remove item'" />
                        </td>
                      </tr>
                    </TransitionGroup>
                  </tbody>
                </table>
              </div>
              <div v-else class="text-center py-8 bg-gray-50 border border-dashed border-gray-300 rounded">
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
  
            <Card class="bg-linear-to-br from-purple-50 to-purple-100 border border-purple-200">
              <template #content>
                <p class="text-xs text-purple-600 font-semibold">Tax (VAT)</p>
                <p class="text-2xl font-bold text-purple-900">{{ formatCurrency(totals.tax_total) }}</p>
              </template>
            </Card>
  
            <Card class="bg-linear-to-br from-orange-50 to-orange-100 border border-orange-200">
              <template #content>
                <p class="text-xs text-orange-600 font-semibold">Additional Charges</p>
                <p class="text-2xl font-bold text-orange-900">
                  {{ formatCurrency((form.shipping_cost || 0) - (form.discount_amount || 0)) }}
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
              @click="saveDraft = true; submitForm()" />
            <Button label="Create & Submit" icon="pi pi-check" severity="success" :loading="saving"
              @click="saveDraft = false; submitForm()" />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, computed, onMounted } from 'vue'
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
  calculateLineTotal,
  calculateTotals,
  validateQuantityAgainstBudget,
  checkSupplierStatus,
  getFrequentlyPurchasedProducts,
  formatCurrency,
  validateDeliveryDate: validateDeliveryDateUtil
} = usePoAutomation()

// Form State
const form = reactive({
  supplier_id: null as number | null,
  branch_id: null as number | null,
  order_date: new Date(),
  expected_delivery_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000),
  payment_terms: 'net_30',
  shipping_cost: 0,
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
const storeCurrency = ref('PHP')
const budgetWarnings = ref<Record<number, string>>({})
const supplierWarning = reactive({ show: false, message: '', severity: 'warning' as string })
const deliveryDateError = ref('')
const minDeliveryDate = computed(() => new Date(form.order_date))

const totals = reactive({
  subtotal: 0,
  tax_total: 0,
  total_amount: 0
})

const paymentTerms = [
  { label: 'Cash on Delivery', value: 'cash_on_delivery' },
  { label: 'Net 7 Days', value: 'net_7' },
  { label: 'Net 15 Days', value: 'net_15' },
  { label: 'Net 30 Days', value: 'net_30' },
  { label: 'Net 60 Days', value: 'net_60' },
  { label: 'Advance Payment', value: 'advance_payment' }
]

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
    branches.value = branchesRes.data?.data || branchesRes.data || []
    products.value = [] // Will be populated from supplier dropdown

    // Set default branch if only one exists
    if (branches.value.length === 1) {
      form.branch_id = branches.value[0].id
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

const prefillFromRequisition = async (requisitionId: number) => {
  try {
    const requisitionRes = await procurementService.getPurchaseRequisition(requisitionId)
    const requisition = requisitionRes?.data || requisitionRes?.data?.data || requisitionRes

    if (!requisition) return

    form.branch_id = requisition.branch_id || requisition.branch?.id || form.branch_id
    form.notes = requisition.reason || form.notes

    if (Array.isArray(requisition.items)) {
      form.items = requisition.items.map((item: any) => ({
        id: `req-${item.id}`,
        product_id: item.product_id,
        product_name: item.product?.product_name || item.product_name || '',
        quantity_ordered: item.quantity_requested || 1,
        unit_cost: parseFloat(item.estimated_unit_cost || item.product?.base_price || '0') || 0,
        tax_rate: 12,
        discount_percent: 0,
        line_total: 0
      }))

      form.items.forEach((_, index) => calculateItemTotal(index))
    }

    const firstSupplierId = requisition.items?.[0]?.product?.suppliers?.[0]?.id
    if (firstSupplierId) {
      form.supplier_id = firstSupplierId
      await onSupplierChange()
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

    form.notes = `From RFQ ${rfq.rfq_number || rfqId}`
    form.payment_terms = rfq.payment_terms || form.payment_terms

    const approvedFeedbacks = Array.isArray(rfq.supplier_portal_feedbacks)
      ? rfq.supplier_portal_feedbacks.filter((f: any) => f.status === 'approved')
      : []

    const supplierIds = approvedFeedbacks
      .map((f: any) => f?.supplier_portal?.supplier_id || f?.supplier_portal?.supplier?.id)
      .filter((id: any) => !!id)

    const uniqueSupplierIds = Array.from(new Set(supplierIds))
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

    if (form.supplier_id) {
      await onSupplierChange()
    }

    if (approvedFeedbacks.length > 0) {
      form.items = approvedFeedbacks.map((feedback: any) => {
        const rfqItem = feedback?.rfq_item || {}
        const product = rfqItem?.product || {}
        return {
          id: `rfq-feedback-${feedback.id}`,
          product_id: rfqItem.product_id || product.id || null,
          product_name: product.product_name || rfqItem.product_name || '',
          quantity_ordered: rfqItem.quantity || 1,
          unit_cost: parseFloat(feedback.quoted_price || 0) || 0,
          tax_rate: 12,
          discount_percent: 0,
          line_total: 0,
          stock_level: 0
        }
      })
    } else if (Array.isArray(rfq.items)) {
      form.items = rfq.items.map((item: any) => {
        const product = item.product || {}
        return {
          id: `rfq-item-${item.id || Date.now()}`,
          product_id: item.product_id || product.id || null,
          product_name: product.product_name || item.product_name || '',
          quantity_ordered: item.quantity || 1,
          unit_cost: parseFloat(item.target_price || 0) || 0,
          tax_rate: 12,
          discount_percent: 0,
          line_total: 0,
          stock_level: 0
        }
      })
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

    // Pre-fill form with existing PO data
    form.supplier_id = po.supplier_id
    form.branch_id = po.branch_id
    form.order_date = new Date(po.order_date)
    form.expected_delivery_date = new Date(po.expected_delivery_date)
    form.payment_terms = po.payment_terms
    form.shipping_cost = po.shipping_cost
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
        unit_cost: item.unit_cost,
        tax_rate: item.tax_rate,
        discount_percent: item.discount_percent
      }))
    }

    // Load supplier details to auto-fill
    if (po.supplier_id) {
      await onSupplierChange()
    }

    // Recalculate totals
    calculateTotals()
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
      id: item.product_id,
      product_name: item.product?.product_name || 'Unknown Product',
      sku: item.product?.sku,
      stock_level: item.quantity_available || 0,
      last_price: item.unit_cost || 0,
      quantity_on_hand: item.quantity_on_hand,
      reorder_point: item.reorder_point,
      category_id: item.product?.category_id
    }))
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

const onBranchChange = async () => {
  if (form.branch_id) {
    await loadProductsByBranch(form.branch_id)
  }
}

const onSupplierChange = async () => {
  if (!form.supplier_id) {
    selectedSupplier.value = null
    supplierWarning.show = false
    return
  }

  try {
    const details = await autoFillSupplierDetails(form.supplier_id)
    if (details) {
      form.payment_terms = details.payment_terms || 'net_30'
      setDeliveryDateAuto()

      // Check supplier status
      const status = checkSupplierStatus(selectedSupplier.value)
      if (status.status !== 'ok') {
        supplierWarning.show = true
        supplierWarning.message = status.message
        supplierWarning.severity = status.severity
      } else {
        supplierWarning.show = false
      }
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
      const product = products.value.find((p) => p.id === id)
      if (product) {
        form.items[index].product_id = id
        form.items[index].product_name = product.product_name
        form.items[index].stock_level = product.stock_level || 0
        form.items[index].unit_cost = product.last_price || 0
      }
    }
  }
  calculateItemTotal(index)
}

const addLineItem = () => {
  form.items.push({
    id: `item-${Date.now()}`,
    product_id: null,
    quantity_ordered: 1,
    unit_cost: 0,
    tax_rate: 12,
    discount_percent: 0,
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
  item.line_total = calculateLineTotal(item)

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
  const totalsResult = calculateTotals(form.items, form.shipping_cost, form.discount_amount)
  totals.subtotal = totalsResult.subtotal
  totals.tax_total = totalsResult.tax_total
  totals.total_amount = totalsResult.total_amount
}

const setDeliveryDateAuto = () => {
  const days = selectedSupplier.value?.average_delivery_days || 7
  const deliveryDate = new Date(Date.now() + days * 24 * 60 * 60 * 1000)
  form.expected_delivery_date = deliveryDate
}

const validateDeliveryDate = () => {
  // Convert dates to strings for validation utilities
  const toDateString = (date: any): string => {
    if (date instanceof Date) {
      const iso = date.toISOString()
      const parts = iso.split('T')
      return parts[0] || new Date().toISOString().split('T')[0] as string
    }
    if (typeof date === 'string') {
      return date
    }
    return new Date().toISOString().split('T')[0] as string
  }

  const orderDateStr = toDateString(form.order_date)
  const deliveryDateStr = toDateString(form.expected_delivery_date)

  const validation = validateDeliveryDateUtil(orderDateStr, deliveryDateStr)
  if (!validation.valid) {
    deliveryDateError.value = validation.message || ''
  } else {
    deliveryDateError.value = ''
  }
}

const addQuickProduct = (product: any) => {
  const newItem = {
    id: `item-${Date.now()}`,
    product_id: product.product_id,
    product_name: product.product_name,
    quantity_ordered: product.quantity_ordered,
    unit_cost: product.unit_cost,
    tax_rate: 12,
    discount_percent: 0,
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

const submitForm = async () => {
  // Validation
  if (!form.supplier_id) {
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

  if (deliveryDateError.value) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Please fix delivery date issues', life: 3000 })
    return
  }

  saving.value = true
  try {
    // Convert Date objects to ISO string dates for API
    const orderDate = (form.order_date instanceof Date
      ? form.order_date.toISOString().split('T')[0]
      : form.order_date) || new Date().toISOString().split('T')[0]
    const deliveryDate = (form.expected_delivery_date instanceof Date
      ? form.expected_delivery_date.toISOString().split('T')[0]
      : form.expected_delivery_date) || new Date().toISOString().split('T')[0]

    const payload: Record<string, any> = {
      supplier_id: form.supplier_id,
      branch_id: form.branch_id,
      order_date: orderDate,
      expected_delivery_date: deliveryDate,
      payment_terms: form.payment_terms,
      shipping_cost: form.shipping_cost,
      discount_amount: form.discount_amount,
      notes: form.notes,
      items: form.items.map((item) => ({
        product_id: item.product_id,
        quantity_ordered: item.quantity_ordered,
        unit_cost: item.unit_cost,
        tax_rate: item.tax_rate,
        discount_percent: item.discount_percent
      })),
      status: saveDraft.value ? 'draft' : 'pending_approval'
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
      await procurementService.createPurchaseOrder(payload as any)
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `Purchase Order ${saveDraft.value ? 'saved as draft' : 'submitted'} successfully`,
        life: 2000
      })
    }

    setTimeout(() => {
      router.push({ name: 'procurement.purchase-orders' })
    }, 1500)
  } catch (error) {
    console.error('Failed to save purchase order', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: isEditing.value ? 'Failed to update purchase order' : 'Failed to create purchase order',
      life: 3000
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

