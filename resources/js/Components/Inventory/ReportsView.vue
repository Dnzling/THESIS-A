<template>
  <div class="space-y-4 text-xs">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h1 class="text-lg font-semibold text-slate-900">Inventory Reports</h1>
        <p class="mt-1 text-xs text-slate-500">Finished goods that need attention, based on current stock and recorded sales.</p>
      </div>
      <div class="flex gap-2">
        <Button label="Activity Log" severity="secondary" size="small" outlined @click="router.push({ name: 'inventory.activity-logs' })" />
        <Button label="Export CSV" severity="secondary" size="small" outlined :disabled="!visibleRows.length" @click="exportCsv" />
      </div>
    </div>

    <Card class="border border-slate-200 shadow-sm">
      <template #content>
        <Tabs v-model:value="selectedReport">
          <TabList>
            <Tab value="slow_movers">Slow Movers</Tab>
            <Tab value="fast_movers">Fast Movers</Tab>
            <Tab value="aging">Stock Aging</Tab>
            <Tab value="transactions">Transactions</Tab>
          </TabList>
        </Tabs>

        <div class="mt-4 rounded-xl border border-orange-100 bg-orange-50/60 px-4 py-3">
          <p class="text-sm font-semibold text-slate-900">{{ currentCopy.title }}</p>
          <p class="mt-1 text-xs leading-5 text-slate-600">{{ currentCopy.description }}</p>
        </div>

        <div class="mt-4 flex flex-wrap items-end gap-3 border-b border-slate-100 pb-4">
          <div class="w-full sm:w-60">
            <label class="mb-1 block text-xs font-medium text-slate-600">Report period</label>
            <DatePicker v-model="dateRange" selectionMode="range" dateFormat="M d, yy" size="small"
              placeholder="Select date range" :manualInput="false" fluid />
          </div>
          <div class="min-w-48 flex-1 sm:max-w-72">
            <label class="mb-1 block text-xs font-medium text-slate-600">Find a product</label>
            <InputText v-model="search" placeholder="Name, SKU, or branch" size="small" fluid />
          </div>
          <Button label="Apply Dates" severity="warn" size="small" :disabled="!dateRange?.[0] || !dateRange?.[1]" :loading="loading" @click="loadReport" />
          <Button label="Refresh" severity="secondary" outlined size="small" :loading="loading" @click="loadReport" />
        </div>

        <div v-if="loading" class="space-y-3 py-6">
          <Skeleton height="2.5rem" />
          <Skeleton v-for="n in 5" :key="n" height="2rem" />
        </div>
        <template v-else>
          <div class="grid grid-cols-2 gap-3 py-4 sm:grid-cols-3">
            <div v-for="metric in metrics" :key="metric.label" class="rounded-xl border border-slate-200 bg-slate-50/70 p-3">
              <p class="text-xs text-slate-500">{{ metric.label }}</p>
              <p class="mt-1 text-lg font-semibold text-slate-900">{{ metric.value }}</p>
            </div>
          </div>
          <p v-if="limited" class="mb-3 text-xs text-amber-700">Showing the 200 most recent transactions. Narrow the date range for a focused review.</p>
          <DataTable :value="visibleRows" size="small" class="text-xs" rowHover paginator :rows="15"
            :rowsPerPageOptions="[15, 30, 50]" @row-click="openRow">
            <template #empty>
              <div class="py-10 text-center text-xs text-slate-500">{{ errorMessage || currentCopy.empty }}</div>
            </template>
            <Column header="Product" style="min-width: 220px">
              <template #body="{ data }">
                <p class="font-semibold text-slate-900">{{ data.product_name || 'Unknown product' }}</p>
                <p v-if="data.variation_name" class="text-slate-500">{{ data.variation_name }}</p>
                <p class="text-slate-500">{{ data.sku || 'No SKU' }} · {{ data.branch }}</p>
              </template>
            </Column>
            <template v-if="selectedReport !== 'transactions'">
              <Column field="available" header="Available" sortable />
              <Column v-if="selectedReport !== 'aging'" field="units_sold" :header="`Sold (${periodLabel})`" sortable />
              <Column v-if="selectedReport === 'fast_movers'" field="days_cover" header="Days Cover" sortable>
                <template #body="{ data }">{{ data.days_cover ?? 'No sales pace' }}</template>
              </Column>
              <Column v-if="selectedReport === 'fast_movers'" field="reorder_point" header="Reorder At" sortable />
              <Column v-if="selectedReport === 'slow_movers'" field="stock_value" header="Stock Value" sortable>
                <template #body="{ data }">{{ money(data.stock_value) }}</template>
              </Column>
              <Column v-if="selectedReport === 'aging'" field="days_since_sale" header="Days Without Sale" sortable>
                <template #body="{ data }">{{ data.days_since_sale ?? 'Never sold' }}</template>
              </Column>
              <Column v-if="selectedReport === 'aging'" field="last_sale_at" header="Last Sale">
                <template #body="{ data }">{{ date(data.last_sale_at) }}</template>
              </Column>
            </template>
            <template v-else>
              <Column field="transaction_date" header="Date" sortable><template #body="{ data }">{{ dateTime(data.transaction_date) }}</template></Column>
              <Column field="transaction_type" header="Movement"><template #body="{ data }">{{ label(data.transaction_type) }}</template></Column>
              <Column field="quantity_change" header="Change" sortable><template #body="{ data }"><span :class="data.quantity_change < 0 ? 'text-rose-600' : 'text-emerald-700'">{{ data.quantity_change > 0 ? '+' : '' }}{{ data.quantity_change }}</span></template></Column>
            </template>
            <Column header="Suggested Action" style="min-width: 185px">
              <template #body="{ data }"><Badge :value="data.action" :severity="data.severity" /></template>
            </Column>
            <Column header="" style="width: 75px"><template #body="{ data }"><Button label="View" severity="warn" text size="small" @click.stop="openRow({ data })" /></template></Column>
          </DataTable>
        </template>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import axiosClient from '@/axios'
