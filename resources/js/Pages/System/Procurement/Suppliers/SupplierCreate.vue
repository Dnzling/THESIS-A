<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'procurement.suppliers' })" />
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Add Supplier to Store</h2>
        <p class="text-sm text-gray-500 mt-1">Link a verified portal supplier or record an external supplier.</p>
      </div>
    </div>

    <Card>

      <template #content>
        <form class="space-y-6" @submit.prevent="submitForm">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <label class="rounded-xl border p-4 cursor-pointer" :class="mode === 'existing' ? 'border-emerald-400 bg-emerald-50' : 'border-slate-200'">
              <div class="flex items-center gap-2">
                <RadioButton v-model="mode" inputId="mode-existing" value="existing" />
                <span class="font-semibold text-slate-800">Verified Supplier List</span>
              </div>
              <p class="text-xs text-slate-600 mt-2">View verified suppliers and open details before linking.</p>
            </label>

            <label class="rounded-xl border p-4 cursor-pointer" :class="mode === 'invite' ? 'border-blue-400 bg-blue-50' : 'border-slate-200'">
              <div class="flex items-center gap-2">
                <RadioButton v-model="mode" inputId="mode-invite" value="invite" />
                <span class="font-semibold text-slate-800">External Supplier</span>
              </div>
              <p class="text-xs text-slate-600 mt-2">Record an offline supplier without creating a portal account.</p>
            </label>
          </div>

          <div v-if="mode === 'existing'" class="space-y-4">
            <div class="flex items-end gap-2">
              <div class="flex-1">
                <label class="text-sm font-semibold text-gray-700">Search verified suppliers</label>
                <InputText v-model="directorySearch" placeholder="Search by supplier name, email, city, or province" class="w-full" @keyup.enter="loadVerifiedDirectory" />
              </div>
              <Button label="Search" icon="pi pi-search" size="small" :loading="directoryLoading" @click="loadVerifiedDirectory" type="button" />
            </div>

            <div v-if="directoryLoading" class="space-y-3">
              <div v-for="n in 3" :key="n" class="h-28 animate-pulse rounded-xl border border-slate-200 bg-slate-50" />
            </div>
            <div v-else-if="verifiedDirectory.length" class="grid grid-cols-1 gap-3">
              <article v-for="data in verifiedDirectory" :key="data.supplier_portal_id" class="grid grid-cols-1 gap-4 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-orange-200 hover:shadow-sm md:grid-cols-[auto_minmax(0,1fr)_auto] md:items-center">
                <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                  <img v-if="data.logo_url" :src="data.logo_url" :alt="`${data.company_name || data.supplier_name} logo`" class="h-full w-full object-contain" />
                  <i v-else class="pi pi-building text-xl text-slate-400" />
                </div>
                <div class="min-w-0 space-y-2">
                  <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <h3 class="font-semibold text-slate-900">{{ data.company_name || data.supplier_name }}</h3>
                    <div v-if="data.rating !== null" class="flex items-center gap-1.5">
                      <Rating :modelValue="Number(data.rating)" :cancel="false" :readonly="true" />
                      <span class="text-xs font-medium text-slate-700">{{ Number(data.rating).toFixed(1) }}/5</span>
                      <span class="text-xs text-slate-500">({{ data.rating_count }} ratings from other stores)</span>
                    </div>
                    <span v-else class="text-xs text-slate-500">No ratings from other stores yet</span>
                  </div>
                  <p class="text-xs text-slate-600">{{ data.email || '-' }} <span class="mx-1 text-slate-300">·</span> {{ [data.city, data.province].filter(Boolean).join(', ') || 'Address not provided' }}</p>
                  <div v-if="data.products?.length" class="flex flex-wrap gap-1.5">
                    <Tag v-for="product in data.products" :key="product.id" :value="product.name" severity="secondary" :title="product.sku ? `SKU: ${product.sku}` : product.name" />
                  </div>
                  <p v-else class="text-xs text-slate-400">No products listed</p>
                </div>
                <Button class="w-full md:w-auto" size="small" label="View Details" icon="pi pi-arrow-right" iconPos="right" outlined @click="openSupplierShow(data.supplier_portal_id)" />
              </article>
            </div>
            <div v-else class="rounded-xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500">
              No unconnected verified suppliers found.
            </div>
          </div>

          <div v-else>
            <div class="mb-5 flex items-start gap-3 border-b border-slate-200 pb-4">
              <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-orange-50 text-orange-600"><i class="pi pi-building text-lg" /></span>
              <div><h3 class="text-base font-semibold text-slate-900">External Supplier Details</h3><p class="mt-1 text-xs text-slate-500">Add the supplier’s business and pickup details. This creates a record only; no portal account is created.</p></div>
            </div>

            <div class="grid grid-cols-1 gap-x-5 gap-y-4 md:grid-cols-2 xl:grid-cols-3">
              <div class="flex flex-col gap-1.5"><label class="text-xs font-semibold text-slate-700">Supplier Name <span class="text-red-500">*</span></label><InputText v-model="form.supplier_name" class="w-full" size="small" placeholder="Supplier name" /><small v-if="errors.supplier_name" class="text-red-600">{{ errors.supplier_name }}</small></div>
              <div class="flex flex-col gap-1.5"><label class="text-xs font-semibold text-slate-700">Company Name</label><InputText v-model="form.company_name" class="w-full" size="small" placeholder="Registered company name" /></div>
              <div class="flex flex-col gap-1.5"><label class="text-xs font-semibold text-slate-700">Supplier Type <span class="text-red-500">*</span></label><Select v-model="form.supplier_type" :options="supplierTypes" optionLabel="label" optionValue="value" class="w-full" size="small" placeholder="Select type" /><small v-if="errors.supplier_type" class="text-red-600">{{ errors.supplier_type }}</small></div>
              <div class="flex flex-col gap-1.5"><label class="text-xs font-semibold text-slate-700">Contact Person <span class="text-red-500">*</span></label><InputText v-model="form.contact_person" class="w-full" size="small" placeholder="Full name" @blur="validateField('contact_person')" /><small v-if="errors.contact_person" class="text-red-600">{{ errors.contact_person }}</small></div>
              <div class="flex flex-col gap-1.5"><label class="text-xs font-semibold text-slate-700">Phone <span class="text-red-500">*</span></label><InputMask v-model="form.phone" class="w-full" size="small" mask="0999 999 9999" placeholder="0917 123 4567" inputmode="tel" @blur="validateField('phone')" /><small v-if="errors.phone" class="text-red-600">{{ errors.phone }}</small></div>
              <div class="flex flex-col gap-1.5"><label class="text-xs font-semibold text-slate-700">Email <span class="text-red-500">*</span></label><InputText v-model="form.email" type="email" class="w-full" size="small" placeholder="contact@supplier.com" @blur="validateField('email')" /><small v-if="errors.email" class="text-red-600">{{ errors.email }}</small></div>
            </div>

            <div class="mt-6 border-t border-slate-100 pt-5">
              <h4 class="mb-3 text-sm font-semibold text-slate-800">Supplier Address</h4>
              <div class="grid grid-cols-1 gap-x-5 gap-y-4 md:grid-cols-2 xl:grid-cols-3">
                <div class="flex flex-col gap-1.5"><label class="text-xs font-semibold text-slate-700">Province <span class="text-red-500">*</span></label><Select v-model="form.province_id" :options="provinceOptions" optionLabel="label" optionValue="value" class="w-full" size="small" filter placeholder="Select province" :loading="loadingProvinces" @change="onProvinceChange" /><small v-if="errors.province" class="text-red-600">{{ errors.province }}</small></div>
                <div class="flex flex-col gap-1.5"><label class="text-xs font-semibold text-slate-700">City / Municipality <span class="text-red-500">*</span></label><Select v-model="form.city_id" :options="cityOptions" optionLabel="label" optionValue="value" class="w-full" size="small" filter placeholder="Select city" :loading="loadingCities" :disabled="!form.province_id || loadingCities" @change="onCityChange" /><small v-if="errors.city" class="text-red-600">{{ errors.city }}</small></div>
                <div class="flex flex-col gap-1.5"><label class="text-xs font-semibold text-slate-700">Barangay <span class="text-red-500">*</span></label><Select v-model="form.barangay" :options="barangayOptions" optionLabel="label" optionValue="value" class="w-full" size="small" filter placeholder="Select barangay" :loading="loadingBarangays" :disabled="!form.city_id || loadingBarangays" @change="onBarangayChange" /><small v-if="errors.barangay" class="text-red-600">{{ errors.barangay }}</small></div>
                <div class="flex flex-col gap-1.5 md:col-span-2 xl:col-span-3"><label class="text-xs font-semibold text-slate-700">Street Address <span class="text-red-500">*</span></label><InputText v-model="form.address" class="w-full" size="small" placeholder="Building, street, subdivision" /><small v-if="errors.address" class="text-red-600">{{ errors.address }}</small></div>
              </div>
            </div>

            <div class="mt-6 border-t border-slate-100 pt-5">
              <h4 class="text-sm font-semibold text-slate-800">Pickup Location <span class="text-red-500">*</span></h4>
              <p class="mb-3 mt-1 text-xs text-slate-500">Search the address, then click or drag the pin to mark the supplier location.</p>
              <div class="mb-3 flex flex-col gap-2 sm:flex-row"><InputText v-model="mapSearchQuery" class="min-w-0 flex-1" size="small" placeholder="Street, barangay, city, province" @keyup.enter.prevent="searchSupplierLocation" /><Button type="button" label="Find on Map" icon="pi pi-search" severity="warn" size="small" :loading="searchingLocation" @click="searchSupplierLocation" /></div>
              <div ref="mapElement" class="h-72 w-full overflow-hidden rounded-lg border border-slate-200 bg-slate-50" />
              <Message v-if="mapError" severity="error" :closable="false" class="mt-2">{{ mapError }}</Message>
              <small v-if="errors.latitude || errors.longitude" class="mt-2 block text-red-600">Select a pickup location on the map before saving.</small>
              <p v-if="form.latitude !== null && form.longitude !== null" class="mt-2 text-xs text-slate-600">Selected coordinates: {{ form.latitude }}, {{ form.longitude }}</p>
            </div>
          </div>

          <div class="pt-4 flex justify-end gap-2 border-t">
            <Button label="Cancel" severity="secondary" text type="button" @click="router.push({ name: 'procurement.suppliers' })" />
            <Button v-if="mode === 'invite'" label="Save External Supplier" icon="pi pi-check" :loading="saving" type="submit" severity="warn" />
          </div>
        </form>
      </template>
    </Card>

    <Dialog v-model:visible="showInviteConfirmation" modal header="Save external supplier?" :style="{ width: 'min(28rem, 94vw)' }">
      <p class="text-sm leading-6 text-slate-600">
        This adds a supplier record to this store and does not create or link a portal account. A signed contract is required before its contract can be activated.
      </p>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button label="Review Details" severity="secondary" outlined size="small" @click="showInviteConfirmation = false" />
          <Button label="Save External Supplier" severity="warn" icon="pi pi-check" size="small" :loading="saving" @click="confirmInviteSupplier" />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import RadioButton from 'primevue/radiobutton'
