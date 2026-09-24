<template>
  <div class="space-y-6 p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold text-slate-900">My Benefits</h1>
        <p class="text-sm text-slate-500">View your paid contributions, coverage, and benefit requests.</p>
      </div>
      <Button label="Back to profile" icon="pi pi-arrow-left" severity="secondary" text size="small" @click="router.visit('/employee-profile')" />
    </div>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
    <div v-if="loading" class="grid gap-4 md:grid-cols-2"><Skeleton v-for="n in 2" :key="n" height="13rem" /></div>
    <div v-else-if="!benefits.length" class="rounded-xl border border-slate-200 bg-white p-10 text-center text-slate-500">No active benefits have been assigned to your employee profile.</div>
    <div v-else class="grid gap-4 lg:grid-cols-2">
      <Card v-for="benefit in benefits" :key="benefit.deduction_type_id" class="rounded-xl border border-slate-200 shadow-sm">
        <template #title><div class="flex items-center justify-between"><span>{{ benefit.name }}</span><Badge value="Benefit" severity="info" /></div></template>
        <template #content>
          <div class="space-y-4 text-sm">
            <div class="grid grid-cols-2 gap-3">
              <div><p class="text-slate-500">Paid contributions</p><p class="font-semibold text-slate-900">{{ money(benefit.contributed) }}</p></div>
              <div><p class="text-slate-500">{{ benefit.limit_source === 'coverage' ? 'Coverage limit' : 'Contribution balance' }}</p><p class="font-semibold text-slate-900">{{ money(benefit.limit) }}</p></div>
              <div><p class="text-slate-500">Used</p><p class="font-semibold text-slate-900">{{ money(benefit.used) }}</p></div>
              <div><p class="text-slate-500">Available</p><p class="font-semibold text-emerald-700">{{ money(benefit.available) }}</p></div>
            </div>
            <div><div class="mb-1 flex justify-between text-xs"><span>Usage</span><span>{{ benefit.usage_percent }}%</span></div><ProgressBar :value="benefit.usage_percent" :showValue="false" class="h-2" /></div>
            <div><div class="mb-1 flex justify-between text-xs"><span>Contributions toward coverage</span><span>{{ benefit.contribution_percent }}%</span></div><ProgressBar :value="benefit.contribution_percent" :showValue="false" class="h-2" /></div>
            <p v-if="benefit.reserved > 0" class="text-xs text-amber-700">{{ money(benefit.reserved) }} reserved for approved requests.</p>
            <Button label="Request usage" icon="pi pi-plus" size="small" :disabled="benefit.available <= 0" @click="openRequest(benefit)" />
          </div>
        </template>
      </Card>
    </div>

    <Card class="rounded-xl border border-slate-200 shadow-sm">
      <template #title>My requests</template>
      <template #content>
        <DataTable :value="requests" rowHover responsiveLayout="scroll" class="p-datatable-sm" :rows="10" paginator>
          <Column field="created_at" header="Requested"><template #body="{ data }">{{ date(data.created_at) }}</template></Column>
          <Column field="deduction_type.name" header="Benefit" />
          <Column field="provider" header="Provider" />
          <Column field="service_type" header="Service" />
          <Column header="Amount"><template #body="{ data }">{{ money(data.used_amount ?? data.approved_amount ?? data.requested_amount) }}</template></Column>
          <Column header="Status"><template #body="{ data }"><Badge :value="statusLabel(data.status)" :severity="severity(data.status)" /></template></Column>
          <Column header="Proof"><template #body="{ data }"><a v-if="data.receipt_path || data.request_attachment_path" :href="assetUrl(data.receipt_path || data.request_attachment_path)" target="_blank" rel="noopener" class="text-blue-600 underline">View</a></template></Column>
          <Column header="Action"><template #body="{ data }"><Button v-if="data.status === 'approved'" label="Upload receipt" size="small" outlined @click="receiptFor = data; receiptDialog = true" /></template></Column>
          <template #empty><div class="py-8 text-center text-slate-500">No benefit requests yet.</div></template>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="requestDialog" modal header="Request benefit usage" :style="{ width: 'min(34rem, 94vw)' }">
      <div class="space-y-3">
        <p class="text-sm text-slate-600">{{ selectedBenefit?.name }} · Available {{ money(selectedBenefit?.available) }}</p>
        <div><label class="text-xs text-slate-500">Provider or hospital</label><InputText v-model="form.provider" class="w-full" /></div>
        <div><label class="text-xs text-slate-500">Service type</label><InputText v-model="form.service_type" class="w-full" /></div>
        <div><label class="text-xs text-slate-500">Service date</label><DatePicker v-model="form.service_date" showIcon dateFormat="M d, yy" class="w-full" /></div>
        <div><label class="text-xs text-slate-500">Estimated amount</label><InputNumber v-model="form.requested_amount" mode="currency" currency="PHP" locale="en-PH" :min="0" class="w-full" /></div>
        <div><label class="text-xs text-slate-500">Notes</label><Textarea v-model="form.notes" rows="2" class="w-full" /></div>
        <div><label class="text-xs text-slate-500">Supporting document (optional)</label><input type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="block w-full text-sm" @change="attachment = ($event.target as HTMLInputElement).files?.[0] || null" /></div>
        <Message v-if="formError" severity="error" :closable="false">{{ formError }}</Message>
      </div>
      <template #footer><Button label="Cancel" severity="secondary" text @click="requestDialog = false" /><Button label="Submit request" :loading="saving" @click="submit" /></template>
    </Dialog>

    <Dialog v-model:visible="receiptDialog" modal header="Submit final receipt" :style="{ width: 'min(28rem, 94vw)' }">
      <p class="mb-3 text-sm text-slate-600">Upload the bill or receipt for the approved {{ receiptFor?.deduction_type?.name }} request.</p>
      <input type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="block w-full text-sm" @change="receipt = ($event.target as HTMLInputElement).files?.[0] || null" />
      <Message v-if="formError" severity="error" :closable="false" class="mt-3">{{ formError }}</Message>
      <template #footer><Button label="Cancel" severity="secondary" text @click="receiptDialog = false" /><Button label="Submit receipt" :loading="saving" @click="submitReceipt" /></template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import ProgressBar from 'primevue/progressbar'
