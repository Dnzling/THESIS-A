<template>
  <div
    class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.16),_transparent_34%),linear-gradient(180deg,_#eff6ff_0%,_#f8fafc_44%,_#ffffff_100%)]">
    <div class="mx-auto max-w-6xl px-6 py-8">
      <Button label="Back to Job Details" icon="pi pi-arrow-left" severity="secondary" text
        @click="router.push({ name: 'job-portal.detail', params: { id: route.params.id } })" />
  
      <div class="mt-4 grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <div class="space-y-6">
          <Card class="border border-slate-200 shadow-sm">
            <template #title>Application Form</template>
            <template #subtitle>{{ posting?.title }}</template>
            <template #content>
              <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                  <label class="text-sm font-medium text-surface-700">First Name</label>
                  <InputText v-model="form.first_name" class="w-full" autocomplete="given-name" />
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium text-surface-700">Last Name</label>
                  <InputText v-model="form.last_name" class="w-full" autocomplete="family-name" />
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium text-surface-700">Email</label>
                  <InputText v-model="form.email" type="email" class="w-full" autocomplete="email" />
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium text-surface-700">Phone</label>
                  <InputText v-model="form.phone" class="w-full" autocomplete="tel" inputmode="numeric"
                    pattern="\\d*" maxlength="11" placeholder="09123456789" @input="onPhoneInput" />
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium text-surface-700">Birthday</label>
                  <DatePicker v-model="form.birthday" class="w-full" dateFormat="mm/dd/yy" showIcon fluid
                    inputId="birthday" :maxDate="today" />
                </div>

                <!-- Province Select -->
                <div class="space-y-2">
                  <label class="text-sm font-medium text-surface-700">Province</label>
                  <Select v-model="form.province" :options="provinceOptions" optionLabel="label" optionValue="value"
                    placeholder="Select province" class="w-full" @change="handleProvinceChange" />
                </div>
  
                <!-- City Select -->
                <div class="space-y-2">
                  <label class="text-sm font-medium text-surface-700">City/Municipality</label>
                  <Select v-model="form.city" :options="cityOptions" optionLabel="label" optionValue="value"
                    placeholder="Select city" class="w-full" :disabled="!form.province" />
                </div>
  
                <!-- Barangay Select -->
                <div class="space-y-2">
                  <label class="text-sm font-medium text-surface-700">Barangay</label>
                  <Select v-model="form.barangay" :options="barangayOptions" optionLabel="label" optionValue="value"
                    placeholder="Select barangay" class="w-full" :disabled="!form.city" />
                </div>
                <div class="space-y-2 md:col-span-2">
                  <label class="text-sm font-medium text-surface-700">Address</label>
                  <Textarea v-model="form.address" class="w-full" rows="2" autoResize autocomplete="street-address" />
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium text-surface-700">Current Position</label>
                  <InputText v-model="form.current_position" class="w-full" />
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium text-surface-700">Current Company</label>
                  <InputText v-model="form.current_company" class="w-full" />
                </div>
              </div>
            </template>
          </Card>
  
          <Card class="border border-slate-200 shadow-sm">
            <template #title>Required Documents</template>
            <template #content>
              <div class="space-y-4">
                <div v-for="(doc, index) in requiredDocuments" :key="doc.type"
                  class="rounded-2xl border border-dashed border-slate-300 bg-blue-50/70 p-4">
                  <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                      <p class="text-sm font-semibold text-surface-900">{{ doc.label }}</p>
                      <p class="text-xs text-surface-500">PDF, DOC, DOCX, JPG, PNG up to 5MB</p>
                    </div>
                    <Tag :value="doc.file ? 'Attached' : 'Required'" :severity="doc.file ? 'success' : 'warn'" />
                  </div>
                  <FileUpload mode="basic" customUpload auto chooseLabel="Choose File"
                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" :maxFileSize="5000000" class="w-full"
                    @uploader="(event) => onFileUpload(index, event)" />
                </div>
              </div>
            </template>
          </Card>
        </div>
  
        <div>
          <Card class="sticky top-6 border border-slate-200 shadow-sm">
            <template #title>Submission Summary</template>
            <template #content>
              <div class="space-y-4">
                <div class="rounded-2xl bg-blue-50/80 p-4">
                  <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Job Post</p>
                  <p class="mt-1 text-sm font-semibold text-surface-900">{{ posting?.title }}</p>
                  <p class="text-xs text-surface-500">{{ posting?.department }}</p>
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Attached Documents</p>
                  <p class="mt-1 text-sm font-semibold text-surface-900">{{ attachedCount }} / {{ requiredDocuments.length
                    }}</p>
                </div>
                <Button label="Submit Application" icon="pi pi-send" severity="info" :loading="submitting"
                  :disabled="!canSubmit" fluid @click="submitApplication" />
              </div>
            </template>
          </Card>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import hrService, { type JobPosting } from '../../../../services/hr.services'
import { useJobPortalAuthStore } from '../../../../stores/jobPortalAuth'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const portalAuth = useJobPortalAuthStore()
const posting = ref<JobPosting | null>(null)
const submitting = ref(false)

// Address Data (Yajra Address API)
const provinces = ref<any[]>([])
const cities = ref<any[]>([])
const barangays = ref<any[]>([])
const citiesCache = ref<Record<string, any[]>>({})

const form = reactive({
  first_name: portalAuth.user?.fname || '',
  last_name: portalAuth.user?.lname || '',
  email: portalAuth.user?.email || '',
  phone: '',
  birthday: null as Date | null,
  province: '',
  city: '',
  barangay: '',
  address: '',
  current_position: '',
  current_company: '',
})

// Computed options for dropdowns
const provinceOptions = computed(() =>
  provinces.value.map(p => ({
    value: p.province_id,
    label: p.name,
  }))
)

const cityOptions = computed(() =>
  cities.value.map(c => ({
    value: c.city_id,
    label: c.name,
  }))
)

const barangayOptions = computed(() =>
  barangays.value.map(b => ({
    value: b.code,
    label: b.name,
  }))
)

// Fetch all provinces
const fetchProvinces = async () => {
  try {
    const response = await hrService.api.get('/api/address/provinces')
    provinces.value = response.data || []

    // Set Cavite as default if not set
    if (!form.province) {
      const cavite = provinces.value.find(p =>
        String(p.name).trim().toLowerCase() === 'cavite'
      )
      if (cavite) {
        form.province = cavite.province_id
      }
    }
  } catch (error) {
    console.error('Failed to fetch provinces:', error)
    provinces.value = []
  }
}

// Fetch cities/municipalities for selected province
const fetchCities = async (provinceId: string) => {
  if (!provinceId) {
    cities.value = []
    return
  }

  // Check cache first
  if (citiesCache.value[provinceId]) {
    cities.value = citiesCache.value[provinceId]
    return
  }

  try {
    const response = await hrService.api.get(`/api/address/cities/${provinceId}`)
    citiesCache.value[provinceId] = response.data || []
    cities.value = citiesCache.value[provinceId]
  } catch (error) {
    console.error('Failed to fetch cities:', error)
    cities.value = []
  }
}

// Fetch barangays for selected city
const fetchBarangays = async (cityId: string) => {
  if (!cityId) {
    barangays.value = []
    return
  }

  try {
    const response = await hrService.api.get(`/api/address/barangays/${cityId}`)
    barangays.value = response.data || []
  } catch (error) {
    console.error('Failed to fetch barangays:', error)
    barangays.value = []
  }
}

// Handle province change
const handleProvinceChange = async () => {
  form.city = ''
  form.barangay = ''
  await fetchCities(form.province)
}

// Watch for city changes
watch(() => form.city, async (newCity) => {
  form.barangay = ''
  if (newCity) {
    await fetchBarangays(newCity)
  } else {
    barangays.value = []
  }
})

// Rest of your existing code remains the same...
const requiredDocuments = ref([
  { type: 'Resume', label: 'Resume', file: null as File | null },
  { type: 'CoverLetter', label: 'Cover Letter', file: null as File | null },
  { type: 'ID', label: 'Valid ID', file: null as File | null },
])

const attachedCount = computed(() => requiredDocuments.value.filter((doc) => !!doc.file).length)
const today = new Date()
const isBirthdayValid = computed(() => !form.birthday || form.birthday <= today)
const canSubmit = computed(() =>
  !!form.first_name &&
  !!form.last_name &&
  !!form.email &&
  !!form.phone &&
  !!form.birthday &&
  isBirthdayValid.value &&
  !!form.province &&
  !!form.city &&
  !!form.barangay &&
  !!form.address &&
  attachedCount.value === requiredDocuments.value.length &&
  !submitting.value
)

const fetchPosting = async () => {
  const response = await hrService.getPortalJobPosting(route.params.id as string)
  posting.value = response.data
}

const onFileUpload = (index: number, event: any) => {
  requiredDocuments.value[index].file = event.files?.[0] || null
}

const onPhoneInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (!target) return
  form.phone = target.value.replace(/\D/g, '')
}

