<template>
  <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" severity="secondary" text rounded
        @click="router.push({ name: 'inventory.suppliers' })" />
      <div>
        <h1 class="text-lg font-bold text-gray-800">{{ isEditing ? 'Edit Supplier' : 'Add Supplier' }}</h1>
        <p class="mt-1 text-xs text-gray-500">Save the basic contact information used for inventory restocking.</p>
      </div>
    </div>

    <Card>
      <template #content>
        <form class="space-y-6" @submit.prevent="submitForm">
          <div class="grid grid-cols-1 items-start gap-6 md:grid-cols-3 lg:gap-8">
            <div class="md:col-span-1">
              <label class="mb-2 block text-sm font-medium text-gray-700">Supplier Logo</label>
              <button type="button"
                class="flex aspect-square w-full items-center justify-center overflow-hidden rounded-2xl border border-dashed border-gray-300 bg-gray-50 transition hover:border-orange-400 hover:bg-orange-50/30"
                @click="fileInput?.click()">
                <img v-if="logoPreview" :src="logoPreview" alt="Supplier logo preview"
                  class="h-full w-full object-cover" />
                <span v-else class="flex flex-col items-center gap-2 text-gray-400">
                  <i class="pi pi-image text-4xl" />
                  <span class="text-xs">Select logo</span>
                </span>
              </button>
              <input ref="fileInput" type="file" class="hidden" accept="image/jpeg,image/png,image/webp"
                @change="onLogoSelected" />
              <Button v-if="logoFile || (isEditing && existingLogoUrl && !removeExistingLogo)" type="button"
                :label="logoFile ? 'Remove Selected Logo' : 'Remove Logo'" icon="pi pi-trash" severity="danger" text
                size="small" class="mt-2 w-full" @click="removeLogo" />
              <small v-if="errors.logo" class="mt-1 block text-red-500">{{ errors.logo }}</small>
              <p class="mt-2 text-xs leading-5 text-gray-500">Optional. JPG, PNG, or WebP up to 2MB.</p>
            </div>

            <div class="grid grid-cols-1 content-start gap-4 md:col-span-2 md:grid-cols-2">
              <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">Supplier Name <span
                    class="text-red-500">*</span></label>
                <InputText v-model="form.supplier_name" class="w-full" size="small"
                  placeholder="e.g. ABC Furniture Supply" />
                <small v-if="errors.supplier_name" class="text-red-500">{{ errors.supplier_name }}</small>
              </div>

              <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">Contact Person <span
                    class="text-red-500">*</span></label>
                <InputText v-model="form.contact_person" class="w-full" size="small" placeholder="Full name" />
                <small v-if="errors.contact_person" class="text-red-500">{{ errors.contact_person }}</small>
              </div>

              <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">Supplier Type <span
                    class="text-red-500">*</span></label>
                <Select v-model="form.supplier_type" :options="supplierTypeOptions" optionLabel="label"
                  optionValue="value" class="w-full" size="small" placeholder="Select supplier type" />
                <small v-if="errors.supplier_type" class="text-red-500">{{ errors.supplier_type }}</small>
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Phone Number <span
                    class="text-red-500">*</span></label>
                <InputMask v-model="form.phone" class="w-full" size="small" placeholder="09XX XXX XXXX" mask="0999 999 9999"/>
                <small v-if="errors.phone" class="text-red-500">{{ errors.phone }}</small>
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Email <span
                    class="text-red-500">*</span></label>
                <InputText v-model="form.email" type="email" class="w-full" size="small"
                  placeholder="supplier@example.com" />
                <small v-if="errors.email" class="text-red-500">{{ errors.email }}</small>
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Province <span
                    class="text-red-500">*</span></label>
                <Select v-model="form.province_id" :options="provinceOptions" optionLabel="label" optionValue="value"
                  class="w-full" size="small" filter placeholder="Select province" :loading="loadingProvinces"
                  @change="onProvinceChange" />
                <small v-if="errors.province" class="text-red-500">{{ errors.province }}</small>
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">City / Municipality <span
                    class="text-red-500">*</span></label>
                <Select v-model="form.city_id" :options="cityOptions" optionLabel="label" optionValue="value"
                  class="w-full" size="small" filter placeholder="Select city" :loading="loadingCities"
                  :disabled="!form.province_id || loadingCities" @change="onCityChange" />
                <small v-if="errors.city" class="text-red-500">{{ errors.city }}</small>
              </div>

              <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">Barangay <span
                    class="text-red-500">*</span></label>
                <Select v-model="form.barangay" :options="barangayOptions" optionLabel="label" optionValue="value"
                  class="w-full" size="small" filter placeholder="Select barangay" :loading="loadingBarangays"
                  :disabled="!form.city_id || loadingBarangays" />
                <small v-if="errors.barangay" class="text-red-500">{{ errors.barangay }}</small>
              </div>

              <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">Street Address <span
                    class="text-red-500">*</span></label>
                <InputText v-model="form.address" class="w-full" size="small"
                  placeholder="House/building number, street, subdivision" />
                <small v-if="errors.address" class="text-red-500">{{ errors.address }}</small>
              </div>

              <div class="md:col-span-2 space-y-2">
                <div>
                  <label class="mb-1 block text-sm font-medium text-gray-700">Supplier Map Location <span class="text-red-500">*</span></label>
                  <p class="mb-2 text-xs text-gray-500">Search the supplier address or click/drag the pin to mark the pickup location.</p>
                  <div class="flex gap-2">
                    <InputText v-model="mapSearchQuery" class="min-w-0 flex-1" size="small"
                      placeholder="Search street, barangay, city, province"
                      @keyup.enter.prevent="searchSupplierLocation" />
                    <Button type="button" label="Search" icon="pi pi-search" severity="warn" size="small"
                      :loading="searchingLocation" @click="searchSupplierLocation" />
                  </div>
                  <small v-if="errors.coordinates" class="mt-1 block text-red-500">{{ errors.coordinates }}</small>
                </div>
                <div ref="mapElement" class="h-72 w-full overflow-hidden rounded-xl border border-gray-200 bg-gray-50 sm:h-80" />
                <Message v-if="mapError" severity="error" :closable="false" class="text-sm">{{ mapError }}</Message>
                <p v-if="form.latitude && form.longitude" class="text-xs text-gray-600">
                  Pin selected: {{ Number(form.latitude).toFixed(6) }}, {{ Number(form.longitude).toFixed(6) }}
                </p>
                <p v-else class="text-xs text-amber-700">Choose a map location before saving this supplier.</p>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
            <Button type="button" label="Cancel" severity="secondary" outlined size="small"
              @click="router.push({ name: 'inventory.suppliers' })" />
            <Button type="submit" :label="isEditing ? 'Save' : 'Submit'"
              :icon="isEditing ? 'pi pi-save' : 'pi pi-check'" severity="warn" size="small"
              :loading="saving" />
          </div>
        </form>
      </template>
    </Card>

    <Dialog v-model:visible="showCreateConfirmation" modal header="Add this supplier?" :style="{ width: 'min(28rem, 94vw)' }">
      <p class="text-sm leading-6 text-slate-600">
        This supplier will be added to your store's inventory supplier directory. Continue with the submitted contact and address details?
      </p>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button label="Review Details" severity="secondary" outlined size="small" @click="showCreateConfirmation = false" />
          <Button label="Add Supplier" severity="warn" icon="pi pi-check" size="small" :loading="saving" @click="confirmCreateSupplier" />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Message from 'primevue/message'
