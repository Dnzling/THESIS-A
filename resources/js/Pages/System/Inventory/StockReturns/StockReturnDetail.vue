<template>
  <div class="mx-auto max-w-6xl space-y-5 px-4 py-5 sm:px-6">
    <ConfirmDialog />
    <Dialog v-model:visible="resolutionVisible" modal header="Record supplier response" :style="{ width: 'min(32rem, calc(100vw - 2rem))' }">
      <div class="space-y-4">
        <p class="text-sm text-slate-600">Record what the supplier decided after receiving the damaged item.</p>
        <div class="space-y-1.5">
          <label class="text-sm font-medium text-slate-700">Supplier outcome</label>
          <Select v-model="resolution.supplier_resolution" :options="resolutionOptions" optionLabel="label" optionValue="value" placeholder="Select outcome" fluid />
        </div>
        <div class="space-y-1.5">
          <label class="text-sm font-medium text-slate-700">Reference or notes</label>
          <Textarea v-model="resolution.supplier_resolution_notes" rows="3" autoResize fluid placeholder="Credit note, replacement reference, repair notes, or reason rejected." />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="resolutionVisible = false" />
        <Button label="Save outcome" severity="warn" :loading="actionLoading" :disabled="!resolution.supplier_resolution" @click="recordResolution" />
      </template>
    </Dialog>
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" severity="secondary" text rounded aria-label="Back to stock returns"
          @click="router.back()" />
        <div>
          <h1 class="text-xl font-bold text-slate-900">{{ stockReturn?.return_number || 'Stock Return Details' }}</h1>
          <p class="mt-1 text-sm text-slate-500">Review returned items and destination information.</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Tag v-if="stockReturn" :value="humanize(stockReturn.status)" :severity="statusSeverity(stockReturn.status)" />
        <Button label="Print Return Note" icon="pi pi-print" severity="secondary" outlined rounded size="small"
          :disabled="loading || !stockReturn" :loading="printing" @click="printReturn" />
      </div>
    </div>

    <div v-if="loading" class="space-y-4">
      <Skeleton height="9rem" />
      <Skeleton height="18rem" />
    </div>
    <Message v-else-if="error" severity="error" :closable="false">{{ error }}</Message>
    <template v-else-if="stockReturn">
      <Card>
        <template #title><span class="text-base">Return Information</span></template>
        <template #content>
          <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <div><p class="field-label">Type</p><p class="field-value">{{ humanize(stockReturn.return_type) }}</p></div>
            <div><p class="field-label">From Branch</p><p class="field-value">{{ stockReturn.from_branch?.name || '-' }}</p></div>
            <div><p class="field-label">Destination</p><p class="field-value">{{ stockReturn.supplier?.supplier_name || stockReturn.to_branch?.name || '-' }}</p></div>
            <div><p class="field-label">Requested Date</p><p class="field-value">{{ formatDate(stockReturn.requested_date) }}</p></div>
            <div><p class="field-label">Return Reason</p><p class="field-value">{{ humanize(stockReturn.return_reason) }}</p></div>
            <div><p class="field-label">Total Return Value</p><p class="field-value">{{ money(stockReturn.total_value) }}</p></div>
          </div>
          <div v-if="stockReturn.return_type === 'supplier_return'" class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
            <span class="font-medium text-slate-700">Resolution state: </span>
            <span class="text-slate-600">{{ stockReturn.supplier_resolution ? humanize(stockReturn.supplier_resolution) : 'Awaiting supplier response' }}</span>
          </div>
          <p v-if="stockReturn.reason_details" class="mt-4 whitespace-pre-line text-sm text-slate-700">{{ stockReturn.reason_details }}</p>
          <p v-if="stockReturn.notes" class="mt-3 whitespace-pre-line text-sm text-slate-600">{{ stockReturn.notes }}</p>
        </template>
      </Card>

      <Card v-if="stockReturn.return_type === 'supplier_return'" class="border border-orange-200 bg-orange-50/40">
        <template #content>
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <p class="font-semibold text-slate-900">Supplier disposition</p>
              <p class="mt-1 text-sm text-slate-600">{{ stockReturn.supplier_resolution ? humanize(stockReturn.supplier_resolution) : 'Waiting for supplier response' }}</p>
              <p v-if="stockReturn.supplier_resolution_notes" class="mt-1 text-sm text-slate-600">{{ stockReturn.supplier_resolution_notes }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
              <Button v-if="stockReturn.status === 'requested' && canApprove" label="Approve return" icon="pi pi-check" severity="warn" size="small" :loading="actionLoading" @click="approveReturn" />
              <Button v-if="stockReturn.status === 'approved' && canManage" label="Mark dispatched" icon="pi pi-send" severity="warn" outlined size="small" :loading="actionLoading" @click="dispatchReturn" />
              <Button v-if="stockReturn.status === 'in_transit' && canManage" label="Record supplier response" icon="pi pi-file-edit" severity="secondary" outlined size="small" @click="resolutionVisible = true" />
            </div>
          </div>
        </template>
      </Card>

      <Card>
        <template #title><span class="text-base">Returned Items ({{ stockReturn.items?.length || 0 }})</span></template>
        <template #content>
          <DataTable :value="stockReturn.items || []" rowHover size="small" responsiveLayout="scroll">
            <template #empty><div class="py-8 text-center text-sm text-slate-500">No returned items recorded.</div></template>
            <Column header="Product">
              <template #body="{ data }">
                <p class="font-semibold text-slate-900">{{ data.product?.product_name || 'Unknown Product' }}</p>
                <p class="text-xs text-slate-500">SKU: {{ data.variation?.variation_sku || data.product?.sku || '-' }}</p>
                <p v-if="data.variation?.variation_name" class="text-xs text-slate-500">{{ data.variation.variation_name }}</p>
              </template>
            </Column>
            <Column field="quantity_returned" header="Quantity" />
            <Column header="Condition"><template #body="{ data }">{{ humanize(data.condition) }}</template></Column>
            <Column header="Unit Value"><template #body="{ data }">{{ money(data.unit_value ?? data.unit_cost) }}</template></Column>
            <Column header="Total Value"><template #body="{ data }">{{ money(data.total_value ?? data.total_cost) }}</template></Column>
            <Column field="notes" header="Notes"><template #body="{ data }">{{ data.notes || '-' }}</template></Column>
          </DataTable>
        </template>
      </Card>
    </template>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axiosClient from '@/axios'
import Button from 'primevue/button'
import ConfirmDialog from 'primevue/confirmdialog'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import Message from 'primevue/message'
import Skeleton from 'primevue/skeleton'
import Textarea from 'primevue/textarea'
import Tag from 'primevue/tag'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import { computed, reactive } from 'vue'
import inventoryService from '@/services/inventory.service'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const confirm = useConfirm()
const toast = useToast()
const authStore = useAuthStore()
const stockReturn = ref<any>(null)
const loading = ref(true)
const printing = ref(false)
const error = ref('')
const actionLoading = ref(false)
const resolutionVisible = ref(false)
const resolution = reactive({ supplier_resolution: '', supplier_resolution_notes: '' })
const resolutionOptions = [
  { label: 'Replacement', value: 'replacement' },
  { label: 'Credit', value: 'credit' },
  { label: 'Repair', value: 'repair' },
  { label: 'Rejected by supplier', value: 'rejected' },
  { label: 'Other', value: 'other' },
]
const canApprove = computed(() => authStore.hasPermission('inventory.stock_returns.approve'))
const canManage = computed(() => authStore.hasPermission('inventory.stock_returns.manage'))

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await axiosClient.get(`/api/inventory/returns/${route.params.id}`)
    stockReturn.value = response.data?.data ?? null
    if (!stockReturn.value) throw new Error('Stock return details were not returned.')
  } catch (cause: any) {
    error.value = cause?.response?.data?.message || cause?.message || 'Unable to load this stock return.'
  } finally {
    loading.value = false
  }
}

