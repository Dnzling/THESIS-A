<template>
  <div class="min-h-[70vh] flex items-center justify-center">
    <div class="max-w-2xl w-full bg-white shadow-lg rounded-2xl p-8 border border-slate-100">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center">
          <i class="pi pi-lock text-blue-600 text-xl"></i>
        </div>
        <div>
          <h1 class="text-2xl font-semibold text-slate-900">Setup Required</h1>
          <p class="text-sm text-slate-600">This module is not included in your fixed free-trial package.</p>
        </div>
      </div>

      <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 text-sm text-slate-700">
        <p class="font-medium text-slate-800">Module requested:</p>
        <p class="mt-1">{{ moduleLabel }}</p>
      </div>

      <div class="mt-6 grid gap-3 md:grid-cols-2">
        <Button
          label="View Plan & Modules"
          icon="pi pi-arrow-right"
          @click="goToSettings"
        />
        <Button
          label="Upgrade Plan"
          severity="secondary"
          outlined
          @click="goToSettings"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import Button from 'primevue/button'

const page = usePage()

const moduleLabel = computed(() => {
  const query = String(page.url || '').split('?')[1] || ''
  const value = new URLSearchParams(query).get('module') || 'Unknown'
  return value.charAt(0).toUpperCase() + value.slice(1)
})

const goToSettings = () => {
  router.visit('/system/settings')
}
</script>
