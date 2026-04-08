<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Alert Management</h1>
        <p class="text-gray-600 mt-1">Monitor and manage inventory alerts</p>
      </div>
      <div class="flex gap-2">
        <Button
          :label="`Refresh`"
          icon="pi pi-refresh"
          @click="fetchAlerts"
          :loading="loading"
        />
        <Button
          label="Configure Rules"
          icon="pi pi-cog"
          severity="secondary"
          @click="showConfigModal = true"
        />
      </div>
    </div>

    <!-- Alert Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <Card class="hover:shadow-lg transition-shadow">
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Active Alerts</p>
              <p class="text-3xl font-bold text-red-600 mt-1">{{ stats.active }}</p>
            </div>
            <i class="pi pi-exclamation-circle text-4xl text-red-200"></i>
          </div>
        </template>
      </Card>

      <Card class="hover:shadow-lg transition-shadow">
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Critical</p>
              <p class="text-3xl font-bold text-orange-600 mt-1">{{ stats.critical }}</p>
            </div>
            <i class="pi pi-bell text-4xl text-orange-200"></i>
          </div>
        </template>
      </Card>

      <Card class="hover:shadow-lg transition-shadow">
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Acknowledged</p>
              <p class="text-3xl font-bold text-blue-600 mt-1">{{ stats.acknowledged }}</p>
            </div>
            <i class="pi pi-check-circle text-4xl text-blue-200"></i>
          </div>
        </template>
      </Card>

      <Card class="hover:shadow-lg transition-shadow">
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Resolved</p>
              <p class="text-3xl font-bold text-green-600 mt-1">{{ stats.resolved }}</p>
            </div>
            <i class="pi pi-check text-4xl text-green-200"></i>
          </div>
        </template>
      </Card>
    </div>

    <!-- Filters -->
    <Card>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <Dropdown
              v-model="filters.status"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="All statuses"
              class="w-full"
              @change="fetchAlerts"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Severity</label>
            <Dropdown
              v-model="filters.severity"
              :options="severityOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="All severities"
              class="w-full"
              @change="fetchAlerts"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Alert Type</label>
            <Dropdown
              v-model="filters.type"
              :options="typeOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="All types"
              class="w-full"
              @change="fetchAlerts"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Product/SKU</label>
            <InputText
              v-model="filters.search"
              placeholder="Search..."
              class="w-full"
              @keyup.enter="fetchAlerts"
            />
          </div>
        </div>
      </template>
    </Card>

    <!-- Alerts Table -->
    <Card>
      <template #title>Recent Alerts</template>
      <template #content>
        <DataTable
          :value="alerts"
          :loading="loading"
          class="p-datatable-sm"
          stripedRows
          responsiveLayout="scroll"
          :paginator="true"
          :rows="10"
          :totalRecords="totalRecords"
          :lazy="true"
          @page="onPageChange"
        >
          <template #empty>
            <div class="text-center py-8">
              <i class="pi pi-inbox text-4xl text-gray-400 mb-2"></i>
              <p class="text-gray-600">No alerts found</p>
            </div>
          </template>

          <Column field="sku" header="SKU" style="width: 10%">
            <template #body="{ data }">
              <code class="text-xs bg-gray-100 px-2 py-1 rounded">{{ data.sku }}</code>
            </template>
          </Column>

          <Column field="product_name" header="Product" style="width: 20%" sortable>
            <template #body="{ data }">
              <div class="font-medium text-gray-900">{{ data.product_name }}</div>
              <div class="text-xs text-gray-500">{{ data.branch_name }}</div>
            </template>
          </Column>

          <Column field="alert_type" header="Type" style="width: 12%">
            <template #body="{ data }">
              <Tag :value="data.alert_type" :severity="getTypeSeverity(data.alert_type)" />
            </template>
          </Column>

          <Column field="severity" header="Severity" style="width: 10%">
            <template #body="{ data }">
              <Tag :value="data.severity" :severity="getSeveritySeverity(data.severity)" />
            </template>
          </Column>

          <Column header="Stock Info" style="width: 15%">
            <template #body="{ data }">
              <div class="text-sm">
                <div><span class="font-medium">Current:</span> {{ data.current_stock }}</div>
                <div><span class="font-medium">Reorder:</span> {{ data.reorder_point }}</div>
              </div>
            </template>
          </Column>

          <Column field="status" header="Status" style="width: 10%">
            <template #body="{ data }">
              <Badge :value="data.status" :severity="getStatusSeverity(data.status)" />
            </template>
          </Column>

          <Column header="Actions" style="width: 16%" :frozen="true" alignFrozen="right">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button
                  v-if="data.status === 'active'"
                  icon="pi pi-check"
                  severity="warning"
                  text
                  rounded
                  @click="acknowledgeAlert(data)"
                  v-tooltip="'Acknowledge'"
                />
                <Button
                  v-if="data.status !== 'resolved'"
                  icon="pi pi-check-circle"
                  severity="success"
                  text
                  rounded
                  @click="resolveAlert(data)"
                  v-tooltip="'Resolve'"
                />
                <Button
                  icon="pi pi-eye"
                  severity="info"
                  text
                  rounded
                  @click="viewAlertDetails(data)"
                  v-tooltip="'View Details'"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Config Modal -->
    <Dialog v-model:visible="showConfigModal" header="Configure Alert Rules" :modal="true" style="width: 90vw; max-width: 600px">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Low Stock Threshold</label>
          <InputNumber
            v-model="config.low_stock_threshold"
            :min="1"
            :max="100"
            placeholder="Enter percentage"
            suffix="%"
            class="w-full"
          />
          <p class="text-xs text-gray-500 mt-1">Alert when stock falls below this percentage of reorder point</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Out of Stock Threshold</label>
          <InputNumber
            v-model="config.out_of_stock_threshold"
            :min="0"
            :max="10"
            placeholder="Enter quantity"
            class="w-full"
          />
          <p class="text-xs text-gray-500 mt-1">Alert when stock equals or falls below this value</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Overstock Threshold</label>
          <InputNumber
            v-model="config.overstock_threshold"
            :min="100"
            :max="500"
            placeholder="Enter percentage"
            suffix="%"
            class="w-full"
          />
          <p class="text-xs text-gray-500 mt-1">Alert when stock exceeds this percentage of maximum stock</p>
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" @click="showConfigModal = false" text />
        <Button label="Save Configuration" @click="saveConfiguration" />
      </template>
    </Dialog>

    <!-- Details Dialog -->
    <Dialog v-model:visible="showDetailsDialog" header="Alert Details" :modal="true" style="width: 90vw; max-width: 700px">
      <div v-if="selectedAlert" class="space-y-6">
        <!-- Alert Info -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-gray-500">Product SKU</label>
            <p class="text-lg font-semibold text-gray-900 mt-1">{{ selectedAlert.sku }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">Product Name</label>
            <p class="text-lg font-semibold text-gray-900 mt-1">{{ selectedAlert.product_name }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">Branch</label>
            <p class="text-lg font-semibold text-gray-900 mt-1">{{ selectedAlert.branch_name }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">Alert Type</label>
            <Tag :value="selectedAlert.alert_type" :severity="getTypeSeverity(selectedAlert.alert_type)" class="mt-1" />
          </div>
        </div>

        <!-- Stock Info -->
        <div class="bg-gray-50 p-4 rounded-lg">
          <h4 class="font-semibold text-gray-900 mb-3">Stock Information</h4>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
              <p class="text-xs text-gray-500">Current Stock</p>
              <p class="text-2xl font-bold text-gray-900">{{ selectedAlert.current_stock }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Reorder Point</p>
              <p class="text-2xl font-bold text-blue-600">{{ selectedAlert.reorder_point }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Maximum Stock</p>
              <p class="text-2xl font-bold text-green-600">{{ selectedAlert.maximum_stock }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Safety Stock</p>
              <p class="text-2xl font-bold text-amber-600">{{ selectedAlert.safety_stock }}</p>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">Action Notes</label>
          <Textarea
            v-model="actionNotes"
            placeholder="Add notes about the action taken..."
            rows="4"
            class="w-full"
          />
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" @click="showDetailsDialog = false" text />
        <Button
          v-if="selectedAlert?.status === 'active'"
          label="Acknowledge"
          severity="warning"
          @click="confirmAcknowledge"
        />
        <Button
          v-if="selectedAlert?.status !== 'resolved'"
          label="Resolve"
          severity="success"
          @click="confirmResolve"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import axiosClient from '../../axios'

interface Alert {
  id: number
  sku: string
  product_name: string
  branch_name: string
  alert_type: string
  severity: string
  current_stock: number
  reorder_point: number
  maximum_stock: number
  safety_stock: number
  status: string
}

const loading = ref(false)
const alerts = ref<Alert[]>([])
const totalRecords = ref(0)
const stats = reactive({ active: 0, critical: 0, acknowledged: 0, resolved: 0 })

const filters = reactive({
  status: null,
  severity: null,
  type: null,
  search: ''
})

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Acknowledged', value: 'acknowledged' },
  { label: 'Resolved', value: 'resolved' }
]

const severityOptions = [
  { label: 'Critical', value: 'critical' },
  { label: 'High', value: 'high' },
  { label: 'Medium', value: 'medium' },
  { label: 'Low', value: 'low' }
]

const typeOptions = [
  { label: 'Low Stock', value: 'low_stock' },
  { label: 'Out of Stock', value: 'out_of_stock' },
  { label: 'Overstock', value: 'overstock' },
  { label: 'Expiring Soon', value: 'expiring_soon' }
]

const showConfigModal = ref(false)
const showDetailsDialog = ref(false)
const selectedAlert = ref<Alert | null>(null)
const actionNotes = ref('')

const config = reactive({
  low_stock_threshold: 30,
  out_of_stock_threshold: 0,
  overstock_threshold: 150
})

// Get severity styling
const getTypeSeverity = (type: string): string => {
  const severities: { [key: string]: string } = {
    low_stock: 'warning',
    out_of_stock: 'danger',
    overstock: 'info',
    expiring_soon: 'warning'
  }
  return severities[type] || 'info'
}

const getSeveritySeverity = (severity: string): string => {
  const severities: { [key: string]: string } = {
    critical: 'danger',
    high: 'warning',
    medium: 'info',
    low: 'success'
  }
  return severities[severity] || 'info'
}

const getStatusSeverity = (status: string): string => {
  const severities: { [key: string]: string } = {
    active: 'danger',
    acknowledged: 'warning',
    resolved: 'success'
  }
  return severities[status] || 'info'
}

const fetchAlerts = async (page = 0) => {
  loading.value = true
  try {
    const response = await axiosClient.get('/api/inventory/alert-management', {
      params: {
        page: page + 1,
        status: filters.status,
        severity: filters.severity,
        type: filters.type,
        search: filters.search
      }
    })
    alerts.value = response.data.data
    totalRecords.value = response.data.meta?.total || 0
    await fetchStats()
  } catch (error) {
    console.error('Failed to fetch alerts:', error)
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  try {
    const response = await axiosClient.get('/api/inventory/alert-management/statistics')
    stats.active = response.data.active || 0
    stats.critical = response.data.critical || 0
    stats.acknowledged = response.data.acknowledged || 0
    stats.resolved = response.data.resolved || 0
  } catch (error) {
    console.error('Failed to fetch stats:', error)
  }
}

const acknowledgeAlert = async (alert: Alert) => {
  try {
    await axiosClient.post(`/api/inventory/alert-management/${alert.id}/acknowledge`, {
      notes: 'Alert acknowledged'
    })
    alert.status = 'acknowledged'
    stats.acknowledged++
    stats.active = Math.max(0, stats.active - 1)
  } catch (error) {
    console.error('Failed to acknowledge alert:', error)
  }
}

const resolveAlert = async (alert: Alert) => {
  try {
    await axiosClient.post(`/api/inventory/alert-management/${alert.id}/resolve`, {
      notes: 'Alert resolved'
    })
    alert.status = 'resolved'
    stats.resolved++
    stats.active = Math.max(0, stats.active - 1)
  } catch (error) {
    console.error('Failed to resolve alert:', error)
  }
}

const viewAlertDetails = (alert: Alert) => {
  selectedAlert.value = alert
  actionNotes.value = ''
  showDetailsDialog.value = true
}

const confirmAcknowledge = async () => {
  if (selectedAlert.value) {
    await acknowledgeAlert(selectedAlert.value)
    showDetailsDialog.value = false
  }
}

const confirmResolve = async () => {
  if (selectedAlert.value) {
    await resolveAlert(selectedAlert.value)
    showDetailsDialog.value = false
  }
}

const saveConfiguration = async () => {
  try {
    await axiosClient.put('/api/inventory/configuration', config)
    showConfigModal.value = false
  } catch (error) {
    console.error('Failed to save configuration:', error)
  }
}

const onPageChange = (event: any) => {
  fetchAlerts(event.page)
}

onMounted(() => {
  fetchAlerts()
})
</script>