import type { Map as MapboxMap, Marker as MapboxMarker } from 'mapbox-gl'
import 'mapbox-gl/dist/mapbox-gl.css'
import inventoryService from '../../../../services/inventory.service'
import ecommerceService from '../../../../services/ecommerce.service'
import { forwardGeocodeMapbox, requireMapboxToken } from '../../../../utils/mapbox'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const mapElement = ref<HTMLElement | null>(null)
const mapSearchQuery = ref('')
const mapError = ref('')
const searchingLocation = ref(false)
let supplierMap: MapboxMap | null = null
let supplierMarker: MapboxMarker | null = null
let mapboxgl: typeof import('mapbox-gl').default | null = null
const supplierId = Number(route.params.id || 0)
const isEditing = computed(() => supplierId > 0)
const saving = ref(false)
const showCreateConfirmation = ref(false)
const errors = ref<Record<string, string>>({})
const fileInput = ref<HTMLInputElement | null>(null)
const logoFile = ref<File | null>(null)
const logoPreview = ref<string | null>(null)
const existingLogoUrl = ref<string | null>(null)
const removeExistingLogo = ref(false)
const provinces = ref<any[]>([])
const cities = ref<any[]>([])
const barangays = ref<any[]>([])
const loadingProvinces = ref(false)
const loadingCities = ref(false)
const loadingBarangays = ref(false)

