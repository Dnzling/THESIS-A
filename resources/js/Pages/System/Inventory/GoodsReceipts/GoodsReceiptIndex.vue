<template>
  <div class="p-6 min-h-screen space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-xl font-bold text-gray-900">Goods Receipts</h1>
        <p class="text-xs text-gray-600 mt-1">Track received supplies and inventory receiving records</p>
      </div>
      <Button label="New Receipt" icon="pi pi-plus" size="small" @click="$router.push('/inventory/goods-receipts/create')" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card>
        <template #content>
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total Receipts</p>
          <p class="text-2xl font-bold mt-2">{{ stats.total }}</p>
        </template>
      </Card>
      <Card>
        <template #content>
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Full Receipts</p>
          <p class="text-2xl font-bold mt-2">{{ stats.full }}</p>
  
        </template>
      </Card>
      <Card>
        <template #content>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Partial</p>
            <p class="text-2xl font-bold  mt-2">{{ stats.partial }}</p>
        </template>
      </Card>
      <Card>
        <template #content>
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Damaged / Rejected</p>
          <p class="text-2xl font-bold  mt-2">{{ stats.damaged }}</p>
        </template>
      </Card>
    </div>

    <Card>
      <template #header>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end m-4 mt-6">
          <div class="flex flex-col gap-2">
            <label class="text-xs font-medium text-gray-600">Search</label>
            <InputText v-model="searchTerm" placeholder="Search GRN, PO, or supplier" size="small" @keyup.enter="applyFilters" />
          </div>
          <div class="flex flex-col gap-2">
            <label class="text-xs font-medium text-gray-600">Status</label>
            <Select v-model="statusFilter" :options="statusOptions" option-label="label" option-value="value" placeholder="All statuses" showClear size="small" @change="applyFilters" />
          </div>
          <div class="flex flex-col gap-2 md:col-span-2">
            <label class="text-xs font-medium text-gray-600">Receipt date</label>
            <DatePicker v-model="dateRange" selectionMode="range" :manualInput="false" placeholder="Select date range" dateFormat="M d, yy" size="small" showButtonBar showIcon @date-select="applyFilters" />
          </div>
          <div class="flex justify-end gap-2">
            <Button label="Clear Filters" severity="secondary" outlined size="small" @click="clearFilters" />
          </div>
        </div>
      </template>
      <template #content>

        <DataTable :value="receipts" :loading="loading" class="p-datatable-sm" rowHover responsive-layout="scroll" paginator :rows="perPage" :totalRecords="total" :first="(currentPage - 1) * perPage" :rowsPerPageOptions="[15, 25, 50]" @page="onPageChange">
          <Column header="Date" style="width: 140px">
            <template #body="{ data }">
              <span class="text-sm text-gray-700">{{ formatDate(data.receipt_date) }}</span>
            </template>
          </Column>
          <Column field="grn_number" header="GRN" />
          <Column
            field="purchase_order.po_number"
            header="Purchase Order"
            :body="({ data }) => data.purchase_order?.po_number || data.po_number"
          />
          <Column header="Supplier">
            <template #body="{ data }">
              {{ data.purchase_order?.supplier?.supplier_name || data.supplier_name || '-' }}
            </template>
          </Column>
        
          <Column header="Receipt Status">
            <template #body="{ data }">
              <Badge :value="formatStatus(data.receipt_status)" :severity="statusSeverity(data.receipt_status)" />
            </template>
          </Column>
          <Column header="Qty Items">
            <template #body="{ data }">
              {{ data.items_count ?? data.items?.length ?? 0 }}
            </template>
          </Column>
          <Column header="Actions" style="width: 180px">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button icon="pi pi-eye"  text rounded size="small" @click="goToDetail(data.id)" v-tooltip="'View detail'" />
                <Button icon="pi pi-file-pdf" severity="danger" text rounded size="small" @click="printPdf(data.id)" v-tooltip="'Print PDF receipt'" />
              </div>
            </template>
          </Column>
          <template #empty>
            <div class="text-center py-12">
              <i class="pi pi-inbox text-5xl text-gray-300 mb-4"></i>
              <p class="text-lg text-gray-600">No goods receipts found</p>
              <p class="text-sm text-gray-500 mt-1">Try adjusting your filters.</p>
            </div>
          </template>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import procurementService from '../../../../services/procurement.service'
import InputText from 'primevue/inputtext'
const loading = ref(false)

const receipts = ref<any[]>([])
const searchTerm = ref('')
const statusFilter = ref<string | null>(null)
const dateRange = ref<Date[] | null>(null)
const currentPage = ref(1)
const perPage = ref(15)
const total = ref(0)

const statusOptions = [
  { label: 'Full', value: 'full' },
  { label: 'Partial', value: 'partial' },
  { label: 'Damaged', value: 'damaged' },
  { label: 'Rejected', value: 'rejected' },
]

const stats = computed(() => ({
  total: receipts.value.length,
  full: receipts.value.filter((r) => r.receipt_status === 'full').length,
  partial: receipts.value.filter((r) => r.receipt_status === 'partial').length,
  damaged: receipts.value.filter((r) => ['damaged', 'rejected'].includes(r.receipt_status)).length,
}))

const formatFilterDate = (date: Date): string => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const formatStatus = (status: string | null | undefined): string => {
  if (!status) return '-'
  return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const loadReceipts = async (page = 1) => {
  loading.value = true
  try {
    const params: Record<string, any> = { page, per_page: perPage.value }
    if (searchTerm.value.trim()) params.search = searchTerm.value.trim()
    if (statusFilter.value) params.receipt_status = statusFilter.value
    if (dateRange.value?.[0]) params.start_date = formatFilterDate(dateRange.value[0])
    if (dateRange.value?.[1]) params.end_date = formatFilterDate(dateRange.value[1])

    const response = await procurementService.getGoodsReceipts(params)
    // procurementService already returns response.data, so this is the paginator:
    // { current_page, data: [], total, ... }.
    const paginator = response?.data?.current_page !== undefined
      ? response.data
      : response
    receipts.value = Array.isArray(paginator?.data) ? paginator.data : []
    total.value = Number(paginator?.total || 0)
    currentPage.value = page
  } catch (error) {
    console.error('Failed to load goods receipts', error)
    receipts.value = []
  } finally {
    loading.value = false
  }
}

const applyFilters = () => loadReceipts(1)
const clearFilters = () => {
  searchTerm.value = ''
  statusFilter.value = null
  dateRange.value = null
  applyFilters()
}
const onPageChange = (event: any) => {
  perPage.value = event.rows
  loadReceipts(event.page + 1)
}

const statusSeverity = (status: string) => {
  if (status === 'full') return 'success'
  if (status === 'partial') return 'warn'
  if (['damaged', 'rejected'].includes(status)) return 'danger'
  return 'secondary'
}

const formatDate = (value: string | null | undefined): string => {
  if (!value) return '-'
  const d = new Date(value)
  return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

const router = useRouter()
const goToDetail = (id: number) => {
  router.push({ name: 'inventory.goods-receipts.detail', params: { id } })
}

const printPdf = async (id: number) => {
  try {
    const response = await procurementService.generateGRPdf(id)
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(blob)
    window.open(url, '_blank')
  } catch (error) {
    console.error('Failed to download PDF', error)
  }
}

onMounted(() => {
  loadReceipts()
})
</script>
