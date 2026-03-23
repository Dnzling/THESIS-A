<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <!-- Toast notifications -->
    <Toast />
    <ConfirmDialog />

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'procurement.purchase-orders' })" />
        <div>
          <h1 class="text-2xl font-semibold text-gray-900">Purchase Order Details</h1>
          <p class="text-sm text-gray-500 mt-1">View complete purchase order information</p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <Button 
          v-if="detail?.status === 'draft'"
          label="Edit Purchase Order" 
          icon="pi pi-pencil" 
          severity="info" 
          @click="editPO"
        />
        <Button
          v-if="detail?.status === 'approved'"
          label="Send to Supplier"
          icon="pi pi-send"
          severity="success"
          @click="confirmSend"
        />
        <Button
          v-if="detail?.status === 'sent_to_supplier'"
          label="Resend to Supplier"
          icon="pi pi-replay"
          severity="secondary"
          @click="confirmResend"
        />
        <Tag :value="formatStatus(detail?.status)" :severity="statusSeverity(detail?.status)" />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-4">
      <Skeleton height="120px" class="rounded-lg" />
      <Skeleton height="200px" class="rounded-lg" />
      <Skeleton height="300px" class="rounded-lg" />
    </div>

    <!-- Main Content -->
    <div v-else-if="detail" class="space-y-6">
      <div
        v-if="detail?.status === 'pending_finance_approval'"
        class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700"
      >
        <i class="pi pi-clock mr-2"></i>
        Awaiting Finance Approval — this PO cannot be sent to supplier yet.
      </div>
      <!-- PO Header Information -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-lg border border-gray-200">
          <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">PO Number</p>
          <p class="text-lg font-semibold text-gray-900 mt-1">{{ detail?.po_number || '-' }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg border border-gray-200">
          <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Order Date</p>
          <p class="text-lg font-semibold text-gray-900 mt-1">{{ formatDate(detail?.order_date) }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg border border-gray-200">
          <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Expected Delivery</p>
          <p class="text-lg font-semibold text-gray-900 mt-1">{{ formatDate(detail?.expected_delivery_date) }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg border border-gray-200">
          <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Payment Terms</p>
          <p class="text-lg font-semibold text-gray-900 mt-1">{{ formatPaymentTerms(detail?.payment_terms) }}</p>
        </div>
      </div>

      <!-- Supplier and Branch Info -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white p-5 rounded-lg border border-gray-200">
          <div class="flex items-center gap-2 mb-3">
            <i class="pi pi-building text-gray-400"></i>
            <h3 class="font-medium text-gray-700">Supplier Information</h3>
          </div>
          <div class="space-y-2">
            <p class="font-medium text-gray-900">{{ detail?.supplier?.supplier_name || '-' }}</p>
            <p class="text-sm text-gray-600">{{ detail?.supplier?.email || '-' }}</p>
            <p class="text-sm text-gray-600">{{ detail?.supplier?.phone || '-' }}</p>
            <p class="text-sm text-gray-600">{{ detail?.supplier?.address || '-' }}</p>
          </div>
        </div>

        <div class="bg-white p-5 rounded-lg border border-gray-200">
          <div class="flex items-center gap-2 mb-3">
            <i class="pi pi-map-marker text-gray-400"></i>
            <h3 class="font-medium text-gray-700">Delivery Information</h3>
          </div>
          <div class="space-y-2">
            <p class="font-medium text-gray-900">{{ detail?.branch?.name || '-' }}</p>
            <p class="text-sm text-gray-600">{{ detail?.branch?.address || 'No address provided' }}</p>
            <p class="text-sm text-gray-600">{{ detail?.branch?.contact_number || '-' }}</p>
          </div>
        </div>
      </div>

      <!-- Created By Info -->
      <div class="bg-gray-50 px-4 py-3 rounded-lg border border-gray-200 text-sm text-gray-600">
        <span class="font-medium text-gray-700">Created by:</span> 
        {{ detail?.created_by?.fname }} {{ detail?.created_by?.lname }} 
        <span class="text-gray-400 mx-2">•</span>
        <span>{{ formatDate(detail?.created_at) }}</span>
      </div>

      <!-- PO Items Table -->
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center gap-2">
            <i class="pi pi-shopping-cart text-gray-500"></i>
            <h3 class="font-medium text-gray-700">Order Items ({{ detail?.items?.length || 0 }})</h3>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
              <tr>
                <th class="px-6 py-3 text-left">#</th>
                <th class="px-6 py-3 text-left">Product</th>
                <th class="px-6 py-3 text-center">Quantity</th>
                <th class="px-6 py-3 text-right">Unit Price</th>
                <th class="px-6 py-3 text-center">Tax</th>
                <th class="px-6 py-3 text-right">Line Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="(item, index) in detail?.items" :key="item.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 text-gray-500">{{ index + 1 }}</td>
                <td class="px-6 py-4">
                  <div>
                    <p class="font-medium text-gray-900">{{ item.product?.product_name || '-' }}</p>
                    <p class="text-xs text-gray-500 mt-1">SKU: {{ item.product?.sku || '-' }}</p>
                  </div>
                </td>
                <td class="px-6 py-4 text-center font-medium">{{ item?.quantity_ordered || 0 }}</td>
                <td class="px-6 py-4 text-right font-mono">{{ formatCurrency(parseFloat(item?.unit_cost || 0)) }}</td>
                <td class="px-6 py-4 text-center text-gray-600">{{ parseFloat(item?.tax_rate || 0).toFixed(0) }}%</td>
                <td class="px-6 py-4 text-right font-mono font-medium text-blue-600">{{ formatCurrency(parseFloat(item?.line_total || 0)) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Financial Summary -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-lg border border-gray-200">
          <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Subtotal</p>
          <p class="text-xl font-semibold text-gray-900 mt-1">{{ formatCurrency(parseFloat(detail?.subtotal || 0)) }}</p>
        </div>

        <div class="bg-white p-4 rounded-lg border border-gray-200">
          <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Tax (VAT)</p>
          <p class="text-xl font-semibold text-gray-900 mt-1">{{ formatCurrency(parseFloat(detail?.tax_amount || 0)) }}</p>
        </div>

        <div class="bg-white p-4 rounded-lg border border-gray-200">
          <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Shipping & Discounts</p>
          <p class="text-xl font-semibold text-gray-900 mt-1">{{ formatCurrency(calculateNetCharges()) }}</p>
          <div v-if="parseFloat(detail?.shipping_cost || 0) > 0 || parseFloat(detail?.discount_amount || 0) > 0" class="text-xs text-gray-500 mt-1">
            <span v-if="parseFloat(detail?.shipping_cost || 0) > 0">Shipping: +{{ formatCurrency(parseFloat(detail?.shipping_cost || 0)) }}</span>
            <span v-if="parseFloat(detail?.discount_amount || 0) > 0" class="ml-2">Discount: -{{ formatCurrency(parseFloat(detail?.discount_amount || 0)) }}</span>
          </div>
        </div>

        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
          <p class="text-xs text-blue-600 font-medium uppercase tracking-wider">Total Amount</p>
          <p class="text-2xl font-bold text-blue-700 mt-1">{{ formatCurrency(parseFloat(detail?.total_amount || 0)) }}</p>
        </div>
      </div>

      <!-- Notes -->
      <div v-if="detail?.notes" class="bg-white p-5 rounded-lg border border-gray-200">
        <div class="flex items-center gap-2 mb-3">
          <i class="pi pi-file-text text-gray-400"></i>
          <h3 class="font-medium text-gray-700">Notes</h3>
        </div>
        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ detail.notes }}</p>
      </div>

      <!-- Terms and Conditions -->
      <div v-if="detail?.terms_conditions" class="bg-white p-5 rounded-lg border border-gray-200">
        <div class="flex items-center gap-2 mb-3">
          <i class="pi pi-file text-gray-400"></i>
          <h3 class="font-medium text-gray-700">Terms & Conditions</h3>
        </div>
        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ detail.terms_conditions }}</p>
      </div>

      <!-- View Only Status Message -->
      <div v-if="detail?.status !== 'draft'" class="bg-gray-50 px-4 py-3 rounded-lg border border-gray-200 text-sm text-gray-600 text-center">
        <i class="pi pi-info-circle text-gray-400 mr-2"></i>
        This purchase order is in <strong>{{ formatStatus(detail?.status) }}</strong> status and is view-only.
      </div>

      <!-- Activity Timeline -->
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center gap-2">
            <i class="pi pi-history text-gray-500"></i>
            <h3 class="font-medium text-gray-700">Activity Timeline</h3>
          </div>
        </div>
        <div class="p-6">
          <Timeline :value="timelineItems" align="left" class="w-full">
            <template #content="{ item }">
              <div class="pb-6">
                <p class="font-medium text-gray-900">{{ item.title }}</p>
                <p class="text-sm text-gray-500">{{ item.subtitle }}</p>
              </div>
            </template>
          </Timeline>
        </div>
      </div>

      <!-- Goods Receipt Section -->
      <div v-if="detail?.goods_receipts?.length > 0" class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center gap-2">
            <i class="pi pi-inbox text-gray-500"></i>
            <h3 class="font-medium text-gray-700">Goods Receipts</h3>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
              <tr>
                <th class="px-6 py-3 text-left">GR Number</th>
                <th class="px-6 py-3 text-left">Received Date</th>
                <th class="px-6 py-3 text-left">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="receipt in detail.goods_receipts" :key="receipt.id" class="hover:bg-gray-50">
                <td class="px-6 py-4">
                  <RouterLink :to="`/procurement/goods-receipts/${receipt.id}`" class="text-blue-600 hover:text-blue-800 font-medium">
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

    <!-- Email Preview Modal -->
    <Dialog v-model:visible="showEmailDialog" modal header="Supplier Email Preview" :style="{ width: '40rem' }">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-semibold mb-2">To</label>
          <InputText v-model="emailForm.recipient_email" class="w-full" />
        </div>
        <div>
          <label class="block text-sm font-semibold mb-2">Subject</label>
          <InputText v-model="emailForm.subject" class="w-full" />
        </div>
        <div>
          <label class="block text-sm font-semibold mb-2">Message</label>
          <Textarea v-model="emailForm.message" rows="6" class="w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" text @click="showEmailDialog = false" />
        <Button
          :label="emailForm.mode === 'send' ? 'Send' : 'Resend'"
          :loading="emailSending"
          @click="submitEmail"
        />
      </template>
    </Dialog>

    <!-- Not Found State -->
    <div v-else class="text-center py-12 bg-white rounded-lg border border-gray-200">
      <i class="pi pi-exclamation-circle text-4xl text-gray-300 mb-4"></i>
      <h3 class="text-lg font-medium text-gray-700">Purchase Order Not Found</h3>
      <p class="text-gray-500 mt-2 mb-4">The purchase order you're looking for doesn't exist or you don't have permission to view it.</p>
      <Button label="Back to List" icon="pi pi-arrow-left" @click="router.push({ name: 'procurement.purchase-orders' })" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useAuthStore } from '../../../../stores/auth'
