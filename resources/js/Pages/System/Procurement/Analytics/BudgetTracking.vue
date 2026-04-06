<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Budget Tracking</h2>
        <p class="text-sm text-gray-500 mt-1">Monitor procurement spending against allocated budgets</p>
      </div>
      <Button
        icon="pi pi-refresh"
        rounded
        text
        @click="loadBudgetData"
        :loading="loading"
      />
    </div>

    <!-- Overall Budget Summary -->
    <Card>
      <template #title>Annual Budget Summary</template>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <p class="text-gray-600 text-sm">Total Annual Budget</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">₱ {{ formatNumber(totalBudget) }}</p>
          </div>
          <div>
            <p class="text-gray-600 text-sm">YTD Spent</p>
            <p class="text-2xl font-bold text-green-600 mt-1">₱ {{ formatNumber(ytdSpent) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ ytdPercentage }}% utilized</p>
          </div>
          <div>
            <p class="text-gray-600 text-sm">Remaining Budget</p>
            <p class="text-2xl font-bold text-orange-600 mt-1">₱ {{ formatNumber(remainingBudget) }}</p>
          </div>
          <div>
            <p class="text-gray-600 text-sm">Monthly Avg</p>
            <p class="text-2xl font-bold text-purple-600 mt-1">₱ {{ formatNumber(monthlyAvg) }}</p>
          </div>
        </div>

        <!-- Overall Progress -->
        <div class="mt-6">
          <div class="flex justify-between items-center mb-2">
            <span class="text-sm font-semibold">Budget Utilization</span>
            <span class="text-sm text-gray-600">{{ ytdPercentage }}%</span>
          </div>
          <ProgressBar :value="ytdPercentage" class="h-8"></ProgressBar>
        </div>
      </template>
    </Card>

    <!-- Department/Category Budgets -->
    <Card>
      <template #title>Budget by Category</template>
      <template #content>
        <DataTable :value="categoryBudgets" :loading="loading" class="p-datatable-sm">
          <Column field="category_name" header="Category" style="width: 25%"></Column>
          <Column header="Budget" style="width: 15%">
            <template #body="{ data }">
              ₱ {{ formatNumber(data.budget) }}
            </template>
          </Column>
          <Column header="Spent" style="width: 15%">
            <template #body="{ data }">
              ₱ {{ formatNumber(data.spent) }}
            </template>
          </Column>
          <Column header="Remaining" style="width: 15%">
            <template #body="{ data }">
              <span :class="data.remaining >= 0 ? 'text-green-600' : 'text-red-600'">
                ₱ {{ formatNumber(data.remaining) }}
              </span>
            </template>
          </Column>
          <Column header="Usage %" style="width: 15%">
            <template #body="{ data }">
              <Badge
                :value="`${data.percentage}%`"
                :severity="data.percentage > 80 ? 'danger' : data.percentage > 60 ? 'warning' : 'success'"
              />
            </template>
          </Column>
          <Column header="Progress" style="width: 15%">
            <template #body="{ data }">
              <ProgressBar :value="data.percentage" class="h-6"></ProgressBar>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Monthly Trend vs Budget -->
    <Card>
      <template #title>Monthly Spend vs Budget Forecast</template>
      <template #content>
        <Chart
          type="line"
          :data="monthlyBudgetChartData"
          :options="monthlyBudgetChartOptions"
          class="w-full"
          style="height: 400px"
        />
      </template>
    </Card>

    <!-- Budget Alerts -->
    <Card v-if="budgetAlerts.length > 0">
      <template #title>Budget Alerts</template>
      <template #content>
        <div class="space-y-3">
          <div
            v-for="alert in budgetAlerts"
            :key="alert.id"
            class="flex items-start gap-3 p-3 rounded border"
            :class="alert.severity === 'danger' ? 'bg-red-50 border-red-200' : 'bg-yellow-50 border-yellow-200'"
          >
            <i
              :class="[
                'pi',
                alert.severity === 'danger' ? 'pi-exclamation-circle text-red-600' : 'pi-info-circle text-yellow-600',
              ]"
            ></i>
            <div>
              <p class="font-semibold" :class="alert.severity === 'danger' ? 'text-red-800' : 'text-yellow-800'">
                {{ alert.title }}
              </p>
              <p class="text-sm" :class="alert.severity === 'danger' ? 'text-red-700' : 'text-yellow-700'">
                {{ alert.message }}
              </p>
            </div>
          </div>
        </div>
      </template>
    </Card>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'

