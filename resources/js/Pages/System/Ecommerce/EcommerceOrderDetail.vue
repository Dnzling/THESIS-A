<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Order Details</h1>
      </div>
      <div class="flex items-center gap-2">
        <Button v-if="order?.store_id" label="Chat Store" severity="help" outlined @click="goChatStore" />
        <Button v-if="order?.can_cancel" label="Request Cancel" severity="danger" outlined @click="goCancelPage" />
        <Button label="Back" severity="secondary" outlined @click="goBack" />
      </div>
    </div>

    <div v-if="loading" class="space-y-4">
      <Card class="border border-slate-200 shadow-none">
        <template #content>
          <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <Skeleton v-for="idx in 6" :key="idx" height="1.25rem" />
          </div>
        </template>
      </Card>
      <Card class="border border-slate-200 shadow-none">
        <template #content>
          <div class="space-y-3">
            <Skeleton v-for="idx in 4" :key="idx" height="3rem" />
          </div>
        </template>
      </Card>
    </div>

    <template v-else-if="order">
      <Card class="border border-slate-200 shadow-none">
        <template #content>
          <div class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
            <div><span class="text-slate-500">Order #:</span> <span class="font-semibold">{{ order.order_number }}</span></div>
            <div><span class="text-slate-500">Date:</span> <span class="font-semibold">{{ formatDate(order.created_at) }}</span></div>
            <div><span class="text-slate-500">Status:</span> <Tag :value="order.status" /></div>
            <div><span class="text-slate-500">Payment:</span> <Tag :value="order.payment_status" severity="secondary" /></div>
            <div class="md:col-span-2"><span class="text-slate-500">Shipping Address:</span> <span class="font-semibold">{{ order.shipping_address || '-' }}</span></div>
            <div><span class="text-slate-500">Tracking Number:</span> <span class="font-semibold">{{ order.delivery?.tracking_number || '-' }}</span></div>
            <div><span class="text-slate-500">Courier:</span> <span class="font-semibold">{{ order.delivery?.courier_name || '-' }}</span></div>
            <div><span class="text-slate-500">Courier Contact:</span> <span class="font-semibold">{{ order.delivery?.courier_contact || '-' }}</span></div>
            <div><span class="text-slate-500">Delivery Status:</span> <Tag :value="formatStatus(order.delivery?.status || 'pending')" severity="info" /></div>
            <div v-if="order.cancellation_request" class="md:col-span-2">
              <span class="text-slate-500">Cancellation Request:</span>
              <Tag :value="order.cancellation_request.status" severity="warning" class="ml-2" />
            </div>
          </div>
        </template>
      </Card>

      <Card class="border border-slate-200 shadow-none">
        <template #content>
          <div class="space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
              <p class="text-sm font-semibold text-slate-800">Store: {{ order.store_name || 'Store' }}</p>
              <Button v-if="order.store_id" label="Chat" size="small" severity="help" text @click="goChatStore" />
            </div>

            <div
              v-for="item in order.items || []"
              :key="item.id"
              class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 px-3 py-3"
            >
              <div class="flex min-w-0 items-center gap-3">
                <img :src="item.image || '/F.svg'" alt="Product" class="h-14 w-14 rounded-xl border border-slate-200 object-cover" />
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-slate-900">{{ item.product_name }}</p>
                  <p class="truncate text-xs text-slate-500">Variant: {{ item.sku || 'Standard' }}</p>
                </div>
              </div>

              <div class="flex items-center gap-5">
                <Tag :value="order.status" severity="secondary" />
                <p class="text-sm text-slate-600">PHP {{ Number(item.unit_price || 0).toFixed(2) }}</p>
                <p class="text-sm font-semibold text-slate-700">Qty {{ item.quantity }}</p>
                <p class="text-base font-semibold text-slate-900">PHP {{ Number(item.line_total || 0).toFixed(2) }}</p>
                <Button v-if="item.can_return" label="Return" severity="secondary" outlined @click="goReturnPage(item.id)" />
                <Button v-if="item.can_review" label="Review" severity="info" outlined @click="goReviewPage(item.id)" />
              </div>
              <div v-if="item.return_request || item.review" class="w-full border-t border-slate-100 pt-2 text-xs text-slate-600">
                <p v-if="item.return_request">Return request: <span class="font-semibold">{{ item.return_request.status }}</span></p>
                <p v-if="item.review">Your review: {{ item.review.rating }}/5</p>
              </div>
            </div>
          </div>

          <div class="mt-4 ml-auto max-w-sm space-y-2 text-sm">
            <div class="flex justify-between"><span>Subtotal</span><span>PHP {{ Number(order.subtotal || 0).toFixed(2) }}</span></div>
            <div class="flex justify-between"><span>Tax</span><span>PHP {{ Number(order.tax_amount || 0).toFixed(2) }}</span></div>
            <div class="flex justify-between"><span>Shipping</span><span>PHP {{ Number(order.shipping_fee || 0).toFixed(2) }}</span></div>
            <div class="flex justify-between"><span>Discount</span><span>- PHP {{ Number(order.discount_amount || 0).toFixed(2) }}</span></div>
            <Divider />
            <div class="flex justify-between text-base font-bold"><span>Total</span><span>PHP {{ Number(order.total_amount || 0).toFixed(2) }}</span></div>
          </div>
        </template>
      </Card>

      <Card class="border border-slate-200 shadow-none">
        <template #content>
          <div class="mb-3">
            <p class="text-sm font-semibold text-slate-900">Order Timeline</p>
            <p class="text-xs text-slate-500">Track all status changes and delivery updates.</p>
          </div>
          <Timeline v-if="order.timeline?.length" :value="order.timeline" class="w-full">
            <template #content="{ item }">
              <div class="pb-4">
                <div class="flex flex-wrap items-center gap-2">
                  <p class="text-sm font-semibold text-slate-900">{{ item.title }}</p>
                  <Tag v-if="item.status_to" :value="formatStatus(item.status_to)" severity="secondary" class="text-xs" />
                </div>
                <p class="mt-1 text-xs text-slate-600">{{ item.description || '-' }}</p>
                <p class="mt-1 text-xs text-slate-400">
                  {{ formatDateTime(item.created_at) }} • {{ item.actor || 'System' }}
                </p>
                <div v-if="item.meta?.proof_photo_url || item.meta?.proof_signature_url" class="mt-2 flex flex-wrap gap-2">
                  <Button
                    v-if="item.meta?.proof_photo_url"
                    size="small"
                    outlined
                    severity="secondary"
                    icon="pi pi-image"
                    label="Proof Photo"
                    @click="previewMedia(item.meta.proof_photo_url, 'Proof Photo')"
                  />
                  <Button
                    v-if="item.meta?.proof_signature_url"
                    size="small"
                    outlined
                    severity="secondary"
                    icon="pi pi-pencil"
                    label="Signature"
                    @click="previewMedia(item.meta.proof_signature_url, 'Signature')"
                  />
                </div>
              </div>
            </template>
          </Timeline>
          <p v-else class="text-sm text-slate-500">No timeline yet.</p>
        </template>
      </Card>
    </template>

    <Dialog v-model:visible="mediaPreview.visible" modal :header="mediaPreview.title" class="w-full max-w-4xl">
      <div class="flex items-center justify-center rounded-lg bg-slate-50 p-2">
        <img v-if="mediaPreview.url" :src="mediaPreview.url" alt="Delivery proof" class="max-h-[70vh] w-auto rounded-lg object-contain" />
      </div>
      <template #footer>
        <Button label="Open Full View" icon="pi pi-external-link" outlined @click="openExternal(mediaPreview.url)" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import ecommerceService from '@/services/ecommerce.service'
