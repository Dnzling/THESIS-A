<template>
  <div class="p-4 min-h-screen">
    <div class="mb-4 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <Button icon="pi pi-arrow-left" text severity="secondary" @click="goBack" />
        <div>
          <h1 class="text-xl font-bold text-gray-800">Reorder Suggestion Detail</h1>
          <p class="text-xs text-gray-500 mt-0.5">Review this low-stock suggestion and create a restock request.</p>
        </div>
      </div>
    </div>

    <div v-if="loading" class="space-y-3">
      <Skeleton height="8rem" />
      <Skeleton height="14rem" />
    </div>

    <div v-else-if="suggestion" class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <Card>
          <template #content>
            <p class="text-xs text-gray-500">Product</p>
            <p class="font-semibold">{{ suggestion.product_name || '-' }}</p>
            <p class="text-xs text-gray-500">{{ suggestion.product_sku || '-' }}</p>
            <p v-if="suggestion.variation_name" class="mt-1 text-xs font-medium text-orange-700">
              Variant: {{ suggestion.variation_name }}
            </p>
          </template>
        </Card>
        <Card>
          <template #content>
            <p class="text-xs text-gray-500">Branch</p>
            <p class="font-semibold">{{ suggestion.branch_name || '-' }}</p>
            <p class="text-xs text-gray-500">Current Stock: {{ formatQuantity(suggestion.current_stock) }}</p>
          </template>
        </Card>
        <Card>
          <template #content>
            <p class="text-xs text-gray-500">Suggested Qty</p>
            <p class="font-semibold">{{ formatQuantity(suggestion.suggested_quantity) }}</p>
            <p class="text-xs text-gray-500">Est Cost: {{ formatCurrency(suggestion.estimated_cost || 0) }}</p>
          </template>
        </Card>
        <Card>
          <template #content>
            <p class="text-xs text-gray-500">Status</p>
            <Tag :value="capitalize(suggestion.status)" :severity="statusSeverity(suggestion.status)" />
            <div class="mt-2">
              <Tag :value="capitalize(suggestion.priority)" :severity="prioritySeverity(suggestion.priority)" />
            </div>
          </template>
        </Card>
      </div>

      <Card>
        <template #title>How this quantity was calculated</template>
        <template #content>
          <p class="text-sm text-gray-700">{{ suggestion.reason || 'No reason provided.' }}</p>
          <p v-if="suggestion.calculation" class="mt-2 text-xs leading-5 text-slate-500">{{ calculationExplanation }}</p>
          <div v-if="suggestion.calculation" class="mt-4 grid grid-cols-2 gap-3 text-xs sm:grid-cols-3 lg:grid-cols-4">
            <div v-for="metric in calculationMetrics" :key="metric.label" class="rounded-lg bg-slate-50 p-3">
              <div class="text-slate-500">{{ metric.label }}</div>
              <div class="mt-1 font-semibold text-slate-800">{{ metric.value }}</div>
            </div>
          </div>
          <div v-if="suggestion.calculation" class="mt-4 rounded-lg bg-slate-50 p-3 text-xs leading-5 text-slate-600">
            Priority is a stock-risk band based on current stock versus the trigger: Critical at zero or at/below 25%, High at/below 50%, Medium at/below 75%, otherwise Low.
          </div>
        </template>
      </Card>

      <Card>
        <template #title>Timeline</template>
        <template #content>
          <Timeline :value="timelineEvents" align="left">
            <template #content="{ item }">
              <div class="text-sm">
                <div class="font-medium">{{ item.title }}</div>
                <div class="text-gray-500 text-xs">{{ item.time }}</div>
                <div v-if="item.note" class="text-gray-600 mt-1">{{ item.note }}</div>
              </div>
            </template>
          </Timeline>
        </template>
      </Card>
      <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 pt-4">
        <Button v-if="suggestion.status === 'pending'" label="Reject" icon="pi pi-times" size="small" severity="danger" outlined @click="openAction('reject')" />
        <Button v-if="suggestion.status === 'pending' && suggestion.reorder_destination === 'pr' && canManagePr" label="Approve & Create PR" icon="pi pi-check" size="small" severity="warn" @click="openAction('approve')" />
        <Button v-if="suggestion.status === 'pending' && suggestion.reorder_destination === 'po' && canManagePo" label="Approve & Create PO" icon="pi pi-shopping-cart" size="small" severity="warn" @click="inertiaRouter.visit(`/inventory/purchase-orders/create?reorder_suggestion_id=${suggestion.id}`)" />
        <Button v-if="suggestion.status === 'pending' && suggestion.reorder_destination === 'warehouse' && canCreateTransfer" label="Request from Warehouse" icon="pi pi-warehouse" size="small" severity="warn" @click="inertiaRouter.visit(`/inventory/stock-requests/create?type=transfer&reorder_suggestion_id=${suggestion.id}`)" />
        <Button v-if="suggestion.metadata?.stock_transfer_id" label="View Warehouse Request" icon="pi pi-arrow-right-arrow-left" size="small" severity="secondary" outlined @click="router.push({ name: 'inventory.stock-requests.detail', params: { type: 'transfer', id: suggestion.metadata.stock_transfer_id } })" />
        <Button v-if="suggestion.metadata?.purchase_requisition_id" label="View Inventory PR" icon="pi pi-file" size="small" severity="warn" @click="router.push({ name: 'inventory.requisites.detail', params: { id: suggestion.metadata.purchase_requisition_id } })" />
        <Button v-if="suggestion.metadata?.purchase_order_id" label="View Purchase Order" icon="pi pi-file" size="small" severity="warn" @click="inertiaRouter.visit(`/inventory/purchase-orders/${suggestion.metadata.purchase_order_id}`)" />
      </div>
    </div>

    <Dialog v-model:visible="actionDialog.visible" :header="actionDialog.title" modal class="w-full max-w-lg">
      <div class="space-y-3">
        <p class="text-sm text-gray-600">{{ actionDialog.description }}</p>
        <Textarea v-model="actionDialog.notes" rows="4" class="w-full" placeholder="Optional notes" />
      </div>
      <template #footer>
        <Button label="Cancel" text severity="secondary" @click="actionDialog.visible = false" />
        <Button :label="actionDialog.confirmLabel" :severity="actionDialog.severity" :loading="submitting" @click="submitAction" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { router as inertiaRouter } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import inventoryService from '@/services/inventory.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const canManagePr = computed(() => authStore.hasPermission('inventory.requisites.manage'))
