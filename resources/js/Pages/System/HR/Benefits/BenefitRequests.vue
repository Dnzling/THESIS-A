<template>
  <div class="space-y-5 p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div><h1 class="text-xl font-semibold text-slate-900">Benefit Requests</h1><p class="text-sm text-slate-500">Review employee benefit use and record the final covered amount.</p></div>
      <Button icon="pi pi-refresh" label="Refresh" severity="secondary" outlined size="small" :loading="loading" @click="load" />
    </div>
    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
    <Card class="rounded-xl border border-slate-200 shadow-sm">
      <template #content>
        <div class="mb-4 flex gap-2"><Select v-model="status" :options="statuses" optionLabel="label" optionValue="value" class="w-48" @change="load" /></div>
        <DataTable :value="rows" :loading="loading" rowHover responsiveLayout="scroll" class="p-datatable-sm">
          <Column header="Date"><template #body="{ data }">{{ date(data.created_at) }}</template></Column>
          <Column header="Employee"><template #body="{ data }">{{ data.employee?.user?.fname }} {{ data.employee?.user?.lname }}</template></Column>
          <Column field="deduction_type.name" header="Benefit" />
          <Column field="provider" header="Provider" />
          <Column header="Requested"><template #body="{ data }">{{ money(data.requested_amount) }}</template></Column>
          <Column header="Approved"><template #body="{ data }">{{ data.approved_amount ? money(data.approved_amount) : '—' }}</template></Column>
          <Column header="Status"><template #body="{ data }"><Badge :value="data.status === 'completed' ? 'Receipt submitted' : data.status" :severity="severity(data.status)" /></template></Column>
          <Column header="Action"><template #body="{ data }"><Button label="View" icon="pi pi-eye" size="small" outlined @click="selected = data; dialog = true; formError = ''" /></template></Column>
          <template #empty><div class="py-10 text-center text-slate-500">No benefit requests found.</div></template>
        </DataTable>
        <div v-if="total > 0" class="mt-4 flex items-center justify-between text-sm text-slate-500"><span>{{ total }} requests</span><div class="flex gap-2"><Button icon="pi pi-chevron-left" text :disabled="page <= 1" @click="page--; load()" /><span class="py-2">{{ page }}</span><Button icon="pi pi-chevron-right" text :disabled="page >= lastPage" @click="page++; load()" /></div></div>
      </template>
    </Card>
    <Dialog v-model:visible="dialog" modal header="Benefit request" :style="{ width: 'min(38rem, 94vw)' }">
      <div v-if="selected" class="space-y-4 text-sm">
        <div class="grid grid-cols-2 gap-3">
          <div><p class="text-slate-500">Employee</p><p class="font-medium">{{ selected.employee?.user?.fname }} {{ selected.employee?.user?.lname }}</p></div>
          <div><p class="text-slate-500">Benefit</p><p class="font-medium">{{ selected.deduction_type?.name }}</p></div>
          <div><p class="text-slate-500">Provider</p><p class="font-medium">{{ selected.provider }}</p></div>
          <div><p class="text-slate-500">Service</p><p class="font-medium">{{ selected.service_type }}</p></div>
          <div><p class="text-slate-500">Service date</p><p class="font-medium">{{ date(selected.service_date) }}</p></div>
          <div><p class="text-slate-500">Requested amount</p><p class="font-medium">{{ money(selected.requested_amount) }}</p></div>
        </div>
        <p v-if="selected.notes"><span class="text-slate-500">Notes:</span> {{ selected.notes }}</p>
        <p v-if="selected.review_notes"><span class="text-slate-500">HR notes:</span> {{ selected.review_notes }}</p>
        <div class="flex flex-wrap gap-4"><a v-if="selected.request_attachment_path" :href="assetUrl(selected.request_attachment_path)" target="_blank" rel="noopener" class="text-blue-600 underline">Supporting document</a><a v-if="selected.receipt_path" :href="assetUrl(selected.receipt_path)" target="_blank" rel="noopener" class="text-blue-600 underline">Final receipt</a></div>
        <div v-if="selected.status === 'pending'" class="space-y-3 border-t border-slate-100 pt-3"><div><label class="text-xs text-slate-500">Approved amount</label><InputNumber v-model="approvedAmount" mode="currency" currency="PHP" locale="en-PH" :min="0" class="w-full" /></div><div><label class="text-xs text-slate-500">Review notes / rejection reason</label><Textarea v-model="reviewNotes" rows="2" class="w-full" /></div></div>
        <div v-if="selected.status === 'completed'" class="space-y-3 border-t border-slate-100 pt-3"><div><label class="text-xs text-slate-500">Final benefit amount used</label><InputNumber v-model="usedAmount" mode="currency" currency="PHP" locale="en-PH" :min="0" class="w-full" /></div><div><label class="text-xs text-slate-500">Company payment (if any)</label><InputNumber v-model="companyPaymentAmount" mode="currency" currency="PHP" locale="en-PH" :min="0" class="w-full" /><p class="mt-1 text-xs text-slate-500">This creates a Finance expense for payment. Leave at zero if the HMO provider covers the bill.</p></div></div>
        <Message v-if="formError" severity="error" :closable="false">{{ formError }}</Message>
      </div>
      <template #footer>
        <Button label="Close" severity="secondary" text @click="dialog = false" />
        <Button v-if="selected?.status === 'pending'" label="Reject" severity="danger" outlined :loading="saving" @click="review('rejected')" />
        <Button v-if="selected?.status === 'pending'" label="Approve" :loading="saving" @click="review('approved')" />
        <Button v-if="selected?.status === 'completed'" label="Record usage" :loading="saving" @click="settle" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import hrService from '@/services/hr.services'

