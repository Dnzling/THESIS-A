<template>
  <div class="space-y-5">
    <header class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-orange-600">Store Performance</p>
        <h1 class="text-2xl font-semibold text-slate-900">{{ data?.branch?.name || 'My Store' }}</h1>
        <p class="mt-1 text-sm text-slate-500">Performance of your assigned branch across in-store and ecommerce sales.</p>
      </div>
      <div class="flex items-center gap-2">
        <Tag :value="formatLabel(data?.branch?.status || 'active')" :severity="data?.branch?.status === 'active' ? 'success' : 'warn'" />
        <Button label="Refresh" icon="pi pi-refresh" size="small" outlined severity="secondary" :loading="loading" @click="load" />
      </div>
    </header>

    <div v-if="loading && !data" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <Skeleton v-for="item in 8" :key="item" height="7rem" borderRadius="14px" />
    </div>

    <Message v-else-if="error" severity="error" :closable="false">{{ error }}</Message>

    <template v-else-if="data">
      <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <Card v-for="metric in metrics" :key="metric.label" class="border border-slate-200 shadow-sm">
          <template #content>
            <div class="flex items-start justify-between gap-3">
              <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ metric.label }}</p><p class="mt-2 text-2xl font-semibold text-slate-950">{{ metric.value }}</p><p class="mt-1 text-xs text-slate-500">{{ metric.caption }}</p></div>
              <i :class="[metric.icon, 'text-xl text-orange-500']"></i>
            </div>
          </template>
        </Card>
      </section>

      <section class="grid gap-4 xl:grid-cols-3">
        <Card class="border border-slate-200 shadow-sm xl:col-span-2">
          <template #title><span class="text-base">Combined Sales Trend</span></template>
          <template #subtitle>In-store and ecommerce revenue for the last 14 days</template>
          <template #content><Chart type="line" :data="trendData" :options="lineOptions" class="h-72" /></template>
        </Card>
        <Card class="border border-slate-200 shadow-sm">
          <template #title><span class="text-base">Channel Performance</span></template>
          <template #subtitle>Revenue contribution in the last 30 days</template>
          <template #content><Chart type="doughnut" :data="channelData" :options="doughnutOptions" class="h-72" /></template>
        </Card>
      </section>

      <Card class="border border-slate-200 shadow-sm">
        <template #title><span class="text-base">Recent Orders</span></template>
        <template #content>
          <DataTable :value="data.recent_orders" size="small" stripedRows responsiveLayout="scroll">
            <template #empty><div class="py-8 text-center text-sm text-slate-500">No orders recorded for this branch.</div></template>
            <Column field="order_number" header="Order" />
            <Column field="channel" header="Channel"><template #body="{ data: row }"><Badge :value="row.channel" severity="info" /></template></Column>
            <Column field="customer_name" header="Customer"><template #body="{ data: row }">{{ row.customer_name || 'Walk-in customer' }}</template></Column>
            <Column field="status" header="Status"><template #body="{ data: row }"><Tag :value="formatLabel(row.status)" :severity="statusSeverity(row.status)" /></template></Column>
            <Column field="total_amount" header="Total"><template #body="{ data: row }"><span class="font-semibold">{{ money(row.total_amount) }}</span></template></Column>
            <Column field="created_at" header="Date"><template #body="{ data: row }">{{ dateTime(row.created_at) }}</template></Column>
          </DataTable>
        </template>
      </Card>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Chart from 'primevue/chart'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Message from 'primevue/message'
import Skeleton from 'primevue/skeleton'
import Tag from 'primevue/tag'
import { storeModuleService } from '@/services/store-module.service'

const loading = ref(false)
const error = ref('')
const data = ref<any>(null)
const money = (value: any) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value) || 0)
const dateTime = (value: any) => value ? new Date(value).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '—'
const formatLabel = (value: any) => String(value || '—').replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase())
const statusSeverity = (value: any) => ['delivered', 'completed', 'paid'].includes(String(value)) ? 'success' : ['cancelled', 'rejected'].includes(String(value)) ? 'danger' : ['pending', 'processing'].includes(String(value)) ? 'warn' : 'info'

const metrics = computed(() => {
  const kpi = data.value?.kpis || {}
  return [
    { label: 'Sales Today', value: money(kpi.sales_today), caption: `${Number(kpi.orders_today || 0).toLocaleString()} orders today`, icon: 'pi pi-wallet' },
    { label: 'Revenue (30D)', value: money(kpi.revenue_30d), caption: `${Number(kpi.orders_30d || 0).toLocaleString()} total orders`, icon: 'pi pi-chart-line' },
    { label: 'Active Staff', value: Number(kpi.active_staff || 0).toLocaleString(), caption: 'Assigned to this branch', icon: 'pi pi-users' },
    { label: 'Inventory Alerts', value: Number(kpi.low_stock || 0) + Number(kpi.out_of_stock || 0), caption: `${kpi.low_stock || 0} low · ${kpi.out_of_stock || 0} out of stock`, icon: 'pi pi-exclamation-triangle' },
  ]
})
const trendData = computed(() => ({ labels: data.value?.sales_trend?.labels || [], datasets: [{ label: 'Revenue', data: data.value?.sales_trend?.values || [], borderColor: '#f97316', backgroundColor: 'rgba(249,115,22,.12)', fill: true, tension: .35 }] }))
const channelData = computed(() => ({ labels: ['In-store', 'Ecommerce'], datasets: [{ data: [data.value?.kpis?.pos_revenue_30d || 0, data.value?.kpis?.ecommerce_revenue_30d || 0], backgroundColor: ['#0f172a', '#f97316'] }] }))
const lineOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
const doughnutOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, cutout: '66%' }

const load = async () => {
  loading.value = true; error.value = ''
  try { data.value = (await storeModuleService.getDashboard()).data }
  catch (err: any) { error.value = err?.response?.data?.message || 'Unable to load store performance.' }
  finally { loading.value = false }
}
onMounted(load)
</script>
