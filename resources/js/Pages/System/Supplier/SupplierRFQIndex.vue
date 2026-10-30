<template>
  <div class="max-w-7xl mx-auto space-y-4 px-4 py-4 sm:px-6 lg:px-8">
    <div>
      <h1 class="text-xl font-semibold text-slate-900">Request for Quotations</h1>
      <p class="mt-1 text-sm text-slate-500">Review requests and submit your product quotations.</p>
    </div>
  
    <Card class="rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <template #header>
        <div class="flex flex-wrap items-end gap-2 px-4 pt-4 pb-1">
          <div class="w-full sm:w-64">
            <InputText v-model="searchQuery" size="small" placeholder="Search RFQ number" class="w-full text-xs" @input="onSearch" />
          </div>
          <div class="w-full sm:w-40">
            <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" size="small"
              optionValue="value" placeholder="All Statuses" class="w-full text-xs" @change="onFilterChange" />
          </div>
          <div class="w-full sm:w-56">
            <DatePicker v-model="dateRange" selectionMode="range" size="small" dateFormat="yy-mm-dd" showIcon
              placeholder="Date range" class="w-full text-xs" @update:modelValue="onFilterChange" />
          </div>
          <Button v-if="hasActiveFilters" label="Clear Filter" icon="pi pi-filter-slash" severity="danger" size="small" class="text-xs" @click="resetFilters" />
          <Button label="Refresh" icon="pi pi-refresh" severity="secondary" outlined size="small" class="text-xs" :loading="loading" @click="loadRFQs" />
        </div>
      </template>
      <template #content>
        <div class="px-4 pb-4 pt-3">
          <div v-if="loading" class="space-y-3">
            <Skeleton v-for="i in 5" :key="i" height="64px" class="rounded-xl" />
          </div>
          <DataTable v-else :value="rfqs" :paginator="true" :rows="rows" :first="first" :totalRecords="totalRecords"
            :lazy="true" dataKey="id" sortMode="single" :sortField="sortField" :sortOrder="sortOrder"
            @sort="onSort" @page="onPageChange" class="p-datatable-sm text-sm" rowHover responsiveLayout="scroll"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            :rowsPerPageOptions="[10,20,50]">
            <template #empty>
              <div class="py-8 text-center text-sm text-slate-500">No RFQs match the selected filters.</div>
            </template>
  
            <Column header="Date" field="created_at" sortable style="min-width: 140px">
              <template #body="{ data }">
                <span class="text-sm text-gray-700">{{ formatDate(data.created_at) }}</span>
              </template>
            </Column>
  
            <Column header="RFQ" field="rfq_number" style="min-width: 200px">
              <template #body="{ data }">
                <div class="flex items-center gap-2">
              
                  <div>
                    <div class="font-semibold text-gray-900">
                      #{{ data.rfq_number }}
                    </div>
                    <div class="mt-0.5 text-xs text-slate-500">
                      {{ data.store?.store_name || data.store?.name || data.store_name || data.store?.store_code || 'N/A' }}
                    </div>
                  </div>
                </div>
              </template>
            </Column>
  
            <Column header="Items" style="width: 80px">
              <template #body="{ data }">
                <Badge :value="data.items?.length || 0" severity="info" />
              </template>
            </Column>
  
            <Column header="Status" field="status" style="min-width: 140px">
              <template #body="{ data }">
                <Badge :value="formatStatus(data.status)" :severity="getStatusSeverity(data.status)"
                  class="rounded-full text-xs px-3 py-1 font-medium" />
              </template>
            </Column>
  
            <Column header="Actions" style="width: 110px">
              <template #body="{ data }">
              <Button label="View" size="small" icon="pi pi-eye" outlined rounded class="text-xs" @click="viewDetail(data.id)" />
              </template>
            </Column>
          </DataTable>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Badge from 'primevue/badge'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Skeleton from 'primevue/skeleton'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import supplierService from '../../../services/supplier.service'

const router = useRouter()
const toast = useToast()
const loading = ref(true)
const rfqs = ref([])
const searchQuery = ref('')
const statusFilter = ref('')
const dateRange = ref<any | null>(null)
const rows = ref(10)
const first = ref(0)
const totalRecords = ref(0)
const sortField = ref('created_at')
const sortOrder = ref(-1)
const hasActiveFilters = computed(() => Boolean(searchQuery.value.trim() || statusFilter.value || dateRange.value?.some(Boolean)))

const statusOptions = [
  { label: 'All', value: '' },
  { label: 'Draft', value: 'draft' },
  { label: 'Pending', value: 'pending' },
  { label: 'Receiving', value: 'receiving' },
  { label: 'Partially Approved', value: 'partially_approved' },
  { label: 'Awarded', value: 'awarded' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Cancelled', value: 'cancelled' },
]

const getStatusSeverity = (status: string) => {
  const map: { [key: string]: string } = {
    draft: 'secondary',
    pending: 'warn',
    receiving: 'info',
    partially_approved: 'warn',
    awarded: 'success',
    approved: 'success',
    rejected: 'danger',
    cancelled: 'secondary',
  }
  return map[status] || 'info'
}

const formatDate = (date: string) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const loadRFQs = async () => {
  try {
    loading.value = true
    const res = await supplierService.getSupplierRFQs({
      page: Math.floor(first.value / rows.value) + 1,
      per_page: rows.value,
      search: searchQuery.value,
      status: statusFilter.value || undefined,
      date_from: dateRange.value?.[0] ? formatApiDate(dateRange.value[0]) : undefined,
      date_to: dateRange.value?.[1] ? formatApiDate(dateRange.value[1]) : undefined,
      sort_field: sortField.value,
      sort_order: sortOrder.value === 1 ? 'asc' : 'desc',
    })
    const payload = res ?? {}
    const pagination = payload.data ?? payload
    const list = pagination.data ?? []
    rfqs.value = list
    totalRecords.value = pagination.total ?? list.length
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load RFQs',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const onSearch = () => {
  first.value = 0
  loadRFQs()
}

const onPageChange = (event: any) => {
  first.value = event.first
  rows.value = event.rows
  loadRFQs()
}

const onSort = (event: any) => {
  sortField.value = event.sortField
  sortOrder.value = event.sortOrder
  loadRFQs()
}

const resetFilters = () => {
  searchQuery.value = ''
  statusFilter.value = ''
  dateRange.value = null
  first.value = 0
  loadRFQs()
}

const viewDetail = (id: number) => {
  router.push(`/supplier-portal/rfqs/${id}`)
}

const onFilterChange = () => {
  first.value = 0
  loadRFQs()
}

const formatApiDate = (value: any) => {
  if (!value) return undefined
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return undefined
  return d.toISOString().slice(0, 10)
}

const formatStatus = (status: string) => {
  if (!status) return '-'
  return status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

onMounted(() => {
  loadRFQs()
})
</script>

<style scoped>
:deep(.p-datatable .p-datatable-thead > tr > th) {
  padding: 0.75rem;
  background: #f8fafc;
  color: #475569;
  font-size: 0.75rem;
  font-weight: 600;
  border-color: #e2e8f0;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 0.75rem;
  border-color: #e2e8f0;
}
</style>
