<template>
  <div class="min-h-screen p-4">
    <div class="max-w-7xl mx-auto">
      <div class="mb-4 flex items-center gap-3">
        <Button icon="pi pi-arrow-left" severity="secondary" text @click="router.push({ name: 'procurement.purchase-requisitions' })" />
        <div>
          <h1 class="text-xl font-bold text-gray-800">{{ formTitle }}</h1>
          <p class="text-xs text-gray-500 mt-0.5">Request procurement items for your branch with multiple products.</p>
        </div>
      </div>

      <Card>
        <template #content>
          <form class="space-y-4" @submit.prevent="submitForm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-700">Branch</label>
                <Select v-model="form.branch_id" :options="branches" optionLabel="name" optionValue="id"
                  placeholder="Select branch" filter :invalid="errors.branch_id !== undefined" fluid />
                <small class="text-red-500" v-if="errors.branch_id">{{ errors.branch_id }}</small>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-700">Request Type</label>
                <Select v-model="form.requisition_type" :options="requisitionTypes" optionLabel="label" optionValue="value"
                  placeholder="Select type" :invalid="errors.requisition_type !== undefined" fluid />
                <small class="text-red-500" v-if="errors.requisition_type">{{ errors.requisition_type }}</small>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-700">Priority</label>
                <Select v-model="form.priority" :options="priorityOptions" optionLabel="label" optionValue="value" fluid />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-700">Reason / Notes</label>
                <Textarea v-model="form.reason" rows="2" class="w-full" placeholder="Why do you need this procurement?" />
                <small class="text-red-500" v-if="errors.reason">{{ errors.reason }}</small>
              </div>
            </div>

            <div class="border rounded-lg p-3">
              <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-800">Line Items</h3>
                <Button type="button" label="Add Item" icon="pi pi-plus" size="small" outlined @click="addItem" />
              </div>

              <DataTable :value="form.items" responsiveLayout="scroll" class="text-sm">
                <Column header="Product" >
                  <template #body="slotProps">
                    <div class="flex items-center gap-2">
                      <Select
                        v-if="!slotProps.data.product_id"
                        :options="products"
                        optionLabel="product_name"
                        optionValue="id"
                        filter fluid
                        placeholder="Select product"
                        @change="selectProduct(slotProps.index, $event)"
                        class="flex-1"
                      />
                      <span v-else class="flex-1">{{ slotProps.data.product_name }}</span>
                      <Button
                        type="button"
                        icon="pi pi-refresh"
                        severity="secondary"
                        text
                        size="small"
                        @click="clearProduct(slotProps.index)"
                        v-if="slotProps.data.product_id"
                      />
                    </div>
                  </template>
                </Column>

                <Column header="Supplier" style="min-width: 240px">
                  <template #body="slotProps">
                    <Select
                      v-model="slotProps.data.selected_supplier_id"
                      :options="getSuppliersForItem(slotProps.data)"
                      optionLabel="label"
                      optionValue="value"
                      filter fluid
                      showClear
                      placeholder="Auto-resolve"
                      :disabled="!slotProps.data.product_id"
                      :key="`supplier-${slotProps.data.product_id}`"
                    />
                  </template>
                </Column>

                <Column header="Quantity" style="width: 120px">
                  <template #body="slotProps">
                    <InputNumber v-model="slotProps.data.quantity_requested" :min="1" :useGrouping="false" class="w-full" />
                  </template>
                </Column>

                <!-- Unit cost and tax moved to backend; UI no longer shows editable fields -->

                <Column header="Actions" style="width: 120px">
                  <template #body="slotProps">
                    <div class="flex gap-2">
                      <Button
                        type="button"
                        icon="pi pi-trash"
                        severity="danger"
                        text
                        size="small"
                        :disabled="form.items.length === 1"
                        @click="removeItem(slotProps.index)"
                      />
                    </div>
                  </template>
                </Column>
              </DataTable>

              <small v-if="errors.items" class="p-error mt-2 block">{{ errors.items }}</small>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t">
              <Button type="button" label="Cancel" severity="secondary" size="small" @click="router.push({ name: 'procurement.purchase-requisitions' })" />
              <Button type="button" label="Save Draft" icon="pi pi-save" severity="warning" size="small" @click="saveDraft" :loading="saving" />
              <Button
                type="submit"
                label="Create Request"
                size="small"
                :loading="saving"
                :disabled="validItems.length === 0"
              />
            </div>
          </form>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'
import inventoryService from '../../../../services/inventory.service'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const saving = ref(false)

