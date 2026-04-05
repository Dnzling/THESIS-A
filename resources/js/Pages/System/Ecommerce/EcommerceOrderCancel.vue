<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-slate-900">Request Order Cancellation</h1>
      <Button label="Back" severity="secondary" outlined @click="goBack" />
    </div>

    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <div v-if="loading" class="space-y-3">
          <Skeleton v-for="idx in 5" :key="idx" height="1.2rem" />
        </div>
        <div v-else-if="order" class="space-y-4">
          <div class="rounded-xl border border-slate-200 p-3 text-sm">
            <p><span class="text-slate-500">Order #:</span> <span class="font-semibold">{{ order.order_number }}</span></p>
            <p><span class="text-slate-500">Status:</span> <Tag :value="order.status" class="ml-2" /></p>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-700">Reason</label>
            <Textarea v-model="form.reason" rows="3" fluid placeholder="Tell us why you want to cancel this order." />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-700">Additional details (optional)</label>
            <Textarea v-model="form.details" rows="3" fluid placeholder="Add more context for store verification." />
          </div>

          <Button label="Submit Cancellation Request" severity="danger" :loading="submitting" @click="submitCancellation" />
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ecommerceService from '@/services/ecommerce.service'
import Textarea from 'primevue/textarea'
import { showAlert } from '@/utils/swal'
defineOptions({
  layout: EcommerceMobileWrapper,
})


const route = useRoute()
const router = useRouter()
const loading = ref(false)
const submitting = ref(false)
const order = ref<any>(null)
const form = reactive({ reason: '', details: '' })

async function loadOrder() {
  loading.value = true
  try {
    const response = await ecommerceService.getOrder(route.params.id as string)
    order.value = response.data?.data || null
    if (!order.value?.can_cancel) {
      showAlert({ severity: 'warn', summary: 'Not Allowed', detail: 'This order can no longer be cancelled.' })
      goBack()
    }
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load order.' })
    goBack()
  } finally {
    loading.value = false
  }
}

async function submitCancellation() {
  if (!form.reason.trim()) {
    showAlert({ severity: 'warn', summary: 'Required', detail: 'Please provide a reason.' })
    return
  }

  submitting.value = true
  try {
    await ecommerceService.requestOrderCancellation(route.params.id as string, {
      reason: form.reason.trim(),
      details: form.details.trim() || undefined,
    })
    showAlert({ severity: 'success', summary: 'Submitted', detail: 'Cancellation request sent for store verification.' })
    router.push({ name: 'ecommerce.order-detail', params: { id: route.params.id } })
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Unable to submit cancellation.' })
  } finally {
    submitting.value = false
  }
}

function goBack() {
  router.push({ name: 'ecommerce.order-detail', params: { id: route.params.id } })
}

onMounted(loadOrder)
</script>

