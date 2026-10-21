<template>
  <div class="pb-10">
    <button class="mb-4 inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-950" @click="router.push({ name: 'ecommerce.stores' })"><i class="pi pi-arrow-left" /> All stores</button>
    <div v-if="loading" class="space-y-4"><Skeleton height="190px" borderRadius="1.5rem" /><Skeleton height="60px" /><div class="grid grid-cols-2 gap-4 md:grid-cols-4"><Skeleton v-for="i in 8" :key="i" height="280px" /></div></div>

    <template v-else-if="store">
      <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="px-4 py-6 sm:px-7">
          <div class="flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
            <div class="flex min-w-0 items-end gap-4">
              <img :src="imageUrl(store.store_logo) || '/F.svg'" class="h-24 w-24 shrink-0 rounded-3xl border border-slate-200 bg-white object-cover shadow-sm md:h-28 md:w-28" alt="Store logo" @error="imageError" />
              <div class="min-w-0 pb-1"><p class="mb-1 text-xs font-bold uppercase tracking-widest text-orange-600">Showcase</p><div class="flex items-center gap-2"><h1 class="truncate text-2xl font-black tracking-tight text-slate-950 md:text-3xl">{{ storeName }}</h1><i class="pi pi-verified text-orange-500" /></div><p class="mt-1 truncate text-sm text-slate-500"><i class="pi pi-map-marker mr-1" />{{ storeLocation }}</p></div>
            </div>
            <div class="flex flex-wrap gap-2"><Button label="Contact store" icon="pi pi-comments" severity="secondary" outlined @click="goChat" /><Button :label="store.is_following ? 'Following' : 'Follow store'" :icon="store.is_following ? 'pi pi-check' : 'pi pi-heart'" :severity="store.is_following ? 'secondary' : 'warn'" :outlined="store.is_following" :loading="followLoading" @click="toggleFollow" /></div>
          </div>
          <div class="mt-6 grid grid-cols-2 divide-x divide-slate-200 border-t border-slate-200 pt-5 sm:grid-cols-4">
            <div class="px-3 first:pl-0"><b class="block text-xl text-slate-950">{{ store.products_count || 0 }}</b><span class="text-xs text-slate-500">Products</span></div>
            <div class="px-3"><b class="block text-xl text-slate-950">{{ store.followers_count || 0 }}</b><span class="text-xs text-slate-500">Followers</span></div>
            <div class="px-3"><b class="block text-xl text-slate-950"><i class="pi pi-star-fill mr-1 text-sm text-amber-400" />{{ Number(store.rating_avg || 0).toFixed(1) }}</b><span class="text-xs text-slate-500">Store rating</span></div>
            <div class="px-3"><b class="block text-xl text-slate-950">{{ Number(store.badges?.response_rate || 0).toFixed(0) }}%</b><span class="text-xs text-slate-500">Response rate</span></div>
          </div>
        </div>
      </section>

      <nav class="mt-4 flex items-center overflow-x-auto border-b border-slate-300 bg-white px-2">
        <button v-for="tab in tabs" :key="tab" type="button" class="border-b-2 px-5 py-4 text-sm" :class="activeTab === tab ? 'border-slate-950 font-bold text-slate-950' : 'border-transparent font-medium text-slate-500 hover:text-slate-950'" @click="selectTab(tab)">{{ tab }}</button>
        <div class="ml-auto hidden gap-5 px-3 text-xs text-slate-500 lg:flex"><span><i class="pi pi-send mr-1 text-emerald-600" />Ships in {{ Number(store.badges?.avg_shipping_time_hours || 0).toFixed(0) }}h avg.</span><span><i class="pi pi-shield mr-1 text-emerald-600" />Verified seller</span></div>
      </nav>

      <template v-if="activeTab === 'Shop'">
      <div class="mt-5 grid overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm sm:grid-cols-3">
        <div class="flex items-center gap-3 border-b border-slate-200 p-4 sm:border-b-0 sm:border-r"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-600"><i class="pi pi-shield" /></span><div><p class="text-sm font-bold text-slate-900">Verified seller</p><p class="text-xs text-slate-500">Store identity confirmed</p></div></div>
        <div class="flex items-center gap-3 border-b border-slate-200 p-4 sm:border-b-0 sm:border-r"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-orange-50 text-orange-600"><i class="pi pi-send" /></span><div><p class="text-sm font-bold text-slate-900">Reliable dispatch</p><p class="text-xs text-slate-500">{{ Number(store.badges?.avg_shipping_time_hours || 0).toFixed(0) }}h average processing</p></div></div>
        <div class="flex items-center gap-3 p-4"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-amber-50 text-amber-600"><i class="pi pi-star" /></span><div><p class="text-sm font-bold text-slate-900">Customer rated</p><p class="text-xs text-slate-500">{{ Number(store.rating_avg || 0).toFixed(1) }} average store rating</p></div></div>
      </div>

      <div class="mt-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div><h2 class="text-2xl font-black text-slate-950">All items</h2><p class="mt-1 text-sm text-slate-500">Browse products from {{ storeName }}</p></div>
        <div class="flex w-full flex-col gap-2 sm:flex-row lg:w-auto"><span class="relative flex-1 lg:w-80"><i class="pi pi-search absolute left-3 top-1/2 z-10 -translate-y-1/2 text-slate-400" /><InputText v-model="search" :placeholder="`Search in ${storeName}`" class="w-full !rounded-full !pl-10" @keyup.enter="reloadProducts" /></span><Select v-model="sort" :options="sortOptions" optionLabel="label" optionValue="value" class="w-full !rounded-full sm:w-48" @change="reloadProducts" /></div>
      </div>

      <div class="mt-5 flex gap-2 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <button class="shrink-0 rounded-full border px-4 py-2 text-sm font-semibold" :class="!categoryId ? selectedClass : idleClass" @click="chooseCategory(null)">All products</button>
        <button v-for="category in store.categories || []" :key="category.id" class="shrink-0 rounded-full border px-4 py-2 text-sm font-semibold" :class="categoryId === category.id ? selectedClass : idleClass" @click="chooseCategory(category.id)">{{ category.name }}</button>
      </div>

      <div v-if="productsLoading && !products.length" class="mt-6 grid grid-cols-2 gap-5 md:grid-cols-3 xl:grid-cols-4"><Skeleton v-for="i in 8" :key="i" height="300px" borderRadius="1rem" /></div>
      <div v-else-if="!products.length" class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-white py-16 text-center text-sm text-slate-500">No products match your search.</div>
      <div v-else class="mt-6 grid grid-cols-2 gap-x-4 gap-y-9 md:grid-cols-3 xl:grid-cols-4">
        <article v-for="product in products" :key="product.id" class="group cursor-pointer" @click="goProduct(product.id)">
          <div class="relative aspect-square overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition group-hover:-translate-y-0.5 group-hover:shadow-xl"><img :src="imageUrl(product.image) || '/F.svg'" :alt="product.product_name" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" @error="imageError" /><span v-if="product.has_discount" class="absolute left-3 top-3 rounded bg-slate-950 px-2 py-1 text-[10px] font-bold text-white">{{ product.discount_percentage }}% OFF</span>
            <button class="absolute right-3 top-3 grid h-10 w-10 place-items-center rounded-full border border-white/70 bg-white/95 text-slate-800 shadow-md transition hover:scale-105 hover:text-rose-500" :class="isFavorite(product.id) ? '!text-rose-500' : ''" :aria-label="isFavorite(product.id) ? 'Remove from favorites' : 'Add to favorites'" @click.stop="toggleFavorite(product)"><i :class="isFavorite(product.id) ? 'pi pi-heart-fill' : 'pi pi-heart'" /></button>
            <label class="absolute bottom-3 left-3 flex cursor-pointer items-center gap-2 rounded-full bg-white/95 px-3 py-2 text-[11px] font-semibold text-slate-700 shadow-md" @click.stop><Checkbox :modelValue="isCompared(product.id)" binary :disabled="comparisonFull && !isCompared(product.id)" @update:modelValue="toggleCompare(product)" /> Compare</label>
          </div>
          <div class="pt-3"><p class="text-[10px] font-bold uppercase tracking-wider text-orange-600">{{ product.category || 'Furniture' }}</p><h3 class="mt-1 line-clamp-2 min-h-10 text-sm font-semibold text-slate-900 group-hover:underline">{{ product.product_name }}</h3><div class="mt-2 flex flex-wrap items-baseline gap-2"><b class="text-lg text-slate-950">₱{{ money(product.price ?? product.base_price) }}</b><span v-if="product.has_discount" class="text-xs text-slate-400 line-through">₱{{ money(product.base_price) }}</span></div><p class="mt-1 text-xs text-slate-500"><i class="pi pi-star-fill mr-1 text-amber-400" />{{ Number(product.rating_avg || 0).toFixed(1) }} <span class="text-slate-400">({{ product.rating_count || 0 }})</span></p></div>
        </article>
      </div>
      <div ref="loadTrigger" class="h-2" /><div v-if="moreLoading" class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-4"><Skeleton v-for="i in 4" :key="i" height="280px" /></div>

      </template>

      <section v-else-if="activeTab === 'Feedback'" class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <h2 class="text-xl font-bold text-slate-950">Customer feedback</h2>
        <p class="mt-1 text-sm text-slate-500">{{ store.rating_count || 0 }} reviews · {{ Number(store.rating_avg || 0).toFixed(1) }} average rating</p>
        <div v-if="reviewsLoading" class="mt-5 space-y-3"><Skeleton v-for="i in 4" :key="i" height="6rem" /></div>
        <div v-else-if="reviewsError" class="mt-5 text-sm text-red-600">{{ reviewsError }} <button class="font-semibold underline" @click="loadReviews">Retry</button></div>
        <div v-else-if="!reviews.length" class="mt-5 rounded-xl border border-dashed border-slate-200 p-10 text-center text-sm text-slate-500">No feedback yet.</div>
        <div v-else class="mt-5 space-y-3"><article v-for="review in reviews" :key="review.id" class="rounded-xl border border-slate-200 p-4"><div class="flex flex-wrap items-center justify-between gap-2"><p class="font-semibold text-slate-900">{{ review.customer_name }}</p><span class="text-sm font-semibold text-amber-600"><i class="pi pi-star-fill mr-1" />{{ review.rating }}/5</span></div><p class="mt-1 text-xs text-slate-500">{{ review.product_name }}</p><p class="mt-3 whitespace-pre-wrap text-sm text-slate-700">{{ review.review_text || 'No written comment.' }}</p></article></div>
        <div v-if="reviewsPage < reviewsLastPage" class="mt-5 text-center"><Button label="Load more feedback" outlined :loading="reviewsLoading" @click="loadReviews" /></div>
      </section>

      <section v-else class="mt-6 space-y-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7"><h2 class="text-xl font-bold text-slate-950">About {{ storeName }}</h2><div class="mt-5 grid gap-5 text-sm sm:grid-cols-2"><div><p class="text-slate-500">Store type</p><p class="mt-1 font-medium text-slate-900">{{ store.type ? String(store.type).replace(/_/g, ' ') : 'Not specified' }}</p></div><div><p class="text-slate-500">Status</p><p class="mt-1 font-medium capitalize text-slate-900">{{ store.status || 'Not specified' }}</p></div><div><p class="text-slate-500">Phone</p><p class="mt-1 font-medium text-slate-900">{{ store.contact_number || 'Not provided' }}</p></div><div><p class="text-slate-500">Email</p><p class="mt-1 font-medium text-slate-900">{{ store.email || 'Not provided' }}</p></div><div class="sm:col-span-2"><p class="text-slate-500">Address</p><p class="mt-1 font-medium text-slate-900">{{ fullAddress }}</p></div></div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7"><h2 class="text-xl font-bold text-slate-950">Branches</h2><div v-if="store.branches?.length" class="mt-4 grid gap-3 md:grid-cols-2"><div v-for="branch in store.branches" :key="branch.id" class="rounded-xl border border-slate-200 p-4"><p class="font-bold text-slate-900">{{ branch.name }} <span v-if="branch.is_main_branch" class="ml-1 rounded-full bg-orange-100 px-2 py-1 text-[10px] text-orange-700">Main</span></p><p class="mt-2 text-sm text-slate-600">{{ [branch.address, branch.city, branch.province].filter(Boolean).join(', ') || 'Address not provided' }}</p><p v-if="branch.contact_number" class="mt-1 text-sm text-slate-600">{{ branch.contact_number }}</p></div></div><p v-else class="mt-4 text-sm text-slate-500">No branch details available.</p></div>
      </section>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import { useToast } from 'primevue/usetoast'
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'
import ecommerceService from '@/services/ecommerce.service'
import { showAlert } from '@/utils/swal'
import { useAuthStore } from '@/stores/auth'
import { useProductComparison } from '@/composables/useProductComparison'

