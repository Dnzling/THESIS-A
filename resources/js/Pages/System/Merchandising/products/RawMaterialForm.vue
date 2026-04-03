<template>
  <div class="max-w-4xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <Button
          icon="pi pi-arrow-left"
          severity="secondary"
          text
          rounded
          @click="router.push({ name: 'merchandising.products' })"
        />
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Create Raw Material</h1>
          <p class="text-sm text-gray-500">Basic information only</p>
        </div>
      </div>
      <Button label="Save Raw Material" icon="pi pi-check" :loading="submitting" @click="handleSubmit" />
    </div>

    <Card>
      <template #content>
        <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="handleSubmit">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Raw Material Name <span class="text-red-500">*</span>
            </label>
            <InputText v-model="form.product_name" class="w-full" placeholder="e.g., Marine Plywood 18mm" @input="generateSku" />
            <small v-if="errors.product_name" class="text-red-500">{{ errors.product_name }}</small>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              SKU <span class="text-red-500">*</span>
            </label>
            <div class="flex gap-2">
              <InputText v-model="form.sku" class="w-full" placeholder="Auto-generated or manual" />
              <Button icon="pi pi-refresh" severity="secondary" outlined @click="generateSku" />
            </div>
            <small v-if="errors.sku" class="text-red-500">{{ errors.sku }}</small>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Category <span class="text-red-500">*</span>
            </label>
            <Select
              v-model="form.category_id"
              :options="categories"
              optionLabel="category_name"
              optionValue="id"
              placeholder="Select category"
              class="w-full"
              :loading="loadingCategories"
              @change="generateSku"
            />
            <small v-if="errors.category_id" class="text-red-500">{{ errors.category_id }}</small>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
            <InputText v-model="form.brand" class="w-full" placeholder="Optional" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Base Price</label>
            <InputNumber v-model="form.base_price" mode="currency" currency="PHP" locale="en-PH" :min="0" class="w-full" fluid />
            <small v-if="errors.base_price" class="text-red-500">{{ errors.base_price }}</small>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cost Price</label>
            <InputNumber v-model="form.cost_price" mode="currency" currency="PHP" locale="en-PH" :min="0" class="w-full" fluid />
            <small v-if="errors.cost_price" class="text-red-500">{{ errors.cost_price }}</small>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <Textarea v-model="form.description" rows="4" class="w-full" placeholder="Optional notes about this raw material" />
          </div>

          <div class="md:col-span-2 flex items-center gap-2">
            <Checkbox v-model="form.is_active" :binary="true" inputId="raw_active" />
            <label for="raw_active" class="text-sm text-gray-700">Active</label>
          </div>

          <div class="md:col-span-2 flex justify-end gap-2 pt-2">
            <Button label="Cancel" severity="secondary" outlined @click="router.push({ name: 'merchandising.products' })" />
            <Button type="submit" label="Save Raw Material" icon="pi pi-check" :loading="submitting" />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import merchandisingService from '../../../../services/merchandising.service'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'

const router = useRouter()
const toast = useToast()

const loadingCategories = ref(false)
const submitting = ref(false)
const categories = ref<any[]>([])
const errors = ref<Record<string, string>>({})

const form = ref({
  product_name: '',
  sku: '',
  category_id: null as number | null,
  brand: '',
  description: '',
  base_price: null as number | null,
  cost_price: null as number | null,
  is_active: true,
})

const getCategoryCode = () => {
  const selected = categories.value.find((c: any) => c.id === form.value.category_id)
  return String(selected?.category_code || selected?.category_name || 'RAW')
    .toUpperCase()
    .replace(/[^A-Z0-9]/g, '')
    .slice(0, 4)
}

const getNameCode = () => {
  const words = String(form.value.product_name || '')
    .trim()
    .split(/\s+/)
    .filter(Boolean)
  if (!words.length) return 'ITEM'
  const code = words.slice(0, 3).map((w) => w[0]).join('')
  return code.toUpperCase()
}

const generateSku = () => {
  const categoryCode = getCategoryCode()
  const nameCode = getNameCode()
  const suffix = String(Date.now()).slice(-4)
  form.value.sku = `${categoryCode}-${nameCode}-${suffix}`
}

const loadCategories = async () => {
  loadingCategories.value = true
  try {
    const response = await merchandisingService.getCategories({ per_page: 500 })
    categories.value = response.data || response || []
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load categories', life: 3000 })
  } finally {
    loadingCategories.value = false
  }
}

const validate = () => {
  errors.value = {}

  if (!form.value.product_name) errors.value.product_name = 'Raw material name is required'
  if (!form.value.sku) errors.value.sku = 'SKU is required'
  if (!form.value.category_id) errors.value.category_id = 'Category is required'

  if (form.value.base_price != null && form.value.base_price < 0) {
    errors.value.base_price = 'Base price must be 0 or greater'
  }

  if (form.value.cost_price != null && form.value.cost_price < 0) {
    errors.value.cost_price = 'Cost price must be 0 or greater'
  }

  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validate()) {
    toast.add({ severity: 'warn', summary: 'Validation Error', detail: 'Please complete required fields', life: 2500 })
    return
  }

  submitting.value = true
  try {
    await merchandisingService.createProduct({
      product_name: form.value.product_name,
      sku: form.value.sku,
      category_id: Number(form.value.category_id),
      product_type: 'raw_material',
      brand: form.value.brand || undefined,
      description: form.value.description || undefined,
      base_price: form.value.base_price ?? undefined,
      cost_price: form.value.cost_price ?? undefined,
      discounted_price: undefined,
      is_featured: false,
      is_new_arrival: false,
      is_bestseller: false,
      is_active: form.value.is_active,
      stock_status: 'In Stock',
    } as any)

    toast.add({ severity: 'success', summary: 'Saved', detail: 'Raw material created successfully', life: 3000 })
    router.push({ name: 'merchandising.products' })
  } catch (error: any) {
    if (error.response?.status === 422) {
      errors.value = error.response.data?.errors || {}
    }
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to save raw material', life: 4000 })
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  await loadCategories()
  generateSku()
})
</script>
