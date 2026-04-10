<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="mb-6 flex items-center gap-4">
      <Button icon="pi pi-arrow-left" text rounded severity="secondary" @click="router.back()" v-tooltip="'Go back'" />
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Create Stock Order Request</h1>
        <p class="text-gray-600 mt-1">Request inventory for low stock or out of stock items</p>
      </div>
    </div>
  
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Form Card -->
      <Card class="lg:col-span-2">
        <template #content>
          <form @submit.prevent="submitForm" class="space-y-6">
            <!-- Product Section -->
            <div class="pb-6 border-b">
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Product Information</h3>
  
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">Product</label>
                  <Select v-model="form.product_id" :options="products" optionLabel="product_name" optionValue="id"
                    placeholder="Select product" class="w-full" @change="onProductChange" :loading="loadingProducts"
                    showClear filter />
                  <small class="text-red-500" v-if="errors.product_id">{{ errors.product_id }}</small>
                </div>
  
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">SKU</label>
                  <InputText :modelValue="selectedProduct?.sku" :disabled="true" class="w-full"
                    placeholder="Auto-filled" />
                </div>
              </div>
  
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">Current Stock</label>
                  <InputText v-model="currentStock" :disabled="true" class="w-full" placeholder="N/A" />
                </div>
  
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">Reorder Point</label>
                  <InputText v-model="reorderPoint" :disabled="true" class="w-full" placeholder="N/A" />
                </div>
              </div>
            </div>
  
            <!-- Request Details Section -->
            <div class="pb-6 border-b">
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Request Details</h3>
  
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">Branch</label>
                  <Select v-model="form.branch_id" :options="branches" optionLabel="name" optionValue="id"
                    placeholder="Select branch" class="w-full" showClear />
                  <small class="text-red-500" v-if="errors.branch_id">{{ errors.branch_id }}</small>
                </div>
  
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">Quantity Requested <span
                      class="text-red-500">*</span></label>
                  <InputNumber v-model="form.quantity_requested" :min="1" placeholder="Enter quantity" class="w-full"
                    @keyup.enter="submitForm" />
                  <small class="text-red-500" v-if="errors.quantity_requested">{{ errors.quantity_requested }}</small>
                </div>
              </div>
  
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">Priority</label>
                  <Select v-model="form.priority" :options="priorities" optionLabel="label" optionValue="value"
                    placeholder="Select priority" class="w-full" showClear />
                </div>
  
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">Expected Delivery Date</label>
                  <DatePicker v-model="form.expected_delivery_date" dateFormat="dd/mm/yy" :minDate="today" showIcon
                    fluid />
                </div>
              </div>
  
              <div class="space-y-2 mt-4">
                <label class="block text-sm font-medium text-gray-700">Reason</label>
                <Textarea v-model="form.reason" rows="3"
                  placeholder="Explain why this stock is needed (e.g., Low stock, High demand, New branch opening)"
                  class="w-full" />
                <small class="text-red-500" v-if="errors.reason">{{ errors.reason }}</small>
              </div>
  
              <div class="space-y-2 mt-4">
                <label class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                <Textarea v-model="form.notes" rows="2" placeholder="Any additional notes or special requirements"
                  class="w-full" />
              </div>
            </div>
  
            <!-- Form Actions -->
            <div class="flex gap-3 justify-end pt-4">
              <Button label="Cancel" severity="secondary" @click="router.back()" :disabled="submitting" />
              <Button label="Create Request" icon="pi pi-check" @click="submitForm" :loading="submitting"
                :disabled="submitting" />
            </div>
          </form>
        </template>
      </Card>
  
      <!-- Summary Card -->
      <Card>
        <template #header>
          <div class="text-lg font-semibold text-gray-800 p-4">Request Summary</div>
        </template>
        <template #content>
          <div class="space-y-4">
            <div class="bg-blue-50 border border-blue-200 rounded p-4">
              <div class="text-sm text-gray-600">Product</div>
              <div class="text-lg font-semibold text-gray-800 mt-1">
                {{ selectedProduct?.product_name || 'Not selected' }}
              </div>
              <div class="text-sm text-gray-500 mt-1">SKU: {{ selectedProduct?.sku || 'N/A' }}</div>
            </div>
  
            <div class="bg-amber-50 border border-amber-200 rounded p-4">
              <div class="text-sm text-gray-600">Quantity to Request</div>
              <div class="text-lg font-semibold text-gray-800 mt-1">
                {{ form.quantity_requested || '0' }} units
              </div>
            </div>
  
            <div class="bg-green-50 border border-green-200 rounded p-4">
              <div class="text-sm text-gray-600">Priority</div>
              <div class="mt-2">
                <Tag :value="getPriorityLabel(form.priority)" :severity="getPrioritySeverity(form.priority)" />
              </div>
            </div>
  
            <Button label="Create Request" icon="pi pi-plus-circle" class="w-full mt-4" @click="submitForm"
              :loading="submitting" :disabled="submitting || !form.product_id || !form.quantity_requested" />
          </div>
        </template>
      </Card>
    </div>
  
    <Toast />
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter, useRoute } from 'vue-router'
import procurementService from '../../../../services/procurement.service'
import inventoryService from '../../../../services/inventory.service'