const canManagePo = computed(() => authStore.hasPermission('inventory.purchase_orders.manage'))
const canCreateTransfer = computed(() => authStore.hasPermission('inventory.transfers.create') || authStore.hasPermission('inventory.transfers.manage'))

const loading = ref(false)
const submitting = ref(false)
const suggestion = ref<any | null>(null)

const actionDialog = reactive({
  visible: false,
  type: '' as 'approve' | 'reject' | '',
  title: '',
  description: '',
  confirmLabel: 'Confirm',
  severity: 'info' as 'success' | 'danger' | 'warn' | 'info',
  notes: '',
})

const suggestionId = computed(() => Number(route.params.id))

const normalizeSuggestion = (row: any) => ({
  ...row,
  product_name: row?.product?.product_name || row?.product?.name || '-',
  product_sku: row?.variation?.variation_sku || row?.product?.sku || '-',
  variation_name: row?.variation?.variation_name || null,
  calculation: row?.metadata?.calculation || null,
  calculation_source: row?.metadata?.source || null,
  applied_basis_type: row?.metadata?.basis_type || row?.reorder_rule?.basis_type || null,
  forecast_enabled: row?.metadata?.forecast_enabled,
  reorder_rule: row?.reorder_rule || null,
  branch_name: row?.branch?.name || '-',
})

const calculationSourceLabel = computed(() => {
  const source = suggestion.value?.calculation_source
  if (source === 'sales_30_day') return 'Recent sales'
  if (source === 'configured_average_demand') return 'Configured demand'
  if (source === 'branch_inventory_reorder_point') return 'Branch reorder point'
  if (source === 'configured_reorder_rule') return 'Reorder rule'
  return source || 'Manual'
})

