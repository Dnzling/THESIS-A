<template>
  <div class="max-w-6xl mx-auto space-y-6 pb-6">
    <!-- Header -->
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded @click="router.back()" />
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Create Purchase Order from Stock Requests</h2>
        <p class="text-sm text-gray-500 mt-1">Select approved stock orders and create a PO with a supplier</p>
      </div>
    </div>

    <!-- Alert Messages -->
    <Toast />
    <Message v-if="formError" severity="error" :text="formError" class="w-full" />
    <Message v-if="successMessage" severity="success" :text="successMessage" class="w-full" />

    <!-- Steps Indicator -->
    <div class="flex gap-2 mb-6">
      <div 
        v-for="(stepName, index) in steps" 
        :key="index"
        class="flex-1"
      >
        <div 
          :class="[
            'flex items-center justify-center gap-2 p-3 rounded-lg font-semibold text-sm',
            currentStep === index 
              ? 'bg-blue-600 text-white' 
              : currentStep > index
              ? 'bg-green-600 text-white'
              : 'bg-gray-200 text-gray-600'
          ]"
        >
          <i :class="['pi', getStepIcon(index)]"></i>
          {{ stepName }}
        </div>
      </div>
    </div>

    <Card>
      <template #content>
        <!-- STEP 1: Select Stock Order Requests -->
        <Transition name="fade" mode="out-in">
          <div v-if="currentStep === 0" class="space-y-6">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
              <i class="pi pi-check-circle text-blue-600"></i>
              Select Approved Stock Order Requests
            </h3>

            <!-- Summary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <Card class="bg-blue-50 border border-blue-200">
                <template #content>
                  <p class="text-xs text-blue-600 font-semibold">Total Available</p>
                  <p class="text-3xl font-bold text-blue-900">{{ approvedRequests.length }}</p>
                </template>
              </Card>
              <Card class="bg-purple-50 border border-purple-200">
                <template #content>
                  <p class="text-xs text-purple-600 font-semibold">Selected</p>
                  <p class="text-3xl font-bold text-purple-900">{{ selectedRequests.length }}</p>
                </template>
              </Card>
              <Card class="bg-green-50 border border-green-200">
                <template #content>
                  <p class="text-xs text-green-600 font-semibold">Total Quantity</p>
                  <p class="text-3xl font-bold text-green-900">{{ selectedTotalQuantity }}</p>
                </template>
              </Card>
            </div>

            <!-- Filter Options -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 p-4 rounded-lg">
              <div>
                <label class="text-sm font-semibold text-gray-700 block mb-2">Filter by Store</label>
                <Select 
                  v-model="filterStore" 
                  :options="stores"
                  option-label="store_name"
                  option-value="id"
                  placeholder="All stores"
                  class="w-full"
                  @change="loadApprovedRequests"
                />
              </div>
              <div>
                <label class="text-sm font-semibold text-gray-700 block mb-2">Filter by Branch</label>
                <Select 
                  v-model="filterBranch" 
                  :options="branches"
                  option-label="name"
                  option-value="id"
                  placeholder="All branches"
                  class="w-full"
                  @change="loadApprovedRequests"
                />
              </div>
              <div>
                <label class="text-sm font-semibold text-gray-700 block mb-2">Filter by Product</label>
                <Select 
                  v-model="filterProduct" 
                  :options="products"
                  option-label="product_name"
                  option-value="id"
                  placeholder="All products"
                  class="w-full"
                  @change="loadApprovedRequests"
                />
              </div>
            </div>

            <!-- Requests Table -->
            <div v-if="loadingRequests" class="text-center py-8">
              <ProgressSpinner style="width: 50px; height: 50px" stroke-width="4" />
              <p class="mt-2 text-gray-600">Loading approved stock requests...</p>
            </div>

            <div v-else-if="approvedRequests.length === 0" class="text-center py-8 bg-gray-50 border border-dashed border-gray-300 rounded">
              <i class="pi pi-inbox text-gray-400 text-3xl mb-2"></i>
              <p class="text-gray-500">No approved stock requests available</p>
              <p class="text-xs text-gray-400 mt-2">Try adjusting your filters</p>
            </div>

            <div v-else class="overflow-x-auto">
              <DataTable
                :value="approvedRequests"
                selection-mode="multiple"
                v-model:selection="selectedRequests"
                data-key="id"
                responsive-layout="scroll"
                class="w-full text-sm"
              >
                <Column selection-header-style="width: 3rem" header-style="width: 3rem"></Column>
                <Column field="uuid" header="Request ID" style="width: 80px">
                  <template #body="{ data }">
                    <code class="bg-gray-100 px-2 py-1 rounded text-xs">{{ data.uuid.substring(0, 8) }}</code>
                  </template>
                </Column>
                <Column field="branch.name" header="Branch"></Column>
                <Column field="product.product_name" header="Product"></Column>
                <Column field="variation.variation_name" header="Variation">
                  <template #body="{ data }">
                    {{ data.variation?.variation_name || '-' }}
                  </template>
                </Column>
                <Column field="requested_quantity" header="Quantity" style="text-align: right">
                  <template #body="{ data }">
                    <strong>{{ data.requested_quantity }}</strong>
                  </template>
                </Column>
                <Column field="created_at" header="Created" style="width: 100px">
                  <template #body="{ data }">
                    <small>{{ formatDate(data.created_at) }}</small>
                  </template>
                </Column>
                <Column field="approved_at" header="Approved" style="width: 100px">
                  <template #body="{ data }">
                    <small>{{ formatDate(data.approved_at) }}</small>
                  </template>
                </Column>
              </DataTable>
            </div>

            <!-- Next Button -->
            <div class="flex justify-end gap-3 pt-4">
              <Button 
                label="Back" 
                icon="pi pi-arrow-left"
                severity="secondary"
                @click="router.push({ name: 'procurement.purchase-orders' })"
              />
              <Button 
                label="Next: Choose Supplier"
                icon="pi pi-arrow-right"
                :disabled="selectedRequests.length === 0"
                @click="nextStep"
              />
            </div>
          </div>
        </Transition>

        <!-- STEP 2: Review Items & Select Supplier -->
        <Transition name="fade" mode="out-in">
          <div v-if="currentStep === 1" class="space-y-6">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
              <i class="pi pi-shopping-cart text-purple-600"></i>
              Review Items & Select Supplier
            </h3>

            <!-- Items Summary -->
            <Card class="bg-blue-50 border border-blue-200">
              <template #content>
                <p class="text-sm font-semibold text-blue-800 mb-3">📦 Items from Selected Stock Requests</p>
                <div class="space-y-2">
                  <div 
                    v-for="(item, index) in poItems"
                    :key="index"
                    class="flex justify-between items-center p-2 bg-white rounded border border-blue-100"
                  >
                    <div>
                      <p class="font-semibold text-gray-800">{{ item.product_name }}</p>
                      <p class="text-xs text-gray-600">{{ item.variation_name || 'No variation' }}</p>
                    </div>
                    <div class="text-right">
                      <p class="font-bold text-blue-900">Qty: {{ item.quantity_ordered }}</p>
                      <p class="text-xs text-gray-600">From Branch: {{ item.branch_name }}</p>
                    </div>
                  </div>
                </div>
              </template>
            </Card>

            <!-- Supplier Selection -->
            <div>
              <label class="text-sm font-semibold text-gray-700 block mb-3">
                <span class="text-red-500">*</span> Select Supplier
              </label>
              <Select 
                v-model="form.supplier_id"
                :options="suppliers"
                option-label="supplier_name"
                option-value="id"
                placeholder="Choose a supplier..."
                class="w-full"
                filter
                :loading="loadingSuppliers"
                @change="onSupplierChange"
              />
              <p class="text-xs text-gray-500 mt-1">Select a supplier available for these products</p>
            </div>

            <!-- Supplier Details -->
            <div v-if="selectedSupplier" class="bg-purple-50 border-l-4 border-purple-500 p-4 rounded">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                  <p class="text-purple-600 font-semibold">Contact Person</p>
                  <p class="text-gray-800">{{ selectedSupplier.contact_person || '-' }}</p>
                </div>
                <div>
                  <p class="text-purple-600 font-semibold">Average Delivery Days</p>
                  <p class="text-gray-800">{{ selectedSupplier.average_delivery_days || 7 }} days</p>
                </div>
                <div>
                  <p class="text-purple-600 font-semibold">Email</p>
                  <p class="text-gray-800">{{ selectedSupplier.email || '-' }}</p>
                </div>
                <div>
                  <p class="text-purple-600 font-semibold">Phone</p>
                  <p class="text-gray-800">{{ selectedSupplier.phone || '-' }}</p>
                </div>
              </div>
            </div>

            <!-- Next Button -->
            <div class="flex justify-end gap-3 pt-4">
              <Button 
                label="Back"
                icon="pi pi-arrow-left"
                severity="secondary"
                @click="currentStep--"
              />
              <Button 
                label="Next: Enter Terms"
                icon="pi pi-arrow-right"
                :disabled="!form.supplier_id"
                @click="nextStep"
              />
            </div>
          </div>
        </Transition>

        <!-- STEP 3: Enter Payment Terms & Confirm -->
        <Transition name="fade" mode="out-in">
          <div v-if="currentStep === 2" class="space-y-6">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
              <i class="pi pi-cog text-green-600"></i>
              Enter Payment Terms & Charges
            </h3>

            <!-- Items Summary Table -->
            <div class="overflow-x-auto bg-gray-50 p-4 rounded">
              <table class="w-full text-sm">
                <thead class="border-b-2 border-gray-300">
                  <tr>
                    <th class="text-left p-2">Product</th>
                    <th class="text-center p-2">Quantity</th>
                    <th class="text-right p-2">Unit Cost (Est.)</th>
                    <th class="text-right p-2">Line Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in poItems" :key="index" class="border-b border-gray-200">
                    <td class="p-2">
                      <div>
                        <p class="font-semibold">{{ item.product_name }}</p>
                        <p class="text-xs text-gray-500">{{ item.variation_name || '-' }}</p>
                      </div>
                    </td>
                    <td class="p-2 text-center">{{ item.quantity_ordered }}</td>
                    <td class="p-2 text-right">₱ {{ item.unit_cost?.toFixed(2) || '0.00' }}</td>
                    <td class="p-2 text-right font-semibold">₱ {{ ((item.unit_cost || 0) * item.quantity_ordered).toFixed(2) }}</td>
                  </tr>
                </tbody>
                <tfoot class="border-t-2 border-gray-300 font-semibold">
                  <tr>
                    <td colspan="3" class="p-2 text-right">Subtotal:</td>
                    <td class="p-2 text-right">₱ {{ subtotal.toFixed(2) }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <!-- Payment Terms Entry -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="text-sm font-semibold text-gray-700 block mb-2">
                  <span class="text-red-500">*</span> Payment Terms
                </label>
                <Select 
                  v-model="form.payment_terms"
                  :options="paymentTerms"
                  option-label="label"
                  option-value="value"
                  placeholder="Select payment terms..."
                  class="w-full"
                />
              </div>

              <div>
                <label class="text-sm font-semibold text-gray-700 block mb-2">Shipping Cost</label>
                <InputNumber 
                  v-model="form.shipping_cost"
                  :min="0"
                  mode="currency"
                  currency="PHP"
                  @input="calculateTotals"
                  fluid
                />
              </div>
            </div>

            <div>
              <label class="text-sm font-semibold text-gray-700 block mb-2">Discount Amount</label>
              <InputNumber 
                v-model="form.discount_amount"
                :min="0"
                mode="currency"
                currency="PHP"
                @input="calculateTotals"
                fluid
              />
            </div>

            <!-- Totals Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <Card class="bg-blue-50 border border-blue-200">
                <template #content>
                  <p class="text-xs text-blue-600 font-semibold">Subtotal</p>
                  <p class="text-2xl font-bold text-blue-900">₱ {{ subtotal.toFixed(2) }}</p>
                </template>
              </Card>

              <Card class="bg-orange-50 border border-orange-200">
                <template #content>
                  <p class="text-xs text-orange-600 font-semibold">Additional Charges</p>
                  <p class="text-2xl font-bold text-orange-900">
                    ₱ {{ ((form.shipping_cost || 0) - (form.discount_amount || 0)).toFixed(2) }}
                  </p>
                </template>
              </Card>

              <Card class="bg-green-50 border border-green-300 shadow-lg">
                <template #content>
                  <p class="text-xs text-green-600 font-semibold">TOTAL AMOUNT</p>
                  <p class="text-3xl font-bold text-green-900">₱ {{ (subtotal + (form.shipping_cost || 0) - (form.discount_amount || 0)).toFixed(2) }}</p>
                </template>
              </Card>
            </div>

            <!-- Notes -->
            <div>
              <label class="text-sm font-semibold text-gray-700 block mb-2">Notes / Special Instructions</label>
              <Textarea 
                v-model="form.notes"
                rows="4"
                auto-resize
                class="w-full"
                placeholder="Add any special instructions for this purchase order..."
              />
            </div>

            <!-- Navigation Buttons -->
            <div class="flex justify-end gap-3 pt-4 border-t">
              <Button 
                label="Back"
                icon="pi pi-arrow-left"
                severity="secondary"
                @click="currentStep--"
              />
              <Button 
                label="Create Purchase Order"
                icon="pi pi-check"
                :loading="saving"
                :disabled="!form.payment_terms || saving"
                @click="submitForm"
              />
            </div>
          </div>
        </Transition>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '@/services/procurement.service'
const formatDate = (date: string | Date | null | undefined) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

const formatCurrency = (value: number | string | null | undefined) => {
  const amount = typeof value === 'string' ? parseFloat(value) : value || 0
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount)
}

