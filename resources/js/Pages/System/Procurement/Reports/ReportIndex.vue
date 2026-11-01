<template>
  <div class="space-y-5">
    <header class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-900">Procurement Reports</h1>
        <p class="mt-1 text-sm text-slate-500">Review purchasing spend, supplier performance, lead times, and receiving accuracy.</p>
      </div>
      <Button label="Export CSV" icon="pi pi-download" size="small" severity="secondary" outlined :disabled="!rows.length" @click="exportCsv" />
    </header>

    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <div class="grid grid-cols-1 items-end gap-3 md:grid-cols-12">
          <div class="md:col-span-4">
            <label class="mb-1 block text-xs font-medium text-slate-600">Report</label>
            <Select v-model="reportType" :options="reportOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
          <div v-if="reportType === 'spend' || reportType === 'lead_time'" class="md:col-span-2">
            <label class="mb-1 block text-xs font-medium text-slate-600">Year</label>
            <Select v-model="selectedYear" :options="yearOptions" class="w-full" />
          </div>
          <template v-if="reportType === 'receiving'">
            <div class="md:col-span-2">
              <label class="mb-1 block text-xs font-medium text-slate-600">From</label>
              <DatePicker v-model="dateFrom" dateFormat="M d, yy" showIcon fluid />
            </div>
            <div class="md:col-span-2">
              <label class="mb-1 block text-xs font-medium text-slate-600">To</label>
              <DatePicker v-model="dateTo" dateFormat="M d, yy" showIcon fluid />
            </div>
          </template>
          <Button label="Generate Report" icon="pi pi-chart-bar" size="small" class="md:col-span-2" :loading="loading" @click="loadReport" />
        </div>
        <p class="mt-2 text-xs text-slate-500">{{ selectedReportDescription }}</p>
      </template>
    </Card>

    <Message v-if="loadError" severity="error" :closable="false">{{ loadError }}</Message>

    <div v-if="loading" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <Skeleton v-for="item in 4" :key="item" height="5.5rem" borderRadius="0.75rem" />
    </div>
    <section v-else-if="summaryCards.length" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <Card v-for="card in summaryCards" :key="card.label" class="border border-slate-200 shadow-none">
        <template #content>
          <p class="text-xs font-medium text-slate-500">{{ card.label }}</p>
          <p class="mt-2 text-xl font-semibold text-slate-900">{{ card.value }}</p>
          <p v-if="card.hint" class="mt-1 text-xs text-slate-500">{{ card.hint }}</p>
        </template>
      </Card>
    </section>

    <Card class="border border-slate-200 shadow-none">
      <template #title>
        <div class="flex flex-wrap items-center justify-between gap-2">
          <span class="text-base font-semibold text-slate-900">{{ activeReportTitle }}</span>
          <Badge v-if="rows.length" :value="`${rows.length} rows`" severity="secondary" />
        </div>
      </template>
      <template #content>
        <DataTable v-if="!loading" :value="rows" paginator :rows="10" rowHover responsiveLayout="scroll" class="text-sm">
          <template #empty>
            <div class="py-10 text-center">
              <i class="pi pi-chart-bar text-2xl text-slate-300" />
              <p class="mt-2 text-sm font-medium text-slate-700">No report data for this selection</p>
              <p class="mt-1 text-xs text-slate-500">Try another period or generate a different report.</p>
            </div>
          </template>

          <Column v-if="reportType === 'spend'" field="month" header="Month" sortable />
          <Column v-if="reportType === 'spend'" field="po_count" header="Purchase Orders" sortable />
          <Column v-if="reportType === 'spend'" field="amount" header="Committed Spend" sortable>
            <template #body="{ data }">{{ currency(data.amount) }}</template>
          </Column>
          <Column v-if="reportType === 'supplier'" field="name" header="Supplier" sortable />
          <Column v-if="reportType === 'supplier'" field="rating" header="Rating" sortable>
            <template #body="{ data }">{{ number(data.rating, 1) }} / 5</template>
          </Column>
          <Column v-if="reportType === 'supplier'" field="on_time_rate" header="On-Time Rate" sortable>
            <template #body="{ data }">{{ number(data.on_time_rate, 1) }}%</template>
          </Column>
          <Column v-if="reportType === 'supplier'" field="total_orders" header="Orders" sortable />
          <Column v-if="reportType === 'supplier'" field="total_spent" header="Total Spend" sortable>
            <template #body="{ data }">{{ currency(data.total_spent) }}</template>
          </Column>
          <Column v-if="reportType === 'supplier'" field="status" header="Performance">
            <template #body="{ data }"><Tag :value="data.status || 'No rating'" :severity="supplierSeverity(data.status)" /></template>
          </Column>
          <Column v-if="reportType === 'lead_time'" field="supplier_name" header="Supplier" sortable />
          <Column v-if="reportType === 'lead_time'" field="purchase_order_count" header="Received POs" sortable />
          <Column v-if="reportType === 'lead_time'" field="avg_lead_time" header="Average Lead Time" sortable>
            <template #body="{ data }">{{ number(data.avg_lead_time, 1) }} days</template>
          </Column>
          <Column v-if="reportType === 'receiving'" field="metric" header="Metric" />
          <Column v-if="reportType === 'receiving'" field="value" header="Result" />
        </DataTable>
        <div v-else class="space-y-2">
          <Skeleton v-for="row in 5" :key="row" height="2.75rem" borderRadius="0.5rem" />
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Skeleton from 'primevue/skeleton'
import Badge from 'primevue/badge'
import Tag from 'primevue/tag'
import Message from 'primevue/message'
import procurementService from '../../../../services/procurement.service'

