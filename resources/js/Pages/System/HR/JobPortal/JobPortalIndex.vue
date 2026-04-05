<template>
  <JobPortalLayout>
    <div class="space-y-4">
    <section class="rounded-3xl border border-blue-100 bg-white/80 p-6 shadow-sm">
      <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">Explore opportunities across partner stores
      </h1>
      <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
        Browse active job openings and track every application you submit from one dashboard.
      </p>
    </section>
  
    <div>
      <Card class="border border-blue-100 bg-white shadow-sm">
        <template #content>
          <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_160px]">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="search" placeholder="Search by title, department, or keyword" class="w-full"
                @keyup.enter="fetchPostings" />
            </IconField>
            <Button label="Search Jobs" icon="pi pi-search" severity="warn" @click="fetchPostings" />
          </div>
        </template>
      </Card>
  
      <div class="mt-6 space-y-4">
        <Card v-if="loading" v-for="item in 5" :key="item" class="border border-surface-200 shadow-sm">
          <template #content>
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px] lg:items-center">
              <div class="space-y-3">
                <Skeleton width="8rem" height="1.25rem" />
                <Skeleton width="18rem" height="1.75rem" />
                <Skeleton width="10rem" height="1rem" />
                <Skeleton width="100%" height="3.75rem" />
              </div>
              <div class="space-y-3">
                <Skeleton width="100%" height="4rem" />
                <Skeleton width="100%" height="2.75rem" />
              </div>
            </div>
          </template>
        </Card>
  
        <Card v-else-if="postings.length" v-for="posting in postings" :key="posting.id"
          class="border border-surface-200 shadow-sm transition hover:border-blue-200 hover:shadow-md">
          <template #content>
            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_220px] lg:items-center">
              <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                  <Tag :value="posting.status || 'Open'" :severity="posting.status === 'Open' ? 'success' : 'warn'" />
                  <span class="text-xs font-semibold uppercase tracking-wide text-surface-500">{{ storeLabel(posting)
                    }}</span>
                </div>
                <div class="space-y-1">
                  <h3 class="text-2xl font-semibold text-slate-900">{{ roleLabel(posting) }}</h3>
                  <p class="text-sm font-medium text-slate-600">{{ posting.title }}</p>
                </div>
                <p class="line-clamp-3 text-sm leading-7 text-slate-600">
                  {{ posting.description }}
                </p>
              </div>
  
              <div class="space-y-3 rounded-3xl bg-blue-50/80 p-5">
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Salary Range</p>
                  <p class="mt-1 text-base font-semibold text-slate-900">
                    {{ formatCurrency(posting.salary_min) }} - {{ formatCurrency(posting.salary_max) }}
                  </p>
                </div>
                <Button label="Preview Job Post" icon="pi pi-arrow-right" severity="warn" fluid
                  @click="previewPosting(posting.id)" />
              </div>
            </div>
          </template>
        </Card>
  
        <Card v-else class="border border-dashed border-surface-300 shadow-sm">
          <template #content>
            <div class="py-12 text-center">
              <i class="pi pi-briefcase text-3xl text-surface-400" />
              <h3 class="mt-4 text-lg font-semibold text-surface-900">No job posts found</h3>
              <p class="mt-1 text-sm text-surface-500">Try a different search term.</p>
            </div>
          </template>
        </Card>
      </div>
    </div>
    </div>
  </JobPortalLayout>
</template>

<script setup lang="ts">
import JobPortalLayout from './JobPortalLayout.vue'
import { onMounted, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'
import hrService, { type JobPosting } from '../../../../services/hr.services'
import { useJobPortalAuthStore } from '../../../../stores/jobPortalAuth'

const toast = useToast()
const portalAuth = useJobPortalAuthStore()

const postings = ref<JobPosting[]>([])
const search = ref('')
const loading = ref(false)
let postingsRequest: Promise<any> | null = null

const fetchPostings = async () => {
  if (postingsRequest) return postingsRequest
  loading.value = true
  postingsRequest = hrService.getPortalJobPostings({ search: search.value || undefined })
    .then((response) => {
      postings.value = response.data?.data || []
    })
    .catch((error: any) => {
      toast.add({
        severity: 'error',
        summary: 'Unable to load job posts',
        detail: error?.response?.data?.message || 'Please try again.',
        life: 3000,
      })
    })
    .finally(() => {
      postingsRequest = null
      loading.value = false
    })
  return postingsRequest
}

const previewPosting = (id?: number) => {
  if (!id) return
  const targetPath = `/job-portal/postings/${id}`

  if (!portalAuth.isAuthenticated) {
    portalAuth.setPendingRedirect(targetPath)
    router.visit('/job-portal/login')
    return
  }

  router.visit(targetPath)
}

const formatCurrency = (value: number | string) =>
  new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 0 }).format(Number(value || 0))

const storeLabel = (posting: JobPosting) => posting.store?.store_name || posting.store?.name || 'Store opening'
const roleLabel = (posting: JobPosting) =>
  posting.role?.display_name ||
  posting.role?.name ||
  posting.department ||
  'Role'

onMounted(fetchPostings)
</script>
