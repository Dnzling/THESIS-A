<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <section class="relative h-64 md:h-80 rounded-3xl overflow-hidden mb-12 shadow-lg">
      <img src="https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1900&q=80"
        alt="Furniture Banner" class="absolute inset-0 w-full h-full object-cover" />
      <div class="absolute inset-0 bg-gradient-to-r from-black/70 to-transparent flex items-center">
        <div class="pl-10 md:pl-16 text-white">
          <span
            class="inline-block px-3 py-1 rounded-full bg-sky-500/20 text-sky-300 text-xs font-bold uppercase tracking-widest mb-4 backdrop-blur-md">
            New Collection 2026
          </span>
          <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight">Premium Living</h1>
          <p class="mt-4 text-lg text-slate-200 max-w-md">Discover furniture that blends comfort with contemporary
            aesthetics.</p>
        </div>
      </div>
    </section>
  
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      <aside class="lg:col-span-3 space-y-8">
        <div>
          <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4">Categories</h3>
          <nav class="flex flex-col gap-1">
            <button @click="setCategory('all')"
              :class="[selectedCategory === 'all' ? 'bg-slate-900 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100']"
              class="flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 text-sm font-medium">
              <span>All Collection</span>
              <Badge :value="products.length" severity="secondary" />
            </button>
            <button v-for="category in categoryOptions" :key="category" @click="setCategory(category)"
              :class="[selectedCategory === category ? 'bg-slate-900 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100']"
              class="flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 text-sm font-medium">
              <span class="capitalize">{{ category }}</span>
              <Badge :value="categoryCount(category)" :severity="selectedCategory === category ? 'info' : 'secondary'" />
            </button>
          </nav>
        </div>
      </aside>
  
      <main class="lg:col-span-9">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
          <div>
            <h2 class="text-2xl font-bold text-slate-900">Featured Products</h2>
            <p class="text-slate-500 text-sm">Showing {{ filteredProducts.length }} items</p>
          </div>
  
          <div class="flex items-center gap-3">
            <IconField iconPosition="left">
              <InputIcon class="pi pi-search" />
              <InputText v-model="search" placeholder="Search furniture..."
                class="w-full md:w-64 !rounded-xl border-slate-200" />
            </IconField>
            <Select v-model="sort" :options="sortOptions" optionLabel="label" optionValue="value"
              class="w-48 !rounded-xl border-slate-200" />
          </div>
        </div>
  
        <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
          <div v-for="idx in 6" :key="idx" class="bg-white p-4 rounded-2xl border border-slate-100">
            <Skeleton height="200px" border-radius="1rem" class="mb-4" />
            <Skeleton width="60%" height="1.5rem" class="mb-2" />
            <Skeleton width="40%" height="1rem" />
          </div>
        </div>
  
        <div v-else-if="filteredProducts.length" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
          <div
            v-for="product in filteredProducts"
            :key="product.id"
            class="group relative cursor-pointer bg-white rounded-2xl border border-slate-100 p-3 transition-all duration-300 hover:shadow-2xl hover:-translate-y-1"
            @click="goProduct(product.id)"
          >
            <div class="relative overflow-hidden rounded-xl bg-slate-100 aspect-square mb-4">
              <img :src="product.image || '/F.svg'" :alt="product.product_name"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110" />
  
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

const router = useRouter()

const loading = ref(false)
const products = ref<any[]>([])
const search = ref('')
const selectedCategory = ref<string>('all')
const sort = ref<'popular' | 'latest' | 'price_asc' | 'price_desc'>('popular')

const sortOptions = [
  { label: 'Featured', value: 'popular' },
  { label: 'Latest', value: 'latest' },
  { label: 'Price: Low to High', value: 'price_asc' },
  { label: 'Price: High to Low', value: 'price_desc' },
]

const categoryOptions = computed(() => {
  const set = new Set<string>()
  for (const product of products.value) {
    const category = String(product.category || '').trim()
    if (category) set.add(category)
  }
  return Array.from(set).sort((a, b) => a.localeCompare(b))
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

function applyFilters() {
  // kept for UI parity
}

function goProduct(productId: number) {
  router.push({ name: 'ecommerce.product', params: { id: productId } })
}

async function loadProducts() {
  loading.value = true
  try {
    const response = await ecommerceService.getProducts({ per_page: 80 })
    products.value = response.data?.data?.data || response.data?.data || []
  } finally {
    loading.value = false
  }
}

onMounted(loadProducts)
</script>
