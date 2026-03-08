<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <!-- Loading Skeleton -->
    <div v-if="loadingData" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 space-y-6">
        <Skeleton height="400px" class="rounded-lg" />
        <Skeleton height="300px" class="rounded-lg" />
      </div>
      <div class="lg:col-span-1">
        <Skeleton height="600px" class="rounded-lg" />
      </div>
    </div>
  
    <!-- Form -->
    <form v-else @submit.prevent="handleSubmit">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- LEFT COLUMN (2/3) - Basic Info, Pricing, Specs, SEO -->
        <div class="lg:col-span-2 space-y-6">
  
          <!-- Basic Information Card -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-info-circle text-blue-600"></i>
                <span>Basic Information</span>
              </div>
            </template>
            <template #content>
              <div class="space-y-4">
                <!-- Product Name -->
                <div class="flex flex-col gap-2">
                  <label for="product_name" class="text-sm font-semibold text-gray-700">
                    Product Name <span class="text-red-500">*</span>
                  </label>
                  <InputText id="product_name" v-model="form.product_name"
                    placeholder="e.g., Modern L-Shaped Sectional Sofa" :class="{ 'p-invalid': errors.product_name }"
                    @input="generateSKU" />
                  <small v-if="errors.product_name" class="text-red-500">{{ errors.product_name }}</small>
                </div>
  
                <!-- SKU (Auto-generated) -->
                <div class="flex flex-col gap-2">
                  <label for="sku" class="text-sm font-semibold text-gray-700">
                    SKU (Auto-generated) <span class="text-red-500">*</span>
                  </label>
                  <div class="flex gap-2">
                    <InputText id="sku" v-model="form.sku" placeholder="Will be auto-generated"
                      :class="{ 'p-invalid': errors.sku }" readonly class="flex-1" />
                    <Button icon="pi pi-copy" v-tooltip.top="'Copy SKU'" severity="secondary" outlined @click="copySKU"
                      :disabled="!form.sku" />
                    <Button icon="pi pi-refresh" v-tooltip.top="'Regenerate SKU'" severity="secondary" outlined
                      @click="generateSKU" :disabled="!form.product_name || !form.category_id" />
                  </div>
                  <small class="text-gray-500">Format: CATEGORY-ATTRIBUTE-001</small>
                  <small v-if="errors.sku" class="text-red-500">{{ errors.sku }}</small>
                </div>
  
                <!-- Category & Subcategory -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="flex flex-col gap-2">
                    <label for="category" class="text-sm font-semibold text-gray-700">
                      Category <span class="text-red-500">*</span>
                    </label>
                    <Select id="category" v-model="form.category_id" :options="categories" optionLabel="category_name"
                      optionValue="id" placeholder="Select a category" :class="{ 'p-invalid': errors.category_id }"
                      :loading="loadingCategories" @change="onCategoryChange" />
                    <small v-if="errors.category_id" class="text-red-500">{{ errors.category_id }}</small>
                  </div>
  
                  <div class="flex flex-col gap-2">
                    <label for="subcategory" class="text-sm font-semibold text-gray-700">
                      Subcategory
                    </label>
                    <Select id="subcategory" v-model="form.subcategory_id" :options="subcategories"
                      optionLabel="category_name" optionValue="id" placeholder="Select subcategory" showClear
                      :disabled="!form.category_id" />
                  </div>
                </div>
  
                <!-- Brand & Collection -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="flex flex-col gap-2">
                    <label for="brand" class="text-sm font-semibold text-gray-700">
                      Brand
                    </label>
                    <InputText id="brand" v-model="form.brand" placeholder="e.g., IKEA, Ashley Furniture"
                      @input="generateSKU" />
                  </div>
  
                  <div class="flex flex-col gap-2">
                    <label for="collection" class="text-sm font-semibold text-gray-700">
                      Collection Name
                    </label>
                    <InputText id="collection" v-model="form.collection_name" placeholder="e.g., Summer 2024" />
                  </div>
                </div>
  
                <!-- Stock Status -->
                <div class="flex flex-col gap-2">
                  <label for="stock_status" class="text-sm font-semibold text-gray-700">
                    Stock Status <span class="text-red-500">*</span>
                  </label>
                  <Select id="stock_status" v-model="form.stock_status" :options="stockStatusOptions"
                    placeholder="Select stock status" />
                </div>
  
                <!-- Description -->
                <div class="flex flex-col gap-2">
                  <label for="description" class="text-sm font-semibold text-gray-700">
                    Description
                  </label>
                  <Textarea id="description" v-model="form.description" rows="5"
                    placeholder="Enter detailed product description..." />
                </div>
  
                <!-- Product Flags -->
                <div class="flex flex-wrap gap-4 pt-2">
                  <div class="flex items-center gap-2">
                    <Checkbox v-model="form.is_featured" inputId="featured" :binary="true" />
                    <label for="featured" class="text-sm font-medium cursor-pointer">Featured</label>
                  </div>
                  <div class="flex items-center gap-2">
                    <Checkbox v-model="form.is_new_arrival" inputId="newArrival" :binary="true" />
                    <label for="newArrival" class="text-sm font-medium cursor-pointer">New Arrival</label>
                  </div>
                  <div class="flex items-center gap-2">
                    <Checkbox v-model="form.is_bestseller" inputId="bestseller" :binary="true" />
                    <label for="bestseller" class="text-sm font-medium cursor-pointer">Bestseller</label>
                  </div>
                  <div class="flex items-center gap-2">
                    <Checkbox v-model="form.assembly_required" inputId="assembly" :binary="true" />
                    <label for="assembly" class="text-sm font-medium cursor-pointer">Assembly Required</label>
                  </div>
                  <div class="flex items-center gap-2">
                    <Checkbox v-model="form.is_active" inputId="active" :binary="true" />
                    <label for="active" class="text-sm font-medium cursor-pointer">Active</label>
                  </div>
                </div>
              </div>
            </template>
          </Card>
  
          <!-- Pricing Card -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-dollar text-green-600"></i>
                <span>Pricing</span>
              </div>
            </template>
            <template #content>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex flex-col gap-2">
                  <label for="base_price" class="text-sm font-semibold text-gray-700">
                    Base Price (₱) <span class="text-red-500">*</span>
                  </label>
                  <InputNumber id="base_price" v-model="form.base_price" mode="currency" currency="PHP" locale="en-PH"
                    :class="{ 'p-invalid': errors.base_price }" :min="0" fluid />
                  <small v-if="errors.base_price" class="text-red-500">{{ errors.base_price }}</small>
                </div>
  
                <div class="flex flex-col gap-2">
                  <label for="discounted_price" class="text-sm font-semibold text-gray-700">
                    Discounted Price (₱)
                  </label>
                  <InputNumber id="discounted_price" v-model="form.discounted_price" mode="currency" currency="PHP"
                    locale="en-PH" :min="0" fluid />
                  <small class="text-gray-500 text-xs">Leave empty if no discount</small>
                </div>
  
                <div class="flex flex-col gap-2">
                  <label for="tax_rate" class="text-sm font-semibold text-gray-700">
                    Tax Rate (%)
                  </label>
                  <InputNumber id="tax_rate" v-model="form.tax_rate" suffix="%" :min="0" :max="100" :minFractionDigits="2"
                    fluid />
                </div>
              </div>
  
              <!-- Price Change Reason (only when editing and price changed) -->
              <div v-if="isEditMode && originalBasePrice !== form.base_price" class="mt-4">
                <label for="price_change_reason" class="text-sm font-semibold text-gray-700 block mb-2">
                  Price Change Reason <span class="text-red-500">*</span>
                </label>
                <InputText id="price_change_reason" v-model="form.price_change_reason"
                  placeholder="e.g., Seasonal discount, Supplier cost increase" class="w-full" />
              </div>
            </template>
          </Card>
  
          <!-- Specifications Card -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-box text-purple-600"></i>
                <span>Specifications</span>
              </div>
            </template>
            <template #content>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="flex flex-col gap-2">
                  <label for="length" class="text-sm font-semibold text-gray-700">
                    Length (cm)
                  </label>
                  <InputNumber id="length" v-model="form.length_cm" :minFractionDigits="2" suffix=" cm" :min="0" fluid />
                </div>
  
                <div class="flex flex-col gap-2">
                  <label for="width" class="text-sm font-semibold text-gray-700">
                    Width (cm)
                  </label>
                  <InputNumber id="width" v-model="form.width_cm" :minFractionDigits="2" suffix=" cm" :min="0" fluid />
                </div>
  
                <div class="flex flex-col gap-2">
                  <label for="height" class="text-sm font-semibold text-gray-700">
                    Height (cm)
                  </label>
                  <InputNumber id="height" v-model="form.height_cm" :minFractionDigits="2" suffix=" cm" :min="0" fluid />
                </div>
  
                <div class="flex flex-col gap-2">
                  <label for="weight" class="text-sm font-semibold text-gray-700">
                    Weight (kg)
                  </label>
                  <InputNumber id="weight" v-model="form.weight_kg" :minFractionDigits="2" suffix=" kg" :min="0" fluid />
                </div>
              </div>
            </template>
          </Card>
  
          <!-- SEO Card -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-search text-orange-600"></i>
                <span>SEO & Metadata</span>
              </div>
            </template>
            <template #content>
              <div class="space-y-4">
                <div class="flex flex-col gap-2">
                  <label for="meta_title" class="text-sm font-semibold text-gray-700">
                    Meta Title
                  </label>
                  <InputText id="meta_title" v-model="form.meta_title" placeholder="SEO optimized title" maxlength="60" />
                  <small class="text-gray-500">{{ form.meta_title?.length || 0 }}/60 characters</small>
                </div>
  
                <div class="flex flex-col gap-2">
                  <label for="meta_description" class="text-sm font-semibold text-gray-700">
                    Meta Description
                  </label>
                  <Textarea id="meta_description" v-model="form.meta_description" rows="3"
                    placeholder="SEO optimized description" maxlength="160" />
                  <small class="text-gray-500">{{ form.meta_description?.length || 0 }}/160 characters</small>
                </div>
  
                <div class="flex flex-col gap-2">
                  <label for="meta_keywords" class="text-sm font-semibold text-gray-700">
                    Meta Keywords
                  </label>
                  <InputText id="meta_keywords" v-model="form.meta_keywords"
                    placeholder="furniture, sofa, modern, living room" />
                  <small class="text-gray-500">Separate keywords with commas</small>
                </div>
  
                <div class="flex flex-col gap-2">
                  <label for="published_at" class="text-sm font-semibold text-gray-700">
                    Publish Date
                  </label>
                  <DatePicker id="published_at" v-model="form.published_at" showTime hourFormat="24" class="w-full" />
                </div>
              </div>
            </template>
          </Card>
  
        </div>
  
        <!-- RIGHT COLUMN (1/3) - 3D Models & Assets -->
        <div class="lg:col-span-1">
          <div class="sticky top-6">
            <Card>
              <template #title>
                <div class="flex items-center gap-2">
                  <i class="pi pi-cube text-indigo-600"></i>
                  <span>3D Model & Assets</span>
                </div>
              </template>
              <template #content>
                <div class="space-y-6">
  
                  <!-- Primary 3D Model Upload -->
                  <div class="flex flex-col gap-3">
                    <label class="text-sm font-semibold text-gray-700">
                      Primary 3D Model <span class="text-red-500">*</span>
                    </label>
  
                    <!-- Upload Area -->
                    <div v-if="!form.modelFile && !existingModel"
                      class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 transition-colors cursor-pointer"
                      @click="$refs.modelInput.click()" @dragover.prevent @drop.prevent="handleModelDrop">
                      <i class="pi pi-cloud-upload text-4xl text-gray-400 mb-3 block"></i>
                      <p class="text-sm font-medium text-gray-700 mb-1">Drop 3D model here</p>
                      <p class="text-xs text-gray-500">or click to browse</p>
                      <p class="text-xs text-gray-400 mt-2">Supported: GLB, GLTF (Max 100MB)</p>
                    </div>
                    <input ref="modelInput" type="file" accept=".glb,.gltf" class="hidden" @change="handleModelSelect" />
  
                    <!-- Uploaded Model Preview -->
                    <div v-if="form.modelFile"
                      class="bg-linear-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4">
                      <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                          <div class="bg-blue-600 rounded-lg p-2">
                            <i class="pi pi-cube text-white text-xl"></i>
                          </div>
                          <div>
                            <p class="text-sm font-semibold text-gray-800">{{ form.modelFile.name }}</p>
                            <p class="text-xs text-gray-600">{{ formatFileSize(form.modelFile.size) }}</p>
                          </div>
                        </div>
                        <Button icon="pi pi-times" severity="danger" text rounded size="small" @click="removeModel" />
                      </div>
                      <div class="flex items-center gap-2 text-xs text-green-700 bg-green-50 px-3 py-2 rounded">
                        <i class="pi pi-check-circle"></i>
                        <span>Ready to upload</span>
                      </div>
                    </div>
  
                    <!-- Existing Model (Edit Mode) -->
                    <div v-if="existingModel" class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                      <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                          <div class="bg-gray-600 rounded-lg p-2">
                            <i class="pi pi-cube text-white text-xl"></i>
                          </div>
                          <div>
                            <p class="text-sm font-semibold text-gray-800">{{ existingModel.file_name }}</p>
                            <p class="text-xs text-gray-600">{{ formatFileSize(existingModel.file_size_kb * 1024) }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ existingModel.model_format?.toUpperCase() }}</p>
                          </div>
                        </div>
                        <Button icon="pi pi-trash" severity="danger" text rounded size="small"
                          @click="deleteExistingModel" />
                      </div>
                      <Tag value="Uploaded" severity="success" class="w-full justify-center" />
                    </div>
                  </div>
  
                  <!-- Camera Settings -->
                  <div class="border-t border-gray-200 pt-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Camera Settings</h4>
                    <div class="space-y-3">
                      <div class="flex flex-col gap-2">
                        <label class="text-xs font-medium text-gray-600">Camera Angle X (°)</label>
                        <InputNumber v-model="form.default_camera_angle_x" :min="-180" :max="180" suffix="°" showButtons
                          buttonLayout="horizontal" :step="5" />
                      </div>
                      <div class="flex flex-col gap-2">
                        <label class="text-xs font-medium text-gray-600">Camera Angle Y (°)</label>
                        <InputNumber v-model="form.default_camera_angle_y" :min="-180" :max="180" suffix="°" showButtons
                          buttonLayout="horizontal" :step="5" />
                      </div>
                      <div class="flex flex-col gap-2">
                        <label class="text-xs font-medium text-gray-600">Zoom Level</label>
                        <InputNumber v-model="form.default_zoom_level" :min="0.1" :max="10" :minFractionDigits="1"
                          showButtons buttonLayout="horizontal" :step="0.1" />
                      </div>
                    </div>
                  </div>
  
                  <!-- Product Images Upload -->
                  <div class="border-t border-gray-200 pt-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Product Images</h4>
                    <FileUpload mode="basic" name="images[]" accept="image/*" :maxFileSize="5000000" :multiple="true"
                      :auto="false" chooseLabel="Upload Images" class="w-full" @select="handleImageSelect" />
                    <small class="text-gray-500 text-xs block mt-2">JPG, PNG, WebP (Max 5MB each)</small>
  
                    <!-- Image Previews -->
                    <div v-if="form.imageFiles && form.imageFiles.length > 0" class="grid grid-cols-2 gap-2 mt-4">
                      <div v-for="(image, index) in form.imageFiles" :key="index" class="relative group">
                        <img :src="getImagePreview(image)"
                          class="w-full h-24 object-cover rounded-lg border border-gray-200" />
                        <Badge v-if="index === 0" value="Primary" severity="success"
                          class="absolute top-1 left-1 text-xs" />
                        <Button icon="pi pi-times" severity="danger" rounded size="small"
                          class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity"
                          @click="removeImage(index)" />
                      </div>
                    </div>
                  </div>
  
                  <!-- Upload Instructions -->
                  <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <div class="flex items-start gap-2">
                      <i class="pi pi-info-circle text-blue-600 text-sm mt-0.5"></i>
                      <div class="text-xs text-blue-800">
                        <p class="font-semibold mb-1">Upload Tips:</p>
                        <ul class="list-disc list-inside space-y-1">
                          <li>Use optimized GLB format for web</li>
                          <li>Keep models under 50MB for best performance</li>
                          <li>First image will be the primary thumbnail</li>
                        </ul>
                      </div>
                    </div>
                  </div>
  
                </div>
              </template>
            </Card>
          </div>
        </div>
      </div>
  
      <!-- Action Buttons -->
      <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-200">
        <Button label="Cancel" severity="secondary" outlined @click="router.push({ name: 'merchandising.products' })" />
        <Button :label="isEditMode ? 'Update Product' : 'Create Product'" icon="pi pi-check" @click="handleSubmit"
          :loading="submitting" />
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'

