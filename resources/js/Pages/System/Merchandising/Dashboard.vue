<template>
  <div class="module-dashboard space-y-5 pb-6 text-sm">
    <header class="dashboard-hero flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-950">Product Catalog Performance</h1>
        <p class="mt-1 text-sm text-slate-600">See which products are selling and how each store branch is performing.</p>
        <p class="mt-1 text-xs text-slate-500">Includes POS, wholesale, and paid online orders for the selected period.</p>
      </div>
      <div class="flex items-center gap-2">
        <Select v-model="days" :options="periods" optionLabel="label" optionValue="value" class="w-40" aria-label="Reporting period" @change="loadDashboard" />
        <Button icon="pi pi-refresh" label="Refresh" severity="secondary" outlined size="small" :loading="loading" @click="loadDashboard" />
      </div>
    </header>

    <div v-if="loading" class="space-y-5">
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"><Skeleton v-for="item in 4" :key="item" height="112px" class="rounded-2xl" /></div>
      <div class="grid gap-4 xl:grid-cols-3"><Skeleton height="290px" class="rounded-2xl xl:col-span-2" /><Skeleton height="290px" class="rounded-2xl" /></div>
      <Skeleton height="300px" class="rounded-2xl" />
    </div>
    <div v-else-if="loadError" class="rounded-2xl border border-red-200 bg-white p-6">
      <p class="font-semibold text-slate-900">Product performance could not load.</p>
      <p class="mt-1 text-slate-600">{{ loadError }}</p>
      <Button label="Try again" severity="warn" size="small" class="mt-4" @click="loadDashboard" />
    </div>
    <template v-else-if="data">
      <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <Card v-for="metric in metrics" :key="metric.label" class="border shadow-sm" :class="metric.highlight ? 'border-orange-200' : 'border-slate-200'">
          <template #content>
            <div class="flex items-center justify-between gap-2">
              <p class="text-xs font-medium text-slate-600">{{ metric.label }}</p>
              <i :class="[metric.icon, 'text-orange-600']" />
            </div>
            <p class="mt-2 text-2xl font-semibold text-slate-950">{{ metric.value }}</p>
            <p class="mt-2 text-xs text-slate-500">{{ metric.caption }}</p>
          </template>
        </Card>
      </section>

      <section class="grid gap-4 xl:grid-cols-3">
        <Card class="border border-slate-200 shadow-sm xl:col-span-2">
          <template #title>
            <div>
              <h2 class="text-base font-semibold">Store-wide sales trend</h2>
              <p class="mt-1 text-xs font-normal text-slate-500">Sales from all active store branches</p>
            </div>
          </template>
          <template #content>
            <Chart v-if="hasSales" type="line" :data="salesChart" :options="lineOptions" class="h-64" />
            <div v-else class="flex h-64 items-center justify-center text-sm text-slate-500">No product sales in this period.</div>
          </template>
        </Card>
        <Card class="border border-slate-200 shadow-sm">
          <template #title>
            <h2 class="text-base font-semibold">Branch sales comparison</h2>
            <p class="mt-1 text-xs font-normal text-slate-500">Sales by branch for this period</p>
          </template>
          <template #content>
            <Chart v-if="branchesWithSales.length" type="bar" :data="branchChart" :options="branchChartOptions" class="h-64" />
            <div v-else class="flex h-64 items-center justify-center text-sm text-slate-500">No branch sales in this period.</div>
          </template>
        </Card>
      </section>

      <section class="grid gap-4 xl:grid-cols-2">
        <Card class="border border-slate-200 shadow-sm">
          <template #title>
            <div class="flex items-center justify-between gap-2">
              <div>
                <h2 class="text-base font-semibold">Top-performing products</h2>
                <p class="mt-1 text-xs font-normal text-slate-500">Ranked by item sales across branches</p>
              </div>
              <Button label="Product catalog" text size="small" @click="go('merchandising.products')" />
            </div>
          </template>
          <template #content>
            <DataTable :value="topProducts" size="small" rowHover responsiveLayout="scroll">
              <Column header="Product">
                <template #body="{ data: item }">
                  <button class="text-left font-medium text-slate-900 hover:text-orange-600" @click="go('merchandising.products.view', item.id)">{{ item.name }}</button>
                  <p class="text-xs text-slate-500">{{ item.sku || 'No SKU' }}</p>
                </template>
              </Column>
              <Column field="units" header="Units sold" />
              <Column header="Branches">
                <template #body="{ data: item }">{{ item.branches_selling }} / {{ summary.branch_count || 0 }}</template>
              </Column>
              <Column header="Sales">
                <template #body="{ data: item }"><span class="font-semibold text-slate-900">{{ money(item.sales) }}</span></template>
              </Column>
              <template #empty><div class="py-8 text-center text-sm text-slate-500">No product sales in this period.</div></template>
            </DataTable>
          </template>
        </Card>

        <Card class="border border-slate-200 shadow-sm">
          <template #title>
            <h2 class="text-base font-semibold">Branch performance</h2>
            <p class="mt-1 text-xs font-normal text-slate-500">Compare sales, units, and product availability</p>
          </template>
          <template #content>
            <DataTable :value="branchPerformance" size="small" rowHover responsiveLayout="scroll">
              <Column header="Branch">
                <template #body="{ data: branch }">
                  <span class="font-medium text-slate-900">{{ branch.name }}</span>
                  <p class="mt-0.5 text-xs text-slate-500">{{ formatLabel(branch.branch_type) }}</p>
                </template>
              </Column>
              <Column header="Sales">
                <template #body="{ data: branch }"><span class="font-semibold">{{ money(branch.sales) }}</span></template>
              </Column>
              <Column field="units_sold" header="Units" />
              <Column field="products_sold" header="Products" />
              <Column header="Available stock">
                <template #body="{ data: branch }">{{ number(branch.available_units) }}</template>
              </Column>
              <Column header="Change">
                <template #body="{ data: branch }">
                  <span v-if="branch.sales_change_percent !== null" class="text-xs font-medium" :class="branch.sales_change_percent >= 0 ? 'text-emerald-700' : 'text-red-600'">
                    <i :class="branch.sales_change_percent >= 0 ? 'pi pi-arrow-up-right' : 'pi pi-arrow-down-right'" class="mr-1" />{{ Math.abs(branch.sales_change_percent).toFixed(1) }}%
                  </span>
                  <span v-else class="text-xs text-slate-500">No prior sales</span>
                </template>
              </Column>
              <template #empty><div class="py-8 text-center text-sm text-slate-500">No active store branches found.</div></template>
            </DataTable>
          </template>
        </Card>
      </section>

      <p class="px-1 text-xs text-slate-500">Comparison is against the immediately preceding {{ data.period_days }} days. Stock counts show current branch availability.</p>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import merchandisingService from '../../../services/merchandising.service'
