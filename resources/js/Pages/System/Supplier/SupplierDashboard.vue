<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'procurement.suppliers' })" />
        <div>
          <h2 class="text-2xl font-bold text-gray-800">Supplier Dashboard</h2>
          <p class="text-sm text-gray-500 mt-1">Overview of all supplier performance and metrics</p>
        </div>
      </div>
      <Button
        icon="pi pi-refresh"
        text
        rounded
        @click="loadDashboard"
        :loading="loading"
      />
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <Card class="border-l-4 border-blue-500">
        <template #content>
          <div class="text-center">
            <div class="text-3xl font-bold text-blue-600">{{ dashboardData.total_suppliers }}</div>
            <p class="text-sm text-gray-600 mt-1">Total Suppliers</p>
          </div>
        </template>
      </Card>

      <Card class="border-l-4 border-green-500">
        <template #content>
          <div class="text-center">
            <div class="text-3xl font-bold text-green-600">{{ dashboardData.active_suppliers }}</div>
            <p class="text-sm text-gray-600 mt-1">Active</p>
          </div>
        </template>
      </Card>

      <Card class="border-l-4 border-yellow-500">
        <template #content>
          <div class="text-center">
            <div class="text-3xl font-bold text-yellow-600">{{ dashboardData.inactive_suppliers }}</div>
            <p class="text-sm text-gray-600 mt-1">Inactive</p>
          </div>
        </template>
      </Card>

      <Card class="border-l-4 border-red-500">
        <template #content>
          <div class="text-center">
            <div class="text-3xl font-bold text-red-600">{{ dashboardData.blacklisted_suppliers }}</div>
            <p class="text-sm text-gray-600 mt-1">Blacklisted</p>
          </div>
        </template>
      </Card>

      <Card class="border-l-4 border-purple-500">
        <template #content>
          <div class="text-center">
            <div class="text-3xl font-bold text-purple-600">{{ atRiskCount }}</div>
            <p class="text-sm text-gray-600 mt-1">At Risk</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- Average Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <Card>
        <template #header>
          <div class="flex items-center gap-2">
            <i class="pi pi-star"></i>
            <span>Average Quality Score</span>
          </div>
        </template>
        <template #content>
          <div class="flex items-center gap-4">
            <ProgressBar :value="dashboardData.average_quality * 20" :show-value="false" class="flex-1" style="height: 30px" />
            <span class="text-2xl font-bold text-blue-600">{{ dashboardData.average_quality }}/5</span>
          </div>
        </template>
      </Card>

      <Card>
        <template #header>
          <div class="flex items-center gap-2">
            <i class="pi pi-heart"></i>
            <span>Average Rating</span>
          </div>
        </template>
        <template #content>
          <div class="flex items-center gap-4">
            <Rating v-model="dashboardData.average_rating" :cancel="false" :readonly="true" />
            <span class="text-2xl font-bold text-blue-600">{{ dashboardData.average_rating }}/5</span>
          </div>
        </template>
      </Card>
    </div>

    <!-- Charts Row 1 -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Top Performers -->
      <Card>
        <template #header>
          <div class="flex items-center gap-2">
            <i class="pi pi-award"></i>
            <span>Top 5 Performers</span>
          </div>
        </template>
        <template #content>
          <div class="space-y-3">
            <div
              v-for="(supplier, index) in dashboardData.top_performers"
              :key="supplier.id"
              class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
            >
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                  <Badge :value="index + 1" severity="info" />
                  <span class="font-semibold">{{ supplier.supplier_name }}</span>
                </div>
                <div class="flex gap-2">
                  <Rating v-model="supplier.rating" :cancel="false" :readonly="true" />
                  <span class="text-xs text-gray-500">{{ supplier.on_time_deliveries }} on-time</span>
                </div>
              </div>
              <div class="text-right">
                <div class="text-lg font-bold text-blue-600">{{ supplier.quality_score }}/5</div>
                <div class="text-xs text-gray-500">Quality</div>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Risk Distribution -->
      <Card>
        <template #header>
          <div class="flex items-center gap-2">
            <i class="pi pi-chart-pie"></i>
            <span>Risk Distribution</span>
          </div>
        </template>
        <template #content>
          <Chart type="doughnut" :data="riskDistributionData" :options="chartOptions" />
        </template>
      </Card>
    </div>

    <!-- Charts Row 2 -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Performance Overview -->
      <Card>
        <template #header>
          <div class="flex items-center gap-2">
            <i class="pi pi-chart-bar"></i>
            <span>Quality Distribution</span>
          </div>
        </template>
        <template #content>
          <Chart type="bar" :data="qualityDistributionData" :options="chartOptions" style="height: 300px" />
        </template>
      </Card>

      <!-- Category Breakdown -->
      <Card>
        <template #header>
          <div class="flex items-center gap-2">
            <i class="pi pi-list"></i>
            <span>Suppliers by Category</span>
          </div>
        </template>
        <template #content>
          <Chart type="bar" :data="categoryBreakdownData" :options="chartOptions" style="height: 300px" />
        </template>
      </Card>
    </div>

    <!-- At-Risk Suppliers -->
    <Card v-if="atRiskSuppliers.length > 0">
      <template #header>
        <div class="flex items-center gap-2">
          <i class="pi pi-exclamation-circle text-red-600"></i>
          <span>At-Risk Suppliers</span>
        </div>
      </template>
      <template #content>
        <DataTable :value="atRiskSuppliers" responsive-layout="scroll" class="w-full">
          <Column field="supplier_name" header="Supplier" class="w-32">
            <template #body="{ data }">
              <div class="font-semibold">{{ data.supplier_name }}</div>
              <div class="text-xs text-gray-500">{{ data.company_name }}</div>
            </template>
          </Column>

          <Column field="risk_score" header="Risk Score" class="w-24">
            <template #body="{ data }">
              <Tag :value="`${data.risk_score}/100`" :severity="data.risk_color" />
            </template>
          </Column>

          <Column field="risk_reasons" header="Risk Factors" class="w-48">
            <template #body="{ data }">
              <div class="space-y-1">
                <div v-for="(reason, idx) in data.risk_reasons.slice(0, 2)" :key="idx" class="text-xs text-gray-600">
                  • {{ reason }}
                </div>
                <div v-if="data.risk_reasons.length > 2" class="text-xs text-gray-500">
                  +{{ data.risk_reasons.length - 2 }} more
                </div>
              </div>
            </template>
          </Column>

          <Column header="Action" :exportable="false" class="w-20">
            <template #body="{ data }">
              <Button
                icon="pi pi-eye"
                text
                rounded
                severity="info"
                @click="viewSupplier(data.id)"
              />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- No At-Risk Suppliers Message -->
    <Card v-else>
      <template #content>
        <Message severity="success" text="All suppliers are performing well. No at-risk suppliers identified." />
      </template>
    </Card>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import supplierService from '../../../services/supplier.service'

