<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <div class="flex items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900">Delivery Zones</h1>
            <p class="text-sm text-gray-500">Service areas and distance + weight pricing rules per branch.</p>
          </div>
          <div class="flex gap-2">
            <Button severity="info" outlined icon="pi pi-refresh" label="Refresh" @click="loadZones" />
            <Button v-if="canManageZones" severity="info" icon="pi pi-plus" label="Add Zone" @click="openCreate" />
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
              <InputText v-model="filters.search" fluid placeholder="Search zone..." />
            </IconField>
          </div>
          <div class="md:col-span-4">
            <Select v-model="filters.is_active" :options="activeOptions" optionLabel="label" optionValue="value" showClear fluid placeholder="Status" />
          </div>
        </div>
      </template>
    </Card>

    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <DataTable :value="zones" :loading="loading" dataKey="id" stripedRows>
          <Column field="name" header="Zone" />
          <Column header="Active">
            <template #body="{ data }">
              <Tag :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'secondary'" />
            </template>
          </Column>
          <Column header="Rates">
            <template #body="{ data }">
              <span class="text-sm text-gray-700">{{ Array.isArray(data.rates) ? data.rates.length : 0 }} rule(s)</span>
            </template>
          </Column>
          <Column header="Actions">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button text severity="info" icon="pi pi-eye" @click="openRates(data)" />
                <Button v-if="canManageZones" text severity="info" icon="pi pi-pencil" @click="openEdit(data)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="zoneDialog" modal :header="editingZoneId ? 'Edit Zone' : 'Create Zone'" class="w-full max-w-2xl">
      <div class="space-y-4">
        <div>
          <label class="text-sm text-gray-600">Zone Name</label>
          <InputText v-model="zoneForm.name" fluid placeholder="e.g. Cavite North" />
        </div>
        <div>
          <label class="text-sm text-gray-600">Service Areas</label>
          <Textarea v-model="zoneForm.service_areas" rows="4" fluid placeholder="Cities/barangays/notes..." />
        </div>
        <div class="flex items-center gap-2">
          <Checkbox v-model="zoneForm.is_active" :binary="true" />
          <span class="text-sm text-gray-700">Active</span>
        </div>
      </div>
      <template #footer>
        <Button text severity="secondary" label="Cancel" @click="zoneDialog = false" />
        <Button :loading="savingZone" severity="info" :label="editingZoneId ? 'Update' : 'Create'" @click="saveZone" />
      </template>
    </Dialog>

    <Dialog v-model:visible="ratesDialog" modal header="Zone Rates" class="w-full max-w-5xl">
      <div class="flex items-center justify-between mb-3">
        <div>
          <p class="text-sm text-gray-500">Zone</p>
          <p class="text-lg font-semibold text-gray-900">{{ selectedZone?.name || '-' }}</p>
        </div>
        <Button v-if="canManageZones" severity="info" icon="pi pi-plus" label="Add Rate" @click="openAddRate" />
      </div>

      <DataTable :value="zoneRates" :loading="ratesLoading" dataKey="id" stripedRows>
        <Column header="Distance (km)">
          <template #body="{ data }">
            <span class="text-sm">{{ data.min_distance_km }} - {{ data.max_distance_km ?? '∞' }}</span>
          </template>
        </Column>
        <Column header="Weight (kg)">
          <template #body="{ data }">
            <span class="text-sm">{{ data.min_weight_kg }} - {{ data.max_weight_kg ?? '∞' }}</span>
          </template>
        </Column>
        <Column header="Fees">
          <template #body="{ data }">
            <div class="text-sm text-gray-700">
              Base: ₱{{ Number(data.base_fee || 0).toFixed(2) }},
              /km: ₱{{ Number(data.per_km_fee || 0).toFixed(2) }},
              /kg: ₱{{ Number(data.per_kg_fee || 0).toFixed(2) }}
            </div>
          </template>
        </Column>
        <Column header="Active">
          <template #body="{ data }">
            <Tag :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'secondary'" />
          </template>
        </Column>
        <Column header="Actions">
          <template #body="{ data }">
            <div class="flex gap-2">
              <Button v-if="canManageZones" text severity="info" icon="pi pi-pencil" @click="editRate(data)" />
              <Button v-if="canManageZones" text severity="danger" icon="pi pi-trash" @click="deleteRate(data)" />
            </div>
          </template>
        </Column>
      </DataTable>

      <template #footer>
        <Button text severity="secondary" label="Close" @click="ratesDialog = false" />
      </template>
    </Dialog>

    <Dialog v-model:visible="rateDialog" modal :header="editingRateId ? 'Edit Rate' : 'Add Rate'" class="w-full max-w-4xl">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="text-sm text-gray-600">Min Distance (km)</label>
          <InputNumber v-model="rateForm.min_distance_km" fluid :min="0" :minFractionDigits="0" :maxFractionDigits="2" />
        </div>
        <div>
          <label class="text-sm text-gray-600">Max Distance (km)</label>
          <InputNumber v-model="rateForm.max_distance_km" fluid :min="0" :minFractionDigits="0" :maxFractionDigits="2" />
        </div>
        <div>
          <label class="text-sm text-gray-600">Min Weight (kg)</label>
          <InputNumber v-model="rateForm.min_weight_kg" fluid :min="0" :minFractionDigits="0" :maxFractionDigits="2" />
        </div>
        <div>
          <label class="text-sm text-gray-600">Max Weight (kg)</label>
          <InputNumber v-model="rateForm.max_weight_kg" fluid :min="0" :minFractionDigits="0" :maxFractionDigits="2" />
        </div>
        <div>
          <label class="text-sm text-gray-600">Base Fee</label>
          <InputNumber v-model="rateForm.base_fee" fluid mode="currency" currency="PHP" :min="0" />
        </div>
        <div>
          <label class="text-sm text-gray-600">Per KM Fee</label>
          <InputNumber v-model="rateForm.per_km_fee" fluid mode="currency" currency="PHP" :min="0" />
        </div>
        <div>
          <label class="text-sm text-gray-600">Per KG Fee</label>
          <InputNumber v-model="rateForm.per_kg_fee" fluid mode="currency" currency="PHP" :min="0" />
        </div>
        <div class="flex items-center gap-2 mt-6">
          <Checkbox v-model="rateForm.is_active" :binary="true" />
          <span class="text-sm text-gray-700">Active</span>
        </div>
      </div>
      <template #footer>
        <Button text severity="secondary" label="Cancel" @click="rateDialog = false" />
        <Button :loading="savingRate" severity="info" :label="editingRateId ? 'Update' : 'Add'" @click="saveRate" />
      </template>
    </Dialog>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
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
import Textarea from 'primevue/textarea'
import Checkbox from 'primevue/checkbox'
import InputNumber from 'primevue/inputnumber'
import Toast from 'primevue/toast'
import logisticsService from '@/services/logistics.service'
import { useAuthStore } from '@/stores/auth'

