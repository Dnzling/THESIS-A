<template>
  <div class="min-h-screen p-4">
    <div>
      <div class="mb-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-xl font-bold text-gray-800">Stock Issuance</h1>
          </div>
          <div class="flex items-center gap-2">
            <Button
              label="New Stock Issuance"
              icon="pi pi-plus"
              size="small"
              class="text-sm"
              @click="createStockIssue"
            />
          </div>
        </div>
      </div>

      <Card>
        <template #content>
          <!-- Filters -->
          <div class="mb-5 grid grid-cols-1 items-end gap-4 md:grid-cols-4">
              <IconField>
                <InputIcon class="pi pi-search" />
                <InputText
                  v-model="filters.search"
                  placeholder="Search issue number or description"
                  class="w-full text-sm"
                  size="small"
                />
              </IconField>
                <Select
                  v-model="filters.status"
                  :options="statusOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Status"
                  class="w-full text-sm"
                  size="small"
                  showClear
                />
                <Select
                  v-model="filters.movement_type"
                  :options="movementOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Movement"
                  class="w-full text-sm"
                  size="small"
                  showClear
                />
                <Select
                  v-model="filters.issue_type"
                  :options="typeOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Reason"
                  class="w-full text-sm"
                  size="small"
                  showClear
                />
              <div v-if="hasActiveFilters">
                <Button
                  label="Clear All"
                  severity="danger"
                  size="small"
                  @click="clearFilters"
                />
              </div>
          </div>

          <!-- Data Table -->
          <DataTable
            :value="stockIssues"
            :loading="loading"
            paginator
            :rows="filters.per_page"
            :rowsPerPageOptions="[15, 25, 50]"
            :totalRecords="totalRecords"
            :lazy="true"
            @page="onPage"
            @sort="onSort"
            :sortField="filters.sort_field"
            :sortOrder="filters.sort_direction === 'asc' ? 1 : -1"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageSelect"
            tableStyle="min-width: 50rem"
            class="p-datatable-sm text-xs"
            rowHover
            :rowClass="() => 'cursor-pointer hover:bg-orange-50'"
            @row-click="onRowClick"
          >
            <Column field="issue_number" header="Issue Number" style="width: 140px" sortable />
            <Column field="movement_type" header="Movement" style="width: 130px">
              <template #body="slotProps">
                <Badge
                  :value="slotProps.data.movement_type === 'add' ? 'Add Stock' : 'Deduct Stock'"
                  :severity="slotProps.data.movement_type === 'add' ? 'success' : 'danger'"
                  class="capitalize"
                />
              </template>
            </Column>
            <Column field="issue_type" header="Reason" style="width: 130px">
              <template #body="slotProps">
                {{ formatIssueType(slotProps.data.issue_type) }}
              </template>
            </Column>
           
            <Column field="branch" header="Branch" style="min-width: 150px">
              <template #body="slotProps">
                {{ slotProps.data.branch?.name || 'N/A' }}
              </template>
            </Column>
            <Column field="creator" header="Created By" style="min-width: 150px">
              <template #body="slotProps">
                {{ slotProps.data.creator?.full_name || 'N/A' }}
              </template>
            </Column>
            <Column field="total_value" header="Total Value" style="width: 120px" sortable>
              <template #body="slotProps">
                ₱{{ formatNumber(slotProps.data.total_value) }}
              </template>
            </Column>
            <Column field="status" header="Status" style="width: 120px">
              <template #body="slotProps">
                <Badge
                  :value="formatIssueType(slotProps.data.status)"
                  :severity="getStatusSeverity(slotProps.data.status)"
                />
              </template>
            </Column>
            <Column field="issue_date" header="Issue Date" style="width: 150px" sortable>
              <template #body="slotProps">
                {{ formatDate(slotProps.data.issue_date) }}
              </template>
            </Column>
            <Column header="Actions" style="width: 150px">
              <template #body="slotProps">
                <div class="flex gap-2">
                  <Button
                    icon="pi pi-eye"
                    text
                    rounded
                    size="small"
                    @click="viewStockIssue(slotProps.data)"
                    v-tooltip.top="'View Details'"
                  />
              
                </div>
              </template>
            </Column>
            <template #empty>
              <div class="text-center py-8">
                <i class="pi pi-inbox text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">No stock issuance records found</p>
              </div>
            </template>
          </DataTable>
        </template>
      </Card>
    </div>
  </div>

  <!-- Cancel Confirmation Dialog -->
  <Dialog
    v-model:visible="cancelDialog"
    modal
    header="Confirm Cancellation"
    :style="{ width: '450px' }"
  >
    <div class="flex items-center gap-3">
      <i class="pi pi-exclamation-triangle text-orange-500 text-2xl"></i>
      <div>
        <p class="font-medium">Are you sure you want to cancel this stock issue?</p>
        <p class="text-sm text-gray-600 mt-1">
          Reference: <strong>{{ selectedStockIssue?.issue_number }}</strong>
        </p>
        <p class="text-sm text-gray-600 mt-1">
          This will mark the stock issuance as cancelled.
        </p>
      </div>
    </div>
    <template #footer>
      <Button
        label="No, Keep It"
        severity="secondary"
        @click="cancelDialog = false"
      />
      <Button
        label="Yes, Cancel Issue"
        severity="danger"
        @click="cancelStockIssue"
        :loading="cancelLoading"
      />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'

const loading = ref(false)
const cancelLoading = ref(false)
const cancelDialog = ref(false)
const selectedStockIssue = ref<any>(null)
const stockIssues = ref<any[]>([])
const totalRecords = ref(0)
const toast = useToast()
const router = useRouter()

