<template>
  <div class="module-dashboard dashboard--merchandising space-y-5 pb-6 text-sm">
    <div class="dashboard-hero flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <p class="dashboard-eyebrow">Product operations</p>
        <h1 class="mt-1 text-2xl font-semibold text-slate-950">Merchandising Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">Keep product pricing current and customer facing listings ready to shop.</p>
      </div>
      <Button label="Refresh" icon="pi pi-refresh" severity="secondary" outlined size="small" :loading="loading" @click="loadDashboard" />
    </div>

    <div v-if="loading" class="space-y-5">
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <Skeleton v-for="index in 4" :key="index" height="118px" class="rounded-2xl" />
      </div>
      <div class="grid gap-4 xl:grid-cols-3">
        <Skeleton height="285px" class="rounded-2xl xl:col-span-2" />
        <Skeleton height="285px" class="rounded-2xl" />
      </div>
      <div class="grid gap-4 xl:grid-cols-2">
        <Skeleton v-for="index in 2" :key="index" height="220px" class="rounded-2xl" />
      </div>
    </div>
    <div v-else-if="loadError" class="dashboard-panel rounded-2xl border border-red-200 bg-white p-6">
      <p class="font-semibold text-slate-900">The merchandising dashboard could not load.</p>
      <p class="mt-1 text-slate-500">{{ loadError }}</p>
      <Button label="Try again" severity="warn" size="small" class="mt-4" @click="loadDashboard" />
    </div>
    <template v-else>
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <section class="dashboard-panel rounded-2xl border border-pink-200 bg-white p-5">
          <p class="text-xs font-medium text-slate-500">New catalog items this month</p>
          <p class="mt-2 text-2xl font-semibold text-slate-950">{{ summary.new_products_this_month || 0 }}</p>
          <p class="mt-2 text-xs" :class="monthChangeTone">{{ monthChangeLabel }}</p>
        </section>
        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <p class="text-xs font-medium text-slate-500">Active finished goods</p>
          <p class="mt-2 text-2xl font-semibold text-slate-950">{{ summary.active_products || 0 }}</p>
          <p class="mt-2 text-xs text-slate-500">Available in your product catalog</p>
        </section>
        <section class="dashboard-panel rounded-2xl border border-orange-200 bg-white p-5">
          <p class="text-xs font-medium text-slate-500">Price changes to review</p>
          <p class="mt-2 text-2xl font-semibold text-slate-950">{{ summary.pending_price_approvals || 0 }}</p>
          <p class="mt-2 text-xs text-orange-700">Pending approval before prices update</p>
        </section>
        <section class="dashboard-panel rounded-2xl border border-amber-200 bg-white p-5">
          <p class="text-xs font-medium text-slate-500">Active products missing main image</p>
          <p class="mt-2 text-2xl font-semibold text-slate-950">{{ summary.missing_main_images || 0 }}</p>
          <p class="mt-2 text-xs text-amber-700">Add a photo to improve shopping listings</p>
        </section>
      </div>

      <div class="grid gap-4 xl:grid-cols-3">
        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5 xl:col-span-2">
          <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
              <h2 class="font-semibold text-slate-950">Catalog additions</h2>
              <p class="mt-1 text-xs text-slate-500">Finished goods created over the last six months</p>
            </div>
            <Button label="View products" severity="secondary" text size="small" @click="go('merchandising.products')" />
          </div>
          <Chart v-if="trendData.labels.length" type="bar" :data="trendData" :options="chartOptions" class="mt-4 h-56" />
          <p v-else class="flex h-56 items-center justify-center text-xs text-slate-500">No catalog additions in this period.</p>
        </section>
        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <h2 class="font-semibold text-slate-950">Main image readiness</h2>
          <p class="mt-1 text-xs text-slate-500">Active products with a primary product image</p>
          <div v-if="summary.active_products" class="mt-3">
            <Chart type="doughnut" :data="readinessData" :options="doughnutOptions" class="mx-auto h-44 max-w-52" />
            <div class="mt-2 grid grid-cols-2 gap-2 text-center text-xs">
              <span class="text-emerald-700">Image ready <strong>{{ summary.image_ready_products || 0 }}</strong></span>
              <span class="text-amber-700">Needs image <strong>{{ summary.missing_main_images || 0 }}</strong></span>
            </div>
          </div>
          <p v-else class="flex h-48 items-center justify-center text-center text-xs text-slate-500">Add finished goods to begin tracking catalog readiness.</p>
        </section>
      </div>

      <div class="grid gap-4 xl:grid-cols-2">
        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <div class="mb-4 flex items-start justify-between gap-3">
            <div><h2 class="font-semibold text-slate-950">Price changes awaiting review</h2><p class="text-xs text-slate-500">Review proposed prices before they go live</p></div>
            <Button label="View products" severity="secondary" text size="small" @click="go('merchandising.products')" />
          </div>
          <DataTable :value="data.pending_prices || []" size="small" rowHover class="text-xs" @row-click="openPriceReview">
            <Column header="Product">
              <template #body="{ data: item }"><p class="font-medium text-slate-900">{{ item.name }}</p><p class="text-[10px] text-slate-500">{{ item.sku }}</p></template>
            </Column>
            <Column field="category" header="Category">
              <template #body="{ data: item }">{{ item.category || 'Uncategorized' }}</template>
            </Column>
            <Column field="pending_base_price" header="Proposed price">
              <template #body="{ data: item }"><span v-if="item.pending_base_price != null" class="font-medium text-slate-800">{{ money(item.pending_base_price) }}</span><span v-else class="text-slate-400">No base price change</span></template>
            </Column>
            <Column header="Action" headerClass="text-right" bodyClass="text-right">
              <template #body="{ data: item }"><Button label="Review" severity="warn" text size="small" @click.stop="openPriceReview(item)" /></template>
            </Column>
            <template #empty><div class="py-7 text-center text-xs text-slate-500">No price changes are waiting for review.</div></template>
          </DataTable>
        </section>

        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <div class="mb-4 flex items-start justify-between gap-3">
            <div><h2 class="font-semibold text-slate-950">Products missing a main image</h2><p class="text-xs text-slate-500">Active listings that need a product photo</p></div>
            <Button label="View products" severity="secondary" text size="small" @click="go('merchandising.products')" />
          </div>
          <DataTable :value="data.missing_images || []" size="small" rowHover class="text-xs" @row-click="openProductEditor">
            <Column header="Product">
              <template #body="{ data: item }"><p class="font-medium text-slate-900">{{ item.name }}</p><p class="text-[10px] text-slate-500">{{ item.sku }}</p></template>
            </Column>
            <Column field="category" header="Category">
              <template #body="{ data: item }">{{ item.category || 'Uncategorized' }}</template>
            </Column>
            <Column field="created_at" header="Added">
              <template #body="{ data: item }">{{ shortDate(item.created_at) }}</template>
            </Column>
            <Column header="Action" headerClass="text-right" bodyClass="text-right">
              <template #body="{ data: item }"><Button label="Add image" severity="warn" text size="small" @click.stop="openProductEditor(item)" /></template>
            </Column>
            <template #empty><div class="py-7 text-center text-xs text-slate-500">Every active product has a main image.</div></template>
          </DataTable>
        </section>
      </div>

      <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-start justify-between gap-3">
          <div><h2 class="font-semibold text-slate-950">Recently added products</h2><p class="text-xs text-slate-500">Latest catalog entries to check for completeness and pricing</p></div>
          <Button label="View all" severity="secondary" text size="small" @click="go('merchandising.products')" />
        </div>
        <DataTable :value="data.recent_products || []" size="small" rowHover class="text-xs" @row-click="openProduct">
          <Column header="Product">
            <template #body="{ data: item }"><p class="font-medium text-slate-900">{{ item.name }}</p><p class="text-[10px] text-slate-500">{{ item.sku }}</p></template>
          </Column>
          <Column field="category" header="Category">
            <template #body="{ data: item }">{{ item.category || 'Uncategorized' }}</template>
          </Column>
          <Column field="base_price" header="Price">
            <template #body="{ data: item }">{{ money(item.base_price) }}</template>
          </Column>
          <Column field="created_at" header="Added">
            <template #body="{ data: item }">{{ shortDate(item.created_at) }}</template>
          </Column>
          <Column header="Action" headerClass="text-right" bodyClass="text-right">
            <template #body="{ data: item }"><Button label="View" severity="secondary" text size="small" @click.stop="openProduct(item)" /></template>
          </Column>
          <template #empty><div class="py-7 text-center text-xs text-slate-500">No products have been added yet.</div></template>
        </DataTable>
      </section>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import merchandisingService from '../../../services/merchandising.service'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

