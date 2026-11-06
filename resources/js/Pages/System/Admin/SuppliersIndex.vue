<template>
  <div class="mx-auto max-w-7xl space-y-5 pb-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-slate-900">Suppliers</h1>
        <p class="mt-1 text-sm text-slate-500">Platform-wide supplier directory and procurement performance.</p>
      </div>
      <Button label="Supplier Verification Directory" icon="pi pi-verified" severity="secondary" outlined size="small" @click="router.push('/admin/verification/suppliers')" />
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <Card v-for="metric in performanceCards" :key="metric.label" class="border border-slate-200 shadow-none">
        <template #content>
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ metric.label }}</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ metric.value }}</p>
          <p class="mt-1 text-xs text-slate-500">{{ metric.caption }}</p>
        </template>
      </Card>
    </div>

    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <div class="flex flex-wrap items-center gap-2">
          <IconField class="w-full sm:w-72">
            <InputIcon class="pi pi-search" />
            <InputText v-model="search" placeholder="Search supplier, store, contact..." size="small" class="w-full" @input="debouncedLoad" />
          </IconField>
          <Select v-model="status" :options="statusOptions" option-label="label" option-value="value" placeholder="All statuses" show-clear size="small" class="w-full sm:w-44" @change="load" />
          <Button v-if="search || status" label="Clear Filter" icon="pi pi-filter-slash" severity="danger" size="small" @click="clearFilters" />
          <Button icon="pi pi-refresh" severity="secondary" outlined rounded size="small" aria-label="Refresh suppliers" :loading="loading" @click="load" />
        </div>
      </template>
    </Card>

    <Card class="overflow-hidden border border-slate-200 shadow-none">
      <template #content>
        <DataTable :value="suppliers" :loading="loading" data-key="id" paginator :rows="15" :rows-per-page-options="[15, 25, 50]" row-hover responsive-layout="scroll" class="text-sm">
          <template #empty>
            <div class="py-10 text-center text-sm text-slate-500">No suppliers match your filters.</div>
          </template>
          <Column field="supplier_name" header="Supplier" sortable>
            <template #body="{ data }">
              <div class="flex items-center gap-3">
                <img v-if="data.logo_url" :src="data.logo_url" :alt="data.supplier_name" class="h-9 w-9 rounded-lg border border-slate-200 object-cover" />
                <span v-else class="flex h-9 w-9 items-center justify-center rounded-lg bg-orange-50 text-xs font-semibold text-orange-700">{{ initials(data.supplier_name) }}</span>
                <div class="min-w-0">
                  <p class="font-semibold text-slate-900">{{ data.supplier_name }}</p>
                  <p class="text-xs text-slate-500">{{ data.company_name || data.supplier_type || 'Supplier' }}</p>
                </div>
              </div>
            </template>
          </Column>
          <Column field="store.name" header="Registered by store" sortable>
            <template #body="{ data }">{{ data.store?.name || 'Store not linked' }}</template>
          </Column>
          <Column field="contact_person" header="Contact">
            <template #body="{ data }">
              <p class="text-slate-800">{{ data.contact_person || 'Not provided' }}</p>
              <p class="text-xs text-slate-500">{{ data.email || data.phone || 'No contact details' }}</p>
            </template>
          </Column>
          <Column field="status" header="Status" sortable>
            <template #body="{ data }"><Badge :value="title(data.status)" :severity="statusSeverity(data.status)" /></template>
          </Column>
          <Column field="rating" header="Rating" sortable>
            <template #body="{ data }"><span class="font-medium">{{ Number(data.rating || 0).toFixed(1) }} / 5</span></template>
          </Column>
          <Column field="total_orders" header="Orders" sortable />
          <Column field="total_amount_purchased" header="Purchases" sortable>
            <template #body="{ data }">{{ currency(data.total_amount_purchased) }}</template>
          </Column>
          <Column header="Action" :exportable="false" style="width: 100px">
            <template #body="{ data }">
              <Button label="View" icon="pi pi-arrow-right" icon-pos="right" outlined rounded size="small" severity="secondary" @click="router.push({ name: 'admin.suppliers.detail', params: { id: data.id } })" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import axiosClient from '@/axios'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'

const router = useRouter()
const suppliers = ref<any[]>([])
const totals = ref<any>({})
const loading = ref(false)
const search = ref('')
const status = ref<string | null>(null)
const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Blacklisted', value: 'blacklisted' },
]
let searchTimer: ReturnType<typeof setTimeout> | undefined

const performanceCards = computed(() => [
  { label: 'Suppliers', value: Number(totals.value.suppliers_count || 0).toLocaleString(), caption: 'Across registered stores' },
  { label: 'Active', value: Number(totals.value.active_count || 0).toLocaleString(), caption: 'Available supplier records' },
  { label: 'Purchase orders', value: Number(totals.value.orders_count || 0).toLocaleString(), caption: 'Recorded supplier orders' },
  { label: 'Purchase value', value: currency(totals.value.purchased_total), caption: `Average rating ${Number(totals.value.average_rating || 0).toFixed(1)} / 5` },
])

async function load() {
  loading.value = true
  try {
    const response = await axiosClient.get('/api/admin/supplier-directory', {
      params: { search: search.value || undefined, status: status.value || undefined, per_page: 100 },
    })
    const data = response.data?.data || {}
    suppliers.value = data.suppliers?.data || []
    totals.value = data.performance || {}
  } finally {
    loading.value = false
  }
}

function debouncedLoad() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(load, 300)
}

function clearFilters() {
  search.value = ''
  status.value = null
  load()
}

function currency(value: string | number | null | undefined) {
  return Number(value || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })
}

function title(value: string | null | undefined) {
  return String(value || 'unknown').replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
}

function initials(value: string) {
  return String(value || 'S').split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase()
}

function statusSeverity(value: string) {
  return ({ active: 'success', inactive: 'secondary', blacklisted: 'danger' } as Record<string, string>)[value] || 'secondary'
}

onMounted(load)
onUnmounted(() => clearTimeout(searchTimer))
</script>
