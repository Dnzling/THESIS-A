<template>
  <div class="p-4 min-h-screen space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-gray-800">Inventory Transactions</h1>
        <p class="text-xs text-gray-500 mt-0.5">Compact movement log with quick detail preview.</p>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
      <Card>
        <template #content>
          <div>
            <p class="text-xs text-gray-500">Total</p>
            <p class="text-xl font-bold text-gray-800">{{ summary.total_transactions || 0 }}</p>
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div>
            <p class="text-xs text-gray-500">Moved Value</p>
            <p class="text-xl font-bold text-green-600">{{ formatCurrency(summary.total_value_moved) }}</p>
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div>
            <p class="text-xs text-gray-500">Today</p>
            <p class="text-xl font-bold text-blue-600">{{ summary.today_transactions || 0 }}</p>
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div>
            <p class="text-xs text-gray-500">Products Affected</p>
            <p class="text-xl font-bold text-purple-600">{{ summary.unique_products_affected || 0 }}</p>
          </div>
        </template>
      </Card>
    </div>

    <Card>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
          <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-gray-700 mb-1">Search</label>
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" placeholder="Reference, notes, product" class="w-full p-inputtext-sm" />
            </IconField>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Type</label>
            <Select
              v-model="filters.transaction_type"
              :options="transactionTypes"
              optionLabel="label"
              optionValue="value"
              placeholder="All"
              showClear
              class="w-full"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Product</label>
            <Select
              v-model="filters.product_id"
              :options="products"
              optionLabel="product_name"
              optionValue="id"
              placeholder="All"
              showClear
              filter
              class="w-full"
            />
          </div>
            <div class="md:col-span-2">
              <label class="block text-xs font-semibold text-gray-700 mb-1">Date Range</label>
              <Calendar v-model="dateRange" selectionMode="range" dateFormat="yy-mm-dd" class="w-full" showIcon :showButtonBar="false" />
            </div>
        </div>
        <div class="mt-3 flex justify-end">
          <Button size="small" icon="pi pi-filter-slash" label="Reset" severity="secondary" outlined @click="resetFilters" />
        </div>
      </template>
    </Card>

    <Card>
      <template #content>
        <div v-if="loading" class="space-y-3">
          <div class="grid grid-cols-6 gap-3 text-xs text-gray-400">
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
          </div>
          <div v-for="i in 8" :key="i" class="grid grid-cols-6 gap-3">
            <Skeleton height="20px" class="col-span-1" />
            <Skeleton height="20px" class="col-span-1" />
            <Skeleton height="20px" class="col-span-1" />
            <Skeleton height="20px" class="col-span-1" />
            <Skeleton height="20px" class="col-span-1" />
            <Skeleton height="20px" class="col-span-1" />
          </div>
        </div>

        <DataTable
          v-else
          :value="transactions"
          paginator
          stripedRows
          class="p-datatable-sm p-datatable-fluid"
          :rows="filters.per_page"
          :totalRecords="totalRecords"
          :lazy="true"
          dataKey="id"
          @page="onPage"
          @sort="onSort"
          :sortField="filters.sort_field"
          :sortOrder="filters.sort_direction === 'asc' ? 1 : -1"
          @row-click="onRowClick"
          :rowClass="rowClass"
        >
          <Column field="transaction_date" header="Date" sortable style="width: 130px">
            <template #body="{ data }">
              <div class="text-xs">
                <div>{{ formatDate(data.transaction_date) }}</div>
                <div class="text-gray-500">{{ formatTime(data.transaction_date) }}</div>
              </div>
            </template>
          </Column>

          <Column field="transaction_number" header="Reference" sortable style="width: 170px">
            <template #body="{ data }">
              <span class="font-mono text-xs">{{ data.transaction_number || '-' }}</span>
            </template>
          </Column>

          <Column field="transaction_type" header="Type" style="width: 120px">
            <template #body="{ data }">
              <Tag :value="formatTransactionType(data.transaction_type)" :severity="getTransactionTypeSeverity(data.transaction_type)" />
            </template>
          </Column>

          <Column header="Product" style="min-width: 220px">
            <template #body="{ data }">
              <div class="text-sm">
                <div class="font-medium">{{ data.product?.product_name || '-' }}</div>
                <div class="text-xs text-gray-500">
                  {{ data.variation?.variation_name ? `Variant: ${data.variation.variation_name}` : 'No variant' }}
                </div>
              </div>
            </template>
          </Column>

          <Column header="Qty" style="width: 120px">
            <template #body="{ data }">
              <div :class="Number(data.quantity_change) >= 0 ? 'text-green-600' : 'text-red-600'" class="font-semibold">
                {{ Number(data.quantity_change) >= 0 ? '+' : '' }}{{ data.quantity_change ?? 0 }}
              </div>
            </template>
          </Column>

          <Column field="total_value" header="Value" style="width: 130px">
            <template #body="{ data }">
              <div class="text-sm">{{ formatCurrency(data.total_value) }}</div>
            </template>
          </Column>

          <Column field="reference_type" header="Source" style="width: 140px">
            <template #body="{ data }">
              <div class="text-xs">
                <div>{{ formatReferenceType(data.reference_type) }}</div>
                <div class="text-gray-500">ID: {{ data.reference_id || '-' }}</div>
              </div>
            </template>
          </Column>

          <Column header="Actions" style="width: 110px">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button icon="pi pi-eye" size="small" severity="info" text rounded @click="openQuickDetail(data)" />
                <Button icon="pi pi-external-link" size="small" severity="secondary" text rounded @click="openDetailPage(data.id)" />
              </div>
            </template>
          </Column>

          <template #empty>
            <div class="text-center py-10 text-gray-500">
              <i class="pi pi-inbox text-3xl block mb-3"></i>
              <div class="font-semibold">No transactions found</div>
              <div class="text-sm">Try adjusting filters or date range to view records.</div>
            </div>
          </template>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="quickDetailVisible" header="Transaction Details" modal class="w-full max-w-3xl">
      <div v-if="quickDetailLoading" class="py-10 flex justify-center">
        <ProgressSpinner style="width: 32px; height: 32px" strokeWidth="6" />
      </div>
      <div v-else-if="selectedTransaction" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
          <p class="text-xs text-gray-500">Reference</p>
          <p class="font-mono">{{ selectedTransaction.transaction_number || '-' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">Type</p>
          <Tag :value="formatTransactionType(selectedTransaction.transaction_type)" :severity="getTransactionTypeSeverity(selectedTransaction.transaction_type)" />
        </div>
        <div>
          <p class="text-xs text-gray-500">Date</p>
          <p>{{ formatDateTime(selectedTransaction.transaction_date) }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">Branch</p>
          <p>{{ selectedTransaction.branch?.name || '-' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">Product</p>
          <p>{{ selectedTransaction.product?.product_name || '-' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">Variant</p>
          <p>{{ selectedTransaction.variation?.variation_name || 'N/A' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">Quantity Before/After</p>
          <p>{{ selectedTransaction.quantity_before ?? 0 }} -> {{ selectedTransaction.quantity_after ?? 0 }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">Change</p>
          <p :class="Number(selectedTransaction.quantity_change) >= 0 ? 'text-green-600' : 'text-red-600'" class="font-semibold">
            {{ Number(selectedTransaction.quantity_change) >= 0 ? '+' : '' }}{{ selectedTransaction.quantity_change ?? 0 }}
          </p>
        </div>
        <div>
          <p class="text-xs text-gray-500">Unit Cost</p>
          <p>{{ formatCurrency(selectedTransaction.unit_cost) }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">Total Value</p>
          <p class="font-semibold">{{ formatCurrency(selectedTransaction.total_value) }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">Source</p>
          <p>{{ formatReferenceType(selectedTransaction.reference_type) }} ({{ selectedTransaction.reference_id || '-' }})</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">Created By</p>
          <p>{{ selectedTransaction.created_by ? `${selectedTransaction.created_by.fname} ${selectedTransaction.created_by.lname}` : '-' }}</p>
        </div>
        <div class="md:col-span-2">
          <p class="text-xs text-gray-500">Notes</p>
          <p>{{ selectedTransaction.notes || 'No notes provided' }}</p>
        </div>
      </div>
      <template #footer>
        <Button label="Close" size="small" text severity="secondary" @click="quickDetailVisible = false" />
        <Button
          v-if="selectedTransaction?.id"
          label="Open Full Page"
          size="small"
          icon="pi pi-external-link"
          severity="info"
          @click="openDetailPage(selectedTransaction.id)"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '../../../../services/inventory.service'
import Calendar from 'primevue/calendar'

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
  unique_products_affected: 0,
})

const quickDetailVisible = ref(false)
const quickDetailLoading = ref(false)
const selectedTransaction = ref<any | null>(null)

const filters = reactive({
  search: '',
  transaction_type: null as string | null,
  product_id: null as number | null,
  from_date: null as Date | null,
  to_date: null as Date | null,
  page: 1,
  per_page: 15,
  sort_field: 'transaction_date',
  sort_direction: 'desc' as 'asc' | 'desc',
})

const dateRange = ref<[Date | null, Date | null] | null>(null)

const transactionTypes = [
  { label: 'Purchase', value: 'purchase' },
  { label: 'Sale', value: 'sale' },
  { label: 'Adjustment', value: 'adjustment' },
  { label: 'Transfer', value: 'transfer' },
  { label: 'Return', value: 'return' },
  { label: 'Issue', value: 'issue' },
  { label: 'Receive', value: 'receive' },
]

const formatDateParam = (date: Date) => date.toISOString().split('T')[0]

const rowClass = (data: any) => {
  return {
    'cursor-pointer hover:bg-gray-50': true,
  }
}

const loadTransactions = async () => {
  loading.value = true
  try {
    const params: any = {
      page: filters.page,
      per_page: filters.per_page,
      sort_by: filters.sort_field,
      sort_order: filters.sort_direction,
    }
    if (filters.search) params.search = filters.search
    if (filters.transaction_type) params.transaction_type = filters.transaction_type
    if (filters.product_id) params.product_id = filters.product_id
    if (filters.from_date) params.from_date = formatDateParam(filters.from_date)
    if (filters.to_date) params.to_date = formatDateParam(filters.to_date)

    const response = await inventoryService.getTransactions(params)
    if (response.success) {
      if (Array.isArray(response.data?.data)) {
        transactions.value = response.data.data
        totalRecords.value = Number(response.data.total || 0)
      } else if (Array.isArray(response.data)) {
        transactions.value = response.data
        totalRecords.value = response.data.length
      } else {
        transactions.value = []
        totalRecords.value = 0
      }
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to load transactions',
      life: 3000,
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
    if (response.success && response.data) summary.value = response.data
  } catch {
    // no-op
  }
}

const loadProducts = async () => {
  try {
    const response = await inventoryService.getProducts({ per_page: 1000 })
    if (response.success) {
      products.value = Array.isArray(response.data?.data) ? response.data.data : (Array.isArray(response.data) ? response.data : [])
    }
  } catch {
    products.value = []
  }
}

const onPage = (event: any) => {
  filters.page = event.page + 1
  filters.per_page = event.rows
  loadTransactions()
}

const onSort = (event: any) => {
  filters.sort_field = event.sortField || 'transaction_date'
  filters.sort_direction = event.sortOrder === 1 ? 'asc' : 'desc'
  loadTransactions()
}

const onRowClick = (event: any) => {
  const row = event.data
  if (row?.id) openDetailPage(row.id)
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

const openQuickDetail = async (row: any) => {
  quickDetailVisible.value = true
  quickDetailLoading.value = true
  try {
    const response = await inventoryService.getTransaction(Number(row.id))
    selectedTransaction.value = response?.data || row
  } catch {
    selectedTransaction.value = row
  } finally {
    quickDetailLoading.value = false
  }
}

const openDetailPage = (id: number) => {
  quickDetailVisible.value = false
  router.push({ name: 'inventory.transactions.detail', params: { id } })
}

const formatTransactionType = (type: string) => (type ? type.replace(/_/g, ' ') : 'N/A')
const formatReferenceType = (type: string) => (type ? type.replace(/_/g, ' ') : 'N/A')

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

const formatDate = (value?: string) => {
  if (!value) return '-'
  const d = new Date(value)
  return Number.isNaN(d.getTime()) ? '-' : d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })
}

const formatTime = (value?: string) => {
  if (!value) return '-'
  const d = new Date(value)
  return Number.isNaN(d.getTime()) ? '-' : d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

const formatDateTime = (value?: string) => {
  if (!value) return '-'
  const d = new Date(value)
  return Number.isNaN(d.getTime()) ? '-' : d.toLocaleString('en-US', { month: 'short', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const formatCurrency = (value: string | number | null | undefined) => {
  const amount = Number(value || 0)
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
  }).format(amount)
}

let filterTimer: ReturnType<typeof setTimeout> | null = null
watch(
  () => [filters.search, filters.transaction_type, filters.product_id, filters.from_date, filters.to_date],
  () => {
    filters.page = 1
    if (filterTimer) clearTimeout(filterTimer)
    filterTimer = setTimeout(() => {
      loadTransactions()
      loadSummary()
    }, 250)
  },
)

watch(dateRange, (val) => {
  if (!val || !Array.isArray(val)) {
    filters.from_date = null
    filters.to_date = null
    return
  }
  const [from, to] = val
  filters.from_date = from
  filters.to_date = to
})

onMounted(() => {
  loadTransactions()
  loadSummary()
  loadProducts()
})
</script>