const router = useRouter()
const route = useRoute()
const toast = useToast()

const submitting = ref(false)
const loadingProducts = ref(false)

const products = ref<any[]>([])
const branches = ref<any[]>([])
const inventoryItems = ref<any[]>([])
const selectedProduct = ref<any>(null)
const currentStock = ref<string>('N/A')
const reorderPoint = ref<string>('N/A')

const today = new Date()

const priorities = [
  { label: 'Low', value: 'low' },
  { label: 'Medium', value: 'medium' },
  { label: 'High', value: 'high' },
  { label: 'Urgent', value: 'urgent' }
]

const form = reactive({
  product_id: null as number | null,
  branch_id: null as number | null,
  quantity_requested: null as number | null,
  priority: 'high' as string,
  expected_delivery_date: null as any,
  reason: '' as string,
  notes: '' as string
})

const errors = reactive({
  product_id: '',
  branch_id: '',
  quantity_requested: '',
  reason: ''
})

const getPriorityLabel = (priority: string) => {
  const labels: Record<string, string> = {
    low: 'Low',
    medium: 'Medium',
    high: 'High',
    urgent: 'Urgent'
  }
  return labels[priority] || priority
}

const getPrioritySeverity = (priority: string) => {
  const severities: Record<string, string> = {
    low: 'secondary',
    medium: 'warning',
    high: 'info',
    urgent: 'danger'
  }
  return severities[priority] || 'secondary'
}

const loadProducts = async () => {
  loadingProducts.value = true
  try {
    // Fetch all inventory items which contain both product and inventory data
    const response = await inventoryService.getBranchInventory?.(parseInt(route.query.branch_id as string))

    // Handle different response structures
    let itemsData: any[] = []
    if (Array.isArray(response?.data)) {
      itemsData = response.data
    } else if (Array.isArray(response?.data?.data)) {
      itemsData = response.data.data
    } else if (Array.isArray(response?.data?.items)) {
      itemsData = response.data.items
    } else if (Array.isArray(response)) {
      itemsData = response
    }

    // Store all inventory items for later reference
    inventoryItems.value = itemsData

    // Extract unique products from inventory items and attach inventory data
    const uniqueProducts = new Map()
    itemsData.forEach((item: any) => {
      if (item.product && !uniqueProducts.has(item.product.id)) {
        uniqueProducts.set(item.product.id, {
          ...item.product,
          latestInventory: {
            id: item.id, // Store the branch_inventory_id
            reorder_point: item.reorder_point,
            stock_status: item.stock_status,
            quantity_available: item.quantity_available,
            quantity_reserved: item.quantity_reserved,
            quantity_damaged: item.quantity_damaged,
            quantity_incoming: item.quantity_incoming,
            safety_stock: item.safety_stock,
            reorder_quantity: item.reorder_quantity,
            maximum_stock: item.maximum_stock
          }
        })
      }
    })

    products.value = Array.from(uniqueProducts.values())

    // If product_id is in query params, pre-select it
    if (route.query.product_id) {
      const productId = parseInt(route.query.product_id as string)
      form.product_id = productId
      onProductChange()
    }
  } catch (error: any) {
    console.error('Failed to load products:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load products',
      life: 3000
    })
  } finally {
    loadingProducts.value = false
  }
}