const toast = useToast()
const authStore = useAuthStore()

const canManageZones = authStore.hasPermission('logistics.zones.manage')

const loading = ref(false)
const savingZone = ref(false)
const zones = ref<any[]>([])

const filters = reactive({
  search: '',
  is_active: null as boolean | null,
})

const activeOptions = [
  { label: 'Active', value: true },
  { label: 'Inactive', value: false },
]

const zoneDialog = ref(false)
const editingZoneId = ref<number | null>(null)
const zoneForm = reactive<any>({
  name: '',
  service_areas: '',
  is_active: true,
})

const ratesDialog = ref(false)
const rateDialog = ref(false)
const ratesLoading = ref(false)
const savingRate = ref(false)
const selectedZone = ref<any>(null)
const zoneRates = ref<any[]>([])
const editingRateId = ref<number | null>(null)
const rateForm = reactive<any>({
  min_distance_km: 0,
  max_distance_km: null,
  min_weight_kg: 0,
  max_weight_kg: null,
  base_fee: 0,
  per_km_fee: 0,
  per_kg_fee: 0,
  is_active: true,
})

const resetZoneForm = () => {
  editingZoneId.value = null
  zoneForm.name = ''
  zoneForm.service_areas = ''
  zoneForm.is_active = true
}

const resetRateForm = () => {
  editingRateId.value = null
  rateForm.min_distance_km = 0
  rateForm.max_distance_km = null
  rateForm.min_weight_kg = 0
  rateForm.max_weight_kg = null
  rateForm.base_fee = 0
  rateForm.per_km_fee = 0
  rateForm.per_kg_fee = 0
  rateForm.is_active = true
}

