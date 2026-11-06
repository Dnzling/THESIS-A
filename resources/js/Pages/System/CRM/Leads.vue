<template>
  <div class="module-dashboard dashboard--crm max-w-7xl mx-auto space-y-6">
    <Card class="dashboard-hero border border-gray-100 shadow-sm rounded-2xl">
      <template #content>
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900">Customers & Leads</h1>
            <p class="text-sm text-gray-500">Add individual or business customers and follow their sales journey.</p>
          </div>
          <Button v-if="canManageCrm" icon="pi pi-plus" label="Add Customer" @click="openCreate" />
        </div>
      </template>
    </Card>

    <Card class="border border-orange-100 bg-gradient-to-r from-orange-50 via-white to-amber-50 shadow-sm rounded-2xl">
      <template #content>
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
          <div class="max-w-md">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-orange-600">Lead process</p>
            <h2 class="mt-2 text-xl font-bold text-slate-900">Move every lead with a clear next step</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Record the customer interaction before advancing the stage. Qualified, proposal, won, and lost stages require a note for accountability.</p>
          </div>
          <div class="grid flex-1 gap-2 sm:grid-cols-3 lg:grid-cols-6">
            <div v-for="(stage, index) in processStages" :key="stage.value" class="relative rounded-xl border border-white bg-white/80 p-3 shadow-sm">
              <div class="flex items-center gap-2"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-orange-100 text-xs font-bold text-orange-700">{{ index + 1 }}</span><span class="text-xs font-bold text-slate-800">{{ stage.label }}</span></div>
              <p class="mt-2 text-[11px] leading-4 text-slate-500">{{ stage.help }}</p>
            </div>
          </div>
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

    <div v-if="loading" class="space-y-3"><Skeleton v-for="i in 5" :key="i" height="3.5rem" /></div>
    <div v-else-if="error" class="rounded-xl border border-red-200 bg-red-50 p-5 text-red-700">{{ error }} <Button text label="Retry" @click="load" /></div>
    <div v-else-if="!leads.length" class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center"><i class="pi pi-users text-3xl text-slate-400" /><p class="mt-3 font-medium text-slate-800">No customers or leads found</p><p class="text-sm text-slate-500">{{ filters.search || filters.stage ? 'Try another search or stage.' : 'Add a customer to start your CRM pipeline.' }}</p><Button v-if="canManageCrm && !filters.search && !filters.stage" class="mt-4" label="Add Customer" @click="openCreate" /></div>

    <Card v-if="!loading && !error && leads.length" class="border border-gray-100 shadow-sm rounded-2xl">
      <template #content>
        <DataTable :value="leads" :loading="loading" stripedRows paginator lazy :rows="page.rows" :first="(page.current-1)*page.rows" :totalRecords="page.total" @page="onPage">
          <Column field="lead_code" header="Lead Code" />
          <Column field="full_name" header="Name" />
          <Column field="company_name" header="Business"><template #body="{data}">{{ data.company_name || '—' }}</template></Column>
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

    <Dialog v-model:visible="dialog" modal header="Add Customer" class="w-full max-w-xl">
      <div class="grid grid-cols-1 gap-3">
        <label class="text-sm font-medium">Customer type<Select v-model="form.customer_type" :options="customerTypes" optionLabel="label" optionValue="value" fluid class="mt-1" /></label>
        <label class="text-sm font-medium">{{ form.customer_type === 'business' ? 'Contact person' : 'Full name' }} *<InputText v-model="form.full_name" fluid class="mt-1" :invalid="!!formErrors.full_name" /></label>
        <label v-if="form.customer_type === 'business'" class="text-sm font-medium">Business name *<InputText v-model="form.company_name" fluid class="mt-1" :invalid="!!formErrors.company_name" /></label>
        <label class="text-sm font-medium">Email<InputText v-model="form.email" fluid class="mt-1" type="email" :invalid="!!formErrors.email" /></label>
        <label class="text-sm font-medium">Phone<InputText v-model="form.phone" fluid class="mt-1" :invalid="!!formErrors.phone" /></label>
        <label class="text-sm font-medium">Address<InputText v-model="form.address" fluid class="mt-1" /></label>
        <label class="text-sm font-medium">Lead source<Select v-model="form.source" :options="sources" optionLabel="label" optionValue="value" fluid class="mt-1" /></label>
        <InputNumber v-model="form.estimated_value" fluid :min="0" mode="currency" currency="PHP" locale="en-PH" />
        <Textarea v-model="form.notes" rows="3" fluid placeholder="Notes" />
        <p v-if="formError" class="text-sm text-red-600">{{ formError }}</p>
      </div>
      <template #footer>
        <Button text severity="secondary" label="Cancel" @click="dialog=false" />
        <Button :loading="saving" label="Save Customer" @click="saveLead" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import crmService from '@/services/crm.service'
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
import Skeleton from 'primevue/skeleton'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'

