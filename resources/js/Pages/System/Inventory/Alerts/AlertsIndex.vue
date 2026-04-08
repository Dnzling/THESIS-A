<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-800">Stock Alerts</h1>
      <p class="text-gray-600 mt-1">Track and manage inventory threshold alerts</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <Card class="hover:shadow-lg transition-shadow">
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Active Alerts</p>
              <h3 class="text-3xl font-bold text-gray-900">{{ stats.active }}</h3>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
              <i class="pi pi-bell text-2xl text-blue-600"></i>
            </div>
          </div>
        </template>
      </Card>

      <Card class="hover:shadow-lg transition-shadow">
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Critical (Out of Stock)</p>
              <h3 class="text-3xl font-bold text-gray-900">{{ stats.critical }}</h3>
            </div>
            <div class="bg-red-100 p-3 rounded-full">
              <i class="pi pi-exclamation-triangle text-2xl text-red-600"></i>
            </div>
          </div>
        </template>
      </Card>

      <Card class="hover:shadow-lg transition-shadow">
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Acknowledged</p>
              <h3 class="text-3xl font-bold text-gray-900">{{ stats.acknowledged }}</h3>
            </div>
            <div class="bg-yellow-100 p-3 rounded-full">
              <i class="pi pi-check text-2xl text-yellow-600"></i>
            </div>
          </div>
        </template>
      </Card>

      <Card class="hover:shadow-lg transition-shadow">
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Resolved</p>
              <h3 class="text-3xl font-bold text-gray-900">{{ stats.resolved }}</h3>
            </div>
            <div class="bg-green-100 p-3 rounded-full">
              <i class="pi pi-check-circle text-2xl text-green-600"></i>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Filters -->
    <Card class="mb-6">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Select
            v-model="filters.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="All Statuses"
            showClear
            @change="loadAlerts"
          />
          <Select
            v-model="filters.severity"
            :options="severityOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="All Severities"
            showClear
            @change="loadAlerts"
          />
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText
              v-model="filters.search"
              placeholder="Search item..."
              @keyup.enter="loadAlerts"
            />
          </IconField>
          <Button icon="pi pi-filter-slash" label="Reset" @click="resetFilters" />
        </div>
      </template>
    </Card>

    <!-- Alerts Table -->
    <Card>
      <template #content>
        <div v-if="loading" class="space-y-3">
          <div class="grid grid-cols-6 gap-3 text-xs text-gray-400">
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
          </div>
          <div v-for="i in 8" :key="i" class="grid grid-cols-6 gap-3">
            <Skeleton height="20px" class="col-span-1" />
            <Skeleton height="20px" class="col-span-1" />
            <Skeleton height="20px" class="col-span-1" />
            <Skeleton height="20px" class="col-span-1" />
            <Skeleton height="20px" class="col-span-1" />
            <Skeleton height="20px" class="col-span-1" />
          </div>
        </div>

        <DataTable
          v-else
          :value="filteredAlerts"
          paginator
          :rows="10"
          :totalRecords="filteredAlerts.length"
          dataKey="id"
          class="p-datatable-sm"
          stripedRows
        >
          <template #empty>
            <div class="text-center py-8">
              <i class="pi pi-inbox text-4xl text-gray-400"></i>
              <p class="text-gray-600 mt-2">No alerts found</p>
            </div>
          </template>

          <Column field="product.sku" header="SKU">
            <template #body="{ data }">
              {{ data.product?.sku || 'N/A' }}
            </template>
          </Column>

          <Column field="product.product_name" header="Item Name">
            <template #body="{ data }">
              {{ data.product?.product_name || 'N/A' }}
            </template>
          </Column>

          <Column field="alert_type" header="Alert Type">
            <template #body="{ data }">
              <Tag :value="formatAlertType(data.alert_type)" :severity="getTypeSeverity(data.alert_type)" />
            </template>
          </Column>

          <Column field="severity" header="Severity">
            <template #body="{ data }">
              <Tag :value="getSeverityLabel(data.alert_type)" :severity="getSeverityTag(data.alert_type)" />
            </template>
          </Column>

          <Column field="status" header="Status">
            <template #body="{ data }">
              <Tag
                :value="data.status"
                :severity="data.status === 'active' ? 'info' : data.status === 'acknowledged' ? 'warning' : 'success'"
              />
            </template>
          </Column>

          <Column field="current_quantity" header="Qty">
            <template #body="{ data }">
              {{ data.current_quantity ?? '—' }}
            </template>
          </Column>

          <Column field="reorder_point" header="Reorder Point">
            <template #body="{ data }">
              {{ data.reorder_point ?? '—' }}
            </template>
          </Column>

          <Column field="recommended_order_quantity" header="Recommended">
            <template #body="{ data }">
              {{ data.recommended_order_quantity ?? '—' }}
            </template>
          </Column>

          <Column field="created_at" header="Created">
            <template #body="{ data }">
              {{ formatDate(data.created_at) }}
            </template>
          </Column>

          <Column header="Actions" style="width: 150px">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button
                  v-if="data.status === 'active'"
                  icon="pi pi-check"
                  size="small"
                  text
                  severity="success"
                  @click="acknowledgeAlert(data.id)"
                  v-tooltip="'Acknowledge alert'"
                />
                <Button
                  v-if="data.status !== 'resolved'"
                  icon="pi pi-check-circle"
                  size="small"
                  text
                  severity="help"
                  @click="resolveAlert(data.id)"
                  v-tooltip="'Resolve alert'"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

