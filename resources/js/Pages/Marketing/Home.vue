<template>
  <Head title="Home" />
  <div class="min-h-screen bg-[#fff7ed] text-slate-900">
    <TopNav />

    <main>
      <section class="relative overflow-hidden bg-gradient-to-br from-orange-500 via-orange-500 to-amber-500 text-white">
        <div class="absolute inset-0 opacity-30">
          <div class="absolute -left-24 top-12 h-72 w-72 rounded-full bg-white/20 blur-3xl"></div>
          <div class="absolute right-0 top-24 h-80 w-80 rounded-full bg-amber-200/20 blur-3xl"></div>
          <div class="absolute bottom-0 left-1/3 h-64 w-64 rounded-full bg-orange-200/20 blur-3xl"></div>
        </div>

        <div class="relative mx-auto grid max-w-7xl gap-16 px-4 py-20 lg:grid-cols-2 lg:items-center lg:px-8 lg:py-28">
          <div class="max-w-2xl">
           

            <h1 class="mt-6 text-5xl font-black leading-none tracking-tight sm:text-6xl lg:text-7xl">
              Turn visitors into
              <span class="block text-amber-100">buyers faster.</span>
            </h1>

            <p class="mt-6 max-w-xl text-lg leading-8 text-orange-50/95 sm:text-xl">
              Showcase products beautifully, guide buyers to the right plan, and keep store operations moving in one simple platform.
              More clicks, more store sign-ups, and a clearer path to checkout.
            </p>

            <div class="mt-10 flex flex-col gap-4 sm:flex-row sm:items-center">
              <Button
                as="router-link"
                to="/register"
                severity="warning" rounded
                class="!border-0 !bg-white !px-7 !py-4 !text-lg !font-bold !text-orange-600 !shadow-xl hover:!bg-orange-50"
              >
                Get Started Free
              </Button>
              <Button
                as="router-link"
                to="/business/pricing"
                severity="secondary" rounded
                class="!border-white/25 !bg-transparent !px-7 !py-4 !text-lg !font-bold !text-white hover:!bg-white/10"
              >
                View Plans
              </Button>
            </div>

            <div class="mt-10 flex flex-wrap gap-6 text-sm text-orange-50/90">
              <div v-for="item in stats" :key="item.title" class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur">
                <p class="font-semibold text-white">{{ item.title }}</p>
                <p>{{ item.sub }}</p>
              </div>
            </div>
          </div>

          <div class="relative">
            <div class="absolute -left-8 top-10 h-28 w-28 rounded-full bg-white/15 blur-2xl"></div>
            <div class="absolute right-8 top-0 h-36 w-36 rounded-full bg-amber-200/20 blur-3xl"></div>

            <div class="rounded-[1.5rem] border border-white/20 bg-white/95 p-3 shadow-[0_30px_90px_rgba(69,26,0,0.28)]">
              <div class="grid gap-0 overflow-hidden rounded-[1.25rem] border border-slate-200 bg-white lg:grid-cols-[0.92fr_1.08fr]">
                <div class="border-b border-slate-200 bg-slate-50 p-3 lg:border-b-0 lg:border-r">
                  <div class="flex items-center justify-between">
                    <span class="rounded-full bg-sky-100 px-3 py-1 text-sm font-semibold text-sky-700">New</span>
                    <Button
                      label="3D"
                      size="small"
                      
                    />
                  </div>

                  <div class="mt-3 overflow-hidden rounded-[1rem] border border-slate-200 bg-white">
                    <div class="flex aspect-[4/5] items-center justify-center bg-slate-50 p-3">
                      <div class="h-full w-full">
                        <Model3DPreview
                          model-url="/storage/platform/sofa.glb"
                          model-format="glb"
                          :camera-y="12"
                          :zoom="1.95"
                          height="100%"
                        />
                      </div>
                    </div>
                  </div>
                </div>

                <div class="p-4 sm:p-5 lg:p-6">
                  <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-700">In Stock</span>
                  <h2 class="mt-4 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">Luna 3-Seater Sofa</h2>
                  <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
                    <span>Furniture</span>
                    <span class="text-slate-300">•</span>
                    <span>Drift</span>
                    <span class="text-slate-300">•</span>
                    <span>Living Room</span>
                  </div>
           

                  <div class="mt-5">
                    <p class="text-3xl font-black tracking-tight text-slate-900">₱24,900</p>
             
                  </div>

                  <div class="mt-5">

                    <p class="mt-2 text-sm leading-6 text-slate-600">
                      Modern 3D sofa preview with smooth rotation, clear pricing, and trust details that help shoppers move from browsing to buying.
                    </p>
                  </div>
                  <div class="mt-5 flex flex-wrap items-center gap-2">
                  
                    <Button
                      label="Add to Cart"
                      size=small
                      outlined
                    />
                    <Button
                      label="Buy Now"
                      size=small
                    />
                  </div>

                  <div class="mt-4 flex items-center gap-3 text-sm text-slate-500">
                    <div class="flex items-center gap-1 text-amber-400">
                      <span v-for="star in 5" :key="star">★</span>
                    </div>
                    <span>4.8</span>
                    <span class="text-slate-300">•</span>
                    <span>1,284 ratings</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>


      <section class="bg-[#fff7ed] py-20 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 lg:px-8">
          <div class="max-w-3xl">
            <span class="text-sm font-bold uppercase tracking-[0.22em] text-orange-600">One connected platform</span>
            <h2 class="mt-4 text-3xl font-black tracking-tight text-slate-950 sm:text-5xl">
              Every team sees what they need. Every workflow stays connected.
            </h2>
            <p class="mt-5 text-lg leading-8 text-slate-600">
              Replace scattered spreadsheets and disconnected tools with purpose-built modules that move your furniture business from sourcing to delivery.
            </p>
          </div>

          <Carousel
            :value="modules"
            :numVisible="1"
            :numScroll="1"
            :circular="true"
            :autoplayInterval="6500"
            class="mt-12"
          >
            <template #item="{ data, index }">
              <article class="mx-2 grid min-h-[430px] overflow-hidden rounded-[2rem] border border-orange-100 bg-white shadow-[0_24px_70px_rgba(124,45,18,0.10)] lg:grid-cols-[0.8fr_1.2fr]">
                <div class="flex flex-col justify-center p-8 sm:p-12 lg:p-14">
                  <div class="flex items-center gap-3">
                    <span class="text-xs font-bold uppercase tracking-[0.2em] text-orange-600">Module {{ String(index + 1).padStart(2, '0') }}</span>
                    <span class="h-px w-10 bg-orange-300"></span>
                  </div>
                  <h3 class="mt-6 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">{{ data.name }}</h3>
                  <p class="mt-5 text-base leading-8 text-slate-600">{{ data.description }}</p>
                  <div class="mt-8 flex items-center gap-3 text-sm font-semibold text-slate-700">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-orange-100 text-orange-600">
                      <i class="pi pi-check text-xs"></i>
                    </span>
                    {{ data.benefits?.[0] || data.outcome }}
                  </div>
                </div>

                <div class="relative flex min-h-[300px] items-center justify-center overflow-hidden bg-gradient-to-br from-slate-900 to-slate-800 p-6 sm:p-10">
                  <div class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-orange-500/20 blur-3xl"></div>
                  <div class="absolute -bottom-20 left-12 h-52 w-52 rounded-full bg-amber-300/10 blur-3xl"></div>
                  <div class="relative w-full overflow-hidden rounded-2xl border border-white/10 bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                      <div class="flex gap-2"><span class="h-2.5 w-2.5 rounded-full bg-orange-400"></span><span class="h-2.5 w-2.5 rounded-full bg-amber-300"></span><span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span></div>
                      <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ data.name }} UI</span>
                    </div>
                    <img v-if="data.image_url" :src="data.image_url" :alt="`${data.name} interface preview`" class="aspect-[16/9] w-full object-cover" />
                    <div v-else class="flex aspect-[16/9] flex-col items-center justify-center bg-slate-50 p-8 text-center">
                      <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-orange-100 text-orange-600">
                        <i class="pi pi-image text-xl"></i>
                      </div>
                      <p class="mt-4 font-bold text-slate-800">{{ data.name }} preview</p>
                      <p class="mt-1 text-xs text-slate-400">Screenshot will be added here</p>
                    </div>
                  </div>
                </div>
              </article>
            </template>
          </Carousel>
        </div>
      </section>

      <section class="bg-white py-20 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 lg:px-8">
          <div class="mx-auto max-w-3xl text-center">
            <span class="text-sm font-bold uppercase tracking-[0.22em] text-orange-600">Interactive 3D commerce</span>
            <h2 class="mt-4 text-3xl font-black tracking-tight text-slate-950 sm:text-5xl">Let customers inspect furniture before they commit.</h2>
            <p class="mt-5 text-lg leading-8 text-slate-600">
              Give shoppers a clearer sense of shape, proportion, and detail. Select an item below, then drag the preview to explore it from every angle.
            </p>
          </div>

          <div class="mt-12 grid gap-8 lg:grid-cols-[0.72fr_1.28fr] lg:items-stretch">
            <div class="grid grid-cols-2 gap-3">
              <button
                v-for="item in furnitureItems"
                :key="item.name"
                type="button"
                class="group min-h-40 rounded-2xl border p-5 text-left transition duration-300"
                :class="selectedFurniture.name === item.name ? 'border-orange-500 bg-orange-50 shadow-lg shadow-orange-100' : 'border-slate-200 bg-white hover:-translate-y-1 hover:border-orange-300 hover:shadow-lg'"
                @click="selectedFurniture = item"
              >
                <div class="h-16 w-16 overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                  <img v-if="item.thumbnailUrl" :src="item.thumbnailUrl" :alt="item.name" class="h-full w-full object-cover" />
                  <div v-else class="flex h-full w-full items-center justify-center text-lg font-black text-orange-500">{{ item.name.charAt(0) }}</div>
                </div>
                <p class="mt-7 text-lg font-bold text-slate-900">{{ item.name }}</p>
                <p class="mt-1 text-xs leading-5 text-slate-500">{{ item.caption }}</p>
              </button>
            </div>

            <div class="relative min-h-[460px] overflow-hidden rounded-[2rem] border border-slate-200 bg-slate-50 shadow-[0_24px_70px_rgba(15,23,42,0.10)]">
              <div class="absolute left-5 top-5 z-10 rounded-full bg-white/90 px-4 py-2 shadow-sm backdrop-blur">
                <p class="text-xs font-bold uppercase tracking-wider text-orange-600">Now viewing</p>
                <p class="font-bold text-slate-900">{{ selectedFurniture.name }}</p>
              </div>
              <Model3DPreview
                :key="selectedFurniture.name"
                :model-url="selectedFurniture.modelUrl"
                :model-format="selectedFurniture.modelFormat"
                :camera-y="12"
                :zoom="1.8"
                height="100%"
              />
              <div class="absolute bottom-5 left-1/2 -translate-x-1/2 rounded-full bg-slate-950/80 px-4 py-2 text-xs font-medium text-white backdrop-blur">
                Drag to rotate &middot; Scroll to zoom
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="relative overflow-hidden bg-slate-950 py-20 text-white sm:py-24">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_20%,rgba(249,115,22,0.18),transparent_35%)]"></div>
        <div class="relative mx-auto grid max-w-7xl gap-14 px-4 lg:grid-cols-2 lg:items-center lg:px-8">
          <div>
            <span class="text-sm font-bold uppercase tracking-[0.22em] text-orange-400">Decision Support System</span>
            <h2 class="mt-4 text-3xl font-black tracking-tight sm:text-5xl">Know what to reorder before shelves run empty.</h2>
            <p class="mt-5 max-w-xl text-lg leading-8 text-slate-300">
              FurniSync turns sales history and changing demand into practical reorder suggestions, helping teams buy with evidence instead of guesswork.
            </p>
            <div class="mt-9 space-y-5">
              <div v-for="insight in dssInsights" :key="insight.title" class="flex gap-4">
                <span class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-orange-500 text-white"><i class="pi pi-check text-xs"></i></span>
                <div><h3 class="font-bold text-white">{{ insight.title }}</h3><p class="mt-1 text-sm leading-6 text-slate-400">{{ insight.description }}</p></div>
              </div>
            </div>
          </div>

          <div class="rounded-[2rem] border border-white/10 bg-white/5 p-5 shadow-2xl backdrop-blur sm:p-7">
            <div class="flex items-start justify-between gap-4">
              <div><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Demand forecast</p><h3 class="mt-1 text-xl font-bold">Next 30 days</h3></div>
              <span class="rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-bold text-emerald-300">Confidence 89%</span>
            </div>
            <div class="mt-8 flex h-48 items-end gap-3 border-b border-l border-white/10 px-3 pt-4">
              <div v-for="bar in forecastBars" :key="bar.label" class="flex flex-1 flex-col items-center justify-end gap-2">
                <div class="w-full rounded-t-lg bg-gradient-to-t from-orange-600 to-amber-300 transition hover:brightness-110" :style="{ height: `${bar.value}%` }"></div>
                <span class="pb-2 text-[10px] text-slate-500">{{ bar.label }}</span>
              </div>
            </div>
            <div class="mt-6 rounded-2xl border border-orange-400/20 bg-orange-400/10 p-4">
              <div class="flex items-center justify-between gap-4"><div><p class="text-xs text-orange-200">Recommended action</p><p class="mt-1 font-bold">Reorder 18 dining chairs</p></div><span class="rounded-xl bg-orange-500 px-4 py-2 text-xs font-bold text-white">Review suggestion</span></div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <MarketingFooter />
  </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { onMounted, ref } from 'vue'
