<template>
  <div class="space-y-4">
    <!-- Loading Skeleton -->
    <div v-if="loadingData" class="space-y-3">
      <Skeleton height="60px" />
      <Skeleton height="60px" />
      <Skeleton height="100px" />
    </div>

    <!-- Form -->
    <div v-else class="space-y-4">
      
      <!-- Tag Name -->
      <div class="flex flex-col gap-2">
        <label for="tag_name" class="text-sm font-semibold text-gray-700">
          Tag Name <span class="text-red-500">*</span>
        </label>
        <InputText 
          id="tag_name"
          v-model="form.tag_name" 
          placeholder="e.g., Modern, Vintage, Sale, Eco-friendly" 
          :class="{ 'p-invalid': errors.tag_name }"
        />
        <small v-if="errors.tag_name" class="text-red-500">{{ errors.tag_name }}</small>
      </div>

      <!-- Tag Type -->
      <div class="flex flex-col gap-2">
        <label for="tag_type" class="text-sm font-semibold text-gray-700">
          Tag Type <span class="text-red-500">*</span>
        </label>
        <Select 
          id="tag_type"
          v-model="form.tag_type" 
          :options="tagTypes" 
          placeholder="Select tag type" 
          :class="{ 'p-invalid': errors.tag_type }"
        />
        <small v-if="errors.tag_type" class="text-red-500">{{ errors.tag_type }}</small>
        
        <!-- Type Descriptions -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mt-2">
          <p class="text-xs font-semibold text-blue-900 mb-2">Tag Types:</p>
          <ul class="text-xs text-blue-800 space-y-1 list-disc list-inside">
            <li><strong>Style:</strong> Design aesthetics (Modern, Vintage, Industrial)</li>
            <li><strong>Room:</strong> Room categories (Living Room, Bedroom, Office)</li>
            <li><strong>Promotion:</strong> Sales and offers (Sale, New, Limited Edition)</li>
            <li><strong>Feature:</strong> Product features (Eco-friendly, Handmade, Premium)</li>
          </ul>
        </div>
      </div>

      <!-- Slug (Auto-generated) -->
      <div class="flex flex-col gap-2">
        <label for="slug" class="text-sm font-semibold text-gray-700">
          Slug (URL-friendly)
        </label>
        <InputText 
          id="slug"
          v-model="form.slug" 
          placeholder="auto-generated-from-name" 
          disabled
          class="bg-gray-100 font-mono text-sm"
        />
        <small class="text-gray-500">Auto-generated from tag name</small>
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
          placeholder="Optional description..."
        />
      </div>

      <!-- Color -->
      <div class="flex flex-col gap-2">
        <label for="color_hex" class="text-sm font-semibold text-gray-700">
          Tag Color (Optional)
        </label>
        <div class="flex gap-2 items-center">
          <input 
            type="color"
            v-model="form.color_hex"
            class="h-10 w-20 rounded border border-gray-300 cursor-pointer"
          />
          <InputText 
            id="color_hex"
            v-model="form.color_hex" 
            placeholder="#3B82F6" 
            class="flex-1 font-mono"
            maxlength="7"
          />
        </div>
        
        <!-- Color Preview -->
        <div v-if="form.color_hex && form.tag_name" class="mt-2 p-3 rounded-lg flex items-center gap-2" :style="{ backgroundColor: form.color_hex + '20', borderColor: form.color_hex, borderWidth: '2px' }">
          <Tag :value="form.tag_name" :style="{ backgroundColor: form.color_hex, color: getContrastColor(form.color_hex) }" />
          <span class="text-sm text-gray-600">Preview</span>
        </div>
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
          buttonLayout="horizontal"
        />
        <small class="text-gray-500">Lower numbers appear first</small>
      </div>

      <!-- Active Status -->
      <div class="flex items-center gap-2 pt-3 border-t border-gray-200">
        <Checkbox v-model="form.is_active" inputId="is_active" :binary="true" />
        <label for="is_active" class="text-sm font-semibold text-gray-700 cursor-pointer">Active</label>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import merchandisingService from '../../../../services/merchandising.service'

import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import Skeleton from 'primevue/skeleton'
import Tag from 'primevue/tag'

const props = defineProps({
  tagId: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['save', 'cancel'])

const toast = useToast()
const loadingData = ref(false)

const form = reactive({
  tag_name: '',
  tag_type: 'Style',
  slug: '',
  description: '',
  color_hex: '#3B82F6',
  display_order: 0,
  is_active: true
})

const errors = ref<Record<string, string>>({})
const tagTypes = ['Style', 'Room', 'Promotion', 'Feature']

// Watch tag name to auto-generate slug
watch(() => form.tag_name, (newVal) => {
  if (!props.tagId || !form.slug) {
    form.slug = newVal
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '')
  }
})

const loadTag = async () => {
  if (!props.tagId) return
  
  loadingData.value = true
  try {
    const response = await merchandisingService.getTag(props.tagId)
    const tag = response.data
    
    Object.assign(form, {
      tag_name: tag.tag_name,
      tag_type: tag.tag_type,
      slug: tag.slug || '',
      description: tag.description || '',
      color_hex: tag.color_hex || '#3B82F6',
      display_order: tag.display_order || 0,
      is_active: tag.is_active
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load tag',
      life: 5000
    })
    emit('cancel')
  } finally {
    loadingData.value = false
  }
}

const validateForm = () => {
  errors.value = {}
  
  if (!form.tag_name) {
    errors.value.tag_name = 'Tag name is required'
  }
  
  if (!form.tag_type) {
    errors.value.tag_type = 'Tag type is required'
  }
  
  return Object.keys(errors.value).length === 0
}

const save = async () => {
  if (!validateForm()) {
    toast.add({
      severity: 'warn',
      summary: 'Validation Error',
      detail: 'Please fill in all required fields',
      life: 3000
    })
    return false
  }
  
  try {
    if (props.tagId) {
      await merchandisingService.updateTag(props.tagId, form)
    } else {
      await merchandisingService.createTag(form)
    }
    
    return true
  } catch (error: any) {
    console.error('Form submission error:', error)
    
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    }
    
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to save tag',
      life: 5000
    })
    
    return false
  }
}

const getContrastColor = (hexColor: string) => {
  const hex = hexColor.replace('#', '')
  const r = parseInt(hex.substr(0, 2), 16)
  const g = parseInt(hex.substr(2, 2), 16)
  const b = parseInt(hex.substr(4, 2), 16)
  const brightness = (r * 299 + g * 587 + b * 114) / 1000
  return brightness > 128 ? '#000000' : '#ffffff'
}

// Expose save method to parent
defineExpose({ save })

onMounted(() => {
  loadTag()
})
</script>