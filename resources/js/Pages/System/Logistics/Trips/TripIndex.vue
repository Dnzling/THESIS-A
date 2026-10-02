<template>
  <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Delivery Trips</h1>
          <p class="mt-1 text-sm text-slate-600">{{ driverOnly ? 'Your assigned delivery trips.' : 'Group multiple orders into a single truck route.' }}</p>
        </div>
        <div class="flex gap-2">
          <Button icon="pi pi-refresh" label="Refresh" text @click="loadTrips" />
          <Button v-if="canManage" :loading="loadingOptions" icon="pi pi-plus" label="Create Trip" severity="success" @click="openCreate" />
        </div>
      </div>


    <div v-if="pendingOrderId" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
      <span>Add {{ pendingSourceLabel }} order #{{ pendingOrderId }} to a planned trip below, or create a new single-stop trip.</span>
      <Button label="Clear Selection" text size="small" @click="clearPendingOrder" />
    </div>

    <div v-if="loading" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"><div v-for="n in 3" :key="n" class="h-52 animate-pulse rounded-3xl bg-slate-100"></div></div>
    <div v-else-if="!trips.length" class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
      <i class="pi pi-truck text-3xl text-slate-400"></i>
      <h2 class="mt-3 font-semibold text-slate-900">No assigned trips</h2>
      <p class="mt-1 text-sm text-slate-500">{{ driverOnly ? 'Trips assigned to you will appear here.' : 'Create a trip to start batching deliveries.' }}</p>
    </div>
    <div v-else class="grid gap-4 sm:grid-cols-2 lg:hidden">
      <Card v-for="trip in trips" :key="trip.id" class="rounded-3xl border border-slate-200/80 shadow-sm">
        <template #content>
          <div class="flex items-start justify-between gap-3">
            <div><p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Trip reference</p><h2 class="mt-1 text-lg font-semibold text-slate-900">{{ tripReference(trip) }}</h2></div>
            <Tag :value="formatStatus(trip.status)" :severity="statusSeverity(trip.status)" />
          </div>
          <div class="mt-5 space-y-3 text-sm text-slate-700">
            <p><i class="pi pi-user mr-2 text-slate-400"></i>{{ trip.driver ? `${trip.driver.fname} ${trip.driver.lname}` : 'No driver' }}</p>
            <p><i class="pi pi-truck mr-2 text-slate-400"></i>{{ trip.vehicle ? `${trip.vehicle.vehicle_name} (${trip.vehicle.plate_number})` : 'No vehicle' }}</p>
            <p><i class="pi pi-calendar mr-2 text-slate-400"></i>{{ formatDateTime(trip.scheduled_departure_at) }}</p>
            <p><i class="pi pi-map-marker mr-2 text-slate-400"></i>{{ (trip.ecommerce_deliveries_count || 0) + (trip.sales_deliveries_count || 0) + (trip.return_pickups_count || 0) }} stops</p>
          </div>
          <div class="mt-5 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
            <Button label="View Trip" icon="pi pi-arrow-right" size="small" outlined @click="openDetail(trip)" />
            <Button v-if="pendingOrderId && trip.status === 'planned' && canManage" label="Add Order" size="small" severity="success" :loading="addingToTripId === trip.id" @click="addPendingOrder(trip.id)" />
          </div>
        </template>
      </Card>
    </div>

    <Card v-if="!loading && trips.length" class="hidden rounded-3xl border border-slate-200/80 shadow-sm lg:block">
      <template #content>
        <DataTable :value="trips" dataKey="id" rowHover class="text-sm">
          <Column header="Trip" style="width: 12rem"><template #body="{ data }"><span class="font-semibold text-slate-900">{{ tripReference(data) }}</span></template></Column>
          <Column header="Driver"><template #body="{ data }">{{ data.driver ? `${data.driver.fname} ${data.driver.lname}` : '-' }}</template></Column>
          <Column header="Vehicle"><template #body="{ data }">{{ data.vehicle ? `${data.vehicle.vehicle_name} (${data.vehicle.plate_number})` : '-' }}</template></Column>
          <Column header="Stops" style="width: 6rem"><template #body="{ data }">{{ (data.ecommerce_deliveries_count || 0) + (data.sales_deliveries_count || 0) + (data.return_pickups_count || 0) }}</template></Column>
          <Column header="Status" style="width: 11rem"><template #body="{ data }"><Tag :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" /></template></Column>
          <Column header="Scheduled" style="width: 13rem"><template #body="{ data }">{{ formatDateTime(data.scheduled_departure_at) }}</template></Column>
          <Column header="Actions" style="width: 12rem">
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <Button v-if="pendingOrderId && data.status === 'planned' && canManage" label="Add Order" size="small" severity="success" :loading="addingToTripId === data.id" @click="addPendingOrder(data.id)" />
                <Button icon="pi pi-eye" text rounded severity="info" aria-label="View trip" @click="openDetail(data)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="tripDialog" modal header="Create Trip" class="w-full max-w-2xl">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm text-slate-600">Driver</label>
          <Select v-model="form.driver_user_id" :options="employees" optionLabel="name" optionValue="id" filter fluid placeholder="Select driver" />
          <p v-if="!loadingOptions && !employees.length" class="mt-1 text-xs text-amber-700">No active driver is available for this store.</p>
        </div>
        <div>
          <label class="mb-1 block text-sm text-slate-600">Vehicle</label>
          <Select v-model="form.vehicle_id" :options="vehicles" optionLabel="label" optionValue="id" filter fluid placeholder="Select vehicle" />
          <p v-if="!loadingOptions && !vehicles.length" class="mt-1 text-xs text-amber-700">No active vehicle is available for this store.</p>
        </div>
        <div class="md:col-span-2">
          <label class="mb-1 block text-sm text-slate-600">Scheduled Departure</label>
          <DatePicker v-model="form.scheduled_departure_at" showTime hourFormat="12" fluid />
        </div>
        <div class="md:col-span-2">
          <label class="mb-1 block text-sm text-slate-600">Notes</label>
          <Textarea v-model="form.notes" rows="3" fluid placeholder="Optional notes" />
        </div>
      </div>
      <template #footer>
        <Button text severity="secondary" label="Cancel" @click="tripDialog = false" />
        <Button :loading="saving" :disabled="loadingOptions || !form.driver_user_id || !form.vehicle_id" severity="success" label="Create Trip" @click="createTrip" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import DatePicker from 'primevue/datepicker'
