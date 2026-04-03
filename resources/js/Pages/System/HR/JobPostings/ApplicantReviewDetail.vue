<template>
  <div class="min-h-full">
    <div class="mx-auto max-w-7xl px-6">
      <Button label="Back to Applicants" icon="pi pi-arrow-left" severity="secondary" text @click="goBack" />
  
      <div v-if="loading" class="mt-4 space-y-3">
        <Skeleton height="10rem" />
        <Skeleton height="16rem" />
      </div>
  
      <div v-else class="mt-4 grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
        <div class="space-y-4">
          <Card class="border border-slate-200 shadow-none">
            <template #content>
              <div class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-600">Applicant Review</p>
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-900">{{ applicantName }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ application?.email }} - {{ application?.phone || 'No phone provided' }}</p>
                  </div>
                  <Tag :value="application?.status || 'Applied'" :severity="statusSeverity(application?.status)" />
                </div>
  
                
              </div>
            </template>
          </Card>
  
          <Card class="border border-slate-200 shadow-none">
            <template #title>Documents</template>
            <template #content>
              <div v-if="application?.documents?.length" class="space-y-3">
                <div v-for="doc in application.documents" :key="doc.id"
                  class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                  <div>
                    <p class="font-semibold text-slate-900">{{ doc.document_type || 'Document' }}</p>
                    <p class="text-xs text-slate-500">{{ doc.file_name || 'Attachment' }}</p>
                  </div>
                  <div class="flex gap-2">
                    <Button label="View" icon="pi pi-eye" severity="info" outlined @click="previewDocument(doc)" />
                    <Button label="Download" icon="pi pi-download" severity="secondary" outlined
                      @click="downloadDocument(doc)" />
                  </div>
                </div>
              </div>
              <Message v-else severity="info" :closable="false">No documents uploaded.</Message>
            </template>
          </Card>
        </div>
  
        <div class="space-y-4">
          <Card class="border border-blue-100 bg-blue-50/70 shadow-none">
            <template #title>Action</template>
            <template #content>
              <div v-if="isHired" class="space-y-3">
                <Message severity="success" :closable="false">This applicant is already hired.</Message>
                <Button v-if="application?.employee_id" label="View Employee Profile" icon="pi pi-id-card"
                  severity="success" fluid
                  @click="router.push({ name: 'hr.employees.view', params: { id: application.employee_id } })" />
              </div>
  
              <div v-else-if="canScheduleInterview" class="space-y-3">
                <DatePicker v-model="interviewForm.interview_date" :minDate="new Date()" showTime hourFormat="12" class="w-full"
                  placeholder="Schedule Interview Date" showIcon />
                <Select v-model="interviewForm.interview_type" :options="interviewTypeOptions" optionLabel="label"
                  optionValue="value" placeholder="Interview type" class="w-full" />
                <Textarea v-model="interviewForm.notes" rows="3" class="w-full" placeholder="Interview notes" />
                <Message v-if="errorMessage" severity="error" :closable="false">{{ errorMessage }}</Message>
                <Button label="Schedule Interview" icon="pi pi-calendar-plus" severity="info" fluid :loading="submitting"
                  @click="scheduleInterview" />
              </div>
  
              <div v-else class="space-y-3">
                <p class="text-sm leading-6 text-slate-600">
                  This applicant is already in the decision-ready stage. Continue to the dedicated decision page for Hire
                  or Reject.
                </p>
                <Message v-if="decisionLocked" severity="info" :closable="false">
                  Decision action will be available on {{ nextInterviewDateLabel }} (scheduled interview date).
                </Message>
                <Button v-else label="Open Decision Page" icon="pi pi-arrow-right" severity="info" fluid
                  @click="router.push({ name: 'hr.job-applications.decision', params: { applicationId: route.params.applicationId } })" />
              </div>
            </template>
          </Card>
  
          <Card class="border border-slate-200 shadow-none">
            <template #title>Timeline</template>
            <template #content>
              <div class="space-y-3">
                <div v-for="item in application?.timeline || []" :key="item.id" class="rounded-2xl bg-slate-50 p-4">
                  <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-slate-900">{{ item.status }}</p>
                    <span class="text-xs text-slate-500">{{ formatDate(item.changed_at) }}</span>
                  </div>
                  <p class="mt-1 text-xs text-slate-500">{{ item.notes || 'No notes provided.' }}</p>
                </div>
              </div>
            </template>
          </Card>
        </div>
      </div>
    </div>
  
    <Dialog v-model:visible="previewVisible" modal maximizable :style="{ width: 'min(78rem, 96vw)' }"
      header="Document Preview">
      <div v-if="previewUrl" class="min-h-[70vh]">
        <img v-if="previewIsImage" :src="previewUrl" alt="Document preview"
          class="mx-auto max-h-[70vh] rounded-xl object-contain" />
        <iframe v-else :src="previewUrl" class="h-[70vh] w-full rounded-xl border border-slate-200" />
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import hrService from '../../../../services/hr.services'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const application = ref<any | null>(null)
const loading = ref(false)
const submitting = ref(false)
const errorMessage = ref('')
const previewVisible = ref(false)
const previewUrl = ref('')
const previewMimeType = ref('')

