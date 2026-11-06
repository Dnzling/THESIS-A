<template>
  <div class="mx-auto max-w-4xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-bold text-slate-900">Customer Review</h1>
        <p class="text-sm text-slate-500">Review the product feedback and respond on behalf of the store.</p>
      </div>
      <Button severity="secondary" outlined icon="pi pi-arrow-left" label="Back to Reviews" size="small" @click="goBack" />
    </div>

    <Card class="border border-slate-200 shadow-sm">
      <template #content>
        <div v-if="loading" class="space-y-4" aria-label="Loading review details">
          <Skeleton height="4rem" />
          <Skeleton height="8rem" />
          <Skeleton height="5rem" />
        </div>
        <div v-else-if="review.id" class="space-y-5">
          <section class="flex flex-wrap items-start justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
            <div class="min-w-0">
              <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Product</div>
              <div class="mt-1 text-base font-semibold text-slate-900">{{ review.product?.product_name || 'Product' }}</div>
              <div class="text-xs text-slate-500">SKU: {{ review.product?.sku || '-' }}</div>
            </div>
            <Badge :value="formatStatus(review.status)" :severity="statusSeverity(review.status)" />
          </section>

          <section class="grid gap-4 sm:grid-cols-2">
            <div>
              <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Customer</div>
              <div class="mt-1 font-medium text-slate-900">{{ review.customer_name || 'Customer' }}</div>
              <div v-if="review.customer_contact" class="text-sm text-slate-500">{{ review.customer_contact }}</div>
            </div>
            <div>
              <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Submitted</div>
              <div class="mt-1 text-sm text-slate-700">{{ formatDate(review.created_at) }}</div>
              <div v-if="review.order_number || review.order_id" class="text-xs text-slate-500">Order {{ review.order_number || `#${review.order_id}` }}</div>
            </div>
          </section>

          <section class="rounded-lg border border-slate-200 p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="text-sm font-semibold text-slate-800">Customer feedback</div>
              <div class="flex items-center gap-1 text-amber-500" :aria-label="`${review.rating || 0} out of 5 stars`">
                <i v-for="n in 5" :key="n" class="pi" :class="n <= (review.rating || 0) ? 'pi-star-fill' : 'pi-star'" />
                <span class="ml-1 text-sm font-medium text-slate-700">{{ review.rating || 0 }}/5</span>
              </div>
            </div>
            <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ review.message || 'The customer left a rating without written comments.' }}</p>
            <div v-if="review.is_recommended !== null && review.is_recommended !== undefined" class="mt-3 text-xs text-slate-600">
              Recommendation: <span class="font-medium">{{ review.is_recommended ? 'Recommends this product' : 'Does not recommend this product' }}</span>
            </div>
            <div v-if="ratingDetails.length" class="mt-4 grid gap-2 border-t border-slate-100 pt-3 sm:grid-cols-2">
              <div v-for="detail in ratingDetails" :key="detail.label" class="flex justify-between gap-3 text-xs">
                <span class="text-slate-500">{{ detail.label }}</span>
                <span class="font-medium text-slate-700">{{ detail.value }}/5</span>
              </div>
            </div>
            <a v-if="review.attachment_url" :href="review.attachment_url" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-orange-700 hover:underline">
              <i class="pi pi-image" /> View customer attachment
            </a>
          </section>

          <section class="space-y-2">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="text-sm font-semibold text-slate-800">Store reply</div>
              <small v-if="review.replied_at" class="text-xs text-slate-500">Replied {{ formatDate(review.replied_at) }}</small>
            </div>
            <Textarea
              v-model="replyText"
              rows="4"
              class="w-full"
              :disabled="!canManageReviews || saving"
              :maxlength="2000"
              :placeholder="canManageReviews ? 'Write a helpful, professional response...' : 'You have view-only access to reviews.'"
              autoResize
            />
            <small v-if="canManageReviews" class="text-xs text-slate-500">Your reply will be visible to the customer.</small>
          </section>
        </div>
        <div v-else class="rounded-lg border border-slate-200 bg-slate-50 p-6 text-center text-sm text-slate-600">
          Review details are unavailable or you do not have access to this store's review.
        </div>
      </template>
      <template v-if="!loading && review.id && canManageReviews" #footer>
        <div class="flex justify-end">
          <Button :label="review.reply ? 'Update Reply' : 'Send Reply'" icon="pi pi-send" severity="warn" size="small" :loading="saving" :disabled="!replyText.trim()" @click="saveReply" />
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { router as inertiaRouter } from '@inertiajs/vue3'
import { useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import crmService from '@/services/crm.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import Textarea from 'primevue/textarea'
import Skeleton from 'primevue/skeleton'

const route = useRoute()
const toast = useToast()
const authStore = useAuthStore()
const canManageReviews = computed(() => authStore.hasPermission('crm.reviews.manage'))

const loading = ref(false)
const saving = ref(false)
const review = ref<any>({})
const replyText = ref('')
const ratingDetails = computed(() => [
  { label: 'Appearance', value: review.value.appearance_rating },
  { label: 'Assembly', value: review.value.assembly_rating },
  { label: 'Product quality', value: review.value.quality_rating },
  { label: 'Value for money', value: review.value.value_rating },
  { label: 'Met expectations', value: review.value.expectations_rating },
].filter(item => item.value !== null && item.value !== undefined))

const formatStatus = (value: string) => String(value || 'pending').replaceAll('_', ' ').replace(/\b\w/g, letter => letter.toUpperCase())
const statusSeverity = (value: string) => value === 'replied' ? 'success' : 'warn'
const formatDate = (value?: string | null) => value
  ? new Date(value).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' })
  : '-'

const loadReview = async () => {
  loading.value = true
  try {
    const response = await crmService.getReview(String(route.params.id))
    review.value = response?.data || {}
    replyText.value = review.value.reply || ''
  } catch (error: any) {
    review.value = {}
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load review', life: 3000 })
  } finally {
    loading.value = false
  }
}

const saveReply = async () => {
  if (!canManageReviews.value || !replyText.value.trim() || saving.value) return
  saving.value = true
  try {
    await crmService.replyReview(String(route.params.id), { reply: replyText.value.trim() })
    toast.add({ severity: 'success', summary: 'Reply Sent', detail: 'Your response was saved and the customer was notified.', life: 2500 })
    await loadReview()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to save reply', life: 3000 })
  } finally {
    saving.value = false
  }
}

const goBack = () => inertiaRouter.visit('/crm/reviews')

onMounted(loadReview)
</script>