const form = reactive({
  supplier_name: '',
  contact_person: '',
  phone: '',
  email: '',
  supplier_type: '',
  province_id: '',
  city_id: '',
  barangay: '',
  address: '',
  latitude: null as number | null,
  longitude: null as number | null,
})

const supplierTypeOptions = [
  { label: 'Manufacturer', value: 'manufacturer' },
  { label: 'Wholesaler', value: 'wholesaler' },
  { label: 'Distributor', value: 'distributor' },
  { label: 'Importer', value: 'importer' },
  { label: 'Local Artisan', value: 'local_artisan' },
]

const itemsFromResponse = (response: any) => {
  const payload = response?.data ?? response ?? []
  return Array.isArray(payload) ? payload : (Array.isArray(payload?.data) ? payload.data : [])
}

const provinceOptions = computed(() => provinces.value.map((province: any) => ({
  label: province.name || province.province_name,
  value: String(province.province_id || province.id || province.code || ''),
})))
const cityOptions = computed(() => cities.value.map((city: any) => ({
  label: city.name || city.city_name,
  value: String(city.city_id || city.id || city.code || ''),
})))
const barangayOptions = computed(() => barangays.value.map((barangay: any) => ({
  label: barangay.name || barangay.barangay_name,
  value: barangay.name || barangay.barangay_name,
})))

const loadProvinces = async () => {
  loadingProvinces.value = true
  try {
    provinces.value = itemsFromResponse(await ecommerceService.getProvinces())
  } finally {
    loadingProvinces.value = false
  }
}

const onProvinceChange = async () => {
  form.city_id = ''
  form.barangay = ''
  cities.value = []
  barangays.value = []
  if (!form.province_id) return
  loadingCities.value = true
  try {
    cities.value = itemsFromResponse(await ecommerceService.getCities(form.province_id))
  } finally {
    loadingCities.value = false
  }
}

const onCityChange = async () => {
  form.barangay = ''
  barangays.value = []
  if (!form.city_id) return
  loadingBarangays.value = true
  try {
    barangays.value = itemsFromResponse(await ecommerceService.getBarangays(form.city_id))
  } finally {
    loadingBarangays.value = false
  }
}

const setSupplierLocation = (latitude: number, longitude: number) => {
  form.latitude = Number(latitude.toFixed(7))
  form.longitude = Number(longitude.toFixed(7))
  const point: [number, number] = [form.longitude, form.latitude]

  if (supplierMap && mapboxgl) {
    if (!supplierMarker) {
      supplierMarker = new mapboxgl.Marker({ draggable: true, color: '#f97316' })
        .setLngLat(point)
        .addTo(supplierMap)
      supplierMarker.on('dragend', () => {
        const moved = supplierMarker?.getLngLat()
        if (moved) setSupplierLocation(moved.lat, moved.lng)
      })
    } else {
      supplierMarker.setLngLat(point)
    }
    supplierMap.easeTo({ center: point, zoom: 16 })
  }
}