type ProductRow = { id: number; [key: string]: any }
type DashboardData = {
  summary?: Record<string, number>
  catalog_trend?: { label: string; value: number }[]
  pending_prices?: ProductRow[]
  missing_images?: ProductRow[]
  recent_products?: ProductRow[]
}

const router = useRouter()
const loading = ref(true)
const loadError = ref('')
const data = ref<DashboardData>({})
const summary = computed(() => data.value.summary || {})

const money = (value: unknown) => new Intl.NumberFormat('en-PH', {
  style: 'currency', currency: 'PHP', maximumFractionDigits: 2,
}).format(Number(value) || 0)
const shortDate = (value: unknown) => value
  ? new Intl.DateTimeFormat('en-PH', { month: 'short', day: 'numeric', timeZone: 'Asia/Manila' }).format(new Date(String(value).slice(0, 10) + 'T12:00:00+08:00'))
  : '—'
const go = (name: string, id?: number) => router.push(id ? { name, params: { id } } : { name })
const openProduct = (event: { data: ProductRow }) => go('merchandising.products.view', event.data.id)
const openProductEditor = (event: { data: ProductRow } | ProductRow) => {
  const product = 'data' in event ? event.data : event
  go('merchandising.products.edit', product.id)
}
const openPriceReview = (event: { data: ProductRow } | ProductRow) => {
  const product = 'data' in event ? event.data : event
  router.push({ name: 'merchandising.products', query: { search: product.sku } })
}

