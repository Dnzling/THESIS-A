<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-800">Stock Issues</h1>
            <p class="text-gray-600 mt-1">Manage stock issue transactions</p>
          </div>
          <Button
            label="Issue Stock"
            @click="createStockIssue"
            class="bg-blue-600 hover:bg-blue-700"
          />
        </div>
      </div>

      <Card>
        <template #content>
          <!-- Filters -->
          <div class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <InputText
                  v-model="filters.search"
                  placeholder="Search issues..."
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                <Select
                  v-model="filters.type"
                  :options="typeOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="All Types"
                  class="w-full"
                  showClear
                  @change="onFilter"
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
                  @change="onFilter"
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
            :value="stockIssues"
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
            <Column field="reference_number" header="Reference" style="width: 140px" sortable />
            <Column field="type" header="Type" style="width: 120px">
              <template #body="slotProps">
                <Tag
                  :value="slotProps.data.type"
                  :severity="getTypeSeverity(slotProps.data.type)"
                  class="capitalize"
                />
              </template>
            </Column>
            <Column field="product.name" header="Product" style="min-width: 200px" />
            <Column field="warehouse.name" header="Warehouse" style="min-width: 150px" />
            <Column field="location.name" header="Location" style="min-width: 150px" />
            <Column field="quantity" header="Quantity" style="width: 100px" sortable>
              <template #body="slotProps">
                <span class="font-medium text-red-600">
                  -{{ slotProps.data.quantity }}
                </span>
              </template>
            </Column>
            <Column field="unit_cost" header="Unit Cost" style="width: 120px">
              <template #body="slotProps">
                ${{ slotProps.data.unit_cost?.toFixed(2) || '0.00' }}
              </template>
            </Column>
            <Column field="total_cost" header="Total Cost" style="width: 120px" sortable>
              <template #body="slotProps">
                ${{ slotProps.data.total_cost?.toFixed(2) || '0.00' }}
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
            <Column field="issued_at" header="Issued Date" style="width: 150px" sortable>
              <template #body="slotProps">
                {{ formatDate(slotProps.data.issued_at) }}
              </template>
            </Column>
            <Column header="Actions" style="width: 150px">
              <template #body="slotProps">
                <div class="flex gap-2">
                  <Button
                    icon="pi pi-eye"
                    severity="info"
                    outlined
                    @click="viewStockIssue(slotProps.data)"
                    v-tooltip.top="'View Details'"
                  />
                  <Button
                    icon="pi pi-pencil"
                    severity="warning"
                    outlined
                    @click="editStockIssue(slotProps.data)"
                    v-tooltip.top="'Edit Issue'"
                    :disabled="slotProps.data.status === 'completed'"
                  />
                  <Button
                    icon="pi pi-times"
                    severity="danger"
                    outlined
                    @click="confirmCancel(slotProps.data)"
                    v-tooltip.top="'Cancel Issue'"
                    :disabled="slotProps.data.status === 'cancelled' || slotProps.data.status === 'completed'"
                  />
                </div>
              </template>
            </Column>
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
          Reference: <strong>{{ selectedStockIssue?.reference_number }}</strong>
        </p>
        <p class="text-sm text-gray-600 mt-1">
          This will reverse the stock reduction and mark the issue as cancelled.
        </p>
      </div>
    </div>
    <template #footer>
      <Button
        label="Cancel"
        severity="secondary"
        @click="cancelDialog = false"
      />
      <Button
        label="Confirm Cancellation"
        severity="danger"
        @click="cancelStockIssue"
        :loading="cancelLoading"
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
const cancelLoading = ref(false)
const cancelDialog = ref(false)
const selectedStockIssue = ref<any>(null)
const stockIssues = ref<any[]>([])
const warehouses = ref<any[]>([])
const totalRecords = ref(0)
const toast = useToast()
const router = useRouter()

const filters = reactive({
  search: '',
  status: null as string | null,
  type: null as string | null,
  warehouse_id: null as number | null,
  page: 1,
  per_page: 10,
  sort_field: 'issued_at',
  sort_direction: 'desc'
})

const statusOptions = [
  { label: 'Pending', value: 'pending' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' }
]

const typeOptions = [
  { label: 'Sale', value: 'sale' },
  { label: 'Damage', value: 'damage' },
  { label: 'Loss', value: 'loss' },
  { label: 'Transfer', value: 'transfer' },
  { label: 'Return', value: 'return' },
  { label: 'Other', value: 'other' }
]

const loadStockIssues = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getStockIssues({
      ...filters,
      search: filters.search || undefined,
      status: filters.status || undefined,
      type: filters.type || undefined,
      warehouse_id: filters.warehouse_id || undefined
    })

    if (response.success) {
      stockIssues.value = response.data || []
      totalRecords.value = response.meta?.total || 0
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load stock issues',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadWarehouses = async () => {
  try {
    const response = await inventoryService.getWarehouses()
    if (response.success) {
      warehouses.value = response.data || []
    }
  } catch (error) {
    console.error('Failed to load warehouses', error)
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
  filters.type = null
  filters.warehouse_id = null
  filters.page = 1
  loadStockIssues()
}

const createStockIssue = () => {
  router.push({ name: 'inventory.stock-issues.create' })
}

const viewStockIssue = (stockIssue: any) => {
  router.push({ name: 'inventory.stock-issues.detail', params: { id: stockIssue.id } })
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
    case 'completed': return 'success'
    case 'pending': return 'warning'
    case 'cancelled': return 'danger'
    default: return 'secondary'
  }
}

const getTypeSeverity = (type: string) => {
  switch (type) {
    case 'sale': return 'success'
    case 'damage': return 'danger'
    case 'loss': return 'warning'
    case 'transfer': return 'info'
    case 'return': return 'secondary'
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
  loadStockIssues()
  loadWarehouses()
})
</script>