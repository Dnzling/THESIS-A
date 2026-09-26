<template>
  <div class="space-y-6 p-4 text-sm md:p-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-900">Tax / VAT Report</h1>
        <p class="mt-1 text-sm text-slate-500">Store-wide VAT position from POS, Ecommerce, and supplier invoices.</p>
      </div>
      <Button icon="pi pi-refresh" label="Refresh" severity="secondary" outlined size="small" :loading="loading" @click="loadReport" />
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
      <Card v-for="card in summaryCards" :key="card.label" class="rounded-2xl border border-slate-200 shadow-sm">
        <template #content>
          <div class="flex items-start justify-between p-5">
            <div>
              <p class="text-xs font-medium uppercase tracking-wider text-slate-500">{{ card.label }}</p>
              <Skeleton v-if="loading" width="8rem" height="1.75rem" class="mt-3" />
              <p v-else class="mt-3 text-2xl font-semibold" :class="card.color">₱{{ formatMoney(card.value) }}</p>
            </div>
            <div class="flex h-9 w-9 items-center justify-center rounded-full" :class="card.iconBg">
              <i :class="[card.icon, card.iconColor]"></i>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <Card class="rounded-2xl border border-slate-200 shadow-sm">
      <template #content>
        <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-4">
          <div class="space-y-2 md:col-span-2">
            <label class="text-xs font-medium uppercase tracking-wider text-slate-500">Date range</label>
            <DatePicker
              v-model="dateRange"
              selectionMode="range"
              :manualInput="false"
              dateFormat="M d, yy"
              placeholder="Select reporting period"
              class="w-full"
              inputClass="w-full"
              size="small"
            />
          </div>

          <div class="space-y-2">
            <label class="text-xs font-medium uppercase tracking-wider text-slate-500">Branch</label>
            <Select v-model="filters.branch_id" :options="branchOptions" optionLabel="label" optionValue="value" fluid size="small" />
          </div>

          <div class="space-y-2">
            <label class="text-xs font-medium uppercase tracking-wider text-slate-500">Source</label>
            <Select v-model="filters.source" :options="sourceOptions" optionLabel="label" optionValue="value" fluid size="small" />
          </div>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-3">
          <p class="text-xs text-slate-500">Output VAT is collected from customers. Input VAT is recorded from supplier invoices.</p>
          <Button label="Apply filters" icon="pi pi-filter" size="small" :loading="loading" @click="loadReport" />
        </div>
      </template>
    </Card>

    <Card class="rounded-2xl border border-slate-200 shadow-sm">
      <template #title>
        <div class="flex items-center justify-between gap-3 px-1 pt-1">
          <span class="text-base font-semibold text-slate-900">Tax transactions</span>
          <span class="text-xs font-normal text-slate-500">{{ totalRows }} records</span>
        </div>
      </template>
      <template #content>
        <div v-if="loading" class="space-y-3 px-1 pb-2">
          <div v-for="row in 6" :key="row" class="grid grid-cols-7 gap-4 border-b border-slate-100 py-4">
            <Skeleton v-for="cell in 7" :key="cell" height="1rem" />
          </div>
        </div>
        <DataTable v-else :value="rows" rowHover responsiveLayout="scroll" class="p-datatable-sm text-xs" :emptyMessage="'No tax transactions found.'">
          <Column field="date" header="Date" style="min-width: 125px">
            <template #body="{ data }">{{ formatDate(data.date) }}</template>
          </Column>
          <Column field="reference" header="Reference" style="min-width: 150px">
            <template #body="{ data }"><span class="font-medium text-slate-800">{{ data.reference }}</span></template>
          </Column>
          <Column field="source_label" header="Source" style="min-width: 130px">
            <template #body="{ data }"><Badge :value="data.source_label" :severity="sourceSeverity(data.source)" /></template>
          </Column>
          <Column field="branch" header="Branch" style="min-width: 170px" />
          <Column field="counterparty" header="Customer / Supplier" style="min-width: 190px" />
          <Column field="taxable_amount" header="Taxable Base" style="min-width: 135px">
            <template #body="{ data }">₱{{ formatMoney(data.taxable_amount) }}</template>
          </Column>
          <Column field="tax_amount" header="VAT" style="min-width: 125px">
            <template #body="{ data }">
              <span :class="data.tax_type === 'input' ? 'text-blue-700' : 'text-emerald-700'">₱{{ formatMoney(data.tax_amount) }}</span>
            </template>
          </Column>
          <Column field="status" header="Status" style="min-width: 110px">
            <template #body="{ data }"><Badge :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" /></template>
          </Column>
          <template #empty>
            <div class="py-12 text-center text-sm text-slate-500">No tax transactions found for the selected filters.</div>
          </template>
        </DataTable>

        <div v-if="!loading && totalRows > 0" class="mt-4 flex flex-col items-center justify-between gap-3 border-t border-slate-100 pt-4 sm:flex-row">
          <div class="flex items-center gap-2 text-xs text-slate-500">
            <span>Rows</span>
            <Select v-model="filters.per_page" :options="[15, 20, 50]" size="small" class="w-20" @change="loadReport" />
            <span>Page {{ page }} of {{ lastPage }}</span>
          </div>
          <div class="flex gap-2">
            <Button icon="pi pi-chevron-left" severity="secondary" text rounded size="small" :disabled="page <= 1 || loading" @click="goToPage(page - 1)" />
            <Button icon="pi pi-chevron-right" severity="secondary" text rounded size="small" :disabled="page >= lastPage || loading" @click="goToPage(page + 1)" />
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import financeService from '../../../services/finance.service'

