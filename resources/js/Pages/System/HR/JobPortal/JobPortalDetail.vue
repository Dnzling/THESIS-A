<template>
  <JobPortalLayout>
    <div class="py-6 lg:py-10">
      <div class="space-y-4">
    <div>
      <Button label="Back to Jobs" icon="pi pi-arrow-left" rounded @click="router.push({ name: 'job-portal.index' })" />
  
      <div v-if="loading" class="mt-2 space-y-3">
        <Skeleton height="11rem" />
        <Skeleton height="10rem" />
      </div>
  
      <div v-else class="mt-2 grid gap-4 xl:grid-cols-[1.4fr_0.6fr]">
        <div class="space-y-6">
          <Card class="border border-orange-100 shadow-sm">
            <template #content>
              <div class="space-y-4">
                <div class="flex items-center gap-3">
                  <img v-if="posting?.company_logo_url" :src="posting.company_logo_url" :alt="`${storeLabel} logo`" class="h-14 w-14 shrink-0 rounded-xl border border-slate-200 bg-white object-contain p-1" />
                  <div v-else class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600"><i class="pi pi-building text-xl" /></div>
                  <span class="text-lg font-bold text-slate-700">{{ storeLabel }}</span>
                </div>
                <p v-if="posting?.branch?.name" class="text-sm text-slate-500">
                  <i class="pi pi-map-marker mr-1" />{{ posting.branch.name }}<span v-if="posting.branch.city || posting.branch.province"> · {{ [posting.branch.city, posting.branch.province].filter(Boolean).join(', ') }}</span>
                </p>
                <!-- <div class="flex flex-wrap items-center gap-2">
                  <Tag value="Open" severity="success" />
                  <Tag :value="employmentTypeLabel" severity="warn" />
                </div> -->
                <div>
                  <h1 class="text-3xl font-semibold text-surface-900">{{ roleLabel }}</h1>
                  <p class="mt-1 text-base text-surface-600">{{ posting?.title }}</p>
                </div>
                <div class="whitespace-pre-line break-words border-x-2 border-orange-500 px-3 py-2 text-sm leading-7 text-surface-600">{{ posting?.description }}</div>
              </div>
            </template>
          </Card>
  
          <Card class="border border-orange-100 shadow-sm">
            <template #title>Hiring Process</template>
            <template #content>
              <div class="space-y-3">
                <div v-for="(stage, index) in stages" :key="index"
                  class="flex items-start gap-3 rounded-2xl bg-orange-50/70 p-4">
                  <span
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-orange-600 text-xs font-semibold text-white">{{
                    index + 1 }}</span>
                  <div>
                    <p class="text-sm font-semibold text-surface-900">{{ stage.stage_name || stage.name }}</p>
                    <p class="text-xs text-surface-500">{{ stage.description || 'No extra notes provided.' }}</p>
                  </div>
                </div>
              </div>
            </template>
          </Card>
        </div>
  
        <div>
          <Card class="sticky top-4 border border-orange-100 shadow-sm">
            <template #title>Job Highlights</template>
            <template #content>
              <div class="space-y-5">
                <div class="rounded-2xl bg-orange-50/80 p-4">
                  <p class="text-xs font-semibold uppercase tracking-wide text-orange-700">Salary Range</p>
                  <p class="mt-1 text-lg font-semibold text-surface-900">
                    {{ formatCurrency(posting?.salary_min) }} - {{ formatCurrency(posting?.salary_max) }}
                  </p>
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Role</p>
                  <p class="mt-1 text-sm font-semibold text-surface-900">{{ roleLabel }}</p>
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Employment Type</p>
                  <p class="mt-1 text-sm font-semibold text-surface-900">{{ employmentTypeLabel }}</p>
                </div>
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Minimum Age</p>
          <p class="mt-1 text-sm font-semibold text-surface-900">{{ posting?.minimum_age || 18 }} years</p>
        </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Store</p>
                  <p class="mt-1 text-sm font-semibold text-surface-900">{{ storeLabel }}</p>
                </div>
                <div v-if="posting?.branch?.name">
                  <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Branch</p>
                  <p class="mt-1 text-sm font-semibold text-surface-900">{{ posting.branch.name }}</p>
                </div>
                <Message v-if="applicationSubmitted" severity="success" :closable="false">
                  <span class="font-semibold">Application submitted</span>
                  <span class="block text-xs">You have already applied for this position.</span>
                </Message>
                <Button v-else label="Apply Job" icon="pi pi-send" severity="warn" fluid @click="applyVisible = true" />
              </div>
            </template>
          </Card>
        </div>
      </div>
    </div>
      </div>
    </div>
    <Dialog v-model:visible="applyVisible" modal header="Apply for this job" :style="{ width: 'min(95vw, 640px)' }">
      <form class="space-y-4 text-sm" @submit.prevent="submitApplication">
        <p class="text-slate-500">Tell the hiring team who you are. Attach supporting documents if you want to include them. No account is needed.</p>
        <Message severity="info" :closable="false">Applicants must be at least {{ posting?.minimum_age || 18 }} years old. Your birthday is required to confirm eligibility.</Message>
        <div class="grid gap-3 sm:grid-cols-2">
          <label class="space-y-1">First name *<InputText v-model.trim="form.first_name" size="small" fluid required /></label>
          <label class="space-y-1">Middle name<InputText v-model.trim="form.middle_name" size="small" fluid /></label>
          <label class="space-y-1">Last name *<InputText v-model.trim="form.last_name" size="small" fluid required /></label>
          <label class="space-y-1">Phone number *<InputMask v-model="form.phone" mask="0999-999-9999" placeholder="09XX-XXX-XXXX" size="small" fluid required /></label>
          <label class="space-y-1">Birthday *<DatePicker v-model="form.birthday" dateFormat="M d, yy" :maxDate="minimumBirthday" :manualInput="false" showIcon placeholder="Select birthday" size="small" fluid :invalid="birthdayError" /><small v-if="birthdayError" class="block text-red-600">You must be at least {{ posting?.minimum_age || 18 }} years old to apply.</small></label>
          <label class="space-y-1">Province *<Select v-model="location.province_id" :options="provinceOptions" optionLabel="name" optionValue="province_id" placeholder="Select province" size="small" fluid filter @change="onProvinceChange" /></label>
          <label class="space-y-1">City / Municipality *<Select v-model="location.city_id" :options="cityOptions" optionLabel="name" optionValue="city_id" placeholder="Select city" size="small" fluid filter :disabled="!location.province_id" @change="onCityChange" /></label>
          <label class="space-y-1">Barangay *<Select v-model="location.barangay_code" :options="barangayOptions" optionLabel="name" optionValue="code" placeholder="Select barangay" size="small" fluid filter :disabled="!location.city_id" @change="onBarangayChange" /></label>
          <label class="space-y-1 sm:col-span-2">Address *<Textarea v-model.trim="form.address" rows="2" autoResize placeholder="House/Lot, Street, Building" class="w-full" /></label>
        </div>
        <label class="block space-y-1">Email *
          <InputText v-model.trim="form.email" type="email" placeholder="name@example.com" size="small" fluid required />
        </label>
        <div class="space-y-3 rounded-xl border border-slate-200 p-3">
          <div class="flex items-center justify-between gap-2">
            <div>
              <p class="font-medium text-slate-800">Attachments <span class="font-normal text-slate-500">(optional)</span></p>
              <p class="text-xs text-slate-500">PDF, Word, JPG or PNG. Maximum 5 MB per file.</p>
            </div>
            <Button type="button" label="Add attachment" icon="pi pi-plus" severity="secondary" outlined size="small" @click="addAttachment" />
          </div>
          <div v-if="!attachments.length" class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">No attachments added.</div>
          <div v-for="attachment in attachments" :key="attachment.id" class="space-y-2 rounded-lg border border-orange-100 bg-orange-50/50 p-3">
            <div class="grid gap-2 sm:grid-cols-[12rem_minmax(0,1fr)_auto] sm:items-center">
              <Select v-model="attachment.document_type" :options="documentTypes" placeholder="Document type" size="small" fluid />
              <input type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="block w-full rounded-lg border border-slate-200 bg-white p-2 text-xs" @change="selectAttachment(attachment, $event)" />
              <Button type="button" icon="pi pi-trash" severity="danger" text size="small" aria-label="Remove attachment" @click="confirmRemoveAttachment(attachment)" />
            </div>
            <div v-if="attachment.file" class="flex items-center gap-3 text-xs text-slate-600">
              <img v-if="attachment.preview" :src="attachment.preview" alt="Selected document preview" class="h-12 w-12 rounded-lg object-cover" />
              <span v-else class="flex h-12 w-12 items-center justify-center rounded-lg bg-white font-semibold text-orange-700">{{ attachment.file.name.split('.').pop()?.toUpperCase() }}</span>
              <span class="min-w-0 flex-1 truncate">{{ attachment.file.name }}</span>
            </div>
          </div>
        </div>
        <Message v-if="formError" severity="error" :closable="false">{{ formError }}</Message>
        <div class="flex justify-end gap-2 pt-2">
          <Button type="button" label="Cancel" severity="secondary" text size="small" @click="applyVisible = false" />
          <Button type="submit" label="Submit application" severity="warn" size="small" :loading="submitting" />
        </div>
      </form>
    </Dialog>
  </JobPortalLayout>
