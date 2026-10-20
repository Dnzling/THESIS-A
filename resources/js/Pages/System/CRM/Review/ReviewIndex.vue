<template>
  <div class="min-h-screen space-y-4 p-4 text-xs">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-bold text-slate-900">Reviews</h1>
        <p class="text-xs text-slate-500">Track ratings, feedback, and customer replies.</p>
      </div>
      <Button severity="warn" outlined icon="pi pi-refresh" label="Refresh" size="small" :loading="loading" @click="loadReviews" />
    </div>

    <Card class="border border-slate-200 shadow-sm">
      <template #content>
        <div class="mb-5 grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(220px,2fr)_1fr_1fr_auto_auto]">
          <IconField><InputIcon class="pi pi-search" /><InputText v-model="filters.search" placeholder="Search reviews" class="w-full text-sm" size="small" @keyup.enter="loadReviews" /></IconField>
          <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="All Status" class="w-full text-sm" size="small" />
          <Select v-model="filters.rating" :options="ratingOptions" optionLabel="label" optionValue="value" placeholder="All Ratings" class="w-full text-sm" size="small" />
          <Button label="Apply" severity="warn" size="small" @click="loadReviews" />
          <Button v-if="hasActiveFilters" label="Reset" icon="pi pi-filter-slash" outlined severity="secondary" size="small" @click="resetFilters" />
        </div>
        <div v-if="loading" class="space-y-3 py-3" aria-label="Loading reviews"><Skeleton v-for="row in 6" :key="row" height="2.5rem" /></div>
        <DataTable v-else :value="reviews" size="small" class="text-xs" rowHover paginator :rows="15" :rowsPerPageOptions="[15, 25, 50]" scrollable @row-click="openDetail($event.data)">
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
            <Column header="Actions" style="width: 180px">
              <template #body="{ data }">
                <div class="flex items-center gap-1">
                  <Button text severity="warn" icon="pi pi-eye" label="Detail" size="small" @click.stop="openDetail(data)" />
                  <Button text severity="secondary" icon="pi pi-reply" label="Reply" size="small" :disabled="!canManageReviews" @click.stop="openReply(data)" />
                </div>
              </template>
            </Column>
            <template #empty>
              <div class="py-8 text-center text-sm text-gray-500">No reviews found.</div>
            </template>
          </DataTable>
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
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'primevue/usetoast'
import crmService from '@/services/crm.service'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Skeleton from 'primevue/skeleton'

const authStore = useAuthStore()
const canManageReviews = authStore.hasPermission('sales.reviews.manage')
const toast = useToast()
const route = useRoute()
const router = useRouter()

const loading = ref(true)
const reviews = ref<any[]>([])
const filters = ref<any>({
  search: '',
  status: '',
  rating: null,
})
const hasActiveFilters = computed(() => Boolean(filters.value.search.trim() || filters.value.status || filters.value.rating))
const resetFilters = () => {
  filters.value = { search: '', status: '', rating: null }
  loadReviews()
}

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

const openReply = (review: any) => {
  activeReview.value = review
  replyText.value = review?.reply || ''
  showReply.value = true
}

const openDetail = (review: any) => {
  router.visit(`/crm/reviews/${review.id}`)
}

const loadReviews = async () => {
  loading.value = true
  try {
    const storeId = route.query.store_id ? Number(route.query.store_id) : undefined
    const res = await crmService.getReviews({
      store_id: storeId || undefined,
      search: filters.value.search || undefined,
      status: filters.value.status || undefined,
      rating: filters.value.rating ?? undefined,
      per_page: 50,
    })
    reviews.value = Array.isArray(res?.data?.data) ? res.data.data : (Array.isArray(res?.data) ? res.data : [])
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load reviews', life: 3000 })
  } finally {
    loading.value = false
  }
}

const submitReply = async () => {
  if (!activeReview.value) return
  try {
    await crmService.replyReview(activeReview.value.id, { reply: replyText.value })
    toast.add({ severity: 'success', summary: 'Replied', detail: 'Reply saved.', life: 2200 })
    showReply.value = false
    await loadReviews()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to save reply', life: 3000 })
  }
}

onMounted(loadReviews)
</script>
