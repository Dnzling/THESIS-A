<!-- views/admin/Dashboard.vue -->
<template>
  <div class="space-y-6">
    <!-- Welcome Header -->
    <div class="bg-linear-to-r from-blue-600 to-purple-600 text-white shadow-xl rounded-2xl p-8">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold">FurnitureSync Admin Dashboard</h1>
          <p class="text-blue-100 mt-2">Welcome back, Admin! Here's your platform overview</p>
          <div class="flex items-center space-x-4 mt-4">
            <div class="flex items-center">
              <i class="pi pi-calendar mr-2"></i>
              <span>{{ currentDate }}</span>
            </div>
            <div class="flex items-center">
              <i class="pi pi-clock mr-2"></i>
              <span>{{ currentTime }}</span>
            </div>
          </div>
        </div>
        <div class="text-right">
          <div class="text-4xl font-bold">{{ formatCurrency(totalPlatformRevenue) }}</div>
          <p class="text-blue-200">Total Platform Revenue</p>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <!-- Active Stores -->
      <div class="bg-white shadow rounded-xl p-6">
        <div class="flex items-center justify-between">
          <div>
            <h6 class="text-sm font-semibold text-gray-500">Active Stores</h6>
            <p class="text-2xl font-bold text-gray-800 mt-2">{{ stats.activeStores }}</p>
            <p class="text-xs text-green-500 mt-1">+{{ stats.newStoresThisWeek }} this week</p>
          </div>
          <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
            <i class="pi pi-store text-blue-600 text-xl"></i>
          </div>
        </div>
        <div class="mt-4">
          <router-link to="/admin/stores" class="text-blue-600 text-sm font-medium hover:text-blue-800">
            View All Stores →
          </router-link>
        </div>
      </div>

      <!-- Active Subscriptions -->
      <div class="bg-white shadow rounded-xl p-6">
        <div class="flex items-center justify-between">
          <div>
            <h6 class="text-sm font-semibold text-gray-500">Active Subscriptions</h6>
            <p class="text-2xl font-bold text-gray-800 mt-2">{{ stats.activeSubscriptions }}</p>
            <p class="text-xs text-green-500 mt-1">{{ stats.subscriptionGrowth }}% growth</p>
          </div>
          <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
            <i class="pi pi-credit-card text-green-600 text-xl"></i>
          </div>
        </div>
        <div class="mt-4">
          <router-link to="/admin/subscriptions/active" class="text-blue-600 text-sm font-medium hover:text-blue-800">
            Manage Subscriptions →
          </router-link>
        </div>
      </div>

      <!-- Pending Validations -->
      <div class="bg-white shadow rounded-xl p-6">
        <div class="flex items-center justify-between">
          <div>
            <h6 class="text-sm font-semibold text-gray-500">Pending Validations</h6>
            <p class="text-2xl font-bold text-gray-800 mt-2">{{ stats.pendingValidations }}</p>
            <p class="text-xs text-yellow-500 mt-1">Requires attention</p>
          </div>
          <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
            <i class="pi pi-clock text-yellow-600 text-xl"></i>
          </div>
        </div>
        <div class="mt-4">
          <router-link to="/admin/store-validation" class="text-blue-600 text-sm font-medium hover:text-blue-800">
            Review Now →
          </router-link>
        </div>
      </div>

      <!-- Monthly Revenue -->
      <div class="bg-white shadow rounded-xl p-6">
        <div class="flex items-center justify-between">
          <div>
            <h6 class="text-sm font-semibold text-gray-500">Monthly Revenue</h6>
            <p class="text-2xl font-bold text-gray-800 mt-2">{{ formatCurrency(stats.monthlyRevenue) }}</p>
            <p class="text-xs text-green-500 mt-1">+{{ stats.revenueGrowth }}% vs last month</p>
          </div>
          <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
            <i class="pi pi-chart-line text-purple-600 text-xl"></i>
          </div>
        </div>
        <div class="mt-4">
          <router-link to="/admin/analytics" class="text-blue-600 text-sm font-medium hover:text-blue-800">
            View Analytics →
          </router-link>
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
          <router-link to="/admin/billing" class="text-blue-600 text-sm font-medium hover:text-blue-800">
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

// State
const revenueChartView = ref<'monthly' | 'yearly'>('monthly')
const growthPeriod = ref({ name: 'Last 6 months', value: '6months' })
const currentDate = ref('')
const currentTime = ref('')

// Stats Data
const stats = ref({
  activeStores: 156,
  newStoresThisWeek: 12,
  activeSubscriptions: 142,
  subscriptionGrowth: 8.5,
  pendingValidations: 23,
  monthlyRevenue: 452500,
  revenueGrowth: 15.2
})

const totalPlatformRevenue = computed(() => 3250000)

// Growth Period Options
const growthPeriodOptions = ref([
  { name: 'Last 3 months', value: '3months' },
  { name: 'Last 6 months', value: '6months' },
  { name: 'Last 12 months', value: '12months' },
  { name: 'Year to Date', value: 'ytd' }
])

