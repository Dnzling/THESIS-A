<template>
  <div class="p-6  min-h-screen">
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Products</h1>
      </div>
      <div class="flex items-center gap-2">
        <Button @click="$router.push({ name: 'merchandising.products.logs' })" icon="pi pi-history" label="Logs"
          severity="info" outlined />
        <Button @click="$router.push({ name: 'merchandising.products.create' })" icon="pi pi-plus" label="Add Product"
          severity="success" />
      </div>
    </div>
  
    <!-- Filters Card -->
    <Card class="mb-6">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText v-model="filters.search" placeholder="Search products" class="w-full" @input="onFilterChange" />
          </IconField>
  
          <Select v-model="filters.category_id" :options="categories" optionLabel="category_name" optionValue="id"
            placeholder="All Categories" class="w-full" showClear @change="onFilterChange" />
  
          <Select v-model="filters.is_active" :options="activeStatuses" optionLabel="label" optionValue="value"
            placeholder="Status" class="w-full" showClear @change="onFilterChange" />
        </div>
  
        <div class="mt-4 flex gap-2">
          <Button v-if="selectedProducts.length > 0" @click="bulkActivate" icon="pi pi-check" label="Activate"
            severity="success" size="small" />
          <Button v-if="selectedProducts.length > 0" @click="bulkDeactivate" icon="pi pi-times" label="Deactivate"
            severity="warning" size="small" />
          <Button @click="resetFilters" icon="pi pi-filter-slash" label="Reset" severity="secondary" size="small"
            outlined />
        </div>
      </template>
    </Card>
  
    <!-- Products Table -->
    <Card>
      <template #content>
        <DataTable v-model:selection="selectedProducts" :value="products" :loading="loading" paginator :rows="15"
          :totalRecords="totalRecords" :lazy="true" @page="onPage" @sort="onSort" dataKey="id"
          :rowsPerPageOptions="[15, 25, 50]" currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          class="p-datatable-sm" stripedRows>
  
          <template #empty>
            <div class="text-center py-8">
              <i class="pi pi-inbox text-4xl text-gray-400"></i>
              <p class="text-gray-600 mt-2">No products found</p>
            </div>
          </template>
  
          <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
  
          <Column field="sku" header="SKU" sortable>
            <template #body="{ data }">
              <span class="font-mono text-sm">{{ data.sku }}</span>
            </template>
          </Column>
  
          <Column field="product_name" header="Product Name" sortable>
            <template #body="{ data }">
              <div class="flex items-center gap-3">
                <div>
                  <p class="font-medium text-gray-900">{{ data.product_name }}</p>
                  <p class="text-sm text-gray-500">{{ data.brand }}</p>
                </div>
              </div>
            </template>
          </Column>
  
          <Column field="category.category_name" header="Category"></Column>
  
          <Column field="base_price" header="Price" sortable>
            <template #body="{ data }">
              <div>
                <p v-if="data.discounted_price" class="font-semibold text-red-600">PHP {{ formatPrice(data.discounted_price) }}</p>
                <p :class="data.discounted_price ? 'text-sm text-gray-500 line-through' : 'font-semibold text-gray-900'">
                  PHP {{ formatPrice(data.base_price) }}
                </p>
              </div>
            </template>
          </Column>
  
          <Column field="is_active" header="Status">
            <template #body="{ data }">
              <Tag :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'secondary'" />
            </template>
          </Column>

          <Column header="Price Approval">
            <template #body="{ data }">
              <div class="space-y-1">
                <Tag :value="priceApprovalLabel(data.price_approval_status)" :severity="priceApprovalSeverity(data.price_approval_status)" />
                <small v-if="data.price_approval_status === 'pending'" class="block text-xs text-gray-500">
                  Pending:
                  <span v-if="data.pending_base_price != null">PHP {{ formatPrice(data.pending_base_price) }}</span>
                  <span v-if="data.pending_discounted_price != null"> / PHP {{ formatPrice(data.pending_discounted_price) }}</span>
                </small>
              </div>
            </template>
          </Column>
  
          <Column header="Variations">
            <template #body="{ data }">
              <Badge :value="data.variations_count || 0" severity="info" />
            </template>
          </Column>
  
          <Column header="Actions" :frozen="true" alignFrozen="right">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button icon="pi pi-eye" severity="info" text rounded v-tooltip.top="'View Details'"
                  @click="viewProduct(data.id)" />
                <Button
                  v-if="authStore.hasPermission('merchandising.variations.create')"
                  icon="pi pi-sparkles"
                  severity="info"
                  text
                  rounded
                  v-tooltip.top="'Add Variant'"
                  @click="addVariant(data.id)"
                />
                <Button v-if="authStore.hasPermission('merchandising.products.update')" icon="pi pi-pencil"
                  severity="warning" text rounded v-tooltip.top="'Edit'" @click="editProduct(data.id)" />
                <Button v-if="authStore.hasPermission('merchandising.products.delete')" icon="pi pi-trash"
                  severity="danger" text rounded v-tooltip.top="'Delete'" @click="confirmDelete(data)" />
                <Button
                  v-if="canApprovePricing() && data.price_approval_status === 'pending'"
                  icon="pi pi-check"
                  severity="success"
                  text
                  rounded
                  v-tooltip.top="'Approve Price'"
                  @click="approvePrice(data)"
                />
                <Button
                  v-if="canApprovePricing() && data.price_approval_status === 'pending'"
                  icon="pi pi-times"
                  severity="danger"
                  text
                  rounded
                  v-tooltip.top="'Reject Price'"
                  @click="rejectPrice(data)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  
    <!-- Delete Confirmation -->
    <Dialog v-model:visible="deleteDialogVisible" header="Confirm Delete" :modal="true" class="w-96">
      <div class="flex items-center gap-3">
        <i class="pi pi-exclamation-triangle text-4xl text-red-600"></i>
        <div>
          <p class="font-semibold">Are you sure you want to delete this product?</p>
          <p class="text-sm text-gray-600 mt-1">This action cannot be undone.</p>
        </div>
      </div>
      <template #footer>
        <Button @click="deleteDialogVisible = false" label="Cancel" severity="secondary" text />
        <Button @click="deleteProduct" label="Delete" severity="danger" :loading="deleting" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import merchandisingService from '../../../../services/merchandising.service'
