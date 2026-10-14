<template>
  <div class="min-h-screen p-4">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-bold text-gray-800">Stock Adjustments</h1>
        <p class="text-xs text-gray-500">Review stock corrections, physical counts, and approvals across your branches.</p>
      </div>
      <SplitButton v-if="canCreateAdjustments" label="Create Adjustment" icon="pi pi-plus" severity="warn" size="small"
        :model="createAdjustmentItems" @click="openCreate()" />
    </div>

    <Card>
      <template #content>
        <div class="mb-5 grid grid-cols-1 items-end gap-3 sm:grid-cols-2 xl:grid-cols-5">
          <IconField class="w-full">
            <InputIcon class="pi pi-search" />
            <InputText v-model="filters.search" placeholder="Search reference or reason" size="small" class="w-full text-sm"
              @keyup.enter="loadAdjustments(1)" />
          </IconField>
          <Select v-model="filters.type" :options="typeOptions" optionLabel="label" optionValue="value"
            placeholder="Adjustment Type" showClear size="small" class="w-full text-sm" @change="loadAdjustments(1)" />
          <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value"
            placeholder="Status" showClear size="small" class="w-full text-sm" @change="loadAdjustments(1)" />
          <DatePicker v-model="dateRange" selectionMode="range" dateFormat="M d, yy" :manualInput="false"
            placeholder="Adjustment date range" showIcon size="small" class="w-full text-sm" @date-select="onDateSelect" />
          <div class="flex gap-2">
            <Button label="Search" icon="pi pi-search" size="small" @click="loadAdjustments(1)" />
            <Button v-if="hasActiveFilters" label="Clear All" severity="danger" outlined size="small" @click="resetFilters" />
          </div>
        </div>

        <div v-if="error" class="mb-4 rounded-lg border border-red-100 bg-red-50 p-3 text-sm text-red-700">{{ error }}</div>
        <div v-if="loading" class="space-y-3">
          <Skeleton height="1.5rem" />
          <Skeleton v-for="row in 7" :key="row" height="2.75rem" />
        </div>
        <DataTable v-else :value="adjustments" dataKey="id" paginator lazy :rows="pagination.per_page"
          :totalRecords="pagination.total" :first="(pagination.current_page - 1) * pagination.per_page"
          :rowsPerPageOptions="[15, 25, 50]" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageSelect"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" class="p-datatable-sm text-xs"
          rowHover :rowClass="() => 'cursor-pointer'" :sortField="sortField" :sortOrder="sortOrder"
          @page="onPageChange" @sort="onSort" @row-click="onRowClick">
          <template #empty>
            <div class="py-10 text-center">
              <i class="pi pi-inbox text-4xl text-slate-300"></i>
              <p class="mt-2 font-medium text-slate-700">No stock adjustments found</p>
              <p class="mt-1 text-xs text-slate-500">Try changing the filters or create an adjustment.</p>
            </div>
          </template>
          <Column field="adjustment_date" header="Date" sortable style="min-width: 130px">
            <template #body="{ data }"><p class="font-medium text-slate-800">{{ formatDate(data.adjustment_date) }}</p><p class="text-[11px] text-slate-500">{{ formatTime(data.created_at) }}</p></template>
          </Column>
          <Column field="adjustment_number" header="Reference" sortable style="min-width: 180px">
            <template #body="{ data }"><span class="font-mono font-semibold text-slate-900">{{ data.adjustment_number }}</span></template>
          </Column>
          <Column header="Branch" style="min-width: 140px"><template #body="{ data }">{{ data.branch?.name || '—' }}</template></Column>
          <Column header="Type" style="min-width: 130px"><template #body="{ data }">{{ label(data.type) }}</template></Column>
          <Column header="Reason" style="min-width: 190px"><template #body="{ data }"><span class="block max-w-xs truncate" :title="formatReason(data.reason)">{{ formatReason(data.reason) }}</span></template></Column>
          <Column header="Items" style="width: 80px"><template #body="{ data }">{{ data.items_count ?? 0 }}</template></Column>
          <Column field="status" header="Status" sortable style="min-width: 130px"><template #body="{ data }"><Tag :value="label(data.status)" :severity="statusSeverity(data.status)" /></template></Column>
          <Column header="Actions" style="width: 75px">
            <template #body="{ data }"><Button v-if="canViewAdjustments" icon="pi pi-eye" text rounded size="small" aria-label="View adjustment" @click.stop="openDetail(data.id)" /></template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import axiosClient from '@/axios'
