<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Products</h1>
        <p class="text-gray-600 mt-1">Manage your product catalog</p>
      </div>
      <Button
        icon="pi pi-plus"
        label="Add Product"
        @click="createProduct"
        class="bg-blue-600 hover:bg-blue-700"
      />
    </div>

    <!-- Filters -->
    <Card class="mb-6">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText v-model="filters.search" placeholder="Search products" class="w-full" />
          </IconField>

          <Select
            v-model="filters.category_id"
            :options="categories"
            optionLabel="name"
            optionValue="id"
            placeholder="Category"
            class="w-full"
            showClear
          />

          <Select
            v-model="filters.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Status"
            class="w-full"
            showClear
          />

          <Button icon="pi pi-search" label="Search" @click="loadProducts" />
          <Button icon="pi pi-filter-slash" label="Reset" severity="secondary" @click="resetFilters" />
        </div>
      </template>
    </Card>

    <!-- Products Table -->
    <Card>
      <template #content>
        <DataTable
          :value="products"
          :loading="loading"
          paginator
          :rows="filters.per_page"
          :totalRecords="totalRecords"
          :lazy="true"
          @page="onPage"
          dataKey="id"
          :rowsPerPageOptions="[15, 25, 50]"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          class="p-datatable-sm"
          stripedRows
        >
          <template #empty>
            <div class="text-center py-8">
              <i class="pi pi-inbox text-4xl text-gray-400"></i>
              <p class="text-gray-600 mt-2">No products found</p>
            </div>
          </template>

          <Column field="sku" header="SKU" style="width: 12%">
            <template #body="{ data }">
              <span class="font-medium">{{ data.sku }}</span>
            </template>
          </Column>

          <Column field="name" header="Product Name" style="width: 25%">
            <template #body="{ data }">
              <div>
                <div class="font-medium">{{ data.name }}</div>
                <div class="text-sm text-gray-500">{{ data.description?.substring(0, 50) }}{{ data.description?.length > 50 ? '...' : '' }}</div>
              </div>
            </template>
          </Column>

          <Column field="category.name" header="Category" style="width: 15%">
            <template #body="{ data }">
              {{ data.category?.name || 'N/A' }}
            </template>
          </Column>

          <Column field="unit.name" header="Unit" style="width: 10%">
            <template #body="{ data }">
              {{ data.unit?.name || 'N/A' }}
            </template>
          </Column>

          <Column field="base_price" header="Price" style="width: 12%">
            <template #body="{ data }">
              ${{ data.base_price }}
            </template>
          </Column>

          <Column field="status" header="Status" style="width: 12%">
            <template #body="{ data }">
              <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
            </template>
          </Column>

          <Column header="Actions" style="width: 14%">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button icon="pi pi-eye" size="small" text severity="info" @click="viewProduct(data)" v-tooltip="'View details'" />
                <Button icon="pi pi-pencil" size="small" text severity="success" @click="editProduct(data)" v-tooltip="'Edit product'" />
                <Button icon="pi pi-trash" size="small" text severity="danger" @click="deleteProduct(data)" v-tooltip="'Delete product'" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'

const loading = ref(false)
const products = ref<any[]>([])
const categories = ref<any[]>([])
const totalRecords = ref(0)
const toast = useToast()
const router = useRouter()

const filters = reactive({
  search: '',
  category_id: null as number | null,
  status: null as string | null,
  page: 1,
  per_page: 15
})

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Discontinued', value: 'discontinued' }
]

const loadProducts = async () => {
  loading.value = true
  try {
    const params: any = {
      page: filters.page,
      per_page: filters.per_page
    }

    if (filters.search) params.search = filters.search
    if (filters.category_id) params.category_id = filters.category_id
    if (filters.status) params.status = filters.status

    const response = await inventoryService.getProducts(params)

    if (response.success && response.data) {
      products.value = response.data.data || []
      totalRecords.value = response.data.total || products.value.length
    } else {
      products.value = []
      totalRecords.value = 0
    }
  } catch (error: any) {
    console.error('Failed to load products', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load products',
      life: 3000
    })
    products.value = []
    totalRecords.value = 0
  } finally {
    loading.value = false
  }
}

const loadCategories = async () => {
  try {
    const response = await inventoryService.getCategories()
    if (response.success) {
      categories.value = response.data || []
    }
  } catch (error) {
    console.error('Failed to load categories', error)
  }
}

const onPage = (event: any) => {
  filters.page = event.page + 1
  filters.per_page = event.rows
  loadProducts()
}

const resetFilters = () => {
  filters.search = ''
  filters.category_id = null
  filters.status = null
  filters.page = 1
  filters.per_page = 15
  loadProducts()
}

const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'active': return 'success'
    case 'inactive': return 'warning'
    case 'discontinued': return 'danger'
    default: return 'info'
  }
}

const createProduct = () => {
  router.push({ name: 'inventory.products.create' })
}

const viewProduct = (product: any) => {
  router.push({ name: 'inventory.products.detail', params: { id: product.id } })
}

const editProduct = (product: any) => {
  router.push({ name: 'inventory.products.edit', params: { id: product.id } })
}

const deleteProduct = async (product: any) => {
  if (confirm(`Are you sure you want to delete "${product.name}"?`)) {
    try {
      await inventoryService.deleteProduct(product.id)
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Product deleted successfully',
        life: 3000
      })
      loadProducts()
    } catch (error: any) {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to delete product',
        life: 3000
      })
    }
  }
}

onMounted(() => {
  loadProducts()
  loadCategories()
})
</script>