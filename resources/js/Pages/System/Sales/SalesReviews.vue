<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">Reviews Index</h1>
        <p class="text-sm text-gray-500">Track ratings, feedback, and customer replies.</p>
      </div>
      <Button severity="info" outlined icon="pi pi-refresh" label="Refresh" :loading="loading" @click="loadReviews" />
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
      <Card class="rounded-2xl border border-gray-100 shadow-sm">
        <template #content><div class="p-4"><p class="text-xs uppercase text-gray-500">Total Reviews</p><p class="mt-2 text-2xl font-semibold text-gray-900">{{ summary.total_reviews }}</p></div></template>
      </Card>
      <Card class="rounded-2xl border border-gray-100 shadow-sm">
        <template #content><div class="p-4"><p class="text-xs uppercase text-gray-500">Pending</p><p class="mt-2 text-2xl font-semibold text-amber-600">{{ summary.pending_reviews }}</p></div></template>
      </Card>
      <Card class="rounded-2xl border border-gray-100 shadow-sm">
        <template #content><div class="p-4"><p class="text-xs uppercase text-gray-500">Replied</p><p class="mt-2 text-2xl font-semibold text-emerald-600">{{ summary.replied_reviews }}</p></div></template>
      </Card>
      <Card class="rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-4">
            <p class="text-xs uppercase text-gray-500">Average Rating</p>
            <div class="mt-2 flex items-center gap-2 text-amber-500">
              <i v-for="n in 5" :key="n" class="pi" :class="n <= Math.round(summary.average_rating || 0) ? 'pi-star-fill' : 'pi-star'" />
              <span class="text-sm font-semibold text-gray-900">{{ Number(summary.average_rating || 0).toFixed(2) }}</span>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <div class="grid grid-cols-1 gap-3 p-4 md:grid-cols-4">
          <InputText v-model="filters.search" placeholder="Search customer / order / review..." @keyup.enter="loadReviews" />
          <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="All Status" />
          <Select v-model="filters.rating" :options="ratingOptions" optionLabel="label" optionValue="value" placeholder="All Ratings" />
          <Button icon="pi pi-filter" label="Apply Filters" @click="loadReviews" />
        </div>
      </template>
    </Card>

    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <div class="p-4">
          <DataTable :value="reviews" :loading="loading" stripedRows responsiveLayout="scroll">
            <Column header="Product" style="min-width: 220px">
              <template #body="{ data }">
                <div>
                  <p class="font-medium text-gray-900">{{ data.product?.product_name || 'Product' }}</p>
                  <p class="text-xs text-gray-500">SKU: {{ data.product?.sku || '-' }}</p>
                </div>
              </template>
            </Column>
            <Column field="customer_name" header="Customer" style="min-width: 170px" />
            <Column field="rating" header="Rating" style="width: 130px">
              <template #body="{ data }">
                <div class="flex items-center gap-1 text-amber-500">
                  <i v-for="n in 5" :key="n" class="pi" :class="n <= (data.rating || 0) ? 'pi-star-fill' : 'pi-star'" />
                </div>
              </template>
            </Column>
            <Column field="message" header="Review" style="min-width: 280px">
              <template #body="{ data }">
                <p class="line-clamp-2 text-sm text-gray-700">{{ data.message || '-' }}</p>
              </template>
            </Column>
            <Column field="status" header="Status" style="width: 120px">
              <template #body="{ data }">
                <Tag :value="formatStatus(data.status)" :severity="data.status === 'replied' ? 'success' : 'warning'" />
              </template>
            </Column>
            <Column header="Actions" style="width: 180px">
              <template #body="{ data }">
                <div class="flex items-center gap-1">
                  <Button text severity="info" icon="pi pi-eye" label="Detail" @click="openDetail(data)" />
                  <Button text severity="success" icon="pi pi-reply" label="Reply" :disabled="!canManageReviews" @click="openReply(data)" />
                </div>
              </template>
            </Column>
            <template #empty>
              <div class="py-8 text-center text-sm text-gray-500">No reviews found.</div>
            </template>
          </DataTable>
        </div>
      </template>
    </Card>

    <Dialog v-model:visible="showReply" header="Reply to Review" :style="{ width: '540px' }">
      <div class="space-y-3">
        <p class="text-sm text-gray-700">{{ activeReview?.message || '-' }}</p>
        <Textarea v-model="replyText" rows="4" class="w-full" placeholder="Write your response..." />
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" @click="showReply = false" />
        <Button label="Send Reply" icon="pi pi-send" :disabled="!replyText.trim()" @click="submitReply" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'primevue/usetoast'
import salesService from '@/services/sales.service'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'

const authStore = useAuthStore()
const canManageReviews = authStore.hasPermission('sales.reviews.manage')
const toast = useToast()
const router = useRouter()

const loading = ref(false)
const reviews = ref<any[]>([])
const summary = ref<any>({
  total_reviews: 0,
  pending_reviews: 0,
  replied_reviews: 0,
  average_rating: 0,
})

const filters = ref<any>({
  search: '',
  status: '',
  rating: null,
})

const statusOptions = [
  { label: 'All Status', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Replied', value: 'replied' },
]

const ratingOptions = [
  { label: 'All Ratings', value: null },
  { label: '5 Stars', value: 5 },
  { label: '4 Stars', value: 4 },
  { label: '3 Stars', value: 3 },
  { label: '2 Stars', value: 2 },
  { label: '1 Star', value: 1 },
]

const showReply = ref(false)
const replyText = ref('')
const activeReview = ref<any>(null)

const formatStatus = (status: string) => (status || '').replace(/_/g, ' ').replace(/\b\w/g, (s) => s.toUpperCase()) || 'Pending'

const openReply = (review: any) => {
  activeReview.value = review
  replyText.value = review?.reply || ''
  showReply.value = true
}

const openDetail = (review: any) => {
  router.push({ name: 'sales.reviews.detail', params: { id: review.id } })
}

const loadReviews = async () => {
  loading.value = true
  try {
    const res = await salesService.getReviews({
      search: filters.value.search || undefined,
      status: filters.value.status || undefined,
      rating: filters.value.rating ?? undefined,
      per_page: 50,
    })
    reviews.value = res?.data?.data || []
    summary.value = res?.summary || summary.value
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load reviews', life: 3000 })
  } finally {
    loading.value = false
  }
}

const submitReply = async () => {
  if (!activeReview.value) return
  try {
    await salesService.replyReview(activeReview.value.id, { reply: replyText.value })
    toast.add({ severity: 'success', summary: 'Replied', detail: 'Reply saved.', life: 2200 })
    showReply.value = false
    await loadReviews()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to save reply', life: 3000 })
  }
}

onMounted(loadReviews)
</script>
