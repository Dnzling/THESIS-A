<template>
  <div class="min-h-screen bg-gradient-to-b from-white via-orange-50/20 to-slate-50 text-slate-900">
    <TopNav />

    <main>
      <section class="relative overflow-hidden py-20 lg:py-28">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(245,158,107,0.18),transparent_55%)]"></div>
        <div class="mx-auto max-w-7xl px-4 text-center lg:px-8">

          <h1 class="mx-auto mt-5 max-w-3xl text-4xl font-bold leading-tight sm:text-5xl">
            Simple plans for modern furniture teams.
          </h1>
          <p class="mx-auto mt-5 max-w-2xl text-lg text-slate-600">
            Start small, scale fast. Every plan includes 3D product tools, DSS insights, and onboarding support.
          </p>

          <div class="mt-8 inline-flex items-center gap-3 rounded-full border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <span class="text-sm font-medium" :class="isYearly ? 'text-slate-900' : 'text-orange-500'">Monthly</span>
            <ToggleSwitch
              class="relative h-7 w-12 rounded-full transition"
              :class="isYearly ? 'bg-orange-500' : 'bg-slate-300'"
              @click="toggleBillingCycle"
            >
              <span
                class="absolute top-1 h-5 w-5 rounded-full bg-white shadow transition-transform"
                :class="isYearly ? 'translate-x-6' : 'translate-x-1'"
              ></span>
            </ToggleSwitch>
            <span class="text-sm font-medium" :class="!isYearly ? 'text-slate-900' : 'text-orange-500'">Yearly</span>
            <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">Save 20%</span>
          </div>
        </div>
      </section>

      <section class="py-6">
        <div class="mx-auto max-w-7xl px-4 lg:px-8">
          <div v-if="loadingPlans && !visiblePlans.length" class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <h2 class="text-2xl font-bold text-orange-500">Loading plans</h2>
            <p class="mt-2 text-slate-600">Please wait while we fetch the latest offers.</p>
          </div>

          <div class="grid gap-6 lg:grid-cols-3">
            <article
              v-for="plan in visiblePlans"
              :key="plan.id"
              class="relative rounded-3xl border bg-white p-8 shadow-lg shadow-slate-200/60 transition hover:-translate-y-1 hover:shadow-xl"
              :class="plan.is_featured ? 'border-orange-200 ring-1 ring-orange-200' : 'border-slate-200'"
            >
              <div v-if="plan.is_featured" class="absolute right-6 top-6 rounded-full bg-orange-500 px-3 py-1 text-xs font-semibold text-white">
                Most Popular
              </div>

              <div class="space-y-1 pr-24">
                <h2 class="text-2xl font-bold text-slate-900">{{ plan.name }}</h2>
                <p class="text-sm leading-6 text-slate-600">{{ plan.description || 'Flexible plan for growing furniture teams.' }}</p>
              </div>

              <div class="mt-6 flex items-end gap-2">
                <span class="text-4xl font-bold tracking-tight text-slate-900">₱{{ formatPrice(plan) }}</span>
                <span class="pb-1 text-sm text-slate-500">/ {{ billingPeriod }}</span>
              </div>

              <p class="mt-2 text-sm text-slate-500">
                Billed {{ isYearly ? 'yearly' : 'monthly' }}
                <span v-if="isYearly" class="ml-2 font-semibold text-orange-700">Save ₱{{ yearlySavings(plan) }}</span>
              </p>
               <button
                type="button"
                class="mt-8 inline-flex w-full items-center justify-center rounded-full bg-orange-500 px-5 py-3 font-semibold text-white shadow-lg shadow-orange-500/20 transition hover:-translate-y-0.5 hover:bg-orange-600"
                @click="selectPlan(plan)"
              >
                Choose Plan
              </button>

              <div class="mt-6 space-y-3">
                <div v-for="feature in plan.features || []" :key="feature" class="flex items-start gap-3 text-sm text-slate-700">
                  <span class="mt-1 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-orange-100 text-orange-600">
                    <svg viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5">
                      <path fill-rule="evenodd" d="M16.704 5.297a1 1 0 0 1 0 1.414l-7.071 7.071a1 1 0 0 1-1.414 0L3.296 8.859A1 1 0 1 1 4.71 7.445l4.213 4.213 6.364-6.364a1 1 0 0 1 1.417.003Z" clip-rule="evenodd" />
                    </svg>
                  </span>
                  <span>{{ feature }}</span>
                </div>
              </div>

             
            </article>
          </div>
        </div>
      </section>

      <section class="py-16">
        <div class="mx-auto max-w-7xl px-4 lg:px-8">
          <div class="mb-8 text-center">
            <span class="text-sm font-semibold uppercase tracking-[0.2em] text-orange-500">Compare Plans</span>
            <h2 class="mt-3 text-3xl font-bold sm:text-4xl">See the difference at a glance.</h2>
          </div>

          <div v-if="planComparison.length" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="grid grid-cols-3 border-b border-slate-200 bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-700">
              <span>Feature</span>
              <span v-for="plan in visiblePlans" :key="plan.id" class="text-center">{{ plan.name }}</span>
            </div>

            <div v-for="row in planComparison" :key="row.feature" class="grid grid-cols-3 border-b border-slate-100 px-6 py-4 text-sm last:border-b-0">
              <span class="font-medium text-slate-700">{{ row.feature }}</span>
              <span v-for="plan in visiblePlans" :key="plan.id" class="flex justify-center text-slate-600">
                <span v-if="row.values[plan.plan_key] === true" class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                  <svg viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5">
                    <path fill-rule="evenodd" d="M16.704 5.297a1 1 0 0 1 0 1.414l-7.071 7.071a1 1 0 0 1-1.414 0L3.296 8.859A1 1 0 1 1 4.71 7.445l4.213 4.213 6.364-6.364a1 1 0 0 1 1.417.003Z" clip-rule="evenodd" />
                  </svg>
                </span>
                <span v-else>{{ row.values[plan.plan_key] || '-' }}</span>
              </span>
            </div>
          </div>
        </div>
      </section>

      <section class="py-16">
        <div class="mx-auto max-w-7xl px-4 lg:px-8">
          <div class="mb-8 text-center">
            <span class="text-sm font-semibold uppercase tracking-[0.2em] text-orange-500">FAQ</span>
            <h2 class="mt-3 text-3xl font-bold sm:text-4xl">Questions? We have answers.</h2>
          </div>

          <div class="mx-auto grid max-w-4xl gap-4">
            <details v-for="item in faqs" :key="item.q" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
              <summary class="cursor-pointer list-none font-semibold text-slate-900">
                <div class="flex items-center justify-between gap-4">
                  <span>{{ item.q }}</span>
                  <span class="text-slate-400 transition group-open:rotate-45">+</span>
                </div>
              </summary>
              <p class="mt-3 text-sm leading-6 text-slate-600">{{ item.a }}</p>
            </details>
          </div>
        </div>
      </section>

     
    </main>

    <MarketingFooter />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import TopNav from '@/Components/MarketingHeader.vue'
