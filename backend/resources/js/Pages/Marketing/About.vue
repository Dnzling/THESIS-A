<template>
  <div class="about-shell">
    <TopNav />

    <main>
      <section class="hero">
        <div class="container hero-grid">
          <div>
            <span class="eyebrow">About FurniSync</span>
            <h1 class="hero-title">We build the operating system for furniture businesses.</h1>
            <p class="hero-sub">
              From inventory intelligence to immersive 3D retail, our mission is to help teams ship faster, sell smarter, and serve every customer with confidence.
            </p>
            <div class="hero-actions">
              <Link href="/pricing" class="btn primary">See Pricing</Link>
              <Link href="/register" class="btn ghost">Start Trial</Link>
            </div>
          </div>
          <div class="hero-panel">
            <div class="panel-card">
              <h3>Why we exist</h3>
              <p>
                Furniture operations are complex, but most tools are built for generic retail. We designed FurniSync specifically for bulky inventory, long lead times, and showroom-first selling.
              </p>
            </div>
            <div class="panel-card">
              <h3>What we deliver</h3>
              <p>
                A single platform that unifies merchandising, procurement, logistics, and finance while keeping your customer experience premium.
              </p>
            </div>
          </div>
        </div>
      </section>

      <section class="story">
        <div class="container story-grid">
          <div>
            <h2 class="section-title">A smarter backbone for every store.</h2>
            <p class="section-sub">
              We partnered with store managers, warehouse leads, and purchasing teams to map out the real workflows that keep furniture businesses moving. The result is a product that replaces spreadsheets, siloed apps, and guesswork with a unified control center.
            </p>
            <div class="value-list">
              <div class="value-item">Operational clarity with real-time inventory truth.</div>
              <div class="value-item">Decision support that balances demand, cash flow, and lead time.</div>
              <div class="value-item">3D experiences that help shoppers commit faster.</div>
            </div>
          </div>
          <div class="story-card">
            <div class="story-metric">
              <div class="metric-value">8+</div>
              <div class="metric-label">Core modules connected</div>
            </div>
            <div class="story-metric">
              <div class="metric-value">120 hrs</div>
              <div class="metric-label">Monthly ops time saved</div>
            </div>
            <div class="story-metric">
              <div class="metric-value">18%</div>
              <div class="metric-label">Lower holding costs</div>
            </div>
          </div>
        </div>
      </section>

      <section class="principles">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Our Principles</span>
            <h2 class="section-title">Designed with operators, not just developers.</h2>
            <p class="section-sub">Every feature is tested against a real store workflow before it ships.</p>
          </div>
          <div class="principle-grid">
            <div class="principle-card">
              <h3>Showroom-first UX</h3>
              <p>Retail teams can sell faster because the right data appears exactly when they need it.</p>
            </div>
            <div class="principle-card">
              <h3>Operational transparency</h3>
              <p>Procurement, logistics, and finance see the same numbers, reducing delays and disputes.</p>
            </div>
            <div class="principle-card">
              <h3>Automation with control</h3>
              <p>Smart suggestions, human approvals. You decide when the system acts.</p>
            </div>
          </div>
        </div>
      </section>

      <section class="cta">
        <div class="container cta-card">
          <div>
            <h2>Ready to build the next generation of your business?</h2>
            <p>Let us walk you through a tailored demo of FurniSync in action.</p>
          </div>
          <div class="cta-actions">
            <Link href="/register" class="btn primary">Book a Demo</Link>
            <Link href="/" class="btn ghost">Back to Home</Link>
          </div>
        </div>
      </section>
    </main>

    <footer class="footer">
      <div class="container">
        <div class="footer-grid">
          <div>
            <div class="logo">FurniSync</div>
            <p>Furniture-first operations, powered by intelligent automation.</p>
          </div>
          <div>
            <div class="footer-title">Explore</div>
            <a href="/pricing">Pricing</a>
            <a href="/about">About</a>
             <a href="/job-portal">Jobs</a>
            <a href="/shop">Ecommerce</a>
          </div>
          <div>
            <div class="footer-title">Contact</div>
            <a href="mailto:support@furnisync.app">support@furnisync.app</a>
            <a href="/customer/login">Customer Login</a>
          </div>
        </div>
        <div class="footer-bottom">� 2026 FurniSync. All rights reserved.</div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import TopNav from '@/Components/TopNav.vue'
