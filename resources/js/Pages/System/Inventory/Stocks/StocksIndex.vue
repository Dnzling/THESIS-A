<template>
  <div class="min-h-screen p-4">
    <div class="mb-4 flex items-center justify-between">
      <div>
        <h1 class="text-lg font-bold text-gray-800">Inventory</h1>
      </div>
      <div class="flex items-center gap-2">
        <!-- <Button
          v-if="canCreateAdjustments"
          label="Reorder Suggestions"
          size="small"
          class="text-sm"
          @click="router.push({ name: 'inventory.reorder-suggestions' })"
        /> -->
      </div>
    </div>
  
    <Card>
      <template #content>
        <!-- Filters -->
        <div class="grid grid-cols-1 gap-4 items-end md:grid-cols-4 mb-5">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText v-model="filters.search" placeholder="Search item name or SKU" class="w-full text-sm"
              size="small" />
          </IconField>
  
          <Select v-if="showBranchColumn" v-model="filters.branch_id" :options="branches" optionLabel="name"
            optionValue="id" placeholder="Branch" class="w-full text-sm" size="small" showClear />
  
          <Select v-model="filters.stock_status" :options="stockStatuses" optionLabel="label" optionValue="value"
            placeholder="Stock Status" size="small" class="w-full text-sm" showClear />
  
          <Select v-model="filters.product_type" :options="productTypeOptions" optionLabel="label" optionValue="value"
            placeholder="Product Type" size="small" class="w-full text-sm" showClear />
          <div v-if="hasActiveFilters">
            <Button label="Clear All" severity="danger" size="small" @click="resetFilters" />
          </div>
        </div>

        <!-- Items Table -->
        <div v-if="loading" class="space-y-2">
          <div class="grid grid-cols-6 gap-2 text-xs text-gray-400">
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
          </div>
          <div v-for="i in 8" :key="i" class="grid grid-cols-6 gap-2">
            <Skeleton height="40px" class="col-span-1" />
            <Skeleton height="40px" class="col-span-1" />
            <Skeleton height="40px" class="col-span-1" />
            <Skeleton height="40px" class="col-span-1" />
            <Skeleton height="40px" class="col-span-1" />
            <Skeleton height="40px" class="col-span-1" />
          </div>
        </div>
  
        <DataTable v-else :value="items" paginator :rows="filters.per_page" :totalRecords="totalRecords" :lazy="true"
          @page="onPage" @sort="onSort" :sortField="sortField" :sortOrder="sortOrder" dataKey="id"
          @row-click="onItemRowClick" :rowClass="itemRowClass"
          :rowsPerPageOptions="[15, 25, 50]" currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageSelect"
          class="p-datatable-sm text-xs" stripedRows>
          <template #empty>
            <div class="text-center py-8">
              <i class="pi pi-inbox text-4xl text-gray-400"></i>
              <p class="text-gray-600 mt-2">No inventory records found</p>
            </div>
          </template>
  
          <Column v-if="showBranchColumn" field="branch.name" header="Branch" style="width: 12%">
            <template #body="{ data }">
              <span class="text-xs text-gray-700">{{ getBranchName(data) }}</span>
            </template>
          </Column>
  
          <Column field="sku" header="SKU" style="width: 12%">
            <template #body="{ data }">
              <span class="text-xs text-gray-700">{{ data.product?.sku || data.sku || 'N/A' }}</span>
            </template>
          </Column>
  
          <Column field="product_name" header="Product Name" style="width: 18%">
            <template #body="{ data }">
              <div class="space-y-0.5 text-xs">
                <div class="font-medium text-gray-900">{{ data.product?.product_name || data.product_name || 'N/A' }}
                </div>
               
              </div>
            </template>
          </Column>
  
          <Column header="Supplier" style="width: 16%">
            <template #body="{ data }">
              <span class="text-xs text-gray-700">{{ getSupplierName(data) }}</span>
            </template>
          </Column>
  
          <Column header="Cost/Unit" style="width: 12%">
            <template #body="{ data }">
              <span class="text-xs text-gray-700">{{ formatMoney(getUnitCost(data)) }}</span>
            </template>
          </Column>
  
          <Column header="Reorder Level" style="width: 5%">
            <template #body="{ data }">
              <span class="text-xs text-gray-700">{{ getReorderLevel(data) }}</span>
            </template>
          </Column>
  
          <Column header="Quantity on Hand" style="width: 8%">
            <template #body="{ data }">
              <span class="text-xs text-gray-700">{{ data.quantity_available ?? data.inventory?.[0]?.quantity_available ??
                0 }}</span>
            </template>
          </Column>
  
          <Column header="Stock Value" style="width: 12%">
            <template #body="{ data }">
              <span class="text-xs text-gray-700">{{ formatMoney(getStockValue(data)) }}</span>
            </template>
          </Column>
  
          <Column header="Reorder" style="width: 8%">
            <template #body="{ data }">
              <Tag :value="needsReorder(data) ? 'Yes' : 'No'" :severity="needsReorder(data) ? 'warn' : 'success'"
                class="text-xs" />
            </template>
          </Column>
  
          <Column header="Order Date" style="width: 15%">
            <template #body="{ data }">
              <span class="text-xs text-gray-700">{{ formatDate(data.created_at) }}</span>
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

