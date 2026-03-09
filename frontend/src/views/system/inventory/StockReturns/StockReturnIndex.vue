<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-800">Stock Returns</h1>
            <p class="text-gray-600 mt-1">Manage stock return transactions</p>
          </div>
          <Button
            label="Add Return"
            @click="createStockReturn"
            class="bg-blue-600 hover:bg-blue-700"
          />
        </div>
      </div>

      <Card>
        <template #content>
          <!-- Filters -->
          <div class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <InputText
                  v-model="filters.search"
                  placeholder="Search returns..."
                  class="w-full"
                  @input="onFilter"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <Select
                  v-model="filters.status"
                  :options="statusOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="All Status"
                  class="w-full"
                  showClear
                  @change="onFilter"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Return Reason</label>
                <Select
                  v-model="filters.return_reason"
                  :options="returnReasonOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="All Reasons"
                  class="w-full"
                  showClear
                  @change="onFilter"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                <Calendar
                  v-model="filters.from_date"
                  placeholder="Select date"
                  class="w-full"
                  showIcon
                  dateFormat="yy-mm-dd"
                  @date-select="onFilter"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                <Calendar
                  v-model="filters.to_date"
                  placeholder="Select date"
                  class="w-full"
                  showIcon
                  dateFormat="yy-mm-dd"
                  @date-select="onFilter"
                />
              </div>
              <div class="flex items-end">
                <Button
                  label="Clear Filters"
                  severity="secondary"
                  @click="clearFilters"
                  class="w-full"
                />
              </div>
            </div>
          </div>

          <!-- Data Table -->
          <DataTable
            :value="stockReturns"
            :loading="loading"
            paginator
            :rows="10"
            :rowsPerPageOptions="[5, 10, 25, 50]"
            :totalRecords="totalRecords"
            :lazy="true"
            @page="onPage"
            @sort="onSort"
            tableStyle="min-width: 50rem"
            class="p-datatable-sm"
            :globalFilterFields="['reference_number', 'notes']"
          >
            <Column field="reference_number" header="Reference" sortable style="min-width: 150px" />
            <Column field="return_date" header="Return Date" sortable style="width: 120px">
              <template #body="slotProps">
                {{ formatDate(slotProps.data.return_date) }}
              </template>
            </Column>
            <Column field="status" header="Status" style="width: 120px">
              <template #body="slotProps">
                <Tag
                  :value="slotProps.data.status"
                  :severity="getStatusSeverity(slotProps.data.status)"
                  class="capitalize"
                />
              </template>
            </Column>
            <Column field="return_reason" header="Reason" style="width: 140px">
              <template #body="slotProps">
                <span class="capitalize">{{ slotProps.data.return_reason.replace('_', ' ') }}</span>
              </template>
            </Column>
            <Column field="total_quantity" header="Total Qty" style="width: 100px" sortable>
              <template #body="slotProps">
                <span class="font-medium">{{ slotProps.data.total_quantity || 0 }}</span>
              </template>
            </Column>
            <Column field="total_value" header="Total Value" style="width: 120px" sortable>
              <template #body="slotProps">
                <span class="font-medium">${{ slotProps.data.total_value?.toFixed(2) || '0.00' }}</span>
              </template>
            </Column>
            <Column field="processed_by" header="Processed By" style="min-width: 150px">
              <template #body="slotProps">
                <div class="text-sm">
                  <div class="font-medium">{{ slotProps.data.processed_by_user?.name }}</div>
                  <div class="text-gray-500">{{ slotProps.data.processed_by_user?.email }}</div>
                </div>
              </template>
            </Column>
            <Column field="created_at" header="Created" style="width: 150px" sortable>
              <template #body="slotProps">
                {{ formatDate(slotProps.data.created_at) }}
              </template>
            </Column>
            <Column header="Actions" style="width: 150px">
              <template #body="slotProps">
                <div class="flex gap-2">
                  <Button
                    icon="pi pi-eye"
                    severity="info"
                    outlined
                    @click="viewStockReturn(slotProps.data)"
                    v-tooltip.top="'View Details'"
                  />
                  <Button
                    icon="pi pi-pencil"
                    severity="warning"
                    outlined
                    @click="editStockReturn(slotProps.data)"
                    v-tooltip.top="'Edit Return'"
                    :disabled="slotProps.data.status === 'completed'"
                  />
                  <Button
                    icon="pi pi-trash"
                    severity="danger"
                    outlined
                    @click="confirmDelete(slotProps.data)"
                    v-tooltip.top="'Delete Return'"
                    :disabled="slotProps.data.status === 'completed'"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>
    </div>
  </div>

  <!-- Delete Confirmation Dialog -->
  <Dialog
    v-model:visible="deleteDialog"
    modal
    header="Confirm Delete"
    :style="{ width: '450px' }"
  >
    <div class="flex items-center gap-3">
      <i class="pi pi-exclamation-triangle text-red-500 text-2xl"></i>
      <div>
        <p class="font-medium">Are you sure you want to delete this stock return?</p>
        <p class="text-sm text-gray-600 mt-1">
          Reference: <strong>{{ selectedStockReturn?.reference_number }}</strong>
        </p>
        <p v-if="selectedStockReturn?.status === 'completed'" class="text-sm text-red-600 mt-2">
          Warning: This return has been completed and may affect inventory records.
        </p>
      </div>
    </div>
    <template #footer>
      <Button
        label="Cancel"
        severity="secondary"
        @click="deleteDialog = false"
      />
      <Button
        label="Delete"
        severity="danger"
        @click="deleteStockReturn"
        :loading="deleteLoading"
      />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'

