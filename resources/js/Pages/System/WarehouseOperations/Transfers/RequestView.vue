<template>
  <div class="min-h-screen space-y-6 p-4 md:p-6">
    <ConfirmDialog />
    <template v-if="transfer">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <Button label="Back to Transfer Requests" icon="pi pi-arrow-left" text size="small" @click="router.visit('/warehouse-operations/transfer-requests')"/>
          <div class="mt-2 flex items-center gap-3"><h1 class="text-2xl font-semibold text-slate-900">{{ transfer.transfer_number }}</h1><Badge :value="label(transfer.status)" :severity="severity(transfer.status)"/></div>
          <p class="mt-1 text-sm text-slate-500">Requested {{ dateTime(transfer.created_at) }}</p>
        </div>
        <div v-if="canAct" class="flex gap-2">
          <Button v-if="canReject" label="Reject" icon="pi pi-times" severity="danger" outlined size="small" @click="rejectVisible = true"/>
          <Button v-if="canApprove" label="Approve" icon="pi pi-check" size="small" @click="confirmApprove"/>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <Card v-for="card in summary" :key="card.label" class="border border-slate-200 shadow-sm"><template #content><p class="text-xs uppercase tracking-wide text-slate-500">{{ card.label }}</p><p class="mt-1 text-lg font-semibold text-slate-900">{{ card.value }}</p></template></Card>
      </div>

      <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
          <Card class="border border-slate-200 shadow-sm"><template #title><span class="text-base">Transfer Items</span></template><template #content>
            <DataTable :value="transfer.items || []" size="small" stripedRows rowHover>
              <template #empty><div class="py-8 text-center text-slate-500">No transfer items recorded.</div></template>
              <Column header="Product"><template #body="{ data }"><p class="font-medium text-slate-900">{{ data.product?.product_name || '—' }}</p><p class="text-xs text-slate-500">{{ data.variation?.variation_sku || data.product?.sku || 'No SKU' }}<span v-if="data.variation"> · {{ data.variation.variation_name }}</span></p></template></Column>
              <Column header="Category"><template #body="{ data }">{{ data.product?.category?.category_name || '—' }}</template></Column>
              <Column header="Requested"><template #body="{ data }"><strong>{{ number(data.requested_quantity) }}</strong> {{ data.product?.unit_of_measurement || 'unit' }}</template></Column>
              <Column header="Approved"><template #body="{ data }">{{ data.approved_quantity == null ? '—' : number(data.approved_quantity) }}</template></Column>
              <Column header="Unit Cost"><template #body="{ data }">{{ money(data.unit_value) }}</template></Column>
              <Column header="Line Value"><template #body="{ data }"><strong>{{ money(Number(data.unit_value || 0) * Number(data.requested_quantity || 0)) }}</strong></template></Column>
            </DataTable>
          </template></Card>

          <Card class="border border-slate-200 shadow-sm"><template #title><span class="text-base">Request Information</span></template><template #content>
            <div class="grid gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3"><div v-for="field in requestFields" :key="field.label"><p class="text-xs uppercase tracking-wide text-slate-500">{{ field.label }}</p><p class="mt-1 font-medium text-slate-900">{{ field.value || '—' }}</p></div></div>
            <Divider/><p class="text-xs uppercase tracking-wide text-slate-500">Reason</p><p class="mt-2 text-sm text-slate-700">{{ transfer.reason || 'No reason provided.' }}</p>
            <template v-if="transfer.notes"><p class="mt-4 text-xs uppercase tracking-wide text-slate-500">Notes</p><p class="mt-2 text-sm text-slate-700">{{ transfer.notes }}</p></template>
            <template v-if="transfer.rejection_reason"><Message severity="error" class="mt-5"><strong>Rejection reason:</strong> {{ transfer.rejection_reason }}</Message></template>
          </template></Card>
        </div>

        <div class="space-y-6">
          <Card class="border border-slate-200 shadow-sm"><template #title><span class="text-base">Transfer Route</span></template><template #content><dl class="space-y-4 text-sm"><div v-for="field in routeFields" :key="field.label" class="border-b border-slate-100 pb-3"><dt class="text-slate-500">{{ field.label }}</dt><dd class="mt-1 font-medium text-slate-900">{{ field.value || '—' }}</dd></div></dl></template></Card>
          <Card class="border border-slate-200 shadow-sm"><template #title><span class="text-base">Cost Rundown</span></template><template #content><div class="space-y-3 text-sm"><div class="flex justify-between"><span class="text-slate-500">Goods value</span><strong>{{ money(transfer.goods_value) }}</strong></div><div class="flex justify-between"><span class="text-slate-500">Distance</span><strong>{{ number(transfer.distance_km) }} km</strong></div><div class="flex justify-between"><span class="text-slate-500">Cost method</span><strong>{{ label(transfer.cost_method) }}</strong></div><Divider/><div class="flex justify-between text-base"><span class="font-semibold">Shipping fee</span><strong>{{ money(transfer.transfer_cost) }}</strong></div><div class="flex justify-between rounded-lg bg-slate-900 px-3 py-3 text-white"><span>Total transfer value</span><strong>{{ money(Number(transfer.goods_value || 0) + Number(transfer.transfer_cost || 0)) }}</strong></div></div></template></Card>
        </div>
      </div>
    </template>
    <div v-else class="flex min-h-[60vh] items-center justify-center"><ProgressSpinner v-if="loading"/><Message v-else severity="error">The transfer request could not be loaded.</Message></div>

    <Dialog v-model:visible="rejectVisible" modal header="Reject Transfer Request" :style="{ width: 'min(92vw, 520px)' }">
      <div class="space-y-4"><Message severity="warn">Select a reason and provide details for the requesting branch.</Message><div><label class="mb-2 block text-sm font-medium text-slate-700">Reason <span class="text-red-500">*</span></label><Select v-model="rejectForm.reason" :options="rejectReasons" placeholder="Select rejection reason" class="w-full"/></div><div><label class="mb-2 block text-sm font-medium text-slate-700">Details</label><Textarea v-model="rejectForm.details" rows="4" class="w-full" placeholder="Add useful details about the rejection"/></div><small v-if="rejectError" class="text-red-600">{{ rejectError }}</small></div>
      <template #footer><Button label="Cancel" severity="secondary" text @click="rejectVisible = false"/><Button label="Continue" severity="danger" :disabled="!rejectForm.reason" @click="confirmReject"/></template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import WarehouseService from '@/services/warehouse.service'
