<template>
  <div class="module-dashboard space-y-5 pb-6 text-sm">
    <header class="dashboard-hero flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h1 class="mt-1 text-2xl font-semibold text-slate-950">Product Sales Analysis</h1>
        <p class="mt-1 max-w-2xl text-sm text-slate-600">Compare product, category, and branch results with the previous period. Includes paid POS and online orders.</p>
      </div>
      <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:flex lg:flex-wrap lg:items-center">
        <Select v-model="filters.days" :options="periodOptions" option-label="label" option-value="value" aria-label="Report period" class="w-full sm:w-40" size="small" @change="applyFilters" />
        <Select v-model="filters.branch_id" :options="branchOptions" option-label="name" option-value="id" aria-label="Branch" class="w-full sm:w-48" size="small" @change="applyFilters" />
        <Select v-model="filters.category_id" :options="categoryOptions" option-label="name" option-value="id" aria-label="Category" class="w-full sm:w-48" size="small" @change="applyFilters" />
        <Button icon="pi pi-refresh" label="Refresh" severity="secondary" outlined size="small" :loading="loading" @click="loadReport" />
      </div>
    </header>

    <section class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
      <p class="text-xs text-slate-500">Compared with the immediately preceding {{ filters.days }} days.</p>
      <div class="w-full sm:w-72">
        <InputText v-model="searchInput" class="w-full" size="small" placeholder="Filter products by name or SKU" aria-label="Search products" @input="onSearchInput" />
      </div>
    </section>

    <div v-if="loading && !report" class="space-y-4">
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"><Skeleton v-for="n in 4" :key="n" height="112px" class="rounded-xl" /></div>
      <Skeleton height="280px" class="rounded-xl" />
      <div class="grid gap-4 xl:grid-cols-2"><Skeleton height="260px" class="rounded-xl" /><Skeleton height="260px" class="rounded-xl" /></div>
      <Skeleton height="320px" class="rounded-xl" />
    </div>
    <div v-else-if="loadError" class="rounded-xl border border-red-200 bg-white p-5">
      <p class="font-semibold text-slate-900">Reports could not be loaded.</p>
      <p class="mt-1 text-sm text-slate-600">{{ loadError }}</p>
      <Button label="Try again" size="small" severity="warn" class="mt-3" @click="loadReport" />
    </div>
    <template v-else-if="report">
      <Card class="border border-slate-200 shadow-sm">
        <template #content>
          <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <div>
              <p class="text-xs font-medium text-slate-500">Selected period sales</p>
              <p class="mt-1 text-xl font-semibold text-slate-950">{{ money(report.summary.sales) }}</p>
              <p class="mt-1 text-xs text-slate-500">{{ count(report.summary.units_sold) }} units · {{ count(report.summary.orders) }} paid orders</p>
            </div>
            <div>
              <p class="text-xs font-medium text-slate-500">Previous {{ filters.days }} days</p>
              <p class="mt-1 text-xl font-semibold text-slate-700">{{ money(report.summary.previous_sales) }}</p>
              <p class="mt-1 text-xs text-slate-500">{{ count(report.summary.previous_units_sold) }} units · {{ count(report.summary.previous_orders) }} paid orders</p>
            </div>
            <div>
              <p class="text-xs font-medium text-slate-500">Sales change</p>
              <p class="mt-1 text-xl font-semibold" :class="salesChange === null ? 'text-slate-600' : salesChange >= 0 ? 'text-emerald-700' : 'text-red-600'">
                <i v-if="salesChange !== null" :class="salesChange >= 0 ? 'pi pi-arrow-up-right' : 'pi pi-arrow-down-right'" class="mr-1 text-sm" />
                {{ salesChange === null ? (Number(report.summary.sales) > 0 ? 'New this period' : 'No change') : `${Math.abs(salesChange).toFixed(1)}%` }}
              </p>
              <p class="mt-1 text-xs text-slate-500">{{ money(Number(report.summary.sales) - Number(report.summary.previous_sales)) }} versus previous period</p>
            </div>
            <div>
              <p class="text-xs font-medium text-slate-500">Active catalog</p>
              <p class="mt-1 text-xl font-semibold text-slate-950">{{ count(report.summary.active_products) }}</p>
              <p class="mt-1 text-xs text-slate-500">Finished products available for analysis</p>
            </div>
          </div>
        </template>
      </Card>

      <section>
        <Card class="border border-slate-200 shadow-sm">
          <template #title>
            <h2 class="text-base font-semibold text-slate-900">Daily sales trend</h2>
            <p class="mt-1 text-xs font-normal text-slate-500">Use this to spot changes during the selected period; comparisons below show where the change came from.</p>
          </template>
          <template #content>
            <Chart v-if="hasSales" type="line" :data="trendChart" :options="trendOptions" class="h-64" />
            <div v-else class="flex h-64 items-center justify-center text-sm text-slate-500">No qualifying sales for this period and filter.</div>
          </template>
        </Card>
      </section>

      <section class="grid gap-4 xl:grid-cols-2">
        <Card class="border border-slate-200 shadow-sm">
          <template #title>
            <h2 class="text-base font-semibold text-slate-900">Category comparison</h2>
            <p class="mt-1 text-xs font-normal text-slate-500">Sales by category versus the previous period</p>
          </template>
          <template #content>
            <DataTable :value="report.category_sales" size="small" row-hover responsive-layout="scroll">
              <Column field="name" header="Category" />
              <Column field="sales" header="This period"><template #body="{ data }">{{ money(data.sales) }}</template></Column>
              <Column field="previous_sales" header="Previous"><template #body="{ data }">{{ money(data.previous_sales) }}</template></Column>
              <Column field="change_percent" header="Change"><template #body="{ data }"><span :class="data.change_percent === null ? 'text-slate-500' : data.change_percent >= 0 ? 'text-emerald-700' : 'text-red-600'">{{ formatChange(data.change_percent) }}</span></template></Column>
              <template #empty><div class="py-5 text-center text-sm text-slate-500">No category sales in these periods.</div></template>
            </DataTable>
          </template>
        </Card>
        <Card class="border border-slate-200 shadow-sm">
          <template #title>
            <h2 class="text-base font-semibold text-slate-900">Branch comparison</h2>
            <p class="mt-1 text-xs font-normal text-slate-500">See which branches gained or lost sales</p>
          </template>
          <template #content>
            <DataTable :value="report.branch_sales" size="small" row-hover responsive-layout="scroll">
              <Column field="name" header="Branch" />
              <Column field="sales" header="This period"><template #body="{ data }">{{ money(data.sales) }}</template></Column>
              <Column field="previous_sales" header="Previous"><template #body="{ data }">{{ money(data.previous_sales) }}</template></Column>
              <Column field="change_percent" header="Change"><template #body="{ data }"><span :class="data.change_percent === null ? 'text-slate-500' : data.change_percent >= 0 ? 'text-emerald-700' : 'text-red-600'">{{ formatChange(data.change_percent) }}</span></template></Column>
              <template #empty><div class="py-5 text-center text-sm text-slate-500">No active store branches found.</div></template>
            </DataTable>
          </template>
        </Card>
      </section>

      <Card class="border border-slate-200 shadow-sm">
        <template #title>
          <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
              <h2 class="text-base font-semibold text-slate-900">Product-level period comparison</h2>
              <p class="mt-1 text-xs font-normal text-slate-500">Compare each product’s units and sales with the previous period</p>
            </div>
            <span class="text-xs font-normal text-slate-500">{{ report.products.total }} products</span>
          </div>
        </template>
        <template #content>
          <DataTable
            :value="report.products.data"
            lazy
            paginator
            :rows="filters.per_page"
            :first="(report.products.current_page - 1) * filters.per_page"
            :total-records="report.products.total"
            :rows-per-page-options="[10, 15, 25, 50]"
            data-key="id"
            size="small"
            row-hover
            responsive-layout="scroll"
            @page="onPage"
          >
            <Column field="name" header="Product">
              <template #body="{ data }">
                <span class="font-medium text-slate-900">{{ data.name }}</span>
                <span class="mt-0.5 block text-xs text-slate-500">{{ data.sku || 'No item number' }}</span>
              </template>
            </Column>
            <Column field="category" header="Category">
              <template #body="{ data }">{{ data.category || 'Uncategorized' }}</template>
            </Column>
            <Column field="units_sold" header="Units sold" />
            <Column field="previous_units_sold" header="Previous units" />
            <Column field="branches_selling" header="Branches selling">
              <template #body="{ data }">{{ data.branches_selling }} / {{ report.branch_sales.length }}</template>
            </Column>
            <Column field="sales" header="This period sales">
              <template #body="{ data }"><span class="font-semibold text-slate-900">{{ money(data.sales) }}</span></template>
            </Column>
            <Column field="previous_sales" header="Previous sales">
              <template #body="{ data }">{{ money(data.previous_sales) }}</template>
            </Column>
            <Column field="sales_change_percent" header="Sales change">
              <template #body="{ data }"><span :class="data.sales_change_percent === null ? 'text-slate-500' : data.sales_change_percent >= 0 ? 'text-emerald-700' : 'text-red-600'">{{ formatChange(data.sales_change_percent) }}</span></template>
            </Column>
            <template #empty><div class="py-8 text-center text-sm text-slate-500">No products match the selected filters.</div></template>
          </DataTable>
        </template>
      </Card>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent, onMounted, reactive, ref } from 'vue'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import merchandisingService from '../../../../services/merchandising.service'

