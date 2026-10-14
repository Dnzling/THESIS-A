<template>
  <footer class="relative left-1/2 -mb-6 w-screen -translate-x-1/2 bg-[#f56617] text-white md:-mb-8">
    <div class="bg-[#fff8f3] px-4 py-5 text-slate-900 sm:px-6 md:py-7">
      <div class="mx-auto max-w-7xl">
        <div class="feedback-card relative overflow-hidden rounded-2xl border border-orange-100 bg-white px-5 py-5 shadow-[0_16px_40px_-28px_rgba(124,45,18,.55)] sm:px-7 md:flex md:items-center md:justify-between">
          <div class="absolute -right-10 -top-12 h-32 w-32 rounded-full bg-orange-100/80" />
          <div class="absolute -bottom-16 right-32 h-28 w-28 rounded-full border-[16px] border-amber-50" />
          <div class="relative flex items-start gap-4">
            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-orange-100 text-orange-600"><i class="pi pi-comment text-xl" /></span>
            <div><p class="text-base font-bold tracking-tight text-slate-900 sm:text-lg">Help us make FurniSync better</p><p class="mt-1 max-w-xl text-sm leading-6 text-slate-600">Was it easy to find what you were looking for today? Your feedback directly improves the shopping experience.</p></div>
          </div>
          <div class="relative mt-5 flex flex-wrap gap-2 md:mt-0 md:pl-6">
            <Button label="Yes, it was easy" icon="pi pi-thumbs-up" size="small" severity="warn" :loading="quickSending" class="!rounded-full !px-4" @click="quickFeedback(5)" />
            <Button label="Share feedback" icon="pi pi-arrow-right" iconPos="right" size="small" severity="secondary" outlined class="!rounded-full !px-4" @click="feedbackOpen = true" />
          </div>
        </div>
      </div>
    </div>
    <div class="mx-auto max-w-7xl px-6 sm:px-8">
      <div class="grid gap-x-8 gap-y-10 py-12 sm:py-14 md:grid-cols-2 lg:grid-cols-12 lg:gap-x-10">
        <div class="lg:col-span-4">
          <Link href="/" class="inline-flex items-center gap-3" aria-label="FurniSync home">

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
    <Dialog v-model:visible="feedbackOpen" modal header="Tell us about your experience" class="w-[calc(100%-2rem)] max-w-xl">
      <p class="text-sm text-slate-600">Your feedback helps us make shopping easier. This is for the FurniSync website experience, not a product review.</p>
      <div class="mt-5 space-y-4"><div><label class="mb-2 block text-sm font-semibold text-slate-800">How was your experience?</label><div class="flex gap-2"><button v-for="star in 5" :key="star" class="grid h-11 w-11 place-items-center rounded-full transition" :class="feedback.rating >= star ? 'bg-amber-100 text-amber-500' : 'bg-slate-100 text-slate-400 hover:bg-amber-50'" @click="feedback.rating = star"><i class="pi pi-star-fill text-lg" /></button></div></div><div><label class="mb-2 block text-sm font-semibold text-slate-800">What can we improve? <span class="font-normal text-slate-400">Optional</span></label><Textarea v-model="feedback.message" rows="4" class="w-full" placeholder="Share your feedback here..." /></div><Select v-model="feedback.topic" :options="topics" optionLabel="label" optionValue="value" class="w-full" /></div>
      <template #footer><Button label="Cancel" severity="secondary" text @click="feedbackOpen = false" /><Button label="Send feedback" icon="pi pi-send" severity="warn" :loading="sending" @click="submitFeedback" /></template>
    </Dialog>
  </footer>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { reactive, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import axiosClient from '@/axios'

const toast = useToast(); const page = usePage(); const feedbackOpen = ref(false); const sending = ref(false); const quickSending = ref(false)
const feedback = reactive({ rating: 0, message: '', topic: 'website' })
const topics = [{ label: 'Website experience', value: 'website' }, { label: 'Finding products', value: 'products' }, { label: 'Checkout', value: 'checkout' }, { label: 'Delivery', value: 'delivery' }, { label: 'Other', value: 'other' }]
async function submitFeedback() { if (!feedback.rating && !feedback.message.trim()) { toast.add({ severity: 'warn', summary: 'Feedback', detail: 'Select a rating or write a comment first.', life: 2500 }); return } sending.value = true; try { await axiosClient.post('/api/ecommerce/feedback', { ...feedback, page_url: page.url }); feedbackOpen.value = false; feedback.rating = 0; feedback.message = ''; toast.add({ severity: 'success', summary: 'Thank you!', detail: 'Your feedback was sent to our team.', life: 3000 }) } catch (e: any) { toast.add({ severity: 'error', summary: 'Feedback', detail: e?.response?.data?.message || 'Unable to send feedback.', life: 3000 }) } finally { sending.value = false } }
async function quickFeedback(rating: number) { quickSending.value = true; try { await axiosClient.post('/api/ecommerce/feedback', { rating, topic: 'website', page_url: page.url }); toast.add({ severity: 'success', summary: 'Thank you!', detail: 'Your feedback was sent.', life: 2600 }) } catch (e: any) { toast.add({ severity: 'error', summary: 'Feedback', detail: e?.response?.data?.message || 'Unable to send feedback.', life: 3000 }) } finally { quickSending.value = false } }

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

.feedback-card {
  background-image: linear-gradient(135deg, rgba(255,255,255,.95), rgba(255,247,237,.72));
}
</style>
