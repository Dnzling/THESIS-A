<template>
  <div class="space-y-6">
    <ConfirmDialog />
  
  
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div class="space-y-2">
        <h1 class="text-2xl font-semibold tracking-tight">Job Postings</h1>
      </div>
  
      <Button v-if="hasPermission('hr.recuitment.manage')" severity="info" label="New Job Posting" icon="pi pi-plus" class="p-button-sm"
        @click="openCreateModal" />
    </div>
  
    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <Card class="border border-blue-100 shadow-sm">
        <template #content>
          <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Total Postings</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ filteredPostings.length }}</p>
        </template>
      </Card>
      <Card class="border border-emerald-100 shadow-sm">
        <template #content>
          <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Open Roles</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ openPostingsCount }}</p>
        </template>
      </Card>
      <Card class="border border-violet-100 shadow-sm">
        <template #content>
          <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Total Applicants</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ totalApplicants }}</p>
        </template>
      </Card>
      <Card class="border border-amber-100 shadow-sm">
        <template #content>
          <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Hired Applicants</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ totalHired }}</p>
        </template>
      </Card>
    </section>
  
    <Card class="border border-slate-200 shadow-sm">
      <template #content>
        <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px]">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText v-model="searchTerm" placeholder="Search by title or description" class="w-full" />
          </IconField>
  
          <Select v-model="filterStatus" :options="statusOptions" optionLabel="label" optionValue="value"
            placeholder="Filter by status" class="w-full" />
        </div>
      </template>
    </Card>
  
    <div v-if="loading" class="space-y-3">
      <Card v-for="item in 4" :key="item" class="border border-slate-200 shadow-sm">
        <template #content>
          <div class="space-y-4">
            <Skeleton width="10rem" height="1rem" />
            <Skeleton width="16rem" height="1.5rem" />
            <Skeleton width="100%" height="4rem" />
            <div class="grid gap-3 sm:grid-cols-3">
              <Skeleton v-for="box in 3" :key="box" width="100%" height="4rem" />
            </div>
          </div>
        </template>
      </Card>
    </div>
  
    <div v-else-if="filteredPostings.length" class="space-y-3">
      <Card v-for="posting in filteredPostings" :key="posting.id"
        class="cursor-pointer border border-slate-200 transition-all duration-200 hover:border-blue-200 hover:shadow-md"
        @click="router.push({ name: 'hr.recuitment.detail', params: { postingId: posting.id } })">
        <template #content>
          <div class="space-y-3">
            <div class="flex items-start justify-between gap-4">
              <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                  <h3 class="text-xl font-semibold text-surface-900">{{ posting.title }}</h3>
                  <Tag :value="posting.status" :severity="getStatusSeverity(posting.status)" />
                  <Tag v-if="posting.role?.display_name || posting.role?.name" severity="info"
                    :value="posting.role?.display_name || posting.role?.name" />
                </div>
                <p class="text-sm font-medium text-surface-600">{{ posting.department }}</p>
              </div>
  
              <div v-if="hasPermission('edit-recuitment')" class="flex items-center gap-2">
                <Button icon="pi pi-pencil" severity="secondary" text rounded @click.stop="editPosting(posting)" />
                <Button icon="pi pi-trash" severity="danger" text rounded @click.stop="deletePosting(posting.id)" />
              </div>
            </div>
  
            <p class="line-clamp-3 text-sm leading-6 text-surface-600">
              {{ posting.description || 'No description provided.' }}
            </p>
  
            <div class="grid gap-3 rounded-2xl bg-blue-50/70 p-4 sm:grid-cols-4">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-surface-300">Salary Range</p>
                <p class="mt-1 text-sm font-semibold text-surface-900">
                  {{ formatCurrency(posting.salary_min) }} - {{ formatCurrency(posting.salary_max) }}
                </p>
              </div>
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-surface-300">Applications</p>
                <p class="mt-1 text-sm font-semibold text-surface-900">
                  {{ posting.applications?.length || 0 }}
                </p>
              </div>
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-surface-300">Hiring State</p>
                <p class="mt-1 text-sm font-semibold text-surface-900">{{ posting.status }}</p>
              </div>
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-surface-300">Created On</p>
                <p class="mt-1 text-sm font-semibold text-surface-900">{{ formatDate(posting.created_at) }}</p>
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>
  
    <Card v-else class="border border-dashed border-slate-300 shadow-sm">
      <template #content>
        <div class="flex flex-col items-center justify-center gap-3 py-12 text-center">
          <div class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-600">
            <i class="pi pi-briefcase text-xl" />
          </div>
          <div class="space-y-1">
            <h3 class="text-lg font-semibold text-surface-900">No job postings found</h3>
            <p class="text-sm text-gray-50">Adjust the filters or create a new posting.</p>
          </div>
        </div>
      </template>
    </Card>
  
    <JobPostingFormModal v-if="showCreateModal" :posting="editingPosting" @close="closeModal" @save="savePosting" />
  
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Card from 'primevue/card'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Skeleton from 'primevue/skeleton'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { usePermissions } from '../../../../composables/usePermissions'
import hrService from '../../../../services/hr.services'
import JobPostingFormModal from '@/Components/JobPostingFormModal.vue'

const { hasPermission } = usePermissions()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

const postings = ref<any[]>([])
const searchTerm = ref('')
const filterStatus = ref<string | null>(null)
const showCreateModal = ref(false)
const editingPosting = ref<any | null>(null)
const loading = ref(false)

