<template>
  <footer class="relative left-1/2 -mb-6 w-screen -translate-x-1/2 bg-[#f56617] text-white md:-mb-8">
    <div class="mx-auto max-w-7xl px-6 sm:px-8">
      <div class="grid gap-x-8 gap-y-10 py-12 sm:py-14 md:grid-cols-2 lg:grid-cols-12 lg:gap-x-10">
        <div class="lg:col-span-4">
          <Link href="/" class="inline-flex items-center gap-3" aria-label="FurniSync home">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-orange-600 shadow-sm">
              <span class="footer-brand text-xl leading-none">F</span>
            </span>
            <span class="footer-brand text-2xl leading-none tracking-wide">FURNISYNC</span>
          </Link>
          <p class="mt-5 max-w-xs text-sm leading-6 text-white/85">
            A smarter way to move furniture forward, from the showroom to your home.
          </p>
          <p class="mt-5 text-xs font-medium text-white/70">Shop with confidence. Built for better furniture experiences.</p>
        </div>

        <nav class="lg:col-span-2" aria-label="Explore FurniSync">
          <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-white/70">Explore</h2>
          <ul class="mt-5 space-y-3 text-sm">
            <li v-for="link in exploreLinks" :key="link.label">
              <Link :href="link.href" class="text-white/90 transition-colors hover:text-white hover:underline hover:underline-offset-4">{{ link.label }}</Link>
            </li>
          </ul>
        </nav>

        <nav class="lg:col-span-2" aria-label="Opportunities">
          <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-white/70">Opportunities</h2>
          <ul class="mt-5 space-y-3 text-sm">
            <li v-for="link in opportunityLinks" :key="link.label">
              <Link :href="link.href" class="text-white/90 transition-colors hover:text-white hover:underline hover:underline-offset-4">{{ link.label }}</Link>
            </li>
          </ul>
        </nav>

        <div class="border-t border-white/20 pt-7 md:border-t-0 md:pt-0 lg:col-span-4 lg:border-l lg:pl-8">
          <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-white/70">Need help?</h2>
          <p class="mt-5 max-w-sm text-sm leading-6 text-white/90">Questions about an order or your account? Our team is ready to help.</p>
          <div class="mt-5 space-y-3 text-sm">
            <a href="tel:+639123456789" class="flex w-fit items-center gap-3 text-white/90 transition-colors hover:text-white hover:underline hover:underline-offset-4">
              <i class="pi pi-phone text-xs text-white/75" aria-hidden="true"></i>
              <span>+63 912 345 6789</span>
            </a>
            <a href="mailto:info@furnisync.com" class="flex w-fit items-center gap-3 text-white/90 transition-colors hover:text-white hover:underline hover:underline-offset-4">
              <i class="pi pi-envelope text-xs text-white/75" aria-hidden="true"></i>
              <span>info@furnisync.com</span>
            </a>
          </div>
        </div>
      </div>

      <div class="flex flex-col gap-4 border-t border-white/25 py-6 text-xs text-white/75 md:flex-row md:items-center md:justify-between">
        <span>&copy; {{ new Date().getFullYear() }} FurniSync. All rights reserved.</span>
        <nav aria-label="Customer care" class="flex flex-wrap gap-x-5 gap-y-2">
          <Link v-for="link in legalLinks" :key="link.label" :href="link.href" class="transition-colors hover:text-white hover:underline hover:underline-offset-4">{{ link.label }}</Link>
        </nav>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

const exploreLinks = [
  { label: 'Shop', href: '/shop' },
  { label: 'Business', href: '/business' },
  { label: 'Pricing', href: '/business/pricing' },
  { label: 'About', href: '/business/about' },
]

const opportunityLinks = [
  { label: 'Become a Supplier', href: '/supplier/register' },
  { label: 'Find Jobs', href: '/job-portal' },
  { label: 'Admin', href: '/super-admin/login' },
]

const legalLinks = [
  { label: 'Privacy Policy', href: '/legal/privacy-policy' },
  { label: 'Returns & Refunds', href: '/legal/returns-refunds' },
  { label: 'Terms & Conditions', href: '/legal/terms-and-conditions' },
]
</script>

<style scoped>
@font-face {
  font-family: 'Barabara';
  src: url('/fonts/BARABARA-final.otf') format('opentype');
  font-weight: 400;
  font-style: normal;
  font-display: swap;
}

.footer-brand {
  font-family: 'Barabara', sans-serif;
}
</style>