import { useToast } from 'primevue/usetoast'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import Skeleton from 'primevue/skeleton'

type ReportType = 'slow_movers' | 'fast_movers' | 'aging' | 'transactions'
type ReportRow = Record<string, any>

const router = useRouter()
const toast = useToast()
const selectedReport = ref<ReportType>('slow_movers')
const dateRange = ref<Date[]>([new Date(Date.now() - 90 * 86400000), new Date()])
const search = ref('')
const rows = ref<ReportRow[]>([])
const loading = ref(true)
const limited = ref(false)
const errorMessage = ref('')
let requestNumber = 0

const copy: Record<ReportType, { title: string; description: string; empty: string }> = {
  slow_movers: {
    title: 'Stock that is not moving',
    description: 'Available finished goods with no sales after 30 days in stock, or more than 60 days of stock at the selected sales pace. Review merchandising or pricing before reordering.',
    empty: 'No slow-moving finished goods for this period.'
  },
  fast_movers: {
    title: 'Products selling now',
    description: 'Finished goods with recorded sales, ranked by units sold. Compare available stock and days of cover to decide what to replenish.',
    empty: 'No finished-good sales recorded for this period.'
  },
  aging: {
    title: 'Stock waiting for its next sale',
    description: 'Days since the last recorded sale are a sales-inactivity proxy, not the physical age of each unit. Prioritize older, unsold stock for review.',
    empty: 'No finished goods with available stock.'
  },
  transactions: {
    title: 'What changed in stock',
    description: 'Recent finished-good stock movements, including sales and adjustments. Open a movement to verify its source and details.',
    empty: 'No finished-good transactions in this period.'
  }
}

