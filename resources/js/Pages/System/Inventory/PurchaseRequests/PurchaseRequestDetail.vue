<template>
  <div class="bg-slate-50 min-h-screen p-6">
    <div class="mb-6 flex items-center gap-4">
      <Button icon="pi pi-arrow-left" text rounded severity="secondary" @click="router.back()" v-tooltip="'Go back'" />
      <div>
        <h1 class="text-2xl font-semibold text-slate-800">Stock Order Request Details</h1>
        <p class="text-sm text-slate-500">Request summary and inventory context</p>
      </div>
    </div>
  
    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-12">
      <ProgressSpinner />
    </div>
  
    <!-- Error State -->
    <Message v-if="error" severity="error" :text="error" class="mb-6" />
  
    <!-- Content -->
    <div v-if="request && !loading" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Details -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Product Section -->
        <Card class="border border-slate-200 shadow-sm">
          <template #header>
            <div class="text-sm font-semibold text-slate-700 uppercase tracking-wide p-4 border-b border-slate-200">
              Product Information
            </div>
          </template>
          <template #content>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <div class="text-xs text-slate-500 mb-1">Product Name</div>
                <div class="text-lg font-semibold text-slate-800">{{ request.product?.product_name || 'N/A' }}</div>
              </div>
              <div>
                <div class="text-xs text-slate-500 mb-1">SKU</div>
                <div class="text-lg font-semibold text-slate-800">{{ request.product?.sku || 'N/A' }}</div>
              </div>
              <div>
                <div class="text-xs text-slate-500 mb-1">Brand</div>
                <div class="text-lg font-semibold text-slate-800">{{ request.product?.brand || 'N/A' }}</div>
              </div>
              <div>
                <div class="text-xs text-slate-500 mb-1">Collection</div>
                <div class="text-lg font-semibold text-slate-800">{{ request.product?.collection_name || 'N/A' }}</div>
              </div>
              <div>
                <div class="text-xs text-slate-500 mb-1">Quantity Requested</div>
                <div class="text-lg font-semibold text-slate-800">{{ request.requested_quantity }} units</div>
              </div>
              <div>
                <div class="text-xs text-slate-500 mb-1">Current Stock Level</div>
                <div class="text-lg font-semibold text-slate-800">{{ request.branch_inventory?.quantity_on_hand ?? 'N/A' }}
                  units</div>
              </div>
              <div>
                <div class="text-xs text-slate-500 mb-1">Base Price</div>
                <div class="text-lg font-semibold text-slate-800">{{ request.product?.base_price || 'N/A' }}</div>
              </div>
              <div>
                <div class="text-xs text-slate-500 mb-1">Dimensions</div>
                <div class="text-lg font-semibold text-slate-800">
                  {{ request.product ? `${request.product.length_cm} x ${request.product.width_cm} x ${request.product.height_cm} cm` : 'N/A' }}
                </div>
              </div>
            </div>
          </template>
        </Card>
  
        <!-- Request Details -->
        <Card class="border border-slate-200 shadow-sm">
          <template #header>
            <div class="text-sm font-semibold text-slate-700 uppercase tracking-wide p-4 border-b border-slate-200">
              Request Details
            </div>
          </template>
          <template #content>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <div class="text-xs text-slate-500 mb-1">Branch</div>
                <div class="text-lg font-semibold text-slate-800">{{ request.branch?.name || 'N/A' }}</div>
              </div>
              <div>
                <div class="text-xs text-slate-500 mb-1">Status</div>
                <Tag :value="getStatusLabel(request.status)" :severity="getStatusSeverity(request.status)" />
              </div>
              <div>
                <div class="text-xs text-slate-500 mb-1">Created By</div>
                <div class="text-lg font-semibold text-slate-800">
                  {{ request.created_by ? `${request.created_by.fname} ${request.created_by.lname}` : 'N/A' }}
                </div>
                <div class="text-xs text-slate-500" v-if="request.created_by?.department">
                  {{ request.created_by.department }}
                </div>
              </div>
              <div>
                <div class="text-xs text-slate-500 mb-1">Approved By</div>
                <div class="text-lg font-semibold text-slate-800">
                  {{ request.approved_by ? `${request.approved_by.fname} ${request.approved_by.lname}` : 'Not approved' }}
                </div>
                <div class="text-xs text-slate-500" v-if="request.approved_by?.department">
                  {{ request.approved_by.department }}
                </div>
              </div>
            </div>
  
            <Divider class="my-6" />
  
            <div>
              <div class="text-xs text-slate-500 mb-2">Notes</div>
              <div class="text-slate-800 bg-slate-50 p-4 rounded-lg border border-slate-200">{{ request.notes || 'No notes provided' }}</div>
            </div>
          </template>
        </Card>

        <!-- Inventory Snapshot -->
        <Card class="border border-slate-200 shadow-sm">
          <template #header>
            <div class="text-sm font-semibold text-slate-700 uppercase tracking-wide p-4 border-b border-slate-200">
              Inventory Snapshot
            </div>
          </template>
          <template #content>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <div class="text-xs text-slate-500 mb-1">Stock Status</div>
                <div class="text-lg font-semibold text-slate-800">{{ request.branch_inventory?.stock_status || 'N/A' }}</div>
              </div>
              <div>
                <div class="text-xs text-slate-500 mb-1">Quantity Available</div>
                <div class="text-lg font-semibold text-slate-800">{{ request.branch_inventory?.quantity_available ?? 'N/A' }}</div>
              </div>
              <div>
                <div class="text-xs text-slate-500 mb-1">Reorder Point</div>
                <div class="text-lg font-semibold text-slate-800">{{ request.branch_inventory?.reorder_point ?? 'N/A' }}</div>
              </div>
              <div>
                <div class="text-xs text-slate-500 mb-1">Reorder Quantity</div>
                <div class="text-lg font-semibold text-slate-800">{{ request.branch_inventory?.reorder_quantity ?? 'N/A' }}</div>
              </div>
            </div>
          </template>
        </Card>
      </div>
  
      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Status Card -->
        <Card class="border border-slate-200 shadow-sm">
          <template #header>
            <div class="text-sm font-semibold text-slate-700 uppercase tracking-wide p-4 border-b border-slate-200">
              Status
            </div>
          </template>
          <template #content>
            <div class="space-y-4">
              <div class="text-center p-4 bg-slate-50 border border-slate-200 rounded-lg">
                <div class="text-2xl font-semibold mb-2 text-slate-800">
                  <Tag :value="getStatusLabel(request.status)" :severity="getStatusSeverity(request.status)" />
                </div>
                <div class="text-xs text-slate-500 uppercase tracking-wide">Current Status</div>
              </div>
  
              <div class="space-y-2">
                <div class="flex justify-between">
                  <span class="text-slate-500">Request ID:</span>
                  <span class="font-semibold text-slate-800">#{{ request.id }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-500">Created:</span>
                  <span class="font-semibold text-slate-800">{{ formatDate(request.created_at) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-500">Updated:</span>
                  <span class="font-semibold text-slate-800">{{ formatDate(request.updated_at) }}</span>
                </div>
              </div>

              <Divider />
              <div class="space-y-3">
                <div class="text-xs text-slate-500 uppercase tracking-wide">Status Report</div>
                <div class="flex items-center justify-between">
                  <span class="text-slate-600">RFQ</span>
                  <Tag :value="getRfqStatus(request)" :severity="getRfqSeverity(request)" />
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-slate-600">Purchase Order</span>
                  <Tag :value="getPoStatus(request)" :severity="getPoSeverity(request)" />
                </div>
              </div>

              <!-- Action Buttons -->
              <Divider />
              <div class="flex flex-col gap-2">
                <Button
                  label="Edit"
                  icon="pi pi-pencil"
                  class="w-full"
                  severity="secondary"
                  @click="router.push({ name: 'stock-order-requests.edit', params: { id: request.id } })"
                  v-if="request.status === 'pending'"
                />
                <Button label="Create Purchase Order" icon="pi pi-shopping-cart" class="w-full" severity="info"
                  @click="navigateToCreatePO" v-if="request.status === 'approved'" />
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>
  
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import procurementService from '../../../../services/procurement.service'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const error = ref('')
const request = ref<any>(null)

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    pending: 'Pending',
    approved: 'Approved',
    converted_to_po: 'Converted to PO',
    rejected: 'Rejected',
    cancelled: 'Cancelled',
    partially_ordered: 'Partially Ordered'
  }
  return labels[status] ?? status
}

