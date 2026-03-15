<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <!-- Header -->
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'procurement.rfqs' })" />
        <div>
          <h2 class="text-2xl font-bold text-gray-800">RFQ Details</h2>
          <p class="text-sm text-gray-500 mt-1">{{ detail?.rfq_number || 'Loading...' }}</p>
        </div>
      </div>
      <Tag :value="detail?.status?.toUpperCase() || 'DRAFT'" :severity="statusSeverity(detail?.status || 'draft')" />
    </div>

    <!-- Loading State -->
    <Card v-if="loading">
      <template #content>
        <div class="space-y-4">
          <Skeleton height="100px" class="rounded-lg" />
          <Skeleton height="200px" class="rounded-lg" />
          <Skeleton height="200px" class="rounded-lg" />
        </div>
      </template>
    </Card>

    <template v-else-if="detail">
      <!-- RFQ Header Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card>
          <template #content>
            <div class="space-y-2">
              <p class="text-xs font-semibold text-gray-600 uppercase">RFQ Number</p>
              <p class="text-xl font-bold text-gray-900">{{ detail.rfq_number }}</p>
              <p class="text-xs text-gray-500 mt-2">Created by: {{ detail.created_by?.fname }} {{ detail.created_by?.lname }}</p>
            </div>
          </template>
        </Card>

        <Card>
          <template #content>
            <div class="space-y-2">
              <p class="text-xs font-semibold text-gray-600 uppercase">Title</p>
              <p class="text-lg font-bold text-gray-900">{{ detail.title }}</p>
              <p class="text-xs text-gray-500 mt-2">Type: {{ capitalizeWords(detail.rfq_type) }}</p>
            </div>
          </template>
        </Card>

        <Card>
          <template #content>
            <div class="space-y-2">
              <p class="text-xs font-semibold text-gray-600 uppercase">Deadline</p>
              <p class="text-lg font-bold text-gray-900">{{ formatDate(detail.deadline_date) }}</p>
              <p class="text-xs text-gray-500 mt-2">Days remaining: {{ calculateDaysRemaining(detail.deadline_date) }}</p>
            </div>
          </template>
        </Card>

        <Card>
          <template #content>
            <div class="space-y-2">
              <p class="text-xs font-semibold text-gray-600 uppercase">Suppliers</p>
              <p class="text-2xl font-bold text-gray-900">{{ detail.suppliers?.length || 0 }}</p>
              <p class="text-xs text-gray-500 mt-2">Line Items: {{ detail.items?.length || 0 }}</p>
            </div>
          </template>
        </Card>
      </div>

    

      <!-- RFQ Details Section -->
      <Card>
        <template #header>
          <div class="px-6 pt-6">
            <h3 class="text-lg font-semibold text-gray-800">📋 RFQ Details & Terms</h3>
          </div>
        </template>
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Basic Information -->
            <div class="space-y-3">
              <div class="border-l-4 border-blue-500 pl-3">
                <p class="text-xs font-semibold text-gray-600 uppercase">Description</p>
                <p class="text-gray-900">{{ detail.description || 'No description provided' }}</p>
              </div>
              <div class="border-l-4 border-blue-500 pl-3">
                <p class="text-xs font-semibold text-gray-600 uppercase">Issue Date</p>
                <p class="text-gray-900">{{ formatDate(detail.issue_date) }}</p>
              </div>
              <div class="border-l-4 border-blue-500 pl-3">
                <p class="text-xs font-semibold text-gray-600 uppercase">Expected Delivery</p>
                <p class="text-gray-900">{{ formatDate(detail.expected_delivery_date) || 'Not specified' }}</p>
              </div>
              <div class="border-l-4 border-blue-500 pl-3">
                <p class="text-xs font-semibold text-gray-600 uppercase">Assigned To</p>
                <p class="text-gray-900">{{ detail.assigned_to || 'Not assigned' }}</p>
              </div>
            </div>

            <!-- Payment & Shipping Terms -->
            <div class="space-y-3">
              <div class="border-l-4 border-green-500 pl-3">
                <p class="text-xs font-semibold text-gray-600 uppercase">Currency</p>
                <p class="text-gray-900 font-semibold">{{ detail.currency }}</p>
              </div>
              <div class="border-l-4 border-green-500 pl-3">
                <p class="text-xs font-semibold text-gray-600 uppercase">Payment Terms</p>
                <p class="text-gray-900">{{ capitalizeWords(detail.payment_terms) }}</p>
              </div>
              <div class="border-l-4 border-green-500 pl-3">
                <p class="text-xs font-semibold text-gray-600 uppercase">Shipping Terms</p>
                <p class="text-gray-900">{{ detail.shipping_terms || 'Not specified' }}</p>
              </div>
              <div class="border-l-4 border-green-500 pl-3">
                <p class="text-xs font-semibold text-gray-600 uppercase">Special Instructions</p>
                <p class="text-gray-900">{{ detail.instructions || 'None' }}</p>
              </div>
            </div>
          </div>

          <!-- Qualification Requirements -->
          <div v-if="detail.qualification_requirements" class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <p class="text-xs font-semibold text-gray-600 uppercase mb-2">Qualification Requirements</p>
            <p class="text-gray-900">{{ detail.qualification_requirements }}</p>
          </div>
        </template>
      </Card>

      <!-- Line Items -->
      <Card>
        <template #header>
          <div class="px-6 pt-6">
            <h3 class="text-lg font-semibold text-gray-800">📦 Line Items ({{ detail.items?.length || 0 }})</h3>
          </div>
        </template>
        <template #content>
          <div v-if="detail.items && detail.items.length > 0" class="space-y-3">
            <div v-for="(item, index) in detail.items" :key="index" class="p-4 border rounded-lg bg-gradient-to-r from-orange-50 to-amber-50">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Product Info -->
                <div>
                  <p class="text-xs font-semibold text-gray-600 uppercase mb-1">Product</p>
                  <p class="font-semibold text-gray-900">{{ item.product?.product_name || 'Unknown' }}</p>
                  <p class="text-xs text-gray-600 mt-1">SKU: {{ item.product?.sku || 'N/A' }}</p>
                  <p v-if="item.variation" class="text-xs text-gray-600 mt-1">
                    Variation: <span class="font-medium">{{ item.variation.variation_name }}</span>
                  </p>
                </div>

                <!-- Quantity & Specs -->
                <div>
                  <p class="text-xs font-semibold text-gray-600 uppercase mb-1">Quantity</p>
                  <p class="text-2xl font-bold text-orange-600">{{ item.quantity }}</p>
                  <p v-if="item.specifications" class="text-xs text-gray-600 mt-2">
                    Specs: {{ item.specifications }}
                  </p>
                </div>

                <!-- Requirements -->
                <div>
                  <p class="text-xs font-semibold text-gray-600 uppercase mb-1">Requirements</p>
                  <p class="text-gray-900">{{ item.requirements || 'Standard' }}</p>
                  <p v-if="item.product" class="text-xs text-gray-600 mt-2">
                    Base Price: {{ item.product.currency_symbol || 'PHP' }} {{ parseFloat(item.product.base_price).toFixed(2) }}
                  </p>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-6">
            <p class="text-gray-500">No line items</p>
          </div>
        </template>
      </Card>

      <!-- Suppliers -->
      <Card>
        <template #header>
          <div class="px-6 pt-6">
            <h3 class="text-lg font-semibold text-gray-800">🏢 Invited Suppliers ({{ detail.suppliers?.length || 0 }})</h3>
          </div>
        </template>
        <template #content>
          <div v-if="detail.suppliers && detail.suppliers.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="(invitedSupplier, index) in detail.suppliers" :key="index" class="p-4 border rounded-lg bg-gradient-to-br from-purple-50 to-indigo-50">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <p class="text-xs font-semibold text-gray-600 uppercase mb-1">Supplier</p>
                  <p class="font-semibold text-gray-900">{{ invitedSupplier.supplier?.supplier_name || 'Unknown Supplier' }}</p>
                  <p class="text-sm text-gray-600 mt-1">📧 {{ invitedSupplier.supplier?.email || 'No email' }}</p>
                  <p v-if="invitedSupplier.supplier?.contact_person" class="text-sm text-gray-600">
                    Contact: {{ invitedSupplier.supplier.contact_person }}
                  </p>
                </div>
                <Badge :value="capitalizeWords(invitedSupplier.status)" :severity="getStatusSeverity(invitedSupplier.status)" />
              </div>

              <!-- Status Timeline -->
              <div class="mt-3 space-y-1 text-xs text-gray-600 border-t pt-3">
                <p>📤 Invited: {{ formatDateTime(invitedSupplier.invited_at) }}</p>
                <p v-if="invitedSupplier.viewed_at">👁️ Viewed: {{ formatDateTime(invitedSupplier.viewed_at) }}</p>
                <p v-if="invitedSupplier.responded_at">✅ Responded: {{ formatDateTime(invitedSupplier.responded_at) }}</p>
                <p v-if="invitedSupplier.decline_reason" class="text-red-600">
                  ❌ Decline Reason: {{ invitedSupplier.decline_reason }}
                </p>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-6">
            <p class="text-gray-500">No suppliers invited</p>
          </div>
        </template>
      </Card>

      <!-- Supplier Portal Responses -->
      <Card v-if="portalFeedbackGroups.length > 0">
        <template #header>
          <div class="px-6 pt-6">
            <h3 class="text-lg font-semibold text-gray-800">📨 Supplier Responses (Portal)</h3>
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            <div v-for="group in portalFeedbackGroups" :key="group.supplier_id" class="p-4 border rounded-lg bg-gradient-to-r from-emerald-50 to-teal-50">
              <div class="flex items-start justify-between mb-3">
                <div>
                  <p class="font-semibold text-gray-900">{{ group.supplier_name }}</p>
                  <p class="text-sm text-gray-600">{{ group.supplier_email || 'No email' }}</p>
                </div>
                <div class="flex items-center gap-2">
                  <Badge :value="group.items.length + ' Items'" severity="info" />
                  <Button 
                    v-if="group.items.some((item: any) => item.status === 'pending')"
                    size="small"
                    label="Approve All"
                    severity="success"
                    @click="bulkApproveGroup(group.items.filter((item: any) => item.status === 'pending').map((item: any) => item.feedback_id))"
                  />
                </div>
              </div>
              <div class="space-y-2">
                <div v-for="item in group.items" :key="item.id" class="flex items-start justify-between bg-white rounded p-3 border">
                  <div>
                    <p class="font-medium text-gray-900">{{ item.product_name }}</p>
                    <p class="text-xs text-gray-500">SKU: {{ item.sku || 'N/A' }}</p>
                    <p class="text-xs text-gray-500">Qty: {{ item.quantity }} {{ item.unit || '' }}</p>
                    <div class="mt-2">
                      <Badge :value="item.statusLabel" :severity="item.statusSeverity" />
                      <p v-if="item.rejection_reason" class="text-xs text-red-600 mt-1">
                        Reason: {{ item.rejection_reason }}
                      </p>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="text-sm text-gray-500">Quoted Price</p>
                    <p class="text-lg font-bold text-emerald-600">{{ detail?.currency || 'PHP' }} {{ item.quoted_price }}</p>
                    <p v-if="item.submitted_at" class="text-xs text-gray-500 mt-1">Submitted: {{ formatDateTime(item.submitted_at) }}</p>
                    <div v-if="item.status === 'pending'" class="mt-2 flex gap-2 justify-end">
                      <Button 
                        size="small"
                        label="Approve"
                        severity="success"
                        @click="approveFeedback(item.feedback_id)"
                      />
                      <Button 
                        size="small"
                        label="Reject"
                        severity="danger"
                        @click="openRejectDialog(item.feedback_id)"
                      />
                      <Button 
                        size="small"
                        label="Negotiate"
                        severity="info"
                        @click="openNegoDialog(item.feedback_id)"
                      />
                    </div>
                    <div v-if="item.negotiations && item.negotiations.length > 0" class="mt-2 text-xs text-gray-600">
                      <p class="font-semibold">Negotiations</p>
                      <ul class="space-y-1">
                        <li v-for="nego in item.negotiations" :key="nego.id">
                          {{ detail?.currency || 'PHP' }} {{ parseFloat(nego.counter_price).toFixed(2) }} - {{ formatDateTime(nego.created_at) }}
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
              <div v-if="group.notes.length > 0" class="mt-3 text-xs text-gray-600">
                <p class="font-semibold mb-1">Notes</p>
                <ul class="space-y-1">
                  <li v-for="(note, index) in group.notes" :key="index">• {{ note }}</li>
                </ul>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Quotations -->
      <Card v-if="detail.quotations && detail.quotations.length > 0">
        <template #header>
          <div class="px-6 pt-6">
            <h3 class="text-lg font-semibold text-gray-800">💰 Supplier Quotations ({{ detail.quotations.length }})</h3>
          </div>
        </template>
        <template #content>
          <div class="space-y-3">
            <div v-for="(quotation, index) in detail.quotations" :key="index" class="p-4 border rounded-lg bg-gradient-to-r from-blue-50 to-cyan-50">
              <div class="flex justify-between items-start">
                <div>
                  <p class="font-semibold text-gray-900">{{ quotation.supplier?.supplier_name || 'Unknown' }}</p>
                  <p class="text-sm text-gray-600 mt-1">Quote Date: {{ formatDate(quotation.quote_date) }}</p>
                </div>
                <div class="text-right">
                  <p class="text-2xl font-bold text-blue-600">{{ detail.currency }} {{ parseFloat(quotation.total_price || 0).toFixed(2) }}</p>
                  <Badge :value="quotation.status" :severity="getStatusSeverity(quotation.status)" />
                </div>
              </div>
            </div>
          </div>
        </template>
      </Card>
      <!-- Action Buttons -->
      <div class="flex justify-end gap-2">
        <Button v-if="detail.status === 'draft'" label="Send" icon="pi pi-send" severity="info" :loading="processing" @click="send" />
        <Button v-if="detail.status === 'quotes_received'" label="Award" icon="pi pi-check" severity="success" :loading="processing" @click="award" />
      </div>
      <ConfirmDialog />
      <Dialog v-model:visible="rejectDialogVisible" modal header="Reject Supplier Response" :style="{ width: '32rem' }">
        <div class="space-y-3">
          <p class="text-sm text-gray-600">Provide a reason for rejection. This will be visible to the supplier.</p>
          <Textarea v-model="rejectReason" rows="4" class="w-full" placeholder="Enter rejection reason..." />
          <div class="flex justify-end gap-2">
            <Button label="Cancel" severity="secondary" @click="closeRejectDialog" />
            <Button label="Reject" severity="danger" :loading="processing" @click="submitReject" />
          </div>
        </div>
      </Dialog>
      <Dialog v-model:visible="negoDialogVisible" modal header="Send Counter Offer" :style="{ width: '32rem' }">
        <div class="space-y-3">
          <p class="text-sm text-gray-600">Enter your target price to negotiate with the supplier.</p>
          <InputNumber v-model="negoPrice" mode="currency" currency="PHP" class="w-full" />
          <Textarea v-model="negoNotes" rows="3" class="w-full" placeholder="Notes (optional)" />
          <div class="flex justify-end gap-2">
            <Button label="Cancel" severity="secondary" @click="closeNegoDialog" />
            <Button label="Send Offer" severity="info" :loading="processing" @click="submitNegotiation" />
          </div>
        </div>
      </Dialog>
    </template>

    <!-- Error State -->
    <Card v-else>
      <template #content>
        <div class="text-center py-8">
          <p class="text-lg text-gray-600">RFQ not found</p>
          <Button label="Back to RFQs" text @click="router.push({ name: 'procurement.rfqs' })" class="mt-4" />
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import procurementService from '../../../../services/procurement.service'

