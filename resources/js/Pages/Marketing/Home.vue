<template>
  <Head title="Home"/>
  <div class="bg-gradient-to-b from-white to-orange-50/30">
    <TopNav />

    <main>
      <!-- Hero Section -->
      <section class="relative overflow-hidden py-20 lg:py-32">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(245,158,107,0.12),transparent_55%)]"></div>
        <div class="container mx-auto px-4 lg:px-8 max-w-7xl">
          <div class="text-center max-w-5xl mx-auto">
            <h1 class="text-4xl lg:text-5xl font-bold leading-tight mb-6">
              A modern platform to run every store,
              <span class="text-orange-500 block mt-2">sell smarter, and showcase in 3D.</span>
            </h1>
            <p class="text-gray-600 text-lg lg:text-xl mb-10 max-w-3xl mx-auto">
              FurniSync brings integrated management, decision support system into one clean workspace.
              Built for high-value catalogs, multi-branch teams, and confident customer experiences.
            </p>
            <div class="flex flex-wrap gap-4 justify-center mb-12">
              <Button 
                as="router-link" 
                severity="warn"
                to="/register?plan=simple&trial=1" 
                raised
                class="font-semibold"
                size="medium"
                @click="setTrialPlan('simple')"
              >
            Start Free Trial
              </Button>
              <Button 
                @click="scrollToOverview"
                severity="secondary"
                size="medium"
                class="font-bold"
                label="Watch Demo"
              />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-3xl mx-auto mt-10">
              <div v-for="item in proofItems" :key="item.title" class="text-center">
                <p class="font-bold text-gray-900 mb-1">{{ item.title }}</p>
                <p class="text-sm text-gray-500">{{ item.sub }}</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Stats Section -->
      <section class="py-16">
        <div class="container mx-auto px-4 lg:px-8 max-w-7xl">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <Card v-for="stat in stats" :key="stat.title" class="text-center">
              <template #content>
                <p class="text-3xl lg:text-4xl font-bold text-orange-500 mb-2">{{ stat.value }}</p>
                <p class="font-semibold text-gray-900 mb-1">{{ stat.title }}</p>
                <p class="text-sm text-gray-500">{{ stat.sub }}</p>
              </template>
            </Card>
          </div>
        </div>
      </section>
  
      <!-- Solutions Section -->
      <section ref="overviewSection" class="py-20">
        <div class="container mx-auto px-4 lg:px-8 max-w-7xl">
          <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="text-orange-500 text-sm uppercase tracking-wider">Platform Overview</span>
            <h2 class="text-3xl lg:text-4xl font-bold mt-2 mb-4">Everything you need for modern furniture operations.</h2>
            <p class="text-gray-500">Run merchandising, supply, and sales execution from one intelligent workspace with shared data and live reporting.</p>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <Card v-for="feature in features" :key="feature.title" class="hover:shadow-lg transition-shadow">
              <template #content>
                <div class="w-12 h-12 rounded-xl bg-orange-100 text-orange-500 flex items-center justify-center mb-4" v-html="feature.icon"></div>
                <h3 class="font-bold text-lg mb-2">{{ feature.title }}</h3>
                <p class="text-gray-500 text-sm">{{ feature.description }}</p>
              </template>
            </Card>
          </div>
        </div>
      </section>

      <!-- Workflow Section -->
      <section class="py-20 bg-gradient-to-br from-orange-50/50 to-transparent">
        <div class="container mx-auto px-4 lg:px-8 max-w-7xl">
          <div class="grid lg:grid-cols-2 gap-12 items-center">
            <div>
              <span class="text-orange-500 text-sm uppercase tracking-wider">Workflow</span>
              <h2 class="text-3xl lg:text-4xl font-bold mt-2 mb-4">From warehouse to showroom in one flow.</h2>
              <p class="text-gray-500 mb-8">Connect demand planning, supplier coordination, and showroom experiences without chasing spreadsheets.</p>
              <div class="space-y-3">
                <div v-for="step in steps" :key="step.title" class="flex gap-4 p-4 bg-white rounded-xl shadow-sm">
                  <div class="w-10 h-10 rounded-lg bg-orange-100 text-orange-600 font-bold flex items-center justify-center">{{ step.index }}</div>
                  <div>
                    <p class="font-semibold mb-1">{{ step.title }}</p>
                    <p class="text-sm text-gray-500">{{ step.sub }}</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="bg-gray-900 rounded-2xl p-6 shadow-xl">
              <p class="text-white font-semibold mb-4">Live Branch View</p>
              <div v-for="highlight in highlights" :key="highlight.label" class="flex justify-between items-center py-3 border-b border-gray-700">
                <div>
                  <p class="text-gray-400 text-sm">{{ highlight.label }}</p>
                  <p class="text-white font-medium">{{ highlight.value }}</p>
                </div>
                <Tag :severity="getTagSeverity(highlight.tone)" :value="highlight.badge" />
              </div>
              <p class="text-gray-500 text-xs mt-4">Updated just now</p>
            </div>
          </div>
        </div>
      </section>

      <!-- Showcase Section -->
      <section class="py-20">
        <div class="container mx-auto px-4 lg:px-8 max-w-7xl">
          <div class="grid lg:grid-cols-2 gap-12 items-center">
            <div>
              <span class="text-orange-500 text-sm uppercase tracking-wider">3D Experience</span>
              <h2 class="text-3xl lg:text-4xl font-bold mt-2 mb-4">Sell premium pieces with immersive previews.</h2>
              <p class="text-gray-500 mb-6">Launch photoreal 3D viewers, configure materials, and give your teams the confidence to close higher-ticket orders.</p>
              <Button as="router-link" to="/pricing" severity="primary" class="!bg-orange-500 !border-none">See Pricing</Button>
            </div>
            <div class="space-y-4">
              <Card v-for="card in showcaseCards" :key="card.title" :class="['hover:shadow-lg transition-all', card.glow && 'border-orange-200 bg-gradient-to-r from-orange-50/50 to-transparent']">
                <template #content>
                  <p class="font-semibold text-lg mb-1">{{ card.title }}</p>
                  <p class="text-gray-500 text-sm">{{ card.sub }}</p>
                </template>
              </Card>
            </div>
          </div>
        </div>
      </section>

      <!-- Testimonial Section -->
      <section class="py-20 bg-white">
        <div class="container mx-auto px-4 lg:px-8 max-w-7xl">
          <div class="grid lg:grid-cols-2 gap-8 items-center">
            <Card class="bg-gradient-to-br from-orange-50 to-transparent border-orange-100">
              <template #content>
                <p class=" text-xl lg:text-2xl mb-6">“FurniSync replaced six disconnected tools. Our store managers finally share the same data and our 3D catalog closes deals faster.”</p>
                <div class="flex justify-between items-center">
                  <div>
                    <p class="font-semibold">Rhea Valdez</p>
                    <p class="text-sm text-gray-500">VP Operations, Lumina Living</p>
                  </div>
                  <Tag value="Multi-branch" severity="info" class="!bg-orange-100 !text-orange-600" />
                </div>
              </template>
            </Card>
            <div class="grid grid-cols-1 gap-6">
              <div v-for="metric in metrics" :key="metric.label" class="text-center p-6 bg-orange-50/50 rounded-xl">
                <p class="text-3xl lg:text-4xl font-bold text-orange-500 mb-1">{{ metric.value }}</p>
                <p class="text-gray-600">{{ metric.label }}</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- CTA Section -->
      <section class="py-20">
        <div class="container mx-auto px-4 lg:px-8 max-w-7xl">
          <Card class="">
            <template #content>
              <div class="lg:flex lg:items-center lg:justify-between gap-8">
                <div class="mb-6 lg:mb-0">
                  <h2 class="text-2xl lg:text-3xl font-bold mb-2">Ready to modernize your furniture business?</h2>
                  <p class="text-orange-500">Start a guided trial and see how fast your teams can switch to a smarter, unified workflow.</p>
                </div>
                <div class="flex gap-3">
                  <Button @click="setTrialPlan('simple')" severity="warn">Start Free Trial</Button>
                  <Button as="router-link" to="/pricing" severity="secondary" class="">Compare Plans</Button>
                </div>
              </div>
            </template>
          </Card>
        </div>
      </section>
    </main>

    <MarketingFooter />
  </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import TopNav from '@/Components/MarketingHeader.vue'
