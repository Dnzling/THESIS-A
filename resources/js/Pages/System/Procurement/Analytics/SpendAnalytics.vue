<template>
  <div class="module-dashboard space-y-5 pb-6 text-sm">
    <header class="dashboard-hero flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-orange-700">Procurement</p>
        <h1 class="mt-1 text-2xl font-semibold text-slate-950">Spend Analysis</h1>
        <p class="mt-1 text-sm text-slate-600">Review committed purchase-order spend by month, supplier, and product category.</p>
      </div>
      <div class="flex items-center gap-2">
        <Select v-model="selectedYear" :options="availableYears" aria-label="Spend analysis year" class="w-36" size="small" @change="loadSpendAnalytics" />
        <Button icon="pi pi-refresh" label="Refresh" size="small" severity="secondary" outlined :loading="loading" @click="loadSpendAnalytics" />
      </div>
    </header>

    <Message v-if="loadError" severity="error" :closable="false">{{ loadError }}</Message>

    <section v-if="loading" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <Skeleton v-for="item in 4" :key="item" height="6.5rem" class="rounded-xl" />
    </section>
    <section v-else-if="!loadError" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <Card v-for="metric in summaryCards" :key="metric.label" class="border border-slate-200 shadow-sm">
        <template #content>
          <p class="text-xs font-medium text-slate-500">{{ metric.label }}</p>
          <p class="mt-2 truncate text-xl font-semibold text-slate-950" :title="metric.value">{{ metric.value }}</p>
          <p class="mt-1 text-xs text-slate-500">{{ metric.hint }}</p>
        </template>
      </Card>
    </section>

    <section class="grid gap-4 xl:grid-cols-2">
      <Card class="border border-slate-200 shadow-sm">
        <template #title>
          <div>
            <h2 class="text-base font-semibold text-slate-900">Monthly committed spend</h2>
            <p class="mt-1 text-xs font-normal text-slate-500">Approved and active purchase orders in {{ selectedYear }}</p>
          </div>
        </template>
        <template #content>
          <Skeleton v-if="loading" height="18rem" class="rounded-lg" />
          <Chart v-else-if="hasMonthlySpend" type="line" :data="monthlyChartData" :options="lineOptions" class="h-72" />
          <div v-else class="flex h-72 items-center justify-center text-center text-sm text-slate-500">No committed spend recorded for this year.</div>
        </template>
      </Card>

      <Card class="border border-slate-200 shadow-sm">
        <template #title>
          <div>
            <h2 class="text-base font-semibold text-slate-900">Top suppliers by spend</h2>
            <p class="mt-1 text-xs font-normal text-slate-500">Top 10 suppliers based on committed purchase orders</p>
          </div>
        </template>
        <template #content>
          <Skeleton v-if="loading" height="18rem" class="rounded-lg" />
          <Chart v-else-if="suppliersData.length" type="bar" :data="suppliersChartData" :options="supplierChartOptions" class="h-72" />
          <div v-else class="flex h-72 items-center justify-center text-center text-sm text-slate-500">No supplier spend recorded for this year.</div>
        </template>
      </Card>
    </section>

    <Card class="border border-slate-200 shadow-sm">
      <template #title>
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h2 class="text-base font-semibold text-slate-900">Spend by category</h2>
            <p class="mt-1 text-xs font-normal text-slate-500">Purchase-order item totals, with each category's share of annual spend</p>
          </div>
          <span class="text-xs font-normal text-slate-500">{{ categoryBreakdown.length }} categories</span>
        </div>
      </template>
      <template #content>
        <DataTable v-if="!loading" :value="categoryBreakdown" paginator :rows="10" :rows-per-page-options="[10, 20, 50]" size="small" row-hover responsive-layout="scroll">
          <Column field="category_name" header="Category" />
          <Column field="spend" header="Committed spend">
            <template #body="{ data }"><span class="font-semibold text-slate-900">{{ currency(data.spend) }}</span></template>
          </Column>
          <Column field="percentage" header="Share of total">
            <template #body="{ data }">
              <div class="flex min-w-36 items-center gap-2">
                <ProgressBar :value="data.percentage" class="h-2 flex-1" />
                <span class="w-12 text-right text-xs text-slate-600">{{ data.percentage }}%</span>
              </div>
            </template>
          </Column>
          <template #empty><div class="py-8 text-center text-sm text-slate-500">No category spend recorded for {{ selectedYear }}.</div></template>
        </DataTable>
        <div v-else class="space-y-2"><Skeleton v-for="row in 5" :key="row" height="2.5rem" class="rounded-md" /></div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Select from 'primevue/select'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import ProgressBar from 'primevue/progressbar'
import Skeleton from 'primevue/skeleton'
import Message from 'primevue/message'
import Chart from 'primevue/chart'
import procurementService from '../../../../services/procurement.service'

