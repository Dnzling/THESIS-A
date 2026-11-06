<template>
  <div class="min-h-screen bg-gradient-to-b from-white via-orange-50/20 to-slate-50 px-4 py-10 text-slate-900">
    <Dialog v-model:visible="welcomeVisible" modal :closable="false" :dismissableMask="false" :draggable="false" class="w-[min(92vw,460px)]">
      <div class="py-5 text-center">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-orange-100 text-orange-600"><i class="pi pi-shop text-2xl"></i></div>
        <div class="portal-brand text-2xl text-orange-500">FURNISYNC</div>
        <h2 class="mt-3 text-xl font-semibold text-slate-900">Welcome to FurniSync</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Set up your store, then choose the plan that fits your business. Your one-month free trial starts after you select a plan.</p>
        <Button label="Set Up My Store" severity="warn" class="mt-6 w-full" @click="welcomeVisible = false" />
      </div>
    </Dialog>
    <div class="mx-auto max-w-4xl">
      <div class="mb-8 text-center">
        
        <h1 class="mt-4 text-3xl font-bold sm:text-4xl">Create your store</h1>
        <p class="mt-3 text-slate-600">Create your store first. You can choose a plan and start its one-month free trial next.</p>
      </div>

      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60">
        <div class="mb-5 rounded-xl bg-orange-50 px-4 py-3 text-sm text-orange-800">Your store code is generated automatically. After setup, choose a plan to begin its one-month free trial. No payment is collected during the trial.</div>
        <div class="grid gap-5 md:grid-cols-2">
          <div class="md:col-span-2">
            <h2 class="mb-3 text-base font-semibold text-slate-800">Store information</h2>
            <label class="mb-2 block text-sm font-medium text-slate-700">Store Name *</label>
            <input v-model="form.store_name" type="text" class="field" placeholder="e.g. FurniSync Main Store" data-testid="store-name" />
          </div>

          <div class="md:col-span-2 mt-2 border-t border-slate-100 pt-4 text-base font-semibold text-slate-800">Business information</div>
          <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-medium text-slate-700">Business Type *</label>
            <Select v-model="form.business_type" :options="businessTypeOptions" rounded optionLabel="label" optionValue="value" class="w-full" data-testid="business-type" />
          </div>

          <div class="md:col-span-2 mt-2 border-t border-slate-100 pt-4 text-base font-semibold text-slate-800">Contact information</div>
          <div><label class="mb-2 block text-sm font-medium text-slate-700">Contact Person</label><input :value="contactPerson" type="text" class="field bg-slate-100" disabled data-testid="contact-person" /></div>
          <div><label class="mb-2 block text-sm font-medium text-slate-700">Contact Email</label><input :value="(authStore.currentUser as any)?.email || ''" type="email" class="field bg-slate-100" disabled /></div>

          <div class="md:col-span-2 mt-2 border-t border-slate-100 pt-4 text-base font-semibold text-slate-800">Address</div>
          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Province</label>
            <input value="Cavite" type="text" class="field text-slate-600 bg-slate-100" disabled />
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">City *</label>
            <Select
              v-model="form.city_id"
              :options="cityOptions"
              optionLabel="label"
              optionValue="value"
              class="w-full"
              filter
              :loading="citiesLoading"
              :disabled="!provinceId"
              placeholder="Select City"
              data-testid="city-select"
            />
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Barangay *</label>
            <Select
              v-model="form.barangay"
              :options="barangayOptions"
              optionLabel="label"
              optionValue="value"
              class="w-full"
              filter
              :loading="barangaysLoading"
              :disabled="!form.city_id"
              data-testid="barangay"
              placeholder="Select barangay"
            />
          </div>

          <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-medium text-slate-700">Address</label>
            <Textarea v-model="form.address" rows="3" class="w-full" placeholder="Street, building, landmark" data-testid="address" />
          </div>

          <div class="md:col-span-2">
            <div class="mb-2 flex flex-wrap items-end justify-between gap-2">
              <div>
                <label class="block text-sm font-semibold text-slate-800">Store location *</label>
                <p class="mt-1 text-xs text-slate-500">Click the map or drag the pin to your store. Only locations inside Cavite are allowed.</p>
              </div>
              <span v-if="form.latitude !== null && form.longitude !== null" class="text-xs text-slate-500">
                {{ form.latitude.toFixed(6) }}, {{ form.longitude.toFixed(6) }}
              </span>
            </div>
            <div class="mb-2 flex flex-col gap-2 sm:flex-row">
              <InputText v-model="locationSearch" class="min-w-0 flex-1" placeholder="Search your store address in Cavite" @keydown.enter.prevent="searchStoreLocation" />
              <Button label="Find location" icon="pi pi-search" severity="secondary" outlined :loading="searchingLocation" @click="searchStoreLocation" />
            </div>
            <div ref="mapEl" class="h-72 w-full overflow-hidden rounded-xl border border-slate-200 bg-slate-100" role="application" aria-label="Map for choosing store location" />
            <p v-if="mapError || pinError" class="mt-2 text-sm text-red-600" role="alert">{{ mapError || pinError }}</p>
            <p v-else-if="pinStatus === 'checking'" class="mt-2 text-sm text-slate-500">Checking the pin location...</p>
            <p v-else-if="pinStatus === 'valid'" class="mt-2 text-sm text-green-700"><i class="pi pi-check-circle mr-1" />Location confirmed inside Cavite.</p>
            <p v-else class="mt-2 text-sm text-slate-500">Select and confirm a pin before creating your store.</p>
          </div>
        </div>

        <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600"><div class="font-semibold text-slate-800">Review & create</div><p class="mt-1">{{ form.store_name || 'Your store' }} · {{ form.business_type }} · {{ form.address || 'Address not entered' }}</p></div>

        <div v-if="errorMessage" class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
          {{ errorMessage }}
        </div>

        <div class="mt-6 flex flex-wrap justify-end gap-3">
          <Button label="Create Store & Choose a Plan" severity="warn" :loading="submitting" :disabled="pinStatus !== 'valid'" @click="submitStore" data-testid="save-store" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import axiosClient from '@/axios'
