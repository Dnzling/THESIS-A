<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <!-- iOS-style Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Purchase Orders</h1>
      </div>
    </div>

    <!-- iOS-style Filters Card -->
    <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <template #header>
        <div class="px-6 pt-6">
          <div class="flex items-center gap-2">
          </div>
        </div>
      </template>
      
      <template #content>
        <div class="p-6 pt-2">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search Input -->
            <div class="md:col-span-2 space-y-2">
              <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Search</label>
              <div class="relative">
                <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search by PO number"
                  class="w-full pl-9 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                  @input="onSearch"
                />
              </div>
            </div>

            <!-- Status Filter -->
            <div class="space-y-2">
              <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Status</label>
              <select
                v-model="statusFilter"
                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all appearance-none"
                @change="onFilterChange"
              >
                <option value="">All Statuses</option>
                <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </div>

            <!-- Reset Button -->
            <div class="flex items-end">
              <button
                @click="resetFilters"
                class="w-full px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl text-sm transition-colors flex items-center justify-center gap-2 border border-gray-200"
              >
                <i class="pi pi-times"></i>
                <span>Reset</span>
              </button>
            </div>
          </div>
        </div>
      </template>
    </Card>

    <!-- POs Table Card -->
    <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <template #header>
        <div class="px-6 pt-6">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
              <i class="pi pi-list text-blue-600 text-sm"></i>
            </div>
            <h2 class="text-lg font-semibold text-gray-900">Purchase Orders</h2>
          </div>
        </div>
      </template>

      <template #content>
        <div class="p-6 pt-2">
          <!-- Loading State -->
          <div v-if="loading" class="space-y-3">
            <div v-for="i in 5" :key="i" class="bg-gray-50 rounded-xl p-4">
              <div class="grid grid-cols-6 gap-4">
                <Skeleton width="120px" height="20px" />
                <Skeleton width="60px" height="20px" />
                <Skeleton width="80px" height="20px" />
                <Skeleton width="100px" height="20px" />
                <Skeleton width="100px" height="20px" />
                <Skeleton width="140px" height="20px" />
              </div>
            </div>
          </div>

          <!-- Data Table -->
          <DataTable
            v-else
            :value="pos"
            :paginator="true"
            :first="first"
            :rows="rows"
            :totalRecords="totalRecords"
            :lazy="true"
            dataKey="id"
            sortMode="single"
            :sortField="sortField"
            :sortOrder="sortOrder"
            @sort="onSort"
            @page="onPageChange"
            class="p-datatable-sm"
            stripedRows
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            :rowsPerPageOptions="[5, 10, 20, 50]"
          >
            <!-- Date Column -->
            <Column header="Date" field="created_at" sortable style="min-width: 140px">
              <template #body="{ data }">
                <span class="text-sm text-gray-700">{{ formatDate(data.created_at) }}</span>
              </template>
            </Column>

            <!-- PO Number Column -->
            <Column header="PO" style="min-width: 140px">
              <template #body="{ data }">
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center">
                    <i class="pi pi-file text-blue-600 text-xs"></i>
                  </div>
                  <div>
                    <div class="font-semibold text-gray-900 hover:text-blue-600 cursor-pointer" @click="viewDetail(data.id, getPurchaseOrderStatus(data))">
                      #{{ getPONumber(data) }}
                    </div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ formatDate(data.order_date || data.created_at) }}</div>
                  </div>
                </div>
              </template>
            </Column>

            <!-- Store Column -->
            <Column header="Store" style="min-width: 160px">
              <template #body="{ data }">
                <div class="text-sm text-gray-800">
                  {{ data.store?.store_name || data.store?.name || data.store_name || data.store?.store_code || 'N/A' }}
                </div>
              </template>
            </Column>

            <!-- Items Column -->
            <Column header="Items" style="width: 80px" class="text-center">
              <template #body="{ data }">
                <Tag :value="getItemCount(data)" severity="info" class="rounded-full text-xs px-3 py-1" />
              </template>
            </Column>

            <!-- Total Column -->
            <Column header="Total" style="min-width: 100px" class="text-right">
              <template #body="{ data }">
                <span class="font-semibold text-green-600">₱{{ formatNumber(getTotalAmount(data)) }}</span>
              </template>
            </Column>

            <!-- Expected Delivery Column -->
            <Column header="Expected Delivery" style="min-width: 120px">
              <template #body="{ data }">
                <div class="flex items-center gap-2">
                  <i class="pi pi-calendar text-gray-400 text-xs"></i>
                  <span class="text-gray-700">{{ formatDate(getExpectedDelivery(data)) }}</span>
                </div>
              </template>
            </Column>

            <!-- Status Column -->
            <Column header="Status" style="min-width: 140px">
              <template #body="{ data }">
                <div class="flex items-center gap-2">
                  <div :class="getStatusDot(getPurchaseOrderStatus(data))" class="w-2 h-2 rounded-full"></div>
                  <Tag 
                    :value="formatStatus(getPurchaseOrderStatus(data))" 
                    :severity="getStatusSeverity(getPurchaseOrderStatus(data))"
                    class="rounded-full text-xs px-3 py-1"
                  />
                </div>
              </template>
            </Column>

            <!-- Goods Received Column -->
            <!-- <Column header="Goods Received" style="min-width: 120px">
              <template #body="{ data }">
                <div v-if="getGoodsReceivedStatus(data) === 'confirmed'" class="flex items-center gap-2">
                  <div class="w-2 h-2 rounded-full bg-green-500"></div>
                  <Tag value="Received" severity="success" class="rounded-full text-xs px-3 py-1" />
                </div>
                <div v-else-if="getGoodsReceivedStatus(data) === 'pending'" class="flex items-center gap-2">
                  <div class="w-2 h-2 rounded-full bg-orange-400"></div>
                  <Tag value="Pending" severity="warning" class="rounded-full text-xs px-3 py-1" />
                </div>
                <span v-else class="text-xs text-gray-400">—</span>
              </template>
            </Column> -->

            <!-- Actions Column -->
            <Column header="Actions" style="min-width: 180px" headerStyle="text-align: center">
              <template #body="{ data }">
                <div class="flex items-center justify-start gap-1">
                  <button
                    @click.stop="viewDetail(data.id, getPurchaseOrderStatus(data))"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors flex items-center gap-1"
                    :class="getActionButtonClass(getPurchaseOrderStatus(data))"
                  >
                    <i :class="getActionIcon(getPurchaseOrderStatus(data))" class="text-xs"></i>
                    <span>{{ getActionLabel(getPurchaseOrderStatus(data)) }}</span>
                  </button>
                  
                  
                  
                  <button
                    v-if="getPurchaseOrderStatus(data) === 'supplier_accepted'"
                    @click.stop="goToDeliveryForm(data.id)"
                    class="px-3 py-1.5 bg-green-50 hover:bg-green-100 text-green-700 rounded-lg text-xs font-medium transition-colors flex items-center gap-1"
                    v-tooltip="'Record Delivery'"
                  >
                    <i class="pi pi-truck text-xs"></i>
                    <span>Deliver</span>
                  </button>
                </div>
              </template>
            </Column>

            <!-- Empty Template -->
            <template #empty>
              <div class="text-center py-12">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                  <i class="pi pi-inbox text-gray-400 text-3xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-700">No Purchase Orders Found</h3>
                <p class="text-gray-500 mt-2">No purchase orders match your current filters</p>
              </div>
            </template>
          </DataTable>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Skeleton from 'primevue/skeleton'
