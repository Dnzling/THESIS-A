<template>
  <div class="max-w-6xl mx-auto pb-6">
    <div class="flex items-center gap-3 mb-6">
      <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'procurement.rfqs' })" />
      <div>
        <h2 class="text-2xl font-bold text-gray-800">{{ isEditMode ? 'Edit Request for Quotation' : 'Create Request for Quotation' }}</h2>
        <p class="text-sm text-gray-500 mt-1">
          {{ isEditMode ? 'Update and resend RFQ details' : 'Create and send RFQ in a single view' }}
        </p>
      </div>
    </div>
  
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 space-y-6">
        <Card>
          <template #header>
            <div class="px-6 pt-6">
              <h3 class="text-lg font-semibold text-gray-800">Basic Information</h3>
              <p class="text-sm text-gray-500 mt-1">Enter RFQ header details</p>
            </div>
          </template>
          <template #content>
            <form class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-2">
                  <label class="text-sm font-semibold text-gray-700"><span class="text-red-500">*</span> Title</label>
                  <InputText v-model="form.title" placeholder="e.g., Office Furniture Purchase"
                    :invalid="errors.title !== undefined" />
                  <small class="text-red-500" v-if="errors.title">{{ errors.title }}</small>
                </div>
  
                <div class="flex flex-col gap-2">
                  <label class="text-sm font-semibold text-gray-700"><span class="text-red-500">*</span> RFQ Type</label>
                  <Select v-model="form.rfq_type" :options="rfqTypes" optionLabel="label" optionValue="value"
                    placeholder="Select type" :invalid="errors.rfq_type !== undefined" fluid />
                  <small class="text-red-500" v-if="errors.rfq_type">{{ errors.rfq_type }}</small>
                </div>
  
                <div class="flex flex-col gap-2">
                  <label class="text-sm font-semibold text-gray-700"><span class="text-red-500">*</span> Issue
                    Date</label>
                  <DatePicker v-model="form.issue_date" dateFormat="yy-mm-dd" :invalid="errors.issue_date !== undefined"
                    fluid />
                  <small class="text-red-500" v-if="errors.issue_date">{{ errors.issue_date }}</small>
                </div>
  
                <div class="flex flex-col gap-2">
                  <label class="text-sm font-semibold text-gray-700"><span class="text-red-500">*</span> Currency</label>
                  <Select v-model="form.currency" :options="currencies" optionLabel="label" optionValue="value"
                    placeholder="Select currency" filter :invalid="errors.currency !== undefined" fluid />
                  <small class="text-red-500" v-if="errors.currency">{{ errors.currency }}</small>
                </div>
  
                <div class="flex flex-col gap-2">
                  <label class="text-sm font-semibold text-gray-700">Shipping Terms</label>
                  <Select v-model="form.shipping_terms" :options="shippingTerms" optionLabel="label" optionValue="value"
                    placeholder="Select terms" fluid />
                </div>
  
              </div>
  
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">Description</label>
                <Textarea v-model="form.description" placeholder="Brief description of the RFQ" rows="3" />
              </div>
  
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">Submission Instructions</label>
                <Textarea v-model="form.instructions" placeholder="Any special instructions for suppliers" rows="3" />
              </div>
  
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">Qualification Requirements</label>
                <Textarea v-model="form.qualification_requirements" placeholder="Supplier qualification criteria"
                  rows="3" />
              </div>
            </form>
          </template>
        </Card>
  
        <Card>
          <template #header>
            <div class="px-6 pt-6">
              <h3 class="text-lg font-semibold text-gray-800">Supplier Selection</h3>
              <p class="text-sm text-gray-500 mt-1">Select suppliers to send RFQ to</p>
            </div>
          </template>
          <template #content>
            <div class="space-y-4">
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700"><span class="text-red-500">*</span> Select
                  Suppliers</label>
                <MultiSelect v-model="selectedSupplierIds" :options="suppliers" optionLabel="name" optionValue="id"
                  placeholder="Select suppliers" filter display="chip" class="w-full" />
                <small class="text-gray-500">Select at least one supplier to send the RFQ</small>
              </div>
  
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700"><span class="text-red-500">*</span> Send Via</label>
                <div class="flex gap-4">
                  <div class="flex items-center">
                    <RadioButton v-model="form.invitation_method" value="email" name="method" />
                    <label class="ml-2">Email</label>
                  </div>
                  <div class="flex items-center">
                    <RadioButton v-model="form.invitation_method" value="portal" name="method" />
                    <label class="ml-2">Supplier Portal</label>
                  </div>
                  <div class="flex items-center">
                    <RadioButton v-model="form.invitation_method" value="both" name="method" />
                    <label class="ml-2">Both</label>
                  </div>
                </div>
              </div>
  
              <div v-if="selectedSupplierIds.length > 0" class="mt-6 border-t pt-4">
                <h4 class="font-semibold mb-3">Recipients ({{ selectedSupplierIds.length }})</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div v-for="supplierId in selectedSupplierIds" :key="supplierId"
                    class="p-3 border rounded-lg bg-blue-50">
                    <p class="font-medium">{{ suppliers.find(s => s.id === supplierId)?.name }}</p>
                    <p class="text-sm text-gray-600">{{ suppliers.find(s => s.id === supplierId)?.email }}</p>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>
  
      <div class="space-y-10">
        <Card>
          <template #header>
            <div class="px-6 pt-6">
              <h3 class="text-lg font-semibold text-gray-800">Requested Products</h3>
              <p class="text-sm text-gray-500 mt-1">Line items from the requisition</p>
            </div>
          </template>
          <template #content>
            <div class="space-y-4">
              <DataTable :value="form.items" stripedRows responsiveLayout="scroll" class="mb-4">
                <Column field="product_name" header="Product">
                  <template #body="slotProps">
                    <div class="flex justify-between items-center" v-if="slotProps.index < form.items.length">
                      <Button icon="pi pi-trash" text severity="danger" @click="removeItem(slotProps.index)" />
                      <Select v-if="!slotProps.data.product_id" :options="products" optionLabel="product_name"
                        optionValue="id" placeholder="Select product" filter fluid
                        @change="selectProduct(slotProps.index, $event)" class="w-full" />
                      <span v-else>{{ slotProps.data.product_name }}</span>
                    </div>
                  </template>
                </Column>
                <Column field="quantity" header="Qty">
                  <template #body="slotProps">
                    <InputNumber v-model="slotProps.data.quantity" :useGrouping="false" :min="1" fluid />
                  </template>
                </Column>
              </DataTable>
  
              <Button label="Add Line Item" icon="pi pi-plus" @click="addItem" severity="secondary" text />
              <div class="text-red-500 text-sm" v-if="errors.items">{{ errors.items }}</div>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
  
  <Dialog v-model:visible="showReview" modal header="Review & Submit RFQ" :style="{ width: '60rem' }">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="p-4 rounded-lg bg-blue-50 border border-blue-200">
        <h4 class="font-semibold text-blue-900 mb-2">RFQ Details</h4>
        <div class="text-sm space-y-1">
          <div><span class="text-gray-600">Title:</span> <span class="font-medium text-gray-900">{{ form.title || 'N/A'
              }}</span></div>
          <div><span class="text-gray-600">Type:</span> <span class="font-medium text-gray-900">{{
              getRfqTypeLabel(form.rfq_type) }}</span></div>
          <div><span class="text-gray-600">Issue Date:</span> <span class="font-medium text-gray-900">{{ form.issue_date
              }}</span></div>
        </div>
      </div>
      <div class="p-4 rounded-lg bg-indigo-50 border border-indigo-200">
        <h4 class="font-semibold text-indigo-900 mb-2">Suppliers</h4>
        <div class="text-sm">
          <div class="mb-2"><span class="text-gray-600">Recipients:</span> <span class="font-medium text-gray-900">{{
              selectedSupplierIds.length }}</span></div>
          <div class="grid grid-cols-1 gap-2">
            <div v-for="supplierId in selectedSupplierIds" :key="supplierId" class="p-2 bg-white border rounded">
              <div class="font-medium">{{ suppliers.find(s => s.id === supplierId)?.name }}</div>
              <div class="text-xs text-gray-600">{{ suppliers.find(s => s.id === supplierId)?.email }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  
    <div class="mt-4 p-4 rounded-lg bg-orange-50 border border-orange-200">
      <h4 class="font-semibold text-orange-900 mb-2">Requested Products</h4>
      <div class="space-y-2">
        <div v-for="(item, index) in form.items.filter(i => i.product_id)" :key="index"
          class="flex justify-between text-sm">
          <span class="font-medium text-gray-900">{{ item.product_name }}</span>
          <span class="text-gray-700">Qty: {{ item.quantity }}</span>
        </div>
        <div v-if="form.items.filter(i => i.product_id).length === 0" class="text-sm text-red-600">No items selected.
        </div>
      </div>
    </div>
  
    <div class="mt-4 flex items-center gap-2">
      <Checkbox v-model="confirmTerms" :binary="true" />
      <label class="text-sm">I confirm all details are correct and ready to send</label>
    </div>
  
    <template #footer>
      <Button label="Close" severity="secondary" text @click="showReview = false" />
      <Button :label="isEditMode ? 'Update & Send RFQ' : 'Create & Send RFQ'" icon="pi pi-send" @click="submitForm"
        :disabled="!confirmTerms || selectedSupplierIds.length === 0" />
    </template>
  </Dialog>
  <div class="sticky bottom-0 -mx-6 px-6 py-3 bg-white/95 backdrop-blur flex justify-end gap-2">
    <Button label="Cancel" severity="secondary" text @click="router.push({ name: 'procurement.rfqs' })" />
    <Button :label="isEditMode ? 'Update Draft' : 'Save as Draft'" icon="pi pi-save" severity="warning" @click="saveDraft" :loading="saving" />
    <Button :label="isEditMode ? 'Review & Update' : 'Review & Create'" icon="pi pi-send" iconPos="right" @click="showReview = true" :loading="saving"
      :disabled="selectedSupplierIds.length === 0" />
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'

interface RFQItem {
  product_id: number | null
  variation_id: number | null
  product_name?: string
  quantity: number
  unit: string
  target_price: number | null
  specifications: string
  requirements?: string
  notes: string
}

interface RFQForm {
  purchase_requisition_id: number | null
  title: string
  description: string
  issue_date: Date
  rfq_type: string
  currency: string
  shipping_terms: string
  instructions: string
  qualification_requirements: string
  items: RFQItem[]
  invitation_method: string
}

interface FormErrors {
  [key: string]: string | undefined
}

interface Supplier {
  id: number
  name: string
  email: string
}

interface Product {
  id: number
  product_name: string
  sku: string
  brand?: string
  base_price: string
  category_id?: number
  category?: string
  variation_id?: number | null
  available_qty?: number
  on_order_qty?: number
  received_qty?: number
  pending_receive_qty?: number
}

const router = useRouter()
const route = useRoute()
const toast = useToast()
const saving = ref(false)
const loading = ref(false)
const confirmTerms = ref(false)
const showReview = ref(false)
const selectedSupplierIds = ref<number[]>([])
const editingRfqId = ref<number | null>(null)
const isEditMode = computed(() => editingRfqId.value !== null)
const products = ref<Product[]>([])
const suppliers = ref<Supplier[]>([])
const errors = reactive<FormErrors>({})

const rfqTypes = [
  { label: 'Purchase', value: 'purchase' },
  { label: 'Service', value: 'service' },
  { label: 'Both', value: 'both' },
]

const currencies = [
  { label: 'PHP (Philippine Peso)', value: 'PHP' },
  { label: 'USD (US Dollar)', value: 'USD' },
  { label: 'EUR (Euro)', value: 'EUR' },
]


const shippingTerms = [
  { label: 'FOB (Free on Board)', value: 'FOB' },
  { label: 'CIF (Cost, Insurance, Freight)', value: 'CIF' },
  { label: 'EXW (Ex Works)', value: 'EXW' },
  { label: 'DDP (Delivered Duty Paid)', value: 'DDP' },
]

const form = reactive<RFQForm>({
  purchase_requisition_id: null,
  title: '',
  description: '',
  issue_date: new Date(),
  rfq_type: 'purchase',
  currency: 'PHP',
  shipping_terms: 'FOB',
  instructions: '',
  qualification_requirements: '',
  items: [{ product_id: null, variation_id: null, quantity: 1, unit: 'pcs', target_price: null, specifications: '', notes: '' }],
  invitation_method: 'both',
})

const toDateOrNull = (value: any): Date | null => {
  if (!value) return null
  const date = new Date(value)
  return isNaN(date.getTime()) ? null : date
}

const loadRfqForEdit = async (id: number) => {
  try {
    const response = await procurementService.getRFQ(id)
    const rfq = response?.data?.data || response?.data || response

    if (!rfq) return

    form.purchase_requisition_id = rfq.purchase_requisition_id ?? null
    form.title = rfq.title || ''
    form.description = rfq.description || ''
    form.issue_date = toDateOrNull(rfq.issue_date) || new Date()
    form.rfq_type = rfq.rfq_type || 'purchase'
    form.currency = rfq.currency || 'PHP'
    form.shipping_terms = rfq.shipping_terms || 'FOB'
    form.instructions = rfq.instructions || ''
    form.qualification_requirements = rfq.qualification_requirements || ''

    if (Array.isArray(rfq.items) && rfq.items.length > 0) {
      form.items = rfq.items.map((item: any) => ({
        product_id: item.product_id || null,
        variation_id: item.variation_id || null,
        product_name: item.product?.product_name || item.product_name || '',
        quantity: item.quantity || 1,
        unit: 'pcs',
        target_price: item.target_price ?? null,
        specifications: item.specifications || '',
        notes: item.notes || '',
      }))
    }

    if (Array.isArray(rfq.suppliers)) {
      selectedSupplierIds.value = rfq.suppliers
        .map((s: any) => s.supplier_id || s.supplier?.id)
        .filter((id: number | null) => !!id)
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to load RFQ for editing',
      life: 3000,
    })
  }
}

