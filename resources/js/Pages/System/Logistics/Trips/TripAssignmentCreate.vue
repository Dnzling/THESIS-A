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
          <div><span class="text-slate-500">{{ source === 'pickup' ? 'Supplier:' : 'Customer:' }}</span> <strong>{{ customerName }}</strong></div>
          <div><span class="text-slate-500">Contact:</span> <strong>{{ customerContact }}</strong></div>
          <div class="md:col-span-2"><span class="text-slate-500">{{ source === 'pickup' ? 'Pickup from:' : 'Address:' }}</span> <strong>{{ deliveryAddress }}</strong></div>
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
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm text-slate-600">Employees Accompanying the Driver</label>
            <MultiSelect v-model="form.assistant_user_ids" :options="availableAssistants" optionLabel="name" optionValue="id" filter display="chip" fluid placeholder="Select employees (optional)" />
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
            <DatePicker v-model="form.scheduled_departure_at" :minDate="new Date()" :disabledDates="unavailableDepartureDates" dateFormat="M d, yy" showIcon fluid>
              <template #date="{ date }"><span :class="{ 'text-red-600 line-through': isUnavailableCalendarDate(date) }">{{ date.day }}</span></template>
            </DatePicker>
            <p v-if="form.vehicle_id" class="mt-1 text-xs text-slate-500">Crossed-out dates already have a planned trip for this vehicle.</p>
          </div>
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm text-slate-600">Notes</label>
            <Textarea v-model="form.notes" rows="3" fluid placeholder="Optional logistics notes" />
          </div>
          <div class="md:col-span-2 flex gap-2">
            <Button type="button" label="Cancel" outlined @click="router.push({ name: 'logistics.trips' })" />
            <Button type="submit" label="Create Trip Assignment" icon="pi pi-check" rounded :loading="submitting" :disabled="!canManage || !order || !form.driver_user_id || !form.vehicle_id || loadingOptions || isDepartureDateUnavailable(form.scheduled_departure_at)" />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Select from 'primevue/select'
import MultiSelect from 'primevue/multiselect'
import DatePicker from 'primevue/datepicker'
import Textarea from 'primevue/textarea'
import Tag from 'primevue/tag'
import logisticsService from '@/services/logistics.service'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const canManage = useAuthStore().hasPermission('logistics.deliveries.manage')
const source = computed<'ecommerce' | 'sales' | 'return_pickup' | 'pickup'>(() => {
  const value = String(route.query.source || '').toLowerCase()
  return value === 'sales' || value === 'return_pickup' || value === 'pickup' ? value : 'ecommerce'
})
const orderId = computed(() => Number(route.query.order_id || 0))
const sourceLabel = computed(() => source.value === 'pickup' ? 'Supplier Pickup' : source.value === 'return_pickup' ? 'Return Pickup' : source.value === 'sales' ? 'Sales' : 'Ecommerce')
const loadingOrder = ref(false)
const loadingOptions = ref(false)
const submitting = ref(false)
const order = ref<any>(null)
const drivers = ref<any[]>([])
const vehicles = ref<any[]>([])
const assistants = ref<any[]>([])
const unavailableDepartureDates = ref<Date[]>([])
const form = reactive({ driver_user_id: null as number | null, assistant_user_ids: [] as number[], vehicle_id: null as number | null, scheduled_departure_at: null as Date | null, notes: '' })
const toDateOnly = (value: Date | null) => value
  ? `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`
  : null
const availableAssistants = computed(() => assistants.value.filter(employee => Number(employee.id) !== Number(form.driver_user_id)))
watch(() => form.driver_user_id, driverId => {
  form.assistant_user_ids = form.assistant_user_ids.filter(id => Number(id) !== Number(driverId))
})
watch(() => form.vehicle_id, vehicleId => { void loadUnavailableDepartureDates(vehicleId) })

const loadUnavailableDepartureDates = async (vehicleId: number | null) => {
  unavailableDepartureDates.value = []
  if (!vehicleId) return
  try {
    const response = await logisticsService.getVehicleUnavailableDepartureDates(vehicleId)
    unavailableDepartureDates.value = (response?.data || []).map((value: string) => new Date(`${value}T00:00:00`))
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Schedule Check Failed', detail: error?.response?.data?.message || 'Unable to check this vehicle’s planned trips.', life: 3500 })
  }
}

const isUnavailableCalendarDate = (date: any) => unavailableDepartureDates.value.some(item =>
  item.getFullYear() === date.year && item.getMonth() === date.month && item.getDate() === date.day,
)
const isDepartureDateUnavailable = (value: Date | null) => Boolean(value && isUnavailableCalendarDate({
  year: value.getFullYear(), month: value.getMonth(), day: value.getDate(),
}))
const selectedDriver = computed(() => drivers.value.find(driver => Number(driver.id) === Number(form.driver_user_id)))
const selectedVehicle = computed(() => vehicles.value.find(vehicle => Number(vehicle.id) === Number(form.vehicle_id)))
const customerName = computed(() => (source.value === 'pickup' ? order.value?.supplier?.supplier_name || order.value?.customer_name : source.value === 'return_pickup' ? order.value?.pickup_name : source.value === 'sales' ? order.value?.customer_name : order.value?.shipping_name) || '-')
const customerContact = computed(() => (source.value === 'pickup' ? order.value?.supplier?.contact_number || order.value?.customer_contact : source.value === 'return_pickup' ? order.value?.pickup_phone : source.value === 'sales' ? order.value?.customer_phone : order.value?.shipping_phone) || '-')
const deliveryAddress = computed(() => (source.value === 'pickup' ? order.value?.supplier?.address || order.value?.origin_address : source.value === 'return_pickup' ? order.value?.pickup_address : source.value === 'sales' ? order.value?.delivery_address : order.value?.shipping_address) || '-')
const formatStatus = (value: string) => String(value || '-').replace(/_/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase())

const loadOrder = async () => {
  if (!Number.isInteger(orderId.value) || orderId.value <= 0) return
  loadingOrder.value = true
  try {
    if (source.value === 'pickup') {
      const response = await logisticsService.getDeliveryOrderDetail('pickup', orderId.value)
      order.value = response?.data?.order || null
    } else if (source.value === 'return_pickup') {
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
    assistants.value = employeeResponse?.data?.employees || []
    vehicles.value = (vehicleResponse?.data?.data || []).map((vehicle: any) => ({ ...vehicle, label: `${vehicle.vehicle_name} (${vehicle.plate_number})` }))
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Options Load Failed', detail: error?.response?.data?.message || 'Unable to load drivers and vehicles.', life: 3500 })
  } finally {
    loadingOptions.value = false
  }
}

const submit = async () => {
  if (!canManage || !order.value || !form.driver_user_id || !form.vehicle_id || submitting.value) return
  if (isDepartureDateUnavailable(form.scheduled_departure_at)) {
    toast.add({ severity: 'warn', summary: 'Vehicle Already Booked', detail: 'Choose another vehicle or departure date.', life: 3000 })
    return
  }
  submitting.value = true
  let tripId: number | null = null
  try {
    const created = await logisticsService.createTrip({
      driver_user_id: form.driver_user_id,
      assistant_user_ids: form.assistant_user_ids,
      vehicle_id: form.vehicle_id,
      scheduled_departure_at: toDateOnly(form.scheduled_departure_at),
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
