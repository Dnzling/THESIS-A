<template>
  <div class="supplier-dashboard-container p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-4">
        <Button
          icon="pi pi-arrow-left"
          class="p-button-text"
          @click="$router.back()"
        />
        <h1 class="text-3xl font-bold text-gray-800">Supplier Dashboard</h1>
      </div>
      <Button
        icon="pi pi-refresh"
        :loading="loading"
        @click="loadDashboardData"
      />
    </div>

    <!-- Summary Cards -->
    <Skeleton v-if="loading" width="100%" height="100px" class="mb-6" />
    <div v-else class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
      <div class="bg-blue-50 rounded-lg p-4 border-l-4 border-blue-500">
        <div class="text-sm text-gray-600 font-semibold mb-1">Total</div>
        <div class="text-3xl font-bold text-blue-600">{{ dashboardData.summary?.total_suppliers || 0 }}</div>
      </div>
      <div class="bg-green-50 rounded-lg p-4 border-l-4 border-green-500">
        <div class="text-sm text-gray-600 font-semibold mb-1">Active</div>
        <div class="text-3xl font-bold text-green-600">{{ dashboardData.summary?.active_suppliers || 0 }}</div>
      </div>
      <div class="bg-yellow-50 rounded-lg p-4 border-l-4 border-yellow-500">
        <div class="text-sm text-gray-600 font-semibold mb-1">Inactive</div>
        <div class="text-3xl font-bold text-yellow-600">{{ dashboardData.summary?.inactive_suppliers || 0 }}</div>
      </div>
      <div class="bg-red-50 rounded-lg p-4 border-l-4 border-red-500">
        <div class="text-sm text-gray-600 font-semibold mb-1">Blacklisted</div>
        <div class="text-3xl font-bold text-red-600">{{ dashboardData.summary?.blacklisted_suppliers || 0 }}</div>
      </div>
      <div class="bg-purple-50 rounded-lg p-4 border-l-4 border-purple-500">
        <div class="text-sm text-gray-600 font-semibold mb-1">At-Risk</div>
        <div class="text-3xl font-bold text-purple-600">{{ dashboardData.summary?.at_risk_count || 0 }}</div>
      </div>
    </div>

    <!-- Average Metrics -->
    <Skeleton v-if="loading" width="100%" height="120px" class="mb-6" />
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="text-sm font-medium text-gray-700 mb-3">Average Quality Score</div>
        <ProgressBar
          :value="(dashboardData.averages?.avg_quality_score || 0) / 5 * 100"
          :show-value="false"
          style="height: 0.5rem"
          class="mb-2"
        />
        <div class="text-lg font-bold text-gray-800">{{ dashboardData.averages?.avg_quality_score || 0 }}/5</div>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="text-sm font-medium text-gray-700 mb-3">Average Rating</div>
        <Rating
          :model-value="dashboardData.averages?.avg_rating || 0"
          :cancel="false"
          read-only
          class="mb-2"
        />
        <div class="text-lg font-bold text-gray-800">{{ dashboardData.averages?.avg_rating || 0 }}/5</div>
      </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
      <!-- Top Performers -->
      <div class="bg-white rounded-lg shadow-sm p-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Top 5 Performers</h3>
        <Skeleton v-if="loading" width="100%" height="300px" />
        <div v-else class="space-y-3">
          <div
            v-for="(supplier, idx) in dashboardData.top_performers"
            :key="supplier.id"
            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
          >
            <div class="flex items-center gap-3">
              <Badge :value="idx + 1" class="text-lg" severity="info" />
              <div>
                <div class="font-semibold text-gray-800">{{ supplier.supplier_name }}</div>
                <Rating :model-value="supplier.rating" :cancel="false" read-only :value="1" />
              </div>
            </div>
            <div class="text-right">
              <div class="text-sm text-gray-600">On-Time: {{ supplier.on_time_percentage }}%</div>
              <div class="text-sm font-semibold text-gray-800">Q: {{ supplier.quality_score }}/5</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Risk Distribution -->
      <ChartWrapper
        title="Risk Distribution"
        chart-type="doughnut"
        :chart-data="getRiskDistributionData()"
        :loading="loading"
      />
    </div>

    <!-- Charts Row 2 -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
      <!-- Quality Distribution -->
      <ChartWrapper
        title="Quality Distribution"
        chart-type="bar"
        :chart-data="getQualityDistributionData()"
        :loading="loading"
      />

      <!-- Category Breakdown -->
      <ChartWrapper
        title="Category Breakdown"
        chart-type="bar"
        :chart-data="getCategoryBreakdownData()"
        :loading="loading"
      />
    </div>

    <!-- At-Risk Suppliers Table -->
    <div class="bg-white rounded-lg shadow-sm">
      <div class="p-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">At-Risk Suppliers</h3>
      </div>

      <Skeleton v-if="loading" width="100%" height="300px" class="p-4" />
      <div v-else>
        <div v-if="atRiskSuppliers.length === 0" class="p-4 text-center text-green-700 bg-green-50">
          <i class="pi pi-check-circle text-2xl mb-2 block" />
          <p>No at-risk suppliers detected</p>
        </div>
        <DataTable v-else :value="atRiskSuppliers" responsive-layout="scroll" class="p-0">
          <Column field="supplier_name" header="Supplier" style="width: 25%">
            <template #body="{ data }">
              <div>
                <div class="font-semibold text-gray-800">{{ data.supplier_name }}</div>
                <div class="text-sm text-gray-500">{{ data.company_name }}</div>
              </div>
            </template>
          </Column>
          <Column field="risk_score" header="Risk Score" style="width: 15%">
            <template #body="{ data }">
              <RiskBadge :risk-score="data.risk_score" />
            </template>
          </Column>
          <Column header="Risk Factors" style="width: 45%">
            <template #body="{ data }">
              <div class="flex flex-wrap gap-1">
                <Tag
                  v-for="(reason, idx) in (data.risk_reasons || []).slice(0, 2)"
                  :key="idx"
                  :value="reason.split(':')[0]"
                  severity="warning"
                  class="text-xs"
                />
                <Tag
                  v-if="(data.risk_reasons || []).length > 2"
                  :value="`+${(data.risk_reasons || []).length - 2} more`"
                  severity="danger"
                  class="text-xs"
                />
              </div>
            </template>
          </Column>
          <Column header="Action" style="width: 15%">
            <template #body="{ data }">
              <Button
                icon="pi pi-external-link"
                class="p-button-rounded p-button-text"
                @click="viewSupplier(data)"
              />
            </template>
          </Column>
        </DataTable>
      </div>
    </div>

    <!-- Toast -->
    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import ChartWrapper from './ChartWrapper.vue'
