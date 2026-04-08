<template>
  <JobPortalLayout>
    <div class="py-6 lg:py-10">
      <div class="mx-auto max-w-5xl px-6">
      <div class="flex flex-wrap items-center gap-3">
        <Button label="Back to Applications" icon="pi pi-arrow-left" severity="secondary" text @click="router.push({ name: 'job-portal.dashboard' })" />
        <Button
          v-if="application?.job_posting_id"
          label="View Posted Job"
          icon="pi pi-external-link"
          severity="warn"
          outlined
          @click="router.push({ name: 'job-portal.detail', params: { id: application.job_posting_id } })"
        />
      </div>

      <div v-if="loading" class="mt-4 grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <Card v-for="item in 4" :key="item" class="border border-gray-50 shadow-sm">
          <template #content>
            <div class="space-y-4">
              <Skeleton width="12rem" height="1.5rem" />
              <Skeleton width="8rem" height="1rem" />
              <Skeleton width="100%" height="6rem" />
            </div>
          </template>
        </Card>
      </div>

      <div v-else class="mt-4 grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <div class="space-y-6">
          <Card class="border border-gray-50 shadow-sm">
            <template #title>{{ postingRoleLabel }}</template>
            <template #subtitle>{{ postingTitle }}</template>
            <template #content>
              <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                  <Tag :value="application?.status || 'Applied'" :severity="statusSeverity(application?.status)" />
                  <span class="text-xs uppercase tracking-wide text-surface-500">
                    {{ postingStore }}
                  </span>
                </div>
                <div class="grid gap-3 rounded-2xl bg-orange-50/60 p-4 sm:grid-cols-2">
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Store</p>
                    <p class="mt-1 text-sm font-semibold text-surface-900">{{ postingStore }}</p>
                  </div>
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Role</p>
                    <p class="mt-1 text-sm font-semibold text-surface-900">{{ postingRoleLabel }}</p>
                  </div>
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Applicant</p>
                  <p class="mt-1 text-sm font-semibold text-surface-900">{{ application?.first_name }} {{ application?.last_name }}</p>
                  <p class="text-sm text-surface-500">{{ application?.email }} - {{ application?.phone }}</p>
                </div>
                <div class="grid gap-3 rounded-2xl bg-slate-50 p-4 md:grid-cols-2">
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Birthday</p>
                    <p class="mt-1 text-sm font-semibold text-surface-900">{{ formatDateOnly(application?.birthday) }}</p>
                  </div>
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">City / Barangay</p>
                    <p class="mt-1 text-sm font-semibold text-surface-900">
                      {{ application?.city || 'N/A' }} / {{ application?.barangay || 'N/A' }} / {{ application?.province || 'N/A' }}
                    </p>
                  </div>
                  <div class="md:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Address</p>
                    <p class="mt-1 text-sm font-semibold text-surface-900">{{ application?.address || 'N/A' }}</p>
                  </div>
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Current Position</p>
                    <p class="mt-1 text-sm font-semibold text-surface-900">{{ application?.current_position || 'Not provided' }}</p>
                  </div>
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Current Company</p>
                    <p class="mt-1 text-sm font-semibold text-surface-900">{{ application?.current_company || 'Not provided' }}</p>
                  </div>
                </div>
              </div>
            </template>
          </Card>

          <Card class="border border-gray-50 shadow-sm">
            <template #title>Timeline</template>
            <template #content>
              <div class="space-y-3">
                <div v-for="item in application?.timeline || []" :key="item.id" class="rounded-2xl bg-orange-50/60 p-4">
                  <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-surface-900">{{ item.status }}</p>
                    <span class="text-xs text-surface-500">{{ formatDate(item.changed_at) }}</span>
                  </div>
                  <p class="mt-1 text-xs text-surface-500">{{ item.notes || 'No additional notes.' }}</p>
                </div>
              </div>
            </template>
          </Card>
        </div>

        <div class="space-y-6">
          <Card class="border border-gray-50 shadow-sm">
            <template #title>Attachments</template>
            <template #content>
              <div class="space-y-2">
                <Button
                  v-for="file in application?.documents || []"
                  :key="file.id"
                  :label="file.document_type"
                  icon="pi pi-eye"
                  severity="secondary"
                  outlined
                  fluid
                  @click="openDocumentViewer(file)"
                />
                <Message v-if="!(application?.documents || []).length" severity="warn" :closable="false">
                  No attachments were uploaded for this application.
                </Message>
              </div>
            </template>
          </Card>

          <Card class="border border-gray-50 shadow-sm">
            <template #title>Responses</template>
            <template #content>
              <div class="space-y-4">
                <div v-for="interview in application?.interviews || []" :key="interview.id" class="rounded-2xl bg-slate-50 p-4">
                  <p class="text-sm font-semibold text-surface-900">Interview Scheduled</p>
                  <p class="mt-1 text-xs text-surface-500">{{ interview.interview_type }} - {{ formatDate(interview.interview_date) }}</p>
                  <p v-if="interview.notes" class="mt-2 text-xs text-surface-500">{{ interview.notes }}</p>
                </div>

                <div v-if="application?.offer" class="rounded-2xl border border-green-200 bg-green-50 p-4">
                  <p class="text-sm font-semibold text-green-900">Offer Update</p>
                  <p class="mt-1 text-xs text-green-800">Position: {{ application.offer.position }}</p>
                  <p class="text-xs text-green-800">Department: {{ application.offer.department }}</p>
                  <p class="text-xs text-green-800">Start Date: {{ formatDate(application.offer.start_date) }}</p>
                </div>

                <Message v-if="!(application?.interviews || []).length && !application?.offer" severity="warn" :closable="false">
                  No employer response has been posted yet.
                </Message>
              </div>
            </template>
          </Card>
        </div>
      </div>
      </div>
    </div>
  </JobPortalLayout>

  <Dialog v-model:visible="viewerOpen" modal header="Document Preview" :style="{ width: 'min(52rem, 96vw)' }">
    <div v-if="viewerUrl" class="space-y-4">
      <p class="text-sm font-medium text-surface-700">{{ viewerLabel }}</p>
      <div class="rounded-2xl border border-surface-200 bg-surface-50 p-3">
        <img v-if="viewerKind === 'image'" :src="viewerUrl" class="max-h-[70vh] w-full rounded-xl object-contain" />
        <iframe v-else-if="viewerKind === 'pdf'" :src="viewerUrl" class="h-[70vh] w-full rounded-xl"></iframe>
        <div v-else class="space-y-2 text-sm text-surface-600">
          <p>Preview not available for this file type.</p>
          <a :href="viewerUrl" target="_blank" class="text-orange-600 underline">Open in new tab</a>
        </div>
      </div>
      <div class="flex justify-end">
        <Button label="Download" icon="pi pi-download" severity="secondary" @click="downloadFile(viewerFile)" />
      </div>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import JobPortalLayout from '../JobPortal/JobPortalLayout.vue'
