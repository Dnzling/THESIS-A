<template>
  <div class="min-h-screen p-6">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-lg font-bold text-gray-800">Stock Counts</h1>
          </div>
          <div class="flex items-center gap-2">
            <Button

              label="Auto Schedule Cycle Counts"
              icon="pi pi-calendar-plus"
              severity="warning" size="small"
              outlined
              @click="showScheduleDialog = true"
            />
            <Button

              label="New Stock Count"
              icon="pi pi-plus" size="small"
              @click="createStockCount"
              class="bg-blue-600 hover:bg-blue-700"
            />
          </div>
        </div>
      </div>

      <!-- Filters -->
      <Card class="mb-6">
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
              <InputText
                v-model="filters.search"
                placeholder="Search by reference..."
                class="w-full"
                @input="debouncedFilter"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
              <Select
                v-model="filters.status"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="All Statuses"
                class="w-full"
                showClear
                @change="applyFilters"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
              <DatePicker
                v-model="filters.date_range"
                selectionMode="range"
                :manualInput="false"
                placeholder="Select date range"
                fluid
                showIcon
                showClear
                :maxDate="new Date()"
                dateFormat="yy-mm-dd"
                @update:modelValue="applyFilters"
              />
            </div>
          </div>
        </template>
      </Card>

      <!-- Stock Counts Table -->
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

          <DataTable
            v-else
            :value="stockCounts"
            class="p-datatable-sm p-datatable-fluid"
            tableStyle="min-width: 50rem"
            :paginator="true"
            :rows="perPage"
            :totalRecords="totalRecords"
            :lazy="true"
            @page="onPage"
            @sort="onSort"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[10, 25, 50]"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
            selectionMode="single"
            dataKey="id"
          >
            <Column field="count_date" header="Date" sortable style="min-width: 180px">
              <template #body="slotProps">
                <div class="text-sm">
                  <div class="font-medium">{{ formatDateTime(slotProps.data.count_date) }}</div>
                  <div class="text-gray-500 text-xs">{{ slotProps.data.reference_number || slotProps.data.count_number || '-' }}</div>
                </div>
              </template>
            </Column>
            <Column field="branch.name" header="Branch" style="min-width: 150px">
              <template #body="slotProps">
                <div class="text-sm">
                  <div class="font-medium">{{ slotProps.data.branch?.name || slotProps.data.store_name || '-' }}</div>
                  <div class="text-gray-500">{{ slotProps.data.branch?.code || slotProps.data.store_code || '' }}</div>
                </div>
              </template>
            </Column>
            <Column field="status" header="Status" style="min-width: 120px">
              <template #body="slotProps">
                <Tag
                  :value="(slotProps.data.status || '').replace(/_/g, ' ')
                    .split(' ')
                    .map(s => s.charAt(0).toUpperCase() + s.slice(1))
                    .join(' ')"
                  :severity="getStatusSeverity(slotProps.data.status)"
                  class="capitalize"
                />
              </template>
            </Column>
            <Column field="total_items" header="Items Counted" style="min-width: 120px">
              <template #body="slotProps">
                {{ slotProps.data.total_items || 0 }}
              </template>
            </Column>
            <Column field="discrepancies_found" header="Discrepancies" style="min-width: 120px">
              <template #body="slotProps">
                <span :class="slotProps.data.discrepancies_found > 0 ? 'text-red-600 font-medium' : 'text-green-600'">
                  {{ slotProps.data.discrepancies_found || 0 }}
                </span>
              </template>
            </Column>
            <Column field="counted_by" header="Counted By" style="min-width: 150px">
              <template #body="slotProps">
                <div class="text-sm">
                  <div class="font-medium">{{ slotProps.data.counted_by_user?.name }}</div>
                  <div class="text-gray-500">{{ formatDateTime(slotProps.data.created_at) }}</div>
                </div>
              </template>
            </Column>
            <Column header="Actions" style="min-width: 120px">
              <template #body="slotProps">
                <div class="flex gap-2">
                  <Button
                    v-if="canViewStockCounts"
                    icon="pi pi-eye"
                    severity="info"
                    outlined
                    v-tooltip.top="'View Details'"
                    @click="viewStockCount(slotProps.data)"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>

      <!-- Delete Confirmation Dialog -->
      <Dialog
        v-model:visible="deleteDialog"
        modal
        header="Confirm Delete"
        :style="{ width: '450px' }"
      >
        <div class="flex items-center">
          <i class="pi pi-exclamation-triangle text-red-500 text-2xl mr-3"></i>
          <span>Are you sure you want to delete this stock count? This action cannot be undone.</span>
        </div>
        <template #footer>
          <Button
            label="Cancel"
            icon="pi pi-times"
            severity="secondary"
            @click="deleteDialog = false"
          />
          <Button
            label="Delete"
            icon="pi pi-check"
            severity="danger"
            @click="deleteStockCount"
            :loading="deleting"
          />
        </template>
      </Dialog>

      <Dialog
        v-model:visible="showScheduleDialog"
        modal
        header="Auto Schedule Cycle Counts"
        :style="{ width: '500px' }"
      >
        <div class="space-y-4">
          <div class="rounded-lg border border-blue-100 bg-blue-50 p-3 text-sm text-blue-800">
            Counts will be scheduled for your current branch.
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Weeks</label>
              <InputNumber v-model="scheduleForm.weeks" :min="1" :max="12" class="w-full" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Items per Count</label>
              <InputNumber v-model="scheduleForm.per_count" :min="10" :max="200" class="w-full" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
            <Calendar v-model="scheduleForm.start_date" class="w-full" showIcon dateFormat="yy-mm-dd" />
          </div>
          <div class="bg-yellow-50 p-4 rounded-lg text-sm text-yellow-800">
            This will create weekly cycle counts using auto-suggested items.
          </div>
        </div>
        <template #footer>
          <Button label="Cancel" severity="secondary" @click="showScheduleDialog = false" />
          <Button label="Schedule" severity="warning" :loading="scheduling" @click="autoScheduleCounts" />
        </template>
      </Dialog>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../../../stores/auth'
