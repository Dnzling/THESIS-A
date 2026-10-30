<template>
  <Head title="Home" />
  <div class="min-h-screen bg-[#fff7ed] text-slate-900">
    <TopNav />

    <main>
      <section class="relative min-h-[760px] overflow-hidden bg-gradient-to-br from-orange-500 via-orange-500 to-amber-500 text-white lg:min-h-[820px]">
        <div class="absolute inset-0 opacity-30">
          <div class="absolute -left-24 top-12 h-72 w-72 rounded-full bg-white/20 blur-3xl"></div>
          <div class="absolute right-0 top-24 h-80 w-80 rounded-full bg-amber-200/20 blur-3xl"></div>
          <div class="absolute bottom-0 left-1/3 h-64 w-64 rounded-full bg-orange-200/20 blur-3xl"></div>
        </div>

        <div class="relative z-20 mx-auto max-w-7xl px-4 py-24 lg:px-8 lg:py-32">
          <div class="pointer-events-auto relative z-20 max-w-2xl">
           

            <p class="text-sm font-bold uppercase text-orange-100">Built for furniture businesses</p>
            <h1 class="mt-6 text-5xl font-black leading-none sm:text-6xl lg:text-7xl">
              Run every part of
              <span class="block text-amber-100">your store as one.</span>
            </h1>

            <p class="mt-6 max-w-xl text-lg leading-8 text-orange-50/95 sm:text-xl">
              Connect procurement, inventory, branches, ecommerce, POS, delivery, returns, employees, and finance in one furniture-first operating platform.
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

          <div class="absolute left-1/2 top-1/2 z-0 h-[760px] w-screen -translate-x-1/2 -translate-y-1/2 lg:h-[820px]">
            <div class="absolute -left-8 top-10 h-28 w-28 rounded-full bg-white/15 blur-2xl"></div>
            <div class="absolute right-8 top-0 h-36 w-36 rounded-full bg-amber-200/20 blur-3xl"></div>

            <div class="absolute inset-0 z-0 flex items-center justify-center opacity-90">
              <Model3DPreview model-url="/storage/platform/sofa.glb" model-format="glb" :camera-y="12" :zoom="1.72" :screen-offset-x="0.28"
                height="100%" transparent-background />
            </div>
            <div class="pointer-events-none absolute inset-0 z-10 bg-gradient-to-r from-orange-600/90 via-orange-500/45 to-transparent"></div>
            <div class="pointer-events-none absolute bottom-5 left-1/2 z-20 -translate-x-1/2 rounded-full bg-slate-950/55 px-4 py-2 text-xs font-semibold text-white backdrop-blur lg:left-[79%]">
              Drag to rotate &middot; Scroll to zoom
            </div>

            <div v-if="false" class="rounded-[1.5rem] border border-white/20 bg-white/95 p-3 shadow-[0_30px_90px_rgba(69,26,0,0.28)]">
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
                  <h2 class="mt-4 text-2xl font-black text-slate-900 sm:text-3xl">Luna 3-Seater Sofa</h2>
                  <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
                    <span>Furniture</span>
                    <span class="text-slate-300">•</span>
                    <span>Drift</span>
                    <span class="text-slate-300">•</span>
                    <span>Living Room</span>
                  </div>
           

                  <div class="mt-5">
                    <p class="text-3xl font-black text-slate-900">₱24,900</p>
             
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


      <section class="business-reveal bg-[#fff7ed] py-20 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 lg:px-8">
          <div class="grid gap-6 border-b border-orange-200 pb-10 lg:grid-cols-[0.85fr_1.15fr] lg:items-end">
            <div>
              <div class="mb-5 flex items-center gap-3 text-sm font-bold text-orange-600">
                <span class="h-2 w-2 rounded-full bg-orange-500"></span>
                Built around daily store operations
              </div>
              <h2 class="text-3xl font-black leading-tight text-slate-950 sm:text-5xl">
                One system for the work your store already does.
              </h2>
            </div>
            <p class="max-w-xl text-base leading-8 text-slate-600 sm:text-lg lg:justify-self-end">
              Purchasing, inventory, sales, delivery, people, and finance share the same records, so every team can continue the work without re-entering information.
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
              <article class="mx-2 grid min-h-[430px] overflow-hidden rounded-[2rem] border border-orange-100 bg-white  lg:grid-cols-[0.8fr_1.2fr]">
                <div class="flex flex-col justify-center p-8 sm:p-12 lg:p-14">
                  <div class="flex items-center gap-3">
                    <span class="text-xs font-bold uppercase text-orange-600">Module {{ String(index + 1).padStart(2, '0') }}</span>
                    <span class="h-px w-10 bg-orange-300"></span>
                  </div>
                  <h3 class="mt-6 text-3xl font-black text-slate-950 sm:text-4xl">{{ data.name }}</h3>
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
                  <div class="relative w-full overflow-hidden rounded-2xl shadow-2xl">
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

      <section v-if="furnitureItems.length && selectedFurniture" class="business-reveal bg-white py-20 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 lg:px-8">
          <div class="mx-auto max-w-3xl text-center">
            <span class="text-sm font-bold uppercase text-orange-600">Interactive 3D commerce</span>
            <h2 class="mt-4 text-3xl font-black text-slate-950 sm:text-5xl">Let customers inspect furniture before they commit.</h2>
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
                :class="selectedFurniture?.name === item.name ? 'border-orange-500 bg-orange-50 shadow-lg shadow-orange-100' : 'border-slate-200 bg-white hover:-translate-y-1 hover:border-orange-300 hover:shadow-lg'"
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
                <p class="text-xs font-bold uppercase text-orange-600">Now viewing</p>
                <p class="font-bold text-slate-900">{{ selectedFurniture?.name }}</p>
              </div>
              <Model3DPreview
                :key="selectedFurniture?.name"
                :model-url="selectedFurniture?.modelUrl || ''"
                :model-format="selectedFurniture?.modelFormat || 'glb'"
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

      <section class="business-reveal relative overflow-hidden bg-slate-950 py-20 text-white sm:py-24">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_20%,rgba(249,115,22,0.18),transparent_35%)]"></div>
        <div class="relative mx-auto grid max-w-7xl gap-14 px-4 lg:grid-cols-2 lg:items-center lg:px-8">
          <div>
            <span class="text-sm font-bold uppercase text-orange-400">Decision Support System</span>
            <h2 class="mt-4 text-3xl font-black sm:text-5xl">Know what to reorder before shelves run empty.</h2>
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
              <div><p class="text-xs font-bold uppercase text-slate-400">Demand forecast</p><h3 class="mt-1 text-xl font-bold">Next 30 days</h3></div>
              <span class="rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-bold text-emerald-300">Confidence 89%</span>
            </div>
            <div class="relative mt-8 flex h-48 items-end gap-3 border-b border-l border-white/15 px-3 pt-4">
              <div class="pointer-events-none absolute inset-x-0 bottom-8 top-0">
                <span class="absolute inset-x-0 top-0 border-t border-dashed border-white/10"></span>
                <span class="absolute inset-x-0 top-1/3 border-t border-dashed border-white/10"></span>
                <span class="absolute inset-x-0 top-2/3 border-t border-dashed border-white/10"></span>
              </div>
              <div v-for="bar in forecastBars" :key="bar.label" class="relative z-10 flex h-full flex-1 flex-col items-center justify-end gap-2">
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

      <section class="business-reveal bg-white py-20 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 lg:px-8">
          <div class="mx-auto max-w-3xl text-center">
            <span class="text-sm font-bold uppercase text-orange-600">One continuous workflow</span>
            <h2 class="mt-4 text-3xl font-black text-slate-950 sm:text-5xl">From supplier request to customer care, nothing gets lost between teams.</h2>
            <p class="mt-5 text-lg leading-8 text-slate-600">Every transaction updates the next team with the information it needs—without duplicate encoding or disconnected spreadsheets.</p>
          </div>
          <div class="mt-16 grid gap-x-10 gap-y-12 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
              <div v-for="step in workflow" :key="step.title">
                <i :class="[step.icon, 'text-4xl text-orange-500']"></i>
                <h3 class="mt-5 text-xl font-bold text-slate-950">{{ step.title }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ step.description }}</p>
              </div>
          </div>
        </div>
      </section>

      <section class="business-reveal bg-orange-50 py-20 sm:py-24">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 lg:grid-cols-2 lg:px-8">
          <div>
            <span class="text-sm font-bold uppercase text-orange-600">Made for your operation</span>
            <h2 class="mt-4 text-3xl font-black text-slate-950 sm:text-5xl">Built for furniture retail at every stage.</h2>
            <div class="mt-8 grid gap-4 sm:grid-cols-2">
              <article v-for="business in businessTypes" :key="business.title" class="rounded-2xl border border-orange-100 bg-white p-5 shadow-sm">
                <i :class="[business.icon, 'text-xl text-orange-500']"></i><h3 class="mt-4 font-bold">{{ business.title }}</h3><p class="mt-2 text-sm leading-6 text-slate-600">{{ business.description }}</p>
              </article>
            </div>
          </div>
          <div class="rounded-[2rem] bg-slate-950 p-8 text-white sm:p-10">
            <span class="text-sm font-bold uppercase text-orange-400">Furniture-specific controls</span>
            <h2 class="mt-4 text-3xl font-black">Generic retail tools stop where furniture operations begin.</h2>
            <div class="mt-8 space-y-5">
              <div v-for="feature in furnitureFeatures" :key="feature.title" class="flex gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-500/15 text-orange-400"><i :class="feature.icon"></i></span>
                <div><h3 class="font-bold">{{ feature.title }}</h3><p class="mt-1 text-sm leading-6 text-slate-400">{{ feature.description }}</p></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="business-reveal bg-white py-20 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 lg:px-8">
          <div class="grid gap-12 lg:grid-cols-[0.9fr_1.1fr] lg:items-start">
            <div>
              <span class="text-sm font-bold uppercase text-orange-600">Start with confidence</span>
              <h2 class="mt-4 text-3xl font-black text-slate-950 sm:text-5xl">Set up securely. Grow without losing control.</h2>
              <p class="mt-5 text-lg leading-8 text-slate-600">Verification protects the marketplace, while branch-level access and role permissions keep sensitive operations in the right hands.</p>
              <div class="mt-8 grid gap-3 sm:grid-cols-2">
                <div v-for="trust in trustFeatures" :key="trust" class="flex items-center gap-3 rounded-xl bg-slate-50 p-4 text-sm font-semibold text-slate-700"><i class="pi pi-shield text-orange-500"></i>{{ trust }}</div>
              </div>
            </div>
            <ol class="space-y-4">
              <li v-for="(step, index) in onboarding" :key="step.title" class="flex gap-5 rounded-2xl border border-slate-200 p-5 shadow-sm">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-orange-500 font-black text-white">{{ index + 1 }}</span>
                <div><h3 class="font-bold text-slate-900">{{ step.title }}</h3><p class="mt-1 text-sm leading-6 text-slate-600">{{ step.description }}</p></div>
              </li>
            </ol>
          </div>
        </div>
      </section>

      <section class="business-reveal bg-slate-50 py-20 sm:py-24">
        <div class="mx-auto max-w-4xl px-4 lg:px-8">
          <div class="text-center"><span class="text-sm font-bold uppercase text-orange-600">Common questions</span><h2 class="mt-4 text-3xl font-black text-slate-950 sm:text-5xl">Know before you start.</h2></div>
          <div class="mt-10 divide-y divide-slate-200 overflow-hidden rounded-3xl border border-slate-200 bg-white px-6 sm:px-8">
            <details v-for="faq in faqs" :key="faq.question" class="group py-6">
              <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-bold text-slate-900"><span>{{ faq.question }}</span><i class="pi pi-plus text-orange-500 transition group-open:rotate-45"></i></summary>
              <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600">{{ faq.answer }}</p>
            </details>
          </div>
        </div>
      </section>

      <!-- <section class="business-reveal bg-gradient-to-r from-orange-600 to-amber-500 py-20 text-white">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-8 px-4 text-center lg:flex-row lg:px-8 lg:text-left">
          <div><p class="text-sm font-bold uppercase text-orange-100">Ready to modernize your store?</p><h2 class="mt-3 max-w-3xl text-3xl font-black sm:text-5xl">Bring every branch, team, product, and customer journey into sync.</h2></div>
          <div class="flex shrink-0 flex-col gap-3 sm:flex-row"><Button as="router-link" to="/register" label="Start Free" class="!border-white !bg-white !text-orange-600" rounded /><Button as="router-link" to="/business/pricing" label="Compare Plans" outlined class="!border-white !text-white" rounded /></div>
        </div>
      </section> -->
    </main>

    <MarketingFooter />
    <ScrollTop :threshold="320" icon="pi pi-arrow-up" class="!bg-orange-500 !text-white" />
  </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from 'primevue/button'
