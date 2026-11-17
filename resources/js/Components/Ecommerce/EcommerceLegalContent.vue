<template>
  <article class="legal-copy text-sm leading-6 text-slate-600">
    <template v-if="type === 'privacy'">
      <p class="rounded-lg border border-orange-100 bg-orange-50/70 p-3 text-xs text-slate-600">
        Effective date: October 28, 2026. This notice describes customer data handling on the FurniSync ecommerce experience.
      </p>

      <h2>Who handles your information</h2>
      <p>FurniSync operates the shopping platform and coordinates account and order services. When you place an order, the participating store shown with that order also handles the information needed to prepare, fulfill, and support your purchase.</p>

      <h2>Information we collect</h2>
      <ul>
        <li>Account details such as your name, email address, birthday, and sign-in information.</li>
        <li>Order details such as products and variants, quantities, customization notes, payment method and transaction status.</li>
        <li>Contact and fulfillment details such as phone number, delivery address, selected pickup or delivery service, and any map pin or coordinates you provide.</li>
        <li>Messages, return or refund requests, and files or photos you choose to submit for support.</li>
        <li>Limited device, session, and usage information used to operate, protect, and troubleshoot the service.</li>
      </ul>

      <h2>How we use it</h2>
      <p>We use this information to create and secure your account, process orders and payments, coordinate pickup or delivery, provide customer support, handle cancellations, returns and refunds, prevent fraud or misuse, and maintain business and legal records. We do not use your account password as a shopping profile field or display it to stores.</p>

      <h2>When information is shared</h2>
      <p>We share relevant order and contact details with the store fulfilling your order, and with delivery, payment, hosting, and other service providers only as needed to provide the service, protect it, or meet legal obligations. A payment provider may collect payment credentials through its own payment flow; FurniSync receives transaction information needed to confirm and manage payment. We do not sell your personal information.</p>

      <h2>Location and device permissions</h2>
      <p>You may provide a saved address or map pin for delivery. Browser location is used only when you choose a feature that requests it and grant permission. You can manage browser permissions in your device settings; a missing location pin may limit delivery estimates or service availability.</p>

      <h2>Retention and protection</h2>
      <p>We keep information only as long as reasonably needed for the purposes above, account and transaction operations, dispute handling, and applicable recordkeeping requirements. We apply reasonable safeguards, but no online service can guarantee absolute security.</p>

      <h2>Your choices and privacy requests</h2>
      <p>You may ask whether we process your information, request access to or correction of it, and request blocking, deletion, or object to processing where applicable. You may also exercise data portability rights where the law applies. Some transaction records may need to be retained to complete orders, resolve disputes, or meet legal obligations. Contact <a href="mailto:info@furnisync.com">info@furnisync.com</a> for privacy questions or requests. You may also lodge a complaint with the National Privacy Commission.</p>
    </template>

    <template v-else>
      <p class="rounded-lg border border-orange-100 bg-orange-50/70 p-3 text-xs text-slate-600">
        Effective date: October 28, 2026. These terms apply when you use the FurniSync customer ecommerce experience.
      </p>

      <h2>FurniSync and participating stores</h2>
      <p>FurniSync provides the online shopping platform and order coordination tools. Products are offered by participating stores, and the store identified with your order is responsible for preparing and fulfilling it. Store-specific product, price, stock, and fulfillment information is shown in the shopping experience and order details.</p>

      <h2>Your account</h2>
      <p>You must be at least 18 years old to register, provide accurate account and contact information, and keep your sign-in credentials confidential. You are responsible for activity on your account. Contact us promptly if you believe it has been accessed without permission.</p>

      <h2>Products, prices, and orders</h2>
      <p>Please review the selected product or variant, quantity, price, discounts, taxes, and fulfillment details before placing an order. Furniture images, colors, materials, dimensions, and finishes may vary slightly from the physical item. Listings and stock are subject to change. An order is subject to availability, payment confirmation where required, and store processing; we will show status updates in your account.</p>

      <h2>Payment</h2>
      <p>Use only the payment methods offered at checkout. Payment processing may be provided by an external payment provider under its own terms and privacy notice. Do not share your account password, payment PIN, or one-time password with a store or another user. An order is not considered paid until the payment status is confirmed.</p>

      <h2>Pickup, delivery, and dates</h2>
      <p>Pickup and delivery options depend on the store, item, and destination. Any applicable fee and the selected service date are displayed during checkout. Delivery and pickup dates are scheduled targets and may change due to stock, route, weather, safety, or other operational constraints; the store will provide updates through the order status.</p>

      <h2>Cancellations, returns, and refunds</h2>
      <p>Cancellation requests, returns, replacements, and refunds are handled through the available order support flow and the posted Returns &amp; Refunds policy. Eligibility and processing depend on the reason, order status, applicable consumer protections, and payment provider timing. Approved refunds are handled through the applicable checkout payment method or payout process; completion times may vary.</p>

      <h2>Acceptable use</h2>
      <p>Do not submit false or misleading information, misuse another person’s account, interfere with platform security or operation, upload unlawful or harmful content, or use FurniSync to conduct fraudulent or unlawful transactions. We may restrict access when reasonably necessary to protect customers, stores, or the service.</p>

      <h2>Service changes and legal rights</h2>
      <p>We may update platform features or these terms and will publish the current version here. Nothing in these terms limits rights or remedies that cannot be waived under applicable Philippine law, including consumer protection laws.</p>

      <h2>Contact</h2>
      <p>For order or account support, contact <a href="mailto:info@furnisync.com">info@furnisync.com</a>.</p>
    </template>
  </article>
</template>

<script setup lang="ts">
defineProps<{ type: 'terms' | 'privacy' }>()
</script>

<style scoped>
.legal-copy h2 { @apply mt-6 text-base font-semibold text-slate-900; }
.legal-copy p { @apply mt-2; }
.legal-copy ul { @apply mt-2 list-disc space-y-1 pl-5; }
.legal-copy a { @apply font-medium text-orange-700 underline underline-offset-2; }
</style>
