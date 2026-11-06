<template>
  <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded aria-label="Back to trips" @click="router.push({ name: 'logistics.trips' })" />
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Create Trip Assignment</h1>
        <p class="text-sm text-slate-600">Assign an order to a new trip with a driver and vehicle.</p>
      </div>
    </div>

    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Order Summary</template>
      <template #content>
        <div v-if="loadingOrder" class="text-sm text-slate-500">Loading order details...</div>
        <div v-else-if="!order" class="text-sm text-slate-500">Select an order from Delivery Detail or Return Pickup Detail to create its trip assignment.</div>
        <div v-else class="grid gap-3 text-sm md:grid-cols-2">
          <div><span class="text-slate-500">Source:</span> <strong>{{ sourceLabel }}</strong></div>
          <div><span class="text-slate-500">Order #:</span> <strong>{{ order.order_number || `#${orderId}` }}</strong></div>
          <div><span class="text-slate-500">Customer:</span> <strong>{{ customerName }}</strong></div>
          <div><span class="text-slate-500">Contact:</span> <strong>{{ customerContact }}</strong></div>
          <div class="md:col-span-2"><span class="text-slate-500">Address:</span> <strong>{{ deliveryAddress }}</strong></div>
          <div><span class="text-slate-500">Status:</span> <Tag :value="formatStatus(order.status)" severity="secondary" /></div>
        </div>
      </template>
    </Card>

    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Trip Assignment</template>
      <template #content>
        <form class="grid gap-4 md:grid-cols-2" @submit.prevent="submit">
          <div>
            <label class="mb-1 block text-sm text-slate-600">Driver *</label>
            <Select v-model="form.driver_user_id" :options="drivers" optionLabel="name" optionValue="id" filter fluid placeholder="Select driver" />
            <small v-if="!loadingOptions && !drivers.length" class="text-amber-700">No active driver is available.</small>
          </div>
          <div>
            <label class="mb-1 block text-sm text-slate-600">Vehicle *</label>
            <Select v-model="form.vehicle_id" :options="vehicles" optionLabel="label" optionValue="id" filter fluid placeholder="Select vehicle" />
            <small v-if="!loadingOptions && !vehicles.length" class="text-amber-700">No active vehicle is available.</small>
          </div>
          <div v-if="selectedDriver" class="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4 text-sm">
            <h3 class="mb-2 font-semibold">Driver Information</h3>
            <p>{{ selectedDriver.name }}</p>
            <p class="text-slate-600">{{ selectedDriver.employee_number || '-' }} · {{ selectedDriver.contact || '-' }}</p>
            <p class="text-slate-600">{{ selectedDriver.branch || '-' }}</p>
          </div>
          <div v-if="selectedVehicle" class="rounded-2xl border border-blue-100 bg-blue-50/60 p-4 text-sm">
            <h3 class="mb-2 font-semibold">Vehicle Details</h3>
            <p>{{ selectedVehicle.label }}</p>
            <p class="text-slate-600">{{ selectedVehicle.vehicle_type || '-' }} · {{ selectedVehicle.capacity_kg ? `${selectedVehicle.capacity_kg} kg` : 'Capacity not set' }}</p>
          </div>
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm text-slate-600">Scheduled Departure</label>
            <DatePicker v-model="form.scheduled_departure_at" showTime hourFormat="12" :minDate="new Date()" dateFormat="MM d, yy" showIcon fluid />
          </div>
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm text-slate-600">Notes</label>
            <Textarea v-model="form.notes" rows="3" fluid placeholder="Optional logistics notes" />
          </div>
          <div class="md:col-span-2 flex gap-2">
            <Button type="button" label="Cancel" outlined @click="router.push({ name: 'logistics.trips' })" />
            <Button type="submit" label="Create Trip Assignment" icon="pi pi-check" severity="success" :loading="submitting" :disabled="!canManage || !order || !form.driver_user_id || !form.vehicle_id || loadingOptions" />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import Textarea from 'primevue/textarea'
