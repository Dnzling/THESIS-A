<template>
  <div class="max-w-6xl mx-auto pb-6">
    <div class="flex items-center gap-3 mb-6">
      <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'procurement.purchase-requisitions' })" />
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Create Purchase Requisition</h2>
        <p class="text-sm text-gray-500 mt-1">Fill in the details to create a requisition</p>
      </div>
    </div>

    <Card class="mb-6">
      <template #header>
        <div class="px-6 pt-6">
          <h3 class="text-lg font-semibold text-gray-800">Basic Information</h3>
          <p class="text-sm text-gray-500 mt-1">Enter requisition header details</p>
        </div>
      </template>
      <template #content>
        <form class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700"><span class="text-red-500">*</span> Branch</label>
            <Select v-model="form.branch_id" :options="branches" optionLabel="name" optionValue="id"
                placeholder="Select branch" filter :invalid="errors.branch_id !== undefined" fluid />
              <small class="text-red-500" v-if="errors.branch_id">{{ errors.branch_id }}</small>
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700"><span class="text-red-500">*</span> Request Type</label>
              <Select v-model="form.requisition_type" :options="requisitionTypes" optionLabel="label" optionValue="value"
                placeholder="Select type" :invalid="errors.requisition_type !== undefined" fluid />
              <small class="text-red-500" v-if="errors.requisition_type">{{ errors.requisition_type }}</small>
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Priority</label>
              <Select v-model="form.priority" :options="priorityOptions" optionLabel="label" optionValue="value" fluid />
            </div>
          </div>
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700"><span class="text-red-500">*</span> Reason</label>
            <Textarea v-model="form.reason" placeholder="Describe the reason for this requisition" rows="3" :invalid="errors.reason !== undefined" />
            <small class="text-red-500" v-if="errors.reason">{{ errors.reason }}</small>
          </div>
        </form>
      </template>
    </Card>

    <Card class="mb-6">
      <template #header>
        <div class="px-6 pt-6">
          <h3 class="text-lg font-semibold text-gray-800">Line Items</h3>
          <p class="text-sm text-gray-500 mt-1">Add products/services you need</p>
        </div>
      </template>
      <template #content>
        <div class="space-y-4">
          <DataTable :value="form.items" stripedRows responsiveLayout="scroll" class="mb-4">
            <Column field="product_name" header="Product">
              <template #body="slotProps">
                <div class="flex justify-between items-center" v-if="slotProps.index < form.items.length">
                  <Select v-if="!slotProps.data.product_id" :options="products" optionLabel="product_name"
                    optionValue="id" placeholder="Select product" filter fluid
                    @change="selectProduct(slotProps.index, $event)" class="w-full" />
                  <span v-else>{{ slotProps.data.product_name }}</span>
                  <Button icon="pi pi-trash" text severity="danger" @click="removeItem(slotProps.index)" />
                </div>
              </template>
            </Column>
            <Column field="quantity_requested" header="Qty" style="width: 100px">
              <template #body="slotProps">
                <InputNumber v-model="slotProps.data.quantity_requested" :useGrouping="false" :min="1" />
              </template>
            </Column>
            <Column field="estimated_unit_cost" header="Est. Unit Cost" style="width: 130px">
              <template #body="slotProps">
                <InputNumber v-model="slotProps.data.estimated_unit_cost" :useGrouping="false" :minFractionDigits="2" :maxFractionDigits="2" fluid />
              </template>
            </Column>
            <Column field="specifications" header="Specifications">
              <template #body="slotProps">
                <InputText v-model="slotProps.data.specifications" placeholder="Special requirements" />
              </template>
            </Column>
          </DataTable>
          <Button label="Add Line Item" icon="pi pi-plus" @click="addItem" severity="secondary" text />
          <div class="text-red-500 text-sm" v-if="errors.items">{{ errors.items }}</div>
        </div>
      </template>
    </Card>

    <Card class="mb-6">
      <template #header>
        <div class="px-6 pt-6">
          <h3 class="text-lg font-semibold text-gray-800">Review & Submit</h3>
          <p class="text-sm text-gray-500 mt-1">Review details before submission</p>
        </div>
      </template>
      <template #content>
        <div class="space-y-6">
          <div class="border rounded-lg p-4 bg-blue-50">
            <h4 class="font-semibold mb-3 text-blue-900">📋 PR Details</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
              <div>
                <p class="text-gray-600 font-medium">Branch</p>
                <p class="text-gray-900">{{ branches.find(b => b.id === form.branch_id)?.name || 'N/A' }}</p>
              </div>
              <div>
                <p class="text-gray-600 font-medium">Type</p>
                <p class="text-gray-900">{{ capitalizeWords(form.requisition_type) }}</p>
              </div>
              <div>
                <p class="text-gray-600 font-medium">Priority</p>
                <p class="text-gray-900">{{ priorityOptions.find(p => p.value === form.priority)?.label }}</p>
              </div>
            </div>
            <div class="mt-3 p-3 bg-white rounded border border-blue-200">
              <p class="text-gray-600 font-medium mb-1">Reason</p>
              <p class="text-gray-900">{{ form.reason || 'None' }}</p>
            </div>
          </div>
          <div class="space-y-3">
            <h4 class="font-semibold text-gray-800">📦 Line Items ({{ validItems.length }} items)</h4>
            <div v-for="(item, index) in validItems" :key="index" class="p-4 border rounded-lg bg-orange-50">
              <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div>
                  <p class="text-xs text-gray-600 font-semibold">Product</p>
                  <p class="font-semibold text-gray-900">{{ item.product_name }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-600 font-semibold">Quantity</p>
                  <p class="text-2xl font-bold text-orange-600">{{ item.quantity_requested }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-600 font-semibold">Est. Unit Cost</p>
                  <p class="font-semibold text-gray-900">{{ item.estimated_unit_cost?.toFixed(2) || '0.00' }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-600 font-semibold">Total</p>
                  <p class="text-lg font-bold text-orange-700">{{ ((item.quantity_requested || 0) * (item.estimated_unit_cost || 0)).toFixed(2) }}</p>
                </div>
              </div>
            </div>
            <div class="p-4 bg-gradient-to-r from-orange-100 to-yellow-100 rounded-lg border border-orange-300">
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                  <p class="text-sm text-gray-600">Estimated Total</p>
                  <p class="text-2xl font-bold text-orange-700">{{ estimatedTotal.toFixed(2) }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Procurement Route</p>
                  <p class="font-semibold text-gray-900 capitalize">{{ procurementRoute }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Required Approvals</p>
                  <p class="font-semibold text-gray-900">{{ requiredApprovals.length }} levels</p>
                </div>
                <div v-if="requiredApprovals.length > 0">
                  <p class="text-sm text-gray-600">Approval Chain</p>
                  <div class="text-xs space-y-1">
                    <p v-for="approval in requiredApprovals" :key="approval" class="text-gray-900">• {{ capitalizeWords(approval) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="flex gap-2">
            <Checkbox v-model="confirmDetails" :binary="true" />
            <label class="text-sm">I confirm all details are correct</label>
          </div>
        </div>
      </template>
    </Card>

    <div class="flex justify-end gap-2">
      <Button label="Cancel" severity="secondary" text @click="router.push({ name: 'procurement.purchase-requisitions' })" />
      <Button label="Save as Draft" icon="pi pi-save" severity="warning" @click="saveDraft" :loading="saving" />
      <Button label="Create & Submit" icon="pi pi-send" iconPos="right" @click="submitForm" :loading="saving" :disabled="!confirmDetails || validItems.length === 0" />
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
const confirmDetails = ref(false)

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
  items: [{ product_id: null, quantity_requested: 1, estimated_unit_cost: 0, specifications: '', product_name: '' }],
})

const errors = reactive<any>({})
const branches = ref<any[]>([])
const products = ref<any[]>([])

const validItems = computed(() => form.items.filter((i: any) => i.product_id))
const estimatedTotal = computed(() => {
  return form.items.reduce((sum: number, item: any) => {
    return sum + ((item.quantity_requested || 0) * (item.estimated_unit_cost || 0))
  }, 0)
})

const procurementRoute = computed(() => {
  if (estimatedTotal.value >= 500000) return 'RFQ Required'
  if (estimatedTotal.value >= 100000) return 'Centralized'
  return 'Branch Direct'
})

const requiredApprovals = computed(() => {
  const approvals: string[] = ['warehouse_manager']
  if (estimatedTotal.value >= 100000) approvals.push('branch_manager')
  if (estimatedTotal.value >= 500000) approvals.push('finance_manager')
  return approvals
})

const capitalizeWords = (str: string): string => {
  return str.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

const validateForm = (): boolean => {
  Object.keys(errors).forEach(key => delete errors[key])

  if (!form.branch_id) { errors.branch_id = 'Branch is required' }
  if (!form.requisition_type) { errors.requisition_type = 'Type is required' }
  if (!form.reason || form.reason.trim() === '') { errors.reason = 'Reason is required' }
  if (validItems.value.length === 0) { errors.items = 'At least one item is required' }

  return Object.keys(errors).length === 0
}

const addItem = () => {
  form.items.push({ product_id: null, quantity_requested: 1, estimated_unit_cost: 0, specifications: '', product_name: '' })
}

const removeItem = (index: number) => {
  form.items.splice(index, 1)
}

const selectProduct = (index: number, event: any) => {
  const product = products.value.find(p => p.id === event.value)
  if (product && form.items[index]) {
    form.items[index].product_id = product.id
    form.items[index].product_name = product.product_name
    form.items[index].estimated_unit_cost = parseFloat(product.base_price) || 0
  }
}

const prefillFromInventoryItem = (item: any) => {
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

  form.items = [{
    product_id: productId || null,
    quantity_requested: requestedQty,
    estimated_unit_cost: basePrice,
    specifications: '',
    product_name: productName
  }]
}

const saveDraft = async () => {
  saving.value = true
  try {
    if (!validateForm()) {
      toast.add({ severity: 'error', summary: 'Error', detail: 'Please complete required fields', life: 3000 })
      saving.value = false
      return
    }

    const response = await procurementService.createPurchaseRequisition({
      branch_id: form.branch_id,
      requisition_type: form.requisition_type,
      reason: form.reason,
      priority: form.priority,
      items: validItems.value,
    })

    if (response.success) {
      toast.add({ severity: 'success', summary: 'Success', detail: 'PR saved as draft', life: 3000 })
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

  if (!confirmDetails.value) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Confirm all details before submitting', life: 3000 })
    return
  }

  saving.value = true
  try {
    const response = await procurementService.createPurchaseRequisition({
      branch_id: form.branch_id,
      requisition_type: form.requisition_type,
      reason: form.reason,
      priority: form.priority,
      items: validItems.value,
    })

    if (response.success && response.data?.id) {
      const submitResponse = await procurementService.submitPurchaseRequisition(response.data.id)
      if (submitResponse.success) {
        toast.add({ severity: 'success', summary: 'Success', detail: 'PR created and submitted successfully', life: 3000 })
        setTimeout(() => router.push({ name: 'procurement.purchase-requisitions.detail', params: { id: response.data.id } }), 1500)
      }
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

    if (route.query.branch_inventory_id) {
      const inventoryId = parseInt(route.query.branch_inventory_id as string)
      const inventoryResponse = await inventoryService.getInventoryItem(inventoryId).catch(() => null)
      const inventoryItem = inventoryResponse?.data || inventoryResponse?.data?.data || inventoryResponse
      prefillFromInventoryItem(inventoryItem)
    } else if (route.query.product_id || route.query.branch_id) {
      const productId = route.query.product_id ? parseInt(route.query.product_id as string) : null
      const branchId = route.query.branch_id ? parseInt(route.query.branch_id as string) : null
      if (branchId) form.branch_id = branchId
      if (productId) {
        const product = products.value.find(p => p.id === productId)
        if (product) {
          form.items = [{
            product_id: product.id,
            quantity_requested: 1,
            estimated_unit_cost: parseFloat(product.base_price) || 0,
            specifications: '',
            product_name: product.product_name
          }]
        }
      }
    }
  } catch (error) {
    console.error('Failed to load data:', error)
  }
})
</script>
