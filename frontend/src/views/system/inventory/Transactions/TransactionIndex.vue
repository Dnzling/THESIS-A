<template>
  <div class="p-6 bg-gray-50 min-h-screen space-y-6">
    <div class="mb-2">
      <h1 class="text-3xl font-bold text-gray-800">Inventory Transactions</h1>
      <p class="text-gray-600 mt-1">Review movement history and totals</p>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card>
        <template #content>
          <div class="flex justify-between items-center">
            <div>
              <p class="text-sm text-gray-600 mb-1">Total Transactions</p>
              <h3 class="text-2xl font-bold text-gray-800">{{ summary.total_transactions || 0 }}</h3>
            </div>
            <i class="pi pi-list text-3xl text-gray-400"></i>
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div class="flex justify-between items-center">
            <div>
              <p class="text-sm text-gray-600 mb-1">Total Value Moved</p>
              <h3 class="text-2xl font-bold text-green-600">₱{{ formatNumber(summary.total_value_moved) }}</h3>
            </div>
            <i class="pi pi-dollar text-3xl text-green-400"></i>
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div class="flex justify-between items-center">
            <div>
              <p class="text-sm text-gray-600 mb-1">Today's Transactions</p>
              <h3 class="text-2xl font-bold text-blue-600">{{ summary.today_transactions || 0 }}</h3>
            </div>
            <i class="pi pi-calendar text-3xl text-blue-400"></i>
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div class="flex justify-between items-center">
            <div>
              <p class="text-sm text-gray-600 mb-1">Unique Products</p>
              <h3 class="text-2xl font-bold text-purple-600">{{ summary.unique_products_affected || 0 }}</h3>
            </div>
            <i class="pi pi-box text-3xl text-purple-400"></i>
          </div>
        </template>
      </Card>
    </div>

    <!-- Filters -->
    <Card>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText 
                v-model="filters.search" 
                placeholder="Search by reference or notes..." 
                class="w-full" 
                @input="onFilterChange"
              />
            </IconField>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Transaction Type</label>
            <Select
              v-model="filters.transaction_type"
              :options="transactionTypes"
              optionLabel="label"
              optionValue="value"
              placeholder="All Types"
              class="w-full"
              showClear
              @change="onFilterChange"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Product</label>
            <Select
              v-model="filters.product_id"
              :options="products"
              optionLabel="product_name"
              optionValue="id"
              placeholder="All Products"
              class="w-full"
              showClear
              filter
              @change="onFilterChange"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
            <DatePicker
              v-model="filters.from_date"
              dateFormat="yy-mm-dd"
              placeholder="From"
              class="w-full"
              @date-select="onFilterChange"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
            <DatePicker
              v-model="filters.to_date"
              dateFormat="yy-mm-dd"
              placeholder="To"
              class="w-full"
              @date-select="onFilterChange"
            />
          </div>
        </div>
        
        <div class="mt-4 flex justify-end">
          <Button 
            icon="pi pi-filter-slash" 
            label="Reset Filters" 
            severity="secondary" 
            outlined 
            @click="resetFilters" 
          />
        </div>
      </template>
    </Card>

    <!-- Transactions Table -->
    <Card>
      <template #content>
        <DataTable 
          :value="transactions" 
          :loading="loading" 
          paginator 
          :rows="filters.per_page"
          :totalRecords="totalRecords"
          :lazy="true"
          @page="onPage"
          @sort="onSort"
          :sortField="filters.sort_field"
          :sortOrder="filters.sort_direction === 'asc' ? 1 : -1"
          class="p-datatable-sm" 
          stripedRows
          dataKey="id"
        >
          <template #empty>
            <div class="text-center py-8">
              <i class="pi pi-inbox text-4xl text-gray-400"></i>
              <p class="text-gray-600 mt-2">No transactions found</p>
            </div>
          </template>

          <Column field="transaction_number" header="Reference" sortable style="width: 150px">
            <template #body="{ data }">
              <span class="font-mono text-sm">{{ data.transaction_number }}</span>
            </template>
          </Column>

          <Column field="transaction_type" header="Type" sortable style="width: 120px">
            <template #body="{ data }">
              <Tag 
                :value="formatTransactionType(data.transaction_type)" 
                :severity="getTransactionTypeSeverity(data.transaction_type)"
                class="capitalize"
              />
            </template>
          </Column>

          <Column field="branch.name" header="Branch" style="width: 150px">
            <template #body="{ data }">
              <div>
                <span>{{ data.branch?.name || 'N/A' }}</span>
                <span v-if="data.related_branch" class="text-xs text-gray-500 block">
                  From: {{ data.related_branch?.name }}
                </span>
              </div>
            </template>
          </Column>

          <Column field="product.product_name" header="Product" style="min-width: 200px">
            <template #body="{ data }">
              <div>
                <span class="font-medium">{{ data.product?.product_name || 'N/A' }}</span>
                <span v-if="data.variation" class="text-xs text-gray-500 block">
                  Variation: {{ data.variation.variation_name }}
                </span>
              </div>
            </template>
          </Column>

          <Column header="Quantity Change" style="width: 120px">
            <template #body="{ data }">
              <span 
                :class="data.quantity_change > 0 ? 'text-green-600' : 'text-red-600'"
                class="font-medium"
              >
                {{ data.quantity_change > 0 ? '+' : '' }}{{ data.quantity_change }}
              </span>
              <span class="text-xs text-gray-500 block">
                Before: {{ data.quantity_before }} → After: {{ data.quantity_after }}
              </span>
            </template>
          </Column>

          <Column field="total_value" header="Total Value" style="width: 120px">
            <template #body="{ data }">
              ₱{{ formatNumber(data.total_value) }}
              <span v-if="data.unit_cost" class="text-xs text-gray-500 block">
                @ ₱{{ formatNumber(data.unit_cost) }}/unit
              </span>
            </template>
          </Column>

          <Column field="reference_type" header="Source" style="width: 120px">
            <template #body="{ data }">
              <Tag 
                :value="formatReferenceType(data.reference_type)" 
                severity="info"
                class="capitalize"
              />
              <span class="text-xs text-gray-500 block">
                ID: {{ data.reference_id }}
              </span>
            </template>
          </Column>

          <Column field="transaction_date" header="Date" sortable style="width: 150px">
            <template #body="{ data }">
              {{ formatDate(data.transaction_date) }}
              <span class="text-xs text-gray-500 block">
                {{ formatTime(data.transaction_date) }}
              </span>
            </template>
          </Column>

          <Column header="Created By" style="width: 150px">
            <template #body="{ data }">
              <div v-if="data.created_by">
                <span>{{ data.created_by.fname }} {{ data.created_by.lname }}</span>
                <span class="text-xs text-gray-500 block">
                  {{ data.created_by.employee_number }}
                </span>
              </div>
              <span v-else>N/A</span>
            </template>
          </Column>

          <Column field="notes" header="Notes" style="min-width: 200px">
            <template #body="{ data }">
              {{ data.notes || 'N/A' }}
            </template>
          </Column>

          <!-- Actions Column -->
          <Column header="Actions" style="width: 100px">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button
                  icon="pi pi-eye"
                  severity="info"
                  outlined
                  @click="viewTransaction(data)"
                  v-tooltip.top="'View Details'"
                  size="small"
                />
                <Button
                  v-if="data.reference_type === 'purchase_order'"
                  icon="pi pi-file-pdf"
                  severity="secondary"
                  outlined
                  @click="viewSourceDocument(data)"
                  v-tooltip.top="'View Source Document'"
                  size="small"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '../../../../services/inventory.service'

