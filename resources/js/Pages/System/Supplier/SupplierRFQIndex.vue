<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Request for Quotations</h1>
      </div>
    </div>
  
    <!-- Filters -->
    <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <template #header>
        <div class="px-6 pt-6 flex items-center gap-2">
  
          <h2 class="text-lg font-semibold text-gray-900">Filters</h2>
        </div>
      </template>
      <template #content>
        <div class="p-6 pt-2 grid grid-cols-1 md:grid-cols-5 gap-4">
          <div class="md:col-span-2 space-y-2">
            <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Search</label>
            <div class="relative">
              <InputText v-model="searchQuery" size="small" fluid placeholder="Search RFQ number" class="w-full pl-9"
                @input="onSearch" />
            </div>
          </div>
  
          <div class="space-y-2">
            <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Status</label>
            <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" size="small" fluid
              optionValue="value" placeholder="All statuses" @change="onFilterChange" />
          </div>
  
          <div class="space-y-2">
            <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Date Range</label>
            <DatePicker v-model="dateRange" selectionMode="range" size="small" fluid dateFormat="yy-mm-dd" showIcon @update:modelValue="onFilterChange" />
          </div>
  
          <div class="flex items-end">
            <Button label="Reset" icon="pi pi-times" size="small" fluid outlined rounded  @click="resetFilters" />
          </div>
        </div>
      </template>
    </Card>
  
    <!-- Table -->
    <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <template #header>
        <div class="px-6 pt-6 flex items-center gap-2">

        </div>
      </template>
      <template #content>
        <div class="p-6 pt-2">
          <div v-if="loading" class="space-y-3">
            <Skeleton v-for="i in 5" :key="i" height="64px" class="rounded-xl" />
          </div>
          <DataTable
            v-else
            :value="rfqs"
            :paginator="true"
            :rows="rows"
            :first="first"
            :totalRecords="totalRecords"
            :lazy="true"
            dataKey="id"
            stripedRows
            sortMode="single"
            :sortField="sortField"
            :sortOrder="sortOrder"
            @sort="onSort"
            @page="onPageChange"
            class="p-datatable-sm"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            :rowsPerPageOptions="[5,10,20,50]"
          >
            <template #empty>
              <div class="text-center text-sm text-gray-500 py-6">No RFQs found.</div>
            </template>
  
            <Column header="Date" field="created_at" sortable style="min-width: 140px">
              <template #body="{ data }">
                <span class="text-sm text-gray-700">{{ formatDate(data.created_at) }}</span>
              </template>
            </Column>

            <Column header="RFQ" field="rfq_number" style="min-width: 200px">
              <template #body="{ data }">
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center">
                    <i class="pi pi-file text-blue-600 text-xs"></i>
                  </div>
                  <div>
                    <div class="font-semibold text-gray-900 hover:text-blue-600 cursor-pointer"
                      @click="viewDetail(data.id)">
                      #{{ data.rfq_number }}
                    </div>
                    <div class="text-xs text-gray-500 mt-0.5 flex flex-col gap-0.5">
                      <span>{{ data.items?.length || 0 }} items</span>
                      <span class="text-[11px] text-gray-400">
                        Store:
                        {{ data.store?.store_name || data.store?.name || data.store_name || data.store?.store_code || 'N/A' }}
                      </span>
                    </div>
                  </div>
                </div>
              </template>
            </Column>
  
            <Column header="Items" style="width: 80px">
              <template #body="{ data }">
                <Tag :value="data.items?.length || 0" severity="info" class="rounded-full text-xs px-3 py-1" />
              </template>
            </Column>
  
            <Column header="Status" field="status" style="min-width: 140px">
              <template #body="{ data }">
                <Tag :value="formatStatus(data.status)" :severity="getStatusSeverity(data.status)"
                  class="rounded-full text-xs px-3 py-1 font-medium" />
              </template>
            </Column>
  
            <Column header="Action" style="width: 110px">
              <template #body="{ data }">
                <Button label="View" icon="pi pi-arrow-right" text @click.stop="viewDetail(data.id)" />
              </template>
            </Column>
          </DataTable>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Skeleton from 'primevue/skeleton'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import supplierService from '../../../services/supplier.service'

const router = useRouter()
const toast = useToast()
const loading = ref(false)
const rfqs = ref([])
const searchQuery = ref('')
const statusFilter = ref('')
const dateRange = ref<any | null>(null)
const rows = ref(10)
const first = ref(0)
const totalRecords = ref(0)
const sortField = ref('created_at')
const sortOrder = ref(-1)

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
    pending: 'info',
    receiving: 'warning',
    partially_approved: 'warning',
    awarded: 'success',
    approved: 'success',
    rejected: 'danger',
    cancelled: 'danger',
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
  loadRFQs()
}

