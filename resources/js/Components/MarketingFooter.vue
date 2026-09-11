<template>
  <footer class="relative left-1/2 -mb-6 w-screen -translate-x-1/2 bg-orange-500 px-6 py-10 text-white sm:px-8 md:-mb-8">
    <div class="mx-auto flex max-w-7xl flex-wrap items-start justify-between gap-10">
      <div>
        <div class="text-lg font-bold text-white">Quick Links</div>
        <div class="mt-3 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
          <Link href="/business" class="text-white transition hover:text-orange-100">Business</Link>
          <Link href="/pricing" class="text-white transition hover:text-orange-100">Pricing</Link>
          <Link href="/about" class="text-white transition hover:text-orange-100">About</Link>
          <Link href="/supplier/register" class="text-white transition hover:text-orange-100">Become a Supplier</Link>
          <Link href="/job-portal" class="text-white transition hover:text-orange-100">Find Jobs</Link>
          <Link href="/super-admin/login" class="text-white transition hover:text-orange-100">Admin</Link>
        </div>
      </div>
      <div>
        <div class="text-sm font-semibold text-white">Support</div>
      </div>
    </div>
    <div class="mx-auto mt-8 max-w-7xl border-t border-white/25 pt-5 text-center text-xs text-orange-50">2026 FurniSync. All rights reserved.</div>
  </footer>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
</script>