import MarketingFooter from '@/Components/MarketingFooter.vue'
import axiosClient from '@/axios'

const isYearly = ref(false)
const plans = ref<any[]>([])
const loadingPlans = ref(false)
const selectedPlanKey = ref<string>(String(new URLSearchParams(window.location.search).get('plan') || ''))
const visiblePlans = computed(() =>
  plans.value
    .filter((plan) => plan.is_active !== false)
    .slice()
    .sort((a, b) => {
      const order = ['free', 'simple']
      const aKey = String(a.plan_key || a.name || '').toLowerCase()
      const bKey = String(b.plan_key || b.name || '').toLowerCase()
      const aIndex = order.findIndex((key) => aKey.includes(key))
      const bIndex = order.findIndex((key) => bKey.includes(key))

      if (aIndex !== -1 || bIndex !== -1) {
        const safeA = aIndex === -1 ? order.length : aIndex
        const safeB = bIndex === -1 ? order.length : bIndex
        if (safeA !== safeB) return safeA - safeB
      }

      return String(a.name || '').localeCompare(String(b.name || ''))
    })
)
const billingPeriod = computed(() => (isYearly.value ? 'year' : 'month'))
const planComparison = computed(() => {
  const rows: { feature: string; values: Record<string, boolean | string> }[] = []
  const featureSet = new Set<string>()
  visiblePlans.value.forEach((plan) => {
    const features = Array.isArray(plan.features) ? plan.features : []
    features.forEach((feature: string) => featureSet.add(feature))
  })

  Array.from(featureSet).forEach((feature) => {
    const values: Record<string, boolean | string> = {}
    visiblePlans.value.forEach((plan) => {
      const features = Array.isArray(plan.features) ? plan.features : []
      values[plan.plan_key] = features.includes(feature)
    })
    rows.push({ feature, values })
  })

  return rows
})

const faqs = [
  {
    q: 'Can I switch plans anytime?',
    a: 'Yes. You can upgrade or downgrade at any time, and changes apply to the next billing cycle.'
  },
  {
    q: 'Is there a free trial?',
    a: 'The Simple plan includes a 14-day free trial. The Unlimited plan is direct payment and activates full modules immediately.'
  },
  {
    q: 'What payment methods do you accept?',
    a: 'We accept major cards and bank transfers for annual subscriptions.'
  },
  {
    q: 'Can I cancel anytime?',
    a: 'Yes. Cancel anytime and keep access until the end of your billing period.'
  }
]

const toggleBillingCycle = () => {
  isYearly.value = !isYearly.value
}

const loadPlans = async () => {
  loadingPlans.value = true
  try {
    const response = await axiosClient.get('/api/public/subscription-plans')
    plans.value = response.data?.data || []
  } catch (error) {
    plans.value = []
  } finally {
    loadingPlans.value = false
  }
}

const formatPrice = (plan: any) => {
  const price = isYearly.value ? Number(plan.yearly_price || 0) : Number(plan.monthly_price || 0)
  return price.toFixed(2)
}

const yearlySavings = (plan: any) => {
  const monthly = Number(plan.monthly_price || 0)
  const yearly = Number(plan.yearly_price || 0)
  const savings = (monthly * 12) - yearly
  return savings > 0 ? savings.toFixed(2) : '0.00'
}

const selectPlan = (plan: any) => {
  void plan
  router.get('/register', {}, { preserveState: true })
}

const startFreeTrial = () => {
  const fallback = visiblePlans.value[0]?.plan_key || 'simple'
  selectPlan({ plan_key: fallback })
}

const scheduleDemo = () => {
  window.open('https://calendly.com/furnisync/demo', '_blank')
}

onMounted(() => {
  loadPlans()
})
</script>
