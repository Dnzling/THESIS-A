<template>
  <div class="space-y-4 text-xs">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h1 class="text-lg font-semibold text-gray-800">Inventory Reports</h1>
      </div>
      <div class="flex gap-2">
        <Button
          label="Activity Log"
          icon="pi pi-history"
          severity="secondary"
          size="small"
          @click="router.push({ name: 'inventory.activity-logs' })"
        />
        <Button
          icon="pi pi-download"
          label="Export"
          severity="secondary"
          size="small"
          @click="exportReport"
          :disabled="selectedReport === null || selectedReport === 'activity_logs'"
        />
        <Button
          icon="pi pi-refresh"
          label="Refresh"
          size="small"
          @click="loadSelectedReport"
        />
      </div>
    </div>

    <Card>
      <template #content>
        <!-- Report Type Selector -->
        <Tabs v-model:value="selectedReport" class="inventory-report-tabs">
          <TabList>
            <Tab v-for="report in reportTypes" :key="report.id" :value="report.id" class="text-xs">
              {{ report.name }}
            </Tab>
          </TabList>
        </Tabs>

    <!-- Date Range Filters -->
    <div v-if="selectedReport" class="mt-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div class="md:col-span-2">
            <label class="block text-xs font-medium text-gray-700 mb-1">Report Period</label>
            <DatePicker
              v-model="dateRange"
              selectionMode="range"
              dateFormat="dd/mm/yy"
              fluid
              class=""
              size="small"
              placeholder="Select date range"
              :manualInput="false"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Group By</label>
            <Select
              v-model="filters.groupBy"
              :options="groupByOptions"
              optionLabel="label"
              optionValue="value"
              class="w-full text-xs"
              size="small"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Product Type</label>
            <Select
              v-model="filters.productType"
              :options="productTypeOptions"
              optionLabel="label"
              optionValue="value"
              class="w-full text-xs"
              size="small"
              showClear
              placeholder="All Types"
            />
          </div>
          <div class="flex items-end">
            <Button label="Generate Report" size="small" class="w-full text-xs" @click="loadSelectedReport" />
          </div>
        </div>
    </div>

    <!-- Report Content -->
    <div v-if="selectedReport && !loading" class="mt-4 space-y-4">
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
            class="p-datatable-sm text-xs"
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
                <span v-else-if="column.type === 'datetime'">{{ formatDateTime(data[column.field]) }}</span>
                <span v-else-if="column.type === 'status'">
                  <Tag :value="data[column.field]" :severity="getStatusSeverity(data[column.field])" />
                </span>
                <span v-else>{{ data[column.field] }}</span>
              </template>
            </Column>
            <Column v-if="selectedReport === 'aging'" header="Actions" style="width: 100px">
              <template #body="{ data }">
                <Button label="View Items" size="small" severity="warn" text class="text-xs"
                  @click="openAgingItems(data)" />
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>
    </div>

        <div v-if="loading" class="mt-4 space-y-4">
          <Skeleton height="80px" v-for="i in 3" :key="i" class="rounded-lg" />
        </div>

        <div v-if="!selectedReport" class="py-10 text-center">
          <i class="pi pi-chart-bar text-4xl text-gray-300 mb-3"></i>
          <p class="text-xs text-gray-600">Select a report type to get started</p>
        </div>
      </template>
    </Card>

    <Dialog v-model:visible="agingDialogVisible" modal header="Stock Aging Items" :style="{ width: 'min(900px, 95vw)' }">
      <DataTable :value="agingDialogItems" class="p-datatable-sm text-xs" stripedRows paginator :rows="10">
        <template #empty>
          <div class="py-8 text-center text-xs text-gray-500">
            <i class="pi pi-inbox mb-2 text-2xl text-gray-300"></i>
            <p>No items found in this aging bucket.</p>
          </div>
        </template>
        <Column field="sku" header="SKU" />
        <Column field="product_name" header="Product" />
        <Column field="product_type" header="Type">
          <template #body="{ data }">{{ getProductTypeLabel(data.product_type) }}</template>
        </Column>
        <Column field="quantity_on_hand" header="Quantity" />
        <Column field="cost_price" header="Cost/Unit">
          <template #body="{ data }">{{ formatPhpCurrency(data.cost_price) }}</template>
        </Column>
        <Column field="last_stock_count_date" header="Last Count">
          <template #body="{ data }">{{ formatDateTime(data.last_stock_count_date) }}</template>
        </Column>
        <Column field="age_days" header="Age (Days)" />
      </DataTable>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { useRouter } from 'vue-router'
import 'chart.js/auto'
import axiosClient from '../../axios'
import { useToast } from 'primevue/usetoast'
import Tab from 'primevue/tab'
import TabList from 'primevue/tablist'
import Tabs from 'primevue/tabs'

type ReportType = 'slow_movers' | 'fast_movers' | 'aging' | 'transactions'