import ecommerceService from '@/services/ecommerce.service'
import { useAuthStore } from '@/stores/auth'
import { forwardGeocodeMapbox, requireMapboxToken } from '@/utils/mapbox'
import type { Map as MapboxMap, Marker as MapboxMarker } from 'mapbox-gl'
import 'mapbox-gl/dist/mapbox-gl.css'

const authStore = useAuthStore()
const submitting = ref(false)
const welcomeVisible = ref(true)
const errorMessage = ref('')
const citiesLoading = ref(false)
const barangaysLoading = ref(false)
const provinceId = ref<string>('')
const cities = ref<any[]>([])
const barangays = ref<any[]>([])
const mapEl = ref<HTMLDivElement | null>(null)
const mapError = ref('')
const pinError = ref('')
const pinStatus = ref<'none' | 'checking' | 'valid' | 'invalid'>('none')
const locationSearch = ref('')
const searchingLocation = ref(false)
let map: MapboxMap | null = null
let marker: MapboxMarker | null = null
let mapboxLibrary: typeof import('mapbox-gl').default | null = null
let locationRequest = 0
let mapResizeTimer: ReturnType<typeof setTimeout> | null = null

const businessTypeOptions = [
  { label: 'Retail', value: 'retail' },
  { label: 'Enterprise', value: 'enterprise' },
  { label: 'Showroom', value: 'showroom' },
  { label: 'Wholesale', value: 'wholesale' },
  { label: 'Online Store', value: 'online_store' },

]

const form = ref({
  store_name: '',
  business_type: 'retail',
  city_id: '',
  barangay: '',
  address: '',
  latitude: null as number | null,
  longitude: null as number | null,
})

