<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">{{ branch?.name || 'Branch Detail' }}</h1>
        <p class="text-sm text-slate-600">{{ branch?.branch_code || branch?.code || '' }}</p>
      </div>
      <Button label="Edit Address / Geofence" icon="pi pi-map" @click="showEdit = true" />
    </div>
  
    <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-slate-700">
          <div>
            <div class="text-xs text-slate-500 uppercase">Address</div>
            <div class="font-medium text-slate-900">{{ branch?.address || '—' }}</div>
            <div class="text-slate-500">{{ branch?.city }}</div>
          </div>
          <div>
            <div class="text-xs text-slate-500 uppercase">Coordinates</div>
            <div class="font-medium text-slate-900">
              {{ branch?.latitude ?? '—' }}, {{ branch?.longitude ?? '—' }}
            </div>
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
            <Dropdown v-model="empRoleFilter" :options="roleOptions" optionLabel="label" optionValue="value"
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
  
    <Dialog v-model:visible="showEdit" modal header="Edit Address & Geofence" class="w-full max-w-4xl">
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
  
          <div class="space-y-2">
            <label class="text-xs font-semibold text-slate-600">Address: {{ form.address }}</label>
            <div class="text-xs text-slate-500">
              Lat: {{ form.latitude || '—' }} | Lng: {{ form.longitude || '—' }}
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
  
          <Button label="Save" size="small" :loading="saving" @click="saveBranch" />
        </div>
  
        <div class="h-80 rounded-xl overflow-hidden border border-slate-200">
          <div id="branch-map" class="h-full w-full"></div>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Slider from 'primevue/slider'
import InputSwitch from 'primevue/inputswitch'
import Tag from 'primevue/tag'
import Dropdown from 'primevue/dropdown'
import inventoryService from '@/services/inventory.service'
import axiosClient from '@/axios'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const route = useRoute()
const branchId = Number(route.params.id)
const branch = ref<any>(null)
const showEdit = ref(false)
const saving = ref(false)
const searching = ref(false)
const form = ref({
  address: '',
  city: '',
  latitude: '',
  longitude: '',
  geofence_radius_m: 0,
  geofence_enabled: true,
})
const searchQuery = ref('')
const employees = ref<any[]>([])
const filteredEmployees = ref<any[]>([])
const empSearch = ref('')
const empRoleFilter = ref('')
const roleOptions = ref<{ label: string; value: string }[]>([])

let map: L.Map | null = null
let marker: L.Marker | null = null
let circle: L.Circle | null = null
let mapReady = false

const loadBranch = async () => {
  const res = await axiosClient.get(`/api/branches/${branchId}`)
  const payload = res?.data ?? res ?? {}
  const data = payload.data ?? payload ?? null
  branch.value = data
  form.value.address = data?.address || ''
  form.value.city = data?.city || ''
  form.value.latitude = data?.latitude || ''
  form.value.longitude = data?.longitude || ''
  form.value.geofence_radius_m = data?.geofence_radius_m || 0
  form.value.geofence_enabled = data?.geofence_enabled ?? true
  if (showEdit.value) {
    initMap()
  }
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

const initMap = () => {
  if (mapReady) {
    redrawShapes()
    return
  }
  const container = document.getElementById('branch-map')
  if (!container) return
  const lat = Number(form.value.latitude) || 14.5995
  const lng = Number(form.value.longitude) || 120.9842
  map = L.map(container).setView([lat, lng], 14)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map)
  map.on('click', (e: any) => {
    form.value.latitude = e.latlng.lat.toFixed(6)
    form.value.longitude = e.latlng.lng.toFixed(6)
  })
  mapReady = true
  redrawShapes()
}

const redrawShapes = () => {
  if (!map) return
  const lat = Number(form.value.latitude) || 0
  const lng = Number(form.value.longitude) || 0
  if (marker) marker.remove()
  if (circle) circle.remove()
  marker = L.marker([lat, lng], { draggable: true }).addTo(map)
  marker.on('dragend', () => {
    const pos = marker!.getLatLng()
    form.value.latitude = pos.lat.toFixed(6)
    form.value.longitude = pos.lng.toFixed(6)
  })
  if (form.value.geofence_radius_m && form.value.latitude && form.value.longitude) {
    circle = L.circle([lat, lng], {
      radius: form.value.geofence_radius_m,
      color: '#6366f1',
      fillOpacity: 0.15,
    }).addTo(map!)
  }
  map.setView([lat, lng], 14)
  setTimeout(() => map?.invalidateSize(), 150)
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
      initMap()
    }
  }
)

const saveBranch = async () => {
  saving.value = true
  try {
    await axiosClient.put(`/api/branches/${branchId}`, {
      address: form.value.address || null,
      city: form.value.city || null,
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
    const q = encodeURIComponent(searchQuery.value.trim())
    const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${q}`)
    const results = await res.json()
    if (results && results.length > 0) {
      const first = results[0]
      form.value.latitude = Number(first.lat).toFixed(6)
      form.value.longitude = Number(first.lon).toFixed(6)
      if (first.display_name) form.value.address = first.display_name
      redrawShapes()
    }
  } catch (e) {
    console.warn('Search failed', e)
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
