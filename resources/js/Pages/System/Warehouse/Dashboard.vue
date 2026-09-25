<template>
  <div class="module-dashboard dashboard--warehouse space-y-5 pb-6 text-sm">
    <div class="dashboard-hero flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <p class="dashboard-eyebrow">Stock operations</p>
        <h1 class="mt-1 text-2xl font-semibold text-slate-950">Warehouse Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">See what needs replenishment, which transfers need attention, and how stock is distributed.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <Button label="Refresh" icon="pi pi-refresh" severity="secondary" outlined size="small" :loading="loading" @click="loadDashboard" />
        <Button label="Create purchase requisition" icon="pi pi-plus" severity="warn" size="small" @click="go('warehouse.purchase-requisitions.create')" />
      </div>
    </div>

    <div v-if="loading" class="space-y-5">
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"><Skeleton v-for="i in 4" :key="i" height="118px" class="rounded-2xl" /></div>
      <div class="grid gap-4 xl:grid-cols-3"><Skeleton height="285px" class="rounded-2xl xl:col-span-2" /><Skeleton height="285px" class="rounded-2xl" /></div>
      <div class="grid gap-4 xl:grid-cols-2"><Skeleton v-for="i in 2" :key="i" height="220px" class="rounded-2xl" /></div>
    </div>

    <div v-else-if="loadError" class="dashboard-panel rounded-2xl border border-red-200 bg-white p-6">
      <p class="font-semibold text-slate-900">The warehouse dashboard could not load.</p>
      <p class="mt-1 text-slate-500">{{ loadError }}</p>
      <Button label="Try again" severity="warn" size="small" class="mt-4" @click="loadDashboard" />
    </div>

    <template v-else>
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <p class="text-xs font-medium text-slate-500">Active warehouses</p>
          <p class="mt-2 text-2xl font-semibold text-slate-950">{{ summary.active_warehouses || 0 }} <span class="text-sm font-normal text-slate-400">/ {{ summary.warehouses || 0 }}</span></p>
          <p class="mt-2 text-xs text-slate-500">Locations available to the store</p>
        </section>
        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <p class="text-xs font-medium text-slate-500">Stocked products</p>
          <p class="mt-2 text-2xl font-semibold text-slate-950">{{ formatNumber(summary.total_skus) }}</p>
          <p class="mt-2 text-xs text-slate-500">{{ formatNumber(summary.quantity_on_hand) }} total units on hand</p>
        </section>
        <section class="dashboard-panel rounded-2xl border border-orange-200 bg-white p-5">
          <p class="text-xs font-medium text-slate-500">Stock needing replenishment</p>
          <p class="mt-2 text-2xl font-semibold text-slate-950">{{ Number(summary.low_stock || 0) + Number(summary.out_of_stock || 0) }}</p>
          <p class="mt-2 text-xs text-orange-700">{{ summary.out_of_stock || 0 }} out of stock, {{ summary.low_stock || 0 }} running low</p>
        </section>
        <section class="dashboard-panel rounded-2xl border border-sky-200 bg-white p-5">
          <p class="text-xs font-medium text-slate-500">Transfer requests</p>
          <p class="mt-2 text-2xl font-semibold text-slate-950">{{ summary.pending_transfers || 0 }}</p>
          <p class="mt-2 text-xs text-sky-700">Waiting for warehouse review</p>
        </section>
      </div>

      <div class="grid gap-4 xl:grid-cols-3">
        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5 xl:col-span-2">
          <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
              <h2 class="font-semibold text-slate-950">Stock by warehouse</h2>
              <p class="mt-1 text-xs text-slate-500">Compare units on hand across active warehouse locations</p>
            </div>
            <Button label="Manage warehouses" severity="secondary" text size="small" @click="go('warehouse.warehouses')" />
          </div>
          <Chart v-if="warehouseChart.labels.length" type="bar" :data="warehouseChart" :options="chartOptions" class="mt-4 h-56" />
          <p v-else class="flex h-56 items-center justify-center text-xs text-slate-500">No warehouse stock data is available yet.</p>
        </section>
        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <h2 class="font-semibold text-slate-950">Store stock health</h2>
          <p class="mt-1 text-xs text-slate-500">SKUs grouped by their current stock status</p>
          <div v-if="stockCount" class="mt-3">
            <Chart type="doughnut" :data="stockHealthData" :options="doughnutOptions" class="mx-auto h-44 max-w-52" />
            <div class="mt-2 grid grid-cols-3 gap-1 text-center text-xs">
              <span class="text-emerald-700">Healthy <strong>{{ summary.in_stock || 0 }}</strong></span>
              <span class="text-orange-700">Low <strong>{{ summary.low_stock || 0 }}</strong></span>
              <span class="text-red-700">Out <strong>{{ summary.out_of_stock || 0 }}</strong></span>
            </div>
          </div>
          <p v-else class="flex h-48 items-center justify-center text-center text-xs text-slate-500">Add stock records to see warehouse health.</p>
        </section>
      </div>

      <div class="grid gap-4 xl:grid-cols-2">
        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <div class="mb-4 flex items-start justify-between gap-3">
            <div><h2 class="font-semibold text-slate-950">Replenishment priorities</h2><p class="text-xs text-slate-500">Out-of-stock and low-stock items across warehouse branches</p></div>
            <Button label="View stock" severity="secondary" text size="small" @click="go('warehouse.stock')" />
          </div>
          <DataTable :value="data.low_stock_items || []" size="small" rowHover class="text-xs" @row-click="openStock">
            <Column header="Product">
              <template #body="{ data: row }"><p class="font-medium text-slate-900">{{ row.variation?.variation_name || row.product?.product_name || 'Item' }}</p><p class="text-[10px] text-slate-500">{{ row.variation?.variation_sku || row.product?.sku || 'No SKU' }}</p></template>
            </Column>
            <Column header="Warehouse"><template #body="{ data: row }">{{ row.branch?.name || row.branch?.branch_name || 'Warehouse' }}</template></Column>
            <Column field="quantity_available" header="Available" />
            <Column header="Status"><template #body="{ data: row }"><Badge :value="formatLabel(row.stock_status)" :severity="stockSeverity(row.stock_status)" /></template></Column>
            <Column header="Action" headerClass="text-right" bodyClass="text-right"><template #body="{ data: row }"><Button label="Open" severity="warn" text size="small" @click.stop="openStock(row)" /></template></Column>
            <template #empty><div class="py-7 text-center text-xs text-slate-500">No warehouse items need replenishment.</div></template>
          </DataTable>
        </section>

        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <div class="mb-4 flex items-start justify-between gap-3">
            <div><h2 class="font-semibold text-slate-950">Transfer requests to review</h2><p class="text-xs text-slate-500">Check requested quantities and approve or reject each transfer</p></div>
            <Button label="View all" severity="secondary" text size="small" @click="go('warehouse.transfer-requests')" />
          </div>
          <DataTable :value="transferRequests" size="small" rowHover class="text-xs" @row-click="openTransferRequest">
            <Column field="transfer_number" header="Request"><template #body="{ data: row }"><span class="font-medium text-slate-900">{{ row.transfer_number || `TR-${row.id}` }}</span></template></Column>
            <Column header="Route"><template #body="{ data: row }">{{ branchName(row.from_branch) }} <span class="text-slate-400">to</span> {{ branchName(row.to_branch) }}</template></Column>
            <Column field="total_quantity" header="Units" />
            <Column header="Requested by"><template #body="{ data: row }">{{ row.requested_by_name || 'Team member' }}</template></Column>
            <Column header="Action" headerClass="text-right" bodyClass="text-right"><template #body="{ data: row }"><Button label="Review" severity="warn" text size="small" @click.stop="openTransferRequest(row)" /></template></Column>
            <template #empty><div class="py-7 text-center text-xs text-slate-500">No transfer requests are waiting for a decision.</div></template>
          </DataTable>
        </section>
      </div>

      <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-start justify-between gap-3">
          <div><h2 class="font-semibold text-slate-950">Recent transfers</h2><p class="text-xs text-slate-500">Track the latest stock movement between branches</p></div>
          <Button label="Transfer history" severity="secondary" text size="small" @click="go('warehouse.transfer-history')" />
        </div>
        <DataTable :value="data.recent_transfers || []" size="small" rowHover class="text-xs" @row-click="openTransfer">
          <Column field="transfer_number" header="Transfer"><template #body="{ data: row }"><span class="font-medium text-slate-900">{{ row.transfer_number || `TR-${row.id}` }}</span></template></Column>
          <Column header="Route"><template #body="{ data: row }">{{ branchName(row.from_branch) }} <span class="text-slate-400">to</span> {{ branchName(row.to_branch) }}</template></Column>
          <Column field="total_items" header="Items" />
          <Column field="total_quantity" header="Units" />
          <Column header="Status"><template #body="{ data: row }"><Badge :value="formatLabel(row.status)" :severity="statusSeverity(row.status)" /></template></Column>
          <Column header="Action" headerClass="text-right" bodyClass="text-right"><template #body="{ data: row }"><Button label="View" severity="secondary" text size="small" @click.stop="openTransfer(row)" /></template></Column>
          <template #empty><div class="py-7 text-center text-xs text-slate-500">No transfer activity yet.</div></template>
        </DataTable>
      </section>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import WarehouseService from '@/services/warehouse.service'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

