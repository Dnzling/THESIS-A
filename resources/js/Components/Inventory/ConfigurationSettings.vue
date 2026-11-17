<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Inventory Configuration</h1>
        <p class="text-gray-600 mt-1">Manage inventory settings and business rules</p>
      </div>
      <Button
        :label="saving ? 'Saving...' : 'Save Changes'"
        icon="pi pi-save"
        :loading="saving"
        @click="saveConfiguration"
      />
    </div>

    <!-- Configuration Sections -->
    <div class="space-y-6">
      <!-- Reorder Rules -->
      <Card>
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-refresh text-blue-600"></i>
            <span>Reorder Rules</span>
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Default Reorder Point
                  <span class="text-red-500">*</span>
                </label>
                <InputNumber
                  v-model="config.default_reorder_point"
                  :min="1"
                  placeholder="Enter quantity"
                  class="w-full"
                />
                <p class="text-xs text-gray-500 mt-1">Default minimum stock level for products</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Default Reorder Quantity
                  <span class="text-red-500">*</span>
                </label>
                <InputNumber
                  v-model="config.default_reorder_qty"
                  :min="1"
                  placeholder="Enter quantity"
                  class="w-full"
                />
                <p class="text-xs text-gray-500 mt-1">Default order quantity when stock is low</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Safety Stock Percentage
                  <span class="text-red-500">*</span>
                </label>
                <InputNumber
                  v-model="config.safety_stock_percentage"
                  :min="0"
                  :max="100"
                  placeholder="Enter percentage"
                  suffix="%"
                  class="w-full"
                />
                <p class="text-xs text-gray-500 mt-1">Buffer stock to maintain (% of reorder point)</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Maximum Stock Multiplier
                  <span class="text-red-500">*</span>
                </label>
                <InputNumber
                  v-model="config.max_stock_multiplier"
                  :min="1"
                  :max="10"
                  :step="0.1"
                  placeholder="Enter multiplier"
                  class="w-full"
                />
                <p class="text-xs text-gray-500 mt-1">Maximum threshold as a multiplier of reorder point</p>
              </div>
            </div>

            <Divider />

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <h4 class="font-semibold text-gray-900 mb-2">Hierarchical Rules Processing</h4>
              <p class="text-sm text-gray-700">
                The system applies reorder rules in this order:
              </p>
              <ol class="list-decimal list-inside text-sm text-gray-600 mt-2 space-y-1">
                <li><strong>Product Level:</strong> Specific thresholds for individual products</li>
                <li><strong>Category Level:</strong> Thresholds for product categories</li>
                <li><strong>Store Level:</strong> Default thresholds for the store</li>
              </ol>
            </div>
          </div>
        </template>
      </Card>

      <!-- Alert Thresholds -->
      <Card>
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-exclamation-circle text-red-600"></i>
            <span>Alert Thresholds</span>
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Low Stock Alert (% of Reorder Point)
                  <span class="text-red-500">*</span>
                </label>
                <InputNumber
                  v-model="config.low_stock_alert_percentage"
                  :min="10"
                  :max="100"
                  placeholder="Enter percentage"
                  suffix="%"
                  class="w-full"
                />
                <p class="text-xs text-gray-500 mt-1">Alert when stock falls below this % of reorder point</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Out of Stock Alert
                  <span class="text-red-500">*</span>
                </label>
                <InputNumber
                  v-model="config.out_of_stock_quantity"
                  :min="0"
                  placeholder="Enter quantity"
                  class="w-full"
                />
                <p class="text-xs text-gray-500 mt-1">Alert when stock reaches this quantity</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Overstock Alert (% of Maximum)
                  <span class="text-red-500">*</span>
                </label>
                <InputNumber
                  v-model="config.overstock_alert_percentage"
                  :min="100"
                  :max="200"
                  placeholder="Enter percentage"
                  suffix="%"
                  class="w-full"
                />
                <p class="text-xs text-gray-500 mt-1">Alert when stock exceeds this % of maximum stock</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Damaged Stock Alert (% of Total)
                </label>
                <InputNumber
                  v-model="config.damaged_stock_percentage"
                  :min="0"
                  :max="100"
                  placeholder="Enter percentage"
                  suffix="%"
                  class="w-full"
                />
                <p class="text-xs text-gray-500 mt-1">Alert when damaged stock exceeds this % of inventory</p>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Transfer Approval Settings -->
      <Card>
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-check-circle text-amber-600"></i>
            <span>Transfer Approval Settings</span>
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Require Finance Approval for Transfers Above Amount
              </label>
              <InputNumber
                v-model="config.finance_approval_threshold"
                :min="0"
                placeholder="Enter amount"
                prefix="$"
                class="w-full"
              />
              <p class="text-xs text-gray-500 mt-1">Set to 0 to disable finance approval</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Require Extended Lead Time (days)
                </label>
                <InputNumber
                  v-model="config.extended_lead_time"
                  :min="0"
                  :max="30"
                  placeholder="Enter days"
                  class="w-full"
                />
                <p class="text-xs text-gray-500 mt-1">Lead time for transfers between branches</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  <div class="flex items-center gap-2">
                    <Checkbox v-model="config.require_transfer_notes" :binary="true" />
                    <span>Require Notes on All Transfers</span>
                  </div>
                </label>
              </div>
            </div>

            <Divider />

            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
              <h4 class="font-semibold text-gray-900 mb-2">Multi-Approval Workflow</h4>
              <p class="text-sm text-gray-700 mb-3">
                Your store is configured with a multi-approval workflow:
              </p>
              <div class="space-y-2 text-sm">
                <div class="flex items-center gap-2">
                  <Badge value="1" severity="info" class="!w-6" />
                  <span><strong>Sender Approval:</strong> Warehouse manager approves the request</span>
                </div>
                <div class="flex items-center gap-2">
                  <Badge value="2" severity="warning" class="!w-6" />
                  <span><strong>Finance Approval:</strong> Finance manager approves transfers > threshold (conditional)</span>
                </div>
                <div class="flex items-center gap-2">
                  <Badge value="3" severity="success" class="!w-6" />
                  <span><strong>Shipment & Receipt:</strong> Final approval and confirmation</span>
                </div>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Notification Settings -->
      <Card>
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-bell text-green-600"></i>
            <span>Notification Settings</span>
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            <div class="space-y-3">
              <div class="flex items-center gap-2">
                <Checkbox v-model="config.notify_low_stock" :binary="true" />
                <label class="text-sm font-medium text-gray-700">Notify on Low Stock Alerts</label>
              </div>

              <div class="flex items-center gap-2">
                <Checkbox v-model="config.notify_transfer_requests" :binary="true" />
                <label class="text-sm font-medium text-gray-700">Notify on Transfer Requests</label>
              </div>

              <div class="flex items-center gap-2">
                <Checkbox v-model="config.notify_transfer_approval" :binary="true" />
                <label class="text-sm font-medium text-gray-700">Notify on Transfer Approvals</label>
              </div>

              <div class="flex items-center gap-2">
                <Checkbox v-model="config.notify_stock_received" :binary="true" />
                <label class="text-sm font-medium text-gray-700">Notify on Stock Received</label>
              </div>

              <div class="flex items-center gap-2">
                <Checkbox v-model="config.enable_email_notifications" :binary="true" />
                <label class="text-sm font-medium text-gray-700">Enable Email Notifications</label>
              </div>
            </div>

            <Divider />

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Notification Recipients (comma-separated emails)
              </label>
              <Textarea
                v-model="config.notification_recipients"
                placeholder="manager@store.com, admin@store.com"
                rows="3"
                class="w-full"
              />
            </div>
          </div>
        </template>
      </Card>

      <!-- Reporting Settings -->
      <Card>
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-chart-bar text-purple-600"></i>
            <span>Reporting Settings</span>
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Stock Aging Report Thresholds (days)
                </label>
                <div class="space-y-2">
                  <div>
                    <small class="text-gray-600">Warning Level (days):</small>
                    <InputNumber
                      v-model="config.stock_aging_warning"
                      :min="30"
                      placeholder="e.g., 90"
                      class="w-full mt-1"
                    />
                  </div>
                  <div>
                    <small class="text-gray-600">Critical Level (days):</small>
                    <InputNumber
                      v-model="config.stock_aging_critical"
                      :min="60"
                      placeholder="e.g., 180"
                      class="w-full mt-1"
                    />
                  </div>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Slow Mover Threshold
                </label>
                <div class="space-y-2">
                  <div>
                    <small class="text-gray-600">Units Sold (last period):</small>
                    <InputNumber
                      v-model="config.slow_mover_units"
                      :min="1"
                      placeholder="e.g., 5"
                      class="w-full mt-1"
                    />
                  </div>
                  <div>
                    <small class="text-gray-600">Period (days):</small>
                    <InputNumber
                      v-model="config.slow_mover_period"
                      :min="7"
                      placeholder="e.g., 90"
                      class="w-full mt-1"
                    />
                  </div>
                </div>
              </div>
            </div>

            <Divider />

            <div class="flex items-center gap-2">
              <Checkbox v-model="config.include_cost_in_reports" :binary="true" />
              <label class="text-sm font-medium text-gray-700">Include Cost Values in Reports</label>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Save Button at Bottom -->
    <div class="flex justify-end gap-2">
      <Button
        label="Reset to Defaults"
        severity="secondary"
        outlined
        @click="resetConfiguration"
      />
      <Button
        :label="saving ? 'Saving...' : 'Save Configuration'"
        icon="pi pi-save"
        :loading="saving"
        @click="saveConfiguration"
      />
    </div>

    <!-- Success Message -->
    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import axiosClient from '../../axios'

