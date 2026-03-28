<template>
  <div class="space-y-6 pb-8">
    <div class="rounded-2xl border border-slate-200 bg-white">
      <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-4 py-3 md:px-6">
        <Button label="Back to Store" severity="secondary" outlined @click="goStoreProfile" />
        <div class="hidden text-sm text-slate-400 md:block">|</div>
        <p class="text-lg font-semibold text-slate-900">{{ storeName }}</p>
        <div class="ml-auto flex items-center gap-2">
          <InputText v-model="search" placeholder="Search products" fluid class="w-[16rem]" @keyup.enter="loadProducts" />
          <Button label="Search" severity="info" @click="loadProducts" />
        </div>
      </div>

      <div class="relative h-56 overflow-hidden rounded-b-2xl md:h-72">
        <img src="https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1800&q=80" alt="Store banner" class="h-full w-full object-cover" />
        <div class="absolute inset-0 bg-black/35" />
        <div class="absolute inset-0 flex items-center justify-center">
          <div class="text-center text-white">
            <p class="text-sm uppercase tracking-[0.16em] text-white/80">Store Collection</p>
            <h1 class="mt-2 text-4xl font-bold md:text-5xl">Products</h1>
            <p class="mt-2 text-sm text-white/80">
              Home <span class="px-2">&gt;</span> Products
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[280px_1fr]">
      <Card class="h-fit border border-slate-200 shadow-none">
        <template #content>
          <div class="space-y-4">
            <h3 class="text-lg font-semibold text-slate-900">Categories</h3>
            <div class="max-h-[28rem] space-y-2 overflow-y-auto pr-1">
              <button
                type="button"
                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm transition"
                :class="!selectedCategoryId ? 'bg-sky-50 text-sky-700' : 'hover:bg-slate-50 text-slate-600'"
                @click="setCategory(null)"
              >
                <span>All Products</span>
                <span class="text-xs">{{ products.length }}</span>
              </button>
              <button
                v-for="category in categories"
                :key="category.id"
                type="button"
                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm transition"
                :class="selectedCategoryId === category.id ? 'bg-sky-50 text-sky-700' : 'hover:bg-slate-50 text-slate-600'"
                @click="setCategory(category.id)"
              >
                <span>{{ category.name }}</span>
                <span class="text-xs text-slate-400">{{ category.product_count || 0 }}</span>
              </button>
            </div>
          </div>
        </template>
      </Card>

      <div class="space-y-4">
        <Card class="border border-slate-200 shadow-none">
          <template #content>
            <div class="flex flex-wrap items-center justify-between gap-3">
              <h2 class="text-3xl font-bold text-slate-900">Products</h2>
              <div class="flex items-center gap-2">
                <span class="text-sm text-slate-500">Sort by</span>
                <Select
                  v-model="sort"
                  :options="sortOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Sort"
                  class="w-44"
                  @change="loadProducts"
                />
              </div>
            </div>
          </template>
        </Card>

        <div v-if="loadingProducts" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <Card v-for="idx in 8" :key="idx" class="border border-slate-200 shadow-none">
            <template #content>
              <Skeleton height="10rem" class="mb-3" />
              <Skeleton height="1.2rem" class="mb-2" />
              <Skeleton height="1rem" width="70%" class="mb-2" />
              <Skeleton height="1rem" width="55%" />
            </template>
          </Card>
        </div>

        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <Card
            v-for="product in products"
            :key="product.id"
            class="cursor-pointer border border-slate-200 shadow-none transition hover:border-sky-300"
            @click="goProduct(product.id)"
          >
            <template #content>
              <div class="space-y-2">
                <img :src="product.image || '/F.svg'" :alt="product.product_name" class="h-44 w-full rounded-lg border border-slate-100 object-cover" />
                <p class="line-clamp-2 text-base font-semibold text-slate-900">{{ product.product_name }}</p>
                <p class="text-sm font-bold text-slate-800">PHP {{ Number(product.price || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                <div class="flex items-center justify-between text-xs text-slate-500">
                  <span class="flex items-center gap-1"><i class="pi pi-star-fill text-amber-500" /> {{ Number(product.rating_avg || 0).toFixed(1) }}</span>
                  <span>{{ product.rating_count || 0 }} reviews</span>
                </div>
                <Tag :value="stockLabel(product.quantity_available)" :severity="stockSeverity(product.quantity_available)" rounded />
              </div>
            </template>
          </Card>
        </div>
      </div>
    </div>

    <Card v-if="activeTab === 'reviews'" class="border border-slate-200 shadow-none">
      <template #content>
        <div class="space-y-3">
          <div class="rounded-xl border border-slate-200 p-3 text-sm">
            <p>Store Rating: <span class="font-semibold">{{ Number(storeRatingAvg).toFixed(2) }}</span></p>
            <p>Total Reviews: <span class="font-semibold">{{ storeRatingCount }}</span></p>
          </div>
          <div v-if="loadingReviews" class="space-y-2">
            <Skeleton v-for="idx in 5" :key="idx" height="3.4rem" />
          </div>
          <div v-else-if="!reviews.length" class="text-sm text-slate-500">No reviews yet.</div>
          <div v-else class="space-y-2">
            <div v-for="review in reviews" :key="review.id" class="rounded-xl border border-slate-200 p-3 text-sm">
              <p class="font-semibold text-slate-800">{{ review.customer_name }} - {{ review.product_name }}</p>
              <p class="text-amber-500"><i class="pi pi-star-fill mr-1" />{{ review.rating }}</p>
              <p class="text-slate-600">{{ review.review_text || 'No comment provided.' }}</p>
            </div>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ecommerceService from '@/services/ecommerce.service'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'

const route = useRoute()
const router = useRouter()

const activeTab = ref<'products' | 'reviews'>((route.query.tab as any) === 'reviews' ? 'reviews' : 'products')
const loadingProducts = ref(false)
const loadingReviews = ref(false)
const products = ref<any[]>([])
const categories = ref<any[]>([])
const reviews = ref<any[]>([])
const storeName = ref('Store Products')
const storeRatingAvg = ref(0)
const storeRatingCount = ref(0)

const search = ref('')
const sort = ref<'popular' | 'latest' | 'price_asc' | 'price_desc'>('popular')
const selectedCategoryId = ref<number | null>(route.query.category_id ? Number(route.query.category_id) : null)
const sortOptions = [
  { label: 'Popular', value: 'popular' },
  { label: 'Latest', value: 'latest' },
  { label: 'Price Low-High', value: 'price_asc' },
  { label: 'Price High-Low', value: 'price_desc' },
]

async function loadStoreSummary() {
  const response = await ecommerceService.getStore(route.params.storeId as string)
  const store = response.data?.data || {}
  storeName.value = store.store_name || 'Store Products'
  storeRatingAvg.value = Number(store.rating_avg || 0)
  storeRatingCount.value = Number(store.rating_count || 0)
  categories.value = store.categories || []
}

async function loadProducts() {
  loadingProducts.value = true
  try {
    const response = await ecommerceService.getActiveStockProducts({
      store_id: Number(route.params.storeId),
      search: search.value || undefined,
      sort: sort.value === 'popular' ? undefined : sort.value,
      category_id: selectedCategoryId.value || undefined,
      per_page: 24,
    })
    products.value = response.data?.data?.data || []
  } finally {
    loadingProducts.value = false
  }
}

async function loadReviews() {
  loadingReviews.value = true
  try {
    const response = await ecommerceService.getStoreReviews(route.params.storeId as string, { per_page: 20 })
    reviews.value = response.data?.data?.data || []
  } finally {
    loadingReviews.value = false
  }
}

function setCategory(categoryId: number | null) {
  selectedCategoryId.value = categoryId
  loadProducts()
}

function setTab(tab: 'products' | 'reviews') {
  activeTab.value = tab
  if (tab === 'reviews' && !reviews.value.length) loadReviews()
}

function stockSeverity(quantity: number | string): 'success' | 'warning' | 'danger' {
  const q = Number(quantity || 0)
  if (q <= 0) return 'danger'
  if (q <= 10) return 'warning'
  return 'success'
}

function stockLabel(quantity: number | string) {
  const q = Number(quantity || 0)
  if (q <= 0) return 'Sold Out'
  if (q <= 10) return `Low Stock (${q})`
  return `In Stock (${q})`
}

function goStoreProfile() {
  router.push({ name: 'ecommerce.store-profile', params: { storeId: route.params.storeId } })
}

function goProduct(productId: number) {
  router.push({ name: 'ecommerce.product', params: { id: productId } })
}

onMounted(async () => {
  await loadStoreSummary()
  if (activeTab.value === 'reviews') {
    await loadReviews()
  } else {
    await loadProducts()
  }
})
</script>