const currentCopy = computed(() => copy[selectedReport.value])
const visibleRows = computed(() => {
  const term = search.value.trim().toLowerCase()
  if (!term) return rows.value
  return rows.value.filter(row => [row.product_name, row.variation_name, row.sku, row.branch]
    .some(value => String(value || '').toLowerCase().includes(term)))
})
const periodLabel = computed(() => {
  if (!dateRange.value?.[0] || !dateRange.value?.[1]) return 'period'
  const days = Math.max(1, Math.round((dateRange.value[1].getTime() - dateRange.value[0].getTime()) / 86400000) + 1)
  return `${days} days`
})
const metrics = computed(() => {
  const data = visibleRows.value
  if (selectedReport.value === 'transactions') return [
    { label: 'Movements shown', value: data.length.toLocaleString() },
    { label: 'Sales', value: data.filter(row => row.transaction_type === 'sale').length.toLocaleString() },
    { label: 'Needs review', value: data.filter(row => row.severity === 'warn').length.toLocaleString() },
  ]
  return [
    { label: 'Finished-good SKUs', value: data.length.toLocaleString() },
    { label: 'Units available', value: data.reduce((sum, row) => sum + Number(row.available || 0), 0).toLocaleString() },
    { label: 'Needs action', value: data.filter(row => row.severity === 'warn' || row.severity === 'danger').length.toLocaleString() },
  ]
})

const apiDate = (value: Date) => `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`
const date = (value: string | null) => value ? new Date(value).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : 'Never'
const dateTime = (value: string | null) => value ? new Date(value).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '-'
const label = (value: string) => String(value || '-').replaceAll('_', ' ').replace(/\b\w/g, char => char.toUpperCase())
const money = (value: number) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))

const loadReport = async () => {
  if (!dateRange.value?.[0] || !dateRange.value?.[1]) return
  const request = ++requestNumber
  loading.value = true
  errorMessage.value = ''
  try {
    const response = await axiosClient.get('/api/inventory/reports/actionable', {
      params: { report: selectedReport.value, date_from: apiDate(dateRange.value[0]), date_to: apiDate(dateRange.value[1]) }
    })
    if (request !== requestNumber) return
    rows.value = response.data.data?.rows || []
    limited.value = Boolean(response.data.data?.limited)
  } catch (error: any) {
    if (request !== requestNumber) return
    rows.value = []
    limited.value = false
    errorMessage.value = error?.response?.data?.message || 'Unable to load this report.'
    toast.add({ severity: 'error', summary: 'Report unavailable', detail: errorMessage.value, life: 3500 })
  } finally {
    if (request === requestNumber) loading.value = false
  }
}

const openRow = ({ data }: { data: ReportRow }) => {
  if (selectedReport.value === 'transactions') {
    router.push({ name: 'inventory.transactions.detail', params: { id: data.id } })
  } else if (data.product_id) {
    router.push({ name: 'inventory.products.detail', params: { id: data.product_id },
      query: data.variation_id ? { variation_id: String(data.variation_id) } : {} })
  }
}

const exportCsv = () => {
  const columns = selectedReport.value === 'transactions'
    ? ['branch', 'sku', 'product_name', 'variation_name', 'transaction_date', 'transaction_type', 'quantity_change', 'action']
    : ['branch', 'sku', 'product_name', 'variation_name', 'available', 'units_sold', 'days_cover', 'days_since_sale', 'last_sale_at', 'reorder_point', 'stock_value', 'action']
  const escape = (value: any) => `"${String(value ?? '').replaceAll('"', '""')}"`
  const csv = [columns.join(','), ...visibleRows.value.map(row => columns.map(key => escape(row[key])).join(','))].join('\r\n')
  const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }))
  const anchor = document.createElement('a')
  anchor.href = url
  anchor.download = `inventory_${selectedReport.value}_${apiDate(new Date())}.csv`
  anchor.click()
  URL.revokeObjectURL(url)
}

watch(selectedReport, loadReport)
onMounted(loadReport)
</script>
