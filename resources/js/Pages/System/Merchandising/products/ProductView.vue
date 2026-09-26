<template>
  <div class="max-w-7xl mx-auto space-y-5 px-4 py-6 pb-6 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <Button 
          icon="pi pi-arrow-left" 
          text 
          rounded
          @click="router.push({ name: 'merchandising.products' })" 
        />
        <div>
          <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Product record</p>
          <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">{{ product?.product_name || 'Product Details' }}</h1>
          <p class="mt-1 font-mono text-xs text-slate-500">{{ product?.sku || 'Loading product...' }}</p>
        </div>
      </div>
      <div class="flex gap-2">
        <Button 
          v-if="primary3DModel"
          label="Preview 3D"
          icon="pi pi-box" 
          size="small"
          severity="secondary"
          @click="openView3DModal" 
        />
        <Button 
          label="Edit Product"
          icon="pi pi-pencil" 
          severity="warn"
          size="small"
          @click="router.push({ name: 'merchandising.products.edit', params: { id: productId } })" 
        />
        <!-- <Button 
          label="Archive" 
          icon="pi pi-briefcase" 
          severity="danger"
          outlined
          size="small"
          @click="confirmDelete" 
        /> -->
      </div>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="space-y-5">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <Skeleton v-for="index in 3" :key="index" height="130px" class="rounded-2xl" />
      </div>
      <Skeleton height="280px" class="rounded-2xl" />
      <Skeleton height="220px" class="rounded-2xl" />
    </div>

    <!-- Product Content -->
    <div v-else-if="product" class="space-y-5">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="mb-3 flex items-center justify-between"><span class="text-xs font-medium uppercase tracking-wider text-slate-500">Catalog price</span><i class="pi pi-wallet text-slate-400" /></div>
          <p class="text-2xl font-semibold text-slate-900">PHP {{ formatPrice(hasDiscount ? product.discounted_price : product.base_price) }}</p>
          <p v-if="hasDiscount" class="mt-2 text-xs text-slate-500"><span class="line-through">PHP {{ formatPrice(product.base_price) }}</span><span class="ml-2 font-medium text-rose-600">{{ discountPercent }}% off</span></p>
          <p v-else class="mt-2 text-xs text-slate-500">Current selling price</p>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="mb-3 flex items-center justify-between"><span class="text-xs font-medium uppercase tracking-wider text-slate-500">Category & brand</span><i class="pi pi-tag text-slate-400" /></div>
          <p class="text-lg font-semibold text-slate-900">{{ product.category?.category_name || 'Uncategorized' }}</p>
          <p class="mt-1 text-xs text-slate-500">{{ product.brand || 'No brand recorded' }}</p>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="mb-3 flex items-center justify-between"><span class="text-xs font-medium uppercase tracking-wider text-slate-500">Listing status</span><i class="pi pi-check-circle text-slate-400" /></div>
          <Tag :value="product.is_active ? 'Active' : 'Inactive'" :severity="product.is_active ? 'success' : 'secondary'" />
          <div class="mt-3 flex flex-wrap gap-1.5">
            <Tag v-if="product.is_featured" value="Featured" severity="warn" />
            <Tag v-if="product.is_new_arrival" value="New Arrival" severity="info" />
            <Tag v-if="product.is_bestseller" value="Bestseller" severity="success" />
          </div>
          <p class="mt-2 text-xs text-slate-500">{{ variations.length }} variation{{ variations.length === 1 ? '' : 's' }}</p>
        </section>
      </div>

      <Card v-if="product.description || product.tags?.length" class="rounded-2xl border border-slate-200 shadow-sm">
        <template #title><span class="text-base font-semibold text-slate-900">Product Information</span></template>
        <template #content>
          <p v-if="product.description" class="whitespace-pre-line text-sm leading-6 text-slate-700">{{ formatDescription(product.description) }}</p>
          <div v-if="product.tags?.length" class="mt-3 flex flex-wrap gap-1.5">
            <Tag v-for="tag in product.tags" :key="`product-tag-${tag.id}`" :value="tag.tag_name" severity="secondary" />
          </div>
        </template>
      </Card>
        
        <!-- Product Header Card -->
        <Card v-if="false">
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
                    <Tag v-for="tag in (product.tags || [])" :key="`product-tag-${tag.id}`" :value="tag.tag_name" severity="info" />
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
        <Card class="rounded-2xl border border-slate-200 shadow-sm">
          <template #title>
            <span class="text-base font-semibold text-slate-900">Product Specifications</span>
          </template>
          <template #content>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
              <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                <p class="text-xs text-slate-500">Length</p>
                <p class="mt-1 font-semibold text-slate-900">
                  {{ product.length_cm ? `${product.length_cm} cm` : 'N/A' }}
                </p>
              </div>
              <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                <p class="text-xs text-slate-500">Width</p>
                <p class="mt-1 font-semibold text-slate-900">
                  {{ product.width_cm ? `${product.width_cm} cm` : 'N/A' }}
                </p>
              </div>
              <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                <p class="text-xs text-slate-500">Height</p>
                <p class="mt-1 font-semibold text-slate-900">
                  {{ product.height_cm ? `${product.height_cm} cm` : 'N/A' }}
                </p>
              </div>
              <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                <p class="text-xs text-slate-500">Weight</p>
                <p class="mt-1 font-semibold text-slate-900">
                  {{ product.weight_kg ? `${product.weight_kg} kg` : 'N/A' }}
                </p>
              </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
              <Tag v-if="product.assembly_required" value="Assembly Required" severity="info" icon="pi pi-wrench" />
              <Tag v-else value="No Assembly" severity="success" icon="pi pi-check" />
              
              <Tag v-if="product.collection_name" :value="`Collection: ${product.collection_name}`" />
            </div>
          </template>
        </Card>

        <!-- Product Variations -->
        <Card v-if="variations && variations.length > 0" class="rounded-2xl border border-slate-200 shadow-sm">
          <template #title>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="text-base font-semibold text-slate-900">Product Variations ({{ variations.length }})</span>
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
            <DataTable :value="variations" size="small" rowHover class="text-xs">
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

        <!-- Product Timestamps -->
        <Card class="rounded-2xl border border-slate-200 shadow-sm">
          <template #title>
            <span class="text-base font-semibold text-slate-900">Product Record</span>
          </template>
          <template #content>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Created At</p>
                <p class="mt-1 font-medium text-slate-900">{{ formatDate(product.created_at) }}</p>
              </div>
              <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Last Updated</p>
                <p class="mt-1 font-medium text-slate-900">{{ formatDate(product.updated_at) }}</p>
              </div>
            </div>
          </template>
        </Card>

      <!-- Product Images Gallery -->
      <Card v-if="productImages && productImages.length > 0" class="rounded-2xl border border-slate-200 shadow-sm">
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
import Model3DPreview from '@/Components/merchandising/Model3DPreview.vue'

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
const hasDiscount = computed(() => {
  const basePrice = Number(product.value?.base_price || 0)
  const discountedPrice = Number(product.value?.discounted_price || 0)
  return discountedPrice > 0 && basePrice > discountedPrice
})
const discountPercent = computed(() => {
  const basePrice = Number(product.value?.base_price || 0)
  const discountedPrice = Number(product.value?.discounted_price || 0)
  return basePrice > 0 ? Math.round(((basePrice - discountedPrice) / basePrice) * 100) : 0
})
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

const formatDescription = (value: unknown) => {
  const html = String(value || '')
    .replace(/<\s*br\s*\/?\s*>/gi, '\n')
    .replace(/<\s*li\b[^>]*>/gi, '\n• ')
    .replace(/<\s*\/(p|div|li|h[1-6]|ul|ol|blockquote)\s*>/gi, '\n')
    .replace(/<\s*(script|style)\b[^>]*>[\s\S]*?<\s*\/\s*\1\s*>/gi, '')
    .replace(/<[^>]*>/g, '')

  const decoder = document.createElement('textarea')
  decoder.innerHTML = html

  return decoder.value
    .replace(/\u00a0/g, ' ')
    .replace(/[ \t]+\n/g, '\n')
    .replace(/\n{3,}/g, '\n\n')
    .trim()
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
