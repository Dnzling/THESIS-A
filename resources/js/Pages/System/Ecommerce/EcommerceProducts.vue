<template>
  <div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-6 py-4 md:py-8">
  
    <Carousel />
  
  
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 md:gap-8">
      <aside class="lg:col-span-3 space-y-5 md:space-y-8">
        <div>
          <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4">Categories</h3>
          <nav class="flex gap-2 overflow-x-auto pb-1 lg:flex-col lg:overflow-visible">
            <button @click="setCategory('all')"
              :class="[selectedCategory === 'all' ? 'bg-orange-500 text-white shadow-md' : 'text-slate-600 hover:bg-orange-50']"
              class="shrink-0 flex items-center justify-between px-3 py-2 md:px-4 md:py-3 rounded-xl transition-all duration-200 text-xs md:text-sm font-medium">
              <span>All Collection</span>
              <Badge :value="products.length" :severity="selectedCategory === 'all' ? 'info' : 'secondary'" />
            </button>
            <button v-for="category in categoryOptions" :key="category" @click="setCategory(category)"
              :class="[selectedCategory === category ? 'bg-orange-500 text-white shadow-md' : 'text-slate-600 hover:bg-orange-50']"
              class="shrink-0 flex items-center justify-between px-3 py-2 md:px-4 md:py-3 rounded-xl transition-all duration-200 text-xs md:text-sm font-medium">
              <span class="capitalize">{{ category }}</span>
              <Badge :value="categoryCount(category)" :severity="selectedCategory === category ? 'info' : 'secondary'" />
            </button>
          </nav>
        </div>
  
        <Card class="border border-blue-100 bg-blue-50/60 shadow-none">
          <template #content>
            <div class="space-y-3">
  
              <p class="text-xs font-bold uppercase tracking-wider text-orange-700">Recommendation</p>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label for="budget-min" class="block mb-1">Min Price</label>
                  <InputNumber id="budget-min" v-model="dss.budgetMin" inputClass="text-sm" fluid placeholder="Min Budget"
                    @blur="syncBudgetInputs" />
                </div>
                <div>
                  <label for="budget-max" class="block mb-1">Max Price</label>
                  <InputNumber id="budget-max" v-model="dss.budgetMax" inputClass="text-sm" fluid placeholder="Max Budget"
                    @blur="syncBudgetInputs" />
                </div>
              </div>
              <Slider v-model="dss.budgetRange" range class="mx-1" :min="0" :max="300000" @slideend="syncBudgetRange" />
              <Select v-model="dss.categoryId" :options="dssCategoryOptions" optionLabel="label" optionValue="value" fluid
                placeholder="Category" showClear />
              <div class="grid grid-cols-3 gap-2">
                <InputNumber v-model="dss.lengthCm" :min="0" inputClass="text-sm" fluid placeholder="L cm" />
                <InputNumber v-model="dss.widthCm" :min="0" inputClass="text-sm" fluid placeholder="W cm" />
                <InputNumber v-model="dss.heightCm" :min="0" inputClass="text-sm" fluid placeholder="H cm" />
              </div>
              <div class="flex gap-2">
                <Button label="Reset" size="small" severity="secondary" fluid @click="resetDss" />
                <Button label="Recommend" size="small" severity="warn" :loading="dssLoading" class="w-full"
                  @click="runDss" />
              </div>
            </div>
          </template>
        </Card>
      </aside>
  
      <main class="lg:col-span-9">
        <section v-if="activeCategoryId" class="mb-8">
          <div class="mb-4">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-600">Recommended sellers</p>
            <h2 class="mt-1 text-xl font-bold text-slate-900">Top shops for {{ activeCategoryName || 'this category' }}</h2>
            <p class="mt-1 text-xs text-slate-500">Ranked by monthly sales and available products in this category.</p>
          </div>

          <div v-if="loadingStores" class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
            <Skeleton v-for="index in 5" :key="index" height="150px" borderRadius="1rem" />
          </div>
          <div v-else-if="topStores.length" class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
            <button v-for="store in topStores" :key="store.id" type="button"
              class="group rounded-2xl border border-slate-200 bg-white p-3 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-orange-300 hover:shadow-lg"
              @click="goStore(store.id)">
              <div class="mx-auto h-16 w-16 overflow-hidden rounded-xl border border-slate-100 bg-slate-50">
                <img :src="normalizeImageUrl(store.store_logo) || '/F.svg'" :alt="`${store.store_name} logo`" class="h-full w-full object-cover" @error="onImageError" />
              </div>
              <p class="mt-3 truncate text-center text-xs font-semibold text-slate-900 group-hover:text-orange-600">{{ store.store_name }}</p>
              <p class="mt-1 text-center text-[10px] text-slate-400">{{ store.matching_products_count }} products · {{ store.monthly_sales }} sold</p>
            </button>
          </div>
          <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-white p-5 text-center text-xs text-slate-500">No matching shops are currently available.</div>
        </section>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 md:gap-4 mb-5 md:mb-8">
          <div>
            <h2 class="text-xl md:text-2xl font-bold text-slate-900">{{ activeCategoryId ? `${activeCategoryName || 'Category'} products` : 'Featured Products' }}</h2>
            <p class="text-slate-500 text-sm">{{ activeCategoryId ? `${totalProducts} available items` : `Showing ${filteredProducts.length} items` }}</p>
          </div>
          <Button
            label="View Trending"
            icon="pi pi-chart-line"
            severity="warning"
            outlined
            @click="goTrending"
          />
        </div>

        <div class="mt-3 mb-4 rounded-2xl border border-slate-200 bg-white/95 backdrop-blur p-3 shadow-sm">
          <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3 w-full md:w-auto">
            <IconField iconPosition="left">
              <InputIcon class="pi pi-search" />
              <InputText v-model="search" placeholder="Search furniture..."
                class="w-full md:w-64 rounded-xl! border-slate-200" />
            </IconField>
            <Select v-model="sort" :options="sortOptions" optionLabel="label" optionValue="value"
              class="w-full sm:w-48 rounded-xl! border-slate-200" />
          </div>
        </div>
  
        <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-2 xl:grid-cols-3 gap-6">
          <div v-for="idx in 6" :key="idx" class="bg-white p-4 rounded-2xl border border-slate-100">
            <Skeleton height="200px" border-radius="1rem" class="mb-4" />
            <Skeleton width="60%" height="1.5rem" class="mb-2" />
            <Skeleton width="40%" height="1rem" />
          </div>
        </div>
  
        <div v-if="dssResults.length" class="mb-8">
          <h3 class="mb-3 text-lg font-semibold text-slate-900">Result Recommended For You</h3>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
              <div v-for="item in dssResults" :key="`dss-${item.id}`"
                class="group relative cursor-pointer bg-white rounded-2xl border border-blue-100 p-3 transition-all duration-300 hover:shadow-2xl hover:-translate-y-1"
              @click="goProduct(item.id)">
              <label class="mb-2 flex w-fit cursor-pointer items-center gap-2 text-xs font-medium text-slate-600" @click.stop>
                <Checkbox :modelValue="isCompared(item.id)" binary :disabled="comparisonFull && !isCompared(item.id)"
                  @update:modelValue="toggleComparison(item)" />
                Compare
              </label>
              <div class="relative overflow-hidden rounded-xl bg-slate-100 aspect-square mb-4">
                <img :src="normalizeImageUrl(item.image) || '/F.svg'" :alt="item.product_name"
                  class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                  @error="onImageError" />
                <div
                  class="absolute left-2 top-2 rounded-full bg-blue-600/90 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-white">
                  Match {{ Math.round((item.score || 0) * 100) }}%
                </div>
                <button type="button"
                  class="absolute bottom-3 right-3 grid h-11 w-11 place-items-center rounded-full border border-slate-200 bg-white/95 text-slate-800 shadow-sm transition hover:scale-105 hover:border-rose-200 hover:text-rose-500"
                  :class="isFavorite(item.id) ? '!border-rose-200 !bg-rose-50 !text-rose-500' : ''"
                  :aria-label="isFavorite(item.id) ? 'Remove from favorites' : 'Add to favorites'"
                  @click.stop="toggleProductFavorite(item.id)">
                  <i :class="isFavorite(item.id) ? 'pi pi-heart-fill' : 'pi pi-heart'" class="text-lg" />
                </button>
              </div>
  
              <div class="px-2 pb-2">
                <div class="mb-1 text-[10px] font-bold uppercase tracking-widest text-sky-600">{{ item.category || 'Home'
                  }}</div>
                <h3 class="font-semibold text-slate-800 truncate mb-2 group-hover:text-sky-600 transition-colors">
                  {{ item.product_name }}
                </h3>
                <div class="flex items-center justify-between">
                  <div>
                    <span class="text-md text-green-600 font-semibold">₱{{ formatMoney(item.price) }}</span>
                    <p class="text-[10px] text-slate-400"></p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
  
        <div v-else-if="filteredProducts.length" class="grid grid-cols-2 sm:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6">
          <div v-for="product in filteredProducts" :key="product.id"
            class="group relative cursor-pointer bg-white rounded-2xl border border-slate-100 p-3 transition-all duration-300 hover:shadow-2xl hover:-translate-y-1"
            @click="goProduct(product.id)">
            <label class="mb-2 flex w-fit cursor-pointer items-center gap-2 text-xs font-medium text-slate-600" @click.stop>
              <Checkbox :modelValue="isCompared(product.id)" binary :disabled="comparisonFull && !isCompared(product.id)"
                @update:modelValue="toggleComparison(product)" />
              Compare
            </label>
            <div class="relative overflow-hidden rounded-xl bg-slate-100 aspect-square mb-4">
              <img :src="normalizeImageUrl(product.image) || '/F.svg'" :alt="product.product_name"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                @error="onImageError" />
              <Badge v-if="product.has_discount" :value="`${product.discount_percentage}% OFF`" severity="danger" class="!absolute !left-2 !top-2 !text-[10px]" />
              <button type="button"
                class="absolute bottom-3 right-3 grid h-11 w-11 place-items-center rounded-full border border-slate-200 bg-white/95 text-slate-800 shadow-sm transition hover:scale-105 hover:border-rose-200 hover:text-rose-500"
                :class="isFavorite(product.id) ? '!border-rose-200 !bg-rose-50 !text-rose-500' : ''"
                :aria-label="isFavorite(product.id) ? 'Remove from favorites' : 'Add to favorites'"
                @click.stop="toggleProductFavorite(product.id)">
                <i :class="isFavorite(product.id) ? 'pi pi-heart-fill' : 'pi pi-heart'" class="text-lg" />
              </button>
  
            </div>
  
            <div class="px-2 pb-2">
              <div class="flex justify-between items-start mb-1">
                <span class="text-[10px] font-bold uppercase tracking-widest text-sky-600">{{ product.category || 'Home'
                  }}</span>
                <div class="flex items-center gap-1 text-xs font-medium text-slate-600">
                  <i class="pi pi-star-fill text-amber-400 text-[10px]" />
                  {{ Number(product.rating_avg || 0).toFixed(1) }}
                </div>
              </div>
              <h3 class="font-semibold text-slate-800 truncate mb-2 group-hover:text-sky-600 transition-colors">
                {{ product.product_name }}
              </h3>
              <div class="flex items-center justify-between">
                <div>
                  <div v-if="product.has_discount" class="flex flex-wrap items-baseline gap-2">
                    <span class="text-md font-semibold text-rose-600">₱{{ formatMoney(product.discounted_price) }}</span>
                    <span class="text-xs text-slate-400 line-through">₱{{ formatMoney(product.base_price) }}</span>
                  </div>
                  <span v-else class="text-md text-green-600 font-semibold">₱{{ formatMoney(product.base_price) }}</span>
                  <p class="text-[10px] text-slate-400"></p>
                </div>
                <span class="text-[10px] text-slate-400 font-medium">{{ product.rating_count || 0 }} reviews</span>
              </div>
            </div>
          </div>
        </div>
  
        <div v-else class="text-center py-20 bg-slate-50 rounded-3xl border-2 border-dashed border-slate-200">
          <i class="pi pi-search text-4xl text-slate-300 mb-4" />
          <p class="text-slate-500 font-medium">We couldn't find any matches for your search.</p>
          <Button label="Clear Filters" link @click="search = ''; selectedCategory = 'all'" />
        </div>

        <Paginator
          v-if="activeCategoryId && totalProducts > perPage"
          :first="(currentPage - 1) * perPage"
          :rows="perPage"
          :totalRecords="totalProducts"
          template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products"
          class="mt-6 !justify-center !text-xs"
          @page="onProductPage"
        />

      </main>
    </div>
  </div>
  <Transition name="compare-tray">
    <div v-if="comparisonProducts.length" class="fixed inset-x-0 bottom-[73px] z-40 px-3 md:bottom-4">
      <div class="mx-auto flex max-w-4xl items-center gap-3 rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-2xl backdrop-blur">
        <div class="min-w-0 flex-1">
          <div class="mb-2 flex items-center justify-between">
            <p class="text-sm font-bold text-slate-900">Compare products <span class="text-orange-600">{{ comparisonCount }}/5</span></p>
            <button type="button" class="text-xs font-medium text-slate-500 hover:text-rose-600" @click="clearComparison">Clear all</button>
          </div>
          <div class="flex gap-2 overflow-x-auto">
            <div v-for="item in comparisonProducts" :key="item.id" class="relative h-12 w-12 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
              <img :src="normalizeImageUrl(String(item.image || '')) || '/F.svg'" :alt="item.product_name" class="h-full w-full object-cover" />
              <button type="button" class="absolute right-0 top-0 grid h-5 w-5 place-items-center rounded-bl-md bg-slate-900/75 text-white" @click="removeComparison(item.id)">
                <i class="pi pi-times text-[9px]" />
              </button>
            </div>
          </div>
        </div>
        <Button :label="`Compare (${comparisonCount})`" icon="pi pi-arrow-right" iconPos="right" severity="warn"
          :disabled="comparisonCount < 2" @click="router.push({ name: 'ecommerce.compare' })" />
      </div>
    </div>
  </Transition>
  <MarketingFooter />
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ecommerceService from '@/services/ecommerce.service'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Slider from 'primevue/slider'
import InputNumber from 'primevue/inputnumber'
import Carousel from '@/Components/Ecommerce/carousel.vue'
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'
import MarketingFooter from '@/Components/MarketingFooter.vue'
import Paginator from 'primevue/paginator'
import { useAuthStore } from '@/stores/auth'
import { showAlert } from '@/utils/swal'
import { useToast } from 'primevue/usetoast'
import Checkbox from 'primevue/checkbox'
import { useProductComparison } from '@/composables/useProductComparison'

