<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Inventory Reports</h1>
        <p class="text-gray-600 mt-1">Generate and analyze inventory metrics</p>
      </div>
      <div class="flex gap-2">
        <Button
          icon="pi pi-download"
          label="Export"
          severity="secondary"
          @click="exportReport"
          :disabled="selectedReport === null"
        />
        <Button
          icon="pi pi-refresh"
          label="Refresh"
          @click="loadSelectedReport"
        />
      </div>
    </div>

    <!-- Report Type Selector -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="report in reportTypes"
        :key="report.id"
        @click="selectedReport = report.id"
        :class="[
          'p-4 rounded-lg border-2 cursor-pointer transition-all',
          selectedReport === report.id
            ? 'border-blue-600 bg-blue-50'
            : 'border-gray-200 bg-white hover:border-gray-300'
        ]"
      >
        <div class="flex items-center gap-3">
          <div :class="['text-3xl', report.icon]"></div>
          <div>
            <h3 class="font-semibold text-gray-900">{{ report.name }}</h3>
            <p class="text-xs text-gray-600">{{ report.description }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Date Range Filters -->
    <Card v-if="selectedReport">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
            <Calendar v-model="filters.startDate" dateFormat="dd/mm/yy" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
            <Calendar v-model="filters.endDate" dateFormat="dd/mm/yy" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Group By</label>
            <Dropdown
              v-model="filters.groupBy"
              :options="groupByOptions"
              optionLabel="label"
              optionValue="value"
              class="w-full"
            />
          </div>
          <div class="flex items-end">
            <Button label="Generate Report" icon="pi pi-chart-bar" class="w-full" @click="loadSelectedReport" />
          </div>
        </div>
      </template>
    </Card>

    <!-- Report Content -->
    <div v-if="selectedReport && !loading" class="space-y-6">
      <!-- Summary Cards -->
      <div v-if="reportSummary" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card v-for="(item, index) in reportSummary" :key="index" class="hover:shadow-lg transition-shadow">
          <template #content>
            <p class="text-sm text-gray-600 mb-1">{{ item.label }}</p>
            <p class="text-3xl font-bold text-gray-900">{{ formatValue(item.value, item.type) }}</p>
            <p v-if="item.change" :class="['text-xs mt-1', item.change > 0 ? 'text-green-600' : 'text-red-600']">
              <i :class="['pi', item.change > 0 ? 'pi-arrow-up' : 'pi-arrow-down']"></i>
              {{ Math.abs(item.change) }}%
            </p>
          </template>
        </Card>
      </div>

      <!-- Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Bar/Line Chart -->
        <Card v-if="chartData">
          <template #title>{{ getReportTitle() }} Trend</template>
          <template #content>
            <Chart type="line" :data="chartData" :options="chartOptions" />
          </template>
        </Card>

        <!-- Pie/Doughnut Chart -->
        <Card v-if="pieChartData">
          <template #title>{{ getReportTitle() }} Distribution</template>
          <template #content>
            <Chart type="doughnut" :data="pieChartData" :options="chartOptions" />
          </template>
        </Card>
      </div>

      <!-- Data Table -->
      <Card>
        <template #title>{{ getReportTitle() }} Details</template>
        <template #content>
          <DataTable
            :value="reportData"
            class="p-datatable-sm"
            stripedRows
            responsiveLayout="scroll"
            :paginator="true"
            :rows="10"
          >
            <template #empty>
              <div class="text-center py-8">
                <i class="pi pi-inbox text-4xl text-gray-400 mb-2"></i>
                <p class="text-gray-600">No data available for this report</p>
              </div>
            </template>

            <!-- Dynamic Columns based on report type -->
            <Column
              v-for="column in reportColumns"
              :key="column.field"
              :field="column.field"
              :header="column.header"
              :sortable="column.sortable !== false"
              :style="{ width: column.width || '150px' }"
            >
              <template #body="{ data }">
                <span v-if="column.type === 'currency'">{{ formatCurrency(data[column.field]) }}</span>
                <span v-else-if="column.type === 'percent'">{{ data[column.field] }}%</span>
                <span v-else-if="column.type === 'status'">
                  <Tag :value="data[column.field]" :severity="getStatusSeverity(data[column.field])" />
                </span>
                <span v-else>{{ data[column.field] }}</span>
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-6">
      <Skeleton height="100px" v-for="i in 3" :key="i" class="rounded-lg" />
    </div>

    <!-- Empty State -->
    <div v-if="!selectedReport" class="text-center py-16">
      <i class="pi pi-chart-bar text-6xl text-gray-300 mb-4"></i>
      <p class="text-gray-600 text-lg">Select a report type to get started</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { Chart } from 'chart.js/auto'
import axiosClient from '../../axios'

type ReportType = 'branch_summary' | 'store_summary' | 'movements' | 'category' | 'slow_movers' | 'fast_movers' | 'transfers' | 'aging'

const selectedReport = ref<ReportType | null>(null)
const loading = ref(false)
const reportData = ref<any[]>([])
const reportSummary = ref<any[]>([])
const chartData = ref<any>(null)
const pieChartData = ref<any>(null)

const reportColumns = ref<any[]>([])
const reportTypes = [
  {
    id: 'branch_summary',
    name: 'Branch Summary',
    description: 'Key performance indicators by branch',
    icon: 'pi pi-chart-pie'
  },
  {
    id: 'store_summary',
    name: 'Store Overview',
    description: 'Overall inventory metrics',
    icon: 'pi pi-chart-line'
  },
  {
    id: 'movements',
    name: 'Stock Movement',
    description: 'Inbound and outbound trends',
    icon: 'pi pi-arrow-right-arrow-left'
  },
  {
    id: 'category',
    name: 'By Category',
    description: 'Inventory breakdown by category',
    icon: 'pi pi-sitemap'
  },
  {
    id: 'slow_movers',
    name: 'Slow Movers',
    description: 'Underperforming products',
    icon: 'pi pi-arrow-down'
  },
  {
    id: 'fast_movers',
    name: 'Fast Movers',
    description: 'Best selling products',
    icon: 'pi pi-arrow-up'
  },
  {
    id: 'transfers',
    name: 'Transfer Metrics',
    description: 'Inter-store transfer analysis',
    icon: 'pi pi-sitemap'
  },
  {
    id: 'aging',
    name: 'Stock Aging',
    description: 'Product age analysis',
    icon: 'pi pi-clock'
  }
]

const filters = reactive({
  startDate: new Date(new Date().setDate(new Date().getDate() - 30)),
  endDate: new Date(),
  groupBy: 'daily'
})

const groupByOptions = [
  { label: 'Daily', value: 'daily' },
  { label: 'Weekly', value: 'weekly' },
  { label: 'Monthly', value: 'monthly' },
  { label: 'Category', value: 'category' },
  { label: 'Branch', value: 'branch' }
]

const chartOptions = {
  maintainAspectRatio: false,
  responsive: true,
  plugins: {
    legend: {
      position: 'bottom'
    }
  }
}

const getReportTitle = (): string => {
  const report = reportTypes.find(r => r.id === selectedReport.value)
  return report?.name || 'Report'
}

const formatValue = (value: any, type?: string): string => {
  if (type === 'currency') return formatCurrency(value)
  if (type === 'percent') return `${value}%`
  if (!isNaN(value)) return Math.round(value).toLocaleString()
  return String(value)
}

const formatCurrency = (value: any): string => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(value)
}