const isDemandDriven = computed(() => ['sales_30_day', 'configured_average_demand'].includes(suggestion.value?.calculation_source))
const appliedTriggerType = computed(() => suggestion.value?.calculation?.trigger_type || suggestion.value?.reorder_rule?.trigger_type || 'reorder_point')
const calculationExplanation = computed(() => {
  if (isDemandDriven.value) {
    return 'Demand-based calculation: average daily demand Ã— supplier lead time + safety stock sets the reorder trigger. The target covers lead time plus review period and safety stock. The order quantity fills the gap from current stock to that target.'
  }
  if (suggestion.value?.applied_basis_type === 'demand_lead_time' && suggestion.value?.forecast_enabled === false) {
    return 'Demand-based forecasting is unavailable for this store plan, so this suggestion falls back to the branch reorder point. The configured refill quantity or maximum stock determines the target when present.'
  }
  return 'Reorder-point calculation: the suggestion triggers when available stock reaches the configured threshold. The target uses maximum stock when set; otherwise it uses current stock plus refill quantity, or twice the reorder point when no refill quantity is set.'
})

const formatMetric = (value: any, digits = 0) => value === null || value === undefined || value === ''
  ? 'â€”'
  : Number(value).toLocaleString('en-PH', { maximumFractionDigits: digits })
const formatWithUnit = (value: any, unit: string, digits = 0) => {
  const formatted = formatMetric(value, digits)
  return formatted === 'â€”' ? formatted : `${formatted} ${unit}`
}

const calculationMetrics = computed(() => {
  const row = suggestion.value
  const calc = row?.calculation
  if (!row || !calc) return []
  const rule = row.reorder_rule
  const demandDriven = isDemandDriven.value
  const triggerType = appliedTriggerType.value
  const maxStock = Number(calc.maximum_stock ?? rule?.maximum_stock ?? 0)
  const refillQty = Number(calc.configured_refill_quantity ?? rule?.reorder_quantity ?? 0)
  const unused = 'Not used for this suggestion'
  return [
    { label: 'Calculation source', value: calculationSourceLabel.value },
    { label: 'Applied rule', value: row.applied_basis_type === 'demand_lead_time' ? 'Demand and lead time' : row.applied_basis_type === 'reorder_point' ? 'Reorder point' : 'Branch inventory settings' },
    { label: 'Available stock', value: formatMetric(row.current_stock) },
    { label: 'Units sold · 30 days', value: demandDriven ? formatMetric(calc.units_sold, 2) : unused },
    { label: 'Average daily demand', value: demandDriven ? formatWithUnit(calc.average_daily_demand, 'units/day', 2) : unused },
    { label: 'Configured average daily demand', value: row.calculation_source === 'configured_average_demand' ? formatWithUnit(rule?.avg_daily_demand ?? calc.average_daily_demand, 'units/day', 2) : unused },
    { label: 'Configured reorder point', value: !demandDriven && triggerType !== 'safety_stock' ? formatWithUnit(calc.configured_reorder_point ?? rule?.reorder_point, 'units') : unused },
    { label: 'Configured refill quantity', value: !demandDriven && maxStock <= 0 && refillQty > 0 ? formatWithUnit(refillQty, 'units') : unused },
    { label: 'Maximum stock', value: !demandDriven && maxStock > 0 ? formatWithUnit(maxStock, 'units') : unused },
    { label: 'Reorder trigger', value: formatWithUnit(calc.reorder_trigger, 'units') },
    { label: 'Target stock', value: formatWithUnit(calc.target_stock, 'units') },
    { label: 'Supplier lead time', value: demandDriven ? formatWithUnit(calc.lead_time_days ?? rule?.lead_time_days, 'days') : unused },
    { label: 'Review period', value: demandDriven ? formatWithUnit(calc.review_period_days ?? rule?.review_period_days, 'days') : unused },
    { label: 'Safety stock', value: demandDriven || triggerType === 'safety_stock' ? formatWithUnit(calc.safety_stock ?? rule?.safety_stock, 'units') : unused },
    { label: 'Suggested order quantity', value: formatWithUnit(row.suggested_quantity, 'units') },
  ]
})