defineOptions({
  layout: EcommerceMobileWrapper,
})

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const toast = useToast()
const {
  products: comparisonProducts,
  count: comparisonCount,
  isFull: comparisonFull,
  contains: isCompared,
  add: addComparison,
  remove: removeComparison,
  clear: clearComparison,
} = useProductComparison()

const loading = ref(false)
const loadingStores = ref(false)
const products = ref<any[]>([])
const topStores = ref<any[]>([])
const activeCategoryName = ref('')
const currentPage = ref(1)
const perPage = 12
const totalProducts = ref(0)
const activeCategoryId = computed(() => Number(route.query.category_id || 0))
const search = ref(String(route.query.search || ''))
const selectedCategory = ref<string>('all')
const sort = ref<'popular' | 'latest' | 'price_asc' | 'price_desc'>('popular')
const dssLoading = ref(false)
const dssResults = ref<any[]>([])
const favoriteProductIds = ref<number[]>([])
const favoriteRequests = ref<number[]>([])
const dss = ref({
  budgetMin: 0,
  budgetMax: 50000,
  budgetRange: [0, 50000] as [number, number],
  categoryId: null as number | null,
  lengthCm: null as number | null,
  widthCm: null as number | null,
  heightCm: null as number | null,
})

const sortOptions = [
  { label: 'Featured', value: 'popular' },
  { label: 'Latest', value: 'latest' },
  { label: 'Price: Low to High', value: 'price_asc' },
  { label: 'Price: High to Low', value: 'price_desc' },
]

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

