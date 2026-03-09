<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-4xl mx-auto">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Edit Stock Issue</h1>
        <p class="text-gray-600 mt-1">Update stock issue information</p>
      </div>

      <div v-if="loading" class="flex justify-center py-12">
        <ProgressSpinner />
      </div>

      <Card v-else-if="stockIssue">
        <template #content>
          <form @submit.prevent="submitForm" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Issue Details -->
              <div class="md:col-span-2">
                <h3 class="text-lg font-semibold mb-4">Issue Details</h3>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type *</label>
                <Select
                  v-model="form.type"
                  :options="typeOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Select issue type"
                  class="w-full"
                  :class="{ 'p-invalid': errors.type }"
                />
                <small v-if="errors.type" class="p-error">{{ errors.type[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Reference Number</label>
                <InputText
                  v-model="form.reference_number"
                  placeholder="Auto-generated if empty"
                  class="w-full"
                  :class="{ 'p-invalid': errors.reference_number }"
                />
                <small v-if="errors.reference_number" class="p-error">{{ errors.reference_number[0] }}</small>
                <small class="text-gray-500 mt-1">Leave empty for auto-generation</small>
              </div>

              <!-- Product Selection -->
              <div class="md:col-span-2">
                <h3 class="text-lg font-semibold mb-4">Product Selection</h3>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Product *</label>
                <Select
                  v-model="form.product_id"
                  :options="products"
                  optionLabel="name"
                  optionValue="id"
                  placeholder="Select product"
                  class="w-full"
                  :class="{ 'p-invalid': errors.product_id }"
                  showClear
                  @change="onProductChange"
                />
                <small v-if="errors.product_id" class="p-error">{{ errors.product_id[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Warehouse *</label>
                <Select
                  v-model="form.warehouse_id"
                  :options="warehouses"
                  optionLabel="name"
                  optionValue="id"
                  placeholder="Select warehouse"
                  class="w-full"
                  :class="{ 'p-invalid': errors.warehouse_id }"
                  showClear
                  @change="onWarehouseChange"
                />
                <small v-if="errors.warehouse_id" class="p-error">{{ errors.warehouse_id[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                <Select
                  v-model="form.location_id"
                  :options="locations"
                  optionLabel="name"
                  optionValue="id"
                  placeholder="Select location (optional)"
                  class="w-full"
                  :class="{ 'p-invalid': errors.location_id }"
                  showClear
                />
                <small v-if="errors.location_id" class="p-error">{{ errors.location_id[0] }}</small>
              </div>

              <!-- Quantity and Cost -->
              <div class="md:col-span-2">
                <h3 class="text-lg font-semibold mb-4">Quantity & Cost</h3>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Quantity *</label>
                <InputNumber
                  v-model="form.quantity"
                  placeholder="0"
                  class="w-full"
                  :class="{ 'p-invalid': errors.quantity }"
                  :max="maxQuantity"
                />
                <small v-if="errors.quantity" class="p-error">{{ errors.quantity[0] }}</small>
                <small v-if="maxQuantity" class="text-gray-500 mt-1">Available: {{ maxQuantity }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Unit Cost</label>
                <InputNumber
                  v-model="form.unit_cost"
                  mode="currency"
                  currency="USD"
                  locale="en-US"
                  placeholder="0.00"
                  class="w-full"
                  :class="{ 'p-invalid': errors.unit_cost }"
                />
                <small v-if="errors.unit_cost" class="p-error">{{ errors.unit_cost[0] }}</small>
                <small class="text-gray-500 mt-1">Cost per unit (optional)</small>
              </div>

              <div class="md:col-span-2">
                <div class="bg-gray-50 p-4 rounded-lg">
                  <div class="flex justify-between items-center">
                    <span class="font-medium">Total Cost:</span>
                    <span class="text-xl font-bold text-red-600">
                      ${{ totalCost.toFixed(2) }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Additional Information -->
              <div class="md:col-span-2">
                <h3 class="text-lg font-semibold mb-4">Additional Information</h3>
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                <Textarea
                  v-model="form.notes"
                  placeholder="Enter any additional notes"
                  class="w-full"
                  rows="3"
                  :class="{ 'p-invalid': errors.notes }"
                />
                <small v-if="errors.notes" class="p-error">{{ errors.notes[0] }}</small>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4 pt-6 border-t">
              <Button
                type="button"
                label="Cancel"
                severity="secondary"
                @click="goBack"
                :disabled="loading"
              />
              <Button
                type="submit"
                label="Update Stock Issue"
                :loading="loading"
                class="bg-blue-600 hover:bg-blue-700"
              />
            </div>
          </form>
        </template>
      </Card>

      <div v-else class="text-center py-12">
        <p class="text-gray-500">Stock issue not found</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter, useRoute } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'

const loading = ref(false)
const products = ref<any[]>([])
const warehouses = ref<any[]>([])
const locations = ref<any[]>([])
const stockLevels = ref<any>({})
const errors = ref<any>({})
const stockIssue = ref<any>(null)
const toast = useToast()
const router = useRouter()
const route = useRoute()

const form = reactive({
  type: '',
  reference_number: '',
  product_id: null as number | null,
  warehouse_id: null as number | null,
  location_id: null as number | null,
  quantity: null as number | null,
  unit_cost: null as number | null,
  notes: ''
})

const typeOptions = [
  { label: 'Sale', value: 'sale' },
  { label: 'Damage', value: 'damage' },
  { label: 'Loss', value: 'loss' },
  { label: 'Transfer', value: 'transfer' },
  { label: 'Return', value: 'return' },
  { label: 'Other', value: 'other' }
]

const maxQuantity = computed(() => {
  if (!form.product_id || !form.warehouse_id) return null
  const key = `${form.product_id}-${form.warehouse_id}`
  return (stockLevels.value[key] || 0) + (stockIssue.value?.quantity || 0) // Add back the original quantity
})

const totalCost = computed(() => {
  if (!form.quantity || !form.unit_cost) return 0
  return form.quantity * form.unit_cost
})

const loadStockIssue = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getStockIssue(route.params.id as string)
    if (response.success) {
      stockIssue.value = response.data
      populateForm()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Failed to load stock issue details',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load stock issue details',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadProducts = async () => {
  try {
    const response = await inventoryService.getProducts({
      status: 'active',
      per_page: 1000
    })
    if (response.success) {
      products.value = response.data || []
    }
  } catch (error) {
    console.error('Failed to load products', error)
  }
}

const loadWarehouses = async () => {
  try {
    const response = await inventoryService.getWarehouses({
      status: 'active'
    })
    if (response.success) {
      warehouses.value = response.data || []
    }
  } catch (error) {
    console.error('Failed to load warehouses', error)
  }
}

const loadLocations = async (warehouseId: number) => {
  try {
    const response = await inventoryService.getLocations({
      warehouse_id: warehouseId,
      status: 'active'
    })
    if (response.success) {
      locations.value = response.data || []
    }
  } catch (error) {
    console.error('Failed to load locations', error)
  }
}

const loadStockLevels = async () => {
  if (!form.product_id) return

  try {
    const response = await inventoryService.getProductStockLevels(form.product_id)
    if (response.success) {
      // Transform stock levels into a lookup object
      const levels: any = {}
      response.data.forEach((level: any) => {
        const key = `${level.product_id}-${level.warehouse_id}`
        levels[key] = level.quantity
      })
      stockLevels.value = levels
    }
  } catch (error) {
    console.error('Failed to load stock levels', error)
  }
}

const populateForm = () => {
  if (!stockIssue.value) return

  form.type = stockIssue.value.type || ''
  form.reference_number = stockIssue.value.reference_number || ''
  form.product_id = stockIssue.value.product_id || null
  form.warehouse_id = stockIssue.value.warehouse_id || null
  form.location_id = stockIssue.value.location_id || null
  form.quantity = stockIssue.value.quantity || null
  form.unit_cost = stockIssue.value.unit_cost || null
  form.notes = stockIssue.value.notes || ''

  // Load locations for the selected warehouse
  if (form.warehouse_id) {
    loadLocations(form.warehouse_id)
  }
}

const onProductChange = () => {
  loadStockLevels()
}

const onWarehouseChange = () => {
  if (form.warehouse_id) {
    loadLocations(form.warehouse_id)
    form.location_id = null // Reset location when warehouse changes
  } else {
    locations.value = []
  }
  loadStockLevels()
}

const submitForm = async () => {
  loading.value = true
  errors.value = {}

  try {
    const response = await inventoryService.updateStockIssue(stockIssue.value.id, form)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Stock issue updated successfully',
        life: 3000
      })
      router.push({ name: 'inventory.stock-issues.detail', params: { id: stockIssue.value.id } })
    } else {
      errors.value = response.errors || {}
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Please check the form for errors',
        life: 3000
      })
    }
  } catch (error: any) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to update stock issue',
        life: 3000
      })
    }
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'inventory.stock-issues.detail', params: { id: stockIssue.value.id } })
}

watch(() => form.product_id, onProductChange)
watch(() => form.warehouse_id, onWarehouseChange)

onMounted(async () => {
  await loadStockIssue()
  loadProducts()
  loadWarehouses()
})
</script>