import { useAuthStore } from '../../../../stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()
const toast = useToast()

// State
const products = ref([])
const categories = ref([])
const tags = ref([])
const attributes = ref([])
const selectedProducts = ref([])
const loading = ref(false)
const totalRecords = ref(0)
const dialogVisible = ref(false)
const deleteDialogVisible = ref(false)
const deleting = ref(false)
const currentProduct = ref(null)

const filters = reactive({
  search: '',
  category_id: null,
  product_type: 'finished_good',
  is_active: null,
  page: 1,
  per_page: 15,
  sort_by: 'created_at',
  sort_order: 'desc'
})
const activeStatuses = [
  { label: 'Active', value: true },
  { label: 'Inactive', value: false }
]

// Methods
const loadProducts = async () => {
  loading.value = true
  try {
    const response = await merchandisingService.getProducts(filters)
    products.value = response.data?.data || response.data?.data?.data || []
    totalRecords.value = response.data?.total || response.data?.data?.total || products.value.length
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load products', life: 3000 })
  } finally {
    loading.value = false
  }
}

const loadCategories = async () => {
  try {
    const response = await merchandisingService.getCategories({ active_only: true })
    categories.value = response.data?.data || response.data?.data?.data || []
  } catch (error) {
    console.error('Failed to load categories')
  }
}

const loadTags = async () => {
  try {
    const response = await merchandisingService.getTags({ active_only: true })
    tags.value = response.data?.data || response.data?.data?.data || []
  } catch (error) {
    console.error('Failed to load tags')
  }
}

