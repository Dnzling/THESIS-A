<template>
  <div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-6 py-4 md:py-8 space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Trending Products</h1>
        <p class="text-sm text-slate-600">DSS ranking based on product movement (sales velocity).</p>
      </div>
      <Button
        label="Back to Products"
        icon="pi pi-arrow-left"
        severity="secondary"
        outlined
        @click="goProducts"
      />
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3">
        <IconField iconPosition="left">
          <InputIcon class="pi pi-search" />
          <InputText
            v-model="search"
            placeholder="Search trending products..."
            class="w-full md:w-64 rounded-xl! border-slate-200"
            @keyup.enter="loadTrending"
          />
        </IconField>
        <Button label="Refresh" icon="pi pi-refresh" :loading="loading" @click="loadTrending" />
      </div>
    </div>

    <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-2 xl:grid-cols-4 gap-4 md:gap-6">
      <div v-for="idx in 8" :key="idx" class="bg-white p-4 rounded-2xl border border-slate-100">
        <Skeleton height="180px" border-radius="1rem" class="mb-4" />
        <Skeleton width="70%" height="1.2rem" class="mb-2" />
        <Skeleton width="50%" height="1rem" />
      </div>
    </div>

    <div v-else-if="items.length" class="grid grid-cols-2 sm:grid-cols-2 xl:grid-cols-4 gap-4 md:gap-6">
      <div
        v-for="(product, index) in items"
        :key="product.id"
        class="group relative cursor-pointer bg-white rounded-2xl border border-slate-100 p-3 transition-all duration-300 hover:shadow-2xl hover:-translate-y-1"
        @click="goProduct(product.id)"
      >
        <div class="relative overflow-hidden rounded-xl bg-slate-100 aspect-square mb-4">
          <img
            :src="normalizeImageUrl(product.image) || '/F.svg'"
            :alt="product.product_name"
            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
            @error="onImageError"
          />
          <div class="absolute left-2 top-2 rounded-full bg-orange-500/90 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-white">
            #{{ index + 1 }} Trending
          </div>
        </div>

        <div class="px-2 pb-2 space-y-1">
          <div class="text-[10px] font-bold uppercase tracking-widest text-orange-600">{{ product.category || 'Home' }}</div>
          <h3 class="font-semibold text-slate-800 truncate group-hover:text-orange-600 transition-colors">
            {{ product.product_name }}
          </h3>
          <div class="text-sm text-green-600 font-semibold">PHP {{ formatMoney(product.price) }}</div>
          <div class="text-xs text-slate-500">Movement Score: {{ Number(product.movement_score || 0).toFixed(2) }}</div>
          <div class="text-[11px] text-slate-400">
            7d: {{ product.movement?.sold_last_7_days || 0 }} | 30d: {{ product.movement?.sold_last_30_days || 0 }}
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-20 bg-slate-50 rounded-3xl border-2 border-dashed border-slate-200">
      <i class="pi pi-chart-line text-4xl text-slate-300 mb-4" />
      <p class="text-slate-500 font-medium">No trending products available right now.</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import ecommerceService from '@/services/ecommerce.service'
import InputText from 'primevue/inputtext'
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'

defineOptions({
  layout: EcommerceMobileWrapper,
})

const router = useRouter()
const loading = ref(false)
const search = ref('')
const items = ref<any[]>([])

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

function formatMoney(amount: number | string) {
  return Number(amount || 0).toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

async function loadTrending() {
  loading.value = true
  try {
    const response = await ecommerceService.getDssTrendingMovement({
      per_page: 24,
      search: search.value.trim() || undefined,
    })
    items.value = response?.data?.data?.data || []
  } finally {
    loading.value = false
  }
}

function goProduct(productId: number) {
  router.push({ name: 'ecommerce.product', params: { id: productId } })
}

function goProducts() {
  router.push({ name: 'ecommerce.products' })
}

onMounted(loadTrending)
</script>