import Message from 'primevue/message'
import type { Map as MapboxMap, Marker as MapboxMarker } from 'mapbox-gl'
import 'mapbox-gl/dist/mapbox-gl.css'
import procurementService from '../../../../services/procurement.service'
import ecommerceService from '../../../../services/ecommerce.service'
import { forwardGeocodeMapbox, requireMapboxToken } from '../../../../utils/mapbox'

const router = useRouter()
const toast = useToast()
const saving = ref(false)
const showInviteConfirmation = ref(false)
const mode = ref<'existing' | 'invite'>('existing')
const mapElement = ref<HTMLElement | null>(null)
const mapSearchQuery = ref('')
const mapError = ref('')
const searchingLocation = ref(false)
let supplierMap: MapboxMap | null = null
let supplierMarker: MapboxMarker | null = null
let mapboxgl: typeof import('mapbox-gl').default | null = null
const supplierTypes = [
  { label: 'Manufacturer', value: 'manufacturer' }, { label: 'Wholesaler', value: 'wholesaler' },
  { label: 'Distributor', value: 'distributor' }, { label: 'Importer', value: 'importer' },
  { label: 'Local Artisan', value: 'local_artisan' },
]

const form = reactive({
  supplier_name: '',
  company_name: '',
  contact_person: '',
  email: '',
  phone: '',
  supplier_type: 'wholesaler',
  province_id: '',
  city_id: '',
  province: '',
  city: '',
  barangay: '',
  address: '',
  latitude: null as number | null,
  longitude: null as number | null,
})