import supplierService from '../../../services/supplier.service'

const router = useRouter()
const toast = useToast()
const loading = ref(false)
const pos = ref<any[]>([])
const searchQuery = ref('')
const statusFilter = ref('')
const first = ref(0)
const rows = ref(10)
const totalRecords = ref(0)
const sortField = ref('created_at')
const sortOrder = ref(-1)
const poFeedback = ref<Record<number, any>>({})
const lastDeliveryPoId = ref<number | null>(null)
const invoiceCreatingId = ref<number | null>(null)

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Draft', value: 'draft' },
  { label: 'Pending Finance Approval', value: 'pending_finance_approval' },
  { label: 'Approved', value: 'approved' },
  { label: 'Sent to Supplier', value: 'sent_to_supplier' },
  { label: 'Supplier Accepted', value: 'supplier_accepted' },
  { label: 'In Transit', value: 'in_transit' },
  { label: 'Delivered', value: 'delivered' },
  { label: 'Goods Received', value: 'goods_received' },
  { label: 'Rejected by Finance', value: 'rejected_finance' },
  { label: 'Supplier Declined', value: 'declined_supplier' },
  { label: 'Cancelled', value: 'cancelled' },
]

// Computed stats
const stats = computed(() => {
  const total = pos.value.length
  const accepted = pos.value.filter(p => p.response === 'accepted').length
  const inTransit = pos.value.filter(p => getPurchaseOrderStatus(p) === 'in_transit').length
  const goodsReceived = pos.value.filter(p => p.receipt_status === 'confirmed').length
  
  return { total, accepted, inTransit, goodsReceived }
})

