<template>
  <footer class="footer">
    <div class="container footer-grid">
      <div>
        <Link  class="brand-logo" href="/super-admin/login">FURNISYNC</Link>
        <p>Modern furniture operations powered by 3D, and human-friendly workflows.</p>
      </div>
      <div>
        <div class="footer-title">Explore</div>
        <Link href="/">Home</Link>
        <Link href="/pricing">Pricing</Link>
        <Link href="/about">About</Link>
        <a href="/job-portal">Jobs</a>
        <a href="/shop">Ecommerce</a>
      </div>
      <div>
        <div class="footer-title">Support</div>
        <a href="mailto:support@furnisync.app">support@furnisync.app</a>
        <Link href="/customer/login">Customer Login</Link>
      </div>
    </div>
    <div class="footer-bottom">2026 FurniSync. All rights reserved.</div>
  </footer>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
</script>

<style scoped>
@font-face {
  font-family: 'Barabara';
  src: url('../../fonts/BARABARA-final.otf') format('opentype');
  font-weight: 400;
  font-style: normal;
  font-display: swap;
}

.footer {
  background: #0b1b2b;
  color: #d1d5db;
  padding: 56px 0 32px;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
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

.brand-logo {
  font-family: 'Barabara';
  font-size: 1.4rem;
  color: #fff;
}

.footer-bottom {
  text-align: center;
  margin-top: 28px;
  font-size: 0.85rem;
  color: #94a3b8;
}

.staff-link {
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.18em;
  color: #94a3b8;
}
</style>