import Skeleton from 'primevue/skeleton'
import Textarea from 'primevue/textarea'
import hrService from '@/services/hr.services'

const loading = ref(true), saving = ref(false), error = ref(''), formError = ref('')
const benefits = ref<any[]>([]), requests = ref<any[]>([])
const requestDialog = ref(false), receiptDialog = ref(false)
const selectedBenefit = ref<any>(null), receiptFor = ref<any>(null)
const attachment = ref<File | null>(null), receipt = ref<File | null>(null)
const form = reactive({ provider: '', service_type: '', service_date: null as Date | null, requested_amount: null as number | null, notes: '' })
const money = (value: any) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
const date = (value: string) => value ? new Date(value).toLocaleDateString('en-PH') : '—'
const statusLabel = (value: string) => value === 'completed' ? 'Receipt submitted' : value.charAt(0).toUpperCase() + value.slice(1)
const severity = (value: string) => value === 'settled' ? 'success' : value === 'rejected' ? 'danger' : value === 'pending' ? 'warn' : 'info'
const assetUrl = (path: string) => `/storage/${path}`
const dateString = (value: Date) => `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`
const load = async () => {
  loading.value = true; error.value = ''
  try { const response = await hrService.api.get('/api/hr/benefit-requests/mine'); benefits.value = response.data.data.benefits || []; requests.value = response.data.data.requests || [] }
  catch (e: any) { error.value = e.response?.data?.message || 'Unable to load benefits.' }
  finally { loading.value = false }
}
const openRequest = (benefit: any) => { selectedBenefit.value = benefit; formError.value = ''; attachment.value = null; requestDialog.value = true }
const submit = async () => {
  if (!selectedBenefit.value || !form.provider || !form.service_type || !form.service_date || !form.requested_amount) { formError.value = 'Complete all required fields.'; return }
  saving.value = true; formError.value = ''
  try {
    const payload = new FormData()
    payload.append('deduction_type_id', String(selectedBenefit.value.deduction_type_id))
    payload.append('provider', form.provider); payload.append('service_type', form.service_type)
    payload.append('service_date', dateString(form.service_date)); payload.append('requested_amount', String(form.requested_amount)); payload.append('notes', form.notes)
    if (attachment.value) payload.append('attachment', attachment.value)
    await hrService.api.post('/api/hr/benefit-requests', payload)
    requestDialog.value = false; Object.assign(form, { provider: '', service_type: '', service_date: null, requested_amount: null, notes: '' }); await load()
  } catch (e: any) { formError.value = Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || 'Unable to submit request.' }
  finally { saving.value = false }
}
const submitReceipt = async () => {
  if (!receipt.value) { formError.value = 'Select a receipt first.'; return }
  saving.value = true; formError.value = ''
  try { const payload = new FormData(); payload.append('receipt', receipt.value); await hrService.api.post(`/api/hr/benefit-requests/${receiptFor.value.id}/complete`, payload); receiptDialog.value = false; receipt.value = null; await load() }
  catch (e: any) { formError.value = e.response?.data?.message || 'Unable to submit receipt.' }
  finally { saving.value = false }
}
onMounted(load)
</script>
