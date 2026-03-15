<template>
  <div class="suppliers-list-container p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-3xl font-bold text-gray-800">Supplier Management</h1>
      <Button
        icon="pi pi-plus"
        label="New Supplier"
        class="px-4 py-2"
        @click="openCreateForm"
      />
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-blue-50 rounded-lg p-4 border-l-4 border-blue-500">
        <div class="text-sm text-gray-600 font-semibold mb-1">Total Suppliers</div>
        <div class="text-3xl font-bold text-blue-600">{{ suppliers.length }}</div>
      </div>
      <div class="bg-green-50 rounded-lg p-4 border-l-4 border-green-500">
        <div class="text-sm text-gray-600 font-semibold mb-1">Active</div>
        <div class="text-3xl font-bold text-green-600">{{ activeCount }}</div>
      </div>
      <div class="bg-yellow-50 rounded-lg p-4 border-l-4 border-yellow-500">
        <div class="text-sm text-gray-600 font-semibold mb-1">Inactive</div>
        <div class="text-3xl font-bold text-yellow-600">{{ inactiveCount }}</div>
      </div>
      <div class="bg-red-50 rounded-lg p-4 border-l-4 border-red-500">
        <div class="text-sm text-gray-600 font-semibold mb-1">Blacklisted</div>
        <div class="text-3xl font-bold text-red-600">{{ blacklistedCount }}</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg p-4 shadow-sm mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
        <InputText
          v-model="searchQuery"
          type="text"
          placeholder="Search by name, company..."
          class="w-full"
        />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
        <Select
          v-model="selectedStatus"
          :options="statusOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="All Statuses"
          class="w-full"
        />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
        <Select
          v-model="selectedCategory"
          :options="categoryOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="All Categories"
          class="w-full"
        />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
        <Select
          v-model="sortBy"
          :options="sortOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="Sort"
          class="w-full"
        />
      </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
      <DataTable
        v-if="!loading"
        :value="filteredSuppliers"
        :rows="25"
        paginator
        responsive-layout="scroll"
        class="p-0"
      >
        <Column field="supplier_name" header="Supplier Name" style="width: 20%">
          <template #body="{ data }">
            <div>
              <div class="font-semibold text-gray-800">{{ data.supplier_name }}</div>
              <div class="text-sm text-gray-500">{{ data.company_name }}</div>
            </div>
          </template>
        </Column>
        <Column field="contact_person" header="Contact" style="width: 20%">
          <template #body="{ data }">
            <div>
              <div class="font-semibold text-gray-800">{{ data.contact_person }}</div>
              <div class="text-sm text-gray-500">{{ data.email }}</div>
            </div>
          </template>
        </Column>
        <Column field="category" header="Category" style="width: 15%">
          <template #body="{ data }">
            <Tag :value="data.category" :severity="getCategorySeverity(data.category)" />
          </template>
        </Column>
        <Column field="status" header="Status" style="width: 12%">
          <template #body="{ data }">
            <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
          </template>
        </Column>
        <Column field="rating" header="Rating" style="width: 12%">
          <template #body="{ data }">
            <Rating v-model="data.rating" :cancel="false" read-only />
          </template>
        </Column>
        <Column field="quality_score" header="Quality" style="width: 12%">
          <template #body="{ data }">
            <ProgressBar
              :value="(data.quality_score / 5) * 100"
              :show-value="false"
              style="height: 0.5rem"
            />
          </template>
        </Column>
        <Column header="Actions" style="width: 15%">
          <template #body="{ data }">
            <div class="flex gap-2">
              <Button
                icon="pi pi-eye"
                class="p-button-rounded p-button-info"
                @click="viewSupplier(data)"
              />
              <Button
                icon="pi pi-pencil"
                class="p-button-rounded p-button-warning"
                @click="editSupplier(data)"
              />
              <Button
                icon="pi pi-trash"
                class="p-button-rounded p-button-danger"
                @click="deleteSupplier(data)"
              />
            </div>
          </template>
        </Column>
      </DataTable>
      <div v-else class="p-4 text-center">
        <ProgressSpinner />
      </div>
    </div>

    <!-- Create/Edit Dialog -->
    <Dialog
      v-model:visible="showFormDialog"
      :header="editingSupplier ? 'Edit Supplier' : 'Create Supplier'"
      :modal="true"
      :closable="true"
      style="width: 90vw; max-width: 600px"
    >
      <SupplierForm
        :supplier="editingSupplier"
        :mode="editingSupplier ? 'edit' : 'create'"
        @save="handleFormSave"
        @close="showFormDialog = false"
      />
    </Dialog>

    <!-- Delete Confirmation -->
    <ConfirmDialog />

    <!-- Toast Messages -->
    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import supplierService from '../../../services/supplier.service'
import SupplierForm from './SupplierForm.vue'

