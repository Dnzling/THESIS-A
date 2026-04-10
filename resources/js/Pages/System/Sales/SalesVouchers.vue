<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <Card class="border border-gray-100 shadow-sm rounded-2xl">
      <template #content>
        <div class="flex items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900">Vouchers</h1>
            <p class="text-sm text-gray-500">Track active and expired store vouchers.</p>
          </div>
          <div class="flex items-center gap-2">
            <Button icon="pi pi-plus" label="Create Voucher" @click="goCreate" />
            <Button icon="pi pi-refresh" label="Refresh" outlined @click="load" />
          </div>
        </div>
      </template>
    </Card>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card class="border border-gray-100 shadow-sm rounded-2xl"><template #content><p class="text-xs text-gray-500 uppercase">Total</p><p class="text-2xl font-semibold">{{ summary.total }}</p></template></Card>
      <Card class="border border-gray-100 shadow-sm rounded-2xl"><template #content><p class="text-xs text-gray-500 uppercase">Active</p><p class="text-2xl font-semibold text-emerald-600">{{ summary.active }}</p></template></Card>
      <Card class="border border-gray-100 shadow-sm rounded-2xl"><template #content><p class="text-xs text-gray-500 uppercase">Inactive</p><p class="text-2xl font-semibold text-orange-600">{{ summary.inactive }}</p></template></Card>
      <Card class="border border-gray-100 shadow-sm rounded-2xl"><template #content><p class="text-xs text-gray-500 uppercase">Expired</p><p class="text-2xl font-semibold text-rose-600">{{ summary.expired }}</p></template></Card>
    </div>

    <Card class="border border-gray-100 shadow-sm rounded-2xl">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
          <InputText v-model="filters.search" placeholder="Search voucher code" @keyup.enter="load" />
          <Dropdown v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="All statuses" />
          <Button label="Apply Filters" icon="pi pi-filter" @click="load" />
        </div>

        <DataTable :value="rows" stripedRows>
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
              <Tag :severity="statusSeverity(data)">{{ statusLabel(data) }}</Tag>
            </template>
          </Column>
          <Column header="Actions">
            <template #body="{ data }">
              <Button size="small" text icon="pi pi-eye" label="View" @click="goShow(data.id)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import salesService from '@/services/sales.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Tag from 'primevue/tag'
import { router } from '@inertiajs/vue3'

const rows = ref<any[]>([])
const summary = reactive({ total: 0, active: 0, inactive: 0, expired: 0 })
const filters = reactive({ search: '', status: '' })
const statusOptions = [
  { label: 'All statuses', value: '' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Expired', value: 'expired' },
]

const load = async () => {
  const res = await salesService.getVouchers({ ...filters, per_page: 100 })
  rows.value = res?.data?.data || []
  Object.assign(summary, res?.summary || {})
}

const money = (v: number | string | null | undefined) =>
  new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(v || 0))
const dt = (v: string | null) => (v ? new Date(v).toLocaleDateString('en-PH') : '-')
const isExpired = (row: any) => !!row?.ends_at && new Date(row.ends_at).getTime() < Date.now()
const statusLabel = (row: any) => (isExpired(row) ? 'Expired' : row?.is_active ? 'Active' : 'Inactive')
const statusSeverity = (row: any) => (isExpired(row) ? 'danger' : row?.is_active ? 'success' : 'warn')
const goCreate = () => router.visit('/sales/vouchers/create')
const goShow = (id: number | string) => router.visit(`/sales/vouchers/${id}`)

onMounted(load)
</script>