function isFavorite(productId: number | string): boolean {
  return favoriteProductIds.value.includes(Number(productId))
}

async function loadFavorites() {
  if (!authStore.isAuthenticated) {
    favoriteProductIds.value = []
    return
  }
  try {
    const response = await ecommerceService.getFavorites()
    favoriteProductIds.value = (response.data?.data?.product_ids || []).map((id: any) => Number(id))
  } catch {
    favoriteProductIds.value = []
  }
}

async function toggleProductFavorite(productId: number | string) {
  const id = Number(productId)
  if (!id || favoriteRequests.value.includes(id)) return
  if (!authStore.isAuthenticated) {
    router.push({ name: 'customer.login', query: { redirect: route.fullPath || '/shop' } })
    return
  }

  const wasFavorite = isFavorite(id)
  favoriteProductIds.value = wasFavorite
    ? favoriteProductIds.value.filter((favoriteId) => favoriteId !== id)
    : [...favoriteProductIds.value, id]
  favoriteRequests.value = [...favoriteRequests.value, id]
  try {
    const response = await ecommerceService.toggleFavorite(id)
    const saved = Boolean(response.data?.data?.is_favorite)
    favoriteProductIds.value = saved
      ? Array.from(new Set([...favoriteProductIds.value, id]))
      : favoriteProductIds.value.filter((favoriteId) => favoriteId !== id)
    toast.add({
      severity: saved ? 'success' : 'info',
      summary: saved ? 'Added to your favorites' : 'Removed from your favorites',
      detail: saved ? 'You can view this product anytime in My Favorites.' : 'This product was removed from My Favorites.',
      life: 2600,
    })
    window.dispatchEvent(new Event('ecommerce-favorites-updated'))
  } catch (error: any) {
    favoriteProductIds.value = wasFavorite
      ? Array.from(new Set([...favoriteProductIds.value, id]))
      : favoriteProductIds.value.filter((favoriteId) => favoriteId !== id)
    showAlert({ severity: 'error', summary: 'Favorites', detail: error?.response?.data?.message || 'Unable to update favorites.' })
  } finally {
    favoriteRequests.value = favoriteRequests.value.filter((favoriteId) => favoriteId !== id)
  }
}