import RiskBadge from './RiskBadge.vue'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import Rating from 'primevue/rating'
import ProgressBar from 'primevue/progressbar'
import Skeleton from 'primevue/skeleton'
import Toast from 'primevue/toast'

const router = useRouter()
const toast = useToast()

const loading = ref(true)
const dashboardData = ref<any>({
  summary: {},
  averages: {},
  top_performers: [],
  risk_distribution: {},
  quality_distribution: {},
  category_breakdown: {},
})
const atRiskSuppliers = ref<any[]>([])

const getRiskDistributionData = () => {
  const dist = dashboardData.value.risk_distribution || {}
  return {
    labels: ['Low', 'Medium', 'High', 'Critical'],
    datasets: [
      {
        data: [dist.low || 0, dist.medium || 0, dist.high || 0, dist.critical || 0],
        backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#7c2d12'],
      },
    ],
  }
}

const getQualityDistributionData = () => {
  const dist = dashboardData.value.quality_distribution || {}
  return {
    labels: ['Excellent', 'Good', 'Average', 'Below Avg'],
    datasets: [
      {
        label: 'Suppliers',
        data: [dist.excellent || 0, dist.good || 0, dist.average || 0, dist.below_average || 0],
        backgroundColor: '#10b981',
      },
    ],
  }
}

const getCategoryBreakdownData = () => {
  const breakdown = dashboardData.value.category_breakdown || {}
  return {
    labels: Object.keys(breakdown),
    datasets: [
      {
        label: 'Suppliers',
        data: Object.values(breakdown),
        backgroundColor: '#3b82f6',
      },
    ],
  }
}

const viewSupplier = (supplier: any) => {
  router.push(`/suppliers/${supplier.id}`)
}

const loadDashboardData = async () => {
  loading.value = true

  try {
    // TODO: Replace with actual API call
    dashboardData.value = {
      summary: {
        total_suppliers: 50,
        active_suppliers: 48,
        inactive_suppliers: 2,
        blacklisted_suppliers: 0,
        at_risk_count: 5,
      },
      averages: {
        avg_quality_score: 4.3,
        avg_rating: 4.5,
      },
      top_performers: [
        {
          id: 1,
          supplier_name: 'Acme Materials Inc',
          rating: 4.8,
          quality_score: 4.7,
          on_time_percentage: 98.5,
          rank: 1,
        },
        {
          id: 3,
          supplier_name: 'Quality Goods Ltd',
          rating: 4.7,
          quality_score: 4.6,
          on_time_percentage: 97.2,
          rank: 2,
        },
      ],
      risk_distribution: {
        low: 40,
        medium: 8,
        high: 2,
        critical: 0,
      },
      quality_distribution: {
        excellent: 25,
        good: 18,
        average: 6,
        below_average: 1,
      },
      category_breakdown: {
        'Raw Materials': 18,
        'Furniture': 15,
        'Accessories': 12,
        'Services': 4,
        'Equipment': 1,
      },
    }

    atRiskSuppliers.value = [
      {
        id: 5,
        supplier_name: 'Risky Supplies Inc',
        company_name: 'Risky Supplies',
        risk_score: 68,
        risk_reasons: ['Late delivery: 25%', 'Payment delays', 'Quality issues'],
      },
    ]
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load dashboard data',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadDashboardData()
})
</script>

<style scoped>
.supplier-dashboard-container {
  background-color: #f8f9fa;
}
</style>
