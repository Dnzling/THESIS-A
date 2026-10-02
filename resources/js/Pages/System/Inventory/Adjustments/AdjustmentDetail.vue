<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <Toast />
    <ConfirmDialog />

    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <button type="button" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-gray-200 bg-white shadow-sm transition-colors hover:bg-gray-50"
          aria-label="Back to stock adjustments" @click="router.visit('/inventory/adjustments')">
          <i class="pi pi-chevron-left text-gray-600"></i>
        </button>
        <div>
          <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Stock Adjustment</h1>
          <p class="mt-1 text-sm text-gray-500">{{ detail?.adjustment_number || 'Review adjustment details' }}</p>
        </div>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <Button v-if="canApprove" label="Reject" icon="pi pi-times" severity="danger" outlined size="small" :disabled="processing" @click="openRejectDialog" />
        <Button v-if="canApprove" label="Approve" icon="pi pi-check" severity="success" size="small" :loading="processing" @click="confirmApprove" />
        <Tag v-if="detail" :value="label(detail.status)" :severity="statusSeverity(detail.status)" />
      </div>
    </div>

    <div v-if="error" class="rounded-xl border border-red-100 bg-red-50 p-4 text-sm text-red-700">{{ error }}</div>
    <div v-if="loading" class="space-y-4">
      <div class="grid gap-4 md:grid-cols-3"><Skeleton v-for="row in 3" :key="row" height="7rem" class="!rounded-2xl" /></div>
      <div class="grid gap-4 md:grid-cols-2"><Skeleton height="12rem" class="!rounded-2xl" /><Skeleton height="12rem" class="!rounded-2xl" /></div>
      <Skeleton height="16rem" class="!rounded-2xl" />
    </div>

    <template v-else-if="detail">
      <div v-if="detail.status === 'pending_approval'" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        This adjustment is awaiting approval. Stock quantities have not changed yet.
      </div>
      <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
          <div class="mb-3 flex items-center justify-between"><span class="text-xs font-medium uppercase tracking-wider text-gray-500">Reference</span><span class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100"><i class="pi pi-file text-blue-600"></i></span></div>
          <p class="break-all font-mono text-lg font-semibold text-gray-900">{{ detail.adjustment_number }}</p>
          <p class="mt-2 text-xs text-gray-500">Created {{ formatDate(detail.created_at) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
          <div class="mb-3 flex items-center justify-between"><span class="text-xs font-medium uppercase tracking-wider text-gray-500">Adjustment Date</span><span class="flex h-9 w-9 items-center justify-center rounded-full bg-violet-100"><i class="pi pi-calendar text-violet-600"></i></span></div>
          <p class="text-xl font-semibold text-gray-900">{{ formatDate(detail.adjustment_date) }}</p>
          <p class="mt-2 text-xs text-gray-500">{{ label(detail.type) }}</p>
        </div>
        <div class="rounded-2xl bg-gradient-to-br p-5 text-white shadow-lg" :class="totalValueDifference < 0 ? 'from-red-600 to-red-700' : totalValueDifference > 0 ? 'from-emerald-600 to-emerald-700' : 'from-slate-600 to-slate-700'">
          <div class="mb-3 flex items-center justify-between"><span class="text-xs font-medium uppercase tracking-wider text-white/80">Value Difference</span><span class="flex h-9 w-9 items-center justify-center rounded-full bg-white/15"><i class="pi pi-chart-line"></i></span></div>
          <p class="text-2xl font-bold">{{ signedMoney(totalValueDifference) }}</p>
          <p class="mt-2 text-xs text-white/80">Across {{ detail.items?.length || 0 }} {{ (detail.items?.length || 0) === 1 ? 'item' : 'items' }}</p>
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
          <div class="mb-4 flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100"><i class="pi pi-box text-blue-600"></i></span><h2 class="font-medium text-gray-900">Adjustment Information</h2></div>
          <dl class="grid gap-4 text-sm sm:grid-cols-2">
            <div><dt class="mb-1 text-xs text-gray-500">Branch</dt><dd class="font-medium text-gray-900">{{ detail.branch?.name || '—' }}</dd></div>
            <div><dt class="mb-1 text-xs text-gray-500">Type</dt><dd class="font-medium text-gray-900">{{ label(detail.type) }}</dd></div>
            <div><dt class="mb-1 text-xs text-gray-500">Status</dt><dd><Tag :value="label(detail.status)" :severity="statusSeverity(detail.status)" /></dd></div>
            <div class="sm:col-span-2"><dt class="mb-1 text-xs text-gray-500">Reason</dt><dd class="whitespace-pre-wrap font-medium text-gray-900">{{ formatReason(detail.reason) }}</dd></div>
          </dl>
        </section>
        <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
          <div class="mb-4 flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100"><i class="pi pi-users text-green-600"></i></span><h2 class="font-medium text-gray-900">Review History</h2></div>
          <dl class="grid gap-4 text-sm sm:grid-cols-2">
            <div><dt class="mb-1 text-xs text-gray-500">Created by</dt><dd class="font-medium text-gray-900">{{ employeeName(detail.created_by) }}</dd></div>
            <div><dt class="mb-1 text-xs text-gray-500">Created at</dt><dd class="font-medium text-gray-900">{{ formatDateTime(detail.created_at) }}</dd></div>
            <div><dt class="mb-1 text-xs text-gray-500">Approved by</dt><dd class="font-medium text-gray-900">{{ detail.approved_by ? employeeName(detail.approved_by) : 'Awaiting approval' }}</dd></div>
            <div><dt class="mb-1 text-xs text-gray-500">Approved at</dt><dd class="font-medium text-gray-900">{{ detail.approved_at ? formatDateTime(detail.approved_at) : 'Not yet approved' }}</dd></div>
            <div v-if="detail.approval_notes" class="sm:col-span-2"><dt class="mb-1 text-xs text-gray-500">Review notes</dt><dd class="whitespace-pre-wrap font-medium text-gray-900">{{ detail.approval_notes }}</dd></div>
          </dl>
        </section>
      </div>

      <section class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="flex items-center gap-2 border-b border-gray-100 bg-gray-50/50 px-6 py-4"><i class="pi pi-list text-gray-500"></i><h2 class="font-medium text-gray-800">Adjustment Items ({{ detail.items?.length || 0 }})</h2></div>
        <DataTable :value="detail.items || []" class="p-datatable-sm text-sm" scrollable>
          <template #empty><div class="py-8 text-center text-gray-500">No items recorded for this adjustment.</div></template>
          <Column header="Product" style="min-width: 230px"><template #body="{ data }"><p class="font-medium text-gray-900">{{ data.product?.product_name || 'Product unavailable' }}</p><p class="text-xs text-gray-500">{{ data.product?.sku || 'No SKU' }}<span v-if="data.variation"> · {{ data.variation.variation_name }}</span></p></template></Column>
          <Column header="System Qty" style="min-width: 110px"><template #body="{ data }">{{ quantity(data.system_quantity) }}</template></Column>
          <Column header="Actual Qty" style="min-width: 110px"><template #body="{ data }">{{ quantity(data.actual_quantity) }}</template></Column>
          <Column header="Difference" style="min-width: 115px"><template #body="{ data }"><Tag :value="signedQuantity(data.difference)" :severity="Number(data.difference) > 0 ? 'success' : Number(data.difference) < 0 ? 'danger' : 'secondary'" /></template></Column>
          <Column header="Unit Cost" style="min-width: 115px"><template #body="{ data }">{{ money(data.unit_cost) }}</template></Column>
          <Column header="Value Difference" style="min-width: 140px"><template #body="{ data }"><span class="font-medium" :class="Number(data.value_difference) < 0 ? 'text-red-600' : Number(data.value_difference) > 0 ? 'text-green-600' : 'text-gray-700'">{{ signedMoney(data.value_difference) }}</span></template></Column>
          <Column header="Notes" style="min-width: 160px"><template #body="{ data }"><span class="whitespace-pre-wrap text-gray-600">{{ data.notes || '—' }}</span></template></Column>
        </DataTable>
      </section>

      <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm"><p class="text-xs font-medium uppercase tracking-wider text-gray-500">Total Items</p><p class="mt-2 text-2xl font-semibold text-gray-900">{{ detail.items?.length || 0 }}</p></div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm"><p class="text-xs font-medium uppercase tracking-wider text-gray-500">Quantity Difference</p><p class="mt-2 text-2xl font-semibold" :class="totalDifference < 0 ? 'text-red-600' : totalDifference > 0 ? 'text-green-600' : 'text-gray-900'">{{ signedQuantity(totalDifference) }}</p></div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm"><p class="text-xs font-medium uppercase tracking-wider text-gray-500">Value Difference</p><p class="mt-2 text-2xl font-semibold" :class="totalValueDifference < 0 ? 'text-red-600' : totalValueDifference > 0 ? 'text-green-600' : 'text-gray-900'">{{ signedMoney(totalValueDifference) }}</p></div>
      </div>
    </template>
    <div v-else class="rounded-2xl border border-gray-100 bg-white py-12 text-center shadow-sm"><i class="pi pi-exclamation-triangle text-4xl text-gray-300"></i><p class="mt-2 text-gray-600">Adjustment not found.</p><Button label="Back to Adjustments" icon="pi pi-arrow-left" text class="mt-3" @click="router.visit('/inventory/adjustments')" /></div>

    <Dialog v-model:visible="showRejectDialog" header="Reject Adjustment" :style="{ width: 'min(520px, 95vw)' }" modal>
      <div class="space-y-4"><p class="rounded-lg border border-red-100 bg-red-50 p-3 text-sm text-red-800">Please provide a reason for rejection. It will be recorded in the review notes.</p><div><label class="mb-1 block text-sm font-medium text-gray-700">Reason *</label><Select v-model="rejectReason" :options="rejectReasonOptions" optionLabel="label" optionValue="value" placeholder="Select a reason" class="w-full" /></div><div><label class="mb-1 block text-sm font-medium text-gray-700">Additional notes</label><Textarea v-model="rejectNotes" rows="3" class="w-full" placeholder="Optional details" /></div></div>
      <template #footer><Button label="Cancel" text severity="secondary" @click="showRejectDialog = false" /><Button label="Reject Adjustment" icon="pi pi-times" severity="danger" :loading="processing" :disabled="!rejectReason" @click="confirmReject" /></template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import axiosClient from '@/axios'
import { useAuthStore } from '@/stores/auth'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Column from 'primevue/column'
import ConfirmDialog from 'primevue/confirmdialog'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'
import Toast from 'primevue/toast'

const auth = useAuthStore()
const page = usePage()
const confirm = useConfirm()
const toast = useToast()
const adjustmentId = computed(() => page.url.split('?')[0].split('/').filter(Boolean).pop())
const loading = ref(true)
const processing = ref(false)
const error = ref('')
const detail = ref<any>(null)
const showRejectDialog = ref(false)
const rejectReason = ref<string | null>(null)
const rejectNotes = ref('')
const rejectReasonOptions = [
  { label: 'Quantity mismatch', value: 'qty_mismatch' },
  { label: 'Wrong branch or items', value: 'wrong_branch_or_items' },
  { label: 'Missing supporting details', value: 'missing_details' },
  { label: 'Duplicate adjustment request', value: 'duplicate_request' },
  { label: 'Policy violation', value: 'policy_violation' },
  { label: 'Other', value: 'other' },
]
const canApprove = computed(() => detail.value?.status === 'pending_approval' && auth.hasPermission('inventory.adjustments.approve'))
const totalDifference = computed(() => (detail.value?.items || []).reduce((sum: number, item: any) => sum + Number(item.difference || 0), 0))
const totalValueDifference = computed(() => (detail.value?.items || []).reduce((sum: number, item: any) => sum + Number(item.value_difference || 0), 0))

function label(value?: string | null) { return value ? value.replace(/_/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase()) : '—' }
function formatReason(value?: string | null) {
  const reasons: Record<string, string> = { physical_count: 'Physical Count Correction', damaged: 'Damaged Goods', expired: 'Expired Items', theft: 'Theft/Loss', wrong_delivery: 'Wrong Delivery', quality_control: 'Quality Control', sample: 'Sample/Demo Usage', other: 'Other' }
  return value ? reasons[value] || label(value) : '—'
}
function statusSeverity(value: string) { return value === 'applied' || value === 'approved' ? 'success' : value === 'pending_approval' ? 'warn' : value === 'rejected' || value === 'cancelled' ? 'danger' : 'secondary' }
function formatDate(value?: string | null) { return value ? new Date(value).toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' }) : '—' }
function formatDateTime(value?: string | null) { return value ? new Date(value).toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : '—' }
function employeeName(employee: any) { return employee ? [employee.fname || employee.user?.fname, employee.lname || employee.user?.lname].filter(Boolean).join(' ') || `Employee #${employee.id}` : '—' }
function money(value: any) { return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0)) }
function signedMoney(value: any) { const number = Number(value || 0); return `${number > 0 ? '+' : number < 0 ? '−' : ''}${money(Math.abs(number))}` }
function quantity(value: any) { return Number(value || 0).toLocaleString('en-PH') }
function signedQuantity(value: any) { const number = Number(value || 0); return `${number > 0 ? '+' : ''}${quantity(number)}` }
async function loadDetail() {
  loading.value = true; error.value = ''
  try { const response = await axiosClient.get(`/api/inventory/adjustments/${adjustmentId.value}`); detail.value = response.data?.data || null }
  catch (cause: any) { detail.value = null; error.value = cause?.response?.data?.message || 'Unable to load adjustment details.' }
  finally { loading.value = false }
}
function confirmApprove() {
  confirm.require({ header: 'Approve Adjustment', message: 'This will apply the adjustment to inventory. Continue?', icon: 'pi pi-exclamation-triangle', acceptLabel: 'Approve', acceptProps: { severity: 'success' }, rejectLabel: 'Cancel', accept: approveAdjustment })
}
async function approveAdjustment() {
  if (!detail.value?.id) return
  processing.value = true
  try { await axiosClient.post(`/api/inventory/adjustments/${detail.value.id}/approve`); toast.add({ severity: 'success', summary: 'Approved', detail: 'Adjustment applied to inventory.', life: 3000 }); await loadDetail() }
  catch (cause: any) { toast.add({ severity: 'error', summary: 'Error', detail: cause?.response?.data?.message || 'Unable to approve adjustment.', life: 3000 }) }
  finally { processing.value = false }
}
function openRejectDialog() { rejectReason.value = null; rejectNotes.value = ''; showRejectDialog.value = true }
function confirmReject() {
  if (!rejectReason.value) return
  confirm.require({ header: 'Reject Adjustment', message: 'Reject this adjustment?', icon: 'pi pi-exclamation-triangle', acceptLabel: 'Reject', acceptProps: { severity: 'danger' }, rejectLabel: 'Cancel', accept: rejectAdjustment })
}
async function rejectAdjustment() {
  if (!detail.value?.id || !rejectReason.value) return
  processing.value = true
  try {
    const reason = rejectReasonOptions.find(option => option.value === rejectReason.value)?.label || 'Other'
    const rejectionReason = rejectNotes.value.trim() ? `${reason}: ${rejectNotes.value.trim()}` : reason
    await axiosClient.post(`/api/inventory/adjustments/${detail.value.id}/reject`, { rejection_reason: rejectionReason })
    showRejectDialog.value = false
    toast.add({ severity: 'success', summary: 'Rejected', detail: 'Adjustment rejected.', life: 3000 })
    await loadDetail()
  } catch (cause: any) { toast.add({ severity: 'error', summary: 'Error', detail: cause?.response?.data?.message || 'Unable to reject adjustment.', life: 3000 }) }
  finally { processing.value = false }
}
onMounted(loadDetail)
</script>