// Helper functions
const getPONumber = (row: any): string => {
  return row?.purchase_order?.po_number || row?.po_number || '-'
}

const getItemCount = (row: any): number => {
  return row?.purchase_order?.items?.length || 0
}

const getTotalAmount = (row: any): number => {
  return parseFloat(row?.purchase_order?.total_amount || row?.total_amount || 0)
}

const getExpectedDelivery = (row: any): string => {
  return row?.purchase_order?.expected_delivery_date || row?.expected_delivery_date
}

const getPurchaseOrderStatus = (row: any): string => {
  return row?.purchase_order?.status || row?.status || ''
}

const getGoodsReceivedStatus = (row: any): string => {
  return row?.receipt_status || 'pending'
}

const getInvoiceStatusForPO = (row: any): string => {
  return row?.purchase_order?.invoice_status || ''
}

const getActionLabel = (status: string): string => {
  if (['supplier_accepted', 'in_transit', 'delivered', 'goods_received', 'declined_supplier'].includes(status)) {
    return 'View'
  }
  return 'Review'
}

const getActionIcon = (status: string): string => {
  if (['supplier_accepted', 'in_transit', 'delivered', 'goods_received', 'declined_supplier'].includes(status)) {
    return 'pi pi-eye'
  }
  return 'pi pi-arrow-right'
}

const getActionButtonClass = (status: string): string => {
  if (['supplier_accepted', 'in_transit', 'delivered', 'goods_received', 'declined_supplier'].includes(status)) {
    return 'bg-blue-50 hover:bg-blue-100 text-blue-700'
  }
  return 'bg-purple-50 hover:bg-purple-100 text-purple-700'
}

const getStatusDot = (status: string): string => {
  const map: Record<string, string> = {
    draft: 'bg-gray-400',
    pending_finance_approval: 'bg-orange-400',
    approved: 'bg-green-500',
    sent_to_supplier: 'bg-blue-400',
    supplier_accepted: 'bg-green-400',
    in_transit: 'bg-purple-400',
    delivered: 'bg-green-600',
    goods_received: 'bg-green-600',
    rejected_finance: 'bg-red-500',
    declined_supplier: 'bg-red-500',
    cancelled: 'bg-red-400',
  }
  return map[status] || 'bg-gray-400'
}

const createInvoiceFromReceipt = async (poId: number) => {
  if (invoiceCreatingId.value === poId) return
  invoiceCreatingId.value = poId
  try {
    const res = await supplierService.getSupplierPODetail(poId)
    const payload = res.data || res
    const detail = payload?.data || payload
    const invoice = detail?.invoice
    if (invoice?.id) {
      toast.add({
        severity: 'info',
        summary: 'Invoice Exists',
        detail: 'An invoice already exists for this PO.',
        life: 4000,
      })
      return
    }
    await supplierService.createInvoiceFromGoodsReceipt({
      purchase_order_id: poId,
      goods_receipt_id: detail?.goods_receipt?.id || null,
      submitted_by_supplier: true,
    })
    toast.add({
      severity: 'success',
      summary: 'Invoice Submitted',
      detail: 'Invoice has been submitted to finance accounts payable.',
      life: 4000,
    })
    loadPOs()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Invoice Error',
      detail: error.response?.data?.message || 'Failed to create invoice.',
      life: 4000,
    })
  } finally {
    invoiceCreatingId.value = null
  }
}

const getStatusSeverity = (status: string): 'success' | 'info' | 'warn' | 'danger' | 'secondary' => {
  const map: Record<string, any> = {
    draft: 'secondary',
    pending_finance_approval: 'warn',
    approved: 'success',
    sent_to_supplier: 'info',
    supplier_accepted: 'success',
    in_transit: 'warn',
    delivered: 'success',
    goods_received: 'success',
    rejected_finance: 'danger',
    declined_supplier: 'danger',
    cancelled: 'danger',
  }
  return map[status] || 'info'
}