const approveReturn = () => confirm.require({
  header: 'Approve supplier return?',
  message: 'This approves the return request only. Damaged stock will be removed when it is marked as dispatched.',
  acceptLabel: 'Approve',
  rejectLabel: 'Keep pending',
  accept: async () => {
    actionLoading.value = true
    try { await inventoryService.approveStockReturn(Number(route.params.id)); await load(); toast.add({ severity: 'success', summary: 'Return approved', detail: 'Stock remains quarantined until dispatch.', life: 3000 }) }
    catch (cause: any) { toast.add({ severity: 'error', summary: 'Unable to approve', detail: cause?.response?.data?.message || 'Approval failed.', life: 4000 }) }
    finally { actionLoading.value = false }
  },
})

const dispatchReturn = () => confirm.require({
  header: 'Mark return as dispatched?',
  message: 'This confirms the damaged items have physically left the branch. The damaged and on-hand counts will be reduced.',
  acceptLabel: 'Mark dispatched',
  rejectLabel: 'Cancel',
  accept: async () => {
    actionLoading.value = true
    try { await inventoryService.shipStockReturn(Number(route.params.id)); await load(); toast.add({ severity: 'success', summary: 'Return dispatched', detail: 'Damaged stock was removed from branch on-hand.', life: 3000 }) }
    catch (cause: any) { toast.add({ severity: 'error', summary: 'Unable to dispatch', detail: cause?.response?.data?.message || 'Dispatch failed.', life: 4000 }) }
    finally { actionLoading.value = false }
  },
})

const recordResolution = async () => {
  if (!resolution.supplier_resolution || actionLoading.value) return
  actionLoading.value = true
  try {
    await inventoryService.receiveStockReturn(Number(route.params.id), {
      supplier_resolution: resolution.supplier_resolution,
      supplier_resolution_notes: resolution.supplier_resolution_notes || undefined,
    })
    resolutionVisible.value = false
    await load()
    toast.add({ severity: 'success', summary: 'Supplier outcome recorded', detail: 'The return record is now complete.', life: 3000 })
  } catch (cause: any) {
    toast.add({ severity: 'error', summary: 'Unable to record outcome', detail: cause?.response?.data?.message || 'Supplier outcome could not be saved.', life: 4000 })
  } finally { actionLoading.value = false }
}

const printReturn = async () => {
  if (printing.value || !stockReturn.value) return
  printing.value = true
  try {
    const response = await axiosClient.get(`/api/inventory/returns/${route.params.id}/print`, { responseType: 'blob' })
    const url = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const printWindow = window.open(url, '_blank')
    if (printWindow) printWindow.opener = null
    setTimeout(() => URL.revokeObjectURL(url), 60_000)
  } catch (cause: any) {
    error.value = cause?.response?.data?.message || 'Unable to create the stock return PDF.'
  } finally {
    printing.value = false
  }
}

const humanize = (value?: string) => value ? value.replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase()) : '-'
const statusSeverity = (status?: string) => ({
  draft: 'secondary', requested: 'warn', approved: 'info', shipped: 'info', received: 'success',
  rejected: 'danger', cancelled: 'secondary',
}[String(status || '').toLowerCase()] || 'secondary')
const formatDate = (value?: string) => value ? new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }) : '-'
const money = (value: unknown) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))

onMounted(load)
</script>

<style scoped>
.field-label { font-size: 0.7rem; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
.field-value { margin-top: .25rem; font-size: .9rem; font-weight: 600; color: #0f172a; }
</style>
