<template>
  <div class="space-y-4 text-xs">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold text-slate-900">Warehouse Reorder</h1>
        <p class="mt-1 text-slate-500">Set warehouse thresholds and review replenishment suggestions.</p>
      </div>
      <div v-if="tab === 'suggestions'" class="flex flex-wrap gap-2">
        <Button v-if="selectedSuggestionIds.length && canCreatePr" :label="`Approve & Create PR (${selectedSuggestionIds.length})`" icon="pi pi-file-plus" severity="warn" size="small" @click="openPrPreview" />
      </div>
      <Button v-else label="Add Rule" severity="warn" size="small" @click="openRule()" />
    </div>

    <Card class="border border-gray-200">
      <template #content>
        <Tabs v-model:value="tab">
          <TabList><Tab value="suggestions">Suggestions</Tab><Tab value="rules">Rules</Tab></TabList>
          <TabPanels>
            <TabPanel value="suggestions">
              <div class="mb-3 rounded-lg border border-blue-100 bg-blue-50/70 px-3 py-2 text-xs leading-5 text-blue-900">
                Suggestions compare branch available stock with the applied reorder rule. When enabled and data is available, demand uses 30-day completed POS and delivered e-commerce sales, net of recorded returns. Select “Why this suggestion?” to inspect the inputs and target.
              </div>
              <div class="mb-3 flex justify-end">
                <Select v-model="status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="All statuses" showClear size="small" class="w-44" @change="loadSuggestions" />
              </div>
              <DataTable :value="suggestions" :loading="loading" dataKey="id" rowHover size="small">
                <template #empty>No reorder suggestions for this warehouse.</template>
                <Column headerStyle="width: 3rem"><template #header>
                  <Checkbox :modelValue="allPendingSelected" :binary="true" :disabled="!pendingSuggestionIds.length || !canCreatePr" @update:modelValue="togglePagePending" />
                </template><template #body="{ data }">
                  <Checkbox v-if="data.status === 'pending'" v-model="selectedSuggestionIds" :value="Number(data.id)" :disabled="!canCreatePr" />
                </template></Column>
                <Column header="Product"><template #body="{ data }"><div class="font-medium text-slate-900">{{ data.product?.product_name || '-' }}</div><div class="text-slate-500">{{ data.variation?.variation_name || data.product?.sku || '' }}</div></template></Column>
                <Column header="Available"><template #body="{ data }">{{ formatQuantity(data.current_stock) }}</template></Column>
                <Column header="Suggested"><template #body="{ data }">{{ formatQuantity(data.suggested_quantity) }}</template></Column>
                <Column header="Decision support" style="min-width: 220px"><template #body="{ data }">
                  <div class="space-y-1.5">
                    <Badge :value="sourceLabel(data)" severity="info" />
                    <div class="text-slate-700">{{ ruleLabel(data) }} · 30d sales {{ formatMetric(calculation(data).units_sold, 2) }}</div>
                    <div class="text-slate-700">Trigger {{ formatMetric(calculation(data).reorder_trigger) }} <span class="text-slate-400">→</span> Target {{ formatMetric(calculation(data).target_stock) }}</div>
                    <Button label="Why this suggestion?" icon="pi pi-info-circle" severity="secondary" size="small" text @click="openDecision(data)" />
                  </div>
                </template></Column>
                <Column header="Priority"><template #body="{ data }"><Badge :value="statusLabel(data.priority)" :severity="priorityColor(data.priority)" /></template></Column>
                <Column header="Status"><template #body="{ data }"><Badge :value="statusLabel(data.status)" :severity="statusColor(data.status)" /></template></Column>
                <Column header="Action"><template #body="{ data }"><div class="flex flex-wrap items-center gap-1"><Button v-if="data.metadata?.inventory_item_id && data.metadata?.forecast_enabled !== false" label="View forecast" icon="pi pi-chart-line" severity="secondary" size="small" text @click="viewForecast(data)" /><Button v-if="data.status === 'pending'" label="Approve & Create PR" severity="warn" size="small" text :disabled="!canCreatePr" @click="approve(data)" /></div></template></Column>
              </DataTable>
              <Paginator v-if="suggestionTotal > 15" :rows="15" :totalRecords="suggestionTotal" :first="(suggestionPage - 1) * 15" @page="suggestionPage = $event.page + 1; loadSuggestions()" />
            </TabPanel>
            <TabPanel value="rules">
              <DataTable :value="rules" :loading="loading" dataKey="id" rowHover size="small">
                <template #empty>No warehouse reorder rules yet. Add one to define a product's threshold.</template>
                <Column header="Product"><template #body="{ data }">{{ data.product?.product_name || '-' }}</template></Column>
                <Column header="Basis"><template #body="{ data }">{{ basisLabel(data.basis_type) }}</template></Column>
                <Column header="Reorder at"><template #body="{ data }">{{ formatQuantity(data.reorder_point) }}</template></Column>
                <Column header="Refill quantity"><template #body="{ data }">{{ formatQuantity(data.reorder_quantity) }}</template></Column>
                <Column header="Action"><template #body="{ data }"><Button label="Edit" severity="warn" size="small" text @click="openRule(data)" /></template></Column>
              </DataTable>
              <Paginator v-if="ruleTotal > 15" :rows="15" :totalRecords="ruleTotal" :first="(rulePage - 1) * 15" @page="rulePage = $event.page + 1; loadRules()" />
            </TabPanel>
          </TabPanels>
        </Tabs>
      </template>
    </Card>

    <Dialog v-model:visible="ruleDialog" modal header="Warehouse Reorder Rule" class="w-full max-w-lg">
      <div class="grid gap-3 text-xs">
        <label>Product <span class="text-red-500">*</span></label>
        <Select v-model="form.product_id" :options="products" optionLabel="product_name" optionValue="id" filter size="small" placeholder="Select warehouse product" :disabled="Boolean(editingId)" />
        <label>Calculation basis</label>
        <Select v-model="form.basis_type" :options="basisOptions" optionLabel="label" optionValue="value" size="small" />
        <label>Reorder point</label><InputNumber v-model="form.reorder_point" :min="0" :maxFractionDigits="0" size="small" fluid />
        <label>Refill quantity</label><InputNumber v-model="form.reorder_quantity" :min="1" :maxFractionDigits="0" size="small" fluid />
        <label>Safety stock</label><InputNumber v-model="form.safety_stock" :min="0" :maxFractionDigits="0" size="small" fluid />
        <label>Maximum stock</label><InputNumber v-model="form.maximum_stock" :min="0" :maxFractionDigits="0" size="small" fluid />
        <template v-if="form.basis_type === 'demand_lead_time'">
          <label>Configured average daily demand (optional)</label><InputNumber v-model="form.avg_daily_demand" :min="0" :maxFractionDigits="2" size="small" fluid />
          <small class="-mt-2 text-slate-500">Used only when 30-day sales history is not available.</small>
          <label>Lead time (days)</label><InputNumber v-model="form.lead_time_days" :min="0" size="small" fluid />
          <label>Review period (days)</label><InputNumber v-model="form.review_period_days" :min="0" size="small" fluid />
        </template>
      </div>
      <template #footer><Button label="Cancel" severity="secondary" size="small" text @click="ruleDialog = false" /><Button label="Save Rule" severity="warn" size="small" :loading="saving" @click="saveRule" /></template>
    </Dialog>
    <Dialog v-model:visible="decisionDialogVisible" modal header="Why this reorder was suggested" :style="{ width: 'min(96vw, 48rem)' }">
      <template v-if="decisionSuggestion">
        <div class="mb-4 rounded-xl border border-orange-200 bg-orange-50 p-4">
          <p class="font-semibold text-slate-900">{{ decisionSuggestion.product?.product_name || 'Product' }}<span v-if="decisionSuggestion.variation?.variation_name"> — {{ decisionSuggestion.variation.variation_name }}</span></p>
          <p class="mt-1 text-sm text-slate-700">{{ decisionSuggestion.reason || 'No explanation was saved for this suggestion.' }}</p>
        </div>
        <div class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
          <div v-for="metric in decisionMetrics" :key="metric.label" class="rounded-lg border border-slate-200 bg-white p-3">
            <p class="text-xs text-slate-500">{{ metric.label }}</p><p class="mt-1 font-semibold text-slate-900">{{ metric.value }}</p>
          </div>
        </div>
        <div class="mt-4 rounded-lg bg-slate-50 p-3 text-xs leading-5 text-slate-600">
          <p class="font-semibold text-slate-800">How to read this</p>
          <p>{{ formulaExplanation(decisionSuggestion) }}</p>
          <p class="mt-2">Priority is a stock-risk band based on how current stock compares with the trigger: Critical at zero or at/below 25%, High at/below 50%, Medium at/below 75%, otherwise Low.</p>
        </div>
      </template>
      <template #footer><Button label="Close" severity="secondary" outlined size="small" @click="decisionDialogVisible = false" /></template>
    </Dialog>
    <Dialog v-model:visible="prPreviewVisible" modal header="Review Purchase Requisition" :closable="!creatingPrs" :closeOnEscape="!creatingPrs" :dismissableMask="!creatingPrs" :style="{ width: 'min(96vw, 60rem)' }">
      <div class="space-y-4">
        <p class="text-sm text-slate-600">Review the selected pending suggestions. Approval creates draft purchase requisitions for this warehouse branch.</p>
        <DataTable :value="selectedSuggestionRows" size="small" responsiveLayout="scroll">
          <Column header="Product"><template #body="{ data }"><div class="font-medium text-slate-900">{{ data.product?.product_name || 'Unknown product' }}</div><div class="text-slate-500">{{ data.variation?.variation_name || data.variation?.variation_sku || data.product?.sku || '' }}</div></template></Column>
          <Column header="Available"><template #body="{ data }">{{ formatQuantity(data.current_stock) }}</template></Column>
          <Column header="Order quantity"><template #body="{ data }">{{ formatQuantity(data.suggested_quantity) }}</template></Column>
          <Column header="Calculation"><template #body="{ data }"><div class="text-xs leading-5"><div class="font-medium text-slate-800">{{ sourceLabel(data) }}</div><div>Trigger {{ formatMetric(calculation(data).reorder_trigger) }} · Target {{ formatMetric(calculation(data).target_stock) }}</div><template v-if="isDemandDriven(data)"><div>30-day sales {{ formatMetric(calculation(data).units_sold, 2) }} · Daily {{ formatMetric(calculation(data).average_daily_demand, 2) }}</div><div>Lead {{ formatMetric(calculation(data).lead_time_days) }}d · Safety {{ formatMetric(calculation(data).safety_stock) }}</div></template><div v-else class="text-slate-500">Demand, lead time, and review period not used<span v-if="(calculation(data).trigger_type || data.metadata?.trigger_type) !== 'safety_stock'">; safety stock not used</span></div><button type="button" class="text-orange-700 underline underline-offset-2" @click="openDecision(data)">View reasoning</button></div></template></Column>
          <Column header="Est. line cost"><template #body="{ data }">{{ money(data.estimated_cost) }}</template></Column>
        </DataTable>
        <div class="flex justify-end border-t border-slate-200 pt-3 text-sm">
          <span class="mr-3 text-slate-500">Estimated total</span><span class="font-semibold text-slate-900">{{ money(selectedEstimatedTotal) }}</span>
        </div>
      </div>
      <template #footer>
        <Button label="Back" severity="secondary" outlined size="small" @click="prPreviewVisible = false" />
        <Button label="Approve & Create Draft PRs" icon="pi pi-check" severity="warn" size="small" :loading="creatingPrs" :disabled="!selectedSuggestionRows.length" @click="confirmCreateSelectedPrs" />
      </template>
    </Dialog>
    <ConfirmDialog />
    <Toast />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import Paginator from 'primevue/paginator'