import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import FileUpload from 'primevue/fileupload'
import Skeleton from 'primevue/skeleton'
import DatePicker from 'primevue/datepicker'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import merchandisingService from '../../../../services/merchandising.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const isEditMode = computed(() => !!route.params.id)
const submitting = ref(false)
const loadingData = ref(false)
const loadingCategories = ref(false)
const existingModel = ref(null)
const originalBasePrice = ref(0)

const form = ref({
  product_name: '',
  sku: '',
  category_id: null,
  subcategory_id: null,
  brand: '',
  collection_name: '',
  stock_status: 'In Stock',
  description: '',
  base_price: null,
  discounted_price: null,
  tax_rate: 12.00,
  length_cm: null,
  width_cm: null,
  height_cm: null,
  weight_kg: null,
  assembly_required: false,
  is_featured: false,
  is_new_arrival: false,
  is_bestseller: false,
  is_active: true,
  meta_title: '',
  meta_description: '',
  meta_keywords: '',
  published_at: null,
  price_change_reason: '',
  // 3D Model fields
  modelFile: null,
  imageFiles: [],
  default_camera_angle_x: 0,
  default_camera_angle_y: 15,
  default_zoom_level: 1.5
})

const errors = ref<Record<string, string>>({})
const categories = ref([])
const subcategories = computed(() => {
  if (!form.value.category_id) return []
  return categories.value.filter((c: any) => c.parent_category_id === form.value.category_id)
})

