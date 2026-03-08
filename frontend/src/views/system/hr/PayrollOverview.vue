<template>
  <div class="space-y-6">
    <!-- Header with Date Filter -->
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold">Payroll Overview</h1>
      <div class="flex gap-2">
        <Select v-model="trendYear" :options="years" placeholder="Select Year" class="w-32" @change="fetchOverview" />
        <Button label="Export Report" icon="pi pi-download" outlined />
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div v-for="card in summaryCards" :key="card.title" 
           class="bg-white rounded-lg shadow-sm p-6 border-l-4" 
           :class="card.borderColor">
        <div class="flex justify-between items-start">
          <div>
            <span class="text-gray-500 text-sm">{{ card.title }}</span>
            <div class="text-2xl font-bold mt-1">{{ card.value }}</div>
            <div v-if="card.trend" class="text-sm mt-2" :class="card.trendColor">
              <i :class="card.trendIcon" class="mr-1"></i>
              {{ card.trend }} from last month
            </div>
          </div>
          <div class="p-3 rounded-lg" :class="card.bgColor">
            <i :class="[card.icon, 'text-xl', card.color]"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Payroll Trends Chart - Takes 2 columns -->
      <Card class="lg:col-span-2">
        <template #title>
          <div class="flex justify-between items-center">
            <div class="flex items-center gap-2">
              <span>Payroll Trends</span>
              <span class="text-sm text-gray-500">{{ trendYear }}</span>
            </div>
            <div class="flex gap-2">
              <Button icon="pi pi-calendar" text rounded />
              <Button icon="pi pi-refresh" text rounded @click="fetchOverview" />
            </div>
          </div>
        </template>
        <template #content>
          <div class="h-80">
            <canvas ref="trendChartRef"></canvas>
          </div>
        </template>
      </Card>

      <!-- Summary Stats -->
      <Card>
        <template #title>Quick Stats</template>
        <template #content>
          <div class="space-y-4">
            <div class="flex justify-between items-center pb-2 border-b">
              <span class="text-gray-600">Average Payroll</span>
              <span class="font-semibold">{{ formatCurrency(averagePayroll) }}</span>
            </div>
            <div class="flex justify-between items-center pb-2 border-b">
              <span class="text-gray-600">Highest Department</span>
              <span class="font-semibold">{{ highestDepartment }}</span>
            </div>
            <div class="flex justify-between items-center pb-2 border-b">
              <span class="text-gray-600">Lowest Department</span>
              <span class="font-semibold">{{ lowestDepartment }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-600">Payroll Period</span>
              <span class="font-semibold">Bi-monthly</span>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Department Breakdown & Upcoming Periods -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Department Breakdown -->
      <Card class="lg:col-span-2">
        <template #title>
          <div class="flex items-center gap-2">
            <span>Department Breakdown</span>
            <span class="text-sm text-gray-500">(by total payroll)</span>
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            <!-- Department List -->
            <div v-for="dept in departmentList" :key="dept.id" 
                 class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg transition">
              <div class="flex items-center gap-3 flex-1">
                <div class="w-2 h-10 rounded-full" :style="{ backgroundColor: dept.color }"></div>
                <div>
                  <div class="font-medium">{{ dept.department }}</div>
                  <div class="text-sm text-gray-500">{{ dept.employee_count }} employees</div>
                </div>
              </div>
              <div class="text-right">
                <div class="font-semibold">{{ formatCurrency(dept.total_payroll) }}</div>
                <div class="text-sm text-gray-500">{{ dept.percentage }}% of total</div>
              </div>
            </div>

            <!-- Mini Pie Chart Preview -->
            <div class="mt-4 pt-4 border-t">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Distribution Overview</span>
                <span class="text-xs text-gray-400">Total: {{ formatCurrency(totalPayroll) }}</span>
              </div>
              <div class="flex h-2 mt-2 rounded-full overflow-hidden">
                <div v-for="(dept, index) in departmentList" :key="index"
                     :style="{ width: dept.percentage + '%', backgroundColor: dept.color }"
                     :title="dept.department + ': ' + dept.percentage + '%'">
                </div>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Upcoming Pay Periods -->
      <Card>
        <template #title>
          <div class="flex items-center gap-2">
            <span>Upcoming Pay Periods</span>
            <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">{{ upcomingPeriods.length }}</span>
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            <div v-for="period in upcomingPeriods" :key="period.id" 
                 class="border rounded-lg p-4 hover:shadow-md transition">
              <div class="flex justify-between items-start mb-2">
                <span class="font-medium">{{ period.period }}</span>
                <Tag :value="period.employees + ' employees'" severity="info" rounded />
              </div>
              <div class="grid grid-cols-2 gap-2 text-sm">
                <div>
                  <span class="text-gray-500 block">Cutoff Date</span>
                  <span class="font-medium">{{ period.cutoff }}</span>
                </div>
                <div>
                  <span class="text-gray-500 block">Pay Date</span>
                  <span class="font-medium">{{ period.payDate }}</span>
                </div>
              </div>
            </div>
            
            <div v-if="upcomingPeriods.length === 0" class="text-center py-8 text-gray-500">
              <i class="pi pi-calendar text-3xl mb-2 block"></i>
              <span>No upcoming periods</span>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Recent Activity -->
    <Card>
      <template #title>
        <div class="flex items-center gap-2">
          <span>Recent Activity</span>
          <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full">{{ activities.length }}</span>
        </div>
      </template>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
          <div v-for="(activity, index) in activities" :key="index"
               class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
            <div class="p-2 bg-white rounded-lg">
              <i :class="activity.icon" class="text-lg"></i>
            </div>
            <div>
              <div class="font-medium text-sm">{{ activity.action }}</div>
              <div class="text-xs text-gray-500 mt-1">
                <i class="pi pi-clock mr-1"></i>
                {{ activity.time }}
              </div>
            </div>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import { useAuthStore } from '../../../stores/auth'
import Chart from 'chart.js/auto'

const authStore = useAuthStore()

// Summary Cards
const summaryCards = ref([
  { 
    title: 'Total Payroll', 
    value: '₱0', 
    icon: 'pi pi-money-bill', 
    color: 'text-green-600',
    bgColor: 'bg-green-100',
    borderColor: 'border-green-500',
    trend: '+12.5%',
    trendColor: 'text-green-600',
    trendIcon: 'pi pi-arrow-up'
  },
  { 
    title: 'Pending Approval', 
    value: '0', 
    icon: 'pi pi-clock', 
    color: 'text-yellow-600',
    bgColor: 'bg-yellow-100',
    borderColor: 'border-yellow-500',
    trend: '3 awaiting',
    trendColor: 'text-yellow-600',
    trendIcon: 'pi pi-exclamation-triangle'
  },
  { 
    title: 'Paid Amount', 
    value: '₱0', 
    icon: 'pi pi-check-circle', 
    color: 'text-blue-600',
    bgColor: 'bg-blue-100',
    borderColor: 'border-blue-500',
    trend: 'This period',
    trendColor: 'text-blue-600',
    trendIcon: 'pi pi-verified'
  },
  { 
    title: 'Active Employees', 
    value: '0', 
    icon: 'pi pi-users', 
    color: 'text-purple-600',
    bgColor: 'bg-purple-100',
    borderColor: 'border-purple-500',
    trend: 'All departments',
    trendColor: 'text-purple-600',
    trendIcon: 'pi pi-sitemap'
  }
])

// Charts
const trendYear = ref('2024')
const years = ['2024', '2023', '2022']
const trendChartRef = ref<HTMLCanvasElement | null>(null)
let trendChart: Chart | null = null

// Department data
const departmentList = ref<any[]>([])
const totalPayroll = ref(0)
const averagePayroll = ref(0)
const highestDepartment = ref('')
const lowestDepartment = ref('')

// Upcoming Periods
const upcomingPeriods = ref([])

// Recent Activity
const activities = ref([])

// Colors for departments
const departmentColors = [
  '#3B82F6', // Blue
  '#10B981', // Green
  '#F59E0B', // Yellow
  '#EF4444', // Red
  '#8B5CF6', // Purple
  '#EC4899', // Pink
  '#6366F1', // Indigo
  '#14B8A6'  // Teal
]

// Format currency
const formatCurrency = (value: number) => {
  return '₱' + value.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// Initialize or update trend chart
const updateTrendChart = (data: any) => {
  if (!trendChartRef.value) return

  if (trendChart) {
    trendChart.destroy()
  }

  const ctx = trendChartRef.value.getContext('2d')
  if (!ctx) return

  trendChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: data.labels,
      datasets: [{
        label: 'Payroll Amount',
        data: data.values,
        borderColor: '#3B82F6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        tension: 0.4,
        fill: true,
        pointBackgroundColor: '#3B82F6',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          callbacks: {
            label: (context) => {
              return 'Amount: ' + formatCurrency(context.raw as number)
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: (value) => '₱' + value.toLocaleString()
          }
        }
      }
    }
  })
}

