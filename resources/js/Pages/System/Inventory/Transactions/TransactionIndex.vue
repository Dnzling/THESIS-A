<template>
  <div class="mx-auto x-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Inventory Transactions</h1>
        <p class="mt-1 text-sm text-gray-500">A history of stock received, issued, sold, and adjusted.</p>
      </div>
    </div>

    <Card>
      <template #content>
        <div class="flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-end">
          <div class="w-full lg:max-w-xs">
            <label class="block text-xs font-semibold text-gray-700 mb-1">Search</label>
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" placeholder="Reference, notes, product" class="w-full p-inputtext-sm" />
            </IconField>
          </div>
          <div class="w-full lg:w-44">
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
          <div class="w-full lg:w-52">
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
            <div class="w-full lg:w-64">
              <label class="block text-xs font-semibold text-gray-700 mb-1">Date Range</label>
              <DatePicker v-model="dateRange" selectionMode="range" dateFormat="M d, yy" :manualInput="false" class="w-full" showIcon :showButtonBar="false" placeholder="Select dates" />
            </div>
          <Button v-if="hasActiveFilters()" size="small" icon="pi pi-filter-slash" label="Clear Filter" severity="danger" outlined @click="resetFilters" />
        </div>
        <hr class="my-4 border-slate-200" />
        <div v-if="loading" class="space-y-3">
          <div class="grid grid-cols-9 gap-3 text-xs text-gray-400">
            <Skeleton v-for="column in 9" :key="`header-${column}`" height="24px" />
          </div>
          <div v-for="i in 8" :key="i" class="grid grid-cols-9 gap-3">
            <Skeleton v-for="column in 9" :key="column" height="20px" />
          </div>
        </div>

        <DataTable
          v-else
          :value="transactions"
          paginator
          rowHover
          class="p-datatable-sm text-sm"
          :rows="filters.per_page"
          :totalRecords="totalRecords"
          :lazy="true"
          dataKey="id"
          @page="onPage"
          @sort="onSort"
          :sortField="filters.sort_field"
          :sortOrder="filters.sort_direction === 'asc' ? 1 : -1"
        >
          <Column field="transaction_date" header="Date" sortable style="min-width: 145px">
            <template #body="{ data }">
              <div>
                <div class="font-medium text-gray-900">{{ formatDate(data.transaction_date) }}</div>
                <div class="text-xs text-gray-500">{{ formatTime(data.transaction_date) }}</div>
              </div>
            </template>
          </Column>

          <Column field="transaction_number" header="Reference" sortable style="min-width: 185px">
            <template #body="{ data }">
              <span class="font-mono text-xs font-semibold text-gray-900">{{ data.transaction_number || '—' }}</span>
            </template>
          </Column>

          <Column field="transaction_type" header="Type" style="min-width: 120px">
            <template #body="{ data }">
              <Tag :value="formatTransactionType(data.transaction_type)" :severity="getTransactionTypeSeverity(data.transaction_type)" />
            </template>
          </Column>

          <Column header="Product" style="min-width: 220px">
            <template #body="{ data }">
              <div>
                <div class="font-medium text-gray-900">{{ data.product?.product_name || 'Product unavailable' }}</div>
                <div class="text-xs text-gray-500">{{ data.variation?.variation_name || data.product?.sku || 'No variant' }}</div>
              </div>
            </template>
          </Column>

          <Column header="Branch" style="min-width: 140px">
            <template #body="{ data }">{{ data.branch?.name || '—' }}</template>
          </Column>

          <Column header="Qty" style="width: 120px">
            <template #body="{ data }">
              <div :class="Number(data.quantity_change) >= 0 ? 'text-green-600' : 'text-red-600'" class="font-semibold">
                {{ signedQuantity(data.quantity_change) }}
              </div>
            </template>
          </Column>

          <Column field="total_value" header="Value" style="width: 150px">
            <template #body="{ data }">
              <div class="font-medium text-gray-800">{{ formatCurrency(data.total_value) }}</div>
            </template>
          </Column>

          <Column header="Created By" style="min-width: 150px">
            <template #body="{ data }">
              <span class="text-slate-700">{{ employeeName(data.created_by) }}</span>
            </template>
          </Column>

          <Column style="width: 100px">
            <template #body="{ data }">
              <Button icon="pi pi-eye" size="small" label="View" outlined rounded aria-label="View transaction" @click="openDetailPage(data.id)" />
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
          <p>{{ formatQuantity(selectedTransaction.quantity_before) }} → {{ formatQuantity(selectedTransaction.quantity_after) }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">Change</p>
          <p :class="Number(selectedTransaction.quantity_change) >= 0 ? 'text-green-600' : 'text-red-600'" class="font-semibold">
            {{ signedQuantity(selectedTransaction.quantity_change) }}
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
          <p>{{ formatReferenceType(selectedTransaction.reference_type) }}{{ selectedTransaction.reference_id ? ` #${selectedTransaction.reference_id}` : '' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">Created By</p>
          <p>{{ employeeName(selectedTransaction.created_by) }}</p>
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
import DatePicker from 'primevue/datepicker'

const router = useRouter()
const toast = useToast()

const loading = ref(false)
const transactions = ref<any[]>([])
const products = ref<any[]>([])
const totalRecords = ref(0)

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

const formatDateParam = (date: Date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
const hasActiveFilters = () => Boolean(filters.search || filters.transaction_type || filters.product_id || filters.from_date || filters.to_date)
const formatQuantity = (value: string | number | null | undefined) => Number(value || 0).toLocaleString('en-PH')
const signedQuantity = (value: string | number | null | undefined) => `${Number(value || 0) > 0 ? '+' : ''}${formatQuantity(value)}`
const employeeName = (employee: any) => {
  if (!employee) return '—'
  const name = [employee.fname || employee.user?.fname, employee.lname || employee.user?.lname].filter(Boolean).join(' ')
  return name || `Employee #${employee.id ?? employee}`
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

const resetFilters = () => {
  filters.search = ''
  filters.transaction_type = null
  filters.product_id = null
  filters.from_date = null
  filters.to_date = null
  dateRange.value = null
  filters.page = 1
  loadTransactions()
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

const formatTransactionType = (type: string) => (type ? type.replace(/_/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase()) : '—')
const formatReferenceType = (type: string) => (type ? type.replace(/_/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase()) : '—')

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
  return Number.isNaN(d.getTime()) ? '—' : d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

const formatTime = (value?: string) => {
  if (!value) return '-'
  const d = new Date(value)
  return Number.isNaN(d.getTime()) ? '—' : d.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' })
}

const formatDateTime = (value?: string) => {
  if (!value) return '-'
  const d = new Date(value)
  return Number.isNaN(d.getTime()) ? '—' : d.toLocaleString('en-PH', { month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' })
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
  loadProducts()
})
</script>