import { Link } from '@inertiajs/vue3'
</script>

<style scoped>
@import url('https://fonts.bunny.net/css?family=manrope:400,500,600,700&family=domine:400,700');

.about-shell {
  --ink: #111827;
  --muted: #495665;
  --brand: #0f766e;
  --paper: #f8f4ee;
  font-family: 'Manrope', sans-serif;
  color: var(--ink);
  background: var(--paper);
}

.container {
  max-width: 1160px;
  margin: 0 auto;
  padding: 0 24px;
}

.hero {
  padding: 96px 0 72px;
  background: linear-gradient(160deg, #ffffff 0%, #f1f5f9 100%);
}

.hero-grid {
  display: grid;
  gap: 40px;
  align-items: center;
}

@media (min-width: 900px) {
  .hero-grid {
    grid-template-columns: 1.1fr 0.9fr;
  }
}

.hero-title {
  font-family: 'Domine', serif;
  font-size: clamp(2.4rem, 4vw, 3.6rem);
  margin: 16px 0;
}

.hero-sub {
  color: var(--muted);
  font-size: 1.1rem;
  max-width: 560px;
}

.hero-actions {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
  margin-top: 24px;
}

.hero-panel {
  display: grid;
  gap: 18px;
}

.panel-card {
  background: #fff;
  border-radius: 18px;
  padding: 22px;
  box-shadow: 0 18px 36px rgba(15, 23, 42, 0.08);
  border: 1px solid #e2e8f0;
}

.eyebrow {
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 2px;
  color: var(--brand);
}

.section-title {
  font-family: 'Domine', serif;
  font-size: clamp(2rem, 3vw, 2.6rem);
}

.section-sub {
  margin-top: 12px;
  color: var(--muted);
}

.story {
  padding: 84px 0;
}

.story-grid {
  display: grid;
  gap: 32px;
}

@media (min-width: 900px) {
  .story-grid {
    grid-template-columns: 1.2fr 0.8fr;
  }
}

.value-list {
  display: grid;
  gap: 12px;
  margin-top: 20px;
}

.value-item {
  padding: 14px 16px;
  background: #fff;
  border-radius: 14px;
  border: 1px solid #e2e8f0;
}

.story-card {
  background: #0f172a;
  color: #e2e8f0;
  border-radius: 22px;
  padding: 24px;
  display: grid;
  gap: 20px;
}

.story-metric {
  border-bottom: 1px solid rgba(148, 163, 184, 0.2);
  padding-bottom: 16px;
}

.story-metric:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.metric-value {
  font-size: 1.8rem;
  font-weight: 700;
}

.metric-label {
  color: #94a3b8;
  font-size: 0.9rem;
}

.principles {
  padding: 84px 0;
  background: #fff;
}

.section-head {
  text-align: center;
  max-width: 680px;
  margin: 0 auto 36px;
}

.principle-grid {
  display: grid;
  gap: 18px;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.principle-card {
  padding: 20px;
  border-radius: 16px;
  border: 1px solid #e5e7eb;
  background: #f8fafc;
}

.btn {
  padding: 12px 24px;
  border-radius: 14px;
  font-weight: 600;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid transparent;
  transition: all 0.2s ease;
}

.btn.primary {
  background: var(--brand);
  color: #fff;
  box-shadow: 0 12px 24px rgba(15, 118, 110, 0.22);
}

.btn.ghost {
  border-color: rgba(15, 118, 110, 0.4);
  color: var(--brand);
  background: #fff;
}

.cta {
  padding: 72px 0;
}

.cta-card {
  background: #fff;
  border-radius: 22px;
  padding: 30px;
  display: grid;
  gap: 20px;
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.1);
}

@media (min-width: 900px) {
  .cta-card {
    grid-template-columns: 1.2fr 0.8fr;
    align-items: center;
  }
}

.cta-actions {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
}

.footer {
  padding: 56px 0 32px;
  background: #0b1b2b;
  color: #d1d5db;
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
  margin-top: 8px;
  color: #d1d5db;
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
  margin-top: 24px;
  font-size: 0.85rem;
  color: #94a3b8;
}
</style>