import Carousel from 'primevue/carousel'
import ScrollTop from 'primevue/scrolltop'
import Model3DPreview from '@/Components/merchandising/Model3DPreview.vue'
import TopNav from '@/Components/MarketingHeader.vue'
import MarketingFooter from '@/Components/MarketingFooter.vue'
import axiosClient from '@/axios'

const stats = [
  { title: 'One source of truth', sub: 'Every branch and team works from connected records' },
  { title: 'Furniture-first tools', sub: 'Bulky delivery, variants, 3D products, and returns' },
  { title: 'Controlled growth', sub: 'Verification, permissions, approvals, and audit trails' },
]

const workflow = [
  { title: 'Procure', icon: 'pi pi-file-edit', description: 'Compare quotations and issue purchase orders.' },
  { title: 'Receive', icon: 'pi pi-box', description: 'Inspect goods and update stock accurately.' },
  { title: 'Sell', icon: 'pi pi-shopping-cart', description: 'Connect POS and ecommerce orders.' },
  { title: 'Deliver', icon: 'pi pi-truck', description: 'Assign branches, drivers, vehicles, and trips.' },
  { title: 'Support', icon: 'pi pi-replay', description: 'Investigate returns and prioritize replacement.' },
  { title: 'Account', icon: 'pi pi-chart-bar', description: 'Keep payments and finance records traceable.' },
]

