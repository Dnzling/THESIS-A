<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <div class="flex items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900">Delivery Management</h1>
            <p class="text-sm text-gray-500">Track active deliveries and update milestones.</p>
          </div>
          <div class="flex gap-2">
            <Button severity="info" outlined label="Vehicles" icon="pi pi-truck" @click="openVehicles" />
            <Button severity="info" outlined label="Refresh" icon="pi pi-refresh" @click="loadDeliveries" />
          </div>
        </div>
      </template>
    </Card>

    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
          <div class="md:col-span-8">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" fluid placeholder="Search order/tracking/courier..." />
            </IconField>
          </div>
          <div class="md:col-span-4">
            <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" showClear fluid placeholder="Filter status" />
          </div>
        </div>
      </template>
    </Card>

    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <DataTable
          :value="deliveries"
          :loading="loading"
          dataKey="id"
          stripedRows
          paginator
          lazy
          :rows="pageState.rows"
          :first="(pageState.page - 1) * pageState.rows"
          :totalRecords="pageState.total"
          :rowsPerPageOptions="[10, 20, 50]"
          @page="onPage"
        >
          <Column header="Order">
            <template #body="{ data }">
              <p class="font-medium text-blue-600">{{ data.order?.order_number || '-' }}</p>
              <p class="text-xs text-gray-500">{{ data.order?.shipping_name || '-' }}</p>
            </template>
          </Column>
          <Column field="tracking_number" header="Tracking" />
          <Column header="Vehicle">
            <template #body="{ data }">
              <span class="text-sm text-gray-700">{{ data.vehicle ? `${data.vehicle.vehicle_name} (${data.vehicle.plate_number})` : 'Unassigned' }}</span>
            </template>
          </Column>
          <Column field="courier_name" header="Courier" />
          <Column field="status" header="Status">
            <template #body="{ data }">
              <Tag :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
          <Column field="estimated_delivery_at" header="ETA">
            <template #body="{ data }">{{ formatDateTime(data.estimated_delivery_at) }}</template>
          </Column>
          <Column header="Actions">
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <Select
                  v-model="data.__next_status"
                  :options="statusOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Next"
                  class="w-40"
                  :disabled="!canManageDeliveries"
                />
                <Button severity="info" text label="Update" :disabled="!canManageDeliveries" @click="updateDelivery(data)" />
                <Button severity="info" text icon="pi pi-eye" @click="openDetail(data.id)" />
              </div>
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
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const loading = ref(false)
const deliveries = ref<any[]>([])
const canManageDeliveries = authStore.hasPermission('logistics.deliveries.manage')

const filters = reactive({
  search: '',
  status: null as string | null,
})
const pageState = reactive({ page: 1, rows: 10, total: 0 })

const statusOptions = [
  { label: 'Assigned', value: 'assigned' },
  { label: 'Packed', value: 'packed' },
  { label: 'In Transit', value: 'in_transit' },
  { label: 'Out For Delivery', value: 'out_for_delivery' },
  { label: 'Delivered', value: 'delivered' },
  { label: 'Failed Delivery', value: 'failed_delivery' },
  { label: 'Cancelled', value: 'cancelled' },
]

const loadDeliveries = async () => {
  loading.value = true
  try {
    const res = await logisticsService.getDeliveries({
      page: pageState.page,
      per_page: pageState.rows,
      search: filters.search || undefined,
      status: filters.status || undefined,
    })
    const payload = res?.data
    deliveries.value = (payload?.data || []).map((d: any) => ({ ...d, __next_status: d.status }))
    pageState.total = Number(payload?.total || 0)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load deliveries', life: 3000 })
  } finally {
    loading.value = false
  }
}

const updateDelivery = async (row: any) => {
  if (!row.__next_status || row.__next_status === row.status) return
  try {
    await logisticsService.updateDeliveryStatus(row.id, { status: row.__next_status })
    toast.add({ severity: 'success', summary: 'Updated', detail: 'Delivery status updated.', life: 2000 })
    loadDeliveries()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Update failed', detail: error?.response?.data?.message || 'Failed to update delivery', life: 3000 })
  }
}

const onPage = (event: any) => {
  pageState.page = Number(event.page || 0) + 1
  pageState.rows = Number(event.rows || 10)
  loadDeliveries()
}

const openVehicles = () => router.push({ name: 'logistics.vehicles' })
const openDetail = (id: number) => router.push({ name: 'logistics.deliveries.detail', params: { id } })
const formatStatus = (status: string) => status.replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase())
const formatDateTime = (v: string | null) => (v ? new Date(v).toLocaleString('en-PH') : '-')
const statusSeverity = (status: string) => {
  if (status === 'delivered') return 'success'
  if (status === 'failed_delivery' || status === 'cancelled') return 'danger'
  if (status === 'out_for_delivery') return 'warning'
  return 'info'
}

watch(() => [filters.search, filters.status], () => {
  pageState.page = 1
  loadDeliveries()
})

onMounted(loadDeliveries)
</script>
