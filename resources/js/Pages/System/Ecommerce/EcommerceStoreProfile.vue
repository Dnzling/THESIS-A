<template>
  <div class="space-y-6 pb-8">
    <Button icon="pi pi-chevron-left" severity="secondary" text rounded aria-label="Back" @click="goStores" />
  
    <div class="rounded-3xl border border-slate-200 bg-white">
      <div class="relative overflow-hidden rounded-b-2xl px-4 py-8 md:px-6">
        <div class="absolute inset-0 bg-orange-500" />
  
        <div class="relative flex flex-col gap-4 text-white md:flex-row md:items-center md:justify-between">
          <div class="flex items-center gap-4">
            <img :src="normalizeImageUrl(store?.store_logo) || '/F.svg'" alt="Store logo"
              class="h-16 w-16 rounded-2xl border border-white/20 bg-white/10 object-cover" @error="onLogoError" />
            <div class="min-w-0">
              <h1 class="mt-1 truncate text-2xl font-bold md:text-3xl">{{ storeDisplayName }}</h1>
              <p class="mt-1 truncate text-sm text-white/70">{{ storeDisplayLocation }}</p>
            </div>
          </div>
  
          <div class="flex items-center gap-2">
            <Button size="small" :label="store?.is_following ? 'Followed' : 'Follow Store'" :outlined="!store?.is_following"
              :severity="store?.is_following ? 'success' : 'info'" :loading="followLoading" @click="toggleFollow" />
          </div>
        </div>
      </div>
    </div>
  
    <div v-if="loading" class="space-y-3">
      <Card v-for="idx in 6" :key="idx" class="border border-slate-200 shadow-none">
        <template #content>
          <Skeleton height="1.4rem" class="mb-2" />
          <Skeleton height="1rem" width="65%" />
        </template>
      </Card>
    </div>
  
  
    <template v-else-if="store">
      <div class="ml-auto flex items-center justify-end gap-1">
  
        <Button label="Vouchers" severity="warn" text @click="goStoreVouchers" />
        <Button label="Chat Store" severity="help" text @click="goChatStore" />
      </div>
      <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-3 text-sm">
          Products: <span class="font-semibold">{{ store.products_count }}</span>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-3 text-sm">
          Followers: <span class="font-semibold">{{ store.followers_count }}</span>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-3 text-sm">
          Rating: <span class="font-semibold">{{ Number(store.rating_avg || 0).toFixed(2) }}</span>
        </div>
      </div>
  
      <div class="rounded-2xl border border-slate-200 bg-white p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">Performance</h2>
            <p class="text-sm text-slate-500">Response, cancellations, and shipping metrics.</p>
          </div>
          <div class="flex flex-wrap gap-2">
            <Tag :value="`Response ${Number(store.badges?.response_rate || 0).toFixed(1)}%`" severity="warn" />
            <Tag :value="`Cancel ${Number(store.badges?.cancellation_rate || 0).toFixed(1)}%`" severity="secondary" />
            <Tag :value="`Avg ship ${Number(store.badges?.avg_shipping_time_hours || 0).toFixed(1)}h`"
              severity="success" />
          </div>
        </div>
      </div>
  
      <div v-if="(store.branches || []).length" class="rounded-2xl border border-slate-200 bg-white p-4">
        <h2 class="mb-2 text-lg font-semibold text-slate-900">Branches Available</h2>
        <div class="space-y-2">
          <div v-for="branch in store.branches" :key="branch.id" class="rounded-xl border border-slate-200 p-3 text-sm">
            <div class="flex flex-wrap items-start justify-between gap-2">
              <div class="min-w-0">
                <p class="font-semibold text-slate-900">
                  {{ branch.name }}
                  <Tag v-if="branch.is_main_branch" value="Main" severity="warn" class="ml-2" />
                </p>
                <p class="text-slate-600">{{ branch.city || '—' }}, {{ branch.province || '—' }}</p>
                <p class="text-xs text-slate-500">{{ branch.address || '—' }}</p>
              </div>
              <div class="shrink-0 text-xs text-slate-500">{{ branch.contact_number || '' }}</div>
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
                <button type="button"
                  class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm transition"
                  :class="!selectedCategoryId ? 'bg-sky-50 text-sky-700' : 'hover:bg-slate-50 text-slate-600'"
                  @click="setCategory(null)">
                  <span>All Products</span>
                  <span class="text-xs">{{ store.products_count || 0 }}</span>
                </button>
                <button v-for="category in store.categories || []" :key="category.id" type="button"
                  class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm transition"
                  :class="selectedCategoryId === category.id ? 'bg-sky-50 text-sky-700' : 'hover:bg-slate-50 text-slate-600'"
                  @click="setCategory(category.id)">
                  <span>{{ category.name }}</span>
                </button>
              </div>
            </div>
          </template>
        </Card>
  
        <div class="space-y-4">
          <div
            class="sticky top-0 z-10 -mx-4 border-b border-slate-100 bg-white/95 px-4 py-3 backdrop-blur md:static md:mx-0 md:border-0 md:bg-transparent md:px-0 md:py-0 md:backdrop-blur-0">
            <div class="flex flex-wrap items-center gap-2">
              <InputText v-model="search" placeholder="Search products" class="w-full md:w-[18rem]"
                @keyup.enter="resetAndLoadProducts" />
              <Select v-model="sort" :options="sortOptions" optionLabel="label" optionValue="value" placeholder="Sort"
                class="w-full md:w-44" @change="resetAndLoadProducts" />
              <Button label="Search" severity="warn" class="w-full md:w-auto" @click="resetAndLoadProducts" />
            </div>
          </div>
  
          <div v-if="loadingProducts && !products.length" class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-4">
            <Card v-for="idx in 8" :key="idx" class="border border-slate-200 shadow-none">
              <template #content>
                <Skeleton height="10rem" class="mb-3" />
                <Skeleton height="1.2rem" class="mb-2" />
                <Skeleton height="1rem" width="70%" />
              </template>
            </Card>
          </div>
  
          <div v-else-if="!products.length" class="text-sm text-slate-500">No products available.</div>
  
          <div v-else class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-4">
            <Card v-for="product in products" :key="product.id"
              class="cursor-pointer border border-slate-200 shadow-none transition hover:border-sky-300"
              @click="goProduct(product.id)">
              <template #content>
                <div class="space-y-2">
                  <img :src="normalizeImageUrl(product.image) || '/F.svg'" :alt="product.product_name"
                    class="h-40 w-full rounded-xl border border-slate-100 object-cover md:h-44" @error="onImageError" />
                  <p class="line-clamp-2 text-sm font-semibold text-slate-900">{{ product.product_name }}</p>
                  <p class="text-xs text-slate-500">PHP {{ Number(product.final_price ?? product.base_price ??
                    0).toFixed(2) }}</p>
                </div>
              </template>
            </Card>
          </div>
  
          <div ref="loadMoreTrigger" class="h-1" />
  
          <div v-if="loadingMore" class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-4">
            <Card v-for="idx in 4" :key="idx" class="border border-slate-200 shadow-none">
              <template #content>
                <Skeleton height="10rem" class="mb-3" />
                <Skeleton height="1.2rem" class="mb-2" />
                <Skeleton height="1rem" width="70%" />
              </template>
            </Card>
          </div>
        </div>
      </div>
  
      <div class="rounded-2xl border border-slate-200 bg-white p-4">
        <h2 class="mb-2 text-lg font-semibold text-slate-900">Store Vouchers</h2>
        <div v-if="!(store.vouchers || []).length" class="text-sm text-slate-500">No active vouchers.</div>
        <div v-else class="grid grid-cols-1 gap-2 md:grid-cols-2">
          <div v-for="voucher in store.vouchers" :key="voucher.id" class="rounded-xl border border-slate-200 p-3 text-sm">
            <p class="font-semibold">{{ voucher.code }}</p>
            <p class="text-slate-600">
              {{
              voucher.discount_type === 'percent'
              ? `${voucher.discount_value}% off`
              : `PHP ${Number(voucher.discount_value).toFixed(2)} off`
              }}
            </p>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ecommerceService from '@/services/ecommerce.service'
