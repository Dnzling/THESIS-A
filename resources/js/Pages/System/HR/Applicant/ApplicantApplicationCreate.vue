<template>
  <JobPortalLayout>
    <div class="py-6 lg:py-10">
      <div class="mx-auto max-w-3xl">
        <Card class="border border-orange-100 shadow-sm">
          <template #content>
            <div class="space-y-4 text-center">
              <h1 class="text-3xl font-semibold text-slate-900">Use your saved profile to apply</h1>
              <p class="text-sm text-slate-500">
                We now submit applications using your saved applicant profile and documents. Please complete your profile first.
              </p>
              <div class="flex flex-wrap justify-center gap-3">
                <Button label="Go to Profile" severity="warn" @click="router.push({ name: 'job-portal.profile' })" />
                <Button label="Back to Job" severity="secondary" outlined @click="router.push({ name: 'job-portal.detail', params: { id: route.params.id } })" />
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </JobPortalLayout>
</template>

<script setup lang="ts">
import JobPortalLayout from '../JobPortal/JobPortalLayout.vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
</script>
