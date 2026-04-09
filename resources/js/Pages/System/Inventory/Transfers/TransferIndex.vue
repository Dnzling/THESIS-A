<template>
  <div class="p-6 min-h-screen">
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-lg font-bold text-gray-800">Stock Transfers</h1>
      </div>
      <Button v-if="canCreateTransfers" label="Create Transfer" icon="pi pi-plus" severity="success"
        @click="router.push({ name: 'inventory.transfers.create' })" size="small" />
    </div>
  
    <!-- Filters -->
    <Card class="mb-6">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText v-model="filters.search" placeholder="Search transfer no..." @keyup.enter="loadTransfers(1)"  size="small" fluid/>
          </IconField>
          <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value"
            placeholder="All Statuses" showClear @change="loadTransfers(1)" fluid  size="small" />
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Date Range</label>
            <Calendar v-model="dateRange" selectionMode="range" dateFormat="yy-mm-dd" class="w-full" showIcon />
          </div>
          <Button icon="pi pi-filter-slash" label="Reset" @click="resetFilters"  size="small" />
        </div>
      </template>
    </Card>
  
    <!-- Transfers Table -->
    <Card>
      <template #content>
        <div v-if="loading" class="space-y-3">
          <div class="grid grid-cols-6 gap-3 text-xs text-gray-400">
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
          </div>
          <div v-for="i in 8" :key="i" class="grid grid-cols-6 gap-3">
            <Skeleton height="20px" class="col-span-1" />
            <Skeleton height="20px" class="col-span-1" />
            <Skeleton height="20px" class="col-span-1" />
            <Skeleton height="20px" class="col-span-1" />
            <Skeleton height="20px" class="col-span-1" />
            <Skeleton height="20px" class="col-span-1" />
          </div>
        </div>

        <div v-else>
          <DataTable :value="transfers" paginator :rows="pagination.per_page"
            :totalRecords="pagination.total" :first="(pagination.current_page - 1) * pagination.per_page"
            @page="onPageChange" dataKey="id" class="p-datatable-sm p-datatable-fluid" stripedRows
            @row-click="onRowClick" :rowClass="rowClass">
            <template #empty>
              <div class="text-center py-8">
                <i class="pi pi-inbox text-4xl text-gray-400"></i>
                <p class="text-gray-600 mt-2">No transfers found</p>
              </div>
            </template>
    
            <Column field="transfer_date" header="Date" sortable style="width: 140px">
              <template #body="{ data }">
                <div class="text-xs">
                  <div>{{ formatDate(data.transfer_date) }}</div>
                  <div class="text-gray-500 text-xs">{{ formatTime(data.transfer_date) }}</div>
                </div>
              </template>
            </Column>

            <Column field="reference_no" header="Transfer No." style="width: 15%">
              <template #body="{ data }">
                <span class="font-medium">{{ data.reference_no }}</span>
              </template>
            </Column>
    
            <Column field="from_branch.name" header="From Branch" style="width: 15%">
              <template #body="{ data }">
                {{ data.from_branch?.name || 'N/A' }}
              </template>
            </Column>
    
            <Column field="to_branch.name" header="To Branch" style="width: 15%">
              <template #body="{ data }">
                {{ data.to_branch?.name || 'N/A' }}
              </template>
            </Column>
    
            <Column field="quantity" header="Qty" style="width: 10%" />
    
            
    
            <Column field="status" header="Status" style="width: 15%">
              <template #body="{ data }">
                <Tag :value="formatStatusLabel(data.status)" :severity="statusSeverity(data.status)" />
              </template>
            </Column>
    
            <Column header="Actions" style="width: 12%">
              <template #body="{ data }">
                <div class="flex gap-2">
                  <Button v-if="canViewTransfers" icon="pi pi-eye" size="small" text severity="info"
                    @click="router.push({ name: 'inventory.transfers.detail', params: { id: data.id } })"
                    v-tooltip="'View details'" />
                  <Button v-if="data.status === 'draft' && canCreateTransfers" icon="pi pi-pencil" size="small" text severity="warning"
                    @click="router.push({ name: 'inventory.transfers.create', query: { edit: data.id } })"
                    v-tooltip="'Edit draft'" />
                  <Button v-if="data.status === 'draft' && canCancelTransfers" icon="pi pi-times" size="small" text severity="danger"
                    @click="confirmCancel(data)" v-tooltip="'Cancel transfer'" />
                </div>
              </template>
            </Column>
          </DataTable>
    
          <div class="flex justify-between items-center mt-4 text-sm text-gray-600">
            <div>
              Showing {{ (pagination.current_page - 1) * pagination.per_page + 1 }} to
              {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}
              of {{ pagination.total }} entries
            </div>
            <div class="flex items-center gap-2">
              <span>Rows per page:</span>
              <Select v-model="pagination.per_page" :options="[10, 15, 25, 50, 100]" @change="loadTransfers(1)"
                style="width: 80px" />
            </div>
          </div>
        </div>
      </template>
    </Card>
    <ConfirmDialog />
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import axios from 'axios'
import { useAuthStore } from '../../../../stores/auth'

interface Transfer {
  id: number
  reference_no: string
  from_branch: { id: number; name: string } | null
  to_branch: { id: number; name: string } | null
  quantity: number
  transfer_date: string
  status: string
}

interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const authStore = useAuthStore()
const loading = ref(false)
const transfers = ref<Transfer[]>([])

