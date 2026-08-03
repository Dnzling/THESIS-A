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
                <Tag :value="displayStatusLabel" :severity="statusSeverity(displayStatusLabel)" />
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
  
              <div v-else class="space-y-4">
                <div class="space-y-3">
                <DatePicker v-model="interviewForm.interview_date" :minDate="new Date()" showTime hourFormat="12" class="w-full"
                  placeholder="Schedule Interview Date" showIcon />
                <Select v-model="interviewForm.interview_type" :options="interviewTypeOptions" optionLabel="label"
                  optionValue="value" placeholder="Interview type" class="w-full" />
                <Textarea v-model="interviewForm.notes" rows="3" class="w-full" placeholder="Interview notes" />
                <Message v-if="errorMessage" severity="error" :closable="false">{{ errorMessage }}</Message>
                <Button :label="interviewButtonLabel" icon="pi pi-calendar-plus" severity="info" fluid :loading="submitting"
                  @click="scheduleInterview" />
                </div>

                <div v-if="application?.interviews?.length" class="space-y-3">
                  <Message severity="info" :closable="false">
                    Interview attempts are already recorded. You can schedule another attempt below before opening decision.
                  </Message>
                  <div v-for="(interview, index) in application.interviews" :key="interview.id" class="rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="flex items-center justify-between gap-3">
                      <div>
                        <p class="text-sm font-semibold text-slate-900">Attempt {{ application.interviews.length - index }}</p>
                        <p class="text-xs text-slate-500">{{ interview.interview_type }}</p>
                      </div>
                      <Tag :value="formatDate(interview.interview_date)" severity="contrast" />
                    </div>
                    <p v-if="interview.notes" class="mt-2 text-xs text-slate-500">{{ interview.notes }}</p>
                  </div>
                </div>

                <div class="pt-6">
                 
                  <div class="grid gap-2 md:grid-cols-2">
                  
                    <Button label="Reject" icon="pi pi-times" severity="danger" outlined :disabled="rejecting" fluid @click="showRejectDialog = true" />
                      <Button label="Hire" icon="pi pi-check" severity="success" :loading="hiring" fluid @click="router.push({ name: 'hr.job-applications.onboarding', params: { applicationId: route.params.applicationId } })" />
                  </div>
                </div>
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

    <Dialog v-model:visible="showRejectDialog" modal header="Reject Applicant" :style="{ width: 'min(32rem, 95vw)' }">
      <div class="space-y-4">
        <Select
          v-model="rejection.reason"
          :options="rejectionReasons"
          optionLabel="label"
          optionValue="value"
          placeholder="Select rejection reason"
          class="w-full"
        />
        <Textarea
          v-model="rejection.notes"
          rows="4"
          class="w-full"
          placeholder="Add a concise explanation for the applicant record."
        />
        <Message v-if="rejectionError" severity="error" :closable="false">{{ rejectionError }}</Message>
        <div class="flex justify-end gap-2">
          <Button label="Cancel" severity="secondary" text @click="showRejectDialog = false" />
          <Button label="Confirm Reject" icon="pi pi-times" severity="danger" :loading="rejecting" @click="rejectApplicant" />
        </div>
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
const hiring = ref(false)
const rejecting = ref(false)
const errorMessage = ref('')
const showRejectDialog = ref(false)
const rejectionError = ref('')
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

const rejection = reactive({
  reason: '',
  notes: '',
})

const rejectionReasons = [
  { label: 'Qualifications Mismatch', value: 'Qualifications Mismatch' },
  { label: 'Compensation Mismatch', value: 'Compensation Mismatch' },
  { label: 'Incomplete Requirements', value: 'Incomplete Requirements' },
  { label: 'No Show / Unavailable', value: 'No Show / Unavailable' },
  { label: 'Position Closed', value: 'Position Closed' },
  { label: 'Other', value: 'Other' },
]

const applicantName = computed(() => application.value?.full_name || `${application.value?.first_name || ''} ${application.value?.last_name || ''}`.trim())
const interviewList = computed(() => Array.isArray(application.value?.interviews) ? application.value.interviews : [])
const upcomingInterview = computed(() => {
  const now = new Date()
  return [...interviewList.value]
    .filter((interview: any) => interview?.interview_date && new Date(interview.interview_date) > now)
    .sort((a: any, b: any) => new Date(a.interview_date).getTime() - new Date(b.interview_date).getTime())[0] || null
})
const displayStatusLabel = computed(() => upcomingInterview.value ? 'Upcoming Interview' : (application.value?.status || 'Applied'))
const canScheduleInterview = computed(() => !['hired', 'rejected'].includes(String(application.value?.status || '').toLowerCase()))
const interviewButtonLabel = computed(() => (interviewList.value.length ? 'Schedule Another Interview' : 'Schedule Interview'))
const isHired = computed(() => String(application.value?.status || '').toLowerCase() === 'hired')
const previewIsImage = computed(() => previewMimeType.value.startsWith('image/'))

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

const hireApplicant = async () => {
  if (hiring.value) return

  hiring.value = true
  errorMessage.value = ''
  try {
    const response = await hrService.api.post(`/api/job-portal/recruitment/applications/${route.params.applicationId}/hire`)
    toast.add({
      severity: 'success',
      summary: 'Applicant hired',
      detail: response?.data?.message || 'Applicant has been hired successfully.',
      life: 2500,
    })

    router.push({ name: 'hr.job-applications.onboarding', params: { applicationId: route.params.applicationId } })
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message || 'Unable to hire applicant.'
  } finally {
    hiring.value = false
  }
}

const rejectApplicant = async () => {
  rejectionError.value = ''
  if (!rejection.reason) {
    rejectionError.value = 'Please select a rejection reason.'
    return
  }

  rejecting.value = true
  try {
    await hrService.rejectApplicant(route.params.applicationId as string, {
      reason: rejection.reason,
      notes: rejection.notes || undefined,
    })
    toast.add({
      severity: 'success',
      summary: 'Applicant rejected',
      detail: 'The rejection decision has been recorded.',
      life: 2500,
    })
    showRejectDialog.value = false
    router.push({ name: 'hr.job-postings' })
  } catch (error: any) {
    const firstError = error?.response?.data?.errors ? Object.values(error.response.data.errors)[0] : null
    rejectionError.value = Array.isArray(firstError) ? firstError[0] : (error?.response?.data?.message || 'Unable to reject applicant.')
  } finally {
    rejecting.value = false
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
const statusSeverity = (status?: string) => ({ Applied: 'info', Screening: 'contrast', Interview: 'warn', 'Upcoming Interview': 'warn', Offer: 'success', Accepted: 'success', Hired: 'success', Rejected: 'danger' }[status || 'Applied'] || 'secondary')

watch(previewVisible, (visible) => {
  if (!visible) revokePreviewUrl()
})

onMounted(loadApplication)
</script>
