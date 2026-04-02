<template>
  <JobPortalLayout>
    <div class="py-6 lg:py-10">
      <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 class="text-3xl font-semibold text-slate-900">Profile & Documents</h1>
            <p class="text-sm text-slate-500">Complete this once so you can apply instantly to any job posting.</p>
          </div>
          <Button label="Edit Profile" severity="warn" @click="openEditDialog" />
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
          <Card class="border border-orange-100 shadow-sm">
            <template #title>Credentials</template>
            <template #content>
              <div class="grid gap-4 md:grid-cols-2 text-sm">
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Name</p>
                  <p class="mt-1 font-semibold text-slate-900">{{ profile.first_name }} {{ profile.last_name }}</p>
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Phone</p>
                  <p class="mt-1 font-semibold text-slate-900">{{ profile.phone || '-' }}</p>
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Birthday</p>
                  <p class="mt-1 font-semibold text-slate-900">{{ formattedBirthday }}</p>
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Email</p>
                  <div class="mt-1 flex items-center gap-2">
                    <p class="font-semibold text-slate-900">{{ profile.email || '-' }}</p>
                    <Button label="Change" size="small" severity="secondary" outlined @click="openEmailDialog" />
                  </div>
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Address</p>
                  <p class="mt-1 text-slate-700">{{ formatAddress }}</p>
                </div>
              </div>
            </template>
          </Card>

          <Card class="border border-orange-100 shadow-sm">
            <template #title>Documents</template>
            <template #content>
              <div class="space-y-4">
                <Message severity="warn" :closable="false">
                  Upload at least one document (resume, portfolio, or ID). This is required before you can apply to a job.
                </Message>

                <div class="space-y-2">
                  <label class="text-sm font-medium text-slate-700">Document Type</label>
                  <InputText v-model="documentType" fluid placeholder="Resume, ID, Portfolio" />
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium text-slate-700">Attach File</label>
                  <input type="file" fluid @change="handleFileChange" />
                </div>
                <Button label="Upload Document" severity="warn" :loading="uploading" :disabled="!selectedFile" @click="uploadDocument" />

                <div v-if="documents.length" class="space-y-3">
                  <div v-for="doc in documents" :key="doc.id" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-orange-100 bg-orange-50/60 px-4 py-3">
                    <div>
                      <p class="text-sm font-semibold text-slate-900">{{ doc.file_name }}</p>
                      <p class="text-xs text-slate-500">{{ doc.document_type || 'Other' }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                      <Button label="View" icon="pi pi-eye" severity="secondary" outlined size="small" @click="openDocumentViewer(doc)" />
                      <Button icon="pi pi-trash" severity="danger" text @click="removeDocument(doc.id)" />
                    </div>
                  </div>
                </div>
                <p v-else class="text-sm text-slate-400">No documents uploaded yet.</p>
              </div>
            </template>
          </Card>
        </div>
      </div>
    </div>
  </JobPortalLayout>

  <Dialog v-model:visible="editDialogOpen" modal header="Edit Credentials" :style="{ width: 'min(48rem, 94vw)' }">
    <div class="space-y-6">
      <div class="grid gap-4 md:grid-cols-2">
        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-700">First Name</label>
          <InputText v-model="editForm.first_name" class="w-full" />
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-700">Last Name</label>
          <InputText v-model="editForm.last_name" class="w-full" />
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-700">Phone</label>
          <InputMask mask="+63 999-999-9999" placeholder="+63" v-model="editForm.phone" class="w-full" />
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-700">Birthday</label>
          <DatePicker v-model="editForm.birthday" :maxDate="new Date()" class="w-full" showIcon />
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-700">Province</label>
          <Select v-model="editProvinceId" :options="provinceOptions" optionLabel="label" optionValue="value"
            placeholder="Select province" class="w-full" filter />
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-700">City</label>
          <Select v-model="editCityId" :options="cityOptions" optionLabel="label" optionValue="value"
            placeholder="Select city" class="w-full" filter :disabled="!editProvinceId" />
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-700">Barangay</label>
          <Select v-model="editBarangayId" :options="barangayOptions" optionLabel="label" optionValue="value"
            placeholder="Select barangay" class="w-full" filter :disabled="!editCityId" />
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-700">Address</label>
          <Textarea v-model="editForm.address" class="w-full" rows="3" autoResize />
        </div>
      </div>
    </div>
    <template #footer>
      <Button label="Cancel" severity="secondary" outlined @click="editDialogOpen = false" />
      <Button label="Save Profile" severity="warn" :loading="saving" @click="saveProfileFromDialog" />
    </template>
  </Dialog>

  <Dialog v-model:visible="emailDialogOpen" modal header="Change Email" :style="{ width: 'min(28rem, 92vw)' }">
    <div class="space-y-4">
      <div class="space-y-2">
        <label class="text-sm font-medium text-slate-700">New Email</label>
        <InputText v-model="emailForm.newEmail" class="w-full" placeholder="you@example.com" :disabled="emailStep === 'otp'" />
      </div>
      <div v-if="emailStep === 'otp'" class="space-y-2">
        <label class="text-sm font-medium text-slate-700">OTP Code</label>
        <InputOtp v-model="emailForm.otp" integerOnly :length="6" />
      </div>
    </div>
    <template #footer>
      <Button label="Cancel" severity="secondary" outlined @click="closeEmailDialog" />
      <Button
        :label="emailStep === 'otp' ? 'Verify OTP' : 'Send OTP'"
        severity="warn"
        :loading="emailSubmitting"
        @click="handleEmailChange"
      />
    </template>
  </Dialog>

  <Dialog v-model:visible="viewerOpen" modal header="Document Preview" :style="{ width: 'min(52rem, 96vw)' }">
    <div v-if="viewerUrl" class="space-y-4">
      <p class="text-sm font-medium text-slate-700">{{ viewerLabel }}</p>
      <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
        <img v-if="viewerKind === 'image'" :src="viewerUrl" class="max-h-[70vh] w-full rounded-xl object-contain" />
        <iframe v-else-if="viewerKind === 'pdf'" :src="viewerUrl" class="h-[70vh] w-full rounded-xl"></iframe>
        <div v-else class="space-y-2 text-sm text-slate-600">
          <p>Preview not available for this file type.</p>
          <a :href="viewerUrl" target="_blank" class="text-orange-600 underline">Open in new tab</a>
        </div>
      </div>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import JobPortalLayout from '../JobPortal/JobPortalLayout.vue'
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import hrService, { type ApplicantProfile } from '../../../../services/hr.services'
import { useJobPortalAuthStore } from '../../../../stores/jobPortalAuth'
import ecommerceService from '../../../../services/ecommerce.service'

const toast = useToast()
const portalAuth = useJobPortalAuthStore()

const saving = ref(false)
const uploading = ref(false)
const editDialogOpen = ref(false)
const emailDialogOpen = ref(false)
const emailSubmitting = ref(false)
const emailStep = ref<'input' | 'otp'>('input')
const selectedFile = ref<File | null>(null)
const documentType = ref('Resume')
const documents = ref<any[]>([])
const provinces = ref<any[]>([])
const cities = ref<any[]>([])
const barangays = ref<any[]>([])
const citiesCache = ref<Record<string, any[]>>({})
const editProvinceId = ref<string | number | null>(null)
const editCityId = ref<string | number | null>(null)
const editBarangayId = ref<string | number | null>(null)
const isInitializing = ref(true)

const profile = reactive<ApplicantProfile>({
  first_name: portalAuth.user?.fname || '',
  last_name: portalAuth.user?.lname || '',
  email: portalAuth.user?.email || '',
  phone: '',
  birthday: '',
  city: '',
  province: '',
  barangay: '',
  address: '',
  current_position: '',
  current_company: '',
})

const editForm = reactive<ApplicantProfile>({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  birthday: '',
  city: '',
  province: '',
  barangay: '',
  address: '',
  current_position: '',
  current_company: '',
})

const emailForm = reactive({
  newEmail: '',
  otp: '',
})

const viewerOpen = ref(false)
const viewerUrl = ref('')
const viewerLabel = ref('')
const viewerKind = ref<'image' | 'pdf' | 'other'>('other')

const loadProfile = async () => {
  const response = await hrService.getApplicantProfile()
  const payload = response?.data ?? response
  const profileData = payload?.data ?? payload
  if (profileData) {
    Object.assign(profile, profileData)
    documents.value = profileData.documents || []
  }
}

const loadProvinces = async () => {
  const response = await ecommerceService.getProvinces()
  provinces.value = response.data || []
}

const loadCities = async (provinceId: string | number) => {
  if (!provinceId) {
    cities.value = []
    return
  }
  const cacheKey = String(provinceId)
  if (citiesCache.value[cacheKey]) {
    cities.value = citiesCache.value[cacheKey]
    return
  }
  const response = await ecommerceService.getCities(String(provinceId))
  citiesCache.value[cacheKey] = response.data || []
  cities.value = citiesCache.value[cacheKey]
}

const loadBarangays = async (cityId: string | number) => {
  if (!cityId) {
    barangays.value = []
    return
  }
  const response = await ecommerceService.getBarangays(String(cityId))
  barangays.value = response.data || []
}

const syncSelections = async () => {
  if (!provinces.value.length) return
  const normalize = (value?: string) => String(value || '').trim().toLowerCase()
  const province = provinces.value.find((item) => normalize(item.name) === normalize(profile.province))
  if (province) {
    editProvinceId.value = province.province_id
    await loadCities(province.province_id)
    const city = cities.value.find((item) => normalize(item.name) === normalize(profile.city))
    if (city) {
      editCityId.value = city.city_id
      await loadBarangays(city.city_id)
      const barangay = barangays.value.find((item) => normalize(item.name) === normalize(profile.barangay))
      if (barangay) {
        editBarangayId.value = barangay.code
      }
    }
  }
}

const saveProfileFromDialog = async () => {
  saving.value = true
  try {
    const requiredFields = [
      { key: 'first_name', label: 'First Name' },
      { key: 'last_name', label: 'Last Name' },
      { key: 'phone', label: 'Phone' },
      { key: 'birthday', label: 'Birthday' },
      { key: 'province', label: 'Province' },
      { key: 'city', label: 'City' },
      { key: 'barangay', label: 'Barangay' },
      { key: 'address', label: 'Address' },
    ]

    const missing = requiredFields.filter((field) => !String(editForm[field.key as keyof ApplicantProfile] || '').trim())
    if (missing.length) {
      toast.add({
        severity: 'warn',
        summary: 'Missing fields',
        detail: `Please complete: ${missing.map((field) => field.label).join(', ')}`,
        life: 3000,
      })
      saving.value = false
      return
    }

    const formatDate = (value: any) => {
      if (!value) return ''
      if (typeof value === 'string') return value
      if (value instanceof Date) return value.toISOString().slice(0, 10)
      return String(value)
    }

    const payload = {
      ...editForm,
      email: profile.email || portalAuth.user?.email || '',
      birthday: formatDate(editForm.birthday),
    }
    const response = await hrService.saveApplicantProfile(payload)
    const normalized = response?.data && response?.data?.data !== undefined ? response.data : response
    if (normalized?.success === false) {
      throw new Error(normalized?.message || 'Unable to save profile.')
    }
    const profileData = normalized?.data ?? normalized
    if (profileData) {
      Object.assign(profile, profileData)
      if (profileData.documents) {
        documents.value = profileData.documents
      }
    }
    toast.add({ severity: 'success', summary: 'Profile saved', detail: 'Credentials updated successfully.', life: 2500 })
    editDialogOpen.value = false
  } catch (error: any) {
    const apiErrors = error.response?.data?.errors
    const apiMessage =
      error.message ||
      error.response?.data?.message ||
      (apiErrors ? Object.values(apiErrors).flat().join(' ') : null) ||
      'Please check required fields and try again.'
    toast.add({ severity: 'error', summary: 'Unable to save', detail: apiMessage, life: 3500 })
  } finally {
    saving.value = false
  }
}

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  selectedFile.value = target.files?.[0] || null
}