const loadAttributes = async () => {
  try {
    const response = await merchandisingService.getAttributes({ filterable_only: true })
    attributes.value = response.data?.data || response.data?.data?.data || []
  } catch (error) {
    console.error('Failed to load attributes')
  }
}

const onPage = (event: any) => {
  filters.page = event.page + 1
  filters.per_page = event.rows
  loadProducts()
}

const onSort = (event: any) => {
  filters.sort_by = event.sortField
  filters.sort_order = event.sortOrder === 1 ? 'asc' : 'desc'
  loadProducts()
}

const onFilterChange = () => {
  filters.page = 1
  loadProducts()
}

const resetFilters = () => {
  filters.search = ''
  filters.category_id = null
  filters.product_type = 'finished_good'
  filters.is_active = null
  loadProducts()
}


// Add these methods to your script setup

const viewProduct = (productId: number) => {
  router.push({
    name: 'merchandising.products.view',
    params: { id: productId }
  })
}

const editProduct = (productId: number) => {
  router.push({
    name: 'merchandising.products.edit',
    params: { id: productId }
  })
}

const addVariant = (productId: number) => {
  router.push({
    name: 'merchandising.variations.create',
    query: { product_id: String(productId) }
  })
}

const canApprovePricing = () => {
  return authStore.hasPermission('finance.all.approve')
    || authStore.hasPermission('finance.settings.approve.store')
    || authStore.hasPermission('finance.settings.approve.all')
    || authStore.hasPermission('finance.purchase-orders.approve')
}

const priceApprovalLabel = (status?: string) => {
  if (status === 'pending') return 'Pending Finance'
  if (status === 'rejected') return 'Rejected'
  return 'Approved'
}

const priceApprovalSeverity = (status?: string) => {
  if (status === 'pending') return 'warning'
  if (status === 'rejected') return 'danger'
  return 'success'
}

const approvePrice = async (product: any) => {
  try {
    await merchandisingService.approveProductPrice(product.id)
    toast.add({ severity: 'success', summary: 'Approved', detail: 'Price change approved.', life: 3000 })
    loadProducts()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to approve price change',
      life: 3000
    })
  }
}

const rejectPrice = async (product: any) => {
  const reason = window.prompt('Enter rejection reason:')
  if (!reason) return

  try {
    await merchandisingService.rejectProductPrice(product.id, reason)
    toast.add({ severity: 'success', summary: 'Rejected', detail: 'Price change rejected.', life: 3000 })
    loadProducts()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to reject price change',
      life: 3000
    })
  }
}

const confirmDelete = (product: any) => {
  currentProduct.value = product
  deleteDialogVisible.value = true
}

const deleteProduct = async () => {
  deleting.value = true
  try {
    await merchandisingService.deleteProduct(currentProduct.value.id)
    toast.add({ severity: 'success', summary: 'Success', detail: 'Product deleted', life: 3000 })
    deleteDialogVisible.value = false
    loadProducts()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to delete product', life: 3000 })
  } finally {
    deleting.value = false
  }
}

const bulkActivate = async () => {
  try {
    const ids = selectedProducts.value.map((p: any) => p.id)
    await merchandisingService.bulkStatusUpdate(ids, true)
    toast.add({ severity: 'success', summary: 'Success', detail: 'Products activated', life: 3000 })
    selectedProducts.value = []
    loadProducts()
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to activate products', life: 3000 })
  }
}

const bulkDeactivate = async () => {
  try {
    const ids = selectedProducts.value.map((p: any) => p.id)
    await merchandisingService.bulkStatusUpdate(ids, false)
    toast.add({ severity: 'success', summary: 'Success', detail: 'Products deactivated', life: 3000 })
    selectedProducts.value = []
    loadProducts()
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to deactivate products', life: 3000 })
  }
}

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2 }).format(price)
}

onMounted(() => {
  loadProducts()
  loadCategories()
  loadTags()
  loadAttributes()
})
</script>
