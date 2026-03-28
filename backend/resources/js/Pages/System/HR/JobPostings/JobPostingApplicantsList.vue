<template>
  <div class="min-h-full">
    <div class="mx-auto max-w-6xl px-6 py-6">
      <div class="flex items-center justify-between gap-3">
        <div>
          <Button label="Back to Job Posting" icon="pi pi-arrow-left" severity="secondary" text @click="router.push({ name: 'hr.job-postings.detail', params: { postingId: route.params.postingId } })" />
          <p class="mt-1 text-xs font-semibold uppercase tracking-[0.24em] text-blue-600">Applicants</p>
          <h1 class="text-3xl font-semibold tracking-tight text-slate-900">{{ posting?.title || 'Job Posting' }}</h1>
        </div>
      </div>

      <div v-if="loading" class="mt-4 space-y-3">
        <Skeleton v-for="item in 4" :key="item" height="5.5rem" />
      </div>

      <Card v-else class="mt-4 border border-slate-200 shadow-none">
        <template #content>
          <div class="space-y-2">
            <div
              v-for="application in applicants"
              :key="application.id"
              class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:flex-row md:items-center md:justify-between"
            >
              <div>
                <p class="font-semibold text-slate-900">{{ application.full_name || `${application.first_name} ${application.last_name}` }}</p>
                <p class="text-sm text-slate-500">{{ application.email }} - {{ application.phone || 'No phone provided' }}</p>
              </div>
              <div class="flex items-center gap-2">
                <Tag :value="application.status || 'Applied'" :severity="applicationSeverity(application.status)" />
                <Button
                  v-if="isHired(application) && application.employee_id"
                  label="View Employee Profile"
                  icon="pi pi-id-card"
                  severity="success"
                  outlined
                  @click="router.push({ name: 'hr.employees.view', params: { id: application.employee_id } })"
                />
                <Button
                  v-else
                  label="Review Applicant"
                  icon="pi pi-user"
                  severity="info"
                  outlined
                  @click="router.push({ name: 'hr.job-applications.review', params: { applicationId: application.id } })"
                />
              </div>
            </div>

            <Message v-if="!applicants.length" severity="info" :closable="false">
              No applicants yet for this posting.
            </Message>
          </div>
        </template>
      </Card>
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

const loadPosting = async () => {
  loading.value = true
  try {
    const response = await hrService.getJobPosting(route.params.postingId as string)
    posting.value = response?.data || response
  } finally {
    loading.value = false
  }
}

const applicationSeverity = (status?: string) => ({ Applied: 'info', Screening: 'contrast', Interview: 'warn', Offer: 'success', Accepted: 'success', Hired: 'success', Rejected: 'danger' }[status || 'Applied'] || 'secondary')

const isHired = (application: any) => String(application?.status || '').toLowerCase() === 'hired'

onMounted(loadPosting)
</script>