import inventoryService from '../../../../services/inventory.service'
import { debounce } from 'lodash'
import InputNumber from 'primevue/inputnumber'
import Calendar from 'primevue/calendar'

const loading = ref(false)
const deleting = ref(false)
const deleteDialog = ref(false)
const showScheduleDialog = ref(false)
const scheduling = ref(false)
const selectedStockCount = ref<any>(null)
const stockCounts = ref<any[]>([])
const totalRecords = ref(0)
const perPage = ref(10)
const toast = useToast()
const router = useRouter()
const authStore = useAuthStore()

const canViewStockCounts = authStore.hasPermission('inventory.stock_counts.view')
const canCreateStockCounts = authStore.hasPermission('inventory.stock_counts.manage')
const canUpdateStockCounts = authStore.hasPermission('inventory.stock_counts.manage')
const canCompleteStockCounts = authStore.hasPermission('inventory.stock_counts.manage')
const canDeleteStockCounts = authStore.hasPermission('inventory.stock_counts.delete')

const filters = reactive({
  search: '',
  status: null as string | null,
  branch_id: null as number | null,
  date_range: null as Date[] | null
})

const scheduleForm = reactive({
  branch_id: null as number | null,
  weeks: 4,
  per_count: 50,
  start_date: null as Date | null
})

const statusOptions = [
  { label: 'Draft', value: 'draft' },
  { label: 'In Progress', value: 'in_progress' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' }
]

const loadStockCounts = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page,
      per_page: perPage.value,
      search: filters.search || undefined,
      status: filters.status || undefined,
      branch_id: filters.branch_id || undefined,
      start_date: filters.date_range?.[0]?.toISOString().split('T')[0] || undefined,
      end_date: filters.date_range?.[1]?.toISOString().split('T')[0] || undefined
    }

    const response = await inventoryService.getStockCounts(params)

    if (response.success) {
      stockCounts.value = response.data || []
      totalRecords.value = response.meta?.total || 0
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to load stock counts',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load stock counts',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}


const debouncedFilter = debounce(() => {
  applyFilters()
}, 500)

const applyFilters = () => {
  loadStockCounts(1)
}

const onPage = (event: any) => {
  loadStockCounts(event.page + 1)
}

const onSort = () => {
  // Handle sorting if needed
  loadStockCounts(1)
}

const createStockCount = () => {
  router.push({ name: 'inventory.stock-counts.create' })
}

const viewStockCount = (stockCount: any) => {
  router.push({ name: 'inventory.stock-counts.detail', params: { id: stockCount.id } })
}

const editStockCount = (stockCount: any) => {
  router.push({ name: 'inventory.stock-counts.edit', params: { id: stockCount.id } })
}

const completeStockCount = async (stockCount: any) => {
  try {
    const response = await inventoryService.completeStockCount(stockCount.id)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Stock count completed successfully',
        life: 3000
      })
      loadStockCounts()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to complete stock count',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to complete stock count',
      life: 3000
    })
  }
}

const confirmDelete = (stockCount: any) => {
  selectedStockCount.value = stockCount
  deleteDialog.value = true
}

const deleteStockCount = async () => {
  if (!selectedStockCount.value) return

  deleting.value = true
  try {
    const response = await inventoryService.deleteStockCount(selectedStockCount.value.id)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Stock count deleted successfully',
        life: 3000
      })
      deleteDialog.value = false
      selectedStockCount.value = null
      loadStockCounts()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to delete stock count',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to delete stock count',
      life: 3000
    })
  } finally {
    deleting.value = false
  }
}

const autoScheduleCounts = async () => {
  if (!scheduleForm.branch_id) {
    toast.add({
      severity: 'warn',
      summary: 'Select Warehouse',
      detail: 'Please select a warehouse to schedule counts.',
      life: 3000
    })
    return
  }

  scheduling.value = true
  try {
    const payload = {
      branch_id: scheduleForm.branch_id,
      weeks: scheduleForm.weeks,
      per_count: scheduleForm.per_count,
      start_date: scheduleForm.start_date
        ? scheduleForm.start_date.toISOString().split('T')[0]
        : undefined
    }
    const response = await inventoryService.autoScheduleStockCounts(payload)
    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Scheduled',
        detail: response.message || 'Cycle counts scheduled.',
        life: 3000
      })
      showScheduleDialog.value = false
      loadStockCounts()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to schedule counts',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to schedule counts',
      life: 3000
    })
  } finally {
    scheduling.value = false
  }
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString()
}

const formatTime = (date?: string) => {
  if (!date) return ''
  try {
    const d = new Date(date)
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  } catch {
    return ''
  }
}

const formatDateTime = (date?: string) => {
  if (!date) return '-'
  try {
    const d = new Date(date)
    return d.toLocaleString()
  } catch {
    return String(date)
  }
}


const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'draft': return 'secondary'
    case 'in_progress': return 'info'
    case 'completed': return 'success'
    case 'cancelled': return 'danger'
    default: return 'secondary'
  }
}

onMounted(() => {
  const userBranchId = Number((authStore.user as any)?.branch_id || 0)
  if (userBranchId) {
    filters.branch_id = userBranchId
    scheduleForm.branch_id = userBranchId
  }
  loadStockCounts()
})
</script>
