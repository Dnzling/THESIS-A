<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'procurement.purchase-requisitions' })" />
        <div>
          <h2 class="text-2xl font-bold text-gray-800">Requisition Details</h2>
          <p class="text-sm text-gray-500 mt-1">Review and approve purchase requisition</p>
        </div>
      </div>
      <Tag :value="detail?.status || 'draft'" :severity="statusSeverity(detail?.status || 'draft')" />
    </div>

    <div v-if="loading" class="space-y-4">
      <Skeleton height="200px" class="rounded-lg" />
      <Skeleton height="280px" class="rounded-lg" />
    </div>

    <div v-else class="space-y-6">
      <Card>
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <p class="text-xs text-gray-600">PR Number</p>
              <p class="font-semibold text-gray-900">{{ detail?.pr_number || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Type</p>
              <p class="font-semibold text-gray-900">{{ detail?.requisition_type || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Required Date</p>
              <p class="font-semibold text-gray-900">{{ detail?.required_date || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Reason</p>
              <p class="font-semibold text-gray-900">{{ detail?.reason || '-' }}</p>
            </div>
          </div>
        </template>
      </Card>

      <Card>
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-list text-blue-600"></i>
            <span>Requested Items</span>
          </div>
        </template>
        <template #content>
          <DataTable :value="detail?.items || []" class="p-datatable-sm" stripedRows>
            <Column field="product_id" header="Product ID" />
            <Column field="variation_id" header="Variation ID" />
            <Column field="quantity_requested" header="Quantity" />
            <Column field="estimated_unit_cost" header="Est. Cost" />
          </DataTable>

          <div v-if="canAction" class="pt-4 flex gap-2 justify-end">
            <Button label="Reject" icon="pi pi-times" severity="danger" outlined :loading="processing" @click="reject" />
            <Button label="Approve" icon="pi pi-check" severity="success" :loading="processing" @click="approve" />
            <Button label="Convert" icon="pi pi-sync" severity="info" :loading="processing" @click="convert" />
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import procurementService from '../../../../services/procurement.service'

const route = useRoute()
const router = useRouter()
const requisitionId = Number(route.params.id)

const loading = ref(false)
const processing = ref(false)
const detail = ref<any>(null)
const canAction = computed(() => ['submitted'].includes(detail.value?.status))

const loadDetail = async () => {
  loading.value = true
  try {
    const response = await procurementService.getPurchaseRequisition(requisitionId)
    detail.value = response.data || null
  } catch (error) {
    console.error('Failed to load requisition detail', error)
    detail.value = null
  } finally {
    loading.value = false
  }
}

const approve = async () => {
  processing.value = true
  try {
    await procurementService.approvePurchaseRequisition(requisitionId)
    await loadDetail()
  } finally {
    processing.value = false
  }
}

const reject = async () => {
  processing.value = true
  try {
    await procurementService.rejectPurchaseRequisition(requisitionId)
    await loadDetail()
  } finally {
    processing.value = false
  }
}

const convert = async () => {
  processing.value = true
  try {
    await procurementService.convertPurchaseRequisition(requisitionId)
    await loadDetail()
  } finally {
    processing.value = false
  }
}

const statusSeverity = (status: string) => {
  if (status === 'approved') return 'success'
  if (status === 'submitted') return 'info'
  if (status === 'rejected') return 'danger'
  return 'secondary'
}

onMounted(() => {
  loadDetail()
})
</script>