const router = useRouter()
const toast = useToast()
const atRiskSuppliers = ref<any[]>([])

const getAtRiskSuppliers = async () => {
  try {
    const response = await supplierService.getAtRiskSuppliers()
    atRiskSuppliers.value = response.data || []
    return response
  } catch {
    atRiskSuppliers.value = []
    return null
  }
}

const loading = ref(false)
const dashboardData = ref({
  total_suppliers: 0,
  active_suppliers: 0,
  inactive_suppliers: 0,
  blacklisted_suppliers: 0,
  average_quality: 0,
  average_rating: 0,
  top_performers: []
})

const atRiskCount = computed(() => {
  return dashboardData.value.total_suppliers - 
         dashboardData.value.active_suppliers - 
         dashboardData.value.inactive_suppliers - 
         dashboardData.value.blacklisted_suppliers
})

const chartOptions = {
  maintainAspectRatio: false,
  responsive: true,
  plugins: {
    legend: {
      display: true,
      position: 'bottom'
    }
  }
}

const riskDistributionData = computed(() => ({
  labels: ['Low Risk', 'Medium Risk', 'High Risk', 'Critical'],
  datasets: [
    {
      data: [
        atRiskSuppliers.value.filter((s: any) => s.risk_score < 20).length,
        atRiskSuppliers.value.filter((s: any) => s.risk_score >= 20 && s.risk_score < 50).length,
        atRiskSuppliers.value.filter((s: any) => s.risk_score >= 50 && s.risk_score < 75).length,
        atRiskSuppliers.value.filter((s: any) => s.risk_score >= 75).length
      ],
      backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#991b1b'],
      borderColor: ['#059669', '#d97706', '#dc2626', '#7f1d1d']
    }
  ]
}))

const qualityDistributionData = computed(() => ({
  labels: ['Excellent (4-5)', 'Good (3-4)', 'Average (2-3)', 'Below Avg (<2)'],
  datasets: [
    {
      label: 'Number of Suppliers',
      backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
      data: [0, 0, 0, 0]
    }
  ]
}))

const categoryBreakdownData = computed(() => ({
  labels: ['Raw Materials', 'Furniture', 'Accessories', 'Services'],
  datasets: [
    {
      label: 'Supplier Count',
      backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#a855f7'],
      data: [0, 0, 0, 0]
    }
  ]
}))

const loadDashboard = async () => {
  loading.value = true
  try {
    const response = await supplierService.getDashboard()
    if (response) {
      dashboardData.value = response
    }

    // Load at-risk suppliers
    await getAtRiskSuppliers()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load dashboard data',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const viewSupplier = (id: number) => {
  router.push({ name: 'supplier-detail', params: { id } })
}

onMounted(() => {
  loadDashboard()
})
</script>
