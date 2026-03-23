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
                  <ColorPicker v-model="colorHexValue" inputId="color_hex_picker" format="hex" />
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

      <!-- Variant 3D Model Card -->
      <Card class="mb-6">
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-box text-orange-600"></i>
            <span>Variant 3D Model</span>
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            <div class="flex flex-col gap-2">
              <label for="custom_3d_model_id" class="text-sm font-semibold text-gray-700">
                Existing Product 3D Model
              </label>
              <Select
                id="custom_3d_model_id"
                v-model="form.custom_3d_model_id"
                :options="product3DModels"
                optionLabel="file_name"
                optionValue="id"
                placeholder="Use existing 3D model (optional)"
                :disabled="!form.product_id || loadingProductModels"
                :loading="loadingProductModels"
                showClear
                filter
              />
              <small class="text-gray-500">Pick an existing model or upload a new one below for this variant.</small>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
              <div class="flex flex-col gap-2">
                <label for="default_camera_angle_x" class="text-sm font-semibold text-gray-700">Camera X</label>
                <InputNumber
                  id="default_camera_angle_x"
                  v-model="form.default_camera_angle_x"
                  :min="-180"
                  :max="180"
                  suffix=" deg"
                  showButtons
                  buttonLayout="horizontal"
                  :step="5"
                  fluid
                />
              </div>
              <div class="flex flex-col gap-2">
                <label for="default_camera_angle_y" class="text-sm font-semibold text-gray-700">Camera Y</label>
                <InputNumber
                  id="default_camera_angle_y"
                  v-model="form.default_camera_angle_y"
                  :min="-180"
                  :max="180"
                  suffix=" deg"
                  showButtons
                  buttonLayout="horizontal"
                  :step="5"
                  fluid
                />
              </div>
              <div class="flex flex-col gap-2">
                <label for="default_zoom_level" class="text-sm font-semibold text-gray-700">Zoom</label>
                <InputNumber
                  id="default_zoom_level"
                  v-model="form.default_zoom_level"
                  :min="0.1"
                  :max="20"
                  :step="0.1"
                  :minFractionDigits="1"
                  showButtons
                  buttonLayout="horizontal"
                  fluid
                />
              </div>
            </div>

            <div class="flex flex-col gap-2">
              <label for="variant_model_upload" class="text-sm font-semibold text-gray-700">
                Upload New 3D Model (Optional)
              </label>
              <InputText
                id="variant_model_upload"
                type="file"
                accept=".glb,.gltf,.obj,.fbx,.usdz"
                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                @change="handleVariantModelSelect"
              />
              <small v-if="variantModelFile" class="text-blue-600">
                Ready: {{ variantModelFile.name }}
              </small>
              <small class="text-gray-500">If uploaded, this will be linked automatically to the variant.</small>
            </div>

            <div v-if="selected3DModel?.previewUrl" class="space-y-2">
              <p class="text-sm font-semibold text-gray-700">
                3D Preview: {{ selected3DModel.file_name || 'Selected Model' }}
              </p>
              <Model3DPreview
                :model-url="selected3DModel.previewUrl"
                :model-format="selected3DModel.model_format"
                :camera-x="form.default_camera_angle_x"
                :camera-y="form.default_camera_angle_y"
                :zoom="form.default_zoom_level"
                height="280px"
              />
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
import ColorPicker from 'primevue/colorpicker'
import Model3DPreview from '../../../../components/merchandising/Model3DPreview.vue'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const isEditMode = computed(() => !!route.params.id)
const submitting = ref(false)
const loadingData = ref(false)
const loadingProducts = ref(false)
const loadingProductModels = ref(false)
const uploadingModel = ref(false)
const products = ref([])
const product3DModels = ref<any[]>([])
const selectedProduct = ref(null)
const variantModelFile = ref<File | null>(null)

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
  custom_3d_model_id: null as number | null,
  default_camera_angle_x: 0,
  default_camera_angle_y: 15,
  default_zoom_level: 1.5,
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

const colorHexValue = computed({
  get: () => String(form.color_hex || '').replace('#', ''),
  set: (value: string) => {
    form.color_hex = value ? `#${value}` : ''
  }
})

const getModelUrl = (asset: any): string => {
  return asset?.url || asset?.auth_url || asset?.file_url || asset?.model_url || ''
}

const selected3DModel = computed(() => {
  const selected = product3DModels.value.find((asset: any) => Number(asset.id) === Number(form.custom_3d_model_id))
  if (!selected) return null
  return {
    ...selected,
    previewUrl: getModelUrl(selected),
  }
})

// Watch product selection
watch(() => form.product_id, (newVal) => {
  if (newVal) {
    selectedProduct.value = products.value.find((p: any) => p.id === newVal)
    generateSKU()
    loadProductModels(newVal)
  } else {
    product3DModels.value = []
    form.custom_3d_model_id = null
  }
})

watch(
  () => form.custom_3d_model_id,
  (newVal) => {
    if (!newVal) return
    const selected = product3DModels.value.find((asset: any) => Number(asset.id) === Number(newVal))
    if (!selected) return
    form.default_camera_angle_x = Number(selected.default_camera_angle_x ?? 0)
    form.default_camera_angle_y = Number(selected.default_camera_angle_y ?? 15)
    form.default_zoom_level = Number(selected.default_zoom_level ?? 1.5)
  }
)

