<template>
  <div class="min-h-screen p-4">
    <!-- Header -->
    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-lg font-bold text-gray-900">Products</h1>
        <p class="text-xs text-gray-500">Manage product details, ecommerce pricing, media, and variations.</p>
      </div>
      <Button v-if="authStore.hasPermission('merchandising.products.create')" label="Create Product" size="small"
        @click="createProduct" />
    </div>
  
    <!-- Filters Card -->
    <Card class="border border-gray-200 shadow-sm">
      <template #content>
        <div class="mb-4 grid grid-cols-1 items-end gap-4 md:grid-cols-6">
          <IconField class="w-full md:col-span-2">
            <InputIcon class="pi pi-search" />
            <InputText v-model="filters.search" placeholder="Search by product name or SKU" class="w-full text-sm"
              size="small" @input="onFilterChange" />
          </IconField>
  
          <Select v-model="filters.category_id" :options="categories" optionLabel="category_name" optionValue="id"
            placeholder="All Categories" class="w-full text-sm" size="small" showClear @change="onFilterChange" />
  
          <Select v-model="filters.is_active" :options="activeStatuses" optionLabel="label" optionValue="value"
            placeholder="Status" class="w-full text-sm" size="small" showClear @change="onFilterChange" />

          <Select v-model="filters.product_type" :options="productTypeOptions" optionLabel="label" optionValue="value"
            placeholder="Type" class="w-full text-sm" size="small" showClear @change="onFilterChange" />

          <div v-if="hasActiveFilters" class="flex justify-end">
            <Button @click="resetFilters" label="Clear All" severity="danger" size="small" class="text-sm" />
          </div>
        </div>
  
        <div v-if="selectedProducts.length" class="m-4 flex flex-wrap gap-2 border-t border-gray-100 p-4">
          <Button v-if="selectedProducts.length > 0" @click="bulkActivate" icon="pi pi-check" label="Activate"
            severity="warn" size="small" class="text-sm" />
          <Button v-if="selectedProducts.length > 0" @click="bulkDeactivate" icon="pi pi-times" label="Deactivate"
            severity="warn" outlined size="small" class="text-sm" />
        </div>

        <div v-if="loading" class="space-y-2">
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
  
          <Column field="product_name" header="Product" sortable style="min-width: 240px">
            <template #body="{ data }">
              <div class="flex items-center gap-3">
                <div class="h-14 w-14 shrink-0 overflow-hidden rounded-md border border-slate-200 bg-slate-50">
                  <img v-if="getPrimaryImage(data)" :src="getPrimaryImage(data)" :alt="data.product_name"
                    class="h-full w-full object-cover" />
                  <div v-else class="flex h-full w-full items-center justify-center">
                    <i class="pi pi-image text-lg text-slate-300"></i>
                  </div>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-900">{{ data.product_name }}</p>
                  <p class="font-mono text-[11px] text-gray-500">{{ data.sku }}</p>
                  <p v-if="data.variations_count" class="text-[11px] text-orange-600">{{ data.variations_count }} variations</p>
                </div>
              </div>
            </template>
          </Column>

          <Column field="product_type" header="Type">
            <template #body="{ data }">
              <Tag :severity="getTypeSeverity(data.product_type)" size="small">
                <span class="text-xs">{{ getTypeLabel(data.product_type) }}</span>
              </Tag>
            </template>
          </Column>
  
          <Column field="category.category_name" header="Category">
            <template #body="{ data }">
              <div class="text-xs">
                <p class="text-gray-800">{{ data.category?.category_name || '-' }}</p>
                <p v-if="data.subcategory?.category_name" class="text-gray-500">{{ data.subcategory.category_name }}</p>
              </div>
            </template>
          </Column>

          <Column field="brand" header="Brand">
            <template #body="{ data }"><span class="text-xs text-gray-700">{{ data.brand || '-' }}</span></template>
          </Column>
  
          <Column field="base_price" header="Price/Unit" sortable>
            <template #body="{ data }">
              <div>
                <p v-if="data.discounted_price" class="text-xs font-semibold text-red-600">₱{{ formatPrice(data.discounted_price) }}</p>
                <p class="text-xs" :class="data.discounted_price ? 'text-gray-500 line-through' : 'text-gray-900'">
                  ₱{{ formatPrice(data.base_price) }}/<b>{{ data.unit_of_measurement || 'unit' }}</b>
                </p>
              </div>
            </template>
          </Column>

          <Column header="Supplier">
            <template #body="{ data }">
              <div v-if="getPreferredSupplier(data)" class="flex items-center gap-2">
                <div>
                  <p class="text-xs text-gray-800">{{ getSupplierName(data) }}</p>
                  <p class="text-[11px] text-gray-500">{{ getSupplierNumber(data) }}</p>
                </div>
              </div>
              <span v-else class="text-xs text-gray-400">No supplier</span>
            </template>
          </Column>
  
          <Column field="is_active" header="Status">
            <template #body="{ data }">
              <Tag :severity="data.is_active ? 'success' : 'secondary'">
                <span class="text-xs">{{ data.is_active ? 'Active' : 'Inactive' }}</span>
              </Tag>
            </template>
          </Column>
  
          <Column frozen alignFrozen="left">
            <template #body="{ data }">
              <Button icon="pi pi-ellipsis-h" outlined rounded size="small" aria-label="Product actions"
                @click.stop="openActionMenu($event, data)" />
            </template>
          </Column>
        </DataTable>
        <Menu ref="actionMenu" :model="actionMenuItems" popup />
      </template>
    </Card>
  
    <!-- Archive Confirmation -->
    <Dialog v-model:visible="deleteDialogVisible" header="Archive Product" :modal="true" class="w-96">
      <div class="flex items-center gap-3">
        <i class="pi pi-exclamation-triangle text-4xl text-red-600"></i>
        <div>
          <p class="font-semibold">Archive {{ currentProduct?.product_name || 'this product' }}?</p>
          <p class="text-sm text-gray-600 mt-1">The product will be removed from active product lists.</p>
        </div>
      </div>
      <template #footer>
        <Button @click="deleteDialogVisible = false" label="Cancel" severity="secondary" text />
        <Button @click="deleteProduct" label="Archive" icon="pi pi-box" severity="danger" :loading="deleting" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import merchandisingService from '../../../../services/merchandising.service'