type Row = { id: number; [key: string]: any }

const router = useRouter()
const loading = ref(true)
const loadError = ref('')
const data = ref<any>({ summary: {}, low_stock_items: [], recent_transfers: [] })
const warehouses = ref<Row[]>([])
const transferRequests = ref<Row[]>([])
const summary = computed(() => data.value.summary || {})
const stockCount = computed(() => Number(summary.value.in_stock || 0) + Number(summary.value.low_stock || 0) + Number(summary.value.out_of_stock || 0))

const warehouseChart = computed(() => ({
  labels: warehouses.value.slice(0, 8).map(warehouse => warehouse.name || warehouse.warehouse_code || 'Warehouse'),
  datasets: [{
    label: 'Units on hand',
    data: warehouses.value.slice(0, 8).map(warehouse => Number(warehouse.quantity_on_hand || 0)),
    backgroundColor: '#fdba74',
    hoverBackgroundColor: '#ea580c',
    borderRadius: 7,
    maxBarThickness: 44,
  }],
}))
const stockHealthData = computed(() => ({
  labels: ['In stock', 'Low stock', 'Out of stock'],
  datasets: [{
    data: [summary.value.in_stock || 0, summary.value.low_stock || 0, summary.value.out_of_stock || 0],
    backgroundColor: ['#34d399', '#fb923c', '#f87171'],
    borderWidth: 0,
  }],
}))
const chartOptions = {
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { display: false }, ticks: { color: '#64748b' } },
    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#64748b', precision: 0 } },
  },
}
const doughnutOptions = { maintainAspectRatio: false, cutout: '74%', plugins: { legend: { display: false } } }