const errors = reactive<Record<string, string>>({})
const provinces = ref<any[]>([])
const cities = ref<any[]>([])
const barangays = ref<any[]>([])
const loadingProvinces = ref(false)
const loadingCities = ref(false)
const loadingBarangays = ref(false)
const directoryLoading = ref(false)
const directorySearch = ref('')
const verifiedDirectory = ref<any[]>([])

const itemsFromResponse = (response: any) => {
  const payload = response?.data ?? response ?? []
  return Array.isArray(payload) ? payload : (Array.isArray(payload?.data) ? payload.data : [])
}
const provinceOptions = computed(() => provinces.value.map((item: any) => ({
  label: item.name || item.province_name,
  value: String(item.province_id || item.id || item.code || ''),
})))
const cityOptions = computed(() => cities.value.map((item: any) => ({
  label: item.name || item.city_name,
  value: String(item.city_id || item.id || item.code || ''),
})))
const barangayOptions = computed(() => barangays.value.map((item: any) => ({
  label: item.name || item.barangay_name,
  value: item.name || item.barangay_name,
})))

const loadProvinces = async () => {
  loadingProvinces.value = true
  try {
    provinces.value = itemsFromResponse(await ecommerceService.getProvinces())
  } catch {
    provinces.value = []
    toast.add({ severity: 'error', summary: 'Address list unavailable', detail: 'Could not load provinces. Try refreshing the page.', life: 3500 })
  } finally {
    loadingProvinces.value = false
  }
}

