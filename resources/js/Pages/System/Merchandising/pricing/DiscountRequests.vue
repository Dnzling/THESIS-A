<template>
  <div class="space-y-5 text-sm">
    <header class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-orange-700">Promotions &amp; Discounts</p>
        <h1 class="mt-1 text-xl font-semibold text-slate-900">Discount Requests</h1>
        <p class="mt-1 text-sm text-slate-500">Review promotion proposals raised from slow-moving inventory reports.</p>
      </div>
      <Button label="Promotions & Discounts" icon="pi pi-arrow-left" severity="secondary" outlined size="small" @click="router.push({ name: 'merchandising.pricing' })" />
    </header>

    <Message severity="info" :closable="false">
      Approving a request creates a promotion for that product at its requesting branch. Choose the promotion dates when approving.
    </Message>

    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
          <IconField class="w-full sm:max-w-sm">
            <InputIcon class="pi pi-search" />
            <InputText v-model="search" placeholder="Search product, SKU, or branch" size="small" fluid @input="searchLater" />
          </IconField>
          <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs text-slate-500">{{ requests.length }} request{{ requests.length === 1 ? '' : 's' }}</span>
            <Button v-if="canManage && selectedRequests.length" :label="`Approve Selected (${selectedRequests.length})`" icon="pi pi-check" severity="warn" size="small" :disabled="hasVariantSelection || decisionLoading" @click="openApproval" />
            <Button v-if="canManage && selectedRequests.length" :label="`Reject Selected (${selectedRequests.length})`" icon="pi pi-times" severity="secondary" outlined size="small" :disabled="decisionLoading" @click="rejectionVisible = true" />
          </div>
        </div>

        <div v-if="loading" class="space-y-2"><Skeleton v-for="item in 5" :key="item" height="2.5rem" /></div>
        <Message v-else-if="error" severity="error" :closable="false">{{ error }}</Message>
        <DataTable v-else :value="requests" dataKey="id" size="small" rowHover responsiveLayout="scroll" class="text-xs">
          <template #empty><div class="py-12 text-center text-sm text-slate-500">No discount requests yet. Requests from Inventory Reports will appear here.</div></template>
          <Column v-if="canManage" headerStyle="width: 3rem">
            <template #header>
              <Checkbox :modelValue="allPendingSelected" binary aria-label="Select all pending requests" @update:modelValue="toggleAllPending" />
            </template>
            <template #body="{ data }">
              <Checkbox v-if="data.status === 'pending'" :modelValue="isSelected(data)" binary :title="data.variation_id ? 'This request is for a variant. It can be rejected, but current promotions apply to the full product.' : undefined" :aria-label="`Select ${data.product_name}`" @update:modelValue="toggleSelected(data, $event)" />
              <span v-else class="text-slate-300">—</span>
            </template>
          </Column>
          <Column header="Product" style="min-width: 210px">
            <template #body="{ data }">
              <p class="font-semibold text-slate-900">{{ data.product_name }}</p>
              <p class="mt-0.5 text-xs text-slate-500">{{ data.variation_name || data.sku || 'Finished good' }}</p>
            </template>
          </Column>
          <Column header="Category" style="min-width: 130px">
            <template #body="{ data }">{{ data.category_name || '—' }}</template>
          </Column>
          <Column header="Subcategory" style="min-width: 130px">
            <template #body="{ data }">{{ data.subcategory_name || '—' }}</template>
          </Column>
          <Column field="branch_name" header="Branch" />
          <Column header="Proposed discount" style="min-width: 140px">
            <template #body="{ data }"><span class="font-semibold text-orange-700">{{ discountLabel(data) }}</span></template>
          </Column>
          <Column header="Sales / Stock" style="min-width: 120px">
            <template #body="{ data }">{{ data.units_sold }} sold / {{ data.available_stock }} available</template>
          </Column>
          <Column header="Requested by" field="requested_by" />
          <Column header="Submitted" style="min-width: 130px">
            <template #body="{ data }">{{ dateTime(data.created_at) }}</template>
          </Column>
          <Column header="Status">
            <template #body="{ data }"><Badge :value="label(data.status)" :severity="statusSeverity(data.status)" /></template>
          </Column>
          <Column header="Action" style="width: 90px">
            <template #body="{ data }"><Button label="View" severity="secondary" outlined rounded size="small" @click="openRequest(data)" /></template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Drawer v-model:visible="detailVisible" position="right" modal header="Discount Request Details" class="w-full sm:w-[34rem]">
      <div v-if="activeRequest" class="space-y-5">
        <section>
          <h2 class="text-lg font-semibold text-slate-900">{{ activeRequest.product_name }}</h2>
          <p class="mt-1 text-xs text-slate-500">{{ activeRequest.variation_name || activeRequest.sku || 'Finished good' }} · {{ activeRequest.branch_name }}</p>
        </section>
        <section class="grid grid-cols-2 gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
          <div><p class="text-xs text-slate-500">Proposed discount</p><p class="mt-1 font-semibold text-orange-700">{{ discountLabel(activeRequest) }}</p></div>
          <div><p class="text-xs text-slate-500">Report period</p><p class="mt-1 font-medium">{{ date(activeRequest.report_period_start) }} – {{ date(activeRequest.report_period_end) }}</p></div>
          <div><p class="text-xs text-slate-500">Units sold</p><p class="mt-1 font-medium">{{ activeRequest.units_sold }}</p></div>
          <div><p class="text-xs text-slate-500">Available stock</p><p class="mt-1 font-medium">{{ activeRequest.available_stock }}</p></div>
          <div><p class="text-xs text-slate-500">Days of cover</p><p class="mt-1 font-medium">{{ activeRequest.days_cover ?? 'No sales pace' }}</p></div>
          <div><p class="text-xs text-slate-500">Days without sale</p><p class="mt-1 font-medium">{{ activeRequest.days_without_sale ?? 'No recorded sale' }}</p></div>
          <div><p class="text-xs text-slate-500">Days listed</p><p class="mt-1 font-medium">{{ activeRequest.days_listed }}</p></div>
          <div><p class="text-xs text-slate-500">Stock value</p><p class="mt-1 font-medium">{{ money(activeRequest.stock_value) }}</p></div>
        </section>
        <section>
          <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Reason for promotion</h3>
          <p class="mt-2 whitespace-pre-line leading-6 text-slate-700">{{ activeRequest.reason }}</p>
        </section>
        <p class="border-t border-slate-200 pt-4 text-xs leading-5 text-slate-500">
          <template v-if="activeRequest.variation_id">This request is for one variant, but the current promotion system targets the full product. It cannot be approved without discounting its other variants.</template>
          <template v-else>Approving creates a promotion for this product at {{ activeRequest.branch_name }}.</template>
        </p>
      </div>
    </Drawer>

    <Dialog v-model:visible="approvalVisible" modal header="Approve discount requests" :draggable="false" class="w-full max-w-2xl">
      <div class="space-y-4">
        <p class="text-sm text-slate-600">This creates one promotion for each selected product at its requested branch. Choose the dates that apply to all selected promotions.</p>
        <div class="grid gap-3 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">Starts on *</label>
            <DatePicker v-model="startsAt" dateFormat="M d, yy" :minDate="today" showIcon fluid size="small" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">Ends on *</label>
            <DatePicker v-model="endsAt" dateFormat="M d, yy" :minDate="startsAt || today" showIcon fluid size="small" />
          </div>
        </div>
        <div class="max-h-56 space-y-2 overflow-y-auto rounded-lg border border-slate-200 p-3">
          <div v-for="request in selectedRequests" :key="request.id" class="flex items-start justify-between gap-3 border-b border-slate-100 pb-2 last:border-0 last:pb-0">
            <div class="min-w-0"><p class="truncate text-sm font-medium text-slate-900">{{ request.product_name }}</p><p class="text-xs text-slate-500">{{ request.branch_name }}</p></div>
            <span class="shrink-0 text-sm font-semibold text-orange-700">{{ discountLabel(request) }}</span>
          </div>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined size="small" :disabled="decisionLoading" @click="approvalVisible = false" />
        <Button label="Confirm Approval" icon="pi pi-check" severity="warn" size="small" :loading="decisionLoading" @click="submitDecision('approve')" />
      </template>
    </Dialog>

    <Dialog v-model:visible="rejectionVisible" modal header="Reject discount requests" :draggable="false" class="w-full max-w-lg">
      <div class="space-y-3">
        <p class="text-sm text-slate-600">Rejecting {{ selectedRequests.length }} request{{ selectedRequests.length === 1 ? '' : 's' }}. Add a reason for the requesters.</p>
        <label for="discount-rejection-reason" class="block text-xs font-medium text-slate-600">Reason *</label>
        <Textarea id="discount-rejection-reason" v-model="rejectionReason" rows="4" fluid maxlength="1500" placeholder="Explain why these requests were rejected." />
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined size="small" :disabled="decisionLoading" @click="rejectionVisible = false" />
        <Button label="Confirm Rejection" icon="pi pi-times" severity="warn" size="small" :loading="decisionLoading" @click="submitDecision('reject')" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import merchandisingService from '../../../../services/merchandising.service'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Drawer from 'primevue/drawer'