const initSupplierMap = async () => {
  if (!mapElement.value || supplierMap) return

  try {
    mapError.value = ''
    const module = await import('mapbox-gl')
    mapboxgl = module.default
    mapboxgl.accessToken = requireMapboxToken()
    const hasLocation = form.latitude !== null && form.longitude !== null
    const center: [number, number] = hasLocation
      ? [form.longitude as number, form.latitude as number]
      : [120.85, 14.20]

    supplierMap = new mapboxgl.Map({
      container: mapElement.value,
      style: 'mapbox://styles/mapbox/streets-v12',
      center,
      zoom: hasLocation ? 15 : 8,
    })
    supplierMap.on('load', () => {
      supplierMap?.resize()
      if (form.latitude !== null && form.longitude !== null) {
        setSupplierLocation(form.latitude, form.longitude)
      }
    })
    supplierMap.on('click', (event) => setSupplierLocation(event.lngLat.lat, event.lngLat.lng))
    supplierMap.on('error', (event) => {
      mapError.value = event.error?.message || 'The supplier map could not be loaded.'
    })
  } catch (error: any) {
    mapError.value = error?.message || 'Unable to load the supplier map.'
  }
}

const searchSupplierLocation = async () => {
  const addressQuery = mapSearchQuery.value.trim() || [
    form.address,
    form.barangay,
    cityOptions.value.find(option => option.value === form.city_id)?.label,
    provinceOptions.value.find(option => option.value === form.province_id)?.label,
    'Philippines',
  ].filter(Boolean).join(', ')

  if (!addressQuery) {
    toast.add({ severity: 'warn', summary: 'Enter an address', detail: 'Enter a supplier address or complete the address fields first.', life: 3000 })
    return
  }

  searchingLocation.value = true
  try {
    const result = await forwardGeocodeMapbox(addressQuery)
    if (!result) {
      toast.add({ severity: 'warn', summary: 'Location not found', detail: 'Try adding the barangay, city, and province.', life: 3000 })
      return
    }
    mapSearchQuery.value = result.address || addressQuery
    setSupplierLocation(result.latitude, result.longitude)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Map search failed', detail: error?.message || 'Unable to search this address.', life: 3500 })
  } finally {
    searchingLocation.value = false
  }
}

const revokePreview = () => {
  if (logoPreview.value) URL.revokeObjectURL(logoPreview.value)
  logoPreview.value = null
}

const onLogoSelected = (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return
  revokePreview()
  logoFile.value = file
  logoPreview.value = URL.createObjectURL(file)
}

const removeLogo = () => {
  if (logoFile.value) {
    revokePreview()
    logoPreview.value = existingLogoUrl.value
  } else {
    revokePreview()
  }
  logoFile.value = null
  if (isEditing.value && existingLogoUrl.value) removeExistingLogo.value = true
  if (fileInput.value) fileInput.value.value = ''
}

const loadSupplier = async () => {
  if (!isEditing.value) return
  saving.value = true
  try {
    const response = await inventoryService.getSupplierForEdit(supplierId)
    const supplier = response.data?.supplier || response.supplier
    if (!supplier) throw new Error('Supplier details were not returned.')

    form.supplier_name = supplier.supplier_name || supplier.company_name || ''
    form.contact_person = supplier.contact_person || ''
    form.phone = supplier.phone || ''
    form.email = supplier.email || ''
    form.supplier_type = supplier.supplier_type || ''
    form.address = supplier.address || ''
    form.barangay = supplier.barangay || ''
    form.latitude = supplier.latitude !== null && supplier.latitude !== undefined ? Number(supplier.latitude) : null
    form.longitude = supplier.longitude !== null && supplier.longitude !== undefined ? Number(supplier.longitude) : null
    existingLogoUrl.value = supplier.logo_url || null
    logoPreview.value = existingLogoUrl.value

    await loadProvinces()
    const province = provinces.value.find((item: any) =>
      String(item.name || item.province_name || '').toLowerCase() === String(supplier.province || '').toLowerCase()
    )
    if (province) {
      form.province_id = String(province.province_id || province.id || province.code || '')
      await onProvinceChange()
      const city = cities.value.find((item: any) =>
        String(item.name || item.city_name || '').toLowerCase() === String(supplier.city || '').toLowerCase()
      )
      if (city) {
        form.city_id = String(city.city_id || city.id || city.code || '')
        await onCityChange()
        const barangay = barangayOptions.value.find((option: any) =>
          String(option.label || '').trim().toLowerCase() === String(supplier.barangay || '').trim().toLowerCase()
        )
        form.barangay = barangay?.value || supplier.barangay || ''
      }
    }
    if (form.latitude !== null && form.longitude !== null) {
      setSupplierLocation(form.latitude, form.longitude)
    }
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to Load Supplier',
      detail: error.response?.data?.message || error.message || 'Supplier details could not be loaded.', life: 4000 })
    router.push({ name: 'inventory.suppliers' })
  } finally {
    saving.value = false
  }
}