import MarketingFooter from '@/Components/MarketingFooter.vue'
import { ref } from 'vue'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Chip from 'primevue/chip'
import Tag from 'primevue/tag'

const overviewSection = ref<HTMLElement | null>(null)

const scrollToOverview = () => {
  overviewSection.value?.scrollIntoView({ behavior: 'smooth' })
}

const setTrialPlan = (plan: 'simple' | 'unlimited') => {
  localStorage.setItem('trial_plan', plan)
  localStorage.setItem('trial_entry', 'marketing')
}

const getTagSeverity = (tone: string) => {
  switch(tone) {
    case 'tone-ok': return 'success'
    case 'tone-warn': return 'warning'
    default: return 'info'
  }
}

const proofItems = [
  { title: '14-day trial', sub: 'No credit card needed' },
  { title: 'Dedicated onboarding', sub: 'White-glove support' },
  { title: 'Enterprise ready', sub: 'SOC-friendly stack' }
]

const stats = [
  { value: '45%', title: 'Fewer stockouts', sub: 'Live inventory orchestration' },
  { value: '3.2x', title: 'Faster purchasing', sub: 'Automated procurement flows' },
  { value: '28%', title: 'Higher sell-through', sub: '3D-driven conversion lifts' }
]

const logos = ['CasaPro', 'Nordline', 'Haven & Co', 'Oakridge', 'StudioOne', 'UrbanCraft']

