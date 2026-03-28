<template>
  <div class="max-w-4xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">Refund Detail</h1>
        <p class="text-sm text-gray-500">Review refund request details.</p>
      </div>
      <Button severity="secondary" outlined icon="pi pi-arrow-left" label="Back" @click="goBack" />
    </div>

    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <div v-if="loading" class="text-sm text-gray-500">Loading...</div>
        <div v-else class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-xs uppercase text-gray-400">Order</div>
              <div class="text-lg font-semibold text-gray-900">{{ refund.order_number || refund.order_id }}</div>
              <div class="text-sm text-gray-500">{{ refund.customer_name || '' }}</div>
            </div>
            <Tag :value="refund.status || 'pending'" :severity="statusSeverity(refund.status)" />
          </div>

          <div>
            <div class="text-xs uppercase text-gray-400">Reason</div>
            <div class="text-sm text-gray-700">{{ refund.reason || '—' }}</div>
          </div>

          <div>
            <div class="text-xs uppercase text-gray-400">Amount</div>
            <div class="text-lg font-semibold text-gray-900">{{ formatMoney(refund.amount) }}</div>
          </div>

          <div>
            <div class="text-xs uppercase text-gray-400">Notes</div>
            <Textarea v-model="notes" rows="3" class="w-full" placeholder="Internal notes..." />
          </div>
        </div>
      </template>
      <template #footer>
        <Button label="Approve" icon="pi pi-check" severity="success" :disabled="!canManageRefunds" @click="updateStatus('approved')" />
        <Button label="Reject" icon="pi pi-times" severity="danger" :disabled="!canManageRefunds" @click="updateStatus('rejected')" />
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
const canManageRefunds = authStore.hasPermission('sales.refunds.manage')

const loading = ref(false)
const refund = ref<any>({})
const notes = ref('')

const loadRefund = async () => {
  loading.value = true
  try {
    const res = await salesService.getRefund(String(route.params.id))
    refund.value = res?.data || {}
    notes.value = refund.value.notes || ''
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load refund', life: 3000 })
  } finally {
    loading.value = false
  }
}

const updateStatus = async (status: 'approved' | 'rejected') => {
  try {
    await salesService.updateRefundStatus(String(route.params.id), { status, notes: notes.value })
    toast.add({ severity: 'success', summary: 'Updated', detail: `Refund ${status}.`, life: 2000 })
    await loadRefund()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to update refund', life: 3000 })
  }
}

const statusSeverity = (status: string) => {
  if (status === 'approved') return 'success'
  if (status === 'rejected') return 'danger'
  return 'warning'
}

const formatMoney = (value: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))

const goBack = () => {
  router.push({ name: 'sales.refunds' })
}

onMounted(loadRefund)
</script>
