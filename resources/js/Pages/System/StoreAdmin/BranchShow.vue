<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">{{ branch?.name || 'Branch Detail' }}</h1>
        <p class="text-sm text-slate-600">{{ branch?.branch_code || branch?.code || '' }}</p>
      </div>
      <Button label="Edit Branch" icon="pi pi-pencil" @click="openEditDialog" />
    </div>
  
    <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-slate-700">
          <div>
            <div class="text-xs text-slate-500 uppercase">Address</div>
            <div class="font-medium text-slate-900">{{ branch?.address || '—' }}</div>
            <div class="text-slate-500">{{ [branch?.barangay, branch?.city, branch?.province].filter(Boolean).join(', ') }}</div>
          </div>
        
          <div>
            <div class="text-xs text-slate-500 uppercase">Geofence Radius</div>
            <Tag :value="branch?.geofence_radius_m ? branch.geofence_radius_m + ' m' : 'Not set'" severity="info" />
          </div>
        </div>
      </template>
    </Card>
  
    <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
      <template #header>
        <div class="px-4 py-3 flex items-center justify-between">
          <div>
            <div class="text-xs uppercase text-slate-500 font-semibold">Employees</div>
            <div class="text-sm text-slate-600">Assigned to this branch</div>
          </div>
        </div>
      </template>
      <template #content>
        <div class="px-4 pb-4 space-y-3">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <InputText v-model="empSearch" size="small" placeholder="Search name or email" @input="applyEmpFilters" />
            <Select v-model="empRoleFilter" :options="roleOptions" optionLabel="label" optionValue="value"
              placeholder="All roles" class="w-full" size="small" @change="applyEmpFilters" />
            <Button size="small" text icon="pi pi-refresh" label="Reset" @click="resetEmpFilters" />
          </div>
          <DataTable :value="filteredEmployees" size="small" stripedRows :paginator="true" :rows="8" dataKey="id"
            class="p-datatable-sm">
            <Column header="Employee Number" style="width:120px">
              <template #body="{ data }">
                <div class="font-semibold text-sm"> {{ data.employee_number }}</div>
              </template>
            </Column>
            <Column header="Name" style="width:120px" >
              <template #body="{ data }">
                <div class="font-semibold text-slate-900">{{ fullname(data) }}</div>
                <div class="text-xs text-slate-500">{{ data.email }}</div>
              </template>
            </Column>
            <Column field="role_name" header="Role" style="width:140px">
              <template #body="{ data }">
                <Tag :value="data.role_name || data.role || '—'" severity="info" />
              </template>
            </Column>
            <Column field="status" header="Status" style="width:120px">
              <template #body="{ data }">
                <Tag :value="(data.status || 'active').toUpperCase()"
                  :severity="(data.status || 'active') === 'active' ? 'success' : 'secondary'" />
              </template>
            </Column>
          </DataTable>
        </div>
      </template>
    </Card>
  
    <Dialog v-model:visible="showEdit" modal header="Edit Branch" class="w-full max-w-4xl">
      <div class="space-y-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-semibold text-slate-600">Branch Name *</label>
            <InputText v-model="form.name" placeholder="Branch name" class="w-full" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-semibold text-slate-600">Branch Type</label>
            <Select v-model="form.branch_type" :options="branchTypeOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
        </div>


        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-semibold text-slate-600">Province</label>
            <Select v-model="form.provinceId" :options="provinceOptions" optionLabel="label" optionValue="value" class="w-full" placeholder="Select province" :loading="isProvincesLoading" @change="onProvinceChange" disable />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-semibold text-slate-600">City *</label>
            <Select v-model="form.cityId" :options="cityOptions" optionLabel="label" optionValue="value" class="w-full" placeholder="Select city" :loading="isCitiesLoading" :disabled="!form.provinceId" @change="onCityChange" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-semibold text-slate-600">Barangay</label>
            <Select v-model="form.barangay" :options="barangayOptions" optionLabel="label" optionValue="value" class="w-full" placeholder="Select barangay" :loading="isBarangaysLoading" :disabled="!form.cityId" />
          </div>
        </div>

        
        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-600">Address *</label>
          <InputText v-model="form.address" placeholder="Street, building, area" class="w-full" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-4">
            <div class="space-y-2">
              <label class="text-xs font-semibold text-slate-600">Search Location</label>
              <div class="flex gap-2">
                <InputText v-model="searchQuery" placeholder="Type address or place" class="flex-1" />
                <Button label="Search" icon="pi pi-search" @click="searchLocation" :loading="searching" />
              </div>
              <small class="text-xs text-slate-500">Search and pick on map to update coordinates.</small>
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-xs font-semibold text-slate-600">Geofence Radius (meters)</label>
            <Slider v-model="form.geofence_radius_m" :min="0" :max="100" :step="10" class="w-full" />
            <div class="text-xs text-slate-600">{{ form.geofence_radius_m || 0 }} m</div>
            <div class="flex items-center gap-2 pt-1">
              <InputSwitch v-model="form.geofence_enabled" />
              <span class="text-sm text-slate-700">Geofence Enabled</span>
            </div>
          </div>
        </div>

        <div class="h-80 rounded-xl overflow-hidden border border-slate-200">
          <div id="branch-map" class="h-full w-full"></div>
        </div>
        <small v-if="mapError" class="text-xs text-red-600">{{ mapError }}</small>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="showEdit = false" />
        <Button label="Save Branch" :loading="saving" @click="saveBranch" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Slider from 'primevue/slider'
