<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-4xl mx-auto">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Edit Product</h1>
        <p class="text-gray-600 mt-1">Update product information</p>
      </div>

      <div v-if="loading" class="flex justify-center py-12">
        <ProgressSpinner />
      </div>

      <Card v-else-if="product">
        <template #content>
          <form @submit.prevent="submitForm" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Basic Information -->
              <div class="md:col-span-2">
                <h3 class="text-lg font-semibold mb-4">Basic Information</h3>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">SKU *</label>
                <InputText
                  v-model="form.sku"
                  placeholder="Enter SKU"
                  class="w-full"
                  :class="{ 'p-invalid': errors.sku }"
                />
                <small v-if="errors.sku" class="p-error">{{ errors.sku[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                <InputText
                  v-model="form.name"
                  placeholder="Enter product name"
                  class="w-full"
                  :class="{ 'p-invalid': errors.name }"
                />
                <small v-if="errors.name" class="p-error">{{ errors.name[0] }}</small>
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <Textarea
                  v-model="form.description"
                  placeholder="Enter product description"
                  class="w-full"
                  rows="3"
                  :class="{ 'p-invalid': errors.description }"
                />
                <small v-if="errors.description" class="p-error">{{ errors.description[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                <Select
                  v-model="form.category_id"
                  :options="categories"
                  optionLabel="name"
                  optionValue="id"
                  placeholder="Select category"
                  class="w-full"
                  :class="{ 'p-invalid': errors.category_id }"
                  showClear
                />
                <small v-if="errors.category_id" class="p-error">{{ errors.category_id[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Unit *</label>
                <Select
                  v-model="form.unit_id"
                  :options="units"
                  optionLabel="name"
                  optionValue="id"
                  placeholder="Select unit"
                  class="w-full"
                  :class="{ 'p-invalid': errors.unit_id }"
                  showClear
                />
                <small v-if="errors.unit_id" class="p-error">{{ errors.unit_id[0] }}</small>
              </div>

              <!-- Pricing -->
              <div class="md:col-span-2">
                <h3 class="text-lg font-semibold mb-4">Pricing</h3>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Base Price *</label>
                <InputNumber
                  v-model="form.base_price"
                  mode="currency"
                  currency="USD"
                  locale="en-US"
                  placeholder="0.00"
                  class="w-full"
                  :class="{ 'p-invalid': errors.base_price }"
                />
                <small v-if="errors.base_price" class="p-error">{{ errors.base_price[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Cost Price</label>
                <InputNumber
                  v-model="form.cost_price"
                  mode="currency"
                  currency="USD"
                  locale="en-US"
                  placeholder="0.00"
                  class="w-full"
                  :class="{ 'p-invalid': errors.cost_price }"
                />
                <small v-if="errors.cost_price" class="p-error">{{ errors.cost_price[0] }}</small>
              </div>

              <!-- Inventory Settings -->
              <div class="md:col-span-2">
                <h3 class="text-lg font-semibold mb-4">Inventory Settings</h3>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Stock Level</label>
                <InputNumber
                  v-model="form.min_stock_level"
                  placeholder="0"
                  class="w-full"
                  :class="{ 'p-invalid': errors.min_stock_level }"
                />
                <small v-if="errors.min_stock_level" class="p-error">{{ errors.min_stock_level[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Maximum Stock Level</label>
                <InputNumber
                  v-model="form.max_stock_level"
                  placeholder="0"
                  class="w-full"
                  :class="{ 'p-invalid': errors.max_stock_level }"
                />
                <small v-if="errors.max_stock_level" class="p-error">{{ errors.max_stock_level[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Reorder Point</label>
                <InputNumber
                  v-model="form.reorder_point"
                  placeholder="0"
                  class="w-full"
                  :class="{ 'p-invalid': errors.reorder_point }"
                />
                <small v-if="errors.reorder_point" class="p-error">{{ errors.reorder_point[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Reorder Quantity</label>
                <InputNumber
                  v-model="form.reorder_quantity"
                  placeholder="0"
                  class="w-full"
                  :class="{ 'p-invalid': errors.reorder_quantity }"
                />
                <small v-if="errors.reorder_quantity" class="p-error">{{ errors.reorder_quantity[0] }}</small>
              </div>

              <!-- Status -->
              <div class="md:col-span-2">
                <h3 class="text-lg font-semibold mb-4">Status</h3>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                <Select
                  v-model="form.status"
                  :options="statusOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Select status"
                  class="w-full"
                  :class="{ 'p-invalid': errors.status }"
                />
                <small v-if="errors.status" class="p-error">{{ errors.status[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Track Serial Numbers</label>
                <Select
                  v-model="form.track_serial_numbers"
                  :options="booleanOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Select option"
                  class="w-full"
                  :class="{ 'p-invalid': errors.track_serial_numbers }"
                />
                <small v-if="errors.track_serial_numbers" class="p-error">{{ errors.track_serial_numbers[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Track Batches</label>
                <Select
                  v-model="form.track_batches"
                  :options="booleanOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Select option"
                  class="w-full"
                  :class="{ 'p-invalid': errors.track_batches }"
                />
                <small v-if="errors.track_batches" class="p-error">{{ errors.track_batches[0] }}</small>
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
                label="Update Product"
                :loading="loading"
                class="bg-blue-600 hover:bg-blue-700"
              />
            </div>
          </form>
        </template>
      </Card>

      <div v-else class="text-center py-12">
        <p class="text-gray-500">Product not found</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter, useRoute } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'

const loading = ref(false)
const categories = ref<any[]>([])
const units = ref<any[]>([])
const errors = ref<any>({})
const product = ref<any>(null)
const toast = useToast()
const router = useRouter()
const route = useRoute()

const form = reactive({
  sku: '',
  name: '',
  description: '',
  category_id: null as number | null,
  unit_id: null as number | null,
  base_price: null as number | null,
  cost_price: null as number | null,
  min_stock_level: null as number | null,
  max_stock_level: null as number | null,
  reorder_point: null as number | null,
  reorder_quantity: null as number | null,
  status: 'active',
  track_serial_numbers: false,
  track_batches: false
})

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Discontinued', value: 'discontinued' }
]

const booleanOptions = [
  { label: 'Yes', value: true },
  { label: 'No', value: false }
]

const loadProduct = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getProduct(route.params.id as string)
    if (response.success) {
      product.value = response.data
      populateForm()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Failed to load product details',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load product details',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadCategories = async () => {
  try {
    const response = await inventoryService.getCategories()
    if (response.success) {
      categories.value = response.data || []
    }
  } catch (error) {
    console.error('Failed to load categories', error)
  }
}

const loadUnits = async () => {
  try {
    const response = await inventoryService.getUnits()
    if (response.success) {
      units.value = response.data || []
    }
  } catch (error) {
    console.error('Failed to load units', error)
  }
}

const populateForm = () => {
  if (!product.value) return

  form.sku = product.value.sku || ''
  form.name = product.value.name || ''
  form.description = product.value.description || ''
  form.category_id = product.value.category_id || null
  form.unit_id = product.value.unit_id || null
  form.base_price = product.value.base_price || null
  form.cost_price = product.value.cost_price || null
  form.min_stock_level = product.value.min_stock_level || null
  form.max_stock_level = product.value.max_stock_level || null
  form.reorder_point = product.value.reorder_point || null
  form.reorder_quantity = product.value.reorder_quantity || null
  form.status = product.value.status || 'active'
  form.track_serial_numbers = product.value.track_serial_numbers || false
  form.track_batches = product.value.track_batches || false
}

const submitForm = async () => {
  loading.value = true
  errors.value = {}

  try {
    const response = await inventoryService.updateProduct(product.value.id, form)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Product updated successfully',
        life: 3000
      })
      router.push({ name: 'inventory.products.detail', params: { id: product.value.id } })
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
        detail: error.response?.data?.message || 'Failed to update product',
        life: 3000
      })
    }
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'inventory.products.detail', params: { id: product.value.id } })
}

onMounted(async () => {
  await loadProduct()
  loadCategories()
  loadUnits()
})
</script>