const loadProducts = async () => {
  loadingProducts.value = true
  try {
    const response = await merchandisingService.getProducts({ per_page: 1000 })
    const payload = response.data || {}
    products.value = Array.isArray(payload?.data) ? payload.data : (payload?.data?.data || [])
  } catch (error) {
    console.error('Failed to load products:', error)
  } finally {
    loadingProducts.value = false
  }
}

const loadProductModels = async (productId: number) => {
  if (!productId) return

  loadingProductModels.value = true
  try {
    const response = await merchandisingService.getAssetsByProduct(productId)
    const payload = response.data || {}
    const grouped = payload.assets_by_type?.['3D_Model'] || []
    const flatAssets = payload.assets || []
    product3DModels.value = grouped.length
      ? grouped
      : flatAssets.filter((asset: any) => asset.asset_type === '3D_Model')
  } catch (error) {
    console.error('Failed to load product 3D models:', error)
    product3DModels.value = []
  } finally {
    loadingProductModels.value = false
  }
}

const loadVariation = async () => {
  if (!isEditMode.value) return
  
  loadingData.value = true
  try {
    const response = await merchandisingService.getVariation(Number(route.params.id))
    const payload = response.data || {}
    const variation = payload?.data || payload || {}
    
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
      custom_3d_model_id: variation.custom_3d_model_id || null,
      default_camera_angle_x: Number(variation?.custom_3d_model?.default_camera_angle_x ?? 0),
      default_camera_angle_y: Number(variation?.custom_3d_model?.default_camera_angle_y ?? 15),
      default_zoom_level: Number(variation?.custom_3d_model?.default_zoom_level ?? 1.5),
      is_active: variation.is_active
    })

    selectedProduct.value = variation.product
    if (variation.product_id) {
      await loadProductModels(variation.product_id)
    }
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

const handleVariantModelSelect = (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input?.files?.[0] || null
  if (file && file.size > 100 * 1024 * 1024) {
    toast.add({
      severity: 'warn',
      summary: 'File too large',
      detail: '3D model must be 100MB or smaller.',
      life: 3000
    })
    input.value = ''
    variantModelFile.value = null
    return
  }
  variantModelFile.value = file
}

const uploadVariantModelIfNeeded = async (): Promise<number | null> => {
  if (!variantModelFile.value) return form.custom_3d_model_id || null
  if (!form.product_id) return null

  uploadingModel.value = true
  try {
    const formData = new FormData()
    formData.append('product_id', String(form.product_id))
    formData.append('asset_type', '3D_Model')
    formData.append('asset_file', variantModelFile.value)
    const ext = variantModelFile.value.name.split('.').pop()?.toLowerCase() || 'glb'
    if (['glb', 'gltf', 'obj', 'fbx', 'usdz'].includes(ext)) {
      formData.append('model_format', ext)
    }
    formData.append('is_primary', '0')
    formData.append('default_camera_angle_x', String(form.default_camera_angle_x ?? 0))
    formData.append('default_camera_angle_y', String(form.default_camera_angle_y ?? 15))
    formData.append('default_zoom_level', String(form.default_zoom_level ?? 1.5))

    const response = await merchandisingService.uploadAsset(formData)
    const uploaded = response.data || null
    return uploaded?.id || null
  } catch (error: any) {
    const validationErrors = error?.response?.data?.errors
    const firstValidationMessage = validationErrors
      ? Object.values(validationErrors)?.[0]?.[0]
      : null
    toast.add({
      severity: 'error',
      summary: 'Model Upload Failed',
      detail: firstValidationMessage || error?.response?.data?.message || 'Unable to upload variant 3D model',
      life: 4000
    })
    throw error
  } finally {
    uploadingModel.value = false
  }
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

const ensureUniqueVariationSku = async () => {
  if (!form.product_id || !form.variation_sku) return

  try {
    const response = await merchandisingService.getVariationsByProduct(Number(form.product_id))
    const payload = response.data || {}
    const rows = payload?.variations || payload?.data?.variations || []

    const currentId = isEditMode.value ? Number(route.params.id) : null
    const existing = rows
      .filter((v: any) => !currentId || Number(v.id) !== currentId)
      .map((v: any) => String(v.variation_sku || '').toUpperCase())

    let candidate = String(form.variation_sku).toUpperCase()
    if (!existing.includes(candidate)) {
      form.variation_sku = candidate
      return
    }

    let idx = 2
    while (existing.includes(`${candidate}-${idx}`)) idx++
    form.variation_sku = `${candidate}-${idx}`
  } catch {
    // non-blocking; backend will still enforce uniqueness
  }
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
    await ensureUniqueVariationSku()
    const uploadedModelId = await uploadVariantModelIfNeeded()

    const submitData = {
      ...form,
      custom_3d_model_id: uploadedModelId,
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
    const firstValidationMessage = error?.response?.data?.errors
      ? Object.values(error.response.data.errors)?.[0]?.[0]
      : null
    
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: firstValidationMessage || error.response?.data?.message || 'Failed to save variation',
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
  Promise.resolve(loadProducts()).then(async () => {
    const productFromQuery = route.query.product_id ? Number(route.query.product_id) : null
    if (!isEditMode.value && productFromQuery) {
      form.product_id = productFromQuery
      selectedProduct.value = products.value.find((p: any) => p.id === productFromQuery) || null
      await loadProductModels(productFromQuery)
    }
    await loadVariation()
  })
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
