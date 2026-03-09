<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-800">Categories</h1>
            <p class="text-gray-600 mt-1">Manage product categories</p>
          </div>
          <Button
            label="Add Category"
            @click="createCategory"
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
                  placeholder="Search categories..."
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Parent Category</label>
                <Select
                  v-model="filters.parent_id"
                  :options="parentCategories"
                  optionLabel="name"
                  optionValue="id"
                  placeholder="All Parents"
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
            :value="categories"
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
            :globalFilterFields="['name', 'description']"
          >
            <Column field="name" header="Name" sortable style="min-width: 200px">
              <template #body="slotProps">
                <div class="flex items-center gap-2">
                  <span v-if="slotProps.data.parent" class="text-gray-400">└─</span>
                  <span class="font-medium">{{ slotProps.data.name }}</span>
                </div>
              </template>
            </Column>
            <Column field="description" header="Description" style="min-width: 250px">
              <template #body="slotProps">
                <span class="text-gray-600">{{ slotProps.data.description || 'No description' }}</span>
              </template>
            </Column>
            <Column field="parent.name" header="Parent Category" style="min-width: 150px">
              <template #body="slotProps">
                <span v-if="slotProps.data.parent">{{ slotProps.data.parent.name }}</span>
                <span v-else class="text-gray-400">Root Category</span>
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
                    @click="viewCategory(slotProps.data)"
                    v-tooltip.top="'View Details'"
                  />
                  <Button
                    icon="pi pi-pencil"
                    severity="warning"
                    outlined
                    @click="editCategory(slotProps.data)"
                    v-tooltip.top="'Edit Category'"
                  />
                  <Button
                    icon="pi pi-trash"
                    severity="danger"
                    outlined
                    @click="confirmDelete(slotProps.data)"
                    v-tooltip.top="'Delete Category'"
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
        <p class="font-medium">Are you sure you want to delete this category?</p>
        <p class="text-sm text-gray-600 mt-1">
          Category: <strong>{{ selectedCategory?.name }}</strong>
        </p>
        <p v-if="selectedCategory?.products_count > 0" class="text-sm text-red-600 mt-2">
          Warning: This category has {{ selectedCategory.products_count }} products associated with it.
          Deleting this category may affect those products.
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
        @click="deleteCategory"
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
const selectedCategory = ref<any>(null)
const categories = ref<any[]>([])
const parentCategories = ref<any[]>([])
const totalRecords = ref(0)
const toast = useToast()
const router = useRouter()

const filters = reactive({
  search: '',
  status: null as string | null,
  parent_id: null as number | null,
  page: 1,
  per_page: 10,
  sort_field: 'created_at',
  sort_direction: 'desc'
})

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' }
]

const loadCategories = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getCategories({
      ...filters,
      search: filters.search || undefined,
      status: filters.status || undefined,
      parent_id: filters.parent_id || undefined
    })

    if (response.success) {
      categories.value = response.data || []
      totalRecords.value = response.meta?.total || 0
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load categories',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadParentCategories = async () => {
  try {
    const response = await inventoryService.getCategories({
      parent_only: true
    })
    if (response.success) {
      parentCategories.value = response.data || []
    }
  } catch (error) {
    console.error('Failed to load parent categories', error)
  }
}

const onFilter = () => {
  filters.page = 1
  loadCategories()
}

const onPage = (event: any) => {
  filters.page = event.page + 1
  filters.per_page = event.rows
  loadCategories()
}

const onSort = (event: any) => {
  filters.sort_field = event.sortField
  filters.sort_direction = event.sortOrder === 1 ? 'asc' : 'desc'
  loadCategories()
}

const clearFilters = () => {
  filters.search = ''
  filters.status = null
  filters.parent_id = null
  filters.page = 1
  loadCategories()
}

const createCategory = () => {
  router.push({ name: 'inventory.categories.create' })
}

const viewCategory = (category: any) => {
  router.push({ name: 'inventory.categories.detail', params: { id: category.id } })
}

const editCategory = (category: any) => {
  router.push({ name: 'inventory.categories.edit', params: { id: category.id } })
}

const confirmDelete = (category: any) => {
  selectedCategory.value = category
  deleteDialog.value = true
}

const deleteCategory = async () => {
  if (!selectedCategory.value) return

  deleteLoading.value = true
  try {
    const response = await inventoryService.deleteCategory(selectedCategory.value.id)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Category deleted successfully',
        life: 3000
      })
      deleteDialog.value = false
      selectedCategory.value = null
      loadCategories()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to delete category',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to delete category',
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

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

onMounted(() => {
  loadCategories()
  loadParentCategories()
})
</script>