const toast = useToast()

// State
const loading = ref(false)
const categoryBudgets = ref<any[]>([])
const monthlyDataComparison = ref<any[]>([])

// Computed
const totalBudget = computed(() => {
  return categoryBudgets.value.reduce((sum, c) => sum + c.budget, 0)
})

const ytdSpent = computed(() => {
  return categoryBudgets.value.reduce((sum, c) => sum + c.spent, 0)
})

const ytdPercentage = computed(() => {
  return totalBudget.value > 0 ? Math.round((ytdSpent.value / totalBudget.value) * 100) : 0
})

const remainingBudget = computed(() => {
  return totalBudget.value - ytdSpent.value
})

const monthlyAvg = computed(() => {
  const currentMonth = new Date().getMonth() + 1
  return currentMonth > 0 ? ytdSpent.value / currentMonth : 0
})

const budgetAlerts = computed(() => {
  const alerts: Array<{
    id: number
    severity: string
    title: string
    message: string
  }> = []
  categoryBudgets.value.forEach(c => {
    if (c.percentage > 90) {
      alerts.push({
        id: c.category_id,
        severity: 'danger',
        title: `${c.category_name} Budget Critical`,
        message: `${c.percentage}% of budget used. Only ₱${formatNumber(c.remaining)} remaining.`,
      })
    } else if (c.percentage > 75) {
      alerts.push({
        id: c.category_id,
        severity: 'warning',
        title: `${c.category_name} Budget Warning`,
        message: `${c.percentage}% of budget used. Please review spending.`,
      })
    }
  })
  return alerts
})

// Chart Data
const monthlyBudgetChartData = computed(() => {
  const now = new Date()
  const startOfCurrentMonth = new Date(now.getFullYear(), now.getMonth(), 1)

  const filtered = monthlyDataComparison.value.filter(m => {
    if (!m?.month) return false
    const [y, mm] = m.month.split('-').map(Number)
    const asDate = new Date(y || now.getFullYear(), (mm || 1) - 1, 1)
    return asDate <= startOfCurrentMonth
  })

  return {
    labels: filtered.map(m => m.month),
    datasets: [
      {
        label: 'Budgeted',
        data: filtered.map(m => m.budgeted),
        backgroundColor: '#93c5fd33',
        borderColor: '#3b82f6',
        fill: true,
        tension: 0.25,
      },
      {
        label: 'Actual Spend',
        data: filtered.map(m => m.actual),
        backgroundColor: '#86efac66',
        borderColor: '#10b981',
        fill: true,
        tension: 0.25,
      },
    ],
  }
})

const monthlyBudgetChartOptions = {
  maintainAspectRatio: false,
  responsive: true,
  plugins: {
    legend: { display: true, position: 'bottom' },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: { callback: value => `₱ ${formatNumber(Number(value))}` },
    },
    x: {
      ticks: { autoSkip: false },
    },
  },
}

// Methods
async function loadBudgetData() {
  loading.value = true
  try {
    const response = await procurementService.getBudgetTracking({ year: new Date().getFullYear() })
    const payload = response?.data || {}
    const data = payload.data || payload

    categoryBudgets.value = Array.isArray(data?.categories) ? data.categories : []
    monthlyDataComparison.value = Array.isArray(data?.monthly) ? data.monthly : []

    // Calculate percentages
    categoryBudgets.value = categoryBudgets.value.map(c => ({
      ...c,
      remaining: c.budget - c.spent,
      percentage: c.budget > 0 ? Math.round((c.spent / c.budget) * 100) : 0,
    }))
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load budget data',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

function formatNumber(value: number): string {
  return new Intl.NumberFormat('en-PH').format(Math.round(value))
}

onMounted(() => {
  loadBudgetData()
})
</script>
