<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <div class="flex items-center justify-between gap-4">
          <div class="flex items-center gap-2">
            <Button text severity="secondary" icon="pi pi-arrow-left" @click="goBack" />
            <div>
              <h1 class="text-2xl font-semibold text-gray-900">{{ order?.order_number || 'Order Detail' }}</h1>
              <p class="text-sm text-gray-500">Process order and assign delivery safely.</p>
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
          </template>
        </Card>

        <Card class="rounded-2xl border border-gray-100 shadow-sm">
          <template #title>Status</template>
          <template #content>
            <div class="space-y-3">
              <Tag :value="formatStatus(order?.status || 'pending')" :severity="statusSeverity(order?.status || 'pending')" />
              <Select v-model="statusForm.status" :options="statusOptions" optionLabel="label" optionValue="value" fluid />
              <Textarea v-model="statusForm.notes" rows="3" fluid placeholder="Notes (optional)" />
              <Button severity="info" :loading="updating" fluid label="Update Status" @click="saveStatus" />
            </div>
          </template>
        </Card>
      </div>

      <Card class="rounded-2xl border border-gray-100 shadow-sm">
        <template #title>Delivery Assignment</template>
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            <Select v-model="deliveryForm.vehicle_id" :options="vehicles" optionLabel="label" optionValue="id" showClear fluid placeholder="Vehicle" />
            <InputText v-model="deliveryForm.courier_name" fluid placeholder="Courier name" />
            <InputText v-model="deliveryForm.courier_contact" fluid placeholder="Courier contact" />
            <InputText v-model="deliveryForm.tracking_number" fluid placeholder="Tracking number" />
            <DatePicker v-model="deliveryForm.estimated_delivery_at" showTime fluid placeholder="Estimated delivery" />
            <Textarea v-model="deliveryForm.notes" rows="2" fluid placeholder="Delivery notes" class="md:col-span-3" />
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-gray-100 shadow-sm">
        <template #title>Ordered Items</template>
        <template #content>
          <DataTable :value="order?.items || []" dataKey="id" stripedRows>
            <Column field="product_name" header="Product" />
            <Column field="sku" header="SKU" />
            <Column field="quantity" header="Qty" />
            <Column field="unit_price" header="Unit Price">
              <template #body="{ data }">{{ formatMoney(data.unit_price) }}</template>
            </Column>
            <Column field="line_total" header="Line Total">
              <template #body="{ data }">{{ formatMoney(data.line_total) }}</template>
            </Column>
          </DataTable>
        </template>
      </Card>

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
                <p class="mt-1 text-xs text-gray-400">
                  {{ formatDateTime(item.created_at) }} • {{ item.actor || 'System' }}
                </p>
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
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '@/services/inventory.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import InputText from 'primevue/inputtext'
import DatePicker from 'primevue/datepicker'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Skeleton from 'primevue/skeleton'
import Timeline from 'primevue/timeline'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const updating = ref(false)
const order = ref<any>(null)
const vehicles = ref<any[]>([])

const statusOptions = [
  { label: 'Processing', value: 'processing' },
  { label: 'Packed', value: 'packed' },
  { label: 'Shipped', value: 'shipped' },
  { label: 'In Transit', value: 'in_transit' },
  { label: 'Out For Delivery', value: 'out_for_delivery' },
  { label: 'Delivered', value: 'delivered' },
  { label: 'Cancelled', value: 'cancelled' },
]

const statusForm = reactive({
  status: 'processing',
  notes: '',
})

const deliveryForm = reactive<any>({
  vehicle_id: null,
  tracking_number: '',
  courier_name: '',
  courier_contact: '',
  estimated_delivery_at: null,
  notes: '',
})

const loadVehicles = async () => {
  try {
    const res = await inventoryService.getDeliveryVehicles({ per_page: 200, status: 'active' })
    const rows = res?.data?.data || []
    vehicles.value = rows.map((v: any) => ({
      ...v,
      label: `${v.vehicle_name} (${v.plate_number})`,
    }))
  } catch {
    vehicles.value = []
  }
}

const loadOrder = async () => {
  loading.value = true
  try {
    const res = await inventoryService.getEcommerceOrder(String(route.params.id))
    order.value = res?.data
    statusForm.status = order.value?.status || 'processing'

    const delivery = order.value?.delivery || {}
    deliveryForm.vehicle_id = delivery.vehicle_id ?? null
    deliveryForm.tracking_number = delivery.tracking_number ?? ''
    deliveryForm.courier_name = delivery.courier_name ?? ''
    deliveryForm.courier_contact = delivery.courier_contact ?? ''
    deliveryForm.estimated_delivery_at = delivery.estimated_delivery_at ? new Date(delivery.estimated_delivery_at) : null
    deliveryForm.notes = delivery.notes ?? ''
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load order', life: 3000 })
  } finally {
    loading.value = false
  }
}

const saveStatus = async () => {
  updating.value = true
  try {
    await inventoryService.updateEcommerceOrderStatus(String(route.params.id), {
      status: statusForm.status,
      notes: statusForm.notes || undefined,
      delivery: {
        vehicle_id: deliveryForm.vehicle_id || undefined,
        tracking_number: deliveryForm.tracking_number || undefined,
        courier_name: deliveryForm.courier_name || undefined,
        courier_contact: deliveryForm.courier_contact || undefined,
        estimated_delivery_at: deliveryForm.estimated_delivery_at || undefined,
        notes: deliveryForm.notes || undefined,
      },
    })
    toast.add({ severity: 'success', summary: 'Updated', detail: 'Order status updated successfully.', life: 2500 })
    await loadOrder()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Update failed', detail: error?.response?.data?.message || 'Failed to update order status', life: 3000 })
  } finally {
    updating.value = false
  }
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
const statusSeverity = (status: string) => {
  if (status === 'delivered') return 'success'
  if (status === 'cancelled') return 'danger'
  if (status === 'pending') return 'warning'
  return 'info'
}

const goBack = () => router.push({ name: 'inventory.ecommerce-orders' })

onMounted(async () => {
  await Promise.all([loadOrder(), loadVehicles()])
})
</script>
