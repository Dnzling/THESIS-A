<template>
  <div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-6 py-4 md:py-8">
  
    <!-- <Carousel /> -->
  
  
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 md:gap-8">
      <aside class="hidden space-y-3 lg:col-span-3 lg:block">
        <Accordion :value="[]" multiple class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
          <AccordionPanel value="sort">
            <AccordionHeader>Sort by</AccordionHeader>
            <AccordionContent>
              <div class="grid gap-1">
                <button v-for="option in sortOptions" :key="option.value" type="button"
                  class="rounded-lg px-3 py-2 text-left text-xs hover:bg-orange-50"
                  :class="sort === option.value ? 'bg-orange-50 font-semibold text-orange-700' : 'text-slate-700'"
                  @click="sort = option.value">{{ option.label }}</button>
              </div>
            </AccordionContent>
          </AccordionPanel>
          <AccordionPanel value="color">
            <AccordionHeader>Color</AccordionHeader>
            <AccordionContent>
              <p v-if="!availableColors.length" class="text-xs text-slate-500">No colors recorded for these products.</p>
              <div v-else class="flex flex-wrap gap-2">
                <button v-for="color in availableColors" :key="color.name" type="button"
                  class="flex items-center gap-2 rounded-full border px-2 py-1 text-xs"
                  :class="selectedColor === color.name ? 'border-orange-500 text-orange-700' : 'border-slate-200'"
                  @click="selectedColor = selectedColor === color.name ? '' : color.name">
                  <span class="h-4 w-4 rounded-full border border-slate-300" :style="{ backgroundColor: color.hex }" />{{ color.name }}
                </button>
              </div>
            </AccordionContent>
          </AccordionPanel>
          <AccordionPanel value="recommend">
            <AccordionHeader>Recommendations</AccordionHeader>
            <AccordionContent>
            <div class="space-y-3">
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
            </AccordionContent>
          </AccordionPanel>
        </Accordion>
      </aside>
      <div class="lg:hidden">
        <Button label="Filters" icon="pi pi-sliders-h" severity="warn" outlined size="small"
          :badge="activeFilters.length ? String(activeFilters.length) : undefined" @click="mobileFiltersVisible = true" />
      </div>

      <Dialog v-model:visible="mobileFiltersVisible" modal header="Filter products" class="w-[calc(100vw-2rem)] max-w-md"
        :breakpoints="{ '640px': 'calc(100vw - 1rem)' }">
        <div class="max-h-[65vh] space-y-6 overflow-y-auto pr-1 text-sm">
          <section>
            <h3 class="mb-2 font-semibold text-slate-900">Sort by</h3>
            <div class="grid grid-cols-2 gap-2">
              <button v-for="option in sortOptions" :key="option.value" type="button"
                class="rounded-xl border px-3 py-2 text-left text-xs"
                :class="sort === option.value ? 'border-orange-500 bg-orange-50 font-semibold text-orange-700' : 'border-slate-200 text-slate-700'"
                @click="sort = option.value">{{ option.label }}</button>
            </div>
          </section>
          <section class="border-t border-slate-200 pt-4">
            <h3 class="mb-2 font-semibold text-slate-900">Color</h3>
            <p v-if="!availableColors.length" class="text-xs text-slate-500">No colors recorded for these products.</p>
            <div v-else class="flex flex-wrap gap-2">
              <button v-for="color in availableColors" :key="color.name" type="button"
                class="flex items-center gap-2 rounded-full border px-3 py-2 text-xs"
                :class="selectedColor === color.name ? 'border-orange-500 text-orange-700' : 'border-slate-200'"
                @click="selectedColor = selectedColor === color.name ? '' : color.name">
                <span class="h-4 w-4 rounded-full border border-slate-300" :style="{ backgroundColor: color.hex }" />{{ color.name }}
              </button>
            </div>
          </section>
          <section class="space-y-3 border-t border-slate-200 pt-4">
            <h3 class="font-semibold text-slate-900">Recommendations</h3>
            <div class="grid grid-cols-2 gap-2">
              <div><label for="mobile-budget-min" class="mb-1 block text-xs">Min Price</label>
                <InputNumber id="mobile-budget-min" v-model="dss.budgetMin" size="small" fluid @blur="syncBudgetInputs" /></div>
              <div><label for="mobile-budget-max" class="mb-1 block text-xs">Max Price</label>
                <InputNumber id="mobile-budget-max" v-model="dss.budgetMax" size="small" fluid @blur="syncBudgetInputs" /></div>
            </div>
            <Slider v-model="dss.budgetRange" range class="mx-1" :min="0" :max="300000" @slideend="syncBudgetRange" />
            <div class="grid grid-cols-3 gap-2">
              <InputNumber v-model="dss.lengthCm" :min="0" size="small" fluid placeholder="L cm" />
              <InputNumber v-model="dss.widthCm" :min="0" size="small" fluid placeholder="W cm" />
              <InputNumber v-model="dss.heightCm" :min="0" size="small" fluid placeholder="H cm" />
            </div>
          </section>
        </div>
        <template #footer>
          <Button label="Show products" size="small" severity="warn" @click="mobileFiltersVisible = false" />
        </template>
      </Dialog>
  
      <main class="lg:col-span-9">
        <section v-if="activeCategoryId" class="mb-8">
          <div class="mb-4">
            <h2 class="mt-1 text-xl font-bold text-slate-900">Top shops for {{ activeCategoryName || 'this category' }}</h2>
            <p class="mt-1 text-xs text-slate-500">Ranked by sales in the last 30 days and available products in this category.</p>
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
              <p class="mt-1 text-center text-[10px] text-slate-400">{{ store.matching_products_count }} products · {{ store.sales_last_30_days }} sold in 30 days</p>
            </button>
          </div>
          <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-white p-5 text-center text-xs text-slate-500">No matching shops are currently available.</div>
        </section>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 md:gap-4 mb-5 md:mb-8">
          <div>
            <h2 class="text-xl md:text-2xl font-bold text-slate-900">{{ activeCategoryId ? `${activeCategoryName || 'Category'} products` : pageHeading }}</h2>
            <p class="text-slate-500 text-sm">{{ activeCategoryId ? `${totalProducts} available items` : pageDescription }}</p>
          </div>
        </div>

        <div v-if="activeFilters.length" class="mb-4 flex flex-wrap gap-2">
          <Chip v-for="filterItem in activeFilters" :key="filterItem.key" :label="filterItem.label" removable
            @remove="clearActiveFilter(filterItem.key)" />
        </div>
  
        <div v-if="loading" class="grid grid-cols-1 gap-3 lg:grid-cols-2 xl:grid-cols-3">
          <div v-for="idx in 6" :key="idx" class="flex gap-4 rounded-2xl border border-slate-200 bg-white p-3 lg:block">
            <Skeleton width="96px" height="96px" borderRadius="0.75rem" class="lg:!h-52 lg:!w-full" />
            <div class="min-w-0 flex-1 space-y-3 py-2">
              <Skeleton width="65%" height="1.2rem" />
              <Skeleton width="40%" height="1rem" />
              <Skeleton width="25%" height="1rem" />
            </div>
          </div>
        </div>
  
        <div v-if="dssResults.length" class="mb-8">
          <h3 class="mb-3 text-lg font-semibold text-slate-900">Result Recommended For You</h3>
          <div class="grid grid-cols-1 gap-3 lg:grid-cols-2 xl:grid-cols-3">
            <article v-for="item in dssResults" :key="`dss-${item.id}`" class="relative flex flex-col rounded-2xl border border-slate-200 bg-white p-3 transition-all duration-300 sm:p-4 lg:hover:z-10 lg:hover:scale-[1.03] lg:hover:border-orange-300 lg:hover:shadow-xl">
              <label class="mb-3 flex w-fit cursor-pointer items-center gap-2 text-xs text-slate-600">
                <Checkbox :modelValue="isCompared(item.id)" binary :disabled="comparisonFull && !isCompared(item.id)" @update:modelValue="toggleComparison(item)" /> Compare
              </label>
              <div class="grid grid-cols-[minmax(0,42%)_minmax(0,1fr)] gap-4 sm:grid-cols-[minmax(0,220px)_minmax(0,1fr)] sm:gap-6 lg:block lg:flex-1">
                <button type="button" class="aspect-square overflow-hidden rounded-xl bg-slate-50 lg:block lg:w-full" @click="goProduct(item.id)">
                  <img :src="normalizeImageUrl(item.image) || '/F.svg'" :alt="item.product_name" class="h-full w-full object-contain" @error="onImageError" />
                </button>
                <div class="flex min-w-0 flex-col items-start lg:mt-3">
                  <span class="text-[11px] font-semibold text-orange-600">{{ Math.round((item.score || 0) * 100) }}% match</span>
                  <button type="button" class="mt-1 text-left text-sm font-bold text-slate-900 hover:underline sm:text-lg" @click="goProduct(item.id)">{{ item.product_name }}</button>
                  <p v-if="plainDescription(item.description)" class="mt-1 line-clamp-3 text-xs leading-5 text-slate-600 sm:text-sm">{{ plainDescription(item.description) }}</p>
                  <p class="mt-3 text-lg font-bold text-slate-900 sm:text-xl">₱{{ formatMoney(item.price) }}</p>
                  <div class="mt-2 flex items-center gap-2">
                    <Button icon="pi pi-shopping-cart" severity="warn" rounded class="!h-9 !w-9" :loading="addingCartProductId === Number(item.id)" aria-label="Add to cart" @click="addListingProductToCart(item)" />
                    <Button :icon="isFavorite(item.id) ? 'pi pi-heart-fill' : 'pi pi-heart'" text rounded :severity="isFavorite(item.id) ? 'danger' : 'secondary'" class="!h-9 !w-9" :aria-label="isFavorite(item.id) ? 'Remove from favorites' : 'Add to favorites'" @click="toggleProductFavorite(item.id)" />
                  </div>
                </div>
              </div>
              <p v-if="item.quantity_available != null" class="mt-3 flex items-center gap-2 text-xs text-slate-600"><span class="h-2.5 w-2.5 rounded-full" :class="Number(item.quantity_available) > 0 ? 'bg-green-600' : 'bg-rose-500'" />{{ Number(item.quantity_available) > 0 ? 'Available for delivery' : 'Out of stock' }}</p>
            </article>
          </div>
        </div>
  
        <div v-else-if="filteredProducts.length" class="grid grid-cols-1 gap-3 lg:grid-cols-2 xl:grid-cols-3">
          <article v-for="product in filteredProducts" :key="product.id" class="relative flex flex-col rounded-2xl border border-slate-200 bg-white p-3 transition-all duration-300 sm:p-4 lg:hover:z-10 lg:hover:scale-[1.03] lg:hover:border-orange-300 lg:hover:shadow-xl">
            <label class="mb-3 flex w-fit cursor-pointer items-center gap-2 text-xs text-slate-600">
              <Checkbox :modelValue="isCompared(product.id)" binary :disabled="comparisonFull && !isCompared(product.id)" @update:modelValue="toggleComparison(product)" /> Compare
            </label>
            <div class="grid grid-cols-[minmax(0,42%)_minmax(0,1fr)] gap-4 sm:grid-cols-[minmax(0,220px)_minmax(0,1fr)] sm:gap-6 lg:block lg:flex-1">
              <button type="button" class="relative aspect-square overflow-hidden rounded-xl bg-slate-50 lg:block lg:w-full" @click="goProduct(product.id)">
                <img :src="normalizeImageUrl(product.image) || '/F.svg'" :alt="product.product_name" class="h-full w-full object-contain" @error="onImageError" />
                <Badge v-if="product.discounted_price && Number(product.discounted_price) < Number(product.base_price)" :value="`${Math.round((1 - Number(product.discounted_price) / Number(product.base_price)) * 100)}% OFF`" severity="danger" class="!absolute !left-2 !top-2 !text-[10px]" />
              </button>
              <div class="flex min-w-0 flex-col items-start lg:mt-3">
                <span v-if="product.is_new_arrival || pageMode === 'new'" class="text-[11px] font-semibold text-orange-600">New</span>
                <button type="button" class="text-left text-sm font-bold text-slate-900 hover:underline sm:text-lg" @click="goProduct(product.id)">{{ product.product_name }}</button>
                <p v-if="plainDescription(product.description)" class="mt-1 line-clamp-3 text-xs leading-5 text-slate-600 sm:text-sm">{{ plainDescription(product.description) }}</p>
                <div class="mt-3 flex flex-wrap items-baseline gap-2">
                  <span class="text-lg font-bold text-slate-900 sm:text-xl">₱{{ formatMoney(product.price ?? product.discounted_price ?? product.base_price) }}</span>
                  <span v-if="product.discounted_price && Number(product.discounted_price) < Number(product.base_price)" class="text-xs text-slate-400 line-through">₱{{ formatMoney(product.base_price) }}</span>
                </div>
                <div class="mt-2 flex items-center gap-2">
                  <Button icon="pi pi-shopping-cart" severity="warn" rounded class="!h-9 !w-9" :loading="addingCartProductId === Number(product.id)" aria-label="Add to cart" @click="addListingProductToCart(product)" />
                  <Button :icon="isFavorite(product.id) ? 'pi pi-heart-fill' : 'pi pi-heart'" text rounded :severity="isFavorite(product.id) ? 'danger' : 'secondary'" class="!h-9 !w-9" :aria-label="isFavorite(product.id) ? 'Remove from favorites' : 'Add to favorites'" @click="toggleProductFavorite(product.id)" />
                </div>
              </div>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-600">
              <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full" :class="Number(product.quantity_available) > 0 ? 'bg-green-600' : 'bg-rose-500'" />{{ Number(product.quantity_available) > 0 ? 'Available for delivery' : 'Out of stock' }}</span>
              <span v-if="Number(product.rating_count || 0) > 0"><i class="pi pi-star-fill mr-1 text-amber-400" />{{ Number(product.rating_avg || 0).toFixed(1) }} ({{ product.rating_count }})</span>
            </div>
          </article>
        </div>
  
        <div v-else-if="!loading" class="text-center py-20 bg-slate-50 rounded-3xl border-2 border-dashed border-slate-200">
          <i class="pi pi-search text-4xl text-slate-300 mb-4" />
          <p class="text-slate-500 font-medium">We couldn't find any matches for your search.</p>
          <Button label="Clear Filters" link @click="clearListFilters" />
        </div>

        <Paginator
          v-if="(activeCategoryId || route.query.room) && totalProducts > perPage"
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
    <div v-if="comparisonProducts.length" class="fixed inset-x-0 bottom-4 z-40 px-3">
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
import Select from 'primevue/select'
import Accordion from 'primevue/accordion'
import AccordionPanel from 'primevue/accordionpanel'
import AccordionHeader from 'primevue/accordionheader'
import AccordionContent from 'primevue/accordioncontent'
import Chip from 'primevue/chip'
import Dialog from 'primevue/dialog'
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
const pageMode = computed(() => route.name === 'ecommerce.new-products' ? 'new' : route.name === 'ecommerce.trending.view' ? 'trending' : 'shop')
const pageHeading = computed(() => route.query.room
  ? `Shop ${String(route.query.room).replace(/-/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase())}`
  : pageMode.value === 'new' ? 'New Products This Month' : pageMode.value === 'trending' ? 'Trending Products' : 'Featured Products')