const onProvinceChange = async () => {
  const selected = provinceOptions.value.find(item => item.value === String(form.province_id))
  form.province = selected?.label || ''
  form.city_id = ''
  form.city = ''
  form.barangay = ''
  cities.value = []
  barangays.value = []
  delete errors.province
  delete errors.city
  delete errors.barangay
  if (!form.province_id) return
  loadingCities.value = true
  try {
    cities.value = itemsFromResponse(await ecommerceService.getCities(form.province_id))
  } catch {
    toast.add({ severity: 'error', summary: 'Address list unavailable', detail: 'Could not load cities for this province.', life: 3500 })
  } finally {
    loadingCities.value = false
  }
}

const onCityChange = async () => {
  const selected = cityOptions.value.find(item => item.value === String(form.city_id))
  form.city = selected?.label || ''
  form.barangay = ''
  barangays.value = []
  delete errors.city
  delete errors.barangay
  if (!form.city_id) return
  loadingBarangays.value = true
  try {
    barangays.value = itemsFromResponse(await ecommerceService.getBarangays(form.city_id))
  } catch {
    toast.add({ severity: 'error', summary: 'Address list unavailable', detail: 'Could not load barangays for this city.', life: 3500 })
  } finally {
    loadingBarangays.value = false
  }
}

const onBarangayChange = () => {
  delete errors.barangay
}

const formProgress = computed(() => {
  if (mode.value === 'existing') {
    return 60
  }

  let filled = 0
  const fields = ['supplier_name', 'contact_person', 'phone', 'email', 'province', 'city', 'barangay', 'address', 'latitude', 'longitude']
  fields.forEach((field) => {
    if ((form as any)[field]) filled++
  })
  return Math.round((filled / fields.length) * 100)
})

const clearErrors = () => {
  Object.keys(errors).forEach((key) => delete errors[key])
}

