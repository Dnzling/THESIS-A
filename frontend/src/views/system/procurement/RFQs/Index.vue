<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Request for Quotations</h1>
        <p class="text-gray-600 mt-1">Manage RFQ lifecycle and supplier responses</p>
      </div>
      <Button label="Create RFQ" icon="pi pi-plus" severity="success" @click="router.push({ name: 'procurement.rfqs.create' })" />
    </div>

    <Card>
      <template #content>
        <DataTable :value="rfqs" :loading="loading" class="p-datatable-sm" stripedRows>
          <Column field="rfq_number" header="RFQ No." />
          <Column field="title" header="Title" />
          <Column field="deadline_date" header="Deadline" />
          <Column field="status" header="Status">
            <template #body="{ data }">
              <Tag :value="data.status" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
          <Column header="Actions">
            <template #body="{ data }">
              <Button icon="pi pi-eye" text rounded severity="info" @click="router.push({ name: 'procurement.rfqs.detail', params: { id: data.id } })" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import procurementService from '../../../../services/procurement.service'

const router = useRouter()
const loading = ref(false)
const rfqs = ref<any[]>([])

const loadRFQs = async () => {
  loading.value = true
  try {
    const response = await procurementService.getRFQs()
    rfqs.value = response.data?.data || []
  } catch (error) {
    console.error('Failed to load RFQs', error)
    rfqs.value = []
  } finally {
    loading.value = false
  }
}

const statusSeverity = (status: string) => {
  if (status === 'awarded') return 'success'
  if (status === 'sent') return 'info'
  if (status === 'cancelled') return 'danger'
  return 'secondary'
}

onMounted(() => {
  loadRFQs()
})
</script>