const getRfqTypeLabel = (value: string) => {
  const type = rfqTypes.find(t => t.value === value)
  return type?.label || value
}

const validateForm = (): boolean => {
  Object.keys(errors).forEach(key => delete errors[key])

  if (!form.title || form.title.trim() === '') {
    errors.title = 'Title is required'
  }

  if (!form.rfq_type) {
    errors.rfq_type = 'RFQ Type is required'
  }

  if (!form.issue_date) {
    errors.issue_date = 'Issue Date is required'
  }

  if (!form.currency) {
    errors.currency = 'Currency is required'
  }

  const validItems = form.items.filter(i => i.product_id)
  if (validItems.length === 0) {
    errors.items = 'At least one line item is required'
  }

  return Object.keys(errors).length === 0
}
const addItem = () => {
  form.items.push({
    product_id: null,
    variation_id: null,
    product_name: '',
    quantity: 1,
    unit: 'pcs',
    target_price: null,
    specifications: '',
    notes: '',
  })
}

const removeItem = (index: number) => {
  form.items.splice(index, 1)
}

const selectProduct = (index: number, event: any) => {
  const selectedProductId = event.value
  const selectedProduct = products.value.find(p => p.id === selectedProductId)

  if (selectedProduct && form.items[index]) {
    form.items[index].product_id = selectedProduct.id
    form.items[index].variation_id = null
    form.items[index].product_name = selectedProduct.product_name
  }
}

