<template>
  <div class="mx-auto space-y-6 px-4 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Return Pickups</h1>
        <p class="mt-1 text-sm text-slate-600">Pickup jobs created from approved customer returns.</p>
      </div>
      <Button icon="pi pi-refresh" label="Refresh" outlined @click="loadPickups" />
    </div>

    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #content>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
          <div class="md:col-span-6">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" placeholder="Search order #, customer, contact" fluid />
            </IconField>
          </div>
          <div class="md:col-span-5">
            <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" fluid />
          </div>
          <div class="md:col-span-1">
            <Button icon="pi pi-filter-slash" outlined class="w-full" @click="resetFilters" />
          </div>
        </div>
      </template>
    </Card>

    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
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
          stripedRows
          paginator
          lazy
          :rows="pageState.rows"
          :totalRecords="pageState.total"
          :rowsPerPageOptions="[10, 20, 50]"
          @page="onPage"
          class="text-xs"
        >
          <template #empty>
            <div class="py-8 text-center text-slate-500">No pickup jobs found.</div>
          </template>

          <Column header="Scheduled" style="width: 10rem">
            <template #body="{ data }">
              <span class="text-[11px] text-slate-600">{{ data.scheduled_at ? formatDateTime(data.scheduled_at) : '-' }}</span>
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

          <Column header="Status" style="width: 8rem">
            <template #body="{ data }">
              <Tag :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>

          <Column header="Driver" style="min-width: 12rem">
            <template #body="{ data }">
              <span class="text-[11px] text-slate-700">{{ driverName(data.driver) }}</span>
            </template>
          </Column>

          <Column header="Actions" style="width: 6rem">
            <template #body="{ data }">
              <Button icon="pi pi-eye" text rounded severity="info" v-tooltip.bottom="'View details'" @click="openDetail(data)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import logisticsService from '@/services/logistics.service'

const router = useRouter()
const toast = useToast()

const loading = ref(false)
const pickups = ref<any[]>([])

const filters = reactive({
  search: '',
  status: 'scheduled',
})

const pageState = reactive({
  page: 1,
  rows: 10,
  total: 0,
})

const statusOptions = [
  { label: 'All', value: '' },
  { label: 'Scheduled', value: 'scheduled' },
  { label: 'Assigned', value: 'assigned' },
  { label: 'Picked Up', value: 'picked_up' },
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
      sort_by: 'scheduled_at',
      sort_order: 'asc',
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

const resetFilters = () => {
  filters.search = ''
  filters.status = 'scheduled'
  pageState.page = 1
  loadPickups()
}

const openDetail = (pickup: any) => {
  router.push({ name: 'logistics.return-pickups.detail', params: { id: pickup.id } })
}

const formatStatus = (status?: string) => {
  if (!status) return '-'
  return String(status).replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase())
}

const statusSeverity = (status?: string) => {
  if (status === 'picked_up') return 'success'
  if (status === 'assigned') return 'info'
  if (status === 'cancelled') return 'danger'
  return 'warning'
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

onMounted(loadPickups)
</script>