type ReportType = 'spend' | 'supplier' | 'lead_time' | 'receiving'

const reportOptions = [
  { label: 'Purchase Spend', value: 'spend' },
  { label: 'Supplier Performance', value: 'supplier' },
  { label: 'Supplier Lead Time', value: 'lead_time' },
  { label: 'Receiving Accuracy', value: 'receiving' },
]
const thisYear = new Date().getFullYear()
const reportType = ref<ReportType>('spend')
const selectedYear = ref(thisYear)
const yearOptions = Array.from({ length: 6 }, (_, index) => thisYear - index)
const dateFrom = ref(new Date(new Date().getFullYear(), 0, 1))
const dateTo = ref(new Date())
const loading = ref(false)
const rows = ref<any[]>([])
const summary = ref<any>({})
const loadError = ref('')

const selectedReportDescription = computed(() => ({
  spend: 'Approved and active purchase orders, grouped by order month.',
  supplier: 'Supplier ratings and delivery metrics recorded for your store.',
  lead_time: `Average time from PO date to first goods receipt in ${selectedYear.value}.`,
  receiving: 'Full receipts compared with all receipts in the selected date range.',
}[reportType.value]))
const activeReportTitle = computed(() => reportOptions.find(option => option.value === reportType.value)?.label || 'Report')
const summaryCards = computed(() => {
  if (reportType.value === 'spend') {
    return [
      { label: 'Committed Spend', value: currency(summary.value.total_spend), hint: `For ${selectedYear.value}` },
      { label: 'Purchase Orders', value: number(summary.value.po_count), hint: 'Approved or active orders' },
      { label: 'Average Monthly Spend', value: currency(summary.value.avg_monthly_spend), hint: 'Across months with activity' },
      { label: 'Suppliers with Spend', value: number(summary.value.supplier_count), hint: 'Within selected year' },
    ]
  }
  if (reportType.value === 'supplier') {
    return [
      { label: 'Suppliers Rated', value: number(summary.value.supplier_count) },
      { label: 'Average Rating', value: `${number(summary.value.average_rating, 1)} / 5` },
      { label: 'Average On-Time Rate', value: `${number(summary.value.average_on_time_rate, 1)}%` },
      { label: 'Top Performer', value: summary.value.top_supplier || 'Not available' },
    ]
  }
  if (reportType.value === 'lead_time') {
    return [
      { label: 'Suppliers Measured', value: number(summary.value.supplier_count) },
      { label: 'Average Lead Time', value: `${number(summary.value.average_days, 1)} days` },
      { label: 'Fastest Average', value: summary.value.fastest_supplier || 'Not available' },
      { label: 'Period', value: String(selectedYear.value) },
    ]
  }
  return [
    { label: 'Receipts', value: number(summary.value.total_receipts) },
    { label: 'Full Receipts', value: number(summary.value.perfect_receipts) },
    { label: 'Receiving Accuracy', value: `${number(summary.value.accuracy_rate, 1)}%` },
    { label: 'Period', value: summary.value.period || 'Selected range' },
  ]
})

function payloadData(response: any) {
  return response?.data?.data ?? response?.data ?? {}
}