import Checkbox from 'primevue/checkbox'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Skeleton from 'primevue/skeleton'
import Textarea from 'primevue/textarea'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const requests = ref<any[]>([])
const loading = ref(true)
const error = ref('')
const search = ref('')
const detailVisible = ref(false)
const activeRequest = ref<any>(null)
const selectedRequests = ref<any[]>([])
const approvalVisible = ref(false)
const rejectionVisible = ref(false)
const decisionLoading = ref(false)
const rejectionReason = ref('')
const today = new Date()
today.setHours(0, 0, 0, 0)
const startsAt = ref<Date | null>(new Date(today))
const endsAt = ref<Date | null>(null)
const canManage = computed(() => ['merchandising.pricing.manage', 'merchandising.pricing.create', 'merchandising.pricing.edit']
  .some((permission) => authStore.hasPermission(permission)))
const allPendingSelected = computed(() => {
  const selectable = requests.value.filter((request) => request.status === 'pending')
  return selectable.length > 0 && selectable.every((request) => isSelected(request))
})
const hasVariantSelection = computed(() => selectedRequests.value.some((request) => Boolean(request.variation_id)))
let searchTimer: ReturnType<typeof setTimeout> | undefined

const loadRequests = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await merchandisingService.getDiscountRequests({ search: search.value.trim() || undefined })
    requests.value = response.data?.requests || []
  } catch (cause: any) {
    error.value = cause?.response?.data?.message || 'Unable to load discount requests.'
    toast.add({ severity: 'error', summary: 'Requests unavailable', detail: error.value, life: 3500 })
  } finally {
    loading.value = false
  }
}
const searchLater = () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(loadRequests, 300)
}
const openRequest = (request: any) => {
  activeRequest.value = request
  detailVisible.value = true
}
const isSelected = (request: any) => selectedRequests.value.some((selected) => selected.id === request.id)
const toggleSelected = (request: any, checked: boolean) => {
  selectedRequests.value = checked
    ? [...selectedRequests.value.filter((selected) => selected.id !== request.id), request]
    : selectedRequests.value.filter((selected) => selected.id !== request.id)
}
const toggleAllPending = (checked: boolean) => {
  const pending = requests.value.filter((request) => request.status === 'pending')
  const ids = new Set(pending.map((request) => request.id))
  selectedRequests.value = checked
    ? [...selectedRequests.value.filter((request) => !ids.has(request.id)), ...pending]
    : selectedRequests.value.filter((request) => !ids.has(request.id))
}
const openApproval = () => {
  if (!selectedRequests.value.length || hasVariantSelection.value) return
  startsAt.value = new Date(today)
  endsAt.value = null
  approvalVisible.value = true
}
const dateTimeForApi = (value: Date | null, endOfDay = false) => {
  if (!value) return ''
  const date = `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`
  return `${date}T${endOfDay ? '23:59:59' : '00:00:00'}`
}
const submitDecision = async (decision: 'approve' | 'reject') => {
  if (!selectedRequests.value.length || decisionLoading.value) return
  if (decision === 'approve' && (!startsAt.value || !endsAt.value || endsAt.value < startsAt.value)) {
    toast.add({ severity: 'warn', summary: 'Promotion dates required', detail: 'Choose a valid start and end date.', life: 3500 })
    return
  }
  if (decision === 'reject' && rejectionReason.value.trim().length < 3) {
    toast.add({ severity: 'warn', summary: 'Reason required', detail: 'Enter at least 3 characters for the rejection reason.', life: 3500 })
    return
  }

  decisionLoading.value = true
  try {
    const response = await merchandisingService.decideDiscountRequests({
      request_ids: selectedRequests.value.map((request) => Number(request.id)),
      decision,
      ...(decision === 'approve' ? { starts_at: dateTimeForApi(startsAt.value), ends_at: dateTimeForApi(endsAt.value, true) } : { reason: rejectionReason.value.trim() }),
    })
    toast.add({ severity: 'success', summary: decision === 'approve' ? 'Requests approved' : 'Requests rejected', detail: response?.message || 'Discount requests were processed.', life: 4000 })
    approvalVisible.value = false
    rejectionVisible.value = false
    selectedRequests.value = []
    rejectionReason.value = ''
    await loadRequests()
  } catch (cause: any) {
    const fieldErrors = Object.values(cause?.response?.data?.errors || {}).flat() as string[]
    toast.add({ severity: 'error', summary: 'Could not process requests', detail: fieldErrors[0] || cause?.response?.data?.message || 'Please refresh and try again.', life: 5000 })
  } finally {
    decisionLoading.value = false
  }
}
const label = (value: string) => String(value || '').replaceAll('_', ' ').replace(/\b\w/g, char => char.toUpperCase())
const statusSeverity = (value: string) => ({ pending: 'warn', approved: 'success', rejected: 'danger' }[value] || 'secondary')
const discountLabel = (request: any) => request.discount_type === 'percentage'
  ? `${Number(request.discount_value)}% off`
  : `${money(request.discount_value)} off`
const money = (value: any) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
const date = (value: string) => value ? new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '-'
const dateTime = (value: string) => value ? new Date(value).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '-'

onMounted(loadRequests)
</script>