defineOptions({ layout: EcommerceMobileWrapper })
const route = useRoute(); const router = useRouter()
const toast = useToast(); const authStore = useAuthStore()
const { count: comparisonCount, isFull: comparisonFull, contains: isCompared, add: addCompare, remove: removeCompare } = useProductComparison()
const loading = ref(true); const followLoading = ref(false); const productsLoading = ref(false); const moreLoading = ref(false)
const store = ref<any>(null); const products = ref<any[]>([]); const search = ref(''); const categoryId = ref<number | null>(null)
const tabs = ['Shop', 'Feedback', 'About'] as const
type StoreTab = typeof tabs[number]
const activeTab = ref<StoreTab>('Shop')
const reviews = ref<any[]>([]); const reviewsLoading = ref(false); const reviewsError = ref(''); const reviewsPage = ref(0); const reviewsLastPage = ref(1)
const favoriteIds = ref<number[]>([]); const favoriteBusy = ref<number[]>([])
const sort = ref('popular'); const page = ref(1); const hasMore = ref(true); const loadTrigger = ref<HTMLElement | null>(null)
let observer: IntersectionObserver | null = null
const sortOptions = [{ label: 'Best match', value: 'popular' }, { label: 'Newest', value: 'latest' }, { label: 'Price: Low to High', value: 'price_asc' }, { label: 'Price: High to Low', value: 'price_desc' }]
const selectedClass = 'border-slate-950 bg-slate-950 text-white'; const idleClass = 'border-slate-300 bg-white text-slate-700 hover:border-slate-950'
const storeName = computed(() => String(store.value?.store_name || store.value?.name || 'Store'))
const storeLocation = computed(() => [store.value?.city, store.value?.address].filter(Boolean).join(' · ') || 'Location not specified')
const fullAddress = computed(() => [store.value?.address, store.value?.barangay, store.value?.city, store.value?.province].filter(Boolean).join(', ') || 'Address not provided')
function imageUrl(raw: string) { if (!raw) return ''; if (/^(https?:|data:)/.test(raw) || raw.startsWith('/storage/')) return raw; return raw.startsWith('storage/') ? `/${raw}` : `/storage/${raw.replace(/^\//, '')}` }
function imageError(e: Event) { const img = e.target as HTMLImageElement; if (img) img.src = '/F.svg' }
function money(value: unknown) { return Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }
function isFavorite(id: number) { return favoriteIds.value.includes(Number(id)) }
async function loadFavorites() { if (!authStore.isAuthenticated) return; try { const response = await ecommerceService.getFavorites(); favoriteIds.value = (response.data?.data?.product_ids || []).map(Number) } catch { favoriteIds.value = [] } }
async function toggleFavorite(product: any) { const id = Number(product.id); if (!authStore.isAuthenticated) { router.push({ name: 'customer.login', query: { redirect: route.fullPath } }); return } if (favoriteBusy.value.includes(id)) return; favoriteBusy.value.push(id); const previous = isFavorite(id); favoriteIds.value = previous ? favoriteIds.value.filter(x => x !== id) : [...favoriteIds.value, id]; try { const response = await ecommerceService.toggleFavorite(id); const saved = Boolean(response.data?.data?.is_favorite); favoriteIds.value = saved ? Array.from(new Set([...favoriteIds.value, id])) : favoriteIds.value.filter(x => x !== id); toast.add({ severity: saved ? 'success' : 'info', summary: saved ? 'Added to your favorites' : 'Removed from your favorites', detail: product.product_name, life: 2400 }); window.dispatchEvent(new Event('ecommerce-favorites-updated')) } catch { favoriteIds.value = previous ? [...favoriteIds.value, id] : favoriteIds.value.filter(x => x !== id) } finally { favoriteBusy.value = favoriteBusy.value.filter(x => x !== id) } }
function toggleCompare(product: any) { if (isCompared(product.id)) { removeCompare(product.id); return } const result = addCompare({ id: Number(product.id), product_name: product.product_name, image: product.image, price: product.price ?? product.base_price, category: product.category }); if (!result.added) toast.add({ severity: 'warn', summary: 'Comparison is full', detail: 'You can compare up to 5 products only.', life: 2600 }) }
function goProduct(id: number) { router.push({ name: 'ecommerce.product', params: { id } }) }
function goChat() { router.push({ name: 'ecommerce.chats', query: { store_id: String(store.value.id) } }) }
function selectTab(tab: StoreTab) { activeTab.value = tab; if (tab === 'Feedback' && !reviewsPage.value && !reviewsLoading.value) loadReviews() }
async function loadReviews() { if (reviewsLoading.value) return; reviewsLoading.value = true; reviewsError.value = ''; try { const response = await ecommerceService.getStoreReviews(route.params.storeId as string, { per_page: 10, page: reviewsPage.value + 1 }); const payload = response.data?.data || {}; reviews.value = [...reviews.value, ...(payload.data || [])]; reviewsPage.value = Number(payload.current_page || reviewsPage.value + 1); reviewsLastPage.value = Number(payload.last_page || reviewsPage.value) } catch { reviewsError.value = 'Could not load feedback.' } finally { reviewsLoading.value = false } }
function chooseCategory(id: number | null) { categoryId.value = id; reloadProducts() }
async function toggleFollow() { followLoading.value = true; try { if (store.value.is_following) { await ecommerceService.unfollowStore(store.value.id); store.value.is_following = false; store.value.followers_count = Math.max(0, Number(store.value.followers_count || 0) - 1) } else { await ecommerceService.followStore(store.value.id); store.value.is_following = true; store.value.followers_count = Number(store.value.followers_count || 0) + 1 } } catch (e: any) { showAlert({ severity: 'warn', summary: 'Follow store', detail: e?.response?.status === 401 ? 'Please log in first.' : 'Unable to update follow status.' }) } finally { followLoading.value = false } }
async function reloadProducts() { page.value = 1; hasMore.value = true; products.value = []; await loadProducts(true) }
async function loadProducts(first = false) { if ((!hasMore.value && !first) || productsLoading.value || moreLoading.value) return; first ? productsLoading.value = true : moreLoading.value = true; try { const response = await ecommerceService.getActiveStockProducts({ store_id: Number(route.params.storeId), search: search.value || undefined, sort: sort.value === 'popular' ? undefined : sort.value, category_id: categoryId.value || undefined, per_page: 12, page: page.value }); const paginated = response.data?.data || {}; const rows = paginated.data || []; products.value = page.value === 1 ? rows : [...products.value, ...rows]; const current = Number(paginated.current_page || page.value); hasMore.value = current < Number(paginated.last_page || current) && rows.length > 0; if (hasMore.value) page.value = current + 1 } finally { productsLoading.value = false; moreLoading.value = false } }
async function initialize() { loading.value = true; try { const response = await ecommerceService.getStore(route.params.storeId as string); store.value = response.data?.data; await Promise.all([reloadProducts(), loadFavorites()]) } catch { showAlert({ severity: 'error', summary: 'Store', detail: 'Failed to load store profile.' }); router.push({ name: 'ecommerce.stores' }) } finally { loading.value = false } await nextTick(); observer = new IntersectionObserver((entries) => { if (entries[0]?.isIntersecting && activeTab.value === 'Shop') loadProducts(false) }, { rootMargin: '300px' }); if (loadTrigger.value) observer.observe(loadTrigger.value) }
onMounted(initialize); onBeforeUnmount(() => observer?.disconnect())
watch(activeTab, async tab => { if (tab === 'Shop') { await nextTick(); if (loadTrigger.value) observer?.observe(loadTrigger.value) } })
</script>
