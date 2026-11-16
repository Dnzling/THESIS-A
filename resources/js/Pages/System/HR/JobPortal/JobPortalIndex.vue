<template>
  <JobPortalLayout>
    <div class="space-y-5 pb-6">
      <section class="rounded-2xl bg-orange-800 px-4 py-6 text-white sm:px-6 sm:py-7">
        <form class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_19rem_auto]" @submit.prevent="fetchPostings(1)">
          <IconField class="min-w-0">
            <InputIcon class="pi pi-search text-slate-500" />
            <InputText
              v-model="search"
              class="!h-12 !w-full !border-0 !bg-white !text-slate-900 placeholder:!text-slate-500"
              placeholder="Search roles, departments, or skills"
              aria-label="Search jobs"
            />
          </IconField>
          <IconField class="min-w-0">
            <InputIcon class="pi pi-map-marker text-slate-500" />
            <InputText
              v-model="location"
              class="!h-12 !w-full !border-0 !bg-white !text-slate-900 placeholder:!text-slate-500"
              placeholder="City, province, or branch"
              aria-label="Search by location"
            />
          </IconField>
          <Button type="submit" label="Find Jobs" icon="pi pi-search" class="!h-12 !border-orange-500 !bg-orange-500 !px-6 !font-semibold !text-white hover:!border-orange-400 hover:!bg-orange-400" :loading="loading" />
        </form>

        <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
          <Select v-model="payFilter" :options="payOptions" optionLabel="label" optionValue="value" aria-label="Filter by pay" class="job-filter" @change="fetchPostings(1)" />
          <Select v-model="employmentType" :options="typeOptions" optionLabel="label" optionValue="value" aria-label="Filter by employment type" class="job-filter" @change="fetchPostings(1)" />
          <Select v-model="department" :options="departmentOptions" optionLabel="label" optionValue="value" aria-label="Filter by department" class="job-filter" @change="fetchPostings(1)" />
          <Select v-model="postedWithin" :options="postedOptions" optionLabel="label" optionValue="value" aria-label="Filter by listing date" class="job-filter" @change="fetchPostings(1)" />
        </div>
      </section>

      <section class="grid items-start gap-5 lg:grid-cols-[minmax(19rem,0.43fr)_minmax(0,0.57fr)]">
        <div class="min-w-0">
          <div class="mb-3 flex items-center justify-between gap-3 px-1">
            <div>
              <h1 class="text-lg font-semibold text-slate-900">{{ totalJobs.toLocaleString() }} job{{ totalJobs === 1 ? '' : 's' }}</h1>
              <p class="text-xs text-slate-500">Open roles from our partner stores</p>
            </div>
            <Button
              :label="sort === 'newest' ? 'Newest' : sort === 'oldest' ? 'Oldest' : sort === 'salary_high' ? 'Highest pay' : 'Lowest pay'"
              :icon="sort.startsWith('salary') ? 'pi pi-sort-amount-down' : 'pi pi-sort-alt'"
              severity="secondary"
              outlined
              size="small"
              aria-label="Change job sorting"
              @click="cycleSort"
            />
          </div>

          <div v-if="loading" class="space-y-3">
            <div v-for="n in 4" :key="n" class="rounded-xl border border-slate-200 bg-white p-4">
              <Skeleton width="10rem" height="1rem" />
              <Skeleton class="mt-3" width="80%" height="1.25rem" />
              <Skeleton class="mt-3" width="55%" height="1rem" />
              <Skeleton class="mt-4" width="100%" height="3rem" />
            </div>
          </div>

          <div v-else-if="postings.length" class="space-y-3">
            <button
              v-for="posting in postings"
              :key="posting.id"
              type="button"
              class="block w-full rounded-xl border bg-white p-4 text-left transition hover:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-300"
              :class="selectedPosting?.id === posting.id ? 'border-orange-500 ring-1 ring-orange-200' : 'border-slate-200'"
              @click="selectPosting(posting)"
            >
              <div class="flex items-start gap-3">
                <img v-if="posting.company_logo_url" :src="posting.company_logo_url" :alt="`${storeLabel(posting)} logo`" class="h-10 w-10 shrink-0 rounded-lg border border-slate-200 bg-white object-contain p-1" />
                <span v-else class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-orange-100 bg-orange-50 text-orange-700"><i class="pi pi-building" /></span>
                <div class="min-w-0 flex-1">
                  <p class="truncate text-xs font-medium text-slate-600">{{ storeLabel(posting) }}</p>
                  <h2 class="mt-1 line-clamp-2 text-base font-semibold leading-5 text-slate-900">{{ roleLabel(posting) }}</h2>
                  <p class="mt-1 text-xs text-slate-600">{{ posting.title }}</p>
                </div>
              </div>
              <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-600">
                <span v-if="posting.branch"><i class="pi pi-map-marker mr-1 text-orange-600" />{{ posting.branch.name }}<template v-if="branchLocation(posting)"> · {{ branchLocation(posting) }}</template></span>
                <span><i class="pi pi-briefcase mr-1 text-orange-600" />{{ employmentTypeLabel(posting.employment_type) }}</span>
              </div>
              <p class="mt-3 line-clamp-3 text-sm leading-5 text-slate-600">{{ posting.description }}</p>
              <div class="mt-3 flex items-center justify-between gap-3 border-t border-slate-100 pt-3">
                <span class="text-sm font-semibold text-slate-900">{{ formatSalary(posting) }}</span>
                <span class="text-xs text-slate-500">{{ postedLabel(posting.created_at) }}</span>
              </div>
            </button>

            <div v-if="lastPage > 1" class="flex items-center justify-between border-t border-slate-200 pt-3">
              <Button label="Previous" icon="pi pi-chevron-left" severity="secondary" text size="small" :disabled="page <= 1 || loading" @click="fetchPostings(page - 1)" />
              <span class="text-xs text-slate-500">Page {{ page }} of {{ lastPage }}</span>
              <Button label="Next" icon="pi pi-chevron-right" iconPos="right" severity="secondary" text size="small" :disabled="page >= lastPage || loading" @click="fetchPostings(page + 1)" />
            </div>
          </div>

          <div v-else class="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center">
            <i class="pi pi-briefcase text-2xl text-orange-500" />
            <h2 class="mt-3 font-semibold text-slate-900">No jobs found</h2>
            <p class="mt-1 text-sm text-slate-500">Try changing your search or filters.</p>
            <Button v-if="hasFilters" label="Clear filters" severity="warn" text size="small" class="mt-2" @click="clearFilters" />
          </div>
        </div>

        <aside class="min-h-[34rem] overflow-hidden rounded-2xl border border-slate-200 bg-white lg:sticky lg:top-4 lg:max-h-[calc(100vh-7rem)] lg:overflow-y-auto">
          <template v-if="selectedPosting">
            <div v-if="detailLoading" class="space-y-4 p-6">
              <Skeleton width="40%" height="1rem" />
              <Skeleton width="75%" height="2rem" />
              <Skeleton width="55%" height="1rem" />
              <Skeleton width="100%" height="8rem" />
              <Skeleton width="100%" height="8rem" />
            </div>
            <div v-else class="divide-y divide-slate-200">
              <div class="p-5 sm:p-6">
                <div class="flex items-center gap-3">
                  <img v-if="detail?.company_logo_url || selectedPosting.company_logo_url" :src="detail?.company_logo_url || selectedPosting.company_logo_url" :alt="`${storeLabel(selectedPosting)} logo`" class="h-12 w-12 rounded-lg border border-slate-200 object-contain p-1" />
                  <span v-else class="flex h-12 w-12 items-center justify-center rounded-lg border border-orange-100 bg-orange-50 text-orange-700"><i class="pi pi-building text-lg" /></span>
                  <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-700">{{ storeLabel(selectedPosting) }}</p>
                    <p v-if="branchLocation(selectedPosting)" class="mt-0.5 text-xs text-slate-500"><i class="pi pi-map-marker mr-1 text-orange-600" />{{ selectedPosting.branch?.name }} · {{ branchLocation(selectedPosting) }}</p>
                  </div>
                </div>
                <h2 class="mt-5 text-2xl font-bold leading-tight text-slate-900">{{ roleLabel(selectedPosting) }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ selectedPosting.title }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                  <Tag :value="employmentTypeLabel(selectedPosting.employment_type)" severity="warn" />
                  <Tag v-if="detail?.minimum_age || selectedPosting.minimum_age" :value="`Minimum age ${detail?.minimum_age || selectedPosting.minimum_age}`" severity="secondary" />
                </div>
                <p class="mt-4 text-lg font-semibold text-orange-800">{{ formatSalary(selectedPosting) }}</p>
                <Button label="View details and apply" icon="pi pi-arrow-right" iconPos="right" severity="warn" class="mt-4 w-full" @click="openPosting(selectedPosting.id)" />
              </div>

              <div class="space-y-2 p-5 sm:p-6">
                <h3 class="text-sm font-semibold text-slate-900">About the role</h3>
                <p class="whitespace-pre-line break-words text-sm leading-6 text-slate-600">{{ detail?.description || selectedPosting.description || 'No role description provided.' }}</p>
              </div>

              <div v-if="stages.length" class="space-y-3 p-5 sm:p-6">
                <h3 class="text-sm font-semibold text-slate-900">Hiring process</h3>
                <div v-for="(stage, index) in stages" :key="stage.id || index" class="flex gap-3">
                  <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-100 text-xs font-semibold text-orange-800">{{ index + 1 }}</span>
                  <div>
                    <p class="text-sm font-medium text-slate-800">{{ stage.stage_name || stage.name }}</p>
                    <p v-if="stage.description" class="mt-0.5 text-xs leading-5 text-slate-500">{{ stage.description }}</p>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <div v-else class="flex min-h-[34rem] flex-col items-center justify-center px-6 py-12 text-center">
            <span class="flex h-28 w-28 items-center justify-center rounded-full bg-orange-50 text-orange-600"><i class="pi pi-briefcase text-4xl" /></span>
            <h2 class="mt-6 text-xl font-semibold text-slate-900">Select a job</h2>
            <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">Choose a position from the list to see the role, location, pay, and hiring process here.</p>
          </div>
        </aside>
      </section>
    </div>
  </JobPortalLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import JobPortalLayout from './JobPortalLayout.vue'
