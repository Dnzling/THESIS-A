<template>
  <div class="min-h-screen">
    <div class="mx-auto min-w-7xl px-6">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">Supplier Profile</p>
          <h1 class="text-2xl font-semibold text-slate-900">Supplier Portal</h1>
          <p class="text-sm text-slate-500">Manage your verification and supplier details.</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button :label="verificationLabel" icon="pi pi-check-circle" severity="success" class="small-pill"
            :disabled="isVerified" @click="goToVerification" />
        </div>
      </div>
  
      <div v-if="loading" class="mt-10 flex items-center justify-center">
        <ProgressSpinner />
      </div>
  
      <template v-else>
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[1.2fr_0.8fr]">
          <section class="rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="text-lg font-semibold text-slate-900">{{ supplierName }}</p>
                <p class="text-sm text-slate-500">{{ supplierTypeLabel }}</p>
              </div>
              <Tag :value="statusLabel" :severity="statusSeverity" rounded />
            </div>
  
            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
              <InfoRow label="Company Name" :value="supplier?.company_name || supplier?.supplier_name || '-'" />
              <InfoRow label="Contact Person" :value="supplier?.contact_person || '-'" />
              <InfoRow label="Email" :value="supplier?.email || user?.email || '-'" />
              <InfoRow label="Phone" :value="supplier?.phone || '-'" />
              <InfoRow label="Address" :value="fullAddress" />
            </div>
  
            <div v-if="portal?.rejection_reason"
              class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
              <p class="font-semibold">Verification Feedback</p>
              <p class="mt-1 text-rose-700">{{ portal?.rejection_reason }}</p>
            </div>
          </section>
  
          <section class="rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm space-y-4">
            <div>
              <p class="text-sm font-semibold text-slate-800">Verification Status</p>
              <p class="text-xs text-slate-500">Upload or update documents to verify your account.</p>
            </div>
  
            <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <p class="text-sm font-semibold text-slate-700">Account Status</p>
                  <p class="text-xs text-slate-500">{{ statusHint }}</p>
                </div>
                <Tag :value="statusLabel" :severity="statusSeverity" rounded />
              </div>
            </div>
  
            <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <p class="text-sm font-semibold text-slate-700">Verification</p>
                  <p class="text-xs text-slate-500">{{ verificationLabel }}</p>
                </div>
                <Button :label="verificationLabel" severity="info" class="small-pill" :disabled="isVerified"
                  @click="goToVerification" />
              </div>
            </div>
          </section>
  
          <section class="rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-lg font-semibold text-slate-900">Location / Coordinates</p>
                <p class="text-xs text-slate-500">Keep your pickup point accurate for delivery distance.</p>
              </div>
              <Button label="Save" rounded size="small" severity="info" :loading="savingCoords"
                :disabled="!coords.latitude || !coords.longitude || savingCoords" @click="saveCoordinates" />
            </div>
  
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
              <div class="space-y-2 md:col-span-2">
                <label class="text-xs font-semibold text-slate-600">Search Place</label>
                <InputText v-model="coords.query" placeholder="Search address to preview on map"
                  @keyup.enter="searchOnMap" fluid />
                <p class="text-[11px] text-slate-500">Click on the map to set your coordinates, or search above.</p>
                <div class="flex gap-4 text-xs text-slate-500 mt-2">
                  <span>Lat: <span class="font-semibold text-slate-700">{{ coords.latitude || '—' }}</span></span>
                  <span>Lng: <span class="font-semibold text-slate-700">{{ coords.longitude || '—' }}</span></span>
                </div>
              </div>
            </div>
  
            <div class="rounded-xl border border-slate-200 bg-slate-50 overflow-hidden h-64 relative">
              <div ref="mapEl" class="absolute inset-0"></div>
            </div>
          </section>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch, nextTick, defineComponent, h } from 'vue'
import { router } from '@inertiajs/vue3'
import { useAuthStore } from '@/stores/auth'
import supplierService from '@/services/supplier.service'
import SystemLayout from '@/Layouts/SystemLayout.vue'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Card from 'primevue/card'
import { onBeforeUnmount, shallowRef } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

defineOptions({ layout: SystemLayout })

const authStore = useAuthStore()
const loading = ref(true)
const portal = ref<any | null>(null)
const supplier = ref<any | null>(null)
const coords = ref<{ latitude: string; longitude: string; query: string }>({ latitude: '', longitude: '', query: '' })
const savingCoords = ref(false)
const mapEl = shallowRef<HTMLDivElement | null>(null)
const mapRef = shallowRef<any>(null)
const markerRef = shallowRef<any>(null)
const mapReady = ref(false)
const user = computed(() => authStore.user)

const isSupplier = computed(() => String(authStore.user?.role || '').toLowerCase().includes('supplier'))

const supplierName = computed(() => supplier.value?.company_name || supplier.value?.supplier_name || 'Supplier')
const supplierTypeLabel = computed(() => {
  const raw = supplier.value?.supplier_type || ''
  return raw ? raw.toString().replace(/_/g, ' ').replace(/\b\w/g, (c: string) => c.toUpperCase()) : 'Supplier Type'
})

const fullAddress = computed(() => {
  const parts = [
    supplier.value?.address,
    supplier.value?.city,
    supplier.value?.province,
    supplier.value?.postal_code,
    supplier.value?.country,
  ].filter(Boolean)
  return parts.length ? parts.join(', ') : '-'
})

const statusLabel = computed(() => {
  const status = portal.value?.status || 'pending'
  return status.toString().toUpperCase()
})

