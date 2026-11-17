<template>
  <div class="mx-auto max-w-6xl space-y-4 px-4 py-5 text-sm md:px-6">
    <div class="flex items-start justify-between gap-3"><div class="flex items-start gap-3"><Button icon="pi pi-arrow-left" text rounded severity="secondary" size="small" @click="goBack" /><div><h1 class="text-xl font-semibold text-slate-900">Assign supplier pickup</h1><p class="mt-1 text-xs text-slate-500">Assign a driver directly, join an existing trip, or create a new trip.</p></div></div><div v-if="po && canManageTrips" class="flex flex-wrap gap-2"><Button label="Existing Trip" icon="pi pi-link" severity="secondary" outlined size="small" @click="openTripPicker" /><Button label="Create Trip" icon="pi pi-truck" severity="secondary" outlined size="small" @click="createTripForPickup" /></div></div>
    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
    <div v-if="loading" class="grid gap-4 lg:grid-cols-2"><Skeleton height="200px" /><Skeleton height="360px" /></div>
    <div v-else-if="po" class="grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
      <Card class="border border-slate-200 shadow-sm"><template #content>
        <div class="flex items-start justify-between gap-3"><div><p class="text-xs uppercase tracking-wide text-orange-600">Supplier pickup</p><h2 class="mt-1 font-semibold text-slate-900">{{ po.po_number }}</h2></div><Badge value="Supplier accepted" severity="success" /></div>
        <div class="mt-5 space-y-4 border-t border-slate-100 pt-4">
          <div><p class="text-xs text-slate-500">Collect from</p><p class="font-medium text-slate-900">{{ po.supplier?.supplier_name || '-' }}</p><p class="text-xs text-slate-500">{{ po.supplier?.address || 'No supplier address on file' }}</p></div>
          <div><p class="text-xs text-slate-500">Deliver to</p><p class="font-medium text-slate-900">{{ po.branch?.name || '-' }}</p><p class="text-xs text-slate-500">{{ po.branch?.address || 'No branch address on file' }}</p></div>
          <div class="grid grid-cols-2 gap-3 border-t border-slate-100 pt-4"><div><p class="text-xs text-slate-500">Items</p><p class="font-semibold text-slate-900">{{ po.items?.length || 0 }}</p></div><div><p class="text-xs text-slate-500">Recorded shipping fee</p><p class="font-semibold text-slate-900">{{ money(po.shipping_cost) }}</p></div></div>
        </div>
      </template></Card>
      <Card class="border border-slate-200 shadow-sm"><template #content>
        <h2 class="font-semibold text-slate-900">Pickup team</h2><p class="mt-1 text-xs text-slate-500">Only active employees with the Driver role are available.</p>
        <form class="mt-5 grid gap-4" @submit.prevent="confirmAssignment">
          <div><label class="mb-1 block text-xs font-medium text-slate-700">Driver <span class="text-red-500">*</span></label><Select v-model="form.driver_id" :options="drivers" optionLabel="name" optionValue="id" filter fluid size="small" placeholder="Select a driver"><template #option="{ option }"><div><p>{{ option.name }}</p><p class="text-xs text-slate-500">{{ option.branch || 'No branch assigned' }}</p></div></template></Select><p v-if="!drivers.length" class="mt-1 text-xs text-amber-700">No active drivers are recorded for this store.</p>
            <div v-if="selectedDriver" class="mt-3 rounded-xl border border-orange-100 bg-orange-50/50 p-4"><div class="flex items-center justify-between"><p class="font-semibold text-slate-900">Employee details</p><Badge value="Active driver" severity="success" /></div><div class="mt-3 grid gap-3 sm:grid-cols-2"><div v-for="field in driverDetails" :key="field.label"><p class="text-xs text-slate-500">{{ field.label }}</p><p class="break-words font-medium text-slate-900">{{ field.value || '-' }}</p></div></div></div>
          </div>
          <div><label class="mb-1 block text-xs font-medium text-slate-700">Pickup assistants</label><MultiSelect v-model="form.assistant_user_ids" :options="availableAssistants" optionLabel="name" optionValue="user_id" filter display="chip" fluid size="small" placeholder="Optional support team" /></div>
          <div><label class="mb-1 block text-xs font-medium text-slate-700">Vehicle <span class="text-red-500">*</span></label><Select v-model="form.vehicle_id" :options="vehicles" optionLabel="label" optionValue="id" filter fluid size="small" placeholder="Select an active store vehicle"><template #option="{ option }"><div><p>{{ option.vehicle_name }} / {{ option.plate_number }}</p><p class="text-xs text-slate-500">{{ option.branch?.name || 'Store-wide' }} / {{ option.vehicle_type || 'Vehicle' }}</p></div></template></Select><p v-if="!vehicles.length" class="mt-1 text-xs text-amber-700">No active vehicles are recorded for this store.</p>
            <div v-if="selectedVehicle" class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-4"><div class="flex items-center justify-between"><p class="font-semibold text-slate-900">Vehicle details</p><Badge value="Active" severity="success" /></div><div class="mt-3 grid gap-3 sm:grid-cols-2"><div v-for="field in vehicleDetails" :key="field.label"><p class="text-xs text-slate-500">{{ field.label }}</p><p class="break-words font-medium text-slate-900">{{ field.value || '-' }}</p></div></div></div>
          </div>
          <div><label class="mb-1 block text-xs font-medium text-slate-700">Expected pickup date <span class="text-red-500">*</span></label><DatePicker v-model="form.expected_pickup_date" :minDate="new Date()" :disabledDates="unavailableDepartureDates" dateFormat="yy-mm-dd" showIcon fluid size="small"><template #date="{ date }"><span :class="{ 'text-red-600 line-through': isUnavailableCalendarDate(date) }">{{ date.day }}</span></template></DatePicker><p v-if="form.vehicle_id" class="mt-1 text-xs text-slate-500">Crossed-out dates already have a planned trip for this vehicle. Use Assign to Trip to group this pickup with an existing trip.</p></div>
          <div><label class="mb-1 block text-xs font-medium text-slate-700">Pickup notes</label><Textarea v-model="form.notes" rows="3" fluid size="small" placeholder="Loading or handling instructions" /></div>
          <div class="flex justify-end gap-2 border-t border-slate-100 pt-4"><Button label="Cancel" severity="secondary" text size="small" type="button" :disabled="submitting" @click="goBack" /><Button label="Assign pickup" severity="warn" size="small" type="submit" :loading="submitting" :disabled="!canSubmit || isSelectedDateUnavailable" /></div>
        </form>
      </template></Card>
    </div>
    <Dialog v-model:visible="tripDialogVisible" modal header="Add Pickup to Existing Trip" :style="{ width: 'min(40rem, 95vw)' }">
      <div class="space-y-4">
        <p class="text-sm text-slate-600">Choose a planned trip for {{ po?.po_number }}. The pickup will use that trip's driver, vehicle, assistants, and departure date.</p>
        <div v-if="loadingTrips" class="space-y-2"><Skeleton height="42px" /><Skeleton height="72px" /></div>
        <Message v-else-if="!plannedTrips.length" severity="info" :closable="false">There are no planned trips available. Create a trip or assign this pickup directly.</Message>
        <template v-else>
          <Select v-model="selectedTripId" :options="plannedTrips" optionLabel="option_label" optionValue="id" filter fluid size="small" placeholder="Select a planned trip" />
          <div v-if="selectedTrip" class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
            <div class="flex flex-wrap items-center justify-between gap-2"><p class="font-semibold text-slate-900">{{ selectedTrip.trip_reference }}</p><Badge value="Planned" severity="warn" /></div>
            <div class="mt-3 grid gap-3 sm:grid-cols-2"><div><p class="text-xs text-slate-500">Driver</p><p class="font-medium">{{ selectedTrip.driver_name || '-' }}</p></div><div><p class="text-xs text-slate-500">Vehicle</p><p class="font-medium">{{ selectedTrip.vehicle_name }} · {{ selectedTrip.plate_number || '-' }}</p></div><div><p class="text-xs text-slate-500">Departure</p><p class="font-medium">{{ dateLabel(selectedTrip.scheduled_departure_at) }}</p></div><div><p class="text-xs text-slate-500">Assigned stops</p><p class="font-medium">{{ selectedTrip.assigned_stops || 0 }}</p></div></div>
          </div>
        </template>
      </div>
      <template #footer><Button label="Cancel" severity="secondary" outlined size="small" @click="tripDialogVisible = false" /><Button label="Add to Trip" icon="pi pi-check" severity="warn" size="small" :loading="addingToTrip" :disabled="!selectedTripId || addingToTrip" @click="confirmAddToTrip" /></template>
    </Dialog>
    <ConfirmDialog /><Toast />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import axiosClient from '@/axios'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import Select from 'primevue/select'
