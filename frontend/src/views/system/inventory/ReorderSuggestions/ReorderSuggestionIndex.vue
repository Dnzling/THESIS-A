<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-800">Reorder Suggestions</h1>
            <p class="text-gray-600 mt-1">View and manage automatic reorder suggestions</p>
          </div>
          <Button
            label="Generate Suggestions"
            icon="pi pi-refresh"
            @click="generateSuggestions"
            class="bg-blue-600 hover:bg-blue-700"
          />
        </div>
      </div>

      <!-- Filters -->
      <Card class="mb-6">
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
              <InputText
                v-model="filters.search"
                placeholder="Search by product..."
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
              <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
              <Select
                v-model="filters.priority"
                :options="priorityOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="All Priorities"
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

      <!-- Reorder Suggestions Table -->
      <Card>
        <template #content>
          <DataTable
            :value="reorderSuggestions"
            :loading="loading"
            class="p-datatable-sm"
            tableStyle="min-width: 50rem"
            :paginator="true"
            :rows="perPage"
            :totalRecords="totalRecords"
            :lazy="true"
            @page="onPage"
            @sort="onSort"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
            :rowsPerPageOptions="[10, 25, 50]"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
            selectionMode="single"
            dataKey="id"
          >
            <Column field="product.name" header="Product" sortable style="min-width: 200px">
              <template #body="slotProps">
                <div class="text-sm">
                  <div class="font-medium text-blue-600 hover:text-blue-800 cursor-pointer"
                       @click="viewReorderSuggestion(slotProps.data)">
                    {{ slotProps.data.product?.name }}
                  </div>
                  <div class="text-gray-500">{{ slotProps.data.product?.code }}</div>
                </div>
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
            <Column field="suggested_quantity" header="Suggested Qty" style="min-width: 120px">
              <template #body="slotProps">
                <span class="font-semibold text-blue-600">{{ slotProps.data.suggested_quantity }}</span>
              </template>
            </Column>
            <Column field="current_stock" header="Current Stock" style="min-width: 120px">
              <template #body="slotProps">
                {{ slotProps.data.current_stock || 0 }}
              </template>
            </Column>
            <Column field="reorder_point" header="Reorder Point" style="min-width: 120px">
              <template #body="slotProps">
                {{ slotProps.data.reorder_point || 0 }}
              </template>
            </Column>
            <Column field="priority" header="Priority" style="min-width: 100px">
              <template #body="slotProps">
                <Tag
                  :value="slotProps.data.priority"
                  :severity="getPrioritySeverity(slotProps.data.priority)"
                  class="capitalize"
                />
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
            <Column field="generated_at" header="Generated" style="min-width: 140px">
              <template #body="slotProps">
                {{ formatDateTime(slotProps.data.generated_at) }}
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
                    @click="viewReorderSuggestion(slotProps.data)"
                  />
                  <Button
                    icon="pi pi-check"
                    severity="success"
                    outlined
                    v-tooltip.top="'Approve & Order'"
                    @click="approveSuggestion(slotProps.data)"
                    :disabled="slotProps.data.status !== 'pending'"
                  />
                  <Button
                    icon="pi pi-times"
                    severity="danger"
                    outlined
                    v-tooltip.top="'Reject'"
                    @click="rejectSuggestion(slotProps.data)"
                    :disabled="slotProps.data.status !== 'pending'"
                  />
                  <Button
                    icon="pi pi-trash"
                    severity="danger"
                    outlined
                    v-tooltip.top="'Delete'"
                    @click="confirmDelete(slotProps.data)"
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
          <span>Are you sure you want to delete this reorder suggestion? This action cannot be undone.</span>
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
            @click="deleteReorderSuggestion"
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
const selectedReorderSuggestion = ref<any>(null)
const reorderSuggestions = ref<any[]>([])
const warehouses = ref<any[]>([])
const totalRecords = ref(0)
const perPage = ref(10)
const toast = useToast()
const router = useRouter()

