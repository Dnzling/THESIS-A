<template>
  <div class="mx-auto space-y-5 px-4 sm:px-6 lg:px-8">
    <ConfirmDialog />
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-900">Delivery Trips</h1>
        <p class="mt-1 text-sm text-slate-600">{{ driverOnly ? 'Your assigned delivery trips.' : 'Group compatible orders into planned delivery routes.' }}</p>
      </div>
      <Button v-if="canManage" rounded :loading="loadingOptions" icon="pi pi-plus" label="Create Trip" size="small" severity="warn" @click="openCreate" />
    </div>


    <div v-if="pendingOrderId" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
      <span>Add {{ pendingSourceLabel }} order #{{ pendingOrderId }} to a planned trip below, or create a new single-stop trip.</span>
      <Button label="Clear Selection" text size="small" @click="clearPendingOrder" />
    </div>

    <Card class="rounded-2xl border border-slate-200/80 shadow-sm">
      <template #content>
        <div class="grid grid-cols-1 items-center gap-2 md:grid-cols-12">
          <div class="md:col-span-4">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" placeholder="Search trip, driver, or vehicle" fluid size="small" />
            </IconField>
          </div>
          <div class="md:col-span-3">
            <Select v-model="filters.status" :options="tripStatusOptions" optionLabel="label" optionValue="value" placeholder="All statuses" fluid size="small" />
          </div>
          <div class="md:col-span-3">
            <DatePicker v-model="filters.date_range" selectionMode="range" dateFormat="M dd, yy" placeholder="Trip created date" showIcon showButtonBar fluid size="small" />
          </div>
          <div class="md:col-span-2">
            <Button v-if="filters.search || filters.status || filters.date_range?.length" label="Clear Filter" icon="pi pi-filter-slash" severity="danger" outlined size="small" class="w-full" @click="resetFilters" />
            <Button v-else label="Refresh" icon="pi pi-refresh" severity="secondary" outlined size="small" class="w-full" :loading="loading" @click="loadTrips" />
          </div>
        </div>
      </template>
    </Card>

    <Card class="rounded-2xl border border-slate-200/80 shadow-sm">
      <template #content>
        <div v-if="loading" class="space-y-2">
          <Skeleton v-for="n in 5" :key="n" height="2.25rem" borderRadius="8px" />
        </div>
        <DataTable v-else :value="trips" dataKey="id" rowHover paginator lazy scrollable size="small"
          :rows="pageState.rows" :first="(pageState.page - 1) * pageState.rows" :totalRecords="pageState.total"
          :rowsPerPageOptions="[10, 20, 50]" :sortField="sort.field" :sortOrder="sort.order"
          @page="onPage" @sort="onSort" class="text-sm">
          <template #empty>
            <div class="py-8 text-center text-sm text-slate-500">{{ driverOnly ? 'No assigned trips found.' : 'No delivery trips match these filters.' }}</div>
          </template>
          <Column field="created_at" header="Date" sortable style="min-width: 8rem"><template #body="{ data }"><span class="whitespace-nowrap text-xs text-slate-600">{{ formatDateTime(data.created_at) }}</span></template></Column>
          <Column header="Trip Reference" style="min-width: 10rem"><template #body="{ data }"><span class="font-semibold text-slate-900">{{ tripReference(data) }}</span></template></Column>
          <Column header="Driver" style="min-width: 11rem"><template #body="{ data }">{{ data.driver ? `${data.driver.fname} ${data.driver.lname}` : '-' }}</template></Column>
          <Column header="Vehicle" style="min-width: 12rem"><template #body="{ data }">{{ data.vehicle ? `${data.vehicle.vehicle_name} (${data.vehicle.plate_number})` : '-' }}</template></Column>
          <Column header="Stops" style="width: 6rem"><template #body="{ data }">{{ (data.ecommerce_deliveries_count || 0) + (data.sales_deliveries_count || 0) + (data.return_pickups_count || 0) + (data.stock_transfers_count || 0) + (data.replacement_returns_count || 0) }}</template></Column>
          <Column field="status" header="Status" sortable style="min-width: 10rem"><template #body="{ data }"><Badge :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" /></template></Column>
          <Column field="scheduled_departure_at" header="Departure" sortable style="min-width: 8rem"><template #body="{ data }">{{ formatDateOnly(data.scheduled_departure_at) }}</template></Column>
          <Column header="Actions" style="min-width: 5rem">
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <Button v-if="pendingOrderId && data.status === 'planned' && canManage" label="Add Order" size="small" severity="warn" :loading="addingToTripId === data.id" @click="addPendingOrder(data.id)" />
                <Button icon="pi pi-eye" label="View" outlined rounded size="small" aria-label="View trip" @click="openDetail(data)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="tripDialog" modal header="Create Trip" class="w-full max-w-2xl">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm text-slate-600">Driver</label>
          <Select v-model="form.driver_user_id" :options="employees" optionLabel="name" optionValue="id" filter fluid placeholder="Select driver" />
          <p v-if="!loadingOptions && !employees.length" class="mt-1 text-xs text-amber-700">No active driver is available for this store.</p>
          <div v-if="selectedDriver" class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
            <div class="flex items-start gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-orange-100 text-sm font-semibold text-orange-700">
                {{ initials(selectedDriver.name) }}
              </div>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-slate-900">{{ selectedDriver.name }}</p>
                <p class="mt-0.5 text-xs text-slate-500">{{ selectedDriver.role || 'Driver' }}<span v-if="selectedDriver.employee_number"> ({{ selectedDriver.employee_number }})</span></p>
              </div>
            </div>
            <div class="mt-3 grid gap-x-4 gap-y-2 text-xs sm:grid-cols-2">
              <div><span class="text-slate-500">Contact</span><p class="mt-0.5 break-all font-medium text-slate-800">{{ selectedDriver.contact || 'Not provided' }}</p></div>
              <div><span class="text-slate-500">Email</span><p class="mt-0.5 break-all font-medium text-slate-800">{{ selectedDriver.email || 'Not provided' }}</p></div>
              <div class="sm:col-span-2"><span class="text-slate-500">Branch</span><p class="mt-0.5 font-medium text-slate-800">{{ selectedDriver.branch || 'No branch assigned' }}</p></div>
            </div>
          </div>
        </div>
        <div>
          <label class="mb-1 block text-sm text-slate-600">Vehicle</label>
          <Select v-model="form.vehicle_id" :options="vehicles" optionLabel="label" optionValue="id" filter fluid placeholder="Select vehicle" />
          <p v-if="!loadingOptions && !vehicles.length" class="mt-1 text-xs text-amber-700">No active vehicle is available for this store.</p>
          <div v-if="selectedVehicle" class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-slate-900">{{ selectedVehicle.vehicle_name }}</p>
                <p class="mt-0.5 text-xs text-slate-500">{{ selectedVehicle.vehicle_type || 'Vehicle' }}<span v-if="selectedVehicle.plate_number"> ({{ selectedVehicle.plate_number }})</span></p>
              </div>
              <Badge :value="selectedVehicle.status || 'Active'" :severity="selectedVehicle.status === 'active' ? 'success' : 'secondary'" />
            </div>
            <div class="mt-3 grid gap-x-4 gap-y-2 text-xs sm:grid-cols-2">
              <div><span class="text-slate-500">Make / model</span><p class="mt-0.5 font-medium text-slate-800">{{ [selectedVehicle.brand, selectedVehicle.model].filter(Boolean).join(' ') || 'Not provided' }}</p></div>
              <div><span class="text-slate-500">Color</span><p class="mt-0.5 font-medium text-slate-800">{{ selectedVehicle.color || 'Not provided' }}</p></div>
              <div><span class="text-slate-500">Load capacity</span><p class="mt-0.5 font-medium text-slate-800">{{ selectedVehicle.capacity_kg ? `${Number(selectedVehicle.capacity_kg).toLocaleString()} kg` : 'Not set' }}</p></div>
              <div><span class="text-slate-500">Stops per trip</span><p class="mt-0.5 font-medium text-slate-800">{{ selectedVehicle.max_orders_per_trip ?? 'Not set' }}</p></div>
              <div class="sm:col-span-2"><span class="text-slate-500">Assigned branch</span><p class="mt-0.5 font-medium text-slate-800">{{ selectedVehicle.branch?.name || 'Branch not specified' }}</p></div>
              <div v-if="selectedVehicle.notes" class="sm:col-span-2"><span class="text-slate-500">Vehicle notes</span><p class="mt-0.5 whitespace-pre-line text-slate-700">{{ selectedVehicle.notes }}</p></div>
            </div>
          </div>
        </div>
        <div class="sm:col-span-2">
          <label class="mb-1 block text-sm text-slate-600">Employees Accompanying the Driver</label>
          <MultiSelect v-model="form.assistant_user_ids" :options="availableAssistants" optionLabel="name" optionValue="id" filter display="chip" fluid placeholder="Select employees (optional)" />
        </div>
        <div class="sm:col-span-2">
          <label class="mb-1 block text-sm text-slate-600">Scheduled Departure</label>
          <DatePicker v-model="form.scheduled_departure_at" dateFormat="M d, yy" :minDate="new Date()" :disabledDates="unavailableDepartureDates" showIcon fluid>
            <template #date="{ date }"><span :class="{ 'text-red-600 line-through': isUnavailableCalendarDate(date) }">{{ date.day }}</span></template>
          </DatePicker>
          <p v-if="form.vehicle_id" class="mt-1 text-xs text-slate-500">Crossed-out dates already have a planned trip for this vehicle.</p>
        </div>
        <div class="sm:col-span-2">
          <label class="mb-1 block text-sm text-slate-600">Notes</label>
          <Textarea v-model="form.notes" rows="3" fluid placeholder="Optional notes" />
        </div>
      </div>
      <template #footer>
        <Button text severity="secondary" label="Cancel" @click="tripDialog = false" />
        <Button :loading="saving" :disabled="loadingOptions || !form.driver_user_id || !form.vehicle_id || isDepartureDateUnavailable(form.scheduled_departure_at)" rounded label="Create Trip" @click="createTrip" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Badge from 'primevue/badge'
