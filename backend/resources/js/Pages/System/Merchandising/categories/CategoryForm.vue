<template>
  <div class="max-w-3xl mx-auto space-y-6 pb-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">
          {{ isEditMode ? 'Edit Category' : 'Add New Category' }}
        </h2>
        <p class="text-sm text-gray-500 mt-1">
          {{ isEditMode ? 'Update category information' : 'Create a new product category' }}
        </p>
      </div>
      <Button 
        label="Back" 
        icon="pi pi-arrow-left" 
        text 
        @click="router.push({ name: 'merchandising.categories' })" 
      />
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loadingData">
      <Skeleton height="400px" class="rounded-lg" />
    </div>

    <!-- Form -->
    <form v-else @submit.prevent="handleSubmit">
      <Card>
        <template #content>
          <div class="space-y-4">
            <!-- Category Code -->
            <div class="flex flex-col gap-2">
              <label for="category_code" class="text-sm font-semibold text-gray-700">
                Category Code <span class="text-red-500">*</span>
              </label>
              <InputText 
                id="category_code"
                v-model="form.category_code" 
                placeholder="e.g., SOFA, CHAIR, TABLE" 
                :class="{ 'p-invalid': errors.category_code }"
              />
              <small v-if="errors.category_code" class="text-red-500">{{ errors.category_code }}</small>
              <small class="text-gray-500">Used for SKU generation and internal reference</small>
            </div>

            <!-- Category Name -->
            <div class="flex flex-col gap-2">
              <label for="category_name" class="text-sm font-semibold text-gray-700">
                Category Name <span class="text-red-500">*</span>
              </label>
              <InputText 
                id="category_name"
                v-model="form.category_name" 
                placeholder="e.g., Sofas & Couches, Dining Tables" 
                :class="{ 'p-invalid': errors.category_name }"
              />
              <small v-if="errors.category_name" class="text-red-500">{{ errors.category_name }}</small>
            </div>

            <!-- Parent Category -->
            <div class="flex flex-col gap-2">
              <label for="parent_category_id" class="text-sm font-semibold text-gray-700">
                Parent Category
              </label>
              <Select 
                id="parent_category_id"
                v-model="form.parent_category_id" 
                :options="categories" 
                optionLabel="category_name" 
                optionValue="id"
                placeholder="None (Root Category)" 
                showClear
                :loading="loadingCategories"
              />
              <small class="text-gray-500">Leave empty to create a root category</small>
            </div>

            <!-- Description -->
            <div class="flex flex-col gap-2">
              <label for="description" class="text-sm font-semibold text-gray-700">
                Description
              </label>
              <Textarea 
                id="description"
                v-model="form.description" 
                rows="4" 
                placeholder="Category description..."
              />
            </div>

            <!-- Icon -->
            <div class="flex flex-col gap-2">
              <label for="icon_path" class="text-sm font-semibold text-gray-700">
                Icon (PrimeIcons class)
              </label>
              <div class="flex gap-2">
                <InputText 
                  id="icon_path"
                  v-model="form.icon_path" 
                  placeholder="e.g., pi pi-box, pi pi-home" 
                  class="flex-1"
                />
                <Button 
                  label="Browse Icons" 
                  icon="pi pi-external-link" 
                  outlined
                  @click="openIconBrowser"
                />
              </div>
              <div v-if="form.icon_path" class="flex items-center gap-2 mt-2 p-3 bg-gray-50 rounded">
                <span class="text-sm text-gray-600">Preview:</span>
                <i :class="form.icon_path" class="text-3xl text-gray-700"></i>
              </div>
              <small class="text-gray-500">
                Visit <a href="https://primevue.org/icons" target="_blank" class="text-blue-600 hover:underline">primeicons.org</a> for icon names
              </small>
            </div>

            <!-- Display Order -->
            <div class="flex flex-col gap-2">
              <label for="display_order" class="text-sm font-semibold text-gray-700">
                Display Order
              </label>
              <InputNumber 
                id="display_order"
                v-model="form.display_order" 
                :min="0"
                showButtons
              />
              <small class="text-gray-500">Lower numbers appear first</small>
            </div>

            <!-- Active Status -->
            <div class="flex items-center gap-2 pt-3 border-t border-gray-200">
              <Checkbox v-model="form.is_active" inputId="is_active" :binary="true" />
              <label for="is_active" class="text-sm font-semibold text-gray-700 cursor-pointer">Active</label>
            </div>
          </div>
        </template>
      </Card>

      <!-- Action Buttons -->
      <div class="flex justify-end gap-3 mt-6">
        <Button 
          label="Cancel" 
          severity="secondary" 
          outlined 
          @click="router.push({ name: 'merchandising.categories' })" 
        />
        <Button 
          :label="isEditMode ? 'Update Category' : 'Create Category'" 
          icon="pi pi-check" 
          @click="handleSubmit"
          :loading="submitting"
        />
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import merchandisingService from '../../../../services/merchandising.service'

import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import Skeleton from 'primevue/skeleton'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const isEditMode = computed(() => !!route.params.id)
const submitting = ref(false)
const loadingData = ref(false)
const loadingCategories = ref(false)
const categories = ref([])

const form = reactive({
  category_code: '',
  category_name: '',
  description: '',
  parent_category_id: null,
  icon_path: '',
  is_active: true,
  display_order: 0
})

const errors = ref<Record<string, string>>({})

const loadCategories = async () => {
  loadingCategories.value = true
  try {
    const response = await merchandisingService.getCategories()
    categories.value = response.data.data
  } catch (error) {
    console.error('Failed to load categories:', error)
  } finally {
    loadingCategories.value = false
  }
}

const loadCategory = async () => {
  if (!isEditMode.value) return
  
  loadingData.value = true
  try {
    const response = await merchandisingService.getCategory(Number(route.params.id))
    const category = response.data
    
    Object.assign(form, {
      category_code: category.category_code,
      category_name: category.category_name,
      description: category.description || '',
      parent_category_id: category.parent_category_id,
      icon_path: category.icon_path || '',
      is_active: category.is_active,
      display_order: category.display_order || 0
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load category',
      life: 5000
    })
    router.push({ name: 'merchandising.categories' })
  } finally {
    loadingData.value = false
  }
}

const validateForm = () => {
  errors.value = {}
  
  if (!form.category_code) {
    errors.value.category_code = 'Category code is required'
  }
  
  if (!form.category_name) {
    errors.value.category_name = 'Category name is required'
  }
  
  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validateForm()) {
    toast.add({
      severity: 'warn',
      summary: 'Validation Error',
      detail: 'Please fill in all required fields',
      life: 3000
    })
    return
  }
  
  submitting.value = true
  
  try {
    if (isEditMode.value) {
      await merchandisingService.updateCategory(Number(route.params.id), form)
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Category updated successfully',
        life: 3000
      })
    } else {
      await merchandisingService.createCategory(form)
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Category created successfully',
        life: 3000
      })
    }
    
    router.push({ name: 'merchandising.categories' })
  } catch (error: any) {
    console.error('Form submission error:', error)
    
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    }
    
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to save category',
      life: 5000
    })
  } finally {
    submitting.value = false
  }
}

const openIconBrowser = () => {
  window.open('https://primevue.org/icons', '_blank')
}

onMounted(() => {
  loadCategories()
  loadCategory()
})
</script>