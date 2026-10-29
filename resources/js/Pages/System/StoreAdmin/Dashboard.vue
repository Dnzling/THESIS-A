<template>
  <div class="space-y-6">
    <div class="">
      <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-semibold text-slate-900">Store Overview</h1>
      </div>
    </div>
    <div v-if="dashboard?.store?.subscription_status === 'trial'" class="rounded-xl border border-orange-200 bg-orange-50 px-5 py-4 text-sm text-orange-900">
      <span class="font-semibold">Free Trial</span> · {{ dashboard.store.trial_days_remaining }} days remaining
    </div>
    <div v-if="dashboard?.store && !dashboard.store.verified" class="flex flex-col gap-3 rounded-xl border border-blue-200 bg-blue-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
      <div><div class="font-semibold text-slate-900">Verify Your Store</div><p class="mt-1 text-sm text-slate-600">Complete business verification to establish your store’s verified status.</p></div>
      <a href="/system/store/verification" class="inline-flex shrink-0 items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Verify Store</a>
    </div>

    <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-6">
      <div class="flex items-center gap-3">
        <ProgressSpinner style="width: 22px; height: 22px" strokeWidth="4" />
        <span class="text-sm text-slate-600">Loading dashboard data…</span>
      </div>
    </div>

    <div v-else-if="error" class="rounded-xl border border-rose-200 bg-rose-50 p-6">
      <p class="text-sm text-rose-700">{{ error }}</p>
    </div>

    <template v-else>
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <Card>
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Store Sales Today</p>
                <p class="text-2xl font-semibold text-slate-900">{{ formatCurrency(kpis.sales_today) }}</p>
              </div>
              <Tag value="Today" severity="success" />
            </div>
          </template>
        </Card>
        <Card>
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Store Orders Today</p>
                <p class="text-2xl font-semibold text-slate-900">{{ kpis.orders_today }}</p>
              </div>
              <Tag value="Orders" severity="info" />
            </div>
          </template>
        </Card>
        <Card>
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Store Sales | Last 30 Days</p>
                <p class="text-2xl font-semibold text-slate-900">{{ formatCurrency(kpis.sales_30d) }}</p>
                <p class="text-xs text-slate-500 mt-1">Average Order Value: {{ formatCurrency(kpis.avg_order_value_30d) }}</p>
              </div>
              <Tag value="30 Days" severity="secondary" />
            </div>
          </template>
        </Card>
        <Card>
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Stock Alerts</p>
                <p class="text-sm text-slate-700 mt-2">Low stock: <span class="font-semibold text-slate-900">{{ kpis.low_stock_count }}</span></p>
                <p class="text-sm text-slate-700">Out of stock: <span class="font-semibold text-slate-900">{{ kpis.out_of_stock_count }}</span></p>
              </div>
              <Tag value="Inventory" severity="warn" />
            </div>
          </template>
        </Card>
      </div>

      <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <Card class="xl:col-span-2">
          <template #content>
            <div class="flex items-center justify-between mb-3">
              <div>
                <h3 class="text-base font-semibold text-slate-800">Sales Trend</h3>
                <p class="text-xs text-slate-500">Paid sales across all branches and sales channels.</p>
              </div>
              <Select v-model="ownerSalesTrend.period.value" :options="trendPeriods" optionLabel="label" optionValue="value" class="w-36" aria-label="Sales trend period" />
            </div>
            <Skeleton v-if="ownerSalesTrend.loading.value" height="18rem" class="rounded-xl" />
            <div v-else-if="ownerSalesTrend.error.value" class="flex h-72 flex-col items-center justify-center gap-2 text-sm text-red-600">{{ ownerSalesTrend.error.value }}<Button text label="Try again" @click="ownerSalesTrend.reload" /></div>
            <Chart v-else-if="ownerSalesTrend.points.value.some((point) => point.value)" type="line" :data="salesTrendData" :options="lineOptions" class="h-72" />
            <p v-else class="flex h-72 items-center justify-center text-sm text-slate-500">No sales in this period.</p>
          </template>
        </Card>
        <Card>
          <template #content>
            <div class="mb-3">
                <h3 class="text-base font-semibold text-slate-800">Order Status | Last 30 Days</h3>
              <p class="text-xs text-slate-500">Distribution of order statuses.</p>
            </div>
            <Chart type="doughnut" :data="orderStatusData" :options="doughnutOptions" class="h-72" />
          </template>
        </Card>
      </div>

      <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <Card class="xl:col-span-2">
          <template #content>
            <div class="mb-3">
              <h3 class="text-base font-semibold text-slate-800">Top-Selling Products | Last 30 Days</h3>
              <p class="text-xs text-slate-500">Ranked by units ordered.</p>
            </div>
            <Chart type="bar" :data="topProductsData" :options="barOptions" class="h-72" />
          </template>
        </Card>
        <Card>
          <template #content>
            <div class="mb-3">
              <h3 class="text-base font-semibold text-slate-800">Procurement Requests</h3>
              <p class="text-xs text-slate-500">Requests that still need attention.</p>
            </div>
            <div class="space-y-2">
              <div class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3">
                <span class="text-sm text-slate-600">Pending Purchase Requisitions</span>
                <span class="text-lg font-semibold text-slate-900">{{ kpis.pending_requisitions }}</span>
              </div>
              <div class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3">
                <span class="text-sm text-slate-600">Pending Purchase Orders</span>
                <span class="text-lg font-semibold text-slate-900">{{ kpis.pending_purchase_orders }}</span>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <Card class="xl:col-span-2">
          <template #content>
            <div class="flex items-center justify-between mb-3">
              <div>
                <h3 class="text-base font-semibold text-slate-800">Recent Orders</h3>
                <p class="text-xs text-slate-500">Latest sales activity.</p>
              </div>
            </div>
            <DataTable :value="dashboard?.recent_orders || []" responsiveLayout="scroll" class="text-sm">
              <Column field="channel" header="Channel"></Column>
              <Column field="order_number" header="Order Number"></Column>
              <Column field="customer_name" header="Customer"></Column>
              <Column field="status" header="Status">
                <template #body="slotProps">
                  {{ formatStatus(slotProps.data.status) }}
                </template>
              </Column>
              <Column field="total_amount" header="Order Total">
                <template #body="slotProps">
                  {{ formatCurrency(slotProps.data.total_amount) }}
                </template>
              </Column>
              <Column field="created_at" header="Order Date">
                <template #body="slotProps">
                  {{ formatDate(slotProps.data.created_at) }}
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>

        <Card>
          <template #content>
            <div class="mb-3">
              <h3 class="text-base font-semibold text-slate-800">Store Branches</h3>
              <p class="text-xs text-slate-500">Branches registered under this store.</p>
            </div>
            <div v-if="branchList.length" class="space-y-2">
              <div
                v-for="branch in branchList"
                :key="branch.id"
                class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2"
              >
                <div>
                  <div class="text-sm font-semibold text-slate-900">
                    {{ branch.name }}
                    <span v-if="branch.is_main_branch" class="text-xs text-emerald-600 ml-1">(Main Branch)</span>
                  </div>
                  <div class="text-xs text-slate-500">{{ [branch.city, branch.province].filter(Boolean).join(', ') }}</div>
                  <div class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1 text-xs text-slate-600">
                    <span>Today</span><span class="text-right font-medium text-slate-800">{{ formatCurrency(branch.sales_today) }}</span>
                    <span>Orders today</span><span class="text-right font-medium text-slate-800">{{ branch.orders_today || 0 }}</span>
                    <span>Sales, 30 days</span><span class="text-right font-medium text-slate-800">{{ formatCurrency(branch.sales_30d) }}</span>
                    <span>Low / out of stock</span><span class="text-right font-medium text-slate-800">{{ branch.low_stock_count || 0 }} / {{ branch.out_of_stock_count || 0 }}</span>
                  </div>
                </div>
                <Tag :value="branch.status || 'active'" :severity="branch.status === 'inactive' ? 'danger' : 'success'" />
              </div>
            </div>
            <div v-else class="text-sm text-slate-500">No branches found.</div>
          </template>
        </Card>
      </div>

    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { storeService } from '@/services/store.service'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import Button from 'primevue/button'
