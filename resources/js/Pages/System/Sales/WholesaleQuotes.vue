<template>
  <div class="min-h-screen p-4">
    <ConfirmDialog />
    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-lg font-bold text-slate-900">Wholesale Quotations</h1>
        <p class="text-xs text-slate-500">Create and track negotiated bulk offers for business customers.</p>
      </div>
      <Button v-if="canManage" label="Create Quotation" icon="pi pi-plus" size="small" @click="router.visit('/sales/wholesale-quotes/create')" />
    </div>

    <Card v-if="canView" class="mb-4 border border-slate-200 shadow-sm">
      <template #title>Wholesale Customer Access</template>
      <template #subtitle>Approve business customers before their account is linked to store wholesale quotes.</template>
      <template #content>
        <DataTable :value="accessRequests" :loading="accessLoading" dataKey="id" rowHover class="text-xs" responsiveLayout="scroll">
          <template #empty><div class="py-5 text-center text-sm text-slate-500">No wholesale access requests.</div></template>
          <Column header="Customer" style="min-width: 190px"><template #body="{ data }">
            <p class="font-semibold text-slate-800">{{ [data.fname, data.lname].filter(Boolean).join(' ') || data.email }}</p>
            <p class="text-slate-500">{{ data.email }}</p>
          </template></Column>
          <Column field="business_name" header="Business name" style="min-width: 170px" />
          <Column field="request_note" header="Note" style="min-width: 180px"><template #body="{ data }">{{ data.request_note || '—' }}</template></Column>
          <Column field="status" header="Status"><template #body="{ data }"><Tag :value="label(data.status)" :severity="severity(data.status)" /></template></Column>
          <Column header="Actions" style="min-width: 160px"><template #body="{ data }">
            <div v-if="data.status === 'pending'" class="flex gap-2">
              <Button label="Approve" size="small" severity="warn" @click="confirmAccess(data, 'approved')" />
              <Button label="Reject" size="small" severity="secondary" outlined @click="confirmAccess(data, 'rejected')" />
            </div>
            <span v-else class="text-slate-400">Reviewed</span>
          </template></Column>
        </DataTable>
      </template>
    </Card>

    <Card v-if="canReviewAccess" class="mb-4 border border-slate-200 shadow-sm">
      <template #title>Customer Product Quote Requests</template>
      <template #subtitle>Customers choose products and quantities; your team sets the branch, availability, price, and fulfillment in the quotation.</template>
      <template #content>
        <DataTable :value="quoteRequests" :loading="quoteRequestLoading" dataKey="id" rowHover class="text-xs" responsiveLayout="scroll">
          <template #empty><div class="py-5 text-center text-sm text-slate-500">No customer product requests.</div></template>
          <Column field="request_number" header="Request" style="min-width: 170px"><template #body="{ data }"><span class="font-semibold">{{ data.request_number }}</span><p class="mt-1 text-[11px] text-slate-500">{{ dateTime(data.created_at) }}</p></template></Column>
          <Column header="Customer / Business" style="min-width: 190px"><template #body="{ data }"><p class="font-medium">{{ data.business_name || [data.fname, data.lname].filter(Boolean).join(' ') || data.email }}</p><p class="text-slate-500">{{ data.email }}</p></template></Column>
          <Column header="Requested items" style="min-width: 230px"><template #body="{ data }"><div v-for="item in parseItems(data.items)" :key="`${item.product_id}:${item.variation_id || 'standard'}`" class="py-0.5"><span class="font-medium">{{ item.quantity }} × {{ item.product_name }}</span><span v-if="item.variant_name" class="text-slate-500"> · {{ item.variant_name }}</span></div><p v-if="data.customer_note" class="mt-1 text-slate-500">Note: {{ data.customer_note }}</p></template></Column>
          <Column field="status" header="Status"><template #body="{ data }"><Tag :value="label(data.status)" :severity="data.status === 'quoted' ? 'success' : data.status === 'cancelled' ? 'secondary' : 'warn'" /></template></Column>
          <Column header="Action" style="min-width: 150px"><template #body="{ data }"><Button v-if="data.status === 'pending' && canManage" label="Prepare quotation" icon="pi pi-file-edit" severity="warn" size="small" @click="prepareQuote(data)" /><Button v-else-if="data.wholesale_quote_id" label="View quotation" icon="pi pi-eye" outlined size="small" @click="view(data.wholesale_quote_id)" /></template></Column>
        </DataTable>
      </template>
    </Card>

    <Message v-if="featureLoaded && !wholesaleEnabled" severity="warn" :closable="false" class="mb-4">
      Wholesale is disabled in Store Settings. Enable it there to create and manage wholesale quotations.
    </Message>

    <Card v-if="!featureLoaded || wholesaleEnabled" class="border border-slate-200 shadow-sm"><template #content>
        <div class="mb-4 grid items-end gap-3 md:grid-cols-5">
          <IconField class="md:col-span-2">
            <InputIcon class="pi pi-search" />
            <InputText v-model="filters.search" placeholder="Quotation number or customer" size="small" fluid
              @input="searchLater" />
          </IconField>
          <Select v-model="filters.status" :options="statuses" optionLabel="label" optionValue="value"
            placeholder="All statuses" size="small" showClear fluid @change="load" />
          <DatePicker v-model="filters.date" placeholder="Created date" dateFormat="M d, yy" size="small" showIcon
            showClear fluid @date-select="load" />
          <Button v-if="hasFilters" label="Clear filters" severity="secondary" outlined size="small"
            @click="clearFilters" />
        </div>

        <DataTable :value="rows" :loading="loading" dataKey="id" paginator :rows="15" :rowsPerPageOptions="[15, 25, 50]"
          rowHover stripedRows class="text-xs" responsiveLayout="scroll">
          <template #empty>
            <div class="py-10 text-center"><i class="pi pi-file-edit text-4xl text-slate-300" />
              <p class="mt-2 text-slate-500">No wholesale quotations found.</p>
            </div>
          </template>
          <Column field="quote_number" header="Quotation" sortable style="min-width:180px"><template
              #body="{ data }"><button class="font-semibold text-orange-600 hover:underline" @click="view(data.id)">{{
                data.quote_number }}</button>
              <p class="mt-1 text-[11px] text-slate-400">{{ dateTime(data.created_at) }}</p>
            </template>
          </Column>
          <Column header="Customer" style="min-width:210px"><template #body="{ data }">
              <p class="font-medium text-slate-800">{{ data.crm_lead?.full_name || '—' }}</p>
              <p class="text-[11px] text-slate-500">{{ data.crm_lead?.email || data.crm_lead?.phone || 'No contact' }}
              </p>
            </template>
          </Column>
          <Column header="Items"><template #body="{ data }">{{ data.items?.length || 0 }}</template></Column>
          <Column header="Created by" style="min-width:150px"><template #body="{ data }">{{ data.creator?.full_name || 'Not available' }}</template></Column>
          <Column field="subtotal" header="Quoted total" sortable><template #body="{ data }"><span
                class="font-semibold">{{ money(data.subtotal) }}</span></template></Column>
          <Column field="valid_until" header="Valid until" sortable><template #body="{ data }">{{
            dateOnly(data.valid_until) }}</template></Column>
          <Column field="status" header="Status" sortable><template #body="{ data }">
              <Tag :value="label(data.status)" :severity="severity(data.status)" />
            </template></Column>
          <Column header="Actions" frozen alignFrozen="right"><template #body="{ data }"><Button label="View"
                icon="pi pi-eye" outlined rounded size="small" @click="view(data.id)" /></template>
          </Column>
        </DataTable>
      </template></Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useAuthStore } from '@/stores/auth'