const router = useRouter()
const toast = useToast()

// Steps configuration
const steps = ['Select Stock Requests', 'Choose Supplier', 'Confirm & Submit']
const currentStep = ref(0)

// Form state
const form = reactive({
  stock_order_request_ids: [] as number[],
  supplier_id: null as number | null,
  payment_terms: null as string | null,
  shipping_cost: 0,
  discount_amount: 0,
  notes: ''
})

// UI State
const saving = ref(false)
const loadingRequests = ref(false)
const loadingSuppliers = ref(false)
const formError = ref('')
const successMessage = ref('')

// Data Lists
const approvedRequests = ref<any[]>([])
const selectedRequests = ref<any[]>([])
const suppliers = ref<any[]>([])
const branches = ref<any[]>([])
const stores = ref<any[]>([])
const products = ref<any[]>([])
const selectedSupplier = ref<any>(null)

// Filters
const filterStore = ref<number | null>(null)
const filterBranch = ref<number | null>(null)
const filterProduct = ref<number | null>(null)

// PO Items derived from selected requests
const poItems = computed(() => {
  return selectedRequests.value.map(req => ({
    stock_order_request_id: req.id,
    product_id: req.product_id,
    product_name: req.product?.product_name || 'Unknown',
    variation_name: req.variation?.variation_name,
    quantity_ordered: req.requested_quantity,
    unit_cost: req.product?.last_purchase_price || 0,
    branch_name: req.branch?.name || 'Unknown'
  }))
})