const stockStatusOptions = ['In Stock', 'Low Stock', 'Out of Stock', 'Pre-order']

const loadCategories = async () => {
  loadingCategories.value = true
  try {
    const response = await merchandisingService.getCategories()
    categories.value = response.data.data
  } catch (error) {
    console.error('Failed to load categories:', error)
    categories.value = []
  } finally {
    loadingCategories.value = false
  }
}

const loadProduct = async () => {
  if (!isEditMode.value) return

  loadingData.value = true
  try {
    const response = await merchandisingService.getProduct(Number(route.params.id))
    const product = response.data

    // Properly map all fields with date conversion
    Object.assign(form.value, {
      product_name: product.product_name || '',
      sku: product.sku || '',
      category_id: product.category_id,
      subcategory_id: product.subcategory_id,
      brand: product.brand || '',
      collection_name: product.collection_name || '',
      stock_status: product.stock_status || 'In Stock',
      description: product.description || '',
      base_price: product.base_price,
      discounted_price: product.discounted_price,
      tax_rate: product.tax_rate || 12.00,
      length_cm: product.length_cm,
      width_cm: product.width_cm,
      height_cm: product.height_cm,
      weight_kg: product.weight_kg,
      assembly_required: product.assembly_required || false,
      is_featured: product.is_featured || false,
      is_new_arrival: product.is_new_arrival || false,
      is_bestseller: product.is_bestseller || false,
      is_active: product.is_active ?? true,
      meta_title: product.meta_title || '',
      meta_description: product.meta_description || '',
      meta_keywords: product.meta_keywords || '',
      // ✅ Convert string date to Date object for DatePicker
      published_at: product.published_at ? new Date(product.published_at) : null,
      price_change_reason: '',
      // Keep existing 3D settings
      default_camera_angle_x: form.value.default_camera_angle_x,
      default_camera_angle_y: form.value.default_camera_angle_y,
      default_zoom_level: form.value.default_zoom_level
    })

    originalBasePrice.value = product.base_price

    // Load 3D models
    if (product.id) {
      await loadProductAssets(product.id)
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load product',
      life: 5000
    })
    router.push({ name: 'merchandising.products' })
  } finally {
    loadingData.value = false
  }
}

