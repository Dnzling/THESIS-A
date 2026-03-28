<template>
  <div class="min-h-full">
    <div class="mx-auto max-w-6xl px-6 py-6">
      <div class="flex flex-wrap items-center gap-3">
        <Button label="Back to Job Postings" icon="pi pi-arrow-left" severity="secondary" text @click="router.push({ name: 'hr.job-postings' })" />
        <Button v-if="application?.job_posting_id" label="View Posted Job" icon="pi pi-briefcase" severity="info" outlined @click="router.push({ name: 'hr.job-postings.detail', params: { postingId: application.job_posting_id } })" />
      </div>

      <div v-if="loading" class="mt-4 space-y-3">
        <Skeleton height="10rem" />
        <Skeleton height="16rem" />
      </div>

      <div v-else class="mt-4 grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
        <Card class="border border-blue-100 shadow-none">
          <template #content>
            <div class="space-y-4">
              <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="space-y-2">
                  <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-600">Applicant Decision</p>
                  <h1 class="text-3xl font-semibold tracking-tight text-slate-900">{{ applicantName }}</h1>
                  <p class="text-sm text-slate-500">{{ application?.email }} - {{ application?.phone || 'No phone provided' }}</p>
                </div>
                <Tag :value="application?.status || 'Applied'" :severity="statusSeverity(application?.status)" />
              </div>

              <div class="rounded-3xl border border-slate-200 bg-white p-5">
                <h2 class="text-lg font-semibold text-slate-900">Profile Review</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Current Position</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ application?.current_position || 'Not provided' }}</p>
                  </div>
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Current Company</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ application?.current_company || 'Not provided' }}</p>
                  </div>
                </div>

                <Divider />

                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Timeline Notes</p>
                  <div class="mt-3 space-y-3">
                    <div v-for="item in application?.timeline || []" :key="item.id" class="rounded-2xl bg-slate-50 p-4">
                      <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-900">{{ item.status }}</p>
                        <span class="text-xs text-slate-500">{{ formatDate(item.changed_at) }}</span>
                      </div>
                      <p class="mt-1 text-xs text-slate-500">{{ item.notes || 'No notes provided.' }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <Card class="border border-blue-100 bg-blue-50/70 shadow-none">
          <template #title>Decision Actions</template>
          <template #content>
            <div class="space-y-4">
              <p class="text-sm leading-6 text-slate-600">
                Choose one action. Hiring moves this applicant to employee onboarding. Rejecting records a formal reason.
              </p>
              <Button
                label="Continue to Employee Onboarding"
                icon="pi pi-arrow-right"
                severity="info"
                fluid
                @click="router.push({ name: 'hr.job-applications.onboarding', params: { applicationId: route.params.applicationId } })"
              />
              <Button
                label="Reject Applicant"
                icon="pi pi-times"
                severity="danger"
                outlined
                :disabled="rejecting"
                fluid
                @click="showRejectDialog = true"
              />
            </div>
          </template>
        </Card>
      </div>
    </div>

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
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import hrService from '../../../../services/hr.services'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const application = ref<any | null>(null)
const loading = ref(false)
const rejecting = ref(false)
const showRejectDialog = ref(false)
const rejectionError = ref('')

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
const formatDate = (value?: string) => (value ? new Date(value).toLocaleString('en-PH') : 'N/A')
const statusSeverity = (status?: string) => ({ Applied: 'info', Screening: 'contrast', Interview: 'warn', Offer: 'success', Accepted: 'success', Hired: 'success', Rejected: 'danger' }[status || 'Applied'] || 'secondary')

const loadApplication = async () => {
  loading.value = true
  try {
    const response = await hrService.getJobApplication(route.params.applicationId as string)
    application.value = response?.data || response
  } finally {
    loading.value = false
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
    const firstError = error?.response?.data?.errors
      ? Object.values(error.response.data.errors)[0]
      : null
    rejectionError.value = Array.isArray(firstError) ? firstError[0] : (error?.response?.data?.message || 'Unable to reject applicant.')
  } finally {
    rejecting.value = false
  }
}

onMounted(loadApplication)
</script>