const formatNumber = (value: unknown) => Number(value || 0).toLocaleString('en-PH')
const formatLabel = (value?: string) => String(value || 'unknown').replaceAll('_', ' ').replace(/\b\w/g, letter => letter.toUpperCase())
const branchName = (branch: any) => branch?.name || branch?.branch_name || 'Branch'
const stockSeverity = (status: string) => status === 'out_of_stock' ? 'danger' : status === 'low_stock' ? 'warn' : 'success'
const statusSeverity = (status: string) => status === 'received' ? 'success' : ['rejected', 'cancelled'].includes(status) ? 'danger' : status === 'in_transit' ? 'info' : 'warn'
const go = (name: string, id?: number) => router.push(id ? { name, params: { id } } : { name })
const openStock = (event: { data: Row } | Row) => {
  const row = 'data' in event ? event.data : event
  go('warehouse.stock.view', row.id)
}
const openTransferRequest = (event: { data: Row } | Row) => {
  const row = 'data' in event ? event.data : event
  go('warehouse.transfer-requests.view', row.id)
}
const openTransfer = (event: { data: Row } | Row) => {
  const row = 'data' in event ? event.data : event
  go('warehouse.transfer-requests.view', row.id)
}

const loadDashboard = async () => {
  loading.value = true
  loadError.value = ''
  try {
    const [dashboardResult, warehouseResult, requestResult] = await Promise.all([
      WarehouseService.dashboard(),
      WarehouseService.warehouses({ per_page: 8, status: 'active' }).catch(() => null),
              WarehouseService.transferRequests({ per_page: 5, status: 'requested' }).catch(() => null),
    ])
    data.value = dashboardResult || { summary: {}, low_stock_items: [], recent_transfers: [] }
    warehouses.value = Array.isArray(warehouseResult?.data) ? warehouseResult.data : []
    transferRequests.value = Array.isArray(requestResult?.data?.data) ? requestResult.data.data : []
  } catch (error: any) {
    data.value = { summary: {}, low_stock_items: [], recent_transfers: [] }
    warehouses.value = []
    transferRequests.value = []
    loadError.value = error?.response?.data?.message || error?.message || 'Please try again.'
  } finally {
    loading.value = false
  }
}

onMounted(loadDashboard)
</script>

<style scoped>
:deep(.p-datatable-tbody > tr) {
  cursor: pointer;
}
</style>
