<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-slate-900">Review Purchased Item</h1>
      <Button label="Back" severity="secondary" outlined @click="goBack" />
    </div>

    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <div v-if="loading" class="space-y-3">
          <Skeleton v-for="idx in 5" :key="idx" height="1.2rem" />
        </div>
        <div v-else-if="selectedItem" class="space-y-4">
          <div class="rounded-xl border border-slate-200 p-3 text-sm">
            <p><span class="text-slate-500">Order #:</span> <span class="font-semibold">{{ order?.order_number }}</span></p>
            <p><span class="text-slate-500">Item:</span> <span class="font-semibold">{{ selectedItem.product_name }}</span></p>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-700">Rating</label>
            <Rating v-model="form.rating" :cancel="false" />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-700">Review (optional)</label>
            <Textarea v-model="form.review_text" rows="4" fluid placeholder="Share your experience with this product." />
          </div>

          <Button label="Submit Review" severity="info" :loading="submitting" @click="submitReview" />
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ecommerceService from '@/services/ecommerce.service'
import Textarea from 'primevue/textarea'
import Rating from 'primevue/rating'
import { showAlert } from '@/utils/swal'
defineOptions({
  layout: EcommerceMobileWrapper,
})


const route = useRoute()
const router = useRouter()
const loading = ref(false)
const submitting = ref(false)
const order = ref<any>(null)
const form = reactive({
  rating: 5,
  review_text: '',
})

const selectedItem = computed(() => {
  const itemId = Number(route.params.itemId)
  return (order.value?.items || []).find((item: any) => Number(item.id) === itemId) || null
})

async function loadOrder() {
  loading.value = true
  try {
    const response = await ecommerceService.getOrder(route.params.id as string)
    order.value = response.data?.data || null
    if (!selectedItem.value || !selectedItem.value.can_review) {
      showAlert({ severity: 'warn', summary: 'Not Allowed', detail: 'Review is not available for this item.' })
      goBack()
    }
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load order item.' })
    goBack()
  } finally {
    loading.value = false
  }
}

async function submitReview() {
  if (!selectedItem.value) return
  if (!form.rating || form.rating < 1) {
    showAlert({ severity: 'warn', summary: 'Required', detail: 'Please provide at least 1 star.' })
    return
  }

  submitting.value = true
  try {
    await ecommerceService.submitItemReview(selectedItem.value.id, {
      rating: Number(form.rating),
      review_text: form.review_text.trim() || undefined,
    })
    showAlert({ severity: 'success', summary: 'Submitted', detail: 'Thank you for your review!' })
    goBack()
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Unable to submit review.' })
  } finally {
    submitting.value = false
  }
}

function goBack() {
  router.push({ name: 'ecommerce.order-detail', params: { id: route.params.id } })
}

onMounted(loadOrder)
</script>

