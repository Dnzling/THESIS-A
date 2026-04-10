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

    <div v-else-if="detail" class="space-y-6">
      <!-- iOS-style Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 ">
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

      <!-- Delivery Status & Logs -->
      <div v-if="['sent_to_supplier', 'supplier_accepted', 'in_transit', 'delivered'].includes(shipmentStatus || '') || deliveryLogs.length" class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/70 shadow-sm p-4">
          <div class="flex items-center justify-between text-sm font-semibold text-slate-600">
            <div class="flex items-center gap-2" v-for="step in steps" :key="step.key">
              <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold',
                step.active ? 'bg-emerald-500' : 'bg-slate-300']">
                {{ step.index }}
              </div>
              <span :class="step.active ? 'text-emerald-600' : 'text-slate-500'">{{ step.label }}</span>
              <div v-if="step.index !== steps.length" class="w-10 h-px bg-slate-200 mx-3"></div>
            </div>
          </div>
          <div class="flex items-center gap-2 text-xs text-slate-500 mt-2">
            <i class="pi pi-info-circle text-slate-400"></i>
            <span v-if="shipmentStatus">Current shipment status: {{ formatStatus(shipmentStatus) }}</span>
            <span v-else>No shipment created yet.</span>
          </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/70 shadow-sm" v-if="deliveryLogs.length">
          <div class="px-6 pt-4 pb-2 flex items-center gap-2">
            <i class="pi pi-book text-slate-500"></i>
            <h3 class="font-semibold text-slate-800">Delivery Logs</h3>
          </div>
          <div v-if="shipmentInfo" class="px-6 pb-3 text-sm text-slate-700">
            <div class="flex flex-wrap gap-4">
              <span class="font-semibold">{{ shipmentInfo.driver_name || 'Driver' }}</span>
              <span>Truck: {{ shipmentInfo.truck_brand || '-' }} {{ shipmentInfo.truck_type || '' }}</span>
              <span>Plate: {{ shipmentInfo.plate_number || '-' }}</span>
              <span>Contact: {{ shipmentInfo.driver_contact || '-' }}</span>
            </div>
          </div>
          <div class="divide-y divide-slate-100">
            <div v-for="log in deliveryLogs" :key="log.id" class="py-3 px-6 flex items-start gap-3">
              <div class="w-2 h-2 rounded-full mt-2" :class="logDotColor(log.event_type)"></div>
              <div class="flex-1">
                <div class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                  {{ log.event_type }}
                  <Tag :value="formatDateTime(log.created_at)" severity="secondary" class="text-xs" />
                </div>
                <div class="text-xs text-slate-500">
                  By {{ shipmentInfo?.driver_name || 'Driver' }}<span v-if="log.receiver_name"> • Receiver: {{ log.receiver_name }}</span>
                </div>
                <p v-if="log.notes" class="text-sm text-slate-700 mt-1">{{ log.notes }}</p>
              </div>
            </div>
          </div>
        </div>
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
              <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
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
                  <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Product Cost</p>
                  <p class="text-sm text-gray-700">₱{{ formatNumber(Number(item?.product?.cost_price ?? 0)) }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Tax Rate</p>
                  <p class="text-sm text-gray-700">{{ Number(item?.product?.tax_rate ?? 0).toFixed(2) }}%</p>
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

       <!-- Action Buttons -->
      <div class="flex justify-end gap-3 flex-wrap">
        <Button
          v-if="canManageRequisitions && detail.status === 'draft'"
          size="medium"
          severity="warn"
          outlined
          icon="pi pi-pencil"
          @click="editPR"
        />

        <Button
          v-if="canManageRequisitions && detail.status === 'draft'"
          size="medium"
          severity="danger"
          outlined
          icon="pi pi-trash"
          @click="deletePR"
        />

        <Button
          v-if="canManageRequisitions && detail.status === 'draft'"
          size="medium"
          severity="info"
          icon="pi pi-send"
          :loading="processing"
          @click="submit"
        :label="processing ? 'Submitting...' : 'Submit'" />

        <Button
          v-if="canApprove"
          size="medium"
          severity="danger"
          outlined
          icon="pi pi-times"
          :loading="processing"
          @click="showRejectDialog = true"
        />

        <Button
          v-if="canApprove"
          size="medium"
          severity="success"
          icon="pi pi-check"
          :loading="processing"
          @click="approve"
          :label="processing ? 'Approving...' : 'Approve'"
        />

        <Button
          v-if="detail && detail.status === 'procurement_processing' && (canManagePurchaseOrders || canManageRfq)"
          size="medium"
          severity="info"
          @click="createRequest"
          label="Create Request"
        />

        <Button
          v-if="canManageReceiving && detail.status === 'delivered' && deliveredPO"
          size="medium"
          severity="secondary"
          icon="pi pi-archive"
          @click="createGoodsReceipt(deliveredPO)"
        />

        <Button
          v-if="canManageRfq && canCreateRfqFromDetail"
          size="medium"
          severity="success"
          icon="pi pi-send"
          @click="createRfqFromRequisition"
        />

        <Button
          v-if="canManagePurchaseOrders && canCreatePoFromDetail"
          size="medium"
          severity="info"
          icon="pi pi-shopping-cart"
          @click="createPoFromRequisition"
        />
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
    </div>

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

    <!-- Confirm Dialog -->
    <div v-if="confirmVisible" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ confirmTitle }}</h3>
        <p class="text-sm text-gray-600">{{ confirmMessage }}</p>

        <div class="flex justify-end gap-3 mt-6">
          <button @click="confirmVisible = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl text-sm">Cancel</button>
          <button @click="performConfirmedAction" :disabled="processing" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-medium rounded-xl text-sm">
            <span v-if="processing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></span>
            Confirm
          </button>
        </div>
      </div>
    </div>

    <!-- Response Dialog -->
    <!-- response dialog removed: success now shows toast and navigates; errors still use toast -->

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
import Button from 'primevue/button'
import procurementService from '../../../../services/procurement.service'
import { useAuthStore } from '../../../../stores/auth'
import axiosClient from '@/axios'

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
const confirmVisible = ref(false)
const confirmTitle = ref('')
const confirmMessage = ref('')
const confirmAction = ref('')
const responseVisible = ref(false)
const responseTitle = ref('')
const responseMessage = ref('')
const responseSeverity = ref<'success' | 'error' | 'info'>('info')
const shipmentStatus = ref<string | null>(null)
const shipmentInfo = ref<any>(null)
const deliveryLogs = ref<any[]>([])
const steps = computed(() => {
  const sent = shipmentStatus.value === 'sent_to_supplier' || shipmentStatus.value === 'supplier_accepted'
  const inTransit = shipmentStatus.value === 'in_transit'
  const delivered = shipmentStatus.value === 'delivered'
  return [
    { key: 'supplier', label: 'Supplier Approval', index: 1, active: sent || inTransit || delivered },
    { key: 'transit', label: 'In Transit', index: 2, active: inTransit || delivered },
    { key: 'delivered', label: 'Order Delivered', index: 3, active: delivered },
  ]
})

