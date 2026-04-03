<template>
  <div class="max-w-4xl mx-auto space-y-6 pb-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">
          {{ isEditMode ? 'Edit Attribute' : 'Add New Attribute' }}
        </h2>
        <p class="text-sm text-gray-500 mt-1">
          {{ isEditMode ? 'Update attribute information' : 'Create a new product attribute for filtering and specifications' }}
        </p>
      </div>
      <Button 
        label="Back" 
        icon="pi pi-arrow-left" 
        text 
        @click="router.push({ name: 'merchandising.attributes' })" 
      />
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loadingData" class="space-y-4">
      <Skeleton height="300px" class="rounded-lg" />
      <Skeleton height="200px" class="rounded-lg" />
    </div>

    <!-- Form -->
    <form v-else @submit.prevent="handleSubmit">
      <!-- Basic Information Card -->
      <Card class="mb-6">
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-info-circle text-blue-600"></i>
            <span>Basic Information</span>
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            <!-- Attribute Name -->
            <div class="flex flex-col gap-2">
              <label for="attribute_name" class="text-sm font-semibold text-gray-700">
                Attribute Name <span class="text-red-500">*</span>
              </label>
              <InputText 
                id="attribute_name"
                v-model="form.attribute_name" 
                placeholder="e.g., Material, Color, Size, Finish" 
                :class="{ 'p-invalid': errors.attribute_name }"
              />
              <small v-if="errors.attribute_name" class="text-red-500">{{ errors.attribute_name }}</small>
            </div>

            <!-- Attribute Type -->
            <div class="flex flex-col gap-2">
              <label for="attribute_type" class="text-sm font-semibold text-gray-700">
                Attribute Type <span class="text-red-500">*</span>
              </label>
              <Select 
                id="attribute_type"
                v-model="form.attribute_type" 
                :options="attributeTypes" 
                placeholder="Select attribute type" 
                :class="{ 'p-invalid': errors.attribute_type }"
                @change="onAttributeTypeChange"
              />
              <small v-if="errors.attribute_type" class="text-red-500">{{ errors.attribute_type }}</small>
              
              <!-- Type Descriptions -->
              <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mt-2">
                <p class="text-xs font-semibold text-blue-900 mb-2">Attribute Types:</p>
                <ul class="text-xs text-blue-800 space-y-1 list-disc list-inside">
                  <li><strong>Text:</strong> Free text input (e.g., Brand, Model Number)</li>
                  <li><strong>Number:</strong> Numeric values with optional unit (e.g., Weight, Height)</li>
                  <li><strong>Select:</strong> Single choice from predefined options (e.g., Material)</li>
                  <li><strong>Multi-select:</strong> Multiple choices from options (e.g., Features)</li>
                  <li><strong>Color:</strong> Color picker with hex values (e.g., Available Colors)</li>
                </ul>
              </div>
            </div>

            <!-- Description -->
            <div class="flex flex-col gap-2">
              <label for="description" class="text-sm font-semibold text-gray-700">
                Description
              </label>
              <Textarea 
                id="description"
                v-model="form.description" 
                rows="3" 
                placeholder="Optional description for this attribute..."
              />
              <small class="text-gray-500">Helps users understand what this attribute represents</small>
            </div>
          </div>
        </template>
      </Card>

      <!-- Type-Specific Settings Card -->
      <Card class="mb-6" v-if="form.attribute_type">
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-cog text-purple-600"></i>
            <span>Type-Specific Settings</span>
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            
            <!-- Number Type Settings -->
            <template v-if="form.attribute_type === 'Number'">
              <div class="flex flex-col gap-2">
                <label for="unit" class="text-sm font-semibold text-gray-700">
                  Unit of Measurement
                </label>
                <InputText 
                  id="unit"
                  v-model="form.unit" 
                  placeholder="e.g., cm, kg, inches, lbs" 
                />
                <small class="text-gray-500">Optional: Specify the unit (cm, kg, etc.)</small>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-2">
                  <label for="min_value" class="text-sm font-semibold text-gray-700">
                    Minimum Value
                  </label>
                  <InputNumber 
                    id="min_value"
                    v-model="form.min_value" 
                    placeholder="0"
                    :minFractionDigits="2"
                  />
                  <small class="text-gray-500">Optional validation</small>
                </div>

                <div class="flex flex-col gap-2">
                  <label for="max_value" class="text-sm font-semibold text-gray-700">
                    Maximum Value
                  </label>
                  <InputNumber 
                    id="max_value"
                    v-model="form.max_value" 
                    placeholder="1000"
                    :minFractionDigits="2"
                  />
                  <small class="text-gray-500">Optional validation</small>
                </div>
              </div>
            </template>

            <!-- Select/Multi-select/Color Type Settings -->
            <template v-if="['Select', 'Multi-select', 'Color'].includes(form.attribute_type)">
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">
                  Predefined Options <span class="text-red-500">*</span>
                </label>
                
                <div class="space-y-2">
                  <div 
                    v-for="(option, index) in form.options" 
                    :key="index" 
                    class="flex gap-2 items-center"
                  >
                    <span class="text-sm text-gray-600 w-8">{{ index + 1 }}.</span>
                    
                    <!-- Color Picker for Color type -->
                    <input 
                      v-if="form.attribute_type === 'Color'"
                      type="color"
                      v-model="form.option_colors[index]"
                      class="h-10 w-16 rounded border border-gray-300 cursor-pointer"
                    />
                    
                    <InputText 
                      v-model="form.options[index]" 
                      :placeholder="form.attribute_type === 'Color' ? 'Color name (e.g., Navy Blue)' : `Option ${index + 1}`"
                      class="flex-1"
                    />
                    
                    <InputText 
                      v-if="form.attribute_type === 'Color'"
                      v-model="form.option_colors[index]"
                      placeholder="#000000"
                      class="w-28 font-mono text-sm"
                      maxlength="7"
                    />
                    
                    <Button 
                      icon="pi pi-trash" 
                      severity="danger"
                      outlined
                      size="small"
                      @click="removeOption(index)"
                      :disabled="form.options.length === 1"
                    />
                  </div>
                </div>

                <Button 
                  label="Add Option" 
                  icon="pi pi-plus" 
                  severity="secondary"
                  outlined
                  size="small"
                  class="w-fit"
                  @click="addOption"
                />
                
                <small class="text-gray-500">
                  Add at least one option. Users will select from these when adding products.
                </small>
              </div>

              <!-- Quick Add Presets -->
              <div v-if="form.attribute_type === 'Select' || form.attribute_type === 'Multi-select'" class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <p class="text-sm font-semibold text-gray-700 mb-2">Quick Add Presets:</p>
                <div class="flex flex-wrap gap-2">
                  <Button 
                    v-if="form.attribute_name.toLowerCase().includes('material')"
                    label="+ Wood, Metal, Fabric, Leather, Plastic"
                    size="small"
                    outlined
                    @click="addPresetOptions(['Wood', 'Metal', 'Fabric', 'Leather', 'Plastic'])"
                  />
                  <Button 
                    v-if="form.attribute_name.toLowerCase().includes('size')"
                    label="+ Small, Medium, Large, XL"
                    size="small"
                    outlined
                    @click="addPresetOptions(['Small', 'Medium', 'Large', 'XL'])"
                  />
                  <Button 
                    v-if="form.attribute_name.toLowerCase().includes('condition')"
                    label="+ New, Like New, Good, Fair"
                    size="small"
                    outlined
                    @click="addPresetOptions(['New', 'Like New', 'Good', 'Fair'])"
                  />
                </div>
              </div>
            </template>

          </div>
        </template>
      </Card>

      <!-- Display & Behavior Settings Card -->
      <Card class="mb-6">
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-sliders-h text-green-600"></i>
            <span>Display & Behavior Settings</span>
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            
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
                buttonLayout="horizontal"
                :step="1"
              />
              <small class="text-gray-500">Lower numbers appear first in product forms and filters</small>
            </div>

            <!-- Checkboxes -->
            <div class="space-y-3 pt-3 border-t border-gray-200">
              <div class="flex items-start gap-3 p-3 bg-blue-50 rounded-lg border border-blue-200">
                <Checkbox v-model="form.is_filterable" inputId="is_filterable" :binary="true" class="mt-1" />
                <div class="flex-1">
                  <label for="is_filterable" class="text-sm font-semibold text-gray-900 cursor-pointer block">
                    Show in Product Filters
                  </label>
                  <p class="text-xs text-gray-600 mt-1">
                    Enable this to allow customers to filter products by this attribute on the storefront
                  </p>
                </div>
              </div>

              <div class="flex items-start gap-3 p-3 bg-orange-50 rounded-lg border border-orange-200">
                <Checkbox v-model="form.is_required" inputId="is_required" :binary="true" class="mt-1" />
                <div class="flex-1">
                  <label for="is_required" class="text-sm font-semibold text-gray-900 cursor-pointer block">
                    Required Field
                  </label>
                  <p class="text-xs text-gray-600 mt-1">
                    Products must have a value for this attribute before they can be published
                  </p>
                </div>
              </div>

              <div class="flex items-start gap-3 p-3 bg-purple-50 rounded-lg border border-purple-200">
                <Checkbox v-model="form.is_variant_option" inputId="is_variant_option" :binary="true" class="mt-1" />
                <div class="flex-1">
                  <label for="is_variant_option" class="text-sm font-semibold text-gray-900 cursor-pointer block">
                    Use for Product Variations
                  </label>
                  <p class="text-xs text-gray-600 mt-1">
                    This attribute can be used to create product variants (e.g., different colors or sizes of the same product)
                  </p>
                </div>
              </div>

              <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                <Checkbox v-model="form.show_on_product_page" inputId="show_on_product_page" :binary="true" class="mt-1" />
                <div class="flex-1">
                  <label for="show_on_product_page" class="text-sm font-semibold text-gray-900 cursor-pointer block">
                    Show on Product Page
                  </label>
                  <p class="text-xs text-gray-600 mt-1">
                    Display this attribute in the product specifications table
                  </p>
                </div>
              </div>
            </div>

          </div>
        </template>
      </Card>

      <!-- Action Buttons -->
      <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
        <Button 
          label="Cancel" 
          severity="secondary" 
          outlined 
          @click="router.push({ name: 'merchandising.attributes' })" 
        />
        <Button 
          :label="isEditMode ? 'Update Attribute' : 'Create Attribute'" 
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

