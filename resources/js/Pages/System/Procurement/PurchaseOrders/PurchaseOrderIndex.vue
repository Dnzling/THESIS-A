<template>
  <div class="space-y-6 p-4 md:p-6">
    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-bold text-gray-800">Purchase Orders</h1>
      </div>
      <!-- <Button v-if="canManagePurchaseOrders" label="New Purchase Order" icon="pi pi-plus" size="small"
        @click="goToCreatePO" /> -->
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <div class="flex items-center justify-between">
            <div class="">
              <p class="text-xs font-bold  uppercase tracking-wide">Total POs</p>
              <Skeleton v-if="loading" width="3rem" height="1.75rem" />
              <p v-else class="text-2xl font-bold text-black-600">{{ stats.totalCount }}</p>
            </div>
            <i class="pi pi-file text-4xl text-black-600"></i>
          </div>
        </template>
      </Card>
      <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-bold  uppercase tracking-wide">Sent to Supplier</p>
              <Skeleton v-if="loading" width="2rem" height="1.75rem" />
              <p v-else class="text-2xl font-bold text-black-600">{{ stats.pendingApproval }}</p>
            </div>
            <i class="pi pi-send text-4xl text-black-600"></i>
          </div>
        </template>
      </Card>
      <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-bold  uppercase tracking-wide">Total Amount</p>
              <Skeleton v-if="loading" width="6rem" height="1.75rem" />
              <p v-else class="text-2xl font-bold text-black-600">₱ {{ formatNumber(stats.totalAmount) }}</p>
            </div>
            <i class="pi pi-money-bill text-4xl text-black-600"></i>
          </div>
        </template>
      </Card>
      <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-bold  uppercase tracking-wide">Delayed Orders</p>
              <Skeleton v-if="loading" width="2rem" height="1.75rem" />
              <p v-else class="text-2xl font-bold text-red-600">{{ stats.delayedCount }}</p>
            </div>
            <i class="pi pi-clock text-4xl text-red-600"></i>
          </div>
        </template>
      </Card>
    </div>

    <!-- POs Table -->
    <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
      <template #header>
        <div class="m-4 mt-6 grid grid-cols-1 gap-3 md:grid-cols-5">
          <div>
            <IconField fluid>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" size="small" placeholder="Search PO, Supplier, or Created By" fluid
                @keyup.enter="applyFilters" />
            </IconField>
          </div>
          <div>
            <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value"
              placeholder="All Statuses" showClear fluid size="small" @change="applyFilters" />
          </div>
          <div>
            <Select v-model="filters.supplier_id" :options="suppliers" optionLabel="supplier_name" optionValue="id"
              placeholder="All Suppliers" showClear fluid size="small" @change="applyFilters" />
          </div>
          <div>
            <DatePicker v-model="dateRange" selectionMode="range" :manualInput="false" placeholder="Order date range"
              dateFormat="M d, yy" show-icon size="small" @date-select="applyFilters" fluid />
          </div>
          <div class="flex items-center gap-2">
            <Button label="Clear Filters" severity="secondary" size="small" outlined @click="resetFilters" />
          </div>
        </div>
      </template>
      <template #content>
        <div v-if="loading" class="space-y-3 px-4 pb-4">
          <div class="grid grid-cols-7 gap-4 border-b border-slate-100 px-3 py-3"><Skeleton v-for="cell in 7" :key="`head-${cell}`" height="0.75rem" /></div>
          <div v-for="row in 6" :key="`skeleton-${row}`" class="grid grid-cols-7 gap-4 border-b border-slate-50 px-3 py-3"><Skeleton v-for="cell in 7" :key="`cell-${row}-${cell}`" height="1.25rem" /></div>
        </div>
        <DataTable v-else rowHover :value="orders" :paginator="true" :rows="10"
          :rowsPerPageOptions="[10, 20, 50]" responsive-layout="scroll" class="p-datatable-sm">

          <Column header="Order Date" style="width: 9%" sortable>
            <template #body="{ data }">
              <span class="text-sm text-gray-700">{{ formatDate(data.order_date) }}</span>
            </template>
          </Column>
          <!-- PO Number -->
          <Column field="po_number" header="PO No." style="width: 15%" sortable>
            <template #body="{ data }">
              <RouterLink :to="`/procurement/purchase-orders/${data.id}`"
                class="text-blue-600 text-xs hover:underline font-semibold">
                {{ data.po_number }}
              </RouterLink>
            </template>
          </Column>


          <!-- Supplier -->
          <Column header="Supplier" style="width: 15%">
            <template #body="{ data }">
              <div>
                <p class="font-semibold">{{ data.supplier?.supplier_name }}</p>
                <p class="text-xs text-gray-500">{{ data.supplier?.supplier_code }}</p>
              </div>
            </template>
          </Column>

          <Column header="Created By" style="width: 14%">
            <template #body="{ data }">
              <span class="text-sm text-gray-700">{{ getPersonName(data.created_by) }}</span>
            </template>
          </Column>

          <!-- Amount & Items -->
          <Column header="Amount / Items" style="width: 13%">
            <template #body="{ data }">
              <div class="text-sm">
                <p class="text-green-600 font-bold">₱ {{ formatNumber(parseFloat(data.total_amount)) }}</p>
              </div>
            </template>
          </Column>

          <!-- Status -->
          <Column header="Status" style="width: 15%">
            <template #body="{ data }">
              <Badge :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>

          <!-- Actions -->
          <Column header="Actions" style="width: 10%" headerStyle="text-align: center">
            <template #body="{ data }">
              <div class="justify-center">
                <Button icon="pi pi-eye" label="View" outlined rounded @click="viewPO(data)" v-tooltip="'View'" size="small" />
              </div>
            </template>
          </Column>

          <!-- Empty State -->
          <template #empty>
            <div class="text-center py-8">
              <i class="pi pi-inbox text-4xl text-gray-300" />
              <p class="text-gray-500 mt-2">No purchase orders found</p>
            </div>
          </template>
        </DataTable>

      </template>
    </Card>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Toast from 'primevue/toast'
