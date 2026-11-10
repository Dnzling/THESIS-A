<template>
  <div class="mx-auto space-y-5 px-4 py-6 sm:px-6 lg:px-8">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-900">Stock Requests</h1>
        <p class="mt-1 text-sm text-slate-500">Manage supplier replenishment and warehouse transfer requests in one place.</p>
      </div>
    </header>

    <Card>
      <template #content>
        <div v-if="sourceLoading" class="mb-4 flex items-center gap-3 border-b border-slate-200 py-3">
          <Skeleton width="10rem" height="1.5rem" />
          <Skeleton width="10rem" height="1.5rem" />
        </div>
        <div v-else-if="canViewPurchase && canViewTransfer" class="mb-4 flex flex-wrap gap-2 border-b border-slate-200">
          <button type="button" class="border-b-2 px-3 py-2 text-sm font-medium transition"
            :class="activeType === 'transfer' ? 'border-orange-500 text-orange-700' : 'border-transparent text-slate-500 hover:text-slate-800'"
            @click="setType('transfer')">Request from Warehouse</button>
          <button type="button" class="border-b-2 px-3 py-2 text-sm font-medium transition"
            :class="activeType === 'purchase' ? 'border-orange-500 text-orange-700' : 'border-transparent text-slate-500 hover:text-slate-800'"
            @click="setType('purchase')">Request from Supplier</button>
         
          <p class="ml-auto self-center pb-2 text-xs text-slate-500">{{ activeType === 'purchase' ? 'Approved requests follow the available supplier workflow' : 'Sent to Warehouse' }}</p>
        </div>
        <div v-else-if="canViewPurchase" class="mb-4 border-b border-slate-200 pb-3">
          <p class="text-sm font-medium text-slate-800">Request from Supplier</p>
          <p class="mt-0.5 text-xs text-slate-500">Submit a supplier replenishment request for approval.</p>
        </div>

        <div v-if="!sourceLoading && !canViewPurchase && !canViewTransfer" class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-6 text-center text-sm text-amber-900">
          You do not have permission to view supplier purchase requests or warehouse transfer requests.
        </div>

        <div v-else-if="!sourceLoading" class="mb-4 flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-end">
          <div class="w-full lg:max-w-xs">
            <label class="mb-1 block text-xs font-medium text-slate-600">Search</label>
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" fluid size="small" :placeholder="activeType === 'purchase' ? 'PR number, requester, or reason' : 'Reference, reason, or branch'"
                @keyup.enter="loadRequests(1)" />
            </IconField>
          </div>
          <div class="w-full lg:w-48">
            <label class="mb-1 block text-xs font-medium text-slate-600">Status</label>
            <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value"
              placeholder="All statuses" showClear fluid size="small" @change="loadRequests(1)" />
          </div>
          <div class="w-full lg:w-64">
            <label class="mb-1 block text-xs font-medium text-slate-600">Date range</label>
            <DatePicker v-model="dateRange" selectionMode="range" dateFormat="M d, yy" :manualInput="false"
              showIcon fluid size="small" placeholder="Select dates" />
          </div>
          <Button v-if="hasActiveFilters" label="Clear Filter" icon="pi pi-filter-slash" severity="danger" outlined size="small" @click="clearFilters" />
          <Button v-if="canCreateActiveRequest" :label="activeType === 'purchase' ? 'Create Supplier Request' : 'Create Stock Transfer'"
            icon="pi pi-plus" severity="warn" size="small" class="self-end lg:ml-auto"
            @click="createActiveRequest" />
        </div>

        <div v-if="sourceLoading || canViewPurchase || canViewTransfer" class="space-y-3">
        <div v-if="sourceLoading || loading" class="space-y-3">
          <Skeleton v-for="row in 7" :key="row" height="2.5rem" />
        </div>
        <DataTable v-else :value="rows" paginator lazy rowHover dataKey="id" class="p-datatable-sm text-sm"
          :rows="perPage" :first="(page - 1) * perPage" :totalRecords="total" :sortField="sortField"
          :sortOrder="sortOrder" :rowsPerPageOptions="[10, 15, 25, 50]" @page="onPage" @sort="onSort">
          <template #empty>
            <div class="py-10 text-center">
              <i class="pi pi-inbox mb-2 text-3xl text-slate-300" />
              <p class="text-sm font-medium text-slate-700">No {{ activeType === 'purchase' ? 'purchase orders' : 'warehouse requests' }} found</p>
              <p class="mt-1 text-xs text-slate-500">Try changing the filters or create a new stock request.</p>
            </div>
          </template>
          <Column :field="dateSortField" header="Date" sortable style="min-width: 150px">
            <template #body="{ data }">
              <div class="text-xs font-medium text-slate-800">{{ formatDate(data.date) }}</div>
              <div class="mt-0.5 text-[11px] text-slate-500">{{ formatTime(data.date) }}</div>
            </template>
          </Column>
          <Column field="reference" :header="activeType === 'purchase' ? 'PR Number' : 'Reference'" style="min-width: 190px">
            <template #body="{ data }"><span class="font-semibold text-slate-900 text-xs">{{ data.reference }}</span></template>
          </Column>
          <Column header="Request" style="min-width: 180px">
            <template #body="{ data }">
              <span class="block text-xs font-medium text-slate-800">{{ data.requestLabel }}</span>
              <span class="mt-0.5 block text-xs text-slate-500">{{ activeType === 'purchase' ? data.branchLabel : data.routeLabel }}</span>
            </template>
          </Column>
          <Column header="Requested By" style="min-width: 150px">
            <template #body="{ data }"><span class="text-slate-700">{{ data.requestedBy || '—' }}</span></template>
          </Column>
          <Column field="itemCount" header="Items" sortable style="width: 90px">
            <template #body="{ data }">{{ data.itemCount }}</template>
          </Column>
          <Column field="amount" header="Estimated Amount" sortable style="min-width: 150px">
            <template #body="{ data }"><span class="font-medium text-slate-800">{{ formatCurrency(data.amount) }}</span></template>
          </Column>
          <Column field="status" header="Status" style="min-width: 150px">
            <template #body="{ data }"><Badge :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" /></template>
          </Column>
          <Column style="width: 105px">
            <template #body="{ data }">
              <Button label="View" icon="pi pi-eye" size="small" outlined rounded
                @click="viewRequest(data.id)" />
            </template>
          </Column>
        </DataTable>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import inventoryService from '@/services/inventory.service'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const authStore = useAuthStore()