import InputSwitch from 'primevue/inputswitch'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import inventoryService from '@/services/inventory.service'
import axiosClient from '@/axios'
import ecommerceService from '@/services/ecommerce.service'
import { showResponseDialog } from '@/utils/responseDialogBus'
import { forwardGeocodeMapbox, requireMapboxToken } from '@/utils/mapbox'
import type { GeoJSONSource, Map as MapboxMap, Marker as MapboxMarker } from 'mapbox-gl'
import 'mapbox-gl/dist/mapbox-gl.css'

const route = useRoute()
const branchId = Number(route.params.id)
const branch = ref<any>(null)
const showEdit = ref(false)
const saving = ref(false)
const searching = ref(false)
const mapError = ref('')
const isProvincesLoading = ref(false)
const isCitiesLoading = ref(false)
const isBarangaysLoading = ref(false)
const provinces = ref<any[]>([])
const cities = ref<any[]>([])
const barangays = ref<any[]>([])
const branchTypeOptions = [
  { label: 'Storefront', value: 'storefront' },
  { label: 'Warehouse', value: 'warehouse' },
]
const form = ref({
  name: '',
  branch_type: 'storefront',
  address: '',
  province: '',
  provinceId: '',
  city: '',
  cityId: '',
  barangay: '',
  latitude: '',
  longitude: '',
  geofence_radius_m: 0,
  geofence_enabled: false,
})
const searchQuery = ref('')
const employees = ref<any[]>([])
const filteredEmployees = ref<any[]>([])
const empSearch = ref('')
const empRoleFilter = ref('')
const roleOptions = ref<{ label: string; value: string }[]>([])

const normalize = (value: unknown) => String(value ?? '').trim().toLowerCase()
const provinceOptions = computed(() => provinces.value.map((item: any) => ({
  label: item.name || item.province_name || 'Province',
  value: String(item.province_id || item.code || item.id || ''),
})))
const cityOptions = computed(() => cities.value.map((item: any) => ({
  label: item.name || item.city_name || 'City',
  value: String(item.city_id || item.code || item.id || ''),
})))
const barangayOptions = computed(() => {
  const options = barangays.value.map((item: any) => ({
    label: item.name || item.barangay_name || 'Barangay',
    value: item.name || item.barangay_name || '',
  }))
  if (form.value.barangay && !options.some((option) => normalize(option.value) === normalize(form.value.barangay))) {
    options.unshift({ label: form.value.barangay, value: form.value.barangay })
  }
  return options
})