interface RFQDetail {
  id: number
  rfq_number: string
  title: string
  description?: string
  issue_date: string
  deadline_date: string
  expected_delivery_date?: string
  rfq_type: string
  currency: string
  payment_terms: string
  shipping_terms?: string
  instructions?: string
  qualification_requirements?: string
  status: string
  assigned_to?: number
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
const rfqId = Number(route.params.id)

const loading = ref(false)
const processing = ref(false)
const detail = ref<RFQDetail | null>(null)
const rejectDialogVisible = ref(false)
const rejectReason = ref('')
const rejectTargetFeedbackId = ref<number | null>(null)
const negoDialogVisible = ref(false)
const negoPrice = ref<number | null>(null)
const negoNotes = ref('')
const negoTargetFeedbackId = ref<number | null>(null)

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

const openNegoDialog = (feedbackId: number) => {
  negoTargetFeedbackId.value = feedbackId
  negoPrice.value = null
  negoNotes.value = ''
  negoDialogVisible.value = true
}

const closeNegoDialog = () => {
  negoDialogVisible.value = false
  negoPrice.value = null
  negoNotes.value = ''
  negoTargetFeedbackId.value = null
}

const submitNegotiation = async () => {
  if (!detail.value || !negoTargetFeedbackId.value || !negoPrice.value) {
    toast.add({
      severity: 'warn',
      summary: 'Required',
      detail: 'Please enter a counter price.',
      life: 3000,
    })
    return
  }

  processing.value = true
  try {
    await procurementService.negotiatePortalFeedback(detail.value.id, negoTargetFeedbackId.value, {
      counter_price: negoPrice.value,
      notes: negoNotes.value || undefined,
    })
    toast.add({
      severity: 'success',
      summary: 'Sent',
      detail: 'Counter offer sent to supplier.',
      life: 3000,
    })
    closeNegoDialog()
    await loadDetail()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to send counter offer',
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

const formatDate = (date: string | null): string => {
  if (!date) return 'Not specified'
  const d = new Date(date)
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}

const formatDateTime = (date: string | null): string => {
  if (!date) return 'N/A'
  const d = new Date(date)
  return d.toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const calculateDaysRemaining = (deadline: string | null): number | string => {
  if (!deadline) return 'N/A'
  const deadlineDate = new Date(deadline)
  const today = new Date()
  const days = Math.ceil((deadlineDate.getTime() - today.getTime()) / (1000 * 3600 * 24))
  return days > 0 ? days : 'Expired'
}

const capitalizeWords = (str: string): string => {
  return str
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
    .join(' ')
}

const statusSeverity = (status: string): string => {
  const severityMap: Record<string, string> = {
    draft: 'secondary',
    sent: 'info',
    quotes_received: 'warning',
    awarded: 'success',
    cancelled: 'danger',
  }
  return severityMap[status] || 'secondary'
}

const getStatusSeverity = (status: string): string => {
  const severityMap: Record<string, string> = {
    pending: 'info',
    viewed: 'warning',
    submitted: 'success',
    declined: 'danger',
    no_interest: 'secondary',
  }
  return severityMap[status] || 'secondary'
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

const close = async () => {
  processing.value = true
  try {
    const response = await procurementService.closeRFQ(rfqId)
    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'RFQ closed',
        life: 3000,
      })
      await loadDetail()
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to close RFQ',
      life: 3000,
    })
  } finally {
    processing.value = false
  }
}

const award = async () => {
  processing.value = true
  try {
    // TODO: Show dialog to select supplier and enter notes
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

onMounted(() => {
  loadDetail()
})
</script>
