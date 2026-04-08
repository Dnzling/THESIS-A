<template>
  <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
  
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <Button icon="pi pi-arrow-left" text rounded @click="goBack" />
        <div>
          <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Trip #{{ trip?.id || '-' }}</h1>
          <p class="mt-1 text-sm text-slate-600">Assign multiple orders to one vehicle.</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Button icon="pi pi-refresh" label="Refresh" outlined @click="loadTrip" />
        <Select v-model="statusForm.status" :options="statusOptions" optionLabel="label" optionValue="value"
          class="w-40" />
        <Button :disabled="!canManage" icon="pi pi-save" label="Update Status" severity="success" @click="saveStatus" />
        <Button v-if="canManage && trip?.status === 'planned'" icon="pi pi-send" label="Dispatch Trip" severity="info"
          @click="dispatchTrip" />
      </div>
    </div>
  
  
    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Trip Summary</template>
      <template #content>
        <div v-if="loading" class="text-sm text-slate-500">Loading...</div>
        <div v-else-if="!trip" class="text-sm text-slate-500">Trip not found.</div>
        <div v-else class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
          <div><span class="text-slate-500">Driver:</span> <strong>{{ driverName }}</strong></div>
          <div><span class="text-slate-500">Vehicle:</span> <strong>{{ vehicleLabel }}</strong></div>
          <div><span class="text-slate-500">Status:</span>
            <Tag :value="formatStatus(trip.status)" :severity="statusSeverity(trip.status)" />
          </div>
          <div><span class="text-slate-500">Scheduled:</span> <strong>{{ formatDateTime(trip.scheduled_departure_at)
              }}</strong></div>
          <div class="md:col-span-2"><span class="text-slate-500">Notes:</span> <strong>{{ trip.notes || '-' }}</strong>
          </div>
          <div class="md:col-span-2">
            <div class="rounded-2xl border border-amber-100 bg-amber-50 p-3 text-xs text-amber-700">
              Capacity: {{ capacityLabel }}
              <span v-if="capacityWarning" class="ml-2 font-semibold">Over capacity!</span>
            </div>
          </div>
        </div>
      </template>
    </Card>

    <ConfirmDialog />
  
    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Assigned Orders</template>
      <template #content>
        <div v-if="!assignedOrders.length" class="text-sm text-slate-500">No orders assigned yet.</div>
        <DataTable v-else :value="assignedOrders" dataKey="id" stripedRows>
          <Column field="source" header="Source" style="width: 8rem" />
          <Column field="order_number" header="Order #" />
          <Column field="customer_name" header="Customer" />
          <Column field="address" header="Address" />
          <Column field="status" header="Status" />
          <Column header="Weight (kg)" style="width: 8rem">
            <template #body="{ data }">
              <span class="text-xs text-slate-600">{{ data.weight_kg.toFixed(2) }}</span>
            </template>
          </Column>
          <Column header="View" style="width: 5rem">
            <template #body="{ data }">
              <Button icon="pi pi-eye" text rounded @click="openAssignedOrderDetail(data)" />
            </template>
          </Column>
          <Column header="Remove" style="width: 6rem">
            <template #body="{ data }">
              <Button
                icon="pi pi-times"
                text
                rounded
                severity="danger"
                :disabled="!canManage"
                @click="removeOrder(data)"
              />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  
    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Add Orders to Trip</template>
      <template #content>
        <div class="flex flex-wrap items-center gap-2 mb-3">
          <Select v-model="availableSource" :options="sourceOptions" optionLabel="label" optionValue="value"
            class="w-40" />
          <Button :loading="loadingAvailable" label="Load Ready Orders" outlined @click="loadAvailableOrders" />
          <Button :disabled="!canManage || !selectedOrderIds.length || willExceedCapacity" label="Add Selected"
            severity="success" @click="addSelected" />
          <Button icon="pi pi-print" outlined label="Export Trip PDF" @click="exportTripPdf" />
        </div>
        <div v-if="!canManage" class="mb-2 text-xs text-rose-600">
          Action disabled: requires `logistics.deliveries.manage` permission.
        </div>
        <div v-if="vehicleCapacity > 0" class="mb-3 text-xs text-slate-500">
          Selected weight: <strong>{{ selectedWeightKg.toFixed(2) }}</strong> kg / Remaining capacity:
          <strong>{{ capacityRemaining.toFixed(2) }}</strong> kg
          <span v-if="willExceedCapacity" class="ml-2 font-semibold text-rose-600">Over capacity</span>
        </div>
  
        <DataTable :value="availableOrders" dataKey="id" stripedRows selectionMode="multiple"
          v-model:selection="selectedOrders" paginator :rows="availablePerPage" :totalRecords="availableTotal"
          :first="(availablePage - 1) * availablePerPage" @page="handleAvailablePage">
          <Column selectionMode="multiple" headerStyle="width: 3rem" />
          <Column field="order_number" header="Order #" />
          <Column field="customer_name" header="Customer" />
          <Column field="delivery_address" header="Address" />
          <Column header="Status">
            <template #body="{ data }">
              <Tag :value="formatStatusLabel(data.order_status)" severity="info" />
            </template>
          </Column>
          <Column header="Distance (km)" style="width: 8rem">
            <template #body="{ data }">
              <span class="text-xs text-slate-500">{{ Number(data.distance_km || 0).toFixed(2) }}</span>
            </template>
          </Column>
          <Column header="Weight (kg)" style="width: 7rem">
            <template #body="{ data }">
              <span class="text-xs text-slate-600">{{ Number(data.weight_kg || 0).toFixed(2) }}</span>
            </template>
          </Column>
          <Column header="View" style="width: 5rem">
            <template #body="{ data }">
              <Button icon="pi pi-eye" text rounded @click="openOrderDetail(data)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  
    <Dialog v-model:visible="detailDialog" modal header="Order Details" class="w-full max-w-3xl">
      <div v-if="detailLoading" class="text-sm text-slate-500">Loading...</div>
      <div v-else-if="!detailOrder" class="text-sm text-slate-500">Order not found.</div>
      <div v-else class="space-y-4 text-sm">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div><span class="text-slate-500">Order #:</span> <strong>{{ detailOrder.order_number }}</strong></div>
          <div><span class="text-slate-500">Status:</span>
            <Tag :value="formatStatus(detailOrder.status)" severity="info" />
          </div>
          <div><span class="text-slate-500">Customer:</span> <strong>{{ detailCustomerName }}</strong></div>
          <div><span class="text-slate-500">Contact:</span> <strong>{{ detailCustomerContact }}</strong></div>
          <div class="md:col-span-2"><span class="text-slate-500">Address:</span> <strong>{{ detailAddress }}</strong>
          </div>
          <div><span class="text-slate-500">Total:</span> <strong>{{ formatCurrency(detailOrder.total_amount) }}</strong>
          </div>
          <div><span class="text-slate-500">Source:</span> <strong>{{ detailSourceLabel }}</strong></div>
        </div>
  
        <div>
          <h4 class="text-sm font-semibold text-slate-700">Order Items</h4>
          <DataTable :value="detailOrder.items || []" dataKey="id" stripedRows class="mt-2">
            <Column field="product_name" header="Product" />
            <Column field="sku" header="SKU" style="width: 8rem" />
            <Column field="quantity" header="Qty" style="width: 5rem" />
            <Column header="Unit Price" style="width: 8rem">
              <template #body="{ data }">
                <span class="text-xs text-slate-600">{{ formatCurrency(data.unit_price) }}</span>
              </template>
            </Column>
            <Column header="Line Total" style="width: 8rem">
              <template #body="{ data }">
                <span class="text-xs text-slate-600">{{ formatCurrency(data.line_total) }}</span>
              </template>
            </Column>
          </DataTable>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import logisticsService from '@/services/logistics.service'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const authStore = useAuthStore()