function toggleComparison(product: any) {
  if (isCompared(product.id)) {
    removeComparison(product.id)
    toast.add({ severity: 'info', summary: 'Removed from comparison', detail: `${product.product_name} was removed.`, life: 2200 })
    return
  }
  const result = addComparison({
    id: Number(product.id),
    product_name: String(product.product_name || 'Product'),
    image: product.image || null,
    price: product.price ?? product.discounted_price ?? product.base_price,
    category: product.category || null,
  })
  if (!result.added && result.reason === 'full') {
    toast.add({ severity: 'warn', summary: 'Comparison is full', detail: 'You can compare up to 5 products only.', life: 2800 })
    return
  }
  toast.add({ severity: 'success', summary: 'Added to comparison', detail: `${comparisonCount.value}/5 products selected.`, life: 2200 })
}

const categoryOptions = computed(() => {
  const set = new Set<string>()
  for (const product of products.value) {
    const category = String(product.category || '').trim()
    if (category) set.add(category)
  }
  return Array.from(set).sort((a, b) => a.localeCompare(b))
})
const dssCategoryOptions = computed(() => {
  const map = new Map<number, string>()
  for (const product of products.value) {
    const id = Number(product.category_id || 0)
    const label = String(product.category || '').trim()
    if (id > 0 && label) map.set(id, label)
  }
  return [{ label: 'All Categories', value: null }, ...Array.from(map.entries()).map(([value, label]) => ({ value, label }))]
})