const router = useRouter()
const toast = useToast()
const loading = ref(false)
const transactions = ref<any[]>([])
const products = ref<any[]>([])
const totalRecords = ref(0)
const summary = ref({
  total_transactions: 0,
  total_value_moved: 0,
  today_transactions: 0,
  unique_products_affected: 0
})

const filters = reactive({
  search: '',
  transaction_type: null as string | null,
  product_id: null as number | null,
  from_date: null as Date | null,
  to_date: null as Date | null,
  page: 1,
  per_page: 15,
  sort_field: 'transaction_date',
  sort_direction: 'desc' as 'asc' | 'desc'
})

const transactionTypes = [
  { label: 'Purchase', value: 'purchase' },
  { label: 'Sale', value: 'sale' },
  { label: 'Adjustment', value: 'adjustment' },
  { label: 'Transfer', value: 'transfer' },
  { label: 'Return', value: 'return' },
  { label: 'Issue', value: 'issue' },
  { label: 'Receive', value: 'receive' }
]

// ==================== NAVIGATION FUNCTIONS ====================

/**
 * Navigate to transaction detail view
 */
const viewTransaction = (transaction: any) => {
  router.push({ 
    name: 'inventory.transactions.detail', 
    params: { id: transaction.id } 
  })
}

/**
 * Navigate to source document (if applicable)
 */