const getStatusSeverity = (status: string): string => {
  const severities: { [key: string]: string } = {
    'in_stock': 'success',
    'low_stock': 'warning',
    'out_of_stock': 'danger',
    'slow_moving': 'warning',
    'fast_moving': 'success'
  }
  return severities[status] || 'info'
}

const loadSelectedReport = async () => {
  if (!selectedReport.value) return

  loading.value = true
  try {
    const endpoint = `/api/inventory/reports/${selectedReport.value}`
    const response = await axiosClient.get(endpoint, {
      params: {
        from_date: filters.startDate?.toISOString(),
        to_date: filters.endDate?.toISOString(),
        group_by: filters.groupBy
      }
    })

    const data = response.data.data

    // Set summary cards
    reportSummary.value = data.summary || []

    // Set table data
    reportData.value = data.items || data.details || []

    // Set columns based on report type
    setReportColumns()

    // Generate charts
    generateCharts(data)
  } catch (error) {
    console.error('Failed to load report:', error)
  } finally {
    loading.value = false
  }
}

const setReportColumns = () => {
  const columnMap: { [key in ReportType]: any[] } = {
    branch_summary: [
      { field: 'branch_name', header: 'Branch', width: '200px' },
      { field: 'total_items', header: 'Total Items', type: 'number' },
      { field: 'total_value', header: 'Inventory Value', type: 'currency' },
      { field: 'low_stock_count', header: 'Low Stock', type: 'number' },
      { field: 'status', header: 'Status', type: 'status' }
    ],
    store_summary: [
      { field: 'metric', header: 'Metric', width: '250px' },
      { field: 'value', header: 'Value', type: 'number' },
      { field: 'change', header: 'Change', type: 'percent' },
      { field: 'target', header: 'Target', type: 'number' }
    ],
    movements: [
      { field: 'date', header: 'Date', width: '120px' },
      { field: 'inbound', header: 'Inbound', type: 'number' },
      { field: 'outbound', header: 'Outbound', type: 'number' },
      { field: 'net', header: 'Net', type: 'number' }
    ],
    category: [
      { field: 'category_name', header: 'Category', width: '200px' },
      { field: 'product_count', header: 'Products', type: 'number' },
      { field: 'inventory_value', header: 'Value', type: 'currency' },
      { field: 'percentage', header: '% of Total', type: 'percent' }
    ],
    slow_movers: [
      { field: 'sku', header: 'SKU', width: '100px' },
      { field: 'product_name', header: 'Product', width: '200px' },
      { field: 'quantity_on_hand', header: 'Current Stock', type: 'number' },
      { field: 'units_sold', header: '90-Day Sales', type: 'number' },
      { field: 'days_in_stock', header: 'Days In Stock', type: 'number' }
    ],
    fast_movers: [
      { field: 'sku', header: 'SKU', width: '100px' },
      { field: 'product_name', header: 'Product', width: '200px' },
      { field: 'quantity_on_hand', header: 'Current Stock', type: 'number' },
      { field: 'units_sold', header: '90-Day Sales', type: 'number' },
      { field: 'sales_velocity', header: 'Velocity', type: 'number' }
    ],
    transfers: [
      { field: 'transfer_no', header: 'Transfer #', width: '120px' },
      { field: 'from_branch', header: 'From', width: '150px' },
      { field: 'to_branch', header: 'To', width: '150px' },
      { field: 'item_count', header: 'Items', type: 'number' },
      { field: 'status', header: 'Status', type: 'status' }
    ],
    aging: [
      { field: 'sku', header: 'SKU', width: '100px' },
      { field: 'product_name', header: 'Product', width: '200px' },
      { field: 'received_date', header: 'Received', width: '120px' },
      { field: 'days_in_inventory', header: 'Days In Inventory', type: 'number' },
      { field: 'inventory_value', header: 'Value', type: 'currency' }
    ]
  }

  reportColumns.value = columnMap[selectedReport.value as ReportType] || []
}