const form = reactive({
  attribute_name: '',
  attribute_type: 'Text',
  description: '',
  unit: '',
  options: [''],
  option_colors: ['#3B82F6'],
  min_value: null,
  max_value: null,
  is_filterable: false,
  is_required: false,
  is_variant_option: false,
  show_on_product_page: true,
  display_order: 0
})

const errors = ref<Record<string, string>>({})

const attributeTypes = ['Text', 'Number', 'Select', 'Multi-select', 'Color']

const loadAttribute = async () => {
  if (!isEditMode.value) return
  
  loadingData.value = true
  try {
    const response = await merchandisingService.getAttribute(Number(route.params.id))
    const attribute = response.data
    
    Object.assign(form, {
      attribute_name: attribute.attribute_name,
      attribute_type: attribute.attribute_type,
      description: attribute.description || '',
      unit: attribute.unit || '',
      options: attribute.options && attribute.options.length > 0 ? [...attribute.options] : [''],
      option_colors: attribute.option_colors && attribute.option_colors.length > 0 ? [...attribute.option_colors] : ['#3B82F6'],
      min_value: attribute.min_value,
      max_value: attribute.max_value,
      is_filterable: attribute.is_filterable || false,
      is_required: attribute.is_required || false,
      is_variant_option: attribute.is_variant_option || false,
      show_on_product_page: attribute.show_on_product_page ?? true,
      display_order: attribute.display_order || 0
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load attribute',
      life: 5000
    })
    router.push({ name: 'merchandising.attributes' })
  } finally {
    loadingData.value = false
  }
}

