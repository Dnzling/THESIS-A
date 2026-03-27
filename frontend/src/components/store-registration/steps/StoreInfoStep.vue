<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Store Information</h2>
  
    <form @submit.prevent="handleNext">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Store Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Store Name *
          </label>
          <InputText v-model="localForm.storeName" type="text" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
            placeholder="Enter store name" />
        </div>
  
        <!-- Business Type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Business Type *
          </label>
          <Select v-model="localForm.businessType" :options="businessTypes" optionLabel="label" optionValue="value"
            placeholder="Select business type" class="w-full" />
        </div>
  
        <!-- Contact Number -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Contact Number *
          </label>
          <InputMask v-model="localForm.contactNumber" mask="+639999999999" type="tel" required
            class="w-full px-4 py-2 border" placeholder="+63" />
        </div>
  
  
        <!-- Email -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Business Email *
          </label>
          <InputText v-model="localForm.email" type="email" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
            placeholder="business@example.com" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Province</label>
          <InputText type="text" class="w-full" value="Cavite" read-only />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">City *</label>
          <Select v-model="localForm.businessAddress.cityId" :options="cityOptions" optionLabel="label"
            optionValue="value" placeholder="Select city" fluid :loading="isCitiesLoading"
            :disabled="!provinceId || isCitiesLoading" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Barangay *</label>
          <div class="flex gap-2">
            <Select v-model="localForm.businessAddress.barangayCode" :options="barangayOptions" optionLabel="label"
              optionValue="value" placeholder="Select barangay" fluid :loading="isBarangaysLoading"
              :disabled="!localForm.businessAddress.cityId || isBarangaysLoading" />
            <Button type="button" class="h-11" icon="pi pi-map-marker" severity="contrast" :loading="isLocating"
              :disabled="isLocating" @click="useCurrentLocation" />
          </div>
        </div>
  
  
        <small v-if="locationError" class="text-red-500 block -mt-3">{{ locationError }}</small>
  
        <!-- Business Address -->
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Business Address *
          </label>
          <Textarea v-model="localForm.businessAddress.address" required rows="3" cols="30" class="w-full px-4 py-2" />
        </div>
      </div>
      <!-- Navigation Buttons -->
      <div class="flex justify-end mt-8 pt-6 border-t border-gray-200">
        <Button type="submit" class="w-1/5" severity="contrast" label="Next" />
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import ecommerceService from '../../../services/ecommerce.service'

interface Props {
  formData: Record<string, any>
}

interface Emits {
  (e: 'update:formData', data: any): void
  (e: 'next'): void
  (e: 'prev'): void
}

const businessTypes = [
  { value: 'retail', label: 'Retail Store' },
  { value: 'restaurant', label: 'Restaurant' },
  { value: 'service', label: 'Service Provider' },
  { value: 'wholesale', label: 'Wholesale' },
  { value: 'online', label: 'Online Store' },
  { value: 'other', label: 'Other' }
]

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Local form data - only update from parent on component creation
const localForm = ref({ ...props.formData })
const isLocating = ref(false)
const locationError = ref('')
const provinceId = ref('')
const provinces = ref<any[]>([])
const cities = ref<any[]>([])
const barangays = ref<any[]>([])
const citiesCache = ref<Record<string, any[]>>({})
const isCitiesLoading = ref(false)
const isBarangaysLoading = ref(false)

const cityOptions = computed(() =>
  cities.value.map((c: any) => ({
    label: c.name,
    value: c.city_id
  }))
)

const barangayOptions = computed(() =>
  barangays.value.map((b: any) => ({
    label: b.name,
    value: b.code
  }))
)

const normalize = (value: unknown) => String(value ?? '').trim().toLowerCase()

const resolveCityFromId = (cityId: string) => {
  if (!cityId) return
  const match = cities.value.find((c: any) => String(c.city_id) === String(cityId))
  if (match) {
    localForm.value.businessAddress.city = match.name
  }
}

const resolveBarangayFromCode = (barangayCode: string) => {
  if (!barangayCode) return
  const match = barangays.value.find((b: any) => String(b.code) === String(barangayCode))
  if (match) {
    localForm.value.businessAddress.barangay = match.name
  }
}

