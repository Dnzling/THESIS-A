<template>
    <div class="space-y-14 pb-8 sm:space-y-20">
        <section
            class="relative overflow-hidden rounded-[2rem] bg-[#f56617] px-6 py-8 text-white shadow-xl shadow-orange-200/60 sm:px-10 sm:py-12 lg:min-h-[430px] lg:px-14"
        >
            <div
                class="absolute -left-20 -top-24 h-72 w-72 rounded-full bg-amber-300/30 blur-3xl"
            ></div>
            <div
                class="absolute -bottom-28 right-1/3 h-72 w-72 rounded-full bg-red-700/20 blur-3xl"
            ></div>
            <div
                class="relative grid items-center gap-8 lg:grid-cols-[0.9fr_1.1fr]"
            >
                <div class="max-w-xl">
                    <h1
                        class="text-2xl font-black leading-[1.02] tracking-tight sm:text-5xl lg:text-6xl"
                    >
                        Find furniture you can explore before you buy.
                    </h1>
                    <p
                        class="mt-5 max-w-lg text-sm leading-6 text-orange-50 sm:text-base"
                    >
                        Furnisync brings trusted local furniture stores,
                        real-time availability, and interactive 3D previews into
                        one seamless shopping experience.
                    </p>
                    <Button
                        label="Shop Now"
                        severity="contrast"
                        rounded
                        class="mt-7 !px-6"
                        @click="goShop"
                    />
                </div>

                <div class="relative mx-auto w-full max-w-2xl">
                    <div
                        class="absolute inset-x-12 bottom-0 h-12 rounded-full blur-2xl"
                    ></div>
                    <div
                        class="relative overflow-hidden rounded-[1.75rem] borderp-2 shadow-2xl"
                    >
                        <Model3DPreview
                            model-url="/storage/platform/sofa.glb"
                            model-format="glb"
                            :camera-x="-18"
                            :camera-y="14"
                            :zoom="1.25"
                            allow-bottom-view
                            height="330px"
                        />
                        <div
                            class="pointer-events-none absolute bottom-5 left-5 rounded-full bg-white/90 px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur"
                        >
                            Drag to rotate in 3D
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section
            class="rounded-[2rem] border border-slate-200/80 bg-white p-5 shadow-sm sm:p-7 lg:p-8"
        >
            <div class="mb-6 flex items-end justify-between gap-4">
                <div>
                    <h2
                        class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl"
                    >
                        Shop by category
                    </h2>
                    <p class="mt-2 text-sm text-slate-500">
                        Explore available furniture from stores across every
                        collection.
                    </p>
                </div>
                <Button
                    label="View all"
                    severity="secondary"
                    text
                    size="small"
                    @click="router.push({ name: 'ecommerce.categories' })"
                />
            </div>

            <div
                v-if="loadingCategories"
                class="grid grid-flow-col grid-rows-2 auto-cols-[9rem] gap-3 overflow-x-auto pb-3 sm:auto-cols-[10rem] md:grid-rows-1"
            >
                <Skeleton
                    v-for="index in 8"
                    :key="index"
                    class="aspect-square w-full"
                    borderRadius="1rem"
                />
            </div>
            <div
                v-else-if="categories.length"
                class="grid grid-flow-col grid-rows-2 auto-cols-[9rem] gap-3 overflow-x-auto pb-3 sm:auto-cols-[10rem] md:grid-rows-1"
            >
                <button
                    v-for="category in categories"
                    :key="category.id"
                    type="button"
                    class="group relative aspect-square w-full overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 text-left shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-orange-300 hover:shadow-lg hover:shadow-orange-100"
                    @click="goCategory(category.id)"
                >
                    <img
                        v-if="category.icon_url"
                        :src="normalizeImageUrl(category.icon_url)"
                        :alt="category.category_name"
                        class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105"
                        @error="onImageError"
                    />
                    <div
                        v-else
                        class="absolute inset-0 bg-gradient-to-br from-orange-50 to-amber-100"
                    ></div>
                    <div
                        class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-900/15 to-transparent"
                    ></div>
                    <span class="absolute inset-x-0 bottom-0 p-3 text-white">
                        <span class="block truncate text-sm font-semibold">{{
                            category.category_name
                        }}</span>
                        <span class="mt-0.5 block text-[10px] text-white/75"
                            >{{
                                category.available_products_count
                            }}
                            available</span
                        >
                    </span>
                </button>
            </div>
            <div
                v-else
                class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500"
            >
                No available categories yet.
            </div>
        </section>

        <section
            v-if="recentlyViewed.length"
            class="rounded-[2rem] border border-slate-200/80 bg-white p-5 shadow-sm sm:p-7 lg:p-8"
        >
            <div class="mb-6 flex items-end justify-between gap-4">
                <div>
                    <p
                        class="text-xs font-bold uppercase tracking-[.18em] text-orange-600"
                    >
                        Pick up where you left off
                    </p>
                    <h2
                        class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl"
                    >
                        Recently viewed
                    </h2>
                    <p class="mt-2 text-sm text-slate-500">
                        Products you explored recently.
                    </p>
                </div>
                <Button
                    label="Clear"
                    severity="secondary"
                    text
                    size="small"
                    @click="clearRecentlyViewed"
                />
            </div>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                <article
                    v-for="product in recentlyViewed.slice(0, 5)"
                    :key="`recent-${product.id}`"
                    class="group w-full cursor-pointer overflow-hidden rounded-2xl border border-slate-100 bg-white p-3 shadow-sm transition hover:-translate-y-1 hover:shadow-xl"
                    @click="goProduct(product.id)"
                >
                    <div
                        class="aspect-square overflow-hidden rounded-xl bg-slate-100"
                    >
                        <img
                            :src="
                                normalizeImageUrl(product.image || '') ||
                                '/F.svg'
                            "
                            :alt="product.product_name"
                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                            @error="onImageError"
                        />
                    </div>
                    <div class="px-1 pt-3">
                        <p
                            class="truncate text-[10px] font-bold uppercase tracking-wider text-orange-600"
                        >
                            {{ product.category || 'Furniture' }}
                        </p>
                        <h3
                            class="mt-1 truncate text-sm font-semibold text-slate-900"
                        >
                            {{ product.product_name }}
                        </h3>
                        <p class="mt-2 text-sm font-bold text-slate-950">
                            ₱{{ formatMoney(product.price || 0) }}
                        </p>
                    </div>
                </article>
            </div>
        </section>

        <section
            class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm"
        >
            <div
                class="flex flex-col gap-3 px-5 pb-2 pt-6 sm:px-7 md:flex-row md:items-end md:justify-between"
            >
                <div>
                    <h2
                        class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl"
                    >
                        Shop by room
                    </h2>
                    <p class="mt-2 text-sm text-slate-500">
                        Start with a room, discover pieces that work beautifully
                        together.
                    </p>
                </div>
                <Button
                    label="Explore all products"
                    severity="secondary"
                    text
                    size="small"
                    @click="goShop"
                />
            </div>
            <div class="grid grid-flow-col grid-rows-2 auto-cols-[9rem] gap-3 overflow-x-auto px-5 pb-6 pt-3 sm:auto-cols-[10rem] sm:px-7 md:grid-rows-1">
                <button
                    v-for="idea in shoppingRooms"
                    :key="idea.id"
                    class="group relative aspect-square w-full overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 text-left shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-orange-300 hover:shadow-lg hover:shadow-orange-100"
                    @click="goRoom(idea)"
                >
                    <img v-if="idea.image_url"
                        :src="idea.image_url"
                        :alt="idea.name"
                        class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-110"
                    />
                    <div v-else class="absolute inset-0 bg-gradient-to-br from-amber-200 via-orange-100 to-slate-200" />
                    <div
                        class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-900/20 to-transparent"
                    />
                    <div class="absolute inset-x-0 bottom-0 p-5 text-white">
                        <h3 class="mt-3 text-sm font-bold">{{ idea.name }}</h3>
                    </div>
                </button>
            </div>
        </section>

        <section
            class="rounded-[2rem] border border-slate-200/80 bg-white p-5 shadow-sm sm:p-7 lg:p-8"
        >
            <div class="mb-6 flex items-end justify-between gap-4">
                <div>
                    <h2
                        class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl"
                    >
                        Flash deals
                    </h2>
                    <p class="mt-2 text-sm text-slate-500">
                        Save on selected in-stock furniture while offers are
                        available.
                    </p>
                </div>
                <Button
                    label="See deals"
                    severity="danger"
                    text
                    size="small"
                    @click="goDeals"
                />
            </div>

            <div
                v-if="loadingDeals"
                class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5"
            >
                <div
                    v-for="index in 5"
                    :key="index"
                    class="w-full rounded-2xl border border-slate-100 bg-white p-3"
                >
                    <Skeleton height="180px" borderRadius="0.75rem" />
                    <Skeleton class="mt-3" width="70%" height="16px" />
                </div>
            </div>
            <div
                v-else-if="flashDeals.length"
                class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5"
            >
                <article
                    v-for="product in flashDeals"
                    :key="`deal-${product.id}`"
                    class="group w-full cursor-pointer overflow-hidden rounded-2xl border border-rose-100 bg-white p-3 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl"
                    @click="goProduct(product.id)"
                >
                    <div
                        class="relative aspect-square overflow-hidden rounded-xl bg-slate-100"
                    >
                        <img
                            :src="normalizeImageUrl(product.image) || '/F.svg'"
                            :alt="product.product_name"
                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                            @error="onImageError"
                        />
                        <Badge
                            :value="`${product.discount_percentage}% OFF`"
                            severity="danger"
                            class="!absolute !left-2 !top-2 !text-[10px]"
                        />
                    </div>
                    <div class="px-1 pb-1 pt-3">
                        <h3
                            class="truncate text-sm font-semibold text-slate-900 group-hover:text-orange-600"
                        >
                            {{ product.product_name }}
                        </h3>
                        <div class="mt-2 flex flex-wrap items-baseline gap-2">
                            <span class="text-sm font-bold text-rose-600"
                                >&#8369;{{
                                    formatMoney(product.discounted_price)
                                }}</span
                            >
                            <span class="text-xs text-slate-400 line-through"
                                >&#8369;{{
                                    formatMoney(product.base_price)
                                }}</span
                            >
                        </div>
                    </div>
                </article>
            </div>
            <div
                v-else
                class="rounded-2xl border border-dashed border-slate-300 bg-white p-7 text-center text-sm text-slate-500"
            >
                No flash deals are available right now.
            </div>
        </section>

        <section
            class="rounded-[2rem] border border-slate-200/80 bg-white p-5 shadow-sm sm:p-7 lg:p-8"
        >
            <div class="mb-6 flex items-end justify-between gap-4">
                <div>
                    <h2
                        class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl"
                    >
                        Top-selling furniture
                    </h2>
                    <p class="mt-2 text-sm text-slate-500">
                        The 15 most purchased in-stock products this month.
                    </p>
                </div>
            </div>

            <div
                v-if="loadingProducts"
                class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5"
            >
                <div
                    v-for="index in 10"
                    :key="index"
                    class="w-full rounded-2xl border border-slate-100 bg-white p-3"
                >
                    <Skeleton height="180px" borderRadius="0.75rem" />
                    <Skeleton class="mt-3" width="45%" height="12px" />
                    <Skeleton class="mt-2" width="80%" height="16px" />
                </div>
            </div>
            <div
                v-else-if="topProducts.length"
                class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5"
            >
                <article
                    v-for="(product, index) in topProducts"
                    :key="product.id"
                    class="group w-full cursor-pointer overflow-hidden rounded-2xl border border-slate-100 bg-white p-3 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl"
                    @click="goProduct(product.id)"
                >
                    <div
                        class="relative aspect-square overflow-hidden rounded-xl bg-slate-100"
                    >
                        <img
                            :src="normalizeImageUrl(product.image) || '/F.svg'"
                            :alt="product.product_name"
                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                            @error="onImageError"
                        />
                        <Badge
                            :value="`TOP ${index + 1}`"
                            severity="warn"
                            class="!absolute !left-2 !top-2 !text-[10px]"
                        />
                        <Badge
                            v-if="product.has_discount"
                            :value="`${product.discount_percentage}% OFF`"
                            severity="danger"
                            class="!absolute !right-2 !top-2 !text-[10px]"
                        />
                    </div>
                    <div class="px-1 pb-1 pt-3">
                        <p class="text-[11px] font-semibold text-orange-600">
                            {{
                                Number(
                                    product.monthly_sales || 0
                                ).toLocaleString()
                            }}
                            sold this month
                        </p>
                        <h3
                            class="mt-1 truncate text-sm font-semibold text-slate-900 group-hover:text-orange-600"
                        >
                            {{ product.product_name }}
                        </h3>
                        <div
                            v-if="product.has_discount"
                            class="mt-2 flex flex-wrap items-baseline gap-2"
                        >
                            <span class="text-sm font-bold text-rose-600"
                                >&#8369;{{
                                    formatMoney(product.discounted_price)
                                }}</span
                            >
                            <span class="text-xs text-slate-400 line-through"
                                >&#8369;{{
                                    formatMoney(product.base_price)
                                }}</span
                            >
                        </div>
                        <p v-else class="mt-2 text-sm font-bold text-slate-950">
                            &#8369;{{ formatMoney(product.base_price) }}
                        </p>
                    </div>
                </article>
            </div>
            <div
                v-else
                class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500"
            >
                No in-stock products are available yet.
            </div>
        </section>

        <!-- About hero -->
        <section
            class="group relative isolate overflow-hidden rounded-[2rem] bg-gradient-to-br from-orange-50 via-white to-slate-100 px-6 py-8 shadow-sm ring-1 ring-orange-100/80 transition duration-500 hover:shadow-xl hover:shadow-orange-100/60 sm:px-10 sm:py-12 lg:px-14 lg:py-14"
        >
            <div
                class="pointer-events-none absolute -right-24 -top-28 -z-10 h-80 w-80 rounded-full bg-orange-300/25 blur-3xl transition duration-700 group-hover:scale-125"
            ></div>
            <div
                class="pointer-events-none absolute -bottom-32 left-1/3 -z-10 h-72 w-72 rounded-full bg-amber-200/30 blur-3xl"
            ></div>

            <div class="grid items-center gap-10 lg:grid-cols-[1.05fr_0.95fr]">
                <div>
                    <h2
                        class="mt-5 max-w-2xl text-3xl font-black leading-tight tracking-tight text-slate-950 sm:text-4xl lg:text-5xl"
                    >
                        More than a shop. A smarter way to move furniture
                        forward.
                    </h2>
                    <p
                        class="mt-5 max-w-xl text-sm leading-7 text-slate-600 sm:text-base"
                    >
                        FurniSync connects the showroom, inventory, procurement,
                        logistics, and customer experience in one operating
                        system built around how furniture businesses really
                        work.
                    </p>
                    <div class="mt-7 flex flex-wrap gap-3">
                        <Button
                            label="Discover FurniSync"
                            rounded
                            class="!px-5"
                            @click="goAbout"
                        />
                        <Button
                            label="Explore the shop"
                            icon="pi pi-shopping-bag"
                            severity="secondary"
                            outlined
                            rounded
                            class="!px-5"
                            @click="goShop"
                        />
                    </div>
                </div>

                <div class="relative min-h-[250px]">
                    <div
                        class="absolute left-2 top-4 w-[72%] rotate-[-5deg] rounded-3xl border border-orange-100 bg-white/90 p-5 shadow-xl shadow-orange-100/50 backdrop-blur transition duration-500 group-hover:rotate-[-8deg] group-hover:-translate-y-2"
                    >
                        <div class="flex items-center justify-between">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-2xl bg-orange-100 text-orange-600"
                            >
                                <i class="pi pi-box text-lg"></i>
                            </div>
                            <span
                                class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-600"
                                >Connected</span
                            >
                        </div>
                        <p class="mt-5 text-lg font-bold text-slate-900">
                            One source of truth
                        </p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            Inventory, orders, and operations stay aligned.
                        </p>
                        <div
                            class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100"
                        >
                            <div
                                class="h-full w-4/5 rounded-full bg-gradient-to-r from-orange-400 to-orange-600"
                            ></div>
                        </div>
                    </div>
                    <div
                        class="absolute bottom-2 right-0 w-[70%] rotate-[6deg] rounded-3xl border border-slate-200 bg-slate-900 p-5 text-white shadow-2xl transition duration-500 group-hover:rotate-[9deg] group-hover:translate-y-1"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-orange-300"
                            >
                                <i class="pi pi-chart-line"></i>
                            </div>
                            <span class="text-xs font-semibold text-slate-300"
                                >Built around your workflow</span
                            >
                        </div>
                        <p class="mt-5 text-2xl font-black">Sell smarter.</p>
                        <p class="mt-1 text-sm text-slate-300">
                            Operate with confidence.
                        </p>
                        <div class="mt-5 flex gap-1.5">
                            <span
                                v-for="index in 6"
                                :key="index"
                                class="h-2 flex-1 rounded-full"
                                :class="
                                    index < 5 ? 'bg-orange-400' : 'bg-white/15'
                                "
                            ></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <MarketingFooter />
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'
import MarketingFooter from '@/Components/MarketingFooter.vue'
import Model3DPreview from '@/Components/merchandising/Model3DPreview.vue'
import ecommerceService from '@/services/ecommerce.service'
import axiosClient from '@/axios'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import Skeleton from 'primevue/skeleton'
import { useRecentlyViewedProducts } from '@/composables/useRecentlyViewedProducts'