const selectPin = async (latitude: number, longitude: number) => {
  const requestId = ++locationRequest
  form.value.latitude = Number(latitude.toFixed(7))
  form.value.longitude = Number(longitude.toFixed(7))
  pinStatus.value = 'checking'
  pinError.value = ''
  const activeMap = map
  if (activeMap && !activeMap.loaded()) {
    await new Promise<void>((resolve) => activeMap.once('load', () => resolve()))
  }
  if (requestId !== locationRequest || !activeMap || map !== activeMap) return

  const coordinates: [number, number] = [longitude, latitude]
  if (!marker && mapboxLibrary) {
    marker = new mapboxLibrary.Marker({ color: '#f97316', draggable: true })
      .setLngLat(coordinates)
      .addTo(activeMap)
    marker.on('dragend', () => {
      const position = marker?.getLngLat()
      if (position) void selectPin(position.lat, position.lng)
    })
  } else {
    marker?.setLngLat(coordinates)
  }

  try {
    const response = await axiosClient.post('/api/stores/validate-location', {
      latitude: form.value.latitude,
      longitude: form.value.longitude,
    }, { headers: { 'X-Suppress-Dialog': '1' } })
    if (requestId !== locationRequest) return
    pinStatus.value = response.data?.valid ? 'valid' : 'invalid'
    if (!response.data?.valid) pinError.value = response.data?.message || 'Place the store pin inside Cavite province.'
  } catch (error: any) {
    if (requestId !== locationRequest) return
    pinStatus.value = 'invalid'
    pinError.value = error?.response?.data?.message || 'Could not verify the pin. Please try again.'
  }
}

const initMap = async () => {
  if (!mapEl.value || map) return
  try {
    const mapboxgl = (await import('mapbox-gl')).default
    mapboxLibrary = mapboxgl
    mapboxgl.accessToken = requireMapboxToken()
    map = new mapboxgl.Map({
      container: mapEl.value,
      style: 'mapbox://styles/mapbox/streets-v12',
      center: [120.95, 14.35],
      zoom: 10,
    })
    map.on('click', (event) => void selectPin(event.lngLat.lat, event.lngLat.lng))
    map.on('load', () => map?.resize())
  } catch (error: any) {
    mapError.value = error?.message || 'Unable to load the location map.'
  }
}

const searchStoreLocation = async () => {
  if (!locationSearch.value.trim()) return
  searchingLocation.value = true
  pinError.value = ''
  try {
    const result = await forwardGeocodeMapbox(`${locationSearch.value.trim()}, Cavite, Philippines`)
    if (!result) throw new Error('Location not found. Try a more specific address in Cavite.')
    if (!map) throw new Error('The map is not ready. Please try again.')
    map.flyTo({ center: [result.longitude, result.latitude], zoom: 15 })
    await selectPin(result.latitude, result.longitude)
  } catch (error: any) {
    pinError.value = error?.message || 'Unable to find that address.'
  } finally {
    searchingLocation.value = false
  }
}

const contactPerson = computed(() => {
  const user = authStore.currentUser as any
  return [user?.first_name, user?.last_name].filter(Boolean).join(' ') || user?.name || 'User'
})

const cityOptions = computed(() => cities.value.map((city: any) => ({
  label: city.name || city.city_name || 'City',
  value: String(city.city_id || city.id || city.code || ''),
})))

const barangayOptions = computed(() => barangays.value.map((barangay: any) => ({
  label: barangay.name || barangay.barangay_name || 'Barangay',
  value: String(barangay.code || barangay.psgc_id || barangay.id || barangay.name || barangay.barangay_name || ''),
})))

const loadCities = async () => {
  citiesLoading.value = true
  try {
    const provinces = await ecommerceService.getProvinces()
    const provinceItems = Array.isArray(provinces.data?.data)
      ? provinces.data.data
      : Array.isArray(provinces.data)
        ? provinces.data
        : []

    const caviteProvince = provinceItems.find((p: any) => String(p.name).trim().toLowerCase() === 'cavite')
    provinceId.value = String(caviteProvince?.province_id || caviteProvince?.id || '')

    if (!provinceId.value) {
      cities.value = []
      return
    }

    const response = await ecommerceService.getCities(provinceId.value)
    cities.value = Array.isArray(response.data?.data)
      ? response.data.data
      : Array.isArray(response.data)
        ? response.data
        : []
  } catch (_error) {
    cities.value = []
  } finally {
    citiesLoading.value = false
  }
}

