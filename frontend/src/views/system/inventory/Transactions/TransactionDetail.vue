<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-6xl mx-auto">
      <!-- Header -->
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-800">Transaction Details</h1>
            <p class="text-gray-600 mt-1">Reference: {{ transaction?.transaction_number }}</p>
          </div>
          <div class="flex gap-3">
            <Button
              label="Print"
              icon="pi pi-print"
              severity="info"
              @click="printTransaction"
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
      <div id="printable-content" v-else-if="transaction">
        <!-- Screen View -->
        <div class="space-y-6 screen-view">
          <!-- Transaction Overview -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
              <Card>
                <template #title>
                  <div class="flex items-center gap-2">
                    <i class="pi pi-info-circle text-blue-500"></i>
                    <span>Transaction Information</span>
                  </div>
                </template>
                <template #content>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Number</label>
                      <p class="text-gray-900 font-mono">{{ transaction.transaction_number }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                      <Tag 
                        :value="formatTransactionType(transaction.transaction_type)" 
                        :severity="getTransactionTypeSeverity(transaction.transaction_type)"
                        class="capitalize"
                      />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Date & Time</label>
                      <p class="text-gray-900">{{ formatFullDateTime(transaction.transaction_date) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                      <p class="text-gray-900">{{ transaction.branch?.name || 'N/A' }}</p>
                      <p v-if="transaction.related_branch" class="text-sm text-gray-500">
                        Related Branch: {{ transaction.related_branch.name }}
                      </p>
                    </div>
                    <div class="md:col-span-2">
                      <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                      <p class="text-gray-900">{{ transaction.notes || 'No notes provided' }}</p>
                    </div>
                  </div>
                </template>
              </Card>
            </div>

            <div>
              <Card>
                <template #title>
                  <div class="flex items-center gap-2">
                    <i class="pi pi-chart-line text-green-500"></i>
                    <span>Value Summary</span>
                  </div>
                </template>
                <template #content>
                  <div class="space-y-4">
                    <div class="text-center">
                      <span class="text-sm text-gray-600 block mb-1">Quantity Change</span>
                      <span 
                        :class="parseFloat(transaction.quantity_change) > 0 ? 'text-green-600' : 'text-red-600'"
                        class="text-3xl font-bold"
                      >
                        {{ parseFloat(transaction.quantity_change) > 0 ? '+' : '' }}{{ transaction.quantity_change }}
                      </span>
                      <div class="text-xs text-gray-500 mt-1">
                        Before: {{ transaction.quantity_before }} → After: {{ transaction.quantity_after }}
                      </div>
                    </div>
                    <div class="border-t pt-4">
                      <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Unit Cost:</span>
                        <span class="font-medium">₱{{ formatNumber(transaction.unit_cost) }}</span>
                      </div>
                      <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Total Value:</span>
                        <span class="font-bold text-lg text-blue-600">₱{{ formatNumber(transaction.total_value) }}</span>
                      </div>
                    </div>
                  </div>
                </template>
              </Card>
            </div>
          </div>

          <!-- Product Details -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-box text-purple-500"></i>
                <span>Product Information</span>
              </div>
            </template>
            <template #content>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex gap-4">
                  <div class="bg-gray-100 p-3 rounded-lg">
                    <i class="pi pi-image text-2xl text-gray-400"></i>
                  </div>
                  <div>
                    <h3 class="font-medium text-lg">{{ transaction.product?.product_name }}</h3>
                    <p class="text-sm text-gray-600">SKU: {{ transaction.product?.sku }}</p>
                    <p class="text-sm text-gray-600">Brand: {{ transaction.product?.brand || 'N/A' }}</p>
                  </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <p>{{ getCategoryName(transaction.product?.category_id) || 'N/A' }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Base Price</label>
                    <p>₱{{ formatNumber(transaction.product?.base_price) }}</p>
                  </div>
                </div>
              </div>

              <!-- Variation Details (if applicable) -->
              <div v-if="transaction.variation" class="mt-4 pt-4 border-t">
                <h4 class="font-medium mb-3">Variation Details</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <div>
                    <label class="block text-sm text-gray-600">Variation Name</label>
                    <p>{{ transaction.variation.variation_name }}</p>
                  </div>
                  <div>
                    <label class="block text-sm text-gray-600">SKU</label>
                    <p>{{ transaction.variation.variation_sku }}</p>
                  </div>
                  <div>
                    <label class="block text-sm text-gray-600">Color</label>
                    <div class="flex items-center gap-2">
                      <span 
                        class="w-4 h-4 rounded-full border" 
                        :style="{ backgroundColor: transaction.variation.color_hex || transaction.variation.color }"
                      ></span>
                      <span>{{ transaction.variation.color || 'N/A' }}</span>
                    </div>
                  </div>
                  <div>
                    <label class="block text-sm text-gray-600">Material</label>
                    <p>{{ transaction.variation.material || 'N/A' }}</p>
                  </div>
                </div>
              </div>
            </template>
          </Card>

          <!-- Reference Information -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-link text-orange-500"></i>
                <span>Reference Information</span>
              </div>
            </template>
            <template #content>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Reference Type</label>
                  <Tag :value="formatReferenceType(transaction.reference_type)" severity="info" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Reference ID</label>
                  <p class="text-gray-900 font-mono">{{ transaction.reference_id || 'N/A' }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Related Branch</label>
                  <p>{{ transaction.related_branch?.name || 'N/A' }}</p>
                </div>
              </div>
            </template>
          </Card>

          <!-- Created By Information -->
          <Card>
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-user text-gray-500"></i>
                <span>Created By</span>
              </div>
            </template>
            <template #content>
              <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                  <p>{{ transaction.created_by?.fname }} {{ transaction.created_by?.lname }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Employee Number</label>
                  <p class="font-mono">{{ transaction.created_by?.employee_number || 'N/A' }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                  <p>{{ transaction.created_by?.department || 'N/A' }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Created At</label>
                  <p>{{ formatFullDateTime(transaction.created_at) }}</p>
                </div>
              </div>
            </template>
          </Card>
        </div>

        <!-- Print View -->
        <div class="print-view">
          <!-- Header -->
          <div class="print-header">
            <div class="flex justify-between items-center mb-6">
              <div>
                <h1 class="text-2xl font-bold">INVENTORY TRANSACTION VOUCHER</h1>
                <p class="text-gray-600">{{ transaction.transaction_number }}</p>
              </div>
              <div class="text-right">
                <p class="text-sm">Date Printed: {{ new Date().toLocaleDateString() }}</p>
                <p class="text-sm">Time Printed: {{ new Date().toLocaleTimeString() }}</p>
              </div>
            </div>
          </div>

          <!-- Company/Branch Info -->
          <div class="print-company-info mb-6 p-4 border rounded">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <h3 class="font-bold">Branch Information</h3>
                <p>{{ transaction.branch?.name || 'N/A' }}</p>
                <p>{{ transaction.branch?.address || 'N/A' }}</p>
                <p>{{ transaction.branch?.city }}, {{ transaction.branch?.province }}</p>
                <p>Contact: {{ transaction.branch?.contact_number || 'N/A' }}</p>
              </div>
              <div class="text-right">
                <h3 class="font-bold">Transaction Type: 
                  <span :class="{
                    'text-green-600': transaction.transaction_type === 'purchase',
                    'text-red-600': transaction.transaction_type === 'sale',
                    'text-yellow-600': transaction.transaction_type === 'adjustment'
                  }">{{ formatTransactionType(transaction.transaction_type) }}</span>
                </h3>
                <p>Date: {{ formatDate(transaction.transaction_date) }}</p>
                <p>Time: {{ formatTime(transaction.transaction_date) }}</p>
              </div>
            </div>
          </div>

          <!-- Transaction Details Table -->
          <div class="print-details mb-6">
            <h3 class="font-bold mb-2">Transaction Details</h3>
            <table class="print-table">
              <tbody>
                <tr>
                  <td class="font-bold w-1/4">Product:</td>
                  <td>{{ transaction.product?.product_name }}</td>
                </tr>
                <tr>
                  <td class="font-bold">SKU:</td>
                  <td>{{ transaction.product?.sku }}</td>
                </tr>
                <tr v-if="transaction.variation">
                  <td class="font-bold">Variation:</td>
                  <td>{{ transaction.variation.variation_name }}</td>
                </tr>
                <tr>
                  <td class="font-bold">Quantity Change:</td>
                  <td :class="parseFloat(transaction.quantity_change) > 0 ? 'text-green-600' : 'text-red-600'">
                    {{ parseFloat(transaction.quantity_change) > 0 ? '+' : '' }}{{ transaction.quantity_change }}
                  </td>
                </tr>
                <tr>
                  <td class="font-bold">Before Quantity:</td>
                  <td>{{ transaction.quantity_before }}</td>
                </tr>
                <tr>
                  <td class="font-bold">After Quantity:</td>
                  <td>{{ transaction.quantity_after }}</td>
                </tr>
                <tr>
                  <td class="font-bold">Unit Cost:</td>
                  <td>₱{{ formatNumber(transaction.unit_cost) }}</td>
                </tr>
                <tr>
                  <td class="font-bold">Total Value:</td>
                  <td class="font-bold">₱{{ formatNumber(transaction.total_value) }}</td>
                </tr>
                <tr>
                  <td class="font-bold">Reference Type:</td>
                  <td>{{ formatReferenceType(transaction.reference_type) || 'N/A' }}</td>
                </tr>
                <tr>
                  <td class="font-bold">Reference ID:</td>
                  <td>{{ transaction.reference_id || 'N/A' }}</td>
                </tr>
                <tr v-if="transaction.notes">
                  <td class="font-bold">Notes:</td>
                  <td>{{ transaction.notes }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Signatures -->
          <div class="print-signatures mt-8 grid grid-cols-2 gap-4">
            <div>
              <p class="font-bold">Created By:</p>
              <div class="signature-line mt-8 border-t border-black pt-1">
                <p>{{ transaction.created_by?.fname }} {{ transaction.created_by?.lname }}</p>
                <p class="text-sm">Date: {{ formatDate(transaction.created_at) }}</p>
              </div>
            </div>
            <div v-if="transaction.approved_by">
              <p class="font-bold">Approved By:</p>
              <div class="signature-line mt-8 border-t border-black pt-1">
                <p>{{ transaction.approved_by?.fname }} {{ transaction.approved_by?.lname }}</p>
                <p class="text-sm">Date: {{ formatDate(transaction.approved_at) }}</p>
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
        <p class="text-gray-500">Transaction not found</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter, useRoute } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'

const loading = ref(false)
const transaction = ref<any>(null)
const categories = ref<any[]>([])
const toast = useToast()
const router = useRouter()
const route = useRoute()

const loadTransaction = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getTransaction(route.params.id as string)
    
    if (response.success) {
      transaction.value = response.data
      console.log('Transaction loaded:', transaction.value)
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Failed to load transaction details',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load transaction details',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadCategories = async () => {
  try {
    const response = await inventoryService.getCategories()
    if (response.success) {
      if (Array.isArray(response.data)) {
        categories.value = response.data
      } else if (response.data?.data) {
        categories.value = response.data.data
      }
    }
  } catch (error) {
    console.error('Failed to load categories', error)
  }
}

const getCategoryName = (categoryId: number) => {
  if (!categoryId || !categories.value.length) return null
  const category = categories.value.find(c => c.id === categoryId)
  return category?.category_name || category?.name || null
}

const printTransaction = () => {
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
  
  const printViewHtml = document.querySelector('.print-view')?.innerHTML || ''
  
  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>Transaction - ${transaction.value?.transaction_number}</title>
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
        .print-table td {
          border: 1px solid #ddd;
          padding: 8px;
        }
        .print-table .font-bold {
          font-weight: bold;
          background-color: #f5f5f5;
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
        .text-green-600 { color: #059669; }
        .text-red-600 { color: #dc2626; }
        .text-yellow-600 { color: #d97706; }
        .text-gray-600 { color: #4b5563; }
        .text-sm { font-size: 0.875rem; }
        .font-bold { font-weight: bold; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .mt-2 { margin-top: 0.5rem; }
        .mt-4 { margin-top: 1rem; }
        .mt-8 { margin-top: 2rem; }
        .p-4 { padding: 1rem; }
        .border { border: 1px solid #ddd; }
        .rounded { border-radius: 4px; }
        .w-1\\/4 { width: 25%; }
        @media print {
          body { margin: 0; }
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
  
  printWindow.onload = () => {
    printWindow.print()
  }
}

const formatTransactionType = (type: string) => {
  return type?.replace(/_/g, ' ') || 'N/A'
}

const formatReferenceType = (type: string) => {
  return type?.replace(/_/g, ' ') || 'N/A'
}

const getTransactionTypeSeverity = (type: string) => {
  switch (type) {
    case 'purchase':
    case 'receive':
      return 'success'
    case 'sale':
    case 'issue':
      return 'danger'
    case 'adjustment':
      return 'warning'
    case 'transfer':
      return 'info'
    case 'return':
      return 'secondary'
    default:
      return 'secondary'
  }
}

const formatDate = (dateString: string) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatTime = (dateString: string) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatFullDateTime = (dateString: string) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
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

const goBack = () => {
  router.push({ name: 'inventory.transactions' })
}

onMounted(async () => {
  await loadTransaction()
  await loadCategories()
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

.print-table td {
  padding: 0.5rem;
  border: 1px solid #e5e7eb;
}

.print-table .font-bold {
  background-color: #f9fafb;
}

.signature-line {
  min-width: 200px;
}
</style>