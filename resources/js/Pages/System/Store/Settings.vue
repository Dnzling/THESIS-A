<template>
  <div class="space-y-5">
    <Toast />
    <header><p class="text-xs font-semibold uppercase tracking-wider text-orange-600">Store Settings</p><h1 class="text-2xl font-semibold text-slate-900">Branch Information</h1><p class="mt-1 text-sm text-slate-500">Manage how this branch is identified and located.</p></header>

    <div v-if="loading" class="grid gap-4 lg:grid-cols-3"><Skeleton v-for="item in 3" :key="item" height="18rem" borderRadius="14px" /></div>
    <Message v-else-if="error" severity="error" :closable="false">{{ error }}</Message>

    <form v-else class="grid gap-4 xl:grid-cols-3" @submit.prevent="save">
      <Card class="border border-slate-200 shadow-sm xl:col-span-2">
        <template #title><span class="text-base">Store Information</span></template>
        <template #content>
          <div class="grid gap-4 sm:grid-cols-2">
            <Field label="Store Name" required><InputText v-model="form.name" class="w-full" /></Field>
            <Field label="Branch Code"><InputText :modelValue="form.branch_code" class="w-full" disabled /></Field>
            <Field label="Contact Number" required><InputText v-model="form.contact_number" class="w-full" /></Field>
            <Field label="Email Address"><InputText v-model="form.email" type="email" class="w-full" /></Field>
            <Field label="Address" required class="sm:col-span-2"><InputText v-model="form.address" class="w-full" /></Field>
            <Field label="City" required>
              <Select v-model="cityId" :options="cityOptions" optionLabel="label" optionValue="value" filter class="w-full" placeholder="Select city" :loading="loadingCities" :disabled="!provinceId" @change="onCityChange" />
            </Field>
            <Field label="Barangay">
              <Select v-model="form.barangay" :options="barangayOptions" optionLabel="label" optionValue="value" filter class="w-full" placeholder="Select barangay" :loading="loadingBarangays" :disabled="!cityId" />
            </Field>
            <Field label="Province" required><InputText v-model="form.province" class="w-full" readonly /></Field>
          </div>
        </template>
      </Card>

      <Card class="border border-slate-200 shadow-sm">
        <template #title><span class="text-base">Store Logo</span></template>
        <template #content>
          <div class="mx-auto flex aspect-square max-w-64 items-center justify-center overflow-hidden rounded-2xl border border-dashed border-slate-300 bg-slate-50">
            <img v-if="logoPreview || form.logo_url" :src="logoPreview || form.logo_url" alt="Store logo" class="h-full w-full object-contain p-3" />
            <div v-else class="text-center text-slate-400"><i class="pi pi-image text-4xl"></i><p class="mt-2 text-xs">No logo uploaded</p></div>
          </div>
          <FileUpload mode="basic" accept="image/png,image/jpeg,image/webp" :maxFileSize="4194304" chooseLabel="Choose Logo" class="mt-4 w-full" customUpload @select="selectLogo" />
          <p class="mt-2 text-xs text-slate-500">PNG, JPG, or WebP up to 4 MB.</p>
        </template>
      </Card>

      <Card class="border border-slate-200 shadow-sm xl:col-span-3">
        <template #title><div class="flex flex-wrap items-center justify-between gap-2"><span class="text-base">Geolocation</span><Button type="button" label="Use Current Location" icon="pi pi-map-marker" size="small" outlined :loading="locating" @click="useCurrentLocation" /></div></template>
        <template #content>
          <div class="grid gap-5 sm:grid-cols-2">
            <Field label="Geofence Radius">
              <div class="flex items-center gap-3">
                <Slider v-model="form.geofence_radius_m" :min="0" :max="5000" :step="10" class="flex-1" />
                <span class="min-w-16 text-right text-sm font-medium text-slate-700">{{ Number(form.geofence_radius_m) || 0 }} m</span>
              </div>
            </Field>
            <div class="flex items-end pb-1"><div class="flex items-center gap-3"><ToggleSwitch v-model="form.geofence_enabled" /><span class="text-sm text-slate-700">Enable geofence</span></div></div>
          </div>
          <div class="mt-4 space-y-2">
            <div class="flex gap-2">
              <InputText v-model="searchQuery" class="flex-1" placeholder="Search an address or place" @keyup.enter.prevent="searchLocation" />
              <Button type="button" label="Search" icon="pi pi-search" :loading="searching" @click="searchLocation" />
            </div>
            <div ref="mapContainer" class="h-80 w-full overflow-hidden rounded-xl border border-slate-200"></div>
            <small class="text-xs text-slate-500">Click the map or drag the marker to set the store location.</small>
            <Message v-if="mapError" severity="error" :closable="false">{{ mapError }}</Message>
          </div>
        </template>
      </Card>

      <div class="flex justify-end border-t border-slate-200 pt-4 xl:col-span-3"><Button type="submit" label="Save Store Information" icon="pi pi-check" :loading="saving" /></div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import Button from 'primevue/button'; import Card from 'primevue/card'; import FileUpload from 'primevue/fileupload'; import InputText from 'primevue/inputtext'; import Message from 'primevue/message'; import Select from 'primevue/select'; import Skeleton from 'primevue/skeleton'; import Slider from 'primevue/slider'; import Toast from 'primevue/toast'; import ToggleSwitch from 'primevue/toggleswitch'
