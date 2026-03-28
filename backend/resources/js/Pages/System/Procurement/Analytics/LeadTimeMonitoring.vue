<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Lead Time Monitoring</h2>
        <p class="text-sm text-gray-500 mt-1">Track delivery performance and lead time trends</p>
      </div>
      <Button
        icon="pi pi-refresh"
        rounded
        text
        @click="loadLeadTimeData"
        :loading="loading"
      />
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card>
        <template #content>
          <div class="text-center">
            <p class="text-gray-600 text-sm">Avg Lead Time</p>
            <p class="text-3xl font-bold text-blue-600 mt-2">{{ avgLeadTime }} days</p>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="text-center">
            <p class="text-gray-600 text-sm">On-Time Deliveries</p>
            <p class="text-3xl font-bold text-green-600 mt-2">{{ onTimePercentage }}%</p>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="text-center">
            <p class="text-gray-600 text-sm">Late Deliveries</p>
            <p class="text-3xl font-bold text-red-600 mt-2">{{ latePercentage }}%</p>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="text-center">
            <p class="text-gray-600 text-sm">Avg Days Late</p>
            <p class="text-3xl font-bold text-orange-600 mt-2">{{ avgDaysLate }} days</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- Lead Time Trend Chart -->
    <Card>
      <template #title>Lead Time Trend (Last 12 Months)</template>
      <template #content>
        <Chart
          type="line"
          :data="trendChartData"
          :options="trendChartOptions"
          class="w-full"
          style="height: 350px"
        />
      </template>
    </Card>

    <!-- Filters -->
    <Card>
      <template #content>
        <div class="flex gap-4 items-end">
          <div class="flex-1">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Filter by Supplier</label>
            <Select
              v-model="filterSupplier"
              :options="suppliers"
              optionLabel="supplier_name"
              optionValue="id"
              placeholder="All Suppliers"
              class="w-full"
              @change="applyFilters"
            />
          </div>
          <div class="flex-1">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Delivery Status</label>
            <Select
              v-model="filterStatus"
              :options="[
                { label: 'All', value: null },
                { label: 'On Time', value: 'on_time' },
                { label: 'Late', value: 'late' },
                { label: 'Early', value: 'early' },
              ]"
              optionLabel="label"
              optionValue="value"
              placeholder="All Status"
              class="w-full"
              @change="applyFilters"
            />
          </div>
          <Button
            icon="pi pi-filter-slash"
            label="Clear"
            severity="secondary"
            @click="clearFilters"
          />
        </div>
      </template>
    </Card>

    <!-- Supplier Lead Times -->
    <Card>
      <template #title>Supplier Lead Time Comparison</template>
      <template #content>
        <Chart
          type="bar"
          :data="supplierChartData"
          :options="supplierChartOptions"
          class="w-full"
          style="height: 350px"
        />
      </template>
    </Card>

    <!-- Deliveries Table -->
    <Card>
      <template #title>Recent Deliveries</template>
      <template #content>
        <DataTable
          :value="filteredDeliveries"
          :loading="loading"
          paginator
          :rows="10"
          responsiveLayout="scroll"
          class="p-datatable-sm"
        >
          <Column field="po_number" header="PO Number" style="width: 12%">
            <template #body="{ data }">
              <RouterLink
                :to="{ name: 'procurement.purchase-orders.detail', params: { id: data.po_id } }"
                class="text-blue-600 hover:underline"
              >
                {{ data.po_number }}
              </RouterLink>
            </template>
          </Column>

          <Column field="supplier_name" header="Supplier" style="width: 15%"></Column>

          <Column field="order_date" header="Order Date" style="width: 12%">
            <template #body="{ data }">
              {{ formatDate(data.order_date) }}
            </template>
          </Column>

          <Column field="expected_delivery_date" header="Expected Delivery" style="width: 12%">
            <template #body="{ data }">
              {{ formatDate(data.expected_delivery_date) }}
            </template>
          </Column>

          <Column field="actual_delivery_date" header="Actual Delivery" style="width: 12%">
            <template #body="{ data }">
              {{ data.actual_delivery_date ? formatDate(data.actual_delivery_date) : 'Pending' }}
            </template>
          </Column>

          <Column header="Lead Time" style="width: 10%">
            <template #body="{ data }">
              <span class="font-semibold">{{ data.lead_time_days }} days</span>
            </template>
          </Column>

          <Column header="Status" style="width: 12%">
            <template #body="{ data }">
              <Badge
                :value="data.delivery_status"
                :severity="
                  data.delivery_status === 'on_time'
                    ? 'success'
                    : data.delivery_status === 'late'
                      ? 'danger'
                      : 'info'
                "
              />
            </template>
          </Column>

          <Column header="Variance" style="width: 12%">
            <template #body="{ data }">
              <span
                :class="{
                  'text-green-600': data.variance_days <= 0,
                  'text-red-600': data.variance_days > 0,
                }"
              >
                {{ data.variance_days > 0 ? '+' : '' }}{{ data.variance_days }} days
              </span>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Lead Time Statistics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Distribution Chart -->
      <Card>
        <template #title>Lead Time Distribution</template>
        <template #content>
          <Chart
            type="doughnut"
            :data="distributionChartData"
            :options="distributionChartOptions"
            class="w-full"
            style="height: 300px"
          />
        </template>
      </Card>

      <!-- Performance by Range -->
      <Card>
        <template #title>Performance by Lead Time Range</template>
        <template #content>
          <DataTable :value="performanceRanges" class="p-datatable-sm">
            <Column field="range" header="Lead Time Range" />
            <Column field="count" header="Count" />
            <Column field="percentage" header="%" style="width: 20%">
              <template #body="{ data }">
                <ProgressBar :value="data.percentage" class="h-6"></ProgressBar>
              </template>
            </Column>
            <Column field="avg_days_late" header="Avg Days Late" />
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
const deliveries = ref<any[]>([])
const suppliers = ref<any[]>([])
const trendData = ref<any[]>([])
const filterSupplier = ref(null)
const filterStatus = ref(null)

