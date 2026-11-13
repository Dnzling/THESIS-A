<template>
  <div class="min-h-screen p-4">
    <div class="mb-4 flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-gray-800">Stocks Overview</h1>
        <p class="text-xs text-gray-500">Monitor available stock, pre-order quantities, and reorder levels.</p>
      </div>
      <div class="flex items-center gap-2">
        <Button
          v-if="canCreateAdjustments"
          label="Reorder Suggestions"
          size="small"
          class="text-sm"
          rounded
          @click="router.push({ name: 'inventory.reorder-suggestions' })"
        />
      </div>
    </div>
  
    <Card>
      <template #content>
        <!-- Filters -->
        <div class="mb-4 flex flex-wrap items-end gap-3">
          <div class="w-full md:w-72">
            <IconField class="w-full">
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" placeholder="Search item name or SKU" class="w-full text-xs" size="small" />
            </IconField>
          </div>
          <div v-if="showBranchColumn" class="w-full md:w-48">
            <Select v-model="filters.branch_id" :options="branches" optionLabel="name" optionValue="id"
              placeholder="Branch" class="w-full text-xs" size="small" showClear />
          </div>
          <div class="w-full md:w-48">
            <Select v-model="filters.stock_status" :options="stockStatuses" optionLabel="label" optionValue="value"
              placeholder="Stock Status" size="small" class="w-full text-xs" showClear />
          </div>
          <div class="w-full md:w-48">
            <Select v-model="filters.product_type" :options="productTypeOptions" optionLabel="label" optionValue="value"
              placeholder="Product Type" size="small" class="w-full text-xs" showClear />
          </div>
          <Button v-if="hasActiveFilters" label="Clear Filter" icon="pi pi-filter-slash" severity="danger" size="small" class="text-xs" @click="resetFilters" />
        </div>

        <!-- Items Table -->
        <div v-if="loading" class="space-y-2">
          <div class="grid grid-cols-8 gap-2 text-xs text-gray-400">
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
          </div>
          <div v-for="i in 8" :key="i" class="grid grid-cols-8 gap-2">
            <Skeleton height="40px" class="col-span-1" />
            <Skeleton height="40px" class="col-span-1" />
            <Skeleton height="40px" class="col-span-1" />
            <Skeleton height="40px" class="col-span-1" />
            <Skeleton height="40px" class="col-span-1" />
            <Skeleton height="40px" class="col-span-1" />
            <Skeleton height="40px" class="col-span-1" />
            <Skeleton height="40px" class="col-span-1" />
          </div>
        </div>
  
        <DataTable v-else :value="items" paginator :rows="filters.per_page"
          @sort="onSort" :sortField="sortField" :sortOrder="sortOrder" dataKey="id"
          @row-click="onItemRowClick" :rowClass="itemRowClass"
          :rowsPerPageOptions="[15, 25, 50]" currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageSelect"
          class="p-datatable-sm text-xs sm:text-sm" rowHover>
          <template #empty>
            <div class="text-center py-8">
              <i class="pi pi-inbox text-4xl text-gray-400"></i>
              <p class="text-gray-600 mt-2">No inventory records found</p>
              <Button
                v-if="canCreateItems"
                label="Add Product"
                icon="pi pi-plus"
                size="small"
                class="mt-4"
                @click="router.push({ name: 'merchandising.products.create' })"
              />
            </div>
          </template>
  
          <Column field="product_name" header="Product" style="min-width: 230px">
            <template #body="{ data }">
              <div class="flex items-center gap-3">
                <div class="h-14 w-14 shrink-0 overflow-hidden rounded-md border border-slate-200 bg-slate-50">
                  <img v-if="getProductImage(data)" :src="getProductImage(data)" :alt="data.product?.product_name || 'Product image'" class="block h-full w-full object-contain" @error="handleProductImageError(data, $event)" />
                  <div v-else class="flex h-full w-full items-center justify-center"><i class="pi pi-image text-lg text-slate-300"></i></div>
                </div>
                <div class="min-w-0">
                  <p class="text-xs font-medium text-gray-900">{{ data.product?.product_name || 'N/A' }}</p>
                  <p v-if="data.variation" class="truncate text-[11px] font-medium text-orange-700">{{ data.variation.variation_name || 'Variant' }}</p>
                  <p class="font-mono text-[11px] text-gray-500">{{ data.variation?.variation_sku || data.product?.sku || 'N/A' }}</p>
                </div>
              </div>
            </template>
          </Column>

          <Column header="Type" style="min-width: 120px">
            <template #body="{ data }">
              <Tag :value="getTypeLabel(data.product?.product_type)" :severity="getTypeSeverity(data.product?.product_type)" class="text-xs" />
            </template>
          </Column>

          <Column header="Location" style="min-width: 150px">
            <template #body="{ data }">
              <div class="text-xs text-gray-700">
                <p>{{ getBranchName(data) }}</p>
                <p v-if="getStockLocation(data)" class="text-[11px] text-gray-500">{{ getStockLocation(data) }}</p>
              </div>
            </template>
          </Column>

          <Column header="Available" sortable sortField="quantity_available">
            <template #body="{ data }">
              <span class="font-semibold text-gray-900">{{ data.quantity_available || 0 }}</span>
            </template>
          </Column>

          <Column header="Pre Order">
            <template #body="{ data }">
              <span class="text-xs font-medium text-blue-700">{{ data.quantity_incoming || 0 }}</span>
            </template>
          </Column>

          <Column header="Reorder Point" sortable sortField="reorder_point">
            <template #body="{ data }">
              <span class="text-xs text-gray-700">{{ getReorderLevel(data) }}</span>
            </template>
          </Column>
  
          <Column field="stock_status" header="Stock Status" style="width: 10%">
            <template #body="{ data }">
              <Badge :value="stockStatusLabel(data.stock_status)" :severity="stockStatusSeverity(data.stock_status)"
                class="text-xs" />
            </template>
          </Column>
  
          <Column header="Actions" style="width: 120px">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button label="View" icon="pi pi-eye" outlined rounded size="small" class="text-xs" aria-label="View inventory item" @click.stop="openItem(data)" />
                <Button v-if="canCreateAdjustments" icon="pi pi-plus-minus" text rounded size="small" severity="warn"
                  aria-label="Adjust stock" @click.stop="openAdjustment(data)" />
              </div>
            </template>
          </Column>
        </DataTable>
        </template>
    </Card>
  
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'
import { useAuthStore } from '../../../../stores/auth'
import Tag from 'primevue/tag'

