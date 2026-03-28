<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-slate-900">Request Item Return</h1>
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
            <p><span class="text-slate-500">Quantity:</span> <span class="font-semibold">{{ selectedItem.quantity }}</span></p>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-700">Return quantity</label>
            <InputNumber v-model="form.requested_quantity" :min="1" :max="selectedItem.quantity" fluid />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-700">Reason</label>
            <Textarea v-model="form.reason" rows="3" fluid placeholder="Why do you want to return this item?" />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-700">Additional details (optional)</label>
            <Textarea v-model="form.details" rows="3" fluid placeholder="Add details (damage, wrong item, etc.)." />
          </div>

          <Button label="Submit Return Request" severity="info" :loading="submitting" @click="submitReturn" />
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import ecommerceService from '@/services/ecommerce.service'
import Textarea from 'primevue/textarea'
import InputNumber from 'primevue/inputnumber'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const submitting = ref(false)
const order = ref<any>(null)
const form = reactive({
  requested_quantity: 1,
  reason: '',
  details: '',
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
    if (!selectedItem.value || !selectedItem.value.can_return) {
      toast.add({ severity: 'warn', summary: 'Not Allowed', detail: 'Return request is not available for this item.', life: 2200 })
      goBack()
      return
    }
    form.requested_quantity = Math.max(1, Math.min(Number(selectedItem.value.quantity || 1), form.requested_quantity))
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load order item.', life: 2500 })
    goBack()
  } finally {
    loading.value = false
  }
}

async function submitReturn() {
  if (!selectedItem.value) return
  if (!form.reason.trim()) {
    toast.add({ severity: 'warn', summary: 'Required', detail: 'Please provide a reason.', life: 1800 })
    return
  }

  submitting.value = true
  try {
    await ecommerceService.requestOrderReturn(selectedItem.value.id, {
      reason: form.reason.trim(),
      details: form.details.trim() || undefined,
      requested_quantity: Number(form.requested_quantity || 1),
    })
    toast.add({ severity: 'success', summary: 'Submitted', detail: 'Return request sent for store verification.', life: 2200 })
    goBack()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Unable to submit return request.', life: 2500 })
  } finally {
    submitting.value = false
  }
}

function goBack() {
  router.push({ name: 'ecommerce.order-detail', params: { id: route.params.id } })
}

onMounted(loadOrder)
</script>
