<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="router.back()" />
        <div>
          <h2 class="text-2xl font-bold text-gray-800">{{ supplier?.supplier_name }}</h2>
          <p class="text-sm text-gray-500 mt-1">{{ supplier?.company_name }}</p>
        </div>
      </div>
      <div class="flex gap-2">
        <Button
          icon="pi pi-pencil"
          label="Edit"
          severity="warning"
          @click="showEditDialog = true"
        />
        <Button
          icon="pi pi-trash"
          label="Delete"
          severity="danger"
          @click="confirmDelete"
        />
      </div>
    </div>

    <!-- Quick Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card>
        <template #content>
          <div class="text-center">
            <Tag :value="supplier?.status" :severity="getStatusColor(supplier?.status)" class="mb-2" />
            <p class="text-xs text-gray-500">Status</p>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="text-center">
            <Rating v-model="supplier.rating" :cancel="false" :readonly="true" class="justify-center mb-2" />
            <p class="text-xs text-gray-500">Rating</p>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="text-center">
            <div class="text-2xl font-bold text-blue-600">{{ supplier?.quality_score }}/5</div>
            <p class="text-xs text-gray-500">Quality Score</p>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="text-center">
            <div class="text-2xl font-bold text-purple-600">{{ onTimePercentage }}%</div>
            <p class="text-xs text-gray-500">On-Time Rate</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- Tabs -->
    <TabView>
      <!-- Tab 1: Overview -->
      <TabPanel header="Overview" class="p-0">
        <div class="p-6 space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Contact Information -->
            <Card>
              <template #header>
                <div class="flex items-center gap-2">
                  <i class="pi pi-phone"></i>
                  <span>Contact Information</span>
                </div>
              </template>
              <template #content>
                <div class="space-y-3">
                  <div>
                    <p class="text-xs text-gray-500">Contact Person</p>
                    <p class="font-semibold">{{ supplier?.contact_person }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">Email</p>
                    <p class="font-semibold">{{ supplier?.email }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">Phone</p>
                    <p class="font-semibold">{{ supplier?.phone }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">Address</p>
                    <p class="font-semibold">{{ supplier?.address }}, {{ supplier?.city }}, {{ supplier?.state }}</p>
                  </div>
                </div>
              </template>
            </Card>

            <!-- Business Information -->
            <Card>
              <template #header>
                <div class="flex items-center gap-2">
                  <i class="pi pi-building"></i>
                  <span>Business Information</span>
                </div>
              </template>
              <template #content>
                <div class="space-y-3">
                  <div>
                    <p class="text-xs text-gray-500">Category</p>
                    <Tag :value="supplier?.category" :severity="getCategoryColor(supplier?.category)" class="capitalize" />
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">Tax ID</p>
                    <p class="font-semibold">{{ supplier?.tax_id || 'N/A' }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">Payment Terms</p>
                    <p class="font-semibold">{{ supplier?.payment_terms }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">Total Orders</p>
                    <p class="font-semibold">{{ supplier?.total_orders }}</p>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>
      </TabPanel>

      <!-- Tab 2: Performance -->
      <TabPanel header="Performance" :disabled="loadingPerformance" class="p-0">
        <div class="p-6 space-y-6" v-if="!loadingPerformance">
          <!-- Performance Metrics -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Card>
              <template #content>
                <div class="text-center">
                  <div class="text-3xl font-bold text-green-600">{{ performanceMetrics?.on_time_percentage }}%</div>
                  <p class="text-sm text-gray-600 mt-2">On-Time Deliveries</p>
                </div>
              </template>
            </Card>

            <Card>
              <template #content>
                <div class="text-center">
                  <div class="text-3xl font-bold text-orange-600">{{ performanceMetrics?.average_delivery_days }} d</div>
                  <p class="text-sm text-gray-600 mt-2">Avg Delivery Days</p>
                </div>
              </template>
            </Card>

            <Card>
              <template #content>
                <div class="text-center">
                  <div class="text-3xl font-bold" :class="getRiskColor(performanceMetrics?.risk_score)">
                    {{ performanceMetrics?.risk_score }}/100
                  </div>
                  <p class="text-sm text-gray-600 mt-2">Risk Score</p>
                </div>
              </template>
            </Card>
          </div>

          <!-- Charts -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <Card>
              <template #header>
                <div class="flex items-center gap-2">
                  <i class="pi pi-chart-line"></i>
                  <span>Delivery Trend</span>
                </div>
              </template>
              <template #content>
                <Chart type="line" :data="deliveryChartData" :options="chartOptions" class="w-full" />
              </template>
            </Card>

            <Card>
              <template #header>
                <div class="flex items-center gap-2">
                  <i class="pi pi-star"></i>
                  <span>Quality Trend</span>
                </div>
              </template>
              <template #content>
                <Chart type="line" :data="qualityChartData" :options="chartOptions" class="w-full" />
              </template>
            </Card>
          </div>
        </div>
        <div v-else class="p-6 text-center">
          <ProgressSpinner />
        </div>
      </TabPanel>

      <!-- Tab 3: Payments -->
      <TabPanel header="Payments" :disabled="loadingPayments" class="p-0">
        <div class="p-6 space-y-6" v-if="!loadingPayments">
          <!-- Payment Status Cards -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <Card class="border-l-4 border-green-500">
              <template #content>
                <div class="text-center">
                  <div class="text-2xl font-bold text-green-600">{{ paymentMetrics.totalPaid }}</div>
                  <p class="text-sm text-gray-600 mt-1">Total Paid</p>
                </div>
              </template>
            </Card>

            <Card class="border-l-4 border-blue-500">
              <template #content>
                <div class="text-center">
                  <div class="text-2xl font-bold text-blue-600">{{ paymentMetrics.totalDue }}</div>
                  <p class="text-sm text-gray-600 mt-1">Total Due</p>
                </div>
              </template>
            </Card>

            <Card class="border-l-4 border-red-500">
              <template #content>
                <div class="text-center">
                  <div class="text-2xl font-bold text-red-600">{{ paymentMetrics.overdueCount }}</div>
                  <p class="text-sm text-gray-600 mt-1">Overdue Invoices</p>
                </div>
              </template>
            </Card>

            <Card class="border-l-4 border-yellow-500">
              <template #content>
                <div class="text-center">
                  <div class="text-2xl font-bold text-yellow-600">{{ paymentMetrics.overdueAmount }}</div>
                  <p class="text-sm text-gray-600 mt-1">Overdue Amount</p>
                </div>
              </template>
            </Card>
          </div>

          <!-- Aging Chart -->
          <Card>
            <template #header>
              <div class="flex items-center gap-2">
                <i class="pi pi-chart-bar"></i>
                <span>Payment Aging Distribution</span>
              </div>
            </template>
            <template #content>
              <Chart type="bar" :data="agingChartData" :options="chartOptions" class="w-full" style="height: 300px" />
            </template>
          </Card>

          <!-- Payment History Button -->
          <Button
            icon="pi pi-history"
            label="View Payment History"
            @click="showPaymentHistory = true"
            class="w-full"
          />
        </div>
        <div v-else class="p-6 text-center">
          <ProgressSpinner />
        </div>
      </TabPanel>

      <!-- Tab 4: Category & Capabilities -->
      <TabPanel header="Category & Capabilities" class="p-0">
        <div class="p-6 space-y-4">
          <Card>
            <template #header>
              <div class="flex items-center gap-2">
                <i class="pi pi-tag"></i>
                <span>Supplier Category</span>
              </div>
            </template>
            <template #content>
              <div class="text-center">
                <Tag :value="supplier?.category" :severity="getCategoryColor(supplier?.category)" class="capitalize text-lg" />
                <p class="text-sm text-gray-600 mt-4">
                  This supplier specializes in {{ supplier?.category?.replace('_', ' ').toLowerCase() }}
                </p>
              </div>
            </template>
          </Card>

          <Button
            icon="pi pi-list"
            label="View Alternative Suppliers"
            @click="viewAlternatives"
            severity="info"
            class="w-full"
          />
        </div>
      </TabPanel>

      <!-- Tab 5: Risk Assessment -->
      <TabPanel header="Risk Assessment" :disabled="loadingPerformance" class="p-0">
        <div class="p-6 space-y-6" v-if="!loadingPerformance">
          <!-- Risk Score -->
          <Card>
            <template #header>
              <div class="flex items-center gap-2">
                <i class="pi pi-exclamation-triangle"></i>
                <span>Risk Score</span>
              </div>
            </template>
            <template #content>
              <div class="text-center">
                <div class="text-5xl font-bold mb-2" :class="getRiskColor(performanceMetrics?.risk_score)">
                  {{ performanceMetrics?.risk_score }}
                </div>
                <Tag
                  :value="getRiskLevel(performanceMetrics?.risk_score)"
                  :severity="getRiskSeverity(performanceMetrics?.risk_score)"
                  class="text-lg"
                />
              </div>
            </template>
          </Card>

          <!-- Risk Factors -->
          <Card v-if="riskReasons.length > 0">
            <template #header>
              <div class="flex items-center gap-2">
                <i class="pi pi-info-circle"></i>
                <span>Risk Factors</span>
              </div>
            </template>
            <template #content>
              <Message
                v-for="(reason, index) in riskReasons"
                :key="index"
                severity="warning"
                :text="reason"
                class="mb-2"
              />
            </template>
          </Card>

          <Card v-else>
            <template #content>
              <Message severity="success" text="No risk factors identified. This is a reliable supplier." />
            </template>
          </Card>
        </div>
        <div v-else class="p-6 text-center">
          <ProgressSpinner />
        </div>
      </TabPanel>

      <!-- Tab 6: Order History -->
      <TabPanel header="Order History" class="p-0">
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <Card>
              <template #content>
                <div class="text-center">
                  <div class="text-3xl font-bold text-blue-600">{{ supplier?.total_orders }}</div>
                  <p class="text-sm text-gray-600 mt-2">Total POs</p>
                </div>
              </template>
            </Card>

            <Card>
              <template #content>
                <div class="text-center">
                  <div class="text-3xl font-bold text-green-600">{{ supplier?.on_time_deliveries }}</div>
                  <p class="text-sm text-gray-600 mt-2">On-Time Deliveries</p>
                </div>
              </template>
            </Card>

            <Card>
              <template #content>
                <div class="text-center">
                  <div class="text-3xl font-bold text-red-600">{{ supplier?.late_deliveries }}</div>
                  <p class="text-sm text-gray-600 mt-2">Late Deliveries</p>
                </div>
              </template>
            </Card>
          </div>

          <Button
            icon="pi pi-list"
            label="View All Purchase Orders"
            @click="viewPurchaseOrders"
            class="w-full"
          />
        </div>
      </TabPanel>
    </TabView>

    <!-- Edit Supplier Dialog -->
    <Dialog v-model:visible="showEditDialog" header="Edit Supplier" :modal="true" class="w-full md:w-3/4">
      <SupplierForm
        :initial-data="supplier"
        mode="edit"
        @save="onSupplierUpdate"
        @close="showEditDialog = false"
      />
    </Dialog>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import SupplierForm from './SupplierForm.vue'
import supplierService from '../../../services/supplier.service'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const supplierId = ref(route.params.id as unknown as number)

const supplier = ref<any>(null)
const loading = ref(false)
const loadingPerformance = ref(false)
const loadingPayments = ref(false)
const showEditDialog = ref(false)
const showPaymentHistory = ref(false)

const performanceMetrics = ref<any>(null)
const performanceHistory = ref<any[]>([])
const paymentStatus = ref<any>(null)
const agingReport = ref<any>(null)

const riskReasons = ref<string[]>([])

const onTimePercentage = computed(() => {
  if (!supplier.value?.total_orders) return 0
  return Math.round((supplier.value.on_time_deliveries / supplier.value.total_orders) * 100)
})

const paymentMetrics = computed(() => ({
  totalDue: paymentStatus.value?.total_due || 0,
  totalPaid: paymentStatus.value?.total_paid || 0,
  overdueAmount: paymentStatus.value?.overdue_amount || 0,
  overdueCount: paymentStatus.value?.overdue_count || 0,
  upcomingCount: paymentStatus.value?.upcoming_count || 0
}))

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

const deliveryChartData = computed(() => ({
  labels: performanceHistory.value?.map((h: any) => new Date(h.metric_date).toLocaleDateString()) || [],
  datasets: [
    {
      label: 'On-Time',
      borderColor: '#10b981',
      backgroundColor: 'rgba(16, 185, 129, 0.1)',
      data: performanceHistory.value?.map((h: any) => h.on_time_count) || []
    },
    {
      label: 'Late',
      borderColor: '#ef4444',
      backgroundColor: 'rgba(239, 68, 68, 0.1)',
      data: performanceHistory.value?.map((h: any) => h.late_count) || []
    }
  ]
}))

const qualityChartData = computed(() => ({
  labels: performanceHistory.value?.map((h: any) => new Date(h.metric_date).toLocaleDateString()) || [],
  datasets: [
    {
      label: 'Quality Score',
      borderColor: '#3b82f6',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      data: performanceHistory.value?.map((h: any) => h.quality_score) || []
    }
  ]
}))

const agingChartData = computed(() => {
  if (!agingReport.value?.data?.aging) {
    return {
      labels: ['Current', '1-30', '31-60', '61-90', '90+'],
      datasets: [
        {
          label: 'Invoices',
          backgroundColor: ['#10b981', '#fbbf24', '#f97316', '#ef4444', '#dc2626'],
          data: [0, 0, 0, 0, 0]
        }
      ]
    }
  }

  const aging = agingReport.value.data.aging
  return {
    labels: ['Current', '1-30', '31-60', '61-90', '90+'],
    datasets: [
      {
        label: 'Invoices',
        backgroundColor: ['#10b981', '#fbbf24', '#f97316', '#ef4444', '#dc2626'],
        data: [aging.current, aging.days_30, aging.days_60, aging.days_90, aging.days_90_plus]
      }
    ]
  }
})

const getRiskReasons = (supplierData: any): string[] => {
  const reasons: string[] = []

  if (supplierData.late_deliveries && supplierData.total_orders) {
    const latePercentage = (supplierData.late_deliveries / supplierData.total_orders) * 100
    if (latePercentage > 20) {
      reasons.push(`High late delivery rate: ${latePercentage.toFixed(1)}%`)
    }
  }

  if (supplierData.recent_delay_percentage > 10) {
    reasons.push(`Recent payment delays: ${supplierData.recent_delay_percentage}%`)
  }

  if (supplierData.quality_score < 3) {
    reasons.push(`Low quality score: ${supplierData.quality_score.toFixed(1)}`)
  }

  if (supplierData.rating < 2) {
    reasons.push(`Low supplier rating: ${supplierData.rating}`)
  }

  if (supplierData.status === 'blacklisted') {
    reasons.push('Supplier is blacklisted')
  }

  return reasons
}

const getPerformanceMetrics = async (id: number) => {
  try {
    const response = await supplierService.getPerformanceMetrics(id)
    performanceMetrics.value = response
    return response
  } catch {
    return null
  }
}

const getPerformanceHistory = async (id: number) => {
  try {
    const response = await supplierService.getPerformanceHistory(id)
    performanceHistory.value = response.data || []
    return response
  } catch {
    return null
  }
}

const getPaymentStatus = async (id: number) => {
  try {
    const response = await supplierService.getPaymentStatus(id)
    paymentStatus.value = response.data
    return response
  } catch {
    return null
  }
}

const getAgingReport = async (id: number) => {
  try {
    const response = await supplierService.getAgingReport(id)
    agingReport.value = response.data
    return response
  } catch {
    return null
  }
}

const getStatusColor = (status: string): string => {
  const colors: Record<string, string> = {
    active: 'success',
    inactive: 'warning',
    blacklisted: 'danger'
  }
  return colors[status] || 'info'
}

const getCategoryColor = (category: string): string => {
  const colors: Record<string, string> = {
    raw_materials: 'info',
    furniture: 'success',
    accessories: 'warning',
    services: 'help'
  }
  return colors[category] || 'secondary'
}

const getRiskColor = (score: number): string => {
  if (score < 20) return 'text-green-600'
  if (score < 50) return 'text-yellow-600'
  if (score < 75) return 'text-orange-600'
  return 'text-red-600'
}

const getRiskLevel = (score: number): string => {
  if (score < 20) return 'Low Risk'
  if (score < 50) return 'Medium Risk'
  if (score < 75) return 'High Risk'
  return 'Critical Risk'
}

const getRiskSeverity = (score: number): string => {
  if (score < 20) return 'success'
  if (score < 50) return 'warning'
  if (score < 75) return 'danger'
  return 'danger'
}

const loadSupplierDetails = async () => {
  loading.value = true
  try {
    const response = await supplierService.getSuppliersByCategory(supplierId.value)
    supplier.value = response[0]
    
    // Load performance data
    loadingPerformance.value = true
    await getPerformanceMetrics(supplierId.value as number)
    await getPerformanceHistory(supplierId.value as number)
    await getPaymentStatus(supplierId.value as number)
    await getAgingReport(supplierId.value as number)
    
    if (supplier.value) {
      riskReasons.value = getRiskReasons(supplier.value)
    }
    loadingPerformance.value = false
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load supplier details',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const onSupplierUpdate = () => {
  showEditDialog.value = false
  loadSupplierDetails()
}

const confirmDelete = () => {
  toast.add({
    severity: 'warn',
    summary: 'Confirm',
    detail: 'Delete supplier?'
  })
}

const viewAlternatives = () => {
  toast.add({
    severity: 'info',
    summary: 'Feature Coming Soon',
    detail: 'View alternative suppliers feature',
    life: 3000
  })
}

const viewPurchaseOrders = () => {
  toast.add({
    severity: 'info',
    summary: 'Feature Coming Soon',
    detail: 'View purchase orders feature',
    life: 3000
  })
}

onMounted(() => {
  loadSupplierDetails()
})
</script>