const fetchProvinces = async () => {
  try {
    const response = await ecommerceService.getProvinces()
    provinces.value = response.data || []

    const cavite = provinces.value.find((p: any) => normalize(p.name) === 'cavite')
    if (cavite) {
      provinceId.value = cavite.province_id
    }
  } catch (error) {
    console.error('Failed to load provinces:', error)
    provinces.value = []
  }
}

const fetchCities = async (selectedProvinceId: string) => {
  if (!selectedProvinceId) {
    cities.value = []
    return
  }

  if (citiesCache.value[selectedProvinceId]) {
    cities.value = citiesCache.value[selectedProvinceId]
    return
  }

  try {
    isCitiesLoading.value = true
    const response = await ecommerceService.getCities(selectedProvinceId)
    citiesCache.value[selectedProvinceId] = response.data || []
    cities.value = citiesCache.value[selectedProvinceId]
  } catch (error) {
    console.error('Failed to load cities:', error)
    cities.value = []
  } finally {
    isCitiesLoading.value = false
  }
}

const fetchBarangays = async (cityId: string) => {
  if (!cityId) {
    barangays.value = []
    return
  }

  try {
    isBarangaysLoading.value = true
    const response = await ecommerceService.getBarangays(cityId)
    barangays.value = response.data || []
  } catch (error) {
    console.error('Failed to load barangays:', error)
    barangays.value = []
  } finally {
    isBarangaysLoading.value = false
  }
}

const useCurrentLocation = () => {
  locationError.value = ''

  if (!navigator.geolocation) {
    locationError.value = 'Geolocation is not supported by this browser.'
    return
  }

  isLocating.value = true
  navigator.geolocation.getCurrentPosition(
    (position) => {
      if (!localForm.value.businessAddress) {
        localForm.value.businessAddress = {}
      }

      localForm.value.businessAddress.latitude = Number(position.coords.latitude.toFixed(6))
      localForm.value.businessAddress.longitude = Number(position.coords.longitude.toFixed(6))
      isLocating.value = false
    },
    (error) => {
      isLocating.value = false
      if (error.code === error.PERMISSION_DENIED) {
        locationError.value = 'Location access denied. Please allow location permission.'
      } else if (error.code === error.TIMEOUT) {
        locationError.value = 'Location request timed out. Please try again.'
      } else {
        locationError.value = 'Unable to fetch location right now.'
      }
    },
    {
      enableHighAccuracy: true,
      timeout: 15000,
      maximumAge: 0,
    }
  )
}

watch(provinceId, async (newProvince) => {
  if (newProvince) {
    await fetchCities(newProvince)

    if (!localForm.value.businessAddress.cityId && localForm.value.businessAddress.city) {
      const match = cities.value.find((c: any) => normalize(c.name) === normalize(localForm.value.businessAddress.city))
      if (match) {
        localForm.value.businessAddress.cityId = match.city_id
      }
    }
  }
})

watch(
  () => localForm.value.businessAddress.cityId,
  async (newCityId) => {
    localForm.value.businessAddress.barangayCode = ''
    localForm.value.businessAddress.barangay = ''

    if (newCityId) {
      resolveCityFromId(String(newCityId))
      await fetchBarangays(String(newCityId))

      if (!localForm.value.businessAddress.barangayCode && localForm.value.businessAddress.barangay) {
        const match = barangays.value.find((b: any) => normalize(b.name) === normalize(localForm.value.businessAddress.barangay))
        if (match) {
          localForm.value.businessAddress.barangayCode = match.code
        }
      }
    } else {
      barangays.value = []
    }
  }
)

watch(
  () => localForm.value.businessAddress.barangayCode,
  (newBarangayCode) => {
    if (newBarangayCode) {
      resolveBarangayFromCode(String(newBarangayCode))
    }
  }
)

onMounted(async () => {
  await fetchProvinces()

  if (provinceId.value) {
    await fetchCities(provinceId.value)
  }
})

// Validation
const handleNext = () => {
  emit('update:formData', localForm.value) // Emit final state
  window.scrollTo(0, 0)
  emit('next')
}
</script>

