<template>
  <div class="min-h-screen bg-gradient-to-b from-white via-orange-50/20 to-slate-50 px-4 py-10 text-slate-900">
    <div class="mx-auto max-w-4xl">
      <div class="mb-8 text-center">
        
        <h1 class="mt-4 text-3xl font-bold sm:text-4xl">Create your store</h1>
        <p class="mt-3 text-slate-600">Keep it simple. We’ll set up your first store before you choose a subscription.</p>
      </div>

      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60">
        <div class="grid gap-5 md:grid-cols-2">
          <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-medium text-slate-700">Store Name *</label>
            <input v-model="form.store_name" type="text" class="field" placeholder="e.g. FurniSync Main Store" data-testid="store-name" />
          </div>

          <div class="hidden">
            <label class="mb-2 block text-sm font-medium text-slate-700">Contact Person</label>
            <input :value="contactPerson" type="text" class="field bg-slate-100" disabled data-testid="contact-person" />
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Business Type *</label>
            <Select v-model="form.business_type" :options="businessTypeOptions" rounded optionLabel="label" optionValue="value" class="w-full" data-testid="business-type" />
          </div>

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
        </div>

        <div v-if="errorMessage" class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
          {{ errorMessage }}
        </div>

        <div class="mt-6 flex flex-wrap justify-end gap-3">
          <Button label="Submit" severity="warn" :loading="submitting" @click="submitStore" data-testid="save-store" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import Button from 'primevue/button'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import axiosClient from '@/axios'
import ecommerceService from '@/services/ecommerce.service'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const submitting = ref(false)
const errorMessage = ref('')
const citiesLoading = ref(false)
const barangaysLoading = ref(false)
const provinceId = ref<string>('')
const cities = ref<any[]>([])
const barangays = ref<any[]>([])

const businessTypeOptions = [
  { label: 'Retail', value: 'retail' },
  { label: 'Enterprise', value: 'enterprise' },
  { label: 'Showroom', value: 'showroom' },
  { label: 'Wholesale', value: 'wholesale' },
]

const form = ref({
  store_name: '',
  business_type: 'retail',
  city_id: '',
  barangay: '',
  address: '',
})

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
      email: (authStore.currentUser as any)?.email || null,
      contact_number: (authStore.currentUser as any)?.contact_number || null,
    }

    const response = await axiosClient.post('/api/stores/register', payload)
    const storeId = response?.data?.store?.store_id || response?.data?.data?.id
    if (!storeId) {
      throw new Error(response?.data?.message || 'Unable to create store.')
    }

    await authStore.fetchCurrentUser({ reloadPermissions: true })
    router.visit(`/subscription-plans?store_id=${encodeURIComponent(String(storeId))}`)
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message || error?.message || 'Unable to save store.'
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  await authStore.fetchCurrentUser()
  if (!(authStore.currentUser as any)?.store_id) {
    await loadCities()
  } else {
    router.visit('/subscription-plans')
  }
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

  background: #fff;
}
</style>