const loadProductAssets = async (productId: number) => {
  try {
    const response = await merchandisingService.getAssetsByProduct(productId)
    const models = response.data.assets_by_type?.['3D_Model'] || []
    if (models.length > 0) {
      existingModel.value = models.find((m: any) => m.is_primary) || models[0]
      if (existingModel.value) {
        form.value.default_camera_angle_x = existingModel.value.default_camera_angle_x || 0
        form.value.default_camera_angle_y = existingModel.value.default_camera_angle_y || 15
        form.value.default_zoom_level = existingModel.value.default_zoom_level || 1.5
      }
    }
  } catch (error) {
    console.error('Failed to load product assets:', error)
  }
}

const onCategoryChange = () => {
  form.value.subcategory_id = null
  generateSKU()
}

// SKU Generation Logic
const generateSKU = async () => {
  if (!form.value.product_name || !form.value.category_id) return

  const category = categories.value.find((c: any) => c.id === form.value.category_id)
  if (!category) return

  // Get category code
  const categoryCode = category.category_code || 'GEN'

  // Get brand initial or use first letter of product name
  const brandCode = form.value.brand
    ? form.value.brand.substring(0, 3).toUpperCase()
    : form.value.product_name.substring(0, 3).toUpperCase()

  // Generate base SKU
  const baseSKU = `${categoryCode}-${brandCode}`

  // Check for uniqueness and get next sequence
  try {
    const response = await merchandisingService.getProducts({ search: baseSKU })
    const existingProducts = response.data.data || []

    // Find highest sequence number
    let maxSequence = 0
    existingProducts.forEach((p: any) => {
      const match = p.sku?.match(new RegExp(`${baseSKU}-(\\d+)`))
      if (match) {
        const seq = parseInt(match[1])
        if (seq > maxSequence) maxSequence = seq
      }
    })

    // Generate new sequence
    const nextSequence = (maxSequence + 1).toString().padStart(3, '0')
    form.value.sku = `${baseSKU}-${nextSequence}`
  } catch (error) {
    // Fallback to random sequence if API fails
    const randomSeq = Math.floor(Math.random() * 1000).toString().padStart(3, '0')
    form.value.sku = `${baseSKU}-${randomSeq}`
  }
}

