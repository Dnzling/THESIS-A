<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <!-- iOS-style Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <button 
          @click="router.push({ name: 'procurement.rfqs' })"
          class="w-10 h-10 rounded-full bg-white hover:bg-gray-100 flex items-center justify-center transition-colors shadow-sm border border-gray-200"
        >
          <i class="pi pi-chevron-left text-gray-600 text-lg"></i>
        </button>
        <div>
          <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">RFQ Details</h1>
          <p class="text-sm text-gray-500 mt-1">{{ detail?.rfq_number || 'Loading...' }}</p>
        </div>
      </div>
      <Tag :value="formatStatus(detail?.status)" :severity="statusSeverity(detail?.status)" class="text-xs" />
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div v-for="i in 3" :key="i" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
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

    <template v-else-if="detail">
      <!-- iOS-style Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
          <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">RFQ Number</span>
            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
              <i class="pi pi-file text-blue-600 text-sm"></i>
            </div>
          </div>
          <p class="text-xl font-semibold text-gray-900">{{ detail.rfq_number }}</p>
          <p class="text-xs text-gray-500 mt-2">Created by: {{ detail.created_by?.fname }} {{ detail.created_by?.lname }}</p>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
          <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Title</span>
            <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
              <i class="pi pi-tag text-purple-600 text-sm"></i>
            </div>
          </div>
          <p class="text-lg font-semibold text-gray-900">{{ detail.title }}</p>
          <p class="text-xs text-gray-500 mt-2">Type: {{ capitalizeWords(detail.rfq_type) }}</p>
        </div>

        <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-5 shadow-lg">
          <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-blue-100 uppercase tracking-wider">Suppliers</span>
            <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
              <i class="pi pi-users text-white text-sm"></i>
            </div>
          </div>
          <p class="text-2xl font-bold text-white">{{ detail.suppliers?.length || 0 }}</p>
          <p class="text-xs text-blue-200 mt-2">{{ detail.items?.length || 0 }} line items</p>
        </div>
      </div>

      <!-- RFQ Details & Terms Card -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
          <div class="flex items-center gap-2">
            <i class="pi pi-file-text text-gray-500"></i>
            <h3 class="font-medium text-gray-700">RFQ Details & Terms</h3>
          </div>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Basic Information -->
            <div class="space-y-4">
              <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Description</p>
                <p class="text-gray-900">{{ detail.description || 'No description provided' }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 mb-1">Issue Date</p>
                <p class="font-medium text-gray-900">{{ formatDate(detail.issue_date) }}</p>
              </div>
            </div>

            <!-- Payment & Shipping Terms -->
            <div class="space-y-4">
              <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Currency</p>
                <p class="font-semibold text-gray-900">{{ detail.currency }}</p>
              </div>
            
              <div>
                <p class="text-xs text-gray-500 mb-1">Special Instructions</p>
                <p class="text-gray-900">{{ detail.instructions || 'None' }}</p>
              </div>
            </div>
          </div>

          <!-- Qualification Requirements -->
          <div v-if="detail.qualification_requirements" class="mt-6 p-4 bg-yellow-50 rounded-xl border border-yellow-100">
            <div class="flex items-center gap-2 mb-2">
              <i class="pi pi-exclamation-triangle text-yellow-600 text-sm"></i>
              <p class="text-xs font-medium text-yellow-800 uppercase tracking-wider">Qualification Requirements</p>
            </div>
            <p class="text-sm text-yellow-800">{{ detail.qualification_requirements }}</p>
          </div>
        </div>
      </div>

      <!-- Line Items -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
          <div class="flex items-center gap-2">
            <i class="pi pi-box text-gray-500"></i>
            <h3 class="font-medium text-gray-700">Line Items ({{ detail.items?.length || 0 }})</h3>
          </div>
        </div>
        <div class="p-6">
          <div v-if="detail.items && detail.items.length > 0" class="space-y-3">
            <div v-for="(item, index) in detail.items" :key="index" 
                 class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-sm transition-shadow">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-1">
                  <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Product</p>
                  <p class="font-semibold text-gray-900">{{ item.product?.product_name || 'Unknown' }}</p>
                  <p class="text-xs text-gray-500 mt-1">SKU: {{ item.product?.sku || 'N/A' }}</p>
                  <p v-if="item.variation" class="text-xs text-gray-500 mt-1">
                    Variation: {{ item.variation.variation_name }}
                  </p>
                </div>
                <div>
                  <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Quantity</p>
                  <p class="text-2xl font-semibold text-blue-600">{{ item.quantity }}</p>
                  <p v-if="item.specifications" class="text-xs text-gray-500 mt-2">
                    Specs: {{ item.specifications }}
                  </p>
                </div>
                <div>
                  <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Requirements</p>
                  <p class="text-sm text-gray-700">{{ item.requirements || 'Standard' }}</p>
                  <p v-if="item.product" class="text-xs text-gray-500 mt-2">
                    Base Price: {{ detail.currency || 'PHP' }} {{ parseFloat(item.product.base_price).toFixed(2) }}
                  </p>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8">
            <i class="pi pi-inbox text-3xl text-gray-300 mb-2"></i>
            <p class="text-gray-500">No line items</p>
          </div>
        </div>
      </div>

      <!-- Invited Suppliers -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
          <div class="flex items-center gap-2">
            <i class="pi pi-users text-gray-500"></i>
            <h3 class="font-medium text-gray-700">Invited Suppliers ({{ detail.suppliers?.length || 0 }})</h3>
          </div>
        </div>
        <div class="p-6">
          <div v-if="detail.suppliers && detail.suppliers.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="(invitedSupplier, index) in detail.suppliers" :key="index" 
                 class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-sm transition-shadow">
              <div class="flex items-start justify-between mb-3">
                <div>
                  <p class="font-semibold text-gray-900">{{ invitedSupplier.supplier?.supplier_name || 'Unknown Supplier' }}</p>
                  <p class="text-sm text-gray-600 mt-1">{{ invitedSupplier.supplier?.email || 'No email' }}</p>
                  <p v-if="invitedSupplier.supplier?.contact_person" class="text-xs text-gray-500 mt-1">
                    Contact: {{ invitedSupplier.supplier.contact_person }}
                  </p>
                </div>
                <Tag :value="formatStatus(invitedSupplier.status)" :severity="getSupplierStatusSeverity(invitedSupplier.status)" />
              </div>

              <div class="space-y-2 text-sm border-t border-gray-100 pt-3">
                <div class="flex items-center gap-2">
                  <i class="pi pi-envelope text-gray-400 text-xs"></i>
                  <span class="text-xs text-gray-600">Invited: {{ formatDateTime(invitedSupplier.invited_at) }}</span>
                </div>
                <div v-if="invitedSupplier.viewed_at" class="flex items-center gap-2">
                  <i class="pi pi-eye text-gray-400 text-xs"></i>
                  <span class="text-xs text-gray-600">Viewed: {{ formatDateTime(invitedSupplier.viewed_at) }}</span>
                </div>
                <div v-if="invitedSupplier.responded_at" class="flex items-center gap-2">
                  <i class="pi pi-check-circle text-green-500 text-xs"></i>
                  <span class="text-xs text-green-600">Responded: {{ formatDateTime(invitedSupplier.responded_at) }}</span>
                </div>
                <div v-if="invitedSupplier.decline_reason" class="mt-2 p-2 bg-red-50 rounded-lg">
                  <p class="text-xs text-red-600">Declined: {{ invitedSupplier.decline_reason }}</p>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8">
            <i class="pi pi-users text-3xl text-gray-300 mb-2"></i>
            <p class="text-gray-500">No suppliers invited</p>
          </div>
        </div>
      </div>

      <!-- Supplier Portal Responses -->
      <div v-if="portalFeedbackGroups.length > 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
          <div class="flex items-center gap-2">
            <i class="pi pi-inbox text-gray-500"></i>
            <h3 class="font-medium text-gray-700">Supplier Responses</h3>
          </div>
        </div>
        <div class="p-6 space-y-4">
          <div v-for="group in portalFeedbackGroups" :key="group.supplier_id" 
               class="bg-white rounded-xl border border-gray-100 p-4">
            <div class="flex items-start justify-between mb-4">
              <div>
                <p class="font-semibold text-gray-900">{{ group.supplier_name }}</p>
                <p class="text-sm text-gray-600">{{ group.supplier_email }}</p>
              </div>
              <div class="flex items-center gap-2">
                <Tag :value="group.items.length + ' Items'" severity="info" class="text-xs" />
                <button
                  v-if="group.items.some((item) => item.status === 'pending')"
                  @click="bulkApproveGroup(group.items.filter((item) => item.status === 'pending').map((item) => item.feedback_id))"
                  class="px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white text-xs font-medium rounded-lg transition-colors"
                >
                  Approve All
                </button>
              </div>
            </div>

            <div class="space-y-3">
              <div v-for="item in group.items" :key="item.id" 
                   class="bg-gray-50 rounded-xl p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <p class="text-xs text-gray-500 mb-1">Product</p>
                    <p class="font-medium text-gray-900">{{ item.product_name }}</p>
                    <p class="text-xs text-gray-500 mt-1">SKU: {{ item.sku || 'N/A' }}</p>
                    <p class="text-xs text-gray-500">Qty: {{ item.quantity }} {{ item.unit || '' }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500 mb-1">Quoted Price</p>
                    <p class="text-lg font-semibold text-green-600">{{ detail?.currency || 'PHP' }} {{ item.quoted_price }}</p>
                    <p v-if="item.submitted_at" class="text-xs text-gray-500 mt-1">Submitted: {{ formatDateTime(item.submitted_at) }}</p>
                  </div>
                  <div class="text-right">
                    <Tag :value="item.statusLabel" :severity="item.statusSeverity" class="mb-2" />
                    <p v-if="item.rejection_reason" class="text-xs text-red-600 mt-1">{{ item.rejection_reason }}</p>
                  </div>
                </div>

                <div v-if="item.negotiations && item.negotiations.length > 0" class="mt-3">
                  <p class="text-xs font-medium text-gray-700 mb-2">Negotiation History</p>
                  <div class="space-y-2">
                    <div v-for="nego in item.negotiations" :key="nego.id" 
                         class="bg-blue-50 p-2 rounded-lg text-xs">
                      <span class="font-medium">Counter: {{ detail?.currency || 'PHP' }} {{ parseFloat(nego.counter_price).toFixed(2) }}</span>
                      <span class="text-gray-500 ml-2">{{ formatDateTime(nego.created_at) }}</span>
                    </div>
                  </div>
                </div>

                <div v-if="item.status === 'pending'" class="mt-4 flex gap-2 justify-end">
                  <button
                    @click="approveFeedback(item.feedback_id)"
                    class="px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white text-xs font-medium rounded-lg transition-colors"
                  >
                    Approve
                  </button>
                  <button
                    @click="openRejectDialog(item.feedback_id)"
                    class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-medium rounded-lg transition-colors"
                  >
                    Reject
                  </button>
                </div>
              </div>
            </div>

            <div v-if="group.notes.length > 0" class="mt-4 p-3 bg-gray-100 rounded-lg">
              <p class="text-xs font-medium text-gray-700 mb-1">Notes</p>
              <ul class="space-y-1">
                <li v-for="(note, index) in group.notes" :key="index" class="text-xs text-gray-600">• {{ note }}</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Quotations -->
      <div v-if="detail.quotations && detail.quotations.length > 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
          <div class="flex items-center gap-2">
            <i class="pi pi-credit-card text-gray-500"></i>
            <h3 class="font-medium text-gray-700">Supplier Quotations</h3>
          </div>
        </div>
        <div class="p-6 space-y-3">
          <div v-for="(quotation, index) in detail.quotations" :key="index" 
               class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-sm transition-shadow">
            <div class="flex justify-between items-start">
              <div>
                <p class="font-semibold text-gray-900">{{ quotation.supplier?.supplier_name || 'Unknown' }}</p>
                <p class="text-sm text-gray-600 mt-1">Quote Date: {{ formatDate(quotation.quote_date) }}</p>
              </div>
              <div class="text-right">
                <p class="text-2xl font-bold text-blue-600">{{ detail.currency }} {{ parseFloat(quotation.total_price || 0).toFixed(2) }}</p>
                <Tag :value="formatStatus(quotation.status)" :severity="getSupplierStatusSeverity(quotation.status)" class="mt-2" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-end gap-3">
        <button
          v-if="canManageRfq && detail.status === 'draft'"
          @click="editRFQ"
          class="px-5 py-2.5 bg-white hover:bg-gray-50 text-amber-600 font-medium rounded-xl text-sm transition-colors flex items-center gap-2 border border-gray-200"
        >
          <i class="pi pi-pencil text-sm"></i>
          <span>Edit RFQ</span>
        </button>
        <button
          v-if="canManageRfq && detail.status === 'draft'"
          @click="deleteRFQ"
          class="px-5 py-2.5 bg-white hover:bg-gray-50 text-red-600 font-medium rounded-xl text-sm transition-colors flex items-center gap-2 border border-gray-200"
        >
          <i class="pi pi-trash text-sm"></i>
          <span>Delete RFQ</span>
        </button>
        <button
          v-if="detail.status === 'quotes_received'"
          @click="award"
          :disabled="processing"
          class="px-5 py-2.5 bg-green-500 hover:bg-green-600 disabled:opacity-50 text-white font-medium rounded-xl text-sm transition-colors flex items-center gap-2 shadow-sm"
        >
          <i class="pi pi-check text-sm"></i>
          <span>{{ processing ? 'Awarding...' : 'Award RFQ' }}</span>
        </button>
        <button
          v-if="canManagePurchaseOrders && detail.status === 'approved'"
          @click="createPOFromRFQ"
          class="px-5 py-2.5 bg-indigo-500 hover:bg-indigo-600 text-white font-medium rounded-xl text-sm transition-colors flex items-center gap-2 shadow-sm"
        >
          <i class="pi pi-shopping-cart text-sm"></i>
          <span>Create PO</span>
        </button>
      </div>
    </template>

    <!-- Error State -->
    <div v-else class="text-center py-12 bg-white rounded-2xl border border-gray-100">
      <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="pi pi-exclamation-circle text-gray-400 text-3xl"></i>
      </div>
      <h3 class="text-lg font-medium text-gray-700">RFQ Not Found</h3>
      <p class="text-gray-500 mt-2 mb-4">The RFQ you're looking for doesn't exist or you don't have permission to view it.</p>
      <button
        @click="router.push({ name: 'procurement.rfqs' })"
        class="px-5 py-2.5 bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-xl text-sm transition-colors inline-flex items-center gap-2"
      >
        <i class="pi pi-arrow-left"></i>
        <span>Back to RFQs</span>
      </button>
    </div>

    <ConfirmDialog />
    
    <!-- Reject Dialog -->
    <div v-if="rejectDialogVisible" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Reject Supplier Response</h3>
        
        <div class="space-y-4">
          <p class="text-sm text-gray-600">Provide a reason for rejection. This will be visible to the supplier.</p>
          <textarea
            v-model="rejectReason"
            rows="4"
            placeholder="Enter rejection reason..."
            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all resize-none"
          ></textarea>
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <button
            @click="closeRejectDialog"
            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl text-sm transition-colors"
          >
            Cancel
          </button>
          <button
            @click="submitReject"
            :disabled="processing"
            class="px-4 py-2 bg-red-500 hover:bg-red-600 disabled:opacity-50 text-white font-medium rounded-xl text-sm transition-colors flex items-center gap-2"
          >
            <span v-if="processing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span>{{ processing ? 'Rejecting...' : 'Reject' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Tag from 'primevue/tag'
import Skeleton from 'primevue/skeleton'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import procurementService from '../../../../services/procurement.service'
import { useAuthStore } from '../../../../stores/auth'

interface RFQDetail {
  id: number
  rfq_number: string
  title: string
  description?: string
  issue_date: string
  rfq_type: string
  currency: string
  shipping_terms?: string
  instructions?: string
  qualification_requirements?: string
  status: string
  created_by?: any
  items: any[]
  suppliers: any[]
  quotations: any[]
  supplier_portal_feedbacks?: any[]
}

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const authStore = useAuthStore()
const rfqId = Number(route.params.id)
const canManageRfq = computed(() => authStore.hasPermission('procurement.rfq.manage'))
const canManagePurchaseOrders = computed(() => authStore.hasPermission('procurement.purchase_orders.manage'))

const loading = ref(false)
const processing = ref(false)
const detail = ref<RFQDetail | null>(null)
const rejectDialogVisible = ref(false)
const rejectReason = ref('')
const rejectTargetFeedbackId = ref<number | null>(null)

// Helper functions
const formatStatus = (status: string): string => {
  if (!status) return 'DRAFT'
  return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const capitalizeWords = (str: string): string => {
  if (!str) return 'N/A'
  return str.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()).join(' ')
}

const formatDate = (date: string | null): string => {
  if (!date) return 'Not specified'
  return new Intl.DateTimeFormat('en-PH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  }).format(new Date(date))
}

const formatDateTime = (date: string | null): string => {
  if (!date) return 'N/A'
  return new Intl.DateTimeFormat('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(new Date(date))
}

const statusSeverity = (status: string): 'success' | 'info' | 'warn' | 'danger' | 'secondary' | 'contrast' => {
  const severityMap: Record<string, any> = {
    draft: 'contrast',
    sent: 'info',
    quotes_received: 'warn',
    awarded: 'success',
    cancelled: 'danger',
  }
  return severityMap[status] || 'contrast'
}

const getSupplierStatusSeverity = (status: string): 'success' | 'info' | 'warn' | 'danger' | 'secondary' => {
  const severityMap: Record<string, any> = {
    pending: 'info',
    viewed: 'warn',
    submitted: 'success',
    declined: 'danger',
    no_interest: 'secondary',
  }
  return severityMap[status] || 'secondary'
}

const portalFeedbackGroups = computed(() => {
  if (!detail.value?.supplier_portal_feedbacks) return []
  const groups: Record<string, any> = {}

  detail.value.supplier_portal_feedbacks.forEach((feedback: any) => {
    const supplier = feedback?.supplier_portal?.supplier
    const supplierId = supplier?.id || feedback?.supplier_portal_id || 'unknown'
    if (!groups[supplierId]) {
      groups[supplierId] = {
        supplier_id: supplierId,
        supplier_name: supplier?.supplier_name || supplier?.company_name || 'Unknown Supplier',
        supplier_email: supplier?.email || '',
        items: [],
        notes: [],
      }
    }

    const rfqItem = feedback?.rfq_item
    const product = rfqItem?.product
    groups[supplierId].items.push({
      id: feedback.id,
      feedback_id: feedback.id,
      product_name: product?.product_name || 'Unknown Item',
      sku: product?.sku,
      quantity: rfqItem?.quantity || '-',
      unit: rfqItem?.unit || '',
      quoted_price: parseFloat(feedback?.quoted_price || 0).toFixed(2),
      submitted_at: feedback?.submitted_at || null,
      status: feedback?.status || 'pending',
      statusLabel: capitalizeWords(feedback?.status || 'pending'),
      statusSeverity: feedback?.status === 'approved' ? 'success' : feedback?.status === 'rejected' ? 'danger' : 'info',
      rejection_reason: feedback?.rejection_reason || '',
      negotiations: feedback?.negotiations || [],
    })

    if (feedback?.description) {
      groups[supplierId].notes.push(feedback.description)
    }
  })

  return Object.values(groups)
})

const approveFeedback = async (feedbackId: number) => {
  if (!detail.value) return
  confirm.require({
    message: 'Approve this supplier response? This will lock other quotes for the same item.',
    header: 'Confirm Approval',
    icon: 'pi pi-check-circle',
    accept: async () => {
      processing.value = true
      try {
        await procurementService.reviewPortalFeedback(detail.value!.id, feedbackId, { status: 'approved' })
        toast.add({
          severity: 'success',
          summary: 'Approved',
          detail: 'Supplier response approved.',
          life: 3000,
        })
        await loadDetail()
      } catch (error: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to approve response',
          life: 3000,
        })
      } finally {
        processing.value = false
      }
    },
  })
}

const openRejectDialog = (feedbackId: number) => {
  rejectTargetFeedbackId.value = feedbackId
  rejectReason.value = ''
  rejectDialogVisible.value = true
}

const closeRejectDialog = () => {
  rejectDialogVisible.value = false
  rejectReason.value = ''
  rejectTargetFeedbackId.value = null
}

const submitReject = async () => {
  if (!detail.value || !rejectTargetFeedbackId.value) return
  if (!rejectReason.value.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Required',
      detail: 'Please provide a rejection reason.',
      life: 3000,
    })
    return
  }

  processing.value = true
  try {
    await procurementService.reviewPortalFeedback(detail.value.id, rejectTargetFeedbackId.value, {
      status: 'rejected',
      rejection_reason: rejectReason.value.trim(),
    })
    toast.add({
      severity: 'success',
      summary: 'Rejected',
      detail: 'Supplier response rejected.',
      life: 3000,
    })
    closeRejectDialog()
    await loadDetail()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to reject response',
      life: 3000,
    })
  } finally {
    processing.value = false
  }
}