const router = useRouter()
const confirm = useConfirm()
const toast = useToast()
const suppliers = ref<any[]>([])
const loading = ref(false)
const error = ref('')

const getSuppliers = async (filters: any = {}) => {
  loading.value = true
  error.value = ''
  try {
    const response = await supplierService.getSuppliers(filters)
    suppliers.value = response.data || response
    return response
  } catch (err: any) {
    error.value = err.message || 'Failed to fetch suppliers'
    return null
  } finally {
    loading.value = false
  }
}

const searchSuppliers = async (query: string) => {
  try {
    const response = await supplierService.searchSuppliers(query)
    return response.data || []
  } catch (err: any) {
    error.value = err.message || 'Failed to search suppliers'
    return []
  }
}

const deleteSupplierService = async (id: number) => {
  loading.value = true
  error.value = ''
  try {
    const response = await supplierService.deleteSupplier(id)
    if (response?.success) {
      suppliers.value = suppliers.value.filter((s) => s.id !== id)
    }
    return response
  } catch (err: any) {
    error.value = err.message || 'Failed to delete supplier'
    return null
  } finally {
    loading.value = false
  }
}

const searchQuery = ref('')
const selectedStatus = ref(null)
const selectedCategory = ref(null)
const sortBy = ref('name')
const showFormDialog = ref(false)
const editingSupplier = ref(null)

const statusOptions = ref([
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Blacklisted', value: 'blacklisted' },
])

const categoryOptions = ref([
  { label: 'Raw Materials', value: 'Raw Materials' },
  { label: 'Furniture', value: 'Furniture' },
  { label: 'Accessories', value: 'Accessories' },
  { label: 'Services', value: 'Services' },
  { label: 'Equipment', value: 'Equipment' },
])

const sortOptions = ref([
  { label: 'Name', value: 'name' },
  { label: 'Rating', value: 'rating' },
  { label: 'Quality', value: 'quality' },
  { label: 'On-Time %', value: 'on_time' },
])

const activeCount = computed(() => suppliers.value.filter(s => s.status === 'active').length)
const inactiveCount = computed(() => suppliers.value.filter(s => s.status === 'inactive').length)
const blacklistedCount = computed(() => suppliers.value.filter(s => s.status === 'blacklisted').length)

const filteredSuppliers = computed(() => {
  let filtered = suppliers.value

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    filtered = filtered.filter(s =>
      s.supplier_name.toLowerCase().includes(q) ||
      s.company_name.toLowerCase().includes(q) ||
      s.email.toLowerCase().includes(q)
    )
  }

  if (selectedStatus.value) {
    filtered = filtered.filter(s => s.status === selectedStatus.value)
  }

  if (selectedCategory.value) {
    filtered = filtered.filter(s => s.category === selectedCategory.value)
  }

  // Sort
  if (sortBy.value === 'rating') {
    filtered.sort((a, b) => (b.rating || 0) - (a.rating || 0))
  } else if (sortBy.value === 'quality') {
    filtered.sort((a, b) => (b.quality_score || 0) - (a.quality_score || 0))
  } else if (sortBy.value === 'on_time') {
    filtered.sort((a, b) => (b.on_time_percentage || 0) - (a.on_time_percentage || 0))
  } else {
    filtered.sort((a, b) => a.supplier_name.localeCompare(b.supplier_name))
  }

  return filtered
})

const getCategorySeverity = (category: string) => {
  const severityMap: any = {
    'Raw Materials': 'info',
    'Furniture': 'success',
    'Accessories': 'warning',
    'Services': 'danger',
    'Equipment': 'secondary',
  }
  return severityMap[category] || 'info'
}

const getStatusSeverity = (status: string) => {
  const severityMap: any = {
    'active': 'success',
    'inactive': 'warning',
    'blacklisted': 'danger',
  }
  return severityMap[status] || 'info'
}

const openCreateForm = () => {
  editingSupplier.value = null
  showFormDialog.value = true
}

const viewSupplier = (supplier: any) => {
  router.push(`/suppliers/${supplier.id}`)
}

const editSupplier = (supplier: any) => {
  editingSupplier.value = { ...supplier }
  showFormDialog.value = true
}

const deleteSupplier = (supplier: any) => {
  confirm.require({
    message: `Are you sure you want to delete ${supplier.supplier_name}?`,
    header: 'Confirm Deletion',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await deleteSupplierService(supplier.id)
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Supplier deleted successfully',
          life: 3000,
        })
        await getSuppliers()
      } catch (error) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to delete supplier',
          life: 3000,
        })
      }
    },
  })
}

const handleFormSave = async () => {
  showFormDialog.value = false
  await getSuppliers()
  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: editingSupplier.value ? 'Supplier updated successfully' : 'Supplier created successfully',
    life: 3000,
  })
}

onMounted(() => {
  getSuppliers()
})
</script>

<style scoped>
.suppliers-list-container {
  background-color: #f8f9fa;
}
</style>