const Chart = defineAsyncComponent(() => import('primevue/chart'))
const periodOptions = [{ label: 'Last 7 days', value: 7 }, { label: 'Last 30 days', value: 30 }, { label: 'Last 90 days', value: 90 }]
const filters = reactive({ days: 30, branch_id: null as number | null, category_id: null as number | null, search: '', page: 1, per_page: 15 })
const searchInput = ref('')
const report = ref<any>(null)
const loading = ref(false)
const loadError = ref('')
let searchTimer: ReturnType<typeof setTimeout> | undefined

const branchOptions = computed(() => [{ id: null, name: 'All branches' }, ...(report.value?.branches || [])])
const categoryOptions = computed(() => [{ id: null, name: 'All categories' }, ...(report.value?.categories || [])])
const money = (value: unknown) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value) || 0)
const count = (value: unknown) => Number(value || 0).toLocaleString('en-PH')
const salesChange = computed(() => {
  const current = Number(report.value?.summary?.sales || 0)
  const previous = Number(report.value?.summary?.previous_sales || 0)
  return previous > 0 ? ((current - previous) / previous) * 100 : null
})
const formatChange = (value: unknown) => value === null || value === undefined ? 'New' : `${Number(value) > 0 ? '+' : ''}${Number(value).toFixed(1)}%`
const hasSales = computed(() => (report.value?.trend || []).some((row: any) => Number(row.sales) > 0))
const trendChart = computed(() => ({
  labels: (report.value?.trend || []).map((row: any) => row.label),
  datasets: [
    { label: 'Sales', data: (report.value?.trend || []).map((row: any) => Number(row.sales)), borderColor: '#f97316', backgroundColor: 'rgba(249, 115, 22, .12)', fill: true, tension: .32, pointRadius: filters.days === 90 ? 0 : 2, yAxisID: 'sales' },
    { label: 'Units sold', data: (report.value?.trend || []).map((row: any) => Number(row.units)), borderColor: '#3b82f6', backgroundColor: '#3b82f6', tension: .25, pointRadius: filters.days === 90 ? 0 : 2, yAxisID: 'units' },
  ],
}))
const trendOptions = {
  maintainAspectRatio: false,
  interaction: { mode: 'index' as const, intersect: false },
  plugins: { legend: { position: 'bottom' as const, labels: { usePointStyle: true, boxWidth: 8 } } },
  scales: {
    x: { grid: { display: false }, ticks: { maxTicksLimit: 9 } },
    sales: { type: 'linear' as const, position: 'left' as const, beginAtZero: true, ticks: { callback: (value: any) => `₱${Number(value).toLocaleString('en-PH')}` } },
    units: { type: 'linear' as const, position: 'right' as const, beginAtZero: true, grid: { drawOnChartArea: false }, ticks: { precision: 0 } },
  },
}
async function loadReport() {
  loading.value = true
  loadError.value = ''
  try {
    const response = await merchandisingService.getReports(filters)
    if (response?.success === false) throw new Error(response.message || 'Unable to load reports.')
    report.value = response.data
  } catch (error: any) {
    loadError.value = error?.response?.data?.message || error?.message || 'Unable to load product catalog reports.'
  } finally {
    loading.value = false
  }
}
function applyFilters() {
  filters.page = 1
  loadReport()
}
function onSearchInput() {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    filters.search = searchInput.value.trim()
    applyFilters()
  }, 300)
}
function onPage(event: { page: number; rows: number }) {
  filters.page = event.page + 1
  filters.per_page = event.rows
  loadReport()
}
onMounted(loadReport)
</script>
