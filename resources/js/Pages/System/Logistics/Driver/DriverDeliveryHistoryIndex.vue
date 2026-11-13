<template>
  <div class="mx-auto space-y-4 px-4 sm:px-6 lg:px-8">
    <div>
      <h1 class="text-lg font-semibold tracking-tight text-slate-900">Delivery History</h1>
      <p class="mt-1 text-sm text-slate-600">Completed deliveries assigned to you.</p>
    </div>

    <Card class="rounded-2xl border border-slate-200/80 shadow-sm">
      <template #content>
        <div class="grid grid-cols-1 items-center gap-2 sm:grid-cols-2 lg:grid-cols-12">
          <div class="sm:col-span-2 lg:col-span-5">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" placeholder="Search reference, recipient, or address" fluid size="small" />
            </IconField>
          </div>
          <div class="sm:col-span-1 lg:col-span-4">
            <DatePicker
              v-model="filters.dateRange"
              selectionMode="range"
              dateFormat="M dd, yy"
              placeholder="Delivery date range"
              showIcon
              showButtonBar
              fluid
              size="small"
            />
          </div>
          <div class="sm:col-span-1 lg:col-span-3">
            <Button
              v-if="hasActiveFilters"
              label="Clear Filter"
              icon="pi pi-filter-slash"
              severity="danger"
              outlined
              size="small"
              class="w-full"
              @click="clearFilters"
            />
            <Button
              v-else
              label="Refresh"
              icon="pi pi-refresh"
              severity="secondary"
              outlined
              size="small"
              class="w-full"
              :loading="loading"
              @click="loadDeliveries"
            />
          </div>
        </div>
      </template>
    </Card>

    <Card class="rounded-2xl border border-slate-200/80 shadow-sm">
      <template #content>
        <div v-if="loading" class="space-y-2">
          <Skeleton v-for="row in 5" :key="row" height="2.5rem" borderRadius="8px" />
        </div>
        <DataTable
          v-else
          :value="deliveries"
          dataKey="id"
          rowHover
          paginator
          lazy
          scrollable
          size="small"
          :first="(pageState.page - 1) * pageState.rows"
          :rows="pageState.rows"
          :totalRecords="pageState.total"
          :rowsPerPageOptions="[10, 20, 50]"
          @page="onPage"
          class="text-sm"
        >
          <template #empty>
            <div class="py-8 text-center text-sm text-slate-500">No completed deliveries found for this period.</div>
          </template>

          <Column header="Date" style="min-width: 10rem">
            <template #body="{ data }">
              <span class="whitespace-nowrap text-xs text-slate-600">{{ formatDate(data.delivery_date || data.created_at) }}</span>
            </template>
          </Column>
          <Column field="order_number" header="Reference" style="min-width: 12rem">
            <template #body="{ data }"><span class="font-semibold text-slate-900">{{ data.order_number || '-' }}</span></template>
          </Column>
          <Column header="Source" style="min-width: 9rem">
            <template #body="{ data }"><Tag :value="sourceLabel(data.source_type)" :severity="sourceSeverity(data.source_type)" /></template>
          </Column>
          <Column field="customer_name" header="Recipient" style="min-width: 12rem">
            <template #body="{ data }"><span class="text-slate-800">{{ data.customer_name || '-' }}</span></template>
          </Column>
          <Column field="delivery_address" header="Delivery Address" style="min-width: 18rem">
            <template #body="{ data }"><span class="text-xs text-slate-600">{{ data.delivery_address || '-' }}</span></template>
          </Column>
          <Column header="Items" style="width: 6rem">
            <template #body="{ data }"><span class="tabular-nums">{{ data.quantity_items ?? 0 }}</span></template>
          </Column>
          <Column header="Weight" style="width: 8rem">
            <template #body="{ data }"><span class="whitespace-nowrap text-slate-700">{{ weight(data.weight_kg) }}</span></template>
          </Column>
          <Column header="Status" style="width: 8rem">
            <template #body><Badge value="Delivered" severity="success" /></template>
          </Column>
          <Column header="Action" style="width: 8rem">
            <template #body="{ data }">
              <Button icon="pi pi-eye" label="View" size="small" outlined rounded aria-label="View delivery" @click="view(data)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Badge from 'primevue/badge'
import Tag from 'primevue/tag'
import DatePicker from 'primevue/datepicker'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Skeleton from 'primevue/skeleton'
import logisticsService from '@/services/logistics.service'

const router = useRouter()
const loading = ref(false)
const deliveries = ref<any[]>([])
const filters = reactive({ search: '', dateRange: null as Date[] | null })
const pageState = reactive({ page: 1, rows: 10, total: 0 })
const hasActiveFilters = computed(() => Boolean(filters.search.trim() || filters.dateRange?.some(Boolean)))
const formatDate = (value: any) => value ? new Intl.DateTimeFormat('en-PH', { year: 'numeric', month: 'short', day: '2-digit' }).format(new Date(value)) : '-'
const weight = (value: any) => `${Number(value || 0).toLocaleString(undefined, { maximumFractionDigits: 2 })} kg`
const view = (row: any) => router.push({ name: 'driver.deliveries.view', params: { source: row.source_type, orderId: row.order_id } })
const sourceLabel = (value?: string) => ({ ecommerce: 'E-commerce', sales: 'In-store', replacement: 'Replacement', stock_transfer: 'Stock Transfer', pickup: 'Supplier Pickup' }[String(value || '')] || String(value || 'Delivery').replace(/_/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase()))
const sourceSeverity = (value?: string) => ({ ecommerce: 'info', sales: 'success', replacement: 'warn', stock_transfer: 'secondary', pickup: 'contrast' }[String(value || '')] || 'secondary')
const dateParam = (date?: Date | null) => date ? `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}` : undefined

const loadDeliveries = async () => {
  loading.value = true
  try {
    const response = await logisticsService.getDeliveryOrders({
      source: 'all',
      status: 'delivered',
      assigned_driver_only: true,
      search: filters.search.trim() || undefined,
      date_from: dateParam(filters.dateRange?.[0]),
      date_to: dateParam(filters.dateRange?.[1]),
      page: pageState.page,
      per_page: pageState.rows,
    })
    const data = response?.data
    deliveries.value = data?.data || []
    pageState.total = Number(data?.total || 0)
  } catch (error) {
    console.error('Failed to load delivery history', error)
    deliveries.value = []
    pageState.total = 0
  } finally {
    loading.value = false
  }
}

const onPage = (event: any) => {
  pageState.page = Number(event.page || 0) + 1
  pageState.rows = Number(event.rows || 10)
  loadDeliveries()
}
const clearFilters = () => {
  filters.search = ''
  filters.dateRange = null
  pageState.page = 1
  loadDeliveries()
}
watch(() => [filters.search, filters.dateRange], () => {
  pageState.page = 1
  loadDeliveries()
}, { deep: true })

onMounted(loadDeliveries)
</script>
