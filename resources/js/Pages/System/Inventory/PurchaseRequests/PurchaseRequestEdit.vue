<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="mb-6 flex items-center gap-4">
      <Button icon="pi pi-arrow-left" text rounded severity="secondary" @click="router.back()" v-tooltip="'Go back'" />
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Edit Stock Order Request</h1>
        <p class="text-gray-600 mt-1">Update request details while status is pending</p>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center items-center py-12">
      <ProgressSpinner />
    </div>

    <Message v-if="error" severity="error" :text="error" class="mb-6" />

    <div v-if="!loading" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <Card class="lg:col-span-2">
        <template #content>
          <form @submit.prevent="submitForm" class="space-y-6">
            <div class="pb-6 border-b">
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Product Information</h3>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">Product</label>
                  <Select
                    v-model="form.product_id"
                    :options="products"
                    optionLabel="product_name"
                    optionValue="id"
                    placeholder="Select product"
                    class="w-full"
                    @change="onProductChange(false)"
                    :loading="loadingProducts"
                    showClear
                    filter
                  />
                  <small class="text-red-500" v-if="errors.product_id">{{ errors.product_id }}</small>
                </div>

                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">Branch</label>
                  <Select
                    v-model="form.branch_id"
                    :options="branches"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select branch"
                    class="w-full"
                    showClear
                  />
                  <small class="text-red-500" v-if="errors.branch_id">{{ errors.branch_id }}</small>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">SKU</label>
                  <InputText :modelValue="selectedProduct?.sku" :disabled="true" class="w-full" placeholder="Auto-filled" />
                </div>

                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">Current Stock</label>
                  <InputText v-model="currentStock" :disabled="true" class="w-full" placeholder="N/A" />
                </div>
              </div>
            </div>

            <div class="pb-6 border-b">
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Request Details</h3>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Quantity Requested <span class="text-red-500">*</span>
                  </label>
                  <InputNumber v-model="form.quantity_requested" :min="1" placeholder="Enter quantity" class="w-full" />
                  <small class="text-red-500" v-if="errors.quantity_requested">{{ errors.quantity_requested }}</small>
                </div>

                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">Reorder Point</label>
                  <InputText v-model="reorderPoint" :disabled="true" class="w-full" placeholder="N/A" />
                </div>
              </div>

              <div class="space-y-2 mt-4">
                <label class="block text-sm font-medium text-gray-700">Notes</label>
                <Textarea v-model="form.notes" rows="3" placeholder="Additional notes (optional)" class="w-full" />
              </div>
            </div>

            <div class="flex gap-3 justify-end pt-4">
              <Button label="Cancel" severity="secondary" @click="router.back()" :disabled="submitting" />
              <Button label="Update Request" icon="pi pi-check" @click="submitForm" :loading="submitting" :disabled="submitting" />
            </div>
          </form>
        </template>
      </Card>

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
              <div class="text-sm text-gray-600">Quantity Requested</div>
              <div class="text-lg font-semibold text-gray-800 mt-1">
                {{ form.quantity_requested || '0' }} units
              </div>
            </div>

            <Button
              label="Update Request"
              icon="pi pi-save"
              class="w-full mt-4"
              @click="submitForm"
              :loading="submitting"
              :disabled="submitting || !form.product_id || !form.quantity_requested || !form.branch_id"
            />
          </div>
        </template>
      </Card>
    </div>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'
import inventoryService from '../../../../services/inventory.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const loadingProducts = ref(false)
const submitting = ref(false)
const error = ref('')

const products = ref<any[]>([])
const branches = ref<any[]>([])
const inventoryItems = ref<any[]>([])
const selectedProduct = ref<any>(null)
const currentStock = ref<string>('N/A')
const reorderPoint = ref<string>('N/A')

const requestId = Number(route.params.id)

const form = reactive({
  product_id: null as number | null,
  branch_id: null as number | null,
  quantity_requested: null as number | null,
  notes: '' as string
})

const errors = reactive({
  product_id: '',
  branch_id: '',
  quantity_requested: ''
})

const loadRequest = async () => {
  if (!requestId) {
    error.value = 'Invalid request ID'
    return
  }

  loading.value = true
  error.value = ''
  try {
    const response = await procurementService.getStockOrderRequest(requestId)
    const data = response?.data

    if (!data) {
      error.value = 'Stock order request not found'
      return
    }

    if (data.status !== 'pending') {
      toast.add({
        severity: 'warn',
        summary: 'Not Editable',
        detail: 'Only pending requests can be edited.',
        life: 3000
      })
      router.push({ name: 'stock-order-requests.detail', params: { id: requestId } })
      return
    }

    form.product_id = data.product?.id ?? null
    form.branch_id = data.branch?.id ?? null
    form.quantity_requested = data.requested_quantity ?? null
    form.notes = data.notes ?? ''
  } catch (err: any) {
    console.error('Failed to load request:', err)
    error.value = err.message || 'Failed to load request details'
  } finally {
    loading.value = false
  }
}

const loadProducts = async () => {
  loadingProducts.value = true
  try {
    const response = await inventoryService.getInventoryItems()
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

    inventoryItems.value = itemsData

    const uniqueProducts = new Map()
    itemsData.forEach((item: any) => {
      if (item.product && !uniqueProducts.has(item.product.id)) {
        uniqueProducts.set(item.product.id, {
          ...item.product,
          latestInventory: {
            id: item.id,
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
  } catch (err: any) {
    console.error('Failed to load products:', err)
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
    const response = await inventoryService.getBranches()
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
  } catch (err: any) {
    console.error('Failed to load branches:', err)
  }
}

const onProductChange = (autoFill = false) => {
  if (form.product_id) {
    const product = products.value.find(p => p.id === form.product_id)
    if (product) {
      selectedProduct.value = product

      if (product.latestInventory) {
        const currentQty = product.latestInventory.quantity_available || 0
        const reorderQty = product.latestInventory.reorder_point || 0

        currentStock.value = currentQty.toString()
        reorderPoint.value = reorderQty.toString()

        if (autoFill && !form.quantity_requested) {
          form.quantity_requested = reorderQty > currentQty ? reorderQty - currentQty : reorderQty
        }
      }
    }
  }
}

const validateForm = () => {
  let isValid = true
  errors.product_id = ''
  errors.branch_id = ''
  errors.quantity_requested = ''

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
    const inventoryItem = inventoryItems.value.find(
      (item: any) => item.product_id === form.product_id && item.branch_id === form.branch_id
    )

    if (!inventoryItem) {
      throw new Error('Inventory item not found for selected product and branch')
    }

    const payload = {
      branch_inventory_id: inventoryItem.id,
      requested_quantity: form.quantity_requested || 0,
      notes: form.notes?.trim() || null
    }

    await procurementService.updateStockOrderRequest(requestId, payload)

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Stock order request updated successfully',
      life: 3000
    })

    setTimeout(() => {
      router.push({ name: 'stock-order-requests.detail', params: { id: requestId } })
    }, 800)
  } catch (err: any) {
    console.error('Failed to update stock order request:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || err.message || 'Failed to update stock order request',
    })
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadRequest(), loadProducts(), loadBranches()])
  onProductChange(false)
})
</script>