const copySKU = () => {
  if (!form.value.sku) return
  navigator.clipboard.writeText(form.value.sku)
  toast.add({
    severity: 'success',
    summary: 'Copied!',
    detail: 'SKU copied to clipboard',
    life: 2000
  })
}

const handleModelSelect = (event: any) => {
  const file = event.target.files[0]
  if (!file) return

  if (file.size > 100000000) {
    toast.add({
      severity: 'error',
      summary: 'File too large',
      detail: 'Model file must be less than 100MB',
      life: 3000
    })
    return
  }

  form.value.modelFile = file
}

const handleModelDrop = (event: DragEvent) => {
  const file = event.dataTransfer?.files[0]
  if (!file) return

  if (!file.name.endsWith('.glb') && !file.name.endsWith('.gltf')) {
    toast.add({
      severity: 'error',
      summary: 'Invalid file',
      detail: 'Only GLB and GLTF files are supported',
      life: 3000
    })
    return
  }

  form.value.modelFile = file
}

const removeModel = () => {
  form.value.modelFile = null
  if ($refs.modelInput) {
    $refs.modelInput.value = null
  }
}

const deleteExistingModel = async () => {
  if (!existingModel.value?.id) return

  try {
    await merchandisingService.deleteAsset(existingModel.value.id)
    existingModel.value = null
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: '3D model deleted',
      life: 3000
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to delete model',
      life: 3000
    })
  }
}