import Dialog from 'primevue/dialog'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import Select from 'primevue/select'
import MultiSelect from 'primevue/multiselect'
import Textarea from 'primevue/textarea'
import DatePicker from 'primevue/datepicker'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Skeleton from 'primevue/skeleton'
import logisticsService from '@/services/logistics.service'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const confirm = useConfirm()
const authStore = useAuthStore()
const canManage = authStore.hasPermission('logistics.deliveries.manage')
const driverOnly = authStore.hasPermission('logistics.driver.trips') || authStore.hasPermission('driver.trips.view')

const trips = ref<any[]>([])
const loading = ref(false)
const loadingOptions = ref(false)
const saving = ref(false)
const tripDialog = ref(false)
const addingToTripId = ref<number | null>(null)
const filters = reactive({ search: '', status: '', date_range: null as Date[] | null })
const pageState = reactive({ page: 1, rows: 10, total: 0 })
const sort = reactive({ field: 'created_at', order: -1 })
const pendingOrderId = computed(() => {
  const id = Number(route.query.order_id || 0)
  return Number.isInteger(id) && id > 0 ? id : null
})
const pendingSource = computed<'ecommerce' | 'sales' | 'return_pickup'>(() => {
  const source = String(route.query.source || '').toLowerCase()
  return source === 'sales' || source === 'return_pickup' ? source : 'ecommerce'
})
const pendingSourceLabel = computed(() => pendingSource.value === 'return_pickup' ? 'return pickup' : pendingSource.value)
const tripStatusOptions = [
  { label: 'All statuses', value: '' },
  { label: 'Planned', value: 'planned' },
  { label: 'In Transit', value: 'in_transit' },
  { label: 'Out For Delivery', value: 'out_for_delivery' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' },
]

const employees = ref<any[]>([])
const assistants = ref<any[]>([])
const vehicles = ref<any[]>([])
const unavailableDepartureDates = ref<Date[]>([])

const form = reactive({
  driver_user_id: null as number | null,
  assistant_user_ids: [] as number[],
  vehicle_id: null as number | null,
  scheduled_departure_at: null as Date | null,
  notes: '',
})
const availableAssistants = computed(() => assistants.value.filter(employee => Number(employee.id) !== Number(form.driver_user_id)))
const selectedDriver = computed(() => employees.value.find(employee => Number(employee.id) === Number(form.driver_user_id)) || null)
const selectedVehicle = computed(() => vehicles.value.find(vehicle => Number(vehicle.id) === Number(form.vehicle_id)) || null)
const initials = (name?: string) => String(name || '?').trim().split(/\s+/).slice(0, 2).map(part => part[0]?.toUpperCase() || '').join('')
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
  year: value.getFullYear(),
  month: value.getMonth(),
  day: value.getDate(),
}))

