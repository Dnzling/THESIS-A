<template>
  <div class="module-dashboard dashboard--ecommerce space-y-5 pb-6 text-sm">
    <header class="dashboard-hero flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-950">E-Commerce Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">Online orders, product performance, and storefront readiness across your store.</p>
      </div>
      <div class="flex items-center gap-2">
        <Select v-model="days" :options="periods" optionLabel="label" optionValue="value" class="w-40" aria-label="Reporting period" @change="loadDashboard" />
        <Button icon="pi pi-refresh" label="Refresh" severity="secondary" outlined size="small" :loading="loading" @click="loadDashboard" />
      </div>
    </header>

    <div v-if="loading" class="space-y-5">
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"><Skeleton v-for="item in 4" :key="item" height="118px" class="rounded-2xl" /></div>
      <div class="grid gap-4 xl:grid-cols-3"><Skeleton height="290px" class="rounded-2xl xl:col-span-2" /><Skeleton height="290px" class="rounded-2xl" /></div>
      <Skeleton v-for="item in 2" :key="item" height="190px" class="rounded-2xl" />
    </div>
    <div v-else-if="loadError" class="dashboard-panel rounded-2xl border border-red-200 bg-white p-6"><p class="font-semibold text-slate-900">The E-Commerce dashboard could not load.</p><p class="mt-1 text-slate-500">{{ loadError }}</p><Button label="Try again" severity="warn" size="small" class="mt-4" @click="loadDashboard" /></div>
    <template v-else-if="data">
      <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <Card v-for="(metric, index) in salesMetrics" :key="metric.label" class="dashboard-panel border shadow-sm" :class="index === 0 ? 'border-orange-200' : 'border-slate-200'">
          <template #content>
            <div class="flex items-start justify-between gap-2"><p class="text-xs font-medium text-slate-500">{{ metric.label }}</p><i :class="[metric.icon, 'text-orange-500']" /></div>
            <p class="mt-2 text-2xl font-semibold text-slate-950">{{ metric.value }}</p>
            <p class="mt-2 text-xs text-slate-500">{{ metric.caption }}</p>
          </template>
        </Card>
      </section>

      <section class="grid gap-4 xl:grid-cols-3">
        <Card class="border border-slate-200 shadow-sm xl:col-span-2">
          <template #title><div class="flex flex-wrap items-center justify-between gap-2"><div><h2 class="text-base font-semibold">Online order value</h2><p class="mt-1 text-xs font-normal text-slate-500">Non-cancelled online order value</p></div><Select v-model="orderValueTrend.period.value" :options="trendPeriods" optionLabel="label" optionValue="value" class="w-36" aria-label="Online order value period" /></div></template>
          <template #content><Skeleton v-if="orderValueTrend.loading.value" height="16rem" class="rounded-xl" /><div v-else-if="orderValueTrend.error.value" class="flex h-64 flex-col items-center justify-center gap-2 text-sm text-red-600">{{ orderValueTrend.error.value }}<Button text label="Try again" @click="orderValueTrend.reload" /></div><Chart v-else-if="hasSales" type="line" :data="salesChart" :options="lineOptions" class="h-64" /><div v-else class="flex h-64 items-center justify-center text-sm text-slate-500">No online sales in this period.</div></template>
        </Card>
        <Card class="border border-slate-200 shadow-sm">
          <template #title><h2 class="text-base font-semibold">Order status</h2><p class="mt-1 text-xs font-normal text-slate-500">All online orders in the selected period</p></template>
          <template #content><Chart v-if="statusRows.length" type="doughnut" :data="statusChart" :options="doughnutOptions" class="mx-auto h-64 max-w-72" /><div v-else class="flex h-64 items-center justify-center text-sm text-slate-500">No online orders yet.</div></template>
        </Card>
      </section>

      <section class="grid gap-4 xl:grid-cols-3">
        <Card class="border border-slate-200 shadow-sm xl:col-span-2">
          <template #title><div class="flex items-center justify-between gap-2"><div><h2 class="text-base font-semibold">Best selling products</h2><p class="mt-1 text-xs font-normal text-slate-500">Item sales from valid online orders</p></div><Button label="Product listings" text size="small" @click="go('merchandising.products')" /></div></template>
          <template #content><Chart v-if="topProducts.length" type="bar" :data="productChart" :options="barOptions" class="h-64" /><div v-else class="flex h-64 items-center justify-center text-sm text-slate-500">Product performance appears after your first online order.</div></template>
        </Card>
        <Card class="border border-slate-200 shadow-sm">
          <template #title><h2 class="text-base font-semibold">Daily online orders</h2><p class="mt-1 text-xs font-normal text-slate-500">Order activity over the selected period</p></template>
          <template #content><Chart v-if="hasOrders" type="bar" :data="ordersChart" :options="ordersOptions" class="h-64" /><div v-else class="flex h-64 items-center justify-center text-sm text-slate-500">No orders in this period.</div></template>
        </Card>
      </section>

      <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <Card v-for="metric in storefrontMetrics" :key="metric.label" class="border border-slate-200 shadow-sm"><template #content><div class="flex items-center justify-between"><span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ metric.label }}</span><i :class="metric.icon" class="text-orange-500" /></div><p class="mt-2 text-2xl font-bold text-slate-950">{{ metric.value }}</p><p class="mt-1 text-xs text-slate-500">{{ metric.caption }}</p></template></Card>
      </section>

      <section class="grid gap-4 xl:grid-cols-2">
        <Card class="border border-slate-200 shadow-sm">
          <template #title><div class="flex items-center justify-between gap-2"><h2 class="text-base font-semibold">Top product performance</h2><Button label="View all" text size="small" @click="go('merchandising.products')" /></div></template>
          <template #content><DataTable :value="topProducts" size="small" stripedRows responsiveLayout="scroll"><Column header="Product"><template #body="{ data: item }"><button class="text-left font-medium text-slate-900 hover:text-orange-600" @click="go('merchandising.products.view', item.id)">{{ item.name }}</button><p class="text-xs text-slate-500">{{ item.sku || 'No SKU' }}</p></template></Column><Column field="units" header="Units" /><Column header="Item sales"><template #body="{ data: item }">{{ money(item.sales) }}</template></Column><template #empty><div class="py-8 text-center text-sm text-slate-500">No product sales yet.</div></template></DataTable></template>
        </Card>
        <Card class="border border-slate-200 shadow-sm">
          <template #title><h2 class="text-base font-semibold">Recent online orders</h2></template>
          <template #content><DataTable :value="data.recent_orders || []" size="small" stripedRows responsiveLayout="scroll"><Column field="order_number" header="Order" /><Column field="shipping_name" header="Customer" /><Column header="Status"><template #body="{ data: item }"><Tag :value="statusLabel(item.status)" :severity="statusSeverity(item.status)" /></template></Column><Column header="Total"><template #body="{ data: item }">{{ money(item.total_amount) }}</template></Column><template #empty><div class="py-8 text-center text-sm text-slate-500">No online orders in this period.</div></template></DataTable></template>
        </Card>
      </section>

      <section class="grid gap-4 xl:grid-cols-2">
        <Card class="border border-slate-200 shadow-sm"><template #title><h2 class="text-base font-semibold">Listings needing an image</h2></template><template #content><DataTable :value="data.missing_images || []" size="small"><Column header="Product"><template #body="{ data: item }"><button class="text-left font-medium text-slate-900 hover:text-orange-600" @click="go('merchandising.products.edit', item.id)">{{ item.name }}</button><p class="text-xs text-slate-500">{{ item.sku || 'No SKU' }}</p></template></Column><Column field="category" header="Category" /><template #empty><div class="py-7 text-center text-sm text-slate-500">All active listings have a main image.</div></template></DataTable></template></Card>
        <Card class="border border-slate-200 shadow-sm"><template #title><h2 class="text-base font-semibold">Listings unavailable to shoppers</h2></template><template #content><DataTable :value="data.out_of_stock_products || []" size="small"><Column header="Product"><template #body="{ data: item }"><button class="text-left font-medium text-slate-900 hover:text-orange-600" @click="go('merchandising.products.view', item.id)">{{ item.name }}</button><p class="text-xs text-slate-500">{{ item.sku || 'No SKU' }}</p></template></Column><Column field="category" header="Category" /><template #empty><div class="py-7 text-center text-sm text-slate-500">All active listings have available stock.</div></template></DataTable></template></Card>
      </section>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import merchandisingService from '../../../services/merchandising.service'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Chart from 'primevue/chart'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import Tag from 'primevue/tag'
