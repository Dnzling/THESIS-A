<template>
  <div class="p-6  min-h-screen">
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-lg font-bold text-gray-800">Purchase Requisitions</h1>
        <p class="text-xs text-gray-600 mt-1">Create, track and process PR requests for procurement</p>
      </div>
    </div>
  
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-bold  uppercase tracking-wide">Total PRs</p>
              <p class="text-2xl font-bold text-gray-900">{{ summary.total }}</p>
            </div>
            <i class="pi pi-file-export text-4xl"></i>
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-bold  uppercase tracking-wide">Draft</p>
              <p class="text-2xl font-bold text-gray-600">{{ summary.draft }}</p>
            </div>
            <i class="pi pi-file text-4xl"></i>
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-bold  uppercase tracking-wide">Pending</p>
              <p class="text-2xl font-bold">{{ summary.pending }}</p>
            </div>
            <i class="pi pi-send text-4xl "></i>
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-bold  uppercase tracking-wide  ">Approved</p>
              <p class="text-2xl font-bold">{{ summary.approved }}</p>
            </div>
            <i class="pi pi-check text-4xl"></i>
          </div>
        </template>
      </Card>
    </div>
  
  
    <Card>
      <template #header>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end m-4 mt-6">
          <div class="flex flex-col gap-2">
  
            <InputText v-model="searchQuery" placeholder="Search PR, requester, or branch..." @keyup.enter="applyFilters"
              size="small" />
          </div>
          <div class="flex flex-col gap-2">
            <Select v-model="filterType" :options="requisitionTypeOptions" optionLabel="label" optionValue="value"
              placeholder="All Types" showClear @change="applyFilters" size="small" />
          </div>
            <div class="flex flex-col gap-2">
            <Select v-model="filterStatus" :options="statusOptions" optionLabel="label" optionValue="value"
              placeholder="All Statuses" showClear @change="applyFilters" size="small" />
          </div>
          <div class="flex flex-col gap-2">
            <DatePicker v-model="dateRange" selectionMode="range" :manualInput="false" placeholder="Select date range"
              dateFormat="M d, yy" size="small" showButtonBar @date-select="applyFilters" show-icon />
          </div>
  
          <div class="flex justify-end">
            <Button label="Clear Filters" severity="secondary" outlined @click="clearFilters" size="small" />
          </div>
        </div>
      </template>
      <template #content>
        <DataTable :value="requisitions" :loading="loading" class="p-datatable-sm" rowHover :expandedRows="expandedRows"
          @update:expandedRows="expandedRows = $event" responsiveLayout="scroll" paginator :rows="perPage"     @row-click="onRowClick"
            :rowClass="rowClass"
          :totalRecords="total" :first="(currentPage - 1) * perPage" @page="onPageChange"
          :rowsPerPageOptions="[15, 25, 50] ">
          <!-- <Column :expander="true" style="width: 3rem" /> -->
          <Column header="Date" style="width: 120px">
            <template #body="{ data }">
              <span class="text-sm text-gray-700">{{ formatDate(data?.created_at) }}</span>
            </template>
          </Column>
          <Column field="pr_number" header="PR No." style="width: 140px">
            <template #body="{ data }">
              <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ">
                {{ data.pr_number }}
              </span>
            </template>
          </Column>
  
  
          <Column field="requisition_type" header="Type" style="width: 100px">
            <template #body="{ data }">
              <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold" :class="{
                    'bg-blue-100 text-blue-800': data.requisition_type === 'regular',
                    'bg-red-100 text-red-800': data.requisition_type === 'urgent',
                    'bg-purple-100 text-purple-800': data.requisition_type === 'emergency',
                    'bg-green-100 text-green-800': data.requisition_type === 'new_product',
                    'bg-yellow-100 text-yellow-800': data.requisition_type === 'seasonal',
                  }">{{ capitalizeWords(data?.requisition_type) }}</span>
            </template>
          </Column>
  
          <Column header="Branch" style="width: 120px">
            <template #body="{ data }">
              <div class="text-sm">
                <p class="font-medium text-gray-900">{{ data?.branch?.name || 'N/A' }}</p>
                <p class="text-xs text-gray-600">{{ data?.branch?.branch_code }}</p>
              </div>
            </template>
          </Column>
  
          <Column header="Requester" style="width: 140px">
            <template #body="{ data }">
              <div class="text-sm">
                <p class="font-medium text-gray-900">{{ getPersonName(data?.requested_by) }}</p>
              </div>
            </template>
          </Column>
  
          <Column field="status" header="Status" style="width: 150px">
            <template #body="{ data }">
              <Badge :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
  
          <Column header="Actions" style="width: 160px">
            <template #body="{ data }">
              <div class="flex gap-2 items-center justify-start">
                <Button icon="pi pi-eye" text rounded
                  @click="router.push({ name: 'procurement.purchase-requisitions.detail', params: { id: data.id } })"
                  v-tooltip="'View Details'" />
              </div>
            </template>
          </Column>
  
          <template #expansion="{ data }">
            <div class="p-6 bg-linear-to-r from-gray-50 to-gray-100 border-t">
              <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                  <h4 class="font-semibold text-gray-800 mb-3">📋 Request Info</h4>
                  <div class="space-y-2 text-sm">
                    <div>
                      <p class="text-gray-600">PR Number</p>
                      <p class="font-medium text-gray-900">{{ data?.pr_number }}</p>
                    </div>
                    <div>
                      <p class="text-gray-600">Type</p>
                      <p class="font-medium text-gray-900 capitalize">{{ capitalizeWords(data?.requisition_type) }}</p>
                    </div>
                    <div>
                      <p class="text-gray-600">Priority</p>
                      <p class="font-medium text-gray-900">{{ data?.priority ? `Level ${data.priority}` : 'N/A' }}</p>
                    </div>
                  </div>
                </div>
                <div>
                  <h4 class="font-semibold text-gray-800 mb-3">🏢 Organization</h4>
                  <div class="space-y-2 text-sm">
                    <div>
                      <p class="text-gray-600">Branch</p>
                      <p class="font-medium text-gray-900">{{ data?.branch?.name || 'N/A' }}</p>
                    </div>
                    <div>
                      <p class="text-gray-600">Requested By</p>
                      <p class="font-medium text-gray-900">{{ getPersonName(data?.requested_by) }}
                      </p>
                    </div>
                    <div>
                      <p class="text-gray-600">Department</p>
                      <p class="font-medium text-gray-900">{{ data?.requested_by?.department || 'N/A' }}</p>
                    </div>
                  </div>
                </div>
                <div>
                  <h4 class="font-semibold text-gray-800 mb-3">💰 Financials</h4>
                  <div class="space-y-2 text-sm">
                    <div>
                      <p class="text-gray-600">Estimated Amount</p>
                      <p class="font-bold text-orange-600 text-lg">₱ {{ parseFloat(data?.estimated_amount ||
                        0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
                    </div>
                    <div>
                      <p class="text-gray-600">Procurement Route</p>
                      <p class="font-medium text-gray-900 capitalize">{{ data?.procurement_route || 'N/A' }}</p>
                    </div>
                  </div>
                </div>
                <div>
                  <h4 class="font-semibold text-gray-800 mb-3">✅ Approvals & Dates</h4>
                  <div class="space-y-2 text-sm">
                    <div>
                      <p class="text-gray-600">Required Approvals</p>
                      <div class="mt-1 space-y-1">
                        <p v-for="approval in (data?.required_approvals || [])" :key="approval" class="text-gray-900">• {{
                          capitalizeWords(approval) }}</p>
                        <p v-if="!data?.required_approvals?.length" class="text-gray-600">None required</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="mt-4 p-3 bg-white rounded border border-gray-200">
                <p class="text-xs font-semibold text-gray-600 mb-1 uppercase">Reason</p>
                <p class="text-gray-900">{{ data?.reason || 'No reason provided' }}</p>
              </div>
            </div>
          </template>
  
          <template #empty>
            <div class="text-center py-12">
              <i class="pi pi-inbox text-5xl text-gray-300 mb-4"></i>
              <p class="text-lg text-gray-600">No purchase requisitions found</p>
            </div>
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

const router = useRouter()
const toast = useToast()
const loading = ref(false)
const requisitions = ref<any[]>([])
const expandedRows = ref<any[]>([])
const currentPage = ref(1)
const perPage = ref(15)
const total = ref(0)
const filterStatus = ref<string | null>(null)
const filterType = ref<string | null>(null)
const searchQuery = ref('')
const dateRange = ref<Date[] | null>(null)

const statusOptions = [
  { label: 'Draft', value: 'draft' },
  { label: 'Pending', value: 'pending' },
  { label: 'Warehouse Approved', value: 'warehouse_approved' },
  { label: 'Branch Manager Approved', value: 'branch_manager_approved' },
  { label: 'Pending Central Review', value: 'pending_central_review' },
  { label: 'Procurement Processing', value: 'procurement_processing' },
  { label: 'RFQ Sent', value: 'rfq_sent' },
  { label: 'Quotes Received', value: 'quotes_received' },
  { label: 'Supplier Selected', value: 'supplier_selected' },
  { label: 'PO Created', value: 'po_created' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Cancelled', value: 'cancelled' },
]

const requisitionTypeOptions = [
  { label: 'Regular', value: 'regular' },
  { label: 'Urgent', value: 'urgent' },
  { label: 'New Product', value: 'new_product' },
  { label: 'Seasonal', value: 'seasonal' },
  { label: 'Emergency', value: 'emergency' },
]

const approvedStatuses = ['warehouse_approved', 'branch_manager_approved', 'procurement_processing']

const summary = computed(() => ({
  total: total.value,
  draft: requisitions.value.filter(r => r.status === 'draft').length,
  pending: requisitions.value.filter(r => r.status === 'pending').length,
  approved: requisitions.value.filter(r => approvedStatuses.includes(r.status)).length,
}))

const capitalizeWords = (str: string): string => {
  if (!str) return ''
  return str.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

const statusSeverity = (status: string): string => {
  const severityMap: Record<string, string> = {
    draft: 'secondary',
    pending: 'info',
    warehouse_approved: 'success',
    branch_manager_approved: 'success',
    pending_central_review: 'warning',
    procurement_processing: 'info',
    rfq_sent: 'info',
    delivered: 'success',
    quotes_received: 'warning',
    supplier_selected: 'contrast',
    po_created: 'success',
    rejected: 'danger',
    cancelled: 'danger',
  }
  return severityMap[status] || 'secondary'
}

const formatStatus = (status: string): string => {
  if (!status) return '-'
  return status.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

const rowClass = (data: any) => ({ 'cursor-pointer hover:bg-gray-50': true })

const onRowClick = (event: any) => {
  const id = event?.data?.id
  if (id) router.push({ name: 'procurement.purchase-requisitions.detail', params: { id } })
}

const formatFilterDate = (date: Date): string => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const getPersonName = (person: any): string => {
  const source = person?.user || person
  const name = [source?.fname, source?.lname].filter(Boolean).join(' ').trim()
  return name || source?.full_name || 'N/A'
}

const formatDate = (value: string | null | undefined): string => {
  if (!value) return '-'
  const d = new Date(value)
  return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

const loadRequisitions = async (page: number = 1) => {
  loading.value = true
  try {
    const params: any = {
      page,
      per_page: perPage.value,
    }
    if (filterStatus.value) params.status = filterStatus.value
    if (filterType.value) params.requisition_type = filterType.value
    if (searchQuery.value.trim()) params.search = searchQuery.value.trim()
    if (dateRange.value?.[0]) params.date_from = formatFilterDate(dateRange.value[0])
    if (dateRange.value?.[1]) params.date_to = formatFilterDate(dateRange.value[1])

    const response = await procurementService.getPurchaseRequisitions(params)
    requisitions.value = response.data?.data || []
    total.value = response.data?.total || 0
    currentPage.value = page
  } catch (error) {
    console.error('Failed to load requisitions', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load PRs',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  currentPage.value = 1
  loadRequisitions(1)
}

const clearFilters = () => {
  searchQuery.value = ''
  filterStatus.value = null
  filterType.value = null
  dateRange.value = null
  applyFilters()
}

const onPageChange = (event: any) => {
  loadRequisitions(event.page + 1)
}

onMounted(() => {
  loadRequisitions()
})
</script>