const loading = ref(false)
const deleteLoading = ref(false)
const deleteDialog = ref(false)
const selectedStockReturn = ref<any>(null)
const stockReturns = ref<any[]>([])
const totalRecords = ref(0)
const toast = useToast()
const router = useRouter()

const filters = reactive({
  search: '',
  status: null as string | null,
  return_reason: null as string | null,
  from_date: null as Date | null,
  to_date: null as Date | null,
  page: 1,
  per_page: 10,
  sort_field: 'created_at',
  sort_direction: 'desc'
})

const statusOptions = [
  { label: 'Pending', value: 'pending' },
  { label: 'Processing', value: 'processing' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' }
]

const returnReasonOptions = [
  { label: 'Damaged', value: 'damaged' },
  { label: 'Defective', value: 'defective' },
  { label: 'Wrong Item', value: 'wrong_item' },
  { label: 'Overstock', value: 'overstock' },
  { label: 'Expired', value: 'expired' },
  { label: 'Customer Return', value: 'customer_return' },
  { label: 'Quality Issue', value: 'quality_issue' },
  { label: 'Other', value: 'other' }
]

const loadStockReturns = async () => {
  loading.value = true
  try {
    const params = {
      ...filters,
      search: filters.search || undefined,
      status: filters.status || undefined,
      return_reason: filters.return_reason || undefined,
      from_date: filters.from_date ? filters.from_date.toISOString().split('T')[0] : undefined,
      to_date: filters.to_date ? filters.to_date.toISOString().split('T')[0] : undefined
    }

    const response = await inventoryService.getStockReturns(params)

    if (response.success) {
      stockReturns.value = response.data || []
      totalRecords.value = response.meta?.total || 0
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load stock returns',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const onFilter = () => {
  filters.page = 1
  loadStockReturns()
}

const onPage = (event: any) => {
  filters.page = event.page + 1
  filters.per_page = event.rows
  loadStockReturns()
}

const onSort = (event: any) => {
  filters.sort_field = event.sortField
  filters.sort_direction = event.sortOrder === 1 ? 'asc' : 'desc'
  loadStockReturns()
}

const clearFilters = () => {
  filters.search = ''
  filters.status = null
  filters.return_reason = null
  filters.from_date = null
  filters.to_date = null
  filters.page = 1
  loadStockReturns()
}

const createStockReturn = () => {
  router.push({ name: 'inventory.stock-returns.create' })
}

const viewStockReturn = (stockReturn: any) => {
  router.push({ name: 'inventory.stock-returns.detail', params: { id: stockReturn.id } })
}

const editStockReturn = (stockReturn: any) => {
  router.push({ name: 'inventory.stock-returns.edit', params: { id: stockReturn.id } })
}

const confirmDelete = (stockReturn: any) => {
  selectedStockReturn.value = stockReturn
  deleteDialog.value = true
}

const deleteStockReturn = async () => {
  if (!selectedStockReturn.value) return

  deleteLoading.value = true
  try {
    const response = await inventoryService.deleteStockReturn(selectedStockReturn.value.id)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Stock return deleted successfully',
        life: 3000
      })
      deleteDialog.value = false
      selectedStockReturn.value = null
      loadStockReturns()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to delete stock return',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to delete stock return',
      life: 3000
    })
  } finally {
    deleteLoading.value = false
  }
}

const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'completed': return 'success'
    case 'processing': return 'info'
    case 'pending': return 'warning'
    case 'cancelled': return 'danger'
    default: return 'secondary'
  }
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

onMounted(() => {
  loadStockReturns()
})
</script>