const statusOptions = [
  { label: 'All Statuses', value: null },
  { label: 'Open', value: 'Open' },
  { label: 'Closed', value: 'Closed' },
  { label: 'On Hold', value: 'On Hold' },
]

const filteredPostings = computed(() =>
  postings.value.filter((posting) => {
    const search = searchTerm.value.trim().toLowerCase()
    const title = posting.title?.toLowerCase?.() || ''
    const description = posting.description?.toLowerCase?.() || ''
    const matchesSearch = !search || title.includes(search) || description.includes(search)
    const matchesStatus = !filterStatus.value || posting.status === filterStatus.value

    return matchesSearch && matchesStatus
  }),
)

const openPostingsCount = computed(() =>
  filteredPostings.value.filter((posting) => posting.status === 'Open').length)

const totalApplicants = computed(() =>
  filteredPostings.value.reduce((sum, posting) => sum + Number(posting.applications?.length || 0), 0))

const totalHired = computed(() =>
  filteredPostings.value.reduce((sum, posting) => {
    const hired = (posting.applications || []).filter((application: any) => String(application.status) === 'Hired').length
    return sum + hired
  }, 0))

const loadPostings = async () => {
  loading.value = true
  try {
    const data = await hrService.getJobPostings()
    postings.value = data?.data || data || []
  } catch (error) {
    console.error('Failed to load job postings:', error)
    toast.add({
      severity: 'error',
      summary: 'Unable to load job postings',
      detail: 'Please refresh and try again.',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  editingPosting.value = null
  showCreateModal.value = true
}

const closeModal = () => {
  showCreateModal.value = false
  editingPosting.value = null
}

const savePosting = async (postingData: any) => {
  const isEditing = !!editingPosting.value

  try {
    if (isEditing) {
      await hrService.updateJobPosting(editingPosting.value.id, postingData)
      if (postingData.screening_stages?.length) {
        await hrService.updateScreeningStages(editingPosting.value.id, postingData.screening_stages)
      }
    } else {

      await hrService.createJobPosting({
        ...postingData
      })
    }

    closeModal()
    await loadPostings()
    toast.add({
      severity: 'success',
      summary: isEditing ? 'Job posting updated' : 'Job posting created',
      detail: 'The job posting has been saved successfully.',
      life: 2500,
    })
  } catch (error) {
    console.error('Failed to save job posting:', error)
    const err = error as any
    const validationErrors = err?.response?.data?.errors
    const firstError = validationErrors
      ? Object.values(validationErrors)[0]
      : null

    toast.add({
      severity: 'error',
      summary: 'Unable to save job posting',
      detail: Array.isArray(firstError) ? firstError[0] : (err?.response?.data?.message || 'Please review the form and try again.'),
      life: 4000,
    })
  }
}

const editPosting = (posting: any) => {
  editingPosting.value = posting
  showCreateModal.value = true
}

const deletePosting = async (id: number) => {
  confirm.require({
    message: 'This job posting will be removed from the hiring board. Do you want to continue?',
    header: 'Delete Job Posting',
    icon: 'pi pi-exclamation-triangle',
    rejectProps: {
      label: 'Cancel',
      severity: 'secondary',
      outlined: true,
    },
    acceptProps: {
      label: 'Delete',
      severity: 'danger',
    },
    accept: async () => {
      try {
        await hrService.deleteJobPosting(id)
        await loadPostings()
        toast.add({
          severity: 'success',
          summary: 'Job posting deleted',
          detail: 'The posting has been removed successfully.',
          life: 2500,
        })
      } catch (error: any) {
        console.error('Failed to delete job posting:', error)
        toast.add({
          severity: 'error',
          summary: 'Unable to delete job posting',
          detail: error?.response?.data?.message || 'Please try again.',
          life: 3500,
        })
      }
    },
  })
}

const getStatusSeverity = (status: string) => {
  const map: Record<string, 'success' | 'danger' | 'warn' | 'secondary'> = {
    Open: 'success',
    Closed: 'danger',
    'On Hold': 'warn',
  }

  return map[status] || 'secondary'
}

const formatCurrency = (value: number | string) =>
  new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 0,
  }).format(Number(value || 0))

const resolveCreator = (posting: any) => {
  // Common API shapes we handle:
  // 1) creator_full_name / created_by_full_name strings
  // 2) creator / createdBy objects with fname/lname/name/full_name
  // 3) created_by may be an object or an id
  const primaryString =
    posting?.creator_full_name ||
    posting?.created_by_full_name
  if (primaryString) return primaryString

  const objectCandidate =
    posting?.creator ||
    posting?.createdBy ||
    posting?.user ||
    posting?.created_by_user ||
    posting?.creatorUser ||
    (typeof posting?.created_by === 'object' ? posting.created_by : null)

  if (objectCandidate) {
    const fullName =
      objectCandidate.full_name ||
      objectCandidate.name ||
      `${objectCandidate.fname || ''} ${objectCandidate.lname || ''}`.trim()
    if (fullName && fullName.trim()) return fullName
  }

  const fallbackName =
    posting?.creator_name ||
    posting?.created_by_name
  if (fallbackName) return fallbackName

  return posting?.created_by ? `User #${posting.created_by}` : 'N/A'
}

const formatDate = (value?: string) =>
  value ? new Date(value).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A'

onMounted(loadPostings)
</script>
