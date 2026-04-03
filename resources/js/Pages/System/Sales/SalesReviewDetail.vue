<template>
  <div class="max-w-4xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">Review Detail</h1>
        <p class="text-sm text-gray-500">Customer feedback and reply.</p>
      </div>
      <Button severity="secondary" outlined icon="pi pi-arrow-left" label="Back" @click="goBack" />
    </div>

    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <div v-if="loading" class="text-sm text-gray-500">Loading...</div>
        <div v-else class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-xs uppercase text-gray-400">Customer</div>
              <div class="text-lg font-semibold text-gray-900">{{ review.customer_name || '—' }}</div>
              <div class="text-sm text-gray-500">{{ review.customer_contact || '' }}</div>
            </div>
            <Tag :value="review.status || 'pending'" :severity="review.status === 'replied' ? 'success' : 'warning'" />
          </div>

          <div>
            <div class="text-xs uppercase text-gray-400">Rating</div>
            <div class="flex items-center gap-1 text-amber-500">
              <i v-for="n in 5" :key="n" class="pi" :class="n <= (review.rating || 0) ? 'pi-star-fill' : 'pi-star'" />
            </div>
          </div>

          <div>
            <div class="text-xs uppercase text-gray-400">Review</div>
            <div class="text-sm text-gray-700">{{ review.message || '—' }}</div>
          </div>

          <div>
            <div class="text-xs uppercase text-gray-400">Reply</div>
            <Textarea v-model="replyText" rows="4" class="w-full" placeholder="Write a reply..." />
          </div>
        </div>
      </template>
      <template #footer>
        <Button label="Save Reply" icon="pi pi-send" :disabled="!canManageReviews" @click="saveReply" />
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import salesService from '@/services/sales.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const canManageReviews = authStore.hasPermission('sales.reviews.manage')

const loading = ref(false)
const review = ref<any>({})
const replyText = ref('')

const loadReview = async () => {
  loading.value = true
  try {
    const res = await salesService.getReview(String(route.params.id))
    review.value = res?.data || {}
    replyText.value = review.value.reply || ''
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load review', life: 3000 })
  } finally {
    loading.value = false
  }
}

const saveReply = async () => {
  try {
    await salesService.replyReview(String(route.params.id), { reply: replyText.value })
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Reply updated.', life: 2000 })
    await loadReview()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to save reply', life: 3000 })
  }
}

const goBack = () => {
  router.push({ name: 'sales.reviews' })
}

onMounted(loadReview)
</script>
