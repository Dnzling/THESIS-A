<template>
  <div class="mx-auto space-y-5 px-4 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-900">Return Pickups</h1>
        <p class="mt-1 text-sm text-slate-600">Pickup jobs created from approved customer returns.</p>
      </div>
      <Button icon="pi pi-refresh" label="Refresh" size="small" severity="secondary" outlined :loading="loading" @click="loadPickups" />
    </div>

    <Card class="rounded-2xl border border-slate-200/80 shadow-sm">
      <template #content>
        <div class="grid grid-cols-1 items-center gap-2 md:grid-cols-12">
          <div class="md:col-span-4">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" placeholder="Search order, return, customer" fluid size="small" />
            </IconField>
          </div>
          <div class="md:col-span-3">
            <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" fluid size="small" />
          </div>
          <div class="md:col-span-3">
            <DatePicker v-model="filters.date_range" selectionMode="range" dateFormat="M dd, yy" placeholder="Created date range" showIcon showButtonBar fluid size="small" />
          </div>
          <div class="md:col-span-2">
            <Button v-if="hasActiveFilters" label="Clear Filter" icon="pi pi-filter-slash" severity="danger" outlined size="small" class="w-full" @click="resetFilters" />
            <Button v-else label="Refresh" icon="pi pi-refresh" severity="secondary" outlined size="small" class="w-full" :loading="loading" @click="loadPickups" />
          </div>
        </div>
      </template>
    </Card>

    <Card class="rounded-2xl border border-slate-200/80 shadow-sm">
      <template #content>
        <div v-if="loading" class="space-y-3">
          <Skeleton height="2.25rem" borderRadius="12px" />
          <Skeleton height="2.25rem" borderRadius="12px" />
          <Skeleton height="2.25rem" borderRadius="12px" />
          <Skeleton height="2.25rem" borderRadius="12px" />
        </div>

        <DataTable
          v-else
          :value="pickups"
          dataKey="id"
          rowHover
          paginator
          lazy
          sortMode="single"
          :sortField="sort.field"
          :sortOrder="sort.order"
          :rows="pageState.rows"
          :first="(pageState.page - 1) * pageState.rows"
          :totalRecords="pageState.total"
          :rowsPerPageOptions="[10, 20, 50]"
          @page="onPage"
          @sort="onSort"
          class="text-sm"
        >
          <template #empty>
            <div class="py-8 text-center text-slate-500">No pickup jobs found.</div>
          </template>

          <Column field="created_at" header="Date" sortable style="min-width: 11rem">
            <template #body="{ data }">
              <span class="whitespace-nowrap text-xs text-slate-600">{{ formatDateTime(data.created_at) }}</span>
            </template>
          </Column>

          <Column header="Scheduled Pickup" style="min-width: 11rem">
            <template #body="{ data }">
              <span class="whitespace-nowrap text-xs text-slate-600">{{ data.scheduled_at ? formatDateTime(data.scheduled_at) : '-' }}</span>
            </template>
          </Column>

          <Column header="Order" style="min-width: 10rem">
            <template #body="{ data }">
              <p class="font-medium text-slate-900">{{ data.return_request?.order?.order_number || `#${data.return_request?.order_id}` }}</p>
              <p class="text-[11px] text-slate-500">Return #{{ data.return_id }}</p>
            </template>
          </Column>

          <Column header="Customer" style="min-width: 12rem">
            <template #body="{ data }">
              <p class="font-medium text-slate-800">{{ data.return_request?.user?.full_name || data.return_request?.user?.email || data.pickup_name || '-' }}</p>
              <p class="text-[11px] text-slate-500">{{ data.pickup_phone || data.return_request?.order?.shipping_phone || '-' }}</p>
            </template>
          </Column>

          <Column field="pickup_address" header="Pickup Address" style="min-width: 16rem" />

          <Column header="Status" style="width: 10rem">
            <template #body="{ data }">
              <Badge :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>

          <Column header="Driver" style="min-width: 12rem">
            <template #body="{ data }">
              <span class="text-[11px] text-slate-700">{{ driverName(data.driver) }}</span>
            </template>
          </Column>

          <Column header="Actions" style="width: 7rem">
            <template #body="{ data }">
              <Button icon="pi pi-eye" label="View" outlined rounded size="small" v-tooltip.bottom="'View details'" @click="openDetail(data)" />
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
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Badge from 'primevue/badge'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Skeleton from 'primevue/skeleton'
import logisticsService from '@/services/logistics.service'