const interviewTypeOptions = [
  { label: 'Screening', value: 'Screening' },
  { label: 'Technical/Skills Test', value: 'Technical/Skills Test' },
  { label: 'Final Interview', value: 'Final Interview' },
]

const interviewForm = reactive({
  interview_date: null as Date | null,
  interview_type: '',
  notes: '',
})

const applicantName = computed(() => application.value?.full_name || `${application.value?.first_name || ''} ${application.value?.last_name || ''}`.trim())
const canScheduleInterview = computed(() => ['Applied', 'Screening'].includes(String(application.value?.status || 'Applied')))
const isHired = computed(() => String(application.value?.status || '').toLowerCase() === 'hired')
const previewIsImage = computed(() => previewMimeType.value.startsWith('image/'))
const latestInterviewDate = computed(() => {
  const interviews = application.value?.interviews || []
  return interviews.length ? interviews[0].interview_date : null
})
const decisionLocked = computed(() => {
  if (!latestInterviewDate.value) return false
  const interviewDate = new Date(latestInterviewDate.value)
  const today = new Date()
  const format = (date: Date) => `${date.getFullYear()}-${date.getMonth()}-${date.getDate()}`
  return format(interviewDate) !== format(today)
})
const nextInterviewDateLabel = computed(() => latestInterviewDate.value ? new Date(latestInterviewDate.value).toLocaleDateString('en-PH', {
  month: 'short',
  day: 'numeric',
  year: 'numeric',
}) : 'scheduled interview date')

const loadApplication = async () => {
  loading.value = true
  try {
    const response = await hrService.getJobApplication(route.params.applicationId as string)
    application.value = response?.data || response
  } finally {
    loading.value = false
  }
}

const toLocalDateTimeString = (value: Date) => {
  const pad = (part: number) => String(part).padStart(2, '0')
  return `${value.getFullYear()}-${pad(value.getMonth() + 1)}-${pad(value.getDate())} ${pad(value.getHours())}:${pad(value.getMinutes())}:${pad(value.getSeconds())}`
}

const revokePreviewUrl = () => {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
    previewUrl.value = ''
    previewMimeType.value = ''
  }
}

const previewDocument = async (doc: any) => {
  revokePreviewUrl()
  const response = await hrService.api.get(`/api/job-applications/${application.value.id}/documents/${doc.id}`, { responseType: 'blob' })
  previewMimeType.value = response.data.type || doc.mime_type || ''
  previewUrl.value = URL.createObjectURL(response.data)
  previewVisible.value = true
}

const downloadDocument = async (doc: any) => {
  const response = await hrService.api.get(`/api/job-applications/${application.value.id}/documents/${doc.id}`, { responseType: 'blob' })
  const url = window.URL.createObjectURL(response.data)
  const link = document.createElement('a')
  link.href = url
  link.download = doc.file_name || 'document'
  link.click()
  window.URL.revokeObjectURL(url)
}

const scheduleInterview = async () => {
  errorMessage.value = ''
  if (!interviewForm.interview_date || !interviewForm.interview_type) {
    errorMessage.value = 'Please complete the interview date and interview type.'
    return
  }

  submitting.value = true
  try {
    await hrService.scheduleInterview(route.params.applicationId as string, {
      interview_date: toLocalDateTimeString(interviewForm.interview_date),
      interview_type: interviewForm.interview_type,
      notes: interviewForm.notes || undefined,
    })
    toast.add({ severity: 'success', summary: 'Interview scheduled', detail: 'Applicant has been notified by email.', life: 2500 })
    await loadApplication()
  } catch (error: any) {
    const firstError = error?.response?.data?.errors ? Object.values(error.response.data.errors)[0] : null
    errorMessage.value = Array.isArray(firstError) ? firstError[0] : (error?.response?.data?.message || 'Unable to schedule interview.')
  } finally {
    submitting.value = false
  }
}

const goBack = () => {
  const postingId = application.value?.job_posting_id
  if (postingId) {
    router.push({ name: 'hr.job-postings.applicants', params: { postingId } })
    return
  }
  router.push({ name: 'hr.job-postings' })
}

const formatDate = (value?: string) => (value ? new Date(value).toLocaleString('en-PH') : 'N/A')
const statusSeverity = (status?: string) => ({ Applied: 'info', Screening: 'contrast', Interview: 'warn', Offer: 'success', Accepted: 'success', Hired: 'success', Rejected: 'danger' }[status || 'Applied'] || 'secondary')

watch(previewVisible, (visible) => {
  if (!visible) revokePreviewUrl()
})

onMounted(loadApplication)
</script>
