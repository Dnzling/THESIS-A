<template>
  <div class="max-w-6xl mx-auto space-y-6 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" severity="secondary" text rounded @click="goBack" />
        <div>
          <h1 class="text-lg font-bold text-gray-800">{{ isEditMode ? 'Edit Item' : 'Create Item' }}</h1>
        </div>
      </div>
     
    </div>

    <Card>
      <template #content>
        <form class="grid grid-cols-1 gap-6 lg:grid-cols-[360px_minmax(0,1fr)]" @submit.prevent="handleSubmit">
          <div class="space-y-4 lg:sticky lg:top-6 lg:self-start">
            <div class="rounded-2xl border border-dashed border-orange-200 bg-orange-50/40 p-4">
              <div class="flex items-center justify-between gap-2">
                <div>
                  <p class="text-sm font-semibold text-gray-800">Item Image</p>
                  <p class="text-xs text-gray-500">Simple square preview</p>
                </div>
                <Button
                  label="Upload"
                  icon="pi pi-upload"
                  
                  size="small"
                  class="text-sm"
                  type="button"
                  @click="triggerImagePicker"
                />
              </div>
              <input ref="imageInput" type="file" accept="image/*" class="hidden" @change="onImageSelected" />
              <div class="mt-4 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="aspect-square w-full flex items-center justify-center bg-gray-50">
                  <img
                    v-if="imagePreview"
                    :src="imagePreview"
                    alt="Product preview"
                    class="h-full w-full object-cover"
                  />
                  <div v-else class="flex flex-col items-center gap-2 text-gray-400">
                    <i class="pi pi-image text-4xl"></i>
                    <span class="text-sm">No image selected</span>
                  </div>
                </div>
              </div>
              <div v-if="imageFile && imagePreview" class="mt-3 flex justify-end">
                <Button type="button" label="Remove Photo" icon="pi pi-trash" severity="danger" outlined size="small" @click="openRemovePhotoPopover" />
              </div>
              <p class="mt-2 text-xs text-gray-500">Square preview only for now. Image storage can be connected next.</p>
              <Popover ref="removePhotoPopover">
                <div class="max-w-xs space-y-3">
                  <p class="text-sm font-medium text-gray-800">Remove this selected photo?</p>
                  <p class="text-xs text-gray-500">The photo will not be uploaded when you save this item.</p>
                  <div class="flex justify-end gap-2">
                    <Button type="button" label="Keep Photo" text size="small" @click="removePhotoPopover?.hide()" />
                    <Button type="button" label="Remove" severity="danger" size="small" @click="removeSelectedPhoto" />
                  </div>
                </div>
              </Popover>
            </div>
          </div>

          <div class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">Item Name <span class="text-red-500">*</span></label>
                <InputText v-model="form.product_name" class="w-full text-sm" size="small" placeholder="e.g. Modern Chair" />
                <small v-if="errors.product_name" class="text-red-500">{{ errors.product_name }}</small>
              </div>

               <div class="md:col-span-1">
                <label class="mb-1 block text-sm font-medium text-gray-700">Item Type <span class="text-red-500">*</span></label>
                <Select
                  v-model="form.product_type"
                  :options="productTypeOptions"
                  optionLabel="label"
                  optionValue="value"
                  class="w-full text-sm"
                  placeholder="Select product type"
                  size="small"
                />
                <small v-if="errors.product_type" class="text-red-500">{{ errors.product_type }}</small>
              </div>

              <div class="md:col-span-1">
                <label class="mb-1 block text-sm font-medium text-gray-700">Category <span class="text-red-500">*</span></label>
                <div class="flex items-start gap-2">
                  <Select
                    v-model="form.category_id"
                    :options="categories"
                    optionLabel="category_name"
                    optionValue="id"
                    class="w-full text-sm"
                    placeholder="Select category"
                    filter
                    showClear
                    size="small"
                    :pt="{
                      overlay: { class: 'text-sm' }
                    }"
                  />
  
                  <Button
                    type="button"
                    icon="pi pi-plus"
                    label="New"
                    severity="warn"
                    outlined
                    size="small"
                    class="text-sm"
                    @click="categoryDialogVisible = true"
                  />
                </div>
                <div class="mt-2">
         
                  <small v-if="!selectedCategoryLabel" class="text-xs text-gray-500">Search and select a stored category.</small>
                </div>
                <small v-if="errors.category_id" class="text-red-500">{{ errors.category_id }}</small>
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Unit Measure <span class="text-red-500">*</span></label>
                <Select
                  v-model="form.unit_of_measurement"
                  :options="unitMeasureOptions"
                  class="w-full text-sm"
                  placeholder="Select unit"
                  size="small"
                />
                <small v-if="errors.unit_of_measurement" class="text-red-500">{{ errors.unit_of_measurement }}</small>
              </div>

              <div v-if="!isEditMode">
                <label class="mb-1 block text-sm font-medium text-gray-700">Available Stock</label>
                <InputNumber v-model="form.initial_stock" :min="0" class="w-full text-sm" fluid size="small" />
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Reorder Point</label>
                <InputNumber v-model="form.reorder_point" :min="0" class="w-full text-sm" fluid size="small" placeholder="10" />
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Cost Price</label>
                <InputNumber v-model="form.cost_price" mode="currency" currency="PHP" locale="en-PH" :min="0" class="w-full text-sm" fluid size="small" placeholder="₱0.00" />
              </div>

              <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                <Textarea v-model="form.description" rows="4" class="w-full text-sm" placeholder="Optional notes" />
              </div>

              <div class="md:col-span-2 flex items-center gap-2">
                <Checkbox v-model="form.is_active" :binary="true" inputId="product_active" />
                <label for="product_active" class="text-sm text-gray-700">Active</label>
              </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
              <Button label="Cancel" severity="secondary" outlined size="small" class="text-sm" @click="goBack" />
              <Button
                type="submit"
                :label="isEditMode ? 'Update Item' : 'Create Item'"
                icon="pi pi-check"
                :loading="submitting"
                severity="warn"
                size="small"
                class="text-sm"
              />
            </div>
          </div>
        </form>
      </template>
    </Card>

    <Dialog v-model:visible="categoryDialogVisible" header="Create Category" :modal="true" class="w-full max-w-md">
      <div class="space-y-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Category Name</label>
          <InputText v-model="newCategoryName" class="w-full text-sm" size="small" placeholder="e.g. Table" />
        </div>
        <small v-if="categoryDialogError" class="text-red-500">{{ categoryDialogError }}</small>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text size="small" @click="categoryDialogVisible = false" />
        <Button label="Create" severity="warn" size="small" @click="createCategoryFromDialog" />
      </template>
    </Dialog>

  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '../../../../services/inventory.service'