// Calculated totals
const selectedTotalQuantity = computed(() => {
  return selectedRequests.value.reduce((sum, req) => sum + req.requested_quantity, 0)
})

const subtotal = computed(() => {
  return poItems.value.reduce((sum, item) => sum + ((item.unit_cost || 0) * item.quantity_ordered), 0)
})

// Payment terms options
const paymentTerms = [
  { label: 'Cash on Delivery', value: 'cash_on_delivery' },
  { label: 'Net 7 Days', value: 'net_7' },
  { label: 'Net 15 Days', value: 'net_15' },
  { label: 'Net 30 Days', value: 'net_30' },
  { label: 'Net 60 Days', value: 'net_60' },
  { label: 'Advance Payment', value: 'advance_payment' }
]

// Helper Methods
const getStepIcon = (stepIndex: number) => {
  if (currentStep.value === stepIndex) return 'pi-spin pi-spinner'
  if (currentStep.value > stepIndex) return 'pi-check'
  return 'pi-circle'
}

// Load Initial Data
onMounted(async () => {
  await Promise.all([
    loadApprovedRequests(),
    loadSuppliers(),
    loadBranches(),
    loadStores()
  ])
})

const loadApprovedRequests = async () => {
  try {
    loadingRequests.value = true
    const response = await procurementService.getPendingStockOrderRequestsForConversion({
      store_id: filterStore.value,
      branch_id: filterBranch.value,
      product_id: filterProduct.value
    })
    approvedRequests.value = response.data || []
  } catch (error) {
    console.error('Failed to load approved requests', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load approved stock requests',
      life: 3000
    })
  } finally {
    loadingRequests.value = false
  }
}