import axiosClient from '@/axios'
import salesService from '@/services/sales.service'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import DatePicker from 'primevue/datepicker'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import ConfirmDialog from 'primevue/confirmdialog'
import Message from 'primevue/message'
const toast = useToast(),
  auth = useAuthStore(),
  confirm = useConfirm()
const canManage = computed(() => auth.hasPermission('sales.wholesale.manage'))
const canView = computed(() => auth.hasPermission('sales.wholesale.view') || canManage.value)
const canReviewAccess = computed(() => auth.hasPermission('sales.wholesale.approve'))
const loading = ref(false),
  rows = ref<any[]>([])
const accessLoading = ref(false)
const accessRequests = ref<any[]>([])
const quoteRequestLoading = ref(false)
const quoteRequests = ref<any[]>([])
const wholesaleEnabled = ref(false)
const featureLoaded = ref(false)
const filters = reactive({
  search: '',
  status: null as string | null,
  date: null as Date | null
})
let timer: any
const statuses = [
  { label: 'Draft', value: 'draft' },
  { label: 'Pending', value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Converted', value: 'converted' }
]
const hasFilters = computed(
  () => !!(filters.search || filters.status || filters.date)
)
const load = async () => {
  if (featureLoaded.value && !wholesaleEnabled.value) return
  loading.value = true
  try {
    const r = await salesService.getWholesaleQuotes({
      search: filters.search,
      status: filters.status,
      date: filters.date?.toISOString().slice(0, 10)
    })
    rows.value = r.data?.data || []
  } catch (e: any) {
    toast.add({
      severity: 'error',
      summary: 'Unable to load quotations',
      detail: e.response?.data?.message || 'Please try again.',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}
const loadFeatureAndAccess = async () => {
  try {
    const response = await axiosClient.get('/api/store/settings')
    wholesaleEnabled.value = Boolean(response.data?.data?.return_resolution?.wholesale_enabled)
  } catch {
    wholesaleEnabled.value = false
  } finally {
    featureLoaded.value = true
  }
  if (wholesaleEnabled.value && canReviewAccess.value) await loadAccessRequests()
  if (wholesaleEnabled.value && canView.value) await loadQuoteRequests()
  if (wholesaleEnabled.value) await load()
}
const loadQuoteRequests = async () => {
  quoteRequestLoading.value = true
  try {
    const response = await axiosClient.get('/api/sales/wholesale-quotes/customer-quote-requests')
    quoteRequests.value = response.data?.data || []
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to load product requests', detail: error.response?.data?.message || 'Please try again.', life: 3000 })
  } finally {
    quoteRequestLoading.value = false
  }
}
const parseItems = (value: any) => {
  try { return typeof value === 'string' ? JSON.parse(value) : (value || []) } catch { return [] }
}
const prepareQuote = (request: any) => router.visit(`/sales/wholesale-quotes/create?request_id=${encodeURIComponent(request.id)}`)
const loadAccessRequests = async () => {
  accessLoading.value = true
  try {
    const response = await axiosClient.get('/api/sales/wholesale-quotes/customer-access')
    accessRequests.value = response.data?.data || []
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to load access requests', detail: error.response?.data?.message || 'Please try again.', life: 3000 })
  } finally {
    accessLoading.value = false
  }
}
const confirmAccess = (access: any, status: 'approved' | 'rejected') => {
  const customerName = [access.fname, access.lname].filter(Boolean).join(' ') || access.email
  confirm.require({
    header: `${status === 'approved' ? 'Approve' : 'Reject'} wholesale access?`,
    message: `${status === 'approved' ? 'This customer will be eligible for this store’s wholesale quotations.' : 'This customer will not be linked to this store’s wholesale quotations.'} Customer: ${customerName}.`,
    rejectProps: { label: 'Cancel', severity: 'secondary', outlined: true },
    acceptProps: { label: status === 'approved' ? 'Approve' : 'Reject', severity: 'warn' },
    accept: async () => {
      try {
        await axiosClient.put(`/api/sales/wholesale-quotes/customer-access/${access.id}`, { status })
        toast.add({ severity: 'success', summary: 'Access updated', detail: `${customerName} was ${status}.`, life: 2500 })
        await loadAccessRequests()
      } catch (error: any) {
        toast.add({ severity: 'error', summary: 'Unable to update access', detail: error.response?.data?.message || 'Please try again.', life: 3000 })
      }
    },
  })
}
const searchLater = () => {
  clearTimeout(timer)
  timer = setTimeout(load, 300)
}
const clearFilters = () => {
  Object.assign(filters, { search: '', status: null, date: null })
  load()
}
const view = (id: number) => router.visit(`/sales/wholesale-quotes/${id}`)
const money = (v: any) =>
  new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(+v || 0)
const normalizedStatus = (v: string) => ({ sent: 'pending', accepted: 'approved', declined: 'rejected' } as Record<string, string>)[v] || v
const label = (v: string) =>
  String(normalizedStatus(v) || '')
    .replace(/_/g, ' ')
    .replace(/\b\w/g, c => c.toUpperCase())
const severity = (v: string) =>
  normalizedStatus(v) === 'converted'
    ? 'success'
    : normalizedStatus(v) === 'approved'
      ? 'success'
      : normalizedStatus(v) === 'rejected'
        ? 'danger'
        : normalizedStatus(v) === 'pending'
          ? 'warn'
          : 'secondary'
const dateOnly = (v: any) =>
  v
    ? new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium' }).format(
      new Date(v)
    )
    : 'No expiry'
const dateTime = (v: any) =>
  v
    ? new Intl.DateTimeFormat('en-PH', {
      dateStyle: 'medium',
      timeStyle: 'short'
    }).format(new Date(v))
    : '—'
onMounted(loadFeatureAndAccess)
</script>
