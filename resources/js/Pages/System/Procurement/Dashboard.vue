<template>
  <div class="module-dashboard dashboard--procurement space-y-5 pb-6 text-sm">
    <div class="dashboard-hero flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-950">Procurement Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">Your purchasing activity and the items waiting for a decision.</p>
      </div>
      <Button label="Refresh" icon="pi pi-refresh" severity="secondary" outlined size="small" :loading="loading" @click="loadDashboard" />
    </div>

    <div v-if="loading" class="space-y-5">
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"><Skeleton v-for="i in 4" :key="i" height="118px" class="rounded-2xl" /></div>
      <div class="grid gap-4 xl:grid-cols-3"><Skeleton height="290px" class="rounded-2xl xl:col-span-2" /><Skeleton height="290px" class="rounded-2xl" /></div>
      <Skeleton v-for="i in 3" :key="i" height="190px" class="rounded-2xl" />
    </div>
    <div v-else-if="loadError" class="dashboard-panel rounded-2xl border border-red-200 bg-white p-6">
      <p class="font-semibold text-slate-900">The procurement dashboard could not load.</p>
      <p class="mt-1 text-slate-500">{{ loadError }}</p>
      <Button label="Try again" severity="warn" size="small" class="mt-4" @click="loadDashboard" />
    </div>
    <template v-else>
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <section class="dashboard-panel rounded-2xl border border-orange-200 bg-white p-5">
          <p class="text-xs font-medium text-slate-500">PO value this month</p>
          <p class="mt-2 text-2xl font-semibold text-slate-950">{{ money(stats.summary?.po_value_this_month) }}</p>
          <p class="mt-2 text-xs" :class="changeTone">{{ changeLabel }}</p>
        </section>
        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <p class="text-xs font-medium text-slate-500">Pending requisitions</p><p class="mt-2 text-2xl font-semibold text-slate-950">{{ stats.summary?.pending_prs || 0 }}</p><p class="mt-2 text-xs text-slate-500">Submitted for review</p>
        </section>
        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <p class="text-xs font-medium text-slate-500">Pending purchase orders</p><p class="mt-2 text-2xl font-semibold text-slate-950">{{ stats.summary?.pending_pos || 0 }}</p><p class="mt-2 text-xs text-slate-500">Draft, approval, or revision needed</p>
        </section>
        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <p class="text-xs font-medium text-slate-500">RFQs with new quotes</p><p class="mt-2 text-2xl font-semibold text-slate-950">{{ stats.summary?.quoted_rfqs || 0 }}</p><p class="mt-2 text-xs text-slate-500">Supplier responses to review</p>
        </section>
      </div>

      <div class="grid gap-4 xl:grid-cols-3">
        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5 xl:col-span-2">
          <div class="flex items-start justify-between gap-2"><div><h2 class="font-semibold text-slate-950">Purchase order value</h2><p class="mt-1 text-xs text-slate-500">Six-month trend, excluding drafts and cancelled orders</p></div><span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-medium text-orange-700">PHP</span></div>
          <Chart type="bar" :data="trendData" :options="barOptions" class="mt-4 h-56" />
        </section>
        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <h2 class="font-semibold text-slate-950">Waiting in the pipeline</h2><p class="mt-1 text-xs text-slate-500">Where procurement needs to follow up</p>
          <div v-if="queueTotal" class="mt-3"><Chart type="doughnut" :data="queueData" :options="doughnutOptions" class="mx-auto h-44 max-w-52" /><div class="mt-2 grid grid-cols-3 gap-1 text-center text-xs"><span class="text-orange-700">PRs <strong>{{ stats.summary?.pending_prs || 0 }}</strong></span><span class="text-sky-700">POs <strong>{{ stats.summary?.pending_pos || 0 }}</strong></span><span class="text-emerald-700">RFQs <strong>{{ stats.summary?.quoted_rfqs || 0 }}</strong></span></div></div>
          <p v-else class="flex h-48 items-center justify-center text-center text-xs text-slate-500">Nothing is waiting for review.</p>
        </section>
      </div>

      <div class="grid gap-4 2xl:grid-cols-2">
        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <div class="mb-4 flex items-center justify-between gap-3"><div><h2 class="font-semibold text-slate-950">Pending PRs</h2><p class="text-xs text-slate-500">Oldest and highest priority first</p></div><Button label="View all" severity="warn" text size="small" @click="go('procurement.purchase-requisitions')" /></div>
          <DataTable :value="stats.pending_prs || []" size="small" rowHover class="text-xs" @row-click="openPr">
            <Column field="number" header="Request"><template #body="{ data }"><span class="font-medium text-slate-900">{{ data.number }}</span><span v-if="data.priority === 1" class="ml-1 text-orange-600">Urgent</span></template></Column>
            <Column field="branch" header="Branch"><template #body="{ data }">{{ data.branch || '-' }}</template></Column>
            <Column field="amount" header="Amount"><template #body="{ data }">{{ money(data.amount) }}</template></Column>
            <Column field="date" header="Submitted"><template #body="{ data }">{{ date(data.date) }}</template></Column>
            <Column header="Action" headerClass="text-right" bodyClass="text-right"><template #body="{ data }"><Button label="View" severity="warn" text size="small" @click.stop="go('procurement.purchase-requisitions.detail', data.id)" /></template></Column>
            <template #empty><div class="py-7 text-center text-xs text-slate-500">No requisitions are waiting for review.</div></template>
          </DataTable>
        </section>

        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <div class="mb-4 flex items-center justify-between gap-3"><div><h2 class="font-semibold text-slate-950">Pending POs</h2><p class="text-xs text-slate-500">Drafts, approvals, and requested revisions</p></div><Button label="View all" severity="warn" text size="small" @click="go('procurement.purchase-orders')" /></div>
          <DataTable :value="stats.pending_pos || []" size="small" rowHover class="text-xs" @row-click="openPo">
            <Column field="number" header="Order"><template #body="{ data }"><span class="font-medium text-slate-900">{{ data.number }}</span></template></Column>
            <Column field="supplier" header="Supplier"><template #body="{ data }">{{ data.supplier || '-' }}</template></Column>
            <Column field="amount" header="Amount"><template #body="{ data }">{{ money(data.amount) }}</template></Column>
            <Column field="status" header="Stage"><template #body="{ data }"><Badge :value="statusLabel(data.status)" :severity="data.status === 'draft' ? 'secondary' : 'warn'" /></template></Column>
            <Column header="Action" headerClass="text-right" bodyClass="text-right"><template #body="{ data }"><Button label="View" severity="warn" text size="small" @click.stop="go('procurement.purchase-orders.detail', data.id)" /></template></Column>
            <template #empty><div class="py-7 text-center text-xs text-slate-500">No purchase orders are pending.</div></template>
          </DataTable>
        </section>
      </div>

      <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-center justify-between gap-3"><div><h2 class="font-semibold text-slate-950">RFQs with supplier quotes</h2><p class="text-xs text-slate-500">Responses waiting for procurement review</p></div><Button label="View all" severity="warn" text size="small" @click="go('procurement.rfqs')" /></div>
        <DataTable :value="stats.quoted_rfqs || []" size="small" rowHover class="text-xs" @row-click="openRfq">
          <Column field="number" header="RFQ"><template #body="{ data }"><span class="font-medium text-slate-900">{{ data.number }}</span></template></Column>
          <Column field="title" header="Title"><template #body="{ data }">{{ data.title || '-' }}</template></Column>
          <Column field="quotes" header="Suppliers quoted"><template #body="{ data }"><Badge :value="data.quotes" severity="info" /></template></Column>
          <Column field="last_quote_at" header="Last quote"><template #body="{ data }">{{ date(data.last_quote_at) }}</template></Column>
          <Column header="Action" headerClass="text-right" bodyClass="text-right"><template #body="{ data }"><Button label="View" severity="warn" text size="small" @click.stop="go('procurement.rfqs.detail', data.id)" /></template></Column>
          <template #empty><div class="py-7 text-center text-xs text-slate-500">No new supplier quotes to review.</div></template>
        </DataTable>
      </section>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import procurementService from '../../../services/procurement.service'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