const loadBranches = async () => {
  try {
    const response = await inventoryService.getBranches?.()

    // Handle different response structures
    let branchesData: any[] = []
    if (Array.isArray(response?.data)) {
      branchesData = response.data
    } else if (Array.isArray(response?.data?.data)) {
      branchesData = response.data.data
    } else if (Array.isArray(response?.data?.items)) {
      branchesData = response.data.items
    } else if (Array.isArray(response)) {
      branchesData = response
    }

    branches.value = branchesData

    // If branch_id is in query params, pre-select it
    if (route.query.branch_id) {
      form.branch_id = parseInt(route.query.branch_id as string)
    }
  } catch (error: any) {
    console.error('Failed to load branches:', error)
  }
}


const onProductChange = () => {
  if (form.product_id) {
    const product = products.value.find(p => p.id === form.product_id)
    if (product) {
      selectedProduct.value = product

      // Set inventory details from product's latestInventory
      if (product.latestInventory) {
        const currentQty = product.latestInventory.quantity_available || 0
        const reorderQty = product.latestInventory.reorder_point || 0

        currentStock.value = currentQty.toString()
        reorderPoint.value = reorderQty.toString()

        // Auto-fill quantity requested based on reorder point
        // If reorder_point > current_quantity, fill the difference
        // Otherwise, request the full reorder quantity
        form.quantity_requested = reorderQty > currentQty ? reorderQty - currentQty : reorderQty
      }
    }
  }
}

const validateForm = () => {
  let isValid = true
  errors.product_id = ''
  errors.branch_id = ''
  errors.quantity_requested = ''
  errors.reason = ''

  if (!form.product_id) {
    errors.product_id = 'Product is required'
    isValid = false
  }

  if (!form.branch_id) {
    errors.branch_id = 'Branch is required'
    isValid = false
  }

  if (!form.quantity_requested || form.quantity_requested <= 0) {
    errors.quantity_requested = 'Quantity must be greater than 0'
    isValid = false
  }

  if (!form.reason || form.reason.trim() === '') {
    errors.reason = 'Reason is required'
    isValid = false
  }

  return isValid
}

const submitForm = async () => {
  if (!validateForm()) {
    toast.add({
      severity: 'warn',
      summary: 'Validation Error',
      detail: 'Please fill all required fields',
      life: 3000
    })
    return
  }

  submitting.value = true
  try {
    // Find the matching inventory item for branch_inventory_id
    const inventoryItem = inventoryItems.value.find(
      (item: any) => item.product_id === form.product_id && item.branch_id === form.branch_id
    )

    if (!inventoryItem) {
      throw new Error('Inventory item not found for selected product and branch')
    }

    const payload = {
      branch_inventory_id: inventoryItem.id,
      requested_quantity: form.quantity_requested || 0
    }

    await procurementService.createStockOrderRequest(payload)

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Stock order request created successfully',
      life: 3000
    })

    // Redirect to list after delay
    setTimeout(() => {
      router.push({ name: 'stock-order-requests.index' })
    }, 1500)
  } catch (error: any) {
    console.error('Failed to create stock order request:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || error.message || 'Failed to create stock order request',
    })
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadProducts()
  loadBranches()

  // Pre-fill from query params if available
  if (route.query.branch_id) {
    form.branch_id = parseInt(route.query.branch_id as string)
  }
})
</script>