const toast = useToast()
const authStore = useAuthStore()
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const formError = ref('')
const formErrors = reactive<Record<string, string>>({})
const leads = ref<any[]>([])
const dialog = ref(false)
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
const customerTypes = [{ label: 'Individual', value: 'individual' }, { label: 'Business', value: 'business' }]
const sources = [{ label: 'Walk-in', value: 'walk_in' }, { label: 'Website', value: 'website' }, { label: 'Social media', value: 'social' }, { label: 'Referral', value: 'referral' }, { label: 'Phone', value: 'phone' }, { label: 'Other', value: 'other' }]
const processStages = [
  { label: 'New', value: 'new', help: 'Capture and assign.' },
  { label: 'Contacted', value: 'contacted', help: 'First outreach recorded.' },
  { label: 'Qualified', value: 'qualified', help: 'Need, budget, and timeline confirmed.' },
  { label: 'Proposal', value: 'proposal', help: 'Offer or quotation sent.' },
  { label: 'Won', value: 'won', help: 'Customer accepted and is ready to order.' },
  { label: 'Lost', value: 'lost', help: 'Record the reason and close.' },
]
const form = reactive<any>({ customer_type: 'individual', company_name: '', full_name: '', email: '', phone: '', address: '', source: 'walk_in', estimated_value: 0, notes: '' })
const canManageCrm = authStore.hasPermission('crm.leads.manage')

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const res = await crmService.getLeads({ page: page.current, per_page: page.rows, search: filters.search || undefined, stage: filters.stage || undefined })
    const payload = res?.data
    leads.value = (payload?.data || []).map((r: any) => ({ ...r, __stage: r.stage }))
    page.total = Number(payload?.total || 0)
  } catch { error.value = 'Could not load customers and leads.' } finally { loading.value = false }
}
const onPage = (e: any) => { page.current = Number(e.page || 0) + 1; page.rows = Number(e.rows || 10); load() }
const openCreate = () => { formError.value = ''; dialog.value = true }
const saveLead = async () => {
  formError.value = ''
  Object.keys(formErrors).forEach(key => delete formErrors[key])
  if (!form.full_name.trim() || (form.customer_type === 'business' && !form.company_name.trim())) { formError.value = 'Enter the customer name and business name when applicable.'; return }
  saving.value = true
  try {
    await crmService.createLead({ ...form, company_name: form.customer_type === 'business' ? form.company_name : null })
    dialog.value = false
    Object.assign(form, { customer_type: 'individual', company_name: '', full_name: '', email: '', phone: '', address: '', source: 'walk_in', estimated_value: 0, notes: '' })
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Customer lead created.', life: 2200 })
    load()
  } catch (e: any) {
    const fields = e?.response?.data?.errors || {}
    Object.entries(fields).forEach(([key, values]) => formErrors[key] = Array.isArray(values) ? String(values[0]) : String(values))
    formError.value = Object.values(formErrors)[0] || e?.response?.data?.message || 'Could not save customer.'
  } finally { saving.value = false }
}
const updateStage = async (row: any) => {
  if (!row.__stage || row.__stage === row.stage) return
  const note = window.prompt('Add a short note for this stage change (required for Qualified, Proposal, Won, and Lost):', '')
  if (['qualified', 'proposal', 'won', 'lost'].includes(row.__stage) && !note?.trim()) {
    row.__stage = row.stage
    toast.add({ severity: 'warn', summary: 'Note required', detail: 'Please explain the stage change.', life: 2500 })
    return
  }
  try {
    await crmService.updateLeadStage(row.id, { stage: row.__stage, note: note?.trim() || undefined })
    toast.add({ severity: 'success', summary: 'Updated', detail: 'Lead stage updated.', life: 2000 })
    load()
  } catch {
    row.__stage = row.stage
  }
}
const money = (v: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(v || 0))
const fmt = (v: string) => String(v || '').replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase())

watch(() => [filters.search, filters.stage], () => { page.current = 1; load() })
onMounted(load)
</script>
