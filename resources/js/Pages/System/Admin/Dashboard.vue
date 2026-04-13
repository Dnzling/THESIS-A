<!-- views/admin/Dashboard.vue -->
<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <!-- Welcome Header -->
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
      <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-gray-900 sm:text-3xl">FurnitureSync Admin Dashboard</h1>
          <p class="mt-2 text-sm text-gray-500">Welcome back, Admin. Here’s your platform overview.</p>
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
        <div class="rounded-2xl border border-gray-100 bg-gray-50 px-5 py-4 text-left sm:text-right">
          <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Total Platform Revenue</div>
          <div class="mt-2 text-3xl font-semibold text-gray-900 sm:text-4xl">{{ formatCurrency(totalPlatformRevenue) }}</div>
        </div>
      </div>
    </div>

    <!-- Improved KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
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
            <div class="text-xs text-gray-400">Active Subscriptions</div>
            <div class="text-2xl font-bold text-gray-800 mt-1">{{ stats.activeSubscriptions }}</div>
            <div class="text-sm text-green-500 mt-1">{{ stats.subscriptionGrowth }}% growth</div>
          </div>
          <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center">
            <i class="pi pi-credit-card text-green-600 text-xl"></i>
          </div>
        </div>
      </div>

      <div class="bg-white shadow-md rounded-2xl p-4 flex flex-col justify-between">
        <div class="flex items-start justify-between">
          <div>
            <div class="text-xs text-gray-400">Pending Validations</div>
            <div class="text-2xl font-bold text-gray-800 mt-1">{{ stats.pendingValidations }}</div>
            <div class="text-sm text-yellow-500 mt-1">Requires attention</div>
          </div>
          <div class="w-12 h-12 bg-yellow-50 rounded-lg flex items-center justify-center">
            <i class="pi pi-clock text-yellow-600 text-xl"></i>
          </div>
        </div>
      </div>

      <div class="bg-white shadow-md rounded-2xl p-4 flex flex-col justify-between">
        <div class="flex items-start justify-between">
          <div>
            <div class="text-xs text-gray-400">Monthly Revenue</div>
            <div class="text-2xl font-bold text-gray-800 mt-1">{{ formatCurrency(stats.monthlyRevenue) }}</div>
            <div class="text-sm text-green-500 mt-1">+{{ stats.revenueGrowth }}% vs last month</div>
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
          <h3 class="text-lg font-semibold text-gray-800">Revenue Trend</h3>
          <div class="flex space-x-2">
            <Button 
              @click="setRevenueChartView('monthly')" 
              :severity="revenueChartView === 'monthly' ? 'primary' : 'secondary'"
              size="small"
            >
              Monthly
            </Button>
            <Button 
              @click="setRevenueChartView('yearly')" 
              :severity="revenueChartView === 'yearly' ? 'primary' : 'secondary'"
              size="small"
            >
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
          <Select 
            v-model="growthPeriod" 
            :options="growthPeriodOptions" 
            optionLabel="name" 
            placeholder="Last 6 months"
            class="w-50"
          />
        </div>
        <div class="h-72">
          <canvas ref="growthChartRef"></canvas>
        </div>
      </div>
    </div>

    <!-- Subscription Overview -->
    <div class="bg-white shadow rounded-xl p-6">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold text-gray-800">Subscription Overview</h3>
        <router-link to="/admin/subscriptions" class="text-blue-600 text-sm font-medium hover:text-blue-800">
          Manage All Plans →
        </router-link>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div v-for="plan in subscriptionPlans" :key="plan.id" 
             class="border rounded-lg p-4 hover:shadow-md transition-shadow">
          <div class="flex justify-between items-start mb-3">
            <div>
              <h4 class="font-bold text-gray-800">{{ plan.name }}</h4>
              <p class="text-2xl font-bold mt-2">{{ formatCurrency(plan.price) }}<span class="text-sm text-gray-500">/{{ plan.period }}</span></p>
            </div>
            <Tag :value="plan.status" :severity="getPlanStatusSeverity(plan.status)" />
          </div>
          <div class="space-y-2 mb-4">
            <div class="flex items-center text-sm text-gray-600">
              <i class="pi pi-check-circle text-green-500 mr-2"></i>
              <span>{{ plan.features.stores }} Stores</span>
            </div>
            <div class="flex items-center text-sm text-gray-600">
              <i class="pi pi-check-circle text-green-500 mr-2"></i>
              <span>{{ plan.features.products }} Products</span>
            </div>
            <div class="flex items-center text-sm text-gray-600">
              <i class="pi pi-check-circle text-green-500 mr-2"></i>
              <span>{{ plan.features.users }} Users</span>
            </div>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-sm text-gray-500">{{ plan.subscribers }} subscribers</span>
            <Button 
              label="Manage" 
              size="small"
              severity="secondary"
              @click="managePlan(plan)"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Pending Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Pending Store Registrations -->
      <div class="bg-white shadow rounded-xl p-6">
        <div class="flex justify-between items-center mb-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-800">Pending Store Registrations</h3>
            <p class="text-sm text-gray-500">{{ pendingStores.length }} stores awaiting approval</p>
          </div>
          <Button 
            label="Review All" 
            size="small"
            @click="goToPendingApprovals"
          />
        </div>
        
        <div class="space-y-3">
          <div v-for="store in pendingStores.slice(0, 3)" :key="store.id" 
               class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100">
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="pi pi-store text-blue-600"></i>
              </div>
              <div>
                <p class="font-medium">{{ store.name }}</p>
                <p class="text-xs text-gray-500">{{ store.owner }} • {{ store.waitingTime }}</p>
              </div>
            </div>
            <div class="flex space-x-2">
              <Button 
                icon="pi pi-check" 
                size="small" 
                severity="success"
                @click="approveStore(store)"
              />
              <Button 
                icon="pi pi-times" 
                size="small" 
                severity="danger"
                @click="rejectStore(store)"
              />
            </div>
          </div>
        </div>
        
        <div v-if="pendingStores.length > 3" class="mt-4 text-center">
          <span class="text-sm text-gray-500">+{{ pendingStores.length - 3 }} more pending stores</span>
        </div>
      </div>

      <!-- Recent Payments -->
      <div class="bg-white shadow rounded-xl p-6">
        <div class="flex justify-between items-center mb-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-800">Recent Payments</h3>
            <p class="text-sm text-gray-500">Latest subscription payments</p>
          </div>
          <router-link to="/finance/receivables" class="text-blue-600 text-sm font-medium hover:text-blue-800">
            View All →
          </router-link>
        </div>
        
        <DataTable :value="recentPayments" tableStyle="min-width: 50rem">
          <Column field="store" header="Store" style="width: 30%">
            <template #body="slotProps">
              <div class="flex items-center">
                <i class="pi pi-store text-gray-400 mr-2"></i>
                <span class="text-sm">{{ slotProps.data.store }}</span>
              </div>
            </template>
          </Column>
          
          <Column field="amount" header="Amount" style="width: 25%">
            <template #body="slotProps">
              <span class="font-bold">{{ formatCurrency(slotProps.data.amount) }}</span>
            </template>
          </Column>
          
          <Column field="date" header="Date" style="width: 25%">
            <template #body="slotProps">
              <span class="text-sm text-gray-500">{{ slotProps.data.date }}</span>
            </template>
          </Column>
          
          <Column field="status" header="Status" style="width: 20%">
            <template #body="slotProps">
              <Tag 
                :value="slotProps.data.status" 
                :severity="getPaymentStatusSeverity(slotProps.data.status)"
                rounded
              />
            </template>
          </Column>
        </DataTable>
      </div>

      <!-- Recent Activities -->
         <!-- Recent Activity -->
      <div class="lg:col-span-2">
        <div class="bg-white shadow rounded-xl p-6">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-semibold text-gray-800">Recent Activity</h3>
            <Button 
              label="View All" 
              text 
              size="small"
              @click="goToActivityLog"
            />
          </div>
          
          <DataTable :value="recentActivities" tableStyle="min-width: 50rem">
            <Column field="time" header="Time" style="width: 15%">
              <template #body="slotProps">
                <span class="text-sm text-gray-500">{{ slotProps.data.time }}</span>
              </template>
            </Column>
            
            <Column field="action" header="Action" style="width: 25%">
              <template #body="slotProps">
                <div class="flex items-center">
                  <div :class="`w-8 h-8 rounded-full flex items-center justify-center mr-3 ${slotProps.data.iconBg}`">
                    <i :class="`${slotProps.data.icon} ${slotProps.data.iconColor}`"></i>
                  </div>
                  <span>{{ slotProps.data.action }}</span>
                </div>
              </template>
            </Column>
            
            <Column field="description" header="Description" style="width: 40%">
              <template #body="slotProps">
                <span class="text-sm">{{ slotProps.data.description }}</span>
              </template>
            </Column>
            
            <Column field="status" header="Status" style="width: 20%">
              <template #body="slotProps">
                <Tag 
                  :value="slotProps.data.status" 
                  :severity="getStatusSeverity(slotProps.data.status)"
                  rounded
                />
              </template>
            </Column>
          </DataTable>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import axiosClient from '@/axios'
