<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-6xl mx-auto">
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-800">Stock Issue Details</h1>
            <p class="text-gray-600 mt-1">Reference: {{ stockIssue?.issue_number }}</p>
          </div>
          <div class="flex gap-3">
            <Button
              label="Print"
              icon="pi pi-print"
              severity="info"
              @click="printStockIssue"
              :disabled="loading"
            />
            <Button
              v-if="stockIssue?.status === 'draft'"
              label="Edit"
              icon="pi pi-pencil"
              severity="secondary"
              @click="editStockIssue"
              :disabled="loading"
            />
            <Button
              v-if="stockIssue?.status === 'draft'"
              label="Cancel Issue"
              icon="pi pi-times"
              severity="danger"
              @click="confirmCancel"
              :disabled="loading"
            />
            <Button
              label="Back to List"
              icon="pi pi-arrow-left"
              severity="secondary"
              @click="goBack"
              :disabled="loading"
            />
          </div>
        </div>
      </div>

      <div v-if="loading" class="flex justify-center py-12">
        <ProgressSpinner />
      </div>

      <!-- Printable Content -->
      <div id="printable-content" v-else-if="stockIssue">
        <!-- Regular View (Screen) -->
        <div class="space-y-6 screen-view">
          <!-- Issue Overview -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
              <Card>
                <template #title>Issue Information</template>
                <template #content>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Issue Number</label>
                      <p class="text-gray-900 font-mono">{{ stockIssue.issue_number }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                      <Tag
                        :value="stockIssue.issue_type"
                        :severity="getTypeSeverity(stockIssue.issue_type)"
                        class="capitalize"
                      />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                      <Tag
                        :value="stockIssue.status"
                        :severity="getStatusSeverity(stockIssue.status)"
                        class="capitalize"
                      />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Issue Date</label>
                      <p class="text-gray-900">{{ formatDate(stockIssue.issue_date) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                      <p class="text-gray-900">{{ stockIssue.branch?.name || 'N/A' }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Requested By</label>
                      <p class="text-gray-900">{{ stockIssue.requester?.full_name || 'N/A' }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Created By</label>
                      <p class="text-gray-900">{{ stockIssue.creator?.full_name || 'N/A' }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Created At</label>
                      <p class="text-gray-900">{{ formatDate(stockIssue.created_at) }}</p>
                    </div>
                    <div class="md:col-span-2">
                      <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                      <p class="text-gray-900">{{ stockIssue.description || 'No description provided' }}</p>
                    </div>
                    <div class="md:col-span-2" v-if="stockIssue.remarks">
                      <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                      <p class="text-gray-900">{{ stockIssue.remarks }}</p>
                    </div>
                    <div class="md:col-span-2" v-if="stockIssue.approval_notes">
                      <label class="block text-sm font-medium text-gray-700 mb-1">Approval Notes</label>
                      <p class="text-gray-900">{{ stockIssue.approval_notes }}</p>
                    </div>
                  </div>
                </template>
              </Card>
            </div>

            <div>
              <Card>
                <template #title>Cost Summary</template>
                <template #content>
                  <div class="space-y-4">
                    <div>
                      <div class="text-2xl font-bold text-blue-600">
                        {{ totalQuantity }}
                      </div>
                      <div class="text-sm text-gray-600">Total Items Issued</div>
                    </div>
                    <div>
                      <div class="text-2xl font-bold text-red-600">
                        ₱{{ formatNumber(stockIssue.total_value) }}
                      </div>
                      <div class="text-sm text-gray-600">Total Value</div>
                    </div>
                  </div>
                </template>
              </Card>

              <!-- Approval Information (if applicable) -->
              <Card class="mt-4" v-if="stockIssue.approver">
                <template #title>Approval Information</template>
                <template #content>
                  <div class="space-y-3">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Approved By</label>
                      <p class="text-gray-900">{{ stockIssue.approver?.full_name || 'N/A' }}</p>
                    </div>
                    <div v-if="stockIssue.approved_at">
                      <label class="block text-sm font-medium text-gray-700 mb-1">Approved At</label>
                      <p class="text-gray-900">{{ formatDate(stockIssue.approved_at) }}</p>
                    </div>
                  </div>
                </template>
              </Card>
            </div>
          </div>

          <!-- Items Table -->
          <Card>
            <template #title>Issued Items</template>
            <template #content>
              <DataTable
                :value="stockIssue.items || []"
                :loading="loading"
                tableStyle="min-width: 50rem"
                class="p-datatable-sm"
              >
                <Column field="inventory_item.product.sku" header="SKU" style="width: 120px">
                  <template #body="slotProps">
                    {{ slotProps.data.inventory_item?.product?.sku || 'N/A' }}
                  </template>
                </Column>
                <Column field="inventory_item.product.product_name" header="Product Name" style="min-width: 200px">
                  <template #body="slotProps">
                    {{ slotProps.data.inventory_item?.product?.product_name || 'N/A' }}
                  </template>
                </Column>
                <Column header="Location" style="width: 150px">
                  <template #body="slotProps">
                    <span v-if="slotProps.data.inventory_item">
                      {{ formatLocation(slotProps.data.inventory_item) }}
                    </span>
                    <span v-else>N/A</span>
                  </template>
                </Column>
                <Column field="quantity" header="Quantity" style="width: 100px">
                  <template #body="slotProps">
                    <span class="font-medium text-red-600">
                      -{{ slotProps.data.quantity }}
                    </span>
                  </template>
                </Column>
                <Column field="unit_cost" header="Unit Cost" style="width: 120px">
                  <template #body="slotProps">
                    ₱{{ formatNumber(slotProps.data.unit_cost) }}
                  </template>
                </Column>
                <Column field="total_value" header="Total Value" style="width: 120px">
                  <template #body="slotProps">
                    ₱{{ formatNumber(slotProps.data.total_value) }}
                  </template>
                </Column>
                <Column field="reason" header="Reason" style="min-width: 150px">
                  <template #body="slotProps">
                    <Tag
                      :value="formatReason(slotProps.data.reason)"
                      :severity="getReasonSeverity(slotProps.data.reason)"
                      class="capitalize"
                    />
                  </template>
                </Column>
                <Column field="remarks" header="Remarks" style="min-width: 150px">
                  <template #body="slotProps">
                    {{ slotProps.data.remarks || 'N/A' }}
                  </template>
                </Column>
              </DataTable>
            </template>
          </Card>

          <!-- Stock Movement History (if available) -->
          <Card v-if="stockMovements.length > 0">
            <template #title>Stock Movement History</template>
            <template #content>
              <DataTable
                :value="stockMovements"
                :loading="stockMovementsLoading"
                paginator
                :rows="5"
                :rowsPerPageOptions="[5, 10]"
                tableStyle="min-width: 50rem"
                class="p-datatable-sm"
              >
                <Column field="type" header="Movement Type" style="width: 150px">
                  <template #body="slotProps">
                    <Tag
                      :value="slotProps.data.type"
                      :severity="getMovementTypeSeverity(slotProps.data.type)"
                      class="capitalize"
                    />
                  </template>
                </Column>
                <Column field="quantity" header="Quantity" style="width: 100px">
                  <template #body="slotProps">
                    <span :class="slotProps.data.quantity > 0 ? 'text-green-600' : 'text-red-600'">
                      {{ slotProps.data.quantity > 0 ? '+' : '' }}{{ slotProps.data.quantity }}
                    </span>
                  </template>
                </Column>
                <Column field="reference" header="Reference" style="width: 150px">
                  <template #body="slotProps">
                    {{ slotProps.data.reference_number || 'N/A' }}
                  </template>
                </Column>
                <Column field="notes" header="Notes" />
                <Column field="created_at" header="Date" style="width: 150px">
                  <template #body="slotProps">
                    {{ formatDate(slotProps.data.created_at) }}
                  </template>
                </Column>
              </DataTable>
            </template>
          </Card>

          <!-- Serial Numbers (if applicable) -->
          <Card v-if="stockIssue.items?.some(item => item.inventory_item?.product?.track_serial_numbers) && serialNumbers.length > 0">
            <template #title>Serial Numbers Issued</template>
            <template #content>
              <DataTable
                :value="serialNumbers"
                :loading="serialNumbersLoading"
                paginator
                :rows="10"
                :rowsPerPageOptions="[5, 10, 25]"
                tableStyle="min-width: 50rem"
                class="p-datatable-sm"
              >
                <Column field="serial_number" header="Serial Number" style="width: 200px" />
                <Column field="batch_number" header="Batch" style="width: 150px" />
                <Column field="status" header="Status" style="width: 120px">
                  <template #body="slotProps">
                    <Tag
                      :value="slotProps.data.status"
                      :severity="getSerialStatusSeverity(slotProps.data.status)"
                      class="capitalize"
                    />
                  </template>
                </Column>
                <Column field="location" header="Location" />
                <Column field="created_at" header="Created" style="width: 150px">
                  <template #body="slotProps">
                    {{ formatDate(slotProps.data.created_at) }}
                  </template>
                </Column>
              </DataTable>
            </template>
          </Card>
        </div>

        <!-- Print View (Hidden on screen) -->
        <div class="print-view">
          <!-- Header -->
          <div class="print-header">
            <div class="flex justify-between items-center mb-6">
              <div>
                <h1 class="text-2xl font-bold">STOCK ISSUE VOUCHER</h1>
                <p class="text-gray-600">{{ stockIssue.issue_number }}</p>
              </div>
              <div class="text-right">
                <p class="text-sm">Date Printed: {{ new Date().toLocaleDateString() }}</p>
                <p class="text-sm">Time Printed: {{ new Date().toLocaleTimeString() }}</p>
              </div>
            </div>
          </div>

          <!-- Company Info -->
          <div class="print-company-info mb-6 p-4 border rounded">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <h3 class="font-bold">Branch Information</h3>
                <p>{{ stockIssue.branch?.name || 'N/A' }}</p>
                <p>{{ stockIssue.branch?.address || 'N/A' }}</p>
                <p>{{ stockIssue.branch?.city }}, {{ stockIssue.branch?.province }}</p>
                <p>Contact: {{ stockIssue.branch?.contact_number || 'N/A' }}</p>
              </div>
              <div class="text-right">
                <h3 class="font-bold">Status: 
                  <span :class="{
                    'text-green-600': stockIssue.status === 'approved',
                    'text-yellow-600': stockIssue.status === 'pending',
                    'text-blue-600': stockIssue.status === 'draft',
                    'text-red-600': stockIssue.status === 'cancelled'
                  }">{{ stockIssue.status?.toUpperCase() }}</span>
                </h3>
                <p>Issue Type: {{ stockIssue.issue_type?.toUpperCase() }}</p>
                <p>Issue Date: {{ formatDate(stockIssue.issue_date) }}</p>
              </div>
            </div>
          </div>

          <!-- Details -->
          <div class="print-details mb-6">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p><strong>Requested By:</strong> {{ stockIssue.requester?.full_name || 'N/A' }}</p>
                <p><strong>Created By:</strong> {{ stockIssue.creator?.full_name || 'N/A' }}</p>
                <p><strong>Created At:</strong> {{ formatDate(stockIssue.created_at) }}</p>
              </div>
              <div>
                <p><strong>Description:</strong> {{ stockIssue.description || 'N/A' }}</p>
                <p><strong>Remarks:</strong> {{ stockIssue.remarks || 'N/A' }}</p>
                <p v-if="stockIssue.approval_notes"><strong>Approval Notes:</strong> {{ stockIssue.approval_notes }}</p>
              </div>
            </div>
          </div>

          <!-- Items Table (Print) -->
          <div class="print-items mb-6">
            <h3 class="font-bold mb-2">Issued Items</h3>
            <table class="print-table">
              <thead>
                <tr>
                  <th>SKU</th>
                  <th>Product Name</th>
                  <th>Location</th>
                  <th>Qty</th>
                  <th>Unit Cost</th>
                  <th>Total Value</th>
                  <th>Reason</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in stockIssue.items" :key="item.id">
                  <td>{{ item.inventory_item?.product?.sku || 'N/A' }}</td>
                  <td>{{ item.inventory_item?.product?.product_name || 'N/A' }}</td>
                  <td>{{ formatLocation(item.inventory_item) }}</td>
                  <td class="text-right">{{ item.quantity }}</td>
                  <td class="text-right">₱{{ formatNumber(item.unit_cost) }}</td>
                  <td class="text-right">₱{{ formatNumber(item.total_value) }}</td>
                  <td>{{ formatReason(item.reason) }}</td>
                </tr>
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="3" class="text-right font-bold">Totals:</td>
                  <td class="text-right font-bold">{{ totalQuantity }}</td>
                  <td></td>
                  <td class="text-right font-bold">₱{{ formatNumber(stockIssue.total_value) }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- Signatures -->
          <div class="print-signatures mt-8 grid grid-cols-3 gap-4">
            <div>
              <p class="font-bold">Requested By:</p>
              <div class="signature-line mt-8 border-t border-black pt-1">
                <p>{{ stockIssue.requester?.full_name || '_________________' }}</p>
                <p class="text-sm">Date: {{ new Date().toLocaleDateString()}}</p>
              </div>
            </div>
            <div>
              <p class="font-bold">Approved By:</p>
              <div class="signature-line mt-8 border-t border-black pt-1">
                <p>{{ stockIssue.approver?.full_name || '_________________' }}</p>
                <p class="text-sm">Date: {{ stockIssue.approved_at ? formatDate(stockIssue.approved_at) : '_________________' }}</p>
              </div>
            </div>
            <div>
              <p class="font-bold">Received By:</p>
              <div class="signature-line mt-8 border-t border-black pt-1">
                <p>_________________</p>
                <p class="text-sm">Date: _________________</p>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="print-footer mt-8 text-center text-sm text-gray-600">
            <p>This is a system-generated document. No signature required if printed electronically.</p>
            <p>Page 1 of 1</p>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-12">
        <p class="text-gray-500">Stock issue not found</p>
      </div>
    </div>
  </div>

  <!-- Cancel Confirmation Dialog -->
  <Dialog
    v-model:visible="cancelDialog"
    modal
    header="Confirm Cancellation"
    :style="{ width: '450px' }"
  >
    <div class="flex items-center gap-3">
      <i class="pi pi-exclamation-triangle text-orange-500 text-2xl"></i>
      <div>
        <p class="font-medium">Are you sure you want to cancel this stock issue?</p>
        <p class="text-sm text-gray-600 mt-1">
          Reference: <strong>{{ stockIssue?.issue_number }}</strong>
        </p>
        <p class="text-sm text-gray-600 mt-1">
          This will reverse the stock reduction and mark the issue as cancelled.
        </p>
      </div>
    </div>
    <template #footer>
      <Button
        label="No, Keep It"
        severity="secondary"
        @click="cancelDialog = false"
      />
      <Button
        label="Yes, Cancel Issue"
        severity="danger"
        @click="cancelStockIssue"
        :loading="cancelLoading"
      />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter, useRoute } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'

const loading = ref(true)
const cancelLoading = ref(false)
const cancelDialog = ref(false)
const stockMovementsLoading = ref(false)
const serialNumbersLoading = ref(false)
const stockIssue = ref<any>(null)
const stockMovements = ref<any[]>([])
const serialNumbers = ref<any[]>([])
const toast = useToast()
const router = useRouter()
const route = useRoute()

const totalQuantity = computed(() => {
  if (!stockIssue.value?.items) return 0
  return stockIssue.value.items.reduce((sum: number, item: any) => sum + item.quantity, 0)
})

const loadStockIssue = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getStockIssue(route.params.id as string)
    if (response.success) {
      stockIssue.value = response.data
      console.log('Stock issue loaded:', stockIssue.value)
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Failed to load stock issue details',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load stock issue details',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadStockMovements = async () => {
  if (!stockIssue.value?.items?.length) return

  stockMovementsLoading.value = true
  try {
    const response = await inventoryService.getProductStockMovements(
      stockIssue.value.items[0].inventory_item?.product_id, 
      {
        reference: stockIssue.value.issue_number
      }
    )
    if (response.success) {
      stockMovements.value = response.data || []
    }
  } catch (error) {
    console.error('Failed to load stock movements', error)
  } finally {
    stockMovementsLoading.value = false
  }
}

const loadSerialNumbers = async () => {
  const hasSerialTrackedItems = stockIssue.value?.items?.some(
    (item: any) => item.inventory_item?.product?.track_serial_numbers
  )
  
  if (!hasSerialTrackedItems) return

  serialNumbersLoading.value = true
  try {
    const response = await inventoryService.getSerialNumbers({
      reference: stockIssue.value.issue_number
    })
    if (response.success) {
      serialNumbers.value = response.data || []
    }
  } catch (error) {
    console.error('Failed to load serial numbers', error)
  } finally {
    serialNumbersLoading.value = false
  }
}

const printStockIssue = () => {
  const printContent = document.getElementById('printable-content')
  const originalTitle = document.title
  
  // Create a new window for printing
  const printWindow = window.open('', '_blank')
  if (!printWindow) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Please allow pop-ups to print',
      life: 3000
    })
    return
  }
  
  // Get the print view HTML
  const printViewHtml = document.querySelector('.print-view')?.innerHTML || ''
  
  // Write to the new window
  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>Stock Issue - ${stockIssue.value?.issue_number}</title>
      <style>
        body {
          font-family: Arial, sans-serif;
          margin: 0;
          padding: 20px;
          color: #333;
        }
        .print-container {
          max-width: 1200px;
          margin: 0 auto;
        }
        .print-header {
          margin-bottom: 20px;
        }
        .print-company-info {
          border: 1px solid #ddd;
          padding: 15px;
          margin-bottom: 20px;
          border-radius: 4px;
        }
        .print-table {
          width: 100%;
          border-collapse: collapse;
          margin-bottom: 20px;
        }
        .print-table th,
        .print-table td {
          border: 1px solid #ddd;
          padding: 8px;
          text-align: left;
        }
        .print-table th {
          background-color: #f5f5f5;
          font-weight: bold;
        }
        .print-table tfoot {
          font-weight: bold;
          background-color: #f9f9f9;
        }
        .print-signatures {
          margin-top: 40px;
        }
        .signature-line {
          margin-top: 40px;
        }
        .print-footer {
          margin-top: 40px;
          padding-top: 20px;
          border-top: 1px solid #ddd;
          text-align: center;
          font-size: 12px;
          color: #666;
        }
        .grid {
          display: grid;
          gap: 1rem;
        }
        .grid-cols-2 {
          grid-template-columns: repeat(2, 1fr);
        }
        .grid-cols-3 {
          grid-template-columns: repeat(3, 1fr);
        }
        .flex {
          display: flex;
        }
        .justify-between {
          justify-content: space-between;
        }
        .items-center {
          align-items: center;
        }
        .text-right {
          text-align: right;
        }
        .text-center {
          text-align: center;
        }
        .font-bold {
          font-weight: bold;
        }
        .mb-2 {
          margin-bottom: 0.5rem;
        }
        .mb-4 {
          margin-bottom: 1rem;
        }
        .mb-6 {
          margin-bottom: 1.5rem;
        }
        .mt-2 {
          margin-top: 0.5rem;
        }
        .mt-4 {
          margin-top: 1rem;
        }
        .mt-8 {
          margin-top: 2rem;
        }
        .p-4 {
          padding: 1rem;
        }
        .border {
          border: 1px solid #ddd;
        }
        .rounded {
          border-radius: 4px;
        }
        .text-green-600 { color: #059669; }
        .text-yellow-600 { color: #d97706; }
        .text-blue-600 { color: #2563eb; }
        .text-red-600 { color: #dc2626; }
        .text-gray-600 { color: #4b5563; }
        .text-sm { font-size: 0.875rem; }
        @media print {
          body { margin: 0; }
          .no-print { display: none; }
        }
      </style>
    </head>
    <body>
      <div class="print-container">
        ${printViewHtml}
      </div>
    </body>
    </html>
  `)
  
  printWindow.document.close()
  
  // Wait for content to load then print
  printWindow.onload = () => {
    printWindow.print()
  }
}

const formatLocation = (inventoryItem: any) => {
  if (!inventoryItem) return 'N/A'
  const parts = []
  if (inventoryItem.warehouse_section) parts.push(inventoryItem.warehouse_section)
  if (inventoryItem.aisle) parts.push(inventoryItem.aisle)
  if (inventoryItem.rack) parts.push(inventoryItem.rack)
  if (inventoryItem.shelf) parts.push(inventoryItem.shelf)
  return parts.join('-') || inventoryItem.bin_code || 'N/A'
}

const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'approved':
    case 'completed': 
      return 'success'
    case 'pending': 
      return 'warning'
    case 'draft': 
      return 'info'
    case 'cancelled': 
      return 'danger'
    default: 
      return 'secondary'
  }
}

const getTypeSeverity = (type: string) => {
  switch (type) {
    case 'expired': 
    case 'damaged': 
      return 'danger'
    case 'lost': 
      return 'warning'
    case 'internal_use': 
      return 'info'
    case 'sample': 
      return 'success'
    default: 
      return 'secondary'
  }
}

const getReasonSeverity = (reason: string) => {
  switch (reason) {
    case 'quality_issue':
    case 'damaged':
      return 'danger'
    case 'expired':
      return 'warning'
    case 'internal_use':
      return 'info'
    default:
      return 'secondary'
  }
}

const getMovementTypeSeverity = (type: string) => {
  switch (type) {
    case 'in': 
    case 'receive':
      return 'success'
    case 'out': 
    case 'issue':
      return 'danger'
    case 'adjustment': 
      return 'warning'
    case 'transfer': 
      return 'info'
    default: 
      return 'secondary'
  }
}

const getSerialStatusSeverity = (status: string) => {
  switch (status) {
    case 'available': 
      return 'success'
    case 'sold':
    case 'issued': 
      return 'info'
    case 'damaged': 
      return 'danger'
    case 'returned': 
      return 'warning'
    default: 
      return 'secondary'
  }
}

const formatReason = (reason: string) => {
  return reason?.replace(/_/g, ' ') || 'N/A'
}

const formatDate = (date: string) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatNumber = (value: string | number | null | undefined) => {
  if (value === null || value === undefined) return '0.00'
  return parseFloat(value.toString()).toFixed(2)
}

const editStockIssue = () => {
  router.push({ name: 'inventory.stock-issues.edit', params: { id: stockIssue.value.id } })
}

const confirmCancel = () => {
  cancelDialog.value = true
}

const cancelStockIssue = async () => {
  if (!stockIssue.value) return

  cancelLoading.value = true
  try {
    const response = await inventoryService.cancelStockIssue(stockIssue.value.id)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Stock issue cancelled successfully',
        life: 3000
      })
      cancelDialog.value = false
      await loadStockIssue()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to cancel stock issue',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to cancel stock issue',
      life: 3000
    })
  } finally {
    cancelLoading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'inventory.stock-issues' })
}

onMounted(async () => {
  await loadStockIssue()
  if (stockIssue.value) {
    loadStockMovements()
    loadSerialNumbers()
  }
})
</script>

<style scoped>
@media print {
  .screen-view {
    display: none;
  }
  
  .print-view {
    display: block !important;
    padding: 0;
    margin: 0;
  }
}

@media screen {
  .print-view {
    display: none;
  }
}

.print-table th,
.print-table td {
  padding: 0.5rem;
  border: 1px solid #e5e7eb;
}

.print-table th {
  background-color: #f9fafb;
  font-weight: 600;
}

.print-table tfoot {
  background-color: #f9fafb;
  font-weight: 600;
}

.signature-line {
  min-width: 200px;
}
</style>