const toast = useToast()
const saving = ref(false)

const config = reactive({
  // Reorder Rules
  default_reorder_point: 50,
  default_reorder_qty: 100,
  safety_stock_percentage: 20,
  max_stock_multiplier: 3,

  // Alert Thresholds
  low_stock_alert_percentage: 50,
  out_of_stock_quantity: 0,
  overstock_alert_percentage: 150,
  damaged_stock_percentage: 5,

  // Transfer Approval
  finance_approval_threshold: 5000,
  extended_lead_time: 3,
  require_transfer_notes: true,

  // Notifications
  notify_low_stock: true,
  notify_transfer_requests: true,
  notify_transfer_approval: true,
  notify_stock_received: true,
  enable_email_notifications: false,
  notification_recipients: '',

  // Reporting
  stock_aging_warning: 90,
  stock_aging_critical: 180,
  slow_mover_units: 5,
  slow_mover_period: 90,
  include_cost_in_reports: true
})

const fetchConfiguration = async () => {
  try {
    const response = await axiosClient.get('/api/inventory/configuration')
    Object.assign(config, response.data.data)
  } catch (error) {
    console.error('Failed to fetch configuration:', error)
    toast.add({
      severity: 'info',
      summary: 'Using Defaults',
      detail: 'Default configuration loaded',
      life: 3000
    })
  }
}

const saveConfiguration = async () => {
  saving.value = true
  try {
    await axiosClient.put('/api/inventory/configuration', config)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Configuration saved successfully',
      life: 3000
    })
  } catch (error) {
    console.error('Failed to save configuration:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to save configuration',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

const resetConfiguration = async () => {
  const confirmed = confirm('Are you sure you want to reset to default values?')
  if (confirmed) {
    await fetchConfiguration()
    toast.add({
      severity: 'info',
      summary: 'Reset',
      detail: 'Configuration reset to saved values',
      life: 3000
    })
  }
}

onMounted(() => {
  fetchConfiguration()
})
</script>
