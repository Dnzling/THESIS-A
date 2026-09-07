<template>
  <div class="min-h-screen p-4">
    <!-- Header -->
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-4 ">
      <div>
        <h1 class="text-lg font-bold text-gray-900">Items</h1>
      </div>
      <div class="flex items-center gap-2">
        <Button
          label="Create Item"
          size="small"
          @click="router.push({ name: 'inventory.products.create' })"
        />
      </div>
    </div>
  
    <Card class="border border-gray-200 shadow-sm">
      <template #content>
        <!-- Filters -->
        <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-6 mb-4">
          <div class="md:col-span-2">
            <IconField class="w-full">
              <InputIcon class="pi pi-search" />
              <InputText
                v-model="filters.search"
                placeholder="Search by item name or SKU"
                class="w-full text-sm"
                size="small"
                @input="onFilterChange"
              />
            </IconField>
          </div>

          <Select
            v-model="filters.category_id"
            :options="categories"
            optionLabel="category_name"
            optionValue="id"
            placeholder="All Categories"
            class="w-full text-sm"
            size="small"
            showClear
            @change="onFilterChange"
          />

          <Select
            v-model="filters.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Status"
            class="w-full text-sm"
            size="small"
            showClear
            @change="onFilterChange"
          />

          <Select
            v-model="filters.product_type"
            :options="productTypeOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Type"
            class="w-full text-sm"
            size="small"
            showClear
            @change="onFilterChange"
          />

          <div v-if="hasActiveFilters" class="flex justify-end">
            <Button
              @click="resetFilters"
              label="Clear All"
              severity="danger"
              size="small"
              class="text-sm"
            />
          </div>
        </div>

        <div class="m-4 flex flex-wrap gap-2 border-t border-gray-100 p-4">
          <Button v-if="selectedProducts.length > 0" @click="bulkActivate" icon="pi pi-check" label="Activate"
            severity="warn" size="small" class="text-sm" />
          <Button v-if="selectedProducts.length > 0" @click="bulkDeactivate" icon="pi pi-times" label="Deactivate"
            severity="warn" outlined size="small" class="text-sm" />
        
        </div>
        <!-- Products Table -->
        <div v-if="loading" class="space-y-2">
          <div class="grid grid-cols-6 gap-3">
            <Skeleton v-for="i in 6" :key="i" height="22px" />
          </div>
          <div v-for="row in 8" :key="row" class="grid grid-cols-6 gap-3">
            <Skeleton v-for="column in 6" :key="column" height="18px" />
          </div>
        </div>
        <DataTable v-else v-model:selection="selectedProducts" :value="products" paginator :rows="15"
          :totalRecords="totalRecords" :lazy="true" @page="onPage" @sort="onSort" dataKey="id"
          @row-click="onProductRowClick" :rowClass="productRowClass"
          :rowsPerPageOptions="[15, 25, 50]" currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          class="p-datatable-sm text-xs" responsive-layout="scroll">
  
          <template #empty>
            <div class="text-center py-8">
              <i class="pi pi-inbox text-4xl text-gray-400"></i>
              <p class="text-gray-600 mt-2">No products found</p>
            </div>
          </template>
  
          <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
  
          <Column field="sku" header="SKU" sortable>
            <template #body="{ data }">
              <span class="font-mono text-tiny">{{ data.sku }}</span>
            </template>
          </Column>
  
          <Column field="product_name" header="Item Name" sortable>
            <template #body="{ data }">
              <div class="flex items-center gap-3">
                <div class="h-14 w-14 shrink-0 overflow-hidden rounded-md border border-slate-200 bg-slate-50">
                  <Carousel
                    v-if="getProductImages(data).length > 1"
                    :value="getProductImages(data)"
                    :numVisible="1"
                    :numScroll="1"
                    :showNavigators="true"
                    :showIndicators="false"
                    class="product-index-carousel h-full"
                  >
                    <template #item="{ data: image }">
                      <img :src="image.url" :alt="image.alt_text || data.product_name" class="h-14 w-14 object-cover" />
                    </template>
                  </Carousel>
                  <img
                    v-else-if="getProductImages(data).length === 1"
                    :src="getProductImages(data)[0].url"
                    :alt="getProductImages(data)[0].alt_text || data.product_name"
                    class="h-full w-full object-cover"
                  />
                  <div v-else class="flex h-full w-full items-center justify-center">
                    <i class="pi pi-image text-lg text-slate-300"></i>
                  </div>
                </div>
                <div>
                  <p class="font-medium text-gray-900 text-xs">{{ data.product_name }}</p>
                  <p class="text-xs text-gray-500">{{ data.brand }}</p>
                </div>
              </div>
            </template>
          </Column>
  
          <Column field="product_type" header="Type">
            <template #body="{ data }">
              <Badge :severity="getTypeSeverity(data.product_type)" size="small">
                <span class="text-xs">{{ getTypeLabel(data.product_type) }}</span>
              </Badge>
            </template>
          </Column>

          <Column field="category.category_name" header="Category"></Column>
  
          <Column field="base_price" header="Cost/Unit" sortable>
            <template #body="{ data }">
              <div>
                <p class=" text-gray-900 text-xs">₱{{ formatPrice(getUnitCost(data)) }}/<span class="font-bold">{{ data.unit_of_measurement }}</span></p>
              </div>
            </template>
          </Column>

          <Column field="reorder_point" header="Reorder Level" sortable>
            <template #body="{ data }">
              <span class="text-xs text-gray-700">{{ getReorderLevel(data) }}</span>
            </template>
          </Column>

          <Column field="supplier_name" header="Supplier">
            <template #body="{ data }">
              <span class="text-xs text-gray-700">{{ getSupplierName(data) }}</span>
            </template>
          </Column>

  
          <Column field="is_active" header="Status">
            <template #body="{ data }">
              <Tag :severity="data.is_active ? 'success' : 'secondary'">
                <span class="text-xs">{{ data.is_active ? 'Active' : 'Inactive' }}</span>
              </Tag>
            </template>
          </Column>
  
      
        </DataTable>
        </template>
    </Card>
  
    <!-- Delete Confirmation -->
    <Dialog v-model:visible="deleteDialogVisible" header="Confirm Delete" :modal="true" class="w-full sm:w-96">
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
import { ref, reactive, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import inventoryService from '../../../../services/inventory.service'
import { useAuthStore } from '../../../../stores/auth'
import { useRouter } from 'vue-router'
import Carousel from 'primevue/carousel'

const authStore = useAuthStore()
const router = useRouter()
const toast = useToast()

// State
const products = ref<any[]>([])
const categories = ref<any[]>([])
const selectedProducts = ref<any[]>([])
const loading = ref(true)
const totalRecords = ref(0)
const dialogVisible = ref(false)
const deleteDialogVisible = ref(false)
const deleting = ref(false)
const currentProduct = ref<any>(null)
const showBranchOnly = ref(false)

const filters = reactive({
  search: '',
  category_id: null,
  product_type: null,
  status: null as string | null,
  available_only: false,
  page: 1,
  per_page: 15,
  sort_by: 'created_at',
  sort_order: 'desc'
})
const statusOptions = [
  { label: 'Active', value: '1' },
  { label: 'Inactive', value: '0' },
  { label: 'No Supplier', value: 'no_supplier' }
]
const productTypeOptions = [
  { label: 'Product', value: 'finished_good' },
  { label: 'Supply', value: 'supply' }
]

const hasActiveFilters = computed(() => {
  return Boolean(
    filters.search.trim() ||
    filters.category_id ||
    filters.product_type ||
    filters.status ||
    filters.available_only
  )
})

// Methods
const loadProducts = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getProducts(filters)
    // inventoryService returns the API body. Products are in response.data.data.
    products.value = response?.data?.data || []
    totalRecords.value = Number(response?.data?.total ?? products.value.length)
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load products', life: 3000 })
  } finally {
    loading.value = false
  }
}