import MultiSelect from 'primevue/multiselect'
import DatePicker from 'primevue/datepicker'
import Textarea from 'primevue/textarea'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'
import Skeleton from 'primevue/skeleton'
import Message from 'primevue/message'
import Dialog from 'primevue/dialog'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const confirm = useConfirm()
const toast = useToast()
const authStore = useAuthStore()
const canManageTrips = authStore.hasPermission('logistics.deliveries.manage')
const base = `/api/logistics/supplier-pickups/${route.params.id}`
const po = ref<any>(null)
const drivers = ref<any[]>([])
const assistants = ref<any[]>([])
const vehicles = ref<any[]>([])
const unavailableDepartureDates = ref<Date[]>([])
const loading = ref(true)
const submitting = ref(false)
const error = ref('')
const tripDialogVisible = ref(false)
const loadingTrips = ref(false)
const addingToTrip = ref(false)
const plannedTrips = ref<any[]>([])
const selectedTripId = ref<number | null>(null)
const form = reactive({ driver_id: null as number | null, assistant_user_ids: [] as number[], vehicle_id: null as number | null, expected_pickup_date: null as Date | null, notes: '' })
const selectedDriver = computed(() => drivers.value.find(row => Number(row.id) === Number(form.driver_id)))
const selectedVehicle = computed(() => vehicles.value.find(row => Number(row.id) === Number(form.vehicle_id)))
const selectedTrip = computed(() => plannedTrips.value.find(row => Number(row.id) === Number(selectedTripId.value)))
const isUnavailableCalendarDate = (date: any) => unavailableDepartureDates.value.some(item => item.getFullYear() === date.year && item.getMonth() === date.month && item.getDate() === date.day)
const isSelectedDateUnavailable = computed(() => Boolean(form.expected_pickup_date && isUnavailableCalendarDate({ year: form.expected_pickup_date.getFullYear(), month: form.expected_pickup_date.getMonth(), day: form.expected_pickup_date.getDate() })))
const driverDetails = computed(() => selectedDriver.value ? [
  { label: 'Name', value: selectedDriver.value.name },
  { label: 'Employee number', value: selectedDriver.value.employee_number },
  { label: 'Position', value: selectedDriver.value.position || 'Driver' },
  { label: 'Branch', value: selectedDriver.value.branch || 'No branch assigned' },
  { label: 'Contact number', value: selectedDriver.value.contact },
  { label: 'Email', value: selectedDriver.value.email },
] : [])
const vehicleDetails = computed(() => selectedVehicle.value ? [
  { label: 'Vehicle name', value: selectedVehicle.value.vehicle_name },
  { label: 'Plate number', value: selectedVehicle.value.plate_number },
  { label: 'Type', value: selectedVehicle.value.vehicle_type },
  { label: 'Brand / model', value: [selectedVehicle.value.brand, selectedVehicle.value.model].filter(Boolean).join(' ') },
  { label: 'Color', value: selectedVehicle.value.color },
  { label: 'Capacity', value: selectedVehicle.value.capacity_kg ? `${selectedVehicle.value.capacity_kg} kg` : null },
  { label: 'Max orders / trip', value: selectedVehicle.value.max_orders_per_trip },
  { label: 'Assigned branch', value: selectedVehicle.value.branch?.name || 'Store-wide' },
  { label: 'Notes', value: selectedVehicle.value.notes },
] : [])
const availableAssistants = computed(() => assistants.value.filter(row => Number(row.user_id) !== Number(selectedDriver.value?.user_id)))
const canSubmit = computed(() => Boolean(po.value && form.driver_id && form.vehicle_id && form.expected_pickup_date && !submitting.value))
const money = (value: any) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value) || 0)
const dateLabel = (value: any) => value ? new Date(`${String(value).slice(0, 10)}T00:00:00`).toLocaleDateString('en-PH', { month: 'short', day: '2-digit', year: 'numeric' }) : 'Not scheduled'
const goBack = () => router.push({ name: 'logistics.deliveries' })
watch(() => form.driver_id, () => { form.assistant_user_ids = form.assistant_user_ids.filter(id => Number(id) !== Number(selectedDriver.value?.user_id)) })
watch(() => form.vehicle_id, async (vehicleId) => {
  unavailableDepartureDates.value = []
  if (!vehicleId) return
  try {
    const response = await axiosClient.get('/api/logistics/trips/vehicle-unavailable-dates', { params: { vehicle_id: vehicleId } })
    unavailableDepartureDates.value = (response.data.data || []).map((value: string) => new Date(`${value}T00:00:00`))
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Unable to check planned trip dates for this vehicle.'
  }
})

