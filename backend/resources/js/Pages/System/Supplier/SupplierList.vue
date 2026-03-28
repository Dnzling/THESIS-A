<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'procurement.suppliers' })" />
        <div>
          <h2 class="text-2xl font-bold text-gray-800">Suppliers</h2>
          <p class="text-sm text-gray-500 mt-1">Manage supplier relationships and track performance</p>
        </div>
      </div>
      <Button icon="pi pi-plus" label="New Supplier" @click="showSupplierForm = true" class="px-4 py-2" />
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card class="border-l-4 border-blue-500">
        <template #content>
          <div class="text-center">
            <div class="text-3xl font-bold text-blue-600">{{ suppliers.length }}</div>
            <p class="text-sm text-gray-600 mt-1">Total Suppliers</p>
          </div>
        </template>
      </Card>

      <Card class="border-l-4 border-green-500">
        <template #content>
          <div class="text-center">
            <div class="text-3xl font-bold text-green-600">{{ activeSupplierCount }}</div>
            <p class="text-sm text-gray-600 mt-1">Active</p>
          </div>
        </template>
      </Card>

      <Card class="border-l-4 border-yellow-500">
        <template #content>
          <div class="text-center">
            <div class="text-3xl font-bold text-yellow-600">{{ inactiveSupplierCount }}</div>
            <p class="text-sm text-gray-600 mt-1">Inactive</p>
          </div>
        </template>
      </Card>

      <Card class="border-l-4 border-red-500">
        <template #content>
          <div class="text-center">
            <div class="text-3xl font-bold text-red-600">{{ blacklistedSupplierCount }}</div>
            <p class="text-sm text-gray-600 mt-1">Blacklisted</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- Filters -->
    <Card>
      <template #header>
        <div class="flex items-center gap-2">
          <i class="pi pi-filter"></i>
          <span>Filters & Search</span>
        </div>
      </template>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Search -->
          <div>
            <label class="text-sm font-semibold text-gray-700 block mb-2">Search</label>
            <InputText
              v-model="filters.search"
              placeholder="Supplier name..."
              class="w-full"
              @input="onSearch"
            />
          </div>

          <!-- Status Filter -->
          <div>
            <label class="text-sm font-semibold text-gray-700 block mb-2">Status</label>
            <Select
              v-model="filters.status"
              :options="statusOptions"
              option-label="label"
              option-value="value"
              placeholder="All statuses"
              class="w-full"
              @change="fetchSuppliers"
            />
          </div>

          <!-- Category Filter -->
          <div>
            <label class="text-sm font-semibold text-gray-700 block mb-2">Category</label>
            <Select
              v-model="filters.category"
              :options="categoryOptions"
              option-label="label"
              option-value="value"
              placeholder="All categories"
              class="w-full"
              @change="fetchSuppliers"
            />
          </div>

          <!-- Sort -->
          <div>
            <label class="text-sm font-semibold text-gray-700 block mb-2">Sort By</label>
            <Select
              v-model="filters.sort_by"
              :options="sortOptions"
              option-label="label"
              option-value="value"
              placeholder="Sort by..."
              class="w-full"
              @change="fetchSuppliers"
            />
          </div>
        </div>
      </template>
    </Card>

    <!-- Suppliers Table -->
    <Card>
      <template #header>
        <div class="flex items-center gap-2">
          <i class="pi pi-building"></i>
          <span>Supplier List</span>
        </div>
      </template>
      <template #content>
        <DataTable
          :value="suppliers"
          :loading="loading"
          :paginator="true"
          :rows="15"
          :total-records="suppliers.length"
          responsive-layout="scroll"
          class="w-full"
        >
          <Column field="supplier_name" header="Supplier Name" class="w-32">
            <template #body="{ data }">
              <div class="font-semibold text-gray-800">{{ data.supplier_name }}</div>
              <div class="text-xs text-gray-500">{{ data.company_name }}</div>
            </template>
          </Column>

          <Column field="contact_person" header="Contact" class="w-28">
            <template #body="{ data }">
              <div class="text-sm">
                <div>{{ data.contact_person }}</div>
                <div class="text-xs text-gray-500">{{ data.email }}</div>
              </div>
            </template>
          </Column>

          <Column field="category" header="Category" class="w-20">
            <template #body="{ data }">
              <Tag
                :value="data.category"
                :severity="getCategoryColor(data.category)"
                class="capitalize"
              />
            </template>
          </Column>

          <Column field="status" header="Status" class="w-20">
            <template #body="{ data }">
              <Tag :value="data.status" :severity="getSupplierStatusColor(data.status)" class="capitalize" />
            </template>
          </Column>

          <Column field="rating" header="Rating" class="w-16">
            <template #body="{ data }">
              <Rating v-model="data.rating" :cancel="false" :readonly="true" />
            </template>
          </Column>

          <Column field="quality_score" header="Quality" class="w-16">
            <template #body="{ data }">
              <ProgressBar :value="data.quality_score * 20" :show-value="false" class="h-2" />
              <span class="text-xs text-gray-600">{{ data.quality_score }}/5</span>
            </template>
          </Column>

          <Column header="Actions" :exportable="false" class="w-20">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button
                  icon="pi pi-eye"
                  text
                  rounded
                  severity="info"
                  @click="viewSupplier(data.id)"
                  v-tooltip="'View'"
                />
                <Button
                  icon="pi pi-pencil"
                  text
                  rounded
                  severity="warning"
                  @click="editSupplier(data)"
                  v-tooltip="'Edit'"
                />
                <Button
                  icon="pi pi-trash"
                  text
                  rounded
                  severity="danger"
                  @click="confirmDelete(data.id)"
                  v-tooltip="'Delete'"
                />
              </div>
            </template>
          </Column>

          <template #empty>
            <div class="text-center py-8">
              <i class="pi pi-inbox text-4xl text-gray-300 mb-4 block"></i>
              <p class="text-gray-500">No suppliers found</p>
            </div>
          </template>
        </DataTable>
      </template>
    </Card>

    <!-- Supplier Form Dialog -->
    <Dialog v-model:visible="showSupplierForm" :header="formMode === 'create' ? 'New Supplier' : 'Edit Supplier'" :modal="true" class="w-full md:w-3/4">
      <SupplierForm
        :initial-data="selectedSupplier"
        :mode="formMode"
        @save="onSupplierSave"
        @close="showSupplierForm = false"
      />
    </Dialog>

    <!-- Confirm Delete Dialog -->
    <Dialog v-model:visible="showDeleteConfirm" header="Confirm Delete" :modal="true" class="w-full md:w-1/3">
      <p>Are you sure you want to delete this supplier? This action cannot be undone.</p>
      <template #footer>
        <Button label="Cancel" icon="pi pi-times" @click="showDeleteConfirm = false" class="p-button-text" />
        <Button label="Delete" icon="pi pi-check" @click="confirmDeleteAction" severity="danger" />
      </template>
    </Dialog>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import SupplierForm from './SupplierForm.vue'