const canManage = authStore.hasPermission('logistics.deliveries.manage')

const trip = ref<any>(null)
const loading = ref(false)
const statusForm = ref({ status: 'planned' })
const statusOptions = [
  { label: 'Planned', value: 'planned' },
  { label: 'In Transit', value: 'in_transit' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' },
]

const availableOrders = ref<any[]>([])
const loadingAvailable = ref(false)
const selectedOrders = ref<any[]>([])
const availableSource = ref<'ecommerce' | 'sales'>('ecommerce')
const availablePage = ref(1)
const availablePerPage = ref(10)
const availableTotal = ref(0)
const detailDialog = ref(false)
const detailLoading = ref(false)
const detailOrder = ref<any>(null)
const detailSource = ref<'ecommerce' | 'sales'>('ecommerce')

const sourceOptions = [
  { label: 'Ecommerce', value: 'ecommerce' },
  { label: 'Sales', value: 'sales' },
]

const assignedOrders = computed(() => {
  if (!trip.value) return []
  const ecommerce = (trip.value.ecommerce_deliveries || []).map((d: any) => ({
    id: `e-${d.id}`,
    source: 'Ecommerce',
    order_number: d.order?.order_number,
    customer_name: d.order?.shipping_name,
    address: d.order?.shipping_address,
    status: d.status,
    weight_kg: calcWeight(d.order?.items || []),
    order_id: d.order?.id,
  }))
  const sales = (trip.value.sales_deliveries || []).map((d: any) => ({
    id: `s-${d.id}`,
    source: 'Sales',
    order_number: d.order?.order_number,
    customer_name: d.order?.customer_name,
    address: d.order?.delivery_address,
    status: d.status,
    weight_kg: calcWeight(d.order?.items || []),
    order_id: d.order?.id,
  }))
  return [...ecommerce, ...sales]
})

const selectedOrderIds = computed(() => selectedOrders.value.map(o => o.order_id))

const driverName = computed(() => trip.value?.driver ? `${trip.value.driver.fname} ${trip.value.driver.lname}` : '-')
const vehicleLabel = computed(() => trip.value?.vehicle ? `${trip.value.vehicle.vehicle_name} (${trip.value.vehicle.plate_number})` : '-')
const tripTotalWeight = computed(() => assignedOrders.value.reduce((sum, row) => sum + (Number(row.weight_kg || 0)), 0))
const vehicleCapacity = computed(() => Number(trip.value?.vehicle?.capacity_kg || 0))
const selectedWeightKg = computed(() => selectedOrders.value.reduce((sum, row) => sum + Number(row.weight_kg || 0), 0))
const capacityRemaining = computed(() => {
  if (!vehicleCapacity.value) return 0
  return Math.max(0, vehicleCapacity.value - tripTotalWeight.value)
})
const willExceedCapacity = computed(() => vehicleCapacity.value > 0 && (selectedWeightKg.value + tripTotalWeight.value) > vehicleCapacity.value)
const capacityLabel = computed(() => {
  if (!vehicleCapacity.value) return `${tripTotalWeight.value.toFixed(2)} kg / No capacity set`
  return `${tripTotalWeight.value.toFixed(2)} kg / ${vehicleCapacity.value.toFixed(2)} kg`
})
const capacityWarning = computed(() => vehicleCapacity.value > 0 && tripTotalWeight.value > vehicleCapacity.value)

const loadTrip = async () => {
  loading.value = true
  try {
    const res = await logisticsService.getTrip(String(route.params.id))
    trip.value = res?.data || null
    statusForm.value.status = trip.value?.status || 'planned'
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Load Failed', detail: error?.response?.data?.message || 'Failed to load trip.', life: 3000 })
  } finally {
    loading.value = false
  }
}

const saveStatus = async () => {
  if (!trip.value) return
  try {
    await logisticsService.updateTripStatus(trip.value.id, { status: statusForm.value.status })
    toast.add({ severity: 'success', summary: 'Updated', detail: 'Trip status updated.', life: 2500 })
    await loadTrip()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Update Failed', detail: error?.response?.data?.message || 'Failed to update status.', life: 3000 })
  }
}

const loadAvailableOrders = async () => {
  loadingAvailable.value = true
  try {
    const res = await logisticsService.getDeliveryOrders({
      source: availableSource.value,
      status: 'ready_for_dispatch',
      per_page: availablePerPage.value,
      page: availablePage.value,
    })
    const rows = res?.data?.data || []
    availableTotal.value = Number(res?.data?.total || rows.length)
    availableOrders.value = rows
      .map((row: any) => ({
        ...row,
        weight_kg: Number(row.weight_kg || 0),
        distance_km: estimateDistanceKm(row.origin_latitude, row.origin_longitude, row.destination_latitude, row.destination_longitude),
        order_status: 'ready_for_dispatch',
      }))
      .sort((a: any, b: any) => Number(a.distance_km || 0) - Number(b.distance_km || 0))
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Load Failed', detail: error?.response?.data?.message || 'Failed to load orders.', life: 3000 })
  } finally {
    loadingAvailable.value = false
  }
}

const addSelected = async () => {
  if (!trip.value || !selectedOrderIds.value.length) return
  if (willExceedCapacity.value) {
    toast.add({ severity: 'warn', summary: 'Capacity Exceeded', detail: 'Selected orders exceed the vehicle capacity.', life: 3000 })
    return
  }
  try {
    const res = await logisticsService.addOrdersToTrip(trip.value.id, {
      source_type: availableSource.value,
      order_ids: selectedOrderIds.value,
    })
    const added = Number(res?.data?.added || 0)
    const overCapacity = Number(res?.data?.over_capacity || 0)
    const message = res?.message || 'Orders added to trip.'
    toast.add({
      severity: overCapacity > 0 ? 'warn' : 'success',
      summary: overCapacity > 0 ? 'Partially Added' : 'Added',
      detail: message,
      life: 3000,
    })
    selectedOrders.value = []
    await Promise.all([loadTrip(), loadAvailableOrders()])
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Add Failed', detail: error?.response?.data?.message || 'Failed to add orders.', life: 3000 })
  }
}

const removeOrder = async (row: any) => {
  if (!trip.value || !row?.order_id) return
  confirm.require({
    message: 'Remove this order from the trip? This will delete the delivery assignment and reset the order to Ready for Dispatch.',
    header: 'Confirm Removal',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Remove',
    rejectLabel: 'Cancel',
    accept: async () => {
      try {
        await logisticsService.removeOrdersFromTrip(trip.value.id, {
          source_type: row.source === 'Sales' ? 'sales' : 'ecommerce',
          order_ids: [row.order_id],
        })
        toast.add({ severity: 'success', summary: 'Removed', detail: 'Order removed from trip.', life: 2000 })
        await loadTrip()
      } catch (error: any) {
        toast.add({ severity: 'error', summary: 'Remove Failed', detail: error?.response?.data?.message || 'Failed to remove order.', life: 3000 })
      }
    },
  })
}

const handleAvailablePage = (event: any) => {
  availablePage.value = Math.floor(event.first / event.rows) + 1
  availablePerPage.value = event.rows
  loadAvailableOrders()
}

const dispatchTrip = async () => {
  if (!trip.value) return
  statusForm.value.status = 'in_transit'
  await saveStatus()
}

const openOrderDetail = async (row: any) => {
  if (!row?.order_id) return
  detailDialog.value = true
  detailLoading.value = true
  detailSource.value = availableSource.value
  try {
    const res = await logisticsService.getDeliveryOrderDetail(availableSource.value, row.order_id)
    detailOrder.value = res?.data?.order || null
  } catch (error: any) {
    detailOrder.value = null
    toast.add({ severity: 'error', summary: 'Load Failed', detail: error?.response?.data?.message || 'Failed to load order.', life: 3000 })
  } finally {
    detailLoading.value = false
  }
}

const openAssignedOrderDetail = async (row: any) => {
  if (!row?.order_id) return
  detailDialog.value = true
  detailLoading.value = true
  detailSource.value = row.source === 'Sales' ? 'sales' : 'ecommerce'
  try {
    const res = await logisticsService.getDeliveryOrderDetail(detailSource.value, row.order_id)
    detailOrder.value = res?.data?.order || null
  } catch (error: any) {
    detailOrder.value = null
    toast.add({ severity: 'error', summary: 'Load Failed', detail: error?.response?.data?.message || 'Failed to load order.', life: 3000 })
  } finally {
    detailLoading.value = false
  }
}

const exportTripPdf = () => {
  if (!trip.value) return
  const rows = assignedOrders.value
    .map((o: any) => `
      <tr>
        <td>${o.source}</td>
        <td>${o.order_number || '-'}</td>
        <td>${o.customer_name || '-'}</td>
        <td>${o.address || '-'}</td>
        <td>${o.status || '-'}</td>
        <td style="text-align:right;">${Number(o.weight_kg || 0).toFixed(2)}</td>
      </tr>
    `).join('')

  const html = `
    <html>
      <head>
        <title>Trip #${trip.value.id}</title>
        <style>
          body { font-family: Arial, sans-serif; padding: 24px; color: #0f172a; }
          h1 { margin: 0 0 8px; }
          .meta { margin-bottom: 16px; font-size: 12px; color: #475569; }
          table { width: 100%; border-collapse: collapse; font-size: 12px; }
          th, td { border: 1px solid #e2e8f0; padding: 8px; text-align: left; }
          th { background: #f8fafc; }
        </style>
      </head>
      <body>
        <h1>Delivery Trip #${trip.value.id}</h1>
        <div class="meta">
          Driver: ${driverName.value} <br/>
          Vehicle: ${vehicleLabel.value} <br/>
          Status: ${formatStatus(trip.value.status)} <br/>
          Scheduled: ${formatDateTime(trip.value.scheduled_departure_at)} <br/>
          Capacity: ${capacityLabel.value}
        </div>
        <table>
          <thead>
            <tr>
              <th>Source</th>
              <th>Order #</th>
              <th>Customer</th>
              <th>Address</th>
              <th>Status</th>
              <th style="text-align:right;">Weight (kg)</th>
            </tr>
          </thead>
          <tbody>
            ${rows}
          </tbody>
        </table>
      </body>
    </html>
  `
  const win = window.open('', '_blank', 'width=900,height=700')
  if (!win) return
  win.document.write(html)
  win.document.close()
  win.focus()
  win.print()
}

const estimateDistanceKm = (oLat?: number, oLng?: number, dLat?: number, dLng?: number) => {
  const lat1 = Number(oLat)
  const lon1 = Number(oLng)
  const lat2 = Number(dLat)
  const lon2 = Number(dLng)
  if (![lat1, lon1, lat2, lon2].every(Number.isFinite)) return 0
  const toRad = (v: number) => (v * Math.PI) / 180
  const R = 6371
  const dLatRad = toRad(lat2 - lat1)
  const dLonRad = toRad(lon2 - lon1)
  const a = Math.sin(dLatRad / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLonRad / 2) ** 2
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
  return R * c
}

const calcWeight = (items: any[]) => {
  return (items || []).reduce((sum, item) => {
    const w = Number(item?.product?.weight_kg ?? 0)
    return sum + w * Number(item?.quantity || 0)
  }, 0)
}

const goBack = () => router.push({ name: 'logistics.trips' })

const formatStatus = (value?: string) => value ? value.replace(/_/g, ' ').replace(/\b\w/g, m => m.toUpperCase()) : '-'
const formatStatusLabel = (value?: string) => formatStatus(value || 'ready_for_dispatch')
const statusSeverity = (value?: string) => {
  if (value === 'completed') return 'success'
  if (value === 'cancelled') return 'danger'
  if (value === 'in_transit') return 'info'
  return 'warning'
}
const formatDateTime = (value?: string) => value ? new Date(value).toLocaleString('en-PH') : '-'
const formatCurrency = (value?: number) => {
  const amount = Number(value || 0)
  return amount.toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })
}

const detailSourceLabel = computed(() => detailSource.value === 'sales' ? 'Sales' : 'Ecommerce')
const detailCustomerName = computed(() => {
  if (!detailOrder.value) return '-'
  return detailSource.value === 'sales'
    ? detailOrder.value.customer_name
    : detailOrder.value.shipping_name
})
const detailCustomerContact = computed(() => {
  if (!detailOrder.value) return '-'
  return detailSource.value === 'sales'
    ? detailOrder.value.customer_phone
    : detailOrder.value.shipping_phone
})
const detailAddress = computed(() => {
  if (!detailOrder.value) return '-'
  return detailSource.value === 'sales'
    ? detailOrder.value.delivery_address
    : detailOrder.value.shipping_address
})

onMounted(async () => {
  await loadTrip()
  await loadAvailableOrders()
})

watch(availableSource, () => {
  availablePage.value = 1
  selectedOrders.value = []
  loadAvailableOrders()
})
</script>