const loading = ref(true)
const items = ref<any[]>([])
const failedProductImageUrls = ref<Record<string, string[]>>({})
const toast = useToast()
const router = useRouter()
const authStore = useAuthStore()

const canCreateItems = computed(() => authStore.hasPermission('products.manage'))
const canCreateAdjustments = computed(() => authStore.hasPermission('inventory.reorder_suggestions.manage'))
const canUpdateItems = computed(() => authStore.hasPermission('products.update') || authStore.hasPermission('inventory.products.manage'))
const canViewWarehouseStock = computed(() => authStore.hasPermission('warehouse.view'))
const branchCount = ref(0)
const branches = ref<any[]>([])
const showBranchColumn = computed(() => branchCount.value >= 2)
const currentUserBranchId = computed<number | null>(() => {
  const user: any = authStore.user || {}
  return Number(
    user.branch_id ||
    user.branch?.id ||
    user.employee?.branch_id ||
    0
  ) || null
})

const filters = reactive({
  search: '',
  stock_status: null as string | null,
  product_type: null as string | null,
  branch_id: null as number | null,
  page: 1,
  per_page: 15
})
const sortField = ref('created_at')
const sortOrder = ref(-1)
let searchTimer: ReturnType<typeof setTimeout> | null = null

const hasActiveFilters = computed(() => {
  const normalizedSearch = filters.search.trim()
  const defaultBranchId = showBranchColumn.value ? (currentUserBranchId.value || branches.value[0]?.id || null) : null
  return Boolean(
    normalizedSearch ||
    filters.stock_status ||
    filters.product_type ||
    (showBranchColumn.value && filters.branch_id && filters.branch_id !== defaultBranchId)
  )
})

