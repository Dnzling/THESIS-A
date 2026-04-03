<template>
  <Dialog
    :visible="!!application"
    modal
    :style="{ width: 'min(76rem, 97vw)' }"
    :breakpoints="{ '1024px': '97vw' }"
    @update:visible="emit('close')"
  >
    <template #header>
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-600">Applicant Review</p>
        <h2 class="text-2xl font-semibold text-slate-900">{{ applicantName }}</h2>
        <p class="text-sm text-slate-500">{{ application?.email }} - {{ application?.phone || 'No phone provided' }}</p>
      </div>
    </template>

    <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
      <div class="space-y-6">
        <Card class="border border-slate-200 shadow-none">
          <template #content>
            <div class="grid gap-4 md:grid-cols-4">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</p>
                <Tag class="mt-2" :value="application?.status || 'Applied'" :severity="statusSeverity(application?.status)" />
              </div>
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Applicant Type</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ application?.employee_id ? 'Internal' : 'External' }}</p>
              </div>
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Application Date</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ formatDate(application?.created_at) }}</p>
              </div>
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Role Applied</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ application?.jobPosting?.title || 'Job posting' }}</p>
              </div>
            </div>
          </template>
        </Card>

        <Card class="border border-slate-200 shadow-none">
          <template #title>Applicant Profile</template>
          <template #content>
            <div class="grid gap-4 md:grid-cols-2">
              <div class="rounded-2xl bg-blue-50/70 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Current Position</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ application?.current_position || 'Not provided' }}</p>
              </div>
              <div class="rounded-2xl bg-blue-50/70 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Current Company</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ application?.current_company || 'Not provided' }}</p>
              </div>
            </div>
          </template>
        </Card>

        <Card class="border border-slate-200 shadow-none">
          <template #title>Documents</template>
          <template #content>
            <div v-if="application?.documents?.length" class="space-y-3">
              <div
                v-for="doc in application.documents"
                :key="doc.id"
                class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:flex-row md:items-center md:justify-between"
              >
                <div>
                  <p class="text-sm font-semibold text-slate-900">{{ doc.document_type || 'Document' }}</p>
                  <p class="text-xs text-slate-500">{{ doc.file_name || 'Attachment' }}</p>
                </div>
                <div class="flex gap-2">
                  <Button
                    label="View"
                    icon="pi pi-eye"
                    severity="info"
                    outlined
                    :loading="previewingDocumentId === doc.id"
                    @click="previewDocument(doc)"
                  />
                  <Button
                    label="Download"
                    icon="pi pi-download"
                    severity="secondary"
                    outlined
                    :loading="downloadingDocumentId === doc.id"
                    @click="downloadDocument(doc)"
                  />
                </div>
              </div>
            </div>
            <Message v-else severity="info" :closable="false">
              No applicant documents were uploaded.
            </Message>
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

      <div class="space-y-6">
        <Card class="border border-blue-100 bg-blue-50/60 shadow-none">
          <template #title>Recommended Next Action</template>
          <template #content>
            <div class="space-y-3">
              <p class="text-sm leading-6 text-slate-600">{{ actionDescription }}</p>
              <Message severity="info" :closable="false">
                The system now follows the applicant lifecycle and removes manual status switching for normal progression.
              </Message>
            </div>
          </template>
        </Card>

        <Card v-if="canScheduleInterview" class="border border-slate-200 shadow-none">
          <template #title>Schedule Interview</template>
          <template #content>
            <div class="space-y-3">
              <DatePicker v-model="interviewForm.interview_date" showTime hourFormat="12" class="w-full" />
              <Select
                v-model="interviewForm.interview_type"
                :options="interviewTypeOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Interview type"
                class="w-full"
              />
              <Textarea v-model="interviewForm.notes" rows="3" class="w-full" placeholder="Interview notes" />
              <Message v-if="interviewError" severity="error" :closable="false">{{ interviewError }}</Message>
              <Button label="Schedule Interview" icon="pi pi-calendar-plus" severity="info" :loading="schedulingInterview" fluid @click="scheduleInterview" />
            </div>
          </template>
        </Card>

        <Card v-else-if="canHireApplicant" class="border border-slate-200 shadow-none">
          <template #title>Decision Flow</template>
          <template #content>
            <div class="space-y-3">
              <p class="text-sm leading-6 text-slate-600">
                This applicant is ready for the formal decision step. Continue to the dedicated page to choose Hire or Reject and, if hired, move into guided onboarding.
              </p>
              <Button label="Open Decision Page" icon="pi pi-arrow-right" severity="info" fluid @click="openDecisionPage" />
            </div>
          </template>
        </Card>

        <Card v-else class="border border-slate-200 shadow-none">
          <template #title>Application State</template>
          <template #content>
            <Message severity="info" :closable="false">
              This applicant is currently in the <strong>{{ application?.status || 'Applied' }}</strong> stage. The available workflow action has already been completed.
            </Message>
          </template>
        </Card>
      </div>
    </div>

    <Dialog
      v-model:visible="previewVisible"
      modal
      maximizable
      :style="{ width: 'min(78rem, 96vw)' }"
      :breakpoints="{ '1024px': '96vw' }"
      header="Document Preview"
    >
      <div v-if="previewUrl" class="min-h-[70vh]">
        <img v-if="previewIsImage" :src="previewUrl" alt="Document preview" class="mx-auto max-h-[70vh] rounded-xl object-contain" />
        <iframe v-else :src="previewUrl" class="h-[70vh] w-full rounded-xl border border-slate-200" />
      </div>
    </Dialog>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import hrService from '../services/hr.services'
