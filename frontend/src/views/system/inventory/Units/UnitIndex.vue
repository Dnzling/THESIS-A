<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-800">Units</h1>
            <p class="text-gray-600 mt-1">Manage measurement units for products</p>
          </div>
          <Button
            label="Add Unit"
            @click="createUnit"
            class="bg-blue-600 hover:bg-blue-700"
          />
        </div>
      </div>

      <Card>
        <template #content>
          <!-- Filters -->
          <div class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <InputText
                  v-model="filters.search"
                  placeholder="Search units..."
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
            </div>
          </div>

          <!-- Data Table -->
          <DataTable
            :value="units"
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
            :globalFilterFields="['name', 'abbreviation', 'description']"
          >
            <Column field="name" header="Unit Name" sortable style="min-width: 150px" />
            <Column field="abbreviation" header="Abbrev." style="width: 100px" />
            <Column field="type" header="Type" style="width: 120px">
              <template #body="slotProps">
                <Tag
                  :value="slotProps.data.type"
                  :severity="getTypeSeverity(slotProps.data.type)"
                  class="capitalize"
                />
              </template>
            </Column>
            <Column field="description" header="Description" style="min-width: 200px">
              <template #body="slotProps">
                <span class="text-gray-600">{{ slotProps.data.description || 'No description' }}</span>
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
                    @click="viewUnit(slotProps.data)"
                    v-tooltip.top="'View Details'"
                  />
                  <Button
                    icon="pi pi-pencil"
                    severity="warning"
                    outlined
                    @click="editUnit(slotProps.data)"
                    v-tooltip.top="'Edit Unit'"
                  />
                  <Button
                    icon="pi pi-trash"
                    severity="danger"
                    outlined
                    @click="confirmDelete(slotProps.data)"
                    v-tooltip.top="'Delete Unit'"
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
        <p class="font-medium">Are you sure you want to delete this unit?</p>
        <p class="text-sm text-gray-600 mt-1">
          Unit: <strong>{{ selectedUnit?.name }}</strong> ({{ selectedUnit?.abbreviation }})
        </p>
        <p v-if="selectedUnit?.products_count > 0" class="text-sm text-red-600 mt-2">
          Warning: This unit is used by {{ selectedUnit.products_count }} products.
          Deleting this unit may affect those products.
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
        @click="deleteUnit"
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
const selectedUnit = ref<any>(null)
const units = ref<any[]>([])
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
  { label: 'Weight', value: 'weight' },
  { label: 'Volume', value: 'volume' },
  { label: 'Length', value: 'length' },
  { label: 'Area', value: 'area' },
  { label: 'Count', value: 'count' },
  { label: 'Other', value: 'other' }
]

const loadUnits = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getUnits({
      ...filters,
      search: filters.search || undefined,
      status: filters.status || undefined,
      type: filters.type || undefined
    })

    if (response.success) {
      units.value = response.data || []
      totalRecords.value = response.meta?.total || 0
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load units',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const onFilter = () => {
  filters.page = 1
  loadUnits()
}

const onPage = (event: any) => {
  filters.page = event.page + 1
  filters.per_page = event.rows
  loadUnits()
}

const onSort = (event: any) => {
  filters.sort_field = event.sortField
  filters.sort_direction = event.sortOrder === 1 ? 'asc' : 'desc'
  loadUnits()
}

const clearFilters = () => {
  filters.search = ''
  filters.status = null
  filters.type = null
  filters.page = 1
  loadUnits()
}

const createUnit = () => {
  router.push({ name: 'inventory.units.create' })
}

const viewUnit = (unit: any) => {
  router.push({ name: 'inventory.units.detail', params: { id: unit.id } })
}

const editUnit = (unit: any) => {
  router.push({ name: 'inventory.units.edit', params: { id: unit.id } })
}

const confirmDelete = (unit: any) => {
  selectedUnit.value = unit
  deleteDialog.value = true
}

const deleteUnit = async () => {
  if (!selectedUnit.value) return

  deleteLoading.value = true
  try {
    const response = await inventoryService.deleteUnit(selectedUnit.value.id)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Unit deleted successfully',
        life: 3000
      })
      deleteDialog.value = false
      selectedUnit.value = null
      loadUnits()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to delete unit',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to delete unit',
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
    case 'weight': return 'primary'
    case 'volume': return 'info'
    case 'length': return 'success'
    case 'area': return 'warning'
    case 'count': return 'danger'
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
  loadUnits()
})
</script>