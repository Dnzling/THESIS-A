<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 p-3">
      <div>
        <p class="text-sm font-semibold text-slate-800">Overtime History</p>
        <p class="text-xs text-slate-500">All overtime requests filed for this employee.</p>
      </div>
      <Button icon="pi pi-refresh" text rounded severity="secondary" @click="refresh" />
    </div>

    <DataTable
      :value="records"
      :loading="loading"
      stripedRows
      showGridlines
      paginator
      :rows="10"
      dataKey="id"
      class="p-datatable-sm"
    >
      <Column field="created_at" header="Filed On" style="width: 140px">
        <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
      </Column>
      <Column field="ot_start" header="OT Window" style="min-width: 220px">
        <template #body="{ data }">
          {{ formatDateTime(data.ot_start) }} - {{ formatDateTime(data.ot_end) }}
        </template>
      </Column>
      <Column field="ot_minutes" header="Hours" style="width: 100px">
        <template #body="{ data }">{{ toHours(data.ot_minutes) }}</template>
      </Column>
      <Column field="ot_type" header="Type" style="width: 120px">
        <template #body="{ data }">{{ formatLabel(data.ot_type) }}</template>
      </Column>
      <Column field="status" header="Status" style="width: 120px">
        <template #body="{ data }">
          <Tag :value="formatLabel(data.status)" :severity="statusSeverity(data.status)" rounded />
        </template>
      </Column>
      <Column field="reason" header="Reason" style="min-width: 260px">
        <template #body="{ data }">{{ data.reason || '-' }}</template>
      </Column>
    </DataTable>

    <div v-if="!loading && !records.length" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500">
      No overtime records yet.
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import hrService from '../../../../../services/hr.services'

const props = defineProps<{
  employeeId: string | number
}>()

const loading = ref(false)
const records = ref<any[]>([])

const refresh = async () => {
  loading.value = true
  try {
    const response = await hrService.api.get('/api/overtime-requests', {
      params: {
        employee_id: props.employeeId,
        per_page: 100,
      },
    })
    const payload = response?.data?.data?.data || response?.data?.data || []
    records.value = Array.isArray(payload) ? payload : []
  } catch (error) {
    records.value = []
    console.error('Failed to load overtime records', error)
  } finally {
    loading.value = false
  }
}

const formatDate = (value?: string | null) => {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

const formatDateTime = (value?: string | null) => {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const toHours = (minutes?: number | string | null) => {
  const totalMinutes = Number(minutes || 0)
  if (!Number.isFinite(totalMinutes) || totalMinutes <= 0) return '0.0'
  return (totalMinutes / 60).toFixed(1)
}

const formatLabel = (value?: string | null) => {
  if (!value) return '-'
  return value
    .toString()
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())
}

const statusSeverity = (status?: string | null) => {
  const map: Record<string, string> = {
    approved: 'success',
    pending: 'warning',
    rejected: 'danger',
    cancelled: 'secondary',
  }
  return map[String(status || '').toLowerCase()] || 'info'
}

defineExpose({ refresh })

onMounted(() => {
  refresh()
})
</script>