import { useRouter } from 'vue-router'

const api = '/api/warehouse/reorder'
const confirm = useConfirm()
const toast = useToast()
const authStore = useAuthStore()
const router = useRouter()
const tab = ref('suggestions')
const status = ref(null)
const statusOptions = [{ label: 'Pending', value: 'pending' }, { label: 'Approved', value: 'approved' }, { label: 'Rejected', value: 'rejected' }]
const basisOptions = [{ label: 'Reorder point', value: 'reorder_point' }, { label: 'Demand and lead time', value: 'demand_lead_time' }]
const loading = ref(false)
const saving = ref(false)
const creatingPrs = ref(false)
const prPreviewVisible = ref(false)
const selectedSuggestionIds = ref<number[]>([])
const ruleDialog = ref(false)
const editingId = ref<number | null>(null)
const rules = ref<any[]>([])
const suggestions = ref<any[]>([])
const products = ref<any[]>([])
const rulePage = ref(1)
const suggestionPage = ref(1)
const ruleTotal = ref(0)
const suggestionTotal = ref(0)
const form = reactive<any>({ product_id: null, basis_type: 'reorder_point', reorder_point: 10, reorder_quantity: 15, safety_stock: 0, maximum_stock: null, avg_daily_demand: null, lead_time_days: 7, review_period_days: 7 })
const errorText = (error: any) => error.response?.data?.message || 'Please try again.'
const priorityColor = (priority: string) => priority === 'critical' ? 'danger' : priority === 'high' ? 'warn' : 'info'
const canCreatePr = computed(() => authStore.hasPermission('warehouse.requisition.manage') || authStore.hasPermission('warehouse.purchase-requisitions.manage'))
const statusLabel = (value: any) => String(value || '').replace(/[_-]+/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
const statusColor = (value: any) => ({ pending: 'warn', approved: 'success', rejected: 'danger', implemented: 'info', cancelled: 'secondary' } as Record<string, string>)[String(value || '').toLowerCase()] || 'secondary'
const basisLabel = (value: any) => value === 'demand_lead_time' ? 'Demand and lead time' : 'Reorder point'
const formatQuantity = (value: any) => Number(value || 0).toLocaleString('en-PH', { maximumFractionDigits: 0 })
const formatMetric = (value: any, digits = 0) => value === null || value === undefined || value === ''
  ? '—'
  : Number(value).toLocaleString('en-PH', { maximumFractionDigits: digits })
const formatWithUnit = (value: any, unit: string, digits = 0) => {
  const formatted = formatMetric(value, digits)
  return formatted === '—' ? formatted : `${formatted} ${unit}`
}
const money = (value: any) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 2 }).format(Number(value || 0))
const calculation = (row: any) => row?.metadata?.calculation || {}
const sourceLabel = (row: any) => {
  const labels: Record<string, string> = {
    sales_30_day: 'Recent sales (30 days)',
    configured_average_demand: 'Configured average demand',
    configured_reorder_rule: 'Configured reorder rule',
    branch_inventory_reorder_point: 'Branch inventory reorder point',
  }
  return labels[row?.metadata?.source] || 'Saved suggestion data'
}
const ruleLabel = (row: any) => row?.reorder_rule_id
  ? (row?.reorder_rule ? basisLabel(row.reorder_rule.basis_type) : 'Rule details unavailable')
  : 'Branch inventory settings'
