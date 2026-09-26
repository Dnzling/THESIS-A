<template>
  <div class="space-y-4 p-4 text-xs md:p-6">
    <div>
      <h1 class="text-xl font-semibold text-slate-900">{{ warehouse ? 'Warehouse' : 'Inventory' }} Forecasting</h1>
      <p class="mt-1 text-slate-500">A transparent 30-day projection from recent stock outflow. Review low-cover items before creating a replenishment request.</p>
    </div>
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <Card v-for="card in cards" :key="card.label" class="border border-slate-200 shadow-sm"><template #content>
        <div class="text-slate-500">{{ card.label }}</div><div class="mt-1 text-xl font-semibold text-slate-900">{{ card.value }}</div>
      </template></Card>
    </div>
    <Card class="border border-slate-200 shadow-sm"><template #content>
      <div class="mb-4 flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-sm font-semibold text-slate-900">Recent stock outflow</h2><p class="text-slate-500">Daily units removed from stock in the selected period</p></div><Select v-model="days" :options="periods" optionLabel="label" optionValue="value" size="small" @change="refresh" /></div>
      <div v-if="loading" class="space-y-2"><Skeleton v-for="n in 3" :key="n" height="36px" /></div>
      <Chart v-else-if="trend.length" type="line" :data="chartData" :options="chartOptions" class="h-60" />
      <div v-else class="py-10 text-center text-slate-500">No stock outflow recorded for this period. Forecast demand requires transaction history.</div>
    </template></Card>
    <Card class="border border-slate-200 shadow-sm"><template #content>
      <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <IconField><InputIcon class="pi pi-search" /><InputText v-model="search" placeholder="Search item or SKU" size="small" fluid @input="searchChanged" /></IconField>
        <Select v-if="branches.length > 1" v-model="branchId" :options="branches" optionLabel="name" optionValue="id" placeholder="All branches" showClear size="small" fluid @change="refresh" />
        <Select v-model="status" :options="statuses" optionLabel="label" optionValue="value" placeholder="All statuses" showClear size="small" fluid @change="refresh" />
      </div>
      <Message v-if="error" severity="error" :closable="false" class="mb-3">{{ error }}</Message>
      <DataTable :value="rows" :loading="loading" rowHover responsiveLayout="scroll" size="small">
        <template #empty><div class="py-10 text-center text-slate-500">No finished-good items match these filters.</div></template>
        <Column field="sku" header="SKU" />
        <Column header="Product" style="min-width: 190px"><template #body="{ data }"><span class="font-medium text-slate-900">{{ data.product_name }}</span></template></Column>
        <Column v-if="branches.length > 1" field="branch_name" header="Branch" />
        <Column field="available" header="Available" />
        <Column field="reorder_point" header="Reorder at" />
        <Column field="sold" header="Used in period" />
        <Column header="30-day forecast"><template #body="{ data }">{{ number(data.forecast_30_days) }}</template></Column>
        <Column header="Days cover"><template #body="{ data }">{{ data.days_cover === null ? 'No demand yet' : number(data.days_cover) }}</template></Column>
        <Column field="recommended" header="Suggested qty" />
        <Column header="Status"><template #body="{ data }"><Badge :value="data.status" :severity="severity(data.status)" /></template></Column>
        <Column header="Action"><template #body="{ data }"><Button label="View stock" size="small" outlined severity="warn" @click="router.visit(warehouse ? `/warehouse/stock/${data.id}` : `/inventory/items/${data.id}`)" /></template></Column>
      </DataTable>
      <Paginator v-if="total > 15" :rows="15" :first="(page - 1) * 15" :totalRecords="total" @page="page = $event.page + 1; load()" />
    </template></Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import Card from 'primevue/card'
import Chart from 'primevue/chart'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Badge from 'primevue/badge'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Skeleton from 'primevue/skeleton'
import Paginator from 'primevue/paginator'
import Message from 'primevue/message'
import Button from 'primevue/button'

const warehouse = window.location.pathname.startsWith('/warehouse/')
const endpoint = warehouse ? '/api/warehouse/forecasting' : '/api/inventory/forecasting'
const periods = [{ label: 'Last 30 days', value: 30 }, { label: 'Last 60 days', value: 60 }, { label: 'Last 90 days', value: 90 }]
const statuses = ['Out of stock', 'Reorder now', 'Watch', 'Healthy'].map(value => ({ label: value, value }))
const days = ref(30)
const branchId = ref<number | null>(null)
const status = ref<string | null>(null)
const search = ref('')
const page = ref(1)
const total = ref(0)
const loading = ref(true)
const error = ref('')
const rows = ref<any[]>([])
const branches = ref<any[]>([])
const trend = ref<any[]>([])
const summary = ref<any>({ products: 0, reorder_now: 0, forecast_units: 0, recommended_units: 0 })
const number = (value: any) => new Intl.NumberFormat('en-PH', { maximumFractionDigits: 1 }).format(Number(value) || 0)
const cards = computed(() => [
  { label: 'Finished goods', value: number(summary.value.products) },
  { label: 'Reorder now', value: number(summary.value.reorder_now) },
  { label: 'Projected 30-day use', value: number(summary.value.forecast_units) },
  { label: 'Suggested units', value: number(summary.value.recommended_units) },
])
const severity = (value: string) => value === 'Out of stock' ? 'danger' : value === 'Reorder now' ? 'warn' : value === 'Watch' ? 'info' : 'success'
const chartData = computed(() => ({ labels: trend.value.map(row => row.day), datasets: [{ label: 'Units out', data: trend.value.map(row => Number(row.units)), borderColor: '#f97316', backgroundColor: 'rgba(249,115,22,.12)', fill: true, tension: .3 }] }))
const chartOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
let searchTimer: ReturnType<typeof setTimeout> | null = null
function searchChanged() { if (searchTimer) clearTimeout(searchTimer); searchTimer = setTimeout(refresh, 300) }
function refresh() { page.value = 1; load() }
async function load() {
  loading.value = true
  error.value = ''
  try {
    const response = await axios.get(endpoint, { params: { days: days.value, branch_id: branchId.value, search: search.value, status: status.value, page: page.value } })
    const data = response.data.data
    rows.value = data.rows || []
    total.value = data.total || 0
    branches.value = data.branches || []
    trend.value = data.trend || []
    summary.value = data.summary || {}
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Could not load the forecast.'
    rows.value = []
  } finally { loading.value = false }
}
onMounted(load)
</script>
