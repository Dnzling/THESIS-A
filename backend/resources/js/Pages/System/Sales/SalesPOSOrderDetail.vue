<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="goBack" />
        <div>
          <h1 class="text-2xl font-bold text-gray-900">POS Order {{ order?.order_number || '-' }}</h1>
          <p class="text-sm text-gray-500">Order details and delivery status</p>
        </div>
      </div>
      <Button
        v-if="canSendToLogistics"
        icon="pi pi-send"
        severity="success"
        label="Send To Logistics"
        :loading="sendingToLogistics"
        :disabled="sendDisabled"
        @click="sendToLogistics"
      />
    </div>

    <Card v-if="order">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <p class="text-xs text-gray-500">Customer</p>
            <p class="font-semibold">{{ order.customer_name || '-' }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Phone</p>
            <p class="font-semibold">{{ order.customer_phone || '-' }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Total</p>
            <p class="font-semibold">{{ money(order.total_amount) }}</p>
          </div>
        </div>
      </template>
    </Card>

    <Card v-if="order">
      <template #content>
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Items</h3>
        <DataTable :value="order.items || []" class="p-datatable-sm" stripedRows>
          <Column field="product_name" header="Product" />
          <Column field="quantity" header="Qty" />
          <Column header="Unit Price">
            <template #body="{ data }">
              {{ money(data.unit_price) }}
            </template>
          </Column>
          <Column header="Line Total">
            <template #body="{ data }">
              {{ money(data.line_total) }}
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Card v-if="order">
      <template #content>
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Delivery</h3>
        <div v-if="!order.delivery_required" class="text-sm text-gray-500">
          This order is for pickup (no delivery).
        </div>
        <div v-else-if="order.delivery" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <p class="text-xs text-gray-500">Tracking</p>
              <p class="font-semibold">{{ order.delivery?.tracking_number || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Status</p>
              <Tag :value="order.delivery?.status || 'assigned'" :severity="statusSeverity(order.delivery?.status || 'assigned')" />
            </div>
            <div>
              <p class="text-xs text-gray-500">Scheduled</p>
              <p class="font-semibold">Handled by Logistics</p>
            </div>
          </div>

          <div>
            <p class="text-xs text-gray-500">Address</p>
            <p class="font-semibold">{{ order.delivery_address || '-' }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Email</p>
            <p class="font-semibold">{{ order.delivery_email || '-' }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Coordinates</p>
            <p class="font-semibold">{{ order.delivery_latitude || '-' }}, {{ order.delivery_longitude || '-' }}</p>
          </div>

          <Divider />
        </div>
        <div v-else class="text-sm text-gray-500">
          Delivery record not found for this order.
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import salesService from '@/services/sales.service'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Divider from 'primevue/divider'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()
const order = ref<any>(null)
const sendingToLogistics = ref(false)

const loadOrder = async () => {
  const res = await salesService.getPosOrder(Number(route.params.id))
  order.value = res?.data || res
}

const money = (v: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(v || 0))
const statusSeverity = (value: string) => {
  if (value === 'delivered') return 'success'
  if (value === 'out_for_delivery' || value === 'in_transit') return 'info'
  if (value === 'failed_delivery' || value === 'cancelled') return 'danger'
  return 'warning'
}
const goBack = () => router.push({ name: 'sales.pos' })

const canSendToLogistics = computed(() => {
  if (!order.value) return false
  if (!authStore.hasPermission('sales.order.approve')) return false
  if (!order.value.delivery_required) return false
  if (order.value.delivery) return false
  return true
})

const sendDisabled = computed(() => {
  if (!order.value) return true
  if (order.value.payment_status !== 'paid') return true
  return !order.value.delivery_address
})

const sendToLogistics = async () => {
  if (!order.value) return
  sendingToLogistics.value = true
  try {
    await salesService.sendPosOrderToLogistics(order.value.id)
    toast.add({
      severity: 'success',
      summary: 'Queued for Logistics',
      detail: 'Order is ready for dispatch.',
      life: 3000,
    })
    await loadOrder()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Action Failed',
      detail: error?.response?.data?.message || 'Failed to send order to logistics.',
      life: 3000,
    })
  } finally {
    sendingToLogistics.value = false
  }
}

onMounted(async () => {
  await loadOrder()
})
</script>