import { useAuthStore } from '@/stores/auth'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import DatePicker from 'primevue/datepicker'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import SplitButton from 'primevue/splitbutton'
import Tag from 'primevue/tag'

const auth = useAuthStore()
const canViewAdjustments = computed(() => auth.hasPermission('inventory.adjustments.view'))
const canCreateAdjustments = computed(() => auth.hasPermission('inventory.adjustments.create') || auth.hasPermission('inventory.adjustments.manage'))
const loading = ref(true)
const error = ref('')
const adjustments = ref<any[]>([])
const pagination = reactive({ current_page: 1, per_page: 15, total: 0 })
const filters = reactive({ search: '', status: null as string | null, type: null as string | null })
const dateRange = ref<Date[] | null>(null)
const sortField = ref('adjustment_date')
const sortOrder = ref(-1)
const hasActiveFilters = computed(() => Boolean(filters.search || filters.status || filters.type || dateRange.value?.some(Boolean)))
const typeOptions = ['physical_count', 'cycle_count', 'spot_check', 'damage', 'loss', 'found', 'correction', 'writeoff'].map(value => ({ label: label(value), value }))
const statusOptions = ['draft', 'pending_approval', 'approved', 'applied', 'rejected', 'cancelled'].map(value => ({ label: label(value), value }))
const createAdjustmentItems = [
  { label: 'Physical Count', icon: 'pi pi-list', command: () => openCreate('physical_count') },
  { label: 'Correction', icon: 'pi pi-pencil', command: () => openCreate('correction') },
]

function label(value?: string | null) { return value ? value.replace(/_/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase()) : '—' }
function formatReason(value?: string | null) {
  const reasons: Record<string, string> = { physical_count: 'Physical Count Correction', damaged: 'Damaged Goods', expired: 'Expired Items', theft: 'Theft/Loss', wrong_delivery: 'Wrong Delivery', quality_control: 'Quality Control', sample: 'Sample/Demo Usage', other: 'Other' }
  return value ? reasons[value] || label(value) : '—'
}
function statusSeverity(value: string) { return value === 'applied' || value === 'approved' ? 'success' : value === 'pending_approval' ? 'warn' : value === 'rejected' || value === 'cancelled' ? 'danger' : 'secondary' }
function formatDate(value?: string | null) { return value ? new Date(value).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—' }
function formatTime(value?: string | null) { return value ? new Date(value).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' }) : '' }
function localDate(value: Date) { return `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}` }
function openCreate(type?: string) { router.visit(`/inventory/adjustments/create${type ? `?type=${type}` : ''}`) }
function openDetail(id: number) { router.visit(`/inventory/adjustments/${id}`) }
function onRowClick(event: any) { if (canViewAdjustments.value && event.data?.id) openDetail(event.data.id) }
function onPageChange(event: any) { pagination.per_page = event.rows; loadAdjustments(event.page + 1) }
function onSort(event: any) { sortField.value = String(event.sortField || 'adjustment_date'); sortOrder.value = event.sortOrder || -1; loadAdjustments(1) }
function onDateSelect() { if (dateRange.value?.length === 2 && dateRange.value[1]) loadAdjustments(1) }
function resetFilters() { filters.search = ''; filters.status = null; filters.type = null; dateRange.value = null; loadAdjustments(1) }
async function loadAdjustments(page = pagination.current_page) {
  loading.value = true
  error.value = ''
  try {
    const params: Record<string, any> = { page, per_page: pagination.per_page, sort_field: sortField.value, sort_direction: sortOrder.value === 1 ? 'asc' : 'desc' }
    if (filters.search.trim()) params.search = filters.search.trim()
    if (filters.status) params.status = filters.status
    if (filters.type) params.type = filters.type
    if (dateRange.value?.[0]) params.date_from = localDate(dateRange.value[0])
    if (dateRange.value?.[1]) params.date_to = localDate(dateRange.value[1])
    const response = await axiosClient.get('/api/inventory/adjustments', { params })
    const result = response.data?.data
    adjustments.value = result?.data || []
    pagination.current_page = result?.current_page || page
    pagination.total = Number(result?.total || 0)
  } catch (cause: any) {
    adjustments.value = []
    error.value = cause?.response?.data?.message || 'Unable to load stock adjustments.'
  } finally { loading.value = false }
}
onMounted(() => loadAdjustments(1))
</script>