const sourceLoading = ref(true)
const hasWarehouseBranch = ref(false)
const canViewStockRequests = computed(() => authStore.hasPermission('inventory.stock-requests.view'))
const canViewPurchase = computed(() => canViewStockRequests.value || authStore.hasPermission('inventory.requisites.view') || authStore.hasPermission('inventory.requisites.manage') || authStore.hasPermission('inventory.requisitions.view') || authStore.hasPermission('inventory.requisitions.manage'))
const canViewTransfer = computed(() => hasWarehouseBranch.value && (canViewStockRequests.value || authStore.hasPermission('inventory.transfers.view') || authStore.hasPermission('inventory.transfers.manage') || authStore.hasPermission('inventory.transfers.create')))
const canCreatePurchase = computed(() => authStore.hasPermission('inventory.requisites.create') || authStore.hasPermission('inventory.requisites.manage') || authStore.hasPermission('inventory.requisitions.create') || authStore.hasPermission('inventory.requisitions.manage'))
const canCreateTransfer = computed(() => hasWarehouseBranch.value && (authStore.hasPermission('inventory.transfers.manage') || authStore.hasPermission('inventory.transfers.create')))
const canCreateActiveRequest = computed(() => activeType.value === 'purchase' ? canCreatePurchase.value : canCreateTransfer.value)
const requestedType = String(route.query.type || '')
const initialType: 'purchase' | 'transfer' = requestedType === 'transfer' && canViewTransfer.value
  ? 'transfer'
  : canViewPurchase.value ? 'purchase' : 'transfer'