let map: MapboxMap | null = null
let marker: MapboxMarker | null = null
let mapboxgl: typeof import('mapbox-gl').default | null = null
let mapReady = false

const loadBranch = async () => {
  const res = await axiosClient.get(`/api/branches/${branchId}`)
  const payload = res?.data ?? res ?? {}
  const data = payload.data ?? payload ?? null
  branch.value = data
  form.value.name = data?.name || ''
  form.value.branch_type = data?.branch_type || 'storefront'
  form.value.address = data?.address || ''
  form.value.province = data?.province || ''
  form.value.city = data?.city || ''
  form.value.barangay = data?.barangay || ''
  form.value.latitude = data?.latitude || ''
  form.value.longitude = data?.longitude || ''
  form.value.geofence_radius_m = data?.geofence_radius_m || 0
  form.value.geofence_enabled = data?.geofence_enabled ?? false
  if (showEdit.value) {
    initMap()
  }
}

const fetchProvinces = async () => {
  isProvincesLoading.value = true
  try {
    const response = await ecommerceService.getProvinces()
    const data = response.data?.data ?? response.data ?? []
    provinces.value = Array.isArray(data) ? data : []
  } finally {
    isProvincesLoading.value = false
  }
}

const fetchCities = async (provinceId: string) => {
  if (!provinceId) {
    cities.value = []
    return
  }
  isCitiesLoading.value = true
  try {
    const response = await ecommerceService.getCities(provinceId)
    const data = response.data?.data ?? response.data ?? []
    cities.value = Array.isArray(data) ? data : []
  } finally {
    isCitiesLoading.value = false
  }
}

const fetchBarangays = async (cityId: string) => {
  if (!cityId) {
    barangays.value = []
    return
  }
  isBarangaysLoading.value = true
  try {
    const response = await ecommerceService.getBarangays(cityId)
    const data = response.data?.data ?? response.data ?? []
    barangays.value = Array.isArray(data) ? data : []
  } finally {
    isBarangaysLoading.value = false
  }
}

const openEditDialog = async () => {
  if (!branch.value) return

  form.value.name = branch.value.name || ''
  form.value.branch_type = branch.value.branch_type || 'storefront'
  form.value.address = branch.value.address || ''
  form.value.province = branch.value.province || ''
  form.value.city = branch.value.city || ''
  form.value.barangay = branch.value.barangay || ''
  form.value.latitude = branch.value.latitude || ''
  form.value.longitude = branch.value.longitude || ''
  form.value.geofence_radius_m = branch.value.geofence_radius_m || 0
  form.value.geofence_enabled = branch.value.geofence_enabled ?? false
  form.value.provinceId = ''
  form.value.cityId = ''
  showEdit.value = true

  try {
    if (!provinces.value.length) await fetchProvinces()
    const province = provinces.value.find((item: any) => normalize(item.name || item.province_name) === normalize(form.value.province))
    form.value.provinceId = String(province?.province_id || province?.code || province?.id || '')
    if (!form.value.provinceId) return

    await fetchCities(form.value.provinceId)
    const city = cities.value.find((item: any) => normalize(item.name || item.city_name) === normalize(form.value.city))
    form.value.cityId = String(city?.city_id || city?.code || city?.id || '')
    if (!form.value.cityId) return

    await fetchBarangays(form.value.cityId)
    const barangay = barangays.value.find((item: any) => normalize(item.name || item.barangay_name) === normalize(form.value.barangay))
    if (barangay) form.value.barangay = barangay.name || barangay.barangay_name
  } catch (error) {
    console.error('Failed to load branch address options:', error)
  }
}

const onProvinceChange = async () => {
  const province = provinces.value.find((item: any) => String(item.province_id || item.code || item.id) === String(form.value.provinceId))
  form.value.province = province?.name || province?.province_name || ''
  form.value.cityId = ''
  form.value.city = ''
  form.value.barangay = ''
  barangays.value = []
  await fetchCities(form.value.provinceId)
}

