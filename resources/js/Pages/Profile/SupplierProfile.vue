<template>
  <div class="min-h-screen bg-slate-50/60 py-6">
    <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-orange-600">Supplier Portal</p>
        <h1 class="mt-1 text-2xl font-semibold text-slate-900">{{ isPaymentAccountView ? 'Payment History' : 'Supplier Profile' }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ isPaymentAccountView ? 'Review payments recorded by your partner stores.' : 'Manage your company information, delivery settings, and verification.' }}</p>
      </div>

      <div v-if="loading" class="flex min-h-72 items-center justify-center rounded-xl border border-slate-200 bg-white">
        <ProgressSpinner />
      </div>

      <div v-else class="grid items-start gap-5 lg:grid-cols-[240px_minmax(0,1fr)]">
        <aside class="space-y-4">
          <section class="rounded-xl border border-slate-200 bg-white p-5 text-center shadow-sm">
            <div class="relative mx-auto h-24 w-24">
              <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-slate-50">
                <img v-if="companyLogoPreview || supplier?.logo_url" :src="companyLogoPreview || supplier.logo_url" :alt="`${supplierName} logo`" class="h-full w-full object-contain" />
                <i v-else class="pi pi-building text-2xl text-slate-400" />
              </div>
              <button v-if="!isPaymentAccountView" type="button" class="absolute bottom-0 right-0 flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-orange-500 text-white" title="Upload company logo" @click="companyLogoInput?.click()"><i class="pi pi-camera text-xs" /></button>
            </div>
            <h2 class="mt-4 break-words text-base font-semibold text-slate-900">{{ supplierName }}</h2>
            <p class="mt-1 text-xs text-slate-500">{{ supplierTypeLabel }}</p>
            <p class="mt-1 break-words text-xs text-slate-500">{{ supplier?.city || portal?.city || fullAddress }}</p>
            <Tag class="mt-3" :value="statusLabel" :severity="statusSeverity" rounded />
          </section>

          <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="space-y-3 text-xs">
              <div class="flex items-center justify-between gap-2"><span class="text-slate-500">Contact person</span><span class="max-w-32 truncate font-medium text-slate-800">{{ supplier?.contact_person || portal?.contact_person || '-' }}</span></div>
              <div class="flex items-center justify-between gap-2"><span class="text-slate-500">Phone</span><span class="max-w-32 truncate font-medium text-slate-800">{{ supplier?.phone || portal?.phone || '-' }}</span></div>
              <div class="flex items-center justify-between gap-2"><span class="text-slate-500">Email</span><span class="max-w-32 truncate font-medium text-slate-800">{{ supplier?.email || user?.email || '-' }}</span></div>
            </div>
          </section>

          <section v-if="!isPaymentAccountView" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Quick Actions</p>
            <div class="flex flex-col gap-2">
              <Button :label="verificationLabel" icon="pi pi-check-circle" severity="warn" outlined size="small" :disabled="isVerified" @click="goToVerification" />
              <Button label="Product Listings" icon="pi pi-box" severity="secondary" outlined size="small" @click="router.visit('/supplier-portal/products')" />
              <Button label="Payment History" icon="pi pi-receipt" severity="secondary" text size="small" @click="router.visit('/supplier-portal/payment-account')" />
            </div>
          </section>
        </aside>

        <main class="min-w-0 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
          <Tabs v-model:value="activeProfileTab">
            <TabList class="mb-5 flex flex-wrap border-b border-slate-200">
              <Tab v-if="!isPaymentAccountView" value="account">Account Settings</Tab>
              <Tab v-if="!isPaymentAccountView" value="company">Company Settings</Tab>
              <Tab v-if="!isPaymentAccountView" value="verification">Verification</Tab>
              <Tab value="billing">{{ isPaymentAccountView ? 'Payment History' : 'Billing' }}</Tab>
            </TabList>
            <TabPanels>
              <TabPanel v-if="!isPaymentAccountView" value="account">
                <section class="space-y-5">
                  <div><h2 class="font-semibold text-slate-900">Company Information</h2><p class="text-xs text-slate-500">Contact details and business information for your supplier account.</p></div>
                  <input ref="companyLogoInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onCompanyLogoSelected" />
                  <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 p-3">
                    <div class="min-w-0"><p class="text-sm font-medium text-slate-800">Company logo</p><p class="text-xs text-slate-500">JPG, PNG, or WebP. Maximum 5 MB.</p></div>
                    <div class="flex gap-2"><Button label="Choose Logo" icon="pi pi-image" severity="secondary" outlined size="small" @click="companyLogoInput?.click()" /><Button v-if="companyLogoFile" label="Save Logo" icon="pi pi-check" severity="warn" size="small" :loading="savingCompanyLogo" @click="saveCompanyLogo" /></div>
                  </div>
                  <Message v-if="companyLogoError" severity="error" :closable="false">{{ companyLogoError }}</Message>
                  <Message v-else-if="companyLogoMessage" severity="success" :closable="false">{{ companyLogoMessage }}</Message>
                  <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Company Name</p><p class="mt-1 text-sm font-medium text-slate-900">{{ supplier?.company_name || supplier?.supplier_name || '-' }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Contact Person</p><p class="mt-1 text-sm font-medium text-slate-900">{{ supplier?.contact_person || portal?.contact_person || '-' }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Email Address</p><p class="mt-1 break-words text-sm font-medium text-slate-900">{{ supplier?.email || user?.email || '-' }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Phone Number</p><p class="mt-1 text-sm font-medium text-slate-900">{{ supplier?.phone || portal?.phone || '-' }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-3 sm:col-span-2"><p class="text-xs text-slate-500">Business Address</p><p class="mt-1 text-sm font-medium text-slate-900">{{ fullAddress }}</p></div>
                  </div>
                </section>
              </TabPanel>

              <TabPanel v-if="!isPaymentAccountView" value="company">
                <div class="space-y-5">
                  <section class="space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-semibold text-slate-900">Supplier Delivery Rates</h2><p class="text-xs text-slate-500">Rates used to estimate delivery fees on partner-store purchase orders.</p></div><Button label="Save Rates" icon="pi pi-save" size="small" severity="warn" :loading="savingShippingRates" @click="saveShippingRates" /></div>
                    <div class="grid gap-4 sm:grid-cols-2">
                      <label class="text-xs font-medium text-slate-600">Rate per kilometer<InputNumber v-model="shippingRates.delivery_fee_per_km" class="mt-1 w-full" mode="currency" currency="PHP" locale="en-PH" :min="0" :maxFractionDigits="2" /></label>
                      <label class="text-xs font-medium text-slate-600">Rate per kilogram<InputNumber v-model="shippingRates.delivery_fee_per_kg" class="mt-1 w-full" mode="currency" currency="PHP" locale="en-PH" :min="0" :maxFractionDigits="2" /></label>
                    </div>
                    <Message v-if="shippingRatesError" severity="error" :closable="false">{{ shippingRatesError }}</Message><Message v-else-if="shippingRatesMessage" severity="success" :closable="false">{{ shippingRatesMessage }}</Message>
                  </section>
                  <section class="space-y-4 border-t border-slate-100 pt-5">
                    <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-semibold text-slate-900">Supplier Pickup Location</h2><p class="text-xs text-slate-500">Set the map pin drivers should use when collecting purchase orders.</p></div><Button label="Use Current Location" icon="pi pi-map-marker" size="small" severity="secondary" outlined :loading="locatingCoordinates" @click="useCurrentLocation" /></div>
                    <div class="flex flex-col gap-2 sm:flex-row"><InputText v-model="locationSearch" class="w-full flex-1" placeholder="Search your supplier address or a nearby place" @keyup.enter.prevent="searchLocation" /><Button label="Search" icon="pi pi-search" size="small" severity="warn" :loading="searchingLocation" @click="searchLocation" /></div>
                    <div ref="mapContainer" class="h-80 w-full overflow-hidden rounded-xl border border-slate-200 bg-slate-50"></div>
                    <p class="text-xs text-slate-500">Click the map or drag the pin to set your supplier coordinates.</p>
                    <div class="flex flex-wrap items-center justify-between gap-3"><p v-if="hasSelectedCoordinates" class="text-xs text-slate-600">Pin: {{ Number(locationCoordinates.latitude).toFixed(6) }}, {{ Number(locationCoordinates.longitude).toFixed(6) }}<span v-if="coordinatesSaved" class="ml-2 text-green-700">Saved</span><span v-else class="ml-2 text-orange-700">Unsaved changes</span></p><p v-else class="text-xs text-amber-700">Choose a point on the map to set your supplier coordinates.</p><Button label="Save Coordinates" icon="pi pi-check" size="small" severity="warn" :disabled="!hasSelectedCoordinates || coordinatesSaved" :loading="savingCoordinates" @click="saveCoordinates" /></div>
                    <Message v-if="locationError" severity="error" :closable="false">{{ locationError }}</Message><Message v-else-if="locationMessage" severity="success" :closable="false">{{ locationMessage }}</Message><Message v-if="mapError" severity="error" :closable="false">{{ mapError }}</Message>
                  </section>
                </div>
              </TabPanel>

              <TabPanel v-if="!isPaymentAccountView" value="verification">
                <section class="space-y-4">
                  <div><h2 class="font-semibold text-slate-900">Verification Status</h2><p class="text-xs text-slate-500">Review your account verification and respond to any requested changes.</p></div>
                  <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs text-slate-500">Account Status</p><div class="mt-2 flex items-center justify-between gap-2"><span class="text-sm text-slate-700">{{ statusHint }}</span><Tag :value="statusLabel" :severity="statusSeverity" rounded /></div></div>
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs text-slate-500">Verification</p><div class="mt-2 flex items-center justify-between gap-2"><span class="text-sm text-slate-700">{{ verificationLabel }}</span><Button :label="verificationLabel" severity="info" class="small-pill" size="small" :disabled="isVerified" @click="goToVerification" /></div></div>
                  </div>
                  <div v-if="portal?.rejection_reason" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"><p class="font-semibold">Verification Feedback</p><p class="mt-1 text-rose-700">{{ portal.rejection_reason }}</p></div>
                </section>
              </TabPanel>

              <TabPanel value="billing">
                <section ref="paymentAccountSectionEl" class="space-y-4">
                  <div class="flex flex-wrap items-center justify-between gap-2"><div><h2 class="font-semibold text-slate-900">Payment Records</h2><p class="text-xs text-slate-500">Latest payments recorded from partner stores.</p></div><Button label="Refresh" icon="pi pi-refresh" size="small" severity="secondary" outlined rounded :loading="loadingPaymentHistory" @click="loadPaymentHistory" /></div>
                  <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs text-slate-500">Supplier Balance</p><p class="mt-1 text-xl font-semibold text-slate-900">PHP {{ formatMoney(supplier?.current_balance || 0) }}</p><p class="mt-1 text-xs text-slate-500">Current recorded account balance</p></div>
                    <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs text-slate-500">Payment Records</p><p class="mt-1 text-xl font-semibold text-slate-900">{{ paymentHistoryRows.length }}</p><p class="mt-1 text-xs text-slate-500">Recent payments loaded for this account</p></div>
                  </div>
                  <div class="overflow-x-auto rounded-lg border border-slate-200">
                    <DataTable :value="paymentHistoryRows" :loading="loadingPaymentHistory" rowHover size="small" class="text-sm">
                      <Column field="created_at" header="Date" sortable style="min-width: 175px"><template #body="{ data }"><span class="whitespace-nowrap text-slate-700">{{ formatDateTime(data.created_at) }}</span></template></Column>
                      <Column field="payer" header="Store" style="min-width: 180px"><template #body="{ data }"><span class="font-medium text-slate-800">{{ data.payer }}</span></template></Column>
                      <Column field="invoice" header="Invoice" style="min-width: 140px"><template #body="{ data }"><span class="font-mono text-xs text-slate-600">{{ data.invoice }}</span></template></Column>
                      <Column field="method" header="Method" style="min-width: 130px"><template #body="{ data }"><span class="capitalize text-slate-700">{{ String(data.method || '-').replaceAll('_', ' ') }}</span></template></Column>
                      <Column field="amount" header="Amount" style="min-width: 130px"><template #body="{ data }"><span class="whitespace-nowrap font-semibold text-slate-900">PHP {{ formatMoney(data.amount) }}</span></template></Column>
                      <Column field="status" header="Status" style="min-width: 120px"><template #body="{ data }"><Badge :value="formatPaymentStatus(data.status)" :severity="paymentStatusSeverity(data.status)" /></template></Column>
                      <template #empty><div class="py-10 text-center text-sm text-slate-500">No payment records yet.</div></template>
                    </DataTable>
                  </div>
                </section>
              </TabPanel>
            </TabPanels>
          </Tabs>
        </main>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { computed, onMounted, ref, nextTick, onBeforeUnmount, reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { useAuthStore } from '@/stores/auth'
import supplierService from '@/services/supplier.service'
import SystemLayout from '@/Layouts/SystemLayout.vue'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Message from 'primevue/message'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import { forwardGeocodeMapbox, requireMapboxToken } from '@/utils/mapbox'
import type { Map as MapboxMap, Marker as MapboxMarker } from 'mapbox-gl'
import 'mapbox-gl/dist/mapbox-gl.css'

defineOptions({ layout: SystemLayout })

const authStore = useAuthStore()
const loading = ref(true)
const portal = ref<any | null>(null)
const supplier = ref<any | null>(null)
const companyLogoInput = ref<HTMLInputElement | null>(null)
const companyLogoFile = ref<File | null>(null)
const companyLogoPreview = ref('')
const savingCompanyLogo = ref(false)
const companyLogoError = ref('')
const companyLogoMessage = ref('')
const shippingRates = reactive({ delivery_fee_per_km: 0, delivery_fee_per_kg: 0 })
const savingShippingRates = ref(false)
const shippingRatesError = ref('')
const shippingRatesMessage = ref('')
const paymentAccountSectionEl = ref<HTMLElement | null>(null)
const loadingPaymentHistory = ref(false)
const paymentHistoryRows = ref<any[]>([])
const paymentHistoryPolling = ref<ReturnType<typeof setInterval> | null>(null)
const mapContainer = ref<HTMLElement | null>(null)
const locationSearch = ref('')
const searchingLocation = ref(false)
const locatingCoordinates = ref(false)
const savingCoordinates = ref(false)
const mapError = ref('')
const locationError = ref('')
const locationMessage = ref('')
const locationCoordinates = reactive<{ latitude: number | null; longitude: number | null }>({ latitude: null, longitude: null })
let map: MapboxMap | null = null
let marker: MapboxMarker | null = null
let mapboxgl: typeof import('mapbox-gl').default | null = null
const user = computed(() => authStore.user)
const hasSelectedCoordinates = computed(() => locationCoordinates.latitude !== null && locationCoordinates.longitude !== null)
const coordinatesSaved = computed(() => {
  const savedLatitude = Number(portal.value?.latitude)
  const savedLongitude = Number(portal.value?.longitude)
  return hasSelectedCoordinates.value
    && Number.isFinite(savedLatitude)
    && Number.isFinite(savedLongitude)
    && Math.abs(savedLatitude - Number(locationCoordinates.latitude)) < 0.0000001
    && Math.abs(savedLongitude - Number(locationCoordinates.longitude)) < 0.0000001
})

const isSupplier = computed(() => String(authStore.user?.role || '').toLowerCase().includes('supplier'))
const isPaymentAccountView = computed(() => {
  if (typeof window === 'undefined') return false
  const pathname = window.location.pathname || ''
  const params = new URLSearchParams(window.location.search || '')
  const section = (params.get('section') || '').toLowerCase()
  return pathname === '/supplier-portal/payment-account' || section === 'payment-account'
})
const activeProfileTab = ref(isPaymentAccountView.value ? 'billing' : 'account')

watch(activeProfileTab, async (tab) => {
  if (tab !== 'company' || isPaymentAccountView.value) return
  await nextTick()
  if (map) {
    map.resize()
    return
  }
  await initializeMap()
})

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

const onCompanyLogoSelected = (event: Event) => {
  const file = (event.target as HTMLInputElement).files?.[0] || null
  companyLogoError.value = ''
  companyLogoMessage.value = ''
  if (companyLogoPreview.value) URL.revokeObjectURL(companyLogoPreview.value)
  companyLogoFile.value = file
  companyLogoPreview.value = file ? URL.createObjectURL(file) : ''
}

const saveCompanyLogo = async () => {
  if (!companyLogoFile.value || savingCompanyLogo.value) return
  savingCompanyLogo.value = true
  companyLogoError.value = ''
  companyLogoMessage.value = ''
  try {
    const payload = new FormData()
    payload.append('logo', companyLogoFile.value)
    const response = await supplierService.updatePortalCompanyLogo(payload)
    supplier.value = response?.data?.supplier || supplier.value
    if (companyLogoPreview.value) URL.revokeObjectURL(companyLogoPreview.value)
    companyLogoPreview.value = ''
    companyLogoFile.value = null
    if (companyLogoInput.value) companyLogoInput.value.value = ''
    companyLogoMessage.value = 'Company logo saved and shared with connected stores.'
  } catch (error: any) {
    companyLogoError.value = error?.response?.data?.message || 'Unable to save the company logo.'
  } finally {
    savingCompanyLogo.value = false
  }
}

const saveShippingRates = async () => {
  if (savingShippingRates.value) return
  savingShippingRates.value = true
  shippingRatesError.value = ''
  shippingRatesMessage.value = ''
  try {
    const response = await supplierService.updatePortalShippingRates({
      delivery_fee_per_km: Number(shippingRates.delivery_fee_per_km || 0),
      delivery_fee_per_kg: Number(shippingRates.delivery_fee_per_kg || 0),
    })
    portal.value = response?.data || portal.value
    shippingRatesMessage.value = 'Delivery rates saved.'
  } catch (error: any) {
    shippingRatesError.value = error?.response?.data?.message || 'Unable to save delivery rates.'
  } finally {
    savingShippingRates.value = false
  }
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
    shippingRates.delivery_fee_per_km = Number(response.data?.delivery_fee_per_km ?? 0)
    shippingRates.delivery_fee_per_kg = Number(response.data?.delivery_fee_per_kg ?? 0)
    supplier.value = response.data?.supplier || null
    locationSearch.value = [
      supplier.value?.address || response.data?.address,
      supplier.value?.city || response.data?.city,
      supplier.value?.province || response.data?.province,
      supplier.value?.country || response.data?.country,
    ].filter(Boolean).join(', ')
    locationCoordinates.latitude = response.data?.latitude == null ? null : Number(response.data.latitude)
    locationCoordinates.longitude = response.data?.longitude == null ? null : Number(response.data.longitude)
    await loadPaymentHistory()
  } catch (error) {
    router.visit('/supplier-portal/registration')
  } finally {
    loading.value = false
    await nextTick()
  }
}

const setCoordinates = (latitude: number, longitude: number, recenter = true) => {
  if (!Number.isFinite(latitude) || !Number.isFinite(longitude) || latitude < -90 || latitude > 90 || longitude < -180 || longitude > 180) return
  locationCoordinates.latitude = latitude
  locationCoordinates.longitude = longitude
  locationError.value = ''
  locationMessage.value = ''
  if (!map || !mapboxgl) return
  if (!marker) {
    marker = new mapboxgl.Marker({ draggable: true }).setLngLat([longitude, latitude]).addTo(map)
    marker.on('dragend', () => {
      const point = marker!.getLngLat()
      setCoordinates(point.lat, point.lng, false)
    })
  } else {
    marker.setLngLat([longitude, latitude])
  }
  if (recenter) map.easeTo({ center: [longitude, latitude], zoom: 15 })
}

const initializeMap = async () => {
  if (!mapContainer.value || map || isPaymentAccountView.value) return
  try {
    mapError.value = ''
    mapboxgl = (await import('mapbox-gl')).default
    mapboxgl.accessToken = requireMapboxToken()
    const hasCoords = hasSelectedCoordinates.value
    map = new mapboxgl.Map({
      container: mapContainer.value,
      style: 'mapbox://styles/mapbox/streets-v12',
      center: hasCoords
        ? [Number(locationCoordinates.longitude), Number(locationCoordinates.latitude)]
        : [122, 12],
      zoom: hasCoords ? 14 : 5.5,
    })
    map.on('load', () => {
      if (hasSelectedCoordinates.value) setCoordinates(Number(locationCoordinates.latitude), Number(locationCoordinates.longitude), false)
      setTimeout(() => map?.resize(), 100)
    })
    map.on('click', (event) => setCoordinates(event.lngLat.lat, event.lngLat.lng))
    map.on('error', (event) => { mapError.value = event.error?.message || 'Mapbox could not load the map.' })
  } catch (error: any) {
    mapError.value = error?.message || 'Unable to load the map.'
  }
}

const searchLocation = async () => {
  if (!locationSearch.value.trim()) return
  searchingLocation.value = true
  locationError.value = ''
  try {
    const result = await forwardGeocodeMapbox(locationSearch.value.trim())
    if (!result) {
      locationError.value = 'Mapbox could not find that address. Try a more specific location.'
      return
    }
    setCoordinates(result.latitude, result.longitude)
  } catch (error: any) {
    locationError.value = error?.message || 'Unable to search for that address.'
  } finally {
    searchingLocation.value = false
  }
}

const useCurrentLocation = () => {
  if (!navigator.geolocation) {
    locationError.value = 'This browser does not support location access.'
    return
  }
  locatingCoordinates.value = true
  locationError.value = ''
  navigator.geolocation.getCurrentPosition(
    (position) => {
      locatingCoordinates.value = false
      setCoordinates(position.coords.latitude, position.coords.longitude)
    },
    () => {
      locatingCoordinates.value = false
      locationError.value = 'Allow location access in your browser and try again.'
    },
    { enableHighAccuracy: true, timeout: 15000 },
  )
}

const saveCoordinates = async () => {
  if (!hasSelectedCoordinates.value || savingCoordinates.value) return
  savingCoordinates.value = true
  locationError.value = ''
  locationMessage.value = ''
  try {
    const response = await supplierService.updatePortalCoordinates({
      latitude: Number(locationCoordinates.latitude),
      longitude: Number(locationCoordinates.longitude),
    })
    portal.value = { ...portal.value, ...(response?.data || {}) }
    locationCoordinates.latitude = Number(response?.data?.latitude ?? locationCoordinates.latitude)
    locationCoordinates.longitude = Number(response?.data?.longitude ?? locationCoordinates.longitude)
    locationMessage.value = 'Supplier pickup coordinates saved.'
  } catch (error: any) {
    locationError.value = error?.response?.data?.message || 'Unable to save the supplier coordinates.'
  } finally {
    savingCoordinates.value = false
  }
}

const loadPaymentHistory = async () => {
  const supplierId = Number(supplier.value?.id || portal.value?.supplier_id || 0)
  if (!supplierId) {
    paymentHistoryRows.value = []
    return
  }

  loadingPaymentHistory.value = true
  try {
    const res = await supplierService.getPaymentHistory(supplierId, {
      page: 1,
      per_page: 20,
      sort_by: 'created_at',
      sort_order: 'desc',
    })
    const root = res?.data ?? res
    const rows = Array.isArray(root)
      ? root
      : Array.isArray(root?.data)
        ? root.data
        : Array.isArray(res?.payments)
          ? res.payments
          : []

    paymentHistoryRows.value = rows.map((row: any) => ({
      payer: row?.payer_name || row?.store_name || row?.paid_by_name || row?.created_by_name || 'Store',
      invoice: row?.invoice_number || row?.invoice?.invoice_number || `INV-${row?.invoice_id ?? '-'}`,
      method: row?.payment_method || '-',
      amount: Number(row?.payment_amount ?? row?.amount ?? 0),
      status: row?.status || 'pending',
      created_at: row?.created_at || row?.payment_date || null,
    }))
  } catch (error) {
    paymentHistoryRows.value = []
  } finally {
    loadingPaymentHistory.value = false
  }
}

const toastSuccess = (msg: string) => {
  // lazy import primevue toast if needed later
  console.log(msg)
}
const toastError = (msg: string) => {
  console.warn(msg)
}

const formatMoney = (value: number | string) => {
  return new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value || 0))
}

const formatDateTime = (value: string | null) => {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '-'
  return date.toLocaleString('en-PH', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const paymentStatusSeverity = (status: string) => {
  const normalized = String(status || '').toLowerCase()
  if (normalized === 'paid' || normalized === 'processed' || normalized === 'completed') return 'success'
  if (normalized === 'cancelled' || normalized === 'failed' || normalized === 'rejected') return 'danger'
  if (normalized === 'pending' || normalized === 'pending_approval') return 'warn'
  return 'info'
}

const formatPaymentStatus = (status: string) => String(status || 'pending')
  .replaceAll('_', ' ')
  .replace(/\b\w/g, letter => letter.toUpperCase())

onMounted(async () => {
  if (!isSupplier.value) {
    router.visit('/profile')
    return
  }
  await loadPortal()

  if (isPaymentAccountView.value) {
    await nextTick()
    paymentAccountSectionEl.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    await loadPaymentHistory()
    if (!paymentHistoryPolling.value) {
      paymentHistoryPolling.value = setInterval(() => {
        loadPaymentHistory()
      }, 12000)
    }
  }
})

onBeforeUnmount(() => {
  if (companyLogoPreview.value) URL.revokeObjectURL(companyLogoPreview.value)
  map?.remove()
  map = null
  marker = null
  if (paymentHistoryPolling.value) {
    clearInterval(paymentHistoryPolling.value)
    paymentHistoryPolling.value = null
  }
})
</script>

<style scoped>
.small-pill {
  border-radius: 999px;
  font-size: 0.85rem;
  padding: 0.45rem 0.9rem;
}
</style>
