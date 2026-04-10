<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <!-- Toast notifications -->
    <Toast />
    <ConfirmDialog />
  
    <!-- iOS-style Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <button 
          @click="router.push({ name: 'finance.purchase-orders' })"
          class="w-10 h-10 rounded-full bg-white hover:bg-gray-100 flex items-center justify-center transition-colors shadow-sm border border-gray-200"
        >
          <i class="pi pi-chevron-left text-gray-600 text-lg"></i>
        </button>
        <div>
          <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Finance Review</h1>
          <p class="text-sm text-gray-500 mt-1">{{ detail?.po_number || 'Loading...' }}</p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <div v-if="detail?.status === 'pending_finance_approval'" class="flex items-center gap-2">
   
          <button
            @click="confirmReject"
            class="px-5 py-2.5 bg-white hover:bg-gray-50 text-red-600 font-medium rounded-xl text-sm transition-colors flex items-center gap-2 border border-gray-200"
          >
            <i class="pi pi-times text-sm"></i>
            <span>Reject</span>
          </button>
                 <button
            @click="confirmApprove"
            class="px-5 py-2.5 bg-green-500 hover:bg-green-600 text-white font-medium rounded-xl text-sm transition-colors flex items-center gap-2 shadow-sm"
          >
            <i class="pi pi-check text-sm"></i>
            <span>Approve</span>
          </button>
        </div>
        <Tag :value="formatStatus(detail?.status)" :severity="statusSeverity(detail?.status)" class="text-xs" />
      </div>
    </div>
  
    <!-- Loading State -->
    <div v-if="loading" class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div v-for="i in 4" :key="i" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
          <Skeleton width="100px" height="12px" class="mb-3" />
          <Skeleton width="150px" height="24px" class="mb-2" />
          <Skeleton width="80px" height="12px" />
        </div>
      </div>
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <Skeleton width="150px" height="16px" class="mb-4" />
        <Skeleton height="100px" class="mb-3" />
        <Skeleton height="200px" />
      </div>
    </div>
  
    <!-- Main Content -->
    <div v-else-if="detail" class="space-y-6">
      <!-- iOS-style Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
          <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">PO Number</span>
            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
              <i class="pi pi-file text-blue-600 text-sm"></i>
            </div>
          </div>
          <p class="text-xl font-semibold text-gray-900">{{ detail?.po_number || '-' }}</p>
          <p class="text-xs text-gray-500 mt-2">Created: {{ formatDate(detail?.created_at) }}</p>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
          <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Order Date</span>
            <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
              <i class="pi pi-calendar text-purple-600 text-sm"></i>
            </div>
          </div>
          <p class="text-xl font-semibold text-gray-900">{{ formatDate(detail?.order_date) }}</p>
          <p class="text-xs text-gray-500 mt-2">Expected: {{ formatDate(detail?.expected_delivery_date) }}</p>
        </div>

    

        <div class="bg-linear-to-br from-blue-600 to-blue-700 rounded-2xl p-5 shadow-lg">
          <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-blue-100 uppercase tracking-wider">Total Amount</span>
            <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
              <i class="pi pi-credit-card text-white text-sm"></i>
            </div>
          </div>
          <p class="text-2xl font-bold text-white tracking-tight">{{ formatCurrency(parseFloat(detail?.total_amount || 0)) }}</p>
          <p class="text-xs text-blue-200 mt-2">Approval needed</p>
        </div>
      </div>
  
      <!-- Supplier and Branch Info -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
              <i class="pi pi-building text-blue-600"></i>
            </div>
            <h3 class="font-medium text-gray-900">Supplier Information</h3>
          </div>
          <div class="space-y-3">
            <div>
              <p class="text-xs text-gray-500 mb-1">Company Name</p>
              <p class="font-medium text-gray-900">{{ detail?.supplier?.company_name || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 mb-1">Contact Person</p>
              <p class="text-sm text-gray-700">{{ detail?.supplier?.contact_person || '-' }}</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <p class="text-xs text-gray-500 mb-1">Email</p>
                <a :href="`mailto:${detail?.supplier?.email}`" class="text-sm text-blue-600 hover:underline">{{ detail?.supplier?.email || '-' }}</a>
              </div>
              <div>
                <p class="text-xs text-gray-500 mb-1">Phone</p>
                <p class="text-sm text-gray-700">{{ detail?.supplier?.phone || '-' }}</p>
              </div>
            </div>
          </div>
        </div>
  
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
              <i class="pi pi-map-marker text-green-600"></i>
            </div>
            <h3 class="font-medium text-gray-900">Delivery Information</h3>
          </div>
          <div class="space-y-3">
            <div>
              <p class="text-xs text-gray-500 mb-1">Branch</p>
              <p class="font-medium text-gray-900">{{ detail?.branch?.name || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 mb-1">Address</p>
              <p class="text-sm text-gray-700">{{ detail?.branch?.address || 'No address provided' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 mb-1">Contact</p>
              <p class="text-sm text-gray-700">{{ detail?.branch?.contact_number || '-' }}</p>
            </div>
          </div>
        </div>
      </div>
  
      <!-- Created By Info -->
      <div class="bg-gray-50/50 rounded-2xl p-4 border border-gray-100 text-sm text-gray-600 flex items-center gap-2">
        <i class="pi pi-user text-gray-400"></i>
        <span class="font-medium text-gray-700">Created by:</span>
        {{ detail?.created_by?.fname }} {{ detail?.created_by?.lname }}
        <span class="text-gray-400 mx-2">•</span>
        <span>{{ formatDate(detail?.created_at) }}</span>
      </div>
  
      <!-- PO Items Table -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
          <div class="flex items-center gap-2">
            <i class="pi pi-shopping-cart text-gray-500"></i>
            <h3 class="font-medium text-gray-700">Order Items ({{ detail?.items?.length || 0 }})</h3>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50/80 text-gray-500 text-xs uppercase tracking-wider">
              <tr>
                <th class="px-6 py-4 text-left font-medium">#</th>
                <th class="px-6 py-4 text-left font-medium">Product</th>
                <th class="px-6 py-4 text-center font-medium">Quantity</th>
                <th class="px-6 py-4 text-right font-medium">Unit Price</th>
                  <th class="px-6 py-4 text-right font-medium">Line Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="(item, index) in detail?.items" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                <td class="px-6 py-4 text-gray-500">{{ index + 1 }}</td>
                <td class="px-6 py-4">
                  <div>
                    <p class="font-medium text-gray-900">{{ item.product?.product_name || '-' }}</p>
                    <p class="text-xs text-gray-500 mt-1">SKU: {{ item.product?.sku || '-' }}</p>
                  </div>
                </td>
                <td class="px-6 py-4 text-center font-medium">{{ item?.quantity_ordered || 0 }}</td>
                <td class="px-6 py-4 text-right font-mono">{{ formatCurrency(parseFloat(item?.unit_cost || 0)) }}</td>
                  <td class="px-6 py-4 text-right font-mono font-medium text-blue-600">
                    {{ formatCurrency(parseFloat(item?.line_total || 0)) }}
                  </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
  
      <!-- Financial Summary -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-2">Subtotal</p>
            <p class="text-2xl font-semibold text-gray-900">{{ formatCurrency(parseFloat(detail?.subtotal || 0)) }}</p>
          </div>

          <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-2">Discount</p>
            <p class="text-2xl font-semibold text-gray-900">
              -{{ formatCurrency(parseFloat(detail?.discount_amount || 0)) }}
              <span v-if="contractDiscountPercent" class="text-xs text-gray-400 ml-2">({{ contractDiscountPercent.toFixed(2) }}%)</span>
            </p>
            <div v-if="parseFloat(detail?.shipping_cost || 0) > 0" class="text-xs text-gray-500 mt-2 flex items-center gap-1">
              <i class="pi pi-truck text-xs"></i> +{{ formatCurrency(parseFloat(detail?.shipping_cost || 0)) }}
            </div>
          </div>

          <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-2">Tax (VAT)</p>
            <p class="text-2xl font-semibold text-gray-900">
              {{ formatCurrency(parseFloat(detail?.tax_amount || 0)) }}
              <span v-if="contractTaxRate" class="text-xs text-gray-400 ml-2">({{ contractTaxRate.toFixed(2) }}%)</span>
            </p>
          </div>
  
        <div class="bg-linear-to-br from-blue-600 to-blue-700 rounded-2xl p-5 shadow-lg">
          <p class="text-xs text-blue-100 font-medium uppercase tracking-wider mb-2">Total Amount</p>
          <p class="text-3xl font-bold text-white">{{ formatCurrency(parseFloat(detail?.total_amount || 0)) }}</p>
        </div>
      </div>
  
      <!-- Notes & Terms -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div v-if="detail?.notes" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <div class="flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
              <i class="pi pi-file-text text-gray-600 text-sm"></i>
            </div>
            <h3 class="font-medium text-gray-900">Notes</h3>
          </div>
          <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ detail.notes }}</p>
        </div>
  
        <div v-if="detail?.terms_conditions" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <div class="flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
              <i class="pi pi-file text-gray-600 text-sm"></i>
            </div>
            <h3 class="font-medium text-gray-900">Terms & Conditions</h3>
          </div>
          <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ detail.terms_conditions }}</p>
        </div>
      </div>
  
      <!-- View Only Status Message -->
      <div v-if="detail?.status !== 'pending_finance_approval'"
        class="bg-gray-50/50 rounded-2xl p-4 border border-gray-100 text-sm text-gray-600 flex items-center justify-center gap-2">
        <i class="pi pi-info-circle text-gray-400"></i>
        This purchase order is in <strong class="mx-1">{{ formatStatus(detail?.status) }}</strong> status and is view-only.
      </div>
  
      <!-- Goods Receipt Section -->
      <div v-if="detail?.goods_receipts?.length > 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
          <div class="flex items-center gap-2">
            <i class="pi pi-inbox text-gray-500"></i>
            <h3 class="font-medium text-gray-700">Goods Receipts</h3>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50/80 text-gray-500 text-xs uppercase tracking-wider">
              <tr>
                <th class="px-6 py-4 text-left font-medium">GR Number</th>
                <th class="px-6 py-4 text-left font-medium">Received Date</th>
                <th class="px-6 py-4 text-left font-medium">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="receipt in detail.goods_receipts" :key="receipt.id" class="hover:bg-gray-50/50 transition-colors">
                <td class="px-6 py-4">
                  <RouterLink :to="`/inventory/goods-receipts/${receipt.id}`"
                    class="text-blue-600 hover:text-blue-800 font-medium">
                    {{ receipt.gr_number }}
                  </RouterLink>
                </td>
                <td class="px-6 py-4 text-gray-700">{{ formatDate(receipt.received_date) }}</td>
                <td class="px-6 py-4">
                  <Tag :value="receipt.status" :severity="receipt.status === 'completed' ? 'success' : 'warning'" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  
    <!-- Not Found State -->
    <div v-else class="text-center py-12 bg-white rounded-2xl border border-gray-100">
      <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="pi pi-exclamation-circle text-gray-400 text-3xl"></i>
      </div>
      <h3 class="text-lg font-medium text-gray-700">Purchase Order Not Found</h3>
      <p class="text-gray-500 mt-2 mb-4">The purchase order you're looking for doesn't exist or you don't have permission to view it.</p>
      <button
        @click="router.push({ name: 'finance.payables' })"
        class="px-5 py-2.5 bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-xl text-sm transition-colors inline-flex items-center gap-2"
      >
        <i class="pi pi-arrow-left"></i>
        <span>Back to Payables</span>
      </button>
    </div>
  
    <!-- Reject Reason Dialog -->
    <div v-if="rejectDialogVisible" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Reject Purchase Order</h3>
        
        <div class="space-y-4">
          <p class="text-sm text-gray-600">Please provide a reason for rejection.</p>
          <textarea
            v-model="rejectReason"
            rows="4"
            placeholder="Enter rejection reason"
            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all resize-none"
          ></textarea>
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <button
            @click="rejectDialogVisible = false"
            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl text-sm transition-colors"
          >
            Cancel
          </button>
          <button
            @click="submitReject"
            :disabled="!rejectReason.trim()"
            class="px-4 py-2 bg-red-500 hover:bg-red-600 disabled:opacity-50 text-white font-medium rounded-xl text-sm transition-colors"
          >
            Submit Rejection
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Tag from 'primevue/tag'
import Skeleton from 'primevue/skeleton'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import { useAuthStore } from '@/stores/auth'
import financeService from '@/services/finance.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const authStore = useAuthStore()
const poId = Number(route.params.id)

// State
const loading = ref(false)
const detail = ref<any>(null)
const contractTaxRate = ref(0)
const contractDiscountPercent = ref(0)
const rejectDialogVisible = ref(false)
const rejectReason = ref('')

// Methods
const loadDetail = async () => {
  loading.value = true
  try {
    const response = await financeService.getPurchaseOrder(poId)
    // Handle nested data structure
    if (response.data?.success && response.data?.data) {
      detail.value = response.data.data
    } else if (response.data?.data) {
      detail.value = response.data.data
    } else {
      detail.value = response.data
    }
    const payload = response.data || response
    contractTaxRate.value = Number(payload?.contract_tax_rate || payload?.data?.contract_tax_rate || 0) || 0
    contractDiscountPercent.value = Number(payload?.contract_discount_percent || payload?.data?.contract_discount_percent || 0) || 0
  } catch (error) {
    console.error('Failed to load purchase order detail', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load purchase order',
      life: 3000
    })
    detail.value = null
  } finally {
    loading.value = false
  }
}

const formatStatus = (status: string) => {
  if (!status) return 'DRAFT'
  return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const statusSeverity = (status: string): 'success' | 'info' | 'warn' | 'danger' | 'secondary' | 'contrast' => {
  const statusMap: Record<string, any> = {
    approved: 'success',
    sent_to_supplier: 'info',
    supplier_accepted: 'success',
    in_transit: 'warn',
    delivered: 'success',
    pending_finance_approval: 'warn',
    rejected_finance: 'danger',
    declined_supplier: 'danger',
    cancelled: 'danger',
    revision_requested: 'warn',
    draft: 'contrast'
  }
  return statusMap[status] || 'contrast'
}

const formatDate = (date: string | Date | undefined) => {
  if (!date) return '-'
  return new Intl.DateTimeFormat('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  }).format(new Date(date))
}

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(value)
}

const formatPaymentTerms = (term: string) => {
  const terms: Record<string, string> = {
    cash_on_delivery: 'Cash on Delivery',
    net_7: 'Net 7 Days',
    net_15: 'Net 15 Days',
    net_30: 'Net 30 Days',
    net_60: 'Net 60 Days',
    advance_payment: 'Advance Payment'
  }
  return terms[term] || term?.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ') || '-'
}

const confirmApprove = () => {
  confirm.require({
    header: 'Approve Purchase Order',
    message: 'Are you sure you want to approve this purchase order?',
    acceptLabel: 'Approve',
    rejectProps: {
            label: 'Cancel',
            severity: 'secondary',
            outlined: true
        },
    accept: () => approvePO(),
  })
}

const confirmReject = () => {
  confirm.require({
    header: 'Reject Purchase Order',
    message: 'Do you want to proceed with rejection?',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Continue',
    rejectProps: {
            label: 'Cancel',
            severity: 'secondary',
            outlined: true
        },
    accept: () => {
      rejectReason.value = ''
      rejectDialogVisible.value = true
    },
  })
}

const approvePO = async () => {
  const notes = ''
  try {
    const userRole =
      authStore.userRole ||
      (authStore.user as any)?.role?.name ||
      (authStore.user as any)?.role
    if (!userRole) {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Unable to determine your role',
        life: 3000
      })
      return
    }

    await financeService.approvePurchaseOrder(poId, {
      role: userRole,
      notes: notes || undefined
    })
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Purchase order approved successfully',
      life: 2000
    })
    await loadDetail()
  } catch (error) {
    console.error('Failed to approve purchase order', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to approve purchase order',
      life: 3000
    })
  }
}

const submitReject = async () => {
  const reason = rejectReason.value.trim()
  if (!reason) return
  try {
    await financeService.rejectPurchaseOrder(poId, { reason })
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Purchase order rejected',
      life: 2000
    })
    rejectDialogVisible.value = false
    await loadDetail()
  } catch (error) {
    console.error('Failed to reject purchase order', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to reject purchase order',
      life: 3000
    })
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
</style>
