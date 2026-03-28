<template>
  <div class="pricing-shell">
    <TopNav />
  
    <main>
      <section class="hero">
        <div class="container hero-inner">
          <span class="eyebrow">Pricing</span>
          <h1 class="hero-title">Simple plans for modern furniture teams.</h1>
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
          <div class="plan-card">
            <div class="plan-head">
              <div class="plan-icon">S</div>
              <div>
                <h2>Simple</h2>
                <p>For boutique stores and single locations.</p>
              </div>
            </div>
            <div class="plan-price">
              <span class="price">₱{{ simplePrice }}</span>
              <span class="period">/ {{ billingPeriod }}</span>
            </div>
            <div class="plan-note">Billed {{ isYearly ? 'yearly' : 'monthly' }}</div>
            <div class="plan-features">
              <div v-for="feature in simpleFeatures" :key="feature" class="feature-row">
                <span class="check"></span>
                <span>{{ feature }}</span>
              </div>
            </div>
            <button class="btn primary" @click="selectPlan('simple')">Start Free Trial</button>
          </div>
  
          <div class="plan-card featured">
            <div class="badge">Most Popular</div>
            <div class="plan-head">
              <div class="plan-icon">U</div>
              <div>
                <h2>Unlimited</h2>
                <p>For multi-store operations and fast growth.</p>
              </div>
            </div>
            <div class="plan-price">
              <span class="price">₱{{ unlimitedPrice }}</span>
              <span class="period">/ {{ billingPeriod }}</span>
            </div>
            <div class="plan-note">
              Billed {{ isYearly ? 'yearly' : 'monthly' }}
              <span v-if="isYearly" class="save-inline">Save ₱{{ unlimitedYearlySavings }}</span>
            </div>
            <div class="plan-features">
              <div v-for="feature in unlimitedFeatures" :key="feature" class="feature-row">
                <span class="check"></span>
                <span>{{ feature }}</span>
              </div>
            </div>
            <button class="btn primary" @click="selectPlan('unlimited')">Start Unlimited Trial</button>
          </div>
        </div>
      </section>
  
      <section class="comparison">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Compare Plans</span>
            <h2 class="section-title">See the difference at a glance.</h2>
          </div>
          <div class="compare-grid">
            <div class="compare-row header">
              <span>Feature</span>
              <span>Simple</span>
              <span>Unlimited</span>
            </div>
            <div v-for="row in comparisonFeatures" :key="row.feature" class="compare-row">
              <span>{{ row.feature }}</span>
              <span class="center">
                <span v-if="row.simple === true" class="check"></span>
                <span v-else>{{ row.simple }}</span>
              </span>
              <span class="center">
                <span v-if="row.unlimited === true" class="check"></span>
                <span v-else>{{ row.unlimited }}</span>
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
  
    <footer class="footer">
      <div class="container footer-grid">
        <div>
          <div class="logo">FurniSync</div>
          <p>Professional furniture management solutions.</p>
        </div>
        <div>
          <div class="footer-title">Explore</div>
          <a href="/">Home</a>
          <a href="/about">About</a>
          <a href="/job-portal">Jobs</a>
          <a href="/shop">Ecommerce</a>
        </div>
        <div>
          <div class="footer-title">Support</div>
          <a href="mailto:support@furnisync.app">support@furnisync.app</a>
          <a href="/customer/login">Customer Login</a>
        </div>
      </div>
      <div class="footer-bottom">� 2026 FurniSync. All rights reserved.</div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import TopNav from '@/Components/TopNav.vue'

const isYearly = ref(false)

const simpleMonthlyPrice = 1490
const simpleYearlyPrice = 14304
const unlimitedMonthlyPrice = 3500
const unlimitedYearlyPrice = 33600

const simplePrice = computed(() => (isYearly.value ? simpleYearlyPrice : simpleMonthlyPrice))
const unlimitedPrice = computed(() => (isYearly.value ? unlimitedYearlyPrice : unlimitedMonthlyPrice))
const billingPeriod = computed(() => (isYearly.value ? 'year' : 'month'))
const unlimitedYearlySavings = computed(() => (unlimitedMonthlyPrice * 12) - unlimitedYearlyPrice)

