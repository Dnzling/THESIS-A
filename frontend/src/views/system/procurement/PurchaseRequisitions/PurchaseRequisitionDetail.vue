<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <!-- iOS-style Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <button 
          @click="router.push({ name: 'procurement.purchase-requisitions' })"
          class="w-10 h-10 rounded-full bg-white hover:bg-gray-100 flex items-center justify-center transition-colors shadow-sm border border-gray-200"
        >
          <i class="pi pi-chevron-left text-gray-600 text-lg"></i>
        </button>
        <div>
          <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Purchase Requisition</h1>
          <p class="text-sm text-gray-500 mt-1">{{ detail?.pr_number || 'Loading...' }}</p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <Tag :value="formatStatus(detail?.status || 'draft')" :severity="statusSeverity(detail?.status || 'draft')" class="text-xs" />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div v-for="i in 4" :key="i" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
          <Skeleton width="80px" height="12px" class="mb-3" />
          <Skeleton width="120px" height="24px" class="mb-2" />
          <Skeleton width="100px" height="12px" />
        </div>
      </div>
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <Skeleton width="150px" height="16px" class="mb-4" />
        <Skeleton height="80px" class="mb-3" />
        <Skeleton height="200px" />
      </div>
    </div>

    <template v-else-if="detail">
      <!-- iOS-style Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
          <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">PR Number</span>
            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
              <i class="pi pi-file text-blue-600 text-sm"></i>
            </div>
          </div>
          <p class="text-xl font-semibold text-gray-900">{{ detail?.pr_number || 'N/A' }}</p>
          <p class="text-xs text-gray-500 mt-2">Created: {{ formatDateTime(detail?.created_at) }}</p>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
          <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Type</span>
            <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
              <i class="pi pi-tag text-purple-600 text-sm"></i>
            </div>
          </div>
          <p class="text-lg font-semibold text-gray-900">{{ capitalizeWords(detail?.requisition_type) }}</p>
          <p class="text-xs text-gray-500 mt-2">Priority: 
            <span :class="getPriorityClass(detail?.priority)">{{ detail?.priority || 'N/A' }}</span>
          </p>
        </div>

        <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-5 shadow-lg">
          <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-blue-100 uppercase tracking-wider">Est. Amount</span>
            <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
              <i class="pi pi-credit-card text-white text-sm"></i>
            </div>
          </div>
          <p class="text-2xl font-bold text-white tracking-tight">₱{{ formatNumber(parseFloat(detail?.estimated_amount || 0)) }}</p>
          <p class="text-xs text-blue-200 mt-2">{{ capitalizeWords(detail?.procurement_route || 'N/A') }}</p>
        </div>
      </div>

      <!-- Status Report Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
              <i class="pi pi-file-pdf text-green-600 text-sm"></i>
            </div>
            <span class="text-sm font-medium text-gray-700">RFQ Status</span>
          </div>
          <div class="flex items-center justify-between">
            <Tag :value="getRfqStatus(detail)" :severity="getRfqSeverity(detail)" />
            <span class="text-xs text-gray-500">{{ getRfqCount(detail) }} RFQ(s)</span>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
              <i class="pi pi-shopping-cart text-purple-600 text-sm"></i>
            </div>
            <span class="text-sm font-medium text-gray-700">PO Status</span>
          </div>
          <div class="flex items-center justify-between">
            <Tag :value="getPoStatus(detail)" :severity="getPoSeverity(detail)" />
            <span class="text-xs text-gray-500">{{ getPoCount(detail) }} PO(s)</span>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
              <i class="pi pi-users text-blue-600 text-sm"></i>
            </div>
            <span class="text-sm font-medium text-gray-700">Suppliers</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-lg font-semibold text-gray-900">{{ detail?.suppliers?.length || 0 }}</span>
            <span class="text-xs text-gray-500">linked</span>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-end gap-3">
        <button
          v-if="detail.status === 'draft'"
          @click="submit"
          :disabled="processing"
          class="px-5 py-2.5 bg-blue-500 hover:bg-blue-600 disabled:opacity-50 text-white font-medium rounded-xl text-sm transition-colors flex items-center gap-2 shadow-sm"
        >
          <i class="pi pi-send text-sm"></i>
          <span>{{ processing ? 'Submitting...' : 'Submit' }}</span>
        </button>
        
        <button
          v-if="canApprove"
          @click="showRejectDialog = true"
          :disabled="processing"
          class="px-5 py-2.5 bg-white hover:bg-gray-50 text-red-600 font-medium rounded-xl text-sm transition-colors flex items-center gap-2 border border-gray-200"
        >
          <i class="pi pi-times text-sm"></i>
          <span>Reject</span>
        </button>
        
        <button
          v-if="canApprove"
          @click="approve"
          :disabled="processing"
          class="px-5 py-2.5 bg-green-500 hover:bg-green-600 disabled:opacity-50 text-white font-medium rounded-xl text-sm transition-colors flex items-center gap-2 shadow-sm"
        >
          <i class="pi pi-check text-sm"></i>
          <span>{{ processing ? 'Approving...' : 'Approve' }}</span>
        </button>
      </div>

      <!-- PR Details Card -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
          <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider flex items-center gap-2">
            <i class="pi pi-info-circle text-gray-400"></i>
            PR Details & Reason
          </h3>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
            <div>
              <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Requested By</p>
              <p class="font-semibold text-gray-900">{{ detail?.requested_by?.fname || '' }} {{ detail?.requested_by?.lname || '' }}</p>
              <p class="text-sm text-gray-600 mt-1">{{ detail?.requested_by?.employee_number || '' }}</p>
            </div>
            <div>
              <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Branch</p>
              <p class="font-semibold text-gray-900">{{ detail?.branch?.name || 'N/A' }}</p>
              <p class="text-sm text-gray-600 mt-1">{{ detail?.branch?.branch_code || '' }}</p>
            </div>
          </div>
          <div class="bg-gray-50 rounded-xl p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Reason</p>
            <p class="text-gray-900">{{ detail?.reason || 'No reason provided' }}</p>
          </div>
        </div>
      </div>

      <!-- Line Items Card -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
          <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider flex items-center gap-2">
            <i class="pi pi-list text-gray-400"></i>
            Line Items ({{ detail.items?.length || 0 }})
          </h3>
        </div>
        <div class="p-6">
          <div v-if="detail?.items && detail.items.length > 0" class="space-y-3">
            <div v-for="(item, index) in detail.items" :key="index" class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-sm transition-shadow">
              <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="col-span-2">
                  <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Product</p>
                  <p class="font-semibold text-gray-900">{{ item?.product?.product_name || 'Unknown' }}</p>
                  <p class="text-xs text-gray-500 mt-1">SKU: {{ item?.product?.sku || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Quantity</p>
                  <p class="text-2xl font-semibold text-gray-900">{{ item?.quantity_requested || 0 }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Unit Cost</p>
                  <p class="font-semibold text-gray-900">₱{{ formatNumber(parseFloat(item?.estimated_unit_cost || 0)) }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Total</p>
                  <p class="text-lg font-semibold text-blue-600">₱{{ formatNumber((item?.quantity_requested || 0) * parseFloat(item?.estimated_unit_cost || 0)) }}</p>
                </div>
              </div>
              <div v-if="item?.specifications" class="mt-3 pt-3 border-t border-gray-100">
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Specifications</p>
                <p class="text-sm text-gray-700">{{ item.specifications }}</p>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8">
            <i class="pi pi-inbox text-3xl text-gray-300 mb-2"></i>
            <p class="text-gray-500">No line items</p>
          </div>
        </div>
      </div>

      <!-- Approval Chain Card -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
          <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider flex items-center gap-2">
            <i class="pi pi-check-circle text-gray-400"></i>
            Approval Chain ({{ (detail.required_approvals || []).length }} levels)
          </h3>
        </div>
        <div class="p-6">
          <div class="space-y-2">
            <div v-for="(approval, index) in (detail?.required_approvals || [])" :key="index" class="flex items-center gap-3 p-3 bg-white rounded-xl border border-gray-100">
              <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center">
                <i class="pi pi-check text-green-600 text-xs"></i>
              </div>
              <span class="font-medium text-gray-900">{{ capitalizeWords(approval) }}</span>
            </div>
            <div v-if="!detail?.required_approvals?.length" class="text-center py-4">
              <p class="text-gray-500">No approvals required</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Related Purchase Orders -->
      <div v-if="detail?.purchase_orders && detail.purchase_orders.length > 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
          <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider flex items-center gap-2">
            <i class="pi pi-shopping-cart text-gray-400"></i>
            Related Purchase Orders
          </h3>
        </div>
        <div class="p-6">
          <div class="space-y-2">
            <div v-for="po in detail.purchase_orders" :key="po.id" class="flex items-center justify-between p-3 bg-white rounded-xl border border-gray-100">
              <div>
                <p class="font-medium text-gray-900">{{ po?.po_number }}</p>
                <p class="text-sm text-gray-500 mt-0.5">Created: {{ formatDate(po?.created_at) }}</p>
              </div>
              <Tag :value="formatStatus(po?.status)" :severity="statusSeverity(po?.status)" />
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Not Found State -->
    <div v-else class="text-center py-12 bg-white rounded-2xl border border-gray-100">
      <i class="pi pi-exclamation-circle text-4xl text-gray-300 mb-4"></i>
      <h3 class="text-lg font-medium text-gray-700">Purchase Requisition Not Found</h3>
      <p class="text-gray-500 mt-2 mb-4">The requisition you're looking for doesn't exist or you don't have permission to view it.</p>
      <button
        @click="router.push({ name: 'procurement.purchase-requisitions' })"
        class="px-5 py-2.5 bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-xl text-sm transition-colors inline-flex items-center gap-2"
      >
        <i class="pi pi-arrow-left"></i>
        <span>Back to List</span>
      </button>
    </div>

    <!-- Reject Dialog -->
    <div v-if="showRejectDialog" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Reject Requisition</h3>
        
        <div class="space-y-4">
          <p class="text-sm text-gray-600">Please provide the reason for rejecting this requisition.</p>
          
          <textarea
            v-model="rejectReason"
            rows="4"
            placeholder="Enter rejection reason"
            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all resize-none"
          ></textarea>
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <button
            @click="showRejectDialog = false"
            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl text-sm transition-colors"
          >
            Cancel
          </button>
          <button
            @click="submitRejection"
            :disabled="processing"
            class="px-4 py-2 bg-red-500 hover:bg-red-600 disabled:opacity-50 text-white font-medium rounded-xl text-sm transition-colors flex items-center gap-2"
          >
            <span v-if="processing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span>{{ processing ? 'Rejecting...' : 'Reject' }}</span>
          </button>
        </div>
      </div>
    </div>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Tag from 'primevue/tag'
import Skeleton from 'primevue/skeleton'
import Toast from 'primevue/toast'
import procurementService from '../../../../services/procurement.service'
import { useAuthStore } from '../../../../stores/auth'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const requisitionId = Number(route.params.id)

const loading = ref(false)
const processing = ref(false)
const detail = ref<any>(null)
const authStore = useAuthStore()
const userRole = computed(() => authStore.userRole || '')
const showRejectDialog = ref(false)
const rejectReason = ref('')

const canApprove = computed(() => {
  if (!detail.value) return false
  return ['pending', 'warehouse_approved', 'branch_manager_approved'].includes(detail.value.status) && !!userRole.value
})

// Helper functions
const capitalizeWords = (str: string): string => {
  if (!str || typeof str !== 'string') return 'N/A'
  return str.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

const formatDate = (date: string | null): string => {
  if (!date) return 'N/A'
  const d = new Date(date)
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatDateTime = (date: string | null): string => {
  if (!date) return 'N/A'
  const d = new Date(date)
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' at ' +
         d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

const formatNumber = (value: number): string => {
  return new Intl.NumberFormat('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value || 0)
}

const getPriorityClass = (priority: any): string => {
  if (!priority || typeof priority !== 'string') return 'text-gray-600'
  
  const priorityLower = priority.toLowerCase()
  const map: Record<string, string> = {
    urgent: 'text-red-600 font-semibold',
    high: 'text-orange-600 font-semibold',
    medium: 'text-yellow-600 font-semibold',
    low: 'text-green-600 font-semibold',
  }
  return map[priorityLower] || 'text-gray-600'
}

const statusSeverity = (status: string): 'success' | 'info' | 'warn' | 'danger' | 'secondary' => {
  const severityMap: Record<string, any> = {
    draft: 'secondary',
    pending: 'info',
    warehouse_approved: 'success',
    branch_manager_approved: 'success',
    pending_central_review: 'warn',
    procurement_processing: 'info',
    rfq_sent: 'info',
    quotes_received: 'warn',
    supplier_selected: 'success',
    po_created: 'success',
    rejected: 'danger',
    cancelled: 'danger',
  }
  return severityMap[status] || 'secondary'
}

const formatStatus = (status: string): string => {
  if (!status || typeof status !== 'string') return 'Draft'
  return status.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

const getRfqCount = (req: any) => {
  if (!req) return 0
  if (Array.isArray(req?.rfqs)) return req.rfqs.length
  if (req?.rfq) return 1
  return 0
}

const getPoCount = (req: any) => {
  if (!req) return 0
  if (Array.isArray(req?.purchase_orders)) return req.purchase_orders.length
  if (req?.purchase_order) return 1
  return 0
}

const getRfqStatus = (req: any) => (getRfqCount(req) > 0 ? 'Created' : 'Not Created')
const getRfqSeverity = (req: any) => (getRfqCount(req) > 0 ? 'success' : 'secondary')
const getPoStatus = (req: any) => (getPoCount(req) > 0 ? 'Created' : 'Not Created')
const getPoSeverity = (req: any) => (getPoCount(req) > 0 ? 'success' : 'secondary')

const loadDetail = async () => {
  loading.value = true
  try {
    const response = await procurementService.getPurchaseRequisition(requisitionId)
    detail.value = response.data || null
  } catch (error) {
    console.error('Failed to load PR detail:', error)
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load PR', life: 3000 })
  } finally {
    loading.value = false
  }
}

const submit = async () => {
  processing.value = true
  try {
    const response = await procurementService.submitPurchaseRequisition(requisitionId)
    if (response.success) {
      toast.add({ severity: 'success', summary: 'Success', detail: 'PR submitted for approval', life: 3000 })
      await loadDetail()
    }
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to submit', life: 3000 })
  } finally {
    processing.value = false
  }
}

const approve = async () => {
  processing.value = true
  try {
    if (!userRole.value) {
      toast.add({ severity: 'warn', summary: 'Missing Role', detail: 'Unable to determine your role.', life: 3000 })
      return
    }
    const response = await procurementService.approvePurchaseRequisition(requisitionId, { role: userRole.value, notes: '' })
    if (response.success) {
      toast.add({ severity: 'success', summary: 'Success', detail: 'PR approved', life: 3000 })
      await loadDetail()

      if (detail.value?.status === 'procurement_processing') {
        const hasSuppliers = Array.isArray(detail.value?.items)
          ? detail.value.items.every((item: any) => Array.isArray(item?.product?.suppliers) && item.product.suppliers.length > 0)
          : false

        if (hasSuppliers) {
          router.push({ name: 'procurement.purchase-orders.create', query: { requisition_id: requisitionId } })
          return
        }

        router.push({ name: 'procurement.rfqs.create', query: { requisition_id: requisitionId } })
      }
    }
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to approve', life: 3000 })
  } finally {
    processing.value = false
  }
}

const submitRejection = async () => {
  processing.value = true
  try {
    if (!rejectReason.value.trim()) {
      toast.add({ severity: 'warn', summary: 'Required', detail: 'Please provide a rejection reason.', life: 3000 })
      return
    }
    const response = await procurementService.rejectPurchaseRequisition(requisitionId, { reason: rejectReason.value })
    if (response.success) {
      toast.add({ severity: 'success', summary: 'Success', detail: 'PR rejected', life: 3000 })
      showRejectDialog.value = false
      rejectReason.value = ''
      await loadDetail()
    }
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to reject', life: 3000 })
  } finally {
    processing.value = false
  }
}

onMounted(() => {
  loadDetail()
})
</script>

<style scoped>
/* Smooth transitions */
* {
  transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

/* iOS-style shadows */
.shadow-sm {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02);
}

.shadow-lg {
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08), 0 4px 10px rgba(0, 0, 0, 0.02);
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

/* Loading spinner */
@keyframes spin {
  to { transform: rotate(360deg); }
}

.animate-spin {
  animation: spin 0.8s linear infinite;
}
</style>