const validateField = (field: 'contact_person' | 'phone' | 'email') => {
  const value = form[field].trim()
  delete errors[field]
  if (!value) {
    errors[field] = `${field === 'contact_person' ? 'Contact person' : field === 'phone' ? 'Phone' : 'Email'} is required`
    return false
  }
  if (field === 'contact_person' && !/^[\p{L}\p{M}][\p{L}\p{M}\s.'’\-]*$/u.test(value)) {
    errors.contact_person = 'Enter a valid name using letters, spaces, apostrophes, or hyphens'
  } else if (field === 'phone') {
    const digits = value.replace(/\D/g, '')
    if (!/^09\d{9}$/.test(digits)) errors.phone = 'Enter a valid Philippine mobile number with 11 digits'
  } else if (field === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
    errors.email = 'Enter a valid email address'
  }
  return !errors[field]
}

const validateForm = (): boolean => {
  clearErrors()

  if (!form.supplier_name?.trim()) errors.supplier_name = 'Supplier name is required'
  validateField('contact_person')
  validateField('phone')
  validateField('email')
  if (!form.supplier_type) errors.supplier_type = 'Supplier type is required'
  for (const field of ['province', 'city', 'barangay', 'address']) {
    if (!(form as any)[field]?.trim()) errors[field] = `${field[0].toUpperCase()}${field.slice(1)} is required`
  }
  if (form.latitude === null || form.latitude < -90 || form.latitude > 90) errors.latitude = 'Valid map pin latitude is required'
  if (form.longitude === null || form.longitude < -180 || form.longitude > 180) errors.longitude = 'Valid map pin longitude is required'
  return Object.keys(errors).length === 0
}

const setSupplierLocation = (latitude: number, longitude: number) => {
  form.latitude = Number(latitude.toFixed(7))
  form.longitude = Number(longitude.toFixed(7))
  delete errors.latitude
  delete errors.longitude
  const point: [number, number] = [form.longitude, form.latitude]
  if (!supplierMap || !mapboxgl) return
  if (!supplierMarker) {
    supplierMarker = new mapboxgl.Marker({ draggable: true, color: '#f97316' }).setLngLat(point).addTo(supplierMap)
    supplierMarker.on('dragend', () => {
      const moved = supplierMarker?.getLngLat()
      if (moved) setSupplierLocation(moved.lat, moved.lng)
    })
  } else supplierMarker.setLngLat(point)
  supplierMap.easeTo({ center: point, zoom: 15 })
}

const initSupplierMap = async () => {
  if (!mapElement.value || supplierMap) return
  try {
    const module = await import('mapbox-gl')
    mapboxgl = module.default
    mapboxgl.accessToken = requireMapboxToken()
    supplierMap = new mapboxgl.Map({ container: mapElement.value, style: 'mapbox://styles/mapbox/streets-v12', center: [120.85, 14.2], zoom: 8 })
    supplierMap.on('load', () => supplierMap?.resize())
    supplierMap.on('click', event => setSupplierLocation(event.lngLat.lat, event.lngLat.lng))
    supplierMap.on('error', event => { mapError.value = event.error?.message || 'The supplier map could not be loaded.' })
  } catch (error: any) {
    mapError.value = error?.message || 'Unable to load the supplier map.'
  }
}

const searchSupplierLocation = async () => {
  const query = [mapSearchQuery.value, form.address, form.barangay, form.city, form.province, 'Philippines'].filter(Boolean).join(', ')
  searchingLocation.value = true
  try {
    const result = await forwardGeocodeMapbox(query)
    if (!result) {
      toast.add({ severity: 'warn', summary: 'Location not found', detail: 'Add more address details and try again.', life: 3000 })
      return
    }
    setSupplierLocation(result.latitude, result.longitude)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Map search failed', detail: error?.message || 'Unable to search the address.', life: 3500 })
  } finally {
    searchingLocation.value = false
  }
}

const openSupplierShow = (portalId: number) => {
  router.push({ name: 'procurement.suppliers.verified.show', params: { portalId } })
}

const loadVerifiedDirectory = async () => {
  directoryLoading.value = true
  try {
    const response = await procurementService.getVerifiedSupplierDirectory({
      search: directorySearch.value,
      available_only: true,
      limit: 300,
    })

    const payload = response?.data ?? response ?? {}
    const list = payload?.data ?? payload ?? []
    verifiedDirectory.value = Array.isArray(list) ? list : []
  } catch (error) {
    verifiedDirectory.value = []
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load verified supplier directory',
      life: 3000,
    })
  } finally {
    directoryLoading.value = false
  }
}

const submitForm = async () => {
  if (mode.value !== 'invite') return

  if (!validateForm()) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please fix all errors before submitting',
      life: 3000,
    })
    return
  }

  showInviteConfirmation.value = true
}

const confirmInviteSupplier = async () => {
  saving.value = true
  try {
    const payload = new FormData()
    Object.entries(form).forEach(([key, value]) => {
      if (value !== null && value !== '') payload.append(key, String(value))
    })
    await procurementService.createSupplier(payload)
    showInviteConfirmation.value = false

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'External supplier added. No portal account was created.',
      life: 3000,
    })

    setTimeout(() => {
      router.push({ name: 'procurement.suppliers' })
    }, 1000)
  } catch (error: any) {
    showInviteConfirmation.value = false
    const errorMessage = error.response?.data?.message || 'Failed to save supplier'
    const errorDetails = error.response?.data?.errors

    if (errorDetails) {
      Object.entries(errorDetails).forEach(([key, messages]: [string, any]) => {
        errors[key] = Array.isArray(messages) ? messages[0] : messages
      })
    }

    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: errorMessage,
      life: 4000,
    })
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadVerifiedDirectory()
  loadProvinces()
})

watch(mode, async value => {
  if (value === 'invite') {
    await nextTick()
    await initSupplierMap()
    supplierMap?.resize()
  } else {
    supplierMarker?.remove()
    supplierMarker = null
    supplierMap?.remove()
    supplierMap = null
  }
})

onBeforeUnmount(() => {
  supplierMarker?.remove()
  supplierMap?.remove()
})
</script>
