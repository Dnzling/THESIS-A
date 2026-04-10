<template>
  <div class="mx-auto space-y-6 px-4 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Logistics Stock Transfers</h1>
        <p class="mt-1 text-sm text-slate-500">Transfers queued and handled by logistics.</p>
      </div>
      <Button icon="pi pi-refresh" label="Refresh" outlined @click="loadTransfers" />
    </div>

    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #content>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
          <div class="md:col-span-6">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" placeholder="Search transfer no / branch" fluid />
            </IconField>
          </div>
          <div class="md:col-span-4">
            <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" fluid />
          </div>
          <div class="md:col-span-2">
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
        </div>
        <DataTable
          v-else
          :value="rows"
          dataKey="id"
          stripedRows
          paginator
          :rows="pageState.rows"
          :first="(pageState.page - 1) * pageState.rows"
          :totalRecords="pageState.total"
          :rowsPerPageOptions="[10, 20, 50]"
          @page="onPage"
          class="text-xs"
        >
          <template #empty>
            <div class="py-8 text-center text-slate-500">No stock transfers found.</div>
          </template>

          <Column header="Transfer No." style="min-width: 12rem">
            <template #body="{ data }">
              <p class="font-semibold text-slate-900">{{ data.transfer_number || '-' }}</p>
            </template>
          </Column>

          <Column header="From" style="min-width: 12rem">
            <template #body="{ data }">
              {{ data.from_branch?.name || data.fromBranch?.name || '-' }}
            </template>
          </Column>

          <Column header="To" style="min-width: 12rem">
            <template #body="{ data }">
              {{ data.to_branch?.name || data.toBranch?.name || '-' }}
            </template>
          </Column>

          <Column header="Requested" style="width: 10rem">
            <template #body="{ data }">
              {{ formatDateTime(data.requested_date || data.created_at) }}
            </template>
          </Column>

          <Column header="Status" style="width: 12rem">
            <template #body="{ data }">
              <Tag :value="formatStatus(data)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>

          <Column header="Actions" style="width: 7rem">
            <template #body="{ data }">
              <Button icon="pi pi-eye" text rounded severity="info" @click="openDetail(data.id)" />
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
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Skeleton from 'primevue/skeleton'
import inventoryService from '../../../../services/inventory.service'

const router = useRouter()
const toast = useToast()

const loading = ref(false)
const rows = ref<any[]>([])

const filters = reactive({
  search: '',
  status: '',
})

const pageState = reactive({
  page: 1,
  rows: 10,
  total: 0,
})

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Receiver Acknowledged', value: 'receiver_acknowledged' },
  { label: 'In Transit', value: 'in_transit' },
  { label: 'Received', value: 'received' },
]

const loadTransfers = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getTransfers({
      page: pageState.page,
      per_page: pageState.rows,
      status: filters.status || undefined,
      search: filters.search || undefined,
    })
    const payload = response?.data || {}
    rows.value = payload?.data || []
    pageState.total = Number(payload?.total || 0)
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Load Failed',
      detail: error?.response?.data?.message || 'Failed to load stock transfers.',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const openDetail = (id: number) => {
  router.push({ name: 'logistics.stock-transfers.detail', params: { id } })
}

const onPage = (event: any) => {
  pageState.page = Number(event.page || 0) + 1
  pageState.rows = Number(event.rows || 10)
  loadTransfers()
}

const resetFilters = () => {
  filters.search = ''
  filters.status = ''
  pageState.page = 1
  loadTransfers()
}

const formatDateTime = (value?: string) => {
  if (!value) return '-'
  return new Date(value).toLocaleString('en-PH', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

const statusSeverity = (status?: string) => {
  const s = String(status || '').toLowerCase()
  if (s === 'received') return 'success'
  if (s === 'in_transit') return 'info'
  if (s === 'receiver_acknowledged' || s === 'receiver_acknowledge') return 'warning'
  if (s === 'cancelled') return 'danger'
  return 'secondary'
}

const formatStatus = (row: any) => {
  const status = String(row?.status || '').toLowerCase()
  const notes = String(row?.notes || '').toLowerCase()
  if (status === 'in_transit' && notes.includes('sent to logistics')) return 'Logistics Processing'
  return status.replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase()) || '-'
}

let timer: ReturnType<typeof setTimeout> | null = null
watch(
  () => filters.status,
  () => {
    pageState.page = 1
    loadTransfers()
  }
)
watch(
  () => filters.search,
  () => {
    if (timer) clearTimeout(timer)
    timer = setTimeout(() => {
      pageState.page = 1
      loadTransfers()
    }, 300)
  }
)

onMounted(loadTransfers)
</script>