import { useAuthStore } from '../../../../stores/auth'
import { useRoute, useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()
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
const actionMenu = ref<any>(null)
const actionProduct = ref<any>(null)

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
const productTypeOptions = [
  { label: 'Product', value: 'finished_good' },
  { label: 'Raw Material', value: 'raw_material' }
]
const hasActiveFilters = computed(() => Boolean(
  filters.search.trim()
  || filters.category_id
  || filters.is_active !== null
  || filters.product_type !== 'finished_good'
))

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

const createProduct = () => {
  router.push({ name: 'merchandising.products.create' })
}

const editProduct = (productId: number) => {
  router.push({
    name: 'merchandising.products.edit',
    params: { id: productId }
  })
}

const actionMenuItems = computed(() => {
  const product = actionProduct.value
  const items: any[] = [
    {
      label: 'View',
      icon: 'pi pi-eye',
      command: () => product?.id && viewProduct(product.id)
    }
  ]

  if (authStore.hasPermission('merchandising.products.update')) {
    items.push({
      label: 'Edit',
      icon: 'pi pi-pencil',
      command: () => product?.id && editProduct(product.id)
    })
  }

  if (authStore.hasPermission('merchandising.products.delete')) {
    items.push({ separator: true })
    items.push({
      label: 'Archive',
      icon: 'pi pi-box',
      class: 'text-red-600',
      command: () => product && confirmDelete(product)
    })
  }

  return items
})

const openActionMenu = (event: MouseEvent, product: any) => {
  actionProduct.value = product
  actionMenu.value?.toggle(event)
}

const productRowClass = () => ({ 'cursor-pointer hover:bg-orange-50': true })

const onProductRowClick = (event: any) => {
  const target = event?.originalEvent?.target as HTMLElement | null
  if (target?.closest('button, input, a')) return
  if (event?.data?.id) viewProduct(event.data.id)
}

const getPrimaryImage = (product: any) => {
  const assets = Array.isArray(product?.assets) ? product.assets : []
  const images = assets.filter((asset: any) =>
    ['Image_Main', 'Image_Gallery', 'Image_360'].includes(asset.asset_type) && asset.url
  )
  const selected = images.find((asset: any) => asset.is_primary) || images[0]
  return selected?.thumbnail_url || selected?.url || null
}

const getTypeLabel = (type?: string) => {
  const normalized = String(type || '').toLowerCase()
  if (normalized === 'finished_good') return 'Product'
  if (normalized === 'raw_material') return 'Raw Material'
  return normalized.replace(/[_-]+/g, ' ').replace(/\b\w/g, character => character.toUpperCase()) || 'Other'
}

const getTypeSeverity = (type?: string) => {
  if (type === 'finished_good') return 'success'
  if (type === 'raw_material') return 'warn'
  return 'secondary'
}

const getPreferredSupplier = (product: any) => {
  const suppliers = Array.isArray(product?.suppliers) ? product.suppliers : []
  return suppliers.find((supplier: any) => Boolean(supplier?.pivot?.is_preferred_supplier)) || suppliers[0] || null
}

const getSupplierName = (product: any) => {
  const supplier = getPreferredSupplier(product)
  return supplier?.supplier_name || supplier?.company_name || product?.supplier_name || '-'
}

const getSupplierNumber = (product: any) => {
  const supplier = getPreferredSupplier(product)
  if (!supplier) return ''
  return supplier.supplier_code || `Supplier ID #${supplier.id}`
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
    toast.add({ severity: 'success', summary: 'Archived', detail: 'Product archived successfully', life: 3000 })
    deleteDialogVisible.value = false
    loadProducts()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to archive product', life: 3000 })
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
  filters.search = String(route.query.search || '')
  loadProducts()
  loadCategories()
  loadTags()
  loadAttributes()
})
</script>
