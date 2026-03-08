<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button 
          icon="pi pi-arrow-left" 
          text 
          rounded
          @click="router.push({ name: 'merchandising.products' })" 
        />
        <div>
          <h2 class="text-2xl font-bold text-gray-800">Product Details</h2>
          <p class="text-sm text-gray-500 mt-1">View and manage product information</p>
        </div>
      </div>
      <div class="flex gap-2">
        <Button 
          label="Edit" 
          icon="pi pi-pencil" 
          severity="warning"
          @click="router.push({ name: 'merchandising.products.edit', params: { id: productId } })" 
        />
        <Button 
          label="Delete" 
          icon="pi pi-trash" 
          severity="danger"
          outlined
          @click="confirmDelete" 
        />
      </div>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 space-y-6">
        <Skeleton height="400px" class="rounded-lg" />
        <Skeleton height="300px" class="rounded-lg" />
      </div>
      <div class="lg:col-span-1">
        <Skeleton height="600px" class="rounded-lg" />
      </div>
    </div>

    <!-- Product Content -->
    <div v-else-if="product" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <!-- LEFT COLUMN (2/3) - Product Information -->
      <div class="lg:col-span-2 space-y-6">
        
        <!-- Product Header Card -->
        <Card>
          <template #content>
            <div class="space-y-4">
              <!-- Product Name & Status -->
              <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div>
                  <h1 class="text-3xl font-bold text-gray-900">{{ product.product_name }}</h1>
                  <div class="flex flex-wrap items-center gap-2 mt-2">
                    <Tag :value="product.sku" severity="secondary" class="font-mono" />
                    <Tag :value="product.is_active ? 'Active' : 'Inactive'" 
                         :severity="product.is_active ? 'success' : 'secondary'" />
                    <Tag v-if="product.is_featured" value="Featured" severity="warning" />
                    <Tag v-if="product.is_new_arrival" value="New Arrival" severity="info" />
                    <Tag v-if="product.is_bestseller" value="Bestseller" icon="pi pi-star-fill" severity="success" />
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-3xl font-bold text-green-600">₱{{ formatPrice(product.base_price) }}</p>
                  <p v-if="product.discounted_price" class="text-lg text-gray-500 line-through">
                    ₱{{ formatPrice(product.discounted_price) }}
                  </p>
                  <p v-if="product.tax_rate" class="text-sm text-gray-600 mt-1">
                    Tax: {{ product.tax_rate }}%
                  </p>
                </div>
              </div>

              <!-- Quick Stats -->
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-gray-50 rounded-lg">
                <div>
                  <p class="text-xs text-gray-600 mb-1">Category</p>
                  <p class="text-sm font-semibold text-gray-900">{{ product.category?.category_name || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-600 mb-1">Stock Status</p>
                  <Tag :value="product.stock_status" :severity="getStockSeverity(product.stock_status)" />
                </div>
                <div>
                  <p class="text-xs text-gray-600 mb-1">Brand</p>
                  <p class="text-sm font-semibold text-gray-900">{{ product.brand || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-600 mb-1">Variations</p>
                  <p class="text-sm font-semibold text-gray-900">{{ product.variations_count || 0 }}</p>
                </div>
              </div>

              <!-- Description -->
              <div v-if="product.description">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Description</h3>
                <p class="text-gray-700 leading-relaxed">{{ product.description }}</p>
              </div>
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
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
              <div>
                <p class="text-xs text-gray-600 mb-1">Length</p>
                <p class="text-lg font-semibold text-gray-900">
                  {{ product.length_cm ? `${product.length_cm} cm` : 'N/A' }}
                </p>
              </div>
              <div>
                <p class="text-xs text-gray-600 mb-1">Width</p>
                <p class="text-lg font-semibold text-gray-900">
                  {{ product.width_cm ? `${product.width_cm} cm` : 'N/A' }}
                </p>
              </div>
              <div>
                <p class="text-xs text-gray-600 mb-1">Height</p>
                <p class="text-lg font-semibold text-gray-900">
                  {{ product.height_cm ? `${product.height_cm} cm` : 'N/A' }}
                </p>
              </div>
              <div>
                <p class="text-xs text-gray-600 mb-1">Weight</p>
                <p class="text-lg font-semibold text-gray-900">
                  {{ product.weight_kg ? `${product.weight_kg} kg` : 'N/A' }}
                </p>
              </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
              <Tag v-if="product.assembly_required" value="Assembly Required" severity="info" icon="pi pi-wrench" />
              <Tag v-else value="No Assembly" severity="success" icon="pi pi-check" />
              
              <Tag v-if="product.collection_name" :value="`Collection: ${product.collection_name}`" />
            </div>
          </template>
        </Card>

        <!-- Product Variations -->
        <Card v-if="variations && variations.length > 0">
          <template #title>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <i class="pi pi-th-large text-indigo-600"></i>
                <span>Product Variations</span>
              </div>
              <Button 
                label="Manage Variations" 
                icon="pi pi-cog" 
                size="small"
                text
                @click="manageVariations"
              />
            </div>
          </template>
          <template #content>
            <DataTable :value="variations" class="p-datatable-sm">
              <Column field="variation_name" header="Variation">
                <template #body="{ data }">
                  <div class="flex items-center gap-2">
                    <div 
                      v-if="data.color_hex" 
                      :style="{ backgroundColor: data.color_hex }"
                      class="w-6 h-6 rounded border border-gray-300"
                    ></div>
                    <span class="font-medium">{{ data.variation_name }}</span>
                  </div>
                </template>
              </Column>
              <Column field="variation_sku" header="SKU">
                <template #body="{ data }">
                  <span class="font-mono text-sm">{{ data.variation_sku }}</span>
                </template>
              </Column>
              <Column header="Attributes">
                <template #body="{ data }">
                  <div class="flex flex-wrap gap-1">
                    <Tag v-if="data.color" :value="data.color" severity="info" size="small" />
                    <Tag v-if="data.size" :value="data.size" severity="secondary" size="small" />
                    <Tag v-if="data.material" :value="data.material" severity="success" size="small" />
                  </div>
                </template>
              </Column>
              <Column field="final_price" header="Price">
                <template #body="{ data }">
                  <span class="font-semibold">₱{{ formatPrice(data.final_price || 0) }}</span>
                  <span v-if="data.price_adjustment !== 0" class="text-xs text-gray-600 ml-2">
                    ({{ data.price_adjustment > 0 ? '+' : '' }}₱{{ formatPrice(data.price_adjustment) }})
                  </span>
                </template>
              </Column>
              <Column field="stock_quantity" header="Stock">
                <template #body="{ data }">
                  <Badge :value="data.stock_quantity" :severity="data.stock_quantity > 10 ? 'success' : 'warning'" />
                </template>
              </Column>
              <Column field="is_active" header="Status">
                <template #body="{ data }">
                  <Tag :value="data.is_active ? 'Active' : 'Inactive'" 
                       :severity="data.is_active ? 'success' : 'secondary'" />
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>

        <!-- Additional Information -->
        <Card>
          <template #title>
            <div class="flex items-center gap-2">
              <i class="pi pi-info-circle text-blue-600"></i>
              <span>Additional Information</span>
            </div>
          </template>
          <template #content>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h4 class="text-sm font-semibold text-gray-700 mb-3">SEO</h4>
                <div class="space-y-2">
                  <div>
                    <p class="text-xs text-gray-600">Meta Title</p>
                    <p class="text-sm text-gray-900">{{ product.meta_title || 'Not set' }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-600">Meta Description</p>
                    <p class="text-sm text-gray-900">{{ product.meta_description || 'Not set' }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-600">Keywords</p>
                    <p class="text-sm text-gray-900">{{ product.meta_keywords || 'Not set' }}</p>
                  </div>
                </div>
              </div>
              <div>
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Publishing</h4>
                <div class="space-y-2">
                  <div>
                    <p class="text-xs text-gray-600">Created At</p>
                    <p class="text-sm text-gray-900">{{ formatDate(product.created_at) }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-600">Last Updated</p>
                    <p class="text-sm text-gray-900">{{ formatDate(product.updated_at) }}</p>
                  </div>
                  <div v-if="product.published_at">
                    <p class="text-xs text-gray-600">Published At</p>
                    <p class="text-sm text-gray-900">{{ formatDate(product.published_at) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </Card>

      </div>

      <!-- RIGHT COLUMN (1/3) - 3D Viewer & Assets -->
      <div class="lg:col-span-1">
        <div class="sticky top-6 space-y-6">
          
          <!-- 3D Model Viewer Card -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-cube text-indigo-600"></i>
                <span>3D Model Viewer</span>
              </div>
            </template>
            <template #content>
              <div class="space-y-4">
                <!-- ✅ 3D Viewer Container -->
                <div 
                  v-if="primary3DModel"
                  ref="viewer3DContainer"
                  class="relative bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg overflow-hidden"
                  style="height: 400px;"
                >
                  <!-- Loading Indicator -->
                  <div v-if="loading3D" class="absolute inset-0 flex items-center justify-center bg-white/90 z-10">
                    <div class="text-center">
                      <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" />
                      <p class="text-sm text-gray-600 mt-2">Loading 3D Model...</p>
                    </div>
                  </div>

                  <!-- Error Message -->
                  <div v-if="model3DError" class="absolute inset-0 flex items-center justify-center bg-red-50 z-10">
                    <div class="text-center p-4">
                      <i class="pi pi-exclamation-triangle text-4xl text-red-500 mb-2"></i>
                      <p class="text-sm text-red-700">Failed to load 3D model</p>
                      <Button label="Retry" size="small" class="mt-2" @click="retryLoad3D" />
                    </div>
                  </div>
                  
                  <!-- Controls Overlay -->
                  <div v-if="!loading3D && !model3DError" class="absolute bottom-4 left-4 right-4 bg-white/95 backdrop-blur rounded-lg p-3 shadow-lg z-20">
                    <div class="flex items-center justify-between gap-2">
                      <Button 
                        icon="pi pi-replay" 
                        v-tooltip.top="'Reset View'"
                        text 
                        rounded 
                        size="small"
                        @click="reset3DView"
                      />
                      <Button 
                        icon="pi pi-camera" 
                        v-tooltip.top="'Screenshot'"
                        text 
                        rounded 
                        size="small"
                        @click="take3DScreenshot"
                      />
                      <Button 
                        icon="pi pi-sync" 
                        v-tooltip.top="'Auto Rotate'"
                        text 
                        rounded 
                        size="small"
                        :class="{ 'bg-blue-100': autoRotate }"
                        @click="toggleAutoRotate"
                      />
                      <Button 
                        icon="pi pi-external-link" 
                        v-tooltip.top="'Fullscreen'"
                        text 
                        rounded 
                        size="small"
                        @click="toggle3DFullscreen"
                      />
                    </div>
                  </div>
                </div>

                <!-- No 3D Model -->
                <div v-else class="bg-gray-100 rounded-lg p-8 text-center" style="height: 400px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                  <i class="pi pi-cube text-6xl text-gray-400 mb-4"></i>
                  <p class="text-gray-600 font-medium">No 3D Model Available</p>
                  <p class="text-sm text-gray-500 mt-2">Upload a 3D model to preview</p>
                  <Button 
                    label="Upload 3D Model" 
                    icon="pi pi-upload" 
                    size="small"
                    class="mt-4"
                    @click="router.push({ name: 'merchandising.assets.upload' })"
                  />
                </div>

                <!-- Model Info -->
                <div v-if="primary3DModel" class="bg-gray-50 rounded-lg p-3">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-gray-700">Model Information</span>
                    <Tag :value="primary3DModel.model_format?.toUpperCase()" severity="info" size="small" />
                  </div>
                  <div class="space-y-1 text-xs text-gray-600">
                    <div class="flex justify-between">
                      <span>File Size:</span>
                      <span class="font-medium">{{ formatFileSize(primary3DModel.file_size_kb * 1024) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span>Uploaded:</span>
                      <span class="font-medium">{{ formatDate(primary3DModel.created_at) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span>AR Compatible:</span>
                      <Tag :value="primary3DModel.is_ar_compatible ? 'Yes' : 'No'" 
                           :severity="primary3DModel.is_ar_compatible ? 'success' : 'secondary'" 
                           size="small" />
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </Card>

          <!-- Product Images Gallery -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-images text-pink-600"></i>
                <span>Product Images</span>
              </div>
            </template>
            <template #content>
              <div v-if="productImages && productImages.length > 0" class="space-y-4">
                <!-- Main Image -->
                <div class="relative rounded-lg overflow-hidden bg-gray-100" style="height: 300px;">
                  <img 
                    :src="selectedImage || productImages[0]?.url" 
                    :alt="product.product_name"
                    class="w-full h-full object-cover"
                    @error="handleImageError"
                  />
                  <Button 
                    icon="pi pi-search-plus" 
                    class="absolute top-2 right-2"
                    rounded
                    @click="openImageGallery"
                  />
                </div>

                <!-- Thumbnail Strip -->
                <div class="grid grid-cols-4 gap-2">
                  <div 
                    v-for="(image, index) in productImages.slice(0, 4)" 
                    :key="index"
                    class="relative rounded-lg overflow-hidden cursor-pointer border-2 transition-all"
                    :class="selectedImage === image.url ? 'border-blue-500' : 'border-transparent hover:border-gray-300'"
                    @click="selectedImage = image.url"
                  >
                    <img 
                      :src="image.thumbnail_url || image.url" 
                      :alt="`Thumbnail ${index + 1}`"
                      class="w-full h-20 object-cover"
                      @error="handleImageError"
                    />
                    <Badge v-if="image.is_primary" value="Main" severity="success" class="absolute top-1 left-1 text-xs" />
                  </div>
                </div>
              </div>

              <!-- No Images -->
              <div v-else class="text-center py-8">
                <i class="pi pi-image text-4xl text-gray-400 mb-3 block"></i>
                <p class="text-gray-600">No product images</p>
                <Button 
                  label="Upload Images" 
                  icon="pi pi-upload" 
                  size="small"
                  class="mt-3"
                  @click="router.push({ name: 'merchandising.assets.upload' })"
                />
              </div>
            </template>
          </Card>

          <!-- All Assets List -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-folder text-yellow-600"></i>
                <span>All Assets ({{ allAssets.length }})</span>
              </div>
            </template>
            <template #content>
              <div class="space-y-2 max-h-96 overflow-y-auto">
                <div v-if="!allAssets || allAssets.length === 0" class="text-center py-4 text-gray-500 text-sm">
                  No assets uploaded
                </div>
                <div 
                  v-else
                  v-for="asset in allAssets" 
                  :key="asset.id"
                  class="flex items-center justify-between p-2 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                >
                  <div class="flex items-center gap-2 flex-1 min-w-0">
                    <i :class="getAssetIcon(asset.asset_type)" class="text-gray-600 flex-shrink-0"></i>
                    <div class="min-w-0 flex-1">
                      <p class="text-sm font-medium text-gray-900 truncate">{{ asset.file_name }}</p>
                      <p class="text-xs text-gray-500">{{ getAssetTypeLabel(asset.asset_type) }} • {{ formatFileSize(asset.file_size_kb * 1024) }}</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-1 flex-shrink-0">
                    <Tag v-if="asset.is_primary" value="Primary" severity="success" size="small" />
                    <Button 
                      icon="pi pi-download" 
                      text 
                      rounded 
                      size="small"
                      v-tooltip.top="'Download'"
                      @click="downloadAsset(asset)"
                    />
                  </div>
                </div>
              </div>
            </template>
          </Card>

        </div>
      </div>

    </div>

    <!-- Error State -->
    <div v-else class="text-center py-12">
      <i class="pi pi-exclamation-triangle text-6xl text-red-500 mb-4"></i>
      <h3 class="text-xl font-semibold text-gray-800 mb-2">Product Not Found</h3>
      <p class="text-gray-600 mb-4">The product you're looking for doesn't exist or has been deleted.</p>
      <Button 
        label="Back to Products" 
        icon="pi pi-arrow-left"
        @click="router.push({ name: 'merchandising.products' })"
      />
    </div>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model:visible="deleteDialogVisible" header="Confirm Delete" :modal="true" class="w-96">
      <div class="flex items-center gap-3">
        <i class="pi pi-exclamation-triangle text-4xl text-red-600"></i>
        <div>
          <p class="font-semibold">Are you sure you want to delete this product?</p>
          <p class="text-sm text-gray-600 mt-1">This action cannot be undone. All related data will be removed.</p>
        </div>
      </div>
      <template #footer>
        <Button @click="deleteDialogVisible = false" label="Cancel" severity="secondary" text />
        <Button @click="deleteProduct" label="Delete" severity="danger" :loading="deleting" />
      </template>
    </Dialog>

    <!-- Image Gallery Dialog -->
    <Dialog v-model:visible="galleryVisible" :modal="true" class="w-full max-w-4xl">
      <template #header>
        <div class="flex items-center gap-2">
          <i class="pi pi-images"></i>
          <span>Product Gallery</span>
        </div>
      </template>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <div 
          v-for="(image, index) in productImages" 
          :key="index"
          class="relative rounded-lg overflow-hidden"
        >
          <img 
            :src="image.url" 
            :alt="`Image ${index + 1}`"
            class="w-full h-48 object-cover cursor-pointer hover:opacity-90 transition-opacity"
            @click="selectedImage = image.url; galleryVisible = false"
            @error="handleImageError"
          />
          <Badge v-if="image.is_primary" value="Primary" severity="success" class="absolute top-2 left-2" />
        </div>
      </div>
    </Dialog>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, onBeforeUnmount, nextTick, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import * as THREE from 'three'
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader'
import { OBJLoader } from 'three/examples/jsm/loaders/OBJLoader'
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls'
import merchandisingService from '../../../../services/merchandising.service'

import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import Skeleton from 'primevue/skeleton'
import ProgressSpinner from 'primevue/progressspinner'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const productId = computed(() => Number(route.params.id))
const loading = ref(false)
const loading3D = ref(false)
const model3DError = ref(false)
const deleting = ref(false)
const deleteDialogVisible = ref(false)
const galleryVisible = ref(false)
const autoRotate = ref(false)

const product = ref(null)
const variations = ref([])
const allAssets = ref([])
const primary3DModel = ref(null)
const productImages = ref([])
const selectedImage = ref(null)

// 3D Viewer refs
const viewer3DContainer = ref<HTMLElement | null>(null)
let scene: THREE.Scene | null = null
let camera: THREE.PerspectiveCamera | null = null
let renderer: THREE.WebGLRenderer | null = null
let controls: OrbitControls | null = null
let model: THREE.Object3D | null = null
let animationId: number | null = null

const loadProduct = async () => {
  loading.value = true
  try {
    const response = await merchandisingService.getProduct(productId.value)
    product.value = response.data

    if (response.data.id) {
      await Promise.all([
        loadVariations(),
        loadAssets()
      ])
    }
  } catch (error: any) {
    console.error('Failed to load product:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load product',
      life: 5000
    })
  } finally {
    loading.value = false
  }
}

const loadVariations = async () => {
  try {
    const response = await merchandisingService.getVariationsByProduct(productId.value)
    variations.value = response.data.variations || response.data.data || []
  } catch (error) {
    console.error('Failed to load variations:', error)
  }
}

const loadAssets = async () => {
  try {
    const response = await merchandisingService.getAssetsByProduct(productId.value)
    allAssets.value = response.data.all_assets || []
    
    // Extract 3D models
    const models = response.data.assets_by_type?.['3D_Model'] || []
    primary3DModel.value = models.find((m: any) => m.is_primary) || models[0] || null
    
    // Extract images
    const mainImages = response.data.assets_by_type?.['Image_Main'] || []
    const galleryImages = response.data.assets_by_type?.['Image_Gallery'] || []
    productImages.value = [...mainImages, ...galleryImages]
    
    // Initialize 3D viewer if model exists
    if (primary3DModel.value && viewer3DContainer.value) {
      nextTick(() => {
        init3DViewer()
      })
    }
  } catch (error) {
    console.error('Failed to load assets:', error)
  }
}

const init3DViewer = () => {
  if (!viewer3DContainer.value || !primary3DModel.value) {
    console.warn('Cannot init 3D viewer: missing container or model')
    return
  }

  loading3D.value = true
  model3DError.value = false

  try {
    // Cleanup existing scene
    cleanup3DScene()

    const container = viewer3DContainer.value
    const width = container.clientWidth
    const height = container.clientHeight

    // Scene setup
    scene = new THREE.Scene()
    scene.background = new THREE.Color(0xf5f5f5)

    // Camera
    camera = new THREE.PerspectiveCamera(50, width / height, 0.1, 1000)
    camera.position.set(
      primary3DModel.value.camera_settings?.angle_x || 2,
      primary3DModel.value.camera_settings?.angle_y || 2,
      primary3DModel.value.camera_settings?.zoom || 5
    )

    // Renderer
    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true })
    renderer.setSize(width, height)
    renderer.setPixelRatio(window.devicePixelRatio)
    renderer.shadowMap.enabled = true
    renderer.shadowMap.type = THREE.PCFSoftShadowMap
    container.appendChild(renderer.domElement)

    // Lights
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.6)
    scene.add(ambientLight)

    const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8)
    directionalLight.position.set(5, 10, 7.5)
    directionalLight.castShadow = true
    scene.add(directionalLight)

    const fillLight = new THREE.DirectionalLight(0xffffff, 0.3)
    fillLight.position.set(-5, 0, -5)
    scene.add(fillLight)

    // Controls
    controls = new OrbitControls(camera, renderer.domElement)
    controls.enableDamping = true
    controls.dampingFactor = 0.05
    controls.minDistance = 1
    controls.maxDistance = 20
    controls.maxPolarAngle = Math.PI / 2

    // Load 3D model
    const modelFormat = primary3DModel.value.model_format?.toLowerCase()
    const loader = modelFormat === 'obj' ? new OBJLoader() : new GLTFLoader()
    
    console.log('Loading 3D model:', primary3DModel.value.url, 'Format:', modelFormat)

    loader.load(
      primary3DModel.value.url,
      (loadedModel: any) => {
        model = modelFormat === 'obj' ? loadedModel : loadedModel.scene
        
        // Center and scale model
        const box = new THREE.Box3().setFromObject(model)
        const center = box.getCenter(new THREE.Vector3())
        const size = box.getSize(new THREE.Vector3())
        const maxDim = Math.max(size.x, size.y, size.z)
        const scale = 3 / maxDim
        
        model.scale.multiplyScalar(scale)
        model.position.sub(center.multiplyScalar(scale))
        
        // Enable shadows
        model.traverse((child: any) => {
          if (child.isMesh) {
            child.castShadow = true
            child.receiveShadow = true
          }
        })
        
        scene?.add(model)
        loading3D.value = false
        console.log('3D model loaded successfully')
        
        // Start animation
        animate()
      },
      (progress: any) => {
        const percent = (progress.loaded / progress.total * 100).toFixed(0)
        console.log(`Loading 3D model: ${percent}%`)
      },
      (error: any) => {
        console.error('Error loading 3D model:', error)
        loading3D.value = false
        model3DError.value = true
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load 3D model',
          life: 3000
        })
      }
    )
  } catch (error) {
    console.error('Error initializing 3D viewer:', error)
    loading3D.value = false
    model3DError.value = true
  }
}

const animate = () => {
  if (!scene || !camera || !renderer || !controls) return

  animationId = requestAnimationFrame(animate)
  
  if (autoRotate.value && model) {
    model.rotation.y += 0.005
  }
  
  controls.update()
  renderer.render(scene, camera)
}

const reset3DView = () => {
  if (controls) {
    controls.reset()
  }
}

const toggleAutoRotate = () => {
  autoRotate.value = !autoRotate.value
}

const take3DScreenshot = () => {
  if (!renderer) return
  
  const dataURL = renderer.domElement.toDataURL('image/png')
  const link = document.createElement('a')
  link.download = `${product.value.sku}-3d-preview.png`
  link.href = dataURL
  link.click()
  
  toast.add({
    severity: 'success',
    summary: 'Screenshot Saved',
    detail: '3D preview downloaded',
    life: 2000
  })
}

const toggle3DFullscreen = () => {
  if (!viewer3DContainer.value) return
  
  if (viewer3DContainer.value.requestFullscreen) {
    viewer3DContainer.value.requestFullscreen()
  }
}

const retryLoad3D = () => {
  model3DError.value = false
  init3DViewer()
}

const cleanup3DScene = () => {
  if (animationId) {
    cancelAnimationFrame(animationId)
    animationId = null
  }
  
  if (renderer) {
    renderer.dispose()
    if (viewer3DContainer.value && renderer.domElement.parentNode === viewer3DContainer.value) {
      viewer3DContainer.value.removeChild(renderer.domElement)
    }
    renderer = null
  }
  
  if (controls) {
    controls.dispose()
    controls = null
  }
  
  scene = null
  camera = null
  model = null
}

const confirmDelete = () => {
  deleteDialogVisible.value = true
}

const deleteProduct = async () => {
  deleting.value = true
  try {
    await merchandisingService.deleteProduct(productId.value)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Product deleted successfully',
      life: 3000
    })
    router.push({ name: 'merchandising.products' })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to delete product',
      life: 3000
    })
  } finally {
    deleting.value = false
    deleteDialogVisible.value = false
  }
}

const manageVariations = () => {
  router.push({ 
    name: 'merchandising.variations', 
    query: { product_id: productId.value }
  })
}

const openImageGallery = () => {
  galleryVisible.value = true
}

const downloadAsset = (asset: any) => {
  window.open(asset.url, '_blank')
  toast.add({
    severity: 'success',
    summary: 'Download Started',
    detail: `Downloading ${asset.file_name}`,
    life: 2000
  })
}

const handleImageError = (event: Event) => {
  const img = event.target as HTMLImageElement
  img.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="300"%3E%3Crect fill="%23f0f0f0" width="400" height="300"/%3E%3Ctext fill="%23999" x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle"%3EImage not found%3C/text%3E%3C/svg%3E'
}

const getAssetIcon = (assetType: string) => {
  const icons: Record<string, string> = {
    '3D_Model': 'pi pi-cube',
    '3D_Thumbnail': 'pi pi-image',
    'Image_Main': 'pi pi-image',
    'Image_Gallery': 'pi pi-images',
    'Image_360': 'pi pi-sync',
    'Video_Product': 'pi pi-video',
    'Video_Assembly': 'pi pi-wrench',
    'Manual_PDF': 'pi pi-file-pdf',
    'Texture_Map': 'pi pi-palette'
  }
  return icons[assetType] || 'pi pi-file'
}

const getAssetTypeLabel = (assetType: string) => {
  return assetType.replace(/_/g, ' ')
}

const getStockSeverity = (status: string) => {
  const severities: Record<string, string> = {
    'In Stock': 'success',
    'Low Stock': 'warning',
    'Out of Stock': 'danger',
    'Pre-order': 'info'
  }
  return severities[status] || 'secondary'
}

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('en-PH', { 
    minimumFractionDigits: 2,
    maximumFractionDigits: 2 
  }).format(price)
}

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

const formatDate = (date: string) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Watch for 3D model changes
watch(primary3DModel, (newModel) => {
  if (newModel && viewer3DContainer.value) {
    nextTick(() => {
      init3DViewer()
    })
  }
})

onMounted(() => {
  loadProduct()
})

// Cleanup on unmount
onBeforeUnmount(() => {
  cleanup3DScene()
})
</script>

<style scoped>
.sticky {
  position: sticky;
  top: 1.5rem;
}

:deep(.p-card-title) {
  font-size: 1rem;
  font-weight: 600;
}

:deep(.p-datatable-sm) .p-datatable-tbody > tr > td {
  padding: 0.5rem;
}

:deep(canvas) {
  display: block;
  max-width: 100%;
}
</style>