const requisitionTypes = [
  { label: 'Regular', value: 'regular' },
  { label: 'Urgent', value: 'urgent' },
  { label: 'New Product', value: 'new_product' },
  { label: 'Seasonal', value: 'seasonal' },
  { label: 'Emergency', value: 'emergency' },
]

const priorityOptions = [
  { label: 'Low (5)', value: 5 },
  { label: 'Medium (3)', value: 3 },
  { label: 'High (2)', value: 2 },
  { label: 'Critical (1)', value: 1 },
]

const form = reactive<any>({
  branch_id: null,
  requisition_type: 'regular',
  reason: '',
  priority: 3,
  items: [{ product_id: null, variation_id: null, selected_supplier_id: null, quantity_requested: 1, estimated_unit_cost: 0, tax_rate: 0, specifications: '', product_name: '' }],
})

const errors = reactive<any>({})
const branches = ref<any[]>([])
const products = ref<any[]>([])
const productDetailsMap = ref<Record<number, any>>({})

const validItems = computed(() => form.items.filter((i: any) => i.product_id))

const editingDraftId = ref<number | null>(null)
const isEditingDraft = computed(() => Boolean(editingDraftId.value))
const formTitle = computed(() => isEditingDraft.value ? 'Edit Purchase Requisition' : 'Create Purchase Requisition')
const payloadItems = computed(() => validItems.value.map((item: any) => ({
  product_id: item.product_id,
  variation_id: item.variation_id,
  selected_supplier_id: item.selected_supplier_id || null,
  quantity_requested: item.quantity_requested,
  specifications: item.specifications,
})))

const buildPayload = (autoSubmit = false) => ({
  branch_id: form.branch_id,
  requisition_type: form.requisition_type,
  reason: form.reason,
  priority: form.priority,
  items: payloadItems.value,
  auto_submit: autoSubmit,
})

const validateForm = (): boolean => {
  Object.keys(errors).forEach(key => delete errors[key])

  if (!form.branch_id) { errors.branch_id = 'Branch is required' }
  if (!form.requisition_type) { errors.requisition_type = 'Type is required' }
  if (!form.reason || form.reason.trim() === '') { errors.reason = 'Reason is required' }
  if (validItems.value.length === 0) { errors.items = 'At least one item is required' }

  return Object.keys(errors).length === 0
}

const addItem = () => {
  form.items.push({ product_id: null, variation_id: null, selected_supplier_id: null, quantity_requested: 1, estimated_unit_cost: 0, tax_rate: 0, specifications: '', product_name: '' })
}

const removeItem = (index: number) => {
  form.items.splice(index, 1)
}

const clearProduct = (index: number) => {
  if (form.items[index]) {
    form.items[index].product_id = null
    form.items[index].product_name = ''
    form.items[index].selected_supplier_id = null
    form.items[index].estimated_unit_cost = 0
    form.items[index].tax_rate = 0
  }
}

const selectProduct = async (index: number, eventOrValue: any) => {
  const productId = Number(eventOrValue && typeof eventOrValue === 'object' ? eventOrValue.value ?? eventOrValue : eventOrValue)
  const product = products.value.find(p => Number(p.id) === productId)
  if (product && form.items[index]) {
    console.debug('[PR Create] selectProduct -> productId', productId, 'product', product)
    form.items[index].product_id = product.id
    form.items[index].product_name = product.product_name
    form.items[index].estimated_unit_cost = parseFloat(product.cost_price || product.base_price) || 0
    form.items[index].tax_rate = Number(product.tax_rate ?? 0)
    form.items[index].selected_supplier_id = null

    // Fetch full product details with suppliers and auto-select default
    await hydrateProductById(productId)
    console.debug('[PR Create] after hydrate, productDetailsMap', productDetailsMap.value[productId])
    form.items[index].selected_supplier_id = getDefaultSupplierIdForItem(form.items[index])
    console.debug('[PR Create] selected_supplier_id set to', form.items[index].selected_supplier_id)
  }
}

const getSuppliersForItem = (item: any) => {
  const pid = Number(item.product_id)
  const product = productDetailsMap.value[pid] || products.value.find((p: any) => Number(p.id) === pid)
  const suppliers = Array.isArray(product?.suppliers) ? product.suppliers : []

  return suppliers
    .slice()
    .sort((a: any, b: any) => Number(Boolean(b?.pivot?.is_preferred_supplier)) - Number(Boolean(a?.pivot?.is_preferred_supplier)))
    .map((supplier: any) => ({
      value: supplier.id,
      label: supplier.supplier_name || supplier.company_name || `Supplier #${supplier.id}`,
      isPreferred: Boolean(supplier.pivot?.is_preferred_supplier),
    }))
}

