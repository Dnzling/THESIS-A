<template>
  <div class="space-y-6 p-4 md:p-6">
    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-bold text-gray-800">Purchase Orders</h1>
        <p class="text-gray-500 mt-1">Manage purchase orders and track delivery status</p>
      </div>
      <Button label="New Purchase Order" icon="pi pi-plus" class="p-button-lg" @click="goToCreatePO" />
    </div>
  
    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <div class="text-center">
            <p class="text-gray-500 text-sm">Total POs</p>
            <p class="text-3xl font-bold text-blue-600">{{ stats.totalCount }}</p>
          </div>
        </template>
      </Card>
      <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <div class="text-center">
            <p class="text-gray-500 text-sm">Sent to Supplier</p>
            <p class="text-2xl font-bold text-orange-600">{{ stats.pendingApproval }}</p>
          </div>
        </template>
      </Card>
      <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <div class="text-center">
            <p class="text-gray-500 text-sm">Total Amount</p>
            <p class="text-2xl font-bold text-purple-600">₱ {{ formatNumber(stats.totalAmount) }}</p>
          </div>
        </template>
      </Card>
      <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <div class="text-center">
            <p class="text-gray-500 text-sm">Delayed Orders</p>
            <p class="text-2xl font-bold text-red-600">{{ stats.delayedCount }}</p>
          </div>
        </template>
      </Card>
    </div>
  
    <!-- Filters -->
    <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText v-model="filters.search" placeholder="Search PO No" class="w-full" @keyup.enter="loadOrders" />
          </IconField>
  
          <Select v-model="filters.status" :options="statusOptions" placeholder="All Status" showClear
            @change="loadOrders" fluid />
  
          <Select v-model="filters.supplier_id" :options="suppliers" optionLabel="supplier_name" optionValue="id"
            placeholder="All Suppliers" showClear @change="loadOrders" fluid />
  
          <DatePicker v-model="filters.date_from" placeholder="From Date" @date-select="loadOrders" fluid />
  
          <DatePicker v-model="filters.date_to" placeholder="To Date" @date-select="loadOrders" fluid />
  
          <Button icon="pi pi-refresh" class="p-button-outlined p-button-rounded" @click="loadOrders"
            v-tooltip="'Refresh'" />
        </div>
      </template>
    </Card>
  
    <!-- POs Table -->
    <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
      <template #content>
        <DataTable v-if="!loading" :value="orders" :loading="loading" :paginator="true" :rows="15"
          responsive-layout="scroll" class="p-datatable-sm">
          <!-- PO Number -->
          <Column field="po_number" header="PO No." style="width: 10%" sortable>
            <template #body="{ data }">
              <RouterLink :to="`/procurement/purchase-orders/${data.id}`"
                class="text-blue-600 hover:underline font-semibold">
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
  
          <!-- Source (Stock Request or Manual) -->
          <Column header="Source" style="width: 10%">
            <template #body="{ data }">
              <Badge v-if="data.stock_order_request_id" value="Stock Request" severity="info" class="text-xs" />
              <Badge v-else value="Manual Entry" severity="secondary" class="text-xs" />
            </template>
          </Column>
  
          <!-- Dates -->
          <Column header="Order" style="width: 14%">
            <template #body="{ data }">
              <div class="text-sm space-y-1">
                <p>Order: {{ formatDate(data.order_date) }}</p>
              
              </div>
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
          <Column header="Status" style="width: 11%">
            <template #body="{ data }">
              <Badge :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
  
          <!-- Actions -->
          <Column header="Actions" style="width: 10%" headerStyle="text-align: center">
            <template #body="{ data }">
              <div class="flex gap-2 justify-center">
                <Button icon="pi pi-eye" text rounded @click="viewPO(data)" v-tooltip="'View'" />
                <Button icon="pi pi-pencil" text rounded severity="info" @click="editPO(data)" v-tooltip="'Edit'" />
                <Button
                  v-if="data.status === 'approved'"
                  icon="pi pi-send"
                  text
                  rounded
                  severity="success"
                  @click="sendToSupplier(data)"
                  v-tooltip="'Send to Supplier'"
                />
                <Button v-if="data.status !== 'draft'" icon="pi pi-print" text rounded @click="printPO(data)"
                  v-tooltip="'Print'" />
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
  
        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-8">
          <ProgressSpinner />
        </div>
      </template>
    </Card>
  
    <Toast />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Toast from 'primevue/toast'
import procurementService from '../../../../services/procurement.service'

const router = useRouter()
const toast = useToast()

// State
const orders = ref<any[]>([])
const suppliers = ref<any[]>([])
const loading = ref(false)
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

const statusOptions = ref([
  { label: 'Draft', value: 'draft' },
  { label: 'Sent to Supplier', value: 'sent_to_supplier' },
  { label: 'Supplier Accepted', value: 'supplier_accepted' },
  { label: 'In Transit', value: 'in_transit' },
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

    const response = await procurementService.getPurchaseOrders(filters.value)
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
  if (['sent_to_supplier', 'in_transit'].includes(status)) return 'warning'
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
    delivered: 'Delivered',
    declined_supplier: 'Supplier Declined',
    revision_requested: 'Revision Requested',
    cancelled: 'Cancelled',
  }
  return map[status] || status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
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

function editPO(po: any) {
  router.push({
    name: 'procurement.purchase-orders.create',
    params: { id: po.id },
  })
}

async function sendToSupplier(po: any) {
  try {
    await procurementService.sendPurchaseOrder(po.id)
    toast.add({
      severity: 'success',
      summary: 'Sent',
      detail: `PO ${po.po_number} sent to supplier.`,
      life: 3000,
    })
    loadOrders()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to send PO to supplier',
      life: 3000,
    })
  }
}

function printPO(po: any) {
  toast.add({
    severity: 'info',
    summary: 'Print',
    detail: `Printing PO ${po.po_number}...`,
    life: 3000,
  })
  // TODO: Implement PDF print functionality
}

onMounted(() => {
  loadSuppliers()
  loadOrders()
})
</script>
