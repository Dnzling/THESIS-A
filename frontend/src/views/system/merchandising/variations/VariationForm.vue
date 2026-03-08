<template>
  <div class="max-w-4xl mx-auto space-y-6 pb-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">
          {{ isEditMode ? 'Edit Variation' : 'Add New Variation' }}
        </h2>
        <p class="text-sm text-gray-500 mt-1">
          {{ isEditMode ? 'Update variation details' : 'Create a new product variation' }}
        </p>
      </div>
      <Button 
        label="Back" 
        icon="pi pi-arrow-left" 
        text 
        @click="$router.push({ name: 'merchandising.variations' })" 
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
            
            <!-- Product Selection -->
            <div class="flex flex-col gap-2">
              <label for="product_id" class="text-sm font-semibold text-gray-700">
                Product <span class="text-red-500">*</span>
              </label>
              <Select 
                id="product_id"
                v-model="form.product_id" 
                :options="products" 
                optionLabel="product_name" 
                optionValue="id"
                placeholder="Select a product" 
                :class="{ 'p-invalid': errors.product_id }"
                :loading="loadingProducts"
                filter
                @change="onProductChange"
              />
              <small v-if="errors.product_id" class="text-red-500">{{ errors.product_id }}</small>
            </div>

            <!-- Variation SKU (Auto-generated) -->
            <div class="flex flex-col gap-2">
              <label for="variation_sku" class="text-sm font-semibold text-gray-700">
                Variation SKU <span class="text-red-500">*</span>
              </label>
              <InputText 
                id="variation_sku"
                v-model="form.variation_sku" 
                placeholder="Will be auto-generated" 
                :class="{ 'p-invalid': errors.variation_sku }"
                readonly
                class="bg-gray-100"
              />
              <small class="text-gray-500">Auto-generated from product SKU and attributes</small>
              <small v-if="errors.variation_sku" class="text-red-500">{{ errors.variation_sku }}</small>
            </div>

            <!-- Variation Name -->
            <div class="flex flex-col gap-2">
              <label for="variation_name" class="text-sm font-semibold text-gray-700">
                Variation Name <span class="text-red-500">*</span>
              </label>
              <InputText 
                id="variation_name"
                v-model="form.variation_name" 
                placeholder="e.g., Navy Blue - Large, Oak Wood Finish" 
                :class="{ 'p-invalid': errors.variation_name }"
              />
              <small v-if="errors.variation_name" class="text-red-500">{{ errors.variation_name }}</small>
            </div>

          </div>
        </template>
      </Card>

      <!-- Attributes Card -->
      <Card class="mb-6">
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-palette text-purple-600"></i>
            <span>Variation Attributes</span>
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            
            <!-- Color -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex flex-col gap-2">
                <label for="color" class="text-sm font-semibold text-gray-700">
                  Color
                </label>
                <InputText 
                  id="color"
                  v-model="form.color" 
                  placeholder="e.g., Navy Blue, Charcoal Gray" 
                  @input="generateSKU"
                />
              </div>

              <div class="flex flex-col gap-2">
                <label for="color_hex" class="text-sm font-semibold text-gray-700">
                  Color Code (Hex)
                </label>
                <div class="flex gap-2 items-center">
                  <input 
                    type="color"
                    v-model="form.color_hex"
                    class="h-10 w-16 rounded border border-gray-300 cursor-pointer"
                  />
                  <InputText 
                    id="color_hex"
                    v-model="form.color_hex" 
                    placeholder="#000000" 
                    class="flex-1 font-mono"
                    maxlength="7"
                  />
                </div>
              </div>
            </div>

            <!-- Size & Material -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex flex-col gap-2">
                <label for="size" class="text-sm font-semibold text-gray-700">
                  Size
                </label>
                <InputText 
                  id="size"
                  v-model="form.size" 
                  placeholder="e.g., Small, Medium, Large, XL" 
                  @input="generateSKU"
                />
              </div>

              <div class="flex flex-col gap-2">
                <label for="material" class="text-sm font-semibold text-gray-700">
                  Material
                </label>
                <InputText 
                  id="material"
                  v-model="form.material" 
                  placeholder="e.g., Leather, Fabric, Wood" 
                  @input="generateSKU"
                />
              </div>
            </div>

            <!-- Finish & Pattern -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex flex-col gap-2">
                <label for="finish" class="text-sm font-semibold text-gray-700">
                  Finish
                </label>
                <InputText 
                  id="finish"
                  v-model="form.finish" 
                  placeholder="e.g., Matte, Glossy, Satin" 
                  @input="generateSKU"
                />
              </div>

              <div class="flex flex-col gap-2">
                <label for="pattern" class="text-sm font-semibold text-gray-700">
                  Pattern
                </label>
                <InputText 
                  id="pattern"
                  v-model="form.pattern" 
                  placeholder="e.g., Solid, Striped, Checkered" 
                />
              </div>
            </div>

          </div>
        </template>
      </Card>

      <!-- Pricing Card -->
      <Card class="mb-6">
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-dollar text-green-600"></i>
            <span>Pricing</span>
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            
            <div v-if="basePrice" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <p class="text-sm font-semibold text-blue-900">Base Product Price: ₱{{ formatPrice(basePrice) }}</p>
            </div>

            <div class="flex flex-col gap-2">
              <label for="price_adjustment" class="text-sm font-semibold text-gray-700">
                Price Adjustment (₱)
              </label>
              <InputNumber 
                id="price_adjustment"
                v-model="form.price_adjustment" 
                mode="currency" 
                currency="PHP" 
                locale="en-PH"
                :minFractionDigits="2"
                fluid
              />
              <small class="text-gray-500">Add or subtract from base price. Use negative values for discounts.</small>
            </div>

            <div v-if="finalPrice !== null" class="bg-green-50 border border-green-200 rounded-lg p-4">
              <p class="text-sm font-semibold text-green-900">Final Price: ₱{{ formatPrice(finalPrice) }}</p>
            </div>

          </div>
        </template>
      </Card>

      <!-- Stock & Dimensions Card -->
      <Card class="mb-6">
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-box text-orange-600"></i>
            <span>Stock & Dimensions</span>
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            
            <!-- Stock Quantity -->
            <div class="flex flex-col gap-2">
              <label for="stock_quantity" class="text-sm font-semibold text-gray-700">
                Stock Quantity <span class="text-red-500">*</span>
              </label>
              <InputNumber 
                id="stock_quantity"
                v-model="form.stock_quantity" 
                :min="0"
                showButtons
                :class="{ 'p-invalid': errors.stock_quantity }"
              />
              <small v-if="errors.stock_quantity" class="text-red-500">{{ errors.stock_quantity }}</small>
            </div>

            <!-- Dimensions -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div class="flex flex-col gap-2">
                <label for="weight" class="text-sm font-semibold text-gray-700">
                  Weight (kg)
                </label>
                <InputNumber 
                  id="weight"
                  v-model="form.weight_kg" 
                  :minFractionDigits="2"
                  suffix=" kg"
                  :min="0"
                  fluid
                />
              </div>

              <div class="flex flex-col gap-2">
                <label for="length" class="text-sm font-semibold text-gray-700">
                  Length (cm)
                </label>
                <InputNumber 
                  id="length"
                  v-model="form.length_cm" 
                  :minFractionDigits="2"
                  suffix=" cm"
                  :min="0"
                  fluid
                />
              </div>

              <div class="flex flex-col gap-2">
                <label for="width" class="text-sm font-semibold text-gray-700">
                  Width (cm)
                </label>
                <InputNumber 
                  id="width"
                  v-model="form.width_cm" 
                  :minFractionDigits="2"
                  suffix=" cm"
                  :min="0"
                  fluid
                />
              </div>

              <div class="flex flex-col gap-2">
                <label for="height" class="text-sm font-semibold text-gray-700">
                  Height (cm)
                </label>
                <InputNumber 
                  id="height"
                  v-model="form.height_cm" 
                  :minFractionDigits="2"
                  suffix=" cm"
                  :min="0"
                  fluid
                />
              </div>
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
      <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
        <Button 
          label="Cancel" 
          severity="secondary" 
          outlined 
          @click="$router.push({ name: 'merchandising.variations' })" 
        />
        <Button 
          :label="isEditMode ? 'Update Variation' : 'Create Variation'" 
          icon="pi pi-check" 
          @click="handleSubmit"
          :loading="submitting"
        />
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import merchandisingService from '../../../../services/merchandising.service'

