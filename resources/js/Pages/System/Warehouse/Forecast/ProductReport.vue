<template>
  <div class="space-y-4 p-4 text-xs md:p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div class="flex items-start gap-3">
        <Button icon="pi pi-chevron-left" aria-label="Go back" v-tooltip.top="'Go back'" severity="secondary" outlined rounded size="small" class="!h-9 !w-9 !p-0" @click="goBack" />
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-orange-600">Warehouse report</p>
          <h1 class="mt-1 text-xl font-semibold text-slate-900">{{ report?.product?.name || 'Product forecast' }}<span v-if="report?.product?.variation_name" class="font-normal text-slate-500"> · {{ report.product.variation_name }}</span></h1>
          <p class="mt-1 text-slate-500">{{ report?.product?.sku || 'Loading product details…' }}<span v-if="report?.product?.branch_name"> · {{ report.product.branch_name }}</span></p>
        </div>
      </div>
      <div v-if="report?.settings" class="flex flex-wrap gap-2">
        <Badge :value="report.settings.rule_source" severity="info" />
        <Badge :value="report.settings.basis_type === 'demand_lead_time' ? 'Demand and lead time' : 'Reorder point'" severity="secondary" />
      </div>
    </div>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <Card class="border border-slate-200">
      <template #content>
        <div class="flex flex-wrap items-end gap-3">
          <div class="w-full sm:w-72">
            <label class="mb-1 block font-medium text-slate-700">Demand history period</label>
            <DatePicker v-model="dateRange" selectionMode="range" dateFormat="M d, yy" :manualInput="false" :maxDate="today" showIcon size="small" class="w-full" placeholder="Select date range" @date-select="onDateSelect" />
          </div>
          <div class="w-full sm:w-44">
            <label class="mb-1 block font-medium text-slate-700">Projection horizon</label>
            <Select v-model="forecastDays" :options="forecastOptions" optionLabel="label" optionValue="value" size="small" fluid @change="loadReport" />
          </div>
          <div class="w-full sm:w-40">
            <label class="mb-1 block font-medium text-slate-700">Report grouping</label>
            <Select v-model="grouping" :options="groupingOptions" optionLabel="label" optionValue="value" size="small" fluid />
          </div>
          <div class="ml-auto flex gap-2">
            <Button label="Export CSV" icon="pi pi-download" severity="secondary" outlined size="small" :disabled="!report" @click="exportCsv" />
            <Button icon="pi pi-refresh" aria-label="Refresh report" v-tooltip.top="'Refresh report'" severity="secondary" outlined size="small" :loading="loading" @click="loadReport" />
          </div>
        </div>
        <p class="mt-3 text-[11px] text-slate-500">{{ report?.demand_source || 'History uses completed POS and delivered e-commerce sales, net of recorded e-commerce returns.' }} Date ranges are limited to one year.</p>
      </template>
    </Card>

    <div v-if="loading && !report" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
      <Card v-for="n in 6" :key="n" class="border border-slate-200"><template #content><Skeleton height="14px" width="45%" /><Skeleton class="mt-3" height="26px" width="60%" /><Skeleton class="mt-2" height="12px" width="75%" /></template></Card>
    </div>

    <template v-if="report">
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <Card v-for="metric in metricCards" :key="metric.label" class="border border-slate-200">
          <template #content>
            <p class="text-slate-500">{{ metric.label }}</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-2">
              <p class="text-xl font-semibold text-slate-900">{{ metric.value }}</p>
              <Badge v-if="metric.badge" :value="metric.badge" :severity="metric.severity || 'secondary'" />
            </div>
            <p class="mt-1 text-[11px] text-slate-500">{{ metric.hint }}</p>
          </template>
        </Card>
      </div>

      <Card class="border border-slate-200">
        <template #content>
          <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
            <div>
              <h2 class="text-sm font-semibold text-slate-900">Observed demand and projection</h2>
              <p class="mt-1 text-slate-500">Daily sales history with a straight-line projection based on the selected period average.</p>
            </div>
            <Badge :value="`${forecastDays}-day projection`" severity="warn" />
          </div>
          <Chart v-if="Number(report.metrics.units_sold) > 0" type="bar" :data="chartData" :options="chartOptions" class="h-72" />
          <div v-else class="py-12 text-center text-slate-500">No completed sales were recorded in this period, so demand-based projections are zero.</div>
        </template>
      </Card>

      <div class="grid gap-4 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.8fr)]">
        <Card class="border border-slate-200">
          <template #content>
            <div class="mb-3">
              <h2 class="text-sm font-semibold text-slate-900">Demand detail</h2>
              <p class="mt-1 text-slate-500">Recorded units grouped by {{ groupingLabel }} across the selected range.</p>
            </div>
            <DataTable :value="groupedSeries" size="small" rowHover paginator :rows="12" responsiveLayout="scroll">
              <template #empty><div class="py-8 text-center text-slate-500">No demand records for this period.</div></template>
              <Column field="period" header="Period" />
              <Column header="Units sold"><template #body="{ data }">{{ quantity(data.units) }}</template></Column>
              <Column header="Daily average"><template #body="{ data }">{{ quantity(data.average) }}</template></Column>
              <Column header="Share of period"><template #body="{ data }">{{ data.share }}%</template></Column>
            </DataTable>
          </template>
        </Card>

        <Card class="border border-slate-200">
          <template #content>
            <h2 class="text-sm font-semibold text-slate-900">Replenishment inputs</h2>
            <p class="mt-1 text-slate-500">Settings applied to this product in this warehouse.</p>
            <dl class="mt-4 divide-y divide-slate-100">
              <div v-for="detail in settingRows" :key="detail.label" class="flex items-start justify-between gap-3 py-2.5">
                <dt class="text-slate-500">{{ detail.label }}</dt>
                <dd class="text-right font-medium text-slate-800">{{ detail.value }}</dd>
              </div>
            </dl>
            <div class="mt-4 rounded-lg border border-orange-100 bg-orange-50/70 p-3 leading-5 text-slate-700">
              <p class="font-semibold text-slate-900">How this projection is calculated</p>
              <p class="mt-1">Average daily demand = net units sold ÷ calendar days in the selected history. Forecast units = average daily demand × projection horizon. Suggested replenishment covers projected demand and safety stock after available and incoming units.</p>
            </div>
            <p class="mt-3 text-[11px] leading-5 text-slate-500">This report is a planning estimate. It does not create a purchase requisition or change inventory quantities.</p>
          </template>
        </Card>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import Chart from 'primevue/chart'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import DatePicker from 'primevue/datepicker'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'

