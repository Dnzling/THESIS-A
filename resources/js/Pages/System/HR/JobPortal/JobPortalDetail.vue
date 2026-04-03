<template>
  <JobPortalLayout>
    <div class="py-6 lg:py-10">
      <div class="space-y-4">
    <div>
      <Button label="Back to Listings" icon="pi pi-arrow-left" severity="secondary" text
        @click="router.push({ name: 'job-portal.index' })" />
  
      <div v-if="loading" class="mt-2 space-y-3">
        <Skeleton height="11rem" />
        <Skeleton height="10rem" />
      </div>
  
      <div v-else class="mt-2 grid gap-4 xl:grid-cols-[1.4fr_0.6fr]">
        <div class="space-y-6">
          <Card class="border border-orange-100 shadow-sm">
            <template #content>
              <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                  <Tag value="Open" severity="success" />
                  <span class="text-xs uppercase tracking-wide text-surface-500">{{ storeLabel }}</span>
                </div>
                <div>
                  <h1 class="text-3xl font-semibold text-surface-900">{{ roleLabel }}</h1>
                  <p class="mt-1 text-base text-surface-600">{{ posting?.title }}</p>
                </div>
                <p class="text-sm leading-7 text-surface-600">{{ posting?.description }}</p>
              </div>
            </template>
          </Card>
  
          <Card class="border border-orange-100 shadow-sm">
            <template #title>Hiring Process</template>
            <template #content>
              <div class="space-y-3">
                <div v-for="(stage, index) in stages" :key="index"
                  class="flex items-start gap-3 rounded-2xl bg-orange-50/70 p-4">
                  <span
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-orange-600 text-xs font-semibold text-white">{{
                    index + 1 }}</span>
                  <div>
                    <p class="text-sm font-semibold text-surface-900">{{ stage.stage_name || stage.name }}</p>
                    <p class="text-xs text-surface-500">{{ stage.description || 'No extra notes provided.' }}</p>
                  </div>
                </div>
              </div>
            </template>
          </Card>
        </div>
  
        <div>
          <Card class="sticky top-4 border border-orange-100 shadow-sm">
            <template #title>Job Highlights</template>
            <template #content>
              <div class="space-y-5">
                <div class="rounded-2xl bg-orange-50/80 p-4">
                  <p class="text-xs font-semibold uppercase tracking-wide text-orange-700">Salary Range</p>
                  <p class="mt-1 text-lg font-semibold text-surface-900">
                    {{ formatCurrency(posting?.salary_min) }} - {{ formatCurrency(posting?.salary_max) }}
                  </p>
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Role</p>
                  <p class="mt-1 text-sm font-semibold text-surface-900">{{ roleLabel }}</p>
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Store</p>
                  <p class="mt-1 text-sm font-semibold text-surface-900">{{ storeLabel }}</p>
                </div>
                <Message v-if="!profileReady" severity="warn" :closable="false">
                  Complete your applicant profile and upload at least one document before applying.
                  <button type="button" class="ml-1 font-semibold text-orange-600 hover:text-orange-700"
                    @click="router.push({ name: 'job-portal.profile' })">
                    Go to Profile
                  </button>
                </Message>
                <Button label="Apply Job" icon="pi pi-send" severity="warn" fluid :disabled="!profileReady"
                  @click="confirmApply" />
              </div>
            </template>
          </Card>
        </div>
      </div>
    </div>
      </div>
    </div>
  </JobPortalLayout>
</template>

<script setup lang="ts">
import JobPortalLayout from './JobPortalLayout.vue'
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import hrService, { type JobPosting, type JobPostingStage } from '../../../../services/hr.services'

const route = useRoute()
const router = useRouter()
const confirm = useConfirm()
const toast = useToast()
const posting = ref<JobPosting | null>(null)
const loading = ref(false)
const profile = ref<any>(null)

const fetchPosting = async () => {
  loading.value = true
  try {
    const response = await hrService.getPortalJobPosting(route.params.id as string)
    posting.value = response.data
  } finally {
    loading.value = false
  }
}

const fetchProfile = async () => {
  try {
    const response = await hrService.getApplicantProfile()
    profile.value = response.data
  } catch {
    profile.value = null
  }
}

const stages = computed<JobPostingStage[]>(() => posting.value?.screeningStages || posting.value?.screening_stages || [])
const storeLabel = computed(() => posting.value?.store?.store_name || posting.value?.store?.name || 'Store opening')
const roleLabel = computed(() => posting.value?.role?.display_name || posting.value?.role?.name || posting.value?.department || 'Role')
const formatCurrency = (value: number | string | undefined) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 0 }).format(Number(value || 0))

const profileReady = computed(() => {
  if (!profile.value) return false
  const required = ['first_name', 'last_name', 'email', 'phone', 'birthday', 'city', 'province', 'barangay', 'address']
  const hasRequired = required.every((key) => Boolean(profile.value?.[key]))
  const hasDocs = Array.isArray(profile.value?.documents) && profile.value.documents.length > 0
  return hasRequired && hasDocs
})

const confirmApply = () => {
  if (!profileReady.value) return
  confirm.require({
    header: 'Submit application?',
    message: 'We will submit your saved profile and documents to this job post.',
    acceptProps: { label: 'Apply', severity: 'warn' },
    rejectProps: { label: 'Cancel', severity: 'secondary', outlined: true },
    accept: async () => {
      try {
        await hrService.applyToPortalJob(route.params.id as string, { use_profile: true })
        toast.add({ severity: 'success', summary: 'Application sent', detail: 'Your profile was submitted.', life: 2500 })
        router.push({ name: 'job-portal.dashboard' })
      } catch (error: any) {
        toast.add({ severity: 'error', summary: 'Unable to apply', detail: error.response?.data?.message || 'Please try again.', life: 3000 })
      }
    },
  })
}

onMounted(() => {
  fetchPosting()
  fetchProfile()
})
</script>