// Recent Activities
const recentActivities = ref([
  {
    id: 1,
    time: '10:30 AM',
    action: 'Store Approved',
    description: 'Modern Furniture Hub approved',
    status: 'Completed',
    icon: 'pi pi-check-circle',
    iconColor: 'text-green-600',
    iconBg: 'bg-green-100'
  },
  {
    id: 2,
    time: '9:45 AM',
    action: 'Payment Received',
    description: 'Premium subscription payment from Classic Furniture',
    status: 'Success',
    icon: 'pi pi-credit-card',
    iconColor: 'text-blue-600',
    iconBg: 'bg-blue-100'
  },
  {
    id: 3,
    time: '9:15 AM',
    action: 'Store Registration',
    description: 'New store registration submitted',
    status: 'Pending',
    icon: 'pi pi-clock',
    iconColor: 'text-yellow-600',
    iconBg: 'bg-yellow-100'
  },
  {
    id: 4,
    time: 'Yesterday',
    action: 'Subscription Renewal',
    description: 'Office Solutions Inc renewed premium plan',
    status: 'Completed',
    icon: 'pi pi-sync',
    iconColor: 'text-green-600',
    iconBg: 'bg-green-100'
  },
  {
    id: 5,
    time: 'Yesterday',
    action: 'Account Suspended',
    description: 'Wood Crafts Studio suspended for violation',
    status: 'Warning',
    icon: 'pi pi-exclamation-triangle',
    iconColor: 'text-red-600',
    iconBg: 'bg-red-100'
  }
])

// Subscription Plans
const subscriptionPlans = ref([
  {
    id: 1,
    name: 'Basic',
    price: 2999,
    period: 'month',
    status: 'Active',
    subscribers: 78,
    features: {
      stores: 1,
      products: 100,
      users: 2
    }
  },
  {
    id: 2,
    name: 'Premium',
    price: 7999,
    period: 'month',
    status: 'Popular',
    subscribers: 52,
    features: {
      stores: 3,
      products: 500,
      users: 5
    }
  },
  {
    id: 3,
    name: 'Enterprise',
    price: 19999,
    period: 'month',
    status: 'Active',
    subscribers: 12,
    features: {
      stores: 10,
      products: 'Unlimited',
      users: 20
    }
  }
])

// Pending Stores
const pendingStores = ref([
  {
    id: 1,
    name: 'Modern Furniture Hub',
    owner: 'Juan Dela Cruz',
    waitingTime: '2 days',
    registrationDate: '2024-01-15'
  },
  {
    id: 2,
    name: 'Wood Crafts Studio',
    owner: 'Maria Santos',
    waitingTime: '1 day',
    registrationDate: '2024-01-16'
  },
  {
    id: 3,
    name: 'Luxury Home Decor',
    owner: 'Robert Lim',
    waitingTime: '3 days',
    registrationDate: '2024-01-14'
  },
  {
    id: 4,
    name: 'Office Solutions Inc',
    owner: 'Sarah Chen',
    waitingTime: 'Just now',
    registrationDate: '2024-01-17'
  },
  {
    id: 5,
    name: 'Eco Furniture Co',
    owner: 'David Green',
    waitingTime: '4 days',
    registrationDate: '2024-01-13'
  }
])

// Recent Payments
const recentPayments = ref([
  {
    id: 1,
    store: 'Classic Furniture Gallery',
    amount: 7999,
    date: 'Today',
    status: 'Paid'
  },
  {
    id: 2,
    store: 'Modern Living Spaces',
    amount: 7999,
    date: 'Today',
    status: 'Paid'
  },
  {
    id: 3,
    store: 'Kids Furniture World',
    amount: 2999,
    date: 'Yesterday',
    status: 'Paid'
  },
  {
    id: 4,
    store: 'Outdoor Living Co',
    amount: 19999,
    date: 'Jan 15, 2024',
    status: 'Paid'
  },
  {
    id: 5,
    store: 'Smart Furniture Tech',
    amount: 7999,
    date: 'Jan 14, 2024',
    status: 'Pending'
  }
])

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
    ? monthlyRevenueData 
    : yearlyRevenueData
  
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
  
  growthChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
      datasets: [
        {
          label: 'New Stores',
          data: [12, 15, 18, 22, 25, 28],
          backgroundColor: 'rgba(79, 70, 229, 0.8)',
          borderColor: 'rgb(79, 70, 229)',
          borderWidth: 1
        },
        {
          label: 'Active Stores',
          data: [120, 125, 130, 140, 148, 156],
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

// Chart Data
const monthlyRevenueData = {
  labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
  platformRevenue: [250000, 280000, 310000, 295000, 325000, 350000, 375000, 400000, 420000, 435000, 450000, 462000],
  subscriptionRevenue: [150000, 165000, 180000, 175000, 190000, 205000, 220000, 235000, 245000, 255000, 265000, 275000]
}

const yearlyRevenueData = {
  labels: ['2020', '2021', '2022', '2023', '2024'],
  platformRevenue: [1250000, 1850000, 2450000, 3100000, 3850000],
  subscriptionRevenue: [750000, 1200000, 1800000, 2400000, 3000000]
}

// Action Functions
const setRevenueChartView = (view: 'monthly' | 'yearly') => {
  revenueChartView.value = view
  initRevenueChart()
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
  router.push(`/admin/subscriptions/plans/${plan.id}`)
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
  setInterval(updateDateTime, 60000) // Update time every minute
  
  setTimeout(() => {
    initRevenueChart()
    initGrowthChart()
  }, 100)
})

onUnmounted(() => {
  if (revenueChart) {
    revenueChart.destroy()
  }
  if (growthChart) {
    growthChart.destroy()
  }
})
</script>