import supplierService from '../../../services/supplier.service'

const router = useRouter()
const toast = useToast()
const suppliers = ref<any[]>([])
const loading = ref(false)
const error = ref('')

const getSupplierStatusColor = (status: string): string => {
  switch (status) {
    case 'active':
      return 'success'
    case 'inactive':
      return 'warning'
    case 'blacklisted':
      return 'danger'
    default:
      return 'info'
  }
}

const activeSupplierCount = computed(() => suppliers.value.filter((s) => s.status === 'active').length)
const inactiveSupplierCount = computed(() => suppliers.value.filter((s) => s.status === 'inactive').length)
const blacklistedSupplierCount = computed(() => suppliers.value.filter((s) => s.status === 'blacklisted').length)

const filters = ref({
  search: '',
  status: 'all',
  category: '',
  sort_by: 'supplier_name'
})

const showSupplierForm = ref(false)
const showDeleteConfirm = ref(false)
const formMode = ref<'create' | 'edit'>('create')
const selectedSupplier = ref<any>(null)
const deleteTargetId = ref<number | null>(null)

const statusOptions = [
  { label: 'All Statuses', value: 'all' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Blacklisted', value: 'blacklisted' }
]

const categoryOptions = [
  { label: 'All Categories', value: '' },
  { label: 'Raw Materials', value: 'raw_materials' },
  { label: 'Furniture', value: 'furniture' },
  { label: 'Accessories', value: 'accessories' },
  { label: 'Services', value: 'services' }
]

const sortOptions = [
  { label: 'Supplier Name', value: 'supplier_name' },
  { label: 'Rating (High to Low)', value: 'rating' },
  { label: 'Quality Score', value: 'quality_score' },
  { label: 'Date Added', value: 'created_at' }
]

const getSuppliers = async (filterParams: any = {}) => {
  loading.value = true
  error.value = ''
  try {
    const response = await supplierService.getSuppliers(filterParams)
    suppliers.value = response.data || response
    return response
  } catch (err: any) {
    error.value = err.message || 'Failed to fetch suppliers'
    return null
  } finally {
    loading.value = false
  }
}

const deleteSupplier = async (id: number) => {
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

const fetchSuppliers = async () => {
  await getSuppliers(filters.value)
}

const onSearch = () => {
  fetchSuppliers()
}

const getCategoryColor = (category: string): string => {
  const colors: Record<string, string> = {
    raw_materials: 'info',
    furniture: 'success',
    accessories: 'warning',
    services: 'help'
  }
  return colors[category] || 'secondary'
}

const viewSupplier = (id: number) => {
  router.push({ name: 'supplier-detail', params: { id } })
}

const editSupplier = (supplier: any) => {
  selectedSupplier.value = { ...supplier }
  formMode.value = 'edit'
  showSupplierForm.value = true
}

const confirmDelete = (id: number) => {
  deleteTargetId.value = id
  showDeleteConfirm.value = true
}

const confirmDeleteAction = async () => {
  if (deleteTargetId.value) {
    const response = await deleteSupplier(deleteTargetId.value)
    if (response?.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Supplier deleted successfully',
        life: 3000
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response?.message || 'Failed to delete supplier',
        life: 3000
      })
    }
  }
  showDeleteConfirm.value = false
  deleteTargetId.value = null
}

const onSupplierSave = async () => {
  showSupplierForm.value = false
  await fetchSuppliers()
  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: `Supplier ${formMode.value === 'create' ? 'created' : 'updated'} successfully`,
    life: 3000
  })
}

onMounted(() => {
  fetchSuppliers()
})
</script>