const loading = ref(true)
const items = ref<any[]>([])
const totalRecords = ref(0)
const toast = useToast()
const router = useRouter()
const authStore = useAuthStore()

const canCreateItems = computed(() => authStore.hasPermission('inventory.products.manage'))
const canCreateAdjustments = computed(() => authStore.hasPermission('inventory.adjustments.manage'))
const canUpdateItems = computed(() => authStore.hasPermission('inventory.products.update') || authStore.hasPermission('inventory.products.manage'))
const canViewWarehouseStock = computed(() => authStore.hasPermission('inventory.warehouse.view'))
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
  { label: 'Finished Good', value: 'finished_good' },
  { label: 'Raw Material', value: 'raw_material' },
  { label: 'Supply', value: 'supply' }
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
      page: filters.page,
      per_page: filters.per_page
    }

    if (filters.search) params.search = filters.search
    if (filters.stock_status) params.stock_status = filters.stock_status
    if (filters.product_type) params.product_type = filters.product_type
    if (showBranchColumn.value && filters.branch_id) params.branch_id = filters.branch_id
    params.sort_by = sortField.value
    params.sort_order = sortOrder.value === 1 ? 'asc' : 'desc'

    const response = await inventoryService.getProducts(params)

    if (response?.data) {
      items.value = response.data.data || response.data || []
      totalRecords.value = response.data.total ?? items.value.length
    } else {
      items.value = []
      totalRecords.value = 0
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
    totalRecords.value = 0
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
    raw_material: 'Raw Material',
    supply: 'Supply',
    finished_good: 'Product'
  }
  return labels[normalized] || 'Product'
}

const getUnitCost = (data: any) => {
  return Number(data.inventory_cost_price ?? data.cost_price ?? data.product?.inventory_cost_price ?? data.product?.cost_price ?? 0)
}

const getReorderLevel = (data: any) => {
  return Number(data.reorder_point ?? data.inventory?.[0]?.reorder_point ?? 0)
}

const needsReorder = (data: any) => {
  const onHand = Number(data.quantity_available ?? data.inventory?.[0]?.quantity_available ?? 0)
  return onHand <= getReorderLevel(data)
}

const getStockValue = (data: any) => {
  return Number(data.quantity_available ?? data.inventory?.[0]?.quantity_available ?? 0) * getUnitCost(data)
}

const getSupplierName = (data: any) => {
  return (
    data.product?.supplier_name ||
    data.product?.suppliers?.[0]?.name ||
    data.product?.suppliers?.[0]?.company_name ||
    'N/A'
  )
}

const getBranchName = (data: any) => {
  return data.branch?.name || branches.value.find((branch) => Number(branch.id) === Number(filters.branch_id))?.name || 'N/A'
}

const itemRowClass = () => ({ 'cursor-pointer hover:bg-orange-50': true })

const onItemRowClick = (event: any) => {
  const target = event?.originalEvent?.target as HTMLElement | null
  if (target?.closest('button, input, a')) return

  const id = event?.data?.id
  if (id) router.push({ name: 'inventory.products.detail', params: { id } })
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
