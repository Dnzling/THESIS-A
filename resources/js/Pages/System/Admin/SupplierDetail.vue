<template>
  <div class="mx-auto max-w-7xl space-y-5 pb-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button label="Go Back" icon="pi pi-arrow-left" outlined rounded size="small" severity="secondary" @click="goBack" />
        <div>
          <h1 class="text-xl font-semibold text-slate-900">{{ supplier?.supplier_name || 'Supplier details' }}</h1>
          <p class="mt-1 text-sm text-slate-500">{{ supplier?.company_name || supplier?.store?.name || 'Platform supplier record' }}</p>
        </div>
      </div>
      <Badge v-if="supplier" :value="title(supplier.status)" :severity="statusSeverity(supplier.status)" />
    </div>

    <div v-if="loading" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"><Skeleton v-for="item in 4" :key="item" height="6rem" /></div>
    <template v-else-if="supplier">
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <Card v-for="metric in metricCards" :key="metric.label" class="border border-slate-200 shadow-none">
          <template #content><p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ metric.label }}</p><p class="mt-2 text-2xl font-semibold text-slate-900">{{ metric.value }}</p></template>
        </Card>
      </div>

      <div class="grid gap-5 lg:grid-cols-3">
        <Card class="border border-slate-200 shadow-none lg:col-span-1">
          <template #title><span class="text-base">Supplier information</span></template>
          <template #content>
            <dl class="space-y-4 text-sm">
              <div><dt class="text-xs text-slate-500">Contact person</dt><dd class="mt-1 font-medium text-slate-900">{{ supplier.contact_person || 'Not provided' }}</dd></div>
              <div><dt class="text-xs text-slate-500">Email / phone</dt><dd class="mt-1 break-all text-slate-900">{{ supplier.email || 'No email' }}<span class="block">{{ supplier.phone || 'No phone' }}</span></dd></div>
              <div><dt class="text-xs text-slate-500">Location</dt><dd class="mt-1 text-slate-900">{{ [supplier.city, supplier.province].filter(Boolean).join(', ') || 'Not provided' }}</dd></div>
              <div><dt class="text-xs text-slate-500">Registered by</dt><dd class="mt-1 text-slate-900">{{ supplier.store?.name || 'Store not linked' }}</dd></div>
              <div><dt class="text-xs text-slate-500">Supplier type</dt><dd class="mt-1 text-slate-900">{{ title(supplier.supplier_type) }}</dd></div>
            </dl>
          </template>
        </Card>

        <Card class="border border-slate-200 shadow-none lg:col-span-2">
          <template #title><span class="text-base">Overall performance</span></template>
          <template #content>
            <div class="grid gap-3 sm:grid-cols-2">
              <div v-for="item in scoreCards" :key="item.label" class="rounded-lg border border-slate-200 p-3">
                <div class="flex items-center justify-between gap-2"><span class="text-sm text-slate-600">{{ item.label }}</span><strong class="text-slate-900">{{ item.value }}</strong></div>
                <ProgressBar :value="item.progress" :show-value="false" class="mt-3 h-2" />
              </div>
            </div>
            <p class="mt-4 text-xs text-slate-500">Performance is based on recorded purchase and goods-receipt evaluations.</p>
          </template>
        </Card>
      </div>

      <Card class="border border-slate-200 shadow-none">
        <template #title><span class="text-base">Recent purchase orders</span></template>
        <template #content>
          <DataTable :value="data.recent_orders || []" data-key="id" row-hover responsive-layout="scroll" class="text-sm">
            <template #empty><p class="py-5 text-center text-sm text-slate-500">No purchase orders are recorded for this supplier.</p></template>
            <Column field="po_number" header="Reference" />
            <Column field="order_date" header="Order date" />
            <Column field="status" header="Status"><template #body="{ data: row }"><Badge :value="title(row.status)" :severity="statusSeverity(row.status)" /></template></Column>
            <Column field="total_amount" header="Amount"><template #body="{ data: row }">{{ currency(row.total_amount) }}</template></Column>
          </DataTable>
        </template>
      </Card>

      <div class="grid gap-5 lg:grid-cols-2">
        <Card class="border border-slate-200 shadow-none">
          <template #title><span class="text-base">Linked products</span></template>
          <template #content>
            <DataTable :value="data.products || []" data-key="id" row-hover responsive-layout="scroll" class="text-sm">
              <template #empty><p class="py-5 text-center text-sm text-slate-500">No products linked.</p></template>
              <Column field="name" header="Product" />
              <Column field="sku" header="SKU" />
            </DataTable>
          </template>
        </Card>
        <Card class="border border-slate-200 shadow-none">
          <template #title><span class="text-base">Supplier contracts</span></template>
          <template #content>
            <DataTable :value="data.contracts || []" data-key="id" row-hover responsive-layout="scroll" class="text-sm">
              <template #empty><p class="py-5 text-center text-sm text-slate-500">No supplier contracts recorded.</p></template>
              <Column field="contract_title" header="Contract" />
              <Column field="start_date" header="Start" />
              <Column field="end_date" header="End" />
              <Column field="status" header="Status"><template #body="{ data: row }"><Badge :value="title(row.status)" :severity="statusSeverity(row.status)" /></template></Column>
            </DataTable>
          </template>
        </Card>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axiosClient from '@/axios'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import ProgressBar from 'primevue/progressbar'
import Skeleton from 'primevue/skeleton'

const route = useRoute()
const router = useRouter()
const loading = ref(true)
const data = ref<any>({})
const supplier = computed(() => data.value.supplier)
const performance = computed(() => data.value.performance || {})
const evaluations = computed(() => performance.value.evaluation_averages || {})
const metricCards = computed(() => [
  { label: 'Total purchase value', value: currency(performance.value.total_amount_purchased) },
  { label: 'Purchase orders', value: Number(performance.value.total_orders || 0).toLocaleString() },
  { label: 'Supplier rating', value: `${Number(performance.value.rating || 0).toFixed(1)} / 5` },
  { label: 'On-time delivery', value: `${Number(performance.value.on_time_delivery_rate || 0).toFixed(1)}%` },
])
const scoreCards = computed(() => [
  { label: 'Overall evaluation', score: evaluations.value.overall },
  { label: 'Product quality', score: evaluations.value.quality },
  { label: 'Quantity accuracy', score: evaluations.value.quantity_accuracy },
  { label: 'Delivery timeliness', score: evaluations.value.delivery_timeliness },
].map((item) => ({ ...item, value: item.score == null ? 'Not rated' : `${Number(item.score).toFixed(2)} / 5`, progress: item.score == null ? 0 : Math.min(100, Number(item.score) * 20) })))

async function load() {
  loading.value = true
  try {
    const response = await axiosClient.get(`/api/admin/supplier-directory/${route.params.id}`)
    data.value = response.data?.data || {}
  } finally {
    loading.value = false
  }
}

function goBack() {
  if (window.history.length > 1) router.back()
  else router.push({ name: 'admin.suppliers.index' })
}

function currency(value: string | number | null | undefined) {
  return Number(value || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })
}
function title(value: string | null | undefined) {
  return String(value || 'unknown').replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
}
function statusSeverity(value: string) {
  if (['active', 'approved', 'received', 'completed', 'paid'].includes(value)) return 'success'
  if (['pending', 'in_progress', 'partially_received'].includes(value)) return 'warn'
  if (['blacklisted', 'rejected', 'cancelled', 'overdue'].includes(value)) return 'danger'
  return 'secondary'
}

onMounted(load)
</script>