const router = useRouter()
const toast = useToast()

const loading = ref(false)
const pickups = ref<any[]>([])

const filters = reactive({
  search: '',
  status: 'ready_for_dispatch',
  date_range: null as Date[] | null,
})

const sort = reactive({ field: 'created_at', order: -1 })
const hasActiveFilters = computed(() => Boolean(filters.search || filters.date_range?.length || filters.status !== 'ready_for_dispatch'))

const pageState = reactive({
  page: 1,
  rows: 10,
  total: 0,
})

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'For Dispatch', value: 'ready_for_dispatch' },
  { label: 'Scheduled', value: 'scheduled' },
  { label: 'Assigned', value: 'assigned' },
  { label: 'Picked Up', value: 'picked_up' },
  { label: 'Out For Delivery', value: 'out_for_delivery' },
  { label: 'Delivered', value: 'delivered' },
  { label: 'Cancelled', value: 'cancelled' },
]

const loadPickups = async () => {
  loading.value = true
  try {
    const res = await logisticsService.getReturnPickups({
      page: pageState.page,
      per_page: pageState.rows,
      status: filters.status || undefined,
      search: filters.search || undefined,
      date_from: formatDateParam(filters.date_range?.[0]),
      date_to: formatDateParam(filters.date_range?.[1]),
      sort_by: sort.field,
      sort_order: sort.order === 1 ? 'asc' : 'desc',
    })
    pickups.value = res?.data || []
    pageState.total = Number(res?.meta?.total || 0)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Load Failed', detail: error?.response?.data?.message || 'Failed to load pickups.', life: 3000 })
  } finally {
    loading.value = false
  }
}

const onPage = (event: any) => {
  pageState.page = Number(event.page || 0) + 1
  pageState.rows = Number(event.rows || 10)
  loadPickups()
}

const onSort = (event: any) => {
  sort.field = event.sortField === 'created_at' ? 'created_at' : 'created_at'
  sort.order = event.sortOrder === 1 ? 1 : -1
  pageState.page = 1
  loadPickups()
}

const resetFilters = () => {
  filters.search = ''
  filters.status = ''
  filters.date_range = null
  pageState.page = 1
  loadPickups()
}

const formatDateParam = (value?: Date | null) => {
  if (!value) return undefined
  const year = value.getFullYear()
  const month = String(value.getMonth() + 1).padStart(2, '0')
  const day = String(value.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const openDetail = (pickup: any) => {
  router.push({ name: 'logistics.return-pickups.detail', params: { id: pickup.id } })
}

const formatStatus = (status?: string) => {
  if (!status) return '-'
  if (status === 'ready_for_dispatch') return 'For Dispatch'
  return String(status).replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase())
}

const statusSeverity = (status?: string) => {
  if (['picked_up', 'delivered'].includes(String(status))) return 'success'
  if (status === 'assigned' || status === 'out_for_delivery') return 'info'
  if (status === 'cancelled') return 'secondary'
  if (status === 'ready_for_dispatch' || status === 'scheduled') return 'warn'
  return 'secondary'
}

const driverName = (driver?: any) => {
  if (!driver) return '-'
  const name = `${driver.fname || ''} ${driver.lname || ''}`.trim()
  return name || driver.email || '-'
}

const formatDateTime = (value: any) => {
  const date = new Date(value)
  return new Intl.DateTimeFormat('en-PH', { year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' }).format(date)
}

watch(
  () => [filters.search, filters.status],
  () => {
    pageState.page = 1
    loadPickups()
  },
)

watch(() => filters.date_range, (range) => {
  if (range?.length === 2 || !range?.length) {
    pageState.page = 1
    loadPickups()
  }
}, { deep: true })

onMounted(loadPickups)
</script>
