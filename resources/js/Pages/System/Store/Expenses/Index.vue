<template>
  <div class="space-y-5 p-4 text-sm md:p-6">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold text-slate-900">Maintenance &amp; Expenses</h1>
        <p class="mt-1 text-sm text-slate-500">Track requests and OPEX for {{ branchName || 'your branch' }}.</p>
      </div>
      <Button v-if="canManage" label="New Expense Request" icon="pi pi-plus" severity="warn" size="small" @click="router.push({ name: 'store.expenses.create' })" />
    </header>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <div class="grid gap-3 sm:grid-cols-3">
      <Card class="border border-slate-200 shadow-sm"><template #content><p class="text-xs uppercase tracking-wide text-slate-500">Recorded OPEX</p><Skeleton v-if="loading" width="8rem" height="1.5rem" class="mt-2" /><p v-else class="mt-2 text-xl font-semibold text-slate-900">{{ money(summary.paid_total) }}</p><p class="mt-1 text-xs text-slate-500">Paid expenses for selected dates</p></template></Card>
      <Card class="border border-slate-200 shadow-sm"><template #content><p class="text-xs uppercase tracking-wide text-slate-500">Paid Requests</p><Skeleton v-if="loading" width="3rem" height="1.5rem" class="mt-2" /><p v-else class="mt-2 text-xl font-semibold text-slate-900">{{ summary.paid_count }}</p><p class="mt-1 text-xs text-slate-500">Included in the OPEX total</p></template></Card>
      <Card class="border border-slate-200 shadow-sm"><template #content><p class="text-xs uppercase tracking-wide text-slate-500">Needs Attention</p><Skeleton v-if="loading" width="3rem" height="1.5rem" class="mt-2" /><p v-else class="mt-2 text-xl font-semibold text-slate-900">{{ summary.pending_count }}</p><p class="mt-1 text-xs text-slate-500">Awaiting branch or Finance review</p></template></Card>
    </div>

    <Card class="border border-slate-200 shadow-sm">
      <template #content>
        <div class="mb-4 flex flex-wrap items-end gap-3">
          <div class="w-full sm:w-64"><label class="mb-1 block text-xs font-medium text-slate-500">Search</label><InputText v-model="filters.search" placeholder="Reference or expense title" fluid @keydown.enter="load" /></div>
          <div class="w-full sm:w-48"><label class="mb-1 block text-xs font-medium text-slate-500">Status</label><Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" fluid @change="load" /></div>
          <div class="w-full sm:w-48"><label class="mb-1 block text-xs font-medium text-slate-500">From</label><DatePicker v-model="filters.startDate" dateFormat="M d, yy" showIcon fluid @date-select="load" /></div>
          <div class="w-full sm:w-48"><label class="mb-1 block text-xs font-medium text-slate-500">To</label><DatePicker v-model="filters.endDate" dateFormat="M d, yy" showIcon fluid @date-select="load" /></div>
          <Button v-if="hasFilters" label="Clear Filter" severity="danger" size="small" outlined @click="clearFilters" />
          <Button label="Search" icon="pi pi-search" severity="warn" size="small" @click="load" />
        </div>

        <DataTable :value="rows" :loading="loading" lazy paginator :rows="15" :totalRecords="totalRecords" :first="(page - 1) * 15" rowHover size="small" @page="onPage">
          <template #empty><div class="py-10 text-center text-slate-500">No branch expenses recorded for these filters.</div></template>
          <Column field="requested_date" header="Date" sortable><template #body="{ data }">{{ formatDate(data.requested_date) }}</template></Column>
          <Column field="reference_number" header="Reference" />
          <Column field="title" header="Request" style="min-width: 220px"><template #body="{ data }"><p class="font-medium text-slate-900">{{ data.title }}</p><p class="text-xs capitalize text-slate-500">{{ data.category }}</p></template></Column>
          <Column field="estimated_amount" header="Estimate" style="width: 140px"><template #body="{ data }">{{ money(data.estimated_amount) }}</template></Column>
          <Column field="actual_amount" header="Actual" style="width: 140px"><template #body="{ data }">{{ data.actual_amount ? money(data.actual_amount) : '-' }}</template></Column>
          <Column field="status" header="Status" style="width: 180px"><template #body="{ data }"><Badge :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" /></template></Column>
          <Column header="Action" style="width: 100px"><template #body="{ data }"><Button label="View" size="small" rounded outlined @click="router.push({ name: 'store.expenses.detail', params: { id: data.id } })" /></template></Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import DatePicker from 'primevue/datepicker'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import storeService from '@/services/store.service'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()
const canManage = auth.hasPermission('store.expenses.manage')
const loading = ref(true)
const error = ref('')
const branchName = ref('')
const rows = ref<any[]>([])
const totalRecords = ref(0)
const page = ref(1)
const summary = ref({ paid_total: 0, paid_count: 0, pending_count: 0 })
const filters = ref({ search: '', status: '', startDate: null as Date | null, endDate: null as Date | null })
const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Pending Approval', value: 'pending_approval' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'With Finance', value: 'submitted_to_finance' },
  { label: 'Finance Approved', value: 'finance_approved' },
  { label: 'Finance Rejected', value: 'finance_rejected' },
  { label: 'Paid', value: 'paid' },
]
const hasFilters = computed(() => !!filters.value.search || !!filters.value.status || !!filters.value.startDate || !!filters.value.endDate)
const money = (value: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
const formatDate = (value: string) => value ? new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium' }).format(new Date(`${value.slice(0, 10)}T12:00:00`)) : '-'
const dateParam = (value: Date | null) => value ? `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}` : undefined
const statusLabel = (value: string) => String(value || '').replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase())
const statusSeverity = (value: string) => ({ pending_approval: 'warn', approved: 'success', rejected: 'danger', submitted_to_finance: 'info', finance_approved: 'success', finance_rejected: 'danger', paid: 'success' }[value] || 'secondary')

async function load() {
  loading.value = true
  error.value = ''
  try {
    const response = await storeService.getBranchExpenses({
      search: filters.value.search || undefined,
      status: filters.value.status || undefined,
      start_date: dateParam(filters.value.startDate),
      end_date: dateParam(filters.value.endDate),
      page: page.value,
      per_page: 15,
    })
    rows.value = response.data?.data || []
    totalRecords.value = Number(response.data?.total || 0)
    branchName.value = response.branch?.name || ''
    summary.value = response.summary || summary.value
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'Unable to load branch expenses.'
  } finally {
    loading.value = false
  }
}
function onPage(event: any) { page.value = Math.floor(event.first / event.rows) + 1; load() }
function clearFilters() { filters.value = { search: '', status: '', startDate: null, endDate: null }; page.value = 1; load() }
onMounted(load)
</script>