const selectedReport = ref<ReportType | null>('slow_movers')
const router = useRouter()
const loading = ref(false)
const reportData = ref<any[]>([])
const agingDetails = ref<Record<string, any[]>>({})
const agingDialogItems = ref<any[]>([])
const agingDialogVisible = ref(false)
const reportSummary = ref<any[]>([])
const chartData = ref<any>(null)
const pieChartData = ref<any>(null)
const toast = useToast()

const reportColumns = ref<any[]>([])
const reportTypes = [
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
    id: 'aging',
    name: 'Stock Aging',
    description: 'Product age analysis',
    icon: 'pi pi-clock'
  },
  {
    id: 'transactions',
    name: 'Transactions',
    description: 'Inventory movement history',
    icon: 'pi pi-list'
  }
]

const filters = reactive({
  groupBy: 'daily',
  productType: null as string | null
})

const dateRange = ref<Date[]>([
  new Date(new Date().setDate(new Date().getDate() - 30)),
  new Date()
])

const groupByOptions = [
  { label: 'Daily', value: 'daily' },
  { label: 'Weekly', value: 'weekly' },
  { label: 'Monthly', value: 'monthly' },
  { label: 'Category', value: 'category' },
  { label: 'Branch', value: 'branch' }
]
const productTypeOptions = [
  { label: 'Finished Good', value: 'finished_good' },
  { label: 'Raw Material', value: 'raw_material' }
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

const getProductTypeLabel = (type?: string) => {
  const labels: Record<string, string> = {
    finished_good: 'Finished Good',
    raw_material: 'Raw Material',
    supply: 'Supply'
  }
  return labels[String(type || '').toLowerCase()] || 'Product'
}

const formatPhpCurrency = (value: any) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(Number(value || 0))
}