import Skeleton from 'primevue/skeleton'
import procurementService from '../../../../services/procurement.service'
import { useAuthStore } from '../../../../stores/auth'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const canManagePurchaseOrders = computed(() => authStore.hasPermission('procurement.purchase_orders.manage'))

// State
const orders = ref<any[]>([])
const suppliers = ref<any[]>([])
const loading = ref(true)
const stats = ref({
  totalCount: 0,
  pendingApproval: 0,
  totalAmount: 0,
  delayedCount: 0,
})

const filters = ref({
  search: '',
  status: '',
  supplier_id: '',
  date_from: '',
  date_to: '',
})
const dateRange = ref<Date[] | null>(null)

const statusOptions = ref([
  { label: 'Draft', value: 'draft' },
  { label: 'Sent to Supplier', value: 'sent_to_supplier' },
  { label: 'Supplier Accepted', value: 'supplier_accepted' },
  { label: 'In Transit', value: 'in_transit' },
  { label: 'Out for Delivery', value: 'out_for_delivery' },
  { label: 'Delivered', value: 'delivered' },
  { label: 'Supplier Declined', value: 'declined_supplier' },
  { label: 'Revision Requested', value: 'revision_requested' },
  { label: 'Cancelled', value: 'cancelled' },
])

// Methods
async function loadOrders() {
  loading.value = true
  try {
    // Debug: Check token and user inf

    const params: any = { ...filters.value }
    if (dateRange.value?.[0]) params.start_date = formatFilterDate(dateRange.value[0])
    if (dateRange.value?.[1]) params.end_date = formatFilterDate(dateRange.value[1])
    delete params.date_from
    delete params.date_to

    const response = await procurementService.getPurchaseOrders(params)
    console.log('📦 API Response:', response)
    console.log('📊 Orders array:', response.data?.data)
    console.log('📊 Total count:', response.data?.total)

    orders.value = response.data?.data || []
    console.log('✅ Orders loaded:', orders.value.length, 'items')
    calculateStats()
  } catch (error: any) {
    console.error('❌ Error loading orders:', error)
    console.error('Status:', error.response?.status)
    console.error('Message:', error.response?.data?.message)
  } finally {
    loading.value = false
  }
}

function formatFilterDate(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function applyFilters() {
  loadOrders()
}

function resetFilters() {
  filters.value = {
    search: '',
    status: '',
    supplier_id: '',
    date_from: '',
    date_to: '',
  }
  dateRange.value = null
  loadOrders()
}

async function loadSuppliers() {
  try {
    const response = await procurementService.getSuppliers({ per_page: 100 })
    suppliers.value = response.data?.data || []
  } catch (error) {
    console.error('Failed to load suppliers', error)
  }
}

function calculateStats() {
  stats.value.totalCount = orders.value.length
  stats.value.pendingApproval = orders.value.filter((o: any) => o.status === 'pending_finance_approval').length
  stats.value.totalAmount = orders.value.reduce((sum: number, o: any) => sum + (parseFloat(o.total_amount) || 0), 0)
  stats.value.delayedCount = orders.value.filter((o: any) => isOverdue(o.expected_delivery_date)).length
}

function statusSeverity(status: string): string {
  if (['supplier_accepted', 'delivered'].includes(status)) return 'success'
  if (['sent_to_supplier', 'in_transit', 'out_for_delivery'].includes(status)) return 'warning'
  if (['cancelled', 'declined_supplier'].includes(status)) return 'danger'
  return 'secondary'
}

function financeSeverity(status: string): string {
  if (['paid'].includes(status)) return 'success'
  if (['finance_approved', 'processing'].includes(status)) return 'info'
  if (['pending', 'partially_paid'].includes(status)) return 'warning'
  if (status === 'overdue') return 'danger'
  return 'secondary'
}

function formatFinanceStatus(status: string): string {
  return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

function formatStatus(status: string): string {
  if (!status) return '-'
  const map: Record<string, string> = {
    draft: 'Draft',
    sent_to_supplier: 'Sent to Supplier',
    supplier_accepted: 'Supplier Accepted',
    in_transit: 'In Transit',
    out_for_delivery: 'Out for Delivery',
    delivered: 'Delivered',
    declined_supplier: 'Supplier Declined',
    revision_requested: 'Revision Requested',
    cancelled: 'Cancelled',
  }
  return map[status] || status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

function getPersonName(person: any): string {
  const source = person?.user || person
  const name = [source?.fname, source?.lname].filter(Boolean).join(' ').trim()
  return name || source?.full_name || 'N/A'
}

function formatDate(date: string): string {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

function formatNumber(value: number): string {
  return new Intl.NumberFormat('en-PH').format(value)
}

function isOverdue(date: string): boolean {
  if (!date) return false
  return new Date(date) < new Date()
}

function isDueSoon(date: string): boolean {
  if (!date) return false
  const daysUntilDue = Math.ceil((new Date(date).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24))
  return daysUntilDue >= 0 && daysUntilDue <= 3
}

function goToCreatePO() {
  router.push({ name: 'procurement.purchase-orders.create' })
}

function viewPO(po: any) {
  router.push({
    name: 'procurement.purchase-orders.detail',
    params: { id: po.id },
  })
}

onMounted(() => {
  loadSuppliers()
  loadOrders()
})
</script>
