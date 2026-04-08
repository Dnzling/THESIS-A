<template>
  <div class="space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white p-6">
      <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-semibold text-slate-900">Store Admin Dashboard</h1>
        <p class="text-sm text-slate-600">Operational overview for your store.</p>
      </div>
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
                <p class="text-xs font-semibold text-slate-500 uppercase">Sales Today</p>
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
                <p class="text-xs font-semibold text-slate-500 uppercase">Orders Today</p>
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
                <p class="text-xs font-semibold text-slate-500 uppercase">Sales (30D)</p>
                <p class="text-2xl font-semibold text-slate-900">{{ formatCurrency(kpis.sales_30d) }}</p>
                <p class="text-xs text-slate-500 mt-1">Avg Order: {{ formatCurrency(kpis.avg_order_value_30d) }}</p>
              </div>
              <Tag value="30D" severity="secondary" />
            </div>
          </template>
        </Card>
        <Card>
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-semibold text-slate-500 uppercase">Inventory Alerts</p>
                <p class="text-sm text-slate-700 mt-2">Low Stock: <span class="font-semibold text-slate-900">{{ kpis.low_stock_count }}</span></p>
                <p class="text-sm text-slate-700">Out of Stock: <span class="font-semibold text-slate-900">{{ kpis.out_of_stock_count }}</span></p>
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
                <h3 class="text-base font-semibold text-slate-800">Sales Trend (Last 7 Days)</h3>
                <p class="text-xs text-slate-500">Daily sales performance.</p>
              </div>
            </div>
            <Chart type="line" :data="salesTrendData" :options="lineOptions" class="h-72" />
          </template>
        </Card>
        <Card>
          <template #content>
            <div class="mb-3">
              <h3 class="text-base font-semibold text-slate-800">Order Status (30D)</h3>
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
              <h3 class="text-base font-semibold text-slate-800">Top Products (30D)</h3>
              <p class="text-xs text-slate-500">Most ordered items by quantity.</p>
            </div>
            <Chart type="bar" :data="topProductsData" :options="barOptions" class="h-72" />
          </template>
        </Card>
        <Card>
          <template #content>
            <div class="mb-3">
              <h3 class="text-base font-semibold text-slate-800">Procurement Queue</h3>
              <p class="text-xs text-slate-500">Open requests needing attention.</p>
            </div>
            <div class="space-y-2">
              <div class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3">
                <span class="text-sm text-slate-600">Pending Requisitions</span>
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
              <Column field="order_number" header="Order #"></Column>
              <Column field="customer_name" header="Customer"></Column>
              <Column field="status" header="Status"></Column>
              <Column field="total_amount" header="Total">
                <template #body="slotProps">
                  {{ formatCurrency(slotProps.data.total_amount) }}
                </template>
              </Column>
              <Column field="created_at" header="Date">
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
              <h3 class="text-base font-semibold text-slate-800">Branches</h3>
              <p class="text-xs text-slate-500">Available branches for this store.</p>
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
                    <span v-if="branch.is_main_branch" class="text-xs text-emerald-600 ml-1">(Main)</span>
                  </div>
                  <div class="text-xs text-slate-500">{{ [branch.city, branch.province].filter(Boolean).join(', ') }}</div>
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

const loading = ref(true)
const error = ref('')
const dashboard = ref<any>(null)

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
  labels: dashboard.value?.charts?.sales_trend?.labels || [],
  datasets: [
    {
      label: 'Sales',
      data: dashboard.value?.charts?.sales_trend?.values || [],
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