const loadOptions = async () => {
  loadingOptions.value = true
  try {
    const [empRes, vehicleRes] = await Promise.all([
      logisticsService.getLogisticsEmployees(),
      logisticsService.getVehicles({ per_page: 100, status: 'active' }),
    ])

    employees.value = empRes?.data?.drivers || []
    assistants.value = empRes?.data?.employees || []
    const rows = vehicleRes?.data?.data || []
    vehicles.value = rows.map((v: any) => ({ ...v, label: `${v.vehicle_name} (${v.plate_number})` }))
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Options Failed', detail: error?.response?.data?.message || 'Failed to load drivers and vehicles.', life: 3500 })
  } finally {
    loadingOptions.value = false
  }
}

const loadTrips = async () => {
  loading.value = true
  try {
    const res = await logisticsService.getTrips({
      page: pageState.page,
      per_page: pageState.rows,
      status: filters.status || undefined,
      search: filters.search || undefined,
      date_from: formatDateParam(filters.date_range?.[0]),
      date_to: formatDateParam(filters.date_range?.[1]),
      sort_by: sort.field,
      sort_order: sort.order === 1 ? 'asc' : 'desc',
    })
    trips.value = res?.data?.data || []
    pageState.total = Number(res?.data?.total || 0)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Load Failed', detail: error?.response?.data?.message || 'Failed to load trips.', life: 3000 })
  } finally {
    loading.value = false
  }
}

