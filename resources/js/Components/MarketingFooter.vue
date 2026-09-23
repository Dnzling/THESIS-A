<template>
  <footer class="relative left-1/2 -mb-6 w-screen -translate-x-1/2 overflow-hidden bg-[#f56617] px-6 py-12 text-white md:-mb-8 sm:px-8 lg:py-14">
    <div class="pointer-events-none absolute -right-24 -top-32 h-80 w-80 rounded-full bg-orange-300/20 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-40 left-1/3 h-72 w-72 rounded-full bg-red-700/10 blur-3xl"></div>
    <div class="relative mx-auto max-w-7xl">
      <div class="space-y-10">
        <div class="max-w-3xl">
          <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-xl font-black text-orange-500 shadow-lg shadow-orange-900/10">F</div>
            <div>
              <div class="text-xl font-black tracking-tight">FurniSync</div>
              <p class="mt-0.5 text-xs text-orange-100">A smarter way to move furniture forward.</p>
            </div>
          </div>

          <div class="mt-9">
            <div class="text-xs font-bold uppercase tracking-[0.18em] text-orange-100">Explore FurniSync</div>
            <div class="mt-4 grid grid-cols-2 gap-x-8 gap-y-3 text-sm sm:grid-cols-3">
              <Link v-for="link in mainLinks" :key="link.label" :href="link.href" class="group flex items-center gap-2 text-white/90 transition hover:text-white">
                <span class="h-1 w-1 rounded-full bg-orange-200 transition group-hover:scale-150"></span>{{ link.label }}
              </Link>
            </div>
          </div>

          <div class="mt-8 border-t border-white/20 pt-6">
            <div class="text-xs font-bold uppercase tracking-[0.18em] text-orange-100">Customer care</div>
            <div class="mt-4 flex flex-wrap gap-x-6 gap-y-3 text-sm">
              <Link v-for="link in legalLinks" :key="link.label" :href="link.href" class="text-white/80 underline-offset-4 transition hover:text-white hover:underline">{{ link.label }}</Link>
            </div>
          </div>
        </div>

        <div class="w-full">
          <div class="rounded-3xl border border-white/20 bg-white/10 p-6 shadow-xl shadow-orange-900/10 backdrop-blur-sm">
            <div class="flex items-center gap-2 text-lg font-bold"><i class="pi pi-comments text-orange-100"></i> Contact Us</div>
            <p class="mt-2 text-sm leading-6 text-orange-50">Need help with your order or account? We’re happy to help.</p>
            <div class="mt-5 space-y-3 text-sm">
              <a href="tel:+639123456789" class="flex items-center gap-3 text-white transition hover:text-orange-100"><i class="pi pi-phone text-orange-100"></i><span>+63 912 345 6789</span></a>
              <a href="mailto:info@furnisync.com" class="flex items-center gap-3 text-white transition hover:text-orange-100"><i class="pi pi-envelope text-orange-100"></i><span>info@furnisync.com</span></a>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-10 flex flex-col gap-3 border-t border-white/20 pt-5 text-xs text-orange-100 sm:flex-row sm:items-center sm:justify-between">
        <span>© 2026 FurniSync. All rights reserved.</span>
        <span class="flex items-center gap-2"><i class="pi pi-shield"></i> Shop with confidence</span>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

const mainLinks = [
  { label: 'Business', href: '/business' },
  { label: 'Pricing', href: '/business/pricing' },
  { label: 'About', href: '/business/about' },
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
