<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <Card class="border border-gray-100 shadow-sm rounded-2xl">
      <template #content>
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900">CRM Leads</h1>
            <p class="text-sm text-gray-500">Manage leads and pipeline stage.</p>
          </div>
          <Button v-if="canManageCrm" severity="info" icon="pi pi-plus" label="New Lead" @click="openCreate" />
        </div>
      </template>
    </Card>

    <Card class="border border-gray-100 shadow-sm rounded-2xl">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
          <div class="md:col-span-8"><InputText v-model="filters.search" fluid placeholder="Search lead..." /></div>
          <div class="md:col-span-4"><Select v-model="filters.stage" :options="stageOptions" optionLabel="label" optionValue="value" showClear fluid placeholder="Stage" /></div>
        </div>
      </template>
    </Card>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card class="border border-gray-100 shadow-sm rounded-2xl"><template #content><p class="text-xs uppercase text-gray-500">Paid Payments</p><p class="text-2xl font-semibold">{{ paymentAnalytics.paid_payments }}</p></template></Card>
      <Card class="border border-gray-100 shadow-sm rounded-2xl"><template #content><p class="text-xs uppercase text-gray-500">Pending Payments</p><p class="text-2xl font-semibold">{{ paymentAnalytics.pending_payments }}</p></template></Card>
      <Card class="border border-gray-100 shadow-sm rounded-2xl"><template #content><p class="text-xs uppercase text-gray-500">Paid Amount</p><p class="text-2xl font-semibold">{{ money(paymentAnalytics.paid_amount) }}</p></template></Card>
      <Card class="border border-gray-100 shadow-sm rounded-2xl"><template #content><p class="text-xs uppercase text-gray-500">Conversion</p><p class="text-2xl font-semibold">{{ Number(paymentAnalytics.conversion_rate || 0).toFixed(2) }}%</p></template></Card>
    </div>

    <Card class="border border-gray-100 shadow-sm rounded-2xl">
      <template #content>
        <DataTable :value="leads" :loading="loading" stripedRows paginator lazy :rows="page.rows" :first="(page.current-1)*page.rows" :totalRecords="page.total" @page="onPage">
          <Column field="lead_code" header="Lead Code" />
          <Column field="full_name" header="Name" />
          <Column field="phone" header="Phone" />
          <Column field="estimated_value" header="Est. Value"><template #body="{data}">{{ money(data.estimated_value) }}</template></Column>
          <Column field="stage" header="Stage"><template #body="{data}"><Tag severity="info" :value="fmt(data.stage)" /></template></Column>
          <Column header="Actions">
            <template #body="{data}">
              <div class="flex items-center gap-2">
                <Select v-model="data.__stage" :options="stageOptions" optionLabel="label" optionValue="value" class="w-40" :disabled="!canManageCrm" />
                <Button text severity="info" label="Update" :disabled="!canManageCrm" @click="updateStage(data)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="dialog" modal header="Create Lead" class="w-full max-w-xl">
      <div class="grid grid-cols-1 gap-3">
        <InputText v-model="form.full_name" fluid placeholder="Full name" />
        <InputText v-model="form.email" fluid placeholder="Email" />
        <InputText v-model="form.phone" fluid placeholder="Phone" />
        <InputText v-model="form.source" fluid placeholder="Source (walk_in, social, referral...)" />
        <InputNumber v-model="form.estimated_value" fluid :min="0" mode="currency" currency="PHP" locale="en-PH" />
        <Textarea v-model="form.notes" rows="3" fluid placeholder="Notes" />
      </div>
      <template #footer>
        <Button text severity="secondary" label="Cancel" @click="dialog=false" />
        <Button severity="info" :loading="saving" label="Save Lead" @click="saveLead" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import salesService from '@/services/sales.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Dialog from 'primevue/dialog'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'

const toast = useToast()
const authStore = useAuthStore()
const loading = ref(false)
const saving = ref(false)
const leads = ref<any[]>([])
const dialog = ref(false)
const paymentAnalytics = ref<any>({
  paid_payments: 0,
  pending_payments: 0,
  paid_amount: 0,
  conversion_rate: 0,
})
const page = reactive({ current: 1, rows: 10, total: 0 })
const filters = reactive({ search: '', stage: null as string | null })
const stageOptions = [
  { label: 'New', value: 'new' },
  { label: 'Contacted', value: 'contacted' },
  { label: 'Qualified', value: 'qualified' },
  { label: 'Proposal', value: 'proposal' },
  { label: 'Won', value: 'won' },
  { label: 'Lost', value: 'lost' },
]
const form = reactive<any>({ full_name: '', email: '', phone: '', source: 'walk_in', estimated_value: 0, notes: '' })
const canManageCrm = authStore.hasPermission('sales.crm.manage')

const load = async () => {
  loading.value = true
  try {
    const res = await salesService.getLeads({ page: page.current, per_page: page.rows, search: filters.search || undefined, stage: filters.stage || undefined })
    const payload = res?.data
    leads.value = (payload?.data || []).map((r: any) => ({ ...r, __stage: r.stage }))
    page.total = Number(payload?.total || 0)
    const analytics = await salesService.getPaymentAnalytics()
    paymentAnalytics.value = analytics?.data || paymentAnalytics.value
  } finally { loading.value = false }
}
const onPage = (e: any) => { page.current = Number(e.page || 0) + 1; page.rows = Number(e.rows || 10); load() }
const openCreate = () => { dialog.value = true }
const saveLead = async () => {
  saving.value = true
  try {
    await salesService.createLead(form)
    dialog.value = false
    form.full_name = ''; form.email = ''; form.phone = ''; form.source = 'walk_in'; form.estimated_value = 0; form.notes = ''
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Lead created.', life: 2200 })
    load()
  } finally { saving.value = false }
}
const updateStage = async (row: any) => {
  if (!row.__stage || row.__stage === row.stage) return
  await salesService.updateLeadStage(row.id, { stage: row.__stage })
  toast.add({ severity: 'success', summary: 'Updated', detail: 'Lead stage updated.', life: 2000 })
  load()
}
const money = (v: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(v || 0))
const fmt = (v: string) => String(v || '').replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase())

watch(() => [filters.search, filters.stage], () => { page.current = 1; load() })
onMounted(load)
</script>
