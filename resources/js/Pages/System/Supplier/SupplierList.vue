<template>
  <div class="max-w-7xl mx-auto space-y-4 px-4 py-4 sm:px-6 lg:px-8 pb-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded size="small" aria-label="Go back" @click="router.back()" />
        <div>
          <h1 class="text-xl font-semibold text-slate-900">Suppliers</h1>
          <p class="text-sm text-slate-500 mt-1">Manage supplier relationships and performance</p>
        </div>
      </div>
      <Button icon="pi pi-plus" label="New Supplier" size="small" @click="showSupplierForm = true" />
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
      <Card class="border-l-4 border-blue-500">
        <template #content>
          <div class="px-4 py-3">
            <div class="text-2xl font-semibold text-slate-900">{{ suppliers.length }}</div>
            <p class="text-xs text-slate-500 mt-1">Total suppliers</p>
          </div>
        </template>
      </Card>

      <Card class="border-l-4 border-green-500">
        <template #content>
          <div class="px-4 py-3">
            <div class="text-2xl font-semibold text-slate-900">{{ activeSupplierCount }}</div>
            <p class="text-xs text-slate-500 mt-1">Active</p>
          </div>
        </template>
      </Card>

      <Card class="border-l-4 border-yellow-500">
        <template #content>
          <div class="px-4 py-3">
            <div class="text-2xl font-semibold text-slate-900">{{ inactiveSupplierCount }}</div>
            <p class="text-xs text-slate-500 mt-1">Inactive</p>
          </div>
        </template>
      </Card>

      <Card class="border-l-4 border-red-500">
        <template #content>
          <div class="px-4 py-3">
            <div class="text-2xl font-semibold text-slate-900">{{ blacklistedSupplierCount }}</div>
            <p class="text-xs text-slate-500 mt-1">Blacklisted</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- Filters -->
    <Card class="rounded-xl border border-slate-200 shadow-sm">
      <template #header>
        <div class="px-4 pt-4 flex items-center gap-2 text-sm font-semibold text-slate-800">
          <i class="pi pi-filter text-orange-600"></i>
          <span>Search &amp; Filters</span>
        </div>
      </template>
      <template #content>
        <div class="flex flex-wrap items-end gap-2 px-4 pb-4">
          <!-- Search -->
          <div class="w-full sm:w-64">
            <InputText
              v-model="filters.search"
              size="small"
              placeholder="Search suppliers"
              class="w-full text-sm"
              @input="onSearch"
            />
          </div>

          <!-- Status Filter -->
          <div class="w-full sm:w-44">
            <Select
              v-model="filters.status"
              :options="statusOptions"
              option-label="label"
              option-value="value"
              placeholder="All statuses"
              size="small"
              class="w-full text-sm"
              @change="fetchSuppliers"
            />
          </div>

          <!-- Category Filter -->
          <div class="w-full sm:w-44">
            <Select
              v-model="filters.category"
              :options="categoryOptions"
              option-label="label"
              option-value="value"
              placeholder="All categories"
              size="small"
              class="w-full text-sm"
              @change="fetchSuppliers"
            />
          </div>

          <!-- Sort -->
          <div class="w-full sm:w-48">
            <Select
              v-model="filters.sort_by"
              :options="sortOptions"
              option-label="label"
              option-value="value"
              placeholder="Sort by..."
              size="small"
              class="w-full text-sm"
              @change="fetchSuppliers"
            />
          </div>
          <Button v-if="hasActiveFilters" label="Clear Filter" icon="pi pi-filter-slash" severity="danger" size="small" @click="resetFilters" />
        </div>
      </template>
    </Card>

    <!-- Suppliers Table -->
    <Card class="rounded-xl border border-slate-200 shadow-sm">
      <template #header>
        <div class="px-4 pt-4 flex items-center gap-2 text-sm font-semibold text-slate-800">
          <i class="pi pi-building"></i>
          <span>Supplier List</span>
        </div>
      </template>
      <template #content>
        <DataTable
          :value="suppliers"
          v-if="!loading"
          :paginator="true"
          :rows="10"
          :rows-per-page-options="[10, 15, 25]"
          :total-records="suppliers.length"
          class="p-datatable-sm text-sm"
          row-hover
          responsive-layout="scroll"
        >
          <Column field="supplier_name" header="Supplier Name" sortable class="w-32">
            <template #body="{ data }">
              <div class="font-semibold text-gray-800">{{ data.supplier_name }}</div>
              <div class="text-xs text-gray-500">{{ data.company_name }}</div>
            </template>
          </Column>

          <Column field="contact_person" header="Contact" sortable class="w-28">
            <template #body="{ data }">
              <div class="text-sm">
                <div>{{ data.contact_person }}</div>
                <div class="text-xs text-gray-500">{{ data.email }}</div>
              </div>
            </template>
          </Column>

          <Column field="category" header="Category" sortable class="w-20">
            <template #body="{ data }">
              <Tag
                :value="formatCategory(data.category)"
                :severity="getCategoryColor(data.category)"
                class="capitalize"
              />
            </template>
          </Column>

          <Column field="status" header="Status" sortable class="w-20">
            <template #body="{ data }">
              <Badge :value="formatStatus(data.status)" :severity="getSupplierStatusColor(data.status)" />
            </template>
          </Column>

          <Column field="rating" header="Rating" sortable class="w-16">
            <template #body="{ data }">
              <Rating v-model="data.rating" :cancel="false" :readonly="true" />
            </template>
          </Column>

          <Column field="quality_score" header="Quality" sortable class="w-16">
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
                  outlined
                  rounded
                  size="small"
                  severity="secondary"
                  @click="viewSupplier(data.id)"
                  v-tooltip="'View'"
                />
                <Button
                  icon="pi pi-pencil"
                  outlined
                  rounded
                  size="small"
                  severity="warning"
                  @click="editSupplier(data)"
                  v-tooltip="'Edit'"
                />
                <Button
                  icon="pi pi-trash"
                  outlined
                  rounded
                  size="small"
                  severity="danger"
                  @click="confirmDelete(data.id)"
                  v-tooltip="'Delete'"
                />
              </div>
            </template>
          </Column>

        </DataTable>
        <div v-else class="space-y-2 p-4">
          <div v-for="row in 5" :key="row" class="grid grid-cols-4 gap-3">
            <Skeleton v-for="cell in 4" :key="cell" height="2rem" />
          </div>
        </div>
        <div v-if="error && !loading" class="mx-4 mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
          {{ error }}
        </div>
        <div v-if="!loading && suppliers.length === 0" class="px-4 pb-6 text-center text-sm text-slate-500">
          No suppliers found. Try changing your filters or add a supplier.
        </div>
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
import Badge from 'primevue/badge'
import Skeleton from 'primevue/skeleton'