import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import Skeleton from 'primevue/skeleton'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const isEditMode = computed(() => !!route.params.id)
const submitting = ref(false)
const loadingData = ref(false)
const loadingProducts = ref(false)
const products = ref([])
const selectedProduct = ref(null)

const form = reactive({
  product_id: null,
  variation_sku: '',
  variation_name: '',
  color: '',
  color_hex: '#3B82F6',
  size: '',
  material: '',
  finish: '',
  pattern: '',
  price_adjustment: 0,
  stock_quantity: 0,
  weight_kg: null,
  length_cm: null,
  width_cm: null,
  height_cm: null,
  is_active: true
})

const errors = ref<Record<string, string>>({})

const basePrice = computed(() => {
  return selectedProduct.value?.base_price || 0
})

const finalPrice = computed(() => {
  if (!basePrice.value) return null
  return basePrice.value + (form.price_adjustment || 0)
})

// Watch product selection
watch(() => form.product_id, (newVal) => {
  if (newVal) {
    selectedProduct.value = products.value.find(p => p.id === newVal)
    generateSKU()
  }
})

const loadProducts = async () => {
  loadingProducts.value = true
  try {
    const response = await merchandisingService.getProducts({ per_page: 1000 })
    products.value = response.data.data
  } catch (error) {
    console.error('Failed to load products:', error)
  } finally {
    loadingProducts.value = false
  }
}