import { showAlert } from '@/utils/swal'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'

defineOptions({
  layout: EcommerceMobileWrapper,
})

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const followLoading = ref(false)
const store = ref<any>(null)

const loadingProducts = ref(false)
const loadingMore = ref(false)
const products = ref<any[]>([])

const selectedCategoryId = ref<number | null>(null)
const search = ref('')
const sort = ref<'popular' | 'latest' | 'price_asc' | 'price_desc'>('popular')
const currentPage = ref(1)
const hasMore = ref(true)

const loadMoreTrigger = ref<HTMLElement | null>(null)
let loadMoreObserver: IntersectionObserver | null = null

const sortOptions = [
  { label: 'Popular', value: 'popular' },
  { label: 'Latest', value: 'latest' },
  { label: 'Price Low-High', value: 'price_asc' },
  { label: 'Price High-Low', value: 'price_desc' },
]

const storeDisplayName = computed(() => {
  const name = String(store.value?.store_name || store.value?.name || '').trim()
  return name !== '' ? name : 'Store'
})

const storeDisplayLocation = computed(() => {
  const city = String(store.value?.city || '').trim()
  const address = String(store.value?.address || '').trim()
  if (city && address) return `${city} · ${address}`
  if (city) return city
  if (address) return address
  return '—'
})

