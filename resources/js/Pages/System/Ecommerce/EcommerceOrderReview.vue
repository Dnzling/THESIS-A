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

          <div class="rounded-2xl border border-orange-200 bg-orange-50/50 p-4 sm:p-5">
            <div class="flex flex-wrap items-end justify-between gap-3">
              <div>
                <label class="text-sm font-semibold text-slate-900">Overall product rating <span class="text-red-600">*</span></label>
                <p class="mt-1 text-xs text-slate-600">How would you rate this item overall?</p>
              </div>
              <span class="text-sm font-semibold text-orange-700">{{ ratingLabel }}</span>
            </div>
            <Rating v-model="form.rating" :cancel="false" class="mt-3" />
          </div>

          <section class="rounded-2xl border border-slate-200 bg-white">
            <button type="button" class="flex w-full items-center justify-between gap-3 p-4 text-left" :aria-expanded="showDetailedRatings" @click="showDetailedRatings = !showDetailedRatings">
              <span>
                <span class="block text-sm font-semibold text-slate-800">Add detailed ratings <span class="font-normal text-slate-500">(optional)</span></span>
                <span class="mt-1 block text-xs text-slate-500">Rate specific aspects if you want to share more detail.</span>
              </span>
              <i class="pi shrink-0 text-slate-500" :class="showDetailedRatings ? 'pi-chevron-up' : 'pi-chevron-down'" />
            </button>
            <div v-if="showDetailedRatings" class="space-y-3 border-t border-slate-100 p-4">
              <div v-for="criterion in reviewCriteria" :key="criterion.key" class="flex flex-wrap items-center justify-between gap-3">
                <label :for="`review-${criterion.key}`" class="text-sm text-slate-700">{{ criterion.label }}</label>
                <div class="flex items-center gap-2">
                  <Rating :id="`review-${criterion.key}`" v-model="form[criterion.key]" :cancel="false" />
                  <span class="w-7 text-right text-xs text-slate-500">{{ form[criterion.key] || '-' }}/5</span>
                </div>
              </div>
            </div>
          </section>

          <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white p-3">
              <input v-model="form.is_recommended" type="checkbox" class="h-5 w-5 rounded border-slate-300 text-orange-600 focus:ring-orange-500" />
              <span class="text-sm font-medium text-slate-700">I would recommend this product</span>
          </label>

          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-700">Review (optional)</label>
            <Textarea v-model="form.review_text" rows="4" fluid placeholder="Share your experience with this product." />
          </div>

          <div class="space-y-2">
            <label for="review-attachment" class="text-sm font-semibold text-slate-700">Photo (optional)</label>
            <input id="review-attachment" ref="attachmentInput" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="onAttachmentChange" />
            <div class="flex flex-wrap items-center gap-3 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3">
              <img v-if="attachmentPreview" :src="attachmentPreview" alt="Review photo preview" decoding="async" class="h-16 w-16 rounded-lg border border-slate-200 object-cover" />
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-slate-800">{{ attachment?.name || 'Add a photo of your item' }}</p>
                <p class="mt-1 text-xs text-slate-500">JPG, PNG, or WebP up to 5 MB.</p>
              </div>
              <Button :label="attachment ? 'Change picture' : 'Choose picture'" :icon="attachment ? 'pi pi-image' : 'pi pi-upload'" severity="secondary" outlined size="small" @click="attachmentInput?.click()" />
              <Button v-if="attachment" icon="pi pi-times" aria-label="Remove picture" severity="danger" text rounded size="small" @click="removeAttachment" />
            </div>
          </div>

          <div class="flex justify-end border-t border-slate-100 pt-4">
            <Button label="Submit review" icon="pi pi-send" :loading="submitting" :disabled="!form.rating" class="!border-orange-600 !bg-orange-600 !text-white hover:!border-orange-700 hover:!bg-orange-700" @click="submitReview" />
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ecommerceService from '@/services/ecommerce.service'
import Textarea from 'primevue/textarea'
import Rating from 'primevue/rating'
import { confirmAlert, showAlert } from '@/utils/swal'
defineOptions({
  layout: EcommerceMobileWrapper,
})


const route = useRoute()
const router = useRouter()
const loading = ref(false)
const submitting = ref(false)
const order = ref<any>(null)
const attachment = ref<File | null>(null)
const attachmentPreview = ref('')
const attachmentInput = ref<HTMLInputElement | null>(null)
const showDetailedRatings = ref(false)
const form = reactive({
  rating: 0,
  appearance_rating: 0,
  assembly_rating: 0,
  quality_rating: 0,
  value_rating: 0,
  expectations_rating: 0,
  is_recommended: true,
  review_text: '',
})

type ReviewRatingKey = 'appearance_rating' | 'assembly_rating' | 'quality_rating' | 'value_rating' | 'expectations_rating'
const reviewCriteria: Array<{ key: ReviewRatingKey; label: string }> = [
  { key: 'appearance_rating', label: 'Appearance' },
  { key: 'assembly_rating', label: 'Ease of assembly/installation' },
  { key: 'quality_rating', label: 'Product quality' },
  { key: 'value_rating', label: 'Value for money' },
  { key: 'expectations_rating', label: 'Works as expected' },
]

const ratingLabel = computed(() => {
  const labels: Record<number, string> = { 1: 'Poor', 2: 'Fair', 3: 'Good', 4: 'Very good', 5: 'Excellent' }
  return form.rating > 0 ? `${labels[form.rating]} · ${form.rating}/5` : 'Select 1 to 5 stars'
})

const selectedItem = computed(() => {
  const itemId = Number(route.params.itemId)
  return (order.value?.items || []).find((item: any) => Number(item.id) === itemId) || null
})

function clearAttachmentPreview() {
  if (attachmentPreview.value) URL.revokeObjectURL(attachmentPreview.value)
  attachmentPreview.value = ''
}

function onAttachmentChange(event: Event) {
  clearAttachmentPreview()
  const input = event.target as HTMLInputElement
  const file = input.files?.[0] || null
  if (file && file.size > 5 * 1024 * 1024) {
    attachment.value = null
    input.value = ''
    showAlert({ severity: 'warn', summary: 'Photo too large', detail: 'Choose an image up to 5 MB.' })
    return
  }
  attachment.value = file
  if (file) attachmentPreview.value = URL.createObjectURL(file)
}

function removeAttachment() {
  attachment.value = null
  clearAttachmentPreview()
  if (attachmentInput.value) attachmentInput.value.value = ''
}

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

  const confirmed = await confirmAlert({
    title: 'Submit your review?',
    text: 'Your rating and feedback will be shared with other shoppers.',
    confirmText: 'Submit review',
    cancelText: 'Keep editing',
  })
  if (!confirmed) return

  submitting.value = true
  try {
    const response = await ecommerceService.submitItemReview(selectedItem.value.id, {
      rating: Number(form.rating),
      appearance_rating: form.appearance_rating || undefined,
      assembly_rating: form.assembly_rating || undefined,
      quality_rating: form.quality_rating || undefined,
      value_rating: form.value_rating || undefined,
      expectations_rating: form.expectations_rating || undefined,
      is_recommended: Boolean(form.is_recommended),
      review_text: form.review_text.trim() || undefined,
      attachment: attachment.value,
    })

    const productRating = response.data?.data?.product_rating
    if (productRating) {
      window.dispatchEvent(new CustomEvent('ecommerce-product-rating-updated', {
        detail: productRating,
      }))
    }

    await showAlert({
      severity: 'success',
      summary: 'Thank you for your review.',
    })
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
onBeforeUnmount(clearAttachmentPreview)
</script>