const productTypeOptions = [
  { label: 'Product', value: 'finished_good' },
  { label: 'Supplies', value: 'supply' },
  { label: 'Raw Material', value: 'raw_material' },
  { label: 'Others', value: 'others' }
]

const stockStatuses = [
  { label: 'In Stock', value: 'in_stock' },
  { label: 'Low Stock', value: 'low_stock' },
  { label: 'Out of Stock', value: 'out_of_stock' },
  { label: 'Needs Reorder', value: 'needs_reorder' }
]

const loadBranchCount = async () => {
  try {
    const response = await inventoryService.getBranches()
    const branchData = response?.data?.data || response?.data || []
    const branchList = Array.isArray(branchData) ? branchData : []
    branchCount.value = branchList.length
    branches.value = branchList
    if (branchList.length >= 2 && !filters.branch_id) {
      filters.branch_id = currentUserBranchId.value || branchList[0]?.id || null
    }
  } catch {
    branchCount.value = 0
    branches.value = []
  }
}

const loadItems = async () => {
  loading.value = true
  try {
    const params: any = {
      page: 1,
      per_page: 500
    }

    if (filters.search) params.search = filters.search
    if (filters.stock_status) params.stock_status = filters.stock_status
    if (filters.product_type) params.product_type = filters.product_type
    if (showBranchColumn.value && filters.branch_id) params.branch_id = filters.branch_id
    params.sort_by = sortField.value
    params.sort_order = sortOrder.value === 1 ? 'asc' : 'desc'

    const targetBranchId = Number(filters.branch_id || currentUserBranchId.value || branches.value[0]?.id || 0)
    const response = targetBranchId
      ? await inventoryService.getBranchInventory(targetBranchId, params)
      : await inventoryService.getInventoryItems(params)

    if (response?.data) {
      items.value = Array.isArray(response.data) ? response.data : (response.data.data || [])
    } else {
      items.value = []
    }
  } catch (error: any) {
    console.error('Failed to load inventory', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'Failed to load inventory',
      life: 3000
    })
    items.value = []
  } finally {
    loading.value = false
  }
}

const onPage = (event: any) => {
  filters.page = event.page + 1
  filters.per_page = event.rows
  loadItems()
}

const onSort = (event: any) => {
  sortField.value = event.sortField || 'created_at'
  sortOrder.value = event.sortOrder || -1
  filters.page = 1
  loadItems()
}

const resetFilters = () => {
  filters.search = ''
  filters.product_type = null
  filters.stock_status = null
  filters.branch_id = showBranchColumn.value ? (currentUserBranchId.value || branches.value[0]?.id || null) : null
  filters.page = 1
  filters.per_page = 15
  sortField.value = 'created_at'
  sortOrder.value = -1
  loadItems()
}

const formatDate = (value: string) => {
  if (!value) return '-'
  return new Date(value).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

const formatMoney = (value: number | string) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2
  }).format(Number(value || 0))
}

const getTypeLabel = (type?: string) => {
  const normalized = String(type || '').toLowerCase()
  const labels: Record<string, string> = {
    supply: 'Supplies',
    raw_material: 'Raw Material',
    finished_good: 'Product'
  }
  return labels[normalized] || normalized.replace(/[_-]+/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase()) || 'Other'
}

const getTypeSeverity = (type?: string) => {
  const normalized = String(type || '').toLowerCase()
  const severities: Record<string, string> = {
    finished_good: 'success',
    supply: 'info',
    raw_material: 'warn'
  }
  return severities[normalized] || 'secondary'
}

