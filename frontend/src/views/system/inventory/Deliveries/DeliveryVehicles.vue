<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <div class="flex items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900">Delivery Vehicles</h1>
            <p class="text-sm text-gray-500">Register vehicles with validation to reduce delivery errors.</p>
          </div>
          <div class="flex gap-2">
            <Button severity="info" outlined icon="pi pi-arrow-left" label="Back to Deliveries" @click="goBack" />
            <Button severity="info" icon="pi pi-plus" label="Add Vehicle" @click="openCreate" />
          </div>
        </div>
      </template>
    </Card>

    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
          <div class="md:col-span-8">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" fluid placeholder="Search by name, plate, brand..." />
            </IconField>
          </div>
          <div class="md:col-span-4">
            <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" showClear fluid placeholder="Filter status" />
          </div>
        </div>
      </template>
    </Card>

    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <DataTable
          :value="vehicles"
          :loading="loading"
          dataKey="id"
          stripedRows
          paginator
          lazy
          :rows="pageState.rows"
          :first="(pageState.page - 1) * pageState.rows"
          :totalRecords="pageState.total"
          :rowsPerPageOptions="[10, 20, 50]"
          @page="onPage"
        >
          <Column field="vehicle_name" header="Vehicle" />
          <Column field="plate_number" header="Plate Number">
            <template #body="{ data }">
              <Tag severity="info" :value="data.plate_number" />
            </template>
          </Column>
          <Column field="vehicle_type" header="Type" />
          <Column field="capacity_kg" header="Capacity (kg)" />
          <Column field="max_orders_per_trip" header="Max Orders/Trip" />
          <Column field="status" header="Status">
            <template #body="{ data }">
              <Tag :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
          <Column header="Actions">
            <template #body="{ data }">
              <Button text severity="info" icon="pi pi-pencil" @click="openEdit(data)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="formDialog" modal :header="editingId ? 'Edit Vehicle' : 'Register Vehicle'" class="w-full max-w-2xl">
      <div class="space-y-4">
        <Message severity="info" :closable="false">
          Tip: Plate number is auto-uppercased and must be unique per store.
        </Message>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="text-sm text-gray-600">Vehicle Name</label>
            <InputText v-model="form.vehicle_name" fluid placeholder="e.g. Main Delivery Van" />
          </div>
          <div>
            <label class="text-sm text-gray-600">Plate Number</label>
            <InputText v-model="form.plate_number" fluid placeholder="e.g. ABC 1234" @input="form.plate_number = String(form.plate_number || '').toUpperCase()" />
          </div>
          <div>
            <label class="text-sm text-gray-600">Vehicle Type</label>
            <Select v-model="form.vehicle_type" :options="vehicleTypes" optionLabel="label" optionValue="value" fluid />
          </div>
          <div>
            <label class="text-sm text-gray-600">Status</label>
            <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" fluid />
          </div>
          <div>
            <label class="text-sm text-gray-600">Brand</label>
            <InputText v-model="form.brand" fluid />
          </div>
          <div>
            <label class="text-sm text-gray-600">Model</label>
            <InputText v-model="form.model" fluid />
          </div>
          <div>
            <label class="text-sm text-gray-600">Color</label>
            <InputText v-model="form.color" fluid />
          </div>
          <div>
            <label class="text-sm text-gray-600">Capacity (kg)</label>
            <InputNumber v-model="form.capacity_kg" fluid :min="0" :minFractionDigits="0" :maxFractionDigits="2" />
          </div>
          <div class="md:col-span-2">
            <label class="text-sm text-gray-600">Max Orders Per Trip</label>
            <InputNumber v-model="form.max_orders_per_trip" fluid :min="1" :max="999" />
          </div>
          <div class="md:col-span-2">
            <label class="text-sm text-gray-600">Notes</label>
            <Textarea v-model="form.notes" rows="3" fluid placeholder="Operational notes..." />
          </div>
        </div>
      </div>
      <template #footer>
        <Button text severity="secondary" label="Cancel" @click="formDialog = false" />
        <Button :loading="saving" severity="info" :label="editingId ? 'Update Vehicle' : 'Save Vehicle'" @click="saveVehicle" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '@/services/inventory.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Message from 'primevue/message'