async function load() {
  loading.value = true
  error.value = ''
  try {
    const [poRes, driversRes, vehiclesRes] = await Promise.all([
      axiosClient.get(`${base}/assignment`), axiosClient.get(`${base}/drivers`), axiosClient.get(`${base}/vehicles`),
    ])
    po.value = poRes.data.data
    drivers.value = driversRes.data.data.drivers || []
    assistants.value = driversRes.data.data.assistants || []
    vehicles.value = (vehiclesRes.data.data || []).map((vehicle: any) => ({ ...vehicle, label: `${vehicle.vehicle_name} / ${vehicle.plate_number}` }))
  } catch (err: any) { error.value = err.response?.data?.message || 'Could not load pickup assignment details.' }
  finally { loading.value = false }
}
function confirmAssignment() {
  if (!canSubmit.value || submitting.value || isSelectedDateUnavailable.value) return
  confirm.require({ header: 'Assign supplier pickup?', message: `Assign ${selectedDriver.value?.name} to collect PO ${po.value.po_number}?`, acceptLabel: 'Assign pickup', rejectLabel: 'Cancel', accept: submit })
}
async function submit() {
  if (!canSubmit.value || submitting.value || !form.expected_pickup_date || isSelectedDateUnavailable.value) return
  submitting.value = true
  try {
    const date = form.expected_pickup_date
    const pickupDate = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
    await axiosClient.post(`${base}/assign`, { driver_id: form.driver_id, assistant_user_ids: form.assistant_user_ids, vehicle_id: form.vehicle_id, expected_pickup_date: pickupDate, notes: form.notes })
    toast.add({ severity: 'success', summary: 'Pickup assigned', life: 2500 })
    goBack()
  } catch (err: any) { toast.add({ severity: 'error', summary: 'Assignment failed', detail: err.response?.data?.message || 'Please try again.', life: 4000 }) }
  finally { submitting.value = false }
}
function createTripForPickup() {
  if (!po.value) return
  router.push({ name: 'logistics.trips.create', query: { source: 'pickup', order_id: String(po.value.id) } })
}
async function openTripPicker() {
  tripDialogVisible.value = true
  selectedTripId.value = null
  loadingTrips.value = true
  try {
    const response = await axiosClient.get('/api/logistics/trips', { params: { status: 'planned', per_page: 100, sort_by: 'scheduled_departure_at', sort_order: 'asc' } })
    const rows = response.data?.data?.data || []
    plannedTrips.value = rows.map((trip: any) => {
      const reference = `TRIP-${new Date(trip.created_at || Date.now()).getFullYear()}-${String(trip.id).padStart(6, '0')}`
      return {
        ...trip,
        trip_reference: reference,
        driver_name: [trip.driver?.fname, trip.driver?.lname].filter(Boolean).join(' '),
        vehicle_name: trip.vehicle?.vehicle_name || 'Vehicle not set',
        plate_number: trip.vehicle?.plate_number,
        option_label: `${reference} · ${trip.driver?.fname || 'No driver'} · ${trip.vehicle?.vehicle_name || 'No vehicle'} · ${dateLabel(trip.scheduled_departure_at)}`,
      }
    })
  } catch (err: any) {
    tripDialogVisible.value = false
    toast.add({ severity: 'error', summary: 'Trips unavailable', detail: err.response?.data?.message || 'Could not load planned trips.', life: 3500 })
  } finally {
    loadingTrips.value = false
  }
}
function confirmAddToTrip() {
  if (!selectedTrip.value || !po.value || addingToTrip.value || !authStore.hasPermission('logistics.deliveries.manage')) return
  confirm.require({
    header: 'Add supplier pickup to trip?',
    message: `Add PO ${po.value.po_number} to ${selectedTrip.value.trip_reference}? It will use that trip's assigned driver and vehicle.`,
    acceptLabel: 'Add to Trip',
    rejectLabel: 'Keep Editing',
    accept: () => { void addToTrip() },
  })
}
async function addToTrip() {
  if (!selectedTrip.value || !po.value || addingToTrip.value) return
  addingToTrip.value = true
  try {
    const response = await axiosClient.post(`/api/logistics/trips/${selectedTrip.value.id}/orders`, { source_type: 'pickup', order_ids: [po.value.id] })
    if (Number(response.data?.data?.added || 0) !== 1) throw new Error(response.data?.message || 'This pickup could not be added to the trip.')
    toast.add({ severity: 'success', summary: 'Pickup added to trip', detail: `${po.value.po_number} is now on ${selectedTrip.value.trip_reference}.`, life: 3000 })
    tripDialogVisible.value = false
    router.push({ name: 'logistics.trips.detail', params: { id: selectedTrip.value.id } })
  } catch (err: any) {
    toast.add({ severity: 'error', summary: 'Unable to add pickup', detail: err.response?.data?.message || err.message || 'Review trip capacity and destination area.', life: 4500 })
  } finally {
    addingToTrip.value = false
  }
}
onMounted(load)
</script>
