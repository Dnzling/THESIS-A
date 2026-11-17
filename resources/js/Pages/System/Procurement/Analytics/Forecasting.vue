<template>
  <div class="space-y-6 pb-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-3xl font-bold text-slate-900">Replenishment Forecast</h1>
        <p class="mt-1 text-slate-600">Compare branch demand and identify where supplies are most likely to need replenishment.</p>
      </div>
      <div class="flex items-center gap-2">
        <Select v-model="days" :options="periodOptions" optionLabel="label" optionValue="value" class="w-36" @change="loadForecast" />
        <Button label="Refresh" icon="pi pi-refresh" severity="secondary" outlined :loading="loading" @click="loadForecast" />
      </div>
    </div>

    <Message v-if="!loading && !forecast.is_multi_branch" severity="info" :closable="false">
      Forecast comparison is available when the store has more than one active branch. This view is showing the available branch data only.
    </Message>

    <div v-if="loading" class="grid grid-cols-1 gap-4 md:grid-cols-4">
      <Skeleton v-for="item in 4" :key="item" height="120px" class="rounded-xl" />
    </div>

    <template v-else>
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <Card v-for="card in summaryCards" :key="card.label" class="border border-slate-200 shadow-sm">
          <template #content>
            <div class="flex items-start justify-between">
              <div><p class="text-sm text-slate-500">{{ card.label }}</p><p class="mt-2 text-2xl font-bold text-slate-900">{{ card.value }}</p><p class="mt-1 text-xs text-slate-500">{{ card.hint }}</p></div>
              <span class="rounded-xl p-3" :class="card.bg"><i :class="[card.icon, card.color, 'text-xl']" /></span>
            </div>
          </template>
        </Card>
      </div>

      <div class="grid grid-cols-1 gap-6 xl:grid-cols-5">
        <Card class="border border-slate-200 shadow-sm xl:col-span-3">
          <template #title>Branch replenishment pressure</template>
          <template #subtitle>Higher scores indicate more immediate stock pressure.</template>
          <template #content><Chart type="bar" :data="branchChartData" :options="barOptions" class="h-80" /></template>
        </Card>
        <Card class="border border-slate-200 shadow-sm xl:col-span-2">
          <template #title>Recent consumption trend</template>
          <template #subtitle>Issues and sales recorded in the selected period.</template>
          <template #content><Chart type="line" :data="demandChartData" :options="lineOptions" class="h-80" /></template>
        </Card>
      </div>

      <Card class="border border-slate-200 shadow-sm">
        <template #title>Branch comparison</template>
        <template #content>
          <DataTable :value="forecast.branch_summary || []" stripedRows responsiveLayout="stack" breakpoint="768px" emptyMessage="No branch inventory data found.">
            <Column field="branch_name" header="Branch" />
            <Column field="items_needing_replenishment" header="Needs Replenishment" />
            <Column field="out_of_stock_items" header="Out of Stock" />
            <Column field="shortage_qty" header="Shortage Qty"><template #body="{ data }">{{ formatNumber(data.shortage_qty) }}</template></Column>
            <Column field="recommended_qty" header="Recommended Qty"><template #body="{ data }">{{ formatNumber(data.recommended_qty) }}</template></Column>
            <Column field="consumed_qty" header="Consumed (period)"><template #body="{ data }">{{ formatNumber(data.consumed_qty) }}</template></Column>
            <Column field="pressure_score" header="Pressure"><template #body="{ data }"><Tag :value="formatNumber(data.pressure_score)" :severity="pressureSeverity(data.pressure_score)" /></template></Column>
          </DataTable>
        </template>
      </Card>

      <Card class="border border-slate-200 shadow-sm">
        <template #title>Top items to replenish</template>
        <template #subtitle>Based on current stock, reorder settings, incoming stock, and recent consumption.</template>
        <template #content>
          <DataTable :value="topItems" stripedRows responsiveLayout="stack" breakpoint="768px" emptyMessage="No replenishment items found.">
            <Column field="product_name" header="Product" />
            <Column field="sku" header="SKU" />
            <Column field="branch_id" header="Branch"><template #body="{ data }">{{ branchName(data.branch_id) }}</template></Column>
            <Column field="available_qty" header="Available"><template #body="{ data }">{{ formatNumber(data.available_qty) }}</template></Column>
            <Column field="projected_30_day_demand" header="30-day Demand"><template #body="{ data }">{{ formatNumber(data.projected_30_day_demand) }}</template></Column>
            <Column field="recommended_qty" header="Recommended Order"><template #body="{ data }"><strong>{{ formatNumber(data.recommended_qty) }}</strong></template></Column>
          </DataTable>
        </template>
      </Card>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import procurementService from '../../../../services/procurement.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Select from 'primevue/select'