const getUnitCost = (data: any) => {
  return Number(data.variation?.cost_price ?? data.product?.inventory_cost_price ?? data.product?.cost_price ?? 0)
}

const getReorderLevel = (data: any) => {
  return Number(data.reorder_point ?? data.variation?.reorder_point ?? 0)
}

const stockStatusLabel = (status: any) => String(status || 'unknown')
  .replaceAll('_', ' ')
  .replace(/\b\w/g, (character) => character.toUpperCase())

const stockStatusSeverity = (status: any) => {
  if (status === 'in_stock') return 'success'
  if (status === 'low_stock' || status === 'needs_reorder') return 'warn'
  if (status === 'out_of_stock') return 'danger'
  return 'secondary'
}

const getStockValue = (data: any) => {
  return Number(data.quantity_available ?? data.inventory?.[0]?.quantity_available ?? 0) * getUnitCost(data)
}

const getSupplierName = (data: any) => {
  return data.variation?.supplier_name || data.product?.supplier_name || data.product?.suppliers?.[0]?.supplier_name || data.product?.suppliers?.[0]?.company_name
}

const getBranchName = (data: any) => {
  return data.branch?.name || branches.value.find((branch) => Number(branch.id) === Number(filters.branch_id))?.name || 'N/A'
}

const getProductImage = (data: any) => {
  const assets = Array.isArray(data?.product?.assets) ? data.product.assets : []
  const productKey = String(data?.product_id || data?.product?.id || '')
  const failedUrls = failedProductImageUrls.value[productKey] || []
  const image = assets
    .filter((asset: any) => ['Image_Main', 'Image_Gallery', 'Image_360'].includes(asset.asset_type) && asset.url && !failedUrls.includes(asset.url))
    .sort((a: any, b: any) => Number(Boolean(b.is_primary)) - Number(Boolean(a.is_primary))
      || Number(a.display_order || 0) - Number(b.display_order || 0))[0]
  return image?.url || null
}

const handleProductImageError = (data: any, event: Event) => {
  const productKey = String(data?.product_id || data?.product?.id || '')
  const image = event.target as HTMLImageElement | null
  const failedUrl = image?.getAttribute('src') || image?.src
  if (!productKey || !failedUrl) return

  const failedUrls = failedProductImageUrls.value[productKey] || []
  if (!failedUrls.includes(failedUrl)) {
    failedProductImageUrls.value = {
      ...failedProductImageUrls.value,
      [productKey]: [...failedUrls, failedUrl],
    }
  }
}

const getStockLocation = (data: any) => {
  return data?.warehouse_location?.name
    || data?.warehouse_location?.location_code
    || data?.bin_code
    || data?.warehouse_section
}

const openItem = (data: any) => {
  const id = Number(data?.id || 0)
  if (id) router.push({
    name: 'inventory.items.show',
    params: { id },
    query: data?.branch_id ? { branch_id: String(data.branch_id) } : undefined,
  })
}

const openAdjustment = (data: any) => {
  const id = data?.product_id || data?.product?.id
  router.push({ name: 'inventory.adjustments.create', query: id ? { product_id: id } : undefined })
}

const itemRowClass = () => ({ 'cursor-pointer hover:bg-orange-50': true })

const onItemRowClick = (event: any) => {
  const target = event?.originalEvent?.target as HTMLElement | null
  if (target?.closest('button, input, a')) return

  openItem(event?.data)
}

onMounted(async () => {
  if (!authStore.user) {
    await authStore.fetchCurrentUser().catch(() => null)
  }
  await loadBranchCount()
  loadItems()
})

watch([() => filters.branch_id, () => filters.stock_status, () => filters.product_type, () => filters.per_page], () => {
  filters.page = 1
  loadItems()
})

watch(() => filters.search, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    filters.page = 1
    loadItems()
  }, 350)
})
</script>