const viewDetail = (id: number) => {
  router.push(`/supplier-portal/rfqs/${id}`)
}

const onFilterChange = () => {
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

<style scoped lang="scss">
.supplier-rfq-index {
  padding: 24px;
  background: #f8f9fc;
  min-height: 100vh;

  @media (max-width: 768px) {
    padding: 16px;
  }
}

.ios-card {
  background: white;
  border-radius: 20px;
  border: none;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.03);
  overflow: hidden;
  transition: all 0.2s ease;

  &:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  }
}

.filter-container {
  display: flex;
  gap: 16px;
  align-items: flex-end;

  @media (max-width: 768px) {
    flex-direction: column;
    gap: 20px;
  }
}

.search-field {
  flex: 1;
}

.ios-label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: #1c1c1e;
  margin-bottom: 8px;
  letter-spacing: -0.2px;
}

.ios-input {
  width: 100%;
  background: #f9f9fb;
  border: 1px solid #e5e5ea;
  border-radius: 12px;
  padding: 12px 16px;
  font-size: 15px;
  color: #1c1c1e;
  transition: all 0.2s ease;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;

  &:focus {
    outline: none;
    border-color: #007aff;
    background: white;
    box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
  }

  &::placeholder {
    color: #8e8e93;
    font-weight: 400;
  }
}

.ios-button-secondary {
  background: #f9f9fb !important;
  border: 1px solid #e5e5ea !important;
  color: #007aff !important;
  padding: 12px 20px !important;
  border-radius: 12px !important;
  font-weight: 500 !important;
  font-size: 15px !important;
  transition: all 0.2s ease !important;
  min-width: 100px;

  &:hover {
    background: #f2f2f6 !important;
    border-color: #d9d9df !important;
  }

  &:active {
    transform: scale(0.98);
  }
}

.ios-button-text {
  color: #007aff !important;
  font-weight: 500 !important;
  font-size: 15px !important;
  padding: 8px 12px !important;
  border-radius: 8px !important;
  transition: all 0.2s ease !important;

  &:hover {
    background: rgba(0, 122, 255, 0.08) !important;
  }

  &:active {
    transform: scale(0.96);
  }
}

.ios-table {
  :deep(.ios-table-root) {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  }

  :deep(.p-datatable-thead) {
    tr {
      background: #f9f9fb;
    }

    th {
      background: transparent;
      color: #6c6c70;
      font-weight: 500;
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 16px 12px;
      border-bottom: 1px solid #e5e5ea;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }
  }

  :deep(.p-datatable-tbody) {
    tr {
      cursor: pointer;
      transition: background 0.2s ease;

      &:hover {
        background: #f9f9fb;
      }

      td {
        padding: 16px 12px;
        border-bottom: 1px solid #f0f0f2;
        font-size: 15px;
        color: #1c1c1e;
        vertical-align: middle;
      }
    }
  }
}

.rfq-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.rfq-number {
  font-weight: 600;
  color: #1c1c1e;
  font-size: 15px;
}

.rfq-date {
  font-size: 13px;
  color: #8e8e93;
}

.items-count {
  font-weight: 500;
  color: #1c1c1e;
}

.deadline-date {
  color: #1c1c1e;
}

.ios-tag {
  border-radius: 12px !important;
  padding: 4px 12px !important;
  font-size: 13px !important;
  font-weight: 500 !important;

  :deep(.p-tag-value) {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  }

  &.p-tag-info {
    background: #e8f0fe !important;
    color: #0066cc !important;
  }

  &.p-tag-success {
    background: #e8f5e9 !important;
    color: #2e7d32 !important;
  }

  &.p-tag-warning {
    background: #fff4e5 !important;
    color: #ed6c02 !important;
  }

  &.p-tag-danger {
    background: #feeceb !important;
    color: #d32f2f !important;
  }

  &.p-tag-secondary {
    background: #e9ecef !important;
    color: #6c757d !important;
  }
}

.ios-paginator {
  :deep(.p-paginator) {
    background: transparent;
    border: none;
    padding: 20px 16px;
    justify-content: center;

    .p-paginator-page,
    .p-paginator-next,
    .p-paginator-last,
    .p-paginator-first,
    .p-paginator-prev {
      min-width: 36px;
      height: 36px;
      border-radius: 8px;
      color: #007aff;
      font-weight: 500;
      transition: all 0.2s ease;

      &:hover {
        background: #f2f2f6;
      }

      &.p-highlight {
        background: #007aff;
        color: white;
      }
    }
  }
}

.rfq-date {
  font-size: 12px;
  color: #6b7280;
}
</style>