const isDemandDriven = (row: any) => ['sales_30_day', 'configured_average_demand'].includes(row?.metadata?.source)
const formulaExplanation = (row: any) => {
  const source = row?.metadata?.source
  const basis = row?.metadata?.basis_type
  const forecastEnabled = row?.metadata?.forecast_enabled
  if (source === 'sales_30_day' || source === 'configured_average_demand') {
    return 'Trigger = average daily demand × supplier lead time + safety stock. Target = average daily demand × (lead time + review period) + safety stock. The suggested quantity fills the gap from current stock to that target.'
  }
  if (basis === 'demand_lead_time' && forecastEnabled === false) {
    return 'Demand-based forecasting is unavailable for this store plan, so this suggestion falls back to the branch inventory reorder point. The configured refill quantity or maximum stock still determines the target when present.'
  }
  return 'The suggestion triggers when available stock reaches the configured reorder point. The target uses maximum stock when set; otherwise it uses current stock plus refill quantity, or twice the reorder point when no refill quantity is configured.'
}
const pendingSuggestionIds = computed(() => suggestions.value.filter(row => row.status === 'pending').map(row => Number(row.id)))
const allPendingSelected = computed(() => pendingSuggestionIds.value.length > 0 && pendingSuggestionIds.value.every(id => selectedSuggestionIds.value.includes(id)))
const selectedSuggestionRows = computed(() => suggestions.value.filter(row => selectedSuggestionIds.value.includes(Number(row.id)) && row.status === 'pending'))
const selectedEstimatedTotal = computed(() => selectedSuggestionRows.value.reduce((sum, row) => sum + Number(row.estimated_cost || 0), 0))
const decisionDialogVisible = ref(false)
const decisionSuggestion = ref<any | null>(null)
const decisionMetrics = computed(() => {
  const row = decisionSuggestion.value
  if (!row) return []
  const calc = calculation(row)
  const rule = row.reorder_rule
  const metricOrRule = (key: string) => calc[key] ?? rule?.[key]
  const demandDriven = isDemandDriven(row)
  const triggerType = calc.trigger_type || row?.metadata?.trigger_type || rule?.trigger_type || 'reorder_point'
  const maximumStock = Number(metricOrRule('maximum_stock') || 0)
  const refillQuantity = Number(metricOrRule('configured_refill_quantity') ?? rule?.reorder_quantity ?? 0)
  const unused = 'Not used for this suggestion'
  return [
    { label: 'Calculation source', value: sourceLabel(row) },
    { label: 'Applied rule', value: ruleLabel(row) },
    { label: 'Available stock', value: formatMetric(row.current_stock) },
    { label: 'Units sold · 30 days', value: demandDriven ? formatMetric(calc.units_sold, 2) : unused },
    { label: 'Average daily demand', value: demandDriven ? formatWithUnit(calc.average_daily_demand, 'units/day', 2) : unused },
    { label: 'Configured average daily demand', value: row?.metadata?.source === 'configured_average_demand' ? formatWithUnit(rule?.avg_daily_demand ?? calc.average_daily_demand, 'units/day', 2) : unused },
    { label: 'Configured reorder point', value: !demandDriven && triggerType !== 'safety_stock' ? formatWithUnit(metricOrRule('configured_reorder_point') ?? rule?.reorder_point, 'units') : unused },
    { label: 'Configured refill quantity', value: !demandDriven && maximumStock <= 0 && refillQuantity > 0 ? formatWithUnit(refillQuantity, 'units') : unused },
    { label: 'Maximum stock', value: !demandDriven && maximumStock > 0 ? formatWithUnit(maximumStock, 'units') : unused },
    { label: 'Reorder trigger', value: formatWithUnit(calc.reorder_trigger, 'units') },
    { label: 'Target stock', value: formatWithUnit(calc.target_stock, 'units') },
    { label: 'Supplier lead time', value: demandDriven ? formatWithUnit(calc.lead_time_days ?? rule?.lead_time_days, 'days') : unused },
    { label: 'Review period', value: demandDriven ? formatWithUnit(calc.review_period_days ?? rule?.review_period_days, 'days') : unused },
    { label: 'Safety stock', value: demandDriven || triggerType === 'safety_stock' ? formatWithUnit(calc.safety_stock ?? rule?.safety_stock, 'units') : unused },
    { label: 'Suggested order quantity', value: formatWithUnit(row.suggested_quantity, 'units') },
  ]
})

