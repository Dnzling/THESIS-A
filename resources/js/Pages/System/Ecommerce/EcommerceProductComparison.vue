<template>
  <section class="mx-auto max-w-7xl px-1 py-4 sm:px-3 md:py-8">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-orange-600">Side-by-side guide</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-900 md:text-3xl">Product comparison</h1>
        <p class="mt-1 text-sm text-slate-500">Compare price, ratings, size, stock, and product options. Maximum of 5 products.</p>
      </div>
      <div class="flex gap-2">
        <Button v-if="comparisonProducts.length" label="Clear all" icon="pi pi-trash" severity="secondary" outlined @click="clearAll" />
        <Button label="Add products" icon="pi pi-plus" severity="warn" @click="router.push({ name: 'ecommerce.products' })" />
      </div>
    </div>

    <div v-if="loading" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="flex gap-4 overflow-hidden">
        <Skeleton v-for="index in Math.max(comparisonProducts.length, 2)" :key="index" width="240px" height="520px" borderRadius="1rem" />
      </div>
    </div>

    <div v-else-if="details.length >= 2" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] table-fixed border-collapse">
          <thead>
            <tr>
              <th class="sticky left-0 z-20 w-44 border-b border-r border-slate-200 bg-slate-50 p-4 text-left align-bottom text-xs font-bold uppercase tracking-wider text-slate-500">Product</th>
              <th v-for="product in details" :key="product.id" class="min-w-56 border-b border-r border-slate-200 p-4 text-left align-top last:border-r-0">
                <div class="relative">
                  <button type="button" class="absolute right-0 top-0 z-10 grid h-8 w-8 place-items-center rounded-full bg-white text-slate-500 shadow hover:bg-rose-50 hover:text-rose-600"
                    aria-label="Remove from comparison" @click="removeProduct(product.id)"><i class="pi pi-times" /></button>
                  <button type="button" class="block w-full text-left" @click="viewProduct(product.id)">
                    <div class="aspect-square overflow-hidden rounded-xl bg-slate-100">
                      <img :src="normalizeImageUrl(product.image) || '/F.svg'" :alt="product.product_name" class="h-full w-full object-cover transition hover:scale-105" @error="onImageError" />
                    </div>
                    <p class="mt-3 text-[10px] font-bold uppercase tracking-wider text-orange-600">{{ product.category || 'Furniture' }}</p>
                    <h2 class="mt-1 line-clamp-2 text-base font-bold text-slate-900">{{ product.product_name }}</h2>
                    <p class="mt-2 text-lg font-bold text-slate-900">₱{{ money(product.price) }}</p>
                  </button>
                  <Button label="View product" size="small" severity="warn" class="mt-3 w-full" @click="viewProduct(product.id)" />
                </div>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in comparisonRows" :key="row.label" class="even:bg-slate-50/70">
              <th class="sticky left-0 z-10 border-b border-r border-slate-200 bg-inherit p-4 text-left text-sm font-semibold text-slate-700">{{ row.label }}</th>
              <td v-for="product in details" :key="`${row.label}-${product.id}`" class="border-b border-r border-slate-200 p-4 text-sm text-slate-700 last:border-r-0">
                <template v-if="row.type === 'rating'">
                  <span class="font-semibold"><i class="pi pi-star-fill mr-1 text-amber-400" />{{ valueFor(product, row) }}</span>
                  <span class="ml-1 text-xs text-slate-400">({{ product.reviews_summary?.total_reviews || product.rating_count || 0 }})</span>
                </template>
                <span v-else :class="row.type === 'stock' && Number(product.quantity_available || 0) > 0 ? 'font-semibold text-emerald-600' : ''">{{ valueFor(product, row) }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-else class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm">
      <span class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-orange-50 text-orange-500"><i class="pi pi-clone text-3xl" /></span>
      <h2 class="mt-5 text-xl font-bold text-slate-900">Select at least 2 products</h2>
      <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">Choose 2 to 5 products from the shop to see their details side by side.</p>
      <Button label="Choose products" icon="pi pi-plus" severity="warn" class="mt-5" @click="router.push({ name: 'ecommerce.products' })" />
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import ecommerceService from '@/services/ecommerce.service'
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'
import { useProductComparison } from '@/composables/useProductComparison'

defineOptions({ layout: EcommerceMobileWrapper })

const router = useRouter()
const toast = useToast()
const loading = ref(false)
const details = ref<any[]>([])
const { products: comparisonProducts, remove, clear } = useProductComparison()

const comparisonRows = [
  { label: 'Price', key: 'price', type: 'money' },
  { label: 'Rating', key: 'rating', type: 'rating' },
  { label: 'Availability', key: 'quantity_available', type: 'stock' },
  { label: 'Brand', key: 'brand' },
  { label: 'Collection', key: 'collection_name' },
  { label: 'Length', key: 'length_cm', type: 'dimension' },
  { label: 'Width', key: 'width_cm', type: 'dimension' },
  { label: 'Height', key: 'height_cm', type: 'dimension' },
  { label: 'Weight', key: 'weight_kg', type: 'weight' },
  { label: 'Assembly', key: 'assembly_required', type: 'boolean' },
  { label: 'Available variants', key: 'variations', type: 'variations' },
]

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

function money(value: unknown) {
  return Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function valueFor(product: any, row: any) {
  const dimensions = product.dimensions || {}
  const value = row.key in dimensions ? dimensions[row.key] : product[row.key]
  if (row.type === 'money') return `₱${money(value)}`
  if (row.type === 'rating') return Number(product.reviews_summary?.average_rating ?? product.rating_avg ?? 0).toFixed(1)
  if (row.type === 'stock') return Number(value || 0) > 0 ? `${Number(value)} in stock` : 'Out of stock'
  if (row.type === 'dimension') return value == null ? 'Not specified' : `${Number(value)} cm`
  if (row.type === 'weight') return value == null ? 'Not specified' : `${Number(value)} kg`
  if (row.type === 'boolean') return value ? 'Required' : 'Not required'
  if (row.type === 'variations') return Array.isArray(value) && value.length ? `${value.length} options` : 'No variants'
  return value || 'Not specified'
}

function viewProduct(id: number) {
  router.push({ name: 'ecommerce.product', params: { id } })
}

function removeProduct(id: number) {
  const product = details.value.find((item) => Number(item.id) === Number(id))
  remove(id)
  details.value = details.value.filter((item) => Number(item.id) !== Number(id))
  toast.add({ severity: 'info', summary: 'Removed from comparison', detail: `${product?.product_name || 'Product'} was removed.`, life: 2200 })
}

function clearAll() {
  clear()
  details.value = []
  toast.add({ severity: 'info', summary: 'Comparison cleared', detail: 'All products were removed from comparison.', life: 2200 })
}

async function loadDetails() {
  if (!comparisonProducts.value.length) return
  loading.value = true
  const responses = await Promise.allSettled(comparisonProducts.value.map((product) => ecommerceService.getProduct(product.id)))
  details.value = responses.flatMap((result: any) => result.status === 'fulfilled' && result.value?.data?.data ? [result.value.data.data] : [])
  loading.value = false
}

onMounted(loadDetails)
</script>