const currentYear = new Date().getFullYear()
const availableYears = Array.from({ length: 6 }, (_, index) => currentYear - index)
const selectedYear = ref(currentYear)
const loading = ref(false)
const loadError = ref('')
const monthlyData = ref<any[]>([])
const suppliersData = ref<any[]>([])
const categoryData = ref<any[]>([])
const hasMonthlySpend = computed(() => monthlyData.value.some(row => row.amount > 0))
const totalSpend = computed(() => monthlyData.value.reduce((sum, row) => sum + Number(row.amount || 0), 0))
const activeMonths = computed(() => monthlyData.value.filter(row => Number(row.amount) > 0).length)
const averageMonthlySpend = computed(() => activeMonths.value ? totalSpend.value / activeMonths.value : 0)
const totalPurchaseOrders = computed(() => monthlyData.value.reduce((sum, row) => sum + Number(row.po_count || 0), 0))
const topSupplier = computed(() => suppliersData.value[0] || null)
const categoryBreakdown = computed(() => categoryData.value.map(category => ({
  ...category,
  percentage: totalSpend.value > 0 ? Math.min((Number(category.spend || 0) / totalSpend.value) * 100, 100) : 0,
})))
const summaryCards = computed(() => [
  { label: 'Committed spend', value: currency(totalSpend.value), hint: `Selected year: ${selectedYear.value}` },
  { label: 'Purchase orders', value: number(totalPurchaseOrders.value), hint: 'Approved or active orders' },
  { label: 'Average active-month spend', value: currency(averageMonthlySpend.value), hint: `${activeMonths.value} month(s) with spend` },
  { label: 'Top supplier', value: topSupplier.value?.supplier_name || 'No supplier spend', hint: topSupplier.value ? currency(topSupplier.value.amount) : `No spend in ${selectedYear.value}` },
])

const monthlyChartData = computed(() => ({
  labels: monthlyData.value.map(row => monthLabel(row.month)),
  datasets: [{
    label: 'Committed spend',
    data: monthlyData.value.map(row => Number(row.amount || 0)),
    borderColor: '#f97316',
    backgroundColor: 'rgba(249, 115, 22, .12)',
    fill: true,
    tension: .32,
    pointRadius: 3,
  }],
}))
const suppliersChartData = computed(() => ({
  labels: suppliersData.value.slice(0, 10).map(row => row.supplier_name),
  datasets: [{ label: 'Committed spend', data: suppliersData.value.slice(0, 10).map(row => Number(row.amount || 0)), backgroundColor: '#fb923c', borderRadius: 5, maxBarThickness: 24 }],
}))
const lineOptions = {
  maintainAspectRatio: false,
  responsive: true,
  interaction: { mode: 'index' as const, intersect: false },
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { display: false } },
    y: { beginAtZero: true, ticks: { callback: (value: any) => currency(value) } },
  },
}
const supplierChartOptions = {
  maintainAspectRatio: false,
  responsive: true,
  indexAxis: 'y' as const,
  plugins: { legend: { display: false } },
  scales: {
    x: { beginAtZero: true, ticks: { callback: (value: any) => currency(value) } },
    y: { grid: { display: false }, ticks: { autoSkip: false } },
  },
}

async function loadSpendAnalytics() {
  loading.value = true
  loadError.value = ''
  try {
    const response = await procurementService.getSpendAnalytics({ year: selectedYear.value })
    if (response?.success === false) throw new Error(response.message || 'Unable to load procurement spend.')
    const data = response?.data || {}
    monthlyData.value = buildMonthlySeries(Array.isArray(data.monthly) ? data.monthly : [], selectedYear.value)
    suppliersData.value = (Array.isArray(data.suppliers) ? data.suppliers : []).map(row => ({
      ...row,
      supplier_name: row.supplier_name || row.name || 'Unknown supplier',
      amount: Number(row.amount || 0),
    }))
    categoryData.value = (Array.isArray(data.categories) ? data.categories : []).map(row => ({
      ...row,
      category_name: row.category_name || 'Uncategorized',
      spend: Number(row.spend || 0),
    }))
  } catch (error: any) {
    loadError.value = error?.response?.data?.message || error?.message || 'Failed to load spend analysis.'
    monthlyData.value = []
    suppliersData.value = []
    categoryData.value = []
  } finally {
    loading.value = false
  }
}

function buildMonthlySeries(raw: any[], year: number) {
  const byMonth = new Map(raw.map(row => [row.month, {
    month: row.month,
    amount: Number(row.amount || 0),
    po_count: Number(row.po_count || 0),
  }]))
  return Array.from({ length: 12 }, (_, index) => {
    const month = `${year}-${String(index + 1).padStart(2, '0')}`
    return byMonth.get(month) || { month, amount: 0, po_count: 0 }
  })
}
function currency(value: unknown) {
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 2 }).format(Number(value || 0))
}
function number(value: unknown) {
  return Number(value || 0).toLocaleString('en-PH')
}
function monthLabel(value: string) {
  const month = Number(String(value).split('-')[1])
  return month >= 1 && month <= 12 ? new Intl.DateTimeFormat('en-PH', { month: 'short' }).format(new Date(2000, month - 1, 1)) : value
}

onMounted(loadSpendAnalytics)
</script>
