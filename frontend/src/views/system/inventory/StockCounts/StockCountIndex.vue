<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-800">Stock Counts</h1>
            <p class="text-gray-600 mt-1">Manage inventory stock counts and audits</p>
          </div>
          <Button
            label="New Stock Count"
            icon="pi pi-plus"
            @click="createStockCount"
            class="bg-blue-600 hover:bg-blue-700"
          />
        </div>
      </div>

      <!-- Filters -->
      <Card class="mb-6">
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
              <label class="block text-sm font-medium text-gray-700 mb-2">Warehouse</label>
              <Select
                v-model="filters.warehouse_id"
                :options="warehouses"
                optionLabel="name"
                optionValue="id"
                placeholder="All Warehouses"
                class="w-full"
                showClear
                :loading="warehousesLoading"
                @change="applyFilters"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
              <Calendar
                v-model="filters.date_range"
                selectionMode="range"
                placeholder="Select date range"
                class="w-full"
                showIcon
                dateFormat="yy-mm-dd"
                @date-select="applyFilters"
              />
            </div>
          </div>
        </template>
      </Card>

      <!-- Stock Counts Table -->
      <Card>
        <template #content>
          <DataTable
            :value="stockCounts"
            :loading="loading"
            class="p-datatable-sm"
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
            <Column field="reference_number" header="Reference" sortable style="min-width: 150px">
              <template #body="slotProps">
                <div class="font-medium text-blue-600 hover:text-blue-800 cursor-pointer"
                     @click="viewStockCount(slotProps.data)">
                  {{ slotProps.data.reference_number }}
                </div>
              </template>
            </Column>
            <Column field="count_date" header="Count Date" sortable style="min-width: 120px">
              <template #body="slotProps">
                {{ formatDate(slotProps.data.count_date) }}
              </template>
            </Column>
            <Column field="warehouse.name" header="Warehouse" style="min-width: 150px">
              <template #body="slotProps">
                <div class="text-sm">
                  <div class="font-medium">{{ slotProps.data.warehouse?.name }}</div>
                  <div class="text-gray-500">{{ slotProps.data.warehouse?.code }}</div>
                </div>
              </template>
            </Column>
            <Column field="status" header="Status" style="min-width: 120px">
              <template #body="slotProps">
                <Tag
                  :value="slotProps.data.status"
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
            <Column header="Actions" style="min-width: 200px">
              <template #body="slotProps">
                <div class="flex gap-2">
                  <Button
                    icon="pi pi-eye"
                    severity="info"
                    outlined
                    v-tooltip.top="'View Details'"
                    @click="viewStockCount(slotProps.data)"
                  />
                  <Button
                    icon="pi pi-pencil"
                    severity="warning"
                    outlined
                    v-tooltip.top="'Edit'"
                    @click="editStockCount(slotProps.data)"
                    :disabled="slotProps.data.status === 'completed'"
                  />
                  <Button
                    icon="pi pi-check"
                    severity="success"
                    outlined
                    v-tooltip.top="'Complete Count'"
                    @click="completeStockCount(slotProps.data)"
                    :disabled="slotProps.data.status === 'completed'"
                  />
                  <Button
                    icon="pi pi-trash"
                    severity="danger"
                    outlined
                    v-tooltip.top="'Delete'"
                    @click="confirmDelete(slotProps.data)"
                    :disabled="slotProps.data.status === 'completed'"
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
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'
import { debounce } from 'lodash'

const loading = ref(false)
const deleting = ref(false)
const warehousesLoading = ref(false)
const deleteDialog = ref(false)
const selectedStockCount = ref<any>(null)
const stockCounts = ref<any[]>([])
const warehouses = ref<any[]>([])
const totalRecords = ref(0)
const perPage = ref(10)
const toast = useToast()
const router = useRouter()

const filters = reactive({
  search: '',
  status: null as string | null,
  warehouse_id: null as number | null,
  date_range: null as Date[] | null
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
      warehouse_id: filters.warehouse_id || undefined,
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

const loadWarehouses = async () => {
  warehousesLoading.value = true
  try {
    const response = await inventoryService.getWarehouses({ per_page: 1000 })

    if (response.success) {
      warehouses.value = response.data || []
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load warehouses',
      life: 3000
    })
  } finally {
    warehousesLoading.value = false
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

const onSort = (event: any) => {
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

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString()
}

const formatDateTime = (dateTime: string) => {
  if (!dateTime) return '-'
  return new Date(dateTime).toLocaleString()
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
  loadStockCounts()
  loadWarehouses()
})
</script>