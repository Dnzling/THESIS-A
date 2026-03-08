<template>
  <div class="space-y-6">
    <h3 class="text-lg font-semibold text-gray-700 border-b pb-2">
      <i class="pi pi-info-circle mr-2"></i>
      Basic Information
    </h3>
    
    <!-- Product Name & SKU -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700">
          Product Name *
        </label>
        <InputText 
          v-model="localForm.productName"
          placeholder="e.g., Modern 3-Seater Sofa"
          class="w-full"
          :class="{ 'p-invalid': errors.productName }"
          @blur="validateField('productName')"
        />
        <small v-if="errors.productName" class="p-error">
          {{ errors.productName }}
        </small>
      </div>
      
      <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700">
          SKU Code *
        </label>
        <InputText 
          v-model="localForm.sku"
          placeholder="e.g., SOFA-MOD-3S-2024"
          class="w-full"
          :class="{ 'p-invalid': errors.sku }"
          @blur="validateField('sku')"
        />
        <small v-if="errors.sku" class="p-error">
          {{ errors.sku }}
        </small>
      </div>
    </div>

    <!-- Category & Type -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700">
          Category *
        </label>
        <Dropdown 
          v-model="localForm.categoryId"
          :options="categories"
          optionLabel="category_name"
          optionValue="category_id"
          placeholder="Select Category"
          class="w-full"
          :class="{ 'p-invalid': errors.categoryId }"
          @change="validateField('categoryId')"
        />
        <small v-if="errors.categoryId" class="p-error">
          {{ errors.categoryId }}
        </small>
      </div>
      
      <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700">
          Product Type
        </label>
        <Dropdown 
          v-model="localForm.productType"
          :options="productTypes"
          placeholder="Select Type"
          class="w-full"
        />
      </div>
    </div>

    <!-- Supplier -->
    <div class="space-y-2">
      <label class="block text-sm font-medium text-gray-700">
        Supplier
      </label>
      <Dropdown 
        v-model="localForm.supplierId"
        :options="suppliers"
        optionLabel="supplier_name"
        optionValue="supplier_id"
        placeholder="Select Supplier"
        class="w-full"
        :showClear="true"
      />
    </div>

    <!-- Descriptions -->
    <div class="space-y-4">
      <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700">
          Short Description
        </label>
        <Textarea 
          v-model="localForm.shortDescription"
          placeholder="Brief description (max 150 characters)"
          rows="2"
          class="w-full"
          :autoResize="true"
        />
        <small class="text-gray-500">
          {{ localForm.shortDescription.length }}/150 characters
        </small>
      </div>
      
      <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700">
          Full Description
        </label>
        <Editor 
          v-model="localForm.fullDescription"
          editorStyle="height: 200px"
          class="border rounded"
        />
      </div>
    </div>

    <!-- Pricing -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700">
          Cost Price (₱) *
        </label>
        <InputNumber 
          v-model="localForm.costPrice"
          mode="currency"
          currency="PHP"
          locale="en-PH"
          class="w-full"
          :class="{ 'p-invalid': errors.costPrice }"
          @blur="validateField('costPrice')"
        />
        <small v-if="errors.costPrice" class="p-error">
          {{ errors.costPrice }}
        </small>
      </div>
      
      <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700">
          Selling Price (₱) *
        </label>
        <InputNumber 
          v-model="localForm.sellingPrice"
          mode="currency"
          currency="PHP"
          locale="en-PH"
          class="w-full"
          :class="{ 'p-invalid': errors.sellingPrice }"
          @blur="validateField('sellingPrice')"
        />
        <small v-if="errors.sellingPrice" class="p-error">
          {{ errors.sellingPrice }}
        </small>
      </div>
      
      <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700">
          Discount %
        </label>
        <InputNumber 
          v-model="localForm.discountPercentage"
          suffix="%"
          :min="0"
          :max="100"
          class="w-full"
        />
      </div>
    </div>

    <!-- Additional Options -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="space-y-4">
        <div class="flex items-center">
          <Checkbox 
            v-model="localForm.vatInclusive"
            inputId="vatInclusive"
            :binary="true"
          />
          <label for="vatInclusive" class="ml-2 text-gray-700">
            Price is VAT Inclusive
          </label>
        </div>
        
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            Warranty Period (Months)
          </label>
          <Dropdown 
            v-model="localForm.warrantyMonths"
            :options="warrantyOptions"
            class="w-full"
          />
        </div>
      </div>
      
      <!-- Profit Margin Display -->
      <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="font-medium text-gray-700 mb-2">Profit Analysis</h4>
        <div class="space-y-2">
          <div class="flex justify-between">
            <span class="text-gray-600">Cost Price:</span>
            <span class="font-medium">₱{{ formatNumber(localForm.costPrice) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Selling Price:</span>
            <span class="font-medium">₱{{ formatNumber(localForm.sellingPrice) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Profit Margin:</span>
            <span 
              :class="[
                'font-bold',
                profitMargin >= 30 ? 'text-green-600' : 
                profitMargin >= 20 ? 'text-yellow-600' : 
                'text-red-600'
              ]"
            >
              {{ profitMargin.toFixed(1) }}%
            </span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Profit Amount:</span>
            <span class="font-medium text-green-600">
              ₱{{ formatNumber(profitAmount) }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, defineProps, defineEmits } from 'vue'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Textarea from 'primevue/textarea'
import InputNumber from 'primevue/inputnumber'
import Checkbox from 'primevue/checkbox'
import Editor from 'primevue/editor'

const props = defineProps({
  formData: {
    type: Object,
    required: true
  },
  categories: {
    type: Array,
    default: () => []
  },
  suppliers: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update:formData', 'validation'])

// Local copy of form data
const localForm = ref({ ...props.formData })

// Error tracking
const errors = ref({})

// Dropdown options
const productTypes = ref([
  { label: 'Furniture', value: 'furniture' },
  { label: 'Accessory', value: 'accessory' },
  { label: 'Decor', value: 'decor' },
  { label: 'Lighting', value: 'lighting' }
])

const warrantyOptions = ref([
  { label: '6 months', value: 6 },
  { label: '12 months', value: 12 },
  { label: '18 months', value: 18 },
  { label: '24 months', value: 24 },
  { label: '36 months', value: 36 },
  { label: 'Lifetime', value: 0 }
])

// Computed profit calculations
const profitAmount = computed(() => {
  return localForm.value.sellingPrice - localForm.value.costPrice
})

const profitMargin = computed(() => {
  if (!localForm.value.sellingPrice) return 0
  return ((profitAmount.value / localForm.value.sellingPrice) * 100)
})

// Validation rules
const validationRules = {
  productName: [
    { required: true, message: 'Product name is required' },
    { min: 3, message: 'Minimum 3 characters' },
    { max: 200, message: 'Maximum 200 characters' }
  ],
  sku: [
    { required: true, message: 'SKU is required' },
    { pattern: /^[A-Z0-9-]+$/, message: 'Only uppercase letters, numbers, and hyphens' }
  ],
  categoryId: [
    { required: true, message: 'Category is required' }
  ],
  costPrice: [
    { required: true, message: 'Cost price is required' },
    { min: 0, message: 'Must be positive' }
  ],
  sellingPrice: [
    { required: true, message: 'Selling price is required' },
    { min: 0, message: 'Must be positive' },
    { 
      validator: (value) => value >= localForm.value.costPrice,
      message: 'Selling price must be ≥ cost price'
    }
  ]
}

// Validation function
const validateField = (field) => {
  const rules = validationRules[field]
  if (!rules) return true

  const value = localForm.value[field]
  let isValid = true
  let errorMessage = ''

  for (const rule of rules) {
    if (rule.required && (!value && value !== 0)) {
      isValid = false
      errorMessage = rule.message
      break
    }
    
    if (rule.min && value < rule.min) {
      isValid = false
      errorMessage = rule.message
      break
    }
    
    if (rule.max && value > rule.max) {
      isValid = false
      errorMessage = rule.message
      break
    }
    
    if (rule.pattern && !rule.pattern.test(value)) {
      isValid = false
      errorMessage = rule.message
      break
    }
    
    if (rule.validator && !rule.validator(value)) {
      isValid = false
      errorMessage = rule.message
      break
    }
  }

  if (isValid) {
    delete errors.value[field]
  } else {
    errors.value[field] = errorMessage
  }

  validateAll()
  return isValid
}

const validateAll = () => {
  const requiredFields = Object.keys(validationRules)
  const allValid = requiredFields.every(field => validateField(field))
  emit('validation', allValid)
}

// Format number helper
const formatNumber = (num) => {
  return Number(num || 0).toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })
}

// Watch for changes and emit to parent
watch(localForm, (newValue) => {
  emit('update:formData', newValue)
  validateAll()
}, { deep: true })

// Initialize validation
validateAll()
</script>