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
      <div class="flex items-center gap-2">
        <Button icon="pi pi-refresh" severity="secondary" text rounded aria-label="Refresh order" @click="loadOrder" />
        <Button
          v-if="canSendToLogistics"
          icon="pi pi-send"
          severity="success"
          label="Mark Ready for Dispatch"
          :loading="sendingToLogistics"
          :disabled="sendDisabled"
          @click="confirmReadyForDispatch"
        />
        <Button icon="pi pi-print" severity="secondary" label="Print Receipt" @click="printReceipt" />
      </div>
    </div>

    <Card v-if="order">
      <template #content>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
          <div>
            <p class="text-xs text-gray-500">Customer</p>
            <p class="font-semibold">{{ order.customer_name || '-' }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Phone</p>
            <p class="font-semibold">{{ order.customer_phone || '-' }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Fulfillment</p>
            <Tag :value="order.delivery_required ? 'Delivery' : 'Pickup'" :severity="order.delivery_required ? 'info' : 'secondary'" />
          </div>
          <div>
            <p class="text-xs text-gray-500">Payment</p>
            <p class="font-semibold">{{ formatLabel(order.payment_method) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Payment Status</p>
            <Tag :value="formatLabel(order.payment_status)" :severity="paymentStatusSeverity(order.payment_status)" />
          </div>
        </div>
      </template>
    </Card>

    <Card v-if="order">
      <template #content>
        <h3 class="mb-4 text-lg font-semibold text-gray-800">Order Summary</h3>
        <div class="ml-auto max-w-md space-y-2 text-sm">
          <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span>{{ money(order.subtotal) }}</span></div>
          <div v-if="Number(order.discount_amount || 0) > 0" class="flex justify-between"><span class="text-slate-500">Discount</span><span>-{{ money(order.discount_amount) }}</span></div>
          <div v-if="Number(order.tax_amount || 0) > 0" class="flex justify-between"><span class="text-slate-500">Tax</span><span>{{ money(order.tax_amount) }}</span></div>
          <div v-if="order.delivery_required" class="flex justify-between"><span class="text-slate-500">Shipping Fee</span><span>{{ money(order.shipping_fee) }}</span></div>
          <Divider />
          <div class="flex justify-between text-base font-bold"><span>Total</span><span class="text-blue-600">{{ money(order.total_amount) }}</span></div>
          <div v-if="hasCommission" class="rounded-lg bg-amber-50 p-2 text-xs text-amber-800">
            Store plan commission: {{ formatRate(order.commission_rate) }} ({{ money(order.commission_amount) }}). This is not added to the customer total.
          </div>
        </div>
      </template>
    </Card>

    <Card v-if="order">
      <template #content>
        <h3 class="mb-4 text-lg font-semibold text-gray-800">Payment</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
          <div>
            <p class="text-xs uppercase tracking-wide text-gray-500">Payment Method</p>
            <p class="font-semibold">{{ formatLabel(order.payment?.payment_method || order.payment_method) }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-gray-500">Payment Status</p>
            <Tag :value="formatLabel(order.payment?.status || order.payment_status)" :severity="paymentStatusSeverity(order.payment?.status || order.payment_status)" />
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-gray-500">Reference</p>
            <p class="break-all font-mono text-sm font-semibold">{{ order.payment_reference || order.payment?.provider_reference || '-' }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-gray-500">Amount Tendered</p>
            <p class="font-semibold">{{ money(order.amount_tendered) }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-gray-500">Change</p>
            <p class="font-semibold text-emerald-600">{{ money(order.change_amount) }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-gray-500">Paid At</p>
            <p class="font-semibold">{{ formatDateTime(order.paid_at || order.payment?.paid_at) }}</p>
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
        <div class="mt-5 ml-auto max-w-sm space-y-2 border-t border-gray-200 pt-4 text-sm">
          <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span>{{ money(order.subtotal) }}</span></div>
          <div class="flex justify-between"><span class="text-gray-500">VATable Sales</span><span>{{ money(vatableSales) }}</span></div>
          <div class="flex justify-between"><span class="text-gray-500">VAT Included (12%)</span><span>{{ money(order.tax_amount) }}</span></div>
          <div v-if="order.delivery_required" class="flex justify-between"><span class="text-gray-500">Shipping Fee</span><span>{{ money(order.shipping_fee) }}</span></div>
          <div v-if="Number(order.discount_amount || 0) > 0" class="flex justify-between"><span class="text-gray-500">Discount</span><span>- {{ money(order.discount_amount) }}</span></div>
          <div class="flex justify-between border-t border-gray-200 pt-2 text-base font-semibold"><span>Order Total</span><span>{{ money(order.total_amount) }}</span></div>
          <div class="flex justify-between"><span class="text-gray-500">Platform Commission ({{ Number(order.commission_percentage || 0).toFixed(2) }}%)</span><span>- {{ money(order.commission_amount) }}</span></div>
          <div class="flex justify-between font-semibold text-emerald-700"><span>Store Net Proceeds</span><span>{{ money(storeNetAmount) }}</span></div>
        </div>
      </template>
    </Card>

    <Card v-if="order">
      <template #content>
        <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ order.delivery_required ? 'Delivery' : 'Pickup' }}</h3>
        <div v-if="!order.delivery_required" class="text-sm text-gray-500">
          This order is marked for pickup. No shipping fee applies.
        </div>
        <div v-else-if="order.delivery" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <p class="text-xs text-gray-500">Tracking</p>
              <p class="font-semibold">{{ order.delivery?.tracking_number || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Status</p>
              <Tag :value="deliveryStatusLabel(order.delivery?.status || 'assigned')" :severity="statusSeverity(order.delivery?.status || 'assigned')" />
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
        <div v-else class="space-y-2 text-sm">
          <Tag value="Awaiting Logistics" severity="warning" />
          <div><span class="text-gray-500">Address:</span> <span class="font-semibold">{{ order.delivery_address || '-' }}</span></div>
          <div><span class="text-gray-500">Shipping Fee:</span> <span class="font-semibold">{{ money(order.shipping_fee) }}</span></div>
        </div>
      </template>
    </Card>
  </div>
  <ConfirmDialog />
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import salesService from '@/services/sales.service'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Divider from 'primevue/divider'
import ConfirmDialog from 'primevue/confirmdialog'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()
const confirm = useConfirm()
const order = ref<any>(null)
const sendingToLogistics = ref(false)
const vatableSales = computed(() => Math.max(
  0,
  Number(order.value?.subtotal || 0)
    - Number(order.value?.discount_amount || 0)
    - Number(order.value?.tax_amount || 0),
))
const storeNetAmount = computed(() => {
  const storedNet = Number(order.value?.store_net_amount || 0)
  if (storedNet > 0) return storedNet

  return Math.max(
    0,
    Number(order.value?.total_amount || 0) - Number(order.value?.commission_amount || 0),
  )
})

const loadOrder = async () => {
  const res = await salesService.getPosOrder(Number(route.params.id))
  order.value = res?.data || res
}

const money = (v: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(v || 0))
const formatLabel = (value: string) => {
  if (value === 'card') return 'Online Payment'
  if (value === 'gcash') return 'GCash'
  return String(value || '-').replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
}
const formatRate = (value: number | string) => `${Number(value || 0).toFixed(2).replace(/\.00$/, '')}%`
const formatDateTime = (value: string | null) => value
  ? new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
  : '-'
const hasCommission = computed(() => Number(order.value?.commission_rate || 0) > 0)
const paymentStatusSeverity = (value: string) => {
  if (value === 'paid') return 'success'
  if (value === 'failed' || value === 'cancelled') return 'danger'
  if (value === 'processing') return 'info'
  return 'warning'
}
const statusSeverity = (value: string) => {
  if (value === 'delivered') return 'success'
  if (value === 'out_for_delivery' || value === 'in_transit') return 'info'
  if (value === 'failed_delivery' || value === 'cancelled') return 'danger'
  return 'warning'
}
const deliveryStatusLabel = (value: string) =>
  String(value || '-').replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
const goBack = () => router.push({ name: 'sales.pos' })

const canSendToLogistics = computed(() => {
  if (!order.value) return false
  if (!authStore.hasPermission('sales.orders.manage')) return false
  if (!order.value.delivery_required) return false
  const orderStatus = String(order.value.status || '').toLowerCase()
  const deliveryStatus = String(order.value.delivery?.status || '').toLowerCase()
  const deliveryNotStarted = !order.value.delivery || ['pending', 'ready_for_dispatch'].includes(deliveryStatus)
  return ['pending', 'pending_payment', 'completed'].includes(orderStatus) && deliveryNotStarted
})

const sendDisabled = computed(() => {
  if (!order.value) return true
  if (order.value.payment_status !== 'paid') return true
  return !order.value.delivery_address
})

const confirmReadyForDispatch = () => {
  if (!order.value) return
  confirm.require({
    header: 'Mark Order Ready for Dispatch?',
    message: `Mark ${order.value.order_number || 'this order'} and its delivery as Ready for Dispatch? Logistics can then assign a driver.`,
    icon: 'pi pi-exclamation-triangle',
    rejectProps: { label: 'Cancel', outlined: true },
    acceptProps: { label: 'Mark Ready for Dispatch', severity: 'success' },
    accept: () => sendToLogistics(),
  })
}

const sendToLogistics = async () => {
  if (!order.value) return
  sendingToLogistics.value = true
  try {
    await salesService.sendPosOrderToLogistics(order.value.id)
    toast.add({
      severity: 'success',
      summary: 'Ready for Dispatch',
      detail: 'Order is now available for logistics assignment.',
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

const printReceipt = () => {
  if (!order.value) return
  window.open(`/api/sales/pos/orders/${order.value.id}/receipt`, '_blank')
}

onMounted(async () => {
  await loadOrder()
})
</script>
