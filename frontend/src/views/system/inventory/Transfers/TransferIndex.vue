<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Stock Transfers</h1>
        <p class="text-gray-600 mt-1">Manage inter-store stock movements and approvals</p>
      </div>
      <Button label="Create Transfer" icon="pi pi-plus" severity="success" @click="router.push({ name: 'inventory.transfers.create' })" />
    </div>

    <!-- Filters -->
    <Card class="mb-6">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Select
            v-model="filters.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="All Statuses"
            showClear
            @change="loadTransfers(1)"
          />
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText
              v-model="filters.search"
              placeholder="Search transfer no..."
              @keyup.enter="loadTransfers(1)"
            />
          </IconField>
          <Calendar
            v-model="filters.start_date"
            dateFormat="yy-mm-dd"
            placeholder="From Date"
            @date-select="loadTransfers(1)"
          />
          <Button icon="pi pi-filter-slash" label="Reset" @click="resetFilters" />
        </div>
      </template>
    </Card>

    <!-- Transfers Table -->
    <Card>
      <template #content>
        <DataTable
          :value="transfers"
          :loading="loading"
          paginator
          :rows="pagination.per_page"
          :totalRecords="pagination.total"
          :first="(pagination.current_page - 1) * pagination.per_page"
          @page="onPageChange"
          dataKey="id"
          class="p-datatable-sm"
          stripedRows
        >
          <template #empty>
            <div class="text-center py-8">
              <i class="pi pi-inbox text-4xl text-gray-400"></i>
              <p class="text-gray-600 mt-2">No transfers found</p>
            </div>
          </template>

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

          <Column field="transfer_date" header="Date" style="width: 12%">
            <template #body="{ data }">
              {{ formatDate(data.transfer_date) }}
            </template>
          </Column>

          <Column field="status" header="Status" style="width: 15%">
            <template #body="{ data }">
              <Tag :value="data.status" :severity="statusSeverity(data.status)" />
            </template>
          </Column>

          <Column header="Actions" style="width: 12%">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button
                  icon="pi pi-eye"
                  size="small"
                  text
                  severity="info"
                  @click="router.push({ name: 'inventory.transfers.detail', params: { id: data.id } })"
                  v-tooltip="'View details'"
                />
                <Button
                  v-if="data.status === 'submitted'"
                  icon="pi pi-check"
                  size="small"
                  text
                  severity="success"
                  @click="approveTransfer(data.id)"
                  v-tooltip="'Approve transfer'"
                />
              </div>
            </template>
          </Column>
        </DataTable>

        <!-- Pagination Info -->
        <div class="flex justify-between items-center mt-4 text-sm text-gray-600">
          <div>
            Showing {{ (pagination.current_page - 1) * pagination.per_page + 1 }} to 
            {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} 
            of {{ pagination.total }} entries
          </div>
          <div class="flex items-center gap-2">
            <span>Rows per page:</span>
            <Select
              v-model="pagination.per_page"
              :options="[10, 15, 25, 50, 100]"
              @change="loadTransfers(1)"
              style="width: 80px"
            />
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

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
const loading = ref(false)
const transfers = ref<Transfer[]>([])

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
  start_date: null as Date | null
})

const statusOptions = [
  { label: 'Draft', value: 'draft' },
  { label: 'Submitted', value: 'submitted' },
  { label: 'Approved', value: 'approved' },
  { label: 'Shipped', value: 'shipped' },
  { label: 'Received', value: 'received' },
  { label: 'Cancelled', value: 'cancelled' }
]

const statusSeverity = (status: string) => {
  const severities: Record<string, string> = {
    draft: 'secondary',
    submitted: 'info',
    approved: 'warning',
    shipped: 'primary',
    received: 'success',
    cancelled: 'danger'
  }
  return severities[status] || 'secondary'
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', { 
    month: 'short', 
    day: 'numeric', 
    year: 'numeric' 
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
    
    // Handle different API response structures
    if (response.data?.data) {
      // Handle { success: true, data: items, meta: pagination } format
      if (Array.isArray(response.data.data)) {
        transfers.value = response.data.data
        if (response.data.meta) {
          pagination.current_page = response.data.meta.current_page || page
          pagination.last_page = response.data.meta.last_page || 1
          pagination.per_page = response.data.meta.per_page || pagination.per_page
          pagination.total = response.data.meta.total || 0
          pagination.from = response.data.meta.from || 0
          pagination.to = response.data.meta.to || 0
        }
      } 
      // Handle Laravel pagination format { data: items, current_page, etc }
      else if (response.data.data && response.data.current_page) {
        transfers.value = response.data.data
        pagination.current_page = response.data.current_page
        pagination.last_page = response.data.last_page
        pagination.per_page = response.data.per_page
        pagination.total = response.data.total
        pagination.from = response.data.from
        pagination.to = response.data.to
      }
    } 
    // Handle direct array response
    else if (Array.isArray(response.data)) {
      transfers.value = response.data
      pagination.total = response.data.length
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

const approveTransfer = async (id: number) => {
  try {
    await axios.post(`/api/inventory/transfers/${id}/approve`)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Transfer approved successfully',
      life: 2000
    })
    loadTransfers(pagination.current_page)
  } catch (error) {
    console.error('Failed to approve transfer', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to approve transfer',
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

// Watch for per_page changes
watch(() => pagination.per_page, () => {
  loadTransfers(1)
})

onMounted(() => {
  loadTransfers(1)
})
</script>