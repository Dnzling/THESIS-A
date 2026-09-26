<template>
  <div class="min-h-screen px-4 py-6 sm:px-6 lg:px-8">
  <div class="mx-auto max-w-7xl space-y-6 pb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="flex items-center gap-3">
        <button @click="goBack" class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 transition-colors hover:bg-gray-200">
          <i class="pi pi-chevron-left text-lg text-gray-600"></i>
        </button>
        <div>
          <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-3xl font-semibold tracking-tight text-gray-900">{{ detailTitle }}</h2>
            <Badge v-if="product?.is_active" value="Active" severity="success" />
          </div>
          <div class="mt-2 flex flex-wrap items-center gap-2">
            <Tag :value="displayItem?.variation_sku || product?.sku || '-'" severity="secondary" class="font-mono" />
            <Tag v-for="tag in product?.tags || []" :key="tag.id" :value="tag.tag_name" severity="info" />
          </div>
        </div>
      </div>
      <div class="flex gap-2">
        <Button
          v-if="primary3DModel"
          label="3D"
          icon="pi pi-box"
          severity="info"
          size="small"
          @click="openView3DModal"
        />
        <Button
          v-if="!selectedVariation"
          label="Edit"
          icon="pi pi-pencil"
          severity="warn"
          size="small"
          @click="goToEdit"
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

    <div v-if="product" class="grid grid-cols-2 gap-3 lg:grid-cols-4">
      <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <div class="mb-3 flex items-center justify-between"><span class="text-sm font-medium text-gray-500">Item Type</span><i class="pi pi-tag text-gray-400"></i></div>
        <span class="text-base font-semibold text-gray-900">{{ productTypeLabel }}</span>
      </div>
      <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <div class="mb-3 flex items-center justify-between"><span class="text-sm font-medium text-gray-500">Available Stock</span><i class="pi pi-box text-gray-400"></i></div>
        <span class="text-base font-semibold text-gray-900">{{ availableStock }}</span>
      </div>
      <div class="rounded-2xl bg-white p-5 shadow-sm">
        <span class="mb-3 block text-sm font-medium ">Cost per Unit</span>
        <span class="text-2xl font-bold tracking-tight text-green-600">₱{{ formatPrice(unitCost) }}</span>
      </div>
            <div v-if="product?.product_type === 'finished_good'" class="rounded-2xl bg-gradient-to-br from-gray-900 to-gray-800 p-5 shadow-lg">
        <span class="mb-3 block text-sm font-medium text-gray-400">Selling Price</span>
        <span class="text-2xl font-bold tracking-tight text-white">₱{{ formatPrice(displaySellingPrice) }}</span>
      </div>
    </div>

    <div v-if="loading" class="space-y-6">
      <Skeleton height="400px" class="rounded-lg" />
      <Skeleton height="300px" class="rounded-lg" />
      <Skeleton height="300px" class="rounded-lg" />
    </div>

    <div v-else-if="product" class="space-y-6">
      

      <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.4fr_0.8fr]">
      <Card class="rounded-2xl border border-gray-100 shadow-sm">
        <template #title>
          <span class="text-sm font-semibold text-gray-800">Item Details</span>
        </template>
          <template #content>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div>
                <p class="text-xs text-gray-600 mb-1">Unit of Measurement</p>
                <p class="text-lg capitalize font-semibold text-gray-900">{{ displayItem?.unit_of_measurement || product.unit_of_measurement || 'N/A' }}</p>
              </div>
              <div v-show="product.product_type === 'finished_good'">
                <p class="text-xs text-gray-600 mb-1">Supplier</p>
                <div class="flex items-center gap-2">
                  <p class="text-lg font-semibold text-gray-900">{{ supplierNames || 'No Supplier' }}</p>
                  <Button
                    v-if="!hasSupplier"
                    label="Create PR"
                    icon="pi pi-file-plus"
                    severity="warn"
                    size="small"
                    outlined
                    @click="goToCreatePR"
                  />
                </div>
              </div>
              <div>
                <p class="text-xs text-gray-600 mb-1">Cost Price per Unit</p>
                <p class="text-lg font-semibold text-gray-900">
                  ₱{{ formatPrice(unitCost) }}
                </p>
              </div>
              <div>
                <p class="text-xs text-gray-600 mb-1">Reorder Level</p>
                <p class="text-lg font-semibold text-gray-900">{{ displayReorderLevel }}</p>
              </div>

              <div v-if="hasDimensions">
                <p class="text-xs text-gray-600 mb-1">Dimensions</p>
                <p class="text-lg font-semibold text-gray-900">{{ dimensionsLabel }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-600 mb-1">Created</p>
                <p class="text-lg font-semibold text-gray-900">{{ formatDate(displayItem?.created_at || product.created_at) }}</p>
              </div>
              <!-- <div>
                <p class="text-xs text-gray-600 mb-1">Created By</p>
                <p class="text-lg font-semibold text-gray-900">{{ product.created_by_name || 'N/A' }}</p>
              </div> -->
            </div>
          </template>
        </Card>

      <!-- <Card class="rounded-2xl border border-gray-100 shadow-sm">
        <template #title>
          <span class="text-sm font-semibold text-gray-800">Stock Summary</span>
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
        </Card> -->

            <Card v-if="productImages.length > 0" class="rounded-2xl border border-gray-100 shadow-sm">
        <template #title>
          <span class="text-sm font-semibold text-gray-800">Attachments</span>
        </template>
        <template #content>
          <Carousel
            v-if="productImages.length > 1"
            :value="productImages"
            :numVisible="1"
            :numScroll="1"
            :showNavigators="true"
            :showIndicators="true"
            class="product-view-carousel"
          >
            <template #item="{ data: image }">
              <div class="relative mx-2 aspect-square overflow-hidden rounded-xl border border-slate-200 bg-slate-50 group cursor-pointer">
                <img
                  :src="image.auth_url || image.url"
                  :alt="image.file_name"
                  class="h-full w-full object-contain p-3 group-hover:scale-105 transition-transform"
                  @error="handleImageError"
                />
                <Badge v-if="image.is_primary" value="Primary" severity="success" class="absolute top-2 left-2" />
                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                  <Button icon="pi pi-search-plus" rounded severity="info" text @click="openImagePreview(image)" />
                  <Button icon="pi pi-download" rounded severity="info" text @click.stop="downloadImageAsset(image)" />
                </div>
              </div>
            </template>
          </Carousel>
          <div v-else class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <div
              v-for="image in productImages"
              :key="image.id"
              class="relative mx-auto aspect-square max-w-md overflow-hidden rounded-xl bg-white group cursor-pointer"
            >
              <img
                :src="image.auth_url || image.url"
                :alt="image.file_name"
                class="h-full w-full object-contain p-3 group-hover:scale-105 transition-transform"
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
      </div>

      <Card v-if="product?.product_type === 'finished_good'" class="rounded-2xl border border-gray-100 shadow-sm">
        <template #title>
          <span class="text-sm font-semibold text-gray-800">Product Variations</span>
        </template>
        <template #content>
          <DataTable :value="variations" class="p-datatable-sm text-xs">
            <template #empty>
              <div class="py-8 text-center text-xs text-gray-500">
                No variants yet. This product currently uses its own SKU and stock.
              </div>
            </template>
            <Column field="variation_name" header="Variation">
              <template #body="{ data }">
                <div class="flex items-center gap-2 text-xs">
                  <div
                    v-if="data.color_hex"
                    :style="{ backgroundColor: data.color_hex }"
                    class="h-5 w-5 rounded border border-gray-300"
                  ></div>
                  <span class="font-medium text-xs">{{ data.variation_name }}</span>
                </div>
              </template>
            </Column>
            <Column field="variation_sku" header="SKU">
              <template #body="{ data }">
                <span class="font-mono text-xs">{{ data.variation_sku }}</span>
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
                <span class="font-semibold text-xs">₱{{ formatPrice(data.final_price || 0) }}</span>
              </template>
            </Column>
            <Column header="Stock">
              <template #body="{ data }">
                <span class="text-xs font-semibold">{{ data.inventory?.[0]?.quantity_available ?? 0 }}</span>
              </template>
            </Column>
            <Column header="Reorder Level">
              <template #body="{ data }">
                <span class="text-xs">{{ data.reorder_point ?? 0 }}</span>
              </template>
            </Column>
            <Column header="Supplier">
              <template #body="{ data }">
                <span class="text-xs">{{ data.supplier_name || 'No supplier' }}</span>
              </template>
            </Column>
            <Column header="Action" style="width: 6rem">
              <template #body="{ data }">
                <Button
                  label="Edit"
                  icon="pi pi-pencil"
                  severity="warn"
                  size="small"
                  text
                  @click.stop="goToEditVariation(data)"
                />
              </template>
            </Column>
          </DataTable>
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
import Carousel from 'primevue/carousel'
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

const selectedVariationId = computed(() => Number(route.query.variation_id || 0))
const selectedVariation = computed(() => {
  if (!selectedVariationId.value) return null
  return variations.value.find((variation: any) => Number(variation.id) === selectedVariationId.value) || null
})
const displayItem = computed(() => selectedVariation.value || product.value)
const detailTitle = computed(() => {
  if (!product.value) return 'Product Details'
  if (!selectedVariation.value) return product.value.product_name
  return `${product.value.product_name} - ${selectedVariation.value.variation_name}`
})

const showActionResponse = (severity: 'success' | 'error' | 'info' | 'warn', title: string, message: string) => {
  showResponseDialog({
    severity,
    title,
    message,
  })
}

const productTypeLabel = computed(() => {
  const type = product.value?.product_type
  return type === 'raw_material' ? 'Raw Material' : type === 'supply' ? 'Supply' : 'Product'
})

const productTypeSeverity = computed(() => {
  const type = product.value?.product_type
  if (type === 'raw_material') return 'info'
  if (type === 'supply') return 'warn'
  return 'success'
})

const hasSupplier = computed(() => {
  return supplierNames.value.length > 0
})

const supplierNames = computed(() => {
  const normalized = Array.isArray(product.value?.supplier_names)
    ? product.value.supplier_names.filter((name: any) => name && String(name).trim())
    : []
  if (normalized.length > 0) return [...new Set(normalized.map((name: string) => String(name).trim()))].join(', ')

  const linked = Array.isArray(product.value?.suppliers) ? product.value.suppliers : []
  const names = linked
    .map((supplier: any) => supplier?.supplier_name || supplier?.company_name)
    .filter((name: any) => name && String(name).trim())

  if (names.length > 0) return [...new Set(names.map((name: string) => String(name).trim()))].join(', ')
  return product.value?.supplier_name ? String(product.value.supplier_name).trim() : ''
})

// Keep the detail view consistent with the product index/API fallback order.
const unitCost = computed(() => {
  return Number(
    selectedVariation.value?.cost_price
      ?? product.value?.inventory_cost_price
      ?? product.value?.cost_price
      ?? product.value?.base_price
      ?? 0
  )
})

const branchInventory = computed(() => {
  if (selectedVariation.value) {
    return Array.isArray(selectedVariation.value.inventory) ? selectedVariation.value.inventory[0] || null : null
  }
  const inventory = Array.isArray(product.value?.inventory) ? product.value.inventory[0] : null
  return inventory || null
})

const availableStock = computed(() => {
  if (selectedVariation.value) {
    return Number(branchInventory.value?.quantity_available || 0)
  }
  if (variations.value.length > 0) {
    return variations.value.reduce(
      (total: number, variation: any) => total + Number(variation.inventory?.[0]?.quantity_available || 0),
      0,
    )
  }
  return Number(branchInventory.value?.quantity_available || 0)
})

const displaySellingPrice = computed(() => selectedVariation.value?.final_price ?? product.value?.base_price ?? 0)
const displayReorderLevel = computed(() => selectedVariation.value?.reorder_point ?? branchInventory.value?.reorder_point ?? product.value?.reorder_point ?? 'N/A')
const hasDimensions = computed(() => ['length_cm', 'width_cm', 'height_cm', 'weight_kg'].some((key) => displayItem.value?.[key]))

const priceLabel = computed(() => {
  const type = product.value?.product_type
  if (type === 'raw_material' || type === 'supply') return 'Unit Cost'
  return 'Base Price'
})

const dimensionsLabel = computed(() => {
  const parts = [
    displayItem.value?.length_cm ? `${displayItem.value.length_cm} cm` : null,
    displayItem.value?.width_cm ? `${displayItem.value.width_cm} cm` : null,
    displayItem.value?.height_cm ? `${displayItem.value.height_cm} cm` : null,
    displayItem.value?.weight_kg ? `${displayItem.value.weight_kg} kg` : null,
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

const goToCreatePR = () => {
  const inventoryId = branchInventory.value?.id
  router.push({
    name: 'inventory.requisites.create',
    query: {
      branch_inventory_id: inventoryId || undefined,
      requested_quantity: branchInventory.value?.reorder_quantity || undefined,
      notes: `Auto-generated from ${product.value?.product_name || 'product'}`
    }
  })
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
    primary3DModel.value = selectedVariation.value?.custom_3d_model
      || models.find((asset: any) => asset.is_primary)
      || models[0]
      || null

    const mainImages = assets.filter((asset: any) => asset.asset_type === 'Image_Main')
    const galleryImages = assets.filter((asset: any) => asset.asset_type === 'Image_Gallery')
    productImages.value = selectedVariation.value?.custom_image
      ? [selectedVariation.value.custom_image]
      : [...mainImages, ...galleryImages]

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
  if (selectedVariation.value) {
    goToEditVariation(selectedVariation.value)
    return
  }
  router.push({ name: 'inventory.products.edit', params: { id: productId.value } })
}

const goToAddVariant = () => {
  router.push({
    name: 'inventory.products.variants.create',
    params: { productId: productId.value },
    query: { product_id: productId.value },
  })
}

const goToEditVariation = (variation: any) => {
  router.push({
    name: 'inventory.products.variants.edit',
    params: { productId: productId.value, id: variation.id },
  })
}

const goBack = () => {
  window.history.back()
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