const filteredProducts = computed(() => {
  let rows = [...products.value]

  if (selectedCategory.value !== 'all') {
    rows = rows.filter((p) => String(p.category || '') === selectedCategory.value)
  }

  const term = search.value.trim().toLowerCase()
  if (term) {
    rows = rows.filter((p) => {
      const name = String(p.product_name || '').toLowerCase()
      const sku = String(p.sku || '').toLowerCase()
      return name.includes(term) || sku.includes(term)
    })
  }

  if (sort.value === 'price_asc') rows.sort((a, b) => Number(a.price || 0) - Number(b.price || 0))
  else if (sort.value === 'price_desc') rows.sort((a, b) => Number(b.price || 0) - Number(a.price || 0))
  else if (sort.value === 'latest') rows.sort((a, b) => new Date(b.created_at || 0).getTime() - new Date(a.created_at || 0).getTime())
  else rows.sort((a, b) => Number(b.monthly_sales || 0) - Number(a.monthly_sales || 0))

  return rows
})

function formatMoney(amount: number | string) {
  return Number(amount || 0).toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

function categoryCount(category: string) {
  return products.value.filter((p) => String(p.category || '') === category).length
}

function setCategory(category: string) {
  selectedCategory.value = category
}

function syncBudgetInputs() {
  const min = Math.max(0, Number(dss.value.budgetMin || 0))
  const max = Math.max(min, Number(dss.value.budgetMax || 0))
  dss.value.budgetMin = min
  dss.value.budgetMax = max
  dss.value.budgetRange = [min, max]
}

function syncBudgetRange() {
  const [min, max] = dss.value.budgetRange
  dss.value.budgetMin = Math.max(0, Number(min || 0))
  dss.value.budgetMax = Math.max(dss.value.budgetMin, Number(max || 0))
}

async function runDss() {
  dssLoading.value = true
  try {
    syncBudgetInputs()
    const res = await ecommerceService.getDssRecommendations({
      budget_min: dss.value.budgetMin,
      budget_max: dss.value.budgetMax,
      category_id: dss.value.categoryId ?? undefined,
      length_cm: dss.value.lengthCm ?? undefined,
      width_cm: dss.value.widthCm ?? undefined,
      height_cm: dss.value.heightCm ?? undefined,
      per_page: 12,
    })
    dssResults.value = res.data?.data?.data || []
  } finally {
    dssLoading.value = false
  }
}

function resetDss() {
  dss.value = {
    budgetMin: 0,
    budgetMax: 50000,
    budgetRange: [0, 50000],
    categoryId: null,
    lengthCm: null,
    widthCm: null,
    heightCm: null,
  }
  dssResults.value = []
}

function applyFilters() {
  // kept for UI parity
}

function goProduct(productId: number) {
  router.push({ name: 'ecommerce.product', params: { id: productId } })
}

function goTrending() {
  router.push({ name: 'ecommerce.trending.view' })
}

function goStore(storeId: number) {
  router.push({ name: 'ecommerce.store-profile', params: { storeId } })
}

function onProductPage(event: any) {
  currentPage.value = Number(event.page) + 1
  loadProducts()
}

async function loadTopStores() {
  if (!activeCategoryId.value) {
    topStores.value = []
    activeCategoryName.value = ''
    return
  }

  loadingStores.value = true
  try {
    const response = await ecommerceService.getTopStoresByCategory(activeCategoryId.value)
    const payload = response.data?.data || {}
    topStores.value = Array.isArray(payload.stores) ? payload.stores.slice(0, 10) : []
    activeCategoryName.value = String(payload.category?.name || '')
  } finally {
    loadingStores.value = false
  }
}

async function loadProducts() {
  loading.value = true
  try {
    const response = await ecommerceService.getActiveStockProducts({
      per_page: activeCategoryId.value ? perPage : 80,
      page: activeCategoryId.value ? currentPage.value : 1,
      search: search.value.trim() || undefined,
      sort: sort.value,
      category_id: route.query.category_id || undefined,
      discounted_only: route.query.deals ? true : undefined,
    })
    const rows = response.data?.data?.data || response.data?.data || []
    products.value = Array.isArray(rows)
      ? rows.filter((product: any) => product.product_type === 'finished_good')
      : []
    const pagination = response.data?.data || {}
    totalProducts.value = Number(pagination.total ?? products.value.length)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadProducts()
  loadTopStores()
  loadFavorites()
})

watch(() => route.query.search, (value) => {
  search.value = String(value || '')
  sort.value = 'popular'
  selectedCategory.value = 'all'
  dssResults.value = []
  loadProducts()
})

watch(() => route.query.category_id, () => {
  selectedCategory.value = 'all'
  dssResults.value = []
  currentPage.value = 1
  loadProducts()
  loadTopStores()
})

watch(() => route.query.deals, () => {
  currentPage.value = 1
  loadProducts()
})
</script>