import procurementService from '../../../../services/procurement.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const authStore = useAuthStore()
const poId = Number(route.params.id)

// State
const loading = ref(false)
const detail = ref<any>(null)
const showEmailDialog = ref(false)
const emailSending = ref(false)
const emailForm = ref({
  recipient_email: '',
  subject: '',
  message: '',
  mode: 'send' as 'send' | 'resend',
})

// Methods
const loadDetail = async () => {
  loading.value = true
  try {
    const response = await procurementService.getPurchaseOrder(poId)
    // Handle nested data structure
    if (response.data?.success && response.data?.data) {
      detail.value = response.data.data
    } else if (response.data?.data) {
      detail.value = response.data.data
    } else {
      detail.value = response.data
    }
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

const editPO = () => {
  router.push({
    name: 'procurement.purchase-orders.edit',
    params: { id: poId }
  })
}

const openEmailDialog = (mode: 'send' | 'resend') => {
  if (!detail.value?.supplier?.email) {
    toast.add({
      severity: 'warn',
      summary: 'Missing Email',
      detail: 'Supplier email is not available.',
      life: 3000
    })
    return
  }

  emailForm.value = {
    recipient_email: detail.value.supplier.email,
    subject: `Purchase Order ${detail.value.po_number}`,
    message: `Hello ${detail.value.supplier.supplier_name},\n\nPlease find the attached Purchase Order ${detail.value.po_number}.\n\nThank you.`,
    mode,
  }
  showEmailDialog.value = true
}

const confirmSend = () => openEmailDialog('send')
const confirmResend = () => openEmailDialog('resend')

const submitEmail = async () => {
  if (!detail.value) return
  emailSending.value = true
  try {
    await procurementService.emailPurchaseOrder(poId, {
      recipient_email: emailForm.value.recipient_email,
      subject: emailForm.value.subject,
      message: emailForm.value.message,
    })

    if (emailForm.value.mode === 'send' && detail.value.status === 'approved') {
      await procurementService.sendPurchaseOrder(poId)
    }

    toast.add({
      severity: 'success',
      summary: 'Sent',
      detail: emailForm.value.mode === 'send' ? 'PO sent to supplier.' : 'PO resent to supplier.',
      life: 2500
    })
    showEmailDialog.value = false
    await loadDetail()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to send email.',
      life: 3000
    })
  } finally {
    emailSending.value = false
  }
}