const normalize = (value: unknown): string =>
  String(value ?? '')
    .trim()
    .toLowerCase()
    .replace(/[\s-]+/g, '_')

const hasApprovalPermission = computed(() => {
  const has = (perm: string) => authStore.hasPermission?.(perm)
  return (
    has('procurement.requisitions.approve') ||
    has('procurement.admin')
  )
})
const canManageRequisitions = computed(() => authStore.hasPermission('procurement.requisitions.manage'))
const canManageRfq = computed(() => authStore.hasPermission('procurement.rfq.manage'))
const canManagePurchaseOrders = computed(() => authStore.hasPermission('procurement.purchase_orders.manage'))
const canManageReceiving = computed(() => authStore.hasPermission('procurement.receiving.manage'))
const processingReadyStatuses = ['warehouse_approved', 'branch_manager_approved', 'procurement_processing']
const canCreateRfqFromDetail = computed(() => {
  if (!detail.value) return false
  return processingReadyStatuses.includes(detail.value.status) && Boolean(detail.value.any_item_missing_supplier)
})
const canCreatePoFromDetail = computed(() => {
  if (!detail.value) return false
  return processingReadyStatuses.includes(detail.value.status) && Boolean(detail.value.all_items_have_suppliers)
})

// Derive supplier binding from items when backend flags are missing
const supplierGrouping = computed(() => {
  const items = Array.isArray(detail.value?.items) ? detail.value.items : []
  const grouped: Record<string, any[]> = {}
  let boundCount = 0
  for (const it of items) {
    const supId = it?.selected_supplier_id ?? (Array.isArray(it?.product?.suppliers) && it.product.suppliers[0]?.id) ?? null
    const key = supId ? String(supId) : 'NO_SUPPLIER'
    if (!grouped[key]) grouped[key] = []
    grouped[key].push(it)
    if (supId) boundCount++
  }
  return {
    groups: grouped,
    total: items.length,
    boundCount,
    unboundCount: items.length - boundCount,
  }
})