import { trendPeriods, useDashboardTrend } from '@/composables/useDashboardTrend'

const loading = ref(true)
const error = ref('')
const dashboard = ref<any>(null)
const ownerSalesTrend = useDashboardTrend('/api/store/dashboard/trend')

const kpis = computed(() => dashboard.value?.kpis || {
  sales_today: 0,
  orders_today: 0,
  sales_30d: 0,
  avg_order_value_30d: 0,
  low_stock_count: 0,
  out_of_stock_count: 0,
  pending_requisitions: 0,
  pending_purchase_orders: 0,
})

const salesTrendData = computed(() => ({
  labels: ownerSalesTrend.points.value.map((point: any) => point.label),
  datasets: [
    {
      label: 'Sales',
      data: ownerSalesTrend.points.value.map((point: any) => Number(point.value)),
      borderColor: '#2563eb',
      backgroundColor: 'rgba(37, 99, 235, 0.15)',
      tension: 0.3,
      fill: true,
    },
  ],
}))

const orderStatusData = computed(() => ({
  labels: dashboard.value?.charts?.order_status?.labels || [],
  datasets: [
    {
      data: dashboard.value?.charts?.order_status?.values || [],
      backgroundColor: ['#22c55e', '#f97316', '#facc15', '#94a3b8', '#ef4444'],
    },
  ],
}))

const topProductsData = computed(() => ({
  labels: dashboard.value?.charts?.top_products?.labels || [],
  datasets: [
    {
      label: 'Quantity',
      data: dashboard.value?.charts?.top_products?.values || [],
      backgroundColor: '#38bdf8',
    },
  ],
}))

const branchList = computed(() => {
  return Array.isArray(dashboard.value?.branches) ? dashboard.value.branches : []
})

const lineOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
  },
  scales: {
    y: { beginAtZero: true },
  },
}

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'bottom' },
  },
}

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
  },
  scales: {
    y: { beginAtZero: true },
  },
}

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    maximumFractionDigits: 2,
  }).format(value || 0)
}

const formatDate = (value: string) => {
  if (!value) return ''
  const date = new Date(value)
  return date.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

const formatStatus = (value: string) => {
  if (!value) return 'Unknown'
  return value
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, (character) => character.toUpperCase())
}

const loadDashboard = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await storeService.getDashboard()
    if (response?.success) {
      dashboard.value = response.data
    } else {
      error.value = response?.message || 'Failed to load dashboard.'
    }
  } catch (err: any) {
    error.value = err?.response?.data?.message || err?.message || 'Failed to load dashboard.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadDashboard()
})
</script>