const timelineItems = computed(() => {
  if (!detail.value) return []
  const logs = detail.value.activity_logs || []

  if (!logs.length) {
    return [
      {
        title: 'PO Created',
        subtitle: detail.value.created_at ? formatDate(detail.value.created_at) : 'Date not available',
      },
    ]
  }

    return logs.map((log: any) => {
      const actor = log.user ? `${log.user.fname ?? ''} ${log.user.lname ?? ''}`.trim() : 'System'
      const action = log.action || ''
      const titleMap: Record<string, string> = {
        po_created: 'PO Created',
        po_approved: 'Finance Approved',
        po_sent_to_supplier: 'Sent to Supplier',
        po_email_sent: 'Email Sent to Supplier',
        po_supplier_accepted: 'Supplier Accepted',
        po_supplier_declined: 'Supplier Declined',
        po_shipment_created: 'Delivery Form Created',
        po_invoice_created: 'Invoice Created',
        po_rejected: 'Rejected by Finance',
        po_cancelled: 'Cancelled',
      }
    return {
      title: titleMap[action] || log.description || 'Activity',
      subtitle: `${formatDate(log.created_at)} • ${actor}`,
    }
  })
})

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

const calculateNetCharges = () => {
  const shipping = parseFloat(detail.value?.shipping_cost || 0)
  const discount = parseFloat(detail.value?.discount_amount || 0)
  return shipping - discount
}

const approvePO = async () => {
  // Show a simple dialog for approval notes
  const notes = prompt('Add approval notes (optional):')
  if (notes === null) return

  try {
    await procurementService.approvePurchaseOrder(poId, {
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

const rejectPO = async () => {
  const reason = prompt('Please provide a reason for rejection:')
  if (!reason) {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Rejection reason is required',
      life: 2000
    })
    return
  }

  try {
    await procurementService.rejectPurchaseOrder(poId, reason)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Purchase order rejected',
      life: 2000
    })
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
/* Remove any emoji-related styles */
</style>
