<template>
  <div class="space-y-5">
    <header class="flex flex-wrap items-end justify-between gap-3">
      <div><p class="text-xs font-semibold uppercase tracking-wider text-orange-600">Store Operations</p><h1 class="text-2xl font-semibold text-slate-900">Branch Attendance</h1><p class="mt-1 text-sm text-slate-500">Read-only attendance status for {{ data?.branch?.name || 'your assigned branch' }}.</p></div>
      <Button label="Refresh" icon="pi pi-refresh" outlined size="small" :loading="loading" @click="load" />
    </header>

    <Card class="border border-slate-200 shadow-sm"><template #content><div class="grid gap-3 md:grid-cols-[14rem_1fr_auto] md:items-end">
      <div><label class="mb-1 block text-sm font-medium text-slate-700">Attendance date</label><DatePicker v-model="selectedDate" dateFormat="MM d, yy" :maxDate="new Date()" showIcon fluid /></div>
      <div><label class="mb-1 block text-sm font-medium text-slate-700">Search employee</label><InputText v-model="search" fluid placeholder="Name or employee ID" @keyup.enter="load" /></div>
      <Button label="Apply" icon="pi pi-search" @click="load" />
    </div></template></Card>

    <div v-if="loading && !data" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"><Skeleton v-for="i in 4" :key="i" height="7rem" borderRadius="14px" /></div>
    <Message v-else-if="error" severity="error" :closable="false">{{ error }}</Message>
    <template v-else-if="data">
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5"><Card v-for="item in metrics" :key="item.label" class="border border-slate-200 shadow-sm"><template #content><p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ item.label }}</p><p class="mt-2 text-2xl font-semibold text-slate-900">{{ item.value }}</p></template></Card></div>
      <Card class="border border-slate-200 shadow-sm"><template #title><span class="text-base">{{ data.branch.name }} · {{ displayDate(data.date) }}</span></template><template #content>
        <DataTable :value="data.employees" paginator :rows="10" stripedRows scrollable>
          <template #empty><div class="py-10 text-center text-sm text-slate-500">No active employees match this branch and search.</div></template>
          <Column header="Employee"><template #body="{ data: row }"><div class="font-medium text-slate-900">{{ row.name || 'Unnamed employee' }}</div><small class="text-slate-500">{{ row.employee_number }}</small></template></Column>
          <Column header="Status"><template #body="{ data: row }"><Tag :value="label(row.status)" :severity="severity(row.status)" /></template></Column>
          <Column header="Clock in"><template #body="{ data: row }">{{ clock(row.clock_in) }}<small v-if="row.late_minutes" class="block text-amber-600">Late {{ duration(row.late_minutes) }}</small></template></Column>
          <Column header="Clock out"><template #body="{ data: row }">{{ clock(row.clock_out) }}<small v-if="row.overtime_minutes" class="block text-orange-600">Overtime {{ duration(row.overtime_minutes) }}</small></template></Column>
          <Column header="Worked"><template #body="{ data: row }">{{ row.worked_minutes == null ? '—' : duration(row.worked_minutes) }}</template></Column>
        </DataTable>
      </template></Card>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import axios from '@/axios'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DatePicker from 'primevue/datepicker'
import InputText from 'primevue/inputtext'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Skeleton from 'primevue/skeleton'
import Message from 'primevue/message'

const selectedDate = ref(new Date())
const search = ref('')
const data = ref<any>(null)
const loading = ref(true)
const error = ref('')
const localDate = (date: Date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
const load = async () => {
  loading.value = true; error.value = ''
  try { data.value = (await axios.get('/api/store/attendance', { params: { date: localDate(selectedDate.value), search: search.value.trim() || undefined } })).data.data }
  catch (err: any) { error.value = err?.response?.data?.message || 'Unable to load branch attendance.' }
  finally { loading.value = false }
}
onMounted(load)
const metrics = computed(() => data.value ? [
  { label: 'Employees', value: data.value.summary.total },
  { label: 'Clocked in', value: data.value.summary.clocked_in },
  { label: 'Present / completed', value: data.value.summary.present },
  { label: 'Late', value: data.value.summary.late },
  { label: 'Not clocked in / absent', value: data.value.summary.not_clocked_in + data.value.summary.absent },
] : [])
const label = (value: string) => String(value || 'unknown').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
const severity = (value: string) => value === 'present' || value === 'clocked_in' ? 'success' : value === 'late' || value === 'not_clocked_in' ? 'warn' : 'danger'
const displayDate = (value: string) => new Date(`${value}T00:00:00`).toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' })
const clock = (value: string | null) => value ? new Date(value).toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' }) : '—'
const duration = (value: number) => `${Math.floor(Number(value || 0) / 60)}h ${Number(value || 0) % 60}m`
</script>
