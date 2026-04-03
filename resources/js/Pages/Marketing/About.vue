<template>
  <div class="bg-gradient-to-b from-white to-orange-50/30 text-slate-900">
    <TopNav />

    <main>
      <section class="relative overflow-hidden py-20 lg:py-28">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(245,158,107,0.18),transparent_55%)]"></div>
        <div class="mx-auto max-w-6xl px-4 lg:px-8">
          <div class="grid gap-10 lg:grid-cols-2 items-center">
            <div>
              <h1 class=" text-4xl lg:text-5xl font-bold leading-tight">
                We build the operating system for furniture businesses.
              </h1>
              <p class="mt-5 text-base lg:text-lg text-slate-600 max-w-xl">
                From inventory intelligence to immersive 3D retail, our mission is to help teams ship faster, sell smarter, and serve every customer with confidence.
              </p>
              <div class="mt-8 flex flex-wrap gap-3">
                <Link href="/pricing" class="inline-flex items-center justify-center rounded-full bg-orange-500 px-6 py-3 text-white font-semibold shadow-sm hover:bg-orange-600">See Pricing</Link>
                <Link href="/register?plan=simple&trial=1" class="inline-flex items-center justify-center rounded-full border border-orange-300 px-6 py-3 text-orange-600 font-semibold bg-white hover:bg-orange-50" @click="setTrialPlan('simple')">Start Trial</Link>
              </div>
            </div>
            <div class="grid gap-4">
              <div class="rounded-2xl border border-orange-100 bg-white/90 p-5 shadow-lg">
                <h3 class="font-semibold text-slate-900 mb-2">Why we exist</h3>
                <p class="text-sm text-slate-600">
                  Furniture operations are complex, but most tools are built for generic retail. We designed FurniSync specifically for bulky inventory, long lead times, and showroom-first selling.
                </p>
              </div>
              <div class="rounded-2xl border border-orange-100 bg-white/90 p-5 shadow-lg">
                <h3 class="font-semibold text-slate-900 mb-2">What we deliver</h3>
                <p class="text-sm text-slate-600">
                  A single platform that unifies merchandising, procurement, logistics, and finance while keeping your customer experience premium.
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="py-20">
        <div class="mx-auto max-w-6xl px-4 lg:px-8">
          <div class="grid gap-10 lg:grid-cols-2 items-center">
            <div>
              <h2 class="text-3xl lg:text-4xl font-bold mb-4">A smarter backbone for every store.</h2>
              <p class="text-slate-600 mb-6">
                We partnered with store managers, warehouse leads, and purchasing teams to map out the real workflows that keep furniture businesses moving. The result is a product that replaces spreadsheets, siloed apps, and guesswork with a unified control center.
              </p>
              <div class="grid gap-3">
                <div class="rounded-xl border border-orange-100 bg-white px-4 py-3 text-sm text-slate-700">Operational clarity with real-time inventory truth.</div>
                <div class="rounded-xl border border-orange-100 bg-white px-4 py-3 text-sm text-slate-700">Decision support that balances demand, cash flow, and lead time.</div>
                <div class="rounded-xl border border-orange-100 bg-white px-4 py-3 text-sm text-slate-700">3D experiences that help shoppers commit faster.</div>
              </div>
            </div>
            <div class="rounded-2xl bg-slate-900 text-white p-6 shadow-xl">
              <div class="border-b border-slate-700 pb-4 mb-4">
                <p class="text-2xl font-bold">8+</p>
                <p class="text-sm text-slate-300">Core modules connected</p>
              </div>
              <div class="border-b border-slate-700 pb-4 mb-4">
                <p class="text-2xl font-bold">120 hrs</p>
                <p class="text-sm text-slate-300">Monthly ops time saved</p>
              </div>
              <div>
                <p class="text-2xl font-bold">18%</p>
                <p class="text-sm text-slate-300">Lower holding costs</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="py-20 bg-white">
        <div class="mx-auto max-w-6xl px-4 lg:px-8">
          <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="text-orange-500 text-sm uppercase tracking-wider">Our Principles</span>
            <h2 class="text-3xl lg:text-4xl font-bold mt-3 mb-4">Designed with operators, not just developers.</h2>
            <p class="text-slate-600">Every feature is tested against a real store workflow before it ships.</p>
          </div>
          <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm">
              <h3 class="font-semibold text-slate-900 mb-2">Showroom-first UX</h3>
              <p class="text-sm text-slate-600">Retail teams can sell faster because the right data appears exactly when they need it.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm">
              <h3 class="font-semibold text-slate-900 mb-2">Operational transparency</h3>
              <p class="text-sm text-slate-600">Procurement, logistics, and finance see the same numbers, reducing delays and disputes.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm">
              <h3 class="font-semibold text-slate-900 mb-2">Automation with control</h3>
              <p class="text-sm text-slate-600">Smart suggestions, human approvals. You decide when the system acts.</p>
            </div>
          </div>
        </div>
      </section>

      <section class="py-16">
        <div class="mx-auto max-w-6xl px-4 lg:px-8">
          <div class="grid gap-6 rounded-3xl border border-orange-100 bg-white p-8 shadow-xl lg:grid-cols-2 items-center">
            <div>
              <h2 class="text-3xl font-bold mb-3">Ready to build the next generation of your business?</h2>
              <p class="text-slate-600">Let us walk you through a tailored demo of FurniSync in action.</p>
            </div>
            <div class="flex flex-wrap gap-3 justify-start lg:justify-end">
              <Link href="/register" class="inline-flex items-center justify-center rounded-full bg-orange-500 px-6 py-3 text-white font-semibold shadow-sm hover:bg-orange-600">Book a Demo</Link>
              <Link href="/" class="inline-flex items-center justify-center rounded-full border border-orange-300 px-6 py-3 text-orange-600 font-semibold bg-white hover:bg-orange-50">Back to Home</Link>
            </div>
          </div>
        </div>
      </section>
    </main>

    <MarketingFooter />
  </div>
</template>

<script setup lang="ts">
import TopNav from '@/Components/MarketingHeader.vue'
import MarketingFooter from '@/Components/MarketingFooter.vue'
import { Link } from '@inertiajs/vue3'

const setTrialPlan = (plan: 'simple' | 'unlimited') => {
  localStorage.setItem('trial_plan', plan)
  localStorage.setItem('trial_entry', 'marketing')
}
</script>

<style scoped>
@import url('https://fonts.bunny.net/css?family=manrope:400,500,600,700&family=plus-jakarta-sans:600,700,800');

* {
  font-family: 'Manrope', sans-serif;
}

</style>
