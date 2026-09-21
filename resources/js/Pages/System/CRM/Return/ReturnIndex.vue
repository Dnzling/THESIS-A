<template>
  <div class="min-h-screen p-4 max-w-7xl mx-auto space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">Returns</h1>
        <p class="text-sm text-gray-500">Review and track customer return requests.</p>
      </div>
      <Button icon="pi pi-refresh" label="Refresh" outlined size="small" @click="loadReturns" />
    </div>

    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <div class="grid grid-cols-1 gap-3 items-end md:grid-cols-2 xl:grid-cols-6">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText
              v-model="filters.search"
              placeholder="Search return ID, order #, customer, product"
              class="w-full"
              size="small"
            />
          </IconField>

          <Select
            v-model="filters.workflow"
            :options="workflowOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Workflow"
            class="w-full"
            size="small"
            showClear
          />

            <Select
            v-model="filters.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Status"
            class="w-full"
            size="small"
            showClear
          />


          <Select
            v-model="filters.investigation_status"
            :options="investigationStatusOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Investigation"
            class="w-full"
            size="small"
            showClear
          />

        
          <DatePicker
            v-model="filters.date_range"
            selectionMode="range"
            placeholder="Date range"
            :maxDate="new Date()"
            class="w-full"
            size="small"
            showIcon
          />

          <div class="flex justify-end">
            <Button v-if="hasActiveFilters" icon="pi pi-filter-slash" label="Reset" outlined size="small" @click="resetFilters" />
          </div>
        </div>
      </template>
    </Card>

    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <div v-if="loading" class="space-y-3 py-2" aria-label="Loading return requests">
          <div v-for="row in filters.per_page > 15 ? 8 : 6" :key="row" class="grid grid-cols-6 gap-4 border-b border-gray-100 py-4">
            <Skeleton height="1rem" />
            <Skeleton height="1rem" />
            <Skeleton height="1rem" />
            <Skeleton height="1rem" />
            <Skeleton height="1.5rem" class="rounded-full" />
            <Skeleton height="1.5rem" class="rounded-lg" />
          </div>
        </div>
        <DataTable
          v-else
          :value="rows"
          dataKey="id"
          stripedRows
          paginator
          lazy
          :rows="filters.per_page"
          :first="(filters.page - 1) * filters.per_page"
          :totalRecords="totalRecords"
          :rowsPerPageOptions="[15, 20, 50]"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageSelect"
          class="p-datatable-sm"
          @page="onPage"
          @sort="onSort"
          :sortField="sortField"
          :sortOrder="sortOrder"
        >
          <template #empty>
            <div class="text-center py-10">
              <i class="pi pi-inbox text-4xl text-gray-400"></i>
              <p class="text-gray-600 mt-2">No return requests found</p>
            </div>
          </template>

             <Column field="created_at" header="Date" class="text-sm" sortable style="width: 20%">
            <template #body="{ data }">
              {{ formatDate(data.created_at) }}
            </template>
          </Column>

          <Column header="Return ID" field="return_number" class="text-xs" style="width: 28%">
            <template #body="{ data }">
              <span class="font-semibold text-gray-900">{{ data.return_number || '—' }}</span>
            </template>
          </Column>


       

          <Column header="Customer" style="width: 18%">
            <template #body="{ data }">
              <div class="text-sm">
                <div class="font-medium text-gray-900">{{ data.user?.full_name || data.user?.email || 'N/A' }}</div>
                <div class="text-xs text-gray-500">{{ data.user?.email || '' }}</div>
              </div>
            </template>
          </Column>

          <Column header="Product" style="width: 22%">
            <template #body="{ data }">
              <div class="text-sm">
                <div class="font-medium text-gray-900">
                  {{ data.order_item?.product?.product_name || data.order_item?.product_name || 'N/A' }}
                </div>
                <div class="text-xs text-gray-500">
                  SKU: {{ data.order_item?.product?.sku || data.order_item?.sku || 'N/A' }}
                </div>
              </div>
            </template>
          </Column>

          <Column header="Qty" style="width: 8%">
            <template #body="{ data }">
              <span class="font-medium">{{ data.requested_quantity ?? 1 }}</span>
            </template>
          </Column>

          <Column header="Workflow Status" style="min-width: 13rem">
            <template #body="{ data }">
              <Badge
                :value="workflowStatus(data).label"
                :severity="workflowStatus(data).severity"
              />
            </template>
          </Column>

          <Column header="Assigned Team" style="min-width: 13rem">
            <template #body="{ data }">
              <div v-if="data.investigation_ticket?.assignees?.length" class="space-y-1">
                <p v-for="employee in data.investigation_ticket.assignees" :key="employee.id"
                  class="text-sm text-slate-700">
                  {{ investigatorName(employee) }}
                </p>
              </div>
              <span v-else class="text-sm text-slate-400">Not assigned</span>
            </template>
          </Column>

          <Column header="Actions" style="width: 10%">
            <template #body="{ data }">
              <Button
                icon="pi pi-eye"
                size="small"
                text
                @click="router.push(`/crm/returns/${data.id}`)"
              />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="attachmentsDialogVisible" header="Attachments" modal class="w-full max-w-5xl">
      <div v-if="attachments.length" class="space-y-3">
        <Galleria
          :value="attachments"
          :numVisible="6"
          :circular="true"
          :showItemNavigators="true"
          :showThumbnails="true"
          containerStyle="max-width: 100%"
        >
          <template #item="{ item }">
            <div class="flex justify-center bg-black/5 rounded-lg overflow-hidden">
              <img :src="item.url" :alt="item.name" class="max-h-[520px] w-auto object-contain" />
            </div>
          </template>
          <template #thumbnail="{ item }">
            <img :src="item.url" :alt="item.name" class="h-14 w-14 object-cover rounded-md" />
          </template>
        </Galleria>
      </div>
      <div v-else class="py-10 text-center text-sm text-gray-600">No attachments.</div>
      <template #footer>
        <Button label="Close" outlined size="small" @click="attachmentsDialogVisible = false" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import Skeleton from 'primevue/skeleton'
