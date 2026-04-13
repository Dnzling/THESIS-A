<template>
  <div class="min-h-screen p-4">
    <div class="max-w-5xl mx-auto space-y-4">
      <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <Button icon="pi pi-arrow-left" severity="secondary" text @click="goBack" />
          <div>
            <h1 class="text-xl font-bold text-gray-800">Purchase Requisition</h1>
            <p class="text-xs text-gray-500 mt-0.5">{{ headerSubtitle }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Button
            v-if="canApprove && canShowApprovalActions"
            label="Reject"
            icon="pi pi-times"
            severity="danger"
            outlined
            size="small"
            :loading="rejecting"
            @click="openReject"
          />
          <Button
            v-if="canApprove && canShowApprovalActions"
            label="Approve"
            icon="pi pi-check"
            severity="success"
            size="small"
            :loading="approving"
            @click="confirmApprove"
          />
          <Tag :value="formatStatus(detail?.status)" :severity="statusSeverity(detail?.status)" />
        </div>
      </div>
  
      <div v-if="loading">
        <Card>
          <template #content>
            <div class="space-y-3">
              <Skeleton height="16px" width="220px" />
              <Skeleton height="48px" />
              <Skeleton height="180px" />
            </div>
          </template>
        </Card>
      </div>
  
      <template v-else-if="detail">
        <Card>
          <template #content>
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="text-xs text-gray-500">PR No.</div>
                <div class="text-lg font-bold text-gray-900">{{ detail.pr_number || `PR #${detail.id}` }}</div>
                <div class="text-xs text-gray-500 mt-0.5">Created {{ formatDateTime(detail.created_at) }}</div>
              </div>
            </div>
  
            <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
              <div class="rounded-lg border border-gray-100 p-3">
                <div class="text-gray-500">Branch</div>
                <div class="font-semibold text-gray-900">{{ detail.branch?.name || branchLabel }}</div>
              </div>
              <div class="rounded-lg border border-gray-100 p-3">
                <div class="text-gray-500">Requester</div>
                <div class="font-semibold text-gray-900">{{ requesterName }}</div>
              </div>
              <div class="rounded-lg border border-gray-100 p-3">
                <div class="text-gray-500">Type</div>
                <div class="font-semibold text-gray-900">{{ String(detail.requisition_type || 'regular') }}</div>
              </div>
              <div class="rounded-lg border border-gray-100 p-3">
                <div class="text-gray-500">Estimated Amount</div>
                <div class="font-semibold text-gray-900">{{ formatMoney(detail.estimated_amount) }}</div>
              </div>
            </div>
  
            <div class="mt-4">
              <div class="text-xs font-semibold text-gray-700 mb-1.5">Reason</div>
              <div class="rounded-lg border border-gray-100 p-3 text-sm text-gray-800 bg-white whitespace-pre-line">
                {{ detail.reason || '—' }}
              </div>
            </div>
          </template>
        </Card>
  
        <Card>
          <template #content>
            <div class="text-sm font-semibold text-gray-800 mb-3">Items</div>
            <DataTable :value="detail.items || []" class="p-datatable-sm text-xs" responsiveLayout="scroll">
              <Column header="Product" style="min-width: 260px">
                <template #body="{ data }">
                  <div class="text-sm">
                    <div class="font-semibold text-gray-900">{{ data.product?.product_name || 'N/A' }}</div>
                    <div class="text-gray-500">
                      SKU: {{ data.variation?.variation_sku || data.product?.sku || '-' }}
                    </div>
                  </div>
                </template>
              </Column>
              <Column header="Qty" style="width: 120px">
                <template #body="{ data }">
                  <span class="font-semibold text-gray-900">{{ data.quantity_requested ?? 0 }}</span>
                </template>
              </Column>
              <Column header="Unit Cost" style="width: 140px">
                <template #body="{ data }">
                  {{ formatMoney(data.estimated_unit_cost) }}
                </template>
              </Column>
              <Column header="Line Total" style="width: 160px">
                <template #body="{ data }">
                  {{ formatMoney((Number(data.quantity_requested || 0) * Number(data.estimated_unit_cost || 0))) }}
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>

        <div v-if="canGenerateReceipt" class="flex justify-end">
          <Button
            label="Generate Receipt"
            icon="pi pi-receipt"
            severity="success"
            size="small"
            @click="generateReceipt"
          />
        </div>
      </template>
    </div>
  </div>
  
  
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useAuthStore } from '@/stores/auth'
import inventoryService from '@/services/inventory.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const authStore = useAuthStore()