const prefillFromRequisition = (requisition: any) => {
  if (!requisition) return

  form.purchase_requisition_id = requisition.id
  if (!form.title) {
    form.title = `RFQ for ${requisition.pr_number || 'Purchase Requisition'}`
  }

  if (!form.description && requisition.reason) {
    form.description = requisition.reason
  }

  if (Array.isArray(requisition.items) && requisition.items.length > 0) {
    form.items = requisition.items.map((item: any) => ({
      product_id: item.product_id || null,
      variation_id: item.variation_id || null,
      product_name: item.product?.product_name || item.product_name || '',
      quantity: item.quantity_requested || 1,
      unit: 'pcs',
      target_price: item.estimated_unit_cost ?? null,
      specifications: item.specifications || '',
      notes: ''
    }))
  }
}
const saveDraft = async () => {
  if (!validateForm()) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please complete required fields',
      life: 3000
    })
    return
  }

  saving.value = true
  try {
    // Validate line items exist
    const validItems = form.items.filter(i => i.product_id)
    if (validItems.length === 0) {
      toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: 'Please add at least one line item before saving',
        life: 3000
      })
      saving.value = false
      return
    }

    // Note: Backend requires supplier_ids, so users must select suppliers before saving
    if (selectedSupplierIds.value.length === 0) {
      toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: 'Please select at least one supplier to send RFQ to',
        life: 3000
      })
      saving.value = false
      return
    }

    // Prepare data for backend (only required fields for backend)
    const payload = {
      purchase_requisition_id: form.purchase_requisition_id,
      title: form.title,
      description: form.description,
      issue_date: form.issue_date instanceof Date
        ? form.issue_date.toISOString().split('T')[0]
        : form.issue_date,
      items: validItems.map(item => ({
        product_id: item.product_id,
        variation_id: item.variation_id,
        quantity: item.quantity,
        specifications: item.specifications,
        requirements: item.requirements || ''
      })),
      supplier_ids: selectedSupplierIds.value
    }

    const response = isEditMode.value && editingRfqId.value
      ? await procurementService.updateRFQ(editingRfqId.value, payload as any)
      : await procurementService.createRFQ(payload as any)

    if (response?.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
      detail: isEditMode.value ? 'RFQ updated successfully' : 'RFQ saved as draft successfully',
        life: 3000
      })
      setTimeout(() => {
        router.push({ name: 'procurement.rfqs' })
      }, 1500)
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response?.message || 'Failed to save draft',
        life: 3000
      })
    }
  } catch (error: any) {
    console.error('Failed to save draft:', error)

    // Handle validation errors from backend
    if (error.response?.data?.errors) {
      const backendErrors = error.response.data.errors
      Object.entries(backendErrors).forEach(([key, value]: [string, any]) => {
        errors[key] = Array.isArray(value) ? value[0] : value
      })
    }

    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || error.message || 'Failed to save draft',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

const submitForm = async () => {
  if (!validateForm()) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please complete required fields',
      life: 3000
    })
    return
  }

  if (!confirmTerms.value) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please confirm all details are correct',
      life: 3000
    })
    return
  }

  if (selectedSupplierIds.value.length === 0) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please select at least one supplier',
      life: 3000
    })
    return
  }

  const validItems = form.items.filter(i => i.product_id)
  if (validItems.length === 0) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please add at least one line item',
      life: 3000
    })
    return
  }

  saving.value = true
  try {
    // Prepare data to match backend controller expectations - include ALL fields
    const payload = {
      purchase_requisition_id: form.purchase_requisition_id,
      title: form.title,
      description: form.description,
      issue_date: form.issue_date instanceof Date
        ? form.issue_date.toISOString().split('T')[0]
        : form.issue_date,
      rfq_type: form.rfq_type,
      currency: form.currency,
      shipping_terms: form.shipping_terms,
      instructions: form.instructions,
      qualification_requirements: form.qualification_requirements,
      items: validItems.map(item => ({
        product_id: item.product_id,
        variation_id: item.variation_id || null,
        quantity: item.quantity,
        specifications: item.specifications || '',
        requirements: item.requirements || ''
      })),
      supplier_ids: selectedSupplierIds.value
    }

    console.log('RFQ Payload:', JSON.stringify(payload, null, 2))

    const rfqResponse = isEditMode.value && editingRfqId.value
      ? await procurementService.updateRFQ(editingRfqId.value, payload as any)
      : await procurementService.createRFQ(payload as any)

    if (!rfqResponse?.success) {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: rfqResponse?.message || 'Failed to create RFQ',
        life: 3000
      })
      return
    }

    const rfqId = rfqResponse.data?.id || editingRfqId.value

    // Send to suppliers
    const sendResponse = await procurementService.sendRfq(rfqId, {
      supplier_ids: selectedSupplierIds.value,
      invitation_method: form.invitation_method,
    })

    if (sendResponse?.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
      detail: isEditMode.value ? 'RFQ updated and sent to suppliers successfully' : 'RFQ created and sent to suppliers successfully',
        life: 3000
      })

      setTimeout(() => {
        router.push({ name: 'procurement.rfqs.detail', params: { id: rfqId } })
      }, 1500)
    } else {
      toast.add({
        severity: 'warn',
        summary: 'Partial Success',
      detail: isEditMode.value ? 'RFQ updated but failed to send to some suppliers' : 'RFQ created but failed to send to some suppliers',
        life: 3000
      })

      setTimeout(() => {
        router.push({ name: 'procurement.rfqs.detail', params: { id: rfqId } })
      }, 1500)
    }
  } catch (error: any) {
    console.error('Failed to create RFQ:', error)

    // Handle validation errors from backend
    if (error.response?.data?.errors) {
      const backendErrors = error.response.data.errors
      Object.entries(backendErrors).forEach(([key, value]: [string, any]) => {
        errors[key] = Array.isArray(value) ? value[0] : value
      })
    }

    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || error.message || 'Failed to create RFQ',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

// Load data on mount
onMounted(async () => {
  loading.value = true
  try {
    // Load products and suppliers in parallel
    const [productsRes, suppliersRes] = await Promise.all([
      procurementService.getProcurementProducts({ per_page: 1000 }).catch(err => {
        console.error('Failed to load products:', err)
        return null
      }),
      procurementService.getSuppliers({ per_page: 1000 }).catch(err => {
        console.error('Failed to load suppliers:', err)
        return null
      })
    ])

    // Process products
    if (productsRes?.data) {
      const productList = productsRes.data.data || (Array.isArray(productsRes.data) ? productsRes.data : [])
      products.value = productList.map((product: any) => ({
        id: product.id,
        product_name: product.product_name || 'Unknown Product',
        sku: product.sku || '',
        brand: product.brand || '',
      }))
    } else {
      toast.add({
        severity: 'warn',
        summary: 'Warning',
        detail: 'Failed to load products',
        life: 3000
      })
    }

    // Process suppliers - handle both paginated and direct array responses
    if (suppliersRes?.data) {
      let supplierList = suppliersRes.data.data || (Array.isArray(suppliersRes.data) ? suppliersRes.data : [])

      // Map suppliers to ensure consistent structure with name and email fields
      suppliers.value = supplierList.map((supplier: any) => ({
        id: supplier.id,
        name: supplier.supplier_name || supplier.name || `${supplier.fname || ''} ${supplier.lname || ''}`.trim(),
        email: supplier.email || '',
        ...supplier // Include all other properties
      }))
    } else {
      toast.add({
        severity: 'warn',
        summary: 'Warning',
        detail: 'Failed to load suppliers',
        life: 3000
      })
    }

    if (route.query.requisition_id) {
      const requisitionId = parseInt(route.query.requisition_id as string)
      const requisitionRes = await procurementService.getPurchaseRequisition(requisitionId).catch(() => null)
      const requisition = requisitionRes?.data || requisitionRes?.data?.data || requisitionRes
      prefillFromRequisition(requisition)
    }

    if (route.query.rfq_id) {
      const rfqId = parseInt(route.query.rfq_id as string)
      if (!isNaN(rfqId)) {
        editingRfqId.value = rfqId
        await loadRfqForEdit(rfqId)
      }
    }
  } catch (error) {
    console.error('Failed to load data:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load required data. Please refresh the page.',
      life: 3000
    })
  } finally {
    loading.value = false
  }
})
</script>