const canViewTransfers = authStore.hasPermission('inventory.transfers.view')
const canCreateTransfers = authStore.hasPermission('inventory.transfers.manage')
const canCancelTransfers = authStore.hasPermission('inventory.transfers.manage')

const pagination = reactive<PaginationMeta>({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0
})

const filters = reactive({
  status: null as string | null,
  search: '',
  start_date: null as Date | null,
  sort_field: 'transfer_date',
  sort_direction: 'desc' as 'asc' | 'desc',
})

const dateRange = ref<[Date | null, Date | null] | null>(null)

const rowClass = (data: any) => ({ 'cursor-pointer hover:bg-gray-50': true })

const onRowClick = (event: any) => {
  const id = event?.data?.id
  if (id) router.push({ name: 'inventory.transfers.detail', params: { id } })
}

watch(dateRange, (val) => {
  if (!val || !Array.isArray(val)) {
    filters.start_date = null
    return
  }
  const [from] = val
  filters.start_date = from
  loadTransfers(1)
})

const statusOptions = [
  { label: 'Draft', value: 'draft' },
  { label: 'Pending Approval', value: 'requested' },
  { label: 'Submitted', value: 'submitted' },
  { label: 'Approved', value: 'approved' },
  { label: 'Shipped', value: 'shipped' },
  { label: 'Received', value: 'received' },
  { label: 'Cancelled', value: 'cancelled' }
]

const statusSeverity = (status: string) => {
  const severities: Record<string, string> = {
    draft: 'secondary',
    requested: 'warning',
    pending_approval: 'warning',
    submitted: 'info',
    approved: 'warning',
    shipped: 'primary',
    received: 'success',
    cancelled: 'danger'
  }
  return severities[status] || 'secondary'
}

const formatStatusLabel = (status: string) => {
  if (status === 'requested' || status === 'pending_approval') return 'pending approval'
  return status
}

const formatDate = (date: string) => {
  if (!date) return '-'
  const parsed = new Date(date)
  if (Number.isNaN(parsed.getTime())) return '-'
  return parsed.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const formatTime = (date: string) => {
  if (!date) return '-'
  const parsed = new Date(date)
  if (Number.isNaN(parsed.getTime())) return '-'
  return parsed.toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit'
  })
}

const loadTransfers = async (page = pagination.current_page) => {
  loading.value = true
  try {
    const params: Record<string, any> = {
      page,
      per_page: pagination.per_page
    }

    if (filters.status) params.status = filters.status
    if (filters.search) params.search = filters.search
    if (filters.start_date) params.start_date = filters.start_date.toISOString().split('T')[0]

    const response = await axios.get('/api/inventory/transfers', { params })
    const payload = response.data?.data ?? response.data

    const rows = Array.isArray(payload?.data)
      ? payload.data
      : Array.isArray(payload)
        ? payload
        : []

    transfers.value = rows.map((row: any) => {
      const items = Array.isArray(row.items) ? row.items : []
      const totalQty = items.reduce((sum: number, item: any) => sum + Number(item.requested_quantity || item.quantity || 0), 0)

      return {
        id: Number(row.id),
        reference_no: row.reference_no || row.transfer_no || row.transfer_number || `TRF-${row.id}`,
        from_branch: row.from_branch || row.fromBranch || null,
        to_branch: row.to_branch || row.toBranch || null,
        quantity: Number(row.quantity ?? totalQty ?? 0),
        transfer_date: row.transfer_date || row.requested_date || row.created_at || '',
        status: row.status || 'draft'
      }
    })

    if (payload && typeof payload === 'object' && !Array.isArray(payload)) {
      pagination.current_page = Number(payload.current_page || page)
      pagination.last_page = Number(payload.last_page || 1)
      pagination.per_page = Number(payload.per_page || pagination.per_page)
      pagination.total = Number(payload.total || transfers.value.length)
      pagination.from = Number(payload.from || (transfers.value.length ? 1 : 0))
      pagination.to = Number(payload.to || transfers.value.length)
    } else {
      pagination.current_page = 1
      pagination.last_page = 1
      pagination.total = transfers.value.length
      pagination.from = transfers.value.length ? 1 : 0
      pagination.to = transfers.value.length
    }

  } catch (error) {
    console.error('Failed to load transfers', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load transfers',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const confirmCancel = (transfer: any) => {
  confirm.require({
    message: `Are you sure you want to cancel transfer "${transfer.reference_no}"?`,
    header: 'Cancel Transfer',
    icon: 'pi pi-exclamation-triangle',
    accept: () => cancelTransfer(transfer.id)
  })
}

const cancelTransfer = async (id: number) => {
  try {
    await axios.post(`/api/inventory/transfers/${id}/cancel`)
    toast.add({
      severity: 'success',
      summary: 'Cancelled',
      detail: 'Transfer cancelled successfully',
      life: 2000
    })
    loadTransfers(pagination.current_page)
  } catch (error) {
    console.error('Failed to cancel transfer', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to cancel transfer',
      life: 3000
    })
  }
}

const resetFilters = () => {
  filters.status = null
  filters.search = ''
  filters.start_date = null
  loadTransfers(1)
}

const onPageChange = (event: { page: number; first: number; rows: number }) => {
  pagination.current_page = event.page + 1
  loadTransfers(pagination.current_page)
}

onMounted(() => {
  loadTransfers(1)
})
</script>