import logisticsService from '@/services/logistics.service'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const authStore = useAuthStore()
const canManage = authStore.hasPermission('logistics.deliveries.manage')
const driverOnly = authStore.hasPermission('driver.trips.view') && !canManage

const trips = ref<any[]>([])
const loading = ref(false)
const loadingOptions = ref(false)
const saving = ref(false)
const tripDialog = ref(false)
const addingToTripId = ref<number | null>(null)
const pendingOrderId = computed(() => {
  const id = Number(route.query.order_id || 0)
  return Number.isInteger(id) && id > 0 ? id : null
})
const pendingSource = computed<'ecommerce' | 'sales' | 'return_pickup'>(() => {
  const source = String(route.query.source || '').toLowerCase()
  return source === 'sales' || source === 'return_pickup' ? source : 'ecommerce'
})
const pendingSourceLabel = computed(() => pendingSource.value === 'return_pickup' ? 'return pickup' : pendingSource.value)

const employees = ref<any[]>([])
const vehicles = ref<any[]>([])

const form = reactive({
  driver_user_id: null as number | null,
  vehicle_id: null as number | null,
  scheduled_departure_at: null as Date | null,
  notes: '',
})

const loadOptions = async () => {
  loadingOptions.value = true
  try {
    const [empRes, vehicleRes] = await Promise.all([
      logisticsService.getLogisticsEmployees(),
      logisticsService.getVehicles({ per_page: 100, status: 'active' }),
    ])

    employees.value = empRes?.data?.drivers || []
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
    const res = await logisticsService.getTrips({ per_page: 50 })
    trips.value = res?.data?.data || []
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Load Failed', detail: error?.response?.data?.message || 'Failed to load trips.', life: 3000 })
  } finally {
    loading.value = false
  }
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
  form.vehicle_id = null
  form.scheduled_departure_at = null
  form.notes = ''
  tripDialog.value = true
  if (!employees.value.length || !vehicles.value.length) await loadOptions()
}

const createTrip = async () => {
  if (!form.driver_user_id || !form.vehicle_id) {
    toast.add({ severity: 'warn', summary: 'Missing', detail: 'Select driver and vehicle.', life: 2500 })
    return
  }

  saving.value = true
  try {
    const response = await logisticsService.createTrip({
      driver_user_id: form.driver_user_id,
      vehicle_id: form.vehicle_id,
      scheduled_departure_at: form.scheduled_departure_at ? new Date(form.scheduled_departure_at).toISOString() : null,
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
  if (value === 'cancelled') return 'danger'
  if (value === 'in_transit') return 'info'
  if (value === 'out_for_delivery') return 'info'
  return 'warning'
}
const formatDateTime = (value?: string) => value ? new Date(value).toLocaleString('en-PH') : '-'

onMounted(async () => {
  await Promise.all([canManage ? loadOptions() : Promise.resolve(), loadTrips()])
})
</script>
