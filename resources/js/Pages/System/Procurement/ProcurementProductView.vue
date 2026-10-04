<template>
  <div class="min-h-screen bg-gray-50/30 px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'procurement.products' })" />
        <div>
          <h1 class="text-2xl font-semibold text-gray-900">{{ product?.product_name || 'Product Details' }}</h1>
          <p class="mt-1 text-sm text-gray-500">{{ product?.sku || '—' }}</p>
        </div>
      </div>
  
      <div v-if="loading" class="space-y-4">
        <Skeleton height="140px" />
        <Skeleton height="260px" />
      </div>
  
      <template v-else-if="product">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
  
          <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <span class="mb-3 block text-sm font-medium text-gray-500">Category</span>
            <span class="font-semibold text-gray-900">{{ product.category?.category_name || '—' }}</span>
          </div>
          <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <span class="mb-3 block text-sm font-medium text-gray-500">Status</span>
            <Badge :value="stockStatusLabel" :severity="stockStatusSeverity" />
  
          </div>
          <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <span class="mb-3 block text-sm font-medium text-gray-500">Quantity on Hand</span>
            <span class="text-2xl font-bold text-gray-900">{{ formatNumber(product.current_stock) }}</span>
          </div>
          <div class="rounded-2xl bg-gradient-to-br from-gray-900 to-gray-800 p-5 shadow-lg">
            <span class="mb-3 block text-sm font-medium text-gray-400">Selling Price</span>
            <span class="font-semibold text-2xl text-white">{{ formatMoney(product.base_price) }}</span>
          </div>
  
        </div>
  
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.4fr_0.8fr]">
          <div class="space-y-6">
            <Card class="border border-gray-100 shadow-sm">
              <template #title><span class="text-sm font-semibold text-gray-800">Product Details</span></template>
              <template #content>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                  <div>
                    <p class="text-xs text-gray-500">Product Name</p>
                    <p class="font-semibold text-gray-900">{{ product.product_name }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">Supplier</p>
                    <p class="font-semibold text-gray-900">{{ supplierNames || 'No supplier linked.' }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">Branch</p>
                    <p class="font-semibold text-gray-900">{{ product.branch_name || '—' }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">Unit of Measurement</p>
                    <p class="font-semibold capitalize text-gray-900">{{ product.unit_of_measurement || '—' }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">Reorder Point</p>
                    <p class="font-semibold text-gray-900">{{ formatNumber(product.reorder_point) }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">Last Purchase Price</p>
                    <p class="font-semibold text-gray-900">{{ formatMoney(product.last_purchase_price) }}</p>
                  </div>
                  <div class="sm:col-span-2 lg:col-span-3">
                    <p class="text-xs text-gray-500">Description</p>
                    <div
                      v-if="product.description"
                      class="prose prose-sm max-w-none text-gray-800"
                      v-html="product.description"
                    />
                    <p v-else class="text-gray-800">No description available.</p>
                  </div>
                </div>
              </template>
            </Card>
          </div>
  
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
                    <img :src="image.auth_url || image.url" :alt="image.file_name" class="h-full w-full object-contain p-3 group-hover:scale-105 transition-transform" @error="handleImageError" />
                    <Badge v-if="image.is_primary" value="Primary" severity="success" class="absolute top-2 left-2" />
                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                      <Button icon="pi pi-search-plus" rounded severity="info" text @click="openImagePreview(image)" />
                      <Button icon="pi pi-download" rounded severity="info" text @click.stop="downloadImageAsset(image)" />
                    </div>
                  </div>
                </template>
              </Carousel>
              <div v-else class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                <div v-for="image in productImages" :key="image.id" class="relative mx-auto aspect-square max-w-md overflow-hidden rounded-xl bg-white group cursor-pointer">
                  <img :src="image.auth_url || image.url" :alt="image.file_name" class="h-full w-full object-contain p-3 group-hover:scale-105 transition-transform" @error="handleImageError" />
                  <Badge v-if="image.is_primary" value="Primary" severity="success" class="absolute top-2 left-2" />
                  <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                    <Button icon="pi pi-search-plus" rounded severity="info" text @click="openImagePreview(image)" />
                    <Button icon="pi pi-download" rounded severity="info" text @click.stop="downloadImageAsset(image)" />
                  </div>
                </div>
              </div>
            </template>
          </Card>
        </div>
  
  
  
  
      </template>
  
      <Card v-else><template #content>
          <p class="py-8 text-center text-gray-500">Product not found.</p>
        </template></Card>
      <Dialog v-model:visible="previewVisible" :header="previewImage?.file_name || 'Product Image'" :modal="true"
        class="w-full max-w-3xl">
        <img v-if="previewImage" :src="previewImage.url" :alt="previewImage.file_name || product?.product_name"
          class="max-h-[70vh] w-full object-contain" />
      </Dialog>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import procurementService from '@/services/procurement.service'
import Carousel from 'primevue/carousel'

const route = useRoute()
const router = useRouter()
const loading = ref(true)
const product = ref<any>(null)
const previewVisible = ref(false)
const previewImage = ref<any>(null)

const productImages = computed(() => (Array.isArray(product.value?.assets) ? product.value.assets : [])
  .filter((asset: any) => ['Image_Main', 'Image_Gallery', 'Image_360'].includes(asset.asset_type) && asset.url)
  .sort((a: any, b: any) => Number(b.is_primary) - Number(a.is_primary) || Number(a.display_order || 0) - Number(b.display_order || 0)))
const supplierNames = computed(() => (Array.isArray(product.value?.suppliers) ? product.value.suppliers : [])
  .map((supplier: any) => supplier.supplier_name || supplier.company_name)
  .filter(Boolean)
  .join(', '))
const openImage = (image: any) => { previewImage.value = image; previewVisible.value = true }
const openImagePreview = openImage
const downloadImageAsset = (image: any) => {
  if (image?.url) window.open(image.url, '_blank')
}
const handleImageError = (event: Event) => {
  const image = event.target as HTMLImageElement
  image.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="300"%3E%3Crect fill="%23f0f0f0" width="400" height="300"/%3E%3Ctext fill="%23999" x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle"%3EImage not found%3C/text%3E%3C/svg%3E'
}

const stockStatusLabel = computed(() => {
  const stock = Number(product.value?.current_stock || 0)
  const reorder = Number(product.value?.reorder_point || 0)
  return stock <= 0 ? 'Out of Stock' : stock <= reorder ? 'Low Stock' : 'In Stock'
})
const stockStatusSeverity = computed(() => stockStatusLabel.value === 'Out of Stock' ? 'danger' : stockStatusLabel.value === 'Low Stock' ? 'warning' : 'success')
const formatNumber = (value: any) => new Intl.NumberFormat('en-PH').format(Number(value || 0))
const formatMoney = (value: any) => Number(value || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })

onMounted(async () => {
  try {
    const response = await procurementService.getProcurementProduct(Number(route.params.id), {
      branch_id: route.query.branch_id || undefined,
    })
    product.value = response?.data || null
  } catch (error) {
    product.value = null
  } finally {
    loading.value = false
  }
})
</script>