const router = useRouter()
const toast = useToast()
const loading = ref(false)
const saving = ref(false)
const formDialog = ref(false)
const editingId = ref<number | null>(null)
const vehicles = ref<any[]>([])

const pageState = reactive({ page: 1, rows: 10, total: 0 })
const filters = reactive({ search: '', status: null as string | null })

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Maintenance', value: 'maintenance' },
  { label: 'Inactive', value: 'inactive' },
]
const vehicleTypes = [
  { label: 'Motorcycle', value: 'motorcycle' },
  { label: 'Van', value: 'van' },
  { label: 'Truck', value: 'truck' },
  { label: 'Car', value: 'car' },
  { label: 'Other', value: 'other' },
]

const form = reactive<any>({
  vehicle_name: '',
  vehicle_type: 'van',
  plate_number: '',
  brand: '',
  model: '',
  color: '',
  capacity_kg: null,
  max_orders_per_trip: 10,
  status: 'active',
  notes: '',
})

const resetForm = () => {
  editingId.value = null
  form.vehicle_name = ''
  form.vehicle_type = 'van'
  form.plate_number = ''
  form.brand = ''
  form.model = ''
  form.color = ''
  form.capacity_kg = null
  form.max_orders_per_trip = 10
  form.status = 'active'
  form.notes = ''
}

const loadVehicles = async () => {
  loading.value = true
  try {
    const res = await inventoryService.getDeliveryVehicles({
      page: pageState.page,
      per_page: pageState.rows,
      search: filters.search || undefined,
      status: filters.status || undefined,
    })
    const payload = res?.data
    vehicles.value = payload?.data || []
    pageState.total = Number(payload?.total || 0)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load vehicles', life: 3000 })
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  resetForm()
  formDialog.value = true
}

const openEdit = (row: any) => {
  editingId.value = row.id
  form.vehicle_name = row.vehicle_name || ''
  form.vehicle_type = row.vehicle_type || 'van'
  form.plate_number = row.plate_number || ''
  form.brand = row.brand || ''
  form.model = row.model || ''
  form.color = row.color || ''
  form.capacity_kg = row.capacity_kg ? Number(row.capacity_kg) : null
  form.max_orders_per_trip = Number(row.max_orders_per_trip || 10)
  form.status = row.status || 'active'
  form.notes = row.notes || ''
  formDialog.value = true
}

const saveVehicle = async () => {
  if (!form.vehicle_name || !form.plate_number) {
    toast.add({ severity: 'warn', summary: 'Missing fields', detail: 'Vehicle name and plate number are required.', life: 2500 })
    return
  }

  saving.value = true
  const payload = {
    ...form,
    plate_number: String(form.plate_number || '').toUpperCase().trim(),
  }
  try {
    if (editingId.value) {
      await inventoryService.updateDeliveryVehicle(editingId.value, payload)
      toast.add({ severity: 'success', summary: 'Updated', detail: 'Vehicle updated successfully.', life: 2200 })
    } else {
      await inventoryService.createDeliveryVehicle(payload)
      toast.add({ severity: 'success', summary: 'Created', detail: 'Vehicle registered successfully.', life: 2200 })
    }
    formDialog.value = false
    loadVehicles()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Save failed', detail: error?.response?.data?.message || 'Failed to save vehicle', life: 3200 })
  } finally {
    saving.value = false
  }
}

const onPage = (event: any) => {
  pageState.page = Number(event.page || 0) + 1
  pageState.rows = Number(event.rows || 10)
  loadVehicles()
}

const formatStatus = (status: string) => status.replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase())
const statusSeverity = (status: string) => (status === 'active' ? 'success' : status === 'maintenance' ? 'warning' : 'secondary')
const goBack = () => router.push({ name: 'inventory.ecommerce-deliveries' })

watch(() => [filters.search, filters.status], () => {
  pageState.page = 1
  loadVehicles()
})

onMounted(loadVehicles)
</script>