const businessTypes = [
  { title: 'Independent stores', icon: 'pi pi-shop', description: 'Replace spreadsheets with one practical operating system.' },
  { title: 'Multi-branch retailers', icon: 'pi pi-sitemap', description: 'See stock, staff, sales, and delivery activity by branch.' },
  { title: 'Furniture showrooms', icon: 'pi pi-building', description: 'Connect rich product presentation with available inventory.' },
  { title: 'Manufacturers and retailers', icon: 'pi pi-cog', description: 'Coordinate purchasing, finished goods, sales, and fulfillment.' },
]

const furnitureFeatures = [
  { title: 'Dimensions, weight, and bulky rules', icon: 'pi pi-arrows-alt', description: 'Price delivery and return handling using the real physical requirements of each product.' },
  { title: 'Variants and immersive 3D', icon: 'pi pi-box', description: 'Present finishes, sizes, materials, and rotatable furniture previews clearly.' },
  { title: 'Branch-aware inventory', icon: 'pi pi-warehouse', description: 'Know where sellable stock lives and prevent orders against unavailable units.' },
  { title: 'Replacement-first returns', icon: 'pi pi-refresh', description: 'Investigate evidence, arrange pickup, quarantine returns, and issue replacement stock.' },
]

const trustFeatures = ['Verified business onboarding', 'Role-based permissions', 'Branch-level access', 'Store data isolation', 'Approval workflows', 'Activity and inventory trails']
const onboarding = [
  { title: 'Register your business', description: 'Create the owner account and choose the plan that fits your operation.' },
  { title: 'Complete store verification', description: 'Submit the required business documents to establish marketplace trust.' },
  { title: 'Configure branches and people', description: 'Set up locations, roles, permissions, warehouses, and operating rules.' },
  { title: 'Add products and opening stock', description: 'Build the catalog, variants, specifications, prices, and inventory balances.' },
  { title: 'Start selling with connected operations', description: 'Use POS and ecommerce while logistics, CRM, and finance stay synchronized.' },
]
const faqs = [
  { question: 'Can I manage more than one branch?', answer: 'Yes. FurniSync keeps inventory, employees, sales, deliveries, and permissions branch-aware while giving authorized owners a store-wide view.' },
  { question: 'Can each employee have different access?', answer: 'Yes. Roles and permissions control which modules and actions staff can use, including optional cross-branch access.' },
  { question: 'Why does my store need verification?', answer: 'Verification helps protect customers and the marketplace. Stores complete verification before publicly publishing products.' },
  { question: 'Do suppliers need to use staff chat?', answer: 'No. Staff chat remains internal. Supplier communication is handled through procurement records, quotations, purchase orders, and formal external communication.' },
  { question: 'How are furniture delivery and return charges calculated?', answer: 'Stores can configure base, distance, weight, bulky-item, minimum, and area-based rules. Store-fault returns can be waived automatically.' },
  { question: 'Can I start with only the modules I need?', answer: 'Available modules and limits depend on the selected subscription plan. You can compare current options on the Pricing page.' },
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

const modules = ref<any[]>(fallbackModules)
const furnitureItems = ref<FurnitureItem[]>([])
const selectedFurniture = ref<FurnitureItem | null>(null)

const loadHomepageContent = async () => {
  try {
    const response = await axiosClient.get('/api/public/home-content')
    const content = response.data?.data || {}
    if (Array.isArray(content.modules)) modules.value = content.modules
    if (Array.isArray(content.furniture) && content.furniture.length) {
      furnitureItems.value = content.furniture
        .filter((item: any) => item.model_url && !String(item.model_url).toLowerCase().endsWith('/platform/sofa.glb'))
        .map((item: any) => ({
        name: item.label,
        caption: item.description || '',
        thumbnailUrl: item.thumbnail_url,
        modelUrl: item.model_url,
        modelFormat: item.model_format || 'glb',
      }))
      selectedFurniture.value = furnitureItems.value[0] || null
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

loadHomepageContent()
</script>
