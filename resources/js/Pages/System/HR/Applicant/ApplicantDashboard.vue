<template>
  <JobPortalLayout>
    <div class="py-6 lg:py-10">
      <div class="space-y-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-3xl font-semibold text-slate-900">My Applications</h1>
      </div>
      <div class="flex gap-3">
        <Button label="Browse Jobs" severity="warn" outlined icon="pi pi-search"
          @click="router.push({ name: 'job-portal.index' })" />
      </div>
    </div>
  
    <div class="space-y-4">
      <Card v-if="loading" v-for="item in 4" :key="item" class="border border-gray-50 shadow-sm">
        <template #content>
          <div class="space-y-3">
            <Skeleton width="8rem" height="1rem" />
            <Skeleton width="14rem" height="1.5rem" />
            <Skeleton width="10rem" height="1rem" />
            <div class="flex gap-3">
              <Skeleton width="10rem" height="2.75rem" />
              <Skeleton width="10rem" height="2.75rem" />
            </div>
          </div>
        </template>
      </Card>
  
      <Card v-for="application in applications" :key="application.id" class="border border-gray-50 shadow-sm">
        <template #content>
          <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-2">
              <div class="flex items-center gap-2">
                <Tag :value="application.status || 'Applied'" :severity="statusSeverity(application.status)" />
                <span class="text-xs uppercase tracking-wide text-gray-500">
                  {{ postingStore(application) }}
                </span>
              </div>
              <h3 class="text-xl font-semibold text-surface-900">{{ postingRoleLabel(application) }}</h3>
              <p class="text-sm text-gray-600">
                Store: {{ postingStore(application) }}
              </p>
            </div>
  
            <div class="flex flex-wrap gap-3">
              <Button label="View Job Post" severity="secondary" outlined icon="pi pi-external-link"
                @click="goToJobPost(application.job_posting_id)" />
              <Button label="View Application" severity="warn" icon="pi pi-arrow-right"
                @click="router.push({ name: 'job-portal.applications.detail', params: { id: application.id } })" />
            </div>
          </div>
        </template>
      </Card>
  
      <Card v-if="!loading && !applications.length" class="border border-dashed border-gray-50 shadow-sm">
        <template #content>
          <div class="py-10 text-center">
            <h3 class="text-lg font-semibold text-surface-900">No submitted applications yet</h3>
            <p class="mt-1 text-sm text-surface-500">Browse available job openings and submit your first application.</p>
                <Button class="mt-4" severity="warn" label="Browse Jobs"
              @click="router.push({ name: 'job-portal.index' })" />
          </div>
        </template>
      </Card>
    </div>
      </div>
    </div>
  </JobPortalLayout>
</template>

<script setup lang="ts">
import JobPortalLayout from '../JobPortal/JobPortalLayout.vue'
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import hrService, { type JobApplication } from '../../../../services/hr.services'

const router = useRouter()
const toast = useToast()
const applications = ref<JobApplication[]>([])
const loading = ref(false)
let applicationsRequest: Promise<any> | null = null

const fetchApplications = async () => {
  if (applicationsRequest) return applicationsRequest
  loading.value = true
  applicationsRequest = hrService.getPortalApplications()
    .then((response) => {
      applications.value = response.data?.data || response.data || []
    })
    .catch((error: any) => {
      toast.add({
        severity: 'error',
        summary: 'Unable to load applications',
        detail: error?.response?.data?.message || 'Please try again.',
        life: 3000,
      })
    })
    .finally(() => {
      applicationsRequest = null
      loading.value = false
    })
  return applicationsRequest
}

const goToJobPost = (jobPostingId?: number) => {
  if (!jobPostingId) return
  router.push({ name: 'job-portal.detail', params: { id: jobPostingId } })
}

const statusSeverity = (status?: string) => {
  const map: Record<string, string> = {
    Applied: 'warn',
    Screening: 'contrast',
    Interview: 'warn',
    Offer: 'success',
    Hired: 'success',
    Rejected: 'danger',
  }
  return map[status || 'Applied'] || 'secondary'
}

const postingRef = (application: any) =>
  application?.jobPosting ||
  application?.job_posting ||
  application?.posting ||
  null

const postingTitle = (application: any) =>
  postingRef(application)?.title || 'N/A'

const postingRoleLabel = (application: any) => {
  const posting = postingRef(application)
  return (
    posting?.role?.display_name ||
    posting?.role?.name ||
    posting?.role_name ||
    posting?.position ||
    posting?.title ||
    'N/A'
  )
}

const postingStore = (application: any) => {
  const posting = postingRef(application)
  const store = posting?.store
  return store?.store_name || store?.name || 'Store'
}

onMounted(fetchApplications)
</script>