const router = useRouter()
const toast = useToast()
const suppliers = ref<any[]>([])
const loading = ref(true)
const error = ref('')

const getSupplierStatusColor = (status: string): string => {
  switch (status) {
    case 'active':
      return 'info'
    case 'inactive':
      return 'secondary'
    case 'blacklisted':
      return 'danger'
    default:
      return 'info'
  }
}

const formatStatus = (status: string) => status ? status.replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase()) : 'Unknown'

const activeSupplierCount = computed(() => suppliers.value.filter((s) => s.status === 'active').length)
const inactiveSupplierCount = computed(() => suppliers.value.filter((s) => s.status === 'inactive').length)
const blacklistedSupplierCount = computed(() => suppliers.value.filter((s) => s.status === 'blacklisted').length)

const filters = ref({
  search: '',
  status: 'all',
  category: '',
  sort_by: 'supplier_name'
})
const hasActiveFilters = computed(() => Boolean(filters.value.search.trim() || filters.value.status !== 'all' || filters.value.category))

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

const resetFilters = () => {
  filters.value = { search: '', status: 'all', category: '', sort_by: 'supplier_name' }
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

const formatCategory = (category: string) => category ? category.replace(/_/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase()) : 'Uncategorized'

const formatStatus = (status: string) => status ? status.replace(/_/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase()) : 'Unknown'

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
