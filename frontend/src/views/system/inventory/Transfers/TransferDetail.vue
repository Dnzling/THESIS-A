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
              <p class="font-semibold text-gray-900">{{ detail?.from_name || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">To</p>
              <p class="font-semibold text-gray-900">{{ detail?.to_name || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Date</p>
              <p class="font-semibold text-gray-900">{{ detail?.transfer_date || '-' }}</p>
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
          <DataTable :value="detail?.items || []" class="p-datatable-sm" stripedRows>
            <Column field="item_name" header="Item" />
            <Column field="quantity" header="Quantity" />
            <Column field="remarks" header="Remarks" />
          </DataTable>

          <div v-if="canAction" class="pt-4 flex gap-2 justify-end">
            <Button v-if="canCancel" label="Cancel" icon="pi pi-times" severity="danger" outlined :loading="processing" @click="cancelTransfer" />
            <Button v-if="canShip" label="Ship" icon="pi pi-send" severity="info" :loading="processing" @click="shipTransfer" />
            <Button v-if="canReceive" label="Receive" icon="pi pi-check" severity="success" :loading="processing" @click="receiveTransfer" />
          </div>
        </template>
      </Card>
    </div>
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

const transferId = computed(() => Number(route.params.id))
const canAction = computed(() => ['approved', 'shipped'].includes(detail.value?.status))
const canCancel = computed(() => detail.value?.status === 'approved')
const canShip = computed(() => detail.value?.status === 'approved')
const canReceive = computed(() => detail.value?.status === 'shipped')

const loadDetail = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getTransfer(transferId.value)
    detail.value = response.data || null
  } catch (error) {
    console.error('Failed to load transfer detail', error)
    detail.value = null
  } finally {
    loading.value = false
  }
}

const shipTransfer = async () => {
  processing.value = true
  try {
    await inventoryService.shipTransfer(transferId.value)
    toast.add({ severity: 'success', summary: 'Success', detail: 'Transfer shipped successfully', life: 2000 })
    await loadDetail()
  } catch (error: any) {
    console.error('Failed to ship transfer', error)
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to ship transfer', life: 3000 })
  } finally {
    processing.value = false
  }
}

const receiveTransfer = async () => {
  processing.value = true
  try {
    await inventoryService.receiveTransfer(transferId.value)
    toast.add({ severity: 'success', summary: 'Success', detail: 'Transfer received successfully', life: 2000 })
    await loadDetail()
  } catch (error: any) {
    console.error('Failed to receive transfer', error)
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to receive transfer', life: 3000 })
  } finally {
    processing.value = false
  }
}

const cancelTransfer = async () => {
  processing.value = true
  try {
    await inventoryService.cancelTransfer(transferId.value)
    toast.add({ severity: 'success', summary: 'Success', detail: 'Transfer cancelled successfully', life: 2000 })
    await loadDetail()
  } catch (error: any) {
    console.error('Failed to cancel transfer', error)
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to cancel transfer', life: 3000 })
  } finally {
    processing.value = false
  }
}

const statusSeverity = (status: string) => {
  if (status === 'received') return 'success'
  if (status === 'shipped') return 'info'
  if (status === 'cancelled') return 'danger'
  if (status === 'approved') return 'help'
  return 'secondary'
}

onMounted(() => {
  loadDetail()
})
</script>