const loadZones = async () => {
  loading.value = true
  try {
    const res = await logisticsService.getZones({
      search: filters.search || undefined,
      is_active: filters.is_active === null ? undefined : filters.is_active,
      per_page: 50,
    })
    zones.value = res?.data?.data || []
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load zones', life: 3000 })
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  resetZoneForm()
  zoneDialog.value = true
}

const openEdit = (zone: any) => {
  editingZoneId.value = Number(zone.id)
  zoneForm.name = zone.name || ''
  zoneForm.service_areas = zone.service_areas || ''
  zoneForm.is_active = !!zone.is_active
  zoneDialog.value = true
}

const saveZone = async () => {
  savingZone.value = true
  try {
    if (editingZoneId.value) {
      await logisticsService.updateZone(editingZoneId.value, { ...zoneForm })
      toast.add({ severity: 'success', summary: 'Updated', detail: 'Zone updated.', life: 2000 })
    } else {
      await logisticsService.createZone({ ...zoneForm })
      toast.add({ severity: 'success', summary: 'Created', detail: 'Zone created.', life: 2000 })
    }
    zoneDialog.value = false
    await loadZones()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to save zone', life: 3000 })
  } finally {
    savingZone.value = false
  }
}

const openRates = async (zone: any) => {
  selectedZone.value = zone
  ratesDialog.value = true
  await loadRates()
}

const loadRates = async () => {
  if (!selectedZone.value?.id) return
  ratesLoading.value = true
  try {
    const res = await logisticsService.getZoneRates(Number(selectedZone.value.id))
    zoneRates.value = res?.data || []
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load rates', life: 3000 })
  } finally {
    ratesLoading.value = false
  }
}

const openAddRate = () => {
  resetRateForm()
  rateDialog.value = true
}

const editRate = (rate: any) => {
  editingRateId.value = Number(rate.id)
  rateForm.min_distance_km = Number(rate.min_distance_km || 0)
  rateForm.max_distance_km = rate.max_distance_km === null ? null : Number(rate.max_distance_km || 0)
  rateForm.min_weight_kg = Number(rate.min_weight_kg || 0)
  rateForm.max_weight_kg = rate.max_weight_kg === null ? null : Number(rate.max_weight_kg || 0)
  rateForm.base_fee = Number(rate.base_fee || 0)
  rateForm.per_km_fee = Number(rate.per_km_fee || 0)
  rateForm.per_kg_fee = Number(rate.per_kg_fee || 0)
  rateForm.is_active = !!rate.is_active
  rateDialog.value = true
}

const saveRate = async () => {
  if (!selectedZone.value?.id) return
  savingRate.value = true
  try {
    const zoneId = Number(selectedZone.value.id)
    if (editingRateId.value) {
      await logisticsService.updateZoneRate(zoneId, editingRateId.value, { ...rateForm })
      toast.add({ severity: 'success', summary: 'Updated', detail: 'Rate updated.', life: 2000 })
    } else {
      await logisticsService.addZoneRate(zoneId, { ...rateForm })
      toast.add({ severity: 'success', summary: 'Added', detail: 'Rate added.', life: 2000 })
    }
    rateDialog.value = false
    await loadRates()
    await loadZones()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to save rate', life: 3000 })
  } finally {
    savingRate.value = false
  }
}

const deleteRate = async (rate: any) => {
  if (!selectedZone.value?.id) return
  try {
    await logisticsService.deleteZoneRate(Number(selectedZone.value.id), Number(rate.id))
    toast.add({ severity: 'success', summary: 'Deleted', detail: 'Rate deleted.', life: 2000 })
    await loadRates()
    await loadZones()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to delete rate', life: 3000 })
  }
}

watch(() => [filters.search, filters.is_active], () => loadZones())

onMounted(loadZones)
</script>

