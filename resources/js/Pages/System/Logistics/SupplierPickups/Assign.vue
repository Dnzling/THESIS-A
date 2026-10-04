<template>
  <div class="mx-auto max-w-6xl space-y-4 px-4 py-5 text-sm md:px-6">
    <div class="flex items-start gap-3"><Button icon="pi pi-arrow-left" text rounded severity="secondary" size="small" @click="goBack" /><div><h1 class="text-xl font-semibold text-slate-900">Assign supplier pickup</h1><p class="mt-1 text-xs text-slate-500">Choose the driver, support team, vehicle, and pickup date for this accepted PO.</p></div></div>
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
          <div><label class="mb-1 block text-xs font-medium text-slate-700">Expected pickup date <span class="text-red-500">*</span></label><DatePicker v-model="form.expected_pickup_date" :minDate="new Date()" dateFormat="yy-mm-dd" showIcon fluid size="small" /></div>
          <div><label class="mb-1 block text-xs font-medium text-slate-700">Pickup notes</label><Textarea v-model="form.notes" rows="3" fluid size="small" placeholder="Loading or handling instructions" /></div>
          <div class="flex justify-end gap-2 border-t border-slate-100 pt-4"><Button label="Cancel" severity="secondary" text size="small" type="button" @click="goBack" /><Button label="Assign pickup" severity="warn" size="small" type="submit" :loading="submitting" :disabled="!canSubmit" /></div>
        </form>
      </template></Card>
    </div>
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

const route = useRoute()
const router = useRouter()
const confirm = useConfirm()
const toast = useToast()
const base = `/api/logistics/supplier-pickups/${route.params.id}`
const po = ref<any>(null)
const drivers = ref<any[]>([])
const assistants = ref<any[]>([])
const vehicles = ref<any[]>([])
const loading = ref(true)
const submitting = ref(false)
const error = ref('')
const form = reactive({ driver_id: null as number | null, assistant_user_ids: [] as number[], vehicle_id: null as number | null, expected_pickup_date: null as Date | null, notes: '' })
const selectedDriver = computed(() => drivers.value.find(row => Number(row.id) === Number(form.driver_id)))
const selectedVehicle = computed(() => vehicles.value.find(row => Number(row.id) === Number(form.vehicle_id)))
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
const canSubmit = computed(() => Boolean(po.value && form.driver_id && form.vehicle_id && form.expected_pickup_date))
const money = (value: any) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value) || 0)
const goBack = () => router.push({ name: 'logistics.deliveries' })
watch(() => form.driver_id, () => { form.assistant_user_ids = form.assistant_user_ids.filter(id => Number(id) !== Number(selectedDriver.value?.user_id)) })

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
  if (!canSubmit.value) return
  confirm.require({ header: 'Assign supplier pickup?', message: `Assign ${selectedDriver.value?.name} to collect PO ${po.value.po_number}?`, acceptLabel: 'Assign pickup', rejectLabel: 'Cancel', accept: submit })
}
async function submit() {
  if (!canSubmit.value || !form.expected_pickup_date) return
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
onMounted(load)
</script>