const filters = reactive({
  search: '',
  status: null as string | null,
  priority: null as string | null,
  warehouse_id: null as number | null,
  date_range: null as Date[] | null
})

const statusOptions = [
  { label: 'Pending', value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Ordered', value: 'ordered' }
]

const priorityOptions = [
  { label: 'Low', value: 'low' },
  { label: 'Medium', value: 'medium' },
  { label: 'High', value: 'high' },
  { label: 'Critical', value: 'critical' }
]

const loadReorderSuggestions = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page,
      per_page: perPage.value,
      search: filters.search || undefined,
      status: filters.status || undefined,
      priority: filters.priority || undefined,
      warehouse_id: filters.warehouse_id || undefined,
      start_date: filters.date_range?.[0]?.toISOString().split('T')[0] || undefined,
      end_date: filters.date_range?.[1]?.toISOString().split('T')[0] || undefined
    }

    const response = await inventoryService.getReorderSuggestions(params)

    if (response.success) {
      reorderSuggestions.value = response.data || []
      totalRecords.value = response.meta?.total || 0
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to load reorder suggestions',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load reorder suggestions',
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
  loadReorderSuggestions(1)
}

const onPage = (event: any) => {
  loadReorderSuggestions(event.page + 1)
}

const onSort = (event: any) => {
  // Handle sorting if needed
  loadReorderSuggestions(1)
}

const generateSuggestions = () => {
  // This would trigger the generation of new reorder suggestions
  toast.add({
    severity: 'info',
    summary: 'Info',
    detail: 'Generating reorder suggestions...',
    life: 3000
  })
  // In a real implementation, this would call an API endpoint
  setTimeout(() => {
    loadReorderSuggestions()
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Reorder suggestions generated successfully',
      life: 3000
    })
  }, 2000)
}

const viewReorderSuggestion = (reorderSuggestion: any) => {
  router.push({ name: 'inventory.reorder-suggestions.detail', params: { id: reorderSuggestion.id } })
}

const approveSuggestion = async (reorderSuggestion: any) => {
  try {
    const response = await inventoryService.approveReorderSuggestion(reorderSuggestion.id)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Reorder suggestion approved and order created',
        life: 3000
      })
      loadReorderSuggestions()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to approve reorder suggestion',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to approve reorder suggestion',
      life: 3000
    })
  }
}

const rejectSuggestion = async (reorderSuggestion: any) => {
  try {
    const response = await inventoryService.rejectReorderSuggestion(reorderSuggestion.id)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Reorder suggestion rejected',
        life: 3000
      })
      loadReorderSuggestions()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to reject reorder suggestion',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to reject reorder suggestion',
      life: 3000
    })
  }
}

const confirmDelete = (reorderSuggestion: any) => {
  selectedReorderSuggestion.value = reorderSuggestion
  deleteDialog.value = true
}

const deleteReorderSuggestion = async () => {
  if (!selectedReorderSuggestion.value) return

  deleting.value = true
  try {
    const response = await inventoryService.deleteReorderSuggestion(selectedReorderSuggestion.value.id)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Reorder suggestion deleted successfully',
        life: 3000
      })
      deleteDialog.value = false
      selectedReorderSuggestion.value = null
      loadReorderSuggestions()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to delete reorder suggestion',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to delete reorder suggestion',
      life: 3000
    })
  } finally {
    deleting.value = false
  }
}

const formatDateTime = (dateTime: string) => {
  if (!dateTime) return '-'
  return new Date(dateTime).toLocaleString()
}

const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'pending': return 'warning'
    case 'approved': return 'success'
    case 'rejected': return 'danger'
    case 'ordered': return 'info'
    default: return 'secondary'
  }
}

const getPrioritySeverity = (priority: string) => {
  switch (priority) {
    case 'low': return 'secondary'
    case 'medium': return 'info'
    case 'high': return 'warning'
    case 'critical': return 'danger'
    default: return 'secondary'
  }
}

onMounted(() => {
  loadReorderSuggestions()
  loadWarehouses()
})
</script>