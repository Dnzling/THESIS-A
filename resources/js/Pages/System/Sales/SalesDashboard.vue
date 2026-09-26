<template>
  <div class="module-dashboard dashboard--sales space-y-5 pb-6 text-sm">
    <div class="dashboard-hero flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <p class="dashboard-eyebrow">Sales performance</p>
        <h1 class="mt-1 text-2xl font-semibold text-slate-950">Sales Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">Track revenue, order activity, and payment performance across your sales channels.</p>
      </div>
      <Button label="Refresh" icon="pi pi-refresh" severity="secondary" outlined size="small" :loading="loading" @click="loadDashboard" />
    </div>

    <div v-if="loading" class="space-y-5">
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <Skeleton v-for="index in 4" :key="index" height="118px" class="rounded-2xl" />
      </div>
      <div class="grid gap-4 xl:grid-cols-3">
        <Skeleton height="285px" class="rounded-2xl xl:col-span-2" />
        <Skeleton height="285px" class="rounded-2xl" />
      </div>
      <Skeleton height="270px" class="rounded-2xl" />
    </div>

    <div v-else-if="loadError" class="dashboard-panel rounded-2xl border border-red-200 bg-white p-6">
      <p class="font-semibold text-slate-900">The sales dashboard could not load.</p>
      <p class="mt-1 text-slate-500">{{ loadError }}</p>
      <Button label="Try again" severity="warn" size="small" class="mt-4" @click="loadDashboard" />
    </div>

    <template v-else>
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <section class="dashboard-panel rounded-2xl border border-orange-200 bg-white p-5">
          <p class="text-xs font-medium text-slate-500">Sales today</p>
          <p class="mt-2 text-2xl font-semibold text-slate-950">{{ money(stats.today_sales) }}</p>
          <p class="mt-2 text-xs" :class="comparisonTone(stats.today_sales, stats.yesterday_sales)">{{ comparisonLabel(stats.today_sales, stats.yesterday_sales, 'previous day') }}</p>
        </section>
        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <p class="text-xs font-medium text-slate-500">Orders today</p>
          <p class="mt-2 text-2xl font-semibold text-slate-950">{{ stats.today_orders || 0 }}</p>
          <p class="mt-2 text-xs" :class="comparisonTone(stats.today_orders, stats.yesterday_orders)">{{ comparisonLabel(stats.today_orders, stats.yesterday_orders, 'previous day') }}</p>
        </section>
        <section class="dashboard-panel rounded-2xl border border-amber-200 bg-white p-5">
          <p class="text-xs font-medium text-slate-500">Sales this month</p>
          <p class="mt-2 text-2xl font-semibold text-slate-950">{{ money(stats.month_sales) }}</p>
          <p class="mt-2 text-xs" :class="comparisonTone(stats.month_sales, stats.previous_month_sales)">{{ comparisonLabel(stats.month_sales, stats.previous_month_sales, 'previous month') }}</p>
        </section>
        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <p class="text-xs font-medium text-slate-500">Orders this month</p>
          <p class="mt-2 text-2xl font-semibold text-slate-950">{{ stats.month_orders || 0 }}</p>
          <p class="mt-2 text-xs" :class="comparisonTone(stats.month_orders, stats.previous_month_orders)">{{ comparisonLabel(stats.month_orders, stats.previous_month_orders, 'previous month') }}</p>
        </section>
      </div>

      <div class="grid gap-4 xl:grid-cols-3">
        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5 xl:col-span-2">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <h2 class="font-semibold text-slate-950">Sales trend</h2>
              <p class="mt-1 text-xs text-slate-500">Paid sales and orders across POS and ecommerce</p>
            </div>
            <Select v-model="trendRange" :options="trendRanges" optionLabel="label" optionValue="value" size="small" class="w-36" aria-label="Sales trend period" @change="loadDashboard" />
          </div>
          <Chart v-if="trendData.labels.length" type="line" :data="trendData" :options="chartOptions" class="mt-4 h-56" />
          <p v-else class="flex h-56 items-center justify-center text-xs text-slate-500">No paid sales in this period.</p>
        </section>

        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <h2 class="font-semibold text-slate-950">Payment methods</h2>
          <p class="mt-1 text-xs text-slate-500">Recorded orders by payment method</p>
          <div v-if="paymentMethods.length" class="mt-3">
            <Chart type="doughnut" :data="paymentData" :options="doughnutOptions" class="mx-auto h-44 max-w-52" />
            <div class="mt-2 space-y-1.5">
              <div v-for="method in paymentMethods" :key="method.payment_method" class="flex items-center justify-between gap-2 text-xs">
                <span class="truncate text-slate-600">{{ formatMethod(method.payment_method) }}</span>
                <strong class="text-slate-900">{{ method.total }}</strong>
              </div>
            </div>
          </div>
          <p v-else class="flex h-48 items-center justify-center text-center text-xs text-slate-500">Payment method data will appear as orders are recorded.</p>
        </section>
      </div>

      <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-start justify-between gap-3">
          <div>
            <h2 class="font-semibold text-slate-950">Recent POS orders</h2>
            <p class="text-xs text-slate-500">Latest in-store orders and their payment status</p>
          </div>
          <Button label="View orders" severity="secondary" text size="small" @click="goToOrders" />
        </div>
        <DataTable :value="stats.recent_orders || []" size="small" rowHover class="text-xs" @row-click="openOrder">
          <Column field="order_number" header="Order" />
          <Column field="customer_name" header="Customer">
            <template #body="{ data }">{{ data.customer_name || 'Walk-in customer' }}</template>
          </Column>
          <Column field="payment_method" header="Payment">
            <template #body="{ data }">{{ formatMethod(data.payment_method) }}</template>
          </Column>
          <Column field="total_amount" header="Total">
            <template #body="{ data }"><span class="font-medium text-slate-900">{{ money(data.total_amount) }}</span></template>
          </Column>
          <Column field="payment_status" header="Payment status">
            <template #body="{ data }"><Badge :value="formatStatus(data.payment_status)" :severity="paymentSeverity(data.payment_status)" /></template>
          </Column>
          <Column field="created_at" header="Date">
            <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
          </Column>
          <template #empty><div class="py-7 text-center text-xs text-slate-500">No sales orders have been recorded yet.</div></template>
        </DataTable>
      </section>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import salesService from '@/services/sales.service'