import Button from 'primevue/button'
import Carousel from 'primevue/carousel'
import Model3DPreview from '@/Components/merchandising/Model3DPreview.vue'
import TopNav from '@/Components/MarketingHeader.vue'
import MarketingFooter from '@/Components/MarketingFooter.vue'
import axiosClient from '@/axios'

const stats = [
  { value: '1 path', title: 'Less user confusion', sub: 'Register, verify, set up store, then subscribe' },
  { value: '2 options', title: 'Plan clarity', sub: 'Free Trial or paid confirmation flow' },
  { value: 'CTA-first', title: 'Higher intent clicks', sub: 'Primary action is visible right away' },
]

const fallbackModules = [
  {
    name: 'Store Management Module',
    description: 'Control branches, store profiles, user roles, permissions, verification, and operating settings from one secure workspace.',
    outcome: 'Keep every branch governed and consistent.',
  },
  {
    name: 'Inventory Module',
    description: 'See stock by branch, monitor reorder levels, record adjustments, and move products with a complete audit trail.',
    outcome: 'Reduce stockouts and avoid excess inventory.',
  },
  {
    name: 'Procurement Module',
    description: 'Create purchase requisitions, collect supplier quotations, compare offers, issue purchase orders, and track receiving.',
    outcome: 'Buy the right stock from the right supplier.',
  },
  {
    name: 'Human Resources Module',
    description: 'Manage employees, departments, attendance, shifts, leave, payroll records, and workforce policies in one place.',
    outcome: 'Give your people team a reliable source of truth.',
  },
  {
    name: 'Job Hiring Module',
    description: 'Publish openings, organize applicants, schedule interviews, evaluate candidates, and move successful hires into onboarding.',
    outcome: 'Turn recruitment into a clear, repeatable process.',
  },
  {
    name: 'Sales Module',
    description: 'Handle customer orders, payments, receipts, fulfillment status, returns, and sales visibility across every branch.',
    outcome: 'Keep each sale traceable from order to completion.',
  },
  {
    name: 'Merchandising Module',
    description: 'Organize catalogs, pricing, promotions, product media, variations, and immersive 3D assets for stronger product presentation.',
    outcome: 'Make furniture easier to discover and trust.',
  },
  {
    name: 'Logistics Module',
    description: 'Coordinate delivery zones, vehicles, drivers, trips, proof of delivery, and return pickups from one operational view.',
    outcome: 'Deliver customer orders with fewer handoff errors.',
  },
]

