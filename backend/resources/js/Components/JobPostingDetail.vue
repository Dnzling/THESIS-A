<template>
  <Dialog
    :visible="true"
    modal
    dismissableMask
    :style="{ width: 'min(92rem, 98vw)' }"
    :breakpoints="{ '1280px': '98vw' }"
    @update:visible="emit('close')"
  >
    <template #header>
      <div class="space-y-1">
        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-surface-500">HR Recruitment Workspace</p>
        <h2 class="text-2xl font-semibold text-surface-900">{{ detail?.title || posting?.title || 'Job Posting' }}</h2>
        <p class="text-sm text-surface-500">{{ detail?.department || posting?.department || 'Department' }}</p>
      </div>
    </template>

    <div class="space-y-6">
      <div v-if="loading" class="grid gap-6 xl:grid-cols-[1.25fr_0.75fr]">
        <Card v-for="item in 4" :key="item" class="border border-slate-200 shadow-none">
          <template #content>
            <div class="space-y-4">
              <Skeleton width="10rem" height="1rem" />
              <Skeleton width="14rem" height="1.5rem" />
              <Skeleton width="100%" height="5rem" />
            </div>
          </template>
        </Card>
      </div>

      <div v-else class="grid gap-6 xl:grid-cols-[1.25fr_0.75fr]">
        <div class="space-y-6">
          <Card class="border border-slate-200 shadow-none">
            <template #content>
              <div class="space-y-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                  <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                      <Tag :value="detail?.status || 'N/A'" :severity="statusSeverity(detail?.status)" />
                      <Tag severity="secondary" :value="`${applicationCount} Applicant${applicationCount === 1 ? '' : 's'}`" />
                      <Tag v-if="detail?.role?.display_name || detail?.role?.name" severity="info" :value="detail?.role?.display_name || detail?.role?.name" />
                    </div>
                    <div>
                      <h3 class="text-3xl font-semibold tracking-tight text-slate-900">{{ detail?.title }}</h3>
                      <p class="mt-1 text-sm font-medium text-slate-600">{{ detail?.department }}</p>
                    </div>
                  </div>

                  <div class="grid gap-3 rounded-3xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-3">
                    <div>
                      <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Minimum Salary</p>
                      <p class="mt-1 text-sm font-semibold text-slate-900">{{ formatCurrency(detail?.salary_min) }}</p>
                    </div>
                    <div>
                      <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Maximum Salary</p>
                      <p class="mt-1 text-sm font-semibold text-slate-900">{{ formatCurrency(detail?.salary_max) }}</p>
                    </div>
                    <div>
                      <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pipeline</p>
                      <p class="mt-1 text-sm font-semibold text-slate-900">{{ screeningStageCount }} Stages</p>
                    </div>
                  </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                  <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Role Summary</p>
                    <p class="mt-3 text-sm leading-7 text-slate-600 whitespace-pre-wrap">
                      {{ detail?.description || 'No description available.' }}
                    </p>
                  </div>

                  <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Hiring Flow</p>
                    <div class="mt-3 space-y-3">
                      <div
                        v-for="(stage, index) in screeningStages"
                        :key="stage.id || `${stage.stage_name}-${index}`"
                        class="flex items-start gap-3 rounded-2xl bg-white p-3"
                      >
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-900 text-xs font-semibold text-white">
                          {{ index + 1 }}
                        </span>
                        <div>
                          <p class="text-sm font-semibold text-slate-900">{{ stage.stage_name || stage.name }}</p>
                          <p class="text-xs text-slate-500">{{ stage.description || 'No notes provided for this stage.' }}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </Card>

          <Card class="border border-slate-200 shadow-none">
            <template #title>
              <div class="flex items-center justify-between gap-3">
                <div>
                  <h3 class="text-lg font-semibold text-slate-900">Applications</h3>
                  <p class="mt-1 text-sm text-slate-500">Review applicants in a shortlist-style view and open the full profile when needed.</p>
                </div>
                <Button icon="pi pi-refresh" severity="secondary" text rounded @click="fetchDetail" />
              </div>
            </template>
            <template #content>
              <div v-if="applications.length" class="space-y-3">
                <button
                  v-for="application in applications"
                  :key="application.id"
                  type="button"
                  class="w-full rounded-2xl border border-slate-200 bg-white p-4 text-left transition hover:border-slate-300 hover:shadow-sm"
                  :class="selectedApplication?.id === application.id ? '!border-blue-200 !bg-blue-50/60' : ''"
                  @click="selectedApplication = application"
                >
                  <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-2">
                      <div class="flex flex-wrap items-center gap-2">
                        <p class="text-base font-semibold text-slate-900">{{ applicantName(application) }}</p>
                        <Tag :value="application.status || 'Applied'" :severity="applicationSeverity(application.status)" />
                      </div>
                      <p class="text-sm text-slate-500">{{ application.email }} · {{ application.phone || 'No phone provided' }}</p>
                      <p class="text-xs uppercase tracking-wide text-slate-400">
                        Applied {{ formatDate(application.created_at) }}
                      </p>
                    </div>
                    <div class="flex items-center gap-2">
                      <Button
                        label="Profile View"
                        icon="pi pi-user"
                        severity="secondary"
                        outlined
                        @click.stop="openApplicantProfile(application.id)"
                      />
                      <Button
                        label="Open Actions"
                        icon="pi pi-arrow-right"
                        severity="info"
                        @click.stop="openApplicantProfile(application.id)"
                      />
                    </div>
                  </div>
                </button>
              </div>

              <Message v-else severity="info" :closable="false">
                No applications have been submitted for this job posting yet.
              </Message>
            </template>
          </Card>
        </div>

        <div class="space-y-6">
          <Card class="border border-slate-200 shadow-none">
            <template #title>Applicant Snapshot</template>
            <template #content>
              <div v-if="selectedApplication" class="space-y-5">
                <div class="flex items-center gap-3">
                  <Avatar shape="circle" class="bg-slate-900 text-white" :label="avatarLabel(selectedApplication)" />
                  <div>
                    <p class="text-base font-semibold text-slate-900">{{ applicantName(selectedApplication) }}</p>
                    <p class="text-sm text-slate-500">{{ selectedApplication.email }}</p>
                  </div>
                </div>

                <div class="grid gap-4 rounded-2xl bg-slate-50 p-4">
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Current Status</p>
                    <Tag class="mt-2" :value="selectedApplication.status || 'Applied'" :severity="applicationSeverity(selectedApplication.status)" />
                  </div>
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Contact Number</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ selectedApplication.phone || 'No phone provided' }}</p>
                  </div>
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Applicant Type</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ selectedApplication.employee_id ? 'Internal Employee' : 'External Candidate' }}</p>
                  </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                  <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">HR Recommendation</p>
                  <p class="mt-2 text-sm leading-6 text-slate-600">
                    Open the applicant profile to review supporting documents, schedule interviews, update status, or continue the hire flow.
                  </p>
                </div>

                <Button label="Open Full Applicant Profile" icon="pi pi-user-edit" severity="info" fluid @click="openApplicantProfile(selectedApplication.id)" />
              </div>

              <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                <i class="pi pi-users text-2xl text-slate-400" />
                <p class="mt-3 text-sm font-semibold text-slate-900">Select an applicant</p>
                <p class="mt-1 text-sm text-slate-500">We’ll show a quick profile summary and HR action entry points here.</p>
              </div>
            </template>
          </Card>
        </div>
      </div>
    </div>

    <ApplicationDetailModal
      v-if="selectedApplicationDetail"
      :application="selectedApplicationDetail"
      @close="selectedApplicationDetail = null"
      @update="handleApplicationUpdated"
    />
  </Dialog>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import hrService from '../services/hr.services'