const openAgingItems = (bucket: any) => {
  const keys: Record<string, string> = {
    '< 30 days': 'less_than_30_days',
    '30-60 days': 'between_30_60_days',
    '60-90 days': 'between_60_90_days',
    '> 90 days': 'older_than_90_days',
    'Never counted': 'never_counted'
  }
  agingDialogItems.value = agingDetails.value[keys[bucket.age_bucket]] || []
  agingDialogVisible.value = true
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

const normalizeReportData = (reportType: ReportType, raw: any) => {
  switch (reportType) {
    case 'transactions': {
      const source = Array.isArray(raw) ? raw : (raw?.data || [])
      const rows = source.map((x: any) => ({
        transaction_number: x?.transaction_number || x?.reference_no || '-',
        transaction_type: x?.transaction_type || '-',
        product_name: x?.product?.product_name || x?.product_name || '-',
        quantity_change: Number(x?.quantity_change || 0),
        transaction_date: x?.transaction_date || x?.created_at || null,
      }))
      return {
        summary: [{ label: 'Transactions', value: rows.length, type: 'number' }],
        items: rows,
        details: raw?.details || {},
      }
    }

    case 'slow_movers': {
      const rows = Array.isArray(raw) ? raw.map((x: any) => ({
        sku: x?.product?.sku || x?.sku || '-',
        product_name: x?.product?.product_name || x?.product_name || '-',
        quantity_on_hand: Number(x?.quantity_available ?? x?.quantity_on_hand ?? 0),
        units_sold: 0,
        days_in_stock: 0,
      })) : []
      return {
        summary: [
          { label: 'Slow Movers', value: rows.length, type: 'number' },
        ],
        items: rows,
        details: raw?.details || {},
      }
    }

    case 'fast_movers': {
      const rows = Array.isArray(raw) ? raw.map((x: any) => ({
        sku: x?.sku || '-',
        product_name: x?.product_name || '-',
        quantity_on_hand: Number(x?.current_stock || 0),
        units_sold: Number(x?.units_sold || 0),
        sales_velocity: Number(x?.days_until_stockout || 0),
      })) : []
      return {
        summary: [
          { label: 'Fast Movers', value: rows.length, type: 'number' },
          { label: 'Units Sold', value: rows.reduce((s: number, r: any) => s + Number(r.units_sold || 0), 0), type: 'number' },
        ],
        items: rows,
        details: raw?.details || {},
      }
    }

    case 'aging': {
      const rows = [
        { age_bucket: '< 30 days', days_in_inventory: 30, item_count: Number(raw?.less_than_30_days || 0) },
        { age_bucket: '30-60 days', days_in_inventory: 60, item_count: Number(raw?.between_30_60_days || 0) },
        { age_bucket: '60-90 days', days_in_inventory: 90, item_count: Number(raw?.between_60_90_days || 0) },
        { age_bucket: '> 90 days', days_in_inventory: 91, item_count: Number(raw?.older_than_90_days || 0) },
        { age_bucket: 'Never counted', days_in_inventory: 0, item_count: Number(raw?.never_counted || 0) },
      ]
      return {
        summary: [
          { label: 'Aging Buckets', value: rows.length, type: 'number' },
          { label: 'Total Items', value: rows.reduce((s: number, r: any) => s + Number(r.item_count || 0), 0), type: 'number' },
        ],
        items: rows,
        details: raw?.details || {},
      }
    }

    default:
      return { summary: [], items: [] }
  }
}

const formatDateTime = (value: any): string => {
  if (!value) return 'N/A'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return String(value)
  return d.toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const loadSelectedReport = async () => {
  if (!selectedReport.value) return

  loading.value = true
  try {
    const endpointMap: Record<ReportType, string> = {
      slow_movers: '/api/inventory/reports/slow-movers',
      fast_movers: '/api/inventory/reports/fast-movers',
      aging: '/api/inventory/reports/aging',
      transactions: '/api/inventory/transactions',
    }

    const endpoint = endpointMap[selectedReport.value]

    const startDate = dateRange.value?.[0] || new Date(new Date().setDate(new Date().getDate() - 30))
    const endDate = dateRange.value?.[1] || startDate
    const days = Math.max(
      1,
      Math.ceil((Number(endDate) - Number(startDate)) / (1000 * 60 * 60 * 24))
    )

    const response = await axiosClient.get(endpoint, {
      params: {
        days,
        per_page: 50,
        group_by: filters.groupBy,
        product_type: filters.productType || undefined
      }
    })

    const data = response.data.data
    const normalized = normalizeReportData(selectedReport.value, data)

    reportSummary.value = normalized.summary || []
    reportData.value = normalized.items || []
    agingDetails.value = selectedReport.value === 'aging' ? (normalized.details || {}) : {}

    // Set columns based on report type
    setReportColumns()

    // Generate charts
    generateCharts({
      chart_data: data?.chart_data || null,
      distribution_data: data?.distribution_data || null,
    })

    if (reportData.value.length > 0) {
      toast.add({
        severity: 'success',
        summary: `${getReportTitle()} Loaded`,
        detail: `Returned ${reportData.value.length} record(s).`,
        life: 2200,
      })
    } else {
      toast.add({
        severity: 'warn',
        summary: `${getReportTitle()} Loaded`,
        detail: 'No data found for selected filters.',
        life: 2600,
      })
    }
  } catch (error: any) {
    console.error('Failed to load report:', error)
    toast.add({
      severity: 'error',
      summary: `${getReportTitle()} Error`,
      detail: error?.response?.data?.message || 'Failed to load report data.',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const setReportColumns = () => {
  const columnMap: { [key in ReportType]: any[] } = {
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
    aging: [
      { field: 'age_bucket', header: 'Age Bucket', width: '180px' },
      { field: 'days_in_inventory', header: 'Age (Days)', type: 'number' },
      { field: 'item_count', header: 'Items', type: 'number' }
    ],
    transactions: [
      { field: 'transaction_number', header: 'Reference', width: '130px' },
      { field: 'transaction_type', header: 'Type', width: '120px' },
      { field: 'product_name', header: 'Product', width: '200px' },
      { field: 'quantity_change', header: 'Quantity', type: 'number' },
      { field: 'transaction_date', header: 'Date', type: 'datetime' }
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
    if (!reportData.value.length) {
      toast.add({
        severity: 'warn',
        summary: 'Export Skipped',
        detail: 'No report rows available to export.',
        life: 2400,
      })
      return
    }

    const columns = reportColumns.value.map((c: any) => c.field)
    const headers = reportColumns.value.map((c: any) => c.header)
    const escapeCsv = (value: any) => {
      const text = String(value ?? '')
      if (text.includes(',') || text.includes('"') || text.includes('\n')) {
        return `"${text.replace(/"/g, '""')}"`
      }
      return text
    }

    const rows = reportData.value.map((row: any) =>
      columns.map((col: string) => escapeCsv(row[col])).join(',')
    )

    const csv = [headers.join(','), ...rows].join('\n')
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `${selectedReport.value}_${new Date().toISOString().split('T')[0]}.csv`)
    document.body.appendChild(link)
    link.click()
    link.parentElement?.removeChild(link)
    window.URL.revokeObjectURL(url)

    toast.add({
      severity: 'success',
      summary: 'Exported',
      detail: `${getReportTitle()} exported successfully.`,
      life: 2200,
    })
  } catch (error: any) {
    console.error('Failed to export report:', error)
    toast.add({
      severity: 'error',
      summary: 'Export Error',
      detail: error?.response?.data?.message || 'Failed to export report.',
      life: 3000,
    })
  }
}

watch(selectedReport, async (value) => {
  if (!value) return
  setReportColumns()
  await loadSelectedReport()
})
</script>

<style scoped>
:deep(.p-chart) {
  position: relative;
  height: 300px;
}
</style>
