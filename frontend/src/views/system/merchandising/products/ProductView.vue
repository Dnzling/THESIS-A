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
          v-if="primary3DModel"
          label="View 3D" 
          icon="pi pi-cube" 
          severity="info"
          @click="openView3DModal" 
        />
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
    <div v-if="loading" class="space-y-6">
      <Skeleton height="400px" class="rounded-lg" />
      <Skeleton height="300px" class="rounded-lg" />
      <Skeleton height="300px" class="rounded-lg" />
    </div>

    <!-- Product Content -->
    <div v-else-if="product" class="space-y-6">
        
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

      <!-- Product Images Gallery -->
      <Card v-if="productImages && productImages.length > 0">
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-images text-pink-600"></i>
            <span>Product Images</span>
          </div>
        </template>
        <template #content>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <div 
              v-for="image in productImages" 
              :key="image.id"
              class="relative rounded-lg overflow-hidden bg-gray-100 group cursor-pointer"
              style="aspect-ratio: 1 / 1;"
            >
              <img 
                :src="image.auth_url || image.url" 
                :alt="image.file_name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                @error="handleImageError"
              />
              <Badge v-if="image.is_primary" value="Primary" severity="success" class="absolute top-2 left-2" />
              
              <!-- Overlay Actions -->
              <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                <Button 
                  icon="pi pi-search-plus"
                  rounded
                  severity="info"
                  text
                  @click="openImagePreview(image)"
                />
                <Button 
                  icon="pi pi-download"
                  rounded
                  severity="info"
                  text
                  @click.stop="downloadImageAsset(image)"
                />
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- 3D Model Viewer Modal -->
      <Dialog 
        v-model:visible="view3DModalVisible" 
        :header="primary3DModel?.file_name || '3D Model'" 
        :modal="true" 
        class="w-full max-w-4xl"
      >
        <div v-if="primary3DModel" class="space-y-4">
          <Model3DPreview
            :model-url="primary3DModel.url"
            :model-format="primary3DModel.model_format"
            :camera-x="primary3DModel?.camera_settings?.angle_x ?? 0"
            :camera-y="primary3DModel?.camera_settings?.angle_y ?? 15"
            :zoom="primary3DModel?.camera_settings?.zoom ?? 1.5"
            height="500px"
          />

          <!-- Model Details -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-gray-50 rounded-lg">
            <div>
              <p class="text-xs text-gray-600 mb-1">Format</p>
              <Tag :value="primary3DModel.model_format?.toUpperCase()" severity="info" />
            </div>
            <div>
              <p class="text-xs text-gray-600 mb-1">File Size</p>
              <p class="text-sm font-semibold">{{ formatFileSize(primary3DModel.file_size_kb * 1024) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600 mb-1">AR Compatible</p>
              <Tag :value="primary3DModel.is_ar_compatible ? 'Yes' : 'No'" 
                   :severity="primary3DModel.is_ar_compatible ? 'success' : 'secondary'" />
            </div>
            <div>
              <p class="text-xs text-gray-600 mb-1">Uploaded</p>
              <p class="text-sm font-semibold">{{ formatDate(primary3DModel.created_at) }}</p>
            </div>
          </div>
        </div>

        <template #footer>
          <Button label="Download" icon="pi pi-download" @click="downloadModel" severity="info" />
          <Button label="Close" severity="secondary" outlined @click="view3DModalVisible = false" />
        </template>
      </Dialog>

      <!-- Image Gallery Preview Modal -->
      <Dialog 
        v-model:visible="imagePreviewVisible" 
        :header="previewImage?.file_name" 
        :modal="true" 
        class="w-full max-w-4xl"
      >
        <div v-if="previewImage" class="text-center bg-gray-100 rounded-lg p-8">
          <img 
          :src="previewImage.auth_url || previewImage.url" 
            @error="handleImageError"
          />
        </div>

        <template #footer>
          <Button label="Download" icon="pi pi-download" @click="downloadImageAsset(previewImage)" severity="info" />
          <Button label="Close" severity="secondary" outlined @click="imagePreviewVisible = false" />
        </template>
      </Dialog>
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
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '../../../../stores/auth'
import merchandisingService from '../../../../services/merchandising.service'

import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import Skeleton from 'primevue/skeleton'
import Model3DPreview from '../../../../components/merchandising/Model3DPreview.vue'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const productId = computed(() => Number(route.params.id))
const loading = ref(false)
const deleting = ref(false)
const deleteDialogVisible = ref(false)
const galleryVisible = ref(false)

// Modal 3D Viewer
const view3DModalVisible = ref(false)

// Image Preview
const imagePreviewVisible = ref(false)
const previewImage = ref<any>(null)

const product = ref<any>(null)
const variations = ref<any[]>([])
const allAssets = ref<any[]>([])
const primary3DModel = ref<any>(null)
const productImages = ref<any[]>([])
const selectedImage = ref(null)

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

// Load images with authentication
const loadImageWithAuth = async (image: any) => {
  if (!image.url) return null

  try {
    const token = authStore.token || localStorage.getItem('auth_token')
    
    const response = await fetch(image.url, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'image/*'
      }
    })

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`)
    }

    const blob = await response.blob()
    return URL.createObjectURL(blob)
  } catch (error) {
    console.error('Failed to load image:', error)
    return null
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
    const allImages = [...mainImages, ...galleryImages]
    productImages.value = allImages
    
    // Load images with auth
    for (const image of productImages.value) {
      if (image.url) {
        image.auth_url = await loadImageWithAuth(image)
      }
    }
  } catch (error) {
    console.error('Failed to load assets:', error)
  }
}

const openView3DModal = () => {
  if (!primary3DModel.value) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No 3D model available for this product',
      life: 3000
    })
    return
  }
  view3DModalVisible.value = true
}

const downloadModel = () => {
  if (!primary3DModel.value) return
  
  window.open(primary3DModel.value.url, '_blank')
  toast.add({
    severity: 'success',
    summary: 'Download Started',
    detail: `Downloading ${primary3DModel.value.file_name}`,
    life: 2000
  })
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

const openImagePreview = async (image: any) => {
  previewImage.value = { ...image }
  
  // Load auth URL if not already loaded
  if (image.url && !image.auth_url) {
    previewImage.value.auth_url = await loadImageWithAuth(image)
  }
  
  imagePreviewVisible.value = true
}

const downloadImageAsset = (image: any) => {
  if (!image) return
  window.open(image.url, '_blank')
  toast.add({
    severity: 'success',
    summary: 'Download Started',
    detail: `Downloading ${image.file_name}`,
    life: 2000
  })
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

onMounted(() => {
  loadProduct()
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