import axiosClient from '../axios'

const props = defineProps({
  application: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['close', 'update'])

const router = useRouter()
const toast = useToast()

const schedulingInterview = ref(false)
const previewVisible = ref(false)
const previewUrl = ref('')
const previewMimeType = ref('')
const previewingDocumentId = ref<number | null>(null)
const downloadingDocumentId = ref<number | null>(null)
const interviewError = ref('')

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

const applicantName = computed(() => props.application?.full_name || `${props.application?.first_name || ''} ${props.application?.last_name || ''}`.trim())
const previewIsImage = computed(() => previewMimeType.value.startsWith('image/'))
const normalizedStatus = computed(() => String(props.application?.status || 'Applied'))
const canScheduleInterview = computed(() => ['Applied', 'Screening'].includes(normalizedStatus.value))
const canHireApplicant = computed(() => ['Interview', 'Offer', 'Accepted'].includes(normalizedStatus.value) && !props.application?.employee_id)

const actionDescription = computed(() => {
  if (canScheduleInterview.value) {
    return 'For new and screening applicants, the system moves them forward by scheduling an interview. Their status is updated automatically after scheduling.'
  }
  if (canHireApplicant.value) {
    return 'This applicant is ready for conversion. Once hired, the system creates the employee, applies deductions, and prepares the next onboarding step.'
  }
  return 'The applicant is currently in a completed or final stage. Review their profile, documents, and timeline for reference.'
})

const formatDate = (date?: string) => (date ? new Date(date).toLocaleString('en-PH') : 'N/A')
const statusSeverity = (status?: string) => ({ Applied: 'info', Screening: 'contrast', Interview: 'warn', Offer: 'success', Accepted: 'success', Hired: 'success', Rejected: 'danger' }[status || 'Applied'] || 'secondary')

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

const downloadDocument = async (doc: any) => {
  downloadingDocumentId.value = doc.id
  try {
    const response = await axiosClient.get(`/api/job-applications/${props.application.id}/documents/${doc.id}`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(response.data)
    const link = document.createElement('a')
    link.href = url
    link.download = doc.file_name || 'document'
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Unable to download document',
      detail: error?.response?.data?.message || 'Please try again.',
      life: 3000,
    })
  } finally {
    downloadingDocumentId.value = null
  }
}

const previewDocument = async (doc: any) => {
  previewingDocumentId.value = doc.id
  try {
    revokePreviewUrl()
    const response = await axiosClient.get(`/api/job-applications/${props.application.id}/documents/${doc.id}`, { responseType: 'blob' })
    previewMimeType.value = response.data.type || doc.mime_type || ''
    previewUrl.value = URL.createObjectURL(response.data)
    previewVisible.value = true
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Unable to preview document',
      detail: error?.response?.data?.message || 'Please try again.',
      life: 3000,
    })
  } finally {
    previewingDocumentId.value = null
  }
}

const scheduleInterview = async () => {
  interviewError.value = ''
  if (!interviewForm.interview_date || !interviewForm.interview_type) {
    interviewError.value = 'Please complete the interview date and interview type.'
    return
  }

  schedulingInterview.value = true
    try {
      await hrService.scheduleInterview(props.application.id, {
        interview_date: toLocalDateTimeString(interviewForm.interview_date),
        interview_type: interviewForm.interview_type,
        notes: interviewForm.notes || undefined,
      })
    toast.add({ severity: 'success', summary: 'Interview scheduled', detail: 'Applicant has been notified by email.', life: 2500 })
    emit('update')
  } catch (error: any) {
    const firstError = error?.response?.data?.errors
      ? Object.values(error.response.data.errors)[0]
      : null
    interviewError.value = Array.isArray(firstError)
      ? firstError[0]
      : (error?.response?.data?.message || 'Unable to schedule the interview.')
  } finally {
    schedulingInterview.value = false
  }
}

const openDecisionPage = () => {
  emit('close')
  router.push({ name: 'hr.job-applications.decision', params: { applicationId: props.application.id } })
}

watch(
  () => props.application,
  () => {
    interviewError.value = ''
  },
  { immediate: true },
)

watch(previewVisible, (value) => {
  if (!value) {
    revokePreviewUrl()
  }
})

</script>
