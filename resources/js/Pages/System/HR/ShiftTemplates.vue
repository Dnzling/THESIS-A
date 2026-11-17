<template>
  <div class="space-y-5 text-sm">
    <header class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-orange-700">Human Resources</p>
        <h1 class="mt-1 text-xl font-semibold text-slate-900">Shift Templates</h1>
        <p class="mt-1 text-sm text-slate-500">Manage reusable shift definitions for employee schedules and onboarding.</p>
      </div>
      <div class="flex gap-2">
        <Button label="Schedule Management" icon="pi pi-arrow-left" severity="secondary" outlined size="small" @click="router.push({ name: 'hr.shifts' })" />
        <Button label="Create Shift Template" icon="pi pi-plus" severity="warn" size="small" @click="router.push({ name: 'hr.shifts.create' })" />
      </div>
    </header>

    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <div class="mb-3 flex items-center justify-between gap-3">
          <p class="text-xs text-slate-500">Templates saved for this store</p>
          <Button icon="pi pi-refresh" label="Refresh" severity="secondary" text size="small" :loading="loading" @click="loadTemplates" />
        </div>
        <div v-if="loading" class="space-y-2">
          <Skeleton v-for="row in 5" :key="row" height="2.75rem" />
        </div>
        <Message v-else-if="error" severity="error" :closable="false">{{ error }}</Message>
        <DataTable v-else :value="templates" dataKey="id" size="small" rowHover responsiveLayout="scroll" class="text-xs">
          <template #empty>
            <div class="py-12 text-center">
              <i class="pi pi-clock text-2xl text-slate-300" />
              <p class="mt-2 text-sm font-medium text-slate-700">No shift templates yet</p>
              <p class="mt-1 text-xs text-slate-500">Create a reusable shift to select for employee schedules.</p>
              <Button label="Create Shift Template" icon="pi pi-plus" severity="warn" size="small" class="mt-4" @click="router.push({ name: 'hr.shifts.create' })" />
            </div>
          </template>
          <Column field="name" header="Template" style="min-width: 180px">
            <template #body="{ data }">
              <p class="font-semibold text-slate-900">{{ data.name }}</p>
              <p class="mt-0.5 text-[11px] text-slate-500">{{ data.code || 'No code' }}</p>
            </template>
          </Column>
          <Column header="Schedule" style="min-width: 190px">
            <template #body="{ data }">
              <p class="font-medium text-slate-800">{{ formatTime(data.start_time) }} – {{ formatTime(data.end_time) }}</p>
              <p class="mt-0.5 text-[11px] text-slate-500">{{ dayLabels(data.week_days) }}</p>
            </template>
          </Column>
          <Column header="Break" style="min-width: 135px">
            <template #body="{ data }">{{ data.break_start ? `${formatTime(data.break_start)} – ${formatTime(data.break_end)}` : 'No break set' }}</template>
          </Column>
          <Column header="Type">
            <template #body="{ data }">{{ label(data.shift_type) }}</template>
          </Column>
          <Column header="Grace period" style="min-width: 110px">
            <template #body="{ data }">{{ Number(data.grace_period_minutes || 0) }} min</template>
          </Column>
          <Column header="Status">
            <template #body="{ data }"><Badge :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'info' : 'secondary'" /></template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import hrService from '@/services/hr.services'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Message from 'primevue/message'
import Skeleton from 'primevue/skeleton'
import { useToast } from 'primevue/usetoast'

const router = useRouter()
const toast = useToast()
const templates = ref<any[]>([])
const loading = ref(true)
const error = ref('')

async function loadTemplates() {
  loading.value = true
  error.value = ''
  try {
    const response = await hrService.getShiftManagementTemplates({ per_page: 200 })
    const rows = response?.data?.data
    templates.value = Array.isArray(rows) ? rows : Array.isArray(rows?.data) ? rows.data : []
  } catch (cause: any) {
    error.value = cause?.response?.data?.message || 'Unable to load shift templates.'
    toast.add({ severity: 'error', summary: 'Shift templates unavailable', detail: error.value, life: 4000 })
  } finally {
    loading.value = false
  }
}

function formatTime(value?: string | null) {
  if (!value) return '—'
  const [hour, minute] = String(value).slice(0, 5).split(':').map(Number)
  return new Date(2000, 0, 1, hour, minute).toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit', hour12: true })
}

function dayLabels(value?: string[] | string | null) {
  const days = Array.isArray(value) ? value : typeof value === 'string' ? value.split(',') : []
  return days.length ? days.map((day) => day.charAt(0).toUpperCase() + day.slice(1)).join(', ') : 'Flexible days'
}

function label(value?: string | null) {
  return value ? value.charAt(0).toUpperCase() + value.slice(1) : '—'
}

onMounted(loadTemplates)
</script>
