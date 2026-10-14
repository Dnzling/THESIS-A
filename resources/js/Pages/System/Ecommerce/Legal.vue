<template>
  <div class="space-y-8 pb-10">
    <section class="rounded-[2rem] bg-gradient-to-br from-orange-500 to-orange-600 px-6 py-10 text-white shadow-xl shadow-orange-100 sm:px-10 sm:py-14">
      <p class="text-sm font-semibold uppercase tracking-[0.2em] text-orange-100">FurniSync Ecommerce</p>
      <h1 class="mt-3 max-w-3xl text-3xl font-black tracking-tight sm:text-5xl">{{ title }}</h1>
      <p class="mt-4 max-w-2xl text-sm leading-7 text-orange-50">Last updated: September 22, 2026</p>
    </section>

    <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
      <div v-if="key === 'privacy'" class="legal-copy">
        <p>FurniSync respects your privacy. This Privacy Policy explains what information we collect when you browse, register, shop, or contact us, and how we use it to provide a secure and reliable furniture shopping experience.</p>
        <h2>Information we collect</h2>
        <p>We may collect your name, contact details, delivery address, account credentials, order history, payment and transaction references, and messages or files you submit for support and returns. We also receive basic device and usage information needed to keep the site secure and improve the service.</p>
        <h2>How we use your information</h2>
        <p>We use information to process orders, coordinate delivery, verify accounts, respond to support requests, manage returns and refunds, prevent fraud, maintain records, and improve FurniSync. We do not sell your personal information.</p>
        <h2>Sharing and retention</h2>
        <p>Information is shared only with service providers and store or delivery partners when needed to complete your order, provide support, process payments, or comply with the law. We retain information only for as long as reasonably necessary for these purposes and our legal obligations.</p>
        <h2>Your choices</h2>
        <p>You may request access to, correction of, or deletion of your personal information, subject to applicable requirements. For privacy questions or requests, contact <a href="mailto:info@furnisync.com">info@furnisync.com</a>.</p>
      </div>

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

      <div v-else class="legal-copy">
        <p>By using FurniSync or placing an order, you agree to these Terms and Conditions. Please read them before creating an account or purchasing a product.</p>
        <h2>Accounts and orders</h2>
        <p>You are responsible for keeping your account details accurate and secure. An order is subject to product availability, delivery coverage, payment confirmation, and acceptance by the applicable store. We may contact you to verify order details or correct an obvious listing error.</p>
        <h2>Products, prices, and delivery</h2>
        <p>Product images, colors, dimensions, and finishes may vary slightly from the physical item. Prices and availability may change without prior notice. Delivery dates are estimates and may be affected by location, holidays, weather, or circumstances outside our control.</p>
        <h2>Acceptable use</h2>
        <p>You must not misuse the platform, interfere with its operation, submit fraudulent information, infringe another person's rights, or use the service for unlawful purposes.</p>
        <h2>Limitation and updates</h2>
        <p>FurniSync provides the platform and order coordination services with reasonable care. Nothing in these terms removes rights that cannot be excluded under applicable Philippine law. We may update these terms from time to time; the latest version will be posted on this page.</p>
        <h2>Contact</h2>
        <p>For questions about these terms, email <a href="mailto:info@furnisync.com">info@furnisync.com</a>.</p>
      </div>
    </article>
  </div>
  <MarketingFooter />
</template>

<script setup lang="ts">
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'
import MarketingFooter from '@/Components/MarketingFooter.vue'
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