import { useAuthStore } from '@/stores/auth'

const page = usePage(), confirm = useConfirm(), toast = useToast(), auth = useAuthStore()
const loading = ref(true), transfer = ref<any>(null), rejectVisible = ref(false), rejectError = ref('')
const rejectForm = reactive({ reason: '', details: '' })
const rejectReasons = ['Insufficient stock', 'Product unavailable', 'Quantity cannot be fulfilled', 'Invalid destination', 'Transfer details require correction', 'Other']
const id = computed(() => String(page.url).match(/transfer-requests\/(\d+)/)?.[1] || '')
const canApprove = computed(() => auth.hasPermission('warehouse.transfers.approve'))
const canReject = computed(() => auth.hasPermission('warehouse.transfers.reject'))
const canAct = computed(() => ['requested', 'pending_approval'].includes(String(transfer.value?.status)) && (canApprove.value || canReject.value))
const label = (value: any) => String(value || '—').replaceAll('_', ' ').replace(/\b\w/g, char => char.toUpperCase())
const number = (value: any) => Number(value || 0).toLocaleString('en-PH', { maximumFractionDigits: 2 })
const money = (value: any) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
const date = (value: any) => value ? new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium' }).format(new Date(value)) : '—'
const dateTime = (value: any) => value ? new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '—'
const person = (employee: any) => employee?.user?.full_name || [employee?.user?.fname, employee?.user?.lname].filter(Boolean).join(' ') || '—'
const severity = (value: string) => value === 'sender_approved' ? 'success' : value === 'rejected' ? 'danger' : 'warn'
const summary = computed(() => [{ label: 'Total Items', value: number(transfer.value.items?.length) }, { label: 'Total Quantity', value: number(transfer.value.items?.reduce((sum: number, item: any) => sum + Number(item.requested_quantity || 0), 0)) }, { label: 'Goods Value', value: money(transfer.value.goods_value) }, { label: 'Shipping Fee', value: money(transfer.value.transfer_cost) }])
const requestFields = computed(() => [{ label: 'Requested By', value: person(transfer.value.requested_by) }, { label: 'Requested Date', value: date(transfer.value.requested_date) }, { label: 'Expected Delivery', value: date(transfer.value.expected_delivery_date) }, { label: 'Approval Policy', value: label(transfer.value.approval_policy_used) }, { label: 'Approved By', value: person(transfer.value.sender_approved_by) }, { label: 'Approved Date', value: date(transfer.value.sender_approved_date) }])
const routeFields = computed(() => [{ label: 'From Location', value: transfer.value.from_branch?.name }, { label: 'Source Type', value: label(transfer.value.from_branch?.branch_type) }, { label: 'To Location', value: transfer.value.to_branch?.name }, { label: 'Destination Type', value: label(transfer.value.to_branch?.branch_type) }])
const load = async () => { loading.value = true; try { transfer.value = await WarehouseService.transferRequest(id.value) } catch (error: any) { toast.add({ severity: 'error', summary: 'Load failed', detail: error.response?.data?.message || 'Unable to load transfer request.', life: 3000 }) } finally { loading.value = false } }
const confirmApprove = () => confirm.require({ header: 'Approve Transfer Request', message: 'Approve this transfer and confirm the requested quantities?', icon: 'pi pi-check-circle', rejectLabel: 'Cancel', acceptLabel: 'Approve', rejectProps: { severity: 'secondary', outlined: true }, accept: async () => { try { await WarehouseService.approveTransferRequest(id.value); toast.add({ severity: 'success', summary: 'Approved', detail: 'Transfer request approved successfully.', life: 2500 }); await load() } catch (error: any) { toast.add({ severity: 'error', summary: 'Approval failed', detail: error.response?.data?.message || 'Unable to approve transfer.', life: 3000 }) } } })
const confirmReject = () => { rejectError.value = ''; if (!rejectForm.reason) { rejectError.value = 'Select a rejection reason.'; return } const reason = rejectForm.details.trim() ? `${rejectForm.reason}: ${rejectForm.details.trim()}` : rejectForm.reason; rejectVisible.value = false; confirm.require({ header: 'Confirm Rejection', message: 'Reject this transfer request? This action will notify the requesting branch.', icon: 'pi pi-exclamation-triangle', rejectLabel: 'Cancel', acceptLabel: 'Reject', acceptProps: { severity: 'danger' }, accept: async () => { try { await WarehouseService.rejectTransferRequest(id.value, reason); toast.add({ severity: 'success', summary: 'Rejected', detail: 'Transfer request rejected.', life: 2500 }); await load() } catch (error: any) { toast.add({ severity: 'error', summary: 'Rejection failed', detail: error.response?.data?.message || 'Unable to reject transfer.', life: 3000 }) } } }) }
onMounted(load)
</script>
