<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-slate-900">Cancel Order</h1>
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
            <label class="text-sm font-semibold text-slate-700">Reason *</label>
            <Select v-model="form.reason" :options="cancellationReasons" placeholder="Select a reason" fluid />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-700">Additional details {{ form.reason === 'Other' ? '*' : '(optional)' }}</label>
            <Textarea v-model="form.details" rows="3" fluid placeholder="Add more context about your cancellation." />
          </div>

          <p class="text-xs text-slate-500">Pending orders are cancelled immediately. If you have paid, Finance will handle the refund.</p>
          <Button label="Cancel Order" severity="danger" :loading="submitting" @click="confirmCancellation" />
        </div>
      </template>
    </Card>
  </div>
  <ConfirmDialog />
</template>

<script setup lang="ts">
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ecommerceService from '@/services/ecommerce.service'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import { showAlert } from '@/utils/swal'
defineOptions({
  layout: EcommerceMobileWrapper,
})


const route = useRoute()
const router = useRouter()
const confirm = useConfirm()
const loading = ref(false)
const submitting = ref(false)
const order = ref<any>(null)
const form = reactive({ reason: '', details: '' })
const cancellationReasons = [
  'Changed my mind',
  'Ordered the wrong item or quantity',
  'Placed a duplicate order',
  'Delivery date no longer works',
  'Payment issue',
  'Other',
]

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

function confirmCancellation() {
  if (!form.reason.trim()) {
    showAlert({ severity: 'warn', summary: 'Required', detail: 'Please select a cancellation reason.' })
    return
  }
  if (form.reason === 'Other' && !form.details.trim()) {
    showAlert({ severity: 'warn', summary: 'Required', detail: 'Please describe your cancellation reason.' })
    return
  }

  confirm.require({
    header: 'Cancel this order?',
    message: 'This will cancel the order immediately. This action cannot be undone.',
    rejectProps: { label: 'Keep Order', outlined: true },
    acceptProps: { label: 'Cancel Order', severity: 'danger' },
    accept: () => {
      confirm.close()
      void submitCancellation()
    },
  })
}

async function submitCancellation() {
  submitting.value = true
  try {
    await ecommerceService.requestOrderCancellation(route.params.id as string, {
      reason: form.reason.trim(),
      details: form.details.trim() || undefined,
    })
    showAlert({ severity: 'success', summary: 'Order Cancelled', detail: 'Your order was cancelled. If you already paid, your refund is pending with Finance.' })
    router.push({ name: 'ecommerce.order-detail', params: { id: route.params.id } })
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Unable to cancel this order.' })
  } finally {
    submitting.value = false
  }
}

function goBack() {
  router.push({ name: 'ecommerce.order-detail', params: { id: route.params.id } })
}

onMounted(loadOrder)
</script>

