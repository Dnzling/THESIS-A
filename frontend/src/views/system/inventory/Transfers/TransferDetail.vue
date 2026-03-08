<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'inventory.transfers' })" />
        <div>
          <h2 class="text-2xl font-bold text-gray-800">Transfer Details</h2>
          <p class="text-sm text-gray-500 mt-1">Review and process stock transfer</p>
        </div>
      </div>
      <Tag :value="detail?.status || 'draft'" :severity="statusSeverity(detail?.status || 'draft')" />
    </div>

    <div v-if="loading" class="space-y-4">
      <Skeleton height="180px" class="rounded-lg" />
      <Skeleton height="250px" class="rounded-lg" />
    </div>

    <div v-else class="space-y-6">
      <Card>
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <p class="text-xs text-gray-600">Transfer No.</p>
              <p class="font-semibold text-gray-900">{{ detail?.transfer_no || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">From</p>
              <p class="font-semibold text-gray-900">{{ detail?.from_branch?.name || detail?.from_name || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">To</p>
              <p class="font-semibold text-gray-900">{{ detail?.to_branch?.name || detail?.to_name || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Date</p>
              <p class="font-semibold text-gray-900">{{ detail?.transfer_date || '-' }}</p>
            </div>
            <div v-if="detail?.expected_receive_date">
              <p class="text-xs text-gray-600">Expected Date</p>
              <p class="font-semibold text-gray-900">{{ detail.expected_receive_date }}</p>
            </div>
            <div v-if="detail?.created_by">
              <p class="text-xs text-gray-600">Created By</p>
              <p class="font-semibold text-gray-900">{{ getUserName(detail.created_by) }}</p>
            </div>
            <div v-if="detail?.remarks">
              <p class="text-xs text-gray-600">Remarks</p>
              <p class="font-semibold text-gray-900">{{ detail.remarks }}</p>
            </div>
          </div>
        </template>
      </Card>

      <Card>
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-list text-emerald-600"></i>
            <span>Transfer Items</span>
          </div>
        </template>
        <template #content>
          <DataTable :value="detail?.items || []" class="p-datatable-sm" stripedRows showGridlines>
            <template #empty>
              <div class="text-center py-6 text-gray-500">No items found</div>
            </template>
            <Column header="Product" style="width: 40%">
              <template #body="{ data }">
                <div class="flex flex-col">
                  <span class="font-medium">{{ data.product?.product_name || data.item_name || 'N/A' }}</span>
                  <span class="text-xs text-gray-500">SKU: {{ data.product?.sku || '-' }}</span>
                </div>
              </template>
            </Column>
            <Column field="quantity" header="Quantity" style="width: 15%" />
            <Column field="remarks" header="Remarks" style="width: 25%">
              <template #body="{ data }">{{ data.remarks || data.notes || '-' }}</template>
            </Column>
          </DataTable>

          <!-- Workflow Action Buttons -->
          <div v-if="canApprove || canShip || canReceive || canCancel" class="pt-4 flex flex-wrap gap-2 justify-end border-t border-gray-200">
            <Button
              v-if="canCancel"
              label="Cancel Transfer"
              icon="pi pi-times"
              severity="danger"
              outlined
              :loading="processing"
              @click="showCancelDialog = true"
            />
            <Button
              v-if="canApprove"
              label="Approve"
              icon="pi pi-check"
              severity="success"
              :loading="processing"
              @click="approveTransfer"
            />
            <Button
              v-if="canShip"
              label="Mark as Shipped"
              icon="pi pi-send"
              severity="info"
              :loading="processing"
              @click="shipTransfer"
            />
            <Button
              v-if="canReceive"
              label="Mark as Received"
              icon="pi pi-check-circle"
              severity="success"
              :loading="processing"
              @click="receiveTransfer"
            />
          </div>
        </template>
      </Card>
    </div>

    <!-- Cancel Confirmation Dialog -->
    <Dialog v-model:visible="showCancelDialog" header="Cancel Transfer" :modal="true" class="w-full sm:w-96">
      <div class="space-y-4">
        <p class="text-gray-600">Are you sure you want to cancel this transfer? This action cannot be undone.</p>
        <div class="flex flex-col gap-2">
          <label class="text-sm font-medium text-gray-700">Reason (optional)</label>
          <Textarea v-model="cancelRemarks" rows="3" placeholder="Enter cancellation reason..." />
        </div>
        <div class="flex justify-end gap-2">
          <Button label="Keep Transfer" severity="secondary" outlined @click="showCancelDialog = false" />
          <Button label="Cancel Transfer" severity="danger" :loading="processing" @click="cancelTransfer" />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '../../../../services/inventory.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const processing = ref(false)
const detail = ref<any>(null)
const showCancelDialog = ref(false)
const cancelRemarks = ref('')

const transferId = computed(() => Number(route.params.id))

const canApprove = computed(() => detail.value?.status === 'submitted' || detail.value?.status === 'draft')
const canShip = computed(() => detail.value?.status === 'approved')
const canReceive = computed(() => detail.value?.status === 'shipped')
const canCancel = computed(() => ['draft', 'submitted', 'approved'].includes(detail.value?.status))

const getUserName = (user: any) => {
  if (!user) return '-'
  return `${user.fname || user.first_name || ''} ${user.lname || user.last_name || ''}`.trim() || `User #${user.id}`
}

const loadDetail = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getTransfer(transferId.value)
    detail.value = response.data?.data || response.data || null
  } catch (error: any) {
    console.error('Failed to load transfer detail', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load transfer details',
      life: 3000
    })
    detail.value = null
  } finally {
    loading.value = false
  }
}

const approveTransfer = async () => {
  processing.value = true
  try {
    await inventoryService.approveTransfer(transferId.value)
    toast.add({ severity: 'success', summary: 'Approved', detail: 'Transfer approved successfully', life: 2000 })
    await loadDetail()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to approve transfer',
      life: 3000
    })
  } finally {
    processing.value = false
  }
}

const shipTransfer = async () => {
  processing.value = true
  try {
    await inventoryService.shipTransfer(transferId.value)
    toast.add({ severity: 'success', summary: 'Shipped', detail: 'Transfer marked as shipped', life: 2000 })
    await loadDetail()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to ship transfer',
      life: 3000
    })
  } finally {
    processing.value = false
  }
}

const receiveTransfer = async () => {
  processing.value = true
  try {
    await inventoryService.receiveTransfer(transferId.value)
    toast.add({ severity: 'success', summary: 'Received', detail: 'Transfer marked as received', life: 2000 })
    await loadDetail()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to receive transfer',
      life: 3000
    })
  } finally {
    processing.value = false
  }
}

const cancelTransfer = async () => {
  processing.value = true
  try {
    await inventoryService.cancelTransfer(transferId.value, cancelRemarks.value || undefined)
    toast.add({ severity: 'success', summary: 'Cancelled', detail: 'Transfer cancelled successfully', life: 2000 })
    showCancelDialog.value = false
    cancelRemarks.value = ''
    await loadDetail()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to cancel transfer',
      life: 3000
    })
  } finally {
    processing.value = false
  }
}

const statusSeverity = (status: string) => {
  const map: Record<string, string> = {
    draft: 'secondary',
    submitted: 'info',
    approved: 'help',
    shipped: 'warning',
    received: 'success',
    cancelled: 'danger'
  }
  return map[status] || 'secondary'
}

onMounted(() => {
  loadDetail()
})
</script>
