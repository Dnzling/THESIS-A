<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'procurement.purchase-orders' })" />
        <div>
          <h2 class="text-2xl font-bold text-gray-800">Purchase Order Details</h2>
          <p class="text-sm text-gray-500 mt-1">Review and process purchase order</p>
        </div>
      </div>
      <Tag :value="detail?.status || 'draft'" :severity="statusSeverity(detail?.status || 'draft')" />
    </div>

    <div v-if="loading" class="space-y-4">
      <Skeleton height="220px" class="rounded-lg" />
      <Skeleton height="280px" class="rounded-lg" />
    </div>

    <div v-else class="space-y-6">
      <Card>
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <p class="text-xs text-gray-600">PO Number</p>
              <p class="font-semibold text-gray-900">{{ detail?.po_number || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Order Date</p>
              <p class="font-semibold text-gray-900">{{ detail?.order_date || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Expected Delivery</p>
              <p class="font-semibold text-gray-900">{{ detail?.expected_delivery_date || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Supplier</p>
              <p class="font-semibold text-gray-900">{{ detail?.supplier?.supplier_name || '-' }}</p>
            </div>
          </div>
        </template>
      </Card>

      <Card>
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-list text-blue-600"></i>
            <span>PO Items</span>
          </div>
        </template>
        <template #content>
          <DataTable :value="detail?.items || []" class="p-datatable-sm" stripedRows>
            <Column field="product_id" header="Product ID" />
            <Column field="quantity_ordered" header="Quantity" />
            <Column field="unit_cost" header="Unit Cost" />
            <Column field="line_total" header="Line Total" />
          </DataTable>

          <div class="pt-4 flex gap-2 justify-end">
            <Button label="Reject" icon="pi pi-times" severity="danger" outlined :loading="processing" @click="reject" />
            <Button label="Approve" icon="pi pi-check" severity="success" :loading="processing" @click="approve" />
            <Button label="Send" icon="pi pi-send" severity="info" :loading="processing" @click="send" />
            <Button label="Cancel" icon="pi pi-ban" severity="warning" :loading="processing" @click="cancel" />
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import procurementService from '../../../../services/procurement.service'

const route = useRoute()
const router = useRouter()
const poId = Number(route.params.id)

const loading = ref(false)
const processing = ref(false)
const detail = ref<any>(null)

const loadDetail = async () => {
  loading.value = true
  try {
    const response = await procurementService.getPurchaseOrder(poId)
    detail.value = response.data || null
  } catch (error) {
    console.error('Failed to load purchase order detail', error)
    detail.value = null
  } finally {
    loading.value = false
  }
}

const approve = async () => {
  processing.value = true
  try {
    await procurementService.approvePurchaseOrder(poId)
    await loadDetail()
  } finally {
    processing.value = false
  }
}

const reject = async () => {
  processing.value = true
  try {
    await procurementService.rejectPurchaseOrder(poId)
    await loadDetail()
  } finally {
    processing.value = false
  }
}

const send = async () => {
  processing.value = true
  try {
    await procurementService.sendPurchaseOrder(poId)
    await loadDetail()
  } finally {
    processing.value = false
  }
}

const cancel = async () => {
  processing.value = true
  try {
    await procurementService.cancelPurchaseOrder(poId)
    await loadDetail()
  } finally {
    processing.value = false
  }
}

const statusSeverity = (status: string) => {
  if (['approved', 'ordered', 'received'].includes(status)) return 'success'
  if (status === 'pending_approval') return 'info'
  if (['cancelled', 'rejected'].includes(status)) return 'danger'
  return 'secondary'
}

onMounted(() => {
  loadDetail()
})
</script>