const loading = ref(true)
const rows = ref<any[]>([])
const branches = ref<any[]>([])
const summary = ref({ output_vat: 0, input_vat: 0, net_vat: 0, taxable_sales: 0 })
const page = ref(1)
const lastPage = ref(1)
const totalRows = ref(0)
const dateRange = ref<Date[] | null>(null)
const filters = ref({ branch_id: null as number | null, source: 'all', per_page: 15 })

const sourceOptions = [
  { label: 'All sources', value: 'all' },
  { label: 'POS sales', value: 'sales' },
  { label: 'Ecommerce', value: 'ecommerce' },
  { label: 'Supplier invoices', value: 'purchases' },
]

const branchOptions = computed(() => [
  { label: 'All branches', value: null },
  ...branches.value.map((branch) => ({ label: branch.name, value: branch.id })),
])

const summaryCards = computed(() => [
  { label: 'Output VAT', value: summary.value.output_vat, color: 'text-emerald-700', icon: 'pi pi-arrow-up-right', iconBg: 'bg-emerald-50', iconColor: 'text-emerald-600' },
  { label: 'Input VAT', value: summary.value.input_vat, color: 'text-blue-700', icon: 'pi pi-arrow-down-left', iconBg: 'bg-blue-50', iconColor: 'text-blue-600' },
  { label: 'Net VAT position', value: summary.value.net_vat, color: summary.value.net_vat >= 0 ? 'text-orange-700' : 'text-blue-700', icon: 'pi pi-calculator', iconBg: 'bg-orange-50', iconColor: 'text-orange-600' },
  { label: 'Taxable sales', value: summary.value.taxable_sales, color: 'text-slate-800', icon: 'pi pi-chart-line', iconBg: 'bg-slate-100', iconColor: 'text-slate-600' },
])

const formatMoney = (value: number | string) => new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value || 0))
const formatDate = (value: string) => value ? new Date(`${value}T00:00:00`).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'
const formatStatus = (value: string) => value ? value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase()) : '—'
const sourceSeverity = (source: string) => source === 'purchases' ? 'info' : source === 'ecommerce' ? 'warn' : 'success'
const statusSeverity = (status: string) => ['paid', 'approved', 'completed', 'delivered'].includes(status) ? 'success' : ['cancelled', 'rejected', 'failed'].includes(status) ? 'danger' : 'warn'

const toDateString = (value: Date | null | undefined) => {
  if (!value) return ''
  const year = value.getFullYear()
  const month = String(value.getMonth() + 1).padStart(2, '0')
  const day = String(value.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const loadReport = async () => {
  loading.value = true
  try {
    const range = dateRange.value || []
    const response = await financeService.getTaxVatReport({
      page: page.value,
      per_page: filters.value.per_page,
      source: filters.value.source,
      branch_id: filters.value.branch_id || undefined,
      date_from: toDateString(range[0]),
      date_to: toDateString(range[1] || range[0]),
    })
    const payload = response?.data || {}
    summary.value = { ...summary.value, ...(payload.summary || {}) }
    rows.value = payload.rows?.data || []
    totalRows.value = Number(payload.rows?.total || 0)
    lastPage.value = Number(payload.rows?.last_page || 1)
    branches.value = payload.branches || []
  } finally {
    loading.value = false
  }
}

const goToPage = (nextPage: number) => {
  page.value = nextPage
  loadReport()
}

watch(() => [filters.value.branch_id, filters.value.source], () => {
  page.value = 1
})

onMounted(loadReport)
</script>

<style scoped>
:deep(.p-card .p-card-content) { padding: 0; }
:deep(.p-card .p-card-body) { padding: 1.25rem; }
:deep(.p-datatable .p-datatable-thead > tr > th) {
  background: #f8fafc;
  color: #64748b;
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  padding: 0.85rem 0.75rem;
  text-transform: uppercase;
}
:deep(.p-datatable .p-datatable-tbody > tr > td) {
  border-bottom: 1px solid #f1f5f9;
  padding: 0.85rem 0.75rem;
}
</style>
