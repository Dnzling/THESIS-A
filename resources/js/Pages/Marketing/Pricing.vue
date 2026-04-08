<template>
  <div class="pricing-shell">
    <TopNav />
  
    <main>
      <section class="hero">
        <div class="container hero-inner">
          <h1 class="hero-title font-bold">Simple plans for modern furniture teams.</h1>
          <p class="hero-sub">
            Start small, scale fast. Every plan includes 3D product tools, DSS insights, and onboarding support.
          </p>
          <div class="billing-toggle">
            <span :class="{ active: !isYearly }">Monthly</span>
            <button class="switch" @click="toggleBillingCycle">
              <span :class="{ on: isYearly }"></span>
            </button>
            <span :class="{ active: isYearly }">Yearly</span>
            <span class="save">Save 20%</span>
          </div>
        </div>
      </section>
  
      <section class="plans">
        <div class="container plan-grid">
          <div v-if="loadingPlans && !visiblePlans.length" class="plan-card">
            <div class="plan-head">
              <div>
                <h2 class="font-bold text-2xl text-orange-500">Loading plans</h2>
                <p>Please wait while we fetch the latest offers.</p>
              </div>
            </div>
          </div>
          <div v-for="plan in visiblePlans" :key="plan.id" class="plan-card" :class="{ featured: plan.is_featured }">
            <div v-if="plan.is_featured" class="badge">Most Popular</div>
            <div class="plan-head">
              <div>
                <h2 class="font-bold text-2xl text-orange-500">{{ plan.name }}</h2>
                <p>{{ plan.description || 'Flexible plan for growing furniture teams.' }}</p>
              </div>
            </div>
            <div class="plan-price">
              <span class="price">₱{{ formatPrice(plan) }}</span>
              <span class="period">/ {{ billingPeriod }}</span>
            </div>
            <div class="plan-note">
              Billed {{ isYearly ? 'yearly' : 'monthly' }}
              <span v-if="isYearly" class="save-inline">Save ₱{{ yearlySavings(plan) }}</span>
            </div>
            <div class="plan-features">
              <div v-for="feature in plan.features || []" :key="feature" class="feature-row">
                <span class="check"></span>
                <span>{{ feature }}</span>
              </div>
            </div>
            <button class="btn primary" @click="selectPlan(plan.plan_key, isUnlimitedPlan(plan))">
              {{ isUnlimitedPlan(plan) ? 'Proceed to Payment' : 'Start Free Trial' }}
            </button>
          </div>
        </div>
      </section>
  
      <section class="comparison">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Compare Plans</span>
            <h2 class="section-title">See the difference at a glance.</h2>
          </div>
          <div class="compare-grid" v-if="planComparison.length">
            <div class="compare-row header">
              <span>Feature</span>
              <span v-for="plan in visiblePlans" :key="plan.id">{{ plan.name }}</span>
            </div>
            <div v-for="row in planComparison" :key="row.feature" class="compare-row">
              <span>{{ row.feature }}</span>
              <span v-for="plan in visiblePlans" :key="plan.id" class="center">
                <span v-if="row.values[plan.plan_key] === true" class="check"></span>
                <span v-else>{{ row.values[plan.plan_key] || '-' }}</span>
              </span>
            </div>
          </div>
        </div>
      </section>
  
      <section class="faq">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">FAQ</span>
            <h2 class="section-title">Questions? We have answers.</h2>
          </div>
          <div class="faq-list">
            <details v-for="item in faqs" :key="item.q">
              <summary>{{ item.q }}</summary>
              <p>{{ item.a }}</p>
            </details>
          </div>
        </div>
      </section>
  
      <section class="cta">
        <div class="container cta-card">
          <div>
            <h2>Ready to transform your furniture business?</h2>
            <p>Start your trial today and let our team tailor FurniSync to your workflow.</p>
          </div>
          <div class="cta-actions">
            <button class="btn primary" @click="startFreeTrial">Start Free Trial</button>
            <button class="btn ghost" @click="scheduleDemo">Schedule a Demo</button>
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

const visiblePlans = computed(() => plans.value.filter((plan) => plan.is_active !== false))
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

const setTrialPlan = (plan: string, directPayment = false) => {
  localStorage.setItem('trial_plan', plan)
  localStorage.setItem('trial_entry', 'pricing')
  if (directPayment) {
    localStorage.setItem('direct_payment', '1')
  } else {
    localStorage.removeItem('direct_payment')
  }
}

const selectPlan = (plan: string, directPayment = false) => {
  const normalized = String(plan || 'simple')
  setTrialPlan(normalized, directPayment)
  router.get(
    '/register',
    { plan: normalized, trial: directPayment ? '0' : '1', direct_payment: directPayment ? '1' : '0' },
    { preserveState: true }
  )
}

const startFreeTrial = () => {
  const fallback = visiblePlans.value[0]?.plan_key || 'simple'
  setTrialPlan(fallback, false)
  router.get('/register', { plan: fallback, trial: '1' }, { preserveState: true })
}

const isUnlimitedPlan = (plan: any) => {
  return String(plan?.plan_key || '').toLowerCase() === 'unlimited'
}

const scheduleDemo = () => {
  window.open('https://calendly.com/furnisync/demo', '_blank')
}

onMounted(() => {
  loadPlans()
})
</script>

<style scoped>
@import url('https://fonts.bunny.net/css?family=manrope:400,500,600,700&family=plus-jakarta-sans:600,700,800');

* {
  font-family: 'Manrope', sans-serif;
}