import { useAuthStore } from '../../../../stores/auth'
import Chip from 'primevue/chip'
import Dialog from 'primevue/dialog'
import Popover from 'primevue/popover'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const submitting = ref(false)
const categories = ref<any[]>([])
const categoryDialogVisible = ref(false)
const newCategoryName = ref('')
const categoryDialogError = ref('')
const selectedCategoryLabel = computed(() => {
  const matched = categories.value.find((category: any) => Number(category.id) === Number(form.category_id))
  return matched?.category_name || ''
})
const errors = ref<Record<string, string>>({})
const submitError = ref('')
const isEditMode = computed(() => Boolean(route.params.id))
const imageInput = ref<HTMLInputElement | null>(null)
const imagePreview = ref<string | null>(null)
const imageFile = ref<File | null>(null)
const removePhotoPopover = ref<any>(null)
const productTypeOptions = [
  { label: 'Product', value: 'finished_good' },
  { label: 'Supply', value: 'supply' },
]
const unitMeasureOptions = ['pcs', 'set', 'piece', 'box', 'kg', 'meter', 'liter', 'pack', 'roll']

const form = reactive({
  product_name: '',
  category_id: null as number | null,
  description: '',
  product_type: 'finished_good',
  cost_price: null as number | null,
  unit_of_measurement: '',
  initial_stock: null as number | null,
  reorder_point: 10 as number | null,
  is_active: true,
})

const loadCategories = async () => {
  try {
    const storeId = authStore.user?.store_id
    const response = await inventoryService.getCategories({
      active_only: true,
      store_id: storeId || undefined
    })
    categories.value = response?.data?.data || response?.data || []
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load categories', life: 3000 })
  }
}

const loadProduct = async (id: number) => {
  try {
    const response = await inventoryService.getProduct(id)
    const product = response.data
    form.product_name = product.product_name || ''
    form.category_id = product.category_id || null
    form.description = product.description || ''
    form.product_type = product.product_type || 'finished_good'
    form.cost_price = product.inventory_cost_price ?? product.cost_price ?? null
    form.unit_of_measurement = product.unit_of_measurement || ''
    form.reorder_point = product.reorder_point ?? 10
    form.is_active = product.is_active !== false
    form.category_id = product.category_id || null
    imagePreview.value = product.primary_3d_model?.url || product.assets?.[0]?.thumbnail_url || null
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load Item', life: 3000 })
  }
}