import hrService, { type JobPostingStage } from '../../../../services/hr.services'

const router = useRouter()
const toast = useToast()
const postings = ref<any[]>([])
const selectedPosting = ref<any | null>(null)
const detail = ref<any | null>(null)
const search = ref('')
const location = ref('')
const employmentType = ref('')
const department = ref('')
const postedWithin = ref('')
const payFilter = ref('')
const sort = ref('newest')
const loading = ref(false)
const detailLoading = ref(false)
const page = ref(1)
const lastPage = ref(1)
const totalJobs = ref(0)
let requestNumber = 0

const payOptions = [
  { label: 'Any pay', value: '' },
  { label: 'Below ₱25,000', value: '0:25000' },
  { label: '₱25,000–₱50,000', value: '25000:50000' },
  { label: '₱50,000 and above', value: '50000:' },
]
const typeOptions = [
  { label: 'Any employment type', value: '' },
  { label: 'Full time', value: 'full_time' },
  { label: 'Part time', value: 'part_time' },
  { label: 'Contract', value: 'contract' },
  { label: 'Internship', value: 'intern' },
]
const postedOptions = [
  { label: 'Any listing date', value: '' },
  { label: 'Past 24 hours', value: 'day' },
  { label: 'Past week', value: 'week' },
  { label: 'Past month', value: 'month' },
]
const departmentOptions = computed(() => {
  const values = [...new Set(postings.value.map((posting) => String(posting.department || '').trim()).filter(Boolean))].sort()
  return [{ label: 'Any department', value: '' }, ...values.map((value) => ({ label: value, value }))]
})
const hasFilters = computed(() => Boolean(search.value || location.value || employmentType.value || department.value || postedWithin.value || payFilter.value))
const stages = computed<JobPostingStage[]>(() => detail.value?.screening_stages || detail.value?.screeningStages || selectedPosting.value?.screening_stages || selectedPosting.value?.screeningStages || [])
const employmentTypeLabels: Record<string, string> = { full_time: 'Full time', part_time: 'Part time', contract: 'Contract', intern: 'Internship' }

