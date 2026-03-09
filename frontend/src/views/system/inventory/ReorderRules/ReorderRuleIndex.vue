<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-800">Reorder Rules</h1>
            <p class="text-gray-600 mt-1">Manage automatic reorder rules for inventory items</p>
          </div>
          <Button
            label="New Reorder Rule"
            icon="pi pi-plus"
            @click="createReorderRule"
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
              <label class="block text-sm font-medium text-gray-700 mb-2">Trigger Type</label>
              <Select
                v-model="filters.trigger_type"
                :options="triggerTypeOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="All Types"
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
          </div>
        </template>
      </Card>

      <!-- Reorder Rules Table -->
      <Card>
        <template #content>
          <DataTable
            :value="reorderRules"
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
                       @click="viewReorderRule(slotProps.data)">
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
            <Column field="trigger_type" header="Trigger Type" style="min-width: 140px">
              <template #body="slotProps">
                <Tag
                  :value="slotProps.data.trigger_type"
                  :severity="getTriggerTypeSeverity(slotProps.data.trigger_type)"
                  class="capitalize"
                />
              </template>
            </Column>
            <Column field="min_stock_level" header="Min Stock" style="min-width: 120px">
              <template #body="slotProps">
                {{ slotProps.data.min_stock_level }}
              </template>
            </Column>
            <Column field="max_stock_level" header="Max Stock" style="min-width: 120px">
              <template #body="slotProps">
                {{ slotProps.data.max_stock_level }}
              </template>
            </Column>
            <Column field="reorder_quantity" header="Reorder Qty" style="min-width: 120px">
              <template #body="slotProps">
                {{ slotProps.data.reorder_quantity }}
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
            <Column field="last_triggered_at" header="Last Triggered" style="min-width: 140px">
              <template #body="slotProps">
                {{ formatDateTime(slotProps.data.last_triggered_at) }}
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
                    @click="viewReorderRule(slotProps.data)"
                  />
                  <Button
                    icon="pi pi-pencil"
                    severity="warning"
                    outlined
                    v-tooltip.top="'Edit'"
                    @click="editReorderRule(slotProps.data)"
                  />
                  <Button
                    icon="pi pi-power-off"
                    :severity="slotProps.data.status === 'active' ? 'danger' : 'success'"
                    outlined
                    v-tooltip.top="slotProps.data.status === 'active' ? 'Deactivate' : 'Activate'"
                    @click="toggleStatus(slotProps.data)"
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
          <span>Are you sure you want to delete this reorder rule? This action cannot be undone.</span>
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
            @click="deleteReorderRule"
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
const selectedReorderRule = ref<any>(null)
const reorderRules = ref<any[]>([])
const warehouses = ref<any[]>([])
const totalRecords = ref(0)
const perPage = ref(10)
const toast = useToast()
const router = useRouter()

const filters = reactive({
  search: '',
  status: null as string | null,
  trigger_type: null as string | null,
  warehouse_id: null as number | null
})

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' }
]

const triggerTypeOptions = [
  { label: 'Min Stock Level', value: 'min_stock' },
  { label: 'Max Stock Level', value: 'max_stock' },
  { label: 'Reorder Point', value: 'reorder_point' },
  { label: 'Demand Forecast', value: 'demand_forecast' }
]

const loadReorderRules = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page,
      per_page: perPage.value,
      search: filters.search || undefined,
      status: filters.status || undefined,
      trigger_type: filters.trigger_type || undefined,
      warehouse_id: filters.warehouse_id || undefined
    }

    const response = await inventoryService.getReorderRules(params)

    if (response.success) {
      reorderRules.value = response.data || []
      totalRecords.value = response.meta?.total || 0
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to load reorder rules',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load reorder rules',
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
  loadReorderRules(1)
}

const onPage = (event: any) => {
  loadReorderRules(event.page + 1)
}

const onSort = (event: any) => {
  // Handle sorting if needed
  loadReorderRules(1)
}

const createReorderRule = () => {
  router.push({ name: 'inventory.reorder-rules.create' })
}

const viewReorderRule = (reorderRule: any) => {
  router.push({ name: 'inventory.reorder-rules.detail', params: { id: reorderRule.id } })
}

const editReorderRule = (reorderRule: any) => {
  router.push({ name: 'inventory.reorder-rules.edit', params: { id: reorderRule.id } })
}

const toggleStatus = async (reorderRule: any) => {
  const newStatus = reorderRule.status === 'active' ? 'inactive' : 'active'

  try {
    const response = await inventoryService.updateReorderRule(reorderRule.id, { status: newStatus })

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `Reorder rule ${newStatus === 'active' ? 'activated' : 'deactivated'} successfully`,
        life: 3000
      })
      loadReorderRules()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to update reorder rule status',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to update reorder rule status',
      life: 3000
    })
  }
}

const confirmDelete = (reorderRule: any) => {
  selectedReorderRule.value = reorderRule
  deleteDialog.value = true
}

const deleteReorderRule = async () => {
  if (!selectedReorderRule.value) return

  deleting.value = true
  try {
    const response = await inventoryService.deleteReorderRule(selectedReorderRule.value.id)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Reorder rule deleted successfully',
        life: 3000
      })
      deleteDialog.value = false
      selectedReorderRule.value = null
      loadReorderRules()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to delete reorder rule',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to delete reorder rule',
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
    case 'active': return 'success'
    case 'inactive': return 'secondary'
    default: return 'secondary'
  }
}

const getTriggerTypeSeverity = (triggerType: string) => {
  switch (triggerType) {
    case 'min_stock': return 'warning'
    case 'max_stock': return 'info'
    case 'reorder_point': return 'success'
    case 'demand_forecast': return 'primary'
    default: return 'secondary'
  }
}

onMounted(() => {
  loadReorderRules()
  loadWarehouses()
})
</script>