import paymongoService from '@/services/paymongo.service'
import Timeline from 'primevue/timeline'
import Dialog from 'primevue/dialog'
defineOptions({
  layout: EcommerceMobileWrapper,
})


const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const order = ref<any>(null)
const mediaPreview = reactive({
  visible: false,
  url: '',
  title: 'Preview',
})

async function loadOrderDetail() {
  loading.value = true
  try {
    const response = await ecommerceService.getOrder(route.params.id as string)
    order.value = response.data?.data || null
    await syncPaymongoPaymentStatus()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load order details', life: 2500 })
    router.push({ name: 'ecommerce.orders' })
  } finally {
    loading.value = false
  }
}

async function syncPaymongoPaymentStatus() {
  const currentOrder = order.value
  if (!currentOrder?.id) return
  if (String(currentOrder.payment_method || '').toLowerCase() !== 'e_wallet') return
  if (String(currentOrder.payment_status || '').toLowerCase() === 'paid') return

  try {
    const latestIntentResponse = await paymongoService.getLatestIntentByPayable('ecommerce_order', Number(currentOrder.id))
    const latestIntent = latestIntentResponse?.data
    const intentId = latestIntent?.payment_intent_id
    if (!intentId) return

    await paymongoService.getIntent(String(intentId))

    const refreshed = await ecommerceService.getOrder(currentOrder.id)
    const refreshedOrder = refreshed.data?.data || currentOrder
    const beforeStatus = String(currentOrder.payment_status || '').toLowerCase()
    const afterStatus = String(refreshedOrder.payment_status || '').toLowerCase()

    order.value = refreshedOrder

    if (beforeStatus !== 'paid' && afterStatus === 'paid') {
      toast.add({ severity: 'success', summary: 'Payment Confirmed', detail: 'Your GCash payment was confirmed.', life: 2800 })
    }
  } catch {
    // Keep order page usable even if PayMongo status refresh fails.
  }
}

function formatDate(value: string) {
  if (!value) return '-'
  return new Date(value).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

function formatDateTime(value: string) {
  if (!value) return '-'
  return new Date(value).toLocaleString('en-PH', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

function formatStatus(value: string) {
  if (!value) return '-'
  return String(value).replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase())
}

function previewMedia(url: string, title: string) {
  mediaPreview.url = url
  mediaPreview.title = title
  mediaPreview.visible = true
}

function openExternal(url: string) {
  if (!url) return
  window.open(url, '_blank')
}

function goBack() {
  router.push({ name: 'ecommerce.orders' })
}

function goCancelPage() {
  if (!order.value?.id) return
  router.push({ name: 'ecommerce.order-cancel', params: { id: order.value.id } })
}

function goReturnPage(itemId: number) {
  if (!order.value?.id) return
  router.push({ name: 'ecommerce.order-return', params: { id: order.value.id, itemId } })
}

function goReviewPage(itemId: number) {
  if (!order.value?.id) return
  router.push({ name: 'ecommerce.order-review', params: { id: order.value.id, itemId } })
}

function goChatStore() {
  if (!order.value?.store_id) return
  router.push({ name: 'ecommerce.chats', query: { store_id: String(order.value.store_id) } })
}

onMounted(loadOrderDetail)
</script>

