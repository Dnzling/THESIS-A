<template>
  <div class="min-h-screen space-y-4 p-4 text-xs">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-bold text-slate-900">Vouchers</h1>
        <p class="text-xs text-slate-500">Track active and expired store vouchers.</p>
      </div>
      <div class="flex items-center gap-2">
        <Button icon="pi pi-plus" label="Create Voucher" severity="warn" size="small" @click="goCreate" />
        <Button icon="pi pi-refresh" label="Refresh" severity="warn" size="small" outlined :loading="loading" @click="load" />
      </div>
    </div>

    <Card class="border border-slate-200 shadow-sm">
      <template #content>
        <div class="mb-5 grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(220px,2fr)_1fr_auto_auto]">
          <IconField><InputIcon class="pi pi-search" /><InputText v-model="filters.search" placeholder="Search voucher code" class="w-full text-sm" size="small" @keyup.enter="load" /></IconField>
          <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="All statuses" class="w-full text-sm" size="small" />
          <Button label="Apply" severity="warn" size="small" @click="load" />
          <Button v-if="hasActiveFilters" label="Reset" icon="pi pi-filter-slash" severity="secondary" outlined size="small" @click="resetFilters" />
        </div>

        <div v-if="loading" class="space-y-3 py-3" aria-label="Loading vouchers"><Skeleton v-for="row in 6" :key="row" height="2.5rem" /></div>
        <DataTable v-else :value="rows" size="small" class="text-xs" rowHover paginator :rows="15" :rowsPerPageOptions="[15, 25, 50]" scrollable @row-click="goShow($event.data.id)">
          <template #empty><div class="py-8 text-center text-xs text-slate-500">No vouchers found.</div></template>
          <Column field="voucher_name" header="Name" />
          <Column field="code" header="Code" />
          <Column field="voucher_slots" header="Voucher Slots" />
          <Column field="discount_type" header="Discount">
            <template #body="{ data }">
              {{ data.discount_type === 'percent' ? `${Number(data.discount_value || 0)}%` : money(data.discount_value) }}
            </template>
          </Column>
          <Column field="min_order_amount" header="Min Order">
            <template #body="{ data }">{{ money(data.min_order_amount) }}</template>
          </Column>
          <Column field="starts_at" header="Start">
            <template #body="{ data }">{{ dt(data.starts_at) }}</template>
          </Column>
          <Column field="ends_at" header="End">
            <template #body="{ data }">{{ dt(data.ends_at) }}</template>
          </Column>
          <Column header="Status">
            <template #body="{ data }">
              <Badge :value="statusLabel(data)" :severity="statusSeverity(data)" />
            </template>
          </Column>
          <Column header="Actions">
            <template #body="{ data }">
              <Button size="small" severity="warn" text icon="pi pi-eye" label="View" @click.stop="goShow(data.id)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import crmService from '@/services/crm.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Badge from 'primevue/badge'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Skeleton from 'primevue/skeleton'
import { useToast } from 'primevue/usetoast'
import { router } from '@inertiajs/vue3'

const rows = ref<any[]>([])
const toast = useToast()
const loading = ref(true)
const filters = reactive({ search: '', status: '' })
const hasActiveFilters = computed(() => Boolean(filters.search.trim() || filters.status))
const resetFilters = () => {
  filters.search = ''
  filters.status = ''
  load()
}
const statusOptions = [
  { label: 'All statuses', value: '' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Expired', value: 'expired' },
]

const load = async () => {
  loading.value = true
  try {
    const res = await crmService.getVouchers({ ...filters, per_page: 100 })
    rows.value = res?.data?.data || []
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load vouchers', life: 3000 })
  } finally {
    loading.value = false
  }
}

const money = (v: number | string | null | undefined) =>
  new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(v || 0))
const dt = (v: string | null) => (v ? new Date(v).toLocaleDateString('en-PH') : '-')
const isExpired = (row: any) => !!row?.ends_at && new Date(row.ends_at).getTime() < Date.now()
const statusLabel = (row: any) => (isExpired(row) ? 'Expired' : row?.is_active ? 'Active' : 'Inactive')
const statusSeverity = (row: any) => (isExpired(row) ? 'danger' : row?.is_active ? 'success' : 'warn')
const goCreate = () => router.visit('/crm/vouchers/create')
const goShow = (id: number | string) => router.visit(`/crm/vouchers/${id}`)

onMounted(load)
</script>
