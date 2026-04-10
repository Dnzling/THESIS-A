<template>
  <div class="max-w-7xl mx-auto pb-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-2xl font-bold">Violation Reports</h2>
        <p class="text-sm text-gray-500">Review both contract reports and termination requests.</p>
      </div>
      <Button label="Refresh" icon="pi pi-refresh" severity="secondary" @click="loadReports" />
    </div>

    <Card class="rounded-xl border border-slate-200 shadow-sm">
      <template #content>
        <div class="p-4 space-y-4">
          <div class="flex flex-wrap items-center gap-3">
            <span class="p-input-icon-left">
              <i class="pi pi-search" />
              <InputText v-model="searchTerm" placeholder="Search store name or code" @keyup.enter="loadReports" />
            </span>
            <Select
              v-model="statusFilter"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Status"
              class="min-w-[180px]"
            />
            <Select
              v-model="actionFilter"
              :options="actionOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Action"
              class="min-w-[180px]"
            />
            <Button label="Apply" icon="pi pi-filter" class="p-button-outlined" @click="loadReports" />
          </div>

          <DataTable
            :value="reports"
            :loading="loading"
            dataKey="id"
            paginator
            :rows="pagination.perPage"
            :totalRecords="pagination.total"
            :first="(pagination.page - 1) * pagination.perPage"
            @page="onPage"
            emptyMessage="No violation reports found."
          >
            <Column field="id" header="#" style="width: 70px" />
            <Column header="Store">
              <template #body="{ data }">
                <div class="text-sm font-medium text-gray-900">{{ data.store?.name || '-' }}</div>
                <div class="text-xs text-gray-500">{{ data.store?.store_code || 'No code' }}</div>
              </template>
            </Column>
            <Column header="Reporter">
              <template #body="{ data }">
                <div class="text-sm">{{ userFullName(data.reporter) }}</div>
                <div class="text-xs text-gray-500">{{ data.reporter?.email || data.reporter_type }}</div>
              </template>
            </Column>
            <Column header="Supplier">
              <template #body="{ data }">
                <div class="text-sm font-medium text-gray-900">{{ data.supplier?.supplier_name || '-' }}</div>
                <div class="text-xs text-gray-500">{{ data.supplier?.supplier_code || 'No code' }}</div>
              </template>
            </Column>
            <Column header="Contract">
              <template #body="{ data }">
                <div class="text-sm font-medium text-gray-900">{{ data.contract_number || '-' }}</div>
                <div class="text-xs text-gray-500">{{ data.contract_title || 'No contract info' }}</div>
              </template>
            </Column>
            <Column field="report_reason" header="Reason" />
            <Column header="Type" style="width: 190px">
              <template #body="{ data }">
                <Tag :value="reportTypeLabel(data)" :severity="reportTypeSeverity(data)" />
              </template>
            </Column>
            <Column header="Status">
              <template #body="{ data }">
                <Tag :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
              </template>
            </Column>
            <Column header="Action">
              <template #body="{ data }">
                <Tag v-if="data.action_type" :value="formatStatus(data.action_type)" severity="info" />
                <span v-else class="text-xs text-gray-400">None</span>
              </template>
            </Column>
            <Column header="Reported">
              <template #body="{ data }">
                <span class="text-sm">{{ formatDate(data.created_at) }}</span>
              </template>
            </Column>
            <Column header="">
              <template #body="{ data }">
                <Button label="View" size="small" severity="info" @click="openReport(data.id)" />
              </template>
            </Column>
          </DataTable>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import axiosClient from '@/axios'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Tag from 'primevue/tag'

const router = useRouter()
const loading = ref(false)
const reports = ref<any[]>([])
const searchTerm = ref('')
const statusFilter = ref('')
const actionFilter = ref('')
const pagination = ref({
  page: 1,
  perPage: 15,
  total: 0,
})

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Actioned', value: 'actioned' },
]

const actionOptions = [
  { label: 'All Actions', value: '' },
  { label: 'Termination Requested', value: 'termination_requested' },
  { label: 'Suspended', value: 'suspended' },
  { label: 'Banned', value: 'banned' },
]

const loadReports = async () => {
  loading.value = true
  try {
    const response = await axiosClient.get('/api/admin/violation-reports', {
      params: {
        search: searchTerm.value || undefined,
        status: statusFilter.value || undefined,
        action_type: actionFilter.value || undefined,
        page: pagination.value.page,
        per_page: pagination.value.perPage,
      },
    })
    const payload = response?.data || {}
    reports.value = payload.data || []
    pagination.value.total = payload.total || 0
  } catch (error) {
    console.error('Failed to load violation reports', error)
  } finally {
    loading.value = false
  }
}

const onPage = (event: any) => {
  pagination.value.page = Math.floor(event.first / event.rows) + 1
  pagination.value.perPage = event.rows
  loadReports()
}

const openReport = (id: number) => {
  router.push({ path: `/admin/violation-reports/${id}` })
}

const formatDate = (value: string) => {
  if (!value) return '-'
  return new Date(value).toLocaleString()
}

const formatStatus = (value: string) => {
  if (!value) return 'Unknown'
  return value.replace(/_/g, ' ').toUpperCase()
}

const statusSeverity = (status: string) => {
  if (status === 'actioned') return 'success'
  if (status === 'pending') return 'warning'
  return 'secondary'
}

const userFullName = (u: any) => {
  if (!u) return 'Anonymous'
  return [u.first_name || u.fname, u.last_name || u.lname].filter(Boolean).join(' ') || 'Anonymous'
}

const reportTypeLabel = (row: any) => {
  return row?.action_type === 'termination_requested' ? 'Termination Request' : 'Violation Report'
}

const reportTypeSeverity = (row: any) => {
  return row?.action_type === 'termination_requested' ? 'danger' : 'info'
}

onMounted(() => {
  loadReports()
})
</script>
