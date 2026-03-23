<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Order Details</h1>
      </div>
      <div class="flex items-center gap-2">
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
            <div class="border-b border-slate-100 pb-2">
              <p class="text-sm font-semibold text-slate-800">Store: {{ order.store_name || 'Store' }}</p>
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
              </div>
            </template>
          </Timeline>
          <p v-else class="text-sm text-slate-500">No timeline yet.</p>
        </template>
      </Card>
    </template>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import ecommerceService from '@/services/ecommerce.service'
import Timeline from 'primevue/timeline'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const order = ref<any>(null)

async function loadOrderDetail() {
  loading.value = true
  try {
    const response = await ecommerceService.getOrder(route.params.id as string)
    order.value = response.data?.data || null
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load order details', life: 2500 })
    router.push({ name: 'ecommerce.orders' })
  } finally {
    loading.value = false
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

onMounted(loadOrderDetail)
</script>
