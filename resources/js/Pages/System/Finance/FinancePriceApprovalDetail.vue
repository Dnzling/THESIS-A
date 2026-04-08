<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <Toast />
    <ConfirmDialog />

    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <Button
          icon="pi pi-chevron-left"
          severity="secondary"
          text
          rounded
          @click="router.push({ name: 'finance.price-approvals' })"
        />
        <div>
          <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Price Approval Review</h1>
          <p class="text-sm text-gray-500 mt-1">{{ detail?.product_name || 'Loading...' }}</p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <div v-if="detail?.price_approval_status === 'pending'" class="flex items-center gap-2">
          <Button
            label="Approve"
            icon="pi pi-check"
            severity="success"
            @click="confirmApprove"
          />
          <Button
            label="Reject"
            icon="pi pi-times"
            severity="danger"
            outlined
            @click="openRejectDialog = true"
          />
        </div>
        <Tag :value="labelStatus(detail?.price_approval_status)" :severity="severityStatus(detail?.price_approval_status)" />
      </div>
    </div>

    <div v-if="loading" class="space-y-4">
      <Skeleton height="90px" />
      <Skeleton height="140px" />
      <Skeleton height="220px" />
    </div>

    <div v-else-if="detail" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
          <p class="text-xs text-gray-500 uppercase tracking-wider mb-2">SKU</p>
          <p class="text-xl font-semibold text-gray-900">{{ detail.sku }}</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
          <p class="text-xs text-gray-500 uppercase tracking-wider mb-2">Current Base Price</p>
          <p class="text-xl font-semibold text-gray-900">PHP {{ formatNumber(Number(detail.base_price || 0)) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
          <p class="text-xs text-gray-500 uppercase tracking-wider mb-2">Requested Base Price</p>
          <p class="text-xl font-semibold text-blue-700">PHP {{ formatNumber(Number(detail.pending_base_price ?? detail.base_price ?? 0)) }}</p>
        </div>
        <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-5 shadow-lg">
          <p class="text-xs text-blue-100 uppercase tracking-wider mb-2">Requested Discounted</p>
          <p class="text-xl font-bold text-white">
            {{ detail.pending_discounted_price != null ? `PHP ${formatNumber(Number(detail.pending_discounted_price))}` : 'None' }}
          </p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <h3 class="font-medium text-gray-900 mb-4">Proposal Information</h3>
          <div class="space-y-2 text-sm">
            <p><span class="text-gray-500">Proposed At:</span> <span class="text-gray-800">{{ formatDateTime(detail.price_proposed_at) }}</span></p>
            <p><span class="text-gray-500">Proposed By User ID:</span> <span class="text-gray-800">{{ detail.price_proposed_by || '-' }}</span></p>
            <p><span class="text-gray-500">Approval Notes:</span> <span class="text-gray-800">{{ detail.price_approval_notes || '-' }}</span></p>
          </div>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <h3 class="font-medium text-gray-900 mb-4">Product Details</h3>
          <div class="space-y-2 text-sm">
            <p><span class="text-gray-500">Category:</span> <span class="text-gray-800">{{ detail.category?.category_name || '-' }}</span></p>
            <p><span class="text-gray-500">Brand:</span> <span class="text-gray-800">{{ detail.brand || '-' }}</span></p>
            <p><span class="text-gray-500">Status:</span> <span class="text-gray-800">{{ detail.is_active ? 'Active' : 'Inactive' }}</span></p>
          </div>
        </div>
      </div>
    </div>

    <Dialog v-model:visible="openRejectDialog" header="Reject Price Change" :modal="true" class="w-full max-w-lg">
      <div class="space-y-3">
        <label class="text-sm font-medium text-gray-700">Reason</label>
        <Textarea v-model="rejectReason" rows="4" placeholder="Enter rejection reason..." fluid />
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="openRejectDialog = false" />
        <Button label="Reject" severity="danger" :disabled="!rejectReason.trim()" @click="submitReject" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import merchandisingService from '@/services/merchandising.service'
import Tag from 'primevue/tag'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import Skeleton from 'primevue/skeleton'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

const id = Number(route.params.id)
const loading = ref(false)
const detail = ref<any>(null)
const openRejectDialog = ref(false)
const rejectReason = ref('')

const loadDetail = async () => {
  loading.value = true
  try {
    const response = await merchandisingService.getProduct(id)
    detail.value = response?.data || response
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load detail', life: 3000 })
    detail.value = null
  } finally {
    loading.value = false
  }
}

const confirmApprove = () => {
  confirm.require({
    message: 'Approve this price change request?',
    header: 'Confirm Approval',
    icon: 'pi pi-check-circle',
    acceptClass: 'p-button-success',
    accept: submitApprove,
  })
}

const submitApprove = async () => {
  try {
    await merchandisingService.approveProductPrice(id, 'Approved by finance')
    toast.add({ severity: 'success', summary: 'Approved', detail: 'Price change approved.', life: 3000 })
    await loadDetail()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to approve', life: 3000 })
  }
}

const submitReject = async () => {
  try {
    await merchandisingService.rejectProductPrice(id, rejectReason.value)
    openRejectDialog.value = false
    rejectReason.value = ''
    toast.add({ severity: 'success', summary: 'Rejected', detail: 'Price change rejected.', life: 3000 })
    await loadDetail()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to reject', life: 3000 })
  }
}

const labelStatus = (status?: string) => {
  if (status === 'pending') return 'Pending'
  if (status === 'rejected') return 'Rejected'
  return 'Approved'
}

const severityStatus = (status?: string): 'success' | 'warn' | 'danger' | 'info' | 'secondary' | 'contrast' => {
  if (status === 'pending') return 'warn'
  if (status === 'rejected') return 'danger'
  return 'success'
}

const formatNumber = (value: number) => {
  return new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value || 0)
}

const formatDateTime = (date?: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

onMounted(loadDetail)
</script>