const onPage = (event: any) => {
  pageState.page = Number(event.page || 0) + 1
  pageState.rows = Number(event.rows || 10)
  loadTrips()
}

const onSort = (event: any) => {
  sort.field = ['created_at', 'scheduled_departure_at', 'status'].includes(event.sortField) ? event.sortField : 'created_at'
  sort.order = event.sortOrder === 1 ? 1 : -1
  pageState.page = 1
  loadTrips()
}

const resetFilters = () => {
  filters.search = ''
  filters.status = ''
  filters.date_range = null
  pageState.page = 1
  loadTrips()
}

const formatDateParam = (value?: Date | null) => {
  if (!value) return undefined
  const year = value.getFullYear()
  const month = String(value.getMonth() + 1).padStart(2, '0')
  const day = String(value.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const clearPendingOrder = () => router.replace({ name: 'logistics.trips' })

const addPendingOrder = async (tripId: number) => {
  if (!pendingOrderId.value || addingToTripId.value) return
  addingToTripId.value = tripId
  try {
    const response = await logisticsService.addOrdersToTrip(tripId, {
      source_type: pendingSource.value,
      order_ids: [pendingOrderId.value],
    })
    if (Number(response?.data?.added || 0) !== 1) {
      toast.add({ severity: 'warn', summary: 'Order Not Added', detail: response?.message || 'Check that the order is ready and fits the trip.', life: 4000 })
      return
    }
    toast.add({ severity: 'success', summary: 'Added to Trip', detail: 'The order now uses this trip’s driver and vehicle.', life: 3000 })
    router.push({ name: 'logistics.trips.detail', params: { id: tripId } })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Add Failed', detail: error?.response?.data?.message || 'Unable to add this order to the trip.', life: 3500 })
  } finally {
    addingToTripId.value = null
  }
}

const openCreate = async () => {
  form.driver_user_id = null
  form.assistant_user_ids = []
  form.vehicle_id = null
  form.scheduled_departure_at = null
  form.notes = ''
  tripDialog.value = true
  if (!employees.value.length || !vehicles.value.length) await loadOptions()
}

const createTrip = async () => {
  if (isDepartureDateUnavailable(form.scheduled_departure_at)) {
    toast.add({ severity: 'warn', summary: 'Vehicle Already Booked', detail: 'Choose another vehicle or departure date.', life: 3000 })
    return
  }
  if (!form.driver_user_id || !form.vehicle_id) {
    toast.add({ severity: 'warn', summary: 'Missing', detail: 'Select driver and vehicle.', life: 2500 })
    return
  }

  const departure = formatDateOnly(toDateOnly(form.scheduled_departure_at) || undefined)
  confirm.require({
    header: 'Create this trip?',
    message: `Create a trip with ${selectedDriver.value?.name || 'the selected driver'} and ${selectedVehicle.value?.vehicle_name || 'the selected vehicle'}${departure ? ` departing on ${departure}` : ''}?`,
    acceptLabel: 'Create Trip',
    rejectLabel: 'Continue Editing',
    accept: () => { void submitTrip() },
  })
}

const submitTrip = async () => {
  if (saving.value) return
  saving.value = true
  try {
    const response = await logisticsService.createTrip({
      driver_user_id: form.driver_user_id,
      assistant_user_ids: form.assistant_user_ids,
      vehicle_id: form.vehicle_id,
      scheduled_departure_at: toDateOnly(form.scheduled_departure_at),
      notes: form.notes || null,
    })
    toast.add({ severity: 'success', summary: 'Created', detail: 'Trip created.', life: 2500 })
    tripDialog.value = false
    if (pendingOrderId.value && response?.data?.id) {
      await addPendingOrder(Number(response.data.id))
    }
    await loadTrips()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Create Failed', detail: error?.response?.data?.message || 'Failed to create trip.', life: 3000 })
  } finally {
    saving.value = false
  }
}

const openDetail = (trip: any) => {
  router.push({ name: 'logistics.trips.detail', params: { id: trip.id } })
}

const formatStatus = (value?: string) => value ? value.replace(/_/g, ' ').replace(/\b\w/g, m => m.toUpperCase()) : '-'
const tripReference = (trip: any) => `TRIP-${new Date(trip.created_at || Date.now()).getFullYear()}-${String(trip.id).padStart(6, '0')}`
const statusSeverity = (value?: string) => {
  if (value === 'completed') return 'success'
  if (value === 'cancelled') return 'secondary'
  if (['in_transit', 'out_for_delivery'].includes(String(value))) return 'info'
  if (['planned', 'pending'].includes(String(value))) return 'warn'
  return 'secondary'
}
const formatDateTime = (value?: string) => value ? new Date(value).toLocaleString('en-PH') : '-'
const formatDateOnly = (value?: string) => {
  if (!value) return '-'
  const raw = String(value)
  const dateOnly = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/)
  if (dateOnly) {
    const date = new Date(Date.UTC(Number(dateOnly[1]), Number(dateOnly[2]) - 1, Number(dateOnly[3])))
    return new Intl.DateTimeFormat('en-PH', { year: 'numeric', month: 'short', day: '2-digit', timeZone: 'UTC' }).format(date)
  }

  const date = new Date(raw)
  return Number.isNaN(date.getTime()) ? '-' : new Intl.DateTimeFormat('en-PH', { year: 'numeric', month: 'short', day: '2-digit', timeZone: 'Asia/Manila' }).format(date)
}
const toDateOnly = (value: Date | null) => value
  ? `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`
  : null

let searchTimer: ReturnType<typeof setTimeout> | null = null
watch(() => [filters.status], () => { pageState.page = 1; loadTrips() })
watch(() => filters.search, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => { pageState.page = 1; loadTrips() }, 300)
})
watch(() => filters.date_range, (range) => {
  if (range?.length === 2 || !range?.length) { pageState.page = 1; loadTrips() }
}, { deep: true })

onMounted(async () => {
  await Promise.all([canManage ? loadOptions() : Promise.resolve(), loadTrips()])
})
</script>