const pageDescription = computed(() => route.query.room
  ? `${totalProducts.value} available products`
  : pageMode.value === 'new' ? `${filteredProducts.value.length} products added this month` : pageMode.value === 'trending' ? 'Popular products ranked by monthly sales' : `Showing ${filteredProducts.value.length} items`)
const search = ref(String(route.query.search || ''))
const selectedCategory = ref<string>('all')
const selectedColor = ref('')
const mobileFiltersVisible = ref(false)
const sort = ref(route.name === 'ecommerce.new-products' ? 'latest' : 'match')
const dssLoading = ref(false)
const dssResults = ref<any[]>([])
const favoriteProductIds = ref<number[]>([])
const favoriteRequests = ref<number[]>([])
const addingCartProductId = ref<number | null>(null)
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
  { label: 'Best Match', value: 'match' },
  { label: 'Price: Low to High', value: 'price_asc' },
  { label: 'Price: High to Low', value: 'price_desc' },
  { label: 'Newest', value: 'latest' },
  { label: 'Customer Rating', value: 'rating' },
  { label: 'Most Popular', value: 'popular' },
]

const availableColors = computed(() => {
  const colors = new Map<string, string>()
  for (const product of products.value) {
    for (const color of product.colors || []) {
      if (color.name && color.hex) colors.set(color.name, color.hex)
    }
  }
  return [...colors].map(([name, hex]) => ({ name, hex })).sort((a, b) => a.name.localeCompare(b.name))
})
const activeFilters = computed(() => [
  ...(search.value.trim() ? [{ key: 'search', label: `Search: ${search.value.trim()}` }] : []),
  ...(selectedColor.value ? [{ key: 'color', label: `Color: ${selectedColor.value}` }] : []),
  ...(sort.value !== 'match' ? [{ key: 'sort', label: sortOptions.find(option => option.value === sort.value)?.label || 'Sort' }] : []),
  ...(route.query.category_id ? [{ key: 'category', label: activeCategoryName.value || 'Category' }] : []),
  ...(route.query.room ? [{ key: 'room', label: String(route.query.room) }] : []),
])
function clearActiveFilter(key: string) {
  if (key === 'search') {
    search.value = ''
    const query = { ...route.query }
    delete query.search
    router.replace({ query })
  }
  if (key === 'color') selectedColor.value = ''
  if (key === 'sort') sort.value = 'match'
  if (key === 'category' || key === 'room') {
    const query = { ...route.query }
    delete query[key === 'category' ? 'category_id' : 'room']
    router.replace({ name: 'ecommerce.products', query })
  }
}