import { trendPeriods, useDashboardTrend } from '@/composables/useDashboardTrend'

type TrendPoint = { date: string; label: string; sales: number; orders: number }
type ProductPerformance = { id: number; name: string; sku?: string | null; units: number; sales: number }
type DashboardData = { period_days: number; summary: Record<string, number>; sales_trend: TrendPoint[]; status_breakdown: { status: string; count: number }[]; top_products: ProductPerformance[]; missing_images: any[]; out_of_stock_products: any[]; recent_orders: any[] }

const router = useRouter()
const orderValueTrend = useDashboardTrend('/api/product-catalog/dashboard/trend')
const periods = [{ label: 'Last 7 days', value: 7 }, { label: 'Last 30 days', value: 30 }, { label: 'Last 90 days', value: 90 }]
const days = ref(30)
const loading = ref(false)
const loadError = ref('')
const data = ref<DashboardData | null>(null)
const summary = computed(() => data.value?.summary || {})
const trend = computed(() => data.value?.sales_trend || [])
const topProducts = computed(() => data.value?.top_products || [])
const statusRows = computed(() => data.value?.status_breakdown || [])
const hasSales = computed(() => orderValueTrend.points.value.some((row: any) => Number(row.value) > 0))
const hasOrders = computed(() => trend.value.some(row => Number(row.orders) > 0))
const money = (value: unknown) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value) || 0)
const number = (value: unknown) => Number(value || 0).toLocaleString('en-PH')
const change = (current: number, previous: number) => previous > 0 ? `${current >= previous ? '+' : ''}${(((current - previous) / previous) * 100).toFixed(1)}% vs prior period` : (current > 0 ? 'New activity this period' : 'No activity this period')
const salesMetrics = computed(() => [
  { label: 'Online order value', value: money(summary.value.order_value), caption: change(Number(summary.value.order_value || 0), Number(summary.value.previous_order_value || 0)), icon: 'pi pi-wallet', tone: 'bg-orange-100 text-orange-600' },
  { label: 'Online orders', value: number(summary.value.orders), caption: change(Number(summary.value.orders || 0), Number(summary.value.previous_orders || 0)), icon: 'pi pi-shopping-bag', tone: 'bg-blue-100 text-blue-600' },
  { label: 'Average order value', value: money(summary.value.average_order_value), caption: 'Value per online order', icon: 'pi pi-chart-bar', tone: 'bg-violet-100 text-violet-600' },
  { label: 'Units sold', value: number(summary.value.units_sold), caption: 'From valid online orders', icon: 'pi pi-box', tone: 'bg-emerald-100 text-emerald-600' },
])
const storefrontMetrics = computed(() => [
  { label: 'Active listings', value: number(summary.value.active_listings), caption: 'Finished goods visible in the catalog', icon: 'pi pi-list' },
  { label: 'Image ready', value: number(summary.value.image_ready_products), caption: `${number(summary.value.missing_main_images)} missing a main image`, icon: 'pi pi-image' },
  { label: '3D enabled', value: number(summary.value.products_with_3d), caption: 'Active products with a 3D model', icon: 'pi pi-cube' },
  { label: 'Unavailable', value: number(summary.value.out_of_stock_products), caption: 'Active products without available stock', icon: 'pi pi-exclamation-circle' },
])
const salesChart = computed(() => ({ labels: orderValueTrend.points.value.map((row: any) => row.label), datasets: [{ label: 'Order value', data: orderValueTrend.points.value.map((row: any) => Number(row.value)), borderColor: '#f97316', backgroundColor: 'rgba(249,115,22,.12)', fill: true, tension: .35, pointRadius: orderValueTrend.period.value === 'month' ? 1 : 2 }] }))
const ordersChart = computed(() => ({ labels: trend.value.map(row => row.label), datasets: [{ label: 'Orders', data: trend.value.map(row => Number(row.orders)), backgroundColor: '#60a5fa', borderRadius: 4, maxBarThickness: 20 }] }))
const productChart = computed(() => ({ labels: topProducts.value.map(row => row.name), datasets: [{ label: 'Item sales', data: topProducts.value.map(row => Number(row.sales)), backgroundColor: '#fb923c', borderRadius: 6 }] }))
const statusLabel = (value: unknown) => String(value || 'Unknown').replace(/_/g, ' ').replace(/\b\w/g, character => character.toUpperCase())
const statusSeverity = (value: unknown) => ['delivered', 'completed'].includes(String(value)) ? 'success' : ['cancelled', 'rejected'].includes(String(value)) ? 'danger' : ['pending', 'processing'].includes(String(value)) ? 'warn' : 'info'
const statusChart = computed(() => ({ labels: statusRows.value.map(row => statusLabel(row.status)), datasets: [{ data: statusRows.value.map(row => Number(row.count)), backgroundColor: ['#f97316', '#60a5fa', '#34d399', '#a78bfa', '#f87171', '#fbbf24', '#94a3b8'] }] }))
const lineOptions = { maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 8 } }, y: { beginAtZero: true, ticks: { callback: (value: any) => `₱${Number(value).toLocaleString('en-PH')}` } } } }
const ordersOptions = { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 7 } }, y: { beginAtZero: true, ticks: { precision: 0 } } } }
const barOptions = { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { callback: (value: any) => `₱${Number(value).toLocaleString('en-PH')}` } }, y: { grid: { display: false }, ticks: { callback: (value: any) => String(topProducts.value[Number(value)]?.name || '').slice(0, 20) } } } }
const doughnutOptions = { maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 12 } } } }
const go = (name: string, id?: number) => router.push(id ? { name, params: { id } } : { name })
const loadDashboard = async () => {
  loading.value = true
  loadError.value = ''
  try {
    const response = await merchandisingService.getDashboardOverview(days.value)
    if (response?.success === false) throw new Error(response.message || 'Unable to load dashboard.')
    data.value = response.data
  } catch (error: any) { loadError.value = error?.response?.data?.message || error?.message || 'Unable to load E-Commerce performance.' }
  finally { loading.value = false }
}
onMounted(loadDashboard)
</script>
