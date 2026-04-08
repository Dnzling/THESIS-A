<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-800">Warehouses</h1>
            <p class="text-gray-600 mt-1">Manage warehouse locations</p>
          </div>
          <Button
            label="Add Warehouse"
            @click="createWarehouse"
            class="bg-blue-600 hover:bg-blue-700"
          />
        </div>
      </div>

      <Card>
        <template #content>
          <!-- Filters -->
          <div class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <InputText
                  v-model="filters.search"
                  placeholder="Search warehouses..."
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
            :value="warehouses"
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
            :globalFilterFields="['name', 'code', 'address', 'description']"
          >
            <Column field="name" header="Warehouse Name" sortable style="min-width: 200px" />
            <Column field="code" header="Code" style="width: 100px" />
            <Column field="type" header="Type" style="width: 120px">
              <template #body="slotProps">
                <Tag
                  :value="slotProps.data.type"
                  :severity="getTypeSeverity(slotProps.data.type)"
                  class="capitalize"
                />
              </template>
            </Column>
            <Column field="address" header="Address" style="min-width: 250px">
              <template #body="slotProps">
                <div class="text-sm">
                  <div>{{ slotProps.data.address }}</div>
                  <div class="text-gray-500">
                    {{ slotProps.data.city }}, {{ slotProps.data.state }} {{ slotProps.data.postal_code }}
                  </div>
                </div>
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
            <Column field="capacity" header="Capacity" style="width: 120px" sortable>
              <template #body="slotProps">
                <span v-if="slotProps.data.capacity">{{ slotProps.data.capacity.toLocaleString() }}</span>
                <span v-else class="text-gray-400">N/A</span>
              </template>
            </Column>
            <Column field="locations_count" header="Locations" style="width: 100px" sortable>
              <template #body="slotProps">
                <span class="font-medium">{{ slotProps.data.locations_count || 0 }}</span>
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
                    @click="viewWarehouse(slotProps.data)"
                    v-tooltip.top="'View Details'"
                  />
                  <Button
                    icon="pi pi-pencil"
                    severity="warning"
                    outlined
                    @click="editWarehouse(slotProps.data)"
                    v-tooltip.top="'Edit Warehouse'"
                  />
                  <Button
                    icon="pi pi-trash"
                    severity="danger"
                    outlined
                    @click="confirmDelete(slotProps.data)"
                    v-tooltip.top="'Delete Warehouse'"
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
        <p class="font-medium">Are you sure you want to delete this warehouse?</p>
        <p class="text-sm text-gray-600 mt-1">
          Warehouse: <strong>{{ selectedWarehouse?.name }}</strong>
        </p>
        <p v-if="selectedWarehouse?.locations_count > 0" class="text-sm text-red-600 mt-2">
          Warning: This warehouse has {{ selectedWarehouse.locations_count }} locations.
          Deleting this warehouse may affect inventory tracking.
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
        @click="deleteWarehouse"
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
const selectedWarehouse = ref<any>(null)
const warehouses = ref<any[]>([])
const totalRecords = ref(0)
const toast = useToast()
const router = useRouter()

const filters = reactive({
  search: '',
  status: null as string | null,
  type: null as string | null,
  page: 1,
  per_page: 10,
  sort_field: 'created_at',
  sort_direction: 'desc'
})

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' }
]

const typeOptions = [
  { label: 'Main', value: 'main' },
  { label: 'Branch', value: 'branch' },
  { label: 'Distribution', value: 'distribution' },
  { label: 'Storage', value: 'storage' },
  { label: 'Retail', value: 'retail' }
]

const loadWarehouses = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getWarehouses({
      ...filters,
      search: filters.search || undefined,
      status: filters.status || undefined,
      type: filters.type || undefined
    })

    if (response.success) {
      warehouses.value = response.data || []
      totalRecords.value = response.meta?.total || 0
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load warehouses',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const onFilter = () => {
  filters.page = 1
  loadWarehouses()
}

const onPage = (event: any) => {
  filters.page = event.page + 1
  filters.per_page = event.rows
  loadWarehouses()
}

const onSort = (event: any) => {
  filters.sort_field = event.sortField
  filters.sort_direction = event.sortOrder === 1 ? 'asc' : 'desc'
  loadWarehouses()
}

const clearFilters = () => {
  filters.search = ''
  filters.status = null
  filters.type = null
  filters.page = 1
  loadWarehouses()
}

const createWarehouse = () => {
  router.push({ name: 'inventory.warehouses.create' })
}

const viewWarehouse = (warehouse: any) => {
  router.push({ name: 'inventory.warehouses.detail', params: { id: warehouse.id } })
}

const editWarehouse = (warehouse: any) => {
  router.push({ name: 'inventory.warehouses.edit', params: { id: warehouse.id } })
}

const confirmDelete = (warehouse: any) => {
  selectedWarehouse.value = warehouse
  deleteDialog.value = true
}

const deleteWarehouse = async () => {
  if (!selectedWarehouse.value) return

  deleteLoading.value = true
  try {
    const response = await inventoryService.deleteWarehouse(selectedWarehouse.value.id)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Warehouse deleted successfully',
        life: 3000
      })
      deleteDialog.value = false
      selectedWarehouse.value = null
      loadWarehouses()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to delete warehouse',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to delete warehouse',
      life: 3000
    })
  } finally {
    deleteLoading.value = false
  }
}

const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'active': return 'success'
    case 'inactive': return 'warning'
    default: return 'secondary'
  }
}

const getTypeSeverity = (type: string) => {
  switch (type) {
    case 'main': return 'primary'
    case 'branch': return 'info'
    case 'distribution': return 'success'
    case 'storage': return 'warning'
    case 'retail': return 'danger'
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
  loadWarehouses()
})
</script>