async function loadReport() {
  loading.value = true
  loadError.value = ''
  rows.value = []
  summary.value = {}
  try {
    if (reportType.value === 'spend') {
      const data = payloadData(await procurementService.getSpendAnalytics({ year: selectedYear.value }))
      const months = Array.isArray(data.monthly) ? data.monthly : []
      rows.value = months.map((row: any) => ({ ...row, amount: Number(row.amount || 0) }))
      const totalSpend = Number(data.total_spend || 0)
      summary.value = {
        total_spend: totalSpend,
        po_count: Number(data.po_count || 0),
        avg_monthly_spend: months.length ? totalSpend / months.length : 0,
        supplier_count: Number(data.supplier_count || 0),
      }
    } else if (reportType.value === 'supplier') {
      const data = payloadData(await procurementService.getSupplierPerformanceAnalytics())
      rows.value = Array.isArray(data) ? data : []
      const rated = rows.value.filter(row => Number(row.rating) > 0)
      summary.value = {
        supplier_count: rows.value.length,
        average_rating: rated.length ? rated.reduce((sum, row) => sum + Number(row.rating || 0), 0) / rated.length : 0,
        average_on_time_rate: rows.value.length ? rows.value.reduce((sum, row) => sum + Number(row.on_time_rate || 0), 0) / rows.value.length : 0,
        top_supplier: rows.value[0]?.name || null,
      }
    } else if (reportType.value === 'lead_time') {
      const response = await procurementService.getLeadTimeAnalysis({ year: selectedYear.value })
      const data = payloadData(response)
      rows.value = Array.isArray(data) ? data : []
      const values = rows.value.map(row => Number(row.avg_lead_time || 0)).filter(value => value > 0)
      const fastest = [...rows.value].sort((a, b) => Number(a.avg_lead_time || 0) - Number(b.avg_lead_time || 0))[0]
      summary.value = {
        supplier_count: rows.value.length,
        average_days: values.length ? values.reduce((sum, value) => sum + value, 0) / values.length : 0,
        fastest_supplier: fastest?.supplier_name || null,
      }
    } else {
      if (!dateFrom.value || !dateTo.value || dateFrom.value > dateTo.value) {
        throw new Error('Choose a valid start and end date for the report.')
      }
      const from = localDate(dateFrom.value)
      const to = localDate(dateTo.value)
      const data = payloadData(await procurementService.getReceivingAccuracy({ date_from: from, date_to: to }))
      summary.value = {
        ...data,
        period: `${formatDate(dateFrom.value)} – ${formatDate(dateTo.value)}`,
      }
      rows.value = [
        { metric: 'Total Receipts', value: number(data.total_receipts) },
        { metric: 'Full Receipts', value: number(data.perfect_receipts) },
        { metric: 'Accuracy Rate', value: `${number(data.accuracy_rate, 1)}%` },
        { metric: 'Assessment', value: data.accuracy_status || 'No data' },
      ]
    }
  } catch (error: any) {
    loadError.value = error?.response?.data?.message || error?.message || 'Unable to generate this report.'
  } finally {
    loading.value = false
  }
}

function exportCsv() {
  const records = rows.value
  if (!records.length) return
  const headers = Object.keys(records[0])
  const quote = (value: unknown) => `"${String(value ?? '').replaceAll('"', '""')}"`
  const csv = [headers.map(quote).join(','), ...records.map(row => headers.map(key => quote(row[key])).join(','))].join('\r\n')
  const link = document.createElement('a')
  link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }))
  link.download = `procurement-${reportType.value}-${new Date().toISOString().slice(0, 10)}.csv`
  link.click()
  URL.revokeObjectURL(link.href)
}

function currency(value: unknown) {
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 2 }).format(Number(value || 0))
}
function number(value: unknown, digits = 0) {
  return Number(value || 0).toLocaleString('en-PH', { maximumFractionDigits: digits, minimumFractionDigits: digits })
}
function formatDate(value: Date) {
  return new Intl.DateTimeFormat('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }).format(value)
}
function localDate(value: Date) {
  const month = String(value.getMonth() + 1).padStart(2, '0')
  const day = String(value.getDate()).padStart(2, '0')
  return `${value.getFullYear()}-${month}-${day}`
}
function supplierSeverity(status: string) {
  if (status === 'Top Performer') return 'success'
  if (status === 'Good') return 'info'
  if (status === 'Needs Review') return 'warn'
  return 'secondary'
}

onMounted(loadReport)
</script>
