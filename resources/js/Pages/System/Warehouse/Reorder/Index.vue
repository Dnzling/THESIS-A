<template>
  <div class="space-y-4 text-xs">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold text-slate-900">Warehouse Reorder</h1>
        <p class="mt-1 text-slate-500">Set warehouse thresholds and review replenishment suggestions.</p>
      </div>
      <Button v-if="tab === 'suggestions'" label="Generate Suggestions" severity="warn" size="small" :loading="generating" @click="generate" />
      <Button v-else label="Add Rule" severity="warn" size="small" @click="openRule()" />
    </div>

    <Card>
      <template #content>
        <Tabs v-model:value="tab">
          <TabList><Tab value="suggestions">Suggestions</Tab><Tab value="rules">Rules</Tab></TabList>
          <TabPanels>
            <TabPanel value="suggestions">
              <div class="mb-3 flex justify-end">
                <Select v-model="status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="All statuses" showClear size="small" class="w-44" @change="loadSuggestions" />
              </div>
              <DataTable :value="suggestions" :loading="loading" dataKey="id" rowHover size="small">
                <template #empty>No reorder suggestions for this warehouse.</template>
                <Column header="Product"><template #body="{ data }"><div class="font-medium text-slate-900">{{ data.product?.product_name || '-' }}</div><div class="text-slate-500">{{ data.variation?.variation_name || data.product?.sku || '' }}</div></template></Column>
                <Column field="current_stock" header="Available" />
                <Column field="suggested_quantity" header="Suggested" />
                <Column header="Priority"><template #body="{ data }"><Badge :value="data.priority" :severity="priorityColor(data.priority)" /></template></Column>
                <Column header="Status"><template #body="{ data }"><Badge :value="data.status" :severity="data.status === 'approved' ? 'success' : 'warn'" /></template></Column>
                <Column header="Action"><template #body="{ data }"><Button v-if="data.status === 'pending'" label="Approve" severity="warn" size="small" text :loading="approving === data.id" @click="approve(data)" /></template></Column>
              </DataTable>
              <Paginator v-if="suggestionTotal > 15" :rows="15" :totalRecords="suggestionTotal" :first="(suggestionPage - 1) * 15" @page="suggestionPage = $event.page + 1; loadSuggestions()" />
            </TabPanel>
            <TabPanel value="rules">
              <DataTable :value="rules" :loading="loading" dataKey="id" rowHover size="small">
                <template #empty>No warehouse reorder rules yet. Add one to define a product's threshold.</template>
                <Column header="Product"><template #body="{ data }">{{ data.product?.product_name || '-' }}</template></Column>
                <Column field="basis_type" header="Basis" />
                <Column field="reorder_point" header="Reorder at" />
                <Column field="reorder_quantity" header="Refill quantity" />
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
        <label>Reorder point</label><InputNumber v-model="form.reorder_point" :min="0" size="small" fluid />
        <label>Refill quantity</label><InputNumber v-model="form.reorder_quantity" :min="1" size="small" fluid />
        <label>Safety stock</label><InputNumber v-model="form.safety_stock" :min="0" size="small" fluid />
        <label>Maximum stock</label><InputNumber v-model="form.maximum_stock" :min="0" size="small" fluid />
        <template v-if="form.basis_type === 'demand_lead_time'">
          <label>Lead time (days)</label><InputNumber v-model="form.lead_time_days" :min="0" size="small" fluid />
          <label>Review period (days)</label><InputNumber v-model="form.review_period_days" :min="0" size="small" fluid />
        </template>
      </div>
      <template #footer><Button label="Cancel" severity="secondary" size="small" text @click="ruleDialog = false" /><Button label="Save Rule" severity="warn" size="small" :loading="saving" @click="saveRule" /></template>
    </Dialog>
    <ConfirmDialog />
    <Toast />
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import axios from 'axios'
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

const api = '/api/warehouse/reorder'
const confirm = useConfirm()
const toast = useToast()
const tab = ref('suggestions')
const status = ref(null)
const statusOptions = [{ label: 'Pending', value: 'pending' }, { label: 'Approved', value: 'approved' }, { label: 'Rejected', value: 'rejected' }]
const basisOptions = [{ label: 'Reorder point', value: 'reorder_point' }, { label: 'Demand and lead time', value: 'demand_lead_time' }]
const loading = ref(false)
const generating = ref(false)
const saving = ref(false)
const approving = ref<number | null>(null)
const ruleDialog = ref(false)
const editingId = ref<number | null>(null)
const rules = ref<any[]>([])
const suggestions = ref<any[]>([])
const products = ref<any[]>([])
const rulePage = ref(1)
const suggestionPage = ref(1)
const ruleTotal = ref(0)
const suggestionTotal = ref(0)
const form = reactive<any>({ product_id: null, basis_type: 'reorder_point', reorder_point: 10, reorder_quantity: 15, safety_stock: 0, maximum_stock: null, lead_time_days: 7, review_period_days: 7 })
const errorText = (error: any) => error.response?.data?.message || 'Please try again.'
const priorityColor = (priority: string) => priority === 'critical' ? 'danger' : priority === 'high' ? 'warn' : 'info'

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
  Object.assign(form, { product_id: rule?.product_id || null, basis_type: rule?.basis_type || 'reorder_point', reorder_point: Number(rule?.reorder_point ?? 10), reorder_quantity: Number(rule?.reorder_quantity ?? 15), safety_stock: Number(rule?.safety_stock ?? 0), maximum_stock: rule?.maximum_stock == null ? null : Number(rule.maximum_stock), lead_time_days: Number(rule?.lead_time_days ?? 7), review_period_days: Number(rule?.review_period_days ?? 7) })
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
async function generate() {
  generating.value = true
  try { const { data } = await axios.post(`${api}/suggestions/generate`); await loadSuggestions(); toast.add({ severity: 'success', summary: `${data.data.total_generated} suggestions generated`, life: 3000 }) }
  catch (error) { toast.add({ severity: 'error', summary: 'Generation failed', detail: errorText(error), life: 4000 }) }
  finally { generating.value = false }
}
function approve(row: any) {
  confirm.require({ message: `Approve replenishment of ${row.suggested_quantity} units? This creates a draft PR.`, header: 'Approve suggestion', acceptLabel: 'Approve', rejectLabel: 'Cancel', accept: async () => {
    approving.value = row.id
    try { await axios.post(`${api}/suggestions/${row.id}/approve`); await loadSuggestions(); toast.add({ severity: 'success', summary: 'Suggestion approved', life: 3000 }) }
    catch (error) { toast.add({ severity: 'error', summary: 'Approval failed', detail: errorText(error), life: 4000 }) }
    finally { approving.value = null }
  } })
}
onMounted(() => { loadRules(); loadSuggestions() })
</script>