const uploadDocument = async () => {
  if (!selectedFile.value) return
  uploading.value = true
  try {
    const response = await hrService.uploadApplicantProfileDocument(selectedFile.value, documentType.value)
    documents.value = [...documents.value, response.data]
    selectedFile.value = null
    toast.add({ severity: 'success', summary: 'Document uploaded', detail: 'File added to your profile.', life: 2200 })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Upload failed', detail: error.response?.data?.message || 'Please try again.', life: 3000 })
  } finally {
    uploading.value = false
  }
}

const removeDocument = async (id: number) => {
  try {
    await hrService.deleteApplicantProfileDocument(id)
    documents.value = documents.value.filter((doc) => doc.id !== id)
    toast.add({ severity: 'success', summary: 'Document removed', detail: 'File deleted.', life: 2000 })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to remove', detail: error.response?.data?.message || 'Please try again.', life: 3000 })
  }
}

const openEditDialog = () => {
  Object.assign(editForm, profile)
  editForm.birthday = profile.birthday ? new Date(profile.birthday) : ''
  editProvinceId.value = provinces.value.find((item: any) => item.name === profile.province)?.province_id ?? null
  editCityId.value = cities.value.find((item: any) => item.name === profile.city)?.city_id ?? null
  editBarangayId.value = barangays.value.find((item: any) => item.name === profile.barangay)?.code ?? null
  editDialogOpen.value = true
}

