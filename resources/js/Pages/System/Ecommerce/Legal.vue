<template>
  <div class="space-y-8 pb-10">
    <section class="rounded-[2rem] bg-gradient-to-br from-orange-500 to-orange-600 px-6 py-10 text-white shadow-xl shadow-orange-100 sm:px-10 sm:py-14">
      <p class="text-sm font-semibold uppercase tracking-[0.2em] text-orange-100">FurniSync Ecommerce</p>
      <h1 class="mt-3 max-w-3xl text-3xl font-black tracking-tight sm:text-5xl">{{ title }}</h1>
      <p class="mt-4 max-w-2xl text-sm leading-7 text-orange-50">Last updated: October 28, 2026</p>
    </section>

    <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
      <EcommerceLegalContent v-if="key === 'privacy'" type="privacy" />

      <div v-else-if="key === 'returns'" class="legal-copy">
        <p>We inspect products before dispatch. If your order arrives damaged, incorrect, incomplete, or missing parts, notify FurniSync within <strong>2 calendar days</strong> from delivery.</p>
        <h2>How to request a return</h2>
        <p>Contact us through your order support channel and include your order number, a description of the issue, and clear photos or videos of the package, product, and any damage or missing components. Keep the original packaging until the review is complete.</p>
        <h2>Eligible reasons</h2>
        <ul><li>Item arrived damaged or defective.</li><li>Wrong item or variation was delivered.</li><li>Accessories, parts, or an item are missing.</li><li>Order is incomplete.</li></ul>
        <h2>Non-eligible reasons</h2>
        <p>Returns are generally not accepted for change of mind, incorrect measurements, or dissatisfaction where the item matches its listing and arrived in acceptable condition. Requests outside the reporting period or without enough evidence may be declined.</p>
        <h2>Replacement and refunds</h2>
        <p>After verification, we may offer a replacement, repair, or an alternative product. If a suitable replacement is unavailable, we will arrange a refund for the qualified item. Refund timing depends on verification and the payment provider; we will provide an update once the refund is approved.</p>
      </div>

      <EcommerceLegalContent v-else-if="key === 'terms'" type="terms" />
    </article>
  </div>
  <MarketingFooter />
</template>

<script setup lang="ts">
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'
import MarketingFooter from '@/Components/MarketingFooter.vue'
import EcommerceLegalContent from '@/Components/ecommerce/EcommerceLegalContent.vue'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

defineOptions({ layout: EcommerceMobileWrapper })
const inertiaPage = usePage<{ title?: string }>()
const title = computed(() => inertiaPage.props.title || 'Legal Information')
const key = computed(() => title.value.toLowerCase().includes('privacy') ? 'privacy' : title.value.toLowerCase().includes('return') ? 'returns' : 'terms')
</script>

<style scoped>
.legal-copy { @apply max-w-4xl text-sm leading-7 text-slate-600; }
.legal-copy h2 { @apply mt-8 text-xl font-bold text-slate-950; }
.legal-copy p { @apply mt-3; }
.legal-copy ul { @apply mt-3 list-disc space-y-2 pl-6; }
.legal-copy a { @apply font-semibold text-orange-600 underline; }
</style>