</template>

<script setup lang="ts">
import JobPortalLayout from './JobPortalLayout.vue'
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import hrService, { type JobPosting, type JobPostingStage } from '../../../../services/hr.services'

const route = useRoute()
const router = useRouter()
const confirm = useConfirm()
const toast = useToast()
const posting = ref<JobPosting | null>(null)
const loading = ref(false)
const applyVisible = ref(false)
const submitting = ref(false)
const applicationSubmitted = ref(false)
const formError = ref('')
const form = reactive({ first_name: '', middle_name: '', last_name: '', email: '', phone: '', birthday: null as Date | null, address: '', province: '', city: '', barangay: '' })
const minimumBirthday = computed(() => {
  const date = new Date()
  date.setFullYear(date.getFullYear() - Number(posting.value?.minimum_age || 18))
  return date
})
const birthdayError = computed(() => Boolean(form.birthday && form.birthday > minimumBirthday.value))
const location = reactive<{ province_id: string | number | null; city_id: string | number | null; barangay_code: string | number | null }>({ province_id: null, city_id: null, barangay_code: null })
const provinceOptions = ref<any[]>([])
const cityOptions = ref<any[]>([])
const barangayOptions = ref<any[]>([])
const documentTypes = ['Resume', 'CoverLetter', 'ID', 'Certificate', 'Portfolio', 'Other']
type Attachment = { id: number; document_type: string; file: File | null; preview: string }
const attachments = ref<Attachment[]>([])
let nextAttachmentId = 1
const appliedStorageKey = computed(() => `furnisync-job-applied:${String(route.params.id)}`)