const activeType = ref<'purchase' | 'transfer'>(initialType)
const loading = ref(false)
const rows = ref<any[]>([])
const page = ref(1)
const perPage = ref(15)
const total = ref(0)
const sortField = ref('created_at')
const sortOrder = ref(-1)
const filters = reactive({ search: '', status: null as string | null })
const dateRange = ref<Date[] | null>(null)
const hasActiveFilters = computed(() => Boolean(filters.search.trim() || filters.status || dateRange.value?.length))
const dateSortField = computed(() => activeType.value === 'purchase' ? 'created_at' : 'requested_date')

const purchaseStatuses = [
  { label: 'Draft', value: 'draft' }, { label: 'Pending Approval', value: 'pending' },
  { label: 'Procurement Pending', value: 'procurement_pending' },
  { label: 'Sourcing in Progress', value: 'procurement_processing' },
  { label: 'Ready for Inventory PO', value: 'inventory_po_pending' },
  { label: 'PO Created', value: 'po_created' }, { label: 'Rejected', value: 'rejected' },
  { label: 'Cancelled', value: 'cancelled' },
]
const transferStatuses = [
  { label: 'Draft', value: 'draft' }, { label: 'Pending Approval', value: 'requested' },
  { label: 'Approved', value: 'approved' }, { label: 'In Transit', value: 'in_transit' },
  { label: 'Received', value: 'received' }, { label: 'Cancelled', value: 'cancelled' },
]
const statusOptions = computed(() => activeType.value === 'purchase' ? purchaseStatuses : transferStatuses)