import { useRouter } from 'vue-router'
import crmService from '@/services/crm.service'

const router = useRouter()
const toast = useToast()

const loading = ref(false)
const rows = ref<any[]>([])
const totalRecords = ref(0)
const suppressFilterWatchers = ref(false)

const sortField = ref('created_at')
const sortOrder = ref(-1)
let latestLoadId = 0

const filters = reactive({
  search: '',
  workflow: null as null | string,
  investigation_status: null as null | string,
  status: null as null | string,
  date_range: null as any,
  page: 1,
  per_page: 15,
})

const hasActiveFilters = computed(() => Boolean(
  filters.search.trim()
  || filters.workflow
  || filters.investigation_status
  || filters.status
  || (Array.isArray(filters.date_range) && (filters.date_range[0] || filters.date_range[1]))
))

const statusOptions = computed(() => ([
  { label: 'Pending Verification', value: 'pending_verification' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Received', value: 'received' },
  { label: 'Refund Pending', value: 'refund_pending' },
  { label: 'Refunded', value: 'refunded' },
  { label: 'Replaced', value: 'replaced' },
]))

const workflowOptions = [
  { label: 'Assigned to Me', value: 'assigned_to_me' },
  { label: 'Needs Investigation', value: 'needs_investigation' },
  { label: 'Awaiting Manager Decision', value: 'awaiting_manager_decision' },
  { label: 'Approved / Awaiting Pickup', value: 'approved' },
  { label: 'Awaiting Physical Inspection', value: 'awaiting_inspection' },
  { label: 'Awaiting Finance Refund', value: 'awaiting_refund' },
  { label: 'Completed', value: 'completed' },
]

const investigationStatusOptions = [
  { label: 'Not Assigned', value: 'unassigned' },
  { label: 'Open', value: 'open' },
  { label: 'In Progress', value: 'in_progress' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' },
]

const toIsoDate = (date: Date) => {
  const d = new Date(date)
  const yyyy = d.getFullYear()
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const dd = String(d.getDate()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd}`
}

const buildParams = () => {
  const params: any = {
    search: filters.search.trim() || undefined,
    workflow: filters.workflow || undefined,
    investigation_status: filters.investigation_status || undefined,
    status: filters.status || undefined,
    page: filters.page,
    per_page: filters.per_page,
    sort_by: sortField.value,
    sort_order: sortOrder.value === 1 ? 'asc' : 'desc',
  }

  const range = filters.date_range as any
  if (Array.isArray(range) && range[0] && range[1]) {
    params.start_date = toIsoDate(range[0])
    params.end_date = toIsoDate(range[1])
  }

  return params
}

const loadReturns = async () => {
  const loadId = ++latestLoadId
  loading.value = true
  try {
    const res = await crmService.getReturns(buildParams())
    if (loadId !== latestLoadId) return
    rows.value = res?.data || []
    totalRecords.value = res?.meta?.total || 0
  } catch (error: any) {
    if (loadId !== latestLoadId) return
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to load returns',
      life: 3000,
    })
  } finally {
    if (loadId === latestLoadId) loading.value = false
  }
}

let searchDebounce: ReturnType<typeof setTimeout> | undefined

const runFilteredSearch = () => {
  filters.page = 1
  loadReturns()
}

const resetFilters = () => {
  if (searchDebounce) clearTimeout(searchDebounce)
  suppressFilterWatchers.value = true
  filters.search = ''
  filters.workflow = null
  filters.investigation_status = null
  filters.status = null
  filters.date_range = null
  filters.page = 1
  filters.per_page = 15
  sortField.value = 'created_at'
  sortOrder.value = -1
  nextTick(() => {
    suppressFilterWatchers.value = false
    loadReturns()
  })
}

const onPage = (event: any) => {
  filters.page = (event.page || 0) + 1
  filters.per_page = event.rows || filters.per_page
  loadReturns()
}

const onSort = (event: any) => {
  sortField.value = event.sortField || 'created_at'
  sortOrder.value = event.sortOrder ?? -1
  loadReturns()
}

const formatDate = (value: any) => {
  if (!value) return 'â€”'
  const date = new Date(value)
  return new Intl.DateTimeFormat('en-PH', { year: 'numeric', month: 'short', day: '2-digit' }).format(date)
}

const prettyStatus = (value: any) => {
  const raw = String(value || '')
  if (raw === 'pending_verification') return 'Return Pending'
  const v = raw.replace(/_/g, ' ')
  return v ? v.charAt(0).toUpperCase() + v.slice(1) : 'â€”'
}

const workflowStatus = (row: any): { label: string; severity: string } => {
  const returnStatus = String(row?.status || '').toLowerCase()
  const investigationStatus = String(row?.investigation_ticket?.status || '').toLowerCase()
  const pickupStatus = String(row?.pickup?.status || '').toLowerCase()

  if (returnStatus === 'rejected') return { label: 'Rejected', severity: 'danger' }
  if (returnStatus === 'refunded') return { label: 'Refunded', severity: 'success' }
  if (returnStatus === 'replaced') return { label: 'Replacement Completed', severity: 'success' }
  if (returnStatus === 'refund_pending') return { label: 'Awaiting Finance Refund', severity: 'warn' }
  if (returnStatus === 'received') return { label: 'Physical Inspection Complete', severity: 'info' }

  if (returnStatus === 'approved') {
    return pickupStatus === 'picked_up'
      ? { label: 'Awaiting Physical Inspection', severity: 'warn' }
      : { label: 'Awaiting Pickup', severity: 'info' }
  }

  if (returnStatus === 'pending_verification') {
    if (!row?.investigation_ticket) return { label: 'Needs Investigation', severity: 'warn' }
    if (investigationStatus === 'completed') return { label: 'Awaiting Manager Decision', severity: 'warn' }
    if (investigationStatus === 'in_progress') return { label: 'Under Investigation', severity: 'info' }
    if (investigationStatus === 'cancelled') return { label: 'Investigation Cancelled', severity: 'danger' }
    return { label: 'Investigation Open', severity: 'info' }
  }

  return { label: prettyStatus(returnStatus), severity: 'secondary' }
}

const investigatorName = (employee: any) =>
  [employee?.user?.fname, employee?.user?.lname].filter(Boolean).join(' ')
  || employee?.user?.email
  || employee?.employee_number
  || 'Assigned employee'

const attachmentsDialogVisible = ref(false)
const attachments = ref<{ url: string; name: string }[]>([])

const openAttachments = (row: any) => {
  const urls: string[] = Array.isArray(row?.evidence_urls) ? row.evidence_urls : []
  attachments.value = urls.map((url, idx) => ({
    url,
    name: `Attachment ${idx + 1}`,
  }))
  attachmentsDialogVisible.value = true
}

watch(
  () => filters.search,
  () => {
    if (suppressFilterWatchers.value) return
    if (searchDebounce) clearTimeout(searchDebounce)
    searchDebounce = setTimeout(runFilteredSearch, 350)
  },
)

watch(() => filters.status, () => {
  if (!suppressFilterWatchers.value) runFilteredSearch()
})

watch([() => filters.workflow, () => filters.investigation_status], () => {
  if (!suppressFilterWatchers.value) runFilteredSearch()
})

watch(
  () => filters.date_range,
  (range) => {
    if (suppressFilterWatchers.value) return
    if (!Array.isArray(range) || (!range[0] && !range[1]) || (range[0] && range[1])) {
      runFilteredSearch()
    }
  },
  { deep: true },
)

onMounted(loadReturns)
onUnmounted(() => {
  if (searchDebounce) clearTimeout(searchDebounce)
})
</script>