import Message from 'primevue/message'
import Skeleton from 'primevue/skeleton'
import Chart from 'primevue/chart'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'

const loading = ref(true)
const days = ref(90)
const forecast = ref<any>({ branch_summary: [], top_items: [], monthly_demand: [] })
const periodOptions = [{ label: 'Last 30 days', value: 30 }, { label: 'Last 90 days', value: 90 }, { label: 'Last 180 days', value: 180 }, { label: 'Last year', value: 365 }]

const loadForecast = async () => {
  loading.value = true
  try {
    const response = await procurementService.getForecasting({ days: days.value })
    forecast.value = response?.data || {}
  } catch {
    forecast.value = { branch_summary: [], top_items: [], monthly_demand: [] }
  } finally { loading.value = false }
}

const topBranch = computed(() => forecast.value.branch_summary?.[0])
const topItems = computed(() => forecast.value.top_items || [])
const summaryCards = computed(() => [
  { label: 'Branches analyzed', value: forecast.value.branch_count || 0, hint: 'Active branches in scope', icon: 'pi pi-building', color: 'text-blue-600', bg: 'bg-blue-50' },
  { label: 'Highest pressure', value: topBranch.value?.branch_name || '—', hint: `${topBranch.value?.items_needing_replenishment || 0} items need attention`, icon: 'pi pi-exclamation-triangle', color: 'text-amber-600', bg: 'bg-amber-50' },
  { label: 'Items to replenish', value: (forecast.value.branch_summary || []).reduce((sum: number, row: any) => sum + Number(row.items_needing_replenishment || 0), 0), hint: 'Across all branches', icon: 'pi pi-box', color: 'text-red-600', bg: 'bg-red-50' },
  { label: 'Recommended quantity', value: formatNumber((forecast.value.branch_summary || []).reduce((sum: number, row: any) => sum + Number(row.recommended_qty || 0), 0)), hint: 'Suggested order quantity', icon: 'pi pi-shopping-cart', color: 'text-emerald-600', bg: 'bg-emerald-50' },
])
const branchChartData = computed(() => ({ labels: (forecast.value.branch_summary || []).map((row: any) => row.branch_name), datasets: [{ label: 'Pressure score', data: (forecast.value.branch_summary || []).map((row: any) => row.pressure_score), backgroundColor: '#f59e0b', borderRadius: 6 }, { label: 'Items needing replenishment', data: (forecast.value.branch_summary || []).map((row: any) => row.items_needing_replenishment), backgroundColor: '#ef4444', borderRadius: 6 }] }))
const demandChartData = computed(() => ({ labels: (forecast.value.monthly_demand || []).map((row: any) => row.month), datasets: [{ label: 'Consumed quantity', data: (forecast.value.monthly_demand || []).map((row: any) => row.consumed_qty), borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.12)', fill: true, tension: .35 }] }))
const barOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
const lineOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
const formatNumber = (value: any) => new Intl.NumberFormat('en-PH', { maximumFractionDigits: 2 }).format(Number(value) || 0)
const branchName = (id: number) => forecast.value.branch_summary?.find((row: any) => Number(row.branch_id) === Number(id))?.branch_name || '—'
const pressureSeverity = (value: number) => Number(value) >= 20 ? 'danger' : Number(value) > 0 ? 'warning' : 'success'
onMounted(loadForecast)
</script>
