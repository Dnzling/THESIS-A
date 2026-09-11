<template>
  <div class="mx-auto  space-y-6 px-4 py-6 sm:px-6 lg:px-8">

      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="goBack" />
        <div>
          <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Assign Supplier Pickup</h1>
          <p class="mt-1 text-sm text-slate-600">Assign your store team to collect supplies from the supplier.</p>
        </div>
      </div>


    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Purchase Order Summary</template>
      <template #content>
        <div v-if="loading" class="text-sm text-slate-500">Loading purchase order...</div>
        <div v-else-if="!po" class="text-sm text-slate-500">Purchase order not found.</div>
        <div v-else class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
          <div><span class="text-slate-500">PO Number:</span> <strong>{{ po.po_number }}</strong></div>
          <div><span class="text-slate-500">Supplier:</span> <strong>{{ po.supplier?.supplier_name || '-' }}</strong></div>
          <div><span class="text-slate-500">Pickup From:</span> <strong>{{ po.supplier?.address || 'Supplier address on file' }}</strong></div>
          <div><span class="text-slate-500">Receive At:</span> <strong>{{ po.branch?.name || po.branch?.branch_name || 'Assigned branch' }}</strong></div>
          <div><span class="text-slate-500">Status:</span> <Tag :value="formatStatus(po.status)" severity="success" /></div>
        </div>
      </template>
    </Card>

    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Pickup Assignment</template>
      <template #content>
        <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="submit">
          <div><label class="mb-1 block text-sm font-medium text-slate-700">Driver *</label><Select v-model="form.driver_id" :options="drivers" optionLabel="name" optionValue="id" fluid filter placeholder="Select driver" :loading="loadingDrivers"><template #option="{ option }"><div><p class="text-sm font-medium text-slate-800">{{ option.name }}</p><p class="text-xs text-slate-500">{{ option.branch || 'No branch assigned' }}</p></div></template></Select><small class="text-xs text-slate-500">Drivers from your branch appear first.</small></div>
          <div><label class="mb-1 block text-sm font-medium text-slate-700">Delivery Assistants</label><MultiSelect v-model="form.assistant_user_ids" :options="availableAssistants" optionLabel="name" optionValue="user_id" fluid filter display="chip" placeholder="Select employees (optional)" :loading="loadingDrivers"><template #option="{ option }"><div><p class="text-sm font-medium text-slate-800">{{ option.name }}</p><p class="text-xs text-slate-500">{{ option.branch || 'No branch assigned' }}</p></div></template></MultiSelect></div>
          <div v-if="selectedDriver" class="md:col-span-2 rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4">
            <div class="mb-3 flex items-center justify-between"><h3 class="font-semibold text-slate-900">Driver Information</h3><Tag value="Active" severity="success" /></div>
            <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
              <div><span class="text-slate-500">Full Name</span><p class="font-medium text-slate-900">{{ selectedDriver.name || '-' }}</p></div>
              <!-- <div><span class="text-slate-500">Employee Number</span><p class="font-medium text-slate-900">{{ selectedDriver.employee_number || '-' }}</p></div> -->
              <div><span class="text-slate-500">Contact</span><p class="font-medium text-slate-900">{{ selectedDriver.contact || '-' }}</p></div>
              <div><span class="text-slate-500">Email</span><p class="font-medium text-slate-900">{{ selectedDriver.email || '-' }}</p></div>
              <div><span class="text-slate-500">Department</span><p class="font-medium text-slate-900">{{ selectedDriver.department || '-' }}</p></div>
              <div><span class="text-slate-500">Position</span><p class="font-medium text-slate-900">{{ selectedDriver.position || 'Driver' }}</p></div>
              <div><span class="text-slate-500">Branch</span><p class="font-medium text-slate-900">{{ selectedDriver.branch || 'All branches' }}</p></div>
            </div>
          </div>
          <div><label class="mb-1 block text-sm font-medium text-slate-700">Vehicle *</label><Select v-model="form.vehicle_id" :options="vehicles" optionLabel="label" optionValue="id" fluid filter placeholder="Select a registered store vehicle" :loading="loadingVehicles" /></div>
       
          <div><label class="mb-1 block text-sm font-medium text-slate-700">Expected Pickup Date *</label><DatePicker :minDate="new Date()" v-model="form.expected_pickup_date" showIcon fluid dateFormat="yy-mm-dd" /></div>
             <div v-if="selectedVehicle" class="md:col-span-2 rounded-2xl border border-blue-100 bg-blue-50/60 p-4">
            <div class="mb-3 flex items-center justify-between"><h3 class="font-semibold text-slate-900">Vehicle Details</h3><Tag :value="selectedVehicle.status || 'Active'" severity="success" /></div>
            <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
              <div><span class="text-slate-500">Vehicle Name</span><p class="font-medium text-slate-900">{{ selectedVehicle.vehicle_name || '-' }}</p></div>
              <div><span class="text-slate-500">Type</span><p class="font-medium capitalize text-slate-900">{{ selectedVehicle.vehicle_type || '-' }}</p></div>
              <div><span class="text-slate-500">Plate Number</span><p class="font-medium text-slate-900">{{ selectedVehicle.plate_number || '-' }}</p></div>
              <div><span class="text-slate-500">Brand / Model</span><p class="font-medium text-slate-900">{{ [selectedVehicle.brand, selectedVehicle.model].filter(Boolean).join(' ') || '-' }}</p></div>
              <div><span class="text-slate-500">Color</span><p class="font-medium text-slate-900">{{ selectedVehicle.color || '-' }}</p></div>
              <div><span class="text-slate-500">Capacity</span><p class="font-medium text-slate-900">{{ selectedVehicle.capacity_kg ? `${selectedVehicle.capacity_kg} kg` : '-' }}</p></div>
              <div><span class="text-slate-500">Max Orders / Trip</span><p class="font-medium text-slate-900">{{ selectedVehicle.max_orders_per_trip || '-' }}</p></div>
              <div><span class="text-slate-500">Branch</span><p class="font-medium text-slate-900">{{ selectedVehicle.branch?.name || selectedVehicle.branch?.branch_name || 'All branches' }}</p></div>
              <div class="sm:col-span-2 lg:col-span-3"><span class="text-slate-500">Vehicle Notes</span><p class="font-medium text-slate-900">{{ selectedVehicle.notes || 'No notes recorded.' }}</p></div>
            </div>
          </div>
      
          <div class="md:col-span-2"><label class="mb-1 block text-sm font-medium text-slate-700">Pickup Notes</label><Textarea v-model="form.notes" rows="3" fluid placeholder="Loading instructions or special handling notes" /></div>
          <div class="flex justify-end gap-2 md:col-span-2"><Button type="button" label="Cancel" text @click="goBack" /><Button type="submit" label="Assign Pickup" icon="pi pi-check" :loading="submitting" :disabled="!canSubmit" /></div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import axiosClient from '../../../../axios'
