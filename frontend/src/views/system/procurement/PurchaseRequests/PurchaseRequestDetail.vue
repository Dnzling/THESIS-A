<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="mb-6 flex items-center gap-4">
      <Button
        icon="pi pi-arrow-left"
        text
        rounded
        severity="secondary"
        @click="router.back()"
        v-tooltip="'Go back'"
      />
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Stock Order Request Details</h1>
        <p class="text-gray-600 mt-1">View complete request information</p>
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
        <Card>
          <template #header>
            <div class="text-lg font-semibold text-gray-800 p-4 flex items-center gap-2">
              <i class="pi pi-box"></i>
              Product Information
            </div>
          </template>
          <template #content>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <div class="text-sm text-gray-600 mb-1">Product Name</div>
                <div class="text-lg font-semibold text-gray-800">{{ request.product?.product_name || 'N/A' }}</div>
              </div>
              <div>
                <div class="text-sm text-gray-600 mb-1">SKU</div>
                <div class="text-lg font-semibold text-gray-800">{{ request.product?.sku || 'N/A' }}</div>
              </div>
              <div>
                <div class="text-sm text-gray-600 mb-1">Quantity Requested</div>
                <div class="text-lg font-semibold text-gray-800">{{ request.requested_quantity }} units</div>
              </div>
              <div>
                <div class="text-sm text-gray-600 mb-1">Current Stock Level</div>
                <div class="text-lg font-semibold text-gray-800">{{ request.branch_inventory?.quantity_on_hand ?? 'N/A' }} units</div>
              </div>
            </div>
          </template>
        </Card>

        <!-- Request Details -->
        <Card>
          <template #header>
            <div class="text-lg font-semibold text-gray-800 p-4 flex items-center gap-2">
              <i class="pi pi-list"></i>
              Request Details
            </div>
          </template>
          <template #content>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <div class="text-sm text-gray-600 mb-1">Branch</div>
                <div class="text-lg font-semibold text-gray-800">{{ request.branch?.name || 'N/A' }}</div>
              </div>
              <div>
                <div class="text-sm text-gray-600 mb-1">Status</div>
                <Tag :value="getStatusLabel(request.status)" :severity="getStatusSeverity(request.status)" />
              </div>
            </div>

            <Divider class="my-6" />

            <div>
              <div class="text-sm text-gray-600 mb-2">Notes</div>
              <div class="text-gray-800 bg-gray-50 p-4 rounded-lg">{{ request.notes || 'No notes provided' }}</div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Status Card -->
        <Card>
          <template #header>
            <div class="text-lg font-semibold text-gray-800 p-4 flex items-center gap-2">
              <i class="pi pi-info-circle"></i>
              Status
            </div>
          </template>
          <template #content>
            <div class="space-y-4">
              <div class="text-center p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg">
                <div class="text-3xl font-bold mb-2">
                  <Tag :value="getStatusLabel(request.status)" :severity="getStatusSeverity(request.status)" />
                </div>
                <div class="text-sm text-gray-600">Current Status</div>
              </div>

              <div class="space-y-2">
                <div class="flex justify-between">
                  <span class="text-gray-600">Request ID:</span>
                  <span class="font-semibold">#{{ request.id }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Created:</span>
                  <span class="font-semibold">{{ formatDate(request.created_at) }}</span>
                </div>
              </div>

              <!-- Action Buttons -->
              <Divider />
              <div class="flex flex-col gap-2">
                <Button
                  label="Approve"
                  icon="pi pi-check"
                  class="w-full"
                  severity="success"
                  @click="approveRequest"
                  v-if="request.status === 'pending'"
                />
                <Button
                  label="Reject"
                  icon="pi pi-times"
                  class="w-full"
                  severity="danger"
                  @click="showRejectDialog = true"
                  v-if="request.status === 'pending'"
                />
                <Button
                  label="Create Purchase Order"
                  icon="pi pi-shopping-cart"
                  class="w-full"
                  severity="info"
                  @click="navigateToCreatePO"
                  v-if="request.status === 'approved'"
                />
                <Button
                  label="Edit"
                  icon="pi pi-pencil"
                  class="w-full"
                  severity="secondary"
                  @click="router.push({ name: 'stock-order-requests.create', query: { edit: request.id } })"
                  v-if="request.status === 'pending'"
                />
              </div>
            </div>
          </template>
        </Card>

        <!-- Quick Actions -->
        <Card>
          <template #header>
            <div class="text-lg font-semibold text-gray-800 p-4 flex items-center gap-2">
              <i class="pi pi-bolt"></i>
              Quick Actions
            </div>
          </template>
          <template #content>
            <div class="flex flex-col gap-2">
              <Button
                label="View Inventory"
                icon="pi pi-list"
                class="w-full"
                text
                @click="router.push({ name: 'inventory.items.index' })"
              />
              <Button
                label="Go to Procurement"
                icon="pi pi-shopping-bag"
                class="w-full"
                text
                @click="router.push({ name: 'procurement.purchase-orders' })"
              />
              <Button
                label="Back to List"
                icon="pi pi-arrow-left"
                class="w-full"
                text
                @click="router.back()"
              />
            </div>
          </template>
        </Card>
      </div>
    </div>

    <!-- Reject Dialog -->
    <Dialog v-model:visible="showRejectDialog" modal header="Reject Request" :style="{ width: '25rem' }">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason</label>
          <Textarea
            v-model="rejectionReason"
            rows="4"
            placeholder="Explain why this request is being rejected"
            class="w-full"
          />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" @click="showRejectDialog = false" />
        <Button label="Reject" severity="danger" @click="confirmReject" :loading="submitting" />
      </template>
    </Dialog>

    <Toast />
    <ConfirmDialog />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import procurementService from '../../../../services/procurement.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

const loading = ref(false)
const submitting = ref(false)
const error = ref('')
const request = ref<any>(null)
const showRejectDialog = ref(false)
const rejectionReason = ref('')

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

const formatDate = (date: string) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDateTime = (date: string) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
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

const approveRequest = () => {
  confirm.require({
    message: `Are you sure you want to approve this request for "${request.value?.product?.product_name}"?`,
    header: 'Confirm Approval',
    icon: 'pi pi-question-circle',
    acceptSeverity: 'success',
    accept: async () => {
      try {
        submitting.value = true
        await procurementService.approveStockOrderRequest(request.value.id)
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Request approved successfully',
          life: 3000
        })
        await loadRequest()
      } catch (err: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: err.message || 'Failed to approve request',
          life: 3000
        })
      } finally {
        submitting.value = false
      }
    }
  })
}

const confirmReject = async () => {
  if (!rejectionReason.value.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Please provide a rejection reason',
      life: 3000
    })
    return
  }

  try {
    submitting.value = true
    await procurementService.rejectStockOrderRequest(request.value.id, rejectionReason.value)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Request rejected successfully',
      life: 3000
    })
    showRejectDialog.value = false
    await loadRequest()
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.message || 'Failed to reject request',
      life: 3000
    })
  } finally {
    submitting.value = false
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
