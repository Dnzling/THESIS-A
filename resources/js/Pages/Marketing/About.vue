<template>
  <Head title="About" />
  <div class="min-h-screen bg-gradient-to-b from-white via-orange-50/20 to-slate-50 text-slate-900">
    <TopNav />

    <main>
      <section class="relative overflow-hidden py-20 lg:py-28">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(245,158,107,0.18),transparent_55%)]"></div>
        <div class="mx-auto grid max-w-7xl gap-10 px-4 lg:grid-cols-2 lg:items-center lg:px-8">
          <div class="relative">
           
            <h1 class="mt-5 max-w-2xl text-4xl font-bold leading-tight sm:text-5xl">
              We build the operating system for furniture businesses.
            </h1>
            <p class="mt-5 max-w-xl text-lg text-slate-600">
              From inventory intelligence to immersive 3D retail, our mission is to help teams ship faster, sell smarter, and serve every customer with confidence.
            </p>
           
          </div>

          <div class="grid gap-4">
            <div class="rounded-3xl border border-orange-100 bg-white/90 p-6 shadow-xl shadow-orange-100/40 backdrop-blur">
              <h3 class="mb-2 text-lg font-semibold">Why we exist</h3>
              <p class="text-sm leading-6 text-slate-600">
                Furniture operations are complex, but most tools are built for generic retail. We designed FurniSync specifically for bulky inventory, long lead times, and showroom-first selling.
              </p>
            </div>
            <div class="rounded-3xl border border-orange-100 bg-white/90 p-6 shadow-xl shadow-orange-100/40 backdrop-blur">
              <h3 class="mb-2 text-lg font-semibold">What we deliver</h3>
              <p class="text-sm leading-6 text-slate-600">
                A single platform that unifies merchandising, procurement, logistics, and finance while keeping your customer experience premium.
              </p>
            </div>
          </div>
        </div>
      </section>

      <section class="py-20">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 lg:grid-cols-2 lg:items-center lg:px-8">
          <div>
            <h2 class="text-3xl font-bold sm:text-4xl">A smarter backbone for every store.</h2>
            <p class="mt-4 max-w-2xl text-slate-600">
              We partnered with store managers, warehouse leads, and purchasing teams to map out the real workflows that keep furniture businesses moving. The result is a product that replaces spreadsheets, siloed apps, and guesswork with a unified control center.
            </p>
            <div class="mt-6 grid gap-3">
              <div class="rounded-2xl border border-orange-100 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                Operational clarity with real-time inventory truth.
              </div>
              <div class="rounded-2xl border border-orange-100 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                Decision support that balances demand, cash flow, and lead time.
              </div>
              <div class="rounded-2xl border border-orange-100 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                3D experiences that help shoppers commit faster.
              </div>
            </div>
          </div>

          <div class="rounded-3xl bg-slate-900 p-8 text-white shadow-2xl shadow-slate-900/20">
            <div class="grid gap-5 sm:grid-cols-3">
              <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                <p class="text-3xl font-bold">8+</p>
                <p class="mt-1 text-sm text-slate-300">Core modules connected</p>
              </div>
              <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                <p class="text-3xl font-bold">120 hrs</p>
                <p class="mt-1 text-sm text-slate-300">Monthly ops time saved</p>
              </div>
              <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                <p class="text-3xl font-bold">18%</p>
                <p class="mt-1 text-sm text-slate-300">Lower holding costs</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="bg-white py-20">
        <div class="mx-auto max-w-7xl px-4 lg:px-8">
          <div class="mx-auto mb-12 max-w-2xl text-center">
            <span class="text-sm font-semibold uppercase tracking-[0.2em] text-orange-500">Our Principles</span>
            <h2 class="mt-3 text-3xl font-bold sm:text-4xl">Designed with operators, not just developers.</h2>
            <p class="mt-4 text-slate-600">Every feature is tested against a real store workflow before it ships.</p>
          </div>

          <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
              <h3 class="text-lg font-semibold">Showroom-first UX</h3>
              <p class="mt-2 text-sm leading-6 text-slate-600">Retail teams can sell faster because the right data appears exactly when they need it.</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
              <h3 class="text-lg font-semibold">Operational transparency</h3>
              <p class="mt-2 text-sm leading-6 text-slate-600">Procurement, logistics, and finance see the same numbers, reducing delays and disputes.</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
              <h3 class="text-lg font-semibold">Automation with control</h3>
              <p class="mt-2 text-sm leading-6 text-slate-600">Smart suggestions, human approvals. You decide when the system acts.</p>
            </div>
          </div>
        </div>
      </section>

     
    </main>

    <MarketingFooter />
  </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import TopNav from '@/Components/MarketingHeader.vue'
import MarketingFooter from '@/Components/MarketingFooter.vue'

const setTrialPlan = (plan: 'simple' | 'unlimited') => {
  localStorage.setItem('trial_plan', plan)
  localStorage.setItem('trial_entry', 'marketing')
}
</script>
