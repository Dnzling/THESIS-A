<!-- views/admin/Dashboard.vue -->
<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <!-- Welcome Header -->
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
      <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-gray-900 sm:text-3xl">FurniSync Overview</h1>
          <p class="mt-2 text-sm text-gray-500">Store growth and subscription performance at a glance.</p>
          <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-gray-600">
            <div class="flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2">
              <i class="pi pi-calendar text-gray-500"></i>
              <span>{{ currentDate }}</span>
            </div>
            <div class="flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2">
              <i class="pi pi-clock text-gray-500"></i>
              <span>{{ currentTime }}</span>
            </div>
          </div>
        </div>
        <div class="flex flex-col items-stretch gap-3 sm:items-end">
          <Button label="Audit Logs" icon="pi pi-history" size="small" severity="secondary" outlined @click="goToActivityLog" />
          <div class="rounded-2xl border border-gray-100 bg-gray-50 px-5 py-4 text-left sm:text-right">
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Total Subscription Revenue</div>
            <div class="mt-2 text-3xl font-semibold text-gray-900 sm:text-4xl">{{ formatCurrency(stats.subscriptionRevenue) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  
    <!-- Improved KPI Cards -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
      <div class="bg-white shadow-md rounded-2xl p-4 flex flex-col justify-between">
        <div class="flex items-start justify-between">
          <div>
            <div class="text-xs text-gray-400">Active Stores</div>
            <div class="text-2xl font-bold text-gray-800 mt-1">{{ stats.activeStores }}</div>
            <div class="text-sm text-green-500 mt-1">+{{ stats.newStoresThisWeek }} this week</div>
          </div>
          <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
            <i class="pi pi-store text-blue-600 text-xl"></i>
          </div>
        </div>
      </div>


      <div class="bg-white shadow-md rounded-2xl p-4 flex flex-col justify-between">
        <div class="flex items-start justify-between">
          <div>
            <div class="text-xs text-gray-400">Subscription Revenue</div>
            <div class="text-2xl font-bold text-gray-800 mt-1">{{ formatCurrency(stats.subscriptionRevenue) }}</div>
            <div class="text-sm text-emerald-500 mt-1">{{ formatCurrency(stats.monthlySubscriptionRevenue) }} this month</div>
          </div>
          <div class="w-12 h-12 bg-emerald-50 rounded-lg flex items-center justify-center">
            <i class="pi pi-wallet text-emerald-600 text-xl"></i>
          </div>
        </div>
      </div>
  
  
  
      <div class="bg-white shadow-md rounded-2xl p-4 flex flex-col justify-between">
        <div class="flex items-start justify-between">
          <div>
            <div class="text-xs text-gray-400">This Month</div>
            <div class="text-2xl font-bold text-gray-800 mt-1">{{ formatCurrency(stats.monthlySubscriptionRevenue) }}</div>
            <div class="text-sm text-gray-500 mt-1">Subscription payments received</div>
          </div>
          <div class="w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center">
            <i class="pi pi-chart-line text-purple-600 text-xl"></i>
          </div>
        </div>
      </div>
    </div>
  
    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Revenue Trend -->
      <div class="bg-white shadow rounded-xl p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-lg font-semibold text-gray-800">Subscription Revenue Trend</h3>
          <div class="flex space-x-2">
            <Button @click="setRevenueChartView('monthly')"
              :severity="revenueChartView === 'monthly' ? 'primary' : 'secondary'" size="small">
              Monthly
            </Button>
            <Button @click="setRevenueChartView('yearly')"
              :severity="revenueChartView === 'yearly' ? 'primary' : 'secondary'" size="small">
              Yearly
            </Button>
          </div>
        </div>
        <div class="h-72">
          <canvas ref="revenueChartRef"></canvas>
        </div>
      </div>
  
      <!-- Store Growth -->
      <div class="bg-white shadow rounded-xl p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-lg font-semibold text-gray-800">Store Growth</h3>
          <Select v-model="growthPeriod" :options="growthPeriodOptions" optionLabel="name" placeholder="Last 6 months"
            class="w-50" />
        </div>
        <div class="h-72">
          <canvas ref="growthChartRef"></canvas>
        </div>
      </div>
    </div>
  
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
      <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="text-lg font-semibold text-slate-900">Subscription Plans</h2>
          <p class="mt-1 text-sm text-slate-500">Active stores on each plan.</p>
        </div>
        <Button label="Manage Plans" icon="pi pi-arrow-right" iconPos="right" size="small" outlined @click="goToSubscriptions" />
      </div>
      <div v-if="plansLoading" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <Skeleton v-for="item in 3" :key="item" height="190px" />
      </div>
      <div v-else-if="subscriptionPlans.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <div v-for="(plan, index) in subscriptionPlans" :key="plan.id"
          class="flex min-h-48 flex-col justify-between rounded-2xl p-5 text-white"
          :class="planGradient(index)">
          <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-white/70">{{ plan.plan_key }}</p>
            <h3 class="mt-2 text-xl font-bold">{{ plan.name }}</h3>
            <p class="mt-2 text-sm text-white/80">{{ plan.description || 'FurniSync subscription plan' }}</p>
          </div>
          <div class="mt-5 flex items-end justify-between gap-3 border-t border-white/20 pt-4">
            <p class="text-sm"><span class="text-2xl font-semibold">{{ plan.active_stores_count || 0 }}</span> active stores</p>
            <p class="text-sm font-medium">{{ formatCurrency(plan.monthly_price) }} / mo</p>
          </div>
        </div>
      </div>
      <p v-else class="py-8 text-center text-sm text-slate-500">No subscription plans found.</p>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
      <div class="mb-4">
        <h2 class="text-lg font-semibold text-slate-900">Recent Subscription Payments</h2>
        <p class="mt-1 text-sm text-slate-500">Subscription checkout transactions only.</p>
      </div>
      <DataTable :value="recentPayments" rowHover size="small" responsiveLayout="scroll" class="text-sm">
        <template #empty><div class="py-8 text-center text-sm text-slate-500">No subscription payments yet.</div></template>
        <Column field="date" header="Date">
          <template #body="{ data }">{{ formatDate(data.date) }}</template>
        </Column>
        <Column field="store" header="Store" />
        <Column field="amount" header="Amount">
          <template #body="{ data }"><span class="font-semibold">{{ formatCurrency(data.amount) }}</span></template>
        </Column>
        <Column field="status" header="Status">
          <template #body="{ data }"><Badge :value="humanizePaymentStatus(data.status)" :severity="getPaymentStatusSeverity(data.status)" /></template>
        </Column>
        <Column header="Action" style="width: 90px">
          <template #body="{ data }">
            <Button label="View" icon="pi pi-eye" outlined rounded size="small" @click="viewPayment(data)" />
          </template>
        </Column>
      </DataTable>
    </section>

    <Dialog v-model:visible="paymentDialogVisible" modal header="Subscription Payment Details" :style="{ width: 'min(34rem, 94vw)' }">
      <div v-if="selectedPayment" class="space-y-4">
        <div class="flex items-start justify-between gap-3 border-b border-slate-200 pb-4">
          <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Store</p>
            <p class="mt-1 font-semibold text-slate-900">{{ selectedPayment.store || 'Unknown Store' }}</p>
          </div>
          <Badge :value="humanizePaymentStatus(selectedPayment.status)" :severity="getPaymentStatusSeverity(selectedPayment.status)" />
        </div>
        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div class="rounded-lg border border-slate-200 p-3">
            <dt class="text-xs text-slate-500">Amount</dt>
            <dd class="mt-1 font-semibold text-slate-900">{{ formatCurrency(selectedPayment.amount) }}</dd>
          </div>
          <div class="rounded-lg border border-slate-200 p-3">
            <dt class="text-xs text-slate-500">Payment Date</dt>
            <dd class="mt-1 text-sm font-medium text-slate-900">{{ formatDate(selectedPayment.date) }}</dd>
          </div>
          <div class="rounded-lg border border-slate-200 p-3">
            <dt class="text-xs text-slate-500">Payment Type</dt>
            <dd class="mt-1 text-sm font-medium text-slate-900">{{ selectedPayment.type || 'Subscription' }}</dd>
          </div>
          <div class="rounded-lg border border-slate-200 p-3">
            <dt class="text-xs text-slate-500">Store Status</dt>
            <dd class="mt-1 text-sm font-medium text-slate-900">{{ formatLabel(selectedPayment.storeStatus) }}</dd>
          </div>
          <div class="rounded-lg border border-slate-200 p-3 sm:col-span-2">
            <dt class="text-xs text-slate-500">Payment Reference</dt>
            <dd class="mt-1 break-all text-sm font-medium text-slate-900">{{ selectedPayment.paymentIntentId || 'Not provided' }}</dd>
          </div>
        </dl>
      </div>
      <template #footer>
        <Button label="Close" severity="secondary" outlined size="small" @click="paymentDialogVisible = false" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import axiosClient from '@/axios'
import { useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import Badge from 'primevue/badge'
import Skeleton from 'primevue/skeleton'
import Dialog from 'primevue/dialog'

let ChartConstructor: any = null
let chartJsPromise: Promise<any> | null = null
const loadChartJs = async () => {
  if (!chartJsPromise) {
    chartJsPromise = import('chart.js').then((module) => {
      module.Chart.register(...module.registerables)
      ChartConstructor = module.Chart
      return module.Chart
    })
  }

  return chartJsPromise
}

const router = useRouter()

// Chart refs
const revenueChartRef = ref<HTMLCanvasElement | null>(null)
const growthChartRef = ref<HTMLCanvasElement | null>(null)
let revenueChart: any = null
let growthChart: any = null
let dateInterval: number | null = null

// State
const revenueChartView = ref<'monthly' | 'yearly'>('monthly')
const growthPeriod = ref({ name: 'Last 6 months', value: '6months' })
const currentDate = ref('')
const currentTime = ref('')

// Stats Data (will be populated from API)
const stats = ref({
  activeStores: 0,
  newStoresThisWeek: 0,
  activeSubscriptions: 0,
  subscriptionGrowth: 0,
  pendingValidations: 0,
  monthlyRevenue: 0,
  revenueGrowth: 0,
  commissionRevenue: 0,
  subscriptionRevenue: 0,
  monthlyCommissionRevenue: 0,
  monthlySubscriptionRevenue: 0,
})

const totalPlatformRevenue = ref(0)
const revenueSeries = ref({
  monthly: { labels: [] as string[], platformRevenue: [] as number[], subscriptionRevenue: [] as number[], commissionRevenue: [] as number[] },
  yearly: { labels: [] as string[], platformRevenue: [] as number[], subscriptionRevenue: [] as number[], commissionRevenue: [] as number[] },
})
const storeGrowthSeries = ref({
  labels: [] as string[],
  newStores: [] as number[],
  activeStores: [] as number[],
})

// Growth Period Options
const growthPeriodOptions = ref([
  { name: 'Last 3 months', value: '3months' },
  { name: 'Last 6 months', value: '6months' },
  { name: 'Last 12 months', value: '12months' },
  { name: 'Year to Date', value: 'ytd' }
])

// Recent Activities (populated from API)
const recentActivities = ref([])

const subscriptionPlans = ref<any[]>([])
const plansLoading = ref(true)
const planGradient = (index: number) => [
  'bg-gradient-to-br from-slate-900 to-blue-900',
  'bg-gradient-to-br from-rose-900 to-blue-950',
  'bg-gradient-to-br from-orange-600 to-red-800',
][index % 3]

const pendingStores = ref([])

const recentPayments = ref([])
const selectedPayment = ref<any>(null)
const paymentDialogVisible = ref(false)

// Helper Functions
const formatCurrency = (amount: number | string) => new Intl.NumberFormat('en-PH', {
  style: 'currency', currency: 'PHP',
}).format(Number(amount || 0))

const formatDate = (value: any) => {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleString('en-PH', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const getStatusSeverity = (status: string) => {
  switch (status.toLowerCase()) {
    case 'completed':
    case 'success':
    case 'paid': return 'success'
    case 'pending': return 'warning'
    case 'warning': return 'danger'
    default: return 'info'
  }
}

const getPlanStatusSeverity = (status: string) => {
  switch (status.toLowerCase()) {
    case 'active': return 'success'
    case 'popular': return 'info'
    case 'inactive': return 'secondary'
    default: return 'info'
  }
}

const getPaymentStatusSeverity = (status: string) => {
  switch (status.toLowerCase()) {
    case 'paid': return 'success'
    case 'pending': return 'warning'
    case 'failed': return 'danger'
    default: return 'info'
  }
}

const formatLabel = (value?: string | null) => value
  ? value.replace(/[_-]/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase())
  : 'Unknown'
const humanizePaymentStatus = (status?: string | null) => formatLabel(status || 'unknown')
const viewPayment = (payment: any) => {
  selectedPayment.value = payment
  paymentDialogVisible.value = true
}

// Chart Functions
const initRevenueChart = async () => {
  if (!revenueChartRef.value) return
  await loadChartJs()
  if (!revenueChartRef.value) return

  if (revenueChart) {
    revenueChart.destroy()
  }

  const ctx = revenueChartRef.value.getContext('2d')
  if (!ctx) return

  const data = revenueChartView.value === 'monthly'
    ? revenueSeries.value.monthly
    : revenueSeries.value.yearly

  revenueChart = new ChartConstructor(ctx, {
    type: 'line',
    data: {
      labels: data.labels,
      datasets: [
        {
          label: 'Subscription Revenue',
          data: data.subscriptionRevenue,
          borderColor: '#10b981',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          borderWidth: 3,
          fill: true,
          tension: 0.4
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function (value) {
              return '₱' + (Number(value) / 1000).toFixed(0) + 'K'
            }
          }
        }
      }
    }
  })
}

const initGrowthChart = async () => {
  if (!growthChartRef.value) return
  await loadChartJs()
  if (!growthChartRef.value) return

  if (growthChart) {
    growthChart.destroy()
  }

  const ctx = growthChartRef.value.getContext('2d')
  if (!ctx) return

  const growthData = storeGrowthSeries.value
  growthChart = new ChartConstructor(ctx, {
    type: 'bar',
    data: {
      labels: growthData.labels,
      datasets: [
        {
          label: 'New Stores',
          data: growthData.newStores,
          backgroundColor: 'rgba(79, 70, 229, 0.8)',
          borderColor: 'rgb(79, 70, 229)',
          borderWidth: 1
        },
        {
          label: 'Active Stores',
          data: growthData.activeStores,
          backgroundColor: 'rgba(16, 185, 129, 0.8)',
          borderColor: 'rgb(16, 185, 129)',
          borderWidth: 1
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Number of Stores'
          }
        }
      }
    }
  })
}

// Chart data is populated from the dashboard API

// Action Functions
const setRevenueChartView = (view: 'monthly' | 'yearly') => {
  revenueChartView.value = view
  void initRevenueChart()
}

const loadDashboard = async () => {
  try {
    const res = await axiosClient.get('/api/admin/dashboard')
    if (res?.data?.success) {
      const d = res.data.data || {}
      stats.value.activeStores = d.active_stores || d.stores_count || 0
      stats.value.newStoresThisWeek = d.new_stores_this_week || 0
      stats.value.activeSubscriptions = d.active_subscriptions || 0
      stats.value.subscriptionGrowth = d.subscription_growth || 0
      stats.value.pendingValidations = d.pending_validations || 0
      stats.value.monthlyRevenue = d.monthly_revenue || 0
      stats.value.revenueGrowth = d.revenue_growth || 0
      stats.value.commissionRevenue = d.commission_revenue || 0
      stats.value.subscriptionRevenue = d.subscription_revenue || 0
      stats.value.monthlyCommissionRevenue = d.monthly_commission_revenue || 0
      stats.value.monthlySubscriptionRevenue = d.monthly_subscription_revenue || 0
      totalPlatformRevenue.value = d.total_platform_revenue || 0

      revenueSeries.value = d.revenue_series || revenueSeries.value
      storeGrowthSeries.value = d.store_growth_series || storeGrowthSeries.value

      await Promise.all([initRevenueChart(), initGrowthChart()])
      // recent activities
      if (Array.isArray(d.recent_activities)) {
        recentActivities.value = d.recent_activities.map((r: any, idx: number) => ({
          id: idx + 1,
          time: r.time,
          action: r.action,
          description: r.description,
          status: r.status,
          icon: 'pi pi-info-circle',
          iconColor: 'text-blue-600',
          iconBg: 'bg-blue-100'
        }))
      }

      if (Array.isArray(d.recent_payments)) {
        recentPayments.value = d.recent_payments.map((payment: any, idx: number) => ({
          id: idx + 1,
          store: payment.store,
          storeStatus: payment.store_status,
          amount: Number(payment.amount || 0),
          currency: payment.currency || 'PHP',
          date: payment.date,
          status: payment.status || 'unknown',
          type: payment.type || 'Payment',
          paymentIntentId: payment.payment_intent_id,
        }))
      }
    }
  } catch (e) {
    console.error('Failed to load dashboard data', e)
  }
}

const loadPlans = async () => {
  plansLoading.value = true
  try {
    const response = await axiosClient.get('/api/admin/subscription-plans')
    subscriptionPlans.value = Array.isArray(response.data?.data) ? response.data.data : []
  } catch (error) {
    console.error('Failed to load subscription plans', error)
    subscriptionPlans.value = []
  } finally {
    plansLoading.value = false
  }
}


const updateDateTime = () => {
  const now = new Date()
  currentDate.value = now.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
  currentTime.value = now.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Navigation Functions
const goToPendingApprovals = () => {
  router.push('/admin/store-validation')
}

const goToSubscriptions = () => {
  router.push('/admin/subscriptions')
}

const goToRevenueReports = () => {
  router.push('/admin/analytics')
}

const goToCustomerValidation = () => {
  router.push('/admin/customer-validation')
}

const goToSettings = () => {
  router.push('/admin/settings')
}

const goToActivityLog = () => {
  router.push('/admin/activity-log')
}

const managePlan = (plan: any) => {
  console.log('Manage plan:', plan)
  router.push('/admin/subscription')
}

const approveStore = (store: any) => {
  console.log('Approve store:', store)
  // Implement approval logic
}

const rejectStore = (store: any) => {
  console.log('Reject store:', store)
  // Implement rejection logic
}

// Lifecycle
onMounted(() => {
  updateDateTime()
  dateInterval = window.setInterval(updateDateTime, 60000)

  void Promise.all([loadDashboard(), loadPlans()])
})

onUnmounted(() => {
  if (revenueChart) {
    revenueChart.destroy()
  }
  if (growthChart) {
    growthChart.destroy()
  }
  if (dateInterval) {
    clearInterval(dateInterval)
  }
})
</script>