import { useRouter } from 'vue-router'
import { Chart, registerables } from 'chart.js'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Select from 'primevue/select'

// Register Chart.js
Chart.register(...registerables)

const router = useRouter()

// Chart refs
const revenueChartRef = ref<HTMLCanvasElement | null>(null)
const growthChartRef = ref<HTMLCanvasElement | null>(null)
let revenueChart: Chart | null = null
let growthChart: Chart | null = null
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
})

const totalPlatformRevenue = ref(0)
const revenueSeries = ref({
  monthly: { labels: [] as string[], platformRevenue: [] as number[], subscriptionRevenue: [] as number[] },
  yearly: { labels: [] as string[], platformRevenue: [] as number[], subscriptionRevenue: [] as number[] },
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

const subscriptionPlans = ref([])

const pendingStores = ref([])

const recentPayments = ref([])

// Helper Functions
const formatCurrency = (amount: number) => {
  return '₱' + amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
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

// Chart Functions
const initRevenueChart = () => {
  if (!revenueChartRef.value) return
  
  if (revenueChart) {
    revenueChart.destroy()
  }
  
  const ctx = revenueChartRef.value.getContext('2d')
  if (!ctx) return
  
  const data = revenueChartView.value === 'monthly' 
    ? revenueSeries.value.monthly 
    : revenueSeries.value.yearly
  
  revenueChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: data.labels,
      datasets: [
        {
          label: 'Platform Revenue',
          data: data.platformRevenue,
          borderColor: '#4f46e5',
          backgroundColor: 'rgba(79, 70, 229, 0.1)',
          borderWidth: 3,
          fill: true,
          tension: 0.4
        },
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
            callback: function(value) {
              return '₱' + (Number(value) / 1000).toFixed(0) + 'K'
            }
          }
        }
      }
    }
  })
}

const initGrowthChart = () => {
  if (!growthChartRef.value) return
  
  if (growthChart) {
    growthChart.destroy()
  }
  
  const ctx = growthChartRef.value.getContext('2d')
  if (!ctx) return
  
  const growthData = storeGrowthSeries.value
  growthChart = new Chart(ctx, {
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
  initRevenueChart()
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
      totalPlatformRevenue.value = d.total_platform_revenue || 0

      revenueSeries.value = d.revenue_series || revenueSeries.value
      storeGrowthSeries.value = d.store_growth_series || storeGrowthSeries.value

      initRevenueChart()
      initGrowthChart()
      // recent activities
      if (Array.isArray(d.recent_activities)) {
        recentActivities.value = d.recent_activities.map((r:any, idx:number)=>({
          id: idx+1,
          time: r.time,
          action: r.action,
          description: r.description,
          status: r.status,
          icon: 'pi pi-info-circle',
          iconColor: 'text-blue-600',
          iconBg: 'bg-blue-100'
        }))
      }
    }
  } catch (e) {
    console.error('Failed to load dashboard data', e)
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
  
  loadDashboard()
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