const onCityChange = async () => {
  const city = cities.value.find((item: any) => String(item.city_id || item.code || item.id) === String(form.value.cityId))
  form.value.city = city?.name || city?.city_name || ''
  form.value.barangay = ''
  await fetchBarangays(form.value.cityId)
}

const loadEmployees = async () => {
  try {
    const res = await axiosClient.get('/api/employees', { params: { branch_id: branchId, per_page: 1000 } })
    const payload = res?.data ?? res ?? {}
    const list = payload.data?.data ?? payload.data ?? payload ?? []
    employees.value = list
    roleOptions.value = [
      { label: 'All roles', value: '' },
      ...Array.from(new Set(list.map((e: any) => e.role_name || e.role).filter(Boolean))).map((r: string) => ({
        label: r,
        value: r,
      })),
    ]
    applyEmpFilters()
  } catch (e) {
    employees.value = []
    filteredEmployees.value = []
  }
}

const fullname = (emp: any) => {
  if (!emp) return '—'
  const name =
    emp.full_name ||
    [emp.first_name || emp.fname, emp.last_name || emp.lname || emp.lnmae]
      .filter(Boolean)
      .join(' ')
  return name || emp.name || '—'
}

const applyEmpFilters = () => {
  const term = empSearch.value.toLowerCase()
  filteredEmployees.value = employees.value.filter((emp: any) => {
    const matchesSearch =
      !term ||
      (emp.full_name && emp.full_name.toLowerCase().includes(term)) ||
      (emp.email && emp.email.toLowerCase().includes(term)) ||
      ((emp.first_name + ' ' + emp.last_name).toLowerCase().includes(term))
    const matchesRole = !empRoleFilter.value || (emp.role_name || emp.role) === empRoleFilter.value
    return matchesSearch && matchesRole
  })
}

const resetEmpFilters = () => {
  empSearch.value = ''
  empRoleFilter.value = ''
  applyEmpFilters()
}

const initMap = async () => {
  if (mapReady) {
    redrawShapes()
    return
  }
  const container = document.getElementById('branch-map')
  if (!container) return
  const lat = Number(form.value.latitude) || 14.5995
  const lng = Number(form.value.longitude) || 120.9842
  try {
    mapError.value = ''
    const mapboxModule = await import('mapbox-gl')
    mapboxgl = mapboxModule.default
    mapboxgl.accessToken = requireMapboxToken()
    map = new mapboxgl.Map({
      container,
      style: 'mapbox://styles/mapbox/streets-v12',
      center: [lng, lat],
      zoom: 14,
    })
    mapReady = true

    const activeMap = map
    activeMap.on('load', () => {
      if (map !== activeMap) return
      activeMap.addSource('branch-geofence', {
        type: 'geojson',
        data: getGeofenceFeature(),
      })
      activeMap.addLayer({
        id: 'branch-geofence-fill',
        type: 'fill',
        source: 'branch-geofence',
        paint: { 'fill-color': '#6366f1', 'fill-opacity': 0.15 },
      })
      activeMap.addLayer({
        id: 'branch-geofence-outline',
        type: 'line',
        source: 'branch-geofence',
        paint: { 'line-color': '#6366f1', 'line-width': 2 },
      })
      redrawShapes()
    })
    activeMap.on('click', (event) => {
      form.value.latitude = event.lngLat.lat.toFixed(6)
      form.value.longitude = event.lngLat.lng.toFixed(6)
    })
    activeMap.on('error', (event) => {
      mapError.value = event.error?.message || 'Mapbox could not load the map. Check the token and network connection.'
    })
  } catch (error: any) {
    map?.remove()
    map = null
    mapReady = false
    mapError.value = error?.message || 'Unable to load the Mapbox map.'
  }
}