const loadVariation = async () => {
  if (!isEditMode.value) return
  
  loadingData.value = true
  try {
    const response = await merchandisingService.getVariation(Number(route.params.id))
    const variation = response.data
    
    Object.assign(form, {
      product_id: variation.product_id,
      variation_sku: variation.variation_sku,
      variation_name: variation.variation_name,
      color: variation.color || '',
      color_hex: variation.color_hex || '#3B82F6',
      size: variation.size || '',
      material: variation.material || '',
      finish: variation.finish || '',
      pattern: variation.pattern || '',
      price_adjustment: variation.price_adjustment || 0,
      stock_quantity: variation.stock_quantity || 0,
      weight_kg: variation.weight_kg,
      length_cm: variation.length_cm,
      width_cm: variation.width_cm,
      height_cm: variation.height_cm,
      is_active: variation.is_active
    })

    selectedProduct.value = variation.product
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load variation',
      life: 5000
    })
    router.push({ name: 'merchandising.variations' })
  } finally {
    loadingData.value = false
  }
}

const onProductChange = () => {
  generateSKU()
}

const generateSKU = () => {
  if (!selectedProduct.value) return
  
  const baseSKU = selectedProduct.value.sku
  const attributes = [
    form.color?.substring(0, 3).toUpperCase(),
    form.size?.substring(0, 2).toUpperCase(),
    form.material?.substring(0, 3).toUpperCase(),
    form.finish?.substring(0, 2).toUpperCase()
  ].filter(Boolean).join('-')
  
  form.variation_sku = attributes ? `${baseSKU}-${attributes}` : baseSKU
}

const validateForm = () => {
  errors.value = {}
  
  if (!form.product_id) {
    errors.value.product_id = 'Please select a product'
  }
  
  if (!form.variation_name) {
    errors.value.variation_name = 'Variation name is required'
  }
  
  if (!form.variation_sku) {
    errors.value.variation_sku = 'Variation SKU is required'
  }
  
  if (form.stock_quantity === null || form.stock_quantity < 0) {
    errors.value.stock_quantity = 'Stock quantity must be 0 or greater'
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
    // Calculate final price
    const submitData = {
      ...form,
      final_price: finalPrice.value
    }

    if (isEditMode.value) {
      await merchandisingService.updateVariation(Number(route.params.id), submitData)
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Variation updated successfully',
        life: 3000
      })
    } else {
      await merchandisingService.createVariation(submitData)
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Variation created successfully',
        life: 3000
      })
    }
    
    router.push({ name: 'merchandising.variations' })
  } catch (error: any) {
    console.error('Form submission error:', error)
    
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    }
    
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to save variation',
      life: 5000
    })
  } finally {
    submitting.value = false
  }
}

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('en-PH', { 
    minimumFractionDigits: 2,
    maximumFractionDigits: 2 
  }).format(price)
}

onMounted(() => {
  loadProducts()
  loadVariation()
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