const getDefaultSupplierIdForItem = (item: any): number | null => {
  const options = getSuppliersForItem(item)
  if (options.length === 0) {
    return null
  }

  const preferred = options.find((option: any) => option.isPreferred)
  if (preferred) {
    return Number(preferred.value)
  }

  if (options.length === 1) {
    return Number(options[0].value)
  }

  return null
}

const hydrateProductById = async (productId: number | null) => {
  if (!productId) return
  const pid = Number(productId)
  if (productDetailsMap.value[pid]) return

  try {
    const response = await procurementService.getProcurementProduct(productId, { with_suppliers: true })
    const fullProduct = response?.data || response
    if (fullProduct?.id) {
      // Ensure suppliers array is present; if backend didn't include suppliers, fetch explicitly
      if (!Array.isArray(fullProduct.suppliers) || fullProduct.suppliers.length === 0) {
        try {
          const suppliersRes = await procurementService.getProductSuppliers(fullProduct.id)
          const suppliers = suppliersRes?.data || suppliersRes || []
          fullProduct.suppliers = Array.isArray(suppliers) ? suppliers : suppliers.data || []
        } catch {
          fullProduct.suppliers = fullProduct.suppliers || []
        }
      }
      productDetailsMap.value[Number(fullProduct.id)] = fullProduct
    }
  } catch {
    // Keep using basic product from list when detail endpoint fails
  }
}

const prefillFromInventoryItem = async (item: any) => {
  if (!item) return

  form.branch_id = item.branch_id ?? form.branch_id

  const stockStatus = item.stock_status || ''
  if (stockStatus === 'out_of_stock') {
    form.requisition_type = 'emergency'
    form.priority = 1
  } else if (stockStatus === 'low_stock') {
    form.requisition_type = 'regular'
    form.priority = 3
  }

  if (!form.reason || form.reason.trim() === '') {
    const statusLabel = stockStatus ? stockStatus.replace(/_/g, ' ') : 'low stock'
    form.reason = `Auto-created from ${statusLabel} inventory alert.`
  }

  const requestedQty = item.reorder_quantity || Math.max((item.reorder_point || 0) - (item.quantity_available || 0), 1)
  const productId = item.product_id || item.product?.id
  const productName = item.product?.product_name || ''
  const basePrice = parseFloat(item.product?.base_price || '0') || 0

  if (productId) {
    const existingProduct = products.value.find(p => p.id === productId)
    if (!existingProduct && item.product) {
      products.value.push(item.product)
    }
  }

  const newItem = {
    product_id: productId || null,
    selected_supplier_id: null,
    quantity_requested: requestedQty,
    estimated_unit_cost: basePrice,
    tax_rate: Number(item.product?.tax_rate ?? 0),
    specifications: '',
    product_name: productName
  }

  form.items = [newItem]

  // Hydrate product details and auto-select supplier
  if (productId) {
    await hydrateProductById(productId)
    newItem.selected_supplier_id = getDefaultSupplierIdForItem(newItem)
  }
}

const mapItemToForm = (item: any) => {
  const product = item.product || {}
  ensureProductInList(product)
  return {
    product_id: item.product_id,
    variation_id: item.variation_id ?? null,
    selected_supplier_id: item.selected_supplier_id ?? null,
    quantity_requested: item.quantity_requested,
    estimated_unit_cost: item.estimated_unit_cost ?? 0,
    tax_rate: Number(item.tax_rate ?? 0),
    specifications: item.specifications ?? '',
    product_name: product.product_name || item.product_name || '',
  }
}

const ensureProductInList = (product: any) => {
  if (!product?.id) return
  const exists = products.value.some(p => p.id === product.id)
  if (!exists) {
    products.value.push(product)
  }
}

const loadDraft = async (draftId: number) => {
  try {
    const response = await procurementService.getPurchaseRequisition(draftId)
    const draft = response.data || response
    if (!draft || draft.status !== 'draft') {
      toast.add({ severity: 'warn', summary: 'Unable to edit', detail: 'Only draft requisitions can be edited', life: 3000 })
      return
    }
    editingDraftId.value = draft.id
    form.branch_id = draft.branch_id || form.branch_id
    form.requisition_type = draft.requisition_type || form.requisition_type
    form.reason = draft.reason || form.reason
    form.priority = draft.priority ?? form.priority
    const items = Array.isArray(draft.items) ? draft.items : []
    form.items = items.length > 0
      ? items.map(mapItemToForm)
      : [{ product_id: null, variation_id: null, selected_supplier_id: null, quantity_requested: 1, estimated_unit_cost: 0, tax_rate: 0, specifications: '', product_name: '' }]

    // Hydrate product details for all items to populate supplier dropdowns
    const productIds = form.items.map((item: any) => item.product_id).filter(Boolean)
    await Promise.all(productIds.map((id: number) => hydrateProductById(id)))

    toast.add({ severity: 'info', summary: 'Editing Draft', detail: `You are editing draft #${draft.pr_number || draft.id}`, life: 2500 })
  } catch (error: any) {
    console.error('Unable to load draft:', error)
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load draft', life: 3000 })
  }
}