import Tag from 'primevue/tag'
import logisticsService from '@/services/logistics.service'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const canManage = useAuthStore().hasPermission('logistics.deliveries.manage')
const source = computed<'ecommerce' | 'sales' | 'return_pickup'>(() => {
  const value = String(route.query.source || '').toLowerCase()
  return value === 'sales' || value === 'return_pickup' ? value : 'ecommerce'
})
const orderId = computed(() => Number(route.query.order_id || 0))
const sourceLabel = computed(() => source.value === 'return_pickup' ? 'Return Pickup' : source.value === 'sales' ? 'Sales' : 'Ecommerce')
const loadingOrder = ref(false)
const loadingOptions = ref(false)
const submitting = ref(false)
const order = ref<any>(null)
const drivers = ref<any[]>([])
const vehicles = ref<any[]>([])
const form = reactive({ driver_user_id: null as number | null, vehicle_id: null as number | null, scheduled_departure_at: null as Date | null, notes: '' })
const selectedDriver = computed(() => drivers.value.find(driver => Number(driver.id) === Number(form.driver_user_id)))
const selectedVehicle = computed(() => vehicles.value.find(vehicle => Number(vehicle.id) === Number(form.vehicle_id)))
const customerName = computed(() => (source.value === 'return_pickup' ? order.value?.pickup_name : source.value === 'sales' ? order.value?.customer_name : order.value?.shipping_name) || '-')
const customerContact = computed(() => (source.value === 'return_pickup' ? order.value?.pickup_phone : source.value === 'sales' ? order.value?.customer_phone : order.value?.shipping_phone) || '-')
const deliveryAddress = computed(() => (source.value === 'return_pickup' ? order.value?.pickup_address : source.value === 'sales' ? order.value?.delivery_address : order.value?.shipping_address) || '-')
const formatStatus = (value: string) => String(value || '-').replace(/_/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase())

const loadOrder = async () => {
  if (!Number.isInteger(orderId.value) || orderId.value <= 0) return
  loadingOrder.value = true
  try {
    if (source.value === 'return_pickup') {
      const response = await logisticsService.getReturnPickup(orderId.value)
      const pickup = response?.data
      order.value = pickup ? { ...pickup, order_number: pickup.return_request?.return_number || `Return #${pickup.return_request?.id || pickup.id}` } : null
    } else {
      const response = await logisticsService.getDeliveryOrderDetail(source.value, orderId.value)
      order.value = response?.data?.order || null
    }
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Order Load Failed', detail: error?.response?.data?.message || 'Unable to load order.', life: 3500 })
  } finally {
    loadingOrder.value = false
  }
}

const loadOptions = async () => {
  loadingOptions.value = true
  try {
    const [employeeResponse, vehicleResponse] = await Promise.all([
      logisticsService.getLogisticsEmployees(),
      logisticsService.getVehicles({ per_page: 100, status: 'active' }),
    ])
    drivers.value = employeeResponse?.data?.drivers || []
    vehicles.value = (vehicleResponse?.data?.data || []).map((vehicle: any) => ({ ...vehicle, label: `${vehicle.vehicle_name} (${vehicle.plate_number})` }))
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Options Load Failed', detail: error?.response?.data?.message || 'Unable to load drivers and vehicles.', life: 3500 })
  } finally {
    loadingOptions.value = false
  }
}

const submit = async () => {
  if (!canManage || !order.value || !form.driver_user_id || !form.vehicle_id || submitting.value) return
  submitting.value = true
  let tripId: number | null = null
  try {
    const created = await logisticsService.createTrip({
      driver_user_id: form.driver_user_id,
      vehicle_id: form.vehicle_id,
      scheduled_departure_at: form.scheduled_departure_at?.toISOString() || null,
      notes: form.notes || null,
    })
    tripId = Number(created?.data?.id) || null
    if (!tripId) throw new Error('The trip was not returned by the server.')
    const assigned = await logisticsService.addOrdersToTrip(tripId, { source_type: source.value, order_ids: [orderId.value] })
    if (Number(assigned?.data?.added || 0) !== 1) throw new Error(assigned?.message || 'The order could not be added to the trip.')
    toast.add({ severity: 'success', summary: 'Trip Assigned', detail: 'The order is assigned to the trip driver and vehicle.', life: 3000 })
    router.push({ name: 'logistics.trips.detail', params: { id: tripId } })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Assignment Failed', detail: `${error?.response?.data?.message || error?.message || 'Unable to create assignment.'}${tripId ? ` Trip #${tripId} was created; open it to resolve the order assignment.` : ''}`, life: 7000 })
  } finally {
    submitting.value = false
  }
}

onMounted(() => { void loadOrder(); void loadOptions() })
</script>
