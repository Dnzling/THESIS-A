<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">Reviews</h1>
        <p class="text-sm text-gray-500">Manage customer feedback and replies.</p>
      </div>
      <Button severity="info" outlined icon="pi pi-refresh" label="Refresh" />
    </div>

    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <DataTable :value="reviews" :loading="loading" stripedRows>
          <Column field="product_id" header="Product" />
          <Column field="customer_name" header="Customer" />
          <Column field="rating" header="Rating">
            <template #body="{ data }">
              <div class="flex items-center gap-1 text-amber-500">
                <i v-for="n in 5" :key="n" class="pi" :class="n <= data.rating ? 'pi-star-fill' : 'pi-star'" />
              </div>
            </template>
          </Column>
          <Column field="message" header="Review" />
          <Column field="status" header="Status">
            <template #body="{ data }">
              <Tag :value="data.status" :severity="data.status === 'replied' ? 'success' : 'warning'" />
            </template>
          </Column>
          <Column header="Actions">
            <template #body="{ data }">
              <Button
                text
                severity="info"
                icon="pi pi-reply"
                label="Reply"
                :disabled="!canManageReviews"
                @click="openReply(data)"
              />
              <Button
                text
                severity="secondary"
                icon="pi pi-eye"
                label="Detail"
                @click="openDetail(data)"
              />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="showReply" header="Reply to Review" :style="{ width: '520px' }">
      <div class="space-y-3">
        <div class="text-sm text-gray-600">
          <div class="font-semibold text-gray-900">{{ activeReview?.product }}</div>
          <div>{{ activeReview?.message }}</div>
        </div>
        <Textarea v-model="replyText" rows="4" class="w-full" placeholder="Write a reply..." />
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" @click="showReply = false" />
        <Button label="Send Reply" icon="pi pi-send" :disabled="!replyText" @click="submitReply" />
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
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'

const authStore = useAuthStore()
const canManageReviews = authStore.hasPermission('sales.reviews.manage')

const toast = useToast()
const router = useRouter()
const loading = ref(false)
const reviews = ref<any[]>([])

const showReply = ref(false)
const replyText = ref('')
const activeReview = ref<any>(null)

const openReply = (review: any) => {
  activeReview.value = review
  replyText.value = ''
  showReply.value = true
}

const openDetail = (review: any) => {
  router.push({ name: 'sales.reviews.detail', params: { id: review.id } })
}

const loadReviews = async () => {
  loading.value = true
  try {
    const res = await salesService.getReviews()
    reviews.value = res?.data?.data || []
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
    replyText.value = ''
    await loadReviews()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to save reply', life: 3000 })
  }
}

onMounted(loadReviews)
</script>