// Fetch overview data from API
const fetchOverview = async () => {
  try {
    const response = await axios.get('api/payroll/overview', {
      headers: {
        'Authorization': `Bearer ${authStore.token}`
      },
      params: { year: trendYear.value }
    })

    if (response.data.success) {
      const data = response.data.data

      // Update summary cards
      summaryCards.value = [
        { 
          title: 'Total Payroll', 
          value: '₱' + (parseFloat(data.summary.total_payroll) || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }), 
          icon: 'pi pi-money-bill', 
          color: 'text-green-600',
          bgColor: 'bg-green-100',
          borderColor: 'border-green-500',
          trend: '+12.5%',
          trendColor: 'text-green-600',
          trendIcon: 'pi pi-arrow-up'
        },
        { 
          title: 'Pending Approval', 
          value: String(data.summary.pending_approval || 0), 
          icon: 'pi pi-clock', 
          color: 'text-yellow-600',
          bgColor: 'bg-yellow-100',
          borderColor: 'border-yellow-500',
          trend: data.summary.pending_approval + ' awaiting',
          trendColor: 'text-yellow-600',
          trendIcon: 'pi pi-exclamation-triangle'
        },
        { 
          title: 'Paid Amount', 
          value: '₱' + (parseFloat(data.summary.paid_amount) || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }), 
          icon: 'pi pi-check-circle', 
          color: 'text-blue-600',
          bgColor: 'bg-blue-100',
          borderColor: 'border-blue-500',
          trend: 'This period',
          trendColor: 'text-blue-600',
          trendIcon: 'pi pi-verified'
        },
        { 
          title: 'Active Employees', 
          value: String(data.summary.active_employees || 0), 
          icon: 'pi pi-users', 
          color: 'text-purple-600',
          bgColor: 'bg-purple-100',
          borderColor: 'border-purple-500',
          trend: 'All departments',
          trendColor: 'text-purple-600',
          trendIcon: 'pi pi-sitemap'
        }
      ]

      // Update trend data
      if (data.trends && data.trends.length > 0) {
        const labels = data.trends.map((t: any) => t.month)
        const values = data.trends.map((t: any) => parseFloat(t.amount) || 0)
        
        updateTrendChart({ labels, values })
      }

      // Update department breakdown
      if (data.department_breakdown) {
        const departments = Object.values(data.department_breakdown)
        const total = departments.reduce((sum: number, dept: any) => sum + parseFloat(dept.total_payroll), 0)
        
        totalPayroll.value = total
        averagePayroll.value = total / departments.length

        // Calculate percentages and add colors
        departmentList.value = departments.map((dept: any, index: number) => ({
          ...dept,
          total_payroll: parseFloat(dept.total_payroll),
          percentage: ((parseFloat(dept.total_payroll) / total) * 100).toFixed(1),
          color: departmentColors[index % departmentColors.length]
        }))

        // Sort by total_payroll descending
        departmentList.value.sort((a, b) => b.total_payroll - a.total_payroll)

        // Get highest and lowest departments
        if (departmentList.value.length > 0) {
          highestDepartment.value = departmentList.value[0].department
          lowestDepartment.value = departmentList.value[departmentList.value.length - 1].department
        }
      }

      // Update upcoming periods
      if (data.upcoming_periods && data.upcoming_periods.length > 0) {
        upcomingPeriods.value = data.upcoming_periods
      }

      // Update activities
      if (data.recent_activities && data.recent_activities.length > 0) {
        activities.value = data.recent_activities
      }
    }
  } catch (error) {
    console.error('Failed to fetch payroll overview:', error)
  }
}

// Watch for year changes
watch(trendYear, () => {
  fetchOverview()
})

// Fetch data on component mount
onMounted(() => {
  fetchOverview()
})
</script>

<style scoped>
.card-hover {
  transition: all 0.2s ease;
}

.card-hover:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
}
</style>