const loadSuppliers = async () => {
  try {
    loadingSuppliers.value = true
    const response = await procurementService.getSuppliers({ per_page: 100 })
    suppliers.value = response.data?.data || response.data || []
  } catch (error) {
    console.error('Failed to load suppliers', error)
  } finally {
    loadingSuppliers.value = false
  }
}

const loadBranches = async () => {
  try {
    const response = await procurementService.getBranches?.() || { data: [] }
    branches.value = response.data?.data || response.data || []
  } catch (error) {
    console.error('Failed to load branches', error)
  }
}

const loadStores = async () => {
  try {
    const response = await procurementService.getStores?.() || { data: [] }
    stores.value = response.data?.data || response.data || []
  } catch (error) {
    console.error('Failed to load stores', error)
  }
}

const onSupplierChange = () => {
  if (form.supplier_id) {
    const supplier = suppliers.value.find(s => s.id === form.supplier_id)
    selectedSupplier.value = supplier || null
  } else {
    selectedSupplier.value = null
  }
}

const calculateTotals = () => {
  // This is just for display calculation
  // Actual calculation happens in computed properties
}

const nextStep = () => {
  if (currentStep.value === 0) {
    if (selectedRequests.value.length === 0) {
      formError.value = 'Please select at least one stock request'
      return
    }
    form.stock_order_request_ids = selectedRequests.value.map(r => r.id)
  } else if (currentStep.value === 1) {
    if (!form.supplier_id) {
      formError.value = 'Please select a supplier'
      return
    }
  }
  formError.value = ''
  currentStep.value++
}

const submitForm = async () => {
  // Validation
  formError.value = ''

  if (form.stock_order_request_ids.length === 0) {
    formError.value = 'No stock requests selected'
    return
  }

  if (!form.supplier_id) {
    formError.value = 'Please select a supplier'
    return
  }

  if (!form.payment_terms) {
    formError.value = 'Please select payment terms'
    return
  }

  saving.value = true
  try {
    const payload = {
      stock_order_request_ids: form.stock_order_request_ids,
      supplier_id: form.supplier_id,
      payment_terms: form.payment_terms,
      shipping_cost: form.shipping_cost || 0,
      discount_amount: form.discount_amount || 0,
      notes: form.notes
    }

    await procurementService.createPurchaseOrder(payload as any)
    
    successMessage.value = 'Purchase Order created successfully'
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Purchase Order created from stock requests',
      life: 2000
    })

    setTimeout(() => {
      router.push({ name: 'procurement.purchase-orders' })
    }, 1500)
  } catch (error) {
    console.error('Failed to create purchase order', error)
    formError.value = 'Failed to create purchase order. Please try again.'
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: formError.value,
      life: 3000
    })
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
