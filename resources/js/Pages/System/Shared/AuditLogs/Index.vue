<template>
  <div class="mx-auto max-w-[1500px] space-y-4 p-4 sm:p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold text-slate-900">{{ title || 'Audit Logs' }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ subtitle || 'Review recorded system activity.' }}</p>
      </div>
      <Button label="Refresh" icon="pi pi-refresh" size="small" severity="secondary" outlined :loading="loading" @click="loadLogs" />
    </div>

    <Card class="border border-slate-200 shadow-sm">
      <template #content>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
          <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-medium text-slate-600">Search activity</label>
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" class="w-full" size="small" placeholder="Person, action, record, or details" @keyup.enter="applyFilters" />
            </IconField>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">From</label>
            <DatePicker v-model="filters.from" size="small" dateFormat="M d, yy" :maxDate="filters.to || undefined" showIcon fluid />
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">To</label>
            <DatePicker v-model="filters.to" size="small" dateFormat="M d, yy" :minDate="filters.from || undefined" showIcon fluid />
          </div>
          <div class="flex gap-2">
            <Button label="Apply" icon="pi pi-filter" severity="warn" size="small" class="flex-1" :loading="loading" @click="applyFilters" />
            <Button v-if="hasFilters" label="Clear Filter" icon="pi pi-filter-slash" severity="danger" size="small" outlined @click="clearFilters" />
          </div>
        </div>
      </template>
    </Card>

    <Card class="overflow-hidden border border-slate-200 shadow-sm">
      <template #content>
        <DataTable
          :value="logs"
          :loading="loading"
          dataKey="id"
          lazy
          paginator
          :first="(filters.page - 1) * filters.perPage"
          :rows="filters.perPage"
          :rowsPerPageOptions="[10, 20, 50, 100]"
          :totalRecords="totalRecords"
          size="small"
          scrollable
          @page="onPage"
        >
          <Column field="created_at" header="Date" style="min-width: 150px">
            <template #body="{ data }">
              <div class="text-xs text-slate-700">{{ formatDate(data.created_at) }}</div>
              <div class="text-[11px] text-slate-500">{{ formatTime(data.created_at) }}</div>
            </template>
          </Column>
          <Column v-if="scope === 'platform'" field="store.name" header="Store" style="min-width: 150px">
            <template #body="{ data }">{{ data.store?.name || 'Platform' }}</template>
          </Column>
          <Column header="Performed By" style="min-width: 150px">
            <template #body="{ data }">{{ actorName(data) }}</template>
          </Column>
          <Column field="action" header="Action" style="min-width: 150px">
            <template #body="{ data }"><Tag :value="formatLabel(data.action)" severity="info" /></template>
          </Column>
          <Column header="Record" style="min-width: 150px">
            <template #body="{ data }">
              <span>{{ formatLabel(data.entity_type) }}</span>
              <span v-if="data.entity_id" class="ml-1 text-xs text-slate-500">#{{ data.entity_id }}</span>
            </template>
          </Column>
          <Column field="description" header="Details" style="min-width: 260px">
            <template #body="{ data }"><span class="line-clamp-2 text-slate-700">{{ data.description || 'No details recorded' }}</span></template>
          </Column>
          <Column v-if="scope === 'store'" header="Branch" style="min-width: 140px">
            <template #body="{ data }">{{ data.branch?.name || 'Store-wide' }}</template>
          </Column>
          <Column header="View" frozen alignFrozen="right" style="width: 100px">
            <template #body="{ data }">
              <Button label="View" icon="pi pi-eye" size="small" severity="secondary" outlined rounded @click="openLog(data)" />
            </template>
          </Column>
          <template #empty>
            <div class="py-10 text-center">
              <i class="pi pi-history mb-2 text-2xl text-slate-300" />
              <p class="text-sm font-medium text-slate-700">No audit activity found</p>
              <p class="mt-1 text-xs text-slate-500">Try changing the search or date range.</p>
            </div>
          </template>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="detailVisible" modal header="Audit Entry Details" :style="{ width: 'min(42rem, calc(100vw - 2rem))' }">
      <div v-if="selectedLog" class="space-y-4 text-sm">
        <div class="grid gap-3 sm:grid-cols-2">
          <div class="rounded-lg border border-slate-200 p-3"><p class="text-xs text-slate-500">Date and time</p><p class="mt-1 font-medium text-slate-900">{{ formatDateTime(selectedLog.created_at) }}</p></div>
          <div class="rounded-lg border border-slate-200 p-3"><p class="text-xs text-slate-500">Performed by</p><p class="mt-1 font-medium text-slate-900">{{ actorName(selectedLog) }}</p><p class="text-xs text-slate-500">{{ selectedLog.user?.email || '' }}</p></div>
          <div v-if="scope === 'platform'" class="rounded-lg border border-slate-200 p-3"><p class="text-xs text-slate-500">Store</p><p class="mt-1 font-medium text-slate-900">{{ selectedLog.store?.name || 'Platform' }}</p></div>
          <div class="rounded-lg border border-slate-200 p-3"><p class="text-xs text-slate-500">Action</p><p class="mt-1 font-medium text-slate-900">{{ formatLabel(selectedLog.action) }}</p></div>
          <div class="rounded-lg border border-slate-200 p-3"><p class="text-xs text-slate-500">Record</p><p class="mt-1 font-medium text-slate-900">{{ formatLabel(selectedLog.entity_type) }}{{ selectedLog.entity_id ? ` #${selectedLog.entity_id}` : '' }}</p></div>
          <div class="rounded-lg border border-slate-200 p-3"><p class="text-xs text-slate-500">Branch / Department</p><p class="mt-1 font-medium text-slate-900">{{ selectedLog.branch?.name || 'Store-wide' }}<span v-if="selectedLog.department?.name"> · {{ selectedLog.department.name }}</span></p></div>
        </div>
        <div><p class="mb-1 text-xs font-medium uppercase tracking-wide text-slate-500">Details</p><p class="rounded-lg bg-slate-50 p-3 text-slate-800">{{ selectedLog.description || 'No details recorded.' }}</p></div>
        <div v-if="selectedLog.meta && Object.keys(selectedLog.meta).length"><p class="mb-1 text-xs font-medium uppercase tracking-wide text-slate-500">Additional data</p><pre class="max-h-56 overflow-auto rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">{{ prettyMeta(selectedLog.meta) }}</pre></div>
        <div class="grid gap-3 text-xs text-slate-500 sm:grid-cols-2"><p>IP address: <span class="text-slate-700">{{ selectedLog.ip_address || 'Not recorded' }}</span></p><p class="break-words">Browser: <span class="text-slate-700">{{ selectedLog.user_agent || 'Not recorded' }}</span></p></div>
      </div>
      <template #footer><Button label="Close" severity="secondary" outlined size="small" @click="detailVisible = false" /></template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import axiosClient from '@/axios'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import { useToast } from 'primevue/usetoast'