const toTitleCase = (value: string) => {
  return value
    .trim()
    .toLowerCase()
    .replace(/\b\w/g, (char) => char.toUpperCase())
}

const capitalizePascal = (value: string) => {
  return value
    .trim()
    .toLowerCase()
    .replace(/\s+/g, ' ')
    .split(' ')
    .filter(Boolean)
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

const createCategoryFromDialog = async () => {
  const rawName = capitalizePascal(newCategoryName.value)
  categoryDialogError.value = ''

  if (!rawName) {
    categoryDialogError.value = 'Category name is required.'
    return
  }

  const existing = categories.value.find((category: any) => category.category_name === rawName)
  if (existing) {
    categoryDialogError.value = 'That category already exists in this store.'
    return
  }

  try {
    const response = await inventoryService.createCategory({ category_name: rawName, is_active: true })
    const created = response?.data || response
    await loadCategories()
    form.category_id = Number(created?.id || 0) || null
    newCategoryName.value = ''
    categoryDialogVisible.value = false
    toast.add({ severity: 'success', summary: 'Created', detail: 'Category created successfully', life: 3000 })
  } catch (error: any) {
    categoryDialogError.value =
      error.response?.data?.message ||
      error.response?.data?.error ||
      error.message ||
      'Failed to create category'
  }
}

const clearCategory = (event: Event) => {
  event.stopPropagation()
  form.category_id = null
}

const triggerImagePicker = () => {
  imageInput.value?.click()
}

const onImageSelected = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return
  imageFile.value = file
  imagePreview.value = URL.createObjectURL(file)
}

const openRemovePhotoPopover = (event: MouseEvent) => {
  removePhotoPopover.value?.toggle(event)
}

const removeSelectedPhoto = () => {
  imageFile.value = null
  imagePreview.value = null
  if (imageInput.value) imageInput.value.value = ''
  removePhotoPopover.value?.hide()
}

const validate = () => {
  errors.value = {}
  submitError.value = ''
  if (!form.product_name) errors.value.product_name = 'Item name is required'
  if (!form.category_id) errors.value.category_id = 'Category is required'
  if (!form.product_type) errors.value.product_type = 'Item type is required'
  if (!form.unit_of_measurement) errors.value.unit_of_measurement = 'Unit measure is required'
  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validate()) return

  submitting.value = true
  try {
    const payload = new FormData()
    payload.append('product_name', form.product_name)
    payload.append('category_id', String(Number(form.category_id)))
    if (form.description) payload.append('description', form.description)
    payload.append('product_type', form.product_type)
    if (form.cost_price != null) payload.append('cost_price', String(form.cost_price))
    if (form.unit_of_measurement) payload.append('unit_of_measurement', form.unit_of_measurement)
    if (!isEditMode.value && form.initial_stock != null) {
      payload.append('initial_stock', String(form.initial_stock))
    }
    if (form.reorder_point != null) payload.append('reorder_point', String(form.reorder_point))
    payload.append('is_active', form.is_active ? '1' : '0')
    if (imageFile.value) {
      payload.append('product_image', imageFile.value)
    }

    if (isEditMode.value) {
      await inventoryService.updateProduct(Number(route.params.id), payload)
      toast.add({ severity: 'success', summary: 'Saved', detail: 'Item updated successfully', life: 3000 })
      goBack()
    } else {
      await inventoryService.createProduct(payload)
      router.push({ name: 'inventory.products.index' })
    }
  } catch (error: any) {
    const apiErrors = error.response?.data?.errors || {}
    if (error.response?.status === 422) {
      errors.value = {
        ...errors.value,
        ...Object.fromEntries(
          Object.entries(apiErrors).map(([key, value]) => [
            key,
            Array.isArray(value) ? value.join(', ') : String(value)
          ])
        )
      }
    }
    submitError.value =
      error.response?.data?.message ||
      error.response?.data?.error ||
      (Object.keys(apiErrors).length
        ? Object.entries(apiErrors)
            .map(([key, value]) => `${key}: ${Array.isArray(value) ? value.join(', ') : String(value)}`)
            .join('\n')
        : 'Failed to save Item')

    toast.add({ severity: 'error', summary: 'Error', detail: submitError.value, life: 4000 })
  } finally {
    submitting.value = false
  }
}

const goBack = () => router.push({ name: 'inventory.items' })

onMounted(async () => {
  await loadCategories()
  if (isEditMode.value) {
    await loadProduct(Number(route.params.id))
  }
})
</script>