const loading = ref(false)
const detail = ref<any>(null)
const submitting = ref(false)
const approving = ref(false)
const rejecting = ref(false)
const cancelling = ref(false)

const rejectDialogVisible = ref(false)
const rejectReason = ref('')
const rejectError = ref('')

const cancelDialogVisible = ref(false)
const cancelReason = ref('')
const cancelError = ref('')

const canManage = computed(() => authStore.hasPermission('inventory.requisites.manage'))
const canApprove = computed(() =>
  authStore.hasPermission('inventory.requisitions.approve') ||
  authStore.hasPermission('inventory.requisites.approve')
)
const canShowApprovalActions = computed(() => {
  const s = String(detail.value?.status || '').toLowerCase()
  return ['draft', 'pending', 'warehouse_approved', 'branch_manager_approved'].includes(s)
})
const canGenerateReceipt = computed(() => {
  const s = String(detail.value?.status || '').toLowerCase()
  if (s !== 'delivered') return false
  const purchaseOrders = Array.isArray(detail.value?.purchase_orders)
    ? detail.value.purchase_orders
    : (Array.isArray(detail.value?.purchaseOrders) ? detail.value.purchaseOrders : [])
  return purchaseOrders.length > 0
})

const id = computed(() => String(route.params.id || ''))

const branchLabel = computed(() => {
  const user = authStore.user as any
  const name = user?.branch?.name || user?.branch_name || user?.employee?.branch?.name
  if (name) return name
  const bid = Number(user?.branch?.id || user?.employee?.branch_id || user?.branch_id || 0)
  return bid ? `Branch #${bid}` : 'Unassigned Branch'
})

const requesterName = computed(() => {
  const emp = detail.value?.requested_by
  if (emp?.fname || emp?.lname) return `${emp?.fname || ''} ${emp?.lname || ''}`.trim()
  return '—'
})

const headerSubtitle = computed(() => {
  const pr = detail.value?.pr_number || (detail.value?.id ? `PR #${detail.value.id}` : '')
  return `${branchLabel.value}${pr ? ` • ${pr}` : ''}`
})

const formatDateTime = (value: any) => {
  if (!value) return '-'
  const d = new Date(value)
  return d.toLocaleString(undefined, { year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' })
}

const formatMoney = (value: any) => {
  const n = Number(value || 0)
  return n.toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })
}

const statusSeverity = (status: string) => {
  const s = String(status || '').toLowerCase()
  if (['procurement_processing', 'approved', 'completed'].includes(s)) return 'success'
  if (['rejected', 'cancelled'].includes(s)) return 'danger'
  if (s === 'draft') return 'secondary'
  return 'warning'
}

const formatStatus = (status: any) => {
  const s = String(status || '').replace(/_/g, ' ').trim()
  return s ? s.charAt(0).toUpperCase() + s.slice(1) : 'Unknown'
}

const goBack = () => router.push({ name: 'inventory.requisites.index' })
const generateReceipt = () => {
  const purchaseOrders = Array.isArray(detail.value?.purchase_orders)
    ? detail.value.purchase_orders
    : (Array.isArray(detail.value?.purchaseOrders) ? detail.value.purchaseOrders : [])
  const po = purchaseOrders[0]
  if (!po?.id) {
    toast.add({ severity: 'warn', summary: 'Missing PO', detail: 'No linked purchase order found for this PR.', life: 2500 })
    return
  }

  router.push({
    name: 'inventory.goods-receipts.create',
    query: { po_id: String(po.id), pr_id: id.value }
  })
}