const createRequest = () => {
  if (!detail.value) return
  const status = normalize(detail.value.status)
  if (status !== 'procurement_processing') {
    // only route when in processing state
    return
  }

  const { boundCount, unboundCount } = supplierGrouping.value
  if (boundCount > 0 && unboundCount > 0) {
    router.push({ name: 'procurement.purchase-orders.create', query: { requisition_id: requisitionId, split: '1' } })
    return
  }
  if (unboundCount === 0 && boundCount > 0) {
    router.push({ name: 'procurement.purchase-orders.create', query: { requisition_id: requisitionId } })
    return
  }
  router.push({ name: 'procurement.rfqs.create', query: { requisition_id: requisitionId } })
}

const canApprove = computed(() => {
  if (!detail.value) return false
  const status = normalize(detail.value.status)
  const approvableStatuses = new Set([
    'pending',
    'warehouse_approved',
    'branch_manager_approved',
    'pending_central_review',
  ])

  return approvableStatuses.has(status) && hasApprovalPermission.value
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

const logDotColor = (eventType: string) => {
  const map: Record<string, string> = {
    'Arrived': 'bg-blue-500',
    'Start Unloading': 'bg-orange-500',
    'Finish Unloading': 'bg-green-500',
    'Delivered': 'bg-emerald-600',
    'Issue': 'bg-red-500',
  }
  return map[eventType] || 'bg-slate-300'
}

const loadDeliveryLogs = async () => {
  try {
    const res = await axiosClient.get(`/api/procurement/requisitions/${requisitionId}/delivery-logs`)
    const payload = res?.data ?? res ?? {}
    const data = payload.data ?? {}
    shipmentStatus.value = data.shipment_status || data.status || data.shipment?.status || null
    shipmentInfo.value = data.shipment || null
    deliveryLogs.value = data.logs || []
  } catch (e) {
    shipmentStatus.value = null
    shipmentInfo.value = null
    deliveryLogs.value = []
  }
}

const loadDetail = async () => {
  loading.value = true
  try {
    const response = await procurementService.getPurchaseRequisition(requisitionId)
    detail.value = response.data || null

    // Run non-critical enrichment in background so main details never get stuck in loading state.
    void enrichSuppliersForItems()
    void loadDeliveryLogs()
  } catch (error) {
    console.error('Failed to load PR detail:', error)
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load PR', life: 3000 })
  } finally {
    loading.value = false
  }
}

const withTimeout = async <T>(promise: Promise<T>, ms = 5000): Promise<T> => {
  let timer: ReturnType<typeof setTimeout> | null = null
  const timeoutPromise = new Promise<never>((_, reject) => {
    timer = setTimeout(() => reject(new Error('Request timed out')), ms)
  })
  try {
    return await Promise.race([promise, timeoutPromise])
  } finally {
    if (timer) clearTimeout(timer)
  }
}

const enrichSuppliersForItems = async () => {
  try {
    const items = Array.isArray(detail.value?.items) ? detail.value.items : []
    const tasks = items.map(async (it: any) => {
      const pid = Number(it?.product?.id || it?.product_id)
      if (!pid) return

      const hasSuppliers = Array.isArray(it?.product?.suppliers) && it.product.suppliers.length > 0
      if (hasSuppliers) return

      try {
        const supRes = await withTimeout(procurementService.getProductSuppliers(pid), 4000)
        const suppliers = supRes?.data || supRes || []
        it.product = it.product || {}
        it.product.suppliers = Array.isArray(suppliers) ? suppliers : (suppliers.data || [])
      } catch (e) {
        // Keep silent; enrichment is optional for initial page load.
      }
    })

    await Promise.allSettled(tasks)
  } catch (e) {
    // silent background failure
  }
}

const submit = async () => {
  // open confirmation
  confirmTitle.value = 'Submit Purchase Requisition'
  confirmMessage.value = 'Are you sure you want to submit this requisition for approval?'
  confirmAction.value = 'submit'
  confirmVisible.value = true
}

const approve = async () => {
  processing.value = true
  try {
    const res = await procurementService.approvePurchaseRequisition(requisitionId, { notes: '' })
    // Log response for debugging
    console.debug('[PR Detail] approve response', res)
    const payload = res.data ?? res
    // The service returns response.data (payload). Treat either payload.success===true
    // or HTTP 2xx as success.
    const httpOk = (payload && payload.success === true) ||
      (res && res.status && res.status >= 200 && res.status < 300) ||
      // some service wrappers return the data object directly (updated requisition)
      (payload && (payload.id || payload.pr_number || payload.status))
    if (httpOk) {
      try { toast.clear() } catch (e) {}
      toast.add({ severity: 'success', summary: 'Approved', detail: payload?.message || 'Purchase requisition approved successfully', life: 1200 })
      // suppress global response error dialogs for a short window to avoid
      // race conditions where a background request triggers an error after success
      try { (window as any).__suppressResponseErrors = true } catch (e) {}
      setTimeout(() => { try { (window as any).__suppressResponseErrors = false } catch (e) {} }, 2000)

      const automation = payload?.automation || {}
      const nextAction = automation?.next_action
      const hasMixed = Boolean(automation?.has_mixed_supplier_assignment)
      const rfqs = Array.isArray(automation?.rfqs) ? automation.rfqs : []
      const pos = Array.isArray(automation?.purchase_orders) ? automation.purchase_orders : []

      if (hasMixed) {
        toast.add({
          severity: 'info',
          summary: 'Processing Queue',
          detail: 'Mixed supplier assignment detected. RFQ was created first for items without supplier, then PO(s) for supplier-linked items.',
          life: 5000
        })
      }

      setTimeout(() => {
        if (nextAction === 'rfq') {
          const firstRfq = rfqs[0]
          if (firstRfq?.id) {
            router.push({ name: 'procurement.rfqs.detail', params: { id: firstRfq.id } })
            return
          }
          router.push({ name: 'procurement.rfqs', query: { requisition_id: requisitionId } })
          return
        }

        if (nextAction === 'po') {
          const firstPo = pos[0]
          if (firstPo?.id) {
            router.push({ name: 'procurement.purchase-orders.detail', params: { id: firstPo.id } })
            return
          }
          router.push({ name: 'procurement.purchase-orders', query: { requisition_id: requisitionId } })
          return
        }

        router.push({ name: 'procurement.purchase-requisitions' })
      }, 1200)
    } else {
      console.error('[PR Detail] approve indicates non-success payload', payload)
      toast.add({ severity: 'error', summary: 'Error', detail: payload?.message || 'Failed to approve', life: 4000 })
    }
  } catch (err: any) {
    console.error('Approve failed', err)
    toast.add({ severity: 'error', summary: 'Error', detail: err.response?.data?.message || err.message || 'Failed to approve', life: 4000 })
  } finally {
    processing.value = false
  }
}

const submitRejection = async () => {
  // confirm rejection with reason
  if (!rejectReason.value.trim()) {
    toast.add({ severity: 'warn', summary: 'Required', detail: 'Please provide a rejection reason.', life: 3000 })
    return
  }
  confirmTitle.value = 'Reject Purchase Requisition'
  confirmMessage.value = `Reject this requisition for reason: "${rejectReason.value.trim()}"?`
  confirmAction.value = 'reject'
  confirmVisible.value = true
}

const editPR = () => {
  confirmTitle.value = 'Edit Purchase Requisition'
  confirmMessage.value = 'Open editor for this requisition?'
  confirmAction.value = 'edit'
  confirmVisible.value = true
}

const deletePR = () => {
  confirmTitle.value = 'Delete Purchase Requisition'
  confirmMessage.value = 'This will permanently delete the requisition. Continue?'
  confirmAction.value = 'delete'
  confirmVisible.value = true
}

const createRfqFromRequisition = () => {
  confirmTitle.value = 'Create RFQ'
  confirmMessage.value = 'Create an RFQ from this requisition?'
  confirmAction.value = 'create_rfq'
  confirmVisible.value = true
}

const createPoFromRequisition = () => {
  confirmTitle.value = 'Create Purchase Order'
  confirmMessage.value = 'Create a Purchase Order from this requisition?'
  confirmAction.value = 'create_po'
  confirmVisible.value = true
}

onMounted(() => {
  loadDetail()
  loadDeliveryLogs()
})

const deliveredPO = computed(() => {
  if (!detail.value?.purchase_orders) return null
  return detail.value.purchase_orders.find((po: any) => po.status === 'delivered')
})

const createGoodsReceipt = (po: any) => {
  confirmTitle.value = 'Create Goods Receipt'
  confirmMessage.value = `Create goods receipt for PO ${po?.po_number || po?.id}?`
  // pass a small payload via confirmAction to identify PO
  confirmAction.value = `create_gr:${po?.id}`
  confirmVisible.value = true
}

const performConfirmedAction = async () => {
  confirmVisible.value = false
  processing.value = true
  try {
    const action = confirmAction.value
    let res: any = null

    if (action === 'submit') {
      res = await procurementService.submitPurchaseRequisition(requisitionId)
    } else if (action === 'approve') {
      res = await procurementService.approvePurchaseRequisition(requisitionId, { notes: '' })
    } else if (action === 'reject') {
      res = await procurementService.rejectPurchaseRequisition(requisitionId, {
        reason: rejectReason.value,
      })
      // close reject dialog when confirmed
      showRejectDialog.value = false
      rejectReason.value = ''
    } else if (action === 'edit') {
      router.push({ name: 'procurement.purchase-requisitions.edit', params: { id: requisitionId } })
      return
    } else if (action === 'delete') {
      res = await procurementService.deletePurchaseRequisition(requisitionId)
      if (res) router.push({ name: 'procurement.purchase-requisitions' })
    } else if (action === 'create_rfq') {
      router.push({ name: 'procurement.rfqs.create', query: { requisition_id: requisitionId } })
      return
    } else if (action === 'create_po') {
      router.push({ name: 'procurement.purchase-orders.create', query: { requisition_id: requisitionId } })
      return
    } else if (action && action.startsWith('create_gr:')) {
      const poId = Number(action.split(':')[1])
      router.push({ name: 'inventory.goods-receipts.create', query: { po_id: poId } })
      return
    }

    // On successful action show a brief success toast and navigate back to list
    if (res) {
      const payload = res.data ?? res
      const ok = payload?.success || (res && res.status && res.status >= 200 && res.status < 300) ||
        // treat returned requisition object as success
        Boolean(payload && (payload.id || payload.pr_number || payload.status))
      await loadDetail()
      if (ok) {
        toast.add({ severity: 'success', summary: 'Success', detail: payload?.message || 'Action completed successfully', life: 1200 })

        const automation = payload?.automation || {}
        const nextAction = automation?.next_action
        const rfqs = Array.isArray(automation?.rfqs) ? automation.rfqs : []
        const pos = Array.isArray(automation?.purchase_orders) ? automation.purchase_orders : []

        setTimeout(() => {
          if (nextAction === 'rfq') {
            const firstRfq = rfqs[0]
            if (firstRfq?.id) {
              router.push({ name: 'procurement.rfqs.detail', params: { id: firstRfq.id } })
              return
            }
            router.push({ name: 'procurement.rfqs', query: { requisition_id: requisitionId } })
            return
          }

          if (nextAction === 'po') {
            const firstPo = pos[0]
            if (firstPo?.id) {
              router.push({ name: 'procurement.purchase-orders.detail', params: { id: firstPo.id } })
              return
            }
            router.push({ name: 'procurement.purchase-orders', query: { requisition_id: requisitionId } })
            return
          }

          router.push({ name: 'procurement.purchase-requisitions' })
        }, 1200)
      } else {
        // Fallback: show error dialog when server indicates failure
        responseTitle.value = 'Response'
        responseMessage.value = payload?.message || JSON.stringify(payload, null, 2)
        responseSeverity.value = 'error'
        responseVisible.value = true
      }
    }
  } catch (err: any) {
    console.error('Action failed', err)
    responseTitle.value = 'Error'
    responseMessage.value = err.response?.data?.message || err.message || 'Failed to perform action'
    responseSeverity.value = 'error'
    responseVisible.value = true
  } finally {
    processing.value = false
    confirmAction.value = ''
  }
}
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