const simpleFeatures = [
  'Up to 500 furniture items',
  'Basic inventory tracking',
  '2 staff accounts',
  'Email support',
  'Basic reports',
  '1 store location'
]

const unlimitedFeatures = [
  'Unlimited furniture items',
  'Advanced inventory management',
  'Unlimited staff accounts',
  'Priority phone and email support',
  'Advanced analytics and reports',
  'Multiple store locations',
  'Custom API access',
  '3D model integration',
  'Bulk import and export',
  'Custom branding'
]

const comparisonFeatures = ref([
  { feature: 'Furniture Items', simple: 'Up to 500', unlimited: 'Unlimited' },
  { feature: 'Staff Accounts', simple: '2', unlimited: 'Unlimited' },
  { feature: 'Store Locations', simple: '1', unlimited: 'Multiple' },
  { feature: 'Inventory Tracking', simple: true, unlimited: true },
  { feature: 'Sales Analytics', simple: 'Basic', unlimited: 'Advanced' },
  { feature: '3D Model Integration', simple: false, unlimited: true },
  { feature: 'Custom API Access', simple: false, unlimited: true },
  { feature: 'Priority Support', simple: false, unlimited: true },
  { feature: 'Bulk Operations', simple: false, unlimited: true },
  { feature: 'Custom Branding', simple: false, unlimited: true }
])

const faqs = [
  {
    q: 'Can I switch plans anytime?',
    a: 'Yes. You can upgrade or downgrade at any time, and changes apply to the next billing cycle.'
  },
  {
    q: 'Is there a free trial?',
    a: 'Absolutely. Both plans include a 14-day free trial with full access.'
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

const setTrialPlan = (plan: 'simple' | 'unlimited') => {
  localStorage.setItem('trial_plan', plan)
  localStorage.setItem('trial_entry', 'pricing')
}

const selectPlan = (plan: string) => {
  const normalized = plan === 'unlimited' ? 'unlimited' : 'simple'
  setTrialPlan(normalized)
  router.get('/register', { plan: normalized, trial: '1' }, { preserveState: true })
}

const startFreeTrial = () => {
  setTrialPlan('simple')
  router.get('/register', { plan: 'simple', trial: '1' }, { preserveState: true })
}

const scheduleDemo = () => {
  window.open('https://calendly.com/furnisync/demo', '_blank')
}
</script>

<style scoped>
@import url('https://fonts.bunny.net/css?family=manrope:400,500,600,700&family=domine:400,700');

.pricing-shell {
  --ink: #0f172a;
  --muted: #475569;
  --brand: #0f766e;
  --accent: #f59e0b;
  font-family: 'Manrope', sans-serif;
  color: var(--ink);
  background: #f8fafc;
}

.container {
  max-width: 1180px;
  margin: 0 auto;
  padding: 0 24px;
}

.hero {
  padding: 96px 0 64px;
  background: linear-gradient(160deg, #ffffff 0%, #f1f5f9 100%);
  text-align: center;
}

.hero-title {
  font-family: 'Domine', serif;
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
  transition: all 0.2s ease;
}

.btn.primary {
  background: var(--brand);
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

.section-head {
  text-align: center;
  margin-bottom: 32px;
}

.section-title {
  font-family: 'Domine', serif;
  font-size: clamp(2rem, 3vw, 2.6rem);
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

.footer {
  background: #0b1b2b;
  color: #d1d5db;
  padding: 56px 0 32px;
}

.footer-grid {
  display: grid;
  gap: 24px;
}

@media (min-width: 900px) {
  .footer-grid {
    grid-template-columns: 2fr 1fr 1fr;
  }
}

.footer a {
  display: block;
  color: #d1d5db;
  text-decoration: none;
  margin-top: 8px;
}

.footer-title {
  font-weight: 600;
  color: #fff;
}

.logo {
  font-family: 'Domine', serif;
  font-size: 1.4rem;
  color: #fff;
}

.footer-bottom {
  text-align: center;
  margin-top: 24px;
  font-size: 0.85rem;
  color: #94a3b8;
}
</style>
