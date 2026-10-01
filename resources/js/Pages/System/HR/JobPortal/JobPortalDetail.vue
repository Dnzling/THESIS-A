<template>
  <JobPortalLayout>
    <div class="py-6 lg:py-10">
      <div class="space-y-4">
    <div>
      <Button label="Back to Listings" icon="pi pi-arrow-left" severity="secondary" text
        @click="router.push({ name: 'job-portal.index' })" />
  
      <div v-if="loading" class="mt-2 space-y-3">
        <Skeleton height="11rem" />
        <Skeleton height="10rem" />
      </div>
  
      <div v-else class="mt-2 grid gap-4 xl:grid-cols-[1.4fr_0.6fr]">
        <div class="space-y-6">
          <Card class="border border-orange-100 shadow-sm">
            <template #content>
              <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                  <Tag value="Open" severity="success" />
                  <Tag :value="employmentTypeLabel" severity="warn" />
                  <span class="text-xs uppercase tracking-wide text-surface-500">{{ storeLabel }}</span>
                </div>
                <div>
                  <h1 class="text-3xl font-semibold text-surface-900">{{ roleLabel }}</h1>
                  <p class="mt-1 text-base text-surface-600">{{ posting?.title }}</p>
                </div>
                <p class="text-sm leading-7 text-surface-600">{{ posting?.description }}</p>
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
                  <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Store</p>
                  <p class="mt-1 text-sm font-semibold text-surface-900">{{ storeLabel }}</p>
                </div>
                <Button label="Apply Job" icon="pi pi-send" severity="warn" fluid @click="applyVisible = true" />
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
        <p class="text-slate-500">Tell the hiring team who you are and attach your document. No account is needed.</p>
        <div class="grid gap-3 sm:grid-cols-3">
          <label class="space-y-1">First name *<InputText v-model.trim="form.first_name" size="small" fluid required /></label>
          <label class="space-y-1">Middle name<InputText v-model.trim="form.middle_name" size="small" fluid /></label>
          <label class="space-y-1">Last name *<InputText v-model.trim="form.last_name" size="small" fluid required /></label>
        </div>
        <label class="block space-y-1">Email *<InputText v-model.trim="form.email" type="email" size="small" fluid required /></label>
        <label class="block space-y-1">Document type *
          <Select v-model="form.document_type" :options="documentTypes" placeholder="Choose a document type" size="small" fluid />
        </label>
        <label class="block space-y-1">Attachment *
          <input type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="block w-full rounded-lg border border-slate-200 p-2 text-xs" @change="selectDocument" />
        </label>
        <p class="text-xs text-slate-500">PDF, Word, JPG or PNG. Maximum 5 MB.</p>
        <div v-if="documentFile" class="flex items-center gap-3 rounded-xl border border-orange-100 bg-orange-50 p-3">
          <img v-if="documentPreview" :src="documentPreview" alt="Selected document preview" class="h-16 w-16 rounded-lg object-cover" />
          <div v-else class="flex h-16 w-16 items-center justify-center rounded-lg bg-white text-xs font-semibold text-orange-700">{{ documentFile.name.split('.').pop()?.toUpperCase() }}</div>
          <span class="min-w-0 flex-1 truncate text-xs">{{ documentFile.name }}</span>
          <Button type="button" icon="pi pi-trash" severity="danger" text size="small" aria-label="Remove attachment" @click="confirmRemoveDocument" />
        </div>
        <Message v-if="formError" severity="error" :closable="false">{{ formError }}</Message>
        <div class="flex justify-end gap-2 pt-2">
          <Button type="button" label="Cancel" severity="secondary" text size="small" @click="applyVisible = false" />
          <Button type="submit" label="Submit application" severity="warn" size="small" :loading="submitting" :disabled="!documentFile || !form.document_type" />
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
const formError = ref('')
const form = reactive({ first_name: '', middle_name: '', last_name: '', email: '', document_type: '' })
const documentTypes = ['Resume', 'CoverLetter', 'ID', 'Certificate', 'Portfolio', 'Other']
const documentFile = ref<File | null>(null)
const documentPreview = ref('')

const fetchPosting = async () => {
  loading.value = true
  try {
    const response = await hrService.getPortalJobPosting(route.params.id as string)
    posting.value = response.data
  } finally {
    loading.value = false
  }
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

const clearDocument = () => {
  if (documentPreview.value) URL.revokeObjectURL(documentPreview.value)
  documentPreview.value = ''
  documentFile.value = null
  const input = document.querySelector<HTMLInputElement>('input[type="file"]')
  if (input) input.value = ''
}

const selectDocument = (event: Event) => {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (documentPreview.value) URL.revokeObjectURL(documentPreview.value)
  documentPreview.value = ''
  documentFile.value = null
  if (!file) return
  if (file.size > 5 * 1024 * 1024) {
    formError.value = 'The attachment must be 5 MB or smaller.'
    return
  }
  formError.value = ''
  documentFile.value = file
  if (file.type.startsWith('image/')) documentPreview.value = URL.createObjectURL(file)
}

const confirmRemoveDocument = () => {
  confirm.require({
    header: 'Remove attachment?',
    message: 'You will need to choose the file again before submitting.',
    acceptProps: { label: 'Remove', severity: 'danger' },
    rejectProps: { label: 'Keep file', severity: 'secondary', outlined: true },
    accept: clearDocument,
  })
}

const submitApplication = async () => {
  if (!documentFile.value || !form.document_type || submitting.value) return
  submitting.value = true
  formError.value = ''
  const payload = new FormData()
  payload.append('first_name', form.first_name)
  payload.append('middle_name', form.middle_name)
  payload.append('last_name', form.last_name)
  payload.append('email', form.email)
  payload.append('documents[0]', documentFile.value)
  payload.append('document_types[0]', form.document_type)
  try {
    await hrService.applyToPortalJob(route.params.id as string, payload)
    applyVisible.value = false
    toast.add({ severity: 'success', summary: 'Application sent', detail: 'The hiring team received your application.', life: 4000 })
    clearDocument()
  } catch (error: any) {
    const errors = error.response?.data?.errors
    formError.value = errors ? Object.values(errors).flat().map(String).join(' ') : (error.response?.data?.message || 'Please try again.')
  } finally {
    submitting.value = false
  }
}

onMounted(fetchPosting)
onBeforeUnmount(() => { if (documentPreview.value) URL.revokeObjectURL(documentPreview.value) })
</script>
