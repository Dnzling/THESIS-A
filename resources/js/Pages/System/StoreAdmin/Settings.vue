<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Store Settings</h1>
        <p class="text-sm text-slate-600">Manage trial setup, modules, and system configuration.</p>
      </div>
      <Button label="Go to Trial Setup" severity="secondary" outlined @click="goToOnboarding" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
      <Card class="lg:col-span-2">
        <template #title>Trial Modules</template>
        <template #content>
          <p class="text-sm text-slate-600 mb-4">
            Choose which modules are enabled for your store. Disabled modules will be hidden from the sidebar and blocked.
          </p>
          <MultiSelect
            v-model="form.modules"
            :options="moduleOptions"
            optionLabel="label"
            optionValue="value"
            display="chip"
            filter
            class="w-full"
            placeholder="Select modules"
          />
          <div class="mt-4 flex items-center justify-between">
            <p class="text-xs text-slate-500">
              You can update these anytime during the trial.
            </p>
            <Button
              label="Save Modules"
              icon="pi pi-check"
              :loading="saving"
              :disabled="form.modules.length === 0 || saving"
              @click="saveModules"
            />
          </div>
        </template>
      </Card>

      <Card>
        <template #title>Trial Status</template>
        <template #content>
          <div class="space-y-3 text-sm text-slate-700">
            <div class="flex items-center justify-between">
              <span>Plan</span>
              <span class="font-semibold">{{ trialPlanLabel }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span>Status</span>
              <Tag :value="subscriptionStatusLabel" :severity="subscriptionSeverity" />
            </div>
            <div class="flex items-center justify-between">
              <span>Ends On</span>
              <span class="font-semibold">{{ subscriptionEndsAtLabel }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span>Days Remaining</span>
              <span class="font-semibold">{{ daysRemainingLabel }}</span>
            </div>
          </div>
          <div class="mt-6 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
            Upgrade your plan anytime to unlock all modules and remove trial limits.
          </div>
          <Button class="mt-4 w-full" label="Upgrade Plan" severity="info" @click="goToUpgrade" />
        </template>
      </Card>
    </div>

    <Card>
      <template #title>Store Profile</template>
      <template #content>
        <div class="grid gap-4 md:grid-cols-2 text-sm text-slate-700">
          <div>
            <div class="text-xs uppercase text-slate-400">Store Name</div>
            <div class="font-semibold">{{ store.name || 'Not set' }}</div>
          </div>
          <div>
            <div class="text-xs uppercase text-slate-400">Contact</div>
            <div class="font-semibold">{{ store.contact_person || 'Not set' }}</div>
          </div>
          <div>
            <div class="text-xs uppercase text-slate-400">Email</div>
            <div class="font-semibold">{{ store.email || 'Not set' }}</div>
          </div>
          <div>
            <div class="text-xs uppercase text-slate-400">Phone</div>
            <div class="font-semibold">{{ store.phone || 'Not set' }}</div>
          </div>
        </div>
      </template>
    </Card>

    <Card>
      <template #title>Attendance Geolocation</template>
      <template #content>
        <p class="text-sm text-slate-600 mb-4">
          Set the main branch location for attendance login/clock-in. Employees must be within the radius to log in.
        </p>
        <div class="grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
          <div>
            <div class="mb-3 flex flex-wrap items-center gap-2">
              <InputText v-model="searchQuery" class="flex-1 min-w-[220px]" placeholder="Search address..." />
              <Button label="Search" icon="pi pi-search" severity="secondary" @click="searchAddress" />
            </div>
            <div ref="mapEl" class="h-72 w-full rounded-xl border border-slate-200"></div>
            <p class="mt-2 text-xs text-slate-500">Drag the pin to adjust the exact location.</p>
          </div>
          <div class="space-y-3 text-sm text-slate-700">
            <div>
              <div class="text-xs uppercase text-slate-400">Address</div>
              <div class="font-semibold">{{ attendance.address || 'Not set' }}</div>
            </div>
            <div class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2">
              <div>
                <div class="text-xs uppercase text-slate-400">Geofence</div>
                <div class="text-sm font-semibold">{{ attendance.geofence_enabled ? 'Enabled' : 'Disabled' }}</div>
              </div>
              <InputSwitch v-model="attendance.geofence_enabled" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <div class="text-xs uppercase text-slate-400">Barangay</div>
                <div class="font-semibold">{{ attendance.barangay || 'Not set' }}</div>
              </div>
              <div>
                <div class="text-xs uppercase text-slate-400">City</div>
                <div class="font-semibold">{{ attendance.city || 'Not set' }}</div>
              </div>
              <div>
                <div class="text-xs uppercase text-slate-400">Province</div>
                <div class="font-semibold">{{ attendance.province || 'Not set' }}</div>
              </div>
              <div>
                <div class="text-xs uppercase text-slate-400">Radius (meters)</div>
                <div class="mt-2 space-y-2">
                  <Slider v-model="attendance.geofence_radius_m" :min="0" :max="100" :step="1" class="w-full" />
                  <div class="text-xs text-slate-500">{{ attendance.geofence_radius_m }} meters</div>
                </div>
              </div>
            </div>
            <div class="text-xs text-slate-500">
              Coordinates: {{ attendance.latitude ?? '—' }}, {{ attendance.longitude ?? '—' }}
            </div>
            <Button
              label="Save Attendance Location"
              icon="pi pi-check"
              :loading="savingAttendance"
              @click="saveAttendance"
            />
          </div>
        </div>
      </template>
    </Card>

    <Card>
      <template #title>Small Store Access Map</template>
      <template #content>
        <p class="text-sm text-slate-600 mb-4">
          This is the frontend permission list for the Small tier (1-5 employees). Use it as the baseline when
          checking feature visibility on the UI.
        </p>

        <div class="grid gap-4 md:grid-cols-2">
          <div
            v-for="module in smallModules"
            :key="module.key"
            class="rounded-lg border border-slate-200 p-4"
          >
            <div class="text-sm font-semibold text-slate-900">{{ module.label }}</div>
            <div class="mt-2 space-y-2">
              <div
                v-for="feature in module.features"
                :key="feature.key"
                class="rounded-md bg-slate-50 px-3 py-2 text-xs text-slate-700"
              >
                <div class="font-medium">{{ feature.label }}</div>
                <div class="mt-1 text-[11px] text-slate-500">
                  {{ feature.permissions.join(', ') }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-6 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
          Total small-tier permissions: <span class="font-semibold">{{ smallPermissionList.length }}</span>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import axiosClient from '@/axios'
import { router } from '@inertiajs/vue3'
import Button from 'primevue/button'
import Card from 'primevue/card'
import MultiSelect from 'primevue/multiselect'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import InputSwitch from 'primevue/inputswitch'
import Slider from 'primevue/slider'
import Tag from 'primevue/tag'
import { usePermissions } from '@/composables/usePermissions'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'

const saving = ref(false)
const savingAttendance = ref(false)
const loading = ref(true)
const mapEl = ref<HTMLElement | null>(null)
const mapReady = ref(false)
const mapRef = ref<L.Map | null>(null)
const markerRef = ref<L.Marker | null>(null)
const circleRef = ref<L.Circle | null>(null)
const searchQuery = ref('')

const store = reactive({
  name: '',
  contact_person: '',
  email: '',
  phone: '',
})

const attendance = reactive({
  branch_id: null as number | null,
  address: '',
  barangay: '',
  city: '',
  province: '',
  latitude: null as number | null,
  longitude: null as number | null,
  geofence_radius_m: 5,
  geofence_enabled: true,
})

const subscription = reactive({
  tier: 'free',
  status: 'trial',
  ends_at: '',
  days_remaining: null as number | null,
})

const onboarding = reactive({
  plan: 'simple',
  modules: [] as string[],
})

const form = reactive({
  modules: [] as string[],
})

const moduleOptions = [
  { label: 'Inventory', value: 'inventory' },
  { label: 'Procurement', value: 'procurement' },
  { label: 'Sales / POS', value: 'sales' },
  { label: 'HR / Payroll', value: 'hr' },
  { label: 'Logistics / Delivery', value: 'logistics' },
  { label: 'Finance', value: 'finance' },
  { label: 'Supplier Portal', value: 'supplier' },
  { label: 'E-commerce', value: 'ecommerce' },
]

const { permissionCatalog, getTierPermissionFeatures, getTierPermissionList } = usePermissions()

const smallFeatureMap = computed(() => getTierPermissionFeatures('small'))
const smallModules = computed(() =>
  permissionCatalog
    .filter(module => (smallFeatureMap.value[module.key] || []).length > 0)
    .map(module => ({
      ...module,
      features: module.features.filter(feature =>
        (smallFeatureMap.value[module.key] || []).includes(feature.key),
      ),
    })),
)
const smallPermissionList = computed(() => getTierPermissionList('small'))

const trialPlanLabel = computed(() => onboarding.plan === 'unlimited' ? 'Unlimited Trial' : 'Simple Trial')

const subscriptionStatusLabel = computed(() => {
  if (subscription.status === 'expired') return 'Expired'
  if (subscription.status === 'active') return 'Active'
  return 'Trial'
})

const subscriptionSeverity = computed(() => {
  if (subscription.status === 'expired') return 'danger'
  if (subscription.status === 'active') return 'success'
  return 'info'
})

const subscriptionEndsAtLabel = computed(() => subscription.ends_at || 'Not set')
const daysRemainingLabel = computed(() => {
  if (subscription.days_remaining === null) return '—'
  return `${subscription.days_remaining} days`
})

const fetchSettings = async () => {
  loading.value = true
  try {
    const response = await axiosClient.get('/api/store/settings')
    const data = response?.data?.data || {}

    store.name = data.store?.name || ''
    store.contact_person = data.store?.contact_person || ''
    store.email = data.store?.email || ''
    store.phone = data.store?.phone || ''

    subscription.tier = data.subscription?.tier || 'free'
    subscription.status = data.subscription?.status || 'trial'
    subscription.ends_at = data.subscription?.ends_at || ''
    subscription.days_remaining = data.subscription?.days_remaining ?? null

    onboarding.plan = data.onboarding?.plan || 'simple'
    onboarding.modules = data.onboarding?.modules || []
    form.modules = [...onboarding.modules]

    attendance.branch_id = data.attendance?.branch_id ?? null
    attendance.address = data.attendance?.address || ''
    attendance.barangay = data.attendance?.barangay || ''
    attendance.city = data.attendance?.city || ''
    attendance.province = data.attendance?.province || ''
    attendance.latitude = data.attendance?.latitude ?? null
    attendance.longitude = data.attendance?.longitude ?? null
    attendance.geofence_radius_m = data.attendance?.geofence_radius_m ?? 5
    attendance.geofence_enabled = data.attendance?.geofence_enabled ?? true
  } catch (error) {
    console.error('Failed to load settings', error)
  } finally {
    loading.value = false
  }
}

const saveModules = async () => {
  saving.value = true
  try {
    await axiosClient.put('/api/store/settings/modules', {
      modules: form.modules,
    })
    onboarding.modules = [...form.modules]
  } catch (error) {
    console.error('Failed to update modules', error)
  } finally {
    saving.value = false
  }
}

const saveAttendance = async () => {
  if (attendance.latitude === null || attendance.longitude === null) return
  savingAttendance.value = true
  try {
    const response = await axiosClient.put('/api/store/settings/attendance', {
      branch_id: attendance.branch_id,
      address: attendance.address || null,
      barangay: attendance.barangay || null,
      city: attendance.city || null,
      province: attendance.province || null,
      latitude: attendance.latitude,
      longitude: attendance.longitude,
      geofence_radius_m: attendance.geofence_radius_m || 5,
      geofence_enabled: attendance.geofence_enabled,
    })
    const data = response?.data?.data || {}
    attendance.address = data.address || attendance.address
    attendance.barangay = data.barangay || attendance.barangay
    attendance.city = data.city || attendance.city
    attendance.province = data.province || attendance.province
    attendance.latitude = data.latitude ?? attendance.latitude
    attendance.longitude = data.longitude ?? attendance.longitude
    attendance.geofence_radius_m = data.geofence_radius_m ?? attendance.geofence_radius_m
  } catch (error) {
    console.error('Failed to update attendance location', error)
  } finally {
    savingAttendance.value = false
  }
}

const setupLeafletDefaults = () => {
  const icon = L.icon({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41],
  })
  L.Marker.prototype.options.icon = icon
}

const reverseGeocode = async (lat: number, lng: number) => {
  try {
    const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&accept-language=en`
    const response = await fetch(url, {
      headers: { 'Accept': 'application/json' },
    })
    if (!response.ok) return
    const data = await response.json()
    const address = data?.address || {}
    attendance.address = data?.display_name || attendance.address
    attendance.barangay =
      address.suburb ||
      address.neighbourhood ||
      address.village ||
      address.quarter ||
      address.hamlet ||
      attendance.barangay
    attendance.city = address.city || address.town || address.municipality || address.county || attendance.city
    attendance.province = address.state || address.region || attendance.province
  } catch (error) {
    console.error('Reverse geocode failed', error)
  }
}

const forwardGeocode = async (query: string) => {
  const trimmed = query.trim()
  if (!trimmed) return null
  const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&q=${encodeURIComponent(trimmed)}&limit=1&addressdetails=1`
  const response = await fetch(url, {
    headers: { 'Accept': 'application/json' },
  })
  if (!response.ok) return null
  const results = await response.json()
  if (!Array.isArray(results) || results.length === 0) return null
  const hit = results[0]
  return {
    lat: parseFloat(hit.lat),
    lng: parseFloat(hit.lon),
  }
}

const syncLocation = async (lat: number, lng: number) => {
  attendance.latitude = lat
  attendance.longitude = lng
  await reverseGeocode(lat, lng)
}

const initMap = async () => {
  if (!mapEl.value || mapReady.value) return
  setupLeafletDefaults()

  const defaultCenter: [number, number] = [
    attendance.latitude ?? 14.5995,
    attendance.longitude ?? 120.9842,
  ]

  const map = L.map(mapEl.value).setView(defaultCenter, attendance.latitude ? 16 : 12)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map)

  const marker = L.marker(defaultCenter, { draggable: true }).addTo(map)
  const circle = L.circle(defaultCenter, {
    radius: attendance.geofence_radius_m || 5,
    color: '#16a34a',
    fillColor: '#22c55e',
    fillOpacity: 0.15,
    weight: 1,
  }).addTo(map)

  marker.on('dragend', async () => {
    const pos = marker.getLatLng()
    circle.setLatLng(pos)
    await syncLocation(pos.lat, pos.lng)
  })

  map.on('click', async (event: L.LeafletMouseEvent) => {
    marker.setLatLng(event.latlng)
    circle.setLatLng(event.latlng)
    await syncLocation(event.latlng.lat, event.latlng.lng)
  })

  if (!attendance.address && attendance.latitude && attendance.longitude) {
    await syncLocation(attendance.latitude, attendance.longitude)
  }

  mapRef.value = map
  markerRef.value = marker
  circleRef.value = circle
  mapReady.value = true

  const updateRadius = () => {
    circle.setRadius(attendance.geofence_radius_m || 5)
  }

  return { updateRadius }
}

const searchAddress = async () => {
  const result = await forwardGeocode(searchQuery.value)
  if (!result) return
  if (mapRef.value) {
    mapRef.value.setView([result.lat, result.lng], 16)
  }
  if (markerRef.value) {
    markerRef.value.setLatLng([result.lat, result.lng])
  }
  if (circleRef.value) {
    circleRef.value.setLatLng([result.lat, result.lng])
  }
  await syncLocation(result.lat, result.lng)
}

const goToOnboarding = () => {
  router.visit('/trial-onboarding')
}

const goToUpgrade = () => {
  router.visit('/admin/subscription')
}

onMounted(async () => {
  await fetchSettings()
  try {
    const handlers = await initMap()
    if (handlers?.updateRadius) {
      watch(
        () => attendance.geofence_radius_m,
        () => handlers.updateRadius()
      )
    }
  } catch (error) {
    console.error('Map init failed', error)
  }
})
</script>