const onAttributeTypeChange = () => {
  // Reset type-specific fields
  if (!['Select', 'Multi-select', 'Color'].includes(form.attribute_type)) {
    form.options = ['']
    form.option_colors = ['#3B82F6']
  }
  if (form.attribute_type !== 'Number') {
    form.unit = ''
    form.min_value = null
    form.max_value = null
  }
}

const addOption = () => {
  form.options.push('')
  if (form.attribute_type === 'Color') {
    form.option_colors.push('#3B82F6')
  }
}

const removeOption = (index: number) => {
  if (form.options.length > 1) {
    form.options.splice(index, 1)
    if (form.attribute_type === 'Color') {
      form.option_colors.splice(index, 1)
    }
  }
}

const addPresetOptions = (presets: string[]) => {
  // Remove empty options
  form.options = form.options.filter(opt => opt.trim() !== '')
  // Add presets
  form.options.push(...presets)
}

const validateForm = () => {
  errors.value = {}
  
  if (!form.attribute_name) {
    errors.value.attribute_name = 'Attribute name is required'
  }
  
  if (!form.attribute_type) {
    errors.value.attribute_type = 'Attribute type is required'
  }

  // Validate options for select types
  if (['Select', 'Multi-select', 'Color'].includes(form.attribute_type)) {
    const validOptions = form.options.filter(opt => opt.trim() !== '')
    if (validOptions.length === 0) {
      toast.add({
        severity: 'warn',
        summary: 'Validation Error',
        detail: 'Please add at least one option for this attribute type',
        life: 3000
      })
      return false
    }
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
    // Clean up data
    const submitData = {
      ...form,
      options: ['Select', 'Multi-select', 'Color'].includes(form.attribute_type)
        ? form.options.filter(opt => opt.trim() !== '')
        : null,
      option_colors: form.attribute_type === 'Color'
        ? form.option_colors
        : null,
      unit: form.attribute_type === 'Number' ? form.unit : null,
      min_value: form.attribute_type === 'Number' ? form.min_value : null,
      max_value: form.attribute_type === 'Number' ? form.max_value : null
    }

    if (isEditMode.value) {
      await merchandisingService.updateAttribute(Number(route.params.id), submitData)
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Attribute updated successfully',
        life: 3000
      })
    } else {
      await merchandisingService.createAttribute(submitData)
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Attribute created successfully',
        life: 3000
      })
    }
    
    router.push({ name: 'merchandising.attributes' })
  } catch (error: any) {
    console.error('Form submission error:', error)
    
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    }
    
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to save attribute',
      life: 5000
    })
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadAttribute()
})
</script>

<style scoped>
:deep(.p-card-title) {
  font-size: 1rem;
  font-weight: 600;
}

:deep(.p-card-content) {
  padding-top: 1rem;
}
</style>