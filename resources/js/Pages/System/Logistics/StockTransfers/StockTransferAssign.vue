<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded aria-label="Back" @click="goBack" />
        <div>
          <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Assign Transfer Delivery</h1>
          <p class="mt-1 text-sm text-slate-500">Assign a driver and vehicle to move this transfer to its destination branch.</p>
        </div>
      </div>
    </div>

    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Transfer Summary</template>
      <template #content>
        <div v-if="loading" class="space-y-3">
          <Skeleton height="1.25rem" width="45%" />
          <Skeleton height="1.25rem" width="70%" />
          <Skeleton height="5rem" />
        </div>
        <Message v-else-if="loadError" severity="error" :closable="false">{{ loadError }}</Message>
        <div v-else-if="transfer" class="space-y-4">
          <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div>
              <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Transfer</p>
              <p class="mt-1 font-semibold text-slate-900">{{ transfer.transfer_number || `#${transfer.id}` }}</p>
            </div>
            <div>
              <p class="text-xs font-medium uppercase tracking-wide text-slate-500">From</p>
              <p class="mt-1 font-medium text-slate-900">{{ branchName(transfer.from_branch || transfer.fromBranch) }}</p>
            </div>
            <div>
              <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Destination</p>
              <p class="mt-1 font-medium text-slate-900">{{ branchName(transfer.to_branch || transfer.toBranch) }}</p>
            </div>
            <div>
              <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Status</p>
              <Tag class="mt-1" :value="formatStatus(transfer.status)" :severity="statusSeverity(transfer.status)" />
            </div>
          </div>

          <DataTable v-if="transfer.items?.length" :value="transfer.items" size="small" rowHover>
            <Column header="Item">
              <template #body="{ data }">
                <div class="font-medium text-slate-900">{{ data.product?.product_name || data.product_name || data.item_name || 'Item' }}</div>
                <div class="text-xs text-slate-500">{{ data.variant?.sku || data.product?.sku || data.sku || '' }}</div>
              </template>
            </Column>
            <Column header="Quantity">
              <template #body="{ data }">{{ data.approved_quantity ?? data.requested_quantity ?? data.quantity ?? 0 }}</template>
            </Column>
          </DataTable>
        </div>
      </template>
    </Card>

    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Delivery Assignment</template>
      <template #content>
        <form class="grid grid-cols-1 gap-5 md:grid-cols-2" @submit.prevent="submitAssignment">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Driver <span class="text-orange-600">*</span></label>
            <Select v-model="form.driver_user_id" :options="drivers" optionLabel="name" optionValue="id" fluid filter placeholder="Select driver">
              <template #option="{ option }">
                <div>
                  <p class="text-sm font-medium text-slate-800">{{ option.name }}</p>
                  <p class="text-xs text-slate-500">{{ option.branch || 'Branch not specified' }}</p>
                </div>
              </template>
            </Select>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Vehicle <span class="text-orange-600">*</span></label>
            <Select v-model="form.vehicle_id" :options="vehicles" optionLabel="label" optionValue="id" fluid filter placeholder="Select vehicle" />
          </div>

          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700">Driver Contact Number <span class="text-orange-600">*</span></label>
            <InputText v-model="form.courier_contact" fluid placeholder="Contact number for delivery coordination" />
          </div>

          <div v-if="selectedDriver" class="rounded-2xl border border-orange-100 bg-orange-50/50 p-4 md:col-span-2">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Driver Information</h2>
            <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
              <div><p class="text-xs text-slate-500">Name</p><p class="font-medium text-slate-900">{{ selectedDriver.name }}</p></div>
              <div><p class="text-xs text-slate-500">Employee Contact</p><p class="font-medium text-slate-900">{{ selectedDriverContact || 'Not provided' }}</p></div>
              <div><p class="text-xs text-slate-500">Branch</p><p class="font-medium text-slate-900">{{ selectedDriver.branch || 'Not specified' }}</p></div>
            </div>
          </div>

          <div v-if="selectedVehicle" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 md:col-span-2">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Vehicle Information</h2>
            <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
              <div><p class="text-xs text-slate-500">Vehicle</p><p class="font-medium text-slate-900">{{ selectedVehicle.vehicle_name || '-' }}</p></div>
              <div><p class="text-xs text-slate-500">Type</p><p class="font-medium capitalize text-slate-900">{{ selectedVehicle.vehicle_type || '-' }}</p></div>
              <div><p class="text-xs text-slate-500">Plate Number</p><p class="font-medium text-slate-900">{{ selectedVehicle.plate_number || '-' }}</p></div>
            </div>
          </div>

          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700">Assignment Notes</label>
            <Textarea v-model="form.notes" rows="3" fluid placeholder="Optional instructions for the driver" />
          </div>

          <Message class="md:col-span-2" severity="info" :closable="false">
            Stock transfer deliveries do not incur a delivery fee. The contact number is prefilled from the selected employee profile and can be corrected if needed.
          </Message>

          <div class="flex flex-wrap gap-2 md:col-span-2">
            <Button type="button" label="Cancel" severity="secondary" outlined @click="goBack" />
            <Button type="submit" label="Assign Delivery" icon="pi pi-check" severity="warn" :loading="submitting" :disabled="!canSubmit" />
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
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'
import inventoryService from '../../../../services/inventory.service'
import logisticsService from '../../../../services/logistics.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const transferId = computed(() => Number(route.params.id || 0))
const transfer = ref<any>(null)
const loading = ref(true)
const submitting = ref(false)
const loadError = ref('')
const drivers = ref<any[]>([])
const vehicles = ref<any[]>([])
const form = reactive({ driver_user_id: null as number | null, vehicle_id: null as number | null, courier_contact: '', notes: '' })