function clearListFilters() {
  search.value = ''
  selectedColor.value = ''
  sort.value = 'match'
  const query = { ...route.query }
  delete query.search
  router.replace({ query })
}

function normalizeImageUrl(raw: string) {
  if (!raw) return ''
  if (raw.startsWith('http://') || raw.startsWith('https://') || raw.startsWith('data:')) return raw
  if (raw.startsWith('/storage/')) return raw
  if (raw.startsWith('storage/')) return `/${raw}`
  return `/storage/${raw.replace(/^\//, '')}`
}

function plainDescription(value: unknown): string {
  return String(value || '').replace(/<[^>]*>/g, ' ').replace(/&nbsp;/gi, ' ').replace(/\s+/g, ' ').trim()
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

async function addListingProductToCart(product: any) {
  const productId = Number(product.id)
  if (!productId || addingCartProductId.value !== null) return
  if (!authStore.isAuthenticated) {
    router.push({ name: 'customer.login', query: { redirect: route.fullPath || '/shop' } })
    return
  }

  addingCartProductId.value = productId
  try {
    const response = await ecommerceService.getProduct(productId)
    const details = response.data?.data
    if (Array.isArray(details?.variations) && details.variations.length) {
      goProduct(productId)
      return
    }
    await ecommerceService.addToCart({ product_id: productId, quantity: 1, store_id: Number(details?.store_id || product.store_id) || undefined })
    window.dispatchEvent(new Event('ecommerce-cart-updated'))
    toast.add({ severity: 'success', summary: 'Added to cart', detail: product.product_name, life: 2200 })
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Cart', detail: error?.response?.data?.message || 'Unable to add this product to your cart.' })
  } finally {
    addingCartProductId.value = null
  }
}

function toggleComparison(product: any) {
  if (isCompared(product.id)) {
    removeComparison(product.id)
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
  else if (sort.value === 'rating') rows.sort((a, b) => Number(b.rating_avg || 0) - Number(a.rating_avg || 0))
  else rows.sort((a, b) => Number(b.sales_last_30_days || 0) - Number(a.sales_last_30_days || 0))

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
      per_page: activeCategoryId.value || route.query.room ? perPage : 80,
      page: activeCategoryId.value || route.query.room ? currentPage.value : 1,
      search: search.value.trim() || undefined,
      sort: sort.value,
      color: selectedColor.value || undefined,
      category_id: route.query.category_id || undefined,
      room: route.query.room || undefined,
      discounted_only: route.query.deals ? true : undefined,
      new_this_month: pageMode.value === 'new' ? true : undefined,
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
  sort.value = 'match'
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

watch(() => route.query.room, () => {
  currentPage.value = 1
  loadProducts()
})

watch(() => route.query.deals, () => {
  currentPage.value = 1
  loadProducts()
})

watch(() => route.name, () => {
  currentPage.value = 1
  selectedCategory.value = 'all'
  sort.value = pageMode.value === 'new' ? 'latest' : 'match'
  loadProducts()
})
watch([sort, selectedColor], () => {
  currentPage.value = 1
  dssResults.value = []
  loadProducts()
})
</script>