.pricing-shell {
  --ink: #0b1b2b;
  --muted: #475569;
  --brand: #f59e6b;
  --brand-strong: #ea7b3c;
  --accent: #f7b37c;
  font-family: 'Manrope', sans-serif;
  color: var(--ink);
  background: #f6f7fb;
}

.container {
  max-width: 1180px;
  margin: 0 auto;
  padding: 0 24px;
}

.hero {
  padding: 96px 0 64px;
  background:
    radial-gradient(circle at top right, rgba(15, 118, 110, 0.18), transparent 55%),
    radial-gradient(circle at top left, rgba(37, 99, 235, 0.1), transparent 45%),
    linear-gradient(160deg, #ffffff 0%, #f3f6fb 100%);
  text-align: center;
}

.hero-title {
  font-family: var(--title-font);
  font-size: clamp(2.4rem, 4vw, 3.4rem);
  margin: 14px 0;
}

.hero-sub {
  color: var(--muted);
  max-width: 660px;
  margin: 0 auto;
}

.eyebrow {
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 2px;
  color: var(--brand);
}

.billing-toggle {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  margin-top: 28px;
  font-weight: 600;
}

.billing-toggle span {
  color: var(--muted);
}

.billing-toggle span.active {
  color: var(--ink);
}

.switch {
  width: 44px;
  height: 24px;
  background: #cbd5f5;
  border-radius: 999px;
  border: none;
  padding: 4px;
  cursor: pointer;
}

.switch span {
  display: block;
  width: 16px;
  height: 16px;
  background: #fff;
  border-radius: 999px;
  transition: transform 0.2s ease;
}

.switch span.on {
  transform: translateX(20px);
  background: var(--brand);
}

.save {
  background: rgba(245, 158, 11, 0.15);
  color: #b45309;
  padding: 4px 8px;
  border-radius: 999px;
  font-size: 0.75rem;
}

.plans {
  padding: 48px 0 72px;
}

.plan-grid {
  display: grid;
  gap: 24px;
}

@media (min-width: 900px) {
  .plan-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.plan-card {
  background: #fff;
  border-radius: 24px;
  padding: 28px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 18px 36px rgba(15, 23, 42, 0.08);
  position: relative;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.plan-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 22px 44px rgba(15, 23, 42, 0.12);
}

.plan-card.featured {
  border: 2px solid rgba(15, 118, 110, 0.5);
  background: linear-gradient(160deg, #ffffff 0%, #f0fdfa 100%);
}

.badge {
  position: absolute;
  top: 20px;
  right: 20px;
  background: var(--brand);
  color: #fff;
  padding: 6px 12px;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.plan-head {
  display: flex;
  gap: 16px;
  align-items: center;
}

.plan-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  font-weight: 700;
  background: rgba(15, 118, 110, 0.15);
  color: var(--brand);
}

.plan-price {
  display: flex;
  align-items: baseline;
  gap: 8px;
  margin-top: 20px;
}

.price {
  font-size: 2.4rem;
  font-weight: 700;
}

.period {
  color: var(--muted);
}

.plan-note {
  color: var(--muted);
  margin-bottom: 18px;
}

.save-inline {
  margin-left: 8px;
  color: #b45309;
  font-weight: 600;
}

.plan-features {
  display: grid;
  gap: 12px;
  margin: 20px 0 24px;
}

.feature-row {
  display: flex;
  gap: 10px;
  align-items: center;
}

.check {
  width: 16px;
  height: 16px;
  border-radius: 999px;
  background: var(--brand);
  position: relative;
}

.check::after {
  content: '';
  position: absolute;
  width: 6px;
  height: 10px;
  border: 2px solid #fff;
  border-top: 0;
  border-left: 0;
  transform: rotate(45deg);
  left: 5px;
  top: 1px;
}

.btn {
  padding: 12px 24px;
  border-radius: 14px;
  font-weight: 600;
  border: 1px solid transparent;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.btn.primary {
  background: linear-gradient(135deg, var(--brand), var(--brand-strong));
  color: #fff;
  width: 100%;
}

.btn.ghost {
  background: transparent;
  border-color: var(--brand);
  color: var(--brand);
}

.comparison {
  padding: 72px 0;
  background: #fff;
}


.compare-grid {
  border: 1px solid #e2e8f0;
  border-radius: 18px;
  overflow: hidden;
}

.compare-row {
  display: grid;
  grid-template-columns: 1.6fr 1fr 1fr;
  padding: 14px 18px;
  border-bottom: 1px solid #e2e8f0;
  align-items: center;
}

.compare-row.header {
  background: #f1f5f9;
  font-weight: 600;
}

.compare-row:last-child {
  border-bottom: none;
}

.center {
  display: flex;
  justify-content: center;
}

.faq {
  padding: 72px 0;
}

.faq-list {
  display: grid;
  gap: 14px;
}

details {
  background: #fff;
  border-radius: 16px;
  padding: 16px 18px;
  border: 1px solid #e2e8f0;
}

summary {
  font-weight: 600;
  cursor: pointer;
}

summary+p {
  margin-top: 10px;
  color: var(--muted);
}

.cta {
  padding: 72px 0;
}

.cta-card {
  background: linear-gradient(160deg, #0f766e 0%, #0f172a 100%);
  color: #f8fafc;
  border-radius: 24px;
  padding: 32px;
  display: grid;
  gap: 20px;
  box-shadow: 0 22px 50px rgba(15, 23, 42, 0.2);
}

.cta-actions {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
}

.cta .btn.primary {
  background: #fff;
  color: #0f172a;
  width: auto;
}

.cta .btn.ghost {
  border-color: #fff;
  color: #fff;
}

</style>