const handleImageSelect = (event: any) => {
  form.value.imageFiles = Array.from(event.files)
}

const getImagePreview = (file: File) => {
  return URL.createObjectURL(file)
}

const removeImage = (index: number) => {
  form.value.imageFiles.splice(index, 1)
}

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

const validateForm = () => {
  errors.value = {}

  if (!form.value.product_name) errors.value.product_name = 'Product name is required'
  if (!form.value.sku) errors.value.sku = 'SKU is required'
  if (!form.value.category_id) errors.value.category_id = 'Category is required'
  if (!form.value.base_price || form.value.base_price <= 0) errors.value.base_price = 'Base price must be greater than 0'

  if (isEditMode.value && originalBasePrice.value !== form.value.base_price && !form.value.price_change_reason) {
    errors.value.price_change_reason = 'Price change reason is required'
  }

  if (!isEditMode.value && !form.value.modelFile && !existingModel.value) {
    toast.add({
      severity: 'warn',
      summary: 'Missing 3D Model',
      detail: 'Please upload a primary 3D model',
      life: 3000
    })
    return false
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
    let productId: number

    // ✅ Prepare data for submission - convert Date back to ISO string
    const submitData = {
      product_name: form.value.product_name,
      sku: form.value.sku,
      category_id: form.value.category_id,
      subcategory_id: form.value.subcategory_id,
      brand: form.value.brand,
      collection_name: form.value.collection_name,
      stock_status: form.value.stock_status,
      description: form.value.description,
      base_price: form.value.base_price,
      discounted_price: form.value.discounted_price,
      tax_rate: form.value.tax_rate,
      length_cm: form.value.length_cm,
      width_cm: form.value.width_cm,
      height_cm: form.value.height_cm,
      weight_kg: form.value.weight_kg,
      assembly_required: form.value.assembly_required,
      is_featured: form.value.is_featured,
      is_new_arrival: form.value.is_new_arrival,
      is_bestseller: form.value.is_bestseller,
      is_active: form.value.is_active,
      meta_title: form.value.meta_title,
      meta_description: form.value.meta_description,
      meta_keywords: form.value.meta_keywords,
      // ✅ Convert Date object to ISO string
      published_at: form.value.published_at instanceof Date 
        ? form.value.published_at.toISOString() 
        : form.value.published_at,
      price_change_reason: form.value.price_change_reason
    }

    // Create or update product
    if (isEditMode.value) {
      await merchandisingService.updateProduct(Number(route.params.id), submitData)
      productId = Number(route.params.id)
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Product updated successfully',
        life: 3000
      })
    } else {
      const response = await merchandisingService.createProduct(submitData)
      productId = response.data.id
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Product created successfully',
        life: 3000
      })
    }

    // Upload 3D model if present
    if (form.value.modelFile) {
      await upload3DModel(productId)
    }

    // Upload images if present
    if (form.value.imageFiles && form.value.imageFiles.length > 0) {
      await uploadImages(productId)
    }
    
    router.push({ name: 'merchandising.products' })
  } catch (error: any) {
    console.error('Form submission error:', error)
    
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    }
    
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to save product',
      life: 5000
    })
  } finally {
    submitting.value = false
  }
}

