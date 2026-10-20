<template>
  <div class="min-h-screen">
    <div class="relative overflow-hidden py-14">
      <div class="mx-auto max-w-7xl px-4 text-center lg:px-8">
        <h1 class="mx-auto mt-5 max-w-3xl text-4xl font-bold leading-tight sm:text-5xl">Simple plans for modern furniture teams.</h1>
        <p class="mx-auto mt-5 max-w-2xl text-lg text-slate-600">Pick the unlimited-duration Free plan, or choose a paid plan and complete payment securely on PayMongo.</p>

        <div class="mt-8 inline-flex items-center gap-3 rounded-full border border-slate-200 bg-white px-4 py-3 shadow-sm">
          <span class="text-sm font-medium" :class="!isYearly ? 'text-slate-900' : 'text-orange-500'">Monthly</span>
          <ToggleSwitch v-model="isYearly" inputId="billing-cycle-switch" aria-label="Toggle billing cycle" />
          <span class="text-sm font-medium" :class="isYearly ? 'text-slate-900' : 'text-orange-500'">Yearly</span>
          <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">Save 20%</span>
        </div>
      </div>
    </div>

    <section class="pb-16">
      <div class="mx-auto max-w-7xl px-4 lg:px-8">
        <div v-if="loadingPlans && !visiblePlans.length" class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
          <h2 class="text-2xl font-bold text-orange-500">Loading plans</h2>
          <p class="mt-2 text-slate-600">Please wait while we fetch the latest offers.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-4">
          <article v-for="plan in visiblePlans" :key="plan.id" class="relative rounded-3xl border bg-white p-8 shadow-lg shadow-slate-200/60 transition hover:-translate-y-1 hover:shadow-xl" :class="plan.is_featured ? 'border-2 border-orange-500 ring-1 ring-orange-100' : 'border-slate-200'">
            <div v-if="plan.is_featured" class="absolute right-6 top-6 rounded-full bg-orange-500 px-3 py-1 text-xs font-semibold text-white">Most Popular</div>
            <div class="space-y-1 pr-24">
              <h2 class="text-2xl font-bold text-slate-900">{{ plan.name }}</h2>
              <p class="text-sm leading-6 text-slate-600">{{ plan.description || 'Flexible plan for growing furniture teams.' }}</p>
            </div>
            <div class="mt-6 flex items-end gap-2">
              <span class="text-4xl font-bold text-slate-900">{{ formatPrice(plan) }}</span>
              <span class="pb-1 text-sm text-slate-500">/ {{ billingPeriod }}</span>
            </div>
            <p class="mt-2 text-sm text-slate-500">
              Billed {{ isYearly ? 'yearly' : 'monthly' }}
              <span v-if="isYearly" class="ml-2 font-semibold text-orange-700">Save {{ yearlySavings(plan) }}</span>
            </p>
            <button type="button" class="mt-8 inline-flex w-full items-center justify-center rounded-full bg-orange-500 px-5 py-3 font-semibold text-white shadow-lg shadow-orange-500/20 transition hover:-translate-y-0.5 hover:bg-orange-600" @click="plan.plan_key === 'free' || Number(plan.monthly_price || 0) === 0 ? chooseFreeTrial(plan) : openConfirmation(plan)">
              {{ plan.plan_key === 'free' || Number(plan.monthly_price || 0) === 0 ? 'Use Free Plan' : 'Choose Plan' }}
            </button>
            <div class="mt-6 space-y-3">
              <div v-for="feature in plan.features || []" :key="feature" class="flex items-start gap-3 text-sm text-slate-700">
                <span class="mt-1 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-orange-100 text-orange-600">✓</span>
                <span>{{ feature }}</span>
              </div>
            </div>
          </article>
        </div>
      </div>
    </section>

  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import ToggleSwitch from 'primevue/toggleswitch'
import axiosClient from '@/axios'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const plans = ref<any[]>([])
const loadingPlans = ref(false)
const isYearly = ref(false)
const storeIdFromQuery = ref<string>(new URLSearchParams(window.location.search).get('store_id') || '')

const billingPeriod = computed(() => (isYearly.value ? 'year' : 'month'))

const visiblePlans = computed(() =>
  plans.value
    .filter((plan) => plan.is_active !== false)
    .slice()
    .sort((a, b) => Number(a.sort_order ?? 0) - Number(b.sort_order ?? 0) || Number(a.id ?? 0) - Number(b.id ?? 0)),
)

const formatPrice = (plan: any) => {
  const price = isYearly.value ? Number(plan.yearly_price || 0) : Number(plan.monthly_price || 0)
  return formatMoney(price)
}

const yearlySavings = (plan: any) => {
  const monthly = Number(plan.monthly_price || 0)
  const yearly = Number(plan.yearly_price || 0)
  const savings = monthly * 12 - yearly
  return formatMoney(savings > 0 ? savings : 0)
}

const formatMoney = (amount: number) => new Intl.NumberFormat('en-PH', {
  style: 'currency',
  currency: 'PHP',
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
}).format(amount)

const loadPlans = async () => {
  loadingPlans.value = true
  try {
    const response = await axiosClient.get('/api/public/subscription-plans')
    plans.value = response.data?.data || []
  } catch {
    plans.value = []
  } finally {
    loadingPlans.value = false
  }
}

const chooseFreeTrial = async (plan: any) => {
  const storeId = Number(storeIdFromQuery.value || (authStore.currentUser as any)?.store_id || 0)
  if (!storeId) {
    router.visit('/store/registration')
    return
  }
  await axiosClient.put(`/api/stores/${storeId}/subscription`, {
    subscription_tier: String(plan.plan_key || 'free').toLowerCase(),
    setup_mode: 'free',
  })
  await authStore.fetchCurrentUser({ reloadPermissions: true })
  router.visit('/store/index')
}

const openConfirmation = (plan: any) => {
  const storeId = Number(storeIdFromQuery.value || (authStore.currentUser as any)?.store_id || 0)
  if (!storeId) {
    router.visit('/store/registration')
    return
  }

  router.visit(`/subscription-checkout?plan=${encodeURIComponent(plan.plan_key)}&cycle=${isYearly.value ? 'yearly' : 'monthly'}&store_id=${storeId}`)
}

onMounted(async () => {
  await authStore.fetchCurrentUser()
  await loadPlans()
})
</script>