function togglePagePending(checked: boolean) {
  const currentPageIds = new Set(pendingSuggestionIds.value)
  selectedSuggestionIds.value = checked
    ? [...new Set([...selectedSuggestionIds.value.filter(id => !currentPageIds.has(id)), ...currentPageIds])]
    : selectedSuggestionIds.value.filter(id => !currentPageIds.has(id))
}

async function loadRules() {
  loading.value = true
  try {
    const { data } = await axios.get(`${api}/rules`, { params: { page: rulePage.value } })
    rules.value = data.data.data
    ruleTotal.value = data.data.total
  } catch (error) { toast.add({ severity: 'error', summary: 'Rules unavailable', detail: errorText(error), life: 4000 }) }
  finally { loading.value = false }
}
async function loadSuggestions() {
  selectedSuggestionIds.value = []
  loading.value = true
  try {
    const { data } = await axios.get(`${api}/suggestions`, { params: { page: suggestionPage.value, status: status.value } })
    suggestions.value = data.data.data
    suggestionTotal.value = data.data.total
  } catch (error) { toast.add({ severity: 'error', summary: 'Suggestions unavailable', detail: errorText(error), life: 4000 }) }
  finally { loading.value = false }
}
async function openRule(rule?: any) {
  editingId.value = rule?.id || null
  Object.assign(form, { product_id: rule?.product_id || null, basis_type: rule?.basis_type || 'reorder_point', reorder_point: Number(rule?.reorder_point ?? 10), reorder_quantity: Number(rule?.reorder_quantity ?? 15), safety_stock: Number(rule?.safety_stock ?? 0), maximum_stock: rule?.maximum_stock == null ? null : Number(rule.maximum_stock), avg_daily_demand: rule?.avg_daily_demand == null ? null : Number(rule.avg_daily_demand), lead_time_days: Number(rule?.lead_time_days ?? 7), review_period_days: Number(rule?.review_period_days ?? 7) })
  try { const { data } = await axios.get(`${api}/options`); products.value = data.data }
  catch (error) { toast.add({ severity: 'error', summary: 'Products unavailable', detail: errorText(error), life: 4000 }); return }
  ruleDialog.value = true
}
async function saveRule() {
  if (!form.product_id || form.reorder_quantity < 1) { toast.add({ severity: 'warn', summary: 'Complete required fields', life: 3000 }); return }
  saving.value = true
  try { await axios.post(`${api}/rules`, form); ruleDialog.value = false; await loadRules(); toast.add({ severity: 'success', summary: 'Rule saved', life: 3000 }) }
  catch (error) { toast.add({ severity: 'error', summary: 'Could not save rule', detail: errorText(error), life: 4000 }) }
  finally { saving.value = false }
}
function openPrPreview() {
  if (!selectedSuggestionRows.value.length) return
  prPreviewVisible.value = true
}
function viewForecast(row: any) {
  router.push({
    name: 'warehouse.forecasting',
    query: {
      inventory_id: Number(row.metadata?.inventory_item_id),
      branch_id: Number(row.branch_id),
    },
  })
}
function openDecision(row: any) {
  decisionSuggestion.value = row
  decisionDialogVisible.value = true
}
function confirmCreateSelectedPrs() {
  if (!selectedSuggestionRows.value.length) return
  confirm.require({
    message: `Approve ${selectedSuggestionRows.value.length} selected suggestions and create draft purchase requisition(s)?`,
    header: 'Confirm PR creation',
    rejectProps: { label: 'Cancel', severity: 'secondary', outlined: true },
    acceptProps: { label: 'Approve & Create PRs', severity: 'warn' },
    accept: async () => {
    creatingPrs.value = true
    try {
      const { data } = await axios.post(`${api}/suggestions/create-prs`, { suggestion_ids: selectedSuggestionRows.value.map(row => Number(row.id)) })
      prPreviewVisible.value = false
      selectedSuggestionIds.value = []
      await loadSuggestions()
      toast.add({ severity: 'success', summary: 'Purchase requisitions created', detail: data.message || 'Draft purchase requisitions were created.', life: 4000 })
    } catch (error) { toast.add({ severity: 'error', summary: 'Could not create purchase requisitions', detail: errorText(error), life: 5000 }) }
    finally { creatingPrs.value = false }
    },
  })
}
function approve(row: any) {
  selectedSuggestionIds.value = [Number(row.id)]
  openPrPreview()
}
onMounted(() => { loadRules(); loadSuggestions() })
</script>