const submitApplication = async () => {
  if (!canSubmit.value) return

  submitting.value = true
  try {
    const formData = new FormData()
    formData.append('first_name', form.first_name)
    formData.append('last_name', form.last_name)
    formData.append('email', form.email)
    formData.append('phone', form.phone)
    if (form.birthday) formData.append('birthday', form.birthday.toISOString().slice(0, 10))

    // Get the actual names for submission
    const provinceName = provinces.value.find(p => p.province_id === form.province)?.name || form.province
    const cityName = cities.value.find(c => c.city_id === form.city)?.name || form.city
    const barangayName = barangays.value.find(b => b.code === form.barangay)?.name || form.barangay

    formData.append('province', provinceName)
    formData.append('city', cityName)
    formData.append('barangay', barangayName)
    formData.append('address', form.address)
    formData.append('current_position', form.current_position)
    formData.append('current_company', form.current_company)
    formData.append('current_position', form.current_position)
    formData.append('current_company', form.current_company)

    requiredDocuments.value.forEach((doc, index) => {
      if (doc.file) {
        formData.append(`documents[${index}]`, doc.file)
        formData.append(`document_types[${index}]`, doc.type)
      }
    })

    await hrService.applyToPortalJob(route.params.id as string, formData)
    toast.add({ severity: 'success', summary: 'Application submitted', detail: 'You can track updates from your dashboard.', life: 2500 })
    router.push({ name: 'job-portal.dashboard' })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Submission failed', detail: error.response?.data?.message || 'Unable to submit application.', life: 3000 })
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  await Promise.all([fetchPosting(), fetchProvinces()])

  // If province was set (Cavite default), fetch its cities
  if (form.province) {
    await fetchCities(form.province)
  }
})
</script>