async function loadStore() {
  loading.value = true
  try {
    const response = await ecommerceService.getStore(route.params.storeId as string)
    store.value = response.data?.data || null
    await resetAndLoadProducts()
  } catch {
    showAlert({ severity: 'error', summary: 'Store', detail: 'Failed to load store profile.' })
    goStores()
  } finally {
    loading.value = false
  }
}

async function toggleFollow() {
  if (!store.value) return
  followLoading.value = true
  try {
    if (store.value.is_following) {
      await ecommerceService.unfollowStore(store.value.id)
      store.value.is_following = false
      store.value.followers_count = Math.max(0, Number(store.value.followers_count || 0) - 1)
    } else {
      await ecommerceService.followStore(store.value.id)
      store.value.is_following = true
      store.value.followers_count = Number(store.value.followers_count || 0) + 1
    }
  } catch (error: any) {
    const detail = error?.response?.status === 401 ? 'Please login first to follow stores.' : 'Unable to update follow status.'
    showAlert({ severity: 'warn', summary: 'Follow', detail })
  } finally {
    followLoading.value = false
  }
}

function goStores() {
  router.push({ name: 'ecommerce.stores' })
}

function setCategory(categoryId: number | null) {
  selectedCategoryId.value = categoryId
  resetAndLoadProducts()
}

function normalizeImageUrl(raw: string) {
  if (!raw) return ''
  if (raw.startsWith('http://') || raw.startsWith('https://') || raw.startsWith('data:')) return raw
  if (raw.startsWith('/storage/')) return raw
  if (raw.startsWith('storage/')) return `/${raw}`
  return `/storage/${raw.replace(/^\//, '')}`
}

function onImageError(event: Event) {
  const target = event.target as HTMLImageElement | null
  if (target) target.src = '/F.svg'
}

function onLogoError(event: Event) {
  const target = event.target as HTMLImageElement | null
  if (target) target.src = '/F.svg'
}

async function resetAndLoadProducts() {
  currentPage.value = 1
  hasMore.value = true
  products.value = []
  await loadProductsPage(true)
}

async function loadProductsPage(isFirstPage = false) {
  if (!route.params.storeId) return
  if (!hasMore.value && !isFirstPage) return
  if (loadingProducts.value || loadingMore.value) return

  if (isFirstPage) {
    loadingProducts.value = true
  } else {
    loadingMore.value = true
  }

  try {
    const response = await ecommerceService.getActiveStockProducts({
      store_id: Number(route.params.storeId),
      search: search.value || undefined,
      sort: sort.value === 'popular' ? undefined : sort.value,
      category_id: selectedCategoryId.value || undefined,
      per_page: 12,
      page: currentPage.value,
    })

    const paginated = response.data?.data || {}
    const items = paginated?.data || []
    products.value = currentPage.value === 1 ? items : [...products.value, ...items]

    const current = Number(paginated.current_page || currentPage.value)
    const last = Number(paginated.last_page || current)
    hasMore.value = current < last && items.length > 0
    if (hasMore.value) currentPage.value = current + 1
  } finally {
    loadingProducts.value = false
    loadingMore.value = false
  }
}

function goProduct(id: number) {
  router.push({ name: 'ecommerce.product', params: { id } })
}

function goStoreReviews() {
  router.push({
    name: 'ecommerce.store-products',
    params: { storeId: route.params.storeId },
    query: { tab: 'reviews' },
  })
}

function goStoreVouchers() {
  router.push({ name: 'ecommerce.store-vouchers', params: { storeId: route.params.storeId } })
}

function goChatStore() {
  if (!store.value?.id) return
  router.push({ name: 'ecommerce.chats', query: { store_id: String(store.value.id) } })
}

function setupInfiniteScroll() {
  if (loadMoreObserver) {
    loadMoreObserver.disconnect()
    loadMoreObserver = null
  }

  if (!loadMoreTrigger.value) return
  loadMoreObserver = new IntersectionObserver(
    (entries) => {
      const entry = entries[0]
      if (!entry?.isIntersecting) return
      if (!hasMore.value) return
      loadProductsPage(false)
    },
    { root: null, threshold: 0.1, rootMargin: '300px' },
  )
  loadMoreObserver.observe(loadMoreTrigger.value)
}

onMounted(async () => {
  await loadStore()
  await nextTick()
  setupInfiniteScroll()
})

onBeforeUnmount(() => {
  if (loadMoreObserver) loadMoreObserver.disconnect()
  loadMoreObserver = null
})
</script>