const formatDateParam = (date?: Date | null) => date
  ? `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
  : undefined
const formatDate = (value?: string) => value ? new Date(value).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }) : '—'
const formatTime = (value?: string) => value ? new Date(value).toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' }) : ''
const formatCurrency = (value: any) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
const formatStatus = (value: any) => {
  const status = String(value || 'unknown').toLowerCase()
  if (status === 'inventory_po_pending') return 'Ready for Inventory PO'
  if (status === 'procurement_pending') return 'Procurement Pending'
  return status.replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
}
const statusSeverity = (status: string) => {
  if (['approved', 'received', 'goods_received', 'delivered', 'po_created'].includes(status)) return 'success'
  if (['procurement_processing', 'sent_to_supplier', 'supplier_accepted', 'in_transit'].includes(status)) return 'info'
  if (['pending', 'procurement_pending', 'pending_receipt', 'partially_received', 'requested', 'pending_approval', 'submitted', 'inventory_po_pending'].includes(status)) return 'warn'
  if (['rejected', 'declined_supplier', 'cancelled', 'failed'].includes(status)) return status === 'cancelled' ? 'secondary' : 'danger'
  if (status === 'draft') return 'secondary'
  return 'info'
}

const setType = (type: 'purchase' | 'transfer') => {
  activeType.value = type
  filters.status = null
  page.value = 1
  sortField.value = type === 'purchase' ? 'created_at' : 'requested_date'
  loadRequests(1)
}

const createActiveRequest = () => {
  if (!canCreateActiveRequest.value) return
  if (activeType.value === 'purchase') {
    router.push({ name: 'inventory.stock-requests.create', query: { type: 'purchase' } })
    return
  }
  router.push({ name: 'inventory.stock-requests.create', query: { type: 'transfer' } })
}

const viewRequest = (id: number) => {
  if (activeType.value === 'purchase') {
    router.push({ name: 'inventory.stock-requests.detail', params: { type: 'purchase', id } })
    return
  }
  router.push({ name: 'inventory.stock-requests.detail', params: { type: 'transfer', id } })
}

const loadRequests = async (targetPage = page.value) => {
  loading.value = true
  page.value = targetPage
  try {
    const params = {
      page: targetPage,
      per_page: perPage.value,
      sort_by: sortField.value,
      sort_order: sortOrder.value === 1 ? 'asc' : 'desc',
      status: filters.status || undefined,
      search: filters.search.trim() || undefined,
      date_from: formatDateParam(dateRange.value?.[0]),
      date_to: formatDateParam(dateRange.value?.[1]),
    }
    if (activeType.value === 'purchase') {
      const response = await inventoryService.getPurchaseRequisitions(params)
      const payload = response?.data
      const list = Array.isArray(payload?.data) ? payload.data : []
      rows.value = list.map((row: any) => ({
        id: Number(row.id),
        reference: row.pr_number || `PR-${row.id}`,
        requestLabel: 'Supplier replenishment',
        branchLabel: row.branch?.name || 'Assigned branch',
        requestedBy: row.requested_by_name || row.requested_by || '',
        itemCount: Number(row.items?.length || 0),
        amount: Number(row.estimated_amount || 0),
        status: row.status || 'draft',
        date: row.created_at,
      }))
      total.value = Number(payload?.total || 0)
    } else {
      const response = await inventoryService.getTransfers(params)
      const payload = response?.data
      const list = Array.isArray(payload?.data) ? payload.data : []
      rows.value = list.map((row: any) => {
        const employee = row.requestedBy?.user || row.requested_by_user || null
        const itemCount = Number(row.items_count ?? row.items?.length ?? 0)
        return {
          id: Number(row.id),
          reference: row.transfer_number || row.transfer_no || `TR-${row.id}`,
          requestLabel: 'Warehouse transfer',
          routeLabel: `${row.from_branch?.name || 'Source'} → ${row.to_branch?.name || 'Destination'}`,
          requestedBy: [employee?.fname, employee?.lname].filter(Boolean).join(' ') || '',
          itemCount,
          amount: Number(row.goods_value || 0) + Number(row.transfer_cost || 0),
          status: row.delivery_status === 'delivered' && row.status !== 'received' ? 'delivered_awaiting_receipt' : row.status || 'draft',
          date: row.requested_date || row.created_at,
        }
      })
      total.value = Number(payload?.total || 0)
    }
  } catch (error: any) {
    rows.value = []
    total.value = 0
    toast.add({ severity: 'error', summary: 'Unable to load requests', detail: error?.response?.data?.message || 'Please try again.', life: 4000 })
  } finally {
    loading.value = false
  }
}

let searchTimer: ReturnType<typeof setTimeout> | undefined
watch(() => filters.search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => loadRequests(1), 300)
})
watch(dateRange, () => loadRequests(1))
const onPage = (event: any) => { perPage.value = event.rows; loadRequests(event.page + 1) }
const onSort = (event: any) => { sortField.value = event.sortField || dateSortField.value; sortOrder.value = event.sortOrder || -1; loadRequests(1) }
const clearFilters = () => { filters.search = ''; filters.status = null; dateRange.value = null; loadRequests(1) }

onMounted(async () => {
  try {
    const response = await inventoryService.getBranches()
    const payload = response?.data ?? response
    const branches = Array.isArray(payload?.data) ? payload.data : Array.isArray(payload) ? payload : []
    hasWarehouseBranch.value = branches.some((branch: any) => String(branch.branch_type || '').toLowerCase() === 'warehouse')
  } catch {
    hasWarehouseBranch.value = false
  }

  if (hasWarehouseBranch.value && canViewTransfer.value) {
    activeType.value = requestedType === 'purchase' && canViewPurchase.value ? 'purchase' : 'transfer'
    sortField.value = activeType.value === 'transfer' ? 'requested_date' : 'created_at'
  } else if (!hasWarehouseBranch.value && activeType.value === 'transfer') {
    activeType.value = 'purchase'
    sortField.value = 'created_at'
  }

  sourceLoading.value = false
  if (canViewPurchase.value || canViewTransfer.value) loadRequests()
})
</script>