const filters = reactive({
  search: '',
  status: null as string | null,
  movement_type: null as string | null,
  issue_type: null as string | null,
  page: 1,
  per_page: 15,
  sort_field: 'issue_date',
  sort_direction: 'desc' as 'asc' | 'desc'
})

const statusOptions = [
  { label: 'Draft', value: 'draft' },
  { label: 'Submitted', value: 'submitted' },
  { label: 'Approved', value: 'approved' },
  { label: 'Issued', value: 'issued' },
  { label: 'Cancelled', value: 'cancelled' }
]

const typeOptions = [
  { label: 'Goods Received', value: 'goods_received' },
  { label: 'Stock Return', value: 'stock_return' },
  { label: 'Inventory Correction', value: 'inventory_correction' },
  { label: 'Opening Balance', value: 'opening_balance' },
  { label: 'Transfer In', value: 'transfer_in' },
  { label: 'Used for Production', value: 'production_use' },
  { label: 'Office Consumption', value: 'office_consumption' },
  { label: 'Store Usage', value: 'store_usage' },
  { label: 'Damaged', value: 'damaged' },
  { label: 'Lost', value: 'lost' },
  { label: 'Expired', value: 'expired' },
  { label: 'Stock Correction', value: 'stock_correction' },
  { label: 'Other', value: 'other' }
]

const movementOptions = [
  { label: 'Add Stock', value: 'add' },
  { label: 'Deduct Stock', value: 'deduct' },
]

const hasActiveFilters = computed(() => Boolean(
  filters.search.trim() || filters.status || filters.movement_type || filters.issue_type
))

let searchTimer: ReturnType<typeof setTimeout> | null = null

const formatIssueType = (type: string) => String(type || 'other').replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase())

const loadStockIssues = async () => {
  loading.value = true
  try {
    const params: any = {
      page: filters.page,
      per_page: filters.per_page,
      sort_by: filters.sort_field,
      sort_order: filters.sort_direction
    }
    
    if (filters.search) params.search = filters.search
    if (filters.status) params.status = filters.status
    if (filters.movement_type) params.movement_type = filters.movement_type
    if (filters.issue_type) params.issue_type = filters.issue_type

    const response = await inventoryService.getStockIssues(params)

    if (response.success) {
      // Handle paginated response
      if (response.data && response.data.data && Array.isArray(response.data.data)) {
        stockIssues.value = response.data.data
        totalRecords.value = response.data.total || 0
      } else if (Array.isArray(response.data)) {
        stockIssues.value = response.data
        totalRecords.value = response.data.length
      } else {
        stockIssues.value = []
        totalRecords.value = 0
      }
      
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Failed to load stock issues',
        life: 3000
      })
    }
  } catch (error: any) {
    console.error('Error loading stock issues:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load stock issues',
      life: 3000
    })
    stockIssues.value = []
    totalRecords.value = 0
  } finally {
    loading.value = false
  }
}

const onFilter = () => {
  filters.page = 1
  loadStockIssues()
}

const onPage = (event: any) => {
  filters.page = event.page + 1
  filters.per_page = event.rows
  loadStockIssues()
}

const onSort = (event: any) => {
  filters.sort_field = event.sortField
  filters.sort_direction = event.sortOrder === 1 ? 'asc' : 'desc'
  loadStockIssues()
}

const clearFilters = () => {
  filters.search = ''
  filters.status = null
  filters.movement_type = null
  filters.issue_type = null
  filters.page = 1
  filters.per_page = 15
  loadStockIssues()
}

const createStockIssue = () => {
  router.push({ name: 'inventory.stock-issues.create' })
}

const viewStockIssue = (stockIssue: any) => {
  router.push({ name: 'inventory.stock-issues.detail', params: { id: stockIssue.id } })
}

const onRowClick = (event: any) => {
  const target = event?.originalEvent?.target as HTMLElement | null
  if (target?.closest('button, a, input')) return
  viewStockIssue(event.data)
}

const editStockIssue = (stockIssue: any) => {
  router.push({ name: 'inventory.stock-issues.edit', params: { id: stockIssue.id } })
}

const confirmCancel = (stockIssue: any) => {
  selectedStockIssue.value = stockIssue
  cancelDialog.value = true
}

const cancelStockIssue = async () => {
  if (!selectedStockIssue.value) return

  cancelLoading.value = true
  try {
    const response = await inventoryService.cancelStockIssue(selectedStockIssue.value.id)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Stock issue cancelled successfully',
        life: 3000
      })
      cancelDialog.value = false
      selectedStockIssue.value = null
      loadStockIssues()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to cancel stock issue',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to cancel stock issue',
      life: 3000
    })
  } finally {
    cancelLoading.value = false
  }
}

const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'approved':
    case 'issued':
      return 'success'
    case 'submitted':
      return 'warning'
    case 'draft': 
      return 'info'
    case 'cancelled': 
      return 'danger'
    default: 
      return 'secondary'
  }
}

const getTypeSeverity = (type: string) => {
  switch (type) {
    case 'expired': 
    case 'damaged': 
      return 'danger'
    case 'lost': 
      return 'warning'
    case 'internal_use': 
      return 'info'
    case 'sample': 
      return 'success'
    default: 
      return 'secondary'
  }
}

const formatDate = (date: string) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatNumber = (value: string | number | null | undefined) => {
  if (value === null || value === undefined) return '0.00'
  return parseFloat(value.toString()).toFixed(2)
}

onMounted(() => {
  loadStockIssues()
})

watch([() => filters.status, () => filters.movement_type, () => filters.issue_type, () => filters.per_page], () => {
  filters.page = 1
  loadStockIssues()
})

watch(() => filters.search, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    filters.page = 1
    loadStockIssues()
  }, 350)
})
</script>
