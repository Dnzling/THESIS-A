<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button
          icon="pi pi-arrow-left"
          text
          rounded
          @click="router.push({ name: 'inventory.products.index' })"
        />
        <div>
          <h2 class="text-2xl font-bold text-gray-800">Item Details</h2>
          <p class="text-sm text-gray-500 mt-1">View inventory item information, stock data, and attachments</p>
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
          @click="goToEdit"
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

    <div v-if="loading" class="space-y-6">
      <Skeleton height="400px" class="rounded-lg" />
      <Skeleton height="300px" class="rounded-lg" />
      <Skeleton height="300px" class="rounded-lg" />
    </div>

    <div v-else-if="product" class="space-y-6">
      <Card>
        <template #content>
          <div class="space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
              <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ product.product_name }}</h1>
                <div class="flex flex-wrap items-center gap-2 mt-2">
                  <Tag :value="product.sku" severity="secondary" class="font-mono" />
                  <Tag :value="productTypeLabel" :severity="productTypeSeverity" />
                  <Tag :value="product.is_active ? 'Active' : 'Inactive'" :severity="product.is_active ? 'success' : 'secondary'" />
                  <Tag v-if="product.is_featured" value="Featured" severity="warning" />
                  <Tag v-if="product.is_new_arrival" value="New Arrival" severity="info" />
                  <Tag v-if="product.is_bestseller" value="Bestseller" icon="pi pi-star-fill" severity="success" />
                </div>
              </div>
              <div class="text-left lg:text-right">
                <p class="text-3xl font-bold text-green-600">₱{{ formatPrice(product.base_price) }}</p>
                <p class="text-sm text-gray-500 mt-1">{{ priceLabel }}</p>
              </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-gray-50 rounded-lg">
              <div>
                <p class="text-xs text-gray-600 mb-1">Category</p>
                <p class="text-sm font-semibold text-gray-900">{{ product.category?.category_name || 'N/A' }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-600 mb-1">Type</p>
                <p class="text-sm font-semibold text-gray-900">{{ productTypeLabel }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-600 mb-1">Variations</p>
                <p class="text-sm font-semibold text-gray-900">{{ variations.length }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-600 mb-1">Stock</p>
                <p class="text-sm font-semibold text-gray-900">{{ branchInventory?.quantity_available ?? 0 }}</p>
              </div>
            </div>

            <div v-if="product.description">
              <h3 class="text-sm font-semibold text-gray-700 mb-2">Description</h3>
              <p class="text-gray-700 leading-relaxed">{{ product.description }}</p>
            </div>
          </div>
        </template>
      </Card>

      <div class="grid grid-cols-1 xl:grid-cols-[1.4fr_0.8fr] gap-6">
        <Card>
          <template #title>
            <div class="flex items-center gap-2">
              <i class="pi pi-box text-purple-600"></i>
              <span>Item Details</span>
            </div>
          </template>
          <template #content>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div>
                <p class="text-xs text-gray-600 mb-1">Unit of Measurement</p>
                <p class="text-lg font-semibold text-gray-900">{{ product.unit_of_measurement || 'N/A' }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-600 mb-1">Supplier</p>
                <p class="text-lg font-semibold text-gray-900">{{ product.supplier_name || 'N/A' }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-600 mb-1">Initial Stock</p>
                <p class="text-lg font-semibold text-gray-900">{{ product.initial_stock ?? 'N/A' }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-600 mb-1">Available Stock</p>
                <p class="text-lg font-semibold text-gray-900">{{ branchInventory?.quantity_available ?? 0 }}</p>
              </div>
              <div v-if="product.length_cm || product.width_cm || product.height_cm || product.weight_kg">
                <p class="text-xs text-gray-600 mb-1">Dimensions</p>
                <p class="text-lg font-semibold text-gray-900">{{ dimensionsLabel }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-600 mb-1">Created</p>
                <p class="text-lg font-semibold text-gray-900">{{ formatDate(product.created_at) }}</p>
              </div>
            </div>
          </template>
        </Card>

        <Card>
          <template #title>
            <div class="flex items-center gap-2">
              <i class="pi pi-chart-bar text-blue-600"></i>
              <span>Stock Summary</span>
            </div>
          </template>
          <template #content>
            <div class="space-y-4">
              <div class="rounded-lg bg-gray-50 p-4">
                <p class="text-xs text-gray-600 mb-1">On Hand</p>
                <p class="text-2xl font-semibold text-gray-900">{{ branchInventory?.quantity_on_hand ?? 0 }}</p>
              </div>
              <div class="rounded-lg bg-gray-50 p-4">
                <p class="text-xs text-gray-600 mb-1">Reserved</p>
                <p class="text-2xl font-semibold text-gray-900">{{ branchInventory?.quantity_reserved ?? 0 }}</p>
              </div>
              <div class="rounded-lg bg-gray-50 p-4">
                <p class="text-xs text-gray-600 mb-1">Reorder Point</p>
                <p class="text-2xl font-semibold text-gray-900">{{ branchInventory?.reorder_point ?? 0 }}</p>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <Card v-if="variations.length > 0 && product?.product_type === 'finished_good'">
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-th-large text-indigo-600"></i>
            <span>Product Variations</span>
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
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>

      <Card v-if="productImages.length > 0">
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-images text-pink-600"></i>
            <span>Attachments</span>
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
              <Tag :value="primary3DModel.is_ar_compatible ? 'Yes' : 'No'" :severity="primary3DModel.is_ar_compatible ? 'success' : 'secondary'" />
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

      <Dialog
        v-model:visible="imagePreviewVisible"
        :header="previewImage?.file_name"
        :modal="true"
        class="w-full max-w-4xl"
      >
        <div v-if="previewImage" class="text-center bg-gray-100 rounded-lg p-8">
          <img :src="previewImage.auth_url || previewImage.url" @error="handleImageError" />
        </div>

        <template #footer>
          <Button label="Download" icon="pi pi-download" @click="downloadImageAsset(previewImage)" severity="info" />
          <Button label="Close" severity="secondary" outlined @click="imagePreviewVisible = false" />
        </template>
      </Dialog>
    </div>

    <div v-else class="text-center py-12">
      <i class="pi pi-exclamation-triangle text-6xl text-red-500 mb-4"></i>
      <h3 class="text-xl font-semibold text-gray-800 mb-2">Item Not Found</h3>
      <p class="text-gray-600 mb-4">The item you're looking for doesn't exist or has been deleted.</p>
      <Button
        label="Back to Products"
        icon="pi pi-arrow-left"
        @click="router.push({ name: 'inventory.products.index' })"
      />
    </div>

    <Dialog v-model:visible="deleteDialogVisible" header="Confirm Delete" :modal="true" class="w-96">
      <div class="flex items-center gap-3">
        <i class="pi pi-exclamation-triangle text-4xl text-red-600"></i>
        <div>
          <p class="font-semibold">Are you sure you want to delete this item?</p>
          <p class="text-sm text-gray-600 mt-1">This action cannot be undone.</p>
        </div>
      </div>
      <template #footer>
        <Button @click="deleteDialogVisible = false" label="Cancel" severity="secondary" text />
        <Button @click="deleteProduct" label="Delete" severity="danger" :loading="deleting" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../../../stores/auth'
import inventoryService from '../../../../services/inventory.service'
import { showResponseDialog } from '@/utils/responseDialogBus'

import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import Skeleton from 'primevue/skeleton'
import Model3DPreview from '../../../../Components/merchandising/Model3DPreview.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const productId = computed(() => Number(route.params.id))
const loading = ref(false)
const deleting = ref(false)
const deleteDialogVisible = ref(false)
const view3DModalVisible = ref(false)
const imagePreviewVisible = ref(false)
const previewImage = ref<any>(null)

const product = ref<any>(null)
const variations = ref<any[]>([])
const allAssets = ref<any[]>([])
const primary3DModel = ref<any>(null)
const productImages = ref<any[]>([])

const showActionResponse = (severity: 'success' | 'error' | 'info' | 'warn', title: string, message: string) => {
  showResponseDialog({
    severity,
    title,
    message,
  })
}

const productTypeLabel = computed(() => {
  const type = product.value?.product_type
  return type === 'raw_material' ? 'Raw Material' : type === 'supply' ? 'Supply' : 'Finished Good'
})

const productTypeSeverity = computed(() => {
  const type = product.value?.product_type
  if (type === 'raw_material') return 'info'
  if (type === 'supply') return 'warning'
  return 'success'
})

const branchInventory = computed(() => {
  const inventory = Array.isArray(product.value?.inventory) ? product.value.inventory[0] : null
  return inventory || null
})

const priceLabel = computed(() => {
  const type = product.value?.product_type
  if (type === 'raw_material' || type === 'supply') return 'Unit Cost'
  return 'Base Price'
})

const dimensionsLabel = computed(() => {
  const parts = [
    product.value?.length_cm ? `${product.value.length_cm} cm` : null,
    product.value?.width_cm ? `${product.value.width_cm} cm` : null,
    product.value?.height_cm ? `${product.value.height_cm} cm` : null,
    product.value?.weight_kg ? `${product.value.weight_kg} kg` : null,
  ].filter(Boolean)

  return parts.length > 0 ? parts.join(' × ') : 'N/A'
})

const loadProduct = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getProduct(productId.value)
    product.value = response.data
    variations.value = product.value?.variations || []

    if (product.value?.id) {
      await loadAssets()
    }
  } catch (error: any) {
    console.error('Failed to load product:', error)
    showActionResponse(
      'error',
      'Failed to Load Product',
      error.response?.data?.message || 'Failed to load product'
    )
  } finally {
    loading.value = false
  }
}

const loadImageWithAuth = async (image: any) => {
  if (!image?.url) return null

  try {
    const token = authStore.token || localStorage.getItem('auth_token')

    const response = await fetch(image.url, {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'image/*',
      },
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
    const assets = Array.isArray(product.value?.assets) ? product.value.assets : []
    allAssets.value = assets

    const models = assets.filter((asset: any) => asset.asset_type === '3D_Model')
    primary3DModel.value = models.find((asset: any) => asset.is_primary) || models[0] || null

    const mainImages = assets.filter((asset: any) => asset.asset_type === 'Image_Main')
    const galleryImages = assets.filter((asset: any) => asset.asset_type === 'Image_Gallery')
    productImages.value = [...mainImages, ...galleryImages]

    for (const image of productImages.value) {
      if (image?.url) {
        image.auth_url = await loadImageWithAuth(image)
      }
    }
  } catch (error) {
    console.error('Failed to load assets:', error)
  }
}

const goToEdit = () => {
  router.push({ name: 'inventory.products.edit', params: { id: productId.value } })
}

const openView3DModal = () => {
  if (!primary3DModel.value) {
    showActionResponse('error', 'No 3D Model Available', 'No 3D model available for this item')
    return
  }
  view3DModalVisible.value = true
}

const downloadModel = () => {
  if (!primary3DModel.value) return

  window.open(primary3DModel.value.url, '_blank')
  showActionResponse('success', 'Download Started', `Downloading ${primary3DModel.value.file_name}`)
}

const confirmDelete = () => {
  deleteDialogVisible.value = true
}

const deleteProduct = async () => {
  deleting.value = true
  try {
    await inventoryService.deleteProduct(productId.value)
    showActionResponse('success', 'Item Deleted', 'Item deleted successfully')
    router.push({ name: 'inventory.products.index' })
  } catch (error: any) {
    showActionResponse('error', 'Delete Failed', error.response?.data?.message || 'Failed to delete item')
  } finally {
    deleting.value = false
    deleteDialogVisible.value = false
  }
}

const handleImageError = (event: Event) => {
  const img = event.target as HTMLImageElement
  img.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="300"%3E%3Crect fill="%23f0f0f0" width="400" height="300"/%3E%3Ctext fill="%23999" x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle"%3EImage not found%3C/text%3E%3C/svg%3E'
}

const openImagePreview = async (image: any) => {
  previewImage.value = { ...image }

  if (image?.url && !image.auth_url) {
    previewImage.value.auth_url = await loadImageWithAuth(image)
  }

  imagePreviewVisible.value = true
}

const downloadImageAsset = (image: any) => {
  if (!image) return
  window.open(image.url, '_blank')
  showActionResponse('success', 'Download Started', `Downloading ${image.file_name}`)
}

const formatPrice = (price: number | string | null | undefined) => {
  if (price == null || price === '') return '0.00'
  return new Intl.NumberFormat('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number(price))
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
    day: 'numeric',
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