const viewSourceDocument = (transaction: any) => {
  if (!transaction.reference_type || !transaction.reference_id) return
  
  // Map reference types to routes
  const routeMap: Record<string, string> = {
    'purchase_order': 'procurement.purchase-orders.detail',
    'sales_order': 'sales.orders.detail',
    'stock_adjustment': 'inventory.adjustments.detail',
    'stock_transfer': 'inventory.transfers.detail',
    'stock_issue': 'inventory.stock-issues.detail',
    'stock_return': 'inventory.stock-returns.detail'
  }
  
  const routeName = routeMap[transaction.reference_type]
  
  if (routeName) {
    router.push({ 
      name: routeName, 
      params: { id: transaction.reference_id } 
    })
  } else {
    toast.add({
      severity: 'info',
      summary: 'Info',
      detail: 'Source document viewer not available for this type',
      life: 3000
    })
  }
}

// ==================== DATA LOADING FUNCTIONS ====================

const loadTransactions = async () => {
  loading.value = true
  try {
    const params: any = {
      page: filters.page,
      per_page: filters.per_page,
      sort_by: filters.sort_field,
      sort_order: filters.sort_direction
    }
    
    if (filters.search) params.search = filters.search
    if (filters.transaction_type) params.transaction_type = filters.transaction_type
    if (filters.product_id) params.product_id = filters.product_id
    if (filters.from_date) params.from_date = formatDateParam(filters.from_date)
    if (filters.to_date) params.to_date = formatDateParam(filters.to_date)

    const response = await inventoryService.getTransactions(params)
    
    if (response.success) {
      if (response.data && response.data.data) {
        transactions.value = response.data.data
        totalRecords.value = response.data.total || 0
      } else if (Array.isArray(response.data)) {
        transactions.value = response.data
        totalRecords.value = response.data.length
      } else {
        transactions.value = []
        totalRecords.value = 0
      }
    }
  } catch (error) {
    console.error('Failed to load transactions', error)
    transactions.value = []
    totalRecords.value = 0
    
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load transactions',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadSummary = async () => {
  try {
    const params: any = {}
    if (filters.from_date) params.from_date = formatDateParam(filters.from_date)
    if (filters.to_date) params.to_date = formatDateParam(filters.to_date)
    
    const response = await inventoryService.getTransactionSummary(params)
    
    if (response.success && response.data) {
      summary.value = response.data
    }
  } catch (error) {
    console.error('Failed to load summary', error)
  }
}

const loadProducts = async () => {
  try {
    const response = await inventoryService.getProducts({
      per_page: 1000
    })
    if (response.success) {
      if (response.data && Array.isArray(response.data.data)) {
        products.value = response.data.data
      } else if (Array.isArray(response.data)) {
        products.value = response.data
      } else {
        products.value = []
      }
    }
  } catch (error) {
    console.error('Failed to load products', error)
  }
}

// ==================== FILTER FUNCTIONS ====================

const onFilterChange = () => {
  filters.page = 1
  loadTransactions()
  loadSummary()
}

const onPage = (event: any) => {
  filters.page = event.page + 1
  filters.per_page = event.rows
  loadTransactions()
}

const onSort = (event: any) => {
  filters.sort_field = event.sortField
  filters.sort_direction = event.sortOrder === 1 ? 'asc' : 'desc'
  loadTransactions()
}

const resetFilters = () => {
  filters.search = ''
  filters.transaction_type = null
  filters.product_id = null
  filters.from_date = null
  filters.to_date = null
  filters.page = 1
  loadTransactions()
  loadSummary()
}

// ==================== FORMATTING FUNCTIONS ====================

const formatTransactionType = (type: string) => {
  return type?.replace(/_/g, ' ') || 'N/A'
}

const formatReferenceType = (type: string) => {
  return type?.replace(/_/g, ' ') || 'N/A'
}

const getTransactionTypeSeverity = (type: string) => {
  switch (type) {
    case 'purchase':
    case 'receive':
      return 'success'
    case 'sale':
    case 'issue':
      return 'danger'
    case 'adjustment':
      return 'warning'
    case 'transfer':
      return 'info'
    case 'return':
      return 'secondary'
    default:
      return 'secondary'
  }
}

const formatDateParam = (date: Date) => {
  return date.toISOString().split('T')[0]
}

const formatDate = (dateString: string) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatTime = (dateString: string) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatNumber = (value: string | number | null | undefined) => {
  if (value === null || value === undefined) return '0.00'
  return parseFloat(value.toString()).toFixed(2)
}

// ==================== WATCHERS ====================

watch([() => filters.from_date, () => filters.to_date], () => {
  loadSummary()
})

// ==================== LIFECYCLE ====================

onMounted(() => {
  loadTransactions()
  loadSummary()
  loadProducts()
})
</script>