const loadCategories = async () => {
  try {
    const storeId = authStore.user?.store_id
    const response = await inventoryService.getCategories({
      active_only: true,
      store_id: storeId || undefined
    })
    categories.value = response?.data?.data || response?.data || []
  } catch (error) {
    console.error('Failed to load categories')
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
  filters.available_only = showBranchOnly.value
  loadProducts()
}

const toggleBranchOnly = () => {
  showBranchOnly.value = !showBranchOnly.value
  filters.available_only = showBranchOnly.value
  onFilterChange()
}

const resetFilters = () => {
  filters.search = ''
  filters.category_id = null
  filters.product_type = null
  filters.status = null
  filters.available_only = false
  showBranchOnly.value = false
  loadProducts()
}


// Add these methods to your script setup

const viewProduct = (productId: number) => {
  router.push({
    name: 'inventory.products.detail',
    params: { id: productId }
  })
}

const editProduct = (productId: number) => {
  router.push({
    name: 'inventory.products.edit',
    params: { id: productId }
  })
}

const productRowClass = () => ({ 'cursor-pointer hover:bg-orange-50': true })

const onProductRowClick = (event: any) => {
  const target = event?.originalEvent?.target as HTMLElement | null
  if (target?.closest('button, input, a')) return

  const id = event?.data?.id
  if (id) viewProduct(id)
}

const confirmDelete = (product: any) => {
  currentProduct.value = product
  deleteDialogVisible.value = true
}

const deleteProduct = async () => {
  deleting.value = true
  try {
    await inventoryService.deleteProduct(currentProduct.value.id)
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
    await inventoryService.bulkStatusUpdate(ids, true)
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
    await inventoryService.bulkStatusUpdate(ids, false)
    toast.add({ severity: 'success', summary: 'Success', detail: 'Products deactivated', life: 3000 })
    selectedProducts.value = []
    loadProducts()
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to deactivate products', life: 3000 })
  }
}

const getBranchAvailability = (product: any) => {
  const inventory = Array.isArray(product.inventory) ? product.inventory[0] : null
  return inventory ? `${inventory.quantity_available ?? 0}` : '0'
}

const getProductImages = (product: any) => {
  const assets = Array.isArray(product?.assets) ? product.assets : []

  return assets
    .filter((asset: any) => ['Image_Main', 'Image_Gallery', 'Image_360'].includes(asset.asset_type) && asset.url)
    .sort((a: any, b: any) => Number(b.is_primary) - Number(a.is_primary) || Number(a.display_order || 0) - Number(b.display_order || 0))
}

const getBranchAvailabilityStatus = (product: any) => {
  const inventory = Array.isArray(product.inventory) ? product.inventory[0] : null
  if (!inventory) return 'Not stocked in your branch'
  if ((inventory.quantity_available ?? 0) <= 0) return 'Out of stock'
  if ((inventory.quantity_available ?? 0) <= (inventory.reorder_point ?? 0)) return 'Low stock'
  return 'In stock'
}

const getTypeLabel = (type?: string) => {
  const normalized = String(type || '').toLowerCase()
  const labels: Record<string, string> = {
    finished_good: 'Product',
    supply: 'Supply'
  }
  return labels[normalized] || 'Product'
}

const getTypeSeverity = (type?: string) => {
  const normalized = String(type || '').toLowerCase()
  const severities: Record<string, string> = {
    finished_good: 'success',
    supply: 'info'
  }
  return severities[normalized] || 'secondary'
}

const getUnitCost = (product: any) => {
  return Number(product.cost_price ?? product.inventory_cost_price ?? product.base_price ?? 0)
}

const getReorderLevel = (product: any) => {
  return product.inventory?.[0]?.reorder_point ?? product.reorder_point ?? 'N/A'
}

const getSupplierName = (data: any) => {
  const product = data?.product || data
  const linkedNames = Array.isArray(product?.supplier_names)
    ? product.supplier_names.filter((name: any) => name && String(name).trim())
    : []

  if (linkedNames.length > 0) return [...new Set(linkedNames.map((name: string) => String(name).trim()))].join(', ')

  const linkedSuppliers = Array.isArray(product?.suppliers) ? product.suppliers : []
  const supplierNames = linkedSuppliers
    .map((supplier: any) => supplier?.supplier_name || supplier?.company_name)
    .filter((name: any) => name && String(name).trim())

  if (supplierNames.length > 0) return [...new Set(supplierNames.map((name: string) => String(name).trim()))].join(', ')

  return product?.supplier_name ? String(product.supplier_name).trim() : '-'
}

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2 }).format(price)
}

onMounted(() => {
  loadProducts()
  loadCategories()
})
</script>
