<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Purchase Orders</h1>
        <p class="text-gray-600 mt-1">Manage purchase order execution and status</p>
      </div>
      <Button label="Create PO" icon="pi pi-plus" severity="success" @click="router.push({ name: 'procurement.purchase-orders.create' })" />
    </div>

    <Card>
      <template #content>
        <DataTable :value="orders" :loading="loading" class="p-datatable-sm" stripedRows>
          <Column field="po_number" header="PO No." />
          <Column field="order_date" header="Order Date" />
          <Column field="expected_delivery_date" header="Expected Delivery" />
          <Column field="status" header="Status">
            <template #body="{ data }">
              <Tag :value="data.status" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
          <Column header="Actions">
            <template #body="{ data }">
              <Button icon="pi pi-eye" text rounded severity="info" @click="router.push({ name: 'procurement.purchase-orders.detail', params: { id: data.id } })" />
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
const orders = ref<any[]>([])

const loadOrders = async () => {
  loading.value = true
  try {
    const response = await procurementService.getPurchaseOrders()
    orders.value = response.data?.data || []
  } catch (error) {
    console.error('Failed to load purchase orders', error)
    orders.value = []
  } finally {
    loading.value = false
  }
}

const statusSeverity = (status: string) => {
  if (['approved', 'ordered', 'received'].includes(status)) return 'success'
  if (status === 'pending_approval') return 'info'
  if (['cancelled', 'rejected'].includes(status)) return 'danger'
  return 'secondary'
}

onMounted(() => {
  loadOrders()
})
</script>