import ApplicationDetailModal from './ApplicationDetailModal.vue'

const props = defineProps<{
  posting: any
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'update'): void
}>()

const toast = useToast()

const loading = ref(false)
const detail = ref<any | null>(null)
const selectedApplication = ref<any | null>(null)
const selectedApplicationDetail = ref<any | null>(null)

const applications = computed(() => detail.value?.applications || [])
const applicationCount = computed(() => applications.value.length)
const screeningStages = computed(() => detail.value?.screening_stages || detail.value?.screeningStages || [])
const screeningStageCount = computed(() => screeningStages.value.length)

const formatCurrency = (value?: number | string) =>
  new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 0 }).format(Number(value || 0))

const formatDate = (value?: string) => (value ? new Date(value).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A')

const statusSeverity = (status?: string) => {
  const map: Record<string, 'success' | 'danger' | 'warn' | 'secondary'> = {
    Open: 'success',
    Closed: 'danger',
    'On Hold': 'warn',
  }
  return map[status || ''] || 'secondary'
}

const applicationSeverity = (status?: string) => {
  const map: Record<string, 'info' | 'success' | 'warn' | 'danger' | 'contrast' | 'secondary'> = {
    Applied: 'info',
    Screening: 'contrast',
    Interview: 'warn',
    Offer: 'success',
    Hired: 'success',
    Rejected: 'danger',
  }
  return map[status || 'Applied'] || 'secondary'
}

const applicantName = (application: any) => {
  const fullName = application.full_name?.trim?.()
  if (fullName) return fullName
  return `${application.first_name || ''} ${application.last_name || ''}`.trim() || 'Applicant'
}

const avatarLabel = (application: any) => {
  const first = application.first_name?.[0] || ''
  const last = application.last_name?.[0] || ''
  return `${first}${last}`.toUpperCase() || 'AP'
}

const fetchDetail = async () => {
  if (!props.posting?.id) return

  loading.value = true
  try {
    const response = await hrService.getJobPosting(props.posting.id)
    detail.value = response?.data || response
    if (applications.value.length) {
      const currentId = selectedApplication.value?.id
      selectedApplication.value = applications.value.find((item: any) => item.id === currentId) || applications.value[0]
    } else {
      selectedApplication.value = null
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Unable to load job posting',
      detail: error?.response?.data?.message || 'Please try again.',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const openApplicantProfile = async (applicationId?: number) => {
  if (!applicationId) return

  try {
    const response = await hrService.getJobApplication(applicationId)
    selectedApplicationDetail.value = response?.data || response
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Unable to open applicant profile',
      detail: error?.response?.data?.message || 'Please try again.',
      life: 3000,
    })
  }
}

const handleApplicationUpdated = async () => {
  selectedApplicationDetail.value = null
  await fetchDetail()
  emit('update')
}

watch(
  () => props.posting?.id,
  () => {
    fetchDetail()
  },
  { immediate: true },
)

onMounted(fetchDetail)
</script>