const fetchPostings = async (nextPage = 1) => {
  const currentRequest = ++requestNumber
  const [salaryMin, salaryMax] = payFilter.value ? payFilter.value.split(':') : ['', '']
  loading.value = true
  page.value = nextPage
  try {
    const response = await hrService.getPortalJobPostings({
      search: search.value.trim() || undefined,
      location: location.value.trim() || undefined,
      employment_type: employmentType.value || undefined,
      department: department.value || undefined,
      posted_within: postedWithin.value || undefined,
      salary_min: salaryMin || undefined,
      salary_max: salaryMax || undefined,
      sort: sort.value,
      per_page: 10,
      page: nextPage,
    })
    if (currentRequest !== requestNumber) return
    const result = response?.data
    const paginator = result?.data
    postings.value = Array.isArray(paginator) ? paginator : Array.isArray(paginator?.data) ? paginator.data : []
    totalJobs.value = Number(paginator?.total ?? postings.value.length)
    lastPage.value = Number(paginator?.last_page ?? 1)
    page.value = Number(paginator?.current_page ?? nextPage)
    if (selectedPosting.value && !postings.value.some((posting) => posting.id === selectedPosting.value.id)) {
      selectedPosting.value = null
      detail.value = null
    }
  } catch (error: any) {
    if (currentRequest === requestNumber) {
      toast.add({ severity: 'error', summary: 'Unable to load jobs', detail: error?.response?.data?.message || 'Please try again.', life: 3000 })
    }
  } finally {
    if (currentRequest === requestNumber) loading.value = false
  }
}

