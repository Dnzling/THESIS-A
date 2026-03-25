<template>
  <div class="mx-auto max-w-6xl px-2 py-4 sm:px-4 md:px-6 md:py-8">
    <div v-if="loading" class="space-y-6">
      <div class="grid grid-cols-1 gap-8 rounded-2xl border border-slate-200 bg-white p-5 md:grid-cols-2">
        <Skeleton height="420px" borderRadius="1rem" />
        <div class="space-y-4">
          <Skeleton width="120px" height="26px" />
          <Skeleton width="85%" height="40px" />
          <Skeleton width="95%" height="18px" />
          <Skeleton width="95%" height="18px" />
          <Skeleton width="45%" height="40px" />
          <Skeleton width="40%" height="20px" />
          <div class="flex gap-3 pt-2">
            <Skeleton width="180px" height="42px" />
            <Skeleton width="160px" height="42px" />
          </div>
        </div>
      </div>
  
      <div class="rounded-2xl border border-slate-200 bg-white p-4">
        <div class="flex gap-2 pb-4">
          <Skeleton width="180px" height="34px" />
          <Skeleton width="120px" height="34px" />
          <Skeleton width="150px" height="34px" />
        </div>
        <Skeleton width="100%" height="18px" class="mb-2" />
        <Skeleton width="92%" height="18px" class="mb-2" />
        <Skeleton width="70%" height="18px" />
      </div>
    </div>
  
    <Card v-else-if="product" class="plain-card overflow-hidden">
      <template #content>
        <div class="grid grid-cols-1 gap-5 md:gap-8 md:grid-cols-2">
          <div class="overflow-hidden rounded-2xl bg-slate-100">
            <div class="flex items-center justify-end p-3">
              <Button v-if="selectedModel3D" :label="show3DViewer ? 'Show Photo' : '3D'" icon="pi pi-cube" size="small"
                severity="info" raised @click="toggle3DViewer" />
            </div>
  
            <div v-if="show3DViewer && selectedModel3D" class="relative min-h-90 w-full">
              <Model3DPreview :model-url="selectedModel3D.url" :model-format="selectedModel3D.model_format"
                :camera-x="selectedModel3D?.camera_settings?.angle_x ?? 0"
                :camera-y="selectedModel3D?.camera_settings?.angle_y ?? 15"
                :zoom="selectedModel3D?.camera_settings?.zoom ?? 1.5" height="360px" />
            </div>
  
            <img v-else-if="primaryImage" :src="primaryImage" :alt="product.product_name"
              class="h-full w-full object-cover" />
            <div v-else class="flex min-h-90 items-center justify-center text-slate-400">
              <i class="pi pi-image text-5xl opacity-30" />
            </div>
  
            <div v-if="!show3DViewer && galleryImages.length > 1" class="border-t border-slate-200 bg-white p-3">
              <Carousel :value="galleryImages" :numVisible="4" :numScroll="1" :circular="false">
                <template #item="{ data }">
                  <button type="button" class="h-18 w-18 overflow-hidden rounded-lg border transition"
                    :class="data === primaryImage ? 'border-blue-500' : 'border-slate-200 hover:border-slate-300'"
                    @click="selectedImage = data">
                    <img :src="data" alt="Product image" class="h-full w-full object-cover" />
                  </button>
                </template>
              </Carousel>
            </div>
          </div>
  
          <div class="space-y-4">
            <Tag :value="stockLabel" :severity="stockSeverity" />
            <h1 class="text-2xl md:text-3xl font-semibold text-slate-900">{{ product.product_name }}</h1>
            <p class="text-sm leading-7 text-slate-600">{{ product.description || 'No description available.' }}</p>
            <p class="text-3xl font-bold text-slate-900">{{ formatCurrency(product.price) }}</p>
            <p class="text-sm text-slate-500">{{ product.quantity_available || 0 }} items available</p>
  
            <div class="rounded-xl border border-slate-200 p-3">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Dimensions</p>
              <div class="mt-2 grid grid-cols-2 gap-2 text-sm text-slate-700">
                <p>Length: <span class="font-semibold">{{ displayDimension(product?.dimensions?.length_cm, 'cm') }}</span>
                </p>
                <p>Width: <span class="font-semibold">{{ displayDimension(product?.dimensions?.width_cm, 'cm') }}</span>
                </p>
                <p>Height: <span class="font-semibold">{{ displayDimension(product?.dimensions?.height_cm, 'cm') }}</span>
                </p>
                <p>Weight: <span class="font-semibold">{{ displayDimension(product?.dimensions?.weight_kg, 'kg') }}</span>
                </p>
              </div>
            </div>
  
            <div v-if="product?.variations?.length" class="space-y-2">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Variations</p>
              <div class="flex flex-wrap gap-2">
                <Button v-for="variation in product.variations" :key="variation.id" size="small"
                  :severity="selectedVariationId === variation.id ? 'info' : 'secondary'"
                  :outlined="selectedVariationId !== variation.id" @click="selectVariation(variation.id)">
                  {{ variationLabel(variation) }}
                </Button>
              </div>
              <p v-if="selectedVariation" class="text-sm text-slate-600">
                Variation Price: <span class="font-semibold">{{ formatCurrency(selectedVariation.final_price ||
                  product.price) }}</span>
                <span v-if="selectedVariation.model_3d" class="ml-2 text-xs text-emerald-600">(Has 3D model)</span>
              </p>
            </div>
  
            <div class="flex flex-col gap-2 pt-2 sm:flex-row sm:items-center sm:gap-3">
              <InputNumber v-model="quantity" :min="1" :max="Math.max(1, Number(product.quantity_available || 1))"
                showButtons class="w-full sm:w-auto" />
              <Button label="Add to Cart" severity="info" class="w-full sm:w-auto"
                :disabled="Number(product.quantity_available || 0) <= 0" @click="addToCart" />
            </div>
          </div>
        </div>
      </template>
    </Card>
  
    <Card v-if="product" class="mt-6 plain-card">
      <template #content>
        <button type="button"
          class="flex w-full items-center justify-between gap-3 rounded-xl border border-slate-200 p-4 text-left transition hover:border-slate-300"
          @click="goStorePage">
          <div class="flex items-center gap-3">
            <img v-if="storeInfo?.logo" :src="storeInfo.logo" :alt="storeInfo.name"
              class="h-12 w-12 rounded-xl border border-slate-200 object-cover" />
            <div v-else
              class="flex h-12 w-12 items-center justify-center rounded-xl border border-slate-200 bg-slate-100 text-sm font-semibold text-slate-700">
              {{ (storeInfo?.name || product.store_name || 'S').slice(0, 1).toUpperCase() }}
            </div>
            <div>
              <p class="text-sm text-slate-500">Store</p>
              <p class="text-base font-semibold text-slate-900">{{ storeInfo?.name || product.store_name || 'Store' }}</p>
            </div>
          </div>
          <div class="text-right">
            <p class="text-sm font-semibold text-amber-500">★ {{ storeRating.toFixed(2) }}</p>
            <p class="text-xs text-slate-500">{{ storeRatingCount }} reviews</p>
          </div>
        </button>
        <div class="mt-2 justify-between flex gap-3">
          <Button label="Report" icon="pi pi-exclamation-triangle" severity="danger" text size="small"
            @click="goChatStore" />
          <Button label="Chat Store" icon="pi pi-comments" severity="help" text size="small" @click="goChatStore" />
        </div>
      </template>
    </Card>
  
    <div v-if="product" class="mt-6 rounded-2xl border border-slate-200 bg-white">
      <div class="flex flex-wrap gap-2 border-b border-slate-200 p-3">
        <Button label="Product Description" size="small" :severity="activeTab === 'description' ? 'info' : 'secondary'"
          :outlined="activeTab !== 'description'" @click="activeTab = 'description'" />
        <Button label="Reviews" size="small" :severity="activeTab === 'reviews' ? 'info' : 'secondary'"
          :outlined="activeTab !== 'reviews'" @click="activeTab = 'reviews'" />
        <Button label="Recommended" size="small" :severity="activeTab === 'recommended' ? 'info' : 'secondary'"
          :outlined="activeTab !== 'recommended'" @click="activeTab = 'recommended'" />
      </div>
  
      <div v-if="activeTab === 'description'" class="p-5">
        <h3 class="text-lg font-semibold text-slate-900">Product Description</h3>
        <p class="mt-3 text-sm leading-7 text-slate-600">
          {{ product.description || 'No description available for this product yet.' }}
        </p>
      </div>
  
      <div v-else-if="activeTab === 'reviews'" class="p-5">
        <div class="grid grid-cols-1 gap-6 border-b border-slate-200 pb-4 lg:grid-cols-12">
          <div class="lg:col-span-3">
            <p class="text-sm text-slate-600">Average Rating</p>
            <div
              class="mt-3 inline-flex h-40 w-40 items-center justify-center rounded-full border-[12px] border-amber-400 text-center">
              <div>
                <i class="pi pi-star-fill text-amber-500"></i>
                <p class="mt-1 text-4xl font-semibold text-slate-900">{{ averageRating }}</p>
                <p class="text-sm text-slate-500">/ 5</p>
              </div>
            </div>
          </div>
          <div class="lg:col-span-9">
            <p class="text-sm text-slate-600">Our customer satisfaction</p>
            <div class="mt-4 space-y-2">
              <div v-for="row in ratingBreakdown" :key="row.star" class="flex items-center gap-3">
                <span class="w-10 text-sm text-slate-600">{{ row.star }}★</span>
                <div class="h-2 flex-1 rounded-full bg-slate-200">
                  <div class="h-2 rounded-full bg-amber-400" :style="{ width: `${row.percent}%` }"></div>
                </div>
                <span class="w-12 text-right text-xs text-slate-500">{{ row.count }}</span>
              </div>
            </div>
          </div>
        </div>
  
        <div class="mt-4 space-y-4">
          <div v-for="review in reviews" :key="review.id" class="rounded-xl border border-slate-200 p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-semibold text-slate-900">{{ review.customer_name }}</p>
                <p class="text-xs text-slate-500">{{ formatDate(review.created_at) }}</p>
              </div>
              <div class="text-sm text-amber-500">{{ '★'.repeat(review.rating) }}</div>
            </div>
            <p class="mt-3 text-sm text-slate-600">{{ review.review_text || 'No review text provided.' }}</p>
          </div>
        </div>
      </div>
  
      <div v-else class="p-5">
        <div v-if="recommendedProducts.length" class="grid grid-cols-2 gap-4 md:grid-cols-4">
          <button v-for="item in recommendedProducts" :key="item.id" type="button"
            class="rounded-xl border border-slate-200 p-2 text-left transition hover:border-slate-300"
            @click="goToRecommended(item.id)">
            <div class="h-28 overflow-hidden rounded-lg bg-slate-100">
              <img v-if="item.image" :src="item.image" :alt="item.product_name" class="h-full w-full object-cover" />
              <div v-else class="flex h-full items-center justify-center text-slate-400">
                <i class="pi pi-image" />
              </div>
            </div>
            <p class="mt-2 line-clamp-2 text-xs font-medium text-slate-800">{{ item.product_name }}</p>
            <p class="mt-1 text-sm font-semibold text-slate-900">{{ formatCurrency(item.price) }}</p>
          </button>
        </div>
        <p v-else class="text-sm text-slate-500">No recommended products yet.</p>
      </div>
    </div>
  
    <div v-else class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-500">
      Product not found.
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import ecommerceService from '@/services/ecommerce.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import InputNumber from 'primevue/inputnumber'
import Skeleton from 'primevue/skeleton'
import Carousel from 'primevue/carousel'
import Model3DPreview from '@/components/merchandising/Model3DPreview.vue'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const quantity = ref(1)
const product = ref<any>(null)
const selectedVariationId = ref<number | null>(null)
const activeTab = ref<'description' | 'reviews' | 'recommended'>('description')
const recommendedProducts = ref<any[]>([])
const storeInfo = ref<{ id: number; name: string; logo: string | null; rating_avg: number; rating_count: number } | null>(null)
const show3DViewer = ref(false)
const selectedImage = ref<string | null>(null)
const selectedVariation = computed(() =>
  (product.value?.variations || []).find((v: any) => Number(v.id) === Number(selectedVariationId.value)) || null
)
const selectedModel3D = computed(() => selectedVariation.value?.model_3d || product.value?.model_3d || null)
const galleryImages = computed<string[]>(() => {
  const images = product.value?.images
  if (!Array.isArray(images)) return product.value?.image ? [product.value.image] : []
  return images
    .map((img: any) => (typeof img === 'string' ? img : (img?.url || img?.image_url || img?.src || '')))
    .filter(Boolean)
})
const primaryImage = computed(() => {
  return selectedImage.value || galleryImages.value[0] || product.value?.image || null
})
const storeRating = computed(() => Number(storeInfo.value?.rating_avg ?? 0))
const storeRatingCount = computed(() => Number(storeInfo.value?.rating_count ?? 0))
const reviews = computed(() => product.value?.reviews?.data || [])
const averageRating = computed(() => {
  return Number(product.value?.reviews_summary?.average_rating || 0).toFixed(1)
})
const ratingBreakdown = computed(() => {
  const counts = (product.value?.reviews_summary?.breakdown || []).map((row: any) => ({
    star: Number(row.star),
    count: Number(row.count || 0),
  }))
  const max = Math.max(...counts.map(c => c.count), 1)
  return counts.map(c => ({
    ...c,
    percent: Math.round((c.count / max) * 100),
  }))
})