type QueueItem = { id: number; number: string; [key: string]: any }
type DashboardStats = { summary?: Record<string, number>; po_trend?: { label: string; value: number }[]; pending_prs?: QueueItem[]; pending_pos?: QueueItem[]; quoted_rfqs?: QueueItem[] }

const router = useRouter()
const loading = ref(true)
const loadError = ref('')
const stats = ref<DashboardStats>({})
const money = (value: unknown) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 2 }).format(Number(value) || 0)
const date = (value: unknown) => value ? new Intl.DateTimeFormat('en-PH', { month: 'short', day: 'numeric', year: 'numeric', timeZone: 'Asia/Manila' }).format(new Date(String(value) + 'T12:00:00+08:00')) : '-'
const go = (name: string, id?: number) => router.push(id ? { name, params: { id } } : { name })
const openPr = (event: { data: QueueItem }) => go('procurement.purchase-requisitions.detail', event.data.id)
const openPo = (event: { data: QueueItem }) => go('procurement.purchase-orders.detail', event.data.id)
const openRfq = (event: { data: QueueItem }) => go('procurement.rfqs.detail', event.data.id)
const statusLabel = (status: string) => status === 'draft' ? 'Draft' : status === 'revision_requested' ? 'Revision needed' : 'Finance approval'
const current = computed(() => Number(stats.value.summary?.po_value_this_month || 0))
const previous = computed(() => Number(stats.value.summary?.po_value_previous_month || 0))
const changeLabel = computed(() => !previous.value ? (current.value ? 'New activity compared with last month' : 'No PO value this or last month') : `${current.value >= previous.value ? '+' : ''}${(((current.value - previous.value) / previous.value) * 100).toFixed(1)}% vs previous month`)
const changeTone = computed(() => current.value >= previous.value ? 'text-emerald-700' : 'text-orange-700')
const queueTotal = computed(() => Number(stats.value.summary?.pending_prs || 0) + Number(stats.value.summary?.pending_pos || 0) + Number(stats.value.summary?.quoted_rfqs || 0))
const trendData = computed(() => ({ labels: (stats.value.po_trend || []).map(item => item.label), datasets: [{ label: 'PO value', data: (stats.value.po_trend || []).map(item => item.value), backgroundColor: '#fdba74', hoverBackgroundColor: '#ea580c', borderRadius: 7, maxBarThickness: 44 }] }))
const queueData = computed(() => ({ labels: ['Pending PRs', 'Pending POs', 'RFQs with quotes'], datasets: [{ data: [stats.value.summary?.pending_prs || 0, stats.value.summary?.pending_pos || 0, stats.value.summary?.quoted_rfqs || 0], backgroundColor: ['#f97316', '#38bdf8', '#34d399'], borderWidth: 0 }] }))
const barOptions = {
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: { callbacks: { label: (context: any) => money(context.parsed.y) } },
  },
  scales: {
    x: { grid: { display: false }, ticks: { color: '#64748b' } },
    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#64748b', callback: (value: number) => `PHP ${new Intl.NumberFormat('en-PH', { notation: 'compact' }).format(value)}` } },
  },
}
const doughnutOptions = { maintainAspectRatio: false, cutout: '74%', plugins: { legend: { display: false } } }

const loadDashboard = async () => {
  loading.value = true
  loadError.value = ''
  try {
    const response = await procurementService.getDashboardStats()
    if (response?.success === false) throw new Error(response.message || 'Please try again.')
    stats.value = response?.data || response || {}
  } catch (error: any) {
    stats.value = {}
    loadError.value = error?.response?.data?.message || error?.message || 'Please try again.'
  } finally {
    loading.value = false
  }
}

onMounted(loadDashboard)
</script>

<style scoped>
:deep(.p-datatable-tbody > tr) { cursor: pointer; }
</style>