const selectPosting = async (posting: any) => {
  selectedPosting.value = posting
  detail.value = null
  if (!posting?.id) return
  detailLoading.value = true
  try {
    const response = await hrService.getPortalJobPosting(posting.id)
    if (selectedPosting.value?.id === posting.id) detail.value = response?.data?.data || response?.data || null
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to load job details', detail: error?.response?.data?.message || 'Please try again.', life: 3000 })
  } finally {
    if (selectedPosting.value?.id === posting.id) detailLoading.value = false
  }
}

const openPosting = (id?: number) => {
  if (id) router.push({ name: 'job-portal.detail', params: { id: String(id) } })
}
const clearFilters = () => {
  search.value = ''
  location.value = ''
  employmentType.value = ''
  department.value = ''
  postedWithin.value = ''
  payFilter.value = ''
  sort.value = 'newest'
  fetchPostings(1)
}
const cycleSort = () => {
  const sorts = ['newest', 'oldest', 'salary_high', 'salary_low']
  sort.value = sorts[(sorts.indexOf(sort.value) + 1) % sorts.length]
  fetchPostings(1)
}
const storeLabel = (posting: any) => posting.store?.store_name || posting.store?.name || 'Partner store'
const roleLabel = (posting: any) => posting.role?.display_name || posting.role?.name || posting.department || posting.title || 'Open position'
const branchLocation = (posting: any) => [posting.branch?.city, posting.branch?.province].filter(Boolean).join(', ')
const employmentTypeLabel = (value?: string) => employmentTypeLabels[value || ''] || 'Full time'
const formatSalary = (posting: any) => {
  const min = Number(posting.salary_min || 0)
  const max = Number(posting.salary_max || 0)
  if (!min && !max) return 'Salary not listed'
  const money = (value: number) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 0 }).format(value)
  if (!max || min === max) return money(min || max)
  return `${money(min)} – ${money(max)}`
}
const postedLabel = (value?: string) => {
  if (!value) return 'Recently posted'
  const age = Math.max(0, Date.now() - new Date(value).getTime())
  const hours = Math.floor(age / 3_600_000)
  if (hours < 1) return 'Just now'
  if (hours < 24) return `${hours}h ago`
  const days = Math.floor(hours / 24)
  return days < 30 ? `${days}d ago` : new Date(value).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' })
}

onMounted(() => fetchPostings(1))
</script>

<style scoped>
:deep(.job-filter.p-select) {
  width: 100%;
  min-height: 2.5rem;
  border-color: rgb(255 255 255 / 45%);
  border-radius: 9999px;
  background: transparent;
  color: white;
}

:deep(.job-filter .p-select-label),
:deep(.job-filter .p-select-dropdown) {
  color: white;
}

:deep(.job-filter.p-select:not(.p-disabled).p-focus) {
  border-color: white;
  box-shadow: 0 0 0 1px white;
}

:deep(.job-filter .p-select-label.p-placeholder) {
  color: rgb(255 255 255 / 85%);
}
</style>