const getStatusSeverity = (status: string) => {
  const severities: Record<string, string> = {
    pending: 'warning',
    approved: 'success',
    converted_to_po: 'info',
    rejected: 'danger',
    cancelled: 'secondary',
    partially_ordered: 'info'
  }
  return severities[status] ?? 'secondary'
}

const getRfqCount = (req: any) => {
  if (Array.isArray(req?.rfqs)) return req.rfqs.length
  if (req?.rfq) return 1
  return 0
}

const getPoCount = (req: any) => {
  if (Array.isArray(req?.purchase_orders)) return req.purchase_orders.length
  if (req?.purchase_order) return 1
  return 0
}

const getRfqStatus = (req: any) => (getRfqCount(req) > 0 ? 'Created' : 'Not Created')
const getRfqSeverity = (req: any) => (getRfqCount(req) > 0 ? 'success' : 'secondary')
const getPoStatus = (req: any) => (getPoCount(req) > 0 ? 'Created' : 'Not Created')
const getPoSeverity = (req: any) => (getPoCount(req) > 0 ? 'success' : 'secondary')

const formatDate = (date: string) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}


const loadRequest = async () => {
  loading.value = true
  error.value = ''
  try {
    const id = route.params.id as string
    const response = await procurementService.getStockOrderRequest(parseInt(id))
    request.value = response?.data || null

    if (!request.value) {
      error.value = 'Stock order request not found'
    }
  } catch (err: any) {
    console.error('Failed to load request:', err)
    error.value = err.message || 'Failed to load request details'
  } finally {
    loading.value = false
  }
}

const navigateToCreatePO = () => {
  router.push({
    name: 'procurement.purchase-orders.create',
    query: {
      from_request: request.value.id,
      product_id: request.value.product?.id || request.value.branch_inventory?.product_id
    }
  })
}

onMounted(() => {
  loadRequest()
})
</script>
