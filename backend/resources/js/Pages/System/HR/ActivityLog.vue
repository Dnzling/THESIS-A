<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h3 class="text-sm font-semibold text-slate-700">Activity Logs</h3>
        <p class="text-xs text-slate-500">Track important user actions across HR and employee self-service.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <DatePicker v-model="filters.dateRange" selectionMode="range" :manualInput="false" placeholder="Date Range"
          fluid />
        <Button label="Filter" icon="pi pi-filter" severity="secondary" @click="fetchLogs" />
      </div>
    </div>

    <DataTable :value="logs" :loading="loading" class="p-datatable-sm" responsiveLayout="scroll">
      <Column field="created_at" header="Date" />
      <Column header="User">
        <template #body="{ data }">
          {{ data.user?.fname }} {{ data.user?.lname }}
        </template>
      </Column>
      <Column field="action" header="Action" />
      <Column field="description" header="Description" />
      <Column header="Department">
        <template #body="{ data }">
          {{ data.department?.name ?? '—' }}
        </template>
      </Column>
    </DataTable>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import hrService from '@/services/hr.services'

const logs = ref<any[]>([])
const loading = ref(false)

const filters = reactive({
  dateRange: null as Date[] | null
})

const fetchLogs = async () => {
  loading.value = true
  try {
    const params: any = {}
    if (filters.dateRange?.[0]) params.from = filters.dateRange[0].toISOString().slice(0, 10)
    if (filters.dateRange?.[1]) params.to = filters.dateRange[1].toISOString().slice(0, 10)
    const response = await hrService.api.get('/api/activity-logs', { params })
    logs.value = response.data?.data?.data ?? response.data?.data ?? []
  } catch {
    logs.value = []
  } finally {
    loading.value = false
  }
}

onMounted(fetchLogs)
</script>

