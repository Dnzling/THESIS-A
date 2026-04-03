<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-800">Locations</h1>
            <p class="text-gray-600 mt-1">Manage warehouse locations</p>
          </div>
          <Button
            label="Add Location"
            @click="createLocation"
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
                  placeholder="Search locations..."
                  class="w-full"
                  @input="onFilter"
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
            :value="locations"
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
            :globalFilterFields="['name', 'code', 'description']"
          >
            <Column field="name" header="Location Name" sortable style="min-width: 200px" />
            <Column field="code" header="Code" style="width: 100px" />
            <Column field="warehouse.name" header="Warehouse" style="min-width: 150px">
              <template #body="slotProps">
                <div class="text-sm">
                  <div class="font-medium">{{ slotProps.data.warehouse?.name }}</div>
                  <div class="text-gray-500">{{ slotProps.data.warehouse?.code }}</div>
                </div>
              </template>
            </Column>
            <Column field="type" header="Type" style="width: 120px">
              <template #body="slotProps">
                <Tag
                  :value="slotProps.data.type"
                  :severity="getTypeSeverity(slotProps.data.type)"
                  class="capitalize"
                />
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
            <Column field="products_count" header="Products" style="width: 100px" sortable>
              <template #body="slotProps">
                <span class="font-medium">{{ slotProps.data.products_count || 0 }}</span>
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
                    @click="viewLocation(slotProps.data)"
                    v-tooltip.top="'View Details'"
                  />
                  <Button
                    icon="pi pi-pencil"
                    severity="warning"
                    outlined
                    @click="editLocation(slotProps.data)"
                    v-tooltip.top="'Edit Location'"
                  />
                  <Button
                    icon="pi pi-trash"
                    severity="danger"
                    outlined
                    @click="confirmDelete(slotProps.data)"
                    v-tooltip.top="'Delete Location'"
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
        <p class="font-medium">Are you sure you want to delete this location?</p>
        <p class="text-sm text-gray-600 mt-1">
          Location: <strong>{{ selectedLocation?.name }}</strong>
        </p>
        <p v-if="selectedLocation?.products_count > 0" class="text-sm text-red-600 mt-2">
          Warning: This location contains {{ selectedLocation.products_count }} products.
          Deleting this location may affect inventory tracking.
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
        @click="deleteLocation"
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
const selectedLocation = ref<any>(null)
const locations = ref<any[]>([])
const warehouses = ref<any[]>([])
const totalRecords = ref(0)
const toast = useToast()
const router = useRouter()

const filters = reactive({
  search: '',
  warehouse_id: null as string | null,
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
  { label: 'Rack', value: 'rack' },
  { label: 'Shelf', value: 'shelf' },
  { label: 'Bin', value: 'bin' },
  { label: 'Floor', value: 'floor' },
  { label: 'Cold Storage', value: 'cold_storage' },
  { label: 'Secure', value: 'secure' }
]

const loadLocations = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getLocations({
      ...filters,
      search: filters.search || undefined,
      warehouse_id: filters.warehouse_id || undefined,
      status: filters.status || undefined,
      type: filters.type || undefined
    })

    if (response.success) {
      locations.value = response.data || []
      totalRecords.value = response.meta?.total || 0
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load locations',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadWarehouses = async () => {
  try {
    const response = await inventoryService.getWarehouses({ per_page: 1000 })

    if (response.success) {
      warehouses.value = response.data || []
    }
  } catch (error: any) {
    // Silently handle warehouse loading errors
  }
}

const onFilter = () => {
  filters.page = 1
  loadLocations()
}

const onPage = (event: any) => {
  filters.page = event.page + 1
  filters.per_page = event.rows
  loadLocations()
}

const onSort = (event: any) => {
  filters.sort_field = event.sortField
  filters.sort_direction = event.sortOrder === 1 ? 'asc' : 'desc'
  loadLocations()
}

const clearFilters = () => {
  filters.search = ''
  filters.warehouse_id = null
  filters.status = null
  filters.type = null
  filters.page = 1
  loadLocations()
}

const createLocation = () => {
  router.push({ name: 'inventory.locations.create' })
}

const viewLocation = (location: any) => {
  router.push({ name: 'inventory.locations.detail', params: { id: location.id } })
}

const editLocation = (location: any) => {
  router.push({ name: 'inventory.locations.edit', params: { id: location.id } })
}

const confirmDelete = (location: any) => {
  selectedLocation.value = location
  deleteDialog.value = true
}

const deleteLocation = async () => {
  if (!selectedLocation.value) return

  deleteLoading.value = true
  try {
    const response = await inventoryService.deleteLocation(selectedLocation.value.id)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Location deleted successfully',
        life: 3000
      })
      deleteDialog.value = false
      selectedLocation.value = null
      loadLocations()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to delete location',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to delete location',
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
    case 'rack': return 'primary'
    case 'shelf': return 'info'
    case 'bin': return 'success'
    case 'floor': return 'warning'
    case 'cold_storage': return 'danger'
    case 'secure': return 'danger'
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
  loadLocations()
})
</script>