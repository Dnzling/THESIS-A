<template>
  <div class="min-h-full">
    <div class="mx-auto max-w-7xl px-6">
      <Button label="Back to Job Postings" icon="pi pi-arrow-left" severity="secondary" text @click="router.push({ name: 'hr.recuitment' })" />

      <div v-if="loading" class="mt-4 space-y-3">
        <Skeleton height="10rem" />
        <Skeleton height="14rem" />
      </div>

      <div v-else class="mt-4 grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
        <Card class="border border-slate-200 shadow-none">
          <template #content>
            <div class="space-y-6">
              <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="space-y-3">
                  <div class="flex flex-wrap items-center gap-2">
                    <Tag :value="posting?.status || 'N/A'" :severity="statusSeverity(posting?.status)" />
                    <Tag v-if="posting?.role?.display_name || posting?.role?.name" severity="info" :value="posting?.role?.display_name || posting?.role?.name" />
                  </div>
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-600">Job Posting Overview</p>
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-900">{{ posting?.title }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ posting?.department }}</p>
                  </div>
                </div>
                <div class="rounded-3xl bg-blue-50/80 p-5">
                  <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Salary Range</p>
                  <p class="mt-2 text-base font-semibold text-slate-900">{{ formatCurrency(posting?.salary_min) }} - {{ formatCurrency(posting?.salary_max) }}</p>
                </div>
              </div>

              <div class="grid gap-3 rounded-3xl border border-slate-200 bg-white p-4 sm:grid-cols-2">
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Created By</p>
                  <p class="mt-1 text-sm font-semibold text-slate-900">{{ resolveCreator(posting) }}</p>
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Created At</p>
                  <p class="mt-1 text-sm font-semibold text-slate-900">{{ formatDate(posting?.created_at) }}</p>
                </div>
              </div>

              <div class="rounded-3xl border border-slate-200 bg-white p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Role Summary</p>
                <p class="mt-3 text-sm leading-7 text-slate-600 whitespace-pre-wrap">{{ posting?.description || 'No description provided.' }}</p>
              </div>

              <div class="rounded-3xl border border-slate-200 bg-white p-5">
                <div class="flex items-center justify-between gap-3">
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Applicant Preview</p>
                    <h2 class="mt-2 text-lg font-semibold text-slate-900">{{ applicants.length }} Applicants</h2>
                  </div>
                  <Button label="Open Applicant List" icon="pi pi-arrow-right" severity="info" outlined @click="router.push({ name: 'hr.job-postings.applicants', params: { postingId: route.params.postingId } })" />
                </div>

                <div class="mt-4 space-y-3">
                  <div
                    v-for="application in previewApplicants"
                    :key="application.id"
                    class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:flex-row md:items-center md:justify-between"
                  >
                    <div>
                      <p class="font-semibold text-slate-900">{{ application.full_name || `${application.first_name} ${application.last_name}` }}</p>
                      <p class="text-sm text-slate-500">{{ application.email }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                      <Tag :value="application.status || 'Applied'" :severity="applicationSeverity(application.status)" />
                      <Button label="Review" icon="pi pi-user" severity="secondary" outlined @click="router.push({ name: 'hr.job-applications.review', params: { applicationId: application.id } })" />
                    </div>
                  </div>

                  <Message v-if="!previewApplicants.length" severity="info" :closable="false">
                    No applicants yet for this posting.
                  </Message>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <Card class="border border-blue-100 bg-blue-50/70 shadow-none">
          <template #title>Hiring Flow</template>
          <template #content>
            <div class="space-y-3">
              <div v-for="(stage, index) in stages" :key="stage.id || index" class="rounded-2xl bg-white p-4">
                <div class="flex items-start gap-3">
                  <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-xs font-semibold text-white">{{ index + 1 }}</span>
                  <div>
                    <p class="text-sm font-semibold text-slate-900">{{ stage.stage_name || stage.name }}</p>
                    <p class="text-xs text-slate-500">{{ stage.description || 'No notes provided.' }}</p>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import hrService from '../../../../services/hr.services'

const route = useRoute()
const router = useRouter()
const posting = ref<any | null>(null)
const loading = ref(false)

const applicants = computed(() => posting.value?.applications || [])
const previewApplicants = computed(() => applicants.value.slice(0, 4))
const stages = computed(() => posting.value?.screening_stages || posting.value?.screeningStages || [])

const loadPosting = async () => {
  loading.value = true
  try {
    const response = await hrService.getJobPosting(route.params.postingId as string)
    posting.value = response?.data || response
  } finally {
    loading.value = false
  }
}

const formatCurrency = (value?: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 0 }).format(Number(value || 0))
const statusSeverity = (status?: string) => ({ Open: 'success', Closed: 'danger', 'On Hold': 'warn' }[status || ''] || 'secondary')
const applicationSeverity = (status?: string) => ({ Applied: 'info', Screening: 'contrast', Interview: 'warn', Offer: 'success', Accepted: 'success', Hired: 'success', Rejected: 'danger' }[status || 'Applied'] || 'secondary')
const resolveCreator = (posting: any) => {
  const primary =
    posting?.creator_full_name ||
    posting?.created_by_full_name
  if (primary) return primary

  const obj =
    posting?.creator ||
    posting?.createdBy ||
    posting?.user ||
    posting?.created_by_user ||
    posting?.creatorUser ||
    (typeof posting?.created_by === 'object' ? posting.created_by : null)
  if (obj) {
    const full =
      obj.full_name ||
      obj.name ||
      `${obj.fname || ''} ${obj.lname || ''}`.trim()
    if (full && full.trim()) return full
  }

  const fallback =
    posting?.creator_name ||
    posting?.created_by_name
  if (fallback) return fallback

  return posting?.created_by ? `User #${posting.created_by}` : 'N/A'
}
const formatDate = (value?: string) => (value ? new Date(value).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A')

onMounted(loadPosting)
</script>