const formatQuantity = (value: unknown) => new Intl.NumberFormat('en-PH', {
  maximumFractionDigits: 0,
}).format(Math.round(Number(value) || 0))

const formatDateTime = (value?: string | null) => {
  if (!value) return '-'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return '-'
  return d.toLocaleString('en-PH', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const formatCurrency = (amount: number) => new Intl.NumberFormat('en-PH', {
  style: 'currency',
  currency: 'PHP',
  minimumFractionDigits: 2,
}).format(Number(amount || 0))

const capitalize = (value: string) => {
  const text = String(value || '')
  return text ? `${text.charAt(0).toUpperCase()}${text.slice(1)}` : '-'
}

const statusSeverity = (status: string) => {
  const key = String(status || '').toLowerCase()
  if (key === 'approved') return 'success'
  if (key === 'rejected') return 'danger'
  if (key === 'implemented') return 'info'
  if (key === 'cancelled') return 'secondary'
  return 'warn'
}

const prioritySeverity = (priority: string) => {
  const key = String(priority || '').toLowerCase()
  if (key === 'critical') return 'danger'
  if (key === 'high') return 'warn'
  if (key === 'medium') return 'info'
  return 'secondary'
}

const timelineEvents = computed(() => {
  if (!suggestion.value) return []
  const s = suggestion.value
  const events = [
    {
      title: 'Suggestion Created',
      time: formatDateTime(s.suggested_at || s.created_at),
      note: s.reason || null,
    },
  ]

  if (s.approved_at) {
    events.push({
      title: s.status === 'rejected' ? 'Suggestion Rejected' : 'Suggestion Approved',
      time: formatDateTime(s.approved_at),
      note: s.approval_notes || null,
    })
  }

  if (s.implemented_at) {
    events.push({
      title: 'Suggestion Implemented',
      time: formatDateTime(s.implemented_at),
      note: s.implementation_notes || null,
    })
  }

  return events
})

const loadSuggestion = async () => {
  if (!suggestionId.value) return
  loading.value = true
  try {
    const response = await inventoryService.getReorderSuggestion(suggestionId.value)
    suggestion.value = normalizeSuggestion(response?.data)
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to load suggestion detail',
      life: 3000,
    })
    router.push({ name: 'inventory.reorder-suggestions' })
  } finally {
    loading.value = false
  }
}

const goBack = () => router.push({ name: 'inventory.reorder-suggestions' })

const openAction = (type: 'approve' | 'reject') => {
  actionDialog.type = type
  actionDialog.notes = ''
  actionDialog.visible = true

  if (type === 'approve') {
    actionDialog.title = 'Approve Suggestion'
    actionDialog.description = 'Approve this suggestion and create a draft Inventory PR. Submit the PR from Inventory Purchase Requisitions.'
    actionDialog.confirmLabel = 'Approve'
    actionDialog.severity = 'success'
    return
  }

  if (type === 'reject') {
    actionDialog.title = 'Reject Suggestion'
    actionDialog.description = 'Reject this suggestion with an optional note.'
    actionDialog.confirmLabel = 'Reject'
    actionDialog.severity = 'danger'
    return
  }
}

const submitAction = async () => {
  if (!actionDialog.type || !suggestionId.value) return

  submitting.value = true
  try {
    if (actionDialog.type === 'approve') {
      const response = await inventoryService.approveReorderSuggestion(suggestionId.value, actionDialog.notes || undefined)
      if (response?.action_required === 'create_purchase_order' && response?.url) {
        actionDialog.visible = false
        inertiaRouter.visit(response.url)
        return
      }
    } else if (actionDialog.type === 'reject') {
      await inventoryService.rejectReorderSuggestion(suggestionId.value, actionDialog.notes || undefined)
    }

    actionDialog.visible = false
    await loadSuggestion()
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: actionDialog.type === 'approve' ? 'Draft PR created in Inventory Purchase Requisitions.' : 'Suggestion rejected.',
      life: 2500,
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to update suggestion',
      life: 3000,
    })
  } finally {
    submitting.value = false
  }
}

onMounted(loadSuggestion)
</script>