type FurnitureItem = {
  name: string
  caption: string
  modelUrl: string
  modelFormat: string
  thumbnailUrl?: string | null
}

const fallbackFurniture: FurnitureItem[] = [
  { name: 'Chair', caption: 'Seating details and proportions', modelUrl: '/storage/platform/sofa.glb', modelFormat: 'glb' },
  { name: 'Table', caption: 'Surface, legs, and full silhouette', modelUrl: '/storage/platform/sofa.glb', modelFormat: 'glb' },
  { name: 'Cabinet', caption: 'Storage form and finish', modelUrl: '/storage/platform/sofa.glb', modelFormat: 'glb' },
  { name: 'Bed', caption: 'Frame scale and room presence', modelUrl: '/storage/platform/sofa.glb', modelFormat: 'glb' },
]

const modules = ref<any[]>(fallbackModules)
const furnitureItems = ref<FurnitureItem[]>(fallbackFurniture)
const selectedFurniture = ref<FurnitureItem>(fallbackFurniture[0])

const loadHomepageContent = async () => {
  try {
    const response = await axiosClient.get('/api/public/home-content')
    const content = response.data?.data || {}
    if (Array.isArray(content.modules)) modules.value = content.modules
    if (Array.isArray(content.furniture) && content.furniture.length) {
      furnitureItems.value = content.furniture.map((item: any) => ({
        name: item.label,
        caption: item.description || '',
        thumbnailUrl: item.thumbnail_url,
        modelUrl: item.model_url || '/storage/platform/sofa.glb',
        modelFormat: item.model_format || 'glb',
      }))
      selectedFurniture.value = furnitureItems.value[0]
    }
  } catch {
    // Keep the bundled fallback content when the public content service is unavailable.
  }
}

const dssInsights = [
  {
    title: 'Sales-based reorder suggestions',
    description: 'Recommend quantities using actual sales velocity, current stock, and configured reorder levels.',
  },
  {
    title: 'Demand over time',
    description: 'Expose rising, falling, and seasonal demand so purchasing teams can react before patterns become problems.',
  },
  {
    title: 'Forecasting teams can explain',
    description: 'Present forecasts with confidence indicators and clear recommended actions instead of opaque predictions.',
  },
]

const forecastBars = [
  { label: 'W1', value: 38 },
  { label: 'W2', value: 52 },
  { label: 'W3', value: 45 },
  { label: 'W4', value: 68 },
  { label: 'W5', value: 76 },
  { label: 'W6', value: 88 },
]

onMounted(loadHomepageContent)
</script>