const validate = () => {
  errors.value = {}
  if (!form.supplier_name.trim()) errors.value.supplier_name = 'Supplier name is required'
  if (!form.contact_person.trim()) errors.value.contact_person = 'Contact person is required'
  if (!form.phone.trim()) errors.value.phone = 'Phone number is required'
  if (!form.email.trim()) errors.value.email = 'Email is required'
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) errors.value.email = 'Enter a valid email address'
  if (!form.supplier_type) errors.value.supplier_type = 'Supplier type is required'
  if (!form.province_id) errors.value.province = 'Province is required'
  if (!form.city_id) errors.value.city = 'City or municipality is required'
  if (!form.barangay) errors.value.barangay = 'Barangay is required'
  if (!form.address.trim()) errors.value.address = 'Street address is required'
  if (form.latitude === null || form.longitude === null) errors.value.coordinates = 'Select the supplier pickup location on the map.'
  if (logoFile.value && logoFile.value.size > 2 * 1024 * 1024) errors.value.logo = 'Logo must not exceed 2 MB'
  return Object.keys(errors.value).length === 0
}

const submitForm = async () => {
  if (!validate()) return
  if (!isEditing.value) {
    showCreateConfirmation.value = true
    return
  }

  await saveSupplier()
}

const confirmCreateSupplier = async () => {
  await saveSupplier()
}

const saveSupplier = async () => {
  saving.value = true

  try {
    const payload = new FormData()
    payload.append('supplier_name', form.supplier_name.trim())
    payload.append('contact_person', form.contact_person.trim())
    payload.append('phone', form.phone.trim())
    payload.append('email', form.email.trim())
    payload.append('supplier_type', form.supplier_type)
    payload.append('province', provinceOptions.value.find(option => option.value === form.province_id)?.label || '')
    payload.append('city', cityOptions.value.find(option => option.value === form.city_id)?.label || '')
    payload.append('barangay', form.barangay)
    payload.append('address', form.address.trim())
    payload.append('latitude', String(form.latitude))
    payload.append('longitude', String(form.longitude))
    if (logoFile.value) payload.append('logo', logoFile.value)
    if (isEditing.value && removeExistingLogo.value && !logoFile.value) payload.append('remove_logo', '1')

    if (isEditing.value) {
      await inventoryService.updateSupplier(supplierId, payload)
      toast.add({ severity: 'success', summary: 'Supplier Updated', detail: 'Supplier information saved.', life: 3000 })
      router.push({ name: 'inventory.suppliers.detail', params: { id: supplierId } })
    } else {
      await inventoryService.createSupplier(payload)
      showCreateConfirmation.value = false
      toast.add({ severity: 'success', summary: 'Supplier Added', detail: 'Supplier added successfully.', life: 3000 })
      router.push({ name: 'inventory.suppliers' })
    }
  } catch (error: any) {
    showCreateConfirmation.value = false
    const apiErrors = error.response?.data?.errors || {}
    errors.value = Object.fromEntries(Object.entries(apiErrors).map(([key, value]) => [
      key,
      Array.isArray(value) ? String(value[0]) : String(value),
    ]))
    toast.add({
      severity: 'error',
      summary: 'Unable to Add Supplier',
      detail: error.response?.data?.message || 'Please check the supplier information and try again.',
      life: 4000,
    })
  } finally {
    saving.value = false
  }
}

onBeforeUnmount(() => {
  revokePreview()
  supplierMap?.remove()
  supplierMap = null
  supplierMarker = null
})
onMounted(() => {
  void initSupplierMap()
  if (isEditing.value) void loadSupplier()
  else void loadProvinces()
})
</script>