const generateCharts = (data: any) => {
  // Generate line/bar chart
  if (data.chart_data) {
    chartData.value = {
      labels: data.chart_data.labels || [],
      datasets: data.chart_data.datasets || []
    }
  }

  // Generate pie/doughnut chart
  if (data.distribution_data) {
    pieChartData.value = {
      labels: data.distribution_data.labels || [],
      datasets: [
        {
          data: data.distribution_data.values || [],
          backgroundColor: [
            '#3b82f6',
            '#ef4444',
            '#10b981',
            '#f59e0b',
            '#8b5cf6',
            '#ec4899',
            '#06b6d4',
            '#84cc16'
          ]
        }
      ]
    }
  }
}

const exportReport = async () => {
  if (!selectedReport.value) return

  try {
    const endpoint = `/api/inventory/reports/${selectedReport.value}/export`
    const response = await axiosClient.get(endpoint, {
      params: {
        from_date: filters.startDate?.toISOString(),
        to_date: filters.endDate?.toISOString(),
        export_format: 'csv'
      },
      responseType: 'blob'
    })

    // Create download link
    const url = window.URL.createObjectURL(response.data)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `${selectedReport.value}_${new Date().toISOString().split('T')[0]}.csv`)
    document.body.appendChild(link)
    link.click()
    link.parentElement?.removeChild(link)
  } catch (error) {
    console.error('Failed to export report:', error)
  }
}
</script>

<style scoped>
:deep(.p-chart) {
  position: relative;
  height: 300px;
}
</style>