const fetchPosting = async () => {
  loading.value = true
  try {
    const response = await hrService.getPortalJobPosting(route.params.id as string)
    posting.value = response.data
    applicationSubmitted.value = Boolean(response.has_applied) || localStorage.getItem(appliedStorageKey.value) === 'true'
  } finally {
    loading.value = false
  }
}

const loadProvinces = async () => {
  try {
    const response = await hrService.api.get('/api/locations/provinces')
    provinceOptions.value = response.data?.data || response.data || []
  } catch (_) {
    formError.value = 'Unable to load province options. Please try again.'
  }
}

const onProvinceChange = async () => {
  location.city_id = null
  location.barangay_code = null
  cityOptions.value = []
  barangayOptions.value = []
  form.city = ''
  form.barangay = ''
  const province = provinceOptions.value.find((item) => String(item.province_id) === String(location.province_id))
  form.province = province?.name || ''
  if (!location.province_id) return
  const response = await hrService.api.get('/api/locations/cities', { params: { province_id: location.province_id } })
  cityOptions.value = response.data?.data || response.data || []
}

const onCityChange = async () => {
  location.barangay_code = null
  barangayOptions.value = []
  form.barangay = ''
  const city = cityOptions.value.find((item) => String(item.city_id) === String(location.city_id))
  form.city = city?.name || ''
  if (!location.city_id) return
  const response = await hrService.api.get('/api/locations/barangays', { params: { city_id: location.city_id } })
  barangayOptions.value = response.data?.data || response.data || []
}

const onBarangayChange = () => {
  const barangay = barangayOptions.value.find((item) => String(item.code) === String(location.barangay_code))
  form.barangay = barangay?.name || ''
}

