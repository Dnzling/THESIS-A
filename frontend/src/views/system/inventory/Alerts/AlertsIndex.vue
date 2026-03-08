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
              <p class="text-sm text-gray-600">Critical</p>
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
        <DataTable
          :value="alerts"
          :loading="loading"
          paginator
          :rows="10"
          :totalRecords="alerts.length"
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

          <Column field="inventory_item.product.sku" header="SKU">
            <template #body="{ data }">
              {{ data.inventory_item?.product?.sku || 'N/A' }}
            </template>
          </Column>

          <Column field="inventory_item.product.product_name" header="Item Name">
            <template #body="{ data }">
              {{ data.inventory_item?.product?.product_name || 'N/A' }}
            </template>
          </Column>

          <Column field="type" header="Alert Type">
            <template #body="{ data }">
              <Tag :value="data.type" :severity="getTypeSeverity(data.type)" />
            </template>
          </Column>

          <Column field="severity" header="Severity">
            <template #body="{ data }">
              <Tag :value="data.severity" :severity="data.severity === 'critical' ? 'danger' : 'warning'" />
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

          <Column field="created_at" header="Created">
            <template #body="{ data }">
              {{ formatDate(data.created_at) }}
            </template>
          </Column>

          <Column header="Actions" style="width: 120px">
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
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import inventoryService from '../../../../services/inventory.service'

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
  return 'secondary'
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

const computeStats = (alertList: any[]) => {
  stats.active = alertList.filter(a => a.status === 'active').length
  stats.critical = alertList.filter(a => a.severity === 'critical').length
  stats.acknowledged = alertList.filter(a => a.status === 'acknowledged').length
  stats.resolved = alertList.filter(a => a.status === 'resolved').length
}

const loadAlerts = async () => {
  loading.value = true
  try {
    const params: any = {}
    if (filters.status) params.status = filters.status
    if (filters.severity) params.severity = filters.severity
    if (filters.search) params.search = filters.search

    const response = await inventoryService.getAlerts(params)
    const alertList = response?.data || []
    alerts.value = alertList

    // Try to load summary stats from dedicated endpoint, fall back to computing locally
    try {
      const summaryResponse = await inventoryService.getAlertSummary()
      const summaryData = summaryResponse?.data || summaryResponse || {}
      stats.active = summaryData.active ?? alertList.filter((a: any) => a.status === 'active').length
      stats.critical = summaryData.critical ?? alertList.filter((a: any) => a.severity === 'critical').length
      stats.acknowledged = summaryData.acknowledged ?? alertList.filter((a: any) => a.status === 'acknowledged').length
      stats.resolved = summaryData.resolved ?? alertList.filter((a: any) => a.status === 'resolved').length
    } catch {
      computeStats(alertList)
    }
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
    await inventoryService.acknowledgeAlert(id)
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

const resetFilters = () => {
  filters.status = null
  filters.severity = null
  filters.search = ''
  loadAlerts()
}

onMounted(() => {
  loadAlerts()
})
</script>