defineOptions({ layout: EcommerceMobileWrapper })

const router = useRouter()
const { recentlyViewed, clearRecentlyViewed } = useRecentlyViewedProducts()
const shoppingRooms = ref<any[]>([])

const categories = ref<any[]>([])
const topProducts = ref<any[]>([])
const flashDeals = ref<any[]>([])
const loadingCategories = ref(true)
const loadingProducts = ref(true)
const loadingDeals = ref(true)

function normalizeImageUrl(raw: string) {
    if (!raw) return ''
    if (/^(https?:|data:|\/storage\/)/.test(raw)) return raw
    return raw.startsWith('storage/')
        ? `/${raw}`
        : `/storage/${raw.replace(/^\//, '')}`
}

function onImageError(event: Event) {
    const image = event.target as HTMLImageElement
    image.src = '/F.svg'
}

function formatMoney(value: number | string) {
    return Number(value || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

function goShop() {
    router.push({ name: 'ecommerce.products' })
}

function goCategory(categoryId: number) {
    router.push({
        name: 'ecommerce.products',
        query: { category_id: categoryId }
    })
}

function goDeals() {
    router.push({ name: 'ecommerce.products', query: { deals: 1 } })
}

function goRoom(room: any) {
    router.push({ name: 'ecommerce.products', query: { room: room.slug } })
}
function openGuide(guide: any) {
    if (guide.search) {
        router.push({
            name: 'ecommerce.products',
            query: { search: guide.search }
        })
        return
    }
    if (guide.title === 'Parts & support') {
        router.push({ name: 'ecommerce.orders' })
        return
    }
    window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' })
}

function goProduct(productId: number) {
    router.push({ name: 'ecommerce.product', params: { id: productId } })
}

function goAbout() {
    router.push('/business/about')
}

async function loadCategories() {
    try {
        const response = await ecommerceService.getActiveStockCategories()
        const rows = response.data?.data || []
        categories.value = Array.isArray(rows) ? rows : []
    } finally {
        loadingCategories.value = false
    }
}

async function loadTopProducts() {
    try {
        const response = await ecommerceService.getActiveStockProducts({
            per_page: 15,
            sort: 'popular'
        })
        const rows = response.data?.data?.data || response.data?.data || []
        topProducts.value = Array.isArray(rows) ? rows.slice(0, 15) : []
    } finally {
        loadingProducts.value = false
    }
}

async function loadFlashDeals() {
    try {
        const response = await ecommerceService.getActiveStockProducts({
            per_page: 10,
            discounted_only: true,
            sort: 'discount'
        })
        const rows = response.data?.data?.data || response.data?.data || []
        flashDeals.value = Array.isArray(rows) ? rows.slice(0, 10) : []
    } finally {
        loadingDeals.value = false
    }
}

onMounted(() => {
    axiosClient.get('/api/public/shopping-rooms').then(({ data }) => { shoppingRooms.value = data?.data || [] }).catch(() => {})
    loadCategories()
    loadTopProducts()
    loadFlashDeals()
})
</script>