import Button from 'primevue/button'
import Card from 'primevue/card'
const Chart = defineAsyncComponent(() => import('primevue/chart'))
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'

type TrendPoint = { date: string; label: string; sales: number; units: number; orders: number }
type ProductPerformance = { id: number; name: string; sku?: string | null; units: number; sales: number; branches_selling: number }
type BranchPerformance = { id: number; name: string; branch_type: string; sales: number; previous_sales: number; sales_change_percent: number | null; units_sold: number; orders: number; products_sold: number; stocked_products: number; available_units: number }
type Summary = { sales: number; orders: number; average_order_value: number; units_sold: number; previous_sales: number; previous_units_sold: number; previous_orders: number; branches_with_sales: number; branch_count: number; active_listings: number; out_of_stock_products: number }
type DashboardData = { period_days: number; summary: Summary; sales_trend: TrendPoint[]; top_products: ProductPerformance[]; branch_performance: BranchPerformance[] }

const router = useRouter()
const periods = [{ label: 'Last 7 days', value: 7 }, { label: 'Last 30 days', value: 30 }, { label: 'Last 90 days', value: 90 }]
const days = ref(30)
const loading = ref(false)
const loadError = ref('')
const data = ref<DashboardData | null>(null)
const summary = computed(() => data.value?.summary || {} as Summary)
const trend = computed(() => data.value?.sales_trend || [])
const topProducts = computed(() => data.value?.top_products || [])
const branchPerformance = computed(() => data.value?.branch_performance || [])
const branchesWithSales = computed(() => branchPerformance.value.filter(branch => branch.sales > 0))
const hasSales = computed(() => trend.value.some(row => Number(row.sales) > 0))
const money = (value: unknown) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value) || 0)
const number = (value: unknown) => Number(value || 0).toLocaleString('en-PH')
const percentChange = (current: number, previous: number) => previous > 0 ? `${current >= previous ? '+' : ''}${(((current - previous) / previous) * 100).toFixed(1)}% vs previous period` : current > 0 ? 'New activity this period' : 'No activity this period'
const metrics = computed(() => [
  { label: 'Product sales', value: money(summary.value.sales), caption: percentChange(Number(summary.value.sales || 0), Number(summary.value.previous_sales || 0)), icon: 'pi pi-wallet', highlight: true },
  { label: 'Units sold', value: number(summary.value.units_sold), caption: percentChange(Number(summary.value.units_sold || 0), Number(summary.value.previous_units_sold || 0)), icon: 'pi pi-box' },
  { label: 'Paid orders', value: number(summary.value.orders), caption: percentChange(Number(summary.value.orders || 0), Number(summary.value.previous_orders || 0)), icon: 'pi pi-shopping-bag' },
  { label: 'Branches with sales', value: `${number(summary.value.branches_with_sales)} / ${number(summary.value.branch_count)}`, caption: `${number(summary.value.active_listings)} active products · ${number(summary.value.out_of_stock_products)} without stock`, icon: 'pi pi-building' },
])
const salesChart = computed(() => ({
  labels: trend.value.map(row => row.label),
  datasets: [
    { label: 'Product sales', data: trend.value.map(row => Number(row.sales)), borderColor: '#f97316', backgroundColor: 'rgba(249,115,22,.12)', fill: true, tension: .35, pointRadius: days.value === 90 ? 0 : 2, yAxisID: 'sales' },
    { label: 'Units sold', data: trend.value.map(row => Number(row.units)), borderColor: '#3b82f6', backgroundColor: '#3b82f6', tension: .3, pointRadius: days.value === 90 ? 0 : 2, yAxisID: 'units' },
  ],
}))
const branchChart = computed(() => ({
  labels: branchesWithSales.value.slice(0, 8).map(branch => branch.name),
  datasets: [{ label: 'Sales', data: branchesWithSales.value.slice(0, 8).map(branch => Number(branch.sales)), backgroundColor: '#fb923c', borderRadius: 5, maxBarThickness: 24 }],
}))
const lineOptions = {
  maintainAspectRatio: false,
  interaction: { mode: 'index', intersect: false },
  plugins: { legend: { position: 'bottom' as const, labels: { usePointStyle: true, boxWidth: 8 } } },
  scales: {
    x: { grid: { display: false }, ticks: { maxTicksLimit: 8 } },
    sales: { type: 'linear' as const, position: 'left' as const, beginAtZero: true, ticks: { callback: (value: any) => `₱${Number(value).toLocaleString('en-PH')}` } },
    units: { type: 'linear' as const, position: 'right' as const, beginAtZero: true, grid: { drawOnChartArea: false }, ticks: { precision: 0 } },
  },
}
const branchChartOptions = {
  indexAxis: 'y' as const,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { beginAtZero: true, ticks: { callback: (value: any) => `₱${Number(value).toLocaleString('en-PH')}` } },
    y: { grid: { display: false }, ticks: { autoSkip: false } },
  },
}
const formatLabel = (value: unknown) => String(value || '').replace(/_/g, ' ').replace(/\b\w/g, character => character.toUpperCase())
const go = (name: string, id?: number) => router.push(id ? { name, params: { id } } : { name })
const loadDashboard = async () => {
  loading.value = true
  loadError.value = ''
  try {
    const response = await merchandisingService.getDashboardOverview(days.value)
    if (response?.success === false) throw new Error(response.message || 'Unable to load dashboard.')
    data.value = response.data
  } catch (error: any) {
    loadError.value = error?.response?.data?.message || error?.message || 'Unable to load product performance.'
  } finally {
    loading.value = false
  }
}
onMounted(loadDashboard)
</script>