// In ProductForm2.vue - Update these functions

const upload3DModel = async (productId: number) => {
  if (!form.value.modelFile) return

  try {
    const formData = new FormData()
    formData.append('product_id', productId.toString())
    formData.append('asset_type', '3D_Model')
    formData.append('asset_file', form.value.modelFile) // ✅ Note: asset_file not model_file
    formData.append('is_primary', '1') // ✅ Use '1' instead of 'true'
    formData.append('model_format', form.value.modelFile.name.split('.').pop()?.toLowerCase() || 'glb')
    formData.append('default_camera_angle_x', form.value.default_camera_angle_x.toString())
    formData.append('default_camera_angle_y', form.value.default_camera_angle_y.toString())
    formData.append('default_zoom_level', form.value.default_zoom_level.toString())

    console.log('Uploading 3D model:', {
      productId,
      fileName: form.value.modelFile.name,
      size: form.value.modelFile.size
    })

    const response = await merchandisingService.uploadAsset(formData)
    
    console.log('3D model upload response:', response)

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: '3D model uploaded successfully',
      life: 3000
    })
  } catch (error: any) {
    console.error('3D model upload error:', error.response || error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to upload 3D model',
      life: 3000
    })
    throw error // Re-throw to handle in main submit
  }
}

const uploadImages = async (productId: number) => {
  if (!form.value.imageFiles || form.value.imageFiles.length === 0) return

  try {
    for (let i = 0; i < form.value.imageFiles.length; i++) {
      const formData = new FormData()
      formData.append('product_id', productId.toString())
      formData.append('asset_type', i === 0 ? 'Image_Main' : 'Image_Gallery')
      formData.append('asset_file', form.value.imageFiles[i]) // ✅ Note: asset_file
      formData.append('is_primary', i === 0 ? '1' : '0') // ✅ Use '1'/'0'
      formData.append('display_order', i.toString())

      console.log(`Uploading image ${i + 1}/${form.value.imageFiles.length}`)

      await merchandisingService.uploadAsset(formData)
    }

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `${form.value.imageFiles.length} images uploaded successfully`,
      life: 3000
    })
  } catch (error: any) {
    console.error('Image upload error:', error.response || error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to upload some images',
      life: 3000
    })
    throw error
  }
}

onMounted(() => {
  loadCategories()
  loadProduct()
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

.sticky {
  position: sticky;
  top: 1.5rem;
}
</style>