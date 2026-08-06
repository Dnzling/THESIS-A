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
                to="/pricing"
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
                      label="Show Photo"
                      size="small"
                      class="!rounded-xl !border-0 !bg-sky-500 !px-4 !py-2 !font-semibold !text-white !shadow-lg hover:!bg-sky-600"
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
                      class="!rounded-xl !border-0 !bg-sky-500 !px-4 !py-2.5 !text-sm !font-semibold !text-white !shadow-lg hover:!bg-sky-600"
                    />
                    <Button
                      label="Buy Now"
                      class="!rounded-xl !border-0 !bg-emerald-500 !px-4 !py-2.5 !text-sm !font-semibold !text-white !shadow-lg hover:!bg-emerald-600"
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


      <section ref="overviewSection" class="bg-[#fff7ed] py-20">
        <div class="mx-auto max-w-7xl px-4 lg:px-8">
          <div class="mx-auto max-w-3xl text-center">
         
            <h2 class="mt-5 text-3xl font-black tracking-tight sm:text-4xl">A sharper funnel from interest to store registration</h2>
            <p class="mt-4 text-lg text-slate-600">
              Keep the experience simple: get attention, capture sign-up, route users to store registration, then let them choose a plan with confidence.
            </p>
          </div>

          <div class="mt-12 grid gap-6 lg:grid-cols-3">
            <Card v-for="feature in features" :key="feature.title" class="h-full border border-orange-100 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
              <template #content>
                <div class="flex h-14 w-14 items-center justify-center rounded-xl text-orange-600" v-html="feature.icon"></div>
                <h3 class="mt-5 text-xl font-bold">{{ feature.title }}</h3>
                <p class="mt-3 text-sm leading-6 text-slate-600">{{ feature.description }}</p>
              </template>
            </Card>
          </div>
        </div>
      </section>

      <section class="bg-white py-20">
        <div class="mx-auto max-w-7xl px-4 lg:px-8">
          <div class="mx-auto max-w-3xl text-center">
            <span class="inline-flex rounded-full bg-orange-100 px-4 py-2 text-sm font-semibold text-orange-700">
              What You Get
            </span>
            <h2 class="mt-5 text-3xl font-black tracking-tight sm:text-4xl">
              Deliverables that make this system worth buying
            </h2>
            <p class="mt-4 text-lg text-slate-600">
              Instead of selling generic software, we deliver a complete furniture selling workflow that helps stores attract shoppers, improve buying confidence, and move faster to revenue.
            </p>
          </div>

          <div class="mt-12 overflow-hidden rounded-[2rem] border border-slate-200 shadow-sm">
            <div class="grid grid-cols-12 bg-slate-900 px-5 py-4 text-sm font-semibold text-white">
              <div class="col-span-12 md:col-span-4">Deliverable</div>
              <div class="col-span-12 md:col-span-4 md:text-center">Common Furniture Management System</div>
              <div class="col-span-12 md:col-span-4 md:text-center porta">Furnisync</div>
            </div>

            <div v-for="row in comparisonRows" :key="row.feature" class="grid grid-cols-12 border-t border-slate-200 bg-white px-5 py-4 text-sm">
              <div class="col-span-12 md:col-span-4">
                <p class="font-semibold text-slate-900">{{ row.feature }}</p>
              </div>
              <div class="col-span-12 mt-2 md:col-span-4 md:mt-0 md:text-center">
                <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-600">
                  {{ row.typical }}
                </span>
              </div>
              <div class="col-span-12 mt-2 md:col-span-4 md:mt-0 md:text-center">
                <span
                  class="inline-flex rounded-full px-3 py-1 font-semibold"
                  :class="row.highlight ? 'bg-orange-100 text-orange-700' : 'bg-emerald-100 text-emerald-700'"
                >
                  {{ row.advanced }}
                </span>
              </div>
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
import { ref } from 'vue'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Model3DPreview from '@/Components/merchandising/Model3DPreview.vue'
import TopNav from '@/Components/MarketingHeader.vue'
import MarketingFooter from '@/Components/MarketingFooter.vue'

const overviewSection = ref<HTMLElement | null>(null)

const stats = [
  { value: '1 path', title: 'Less user confusion', sub: 'Register, verify, set up store, then subscribe' },
  { value: '2 options', title: 'Plan clarity', sub: 'Free Trial or paid confirmation flow' },
  { value: 'CTA-first', title: 'Higher intent clicks', sub: 'Primary action is visible right away' },
]

const features = [
  {
    title: 'Sales-ready storefront',
    description: 'A polished home page, clear CTA path, and strong product presentation that turn visitors into store sign-ups.',
    icon: '<svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19h16"/><path d="M7 16V8l5-4 5 4v8"/><path d="M10 19V11h4v8"/></svg>',
  },
  {
    title: 'Store setup workflow',
    description: 'Guided registration, OTP verification, store details, and subscription selection without confusing extra steps.',
    icon: '<svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 4h10v16H7z"/><path d="M9 8h6"/><path d="M9 12h6"/><path d="M9 16h4"/></svg>',
  },
  {
    title: 'DSS + 3D viewer package',
    description: 'Built-in decision support and a 3D sofa preview that help customers buy faster and help stores sell smarter.',
    icon: '<svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M13 5l7 7-7 7"/><path d="M20 12H4"/></svg>',
  },
]

const steps = [
  { index: '01', title: 'Get attention fast', sub: 'A strong headline and a single primary CTA keep visitors moving.' },
  { index: '02', title: 'Register without friction', sub: 'No trial or subscription is forced during the sign-up step.' },
  { index: '03', title: 'Guide the next action', sub: 'Store registration comes first, then the plan selection screen.' },
]

const comparisonRows = [
  {
    feature: 'Furniture visualization',
    typical: 'Static product photos',
    advanced: 'Interactive 3D sofa preview customers can rotate',
    highlight: true,
  },
  {
    feature: 'Buying confidence',
    typical: 'Customers still guess before ordering',
    advanced: 'Price, rating, specs, and stock shown clearly',
    highlight: false,
  },
  {
    feature: 'Decision support',
    typical: 'Generic reports only',
    advanced: 'DSS insights that guide better store decisions',
    highlight: true,
  },
  {
    feature: 'Sales conversion',
    typical: 'Browse and hope',
    advanced: 'Browse, compare, preview, and buy with confidence',
    highlight: false,
  },
  {
    feature: 'Business fit',
    typical: 'One-size-fits-all software',
    advanced: 'Built for furniture stores that need to sell faster',
    highlight: true,
  },
]
</script>
