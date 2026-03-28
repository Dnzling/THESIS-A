<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Spend Analytics</h2>
        <p class="text-sm text-gray-500 mt-1">Procurement spending trends and pattern analysis</p>
      </div>
      <div class="flex gap-2">
        <Select
          v-model="selectedYear"
          :options="availableYears"
          placeholder="Select Year"
          class="w-32"
          @change="loadSpendAnalytics"
        />
        <Button
          icon="pi pi-refresh"
          rounded
          text
          @click="loadSpendAnalytics"
          :loading="loading"
        />
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card>
        <template #content>
          <div class="text-center">
            <p class="text-gray-600 text-sm">Total Spend</p>
            <p class="text-3xl font-bold text-blue-600 mt-2">₱ {{ formatNumber(totalSpend) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ selectedYear }}</p>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="text-center">
            <p class="text-gray-600 text-sm">Top Supplier</p>
            <p class="text-xl font-bold text-purple-600 mt-2">{{ topSupplier.name || 'N/A' }}</p>
            <p class="text-xs text-gray-500 mt-1">₱ {{ formatNumber(topSupplier.amount) }}</p>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="text-center">
            <p class="text-gray-600 text-sm">Avg Monthly Spend</p>
            <p class="text-3xl font-bold text-green-600 mt-2">₱ {{ formatNumber(avgMonthlySpend) }}</p>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="text-center">
            <p class="text-gray-600 text-sm">Purchase Orders</p>
            <p class="text-3xl font-bold text-orange-600 mt-2">{{ totalPOs }}</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Monthly Spend Chart -->
      <Card>
        <template #title>Monthly Spend Trend</template>
        <template #content>
          <Chart
            type="line"
            :data="monthlyChartData"
            :options="chartOptions"
            class="w-full"
            style="height: 300px"
          />
        </template>
      </Card>

      <!-- Top Suppliers Chart -->
      <Card>
        <template #title>Top 10 Suppliers by Spend</template>
        <template #content>
          <Chart
            type="bar"
            :data="suppliersChartData"
            :options="suppliersChartOptions"
            class="w-full"
            style="height: 300px"
          />
        </template>
      </Card>
    </div>

    <!-- Category Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Category Pie Chart -->
      <Card>
        <template #title>Spend by Category</template>
        <template #content>
          <Chart
            type="doughnut"
            :data="categoryChartData"
            :options="categoryChartOptions"
            class="w-full"
            style="height: 300px"
          />
        </template>
      </Card>

      <!-- Category Table -->
      <Card>
        <template #title>Category Breakdown</template>
        <template #content>
          <DataTable :value="categoryBreakdown" class="p-datatable-sm">
            <Column field="category_name" header="Category" />
            <Column field="spend" header="Total Spend" style="width: 30%">
              <template #body="{ data }">
                ₱ {{ formatNumber(data.spend) }}
              </template>
            </Column>
            <Column field="percentage" header="%" style="width: 20%">
              <template #body="{ data }">
                <ProgressBar :value="data.percentage" />
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>
    </div>

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
const selectedYear = ref(new Date().getFullYear())
const availableYears = ref<number[]>([])
const monthlyData = ref<any[]>([])
const suppliersData = ref<any[]>([])
const categoryData = ref<any[]>([])

// Computed
const totalSpend = computed(() => {
  return monthlyData.value.reduce((sum, m) => sum + m.amount, 0)
})

const avgMonthlySpend = computed(() => {
  const nonZeroMonths = monthlyData.value.filter(m => m.amount > 0)
  return nonZeroMonths.length > 0 ? totalSpend.value / nonZeroMonths.length : 0
})

const totalPOs = computed(() => {
  return monthlyData.value.reduce((sum, m) => sum + (m.po_count || 0), 0)
})

const topSupplier = computed(() => {
  if (suppliersData.value.length === 0) return { name: '', amount: 0 }
  return suppliersData.value[0]
})

const categoryBreakdown = computed(() => {
  const total = totalSpend.value
  return categoryData.value.map(c => ({
    ...c,
    percentage: total > 0 ? Math.round((c.spend / total) * 100) : 0,
  }))
})

// Chart Data
const monthlyChartData = computed(() => ({
  labels: monthlyData.value.map(m => m.month),
  datasets: [
    {
      label: 'Monthly Spend',
      data: monthlyData.value.map(m => m.amount),
      fill: true,
      borderColor: '#3b82f6',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      tension: 0.4,
    },
  ],
}))

const suppliersChartData = computed(() => ({
  labels: suppliersData.value.slice(0, 10).map(s => s.supplier_name),
  datasets: [
    {
      label: 'Spend Amount',
      data: suppliersData.value.slice(0, 10).map(s => s.amount),
      backgroundColor: [
        '#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981',
        '#06b6d4', '#6366f1', '#ef4444', '#14b8a6', '#f97316',
      ],
    },
  ],
}))

const categoryChartData = computed(() => ({
  labels: categoryData.value.map(c => c.category_name),
  datasets: [
    {
      data: categoryData.value.map(c => c.spend),
      backgroundColor: [
        '#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981',
        '#06b6d4', '#6366f1', '#ef4444',
      ],
    },
  ],
}))

const chartOptions = {
  maintainAspectRatio: false,
  responsive: true,
  plugins: {
    legend: { display: true, position: 'bottom' },
  },
}

const suppliersChartOptions = {
  maintainAspectRatio: false,
  responsive: true,
  indexAxis: 'y',
  plugins: {
    legend: { display: false },
  },
}

const categoryChartOptions = {
  maintainAspectRatio: false,
  responsive: true,
  plugins: {
    legend: { position: 'bottom' },
  },
}

// Methods
async function loadSpendAnalytics() {
  loading.value = true
  try {
    const response = await procurementService.getSpendAnalytics({ year: selectedYear.value })
    
    monthlyData.value = response.data?.monthly || []
    suppliersData.value = response.data?.suppliers || []
    categoryData.value = response.data?.categories || []

    // Generate available years (current year and 2 years back)
    const currentYear = new Date().getFullYear()
    availableYears.value = Array.from({ length: 3 }, (_, i) => currentYear - i)
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load spend analytics',
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
  const currentYear = new Date().getFullYear()
  availableYears.value = Array.from({ length: 3 }, (_, i) => currentYear - i)
  selectedYear.value = currentYear
  loadSpendAnalytics()
})
</script>