const load = async () => {
  if (!id.value) return
  loading.value = true
  try {
    const response = await inventoryService.getPurchaseRequisition(id.value)
    if (response?.success) detail.value = response.data
    else detail.value = null
  } catch (e: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed to load PR', life: 3000 })
  } finally {
    loading.value = false
  }
}

const confirmSubmit = () => {
  if (!canManage.value) return
  confirm.require({
    header: 'Submit PR',
    message: 'Submit this purchase requisition for approval?',
    icon: 'pi pi-send',
    accept: () => submit(),
  })
}

const confirmApprove = () => {
  if (!canApprove.value) return
  confirm.require({
    header: 'Approve PR',
    message: 'Approve this purchase requisition?',
    icon: 'pi pi-check',
    accept: () => approve(),
  })
}

const approve = async () => {
  if (!canApprove.value) return
  approving.value = true
  try {
    const res = await inventoryService.approvePurchaseRequisition(id.value, { notes: '' })
    if (res?.success) {
      toast.add({ severity: 'success', summary: 'Approved', detail: 'PR approved.', life: 2000 })
      await load()
    } else {
      toast.add({ severity: 'error', summary: 'Error', detail: res?.message || 'Failed to approve PR', life: 3000 })
    }
  } finally {
    approving.value = false
  }
}

const submit = async () => {
  if (!canManage.value) return
  submitting.value = true
  try {
    const res = await inventoryService.submitPurchaseRequisition(id.value)
    if (res?.success) {
      toast.add({ severity: 'success', summary: 'Submitted', detail: 'PR submitted.', life: 2000 })
      await load()
    } else {
      toast.add({ severity: 'error', summary: 'Error', detail: res?.message || 'Failed to submit PR', life: 3000 })
    }
  } finally {
    submitting.value = false
  }
}

const openReject = () => {
  if (!canApprove.value) return
  const reason = window.prompt('Enter rejection reason:') || ''
  rejectReason.value = reason
  submitReject()
}

const submitReject = async () => {
  if (!canApprove.value) return
  rejectError.value = ''
  const reason = String(rejectReason.value || '').trim()
  if (!reason) {
    rejectError.value = 'Reason is required.'
    toast.add({ severity: 'warn', summary: 'Reason required', detail: 'Please provide a rejection reason.', life: 2500 })
    return
  }

  rejecting.value = true
  try {
    const res = await inventoryService.rejectPurchaseRequisition(id.value, { reason })
    if (res?.success) {
      toast.add({ severity: 'success', summary: 'Rejected', detail: 'PR rejected.', life: 2000 })
      rejectDialogVisible.value = false
      await load()
    } else {
      toast.add({ severity: 'error', summary: 'Error', detail: res?.message || 'Failed to reject PR', life: 3000 })
    }
  } finally {
    rejecting.value = false
  }
}

const openCancel = () => {
  cancelReason.value = ''
  cancelError.value = ''
  cancelDialogVisible.value = true
}

const submitCancel = async () => {
  if (!canManage.value) return
  cancelError.value = ''
  const reason = String(cancelReason.value || '').trim()
  if (!reason) {
    cancelError.value = 'Reason is required.'
    return
  }

  cancelling.value = true
  try {
    const res = await inventoryService.cancelPurchaseRequisition(id.value, { reason })
    if (res?.success) {
      toast.add({ severity: 'success', summary: 'Cancelled', detail: 'PR cancelled.', life: 2000 })
      cancelDialogVisible.value = false
      await load()
    } else {
      toast.add({ severity: 'error', summary: 'Error', detail: res?.message || 'Failed to cancel PR', life: 3000 })
    }
  } finally {
    cancelling.value = false
  }
}

onMounted(async () => {
  try {
    if (!authStore.user) await authStore.fetchCurrentUser()
  } catch { }
  await load()
})
</script>
