<template>
  <div class="min-h-screen p-4">
    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-lg font-bold text-slate-900">Wholesale Quotations</h1>
        <p class="text-xs text-slate-500">Create and track negotiated bulk offers for business customers.</p>
      </div>
      <Button v-if="canManage" label="Create Quotation" icon="pi pi-plus" size="small" @click="router.visit('/sales/wholesale-quotes/create')" />
    </div>

    <Card class="border border-slate-200 shadow-sm"><template #content>
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
import { useAuthStore } from '@/stores/auth'
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
const toast = useToast(),
  auth = useAuthStore()
const canManage = computed(() => auth.hasPermission('sales.orders.manage'))
const loading = ref(false),
  rows = ref<any[]>([])
const filters = reactive({
  search: '',
  status: null as string | null,
  date: null as Date | null
})
let timer: any
const statuses = [
  { label: 'Draft', value: 'draft' },
  { label: 'Sent', value: 'sent' },
  { label: 'Accepted', value: 'accepted' },
  { label: 'Declined', value: 'declined' },
  { label: 'Converted', value: 'converted' }
]
const hasFilters = computed(
  () => !!(filters.search || filters.status || filters.date)
)
const load = async () => {
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
const label = (v: string) =>
  String(v || '')
    .replace(/_/g, ' ')
    .replace(/\b\w/g, c => c.toUpperCase())
const severity = (v: string) =>
  v === 'converted'
    ? 'success'
    : v === 'accepted'
      ? 'info'
      : v === 'declined'
        ? 'danger'
        : v === 'sent'
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
onMounted(load)
</script>