import { useToast } from 'primevue/usetoast'
import { storeModuleService } from '@/services/store-module.service'
import ecommerceService from '@/services/ecommerce.service'
import { forwardGeocodeMapbox, requireMapboxToken } from '@/utils/mapbox'
import type { GeoJSONSource, Map as MapboxMap, Marker as MapboxMarker } from 'mapbox-gl'
import 'mapbox-gl/dist/mapbox-gl.css'

const Field = defineComponent({ props: { label: { type: String, required: true }, required: Boolean }, setup: (props, { slots, attrs }) => () => h('label', { class: ['block', attrs.class] }, [h('span', { class: 'mb-1.5 block text-sm font-medium text-slate-700' }, `${props.label}${props.required ? ' *' : ''}`), slots.default?.()]) })
const toast = useToast(); const loading = ref(false); const saving = ref(false); const locating = ref(false); const error = ref(''); const logoFile = ref<File | null>(null); const logoPreview = ref('')
const form = reactive<any>({ name: '', branch_code: '', contact_number: '', email: '', address: '', barangay: '', city: '', province: 'Cavite', latitude: null, longitude: null, geofence_enabled: false, geofence_radius_m: 100, logo_url: '' })
const provinceId = ref(''); const cityId = ref(''); const provinces = ref<any[]>([]); const cities = ref<any[]>([]); const barangays = ref<any[]>([])
const loadingCities = ref(false); const loadingBarangays = ref(false); const searchQuery = ref(''); const searching = ref(false); const mapError = ref(''); const mapContainer = ref<HTMLElement | null>(null)
const normalize = (value: unknown) => String(value ?? '').trim().toLowerCase()
const cityOptions = computed(() => cities.value.map((item: any) => ({ label: item.name || item.city_name, value: String(item.city_id || item.code || item.id || '') })))
const barangayOptions = computed(() => {
  const options = barangays.value.map((item: any) => ({ label: item.name || item.barangay_name, value: item.name || item.barangay_name }))
  if (form.barangay && !options.some((item) => normalize(item.value) === normalize(form.barangay))) options.unshift({ label: form.barangay, value: form.barangay })
  return options
})
let map: MapboxMap | null = null; let marker: MapboxMarker | null = null; let mapboxgl: typeof import('mapbox-gl').default | null = null
const applyData = (value: any) => Object.assign(form, value, { latitude: value.latitude === null ? null : Number(value.latitude), longitude: value.longitude === null ? null : Number(value.longitude) })
const responseItems = (response: any) => { const data = response?.data?.data ?? response?.data ?? []; return Array.isArray(data) ? data : [] }
const loadAddresses = async () => {
  provinces.value = responseItems(await ecommerceService.getProvinces())
  const province = provinces.value.find((item: any) => normalize(item.name || item.province_name) === normalize(form.province)) || provinces.value.find((item: any) => normalize(item.name || item.province_name) === 'cavite')
  provinceId.value = String(province?.province_id || province?.code || province?.id || '')
  if (!provinceId.value) return
  loadingCities.value = true
  try { cities.value = responseItems(await ecommerceService.getCities(provinceId.value)) } finally { loadingCities.value = false }
  const city = cities.value.find((item: any) => normalize(item.name || item.city_name) === normalize(form.city))
  cityId.value = String(city?.city_id || city?.code || city?.id || '')
  if (cityId.value) await loadBarangays(cityId.value)
}
const loadBarangays = async (id: string) => { loadingBarangays.value = true; try { barangays.value = responseItems(await ecommerceService.getBarangays(id)) } finally { loadingBarangays.value = false } }
const onCityChange = async () => { const selected = cityOptions.value.find((item) => item.value === cityId.value); form.city = selected?.label || ''; form.barangay = ''; barangays.value = []; if (cityId.value) await loadBarangays(cityId.value) }
const load = async () => { loading.value = true; error.value = ''; try { applyData((await storeModuleService.getSettings()).data); await loadAddresses() } catch (err: any) { error.value = err?.response?.data?.message || 'Unable to load store settings.' } finally { loading.value = false; await nextTick(); await initMap() } }
const selectLogo = (event: any) => { const file = event.files?.[0]; if (!file) return; logoFile.value = file; if (logoPreview.value) URL.revokeObjectURL(logoPreview.value); logoPreview.value = URL.createObjectURL(file) }
const useCurrentLocation = () => { if (!navigator.geolocation) return toast.add({ severity: 'warn', summary: 'Location unavailable', detail: 'This browser does not support geolocation.', life: 3000 }); locating.value = true; navigator.geolocation.getCurrentPosition(position => { form.latitude = position.coords.latitude; form.longitude = position.coords.longitude; locating.value = false; redrawMap(true) }, () => { locating.value = false; toast.add({ severity: 'error', summary: 'Location unavailable', detail: 'Allow location access and try again.', life: 3500 }) }, { enableHighAccuracy: true, timeout: 15000 }) }
const geofenceData = () => {
  const lat = Number(form.latitude), lng = Number(form.longitude), radius = Number(form.geofence_radius_m)
  if (form.latitude === null || form.latitude === '' || form.longitude === null || form.longitude === '' || !Number.isFinite(lat) || !Number.isFinite(lng) || radius <= 0) return { type: 'FeatureCollection' as const, features: [] }
  const ring: number[][] = [], angular = radius / 6371008.8, latRad = lat * Math.PI / 180, lngRad = lng * Math.PI / 180
  for (let step = 0; step <= 64; step++) { const bearing = step / 64 * Math.PI * 2; const pointLat = Math.asin(Math.sin(latRad) * Math.cos(angular) + Math.cos(latRad) * Math.sin(angular) * Math.cos(bearing)); const pointLng = lngRad + Math.atan2(Math.sin(bearing) * Math.sin(angular) * Math.cos(latRad), Math.cos(angular) - Math.sin(latRad) * Math.sin(pointLat)); ring.push([pointLng * 180 / Math.PI, pointLat * 180 / Math.PI]) }
  return { type: 'Feature' as const, properties: {}, geometry: { type: 'Polygon' as const, coordinates: [ring] } }
}
const redrawMap = (recenter = false) => {
  if (!map || !mapboxgl) return
  const lat = Number(form.latitude) || 14.2794, lng = Number(form.longitude) || 120.8786
  if (!marker) {
    marker = new mapboxgl.Marker({ draggable: true }).setLngLat([lng, lat]).addTo(map)
    marker.on('dragend', () => { const point = marker!.getLngLat(); form.latitude = point.lat; form.longitude = point.lng })
  } else {
    marker.setLngLat([lng, lat])
  }
  ;(map.getSource('store-geofence') as GeoJSONSource | undefined)?.setData(geofenceData())
  if (recenter) map.easeTo({ center: [lng, lat], zoom: 14 })
  setTimeout(() => map?.resize(), 100)
}
const initMap = async () => { if (!mapContainer.value || map) return; try { mapError.value = ''; mapboxgl = (await import('mapbox-gl')).default; mapboxgl.accessToken = requireMapboxToken(); map = new mapboxgl.Map({ container: mapContainer.value, style: 'mapbox://styles/mapbox/streets-v12', center: [Number(form.longitude) || 120.8786, Number(form.latitude) || 14.2794], zoom: 14 }); map.on('load', () => { map!.addSource('store-geofence', { type: 'geojson', data: geofenceData() }); map!.addLayer({ id: 'store-geofence-fill', type: 'fill', source: 'store-geofence', paint: { 'fill-color': '#f97316', 'fill-opacity': 0.16 } }); map!.addLayer({ id: 'store-geofence-outline', type: 'line', source: 'store-geofence', paint: { 'line-color': '#f97316', 'line-width': 2 } }); redrawMap(true) }); map.on('click', event => { form.latitude = event.lngLat.lat; form.longitude = event.lngLat.lng }); map.on('error', event => { mapError.value = event.error?.message || 'Mapbox could not load the map.' }) } catch (err: any) { mapError.value = err?.message || 'Unable to load the Mapbox map.' } }
const searchLocation = async () => { if (!searchQuery.value.trim()) return; searching.value = true; try { const result = await forwardGeocodeMapbox(searchQuery.value.trim()); if (!result) return toast.add({ severity: 'warn', summary: 'Location not found', detail: 'Try a more specific address.', life: 3000 }); form.latitude = result.latitude; form.longitude = result.longitude; if (result.address) form.address = result.address; redrawMap(true) } catch (err: any) { toast.add({ severity: 'error', summary: 'Mapbox search failed', detail: err?.message || 'Unable to search this location.', life: 4000 }) } finally { searching.value = false } }
const save = async () => { saving.value = true; try { const payload = new FormData(); ['name', 'branch_code', 'contact_number', 'email', 'address', 'barangay', 'city', 'province', 'latitude', 'longitude', 'geofence_radius_m'].forEach(key => payload.append(key, form[key] ?? '')); payload.append('geofence_enabled', form.geofence_enabled ? '1' : '0'); if (logoFile.value) payload.append('logo', logoFile.value); const response = await storeModuleService.updateSettings(payload); applyData(response.data); logoFile.value = null; if (logoPreview.value) URL.revokeObjectURL(logoPreview.value); logoPreview.value = ''; toast.add({ severity: 'success', summary: 'Store updated', detail: response.message, life: 3000 }) } catch (err: any) { toast.add({ severity: 'error', summary: 'Unable to save', detail: err?.response?.data?.message || 'Check the information and try again.', life: 4000 }) } finally { saving.value = false } }
watch(() => [form.latitude, form.longitude, form.geofence_radius_m], redrawMap)
onMounted(load); onBeforeUnmount(() => { map?.remove(); map = null; if (logoPreview.value) URL.revokeObjectURL(logoPreview.value) })
</script>
