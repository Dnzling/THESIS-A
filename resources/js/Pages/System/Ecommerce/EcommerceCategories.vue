<template>
  <div class="space-y-5">
    <header class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
      <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-600">Browse furniture</p>
      <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Shop by category</h1>
      <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Explore categories with furniture currently available from active stores.</p>
    </header>

    <section aria-label="Available furniture categories">
      <div v-if="loading" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
        <Skeleton v-for="index in 10" :key="index" class="aspect-square" borderRadius="1rem" />
      </div>

      <Message v-else-if="error" severity="error" :closable="false">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <span>{{ error }}</span>
          <Button label="Retry" icon="pi pi-refresh" size="small" outlined @click="loadCategories" />
        </div>
      </Message>

      <div v-else-if="categories.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
        <button v-for="category in categories" :key="category.id" type="button"
          class="group relative aspect-square overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 text-left shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-orange-300 hover:shadow-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2"
          @click="openCategory(category.id)">
          <img v-if="category.icon_url" :src="normalizeImageUrl(category.icon_url)" :alt="category.category_name"
            class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105" @error="hideBrokenImage" />
          <div v-else class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-orange-50 to-amber-100 text-orange-300">
            <i class="pi pi-box text-4xl sm:text-5xl" />
          </div>
          <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-900/15 to-transparent" />
          <span class="absolute inset-x-0 bottom-0 p-3 sm:p-4">
            <span class="block truncate text-sm font-semibold text-white sm:text-base">{{ category.category_name }}</span>
            <span class="mt-1 block text-[11px] text-white/75">{{ productCountLabel(category.available_products_count) }}</span>
          </span>
        </button>
      </div>

      <div v-else class="rounded-3xl border border-dashed border-slate-300 bg-white px-5 py-16 text-center">
        <i class="pi pi-box mb-3 text-4xl text-slate-300" />
        <h2 class="text-sm font-semibold text-slate-800">No categories available</h2>
        <p class="mt-1 text-xs text-slate-500">Categories will appear here when they have items available for sale.</p>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'
import ecommerceService from '@/services/ecommerce.service'
import Button from 'primevue/button'
import Message from 'primevue/message'
import Skeleton from 'primevue/skeleton'

defineOptions({ layout: EcommerceMobileWrapper })

interface EcommerceCategory {
  id: number
  category_name: string
  available_products_count: number
  icon_url: string | null
}

const router = useRouter()
const categories = ref<EcommerceCategory[]>([])
const loading = ref(true)
const error = ref('')

function normalizeImageUrl(raw: string | null) {
  if (!raw) return ''
  if (/^(https?:|data:|\/storage\/)/.test(raw)) return raw
  return raw.startsWith('storage/') ? `/${raw}` : `/storage/${raw.replace(/^\//, '')}`
}

function hideBrokenImage(event: Event) {
  const image = event.target as HTMLImageElement
  image.style.display = 'none'
}

function productCountLabel(value: number) {
  const count = Number(value || 0)
  return `${count} ${count === 1 ? 'item' : 'items'} available`
}

function openCategory(categoryId: number) {
  router.push({ name: 'ecommerce.products', query: { category_id: categoryId } })
}

async function loadCategories() {
  loading.value = true
  error.value = ''

  try {
    const response = await ecommerceService.getCategories()
    const rows = response.data?.data || []
    categories.value = Array.isArray(rows) ? rows : []
  } catch (requestError: any) {
    categories.value = []
    error.value = requestError?.response?.data?.message || 'Could not load categories.'
  } finally {
    loading.value = false
  }
}

onMounted(loadCategories)
</script>
