<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <Card class="border border-gray-100 shadow-sm rounded-2xl">
      <template #content>
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900">POS Overview</h1>
            <p class="text-sm text-gray-500">Sales stats and recent POS orders.</p>
          </div>
          <Button severity="info" outlined label="Refresh" icon="pi pi-refresh" @click="refreshAll" />
        </div>
      </template>
    </Card>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card class="border border-gray-100 shadow-sm rounded-2xl">
        <template #content>
          <p class="text-xs uppercase text-gray-500">Payments (30d)</p>
          <p class="text-2xl font-semibold">{{ paymentAnalytics.total_payments }}</p>
        </template>
      </Card>
      <Card class="border border-gray-100 shadow-sm rounded-2xl">
        <template #content>
          <p class="text-xs uppercase text-gray-500">Paid Amount</p>
          <p class="text-2xl font-semibold">{{ money(paymentAnalytics.paid_amount) }}</p>
        </template>
      </Card>
      <Card class="border border-gray-100 shadow-sm rounded-2xl">
        <template #content>
          <p class="text-xs uppercase text-gray-500">Pending</p>
          <p class="text-2xl font-semibold">{{ paymentAnalytics.pending_payments }}</p>
        </template>
      </Card>
      <Card class="border border-gray-100 shadow-sm rounded-2xl">
        <template #content>
          <p class="text-xs uppercase text-gray-500">Conversion Rate</p>
          <p class="text-2xl font-semibold">{{ Number(paymentAnalytics.conversion_rate || 0).toFixed(2) }}%</p>
        </template>
      </Card>
    </div>

    <Card class="border border-gray-100 shadow-sm rounded-2xl">
      <template #title>Recent POS Orders</template>
      <template #content>
        <DataTable :value="orders" :loading="loadingOrders" stripedRows>
          <Column field="order_number" header="Order" />
          <Column field="customer_name" header="Customer" />
          <Column field="payment_method" header="Payment" />
          <Column field="payment_status" header="Payment Status" />
          <Column field="total_amount" header="Total">
            <template #body="{data}">{{ money(data.total_amount) }}</template>
          </Column>
          <Column field="created_at" header="Date">
            <template #body="{data}">{{ new Date(data.created_at).toLocaleString('en-PH') }}</template>
          </Column>
          <Column header="Actions">
            <template #body="{data}">
              <div class="flex items-center gap-1">
                <Button text severity="info" icon="pi pi-eye" label="View" @click="viewOrder(data.id)" />
                <Button
                  v-if="data.payment?.checkout_url && data.payment_status !== 'paid'"
                  text
                  severity="warning"
                  icon="pi pi-external-link"
                  label="Open"
                  @click="openCheckout(data.payment.checkout_url)"
                />
                <Button
                  v-if="data.payment?.provider_reference && data.payment_status !== 'paid'"
                  text
                  severity="info"
                  icon="pi pi-refresh"
                  label="Sync"
                  :disabled="!canManagePos"
                  @click="syncOrderPayment(data.id)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import salesService from '@/services/sales.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const canManagePos = authStore.hasPermission('sales.pos.manage')

const orders = ref<any[]>([])
const loadingOrders = ref(false)
const paymentAnalytics = ref<any>({
  total_payments: 0,
  paid_amount: 0,
  pending_payments: 0,
  conversion_rate: 0,
})

const loadOrders = async () => {
  loadingOrders.value = true
  try {
    const res = await salesService.getPosOrders({ per_page: 20 })
    orders.value = res?.data?.data || []
  } finally {
    loadingOrders.value = false
  }
}

const loadPaymentAnalytics = async () => {
  const res = await salesService.getPaymentAnalytics()
  paymentAnalytics.value = res?.data || paymentAnalytics.value
}

const syncOrderPayment = async (orderId: number) => {
  try {
    await salesService.syncPosOrderPayment(orderId)
    toast.add({ severity: 'success', summary: 'Synced', detail: 'Payment status refreshed.', life: 2200 })
    await Promise.all([loadOrders(), loadPaymentAnalytics()])
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Sync failed', detail: error?.response?.data?.message || 'Unable to sync payment status.', life: 2800 })
  }
}

const openCheckout = (url: string) => {
  if (!url) return
  window.open(url, '_blank', 'noopener')
}

const viewOrder = (orderId: number) => {
  router.push({ name: 'sales.pos.order-detail', params: { id: orderId } })
}

const refreshAll = async () => {
  await Promise.all([loadOrders(), loadPaymentAnalytics()])
}

const money = (v: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(v || 0))

onMounted(() => {
  refreshAll()
})
</script>
