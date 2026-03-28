<template>
  <div class="marketing-shell">
    <TopNav />
  
    <main>
      <section class="hero">
        <div class="container">
          <div class="hero-grid">
            <div class="hero-copy">
              <div class="pill">AI + 3D for modern furniture teams</div>
              <h1 class="hero-title">
                Run every store with
                <span class="hero-accent">clarity, speed, and visual confidence.</span>
              </h1>
              <p class="hero-sub">
                FurniSync connects inventory, procurement, and delivery in one place, while 3D previews and a decision
                support engine keep your stock smart and your sales fast.
              </p>
              <div class="hero-actions">
                <Link href="/register?plan=simple&trial=1" class="btn primary" @click="setTrialPlan('simple')">Start Free Trial</Link>
                <button class="btn ghost" @click="scrollToOverview">See How It Works</button>
              </div>
              <div class="hero-meta">
                <span>14-day trial</span>
                <span></span>
                <span>No credit card</span>
                <span></span>
                <span>Live onboarding</span>
              </div>
            </div>
  
            <div class="hero-frame">
              <div class="frame-header">
                <span class="dot red"></span>
                <span class="dot amber"></span>
                <span class="dot green"></span>
                <span class="frame-title">3D Product Studio</span>
              </div>
              <div class="frame-body">
                <div class="chair">
                  <div class="chair-core"></div>
                  <div class="chair-surface"></div>
                  <div class="chair-shadow"></div>
                </div>
                <div class="frame-stats">
                  <div>
                    <div class="stat-title">Stock Health</div>
                    <div class="stat-value">92%</div>
                  </div>
                  <div>
                    <div class="stat-title">Forecast Accuracy</div>
                    <div class="stat-value">95%</div>
                  </div>
                  <div>
                    <div class="stat-title">Reorder Alerts</div>
                    <div class="stat-value">8</div>
                  </div>
                </div>
              </div>
              <div class="frame-footer">
                <button class="chip">Rotate</button>
                <button class="chip">Zoom</button>
                <button class="chip">Materials</button>
              </div>
            </div>
          </div>
        </div>
      </section>
  
      <section class="trust">
        <div class="container trust-grid">
          <div>
            <h2 class="section-title">Everything your teams need to act faster.</h2>
            <p class="section-sub">
              Centralize warehouse visibility, automate reorders, and deliver a showroom-quality shopping experience in
              every store.
            </p>
          </div>
          <div class="metrics">
            <div class="metric">
              <div class="metric-value">45%</div>
              <div class="metric-label">Fewer stockouts</div>
            </div>
            <div class="metric">
              <div class="metric-value">3.2x</div>
              <div class="metric-label">Faster PO cycles</div>
            </div>
            <div class="metric">
              <div class="metric-value">28%</div>
              <div class="metric-label">Higher sell-through</div>
            </div>
          </div>
        </div>
      </section>
  
      <section ref="overviewSection" class="features">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Platform Overview</span>
            <h2 class="section-title">One intelligent system. Six confident teams.</h2>
            <p class="section-sub">From merchandising to logistics, everyone sees the same truth � updated in real time.
            </p>
          </div>
          <div class="feature-grid">
            <div v-for="feature in features" :key="feature.title" class="feature-card">
              <div class="icon-wrap" v-html="feature.icon"></div>
              <h3>{{ feature.title }}</h3>
              <p>{{ feature.description }}</p>
            </div>
          </div>
        </div>
      </section>
  
      <section class="dss">
        <div class="container dss-grid">
          <div>
            <span class="eyebrow">Decision Support</span>
            <h2 class="section-title">Let data make the next move.</h2>
            <p class="section-sub">
              The DSS engine blends sales velocity, seasonality, and supplier lead times to recommend the right action at
              the right time.
            </p>
            <div class="dss-list">
              <div class="dss-item">Predictive demand models trained on your store history.</div>
              <div class="dss-item">Auto-generated reorder plans with budget checks.</div>
              <div class="dss-item">Scenario planning for promos and clearance cycles.</div>
            </div>
            <Link href="/pricing" class="btn primary">Explore Plans</Link>
          </div>
          <div class="dss-panel">
            <h3>Live Recommendations</h3>
            <div class="panel-row">
              <span>ErgoFlex Chair</span>
              <span class="badge warn">Reorder 120</span>
            </div>
            <div class="panel-row">
              <span>Oakline Table</span>
              <span class="badge ok">Healthy Stock</span>
            </div>
            <div class="panel-row">
              <span>Studio Sofa</span>
              <span class="badge warn">Promote Now</span>
            </div>
            <div class="panel-foot">
              Updated 2 minutes ago
            </div>
          </div>
        </div>
      </section>
  
      <section class="cta">
        <div class="container cta-card">
          <div>
            <h2>Ready to see FurniSync in action?</h2>
            <p>Launch a trial and let our team map your current workflow into a smarter, faster system.</p>
          </div>
          <div class="cta-actions">
            <Link href="/register?plan=simple&trial=1" class="btn primary" @click="setTrialPlan('simple')">Start Free Trial</Link>
            <Link href="/pricing" class="btn ghost">View Pricing</Link>
          </div>
        </div>
      </section>
    </main>
  
    <footer class="footer">
      <div class="container footer-grid">
        <div>
          <div class="logo">FurniSync</div>
          <p>Modern furniture operations powered by AI, 3D, and human-friendly workflows.</p>
        </div>
        <div>
          <div class="footer-title">Platform</div>
          <a href="/pricing">Pricing</a>
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
import TopNav from '@/Components/TopNav.vue'
import { Link } from '@inertiajs/vue3'
import { ref } from 'vue'