const currentMonth = computed(() => Number(summary.value.new_products_this_month || 0))
const previousMonth = computed(() => Number(summary.value.new_products_previous_month || 0))
const monthChangeLabel = computed(() => {
  if (!previousMonth.value) return currentMonth.value ? 'New additions compared with last month' : 'No additions this or last month'
  const change = ((currentMonth.value - previousMonth.value) / previousMonth.value) * 100
  return `${change >= 0 ? '+' : ''}${change.toFixed(1)}% vs previous month`
})
const monthChangeTone = computed(() => currentMonth.value >= previousMonth.value ? 'text-emerald-700' : 'text-orange-700')
const trendData = computed(() => ({
  labels: (data.value.catalog_trend || []).map(item => item.label),
  datasets: [{
    label: 'New products',
    data: (data.value.catalog_trend || []).map(item => item.value),
    backgroundColor: '#f9a8d4',
    hoverBackgroundColor: '#db2777',
    borderRadius: 7,
    maxBarThickness: 44,
  }],
}))
const readinessData = computed(() => ({
  labels: ['Image ready', 'Missing image'],
  datasets: [{
    data: [summary.value.image_ready_products || 0, summary.value.missing_main_images || 0],
    backgroundColor: ['#34d399', '#fbbf24'],
    borderWidth: 0,
  }],
}))
const chartOptions = {
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: { callbacks: { label: (context: any) => `${context.parsed.y} new products` } },
  },
  scales: {
    x: { grid: { display: false }, ticks: { color: '#64748b' } },
    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#64748b', precision: 0 } },
  },
}
const doughnutOptions = { maintainAspectRatio: false, cutout: '74%', plugins: { legend: { display: false } } }

const loadDashboard = async () => {
  loading.value = true
  loadError.value = ''
  try {
    const response = await merchandisingService.getDashboardOverview()
    if (response?.success === false) throw new Error(response.message || 'Please try again.')
    data.value = response?.data || {}
  } catch (error: any) {
    data.value = {}
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