const getGeofenceFeature = () => {
  const radius = Number(form.value.geofence_radius_m)
  const latitude = Number(form.value.latitude)
  const longitude = Number(form.value.longitude)
  if (!radius || !form.value.latitude || !form.value.longitude) {
    return { type: 'FeatureCollection' as const, features: [] }
  }

  const earthRadius = 6371008.8
  const angularDistance = radius / earthRadius
  const latitudeRadians = latitude * Math.PI / 180
  const longitudeRadians = longitude * Math.PI / 180
  const ring: number[][] = []

  for (let step = 0; step <= 64; step += 1) {
    const bearing = step / 64 * Math.PI * 2
    const pointLatitude = Math.asin(
      Math.sin(latitudeRadians) * Math.cos(angularDistance)
        + Math.cos(latitudeRadians) * Math.sin(angularDistance) * Math.cos(bearing),
    )
    const pointLongitude = longitudeRadians + Math.atan2(
      Math.sin(bearing) * Math.sin(angularDistance) * Math.cos(latitudeRadians),
      Math.cos(angularDistance) - Math.sin(latitudeRadians) * Math.sin(pointLatitude),
    )
    ring.push([pointLongitude * 180 / Math.PI, pointLatitude * 180 / Math.PI])
  }

  return {
    type: 'Feature' as const,
    properties: {},
    geometry: { type: 'Polygon' as const, coordinates: [ring] },
  }
}

const redrawShapes = () => {
  if (!map || !mapboxgl) return
  const lat = Number(form.value.latitude) || 0
  const lng = Number(form.value.longitude) || 0
  marker?.remove()
  marker = new mapboxgl.Marker({ draggable: true })
    .setLngLat([lng, lat])
    .addTo(map)
  marker.on('dragend', () => {
    const point = marker!.getLngLat()
    form.value.latitude = point.lat.toFixed(6)
    form.value.longitude = point.lng.toFixed(6)
  })
  const geofenceSource = map.getSource('branch-geofence') as GeoJSONSource | undefined
  geofenceSource?.setData(getGeofenceFeature())
  map.jumpTo({ center: [lng, lat], zoom: 14 })
  setTimeout(() => map?.resize(), 150)
}

watch(
  () => [form.value.latitude, form.value.longitude, form.value.geofence_radius_m],
  () => redrawShapes()
)

watch(
  () => showEdit.value,
  async (visible) => {
    if (visible) {
      await nextTick()
      await initMap()
    }
  }
)

const saveBranch = async () => {
  if (!form.value.name.trim() || !form.value.address.trim() || !form.value.city.trim()) {
    showResponseDialog({
      severity: 'error',
      title: 'Missing Branch Details',
      message: 'Please enter the branch name and address, and select a city.',
    })
    return
  }

  saving.value = true
  try {
    await axiosClient.put(`/api/branches/${branchId}`, {
      name: form.value.name.trim(),
      branch_type: form.value.branch_type,
      address: form.value.address || null,
      province: form.value.province || null,
      city: form.value.city || null,
      barangay: form.value.barangay || null,
      latitude: form.value.latitude || null,
      longitude: form.value.longitude || null,
      geofence_radius_m: form.value.geofence_radius_m || 0,
      geofence_enabled: form.value.geofence_enabled,
    })
    showEdit.value = false
    await loadBranch()
  } finally {
    saving.value = false
  }
}

async function searchLocation() {
  if (!searchQuery.value.trim()) return
  searching.value = true
  try {
    const result = await forwardGeocodeMapbox(searchQuery.value.trim())
    if (!result) {
      showResponseDialog({
        severity: 'warn',
        title: 'Location Not Found',
        message: 'Mapbox could not find that location. Try a more specific address.',
      })
      return
    }
    form.value.latitude = result.latitude.toFixed(6)
    form.value.longitude = result.longitude.toFixed(6)
    if (result.address) form.value.address = result.address
    redrawShapes()
  } catch (error: any) {
    showResponseDialog({
      severity: 'error',
      title: 'Mapbox Search Failed',
      message: error?.message || 'Unable to search for that location.',
    })
  } finally {
    searching.value = false
  }
}

onMounted(loadBranch)
onMounted(loadEmployees)
</script>

<style scoped>
#branch-map {
  min-height: 320px;
}
</style>