const openEmailDialog = () => {
  emailForm.newEmail = ''
  emailForm.otp = ''
  emailStep.value = 'input'
  emailDialogOpen.value = true
}

const closeEmailDialog = () => {
  emailDialogOpen.value = false
  emailStep.value = 'input'
  emailForm.newEmail = ''
  emailForm.otp = ''
}

const handleEmailChange = async () => {
  emailSubmitting.value = true
  try {
    if (emailStep.value === 'input') {
      await hrService.requestApplicantEmailChange(emailForm.newEmail)
      toast.add({ severity: 'success', summary: 'OTP sent', detail: 'Check your new email for the code.', life: 2500 })
      emailStep.value = 'otp'
    } else {
      await hrService.verifyApplicantEmailChange(emailForm.otp)
      await portalAuth.fetchMe()
      profile.email = portalAuth.user?.email || profile.email
      toast.add({ severity: 'success', summary: 'Email updated', detail: 'Your email has been changed.', life: 2500 })
      closeEmailDialog()
    }
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Email update failed', detail: error.response?.data?.message || 'Please try again.', life: 3000 })
  } finally {
    emailSubmitting.value = false
  }
}

const openDocumentViewer = (doc: any) => {
  viewerLabel.value = doc.file_name || doc.document_type || 'Document'
  viewerUrl.value = doc.file_path ? `/storage/${doc.file_path}` : ''
  const mime = String(doc.mime_type || '').toLowerCase()
  if (mime.startsWith('image/')) viewerKind.value = 'image'
  else if (mime === 'application/pdf') viewerKind.value = 'pdf'
  else viewerKind.value = 'other'
  viewerOpen.value = true
}