const props = defineProps<{ inventoryId: number }>()
const router = useRouter()
const loading = ref(false)
const error = ref('')
const report = ref<any>(null)
const grouping = ref('week')
const forecastDays = ref(30)
const forecastOptions = [7, 14, 30, 60, 90].map(value => ({ label: `${value} days`, value }))
const groupingOptions = [{ label: 'Daily', value: 'day' }, { label: 'Weekly', value: 'week' }, { label: 'Monthly', value: 'month' }]
const groupingLabel = computed(() => groupingOptions.find(option => option.value === grouping.value)?.label.toLowerCase() || 'weekly')
const today = new Date()
today.setHours(0, 0, 0, 0)
const initialStart = new Date(today)
initialStart.setDate(initialStart.getDate() - 29)
const dateRange = ref<Date[]>([initialStart, new Date(today)])
const quantity = (value: any, digits = 1) => Number(value || 0).toLocaleString('en-PH', { maximumFractionDigits: digits })
const moneyPercent = (value: number | null) => value === null ? 'No previous-period baseline' : `${value > 0 ? '+' : ''}${quantity(value)}% vs previous period`
const formatDate = (date: Date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
const dateLabel = (value: string) => new Date(`${value}T00:00:00`).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })

const metricCards = computed(() => {
  const metrics = report.value?.metrics || {}
  const unit = report.value?.product?.unit || 'units'
  const daysCover = metrics.days_cover == null ? 'No demand history' : `${quantity(metrics.days_cover)} days`
  return [
    { label: 'Net units sold', value: quantity(metrics.units_sold), hint: `During ${report.value?.period?.days || 0} selected calendar days`, badge: moneyPercent(metrics.change_percent), severity: metrics.change_percent > 0 ? 'success' : metrics.change_percent < 0 ? 'danger' : 'secondary' },
    { label: 'Average daily demand', value: `${quantity(metrics.average_daily_demand, 3)} ${unit}/day`, hint: 'Includes days with no recorded sales' },
    { label: `Projected demand · ${metrics.forecast_days} days`, value: `${quantity(metrics.forecast_units)} ${unit}`, hint: 'Based on the selected history average', badge: `${metrics.forecast_days} days`, severity: 'info' },
    { label: 'Current supply', value: `${quantity(metrics.available)} available`, hint: `${quantity(metrics.incoming)} incoming units` },
    { label: 'Estimated days of cover', value: daysCover, hint: 'Available stock ÷ average daily demand' },
    { label: 'Forecast order gap', value: `${quantity(metrics.suggested_replenishment)} ${unit}`, hint: `Projected stock after horizon: ${quantity(metrics.projected_stock)}` },
  ]
})

const chartData = computed(() => {
  const series = report.value?.series || []
  if (!series.length) return { labels: [], datasets: [] }
  const average = Number(report.value?.metrics?.average_daily_demand || 0)
  const lastDay = new Date(`${series[series.length - 1].date}T00:00:00`)
  const futureDates = Array.from({ length: Number(forecastDays.value) }, (_, index) => {
    const next = new Date(lastDay)
    next.setDate(next.getDate() + index + 1)
    return formatDate(next)
  })
  return {
    labels: [...series.map((row: any) => dateLabel(row.date)), ...futureDates.map(dateLabel)],
    datasets: [
      { type: 'bar', label: 'Observed net sales', data: [...series.map((row: any) => Number(row.units)), ...futureDates.map(() => null)], backgroundColor: 'rgba(249,115,22,0.72)', borderRadius: 3 },
      { type: 'line', label: 'Projected daily demand', data: [...series.map(() => null), ...futureDates.map(() => average)], borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,0.12)', pointRadius: 0, borderDash: [5, 4], tension: 0, spanGaps: false },
    ],
  }
})
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index', intersect: false },
  plugins: { legend: { display: true, position: 'bottom' } },
  scales: { y: { beginAtZero: true, title: { display: true, text: 'Units' } }, x: { ticks: { maxTicksLimit: 16, maxRotation: 0 } } },
}