const selectedDriver = computed(() => drivers.value.find((driver) => Number(driver.id) === Number(form.driver_user_id)))
const selectedVehicle = computed(() => vehicles.value.find((vehicle) => Number(vehicle.id) === Number(form.vehicle_id)))
const selectedDriverContact = computed(() => {
  const driver = selectedDriver.value
  return driver?.contact_number || driver?.contact || driver?.phone || driver?.mobile || ''
})
const canSubmit = computed(() => !!transfer.value && !!form.driver_user_id && !!form.vehicle_id && !!form.courier_contact.trim() && !submitting.value)

const branchName = (branch: any) => branch?.name || '-'
const formatStatus = (status: string) => String(status || 'Unknown').replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
const statusSeverity = (status: string) => {
  const value = String(status || '').toLowerCase()
  if (value === 'received') return 'success'
  if (['in_transit', 'out_for_delivery'].includes(value)) return 'info'
  if (['cancelled', 'rejected'].includes(value)) return 'danger'
  return 'secondary'
}

const loadPage = async () => {
  loading.value = true
  loadError.value = ''
  try {
    const [transferResponse, employeeResponse, vehicleResponse] = await Promise.all([
      inventoryService.getTransfer(transferId.value),
      logisticsService.getLogisticsEmployees(),
      logisticsService.getVehicles({ per_page: 100, status: 'active' }),
    ])
    transfer.value = transferResponse?.data || null
    const employeeData = employeeResponse?.data || []
    drivers.value = Array.isArray(employeeData) ? employeeData : (employeeData.drivers || [])
    const vehicleData = vehicleResponse?.data?.data || []
    vehicles.value = vehicleData.map((vehicle: any) => ({
      ...vehicle,
      label: `${vehicle.vehicle_name || 'Vehicle'}${vehicle.plate_number ? ` (${vehicle.plate_number})` : ''}`,
    }))
    if (!transfer.value) loadError.value = 'Transfer not found.'
  } catch (error: any) {
    loadError.value = error?.response?.data?.message || 'Unable to load the transfer and assignment options.'
  } finally {
    loading.value = false
  }
}

const goBack = () => router.push({ name: 'logistics.stock-transfers.detail', params: { id: transferId.value } })

watch(() => form.driver_user_id, () => {
  if (selectedDriverContact.value) form.courier_contact = selectedDriverContact.value
})

const submitAssignment = async () => {
  if (!canSubmit.value || !selectedDriver.value || !selectedVehicle.value) return
  submitting.value = true
  try {
    await inventoryService.createTransferDelivery(transferId.value, {
      driver_user_id: Number(selectedDriver.value.id),
      vehicle_type: selectedVehicle.value.vehicle_name || selectedVehicle.value.label || 'Assigned Vehicle',
      driver_name: String(selectedDriver.value.name || selectedDriver.value.full_name || 'Assigned Driver').trim(),
      driver_contact: form.courier_contact.trim(),
      notes: form.notes.trim() || undefined,
    })
    toast.add({ severity: 'success', summary: 'Delivery Assigned', detail: 'The transfer delivery has been assigned successfully.', life: 2500 })
    await router.push({ name: 'logistics.stock-transfers.detail', params: { id: transferId.value } })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Assignment Failed', detail: error?.response?.data?.message || 'Could not assign this transfer delivery.', life: 3500 })
  } finally {
    submitting.value = false
  }
}

onMounted(loadPage)
</script>