// Computed
const avgLeadTime = computed(() => {
  if (deliveries.value.length === 0) return 0
  const avg = deliveries.value.reduce((sum, d) => sum + d.lead_time_days, 0) / deliveries.value.length
  return Math.round(avg)
})

const onTimePercentage = computed(() => {
  if (deliveries.value.length === 0) return 0
  const onTime = deliveries.value.filter(d => d.delivery_status === 'on_time').length
  return Math.round((onTime / deliveries.value.length) * 100)
})

const latePercentage = computed(() => {
  if (deliveries.value.length === 0) return 0
  const late = deliveries.value.filter(d => d.delivery_status === 'late').length
  return Math.round((late / deliveries.value.length) * 100)
})

const avgDaysLate = computed(() => {
  const lateDeliveries = deliveries.value.filter(d => d.variance_days > 0)
  if (lateDeliveries.length === 0) return 0
  const avg = lateDeliveries.reduce((sum, d) => sum + d.variance_days, 0) / lateDeliveries.length
  return Math.round(avg)
})

const filteredDeliveries = computed(() => {
  return deliveries.value.filter(d => {
    if (filterSupplier.value && d.supplier_id !== filterSupplier.value) return false
    if (filterStatus.value && d.delivery_status !== filterStatus.value) return false
    return true
  })
})

const performanceRanges = computed(() => {
  const ranges = [
    { range: '0-7 days', min: 0, max: 7 },
    { range: '8-14 days', min: 8, max: 14 },
    { range: '15-30 days', min: 15, max: 30 },
    { range: '31+ days', min: 31, max: 999 },
  ]

  return ranges.map(r => {
    const count = deliveries.value.filter(d => d.lead_time_days >= r.min && d.lead_time_days <= r.max).length
    const lateInRange = deliveries.value
      .filter(d => d.lead_time_days >= r.min && d.lead_time_days <= r.max && d.variance_days > 0)
      .reduce((sum, d) => sum + d.variance_days, 0)

    return {
      ...r,
      count,
      percentage: deliveries.value.length > 0 ? Math.round((count / deliveries.value.length) * 100) : 0,
      avg_days_late: count > 0 ? Math.round(lateInRange / count) : 0,
    }
  })
})

// Chart Data
const trendChartData = computed(() => ({
  labels: trendData.value.map(t => t.month),
  datasets: [
    {
      label: 'Avg Lead Time',
      data: trendData.value.map(t => t.avg_lead_time),
      borderColor: '#3b82f6',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      tension: 0.4,
      fill: true,
    },
    {
      label: 'Expected Lead Time',
      data: trendData.value.map(t => t.expected_lead_time),
      borderColor: '#10b981',
      backgroundColor: 'rgba(16, 185, 129, 0.1)',
      tension: 0.4,
      borderDash: [5, 5],
    },
  ],
}))

const supplierChartData = computed(() => {
  const topSuppliers = suppliers.value.slice(0, 10)
  return {
    labels: topSuppliers.map(s => s.supplier_name),
    datasets: [
      {
        label: 'Avg Lead Time',
        data: topSuppliers.map(s => s.avg_lead_time),
        backgroundColor: '#3b82f6',
      },
    ],
  }
})

const distributionChartData = computed(() => {
  const ranges = performanceRanges.value
  return {
    labels: ranges.map(r => r.range),
    datasets: [
      {
        data: ranges.map(r => r.count),
        backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
      },
    ],
  }
})

const trendChartOptions = {
  maintainAspectRatio: false,
  responsive: true,
  plugins: {
    legend: { display: true, position: 'bottom' },
  },
}

const supplierChartOptions = {
  maintainAspectRatio: false,
  responsive: true,
  indexAxis: 'y',
  plugins: {
    legend: { display: false },
  },
}

const distributionChartOptions = {
  maintainAspectRatio: false,
  responsive: true,
  plugins: {
    legend: { position: 'bottom' },
  },
}

// Methods
async function loadLeadTimeData() {
  loading.value = true
  try {
    const response = await procurementService.getLeadTimeMonitoring()
    
    deliveries.value = response.data?.deliveries || []
    suppliers.value = response.data?.suppliers || []
    trendData.value = response.data?.trend || []
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load lead time data',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  // Filters are applied via computed property
}

function clearFilters() {
  filterSupplier.value = null
  filterStatus.value = null
}

function formatDate(date: string): string {
  return date ? new Date(date).toLocaleDateString('en-PH') : 'N/A'
}

onMounted(() => {
  loadLeadTimeData()
})
</script>