const rows = ref<any[]>([]), loading = ref(false), saving = ref(false), error = ref(''), formError = ref('')
const selected = ref<any>(null), dialog = ref(false), page = ref(1), total = ref(0), lastPage = ref(1)
const status = ref(''), approvedAmount = ref<number | null>(null), usedAmount = ref<number | null>(null), companyPaymentAmount = ref<number | null>(0), reviewNotes = ref('')
const statuses = [{ label: 'All statuses', value: '' }, { label: 'Pending', value: 'pending' }, { label: 'Approved', value: 'approved' }, { label: 'Receipt submitted', value: 'completed' }, { label: 'Settled', value: 'settled' }, { label: 'Rejected', value: 'rejected' }]
const money = (v: any) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(v || 0))
const date = (v: string) => v ? new Date(v).toLocaleDateString('en-PH') : '—'
const assetUrl = (path: string) => `/storage/${path}`
const severity = (v: string) => v === 'settled' ? 'success' : v === 'rejected' ? 'danger' : v === 'pending' ? 'warn' : 'info'
const load = async () => {
  loading.value = true; error.value = ''
  try { const response = await hrService.api.get('/api/hr/benefit-requests', { params: { status: status.value || undefined, page: page.value } }); const data = response.data.data; rows.value = data.data || []; total.value = data.total || 0; lastPage.value = data.last_page || 1 }
  catch (e: any) { error.value = e.response?.data?.message || 'Unable to load requests.' }
  finally { loading.value = false }
}
const review = async (decision: 'approved' | 'rejected') => {
  if (decision === 'approved' && (!approvedAmount.value || approvedAmount.value <= 0)) { formError.value = 'Enter an approved amount.'; return }
  if (decision === 'rejected' && !reviewNotes.value.trim()) { formError.value = 'Enter a rejection reason.'; return }
  saving.value = true; formError.value = ''
  try { await hrService.api.post(`/api/hr/benefit-requests/${selected.value.id}/review`, { decision, approved_amount: decision === 'approved' ? approvedAmount.value : null, review_notes: reviewNotes.value }); dialog.value = false; approvedAmount.value = null; reviewNotes.value = ''; await load() }
  catch (e: any) { formError.value = Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || 'Unable to review request.' }
  finally { saving.value = false }
}
const settle = async () => {
  if (usedAmount.value === null) { formError.value = 'Enter the final used amount.'; return }
  saving.value = true; formError.value = ''
  try { await hrService.api.post(`/api/hr/benefit-requests/${selected.value.id}/settle`, { used_amount: usedAmount.value, company_payment_amount: companyPaymentAmount.value || 0 }); dialog.value = false; usedAmount.value = null; companyPaymentAmount.value = 0; await load() }
  catch (e: any) { formError.value = Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || 'Unable to record usage.' }
  finally { saving.value = false }
}
onMounted(load)
</script>