const stages = computed<JobPostingStage[]>(() => posting.value?.screeningStages || posting.value?.screening_stages || [])
const storeLabel = computed(() => posting.value?.store?.store_name || posting.value?.store?.name || 'Store opening')
const roleLabel = computed(() => posting.value?.role?.display_name || posting.value?.role?.name || posting.value?.department || 'Role')
const employmentTypeLabels: Record<string, string> = {
  full_time: 'Full Time',
  part_time: 'Part Time',
  contract: 'Contract',
  intern: 'Intern',
}
const employmentTypeLabel = computed(() => employmentTypeLabels[posting.value?.employment_type || 'full_time'] || 'Full Time')
const formatCurrency = (value: number | string | undefined) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 0 }).format(Number(value || 0))

const addAttachment = () => attachments.value.push({ id: nextAttachmentId++, document_type: '', file: null, preview: '' })

const selectAttachment = (attachment: Attachment, event: Event) => {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (attachment.preview) URL.revokeObjectURL(attachment.preview)
  attachment.preview = ''
  attachment.file = null
  if (!file) return
  if (file.size > 5 * 1024 * 1024) {
    formError.value = 'The attachment must be 5 MB or smaller.'
    return
  }
  formError.value = ''
  attachment.file = file
  if (file.type.startsWith('image/')) attachment.preview = URL.createObjectURL(file)
}

const removeAttachment = (attachment: Attachment) => {
  if (attachment.preview) URL.revokeObjectURL(attachment.preview)
  attachments.value = attachments.value.filter((item) => item.id !== attachment.id)
}

const confirmRemoveAttachment = (attachment: Attachment) => {
  confirm.require({
    header: 'Remove attachment?',
    message: 'You will need to choose the file again before submitting.',
    acceptProps: { label: 'Remove', severity: 'danger' },
    rejectProps: { label: 'Keep file', severity: 'secondary', outlined: true },
    accept: () => removeAttachment(attachment),
  })
}

const submitApplication = async () => {
  if (submitting.value) return
  if (!form.birthday || birthdayError.value) {
    formError.value = !form.birthday
      ? 'Enter your birthday to confirm you meet the minimum age for this job.'
      : `You must be at least ${posting.value?.minimum_age || 18} years old to apply.`
    return
  }
  if (attachments.value.some((attachment) => !attachment.file || !attachment.document_type)) {
    formError.value = 'Choose a document type and file for each attachment, or remove incomplete attachments.'
    return
  }
  if (!form.phone || !form.address || !form.province || !form.city || !form.barangay) {
    formError.value = 'Complete your phone number and address details before submitting.'
    return
  }
  submitting.value = true
  formError.value = ''
  const payload = new FormData()
  payload.append('first_name', form.first_name)
  payload.append('middle_name', form.middle_name)
  payload.append('last_name', form.last_name)
  payload.append('email', form.email)
  payload.append('phone', form.phone)
  const birthdayYear = form.birthday.getFullYear()
  const birthdayMonth = String(form.birthday.getMonth() + 1).padStart(2, '0')
  const birthdayDay = String(form.birthday.getDate()).padStart(2, '0')
  payload.append('birthday', `${birthdayYear}-${birthdayMonth}-${birthdayDay}`)
  payload.append('province', form.province)
  payload.append('city', form.city)
  payload.append('barangay', form.barangay)
  payload.append('address', form.address)
  attachments.value.forEach((attachment, index) => {
    if (attachment.file) {
      payload.append(`documents[${index}]`, attachment.file)
      payload.append(`document_types[${index}]`, attachment.document_type)
    }
  })
  try {
    await hrService.applyToPortalJob(route.params.id as string, payload)
    applyVisible.value = false
    applicationSubmitted.value = true
    localStorage.setItem(appliedStorageKey.value, 'true')
    toast.add({ severity: 'success', summary: 'Application sent', detail: 'The hiring team received your application.', life: 4000 })
    attachments.value.forEach((attachment) => { if (attachment.preview) URL.revokeObjectURL(attachment.preview) })
    attachments.value = []
  } catch (error: any) {
    const errors = error.response?.data?.errors
    formError.value = errors ? Object.values(errors).flat().map(String).join(' ') : (error.response?.data?.message || 'Please try again.')
  } finally {
    submitting.value = false
  }
}

onMounted(() => { fetchPosting(); loadProvinces() })
onBeforeUnmount(() => attachments.value.forEach((attachment) => { if (attachment.preview) URL.revokeObjectURL(attachment.preview) }))
</script>
