<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <div class="flex items-center justify-between gap-4">
          <div class="flex items-center gap-2">
            <Button text severity="secondary" icon="pi pi-arrow-left" @click="goBack" />
            <div>
              <h1 class="text-2xl font-semibold text-gray-900">{{ order?.order_number || 'Order Detail' }}</h1>
              <p class="text-sm text-gray-500">View order and delivery details.</p>
            </div>
          </div>
          <Button severity="info" outlined icon="pi pi-refresh" label="Refresh" @click="loadOrder" />
        </div>
      </template>
    </Card>

    <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <Skeleton height="12rem" />
      <Skeleton height="12rem" />
      <Skeleton height="12rem" />
    </div>

    <template v-else>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <Card class="rounded-2xl border border-gray-100 shadow-sm lg:col-span-2">
          <template #title>Order Summary</template>
          <template #content>
            <div class="grid grid-cols-2 gap-3 text-sm">
              <div><span class="text-gray-500">Customer:</span> <span class="font-medium text-gray-900">{{ order?.shipping_name || '-' }}</span></div>
              <div><span class="text-gray-500">Contact:</span> <span class="font-medium text-gray-900">{{ order?.shipping_phone || '-' }}</span></div>
              <div><span class="text-gray-500">Payment:</span> <span class="font-medium text-gray-900">{{ order?.payment_method }}</span></div>
              <div><span class="text-gray-500">Payment Status:</span> <span class="font-medium text-gray-900">{{ order?.payment_status }}</span></div>
              <div class="col-span-2"><span class="text-gray-500">Address:</span> <span class="font-medium text-gray-900">{{ order?.shipping_address || '-' }}</span></div>
            </div>

            <Divider />

            <div class="flex items-center justify-between mb-2">
              <h4 class="text-sm font-semibold text-gray-900">Delivery Information</h4>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
              <div><span class="text-gray-500">Assigned Branch:</span> <span class="font-medium text-gray-900">{{ order?.assigned_branch?.name || '-' }}</span></div>
              <div><span class="text-gray-500">Delivery Status:</span> <Tag :value="formatStatus(order?.delivery?.status || 'not_assigned')" :severity="deliverySeverity(order?.delivery?.status || 'assigned')" /></div>
              <div><span class="text-gray-500">Tracking:</span> <span class="font-medium text-gray-900">{{ order?.delivery?.tracking_number || '-' }}</span></div>
              <div><span class="text-gray-500">Courier:</span> <span class="font-medium text-gray-900">{{ order?.delivery?.courier_name || '-' }}</span></div>
              <div><span class="text-gray-500">Courier Contact:</span> <span class="font-medium text-gray-900">{{ order?.delivery?.courier_contact || '-' }}</span></div>
              <div><span class="text-gray-500">Vehicle:</span> <span class="font-medium text-gray-900">{{ order?.delivery?.vehicle ? `${order.delivery.vehicle.vehicle_name} (${order.delivery.vehicle.plate_number})` : '-' }}</span></div>
              <div><span class="text-gray-500">ETA:</span> <span class="font-medium text-gray-900">{{ order?.delivery?.estimated_delivery_at ? formatDateTime(order.delivery.estimated_delivery_at) : '-' }}</span></div>
            </div>
          </template>
        </Card>
      </div>

      <Card class="rounded-2xl border border-gray-100 shadow-sm">
        <template #title>Ordered Items</template>
        <template #content>
          <DataTable :value="order?.items || []" dataKey="id" stripedRows>
            <Column field="product_name" header="Product" />
            <Column field="sku" header="SKU" />
            <Column field="quantity" header="Qty" />
            <Column header="Stock Status">
              <template #body="{ data }">
                <Tag :value="formatStatus(data.branch_inventory?.stock_status || 'unknown')" :severity="(data.branch_inventory?.stock_status || '').includes('out') ? 'danger' : ((data.branch_inventory?.stock_status || '').includes('low') ? 'warning' : 'success')" />
              </template>
            </Column>
            <Column field="unit_price" header="Unit Price">
              <template #body="{ data }">{{ formatMoney(data.unit_price) }}</template>
            </Column>
            <Column field="line_total" header="Line Total">
              <template #body="{ data }">{{ formatMoney(data.line_total) }}</template>
            </Column>
          </DataTable>
        </template>
      </Card>

      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <Card class="rounded-2xl border border-gray-100 shadow-sm">
          <template #title>Customer Chat</template>
          <template #content>
            <div class="space-y-3">
              <Message severity="info" :closable="false">
                Chat replies are now handled in the Sales module for better team workflow.
              </Message>
              <Button
                label="Open Sales Chat Inbox"
                icon="pi pi-comments"
                severity="info"
                outlined
                @click="openSalesChat"
              />
            </div>
          </template>
        </Card>
      </div>

      <Card class="rounded-2xl border border-gray-100 shadow-sm">
        <template #title>Order Timeline</template>
        <template #content>
          <Timeline v-if="(order?.timeline || []).length" :value="order.timeline" class="w-full">
            <template #content="{ item }">
              <div class="pb-4">
                <div class="flex flex-wrap items-center gap-2">
                  <p class="text-sm font-semibold text-gray-900">{{ item.title }}</p>
                  <Tag v-if="item.status_to" :value="formatStatus(item.status_to)" severity="secondary" class="text-xs" />
                </div>
                <p class="mt-1 text-xs text-gray-600">{{ item.description || '-' }}</p>
                <p class="mt-1 text-xs text-gray-400">{{ formatDateTime(item.created_at) }} • {{ item.actor || 'System' }}</p>
              </div>
            </template>
          </Timeline>
          <div v-else class="text-sm text-gray-500">No timeline entries yet.</div>
        </template>
      </Card>
    </template>

  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import salesService from '@/services/sales.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Skeleton from 'primevue/skeleton'
import Timeline from 'primevue/timeline'
import Divider from 'primevue/divider'
import Message from 'primevue/message'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const order = ref<any>(null)

const loadOrder = async () => {
  loading.value = true
  try {
    const res = await salesService.getEcommerceOrder(String(route.params.id))
    order.value = res?.data
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load order', life: 3000 })
  } finally {
    loading.value = false
  }
}

const openSalesChat = () => {
  if (!order.value) {
    router.push({ name: 'sales.chats' })
    return
  }

  router.push({
    name: 'sales.chats',
    query: {
      customer_user_id: order.value.user_id,
      order_id: order.value.id,
    },
  })
}

const formatStatus = (status: string) => status.replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase())
const formatMoney = (value: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
const formatDateTime = (value: string) => {
  if (!value) return '-'
  return new Date(value).toLocaleString('en-PH', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}
const deliverySeverity = (status: string) => {
  if (status === 'delivered') return 'success'
  if (status === 'failed_delivery' || status === 'cancelled') return 'danger'
  if (status === 'out_for_delivery') return 'warning'
  return 'info'
}

const goBack = () => router.push({ name: 'sales.ecommerce-orders' })

onMounted(loadOrder)
</script>
