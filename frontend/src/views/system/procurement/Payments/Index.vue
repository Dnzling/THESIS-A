<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-800">Supplier Payments</h1>
      <p class="text-gray-600 mt-1">Manage payment approvals and processing</p>
    </div>

    <Card>
      <template #content>
        <DataTable :value="payments" :loading="loading" class="p-datatable-sm" stripedRows>
          <Column field="payment_number" header="Payment No." />
          <Column field="payment_date" header="Payment Date" />
          <Column field="payment_amount" header="Amount" />
          <Column field="payment_method" header="Method" />
          <Column field="status" header="Status">
            <template #body="{ data }">
              <Tag :value="data.status" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
          <Column header="Actions">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button icon="pi pi-check" text rounded severity="success" @click="approve(data.id)" />
                <Button icon="pi pi-cog" text rounded severity="info" @click="processPayment(data.id)" />
                <Button icon="pi pi-times" text rounded severity="danger" @click="reject(data.id)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import procurementService from '../../../../services/procurement.service'

const loading = ref(false)
const payments = ref<any[]>([])

const loadPayments = async () => {
  loading.value = true
  try {
    const response = await procurementService.getSupplierPayments()
    payments.value = response.data?.data || []
  } catch (error) {
    console.error('Failed to load supplier payments', error)
    payments.value = []
  } finally {
    loading.value = false
  }
}

const approve = async (id: number) => {
  try {
    await procurementService.approveSupplierPayment(id)
    await loadPayments()
  } catch (error) {
    console.error('Failed to approve payment', error)
  }
}

const reject = async (id: number) => {
  try {
    await procurementService.rejectSupplierPayment(id)
    await loadPayments()
  } catch (error) {
    console.error('Failed to reject payment', error)
  }
}

const processPayment = async (id: number) => {
  try {
    await procurementService.processSupplierPayment(id)
    await loadPayments()
  } catch (error) {
    console.error('Failed to process payment', error)
  }
}

const statusSeverity = (status: string) => {
  if (['approved', 'processing', 'completed'].includes(status)) return 'success'
  if (status === 'pending_approval') return 'info'
  if (['failed', 'cancelled'].includes(status)) return 'danger'
  return 'secondary'
}

onMounted(() => {
  loadPayments()
})
</script>
