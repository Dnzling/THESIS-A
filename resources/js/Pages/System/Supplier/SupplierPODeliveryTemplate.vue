<template>
  <div class="space-y-6 p-6">
    <div class="flex items-start justify-between gap-4">
      <div class="flex items-start gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push(`/supplier-portal/pos/${poId}`)" />
        <div>
          <h1 class="text-2xl font-semibold text-slate-900">Delivery Form</h1>
          <p class="text-sm text-slate-500">Set your delivery details, distance, and charge.</p>
        </div>
      </div>
      <Tag v-if="po" :value="formatStatus(po.status)" :severity="statusSeverity(po.status)" />
    </div>
  
    <PortalStepper class="mb-4" :steps="steps" :current="1" />
  
    <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <Card class="lg:col-span-2 rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <div class="space-y-3">
            <Skeleton height="28px" class="rounded-lg" />
            <Skeleton height="120px" class="rounded-lg" />
            <Skeleton height="120px" class="rounded-lg" />
          </div>
        </template>
      </Card>
      <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <Skeleton height="220px" class="rounded-lg" />
        </template>
      </Card>
    </div>
  
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <Card class="lg:col-span-2 rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">Delivery Template</h2>
              <p class="text-sm text-slate-500">Tap to edit or create your preset.</p>
            </div>
            <Button label="Choose Preset" icon="pi pi-list" outlined @click="openTemplateModal" />
          </div>
  
          <button type="button"
            class="w-full rounded-xl border border-slate-200 bg-white p-4 text-left transition hover:border-slate-300 hover:shadow-sm"
            @click="openTemplateModal">
            <div class="text-xs uppercase tracking-wide text-slate-400">Preset</div>
            <div class="mt-2 space-y-1 text-sm text-slate-600">
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Driver</span>
                <span class="font-medium text-slate-900">{{ activeTemplate?.driver_name || 'Not set' }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Plate</span>
                <span>{{ activeTemplate?.plate_number || '-' }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Cost / KM</span>
                <span>PHP {{ formatMoney(activeTemplate?.cost_per_km || form.cost_per_km || 0) }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Status</span>
                <span class="text-slate-700">{{ templates.length ? 'Preset available' : 'No preset' }}</span>
              </div>
            </div>
          </button>
  
          <div v-if="!templates.length" class="mt-3 text-xs text-slate-500">
            No templates yet. Tap the preset to create one.
          </div>
  
          <div class="mt-4">
            <label class="text-sm font-medium text-slate-700">Expected Delivery Range</label>
            <DatePicker
              v-model="form.expected_delivery_date"
              selectionMode="range"
              :minDate="new Date()"
              fluid
              class="mt-2"
              dateFormat="yy-mm-dd"
            />
            <small class="text-xs text-slate-500">Select start and end dates.</small>
          </div>
        </template>
      </Card>
  
      <div class="space-y-6">
        <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
          <template #content>
            <h3 class="text-lg font-semibold text-slate-900">Location & Distance</h3>
            <p class="text-sm text-slate-500 mb-4">Distance is calculated automatically from your saved coordinates.</p>

            <div class="space-y-3 text-sm">
              <div v-if="missingCoordMessage" class="rounded-xl border border-amber-200 bg-amber-50 text-amber-800 p-3">
                {{ missingCoordMessage }}
              </div>
              <div v-else class="rounded-xl bg-slate-50 p-4 text-sm">
                <div class="flex items-center justify-between">
                  <span class="text-slate-500">Distance</span>
                  <span class="font-semibold text-slate-900">{{ distanceKmDisplay }}</span>
                </div>
                <div class="flex items-center justify-between mt-2">
                  <span class="text-slate-500">Delivery Charge</span>
                  <span class="font-semibold text-emerald-600">PHP {{ formatMoney(deliveryCharge) }}</span>
                </div>
              </div>
            </div>
          </template>
        </Card>
  
        <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
          <template #content>
            <h3 class="text-lg font-semibold text-slate-900">Destination</h3>
            <p class="text-sm text-slate-500">Branch delivery details.</p>
            <div v-if="po?.branch" class="mt-4 text-sm text-slate-600 space-y-1">
              <div class="font-medium text-slate-800">{{ po.branch?.name || 'Branch' }}</div>
              <div>{{ po.branch?.address }}</div>
              <div>{{ po.branch?.city }}</div>
            </div>
            <div v-else class="text-sm text-slate-500 mt-4">Branch details unavailable.</div>
          </template>
        </Card>
  
        <Button label="Continue to Shipment" icon="pi pi-arrow-right" class="w-full" :disabled="!canContinue"
          @click="continueToInvoice" />
      </div>
    </div>
  
    <Dialog v-model:visible="showTemplateListModal" modal header="Delivery Templates" style="width: 560px">
      <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-slate-500">Select a preset or edit details.</p>
        <Button label="Add Template" icon="pi pi-plus" @click="openCreateTemplate" />
      </div>
  
      <div v-if="!templates.length" class="rounded-xl border border-dashed border-slate-200 p-4 text-sm text-slate-600">
        No templates yet. Click "Add Template" to create one.
      </div>
  
      <div v-else class="space-y-3">
        <div v-for="template in templates" :key="template.id" class="rounded-xl border border-slate-200 p-4"
          :class="template.id === selectedTemplateId ? 'border-emerald-300 bg-emerald-50/40' : 'bg-white'">
          <div class="flex items-start justify-between gap-3">
            <div class="space-y-1 text-sm text-slate-600">
              <div class="font-semibold text-slate-900">{{ template.driver_name || 'Driver' }}</div>
              <div>Plate: {{ template.plate_number || '-' }}</div>
              <div>Cost/KM: PHP {{ formatMoney(template.cost_per_km || 0) }}</div>
              <div>Template: {{ template.truck_brand || 'Delivery' }}</div>
            </div>
            <div class="flex flex-col gap-2">
              <Button label="Use" outlined severity="info" @click="selectTemplate(template)" />
              <Button icon="pi pi-pencil" text @click="openEditTemplate(template)" />
              <Button icon="pi pi-trash" severity="danger" text @click="deleteTemplate(template)" />
            </div>
          </div>
        </div>
      </div>
    </Dialog>
  
    <Dialog v-model:visible="showTemplateModal" modal header="Delivery Template" style="width: 520px">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium text-slate-700">Truck Brand</label>
          <InputText v-model="form.truck_brand" class="w-full mt-2" placeholder="Isuzu, Hino, etc." />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Truck Type</label>
          <InputText v-model="form.truck_type" class="w-full mt-2" placeholder="Closed van, flatbed..." />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Number of Wheels</label>
          <Select v-model="form.wheel_count" :options="wheelOptions" optionLabel="label" optionValue="value"
            class="w-full mt-2" placeholder="Select wheels" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Plate Number</label>
          <InputText v-model="form.plate_number" class="w-full mt-2" placeholder="ABC-1234" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Driver Name</label>
          <InputText v-model="form.driver_name" class="w-full mt-2" placeholder="Full name" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Driver Contact</label>
          <InputText v-model="form.driver_contact" class="w-full mt-2" placeholder="+63..." />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Cost per KM</label>
          <InputNumber v-model="form.cost_per_km" class="w-full mt-2" mode="currency" currency="PHP" :min="0" />
        </div>
      </div>
  
      <template #footer>
        <Button label="Cancel" outlined @click="showTemplateModal = false" />
        <Button label="Save Template" icon="pi pi-save" :loading="savingTemplate" @click="saveTemplate" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Dialog from 'primevue/dialog'
import PortalStepper from '@/Components/system/supplier/PortalStepper.vue'
import Skeleton from 'primevue/skeleton'
import supplierService, { SupplierDeliveryTemplate } from '../../../services/supplier.service'
import { watch } from 'vue'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const poId = Number(route.params.id)
const po = ref<any>(null)
const portal = ref<any>(null)
const templates = ref<SupplierDeliveryTemplate[]>([])
const selectedTemplateId = ref<number | null>(null)
const savingTemplate = ref(false)
const distanceLoading = ref(false)
const distanceKm = ref<number | null>(null)
const loading = ref(false)
const showTemplateListModal = ref(false)
const showTemplateModal = ref(false)
const editingTemplateId = ref<number | null>(null)

const steps = [
  { label: 'Review PO', description: 'Approve or reject' },
  { label: 'Delivery Form', description: 'Set delivery details' },
  { label: 'Shipment', description: 'Confirm shipment' },
]

const wheelOptions = [
  { label: '4 wheels', value: 4 },
  { label: '6 wheels', value: 6 },
  { label: '8 wheels', value: 8 },
  { label: '10 wheels', value: 10 },
  { label: '12 wheels', value: 12 },
  { label: '18 wheels', value: 18 },
]

const form = ref({
  truck_brand: '',
  truck_type: '',
  wheel_count: null as number | null,
  plate_number: '',
  driver_name: '',
  driver_contact: '',
  cost_per_km: 0,
  current_latitude: '',
  current_longitude: '',
  expected_delivery_date: null as Date[] | null,
})

const activeTemplate = computed(() => {
  if (!templates.value.length) return null
  const byId = templates.value.find(template => template.id === selectedTemplateId.value)
  return byId || templates.value[0]
})

const branchCoords = computed(() => {
  const lat = po.value?.branch?.latitude
  const lng = po.value?.branch?.longitude
  return lat && lng ? { lat: Number(lat), lng: Number(lng) } : null
})

const hasSupplierCoords = computed(() =>
  Boolean(form.value.current_latitude && form.value.current_longitude)
)
const canCalculateDistance = computed(() => {
  return Boolean(branchCoords.value && hasSupplierCoords.value)
})

const deliveryCharge = computed(() => {
  const km = distanceKm.value || 0
  const rate = Number(form.value.cost_per_km || 0)
  return km * rate
})

const distanceKmDisplay = computed(() => {
  return distanceKm.value ? `${distanceKm.value.toFixed(2)} km` : '-'
})

const canContinue = computed(() => {
  return Boolean(
    form.value.driver_name &&
    form.value.cost_per_km &&
    distanceKm.value &&
    form.value.expected_delivery_date &&
    form.value.expected_delivery_date.length === 2
  )
})

const formatMoney = (value: number) => new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2 }).format(value || 0)

const missingCoordMessage = computed(() => {
  const missing: string[] = []
  if (!hasSupplierCoords.value) missing.push('supplier coordinates in your profile')
  if (!branchCoords.value) missing.push('store/branch coordinates')
  if (!missing.length) return ''
  return `Missing ${missing.join(' and ')}. Please set them to calculate distance.`
})

const statusSeverity = (status: string) => {
  if (status === 'sent_to_supplier') return 'info'
  if (status === 'supplier_accepted') return 'success'
  if (status === 'declined_supplier') return 'danger'
  return 'secondary'
}

const formatStatus = (status: string) => status?.split('_').map(word => word[0]?.toUpperCase() + word.slice(1)).join(' ') || '-'

const loadPO = async () => {
  const res = await supplierService.getSupplierPODetail(poId)
  const payload = res.data || res
  po.value = payload?.data?.po || payload?.po || null
}

const loadPortal = async () => {
  try {
    const res = await supplierService.getMyPortal()
    const payload = res.data || res
    portal.value = payload?.data || payload || null
    const lat =
      portal.value?.latitude ||
      portal.value?.supplier_latitude ||
      portal.value?.supplier?.latitude ||
      portal.value?.supplier?.lat
    const lng =
      portal.value?.longitude ||
      portal.value?.supplier_longitude ||
      portal.value?.supplier?.longitude ||
      portal.value?.supplier?.lng
    if (lat && lng) {
      form.value.current_latitude = String(lat)
      form.value.current_longitude = String(lng)
    }
  } catch (error) {
    console.warn('Failed to load portal coordinates', error)
  }
}

const loadTemplates = async () => {
  const res = await supplierService.getDeliveryTemplates()
  const payload = res.data || res
  templates.value = payload?.data || payload || []
  if (templates.value.length && !selectedTemplateId.value) {
    selectedTemplateId.value = templates.value[0].id
    applyTemplate()
  }
}

const applyTemplate = () => {
  const template = activeTemplate.value
  if (!template) return
  form.value.truck_brand = template.truck_brand || ''
  form.value.truck_type = template.truck_type || ''
  form.value.wheel_count = template.wheel_count ?? null
  form.value.plate_number = template.plate_number || ''
  form.value.driver_name = template.driver_name || ''
  form.value.driver_contact = template.driver_contact || ''
  form.value.cost_per_km = Number(template.cost_per_km || 0)
  toast.add({ severity: 'info', summary: 'Template Loaded', detail: 'Template details applied.', life: 2000 })
}

const openTemplateModal = () => {
  showTemplateListModal.value = true
}

const openCreateTemplate = () => {
  editingTemplateId.value = null
  resetForm()
  showTemplateListModal.value = false
  showTemplateModal.value = true
}

const openEditTemplate = (template: SupplierDeliveryTemplate) => {
  selectedTemplateId.value = template.id
  applyTemplate()
  editingTemplateId.value = template.id
  showTemplateListModal.value = false
  showTemplateModal.value = true
}

const selectTemplate = (template: SupplierDeliveryTemplate) => {
  selectedTemplateId.value = template.id
  applyTemplate()
  showTemplateListModal.value = false
}

const resetForm = () => {
  form.value.truck_brand = ''
  form.value.truck_type = ''
  form.value.wheel_count = null
  form.value.plate_number = ''
  form.value.driver_name = ''
  form.value.driver_contact = ''
  form.value.cost_per_km = 0
}

const deleteTemplate = async (template: SupplierDeliveryTemplate) => {
  if (!template?.id) return
  if (!confirm(`Delete ${template.driver_name || 'this'} template?`)) return
  try {
    await supplierService.deleteDeliveryTemplate(template.id)
    templates.value = templates.value.filter(item => item.id !== template.id)
    if (selectedTemplateId.value === template.id) {
      selectedTemplateId.value = templates.value[0]?.id || null
      if (selectedTemplateId.value) applyTemplate()
    }
    toast.add({ severity: 'success', summary: 'Deleted', detail: 'Template removed.', life: 2000 })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to delete template', life: 3000 })
  }
}

const saveTemplate = async () => {
  if (!form.value.driver_name) {
    toast.add({ severity: 'warn', summary: 'Missing Driver', detail: 'Driver name is required.', life: 2500 })
    return
  }
  savingTemplate.value = true
  try {
    const payloadData = {
      truck_brand: form.value.truck_brand || undefined,
      truck_type: form.value.truck_type || undefined,
      wheel_count: form.value.wheel_count ?? undefined,
      plate_number: form.value.plate_number || undefined,
      driver_name: form.value.driver_name || undefined,
      driver_contact: form.value.driver_contact || undefined,
      cost_per_km: Number(form.value.cost_per_km || 0),
    }
    const hasTemplate = Boolean(editingTemplateId.value)
    const res = hasTemplate
      ? await supplierService.updateDeliveryTemplate(editingTemplateId.value!, payloadData)
      : await supplierService.createDeliveryTemplate(payloadData)
    const payload = res.data || res
    if (payload?.data) {
      if (hasTemplate) {
        templates.value = templates.value.map(item => item.id === payload.data.id ? payload.data : item)
      } else {
        templates.value = [payload.data, ...templates.value]
      }
      selectedTemplateId.value = payload.data.id
      applyTemplate()
      await loadTemplates()
    }
    showTemplateModal.value = false
    toast.add({ severity: 'success', summary: 'Template Saved', detail: 'Template ready to use.', life: 2000 })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to save template', life: 3000 })
  } finally {
    savingTemplate.value = false
  }
}

const calculateDistance = async () => {
  if (!canCalculateDistance.value || !branchCoords.value) {
    distanceKm.value = 0
    return
  }
  distanceLoading.value = true
  try {
    const originLat = Number(form.value.current_latitude)
    const originLng = Number(form.value.current_longitude)
    const destLat = branchCoords.value.lat
    const destLng = branchCoords.value.lng

    const url = `https://router.project-osrm.org/route/v1/driving/${originLng},${originLat};${destLng},${destLat}?overview=false`
    const response = await fetch(url)
    const data = await response.json()
    const meters = data?.routes?.[0]?.distance
    if (meters) {
      distanceKm.value = Number((meters / 1000).toFixed(2))
      toast.add({ severity: 'success', summary: 'Distance Updated', detail: `${distanceKm.value} km`, life: 2000 })
    } else {
      throw new Error('No route data returned')
    }
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Distance Error', detail: 'Failed to calculate distance.', life: 2500 })
  } finally {
    distanceLoading.value = false
  }
}

const continueToInvoice = () => {
  if (!form.value.expected_delivery_date || form.value.expected_delivery_date.length !== 2) {
    toast.add({ severity: 'warn', summary: 'Delivery Range Required', detail: 'Select a start and end date.', life: 2500 })
    return
  }
  if (!canCalculateDistance.value) {
    toast.add({ severity: 'warn', summary: 'Coordinates Missing', detail: 'Add supplier and branch coordinates first.', life: 2500 })
    return
  }
  const draft = {
    po_id: poId,
    truck_brand: form.value.truck_brand,
    truck_type: form.value.truck_type,
    wheel_count: form.value.wheel_count,
    plate_number: form.value.plate_number,
    driver_name: form.value.driver_name,
    driver_contact: form.value.driver_contact,
    cost_per_km: Number(form.value.cost_per_km || 0),
    current_latitude: form.value.current_latitude ? Number(form.value.current_latitude) : null,
    current_longitude: form.value.current_longitude ? Number(form.value.current_longitude) : null,
    distance_km: distanceKm.value,
    delivery_charge: deliveryCharge.value,
    expected_delivery_date: form.value.expected_delivery_date?.[0] || null,
    expected_delivery_range: form.value.expected_delivery_date || null,
  }
  localStorage.setItem(`supplier_delivery_draft_${poId}`, JSON.stringify(draft))
  router.push(`/supplier-portal/pos/${poId}/invoice`)
}

onMounted(async () => {
  loading.value = true
  try {
    await Promise.all([loadPO(), loadTemplates(), loadPortal()])
    if (canCalculateDistance.value) {
      await calculateDistance()
    } else {
      distanceKm.value = 0
    }
  } finally {
    loading.value = false
  }
})

watch(
  () => [branchCoords.value, form.value.current_latitude, form.value.current_longitude],
  async () => {
    if (canCalculateDistance.value) {
      await calculateDistance()
    } else {
      distanceKm.value = 0
    }
  }
)
</script>