const saveDraft = async () => {
  saving.value = true
  try {
    if (!validateForm()) {
      toast.add({ severity: 'error', summary: 'Error', detail: 'Please complete required fields', life: 3000 })
      saving.value = false
      return
    }

    const payload = buildPayload(false) // Don't auto-submit drafts
    const response = editingDraftId.value
      ? await procurementService.updatePurchaseRequisition(editingDraftId.value, payload)
      : await procurementService.createPurchaseRequisition(payload)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: editingDraftId.value ? 'Draft updated' : 'PR saved as draft',
        life: 3000,
      })
      if (!editingDraftId.value) {
        editingDraftId.value = response.data?.id
      }
      setTimeout(() => router.push({ name: 'procurement.purchase-requisitions' }), 1500)
    }
  } catch (error: any) {
    console.error('Save error:', error)
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to save', life: 3000 })
  } finally {
    saving.value = false
  }
}

const submitForm = async () => {
  if (!validateForm()) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Please complete required fields', life: 3000 })
    return
  }

  saving.value = true
  try {
    const payload = buildPayload(true) // Auto-submit on create
    const response = editingDraftId.value
      ? await procurementService.updatePurchaseRequisition(editingDraftId.value, { ...payload, auto_submit: false }) // Don't auto-submit on update
      : await procurementService.createPurchaseRequisition(payload)

    if (response.success) {
      const requisitionId = editingDraftId.value || response.data?.id
      if (editingDraftId.value) {
        // Submit the updated draft
        const submitResponse = await procurementService.submitPurchaseRequisition(requisitionId)
        if (submitResponse.success) {
          toast.add({ severity: 'success', summary: 'Success', detail: 'PR updated and submitted successfully', life: 3000 })
        }
      } else {
        toast.add({ severity: 'success', summary: 'Success', detail: 'PR created and submitted successfully', life: 3000 })
      }
      setTimeout(() => router.push({ name: 'procurement.purchase-requisitions.detail', params: { id: requisitionId } }), 1500)
    }
  } catch (error: any) {
    console.error('Submit error:', error)
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to create PR', life: 3000 })
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  try {
    const [branchesRes, productsRes] = await Promise.all([
      procurementService.getBranches({ per_page: 1000 }).catch(() => ({ data: [] })),
      procurementService.getProcurementProducts({ per_page: 1000 }).catch(() => ({ data: [] })),
    ])

    branches.value = branchesRes.data?.data || branchesRes.data || []
    
    if (Array.isArray(productsRes.data)) {
      products.value = productsRes.data
    } else if (productsRes.data?.data) {
      products.value = productsRes.data.data
    }

    if (branches.value.length > 0 && !form.branch_id) {
      form.branch_id = branches.value[0].id
    }

    const draftId = route.query.draft_id ? parseInt(route.query.draft_id as string, 10) : null
    if (draftId) {
      await loadDraft(draftId)
      return
    }

    if (route.query.branch_inventory_id) {
      const inventoryId = parseInt(route.query.branch_inventory_id as string)
      const inventoryResponse = await inventoryService.getInventoryItem(inventoryId).catch(() => null)
      const inventoryItem = inventoryResponse?.data || inventoryResponse?.data?.data || inventoryResponse
      await prefillFromInventoryItem(inventoryItem)
    } else if (route.query.product_id || route.query.branch_id) {
      const productId = route.query.product_id ? parseInt(route.query.product_id as string) : null
      const branchId = route.query.branch_id ? parseInt(route.query.branch_id as string) : null
      if (branchId) form.branch_id = branchId
      if (productId) {
        const product = products.value.find(p => p.id === productId)
        if (product) {
          const newItem = {
            product_id: product.id,
            selected_supplier_id: null,
            quantity_requested: 1,
            estimated_unit_cost: parseFloat(product.base_price) || 0,
            tax_rate: Number(product.tax_rate ?? 0),
            specifications: '',
            product_name: product.product_name
          }
          form.items = [newItem]
          // Hydrate product details and auto-select supplier
          await hydrateProductById(productId)
          newItem.selected_supplier_id = getDefaultSupplierIdForItem(newItem)
        }
      }
    }
  } catch (error) {
    console.error('Failed to load data:', error)
  }
})
</script>
