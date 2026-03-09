<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-4xl mx-auto">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Create Category</h1>
        <p class="text-gray-600 mt-1">Add a new product category</p>
      </div>

      <Card>
        <template #content>
          <form @submit.prevent="submitForm" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Category Name *</label>
                <InputText
                  v-model="form.name"
                  placeholder="Enter category name"
                  class="w-full"
                  :class="{ 'p-invalid': errors.name }"
                />
                <small v-if="errors.name" class="p-error">{{ errors.name[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Parent Category</label>
                <Select
                  v-model="form.parent_id"
                  :options="parentCategories"
                  optionLabel="name"
                  optionValue="id"
                  placeholder="Select parent category (optional)"
                  class="w-full"
                  showClear
                  :class="{ 'p-invalid': errors.parent_id }"
                />
                <small v-if="errors.parent_id" class="p-error">{{ errors.parent_id[0] }}</small>
                <small class="text-gray-500 mt-1">Leave empty for root category</small>
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <Textarea
                  v-model="form.description"
                  placeholder="Enter category description"
                  class="w-full"
                  rows="3"
                  :class="{ 'p-invalid': errors.description }"
                />
                <small v-if="errors.description" class="p-error">{{ errors.description[0] }}</small>
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                <InputNumber
                  v-model="form.display_order"
                  placeholder="0"
                  class="w-full"
                  :class="{ 'p-invalid': errors.display_order }"
                />
                <small v-if="errors.display_order" class="p-error">{{ errors.display_order[0] }}</small>
                <small class="text-gray-500 mt-1">Lower numbers appear first</small>
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
                label="Create Category"
                :loading="loading"
                class="bg-blue-600 hover:bg-blue-700"
              />
            </div>
          </form>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'

const loading = ref(false)
const parentCategories = ref<any[]>([])
const errors = ref<any>({})
const toast = useToast()
const router = useRouter()

const form = reactive({
  name: '',
  description: '',
  parent_id: null as number | null,
  status: 'active',
  display_order: 0
})

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' }
]

const loadParentCategories = async () => {
  try {
    const response = await inventoryService.getCategories({
      parent_only: true
    })
    if (response.success) {
      parentCategories.value = response.data || []
    }
  } catch (error) {
    console.error('Failed to load parent categories', error)
  }
}

const submitForm = async () => {
  loading.value = true
  errors.value = {}

  try {
    const response = await inventoryService.createCategory(form)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Category created successfully',
        life: 3000
      })
      router.push({ name: 'inventory.categories' })
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
        detail: error.response?.data?.message || 'Failed to create category',
        life: 3000
      })
    }
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'inventory.categories' })
}

onMounted(() => {
  loadParentCategories()
})
</script>