const groupedSeries = computed(() => {
  const groups = new Map<string, { period: string; units: number; days: number }>()
  for (const row of report.value?.series || []) {
    const date = new Date(`${row.date}T00:00:00`)
    let key = row.date
    let label = dateLabel(row.date)
    if (grouping.value === 'week') {
      const mondayOffset = (date.getDay() + 6) % 7
      date.setDate(date.getDate() - mondayOffset)
      key = formatDate(date)
      label = `Week of ${dateLabel(key)}`
    } else if (grouping.value === 'month') {
      key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`
      label = date.toLocaleDateString('en-PH', { month: 'long', year: 'numeric' })
    }
    const group = groups.get(key) || { period: label, units: 0, days: 0 }
    group.units += Number(row.units || 0)
    group.days += 1
    groups.set(key, group)
  }
  const total = Number(report.value?.metrics?.units_sold || 0)
  return [...groups.values()].map(group => ({ ...group, average: group.days ? group.units / group.days : 0, share: total > 0 ? quantity(group.units / total * 100, 1) : '0' })).reverse()
})

const settingRows = computed(() => {
  const settings = report.value?.settings || {}
  const unit = report.value?.product?.unit || 'units'
  return [
    { label: 'Rule source', value: settings.rule_source || '—' },
    { label: 'Calculation basis', value: settings.basis_type === 'demand_lead_time' ? 'Demand and lead time' : 'Reorder point' },
    { label: 'Reorder point', value: `${quantity(settings.reorder_point)} ${unit}` },
    { label: 'Refill quantity', value: `${quantity(settings.refill_quantity)} ${unit}` },
    { label: 'Maximum stock', value: settings.maximum_stock == null ? 'Not configured' : `${quantity(settings.maximum_stock)} ${unit}` },
    { label: 'Rule target stock', value: `${quantity(report.value?.metrics?.rule_target_stock)} ${unit}` },
    { label: 'Rule suggested quantity', value: `${quantity(report.value?.metrics?.rule_suggested_quantity)} ${unit}` },
    { label: 'Safety stock', value: `${quantity(settings.safety_stock)} ${unit}` },
    { label: 'Supplier lead time', value: settings.lead_time_days == null ? 'Not configured' : `${settings.lead_time_days} days` },
    { label: 'Review period', value: settings.review_period_days == null ? 'Not configured' : `${settings.review_period_days} days` },
    { label: 'Configured daily demand', value: settings.average_daily_demand == null ? 'Not configured' : `${quantity(settings.average_daily_demand, 3)} ${unit}/day` },
  ]
})

function onDateSelect() {
  if (dateRange.value?.length === 2 && dateRange.value[0] && dateRange.value[1]) void loadReport()
}
async function loadReport() {
  if (!dateRange.value?.[0] || !dateRange.value?.[1]) return
  loading.value = true
  error.value = ''
  try {
    const response = await axios.get('/api/warehouse/forecasting', {
      params: {
        inventory_id: Number(props.inventoryId),
        branch_id: Number(report.value?.product?.branch_id || 0) || undefined,
        from_date: formatDate(dateRange.value[0]),
        to_date: formatDate(dateRange.value[1]),
        forecast_days: forecastDays.value,
      },
    })
    report.value = response.data.data
  } catch (exception: any) {
    error.value = exception.response?.data?.message || 'Could not load this product forecast.'
  } finally {
    loading.value = false
  }
}
function exportCsv() {
  if (!report.value) return
  const lines = [
    ['Product', report.value.product.name],
    ['SKU', report.value.product.sku],
    ['Branch', report.value.product.branch_name],
    ['History from', report.value.period.from],
    ['History to', report.value.period.to],
    ['Forecast horizon days', report.value.metrics.forecast_days],
    ['Net units sold', report.value.metrics.units_sold],
    ['Average daily demand', report.value.metrics.average_daily_demand],
    ['Projected demand', report.value.metrics.forecast_units],
    ['Available', report.value.metrics.available],
    ['Incoming', report.value.metrics.incoming],
    ['Projected replenishment', report.value.metrics.suggested_replenishment],
    [],
    ['Date', 'Net units sold'],
    ...report.value.series.map((row: any) => [row.date, row.units]),
  ]
  const csv = lines.map(row => row.map((value: any) => `"${String(value ?? '').replaceAll('"', '""')}"`).join(',')).join('\r\n')
  const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }))
  const anchor = document.createElement('a')
  anchor.href = url
  anchor.download = `forecast-${report.value.product.sku || props.inventoryId}-${report.value.period.from}-${report.value.period.to}.csv`
  anchor.click()
  URL.revokeObjectURL(url)
}
function goBack() {
  if (window.history.length > 1) router.back()
  else router.push({ name: 'warehouse.reorder' })
}

onMounted(loadReport)
</script>
