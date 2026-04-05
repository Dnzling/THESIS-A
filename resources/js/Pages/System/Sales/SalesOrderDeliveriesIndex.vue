<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Sales Deliveries</h1>
        <p class="text-gray-600">Track in-store and ecommerce deliveries with driver assignments</p>
      </div>
    </div>

    <Card>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
          <InputText v-model="search" placeholder="Search by tracking or order" class="w-full" />
          <Select v-model="statusFilter" :options="statusOptions" option-label="label" option-value="value" class="w-full" />
          <Button label="Refresh" icon="pi pi-sync" text @click="loadDeliveries" />
        </div>

        <DataTable :value="deliveries" :loading="loading" class="p-datatable-sm" stripedRows responsive-layout="scroll">
          <Column field="tracking_number" header="Tracking #" />
          <Column field="channel" header="Channel">
            <template #body="{ data }">
              <Tag :value="data.channel || 'Online'" :severity="data.channel === 'Online' ? 'info' : 'success'" />
            </template>
          </Column>
          <Column header="Order">
            <template #body="{ data }">
              {{ data.order_number || data.order?.order_number || '-' }}
            </template>
          </Column>
          <Column header="Customer">
            <template #body="{ data }">
              {{ data.customer_name || data.order?.customer_name || '-' }}
            </template>
          </Column>
          <Column header="Status">
            <template #body="{ data }">
              <Tag :value="data.status" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
          <Column header="Scheduled">
            <template #body="{ data }">
              {{ formatDateTime(data.scheduled_delivery_at || data.order?.scheduled_delivery_at) }}
            </template>
          </Column>
          <Column header="Driver">
            <template #body="{ data }">
              {{ driverName(data.driver) || '-' }}
            </template>
          </Column>
          <Column header="Actions" style="width: 140px">
            <template #body="{ data }">
              <Button icon="pi pi-eye" severity="info" text rounded size="small" @click="goToDetail(data)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import salesService from '@/services/sales.service'
import InputText from 'primevue/inputtext'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Select from 'primevue/select'
import Tag from 'primevue/tag'

const router = useRouter()
const loading = ref(false)
const deliveries = ref<any[]>([])
const search = ref('')
const statusFilter = ref('all')

const statusOptions = [
  { label: 'All', value: 'all' },
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
    const res = await salesService.getOrderDeliveries({
      per_page: 50,
      search: search.value || undefined,
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
    })
    deliveries.value = res?.data?.data || res?.data || []
  } finally {
    loading.value = false
  }
}

const goToDetail = (delivery: any) => {
  if (delivery?.source === 'ecommerce') {
    router.push({ name: 'logistics.deliveries.detail', params: { source: 'ecommerce', orderId: delivery.order_id } })
    return
  }

  router.push({ name: 'sales.deliveries.detail', params: { id: delivery.id } })
}

const formatDateTime = (value: string | null | undefined) => {
  if (!value) return '-'
  return new Date(value).toLocaleString('en-PH')
}

const driverName = (driver: any) => {
  if (!driver) return ''
  return `${driver.fname || ''} ${driver.lname || ''}`.trim()
}

const statusSeverity = (status: string) => {
  if (status === 'delivered') return 'success'
  if (status === 'out_for_delivery' || status === 'in_transit') return 'info'
  if (status === 'failed_delivery' || status === 'cancelled') return 'danger'
  return 'warning'
}

onMounted(loadDeliveries)
watch([search, statusFilter], () => {
  loadDeliveries()
})
</script>
