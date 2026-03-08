<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'procurement.rfqs' })" />
        <div>
          <h2 class="text-2xl font-bold text-gray-800">RFQ Details</h2>
          <p class="text-sm text-gray-500 mt-1">Review and process RFQ actions</p>
        </div>
      </div>
      <Tag :value="detail?.status || 'draft'" :severity="statusSeverity(detail?.status || 'draft')" />
    </div>

    <Card v-if="loading">
      <template #content>
        <Skeleton height="280px" class="rounded-lg" />
      </template>
    </Card>

    <Card v-else>
      <template #content>
        <div class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <p class="text-xs text-gray-600">RFQ No.</p>
              <p class="font-semibold text-gray-900">{{ detail?.rfq_number || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Title</p>
              <p class="font-semibold text-gray-900">{{ detail?.title || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Deadline</p>
              <p class="font-semibold text-gray-900">{{ detail?.deadline_date || '-' }}</p>
            </div>
          </div>

          <div class="pt-2 flex gap-2 justify-end">
            <Button label="Send" icon="pi pi-send" severity="info" :loading="processing" @click="send" />
            <Button label="Close" icon="pi pi-times" severity="warning" :loading="processing" @click="close" />
            <Button label="Award" icon="pi pi-check" severity="success" :loading="processing" @click="award" />
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import procurementService from '../../../../services/procurement.service'

const route = useRoute()
const router = useRouter()
const rfqId = Number(route.params.id)

const loading = ref(false)
const processing = ref(false)
const detail = ref<any>(null)

const loadDetail = async () => {
  loading.value = true
  try {
    const response = await procurementService.getRFQ(rfqId)
    detail.value = response.data || null
  } catch (error) {
    console.error('Failed to load RFQ detail', error)
    detail.value = null
  } finally {
    loading.value = false
  }
}

const send = async () => {
  processing.value = true
  try {
    await procurementService.sendRFQ(rfqId)
    await loadDetail()
  } finally {
    processing.value = false
  }
}

const close = async () => {
  processing.value = true
  try {
    await procurementService.closeRFQ(rfqId)
    await loadDetail()
  } finally {
    processing.value = false
  }
}

const award = async () => {
  processing.value = true
  try {
    await procurementService.awardRFQ(rfqId)
    await loadDetail()
  } finally {
    processing.value = false
  }
}

const statusSeverity = (status: string) => {
  if (status === 'awarded') return 'success'
  if (status === 'sent') return 'info'
  if (status === 'cancelled') return 'danger'
  return 'secondary'
}

onMounted(() => {
  loadDetail()
})
</script>