const toast = useToast()
const loading = ref(false)
const alerts = ref<any[]>([])

const stats = reactive({
  active: 0,
  critical: 0,
  acknowledged: 0,
  resolved: 0
})

const filters = reactive({
  status: null as string | null,
  severity: null as string | null,
  search: ''
})

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Acknowledged', value: 'acknowledged' },
  { label: 'Resolved', value: 'resolved' }
]

const severityOptions = [
  { label: 'Warning', value: 'warning' },
  { label: 'Critical', value: 'critical' }
]

const getTypeSeverity = (type: string) => {
  if (type === 'low_stock') return 'warning'
  if (type === 'out_of_stock') return 'danger'
  if (type === 'overstock') return 'info'
  if (type === 'reorder_needed') return 'info'
  return 'secondary'
}

const getSeverityLabel = (type: string) => {
  if (type === 'out_of_stock') return 'critical'
  if (type === 'low_stock') return 'warning'
  if (type === 'overstock') return 'info'
  if (type === 'reorder_needed') return 'warning'
  return 'info'
}

const getSeverityTag = (type: string) => {
  if (type === 'out_of_stock') return 'danger'
  if (type === 'low_stock') return 'warning'
  if (type === 'overstock') return 'info'
  if (type === 'reorder_needed') return 'warning'
  return 'secondary'
}

const formatAlertType = (type?: string) => {
  if (!type) return 'Unknown'
  return type
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase())
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

const loadAlerts = async () => {
  loading.value = true
  try {
    const params: any = {}
    if (filters.status) params.status = filters.status
    params.per_page = 100

    const response = await axios.get('/api/inventory/alerts', { params })
    const payload = response.data?.data || {}
    alerts.value = payload?.data || payload || []

    // Load statistics
    const statsResponse = await axios.get('/api/inventory/alerts/summary')
    const summary = statsResponse.data?.data || {}
    stats.active = summary.total_active ?? summary.active ?? 0
    stats.critical = summary.out_of_stock ?? 0
    stats.acknowledged = summary.acknowledged ?? 0
    stats.resolved = summary.resolved ?? 0
  } catch (error) {
    console.error('Failed to load alerts', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load alerts',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const acknowledgeAlert = async (id: number) => {
  try {
    await axios.post(`/api/inventory/alerts/${id}/acknowledge`)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Alert acknowledged',
      life: 2000
    })
    loadAlerts()
  } catch (error) {
    console.error('Failed to acknowledge alert', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to acknowledge alert',
      life: 3000
    })
  }
}

const resolveAlert = async (id: number) => {
  try {
    await axios.post(`/api/inventory/alerts/${id}/resolve`)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Alert resolved',
      life: 2000
    })
    loadAlerts()
  } catch (error) {
    console.error('Failed to resolve alert', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to resolve alert',
      life: 3000
    })
  }
}

const resetFilters = () => {
  filters.status = null
  filters.severity = null
  filters.search = ''
  loadAlerts()
}

onMounted(() => {
  loadAlerts()
})

const filteredAlerts = computed(() => {
  let list = alerts.value

  if (filters.severity) {
    list = list.filter((alert: any) => {
      const label = getSeverityLabel(alert.alert_type)
      return label === filters.severity
    })
  }

  if (filters.search) {
    const search = filters.search.toLowerCase()
    list = list.filter((alert: any) => {
      const sku = (alert.product?.sku || '').toLowerCase()
      const name = (alert.product?.product_name || '').toLowerCase()
      return sku.includes(search) || name.includes(search)
    })
  }

  return list
})
</script>
