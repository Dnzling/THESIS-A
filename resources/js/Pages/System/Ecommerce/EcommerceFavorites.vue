<template>
  <section class="mx-auto max-w-7xl px-1 py-4 sm:px-3 md:py-8">
    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-orange-600">Saved for later</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-900 md:text-3xl">My Favorites</h1>
        <p class="mt-1 text-sm text-slate-500">{{ favoriteIds.length }} {{ favoriteIds.length === 1 ? 'product' : 'products' }} saved</p>
      </div>
      <Button label="Continue shopping" icon="pi pi-arrow-right" iconPos="right" severity="warn" outlined
        @click="router.push({ name: 'ecommerce.products' })" />
    </div>

    <div v-if="loading" class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <div v-for="index in 8" :key="index" class="rounded-2xl border border-slate-100 bg-white p-3">
        <Skeleton height="220px" borderRadius="0.8rem" />
        <Skeleton width="70%" height="1.2rem" class="mt-4" />
        <Skeleton width="45%" height="1rem" class="mt-2" />
      </div>
    </div>

    <div v-else-if="products.length" class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <article v-for="product in products" :key="product.id"
        class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:border-orange-200 hover:shadow-xl">
        <div class="relative aspect-square cursor-pointer overflow-hidden bg-slate-100" @click="viewProduct(product.id)">
          <img :src="normalizeImageUrl(product.image) || '/F.svg'" :alt="product.product_name"
            class="h-full w-full object-cover transition duration-500 group-hover:scale-105" @error="onImageError" />
          <Badge v-if="product.has_discount" :value="`${product.discount_percentage}% OFF`" severity="danger"
            class="!absolute !left-3 !top-3 !text-[10px]" />
          <button type="button"
            class="absolute bottom-3 right-3 grid h-11 w-11 place-items-center rounded-full border border-rose-200 bg-white/95 text-rose-500 shadow-md transition hover:scale-105 hover:bg-rose-50"
            :disabled="removingIds.includes(Number(product.id))" aria-label="Remove from favorites"
            @click.stop="removeFavorite(product)">
            <i class="pi pi-heart-fill text-lg" />
          </button>
        </div>
        <div class="p-4">
          <div class="mb-1 flex items-center justify-between gap-2">
            <span class="truncate text-[10px] font-bold uppercase tracking-wider text-orange-600">{{ product.category || 'Furniture' }}</span>
            <span class="shrink-0 text-xs text-slate-500"><i class="pi pi-star-fill mr-1 text-[10px] text-amber-400" />{{ Number(product.rating_avg || 0).toFixed(1) }}</span>
          </div>
          <button type="button" class="w-full truncate text-left font-semibold text-slate-900 hover:text-orange-600"
            @click="viewProduct(product.id)">{{ product.product_name }}</button>
          <div class="mt-2 flex flex-wrap items-baseline gap-2">
            <span class="font-bold" :class="product.has_discount ? 'text-rose-600' : 'text-slate-900'">₱{{ formatMoney(product.price ?? product.base_price) }}</span>
            <span v-if="product.has_discount" class="text-xs text-slate-400 line-through">₱{{ formatMoney(product.base_price) }}</span>
          </div>
          <Button label="View product" icon="pi pi-eye" size="small" severity="warn" class="mt-4 w-full"
            @click="viewProduct(product.id)" />
        </div>
      </article>
    </div>

    <div v-else class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm">
      <span class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-rose-50 text-rose-400">
        <i class="pi pi-heart text-3xl" />
      </span>
      <h2 class="mt-5 text-xl font-bold text-slate-900">No favorites yet</h2>
      <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">Tap the heart on a product to save it here and find it quickly later.</p>
      <Button label="Explore products" icon="pi pi-shopping-bag" severity="warn" class="mt-5"
        @click="router.push({ name: 'ecommerce.products' })" />
    </div>
  </section>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import ecommerceService from '@/services/ecommerce.service'
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'
import { showAlert } from '@/utils/swal'

defineOptions({ layout: EcommerceMobileWrapper })

const router = useRouter()
const toast = useToast()
const loading = ref(true)
const favoriteIds = ref<number[]>([])
const products = ref<any[]>([])
const removingIds = ref<number[]>([])

function normalizeImageUrl(raw: string) {
  if (!raw) return ''
  if (/^(https?:|data:)/.test(raw) || raw.startsWith('/storage/')) return raw
  if (raw.startsWith('storage/')) return `/${raw}`
  return `/storage/${raw.replace(/^\//, '')}`
}

function onImageError(event: Event) {
  const target = event.target as HTMLImageElement | null
  if (target) target.src = '/F.svg'
}

function formatMoney(value: unknown) {
  return Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function viewProduct(id: number) {
  router.push({ name: 'ecommerce.product', params: { id } })
}

async function loadFavorites() {
  loading.value = true
  try {
    const favoritesResponse = await ecommerceService.getFavorites()
    favoriteIds.value = (favoritesResponse.data?.data?.product_ids || []).map((id: any) => Number(id))
    if (!favoriteIds.value.length) {
      products.value = []
      return
    }
    const productResponses = await Promise.allSettled(favoriteIds.value.map((id) => ecommerceService.getProduct(id)))
    products.value = productResponses.flatMap((result: any) => {
      if (result.status !== 'fulfilled') return []
      const product = result.value?.data?.data
      return product ? [product] : []
    })
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Favorites', detail: error?.response?.data?.message || 'Unable to load your favorites.' })
  } finally {
    loading.value = false
  }
}

async function removeFavorite(product: any) {
  const id = Number(product.id)
  if (removingIds.value.includes(id)) return
  const previousIndex = products.value.findIndex((item) => Number(item.id) === id)
  removingIds.value.push(id)
  products.value = products.value.filter((item) => Number(item.id) !== id)
  favoriteIds.value = favoriteIds.value.filter((favoriteId) => favoriteId !== id)
  try {
    const response = await ecommerceService.toggleFavorite(id)
    if (response.data?.data?.is_favorite) {
      products.value.splice(Math.max(previousIndex, 0), 0, product)
      favoriteIds.value.push(id)
      return
    }
    toast.add({ severity: 'info', summary: 'Removed from your favorites', detail: `${product.product_name} was removed from My Favorites.`, life: 2600 })
    window.dispatchEvent(new Event('ecommerce-favorites-updated'))
  } catch (error: any) {
    products.value.splice(Math.max(previousIndex, 0), 0, product)
    favoriteIds.value.push(id)
    showAlert({ severity: 'error', summary: 'Favorites', detail: error?.response?.data?.message || 'Unable to remove this product.' })
  } finally {
    removingIds.value = removingIds.value.filter((productId) => productId !== id)
  }
}

onMounted(loadFavorites)
</script>
