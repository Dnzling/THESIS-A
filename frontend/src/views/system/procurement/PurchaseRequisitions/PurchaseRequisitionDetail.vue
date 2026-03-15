<template>
  <div class="bg-slate-50 min-h-screen p-6">
    <div class="max-w-7xl mx-auto space-y-6 pb-6">
      <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'procurement.purchase-requisitions' })" />
          <div>
            <h2 class="text-2xl font-semibold text-slate-800">Purchase Requisition Details</h2>
            <p class="text-sm text-slate-500 mt-1">{{ detail?.pr_number || 'Loading...' }}</p>
          </div>
        </div>
        <Tag :value="detail?.status?.toUpperCase() || 'DRAFT'" :severity="statusSeverity(detail?.status || 'draft')" class="text-xs" />
      </div>

      <Card v-if="loading" class="border border-slate-200 shadow-sm">
        <template #content>
          <div class="space-y-4">
            <Skeleton height="100px" class="rounded-lg" />
            <Skeleton height="200px" class="rounded-lg" />
            <Skeleton height="200px" class="rounded-lg" />
          </div>
        </template>
      </Card>

      <template v-else-if="detail">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Card class="border border-slate-200 shadow-sm">
            <template #content>
              <div class="space-y-2">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">PR Number</p>
                <p class="text-xl font-semibold text-slate-900">{{ detail?.pr_number || 'N/A' }}</p>
                <p class="text-xs text-slate-500 mt-2">Created: {{ formatDateTime(detail?.created_at) }}</p>
              </div>
            </template>
          </Card>
          <Card class="border border-slate-200 shadow-sm">
            <template #content>
              <div class="space-y-2">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Type</p>
                <p class="text-lg font-semibold text-slate-900">{{ capitalizeWords(detail?.requisition_type) }}</p>
                <p class="text-xs text-slate-500 mt-2">Priority: {{ detail?.priority || 'N/A' }}</p>
              </div>
            </template>
          </Card>
          <Card class="border border-slate-200 shadow-sm">
            <template #content>
              <div class="space-y-2">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Required Date</p>
                <p class="text-lg font-semibold text-slate-900">{{ formatDate(detail?.required_date) }}</p>
                <p class="text-xs text-slate-500 mt-2">{{ calculateDaysRemaining(detail?.required_date) }}</p>
              </div>
            </template>
          </Card>
          <Card class="border border-slate-200 shadow-sm">
            <template #content>
              <div class="space-y-2">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Est. Amount</p>
                <p class="text-2xl font-semibold text-slate-900">{{ parseFloat(detail?.estimated_amount || 0).toFixed(2) }}</p>
                <p class="text-xs text-slate-500 mt-2">{{ capitalizeWords(detail?.procurement_route || 'N/A') }}</p>
              </div>
            </template>
          </Card>
        </div>

        <Card class="border border-slate-200 shadow-sm">
          <template #header>
            <div class="px-6 pt-6">
              <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Status Report</h3>
            </div>
          </template>
          <template #content>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="p-4 rounded-lg border border-slate-200 bg-white">
                <div class="text-xs text-slate-500 uppercase tracking-wide">RFQ</div>
                <div class="mt-2">
                  <Tag :value="getRfqStatus(detail)" :severity="getRfqSeverity(detail)" />
                </div>
              </div>
              <div class="p-4 rounded-lg border border-slate-200 bg-white">
                <div class="text-xs text-slate-500 uppercase tracking-wide">Purchase Order</div>
                <div class="mt-2">
                  <Tag :value="getPoStatus(detail)" :severity="getPoSeverity(detail)" />
                </div>
              </div>
              <div class="p-4 rounded-lg border border-slate-200 bg-white">
                <div class="text-xs text-slate-500 uppercase tracking-wide">Suppliers</div>
                <div class="mt-2 text-slate-900 font-semibold">{{ (detail?.suppliers?.length || 0) }} linked</div>
              </div>
            </div>
          </template>
        </Card>

        <div class="flex justify-end gap-2">
          <Button v-if="detail.status === 'draft'" label="Submit" icon="pi pi-send" severity="info" :loading="processing" @click="submit" />
          <Button v-if="canApprove" label="Reject" icon="pi pi-times" severity="danger" :loading="processing" @click="reject" />
          <Button v-if="canApprove" label="Approve" icon="pi pi-check" severity="success" :loading="processing" @click="approve" />
        </div>

        <Card class="border border-slate-200 shadow-sm">
          <template #header>
            <div class="px-6 pt-6">
              <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">PR Details & Reason</h3>
            </div>
          </template>
          <template #content>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Requested By</p>
                <p class="font-semibold text-slate-900">{{ detail?.requested_by?.fname }} {{ detail?.requested_by?.lname }}</p>
                <p class="text-sm text-slate-600">{{ detail?.requested_by?.employee_number }}</p>
              </div>
              <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Branch</p>
                <p class="font-semibold text-slate-900">{{ detail?.branch?.name || 'N/A' }}</p>
                <p class="text-sm text-slate-600">{{ detail?.branch?.branch_code }}</p>
              </div>
            </div>
            <div class="mt-4 p-4 bg-slate-50 rounded border border-slate-200">
              <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Reason</p>
              <p class="text-slate-900">{{ detail?.reason || 'No reason provided' }}</p>
            </div>
          </template>
        </Card>

        <Card class="border border-slate-200 shadow-sm">
          <template #header>
            <div class="px-6 pt-6">
              <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Line Items ({{ detail.items?.length || 0 }})</h3>
            </div>
          </template>
          <template #content>
            <div v-if="detail?.items && detail.items.length > 0" class="space-y-3">
              <div v-for="(item, index) in detail.items" :key="index" class="p-4 border rounded-lg bg-white">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                  <div>
                    <p class="text-xs text-slate-500 font-semibold uppercase tracking-wide">Product</p>
                    <p class="font-semibold text-slate-900">{{ item?.product?.product_name || 'Unknown' }}</p>
                    <p class="text-xs text-slate-500 mt-1">SKU: {{ item?.product?.sku || 'N/A' }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-slate-500 font-semibold uppercase tracking-wide">Quantity</p>
                    <p class="text-2xl font-semibold text-slate-900">{{ item?.quantity_requested || 0 }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-slate-500 font-semibold uppercase tracking-wide">Est. Unit Cost</p>
                    <p class="font-semibold text-slate-900">{{ parseFloat(item?.estimated_unit_cost || 0).toFixed(2) }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-slate-500 font-semibold uppercase tracking-wide">Total</p>
                    <p class="text-lg font-semibold text-slate-900">{{ ((item?.quantity_requested || 0) * parseFloat(item?.estimated_unit_cost || 0)).toFixed(2) }}</p>
                  </div>
                  <div v-if="item?.specifications">
                    <p class="text-xs text-slate-500 font-semibold uppercase tracking-wide">Specs</p>
                    <p class="text-xs text-slate-900">{{ item.specifications }}</p>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-6">
              <p class="text-slate-500">No line items</p>
            </div>
          </template>
        </Card>

        <Card class="border border-slate-200 shadow-sm">
          <template #header>
            <div class="px-6 pt-6">
              <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Approval Chain ({{ (detail.required_approvals || []).length }} levels)</h3>
            </div>
          </template>
          <template #content>
            <div class="space-y-2">
              <div v-for="approval in (detail?.required_approvals || [])" :key="approval" class="flex items-center gap-3 p-3 bg-white rounded border border-slate-200">
                <i class="pi pi-check-circle text-slate-400"></i>
                <span class="font-medium text-slate-900">{{ capitalizeWords(approval) }}</span>
              </div>
              <div v-if="!detail?.required_approvals?.length" class="text-center py-4">
                <p class="text-slate-500">No approvals required</p>
              </div>
            </div>
          </template>
        </Card>

        <Card v-if="detail?.purchase_orders && detail.purchase_orders.length > 0" class="border border-slate-200 shadow-sm">
          <template #header>
            <div class="px-6 pt-6">
              <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Related Purchase Orders</h3>
            </div>
          </template>
          <template #content>
            <div class="space-y-2">
              <div v-for="po in detail.purchase_orders" :key="po.id" class="p-3 bg-white rounded border border-slate-200">
                <p class="font-medium text-slate-900">{{ po?.po_number }}</p>
                <p class="text-sm text-slate-600">Status: {{ po?.status }}</p>
              </div>
            </div>
          </template>
        </Card>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const requisitionId = Number(route.params.id)

const loading = ref(false)
const processing = ref(false)
const detail = ref<any>(null)
const userRole = ref<string>('') // TODO: Get from auth

const canApprove = computed(() => {
  if (!detail.value) return false
  return ['submitted', 'warehouse_approved', 'branch_manager_approved'].includes(detail.value.status) && userRole.value
})

const capitalizeWords = (str: string): string => {
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
  return d.toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const calculateDaysRemaining = (deadline: string | null): string => {
  if (!deadline) return 'N/A'
  const deadlineDate = new Date(deadline)
  const today = new Date()
  const days = Math.ceil((deadlineDate.getTime() - today.getTime()) / (1000 * 3600 * 24))
  if (days < 0) return `${Math.abs(days)}d ago`
  if (days === 0) return 'Today'
  if (days === 1) return 'Tomorrow'
  return `${days}d remaining`
}

const statusSeverity = (status: string): string => {
  const severityMap: Record<string, string> = {
    draft: 'secondary',
    submitted: 'info',
    warehouse_approved: 'warning',
    branch_manager_approved: 'warning',
    approved: 'success',
    rejected: 'danger',
  }
  return severityMap[status] || 'secondary'
}

const getRfqCount = (req: any) => {
  if (Array.isArray(req?.rfqs)) return req.rfqs.length
  if (req?.rfq) return 1
  return 0
}

const getPoCount = (req: any) => {
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
    const response = await procurementService.approvePurchaseRequisition(requisitionId, { role: userRole.value, notes: '' })
    if (response.success) {
      toast.add({ severity: 'success', summary: 'Success', detail: 'PR approved', life: 3000 })
      await loadDetail()
    }
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to approve', life: 3000 })
  } finally {
    processing.value = false
  }
}

const reject = async () => {
  processing.value = true
  try {
    const response = await procurementService.rejectPurchaseRequisition(requisitionId, { role: userRole.value, reason: '' })
    if (response.success) {
      toast.add({ severity: 'success', summary: 'Success', detail: 'PR rejected', life: 3000 })
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

