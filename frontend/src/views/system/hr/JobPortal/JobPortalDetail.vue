<template>
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
          <Card class="border border-blue-100 shadow-sm">
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
  
          <Card class="border border-blue-100 shadow-sm">
            <template #title>Hiring Process</template>
            <template #content>
              <div class="space-y-3">
                <div v-for="(stage, index) in stages" :key="index"
                  class="flex items-start gap-3 rounded-2xl bg-blue-50/70 p-4">
                  <span
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-xs font-semibold text-white">{{
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
          <Card class="sticky top-4 border border-blue-100 shadow-sm">
            <template #title>Job Highlights</template>
            <template #content>
              <div class="space-y-5">
                <div class="rounded-2xl bg-blue-50/80 p-4">
                  <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Salary Range</p>
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
                <Button label="Apply Job" icon="pi pi-send" severity="info" fluid
                  @click="router.push({ name: 'job-portal.apply', params: { id: route.params.id } })" />
              </div>
            </template>
          </Card>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import hrService, { type JobPosting, type JobPostingStage } from '../../../../services/hr.services'

const route = useRoute()
const router = useRouter()
const posting = ref<JobPosting | null>(null)
const loading = ref(false)

const fetchPosting = async () => {
  loading.value = true
  try {
    const response = await hrService.getPortalJobPosting(route.params.id as string)
    posting.value = response.data
  } finally {
    loading.value = false
  }
}

const stages = computed<JobPostingStage[]>(() => posting.value?.screeningStages || posting.value?.screening_stages || [])
const storeLabel = computed(() => posting.value?.store?.store_name || posting.value?.store?.name || 'Store opening')
const roleLabel = computed(() => posting.value?.role?.display_name || posting.value?.role?.name || posting.value?.department || 'Role')
const formatCurrency = (value: number | string | undefined) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 0 }).format(Number(value || 0))

onMounted(fetchPosting)
</script>