const props = defineProps<{ title?: string; subtitle?: string; auditScope: 'store' | 'platform' }>()
const toast = useToast()
const scope = props.auditScope
const loading = ref(false)
const logs = ref<any[]>([])
const totalRecords = ref(0)
const detailVisible = ref(false)
const selectedLog = ref<any | null>(null)
const filters = reactive({ search: '', from: null as Date | null, to: null as Date | null, page: 1, perPage: 20 })

const hasFilters = computed(() => Boolean(filters.search.trim() || filters.from || filters.to))

const dateParam = (value: Date | null) => value
  ? `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`
  : undefined

const loadLogs = async () => {
  loading.value = true
  try {
    const response = await axiosClient.get('/api/activity-logs', {
      params: {
        search: filters.search.trim() || undefined,
        from: dateParam(filters.from),
        to: dateParam(filters.to),
        page: filters.page,
        per_page: filters.perPage,
      },
    })
    const page = response.data?.data
    logs.value = Array.isArray(page?.data) ? page.data : []
    totalRecords.value = Number(page?.total || 0)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Could Not Load Logs', detail: error?.response?.data?.message || 'Unable to load audit activity.', life: 3500 })
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  filters.page = 1
  loadLogs()
}

const clearFilters = () => {
  filters.search = ''
  filters.from = null
  filters.to = null
  applyFilters()
}

const onPage = (event: any) => {
  filters.page = event.page + 1
  filters.perPage = event.rows
  loadLogs()
}

const openLog = (log: any) => {
  selectedLog.value = log
  detailVisible.value = true
}

const actorName = (log: any) => log.user
  ? `${log.user.fname || ''} ${log.user.lname || ''}`.trim() || log.user.email || 'User'
  : 'System'

const formatLabel = (value?: string | null) => value
  ? value.replaceAll('.', ' ').replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
  : 'General'

const formatDateTime = (value?: string) => {
  if (!value) return 'Not recorded'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? 'Not recorded' : date.toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' })
}

const formatDate = (value?: string) => {
  if (!value) return '-'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? '-' : date.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: '2-digit' })
}

const formatTime = (value?: string) => {
  if (!value) return ''
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? '' : date.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' })
}

const prettyMeta = (meta: Record<string, unknown>) => JSON.stringify(meta, null, 2)

onMounted(loadLogs)
</script>
