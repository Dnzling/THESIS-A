<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Purchase Requisitions</h1>
        <p class="text-gray-600 mt-1">Track and process requisition requests</p>
      </div>
      <Button label="Create Requisition" icon="pi pi-plus" severity="success" @click="router.push({ name: 'procurement.purchase-requisitions.create' })" />
    </div>

    <Card>
      <template #content>
        <DataTable :value="requisitions" :loading="loading" class="p-datatable-sm" stripedRows>
          <Column field="pr_number" header="PR No." />
          <Column field="required_date" header="Required Date" />
          <Column field="requisition_type" header="Type" />
          <Column field="status" header="Status">
            <template #body="{ data }">
              <Tag :value="data.status" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
          <Column header="Actions">
            <template #body="{ data }">
              <Button icon="pi pi-eye" text rounded severity="info" @click="router.push({ name: 'procurement.purchase-requisitions.detail', params: { id: data.id } })" />
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
const requisitions = ref<any[]>([])

const loadRequisitions = async () => {
  loading.value = true
  try {
    const response = await procurementService.getPurchaseRequisitions()
    requisitions.value = response.data?.data || []
  } catch (error) {
    console.error('Failed to load requisitions', error)
    requisitions.value = []
  } finally {
    loading.value = false
  }
}

const statusSeverity = (status: string) => {
  if (status === 'approved') return 'success'
  if (status === 'submitted') return 'info'
  if (status === 'rejected') return 'danger'
  return 'secondary'
}

onMounted(() => {
  loadRequisitions()
})
</script>