import procurementService from '../../../../services/procurement.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Select from 'primevue/select'
import MultiSelect from 'primevue/multiselect'
import DatePicker from 'primevue/datepicker'
import Textarea from 'primevue/textarea'
import Tag from 'primevue/tag'

const route = useRoute(); const router = useRouter(); const toast = useToast()
const po = ref<any>(null); const loading = ref(false); const submitting = ref(false)
const drivers = ref<any[]>([]); const assistants = ref<any[]>([]); const loadingDrivers = ref(false)
const vehicles = ref<any[]>([]); const loadingVehicles = ref(false)
const form = reactive({ driver_id: null as number | null, assistant_user_ids: [] as number[], vehicle_id: null as number | null, expected_pickup_date: null as Date | null, notes: '' })
const selectedDriver = computed(() => drivers.value.find((driver: any) => Number(driver.id) === Number(form.driver_id)))
const availableAssistants = computed(() => assistants.value.filter((employee: any) => Number(employee.user_id) !== Number(selectedDriver.value?.user_id)))
watch(() => form.driver_id, () => {
  form.assistant_user_ids = form.assistant_user_ids.filter((id) => Number(id) !== Number(selectedDriver.value?.user_id))
})
const selectedVehicle = computed(() => vehicles.value.find((vehicle: any) => Number(vehicle.id) === Number(form.vehicle_id)))
const canSubmit = computed(() => !!form.driver_id && !!form.vehicle_id && !!form.expected_pickup_date)
const formatCurrency = (value: any) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value) || 0)
const formatStatus = (status: string) => String(status || '').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
const goBack = () => router.push({ name: 'procurement.purchase-orders.detail', params: { id: route.params.id } })
const load = async () => {
  loading.value = true; loadingVehicles.value = true; loadingDrivers.value = true
  try {
    const res = await procurementService.getPurchaseOrder(Number(route.params.id)); const payload = res.data || res; po.value = payload?.data || payload
    const [vehicleRes, driverRes] = await Promise.all([axiosClient.get(`/api/procurement/purchase-orders/${route.params.id}/pickup-vehicles`), axiosClient.get(`/api/procurement/purchase-orders/${route.params.id}/pickup-drivers`)])
    vehicles.value = (vehicleRes?.data?.data || []).map((v: any) => ({ ...v, label: `${v.vehicle_name} - ${v.plate_number} - ${v.vehicle_type}` }))
    drivers.value = driverRes?.data?.data?.drivers || []
    assistants.value = driverRes?.data?.data?.assistants || []
  } catch { toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load purchase order, vehicles, or drivers.', life: 3000 }) }
  finally { loading.value = false; loadingVehicles.value = false; loadingDrivers.value = false }
}
const submit = async () => {
  if (!canSubmit.value || !po.value) return; submitting.value = true
  try {
    await axiosClient.post(`/api/procurement/purchase-orders/${po.value.id}/pickup`, { driver_id: form.driver_id, assistant_user_ids: form.assistant_user_ids, vehicle_id: form.vehicle_id, expected_pickup_date: form.expected_pickup_date?.toISOString().slice(0, 10), notes: form.notes })
    toast.add({ severity: 'success', summary: 'Assigned', detail: 'Supplier pickup assigned successfully.', life: 2500 }); goBack()
  } catch (error: any) { toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to assign pickup.', life: 3000 }) }
  finally { submitting.value = false }
}
onMounted(load)
</script>