const statusSeverity = computed(() => {
  const status = (portal.value?.status || '').toLowerCase()
  if (status === 'approved') return 'success'
  if (status === 'rejected') return 'danger'
  return 'warning'
})

const paymentTermsLabel = computed(() => {
  const raw = supplier.value?.payment_terms || '-'
  return raw.toString().replace(/_/g, ' ').replace(/\b\w/g, (c: string) => c.toUpperCase())
})

const isVerified = computed(() => (portal.value?.status || '').toLowerCase() === 'approved')
const verificationLabel = computed(() => (isVerified.value ? 'Verified' : 'Verify Account'))
const statusHint = computed(() => {
  if (isVerified.value) return 'Your account is verified.'
  if ((portal.value?.status || '').toLowerCase() === 'rejected') return 'Please review the feedback and resubmit.'
  return 'Verification pending. Upload the required documents.'
})

const goToVerification = () => {
  if (isVerified.value) return
  router.visit('/supplier-portal/registration')
}

const loadPortal = async () => {
  loading.value = true
  try {
    const response = await supplierService.getMyPortal()
    if (!response?.success) {
      router.visit('/supplier-portal/registration')
      return
    }
    portal.value = response.data
    supplier.value = response.data?.supplier || null
    coords.value.latitude = response.data?.latitude ? String(response.data.latitude) : ''
    coords.value.longitude = response.data?.longitude ? String(response.data.longitude) : ''
    coords.value.query = [supplier.value?.address, supplier.value?.city].filter(Boolean).join(', ')
  } catch (error) {
    router.visit('/supplier-portal/registration')
  } finally {
    loading.value = false
    await nextTick()
    await initMap()
  }
}

const saveCoordinates = async () => {
  if (!coords.value.latitude || !coords.value.longitude) return
  savingCoords.value = true
  try {
    const res = await supplierService.updatePortalCoordinates({
      latitude: Number(coords.value.latitude),
      longitude: Number(coords.value.longitude),
    })
    portal.value = res.data || portal.value
    toastSuccess('Location updated')
  } catch (error: any) {
    toastError(error.response?.data?.message || 'Failed to update location')
  } finally {
    savingCoords.value = false
  }
}

const toastSuccess = (msg: string) => {
  // lazy import primevue toast if needed later
  console.log(msg)
}
const toastError = (msg: string) => {
  console.warn(msg)
}

const setupLeafletDefaults = () => {
  // Fix default icon URLs in Vite context
  const iconRetinaUrl = 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png'
  const iconUrl = 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png'
  const shadowUrl = 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png'
  // @ts-ignore
  delete L.Icon.Default.prototype._getIconUrl
  L.Icon.Default.mergeOptions({
    iconRetinaUrl,
    iconUrl,
    shadowUrl,
  })
}

const forwardGeocode = async (query: string) => {
  const trimmed = query.trim()
  if (!trimmed) return null
  const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&q=${encodeURIComponent(trimmed)}&limit=1`
  const response = await fetch(url, { headers: { Accept: 'application/json' } })
  if (!response.ok) return null
  const results = await response.json()
  if (!Array.isArray(results) || !results.length) return null
  const hit = results[0]
  return {
    lat: parseFloat(hit.lat),
    lng: parseFloat(hit.lon),
  }
}

const initMap = async () => {
  if (mapReady.value || !mapEl.value) return
  setupLeafletDefaults()

  const center: [number, number] = [
    coords.value.latitude ? Number(coords.value.latitude) : 14.5995,
    coords.value.longitude ? Number(coords.value.longitude) : 120.9842,
  ]

  const map = L.map(mapEl.value).setView(center, coords.value.latitude ? 15 : 12)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map)

  const marker = L.marker(center, { draggable: true }).addTo(map)

  marker.on('dragend', () => {
    const pos = marker.getLatLng()
    coords.value.latitude = pos.lat.toFixed(7)
    coords.value.longitude = pos.lng.toFixed(7)
  })

  map.on('click', (event: any) => {
    marker.setLatLng(event.latlng)
    coords.value.latitude = event.latlng.lat.toFixed(7)
    coords.value.longitude = event.latlng.lng.toFixed(7)
  })

  mapRef.value = map
  markerRef.value = marker
  mapReady.value = true
}

const searchOnMap = async () => {
  const result = await forwardGeocode(coords.value.query || '')
  if (!result || !mapRef.value || !markerRef.value) return
  mapRef.value.setView([result.lat, result.lng], 15)
  markerRef.value.setLatLng([result.lat, result.lng])
  coords.value.latitude = result.lat.toFixed(7)
  coords.value.longitude = result.lng.toFixed(7)
}

onBeforeUnmount(() => {
  if (mapRef.value) {
    mapRef.value.remove()
  }
})

onMounted(async () => {
  if (!isSupplier.value) {
    router.visit('/profile')
    return
  }
  await loadPortal()
})
const InfoRow = defineComponent({
  name: 'InfoRow',
  props: {
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
  },
  setup(props) {
    return () =>
      h('div', { class: 'rounded-xl border border-slate-100 bg-slate-50/80 p-4' }, [
        h('div', { class: 'text-xs text-slate-500' }, props.label),
        h(
          'div',
          { class: 'mt-1 text-sm font-semibold text-slate-900' },
          props.value ?? '-'
        ),
      ])
  },
})
</script>

<style scoped>
.small-pill {
  border-radius: 999px;
  font-size: 0.85rem;
  padding: 0.45rem 0.9rem;
}
</style>
