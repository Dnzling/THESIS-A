<template>
  <div class="p-6 min-h-screen">
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-lg font-bold text-gray-800">Request for Quotations</h1>
      </div>
      <Button v-if="canManageRfq" label="Create RFQ" icon="pi pi-plus"
        @click="router.push({ name: 'procurement.rfqs.create' })" size="small" />
    </div>
  
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-bold  uppercase tracking-wide">Total RFQs</p>
              <p class="text-xl font-bold text-gray-900">{{ summary.total || 0 }}</p>
            </div>
            <i class="pi pi-briefcase text-4xl text-black-500"></i>
          </div>
        </template>
      </Card>
  
      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-bold  uppercase tracking-wide">Draft</p>
              <p class="text-xl font-bold text-black-600">{{ summary.draft || 0 }}</p>
            </div>
            <i class="pi pi-file text-4xl text-black-500"></i>
          </div>
        </template>
      </Card>
  
      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-bold  uppercase tracking-wide">Active</p>
              <p class="text-xl font-bold text-black">{{ summary.sent || 0 }}</p>
            </div>
            <i class="pi pi-send text-4xl text-black"></i>
          </div>
        </template>
      </Card>
  
      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-bold  uppercase tracking-wide">Approved</p>
              <p class="text-xl font-bold text-black-600">{{ summary.approved || 0 }}</p>
            </div>
            <i class="pi pi-check text-4xl text-black"></i>
          </div>
        </template>
      </Card>
    </div>
  
    <!-- DataTable -->
    <Card>
      <template #header>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end m-4 mt-6">
          <div class="flex flex-col gap-2">
            <label class="text-xs font-medium text-gray-600">Search</label>
            <InputText v-model="searchQuery" size="small" placeholder="Search RFQ number or title..." @keyup.enter="applyFilters" />
          </div>
          <div class="flex flex-col gap-2">
            <label class="text-xs font-medium text-gray-600">Status</label>
            <Select v-model="filterStatus" :options="statusOptions" optionLabel="label" optionValue="value"
              placeholder="All Statuses" showClear @change="applyFilters"  size="small"  />
          </div>
          <div class="flex flex-col gap-2">
            <label class="text-xs font-medium text-gray-600">Issue Date</label>
            <DatePicker v-model="dateRange" selectionMode="range" :manualInput="false"
              placeholder="Issue date range" dateFormat="M d, yy"  size="small"  showButtonBar @date-select="applyFilters" show-icon />
          </div>
          <div class="flex justify-end">
            <Button label="Clear Filters" severity="secondary"  size="small"  outlined @click="clearFilters" />
          </div>
        </div>
      </template>
      <template #content>
        <DataTable :value="rfqs" :loading="loading" class="p-datatable-sm" rowHover :expandedRows="expandedRows"
          responsiveLayout="scroll" paginator :rows="perPage" :totalRecords="total" :first="(currentPage - 1) * perPage"
          @page="onPageChange" :rowsPerPageOptions="[15, 25, 50]" @row-click="onRowClick" :rowClass="rowClass">
  
          <Column header="Date" style="width: 120px">
            <template #body="{ data }">
              <span class="text-sm text-gray-700">{{ formatDate(data.issue_date) }}</span>
            </template>
          </Column>
  
          <!-- RFQ Number -->
          <Column field="rfq_number" header="RFQ No." style="width: 120px">
            <template #body="{ data }">
              <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold">
                {{ data.rfq_number }}
              </span>
            </template>
          </Column>
  
          <!-- Title -->
          <Column field="title" header="Title" style="width: 250px">
            <template #body="{ data }">
              <div>
                <p class="font-semibold text-gray-900">{{ data.title }}</p>
                <p class="text-xs text-gray-600 mt-1">Type: <span class="font-medium">{{ capitalizeWords(data.rfq_type)
                    }}</span></p>
              </div>
            </template>
          </Column>
  
          <!-- Status -->
          <Column field="status" header="Status" style="width: 130px">
            <template #body="{ data }">
              <Badge :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
  
          <!-- Created By -->
          <Column field="created_by" header="Created By" style="width: 150px">
            <template #body="{ data }">
              <div v-if="data.created_by">
                <p class="font-medium text-gray-900 text-sm">{{ getPersonName(data.created_by) }}</p>
              </div>
            </template>
          </Column>
  
  
          <!-- Actions -->
          <Column header="Actions" style="width: 160px">
            <template #body="{ data }">
              <div class="flex gap-2 items-center justify-start">
                <Button icon="pi pi-eye" outlined rounded 
                  @click="router.push({ name: 'procurement.rfqs.detail', params: { id: data.id } })"
                  v-tooltip="'View Details'" />
              </div>
            </template>
          </Column>
  
          <!-- Expanded Row Detail -->
          <template #expansion="{ data }">
            <div class="p-6 bg-linear-to-r from-gray-50 to-gray-100 border-t">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- RFQ Info -->
                <div>
                  <h4 class="font-semibold text-gray-800 mb-3">📋 RFQ Information</h4>
                  <div class="space-y-2 text-sm">
                    <div>
                      <p class="text-gray-600">Issue Date</p>
                      <p class="font-medium text-gray-900">{{ formatDate(data.issue_date) }}</p>
                    </div>
                    <div>
                      <p class="text-gray-600">Created</p>
                      <p class="font-medium text-gray-900">{{ formatDateTime(data.created_at) }}</p>
                    </div>
                    <div v-if="data.description">
                      <p class="text-gray-600">Description</p>
                      <p class="font-medium text-gray-900 line-clamp-2">{{ data.description }}</p>
                    </div>
                  </div>
                </div>
  
                <!-- Terms Info -->
                <div>
                  <h4 class="font-semibold text-gray-800 mb-3">💰 Terms & Conditions</h4>
                  <div class="space-y-2 text-sm">
                    <div>
                      <p class="text-gray-600">Currency</p>
                      <p class="font-medium text-gray-900">{{ data.currency }}</p>
                    </div>
                    <div>
                      <p class="text-gray-600">Shipping Terms</p>
                      <p class="font-medium text-gray-900">{{ data.shipping_terms || 'Not specified' }}</p>
                    </div>
                    <div v-if="data.qualification_requirements">
                      <p class="text-gray-600">Qualifications</p>
                      <p class="font-medium text-gray-900 line-clamp-2">{{ data.qualification_requirements }}</p>
                    </div>
                  </div>
                </div>
  
                <!-- Assignment Info -->
                <div>
                  <h4 class="font-semibold text-gray-800 mb-3">👤 Assignment</h4>
                  <div class="space-y-2 text-sm">
                    <div v-if="data.awarded_to_supplier_id">
                      <p class="text-gray-600">Awarded To</p>
                      <p class="font-medium text-green-600">Supplier #{{ data.awarded_to_supplier_id }}</p>
                    </div>
                    <div v-if="data.awarded_at">
                      <p class="text-gray-600">Awarded Date</p>
                      <p class="font-medium text-gray-900">{{ formatDateTime(data.awarded_at) }}</p>
                    </div>
                    <div v-if="data.evaluation_notes">
                      <p class="text-gray-600">Notes</p>
                      <p class="font-medium text-gray-900 line-clamp-2">{{ data.evaluation_notes }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>
  
          <!-- Empty State -->
          <template #empty>
            <div class="text-center py-12">
              <i class="pi pi-inbox text-5xl text-gray-300 mb-4"></i>
              <p class="text-lg text-gray-600">No RFQs found</p>
              <p class="text-sm text-gray-500 mt-1">Create a new RFQ to get started</p>
            </div>
          </template>
  
          <!-- Loading -->
          <template #loadingicon>
            <i class="pi pi-spin pi-spinner"></i>
          </template>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'
import { useAuthStore } from '../../../../stores/auth'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const canManageRfq = computed(() => authStore.hasPermission('procurement.rfq.manage'))

const loading = ref(false)
const rfqs = ref<any[]>([])
const expandedRows = ref<any[]>([])
const currentPage = ref(1)
const perPage = ref(15)
const total = ref(0)
const filterStatus = ref<string | null>(null)
const searchQuery = ref('')
const dateRange = ref<Date[] | null>(null)

const summary = computed(() => {
  return {
    total: rfqs.value.length,
    draft: rfqs.value.filter(r => r.status === 'draft').length,
    sent: rfqs.value.filter(r => r.status === 'sent_to_supplier').length,
    approved: rfqs.value.filter(r => r.status === 'approved').length,
  }
})

const statusOptions = [
  { label: 'Draft', value: 'draft' },
  { label: 'Sent to Supplier', value: 'sent_to_supplier' },
  { label: 'Approved', value: 'approved' },
  { label: 'Partially Approved', value: 'partially_approved' },
  { label: 'Cancelled', value: 'cancelled' },
   { label: 'Rejected', value: 'rejected' },
]

const formatDate = (date: string | null): string => {
  if (!date) return 'N/A'
  const d = new Date(date)
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const rowClass = (data: any) => ({ 'cursor-pointer hover:bg-gray-50': true })

const onRowClick = (event: any) => {
  const id = event?.data?.id
  if (id) router.push({ name: 'procurement.rfqs.detail', params: { id } })
}

const getPersonName = (person: any): string => {
  const source = person?.user || person
  const name = [source?.fname, source?.lname].filter(Boolean).join(' ').trim()
  return name || source?.full_name || 'N/A'
}

const formatDateTime = (date: string | null): string => {
  if (!date) return 'N/A'
  const d = new Date(date)
  return d.toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const capitalizeWords = (str: string | null): string => {
  if (!str) return ''
  return str
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
    .join(' ')
}

const formatStatus = (status: string | null): string => {
  return status ? capitalizeWords(status) : 'Unknown'
}


const statusSeverity = (status: string): string => {
  const severityMap: Record<string, string> = {
    draft: 'secondary',
    partially_approved: 'warn',
    pending: 'info',
    approved: 'success',
    cancelled: 'danger',
  }
  return severityMap[status] || 'secondary'
}

const loadRFQs = async (page: number = 1) => {
  loading.value = true
  try {
    const params: any = {
      page,
      per_page: perPage.value,
    }

    if (filterStatus.value) {
      params.status = filterStatus.value
    }

    if (searchQuery.value) {
      params.search = searchQuery.value
    }

    if (dateRange.value?.[0]) {
      params.date_from = formatFilterDate(dateRange.value[0])
    }
    if (dateRange.value?.[1]) {
      params.date_to = formatFilterDate(dateRange.value[1])
    }

    const response = await procurementService.getRFQs(params)
    rfqs.value = response.data?.data || []
    total.value = response.data?.total || 0
    currentPage.value = page
  } catch (error) {
    console.error('Failed to load RFQs', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load RFQs',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const formatFilterDate = (date: Date): string => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const applyFilters = () => {
  currentPage.value = 1
  loadRFQs(1)
}

const clearFilters = () => {
  searchQuery.value = ''
  filterStatus.value = null
  dateRange.value = null
  applyFilters()
}

const onPageChange = (event: any) => {
  loadRFQs(event.page + 1)
}

onMounted(() => {
  loadRFQs()
})
</script>