import Button from 'primevue/button'
import Chart from 'primevue/chart'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Badge from 'primevue/badge'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'

const router = useRouter()
const loading = ref(true)
const loadError = ref('')
const trendRange = ref('7d')
const stats = ref<any>({ sales_trend: [], recent_orders: [], payments_by_method: [] })
const trendRanges = [
  { label: 'Last 7 days', value: '7d' },
  { label: 'Last 30 days', value: '30d' },
  { label: 'Last 6 months', value: '6m' },
  { label: 'Last 12 months', value: '12m' },
]
const paymentMethods = computed(() => (stats.value.payments_by_method || []).filter((method: any) => Number(method.total) > 0))
const trendData = computed(() => ({
  labels: (stats.value.sales_trend || []).map((point: any) => formatPeriod(point.period)),
  datasets: [
    {
      label: 'Paid sales',
      data: (stats.value.sales_trend || []).map((point: any) => Number(point.sales || 0)),
      borderColor: '#f97316',
      backgroundColor: 'rgba(249, 115, 22, 0.12)',
      fill: true,
      tension: 0.35,
      yAxisID: 'sales',
    },
    {
      label: 'Paid orders',
      data: (stats.value.sales_trend || []).map((point: any) => Number(point.orders || 0)),
      borderColor: '#64748b',
      backgroundColor: '#64748b',
      tension: 0.3,
      yAxisID: 'orders',
    },
  ],
}))
const paymentData = computed(() => ({
  labels: paymentMethods.value.map((method: any) => formatMethod(method.payment_method)),
  datasets: [{
    data: paymentMethods.value.map((method: any) => Number(method.total || 0)),
    backgroundColor: ['#f97316', '#fb923c', '#fdba74', '#64748b', '#cbd5e1'],
    borderWidth: 0,
  }],
}))
const chartOptions = {
  maintainAspectRatio: false,
  interaction: { mode: 'index', intersect: false },
  plugins: {
    legend: { position: 'bottom' as const, labels: { usePointStyle: true, boxWidth: 7, color: '#64748b' } },
    tooltip: { callbacks: { label: (context: any) => context.dataset.yAxisID === 'sales' ? `${context.dataset.label}: ${money(context.parsed.y)}` : `${context.dataset.label}: ${context.parsed.y}` } },
  },
  scales: {
    x: { grid: { display: false }, ticks: { color: '#64748b', maxRotation: 0, autoSkip: true } },
    sales: { type: 'linear' as const, position: 'left' as const, beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#64748b', callback: (value: number) => money(value) } },
    orders: { type: 'linear' as const, position: 'right' as const, beginAtZero: true, grid: { drawOnChartArea: false }, ticks: { color: '#64748b', precision: 0 } },
  },
}
const doughnutOptions = { maintainAspectRatio: false, cutout: '72%', plugins: { legend: { display: false } } }

const money = (value: unknown) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 2 }).format(Number(value) || 0)
const formatDate = (value: unknown) => value ? new Intl.DateTimeFormat('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit', timeZone: 'Asia/Manila' }).format(new Date(String(value))) : '-'
const formatPeriod = (value: string) => {
  if (!value) return '-'
  const date = value.length === 7 ? new Date(`${value}-01T12:00:00+08:00`) : new Date(`${value}T12:00:00+08:00`)
  return new Intl.DateTimeFormat('en-PH', value.length === 7 ? { month: 'short', year: '2-digit', timeZone: 'Asia/Manila' } : { month: 'short', day: 'numeric', timeZone: 'Asia/Manila' }).format(date)
}
const formatMethod = (value: unknown) => String(value || 'Not recorded').replace(/[_-]/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
const formatStatus = (value: unknown) => String(value || 'Unknown').replace(/[_-]/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
const paymentSeverity = (value: unknown) => ['paid', 'succeeded', 'completed'].includes(String(value || '').toLowerCase()) ? 'success' : ['failed', 'cancelled'].includes(String(value || '').toLowerCase()) ? 'danger' : 'warn'
const comparisonLabel = (current: unknown, previous: unknown, period: string) => {
  const currentValue = Number(current) || 0
  const previousValue = Number(previous) || 0
  if (previousValue === 0) return currentValue > 0 ? `New activity vs ${period}` : `No activity vs ${period}`
  const change = ((currentValue - previousValue) / previousValue) * 100
  return `${change > 0 ? '+' : ''}${change.toFixed(1)}% vs ${period}`
}
const comparisonTone = (current: unknown, previous: unknown) => Number(current || 0) >= Number(previous || 0) ? 'text-emerald-700' : 'text-orange-700'

const loadDashboard = async () => {
  loading.value = true
  loadError.value = ''
  try {
    const response = await salesService.getDashboard({ trend_range: trendRange.value })
    if (response?.success === false) throw new Error(response.message || 'Please try again.')
    stats.value = response?.data || {}
  } catch (error: any) {
    stats.value = { sales_trend: [], recent_orders: [], payments_by_method: [] }
    loadError.value = error?.response?.data?.message || error?.message || 'Please try again.'
  } finally {
    loading.value = false
  }
}
const goToOrders = () => router.push({ name: 'sales.orders' })
const openOrder = (event: { data: any }) => router.push({ name: 'sales.pos.order-detail', params: { id: event.data.id } })

onMounted(loadDashboard)
</script>

<style scoped>
:deep(.p-datatable-tbody > tr) {
  cursor: pointer;
}
</style>