const loadBarangays = async (cityId: string | number) => {
  barangaysLoading.value = true
  try {
    const response = await ecommerceService.getBarangays(String(cityId))
    barangays.value = Array.isArray(response.data?.data)
      ? response.data.data
      : Array.isArray(response.data)
        ? response.data
        : []
  } catch (_error) {
    barangays.value = []
  } finally {
    barangaysLoading.value = false
  }
}

const submitStore = async () => {
  errorMessage.value = ''
  if (!form.value.store_name.trim()) {
    errorMessage.value = 'Store name is required.'
    return
  }
  if (!form.value.city_id) {
    errorMessage.value = 'City is required.'
    return
  }
  if (!form.value.barangay.trim()) {
    errorMessage.value = 'Barangay is required.'
    return
  }
  if (!form.value.address.trim()) {
    errorMessage.value = 'Store address is required.'
    return
  }
  if (pinStatus.value !== 'valid' || form.value.latitude === null || form.value.longitude === null) {
    errorMessage.value = 'Choose a store pin inside Cavite and wait for it to be confirmed.'
    return
  }
  
  submitting.value = true
  try {
    const selectedCity = cities.value.find((item: any) => String(item.city_id || item.id || item.code) === String(form.value.city_id))
    const selectedBarangay = barangays.value.find((item: any) => String(item.code || item.psgc_id || item.id || item.name || item.barangay_name || '') === String(form.value.barangay))
    const payload = {
      store_name: form.value.store_name,
      contact_person: contactPerson.value,
      business_type: form.value.business_type,
      province: 'Cavite',
      city: selectedCity?.name || selectedCity?.city_name || '',
      barangay: selectedBarangay?.name || selectedBarangay?.barangay_name || '',
      address: form.value.address,
      latitude: form.value.latitude,
      longitude: form.value.longitude,
      email: (authStore.currentUser as any)?.email || null,
      contact_number: (authStore.currentUser as any)?.contact_number || null,
    }

    const response = await axiosClient.post('/api/stores/register', payload)
    const storeId = response?.data?.store?.store_id || response?.data?.data?.id
    if (!storeId) {
      throw new Error(response?.data?.message || 'Unable to create store.')
    }

    await authStore.fetchCurrentUser({ reloadPermissions: true })
    router.visit(`/subscription-plans?store_id=${storeId}`)
  } catch (error: any) {
    const fields = error?.response?.data?.errors
    errorMessage.value = fields ? String(Object.values(fields).flat()[0]) : (error?.response?.data?.message || error?.message || 'Unable to save store.')
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  await authStore.fetchCurrentUser()
  if (!(authStore.currentUser as any)?.store_id) {
    await loadCities()
  } else {
    const user = authStore.currentUser as any
    router.visit(user?.store?.subscription_status === 'pending'
      ? `/subscription-plans?store_id=${user.store_id}`
      : '/store/index')
  }
})

watch(welcomeVisible, async (visible) => {
  if (visible || (authStore.currentUser as any)?.store_id) return
  await nextTick()
  await initMap()
  if (mapResizeTimer) clearTimeout(mapResizeTimer)
  mapResizeTimer = setTimeout(() => {
    map?.resize()
    map?.triggerRepaint()
  }, 250)
})

onBeforeUnmount(() => {
  locationRequest++
  if (mapResizeTimer) clearTimeout(mapResizeTimer)
  marker?.remove()
  map?.remove()
  marker = null
  map = null
  mapboxLibrary = null
})

watch(
  () => form.value.city_id,
  async (cityId) => {
    form.value.barangay = ''
    barangays.value = []
    if (!cityId) return
    await loadBarangays(cityId)
  }
)
</script>

<style scoped>
.field {
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 0.75rem;
  padding: 0.7rem 0.9rem;
  background: #fff;
}

.portal-brand { font-family: 'Barabara', sans-serif; }
</style>
