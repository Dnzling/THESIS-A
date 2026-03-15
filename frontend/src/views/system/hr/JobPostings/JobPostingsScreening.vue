<template>
  <div class="space-y-6">
    <section class="flex flex-col gap-4 rounded-3xl border border-surface-200 bg-white px-6 py-6 shadow-sm lg:flex-row lg:items-center lg:justify-between">
      <div class="space-y-2">
        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-surface-500">Hiring Pipeline</p>
        <h1 class="text-3xl font-semibold tracking-tight text-surface-900">
          {{ jobPosting?.title || 'Screening Pipeline' }}
        </h1>
        <p class="text-sm text-surface-500">
          Review applicants by stage and move them through the hiring process with less manual tracking.
        </p>
      </div>

      <Button
        label="Refresh"
        icon="pi pi-refresh"
        severity="secondary"
        outlined
        @click="refreshApplications"
      />
    </section>

    <div class="grid gap-4 xl:grid-cols-4">
      <Card
        v-for="stage in screeningStages"
        :key="stage.id"
        class="border border-surface-200 shadow-sm"
      >
        <template #content>
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg font-semibold text-surface-900">{{ stage.name }}</h3>
                <p class="text-xs uppercase tracking-wide text-surface-500">Stage queue</p>
              </div>
              <Tag :value="String(getApplicationsByStage(stage).length)" severity="info" />
            </div>

            <div v-if="getApplicationsByStage(stage).length" class="space-y-3">
              <div
                v-for="app in getApplicationsByStage(stage)"
                :key="app.id"
                class="rounded-2xl border border-surface-200 bg-slate-50 p-4 transition-all duration-200 hover:border-primary-300 hover:bg-white hover:shadow-sm"
              >
                <div class="space-y-3">
                  <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1">
                      <h4 class="text-sm font-semibold text-surface-900">{{ app.full_name }}</h4>
                      <p class="text-xs text-surface-500">{{ app.email }}</p>
                    </div>
                    <Tag
                      :value="app.is_internal ? 'Internal' : 'External'"
                      :severity="app.is_internal ? 'contrast' : 'secondary'"
                    />
                  </div>

                  <div class="grid gap-2 text-xs text-surface-600">
                    <div class="flex items-center gap-2">
                      <i class="pi pi-phone text-[11px]" />
                      <span>{{ app.phone || 'No phone number' }}</span>
                    </div>
                  </div>

                  <div class="flex items-center justify-between gap-3">
                    <Button
                      label="View"
                      severity="secondary"
                      text
                      class="p-0"
                      @click="selectedApplication = app"
                    />
                    <Button
                      icon="pi pi-angle-right"
                      rounded
                      text
                      severity="secondary"
                      @click="moveApplication(app, stage)"
                    />
                  </div>
                </div>
              </div>
            </div>

            <Message v-else severity="secondary" :closable="false">
              No applications in this stage.
            </Message>
          </div>
        </template>
      </Card>
    </div>

    <ApplicationDetailModal
      v-if="selectedApplication"
      :application="selectedApplication"
      @close="selectedApplication = null"
      @update="refreshApplications"
    />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Message from 'primevue/message'
import Tag from 'primevue/tag'
import { useRoute } from 'vue-router'
import hrService from '../../../../services/hr.services'
import ApplicationDetailModal from '../../../../components/ApplicationDetailModal.vue'

const route = useRoute()

const jobPosting = ref<any | null>(null)
const screeningStages = ref<any[]>([])
const applications = ref<any[]>([])
const selectedApplication = ref<any | null>(null)

const getApplicationsByStage = (stage: any) =>
  applications.value.filter((app) => {
    const latestTimeline = app.timeline?.[0]
    return latestTimeline?.stage_id === stage.id
  })

const loadData = async () => {
  try {
    const postingId = route.params.postingId
    const postingData = await hrService.getJobPosting(postingId as string)
    jobPosting.value = postingData
    screeningStages.value = postingData.screeningStages || postingData.screening_stages || []

    const applicationsData = await hrService.getJobPostingApplications(postingId as string)
    applications.value = applicationsData?.data || applicationsData || []
  } catch (error) {
    console.error('Failed to load screening pipeline:', error)
  }
}

const moveApplication = async (app: any, toStage: any) => {
  try {
    await hrService.updateJobApplicationStatus(app.id, {
      status: toStage.name === 'Offer' ? 'Offer' : 'Screening',
      stage_id: toStage.id,
      notes: `Moved to ${toStage.name}`,
    })
    await loadData()
  } catch (error) {
    console.error('Failed to move application:', error)
  }
}

const refreshApplications = async () => {
  await loadData()
}

onMounted(loadData)
</script>