const bulkApproveGroup = async (feedbackIds: number[]) => {
  if (!detail.value || feedbackIds.length === 0) return
  confirm.require({
    message: 'Approve all pending responses for this supplier? This will lock other quotes per item.',
    header: 'Confirm Bulk Approval',
    icon: 'pi pi-check-circle',
    accept: async () => {
      processing.value = true
      try {
        await procurementService.bulkApprovePortalFeedbacks(detail.value!.id, { feedback_ids: feedbackIds })
        toast.add({
          severity: 'success',
          summary: 'Approved',
          detail: 'Selected responses approved.',
          life: 3000,
        })
        await loadDetail()
      } catch (error: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to bulk approve responses',
          life: 3000,
        })
      } finally {
        processing.value = false
      }
    },
  })
}

const loadDetail = async () => {
  loading.value = true
  try {
    const response = await procurementService.getRFQ(rfqId)
    detail.value = response.data || null
  } catch (error) {
    console.error('Failed to load RFQ detail:', error)
    detail.value = null
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load RFQ details',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const send = async () => {
  processing.value = true
  try {
    const response = await procurementService.sendRfq(rfqId, {
      supplier_ids: detail.value?.suppliers.map(s => s.supplier_id) || [],
    })
    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'RFQ sent to suppliers',
        life: 3000,
      })
      await loadDetail()
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to send RFQ',
      life: 3000,
    })
  } finally {
    processing.value = false
  }
}

const award = async () => {
  processing.value = true
  try {
    const response = await procurementService.awardRFQ(rfqId, {
      supplier_id: detail.value?.suppliers[0]?.supplier_id,
      evaluation_notes: 'Best price and quality',
    })
    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'RFQ awarded',
        life: 3000,
      })
      await loadDetail()
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to award RFQ',
      life: 3000,
    })
  } finally {
    processing.value = false
  }
}

const editRFQ = () => {
  router.push({
    name: 'procurement.rfqs.create',
    query: { rfq_id: rfqId },
  })
}

const deleteRFQ = async () => {
  const confirmed = window.confirm('Delete this draft RFQ? This cannot be undone.')
  if (!confirmed) return

  processing.value = true
  try {
    await procurementService.deleteRFQ(rfqId)
    toast.add({
      severity: 'success',
      summary: 'Deleted',
      detail: 'RFQ deleted successfully',
      life: 2500,
    })
    router.push({ name: 'procurement.rfqs' })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to delete RFQ',
      life: 3000,
    })
  } finally {
    processing.value = false
  }
}

const createPOFromRFQ = () => {
  router.push({
    name: 'procurement.purchase-orders.create',
    query: { rfq_id: rfqId },
  })
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