async function toggle3DViewer() {
  show3DViewer.value = !show3DViewer.value
}

const stockLabel = computed(() => {
  const status = String(product.value?.stock_status || '').toLowerCase()
  if (status === 'in_stock') return 'In Stock'
  if (status === 'low_stock') return 'Low Stock'
  return 'Out of Stock'
})

const stockSeverity = computed(() => {
  const status = String(product.value?.stock_status || '').toLowerCase()
  if (status === 'in_stock') return 'success'
  if (status === 'low_stock') return 'warning'
  return 'danger'
})

const formatCurrency = (val: any) =>
  new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(val || 0))

function displayDimension(value: any, unit: 'cm' | 'kg') {
  if (value === null || value === undefined || value === '') return '-'
  const num = Number(value)
  if (Number.isNaN(num)) return '-'
  return `${num.toFixed(2)} ${unit}`
}

function formatDate(value: string) {
  if (!value) return '-'
  return new Date(value).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

function variationLabel(variation: any) {
  const parts = [variation.color, variation.size, variation.material].filter(Boolean)
  return parts.length ? parts.join(' / ') : variation.variation_name
}

function selectVariation(variationId: number) {
  selectedVariationId.value = Number(variationId)
}

async function loadProduct() {
  loading.value = true
  try {
    const response = await ecommerceService.getProduct(String(route.params.id))
    product.value = response.data?.data || response.data
    selectedImage.value = null
    selectedVariationId.value = null
  } catch {
    product.value = null
    toast.add({ severity: 'error', summary: 'Error', detail: 'Unable to load product.', life: 2500 })
  } finally {
    loading.value = false
  }
}

async function loadRecommendedProducts() {
  try {
    const response = await ecommerceService.getProducts({ per_page: 8, search: '' })
    const all = response.data?.data?.data || []
    recommendedProducts.value = all.filter((p: any) => Number(p.id) !== Number(product.value?.id)).slice(0, 4)
  } catch {
    recommendedProducts.value = []
  }
}

async function loadStoreInfo() {
  if (!product.value?.store_id) {
    storeInfo.value = null
    return
  }

  try {
    const response = await ecommerceService.getStore(product.value.store_id)
    const store = response.data?.data || {}
    storeInfo.value = {
      id: Number(store.id || product.value.store_id),
      name: String(store.store_name || product.value.store_name || 'Store'),
      logo: store.logo || product.value.store_logo || null,
      rating_avg: Number(store.rating_avg || 0),
      rating_count: Number(store.rating_count || 0),
    }
  } catch {
    storeInfo.value = {
      id: Number(product.value.store_id),
      name: String(product.value.store_name || 'Store'),
      logo: product.value.store_logo || null,
      rating_avg: 0,
      rating_count: 0,
    }
  }
}

async function addToCart() {
  if (!product.value?.id) return
  try {
    await ecommerceService.addToCart({
      product_id: Number(product.value.id),
      variation_id: selectedVariationId.value ? Number(selectedVariationId.value) : null,
      quantity: Number(quantity.value || 1)
    })
    window.dispatchEvent(new Event('ecommerce-cart-updated'))
    toast.add({ severity: 'success', summary: 'Added to cart', detail: `${product.value.product_name}`, life: 1600 })
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Could not add to cart.', life: 2200 })
  }
}

function goBack() {
  router.push({ name: 'ecommerce.products' })
}

function goToRecommended(id: number) {
  router.push({ name: 'ecommerce.product', params: { id } })
}

function goStorePage() {
  const storeId = Number(storeInfo.value?.id || product.value?.store_id || 0)
  if (!storeId) return
  router.push({ name: 'ecommerce.store-profile', params: { storeId } })
}

function goChatStore() {
  const storeId = Number(storeInfo.value?.id || product.value?.store_id || 0)
  if (!storeId) return
  router.push({
    name: 'ecommerce.chats',
    query: {
      store_id: String(storeId),
      product_id: String(product.value?.id || ''),
      product_name: String(product.value?.product_name || ''),
    },
  })
}

onMounted(async () => {
  await loadProduct()
  await loadStoreInfo()
  await loadRecommendedProducts()
})
watch(() => route.params.id, async () => {
  show3DViewer.value = false
  await loadProduct()
  await loadStoreInfo()
  await loadRecommendedProducts()
})
</script>

<style scoped>
:deep(.plain-card.p-card) {
  border: 1px solid #e2e8f0;
  box-shadow: none;
}
</style>
