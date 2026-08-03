<template>
  <div class="max-w-4xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" severity="secondary" text rounded @click="goBack" />
        <div>
          <h1 class="text-2xl font-bold text-gray-800">{{ isEditMode ? 'Edit Product' : 'Create Product' }}</h1>
          <p class="text-sm text-gray-500">Inventory product entry</p>
        </div>
      </div>
      <Button :label="isEditMode ? 'Update Product' : 'Create Product'" icon="pi pi-check" :loading="submitting" @click="handleSubmit" />
    </div>

    <Card>
      <template #content>
        <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="handleSubmit">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Product Name <span class="text-red-500">*</span></label>
            <InputText v-model="form.product_name" class="w-full" placeholder="e.g. Modern Chair" />
            <small v-if="errors.product_name" class="text-red-500">{{ errors.product_name }}</small>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">SKU <span class="text-red-500">*</span></label>
            <InputText v-model="form.sku" class="w-full" placeholder="Auto-generated or manual" />
            <small v-if="errors.sku" class="text-red-500">{{ errors.sku }}</small>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
            <Select v-model="form.category_id" :options="categories" optionLabel="category_name" optionValue="id" placeholder="Select category" class="w-full" />
            <small v-if="errors.category_id" class="text-red-500">{{ errors.category_id }}</small>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
            <Select v-model="form.product_type" :options="productTypeOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Base Price</label>
            <InputNumber v-model="form.base_price" mode="currency" currency="PHP" locale="en-PH" :min="0" class="w-full" fluid />
            <small v-if="errors.base_price" class="text-red-500">{{ errors.base_price }}</small>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cost Price</label>
            <InputNumber v-model="form.cost_price" mode="currency" currency="PHP" locale="en-PH" :min="0" class="w-full" fluid />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <Textarea v-model="form.description" rows="4" class="w-full" placeholder="Optional notes" />
          </div>

          <div class="md:col-span-2 flex items-center gap-2">
            <Checkbox v-model="form.is_active" :binary="true" inputId="product_active" />
            <label for="product_active" class="text-sm text-gray-700">Active</label>
          </div>

          <div class="md:col-span-2 flex justify-end gap-2 pt-2">
            <Button label="Cancel" severity="secondary" outlined @click="goBack" />
            <Button type="submit" :label="isEditMode ? 'Update Product' : 'Create Product'" icon="pi pi-check" :loading="submitting" />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '../../../../services/inventory.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const submitting = ref(false)
const categories = ref<any[]>([])
const errors = ref<Record<string, string>>({})
const isEditMode = computed(() => Boolean(route.params.id))

const form = reactive({
  product_name: '',
  sku: '',
  category_id: null as number | null,
  product_type: 'finished_good' as 'finished_good' | 'raw_material' | 'supply',
  description: '',
  base_price: null as number | null,
  cost_price: null as number | null,
  is_active: true,
})

const productTypeOptions = [
  { label: 'Finished Good', value: 'finished_good' },
  { label: 'Raw Material', value: 'raw_material' },
  { label: 'Supply', value: 'supply' },
]

const loadCategories = async () => {
  try {
    const response = await inventoryService.getCategories({ active_only: true })
    categories.value = response.data?.data || response.data || []
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load categories', life: 3000 })
  }
}

const loadProduct = async (id: number) => {
  try {
    const response = await inventoryService.getProduct(id)
    const product = response.data
    form.product_name = product.product_name || ''
    form.sku = product.sku || ''
    form.category_id = product.category_id || null
    form.product_type = product.product_type || 'finished_good'
    form.description = product.description || ''
    form.base_price = product.base_price ?? null
    form.cost_price = product.cost_price ?? null
    form.is_active = product.is_active !== false
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load product', life: 3000 })
  }
}

const validate = () => {
  errors.value = {}
  if (!form.product_name) errors.value.product_name = 'Product name is required'
  if (!form.sku) errors.value.sku = 'SKU is required'
  if (!form.category_id) errors.value.category_id = 'Category is required'
  if (form.base_price != null && form.base_price < 0) errors.value.base_price = 'Base price must be 0 or greater'
  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validate()) return

  submitting.value = true
  try {
    const payload = {
      product_name: form.product_name,
      sku: form.sku,
      category_id: Number(form.category_id),
      product_type: form.product_type,
      description: form.description || undefined,
      base_price: form.base_price ?? 0,
      cost_price: form.cost_price ?? undefined,
      is_active: form.is_active,
    }

    if (isEditMode.value) {
      await inventoryService.updateProduct(Number(route.params.id), payload)
      toast.add({ severity: 'success', summary: 'Saved', detail: 'Product updated successfully', life: 3000 })
    } else {
      await inventoryService.createProduct(payload)
      toast.add({ severity: 'success', summary: 'Saved', detail: 'Product created successfully', life: 3000 })
    }

    goBack()
  } catch (error: any) {
    if (error.response?.status === 422) {
      errors.value = error.response.data?.errors || {}
    }
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to save product', life: 4000 })
  } finally {
    submitting.value = false
  }
}

const goBack = () => router.push({ name: 'inventory.products.index' })

onMounted(async () => {
  await loadCategories()
  if (isEditMode.value) {
    await loadProduct(Number(route.params.id))
  }
})
</script>