const overviewSection = ref<HTMLElement | null>(null)

const scrollToOverview = () => {
  overviewSection.value?.scrollIntoView({ behavior: 'smooth' })
}

const setTrialPlan = (plan: 'simple' | 'unlimited') => {
  localStorage.setItem('trial_plan', plan)
  localStorage.setItem('trial_entry', 'marketing')
}

const features = [
  {
    title: '3D Catalog Studio',
    description: 'Spin, zoom, and showcase products with photorealistic material control.',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 3l8 4-8 4-8-4 8-4z"/><path d="M4 11l8 4 8-4"/><path d="M4 19l8 4 8-4"/></svg>'
  },
  {
    title: 'Inventory Clarity',
    description: 'Live stock levels across branches, warehouses, and storefronts.',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 7h18"/><path d="M5 7v10a2 2 0 002 2h10a2 2 0 002-2V7"/><path d="M9 11h6"/></svg>'
  },
  {
    title: 'Smart Procurement',
    description: 'Automate POs, supplier selection, and lead-time tracking.',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M6 3h12l3 6H3l3-6z"/><path d="M4 9v10a2 2 0 002 2h12a2 2 0 002-2V9"/></svg>'
  },
  {
    title: 'Sales Enablement',
    description: 'Give teams instant product knowledge and recommended bundles.',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 12h18"/><path d="M12 3v18"/></svg>'
  },
  {
    title: 'Logistics Control',
    description: 'Track deliveries, routing, and customer updates in real time.',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 7h13v10H3z"/><path d="M16 9h5l-2 5h-3z"/><circle cx="7" cy="19" r="2"/><circle cx="17" cy="19" r="2"/></svg>'
  },
  {
    title: 'Decision Support',
    description: 'AI-driven guidance for markdowns, replenishment, and promo timing.',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 2l7 4v6c0 5-3 9-7 10-4-1-7-5-7-10V6l7-4z"/></svg>'
  }
]
</script>

<style scoped>
@import url('https://fonts.bunny.net/css?family=manrope:400,500,600,700&family=domine:400,700');

.marketing-shell {
  --ink: #0b1b2b;
  --muted: #4b5b6b;
  --brand: #0f766e;
  --accent: #f59e0b;
  --paper: #f6f5f2;
  font-family: 'Manrope', sans-serif;
  color: var(--ink);
  background: var(--paper);
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}

.hero {
  padding: 96px 0 72px;
  background: radial-gradient(circle at top right, rgba(15, 118, 110, 0.15), transparent 55%),
    linear-gradient(180deg, #ffffff 0%, #f7f4ef 100%);
}

.hero-grid {
  display: grid;
  gap: 48px;
  align-items: center;
}

@media (min-width: 1024px) {
  .hero-grid {
    grid-template-columns: 1.1fr 0.9fr;
  }
}

.pill {
  display: inline-flex;
  padding: 6px 14px;
  border-radius: 999px;
  background: rgba(15, 118, 110, 0.12);
  color: var(--brand);
  font-weight: 600;
  font-size: 0.85rem;
}

.hero-title {
  font-family: 'Domine', serif;
  font-size: clamp(2.6rem, 4vw, 3.8rem);
  margin: 20px 0;
}

.hero-accent {
  color: var(--brand);
}

.hero-sub {
  font-size: 1.1rem;
  color: var(--muted);
  max-width: 540px;
}

.hero-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  margin: 28px 0 16px;
}

.btn {
  padding: 14px 26px;
  border-radius: 14px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  transition: all 0.2s ease;
  border: 1px solid transparent;
}

.btn.primary {
  background: var(--brand);
  color: #fff;
  box-shadow: 0 12px 24px rgba(15, 118, 110, 0.24);
}

.btn.primary:hover {
  transform: translateY(-2px);
}

.btn.ghost {
  border-color: rgba(15, 118, 110, 0.4);
  color: var(--brand);
  background: #fff;
}

.hero-meta {
  display: flex;
  gap: 10px;
  color: var(--muted);
  font-size: 0.9rem;
  flex-wrap: wrap;
}

.hero-frame {
  background: #111827;
  border-radius: 24px;
  padding: 24px;
  color: #f8fafc;
  box-shadow: 0 32px 60px rgba(15, 23, 42, 0.3);
}

.frame-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 18px;
}

.frame-title {
  margin-left: 8px;
  font-size: 0.9rem;
  color: #cbd5f5;
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
}