const formatStatus = (status: string): string => {
  if (!status) return '-'
  const map: Record<string, string> = {
    draft: 'Draft',
    pending_finance_approval: 'Pending Finance Approval',
    approved: 'Approved',
    sent_to_supplier: 'Sent to Supplier',
    supplier_accepted: 'Supplier Accepted',
    in_transit: 'In Transit',
    delivered: 'Delivered',
    goods_received: 'Goods Received',
    rejected_finance: 'Rejected by Finance',
    declined_supplier: 'Supplier Declined',
    cancelled: 'Cancelled',
  }
  return map[status] || status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const formatDate = (date: string): string => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('en-PH', { 
    month: 'short', 
    day: 'numeric', 
    year: 'numeric' 
  })
}

const formatNumber = (value: number): string => {
  return new Intl.NumberFormat('en-PH', { 
    minimumFractionDigits: 2, 
    maximumFractionDigits: 2 
  }).format(value || 0)
}

const loadPOs = async () => {
  try {
    loading.value = true
    const params: Record<string, any> = {
      page: Math.floor(first.value / rows.value) + 1,
      per_page: rows.value,
      sort_field: sortField.value,
      sort_order: sortOrder.value === 1 ? 'asc' : 'desc',
    }
    if (searchQuery.value?.trim()) {
      params.search = searchQuery.value.trim()
    }
    if (statusFilter.value) {
      params.status = statusFilter.value
    }
    
    const res = await supplierService.getSupplierPOs(params)
    const payload = res ?? {}
    const pagination = payload.data ?? payload
    if (pagination?.data) {
      pos.value = pagination.data
      totalRecords.value = pagination.total ?? pagination.meta?.total ?? pagination.data.length
    } else {
      pos.value = []
      totalRecords.value = 0
    }

    // Load feedback for each PO
    for (const po of pos.value) {
      try {
        const feedbackRes = await supplierService.getMyPOFeedbacks({ purchase_order_id: po.id })
        const feedbackPayload = feedbackRes.data || feedbackRes
        const feedbackList = feedbackPayload?.data?.data || feedbackPayload?.data || []
        if (feedbackList.length > 0) {
          poFeedback.value[po.id] = feedbackList[0]
        }
      } catch (error) {
        console.error('Error loading PO feedback:', error)
      }
    }
  } catch (error: any) {
    console.error('Failed to load POs:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load POs',
      life: 3000,
    })
    pos.value = []
    totalRecords.value = 0
  } finally {
    loading.value = false
  }
}

const onSearch = () => {
  first.value = 0
  loadPOs()
}

const onFilterChange = () => {
  first.value = 0
  loadPOs()
}

const onPageChange = (event: any) => {
  first.value = event.first
  loadPOs()
}

const onSort = (event: any) => {
  sortField.value = event.sortField
  sortOrder.value = event.sortOrder
  loadPOs()
}

const resetFilters = () => {
  searchQuery.value = ''
  statusFilter.value = ''
  first.value = 0
  loadPOs()
}

const viewDetail = (id: number, status?: string) => {
  if (['in_transit', 'delivered', 'goods_received', 'declined_supplier'].includes(status || '')) {
    router.push(`/supplier-portal/pos/${id}/view`)
    return
  }
  router.push(`/supplier-portal/pos/${id}`)
}

const goToDeliveryForm = (id: number) => {
  localStorage.setItem('last_delivery_form_po_id', String(id))
  lastDeliveryPoId.value = id
  router.push(`/supplier-portal/pos/${id}/delivery-template`)
}

onMounted(() => {
  const stored = localStorage.getItem('last_delivery_form_po_id')
  lastDeliveryPoId.value = stored ? Number(stored) : null
  loadPOs()
})
</script>

<style scoped>
/* Smooth transitions */
* {
  transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

/* iOS-style shadows */
:deep(.p-card) {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02);
}

:deep(.p-card .p-card-content) {
  padding: 0;
}

:deep(.p-card .p-card-body) {
  padding: 0;
}

/* iOS-style table */
:deep(.p-datatable) {
  border-radius: 16px;
  overflow: hidden;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  background-color: #f9fafb;
  color: #6b7280;
  font-weight: 500;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 1rem 1rem;
  border-bottom: 1px solid #f3f4f6;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 1rem 1rem;
  border-bottom: 1px solid #f3f4f6;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: #f9fafb;
}

/* iOS-style tags */
:deep(.p-tag) {
  border-radius: 9999px;
  font-weight: 500;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}
</style>