import { onBeforeUnmount, onMounted, ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import hrService, { type JobApplication } from '../../../../services/hr.services'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const application = ref<JobApplication | null>(null)
const loading = ref(false)
const viewerOpen = ref(false)
const viewerUrl = ref('')
const viewerLabel = ref('')
const viewerKind = ref<'image' | 'pdf' | 'other'>('other')
const viewerFile = ref<any>(null)

const postingRef = computed(() => application.value?.jobPosting || application.value?.job_posting || application.value?.posting || null)
const postingTitle = computed(() => postingRef.value?.title || 'N/A')
const postingDepartment = computed(() => postingRef.value?.department || '')
const postingStore = computed(() => postingRef.value?.store?.store_name || postingRef.value?.store?.name || 'Store')
const postingRoleLabel = computed(() =>
  postingRef.value?.role?.display_name
  || postingRef.value?.role?.name
  || postingRef.value?.role_name
  || postingRef.value?.position
  || postingRef.value?.title
  || 'N/A',
)

const fetchApplication = async () => {
  loading.value = true
  try {
    const response = await hrService.getApplicantApplication(route.params.id as string)
    application.value = response.data
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Unable to load application',
      detail: error?.response?.data?.message || 'Please try again.',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const downloadFile = async (file: any) => {
  try {
    const blob = await hrService.downloadApplicantDocument(route.params.id as string, file.id)
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = file.file_name || 'document'
    link.click()
    window.URL.revokeObjectURL(url)
    toast.add({ severity: 'success', summary: 'Download started', detail: `${file.document_type} is being downloaded.`, life: 2200 })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Unable to download file',
      detail: error?.response?.data?.message || 'Please try again.',
      life: 3000,
    })
  }
}

const openDocumentViewer = async (file: any) => {
  try {
    const blob = await hrService.downloadApplicantDocument(route.params.id as string, file.id)
    viewerFile.value = file
    viewerLabel.value = file.file_name || file.document_type || 'Document'
    if (viewerUrl.value) {
      window.URL.revokeObjectURL(viewerUrl.value)
    }
    viewerUrl.value = window.URL.createObjectURL(blob)
    const mime = String(file.mime_type || blob.type || '').toLowerCase()
    if (mime.startsWith('image/')) viewerKind.value = 'image'
    else if (mime === 'application/pdf') viewerKind.value = 'pdf'
    else viewerKind.value = 'other'
    viewerOpen.value = true
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Unable to preview file',
      detail: error?.response?.data?.message || 'Please try again.',
      life: 3000,
    })
  }
}

const formatDate = (value?: string) => (value ? new Date(value).toLocaleString('en-PH') : 'N/A')
const formatDateOnly = (value?: string | Date | null) => {
  if (!value) return 'N/A'
  const date = typeof value === 'string' ? new Date(value) : value
  return date.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}
const statusSeverity = (status?: string) => ({ Applied: 'warn', Screening: 'contrast', Interview: 'warn', Offer: 'success', Hired: 'success', Rejected: 'danger' }[status || 'Applied'] || 'secondary')

onMounted(fetchApplication)

watch(viewerOpen, (value) => {
  if (value) return
  if (viewerUrl.value) {
    window.URL.revokeObjectURL(viewerUrl.value)
  }
  viewerUrl.value = ''
  viewerLabel.value = ''
  viewerKind.value = 'other'
  viewerFile.value = null
})

onBeforeUnmount(() => {
  if (viewerUrl.value) {
    window.URL.revokeObjectURL(viewerUrl.value)
  }
})
</script>