const features = [
  {
    title: '3D Catalog Studio',
    description: 'Spin, zoom, and configure product materials with photoreal control.',
    icon: '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 3l8 4-8 4-8-4 8-4z"/><path d="M4 11l8 4 8-4"/><path d="M4 19l8 4 8-4"/></svg>'
  },
  {
    title: 'Inventory Command Center',
    description: 'Real-time stock, branch availability, and demand insights in one dashboard.',
    icon: '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 7h18"/><path d="M5 7v10a2 2 0 002 2h10a2 2 0 002-2V7"/><path d="M9 11h6"/></svg>'
  },
  {
    title: 'Smart Procurement',
    description: 'Automate POs, supplier selection, and lead-time tracking with AI guidance.',
    icon: '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M6 3h12l3 6H3l3-6z"/><path d="M4 9v10a2 2 0 002 2h12a2 2 0 002-2V9"/></svg>'
  },
  {
    title: 'Sales Enablement',
    description: 'Give teams instant product knowledge, bundles, and showroom scripts.',
    icon: '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 12h18"/><path d="M12 3v18"/></svg>'
  },
  {
    title: 'Logistics Control',
    description: 'Track deliveries, routing, and customer updates in real time.',
    icon: '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 7h13v10H3z"/><path d="M16 9h5l-2 5h-3z"/><circle cx="7" cy="19" r="2"/><circle cx="17" cy="19" r="2"/></svg>'
  },
  {
    title: 'Decision Support',
    description: 'AI-driven guidance for markdowns, replenishment, and promo timing.',
    icon: '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 2l7 4v6c0 5-3 9-7 10-4-1-7-5-7-10V6l7-4z"/></svg>'
  }
]

const steps = [
  { index: '01', title: 'Connect your catalog', sub: 'Import SKUs, materials, and inventory across branches.' },
  { index: '02', title: 'Activate 3D experiences', sub: 'Launch immersive viewers and material configurators.' },
  { index: '03', title: 'Automate procurement', sub: 'AI recommends POs and supplier timing.' },
  { index: '04', title: 'Deliver confidently', sub: 'Real-time logistics visibility for every order.' }
]

const highlights = [
  { label: 'Cebu Showroom', value: '412 active SKUs', badge: 'Healthy', tone: 'tone-ok' },
  { label: 'Manila Warehouse', value: 'Reorder queue 28', badge: 'Action', tone: 'tone-warn' },
  { label: 'Davao Outlet', value: 'Sell-through 82%', badge: 'On track', tone: 'tone-ok' }
]

const showcaseCards = [
  { title: 'Material Lab', sub: 'Toggle upholstery, wood, and finish in seconds.', glow: false },
  { title: 'Sales Assist', sub: 'Guided bundles and smart add-ons.', glow: true },
  { title: 'Executive Reporting', sub: 'Executive views for revenue, demand, and inventory health.', glow: false }
]

const metrics = [
  { value: '32%', label: 'Faster approvals' },
  { value: '2.8x', label: 'Showroom engagement' },
  { value: '$1.4M', label: 'Recovered margin' }
]
</script>

<style>

@import url('https://fonts.bunny.net/css?family=manrope:400,500,600,700&family=plus-jakarta-sans:600,700,800');

* {
  font-family: 'Manrope', sans-serif;
}

</style>