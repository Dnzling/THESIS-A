<template>
  <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="rounded-3xl border border-slate-200/80 bg-linear-to-br from-orange-50 via-white to-amber-50 p-6 shadow-sm">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Delivery Trips</h1>
          <p class="mt-1 text-sm text-slate-600">Group multiple orders into a single truck route.</p>
        </div>
        <div class="flex gap-2">
          <Button icon="pi pi-refresh" label="Refresh" outlined @click="loadTrips" />
          <Button v-if="canManage" icon="pi pi-plus" label="Create Trip" severity="success" @click="openCreate" />
        </div>
      </div>
    </div>

    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #content>
        <DataTable :value="trips" :loading="loading" dataKey="id" stripedRows>
          <template #empty>
            <div class="py-8 text-center text-slate-500">No trips yet.</div>
          </template>
          <Column header="Trip" style="width: 8rem">
            <template #body="{ data }">
              <span class="font-medium text-slate-900">#{{ data.id }}</span>
            </template>
          </Column>
          <Column header="Driver">
            <template #body="{ data }">
              <span class="text-sm text-slate-700">{{ data.driver ? `${data.driver.fname} ${data.driver.lname}` : '-' }}</span>
            </template>
          </Column>
          <Column header="Vehicle">
            <template #body="{ data }">
              <span class="text-sm text-slate-700">{{ data.vehicle ? `${data.vehicle.vehicle_name} (${data.vehicle.plate_number})` : '-' }}</span>
            </template>
          </Column>
          <Column header="Orders" style="width: 8rem">
            <template #body="{ data }">
              <span class="text-sm text-slate-700">{{ (data.ecommerce_deliveries_count || 0) + (data.sales_deliveries_count || 0) }}</span>
            </template>
          </Column>
          <Column header="Status" style="width: 10rem">
            <template #body="{ data }">
              <Tag :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
          <Column header="Scheduled" style="width: 12rem">
            <template #body="{ data }">
              <span class="text-xs text-slate-500">{{ formatDateTime(data.scheduled_departure_at) }}</span>
            </template>
          </Column>
          <Column header="Actions" style="width: 8rem">
            <template #body="{ data }">
              <Button icon="pi pi-eye" text rounded severity="info" @click="openDetail(data)" />
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
        </div>
        <div>
          <label class="mb-1 block text-sm text-slate-600">Vehicle</label>
          <Select v-model="form.vehicle_id" :options="vehicles" optionLabel="label" optionValue="id" filter fluid placeholder="Select vehicle" />
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
        <Button :loading="saving" severity="success" label="Create Trip" @click="createTrip" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
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
const toast = useToast()
const authStore = useAuthStore()
const canManage = authStore.hasPermission('logistics.deliveries.manage')

const trips = ref<any[]>([])
const loading = ref(false)
const saving = ref(false)
const tripDialog = ref(false)

const employees = ref<any[]>([])
const vehicles = ref<any[]>([])

const form = reactive({
  driver_user_id: null as number | null,
  vehicle_id: null as number | null,
  scheduled_departure_at: null as Date | null,
  notes: '',
})

const loadOptions = async () => {
  const [empRes, vehicleRes] = await Promise.all([
    logisticsService.getLogisticsEmployees(),
    logisticsService.getVehicles({ per_page: 100 }),
  ])

  employees.value = empRes?.data || []
  const rows = vehicleRes?.data?.data || []
  vehicles.value = rows.map((v: any) => ({ ...v, label: `${v.vehicle_name} (${v.plate_number})` }))
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

const openCreate = () => {
  form.driver_user_id = null
  form.vehicle_id = null
  form.scheduled_departure_at = null
  form.notes = ''
  tripDialog.value = true
}

const createTrip = async () => {
  if (!form.driver_user_id || !form.vehicle_id) {
    toast.add({ severity: 'warn', summary: 'Missing', detail: 'Select driver and vehicle.', life: 2500 })
    return
  }

  saving.value = true
  try {
    await logisticsService.createTrip({
      driver_user_id: form.driver_user_id,
      vehicle_id: form.vehicle_id,
      scheduled_departure_at: form.scheduled_departure_at ? new Date(form.scheduled_departure_at).toISOString() : null,
      notes: form.notes || null,
    })
    toast.add({ severity: 'success', summary: 'Created', detail: 'Trip created.', life: 2500 })
    tripDialog.value = false
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
const statusSeverity = (value?: string) => {
  if (value === 'completed') return 'success'
  if (value === 'cancelled') return 'danger'
  if (value === 'in_transit') return 'info'
  return 'warning'
}
const formatDateTime = (value?: string) => value ? new Date(value).toLocaleString('en-PH') : '-'

onMounted(async () => {
  await Promise.all([loadOptions(), loadTrips()])
})
</script>