.dot.red {
  background: #f87171;
}

.dot.amber {
  background: #fbbf24;
}

.dot.green {
  background: #34d399;
}

.frame-body {
  background: linear-gradient(145deg, #1f2937, #0f172a);
  border-radius: 18px;
  padding: 28px;
}

.chair {
  position: relative;
  height: 180px;
  display: grid;
  place-items: center;
}

.chair-core {
  width: 120px;
  height: 120px;
  background: linear-gradient(135deg, #0f766e, #38bdf8);
  border-radius: 24px;
  transform: rotate(45deg);
}

.chair-surface {
  position: absolute;
  width: 90px;
  height: 90px;
  background: rgba(255, 255, 255, 0.7);
  border-radius: 18px;
  transform: rotate(45deg);
}

.chair-shadow {
  position: absolute;
  bottom: 8px;
  width: 140px;
  height: 16px;
  background: rgba(15, 23, 42, 0.6);
  filter: blur(10px);
  border-radius: 999px;
}

.frame-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-top: 18px;
  text-align: center;
}

.stat-title {
  font-size: 0.7rem;
  color: #94a3b8;
}

.stat-value {
  font-size: 1.1rem;
  font-weight: 600;
}

.frame-footer {
  display: flex;
  gap: 8px;
  margin-top: 16px;
}

.chip {
  background: #1f2937;
  color: #e2e8f0;
  border: none;
  padding: 6px 12px;
  border-radius: 999px;
  font-size: 0.75rem;
}

.trust {
  padding: 64px 0;
  background: #fff;
}

.trust-grid {
  display: grid;
  gap: 32px;
  align-items: center;
}

@media (min-width: 900px) {
  .trust-grid {
    grid-template-columns: 1fr 1fr;
  }
}

.section-title {
  font-family: 'Domine', serif;
  font-size: clamp(2rem, 3vw, 2.6rem);
}

.section-sub {
  color: var(--muted);
  margin-top: 12px;
  font-size: 1rem;
}

.metrics {
  display: grid;
  gap: 16px;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
}

.metric {
  padding: 18px;
  border-radius: 16px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}

.metric-value {
  font-size: 1.6rem;
  font-weight: 700;
  color: var(--brand);
}

.features {
  padding: 84px 0;
}

.section-head {
  text-align: center;
  max-width: 720px;
  margin: 0 auto 40px;
}

.eyebrow {
  text-transform: uppercase;
  letter-spacing: 2px;
  font-size: 0.75rem;
  color: var(--brand);
}

.feature-grid {
  display: grid;
  gap: 20px;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.feature-card {
  background: #fff;
  border-radius: 18px;
  padding: 24px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06);
}

.feature-card h3 {
  margin-top: 14px;
  font-weight: 700;
}

.feature-card p {
  color: var(--muted);
  margin-top: 8px;
}

.icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: rgba(15, 118, 110, 0.12);
  color: var(--brand);
  display: grid;
  place-items: center;
}

.dss {
  padding: 86px 0;
  background: linear-gradient(140deg, rgba(15, 118, 110, 0.08), rgba(245, 158, 11, 0.12));
}

.dss-grid {
  display: grid;
  gap: 36px;
  align-items: center;
}

@media (min-width: 900px) {
  .dss-grid {
    grid-template-columns: 1.1fr 0.9fr;
  }
}

.dss-list {
  display: grid;
  gap: 12px;
  margin: 20px 0 28px;
}

.dss-item {
  padding: 14px 18px;
  border-radius: 14px;
  background: #fff;
  border: 1px solid #e2e8f0;
}

.dss-panel {
  background: #0f172a;
  color: #e2e8f0;
  border-radius: 22px;
  padding: 26px;
}

.panel-row {
  display: flex;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid rgba(148, 163, 184, 0.2);
}

.panel-foot {
  margin-top: 16px;
  font-size: 0.8rem;
  color: #94a3b8;
}

.badge {
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.badge.warn {
  background: rgba(245, 158, 11, 0.2);
  color: #fbbf24;
}

.badge.ok {
  background: rgba(34, 197, 94, 0.2);
  color: #4ade80;
}

.cta {
  padding: 80px 0;
}

.cta-card {
  background: #fff;
  border-radius: 24px;
  padding: 32px;
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
  display: grid;
  gap: 20px;
  align-items: center;
}

@media (min-width: 900px) {
  .cta-card {
    grid-template-columns: 1.2fr 0.8fr;
  }
}

.cta-actions {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
  justify-content: flex-start;
}

.footer {
  background: #0b1b2b;
  color: #d1d5db;
  padding: 56px 0 32px;
}

.footer-grid {
  display: grid;
  gap: 28px;
}

@media (min-width: 900px) {
  .footer-grid {
    grid-template-columns: 2fr 1fr 1fr;
  }
}

.footer a {
  display: block;
  color: #d1d5db;
  margin-top: 8px;
  text-decoration: none;
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
  margin-top: 28px;
  font-size: 0.85rem;
  color: #94a3b8;
}
</style>
