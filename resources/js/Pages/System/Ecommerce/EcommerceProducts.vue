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
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 md:gap-4 mb-5 md:mb-8">
          <div>
            <h2 class="text-xl md:text-2xl font-bold text-slate-900">Featured Products</h2>
            <p class="text-slate-500 text-sm">Showing {{ filteredProducts.length }} items</p>
          </div>
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
              <div class="relative overflow-hidden rounded-xl bg-slate-100 aspect-square mb-4">
                <img :src="normalizeImageUrl(item.image) || '/F.svg'" :alt="item.product_name"
                  class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                  @error="onImageError" />
                <div
                  class="absolute left-2 top-2 rounded-full bg-blue-600/90 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-white">
                  Match {{ Math.round((item.score || 0) * 100) }}%
                </div>
              </div>
  
              <div class="px-2 pb-2">
                <div class="mb-1 text-[10px] font-bold uppercase tracking-widest text-sky-600">{{ item.category || 'Home'
                  }}</div>
                <h3 class="font-semibold text-slate-800 truncate mb-2 group-hover:text-sky-600 transition-colors">
                  {{ item.product_name }}
                </h3>
                <div class="flex items-center justify-between">
                  <span class="text-md text-green-600 font-semibold">₱{{ formatMoney(item.price) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
  
        <div v-else-if="filteredProducts.length" class="grid grid-cols-2 sm:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6">
          <div v-for="product in filteredProducts" :key="product.id"
            class="group relative cursor-pointer bg-white rounded-2xl border border-slate-100 p-3 transition-all duration-300 hover:shadow-2xl hover:-translate-y-1"
            @click="goProduct(product.id)">
            <div class="relative overflow-hidden rounded-xl bg-slate-100 aspect-square mb-4">
              <img :src="normalizeImageUrl(product.image) || '/F.svg'" :alt="product.product_name"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                @error="onImageError" />
  
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
                <span class="text-md text-green-600 font-semibold">₱{{ formatMoney(product.price) }}</span>
                <span class="text-[10px] text-slate-400 font-medium">{{ product.rating_count || 0 }} sold</span>
              </div>
            </div>
          </div>
        </div>
  
        <div v-else class="text-center py-20 bg-slate-50 rounded-3xl border-2 border-dashed border-slate-200">
          <i class="pi pi-search text-4xl text-slate-300 mb-4" />
          <p class="text-slate-500 font-medium">We couldn't find any matches for your search.</p>
          <Button label="Clear Filters" link @click="search = ''; selectedCategory = 'all'" />
        </div>

      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import ecommerceService from '@/services/ecommerce.service'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Slider from 'primevue/slider'
import InputNumber from 'primevue/inputnumber'
import Carousel from '@/Components/Ecommerce/carousel.vue'
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'

defineOptions({
  layout: EcommerceMobileWrapper,
})

const router = useRouter()

const loading = ref(false)
const products = ref<any[]>([])
const search = ref('')
const selectedCategory = ref<string>('all')
const sort = ref<'popular' | 'latest' | 'price_asc' | 'price_desc'>('popular')
const dssLoading = ref(false)
const dssResults = ref<any[]>([])
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
  else rows.sort((a, b) => Number(b.rating_count || 0) - Number(a.rating_count || 0))

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

async function loadProducts() {
  loading.value = true
  try {
    const response = await ecommerceService.getActiveStockProducts({ per_page: 80 })
    products.value = response.data?.data?.data || response.data?.data || []
  } finally {
    loading.value = false
  }
}

onMounted(loadProducts)
</script>