watch(editProvinceId, async (value) => {
  if (!value) {
    cities.value = []
    editCityId.value = null
    if (!isInitializing.value) editForm.province = ''
    return
  }
  const province = provinces.value.find((item) => String(item.province_id) === String(value))
  if (province) editForm.province = province.name
  await loadCities(value)
  if (!isInitializing.value) {
    editCityId.value = null
    barangays.value = []
    editBarangayId.value = null
  }
})

watch(editCityId, async (value) => {
  if (!value) {
    barangays.value = []
    editBarangayId.value = null
    if (!isInitializing.value) editForm.city = ''
    return
  }
  const city = cities.value.find((item) => String(item.city_id) === String(value))
  if (city) editForm.city = city.name
  await loadBarangays(value)
  if (!isInitializing.value) {
    editBarangayId.value = null
  }
})

watch(editBarangayId, (value) => {
  if (!value) {
    if (!isInitializing.value) editForm.barangay = ''
    return
  }
  const barangay = barangays.value.find((item) => String(item.code) === String(value))
  if (barangay) editForm.barangay = barangay.name
})

const formatAddress = computed(() => {
  const parts = [profile.barangay, profile.city, profile.province].filter(Boolean)
  const suffix = parts.length ? `, ${parts.join(', ')}` : ''
  return `${profile.address || '-'}${suffix}`
})

const formattedBirthday = computed(() => {
  if (!profile.birthday) return '-'
  const raw = String(profile.birthday)
  if (raw.includes('T')) return raw.split('T')[0]
  return raw
})

const provinceOptions = computed(() => provinces.value.map((p: any) => ({ label: p.name, value: p.province_id })))
const cityOptions = computed(() => cities.value.map((c: any) => ({ label: c.name, value: c.city_id })))
const barangayOptions = computed(() => barangays.value.map((b: any) => ({ label: b.name, value: b.code })))

onMounted(async () => {
  await loadProfile()
  await loadProvinces()
  await syncSelections()
  isInitializing.value = false
})
</script>
